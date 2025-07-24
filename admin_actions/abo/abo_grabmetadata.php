<?php
// abo_grabmetadata.php - Extract website metadata, contact info, and business details
// Part of the Automation Business Onboarding (ABO) system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get company ID - support both encoded and raw for debugging
$specific_company_id = null;

if (isset($_GET['rawid'])) {
    // Debug mode - use raw ID directly
    $specific_company_id = intval($_GET['rawid']);
} elseif (isset($_GET['id'])) {
    // Production mode - decode the ID
    $encoded_id = $_GET['id'];
    $specific_company_id = $qik->decodeID($encoded_id);
}

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processor' => 'abo_grabmetadata',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => []
];

try {
    // Get companies to process
    if ($specific_company_id) {
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.company_id = :company_id 
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabmetadata'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending metadata collection
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabmetadata'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending metadata collection';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    
    try {
        $database->beginTransaction();
        
        // Update progress to in_progress
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'in_progress', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabmetadata'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Fetch the company website
        $ch = curl_init($company['company_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $metadata_found = [];
        
        if ($httpCode === 200 && !empty($html)) {
            // Extract meta tags
            // Description
            if (preg_match('/<meta\s+(?:name|property)=["\'](?:description|og:description)["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $html, $desc_match)) {
                $metadata_found['description'] = html_entity_decode(trim($desc_match[1]), ENT_QUOTES | ENT_HTML5);
            }
            
            // Keywords
            if (preg_match('/<meta\s+name=["\']keywords["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $html, $keywords_match)) {
                $metadata_found['keywords'] = html_entity_decode(trim($keywords_match[1]), ENT_QUOTES | ENT_HTML5);
            }
            
            // Title
            if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $title_match)) {
                $metadata_found['page_title'] = html_entity_decode(trim($title_match[1]), ENT_QUOTES | ENT_HTML5);
            }
            
            // Open Graph tags
            if (preg_match('/<meta\s+property=["\']og:title["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $html, $og_title)) {
                $metadata_found['og_title'] = html_entity_decode(trim($og_title[1]), ENT_QUOTES | ENT_HTML5);
            }
            
            if (preg_match('/<meta\s+property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $html, $og_image)) {
                $metadata_found['og_image'] = trim($og_image[1]);
            }
            
            // Extract contact information
            // Email addresses
            if (preg_match_all('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/i', $html, $email_matches)) {
                $emails = array_unique($email_matches[0]);
                // Filter out common non-contact emails
                $contact_emails = array_filter($emails, function($email) {
                    $skip_patterns = ['noreply', 'no-reply', 'donotreply', 'example.com', 'domain.com', 'email.com', 'yoursite', 'sentry.io'];
                    foreach ($skip_patterns as $pattern) {
                        if (stripos($email, $pattern) !== false) {
                            return false;
                        }
                    }
                    return true;
                });
                if (!empty($contact_emails)) {
                    $metadata_found['contact_emails'] = array_values($contact_emails);
                }
            }
            
            // Phone numbers (US format)
            if (preg_match_all('/(?:\+?1[-.\s]?)?\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})/', $html, $phone_matches)) {
                $phones = [];
                for ($i = 0; $i < count($phone_matches[0]); $i++) {
                    $phone = preg_replace('/[^0-9]/', '', $phone_matches[0][$i]);
                    if (strlen($phone) == 10) {
                        $phone = '1' . $phone;
                    }
                    if (strlen($phone) == 11 && $phone[0] == '1') {
                        $phones[] = $phone;
                    }
                }
                if (!empty($phones)) {
                    $metadata_found['contact_phones'] = array_unique($phones);
                }
            }
            
            // Business hours - look for common patterns
            if (preg_match('/(?:hours|open|schedule)[^<]*?:?\s*([^<]*?(?:monday|mon|tuesday|tue|wednesday|wed|thursday|thu|friday|fri|saturday|sat|sunday|sun)[^<]*?(?:am|pm|closed)[^<]*)/i', $html, $hours_match)) {
                $hours_text = strip_tags($hours_match[1]);
                $hours_text = preg_replace('/\s+/', ' ', trim($hours_text));
                if (strlen($hours_text) < 500) { // Reasonable length for hours
                    $metadata_found['business_hours_text'] = $hours_text;
                }
            }
            
            // Look for address information
            // Common address patterns
            if (preg_match('/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Way|Court|Ct|Plaza|Place|Pl)\.?(?:\s+(?:Suite|Ste|Unit|Apt|#)\s*\w+)?),?\s*([A-Za-z\s]+),?\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/i', $html, $address_match)) {
                $metadata_found['address'] = [
                    'street' => trim($address_match[1]),
                    'city' => trim($address_match[2]),
                    'state' => trim($address_match[3]),
                    'zip' => trim($address_match[4]),
                    'full' => trim($address_match[0])
                ];
            }
            
            // Extract structured data (JSON-LD)
            if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $jsonld_matches)) {
                foreach ($jsonld_matches[1] as $jsonld) {
                    try {
                        $data = json_decode($jsonld, true);
                        if ($data && isset($data['@type'])) {
                            // Look for Organization or LocalBusiness schemas
                            if (in_array($data['@type'], ['Organization', 'LocalBusiness', 'Store', 'Restaurant'])) {
                                if (isset($data['name'])) {
                                    $metadata_found['schema_name'] = $data['name'];
                                }
                                if (isset($data['telephone'])) {
                                    $metadata_found['schema_phone'] = $data['telephone'];
                                }
                                if (isset($data['email'])) {
                                    $metadata_found['schema_email'] = $data['email'];
                                }
                                if (isset($data['address'])) {
                                    $metadata_found['schema_address'] = $data['address'];
                                }
                                if (isset($data['openingHours'])) {
                                    $metadata_found['schema_hours'] = $data['openingHours'];
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Invalid JSON, skip
                    }
                }
            }
        }
        
        // Store the metadata as attributes
        $attributes_added = 0;
        foreach ($metadata_found as $key => $value) {
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'metadata', :name, :description, 'active', NOW())
                        ON DUPLICATE KEY UPDATE description = VALUES(description), modify_dt = NOW()";
            
            $description = is_array($value) ? json_encode($value) : $value;
            
            $database->query($attr_sql, [
                'company_id' => $company_id,
                'name' => $key,
                'description' => substr($description, 0, 65535) // TEXT field limit
            ]);
            $attributes_added++;
        }
        
        // Update status based on what we found
        if ($attributes_added > 0) {
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = $attributes_added . ' metadata fields extracted';
            $result['metadata_summary'] = array_keys($metadata_found);
        } else {
            $status = 'attempted';
            $result['successful'] = 1;
            $result['data_collected'] = 'No metadata extracted from website';
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabmetadata'";
        $database->query($complete_sql, [
            'status' => $status,
            'company_id' => $company_id
        ]);
        
        $database->commit();
        
    } catch (Exception $e) {
        $database->rollBack();
        
        // Update progress to error
        $error_sql = "UPDATE bg_company_attributes 
                     SET description = 'error', modify_dt = NOW() 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress' 
                     AND name = 'abo_grabmetadata'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        error_log("ABO grab metadata error for company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO grab metadata fatal error: " . $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);