<?php
// abo_grabprivacy.php - Extract privacy policy and track content changes
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
    'processor' => 'abo_grabprivacy',
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
                AND ca.name = 'abo_grabprivacy'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending privacy collection
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabprivacy'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending privacy policy collection';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    
    try {
        $database->beginTransaction();
        
        // Update progress to in_progress
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'in_progress', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabprivacy'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Possible variations for Privacy Policy
        $privacy_variations = [
            'Privacy Policy', 'privacy-policy', 'Privacy Statement', 
            'Privacy Notice', 'Privacy', 'Data Protection', 'Privacy Center',
            'Your Privacy', 'Privacy & Security', 'Privacy and Security'
        ];
        
        $privacy_found = false;
        $privacy_url = '';
        $privacy_content = '';
        $content_hash = '';
        
        // URLs to check in order of priority
        $urls_to_check = [];
        if (!empty($company['company_url'])) $urls_to_check[] = $company['company_url'];
        if (!empty($company['info_url'])) $urls_to_check[] = $company['info_url'];
        if (!empty($company['signup_url'])) $urls_to_check[] = $company['signup_url'];
        
        foreach ($urls_to_check as $url) {
            if (empty($url)) continue;
            
            // Fetch the webpage
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || empty($html)) {
                continue;
            }
            
            // Parse URL for base domain
            $parsed_url = parse_url($url);
            $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
            
            // Look for privacy links in the HTML
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            libxml_clear_errors();
            
            foreach ($dom->getElementsByTagName('a') as $anchor) {
                $link_text = trim($anchor->nodeValue);
                $link_href = $anchor->getAttribute('href');
                
                if (empty($link_href)) continue;
                
                // Check if link text matches any privacy variation
                // Avoid false positives by checking for exact phrase matches
                foreach ($privacy_variations as $privacy) {
                    $privacy_lower = strtolower($privacy);
                    $text_lower = strtolower($link_text);
                    $href_lower = strtolower($link_href);
                    
                    // Check for exact match in link text or reasonable match in URL
                    if ($text_lower === $privacy_lower || 
                        $text_lower === 'privacy' || 
                        $text_lower === 'privacy policy' ||
                        $text_lower === 'privacy statement' ||
                        preg_match('/\b' . preg_quote($privacy_lower, '/') . '\b/', $text_lower) ||
                        preg_match('/\/privacy[^a-z]|\/privacy-|\/privacy$|\/legal\/privacy/', $href_lower)) {
                        
                        // Make URL absolute
                        if (!filter_var($link_href, FILTER_VALIDATE_URL)) {
                            if (substr($link_href, 0, 2) === '//') {
                                $link_href = $parsed_url['scheme'] . ':' . $link_href;
                            } elseif (substr($link_href, 0, 1) === '/') {
                                $link_href = $base_url . $link_href;
                            } else {
                                $link_href = $base_url . '/' . $link_href;
                            }
                        }
                        
                        $privacy_url = $link_href;
                        $privacy_found = true;
                        break 2; // Break both loops
                    }
                }
            }
            
            if ($privacy_found) break; // Stop checking other URLs
        }
        
        // If we found a privacy URL, fetch and hash the content
        if ($privacy_found && !empty($privacy_url)) {
            // Fetch the privacy page
            $ch = curl_init($privacy_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            
            $privacy_html = curl_exec($ch);
            $privacy_httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($privacy_httpCode === 200 && !empty($privacy_html)) {
                // Extract text content from HTML
                $dom = new DOMDocument();
                @$dom->loadHTML($privacy_html);
                
                // Remove script and style elements
                $xpath = new DOMXPath($dom);
                foreach ($xpath->query('//script|//style') as $node) {
                    $node->parentNode->removeChild($node);
                }
                
                // Get the body text
                $body = $dom->getElementsByTagName('body')->item(0);
                if ($body) {
                    $privacy_content = $body->textContent;
                    // Clean up whitespace
                    $privacy_content = preg_replace('/\s+/', ' ', trim($privacy_content));
                    
                    // Generate content hash for change detection
                    $content_hash = hash('sha256', $privacy_content);
                }
            }
        }
        
        // Store the results
        if ($privacy_found && !empty($privacy_url)) {
            // Store privacy URL
            $url_sql = "INSERT INTO bg_company_attributes 
                       (company_id, type, name, description, status, `grouping`, create_dt)
                       VALUES 
                       (:company_id, 'url', 'privacy', :url, 'active', 'policies', NOW())
                       ON DUPLICATE KEY UPDATE 
                       description = VALUES(description),
                       modify_dt = NOW()";
            $database->query($url_sql, [
                'company_id' => $company_id,
                'url' => $privacy_url
            ]);
            
            // Check if we need to create or update policy record
            $policy_check_sql = "SELECT policy_id, content_hash, version 
                               FROM bg_company_policies 
                               WHERE company_id = :company_id 
                               AND policy_type = 'privacy' 
                               AND status IN ('active', 'verified')
                               ORDER BY version DESC
                               LIMIT 1";
            $policy_stmt = $database->query($policy_check_sql, ['company_id' => $company_id]);
            $existing_policy = $policy_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing_policy) {
                // Create new policy record
                $policy_sql = "INSERT INTO bg_company_policies 
                             (company_id, policy_type, policy_name, url, content_hash, 
                              version, status, last_verified)
                             VALUES 
                             (:company_id, 'privacy', 'Privacy Policy', :url, :hash, 
                              1, 'verified', NOW())";
                $database->query($policy_sql, [
                    'company_id' => $company_id,
                    'url' => $privacy_url,
                    'hash' => $content_hash
                ]);
            } else {
                // Check if content has changed
                if ($existing_policy['content_hash'] !== $content_hash) {
                    // Update existing policy as changed
                    $update_sql = "UPDATE bg_company_policies 
                                 SET status = 'changed' 
                                 WHERE policy_id = :policy_id";
                    $database->query($update_sql, ['policy_id' => $existing_policy['policy_id']]);
                    
                    // Create new version
                    $new_version = $existing_policy['version'] + 1;
                    $policy_sql = "INSERT INTO bg_company_policies 
                                 (company_id, policy_type, policy_name, url, content_hash, 
                                  version, status, last_verified)
                                 VALUES 
                                 (:company_id, 'privacy', 'Privacy Policy', :url, :hash, 
                                  :version, 'verified', NOW())";
                    $database->query($policy_sql, [
                        'company_id' => $company_id,
                        'url' => $privacy_url,
                        'hash' => $content_hash,
                        'version' => $new_version
                    ]);
                } else {
                    // Content unchanged, update last verified and URL if needed
                    $verify_sql = "UPDATE bg_company_policies 
                                 SET last_verified = NOW(), url = :url 
                                 WHERE policy_id = :policy_id";
                    $database->query($verify_sql, [
                        'policy_id' => $existing_policy['policy_id'],
                        'url' => $privacy_url
                    ]);
                }
            }
            
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = 'Privacy policy found at: ' . $privacy_url;
            $result['content_hash'] = $content_hash;
        } else {
            // No privacy policy found
            $status = 'attempted';
            $result['successful'] = 1;
            $result['data_collected'] = 'No privacy policy found';
            
            // Log the attempt
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'data_collection', 'privacy_search_result', 'not_found', 'active', NOW())";
            $database->query($attr_sql, ['company_id' => $company_id]);
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabprivacy'";
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
                     AND name = 'abo_grabprivacy'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        error_log("ABO grab privacy error for company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO grab privacy fatal error: " . $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);