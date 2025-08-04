<?php
/**
 * ABO Auto Review Processor
 * 
 * Automatically reviews business submissions by comparing the business name
 * with website content. If confidence is exceptional, auto-approves the submission
 * to allow remaining ABO tasks to proceed.
 * 
 * @package ABO
 * @author Birthday Gold
 * @date 2025-08-04
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Scheduler key check
$key = $_GET['key'] ?? '';
$schedulerkey = $_SERVER['SCHEDULERKEY'] ?? 'SCHEDULERKEY_HERE';

if ($key !== $schedulerkey) {
    header('HTTP/1.1 403 Forbidden');
    die('Invalid scheduler key');
}

// Get company ID - support both encoded and raw for debugging
$specific_company_id = null;
if (isset($_GET['rawid'])) {
    $specific_company_id = intval($_GET['rawid']);
} elseif (isset($_GET['id'])) {
    $specific_company_id = $qik->decodeID($_GET['id']);
}

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processed' => 0,
    'auto_approved' => 0,
    'manual_review' => 0,
    'errors' => [],
    'companies' => []
];

// Get Guzzle client for web requests
$client = new \GuzzleHttp\Client([
    'timeout' => 30,
    'verify' => false,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (compatible; BirthdayGold-AutoReview/1.0)'
    ]
]);

try {
    // Get companies to process
    if ($specific_company_id) {
        // Manual trigger for specific company
        $sql = "SELECT c.* FROM bg_companies c 
                WHERE c.company_id = :company_id 
                AND c.status = 'submitted'
                AND c.source = 'user_recommendation'
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Automatic processing - get newly submitted companies
        $sql = "SELECT c.* FROM bg_companies c 
                LEFT JOIN bg_company_attributes ca 
                    ON c.company_id = ca.company_id 
                    AND ca.type = 'onboarding_progress' 
                    AND ca.name = 'abo_autoreview'
                WHERE c.status = 'submitted' 
                AND c.source = 'user_recommendation'
                AND (ca.description IS NULL OR ca.description = 'pending')
                ORDER BY c.create_dt ASC 
                LIMIT 5";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($submissions as $submission) {
        $result['processed']++;
        $company_id = $submission['company_id'];
        $company_name = $submission['company_name'];
        $company_url = $submission['company_url'];
        $signup_url = $submission['signup_url'];
        
        $company_result = [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'confidence_score' => 0,
            'checks' => [],
            'decision' => 'manual_review',
            'reasons' => []
        ];
        
        try {
            $database->beginTransaction();
            
            // Update onboarding progress to in_progress
            $progress_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, status, create_dt, modify_dt)
                            VALUES 
                            (:company_id, 'onboarding_progress', 'abo_autoreview', 'in_progress', 'active', NOW(), NOW())
                            ON DUPLICATE KEY UPDATE 
                            description = 'in_progress', modify_dt = NOW()";
            $database->query($progress_sql, ['company_id' => $company_id]);
            
            // Perform auto-review checks
            $confidence_score = 0;
            $max_score = 0;
            
            // Check 1: Company URL accessibility and content
            $home_check = checkWebsiteContent($client, $company_url, $company_name);
            $company_result['checks']['home_url'] = $home_check;
            $confidence_score += $home_check['score'];
            $max_score += $home_check['max_score'];
            
            // Check 2: Signup URL accessibility and relevance
            $signup_check = checkSignupUrl($client, $signup_url, $company_name);
            $company_result['checks']['signup_url'] = $signup_check;
            $confidence_score += $signup_check['score'];
            $max_score += $signup_check['max_score'];
            
            // Check 3: Domain consistency
            $domain_check = checkDomainConsistency($company_url, $signup_url);
            $company_result['checks']['domain_consistency'] = $domain_check;
            $confidence_score += $domain_check['score'];
            $max_score += $domain_check['max_score'];
            
            // Check 4: Business legitimacy indicators
            $legitimacy_check = checkBusinessLegitimacy($client, $company_url);
            $company_result['checks']['legitimacy'] = $legitimacy_check;
            $confidence_score += $legitimacy_check['score'];
            $max_score += $legitimacy_check['max_score'];
            
            // Calculate final confidence percentage
            $confidence_percentage = ($max_score > 0) ? round(($confidence_score / $max_score) * 100) : 0;
            $company_result['confidence_score'] = $confidence_percentage;
            
            // Decision logic
            if ($confidence_percentage >= 85) {
                // Exceptional confidence - auto approve
                $company_result['decision'] = 'auto_approved';
                $company_result['reasons'][] = "High confidence score ({$confidence_percentage}%)";
                
                // Update company status to approved_pending_data
                $update_sql = "UPDATE bg_companies 
                              SET status = 'approved_pending_data', modify_dt = NOW() 
                              WHERE company_id = :company_id";
                $database->query($update_sql, ['company_id' => $company_id]);
                
                // Add auto-approval attribute
                $attr_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, value, status, create_dt)
                            VALUES 
                            (:company_id, 'auto_review', 'approval', 'auto_approved', :value, 'active', NOW())";
                $database->query($attr_sql, [
                    'company_id' => $company_id,
                    'value' => json_encode([
                        'confidence_score' => $confidence_percentage,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'checks' => $company_result['checks']
                    ])
                ]);
                
                $result['auto_approved']++;
                
                // Initialize remaining ABO processors as pending
                initializeAboProcessors($database, $company_id);
                
            } else {
                // Needs manual review
                $company_result['decision'] = 'manual_review';
                $company_result['reasons'][] = "Confidence score below threshold ({$confidence_percentage}% < 85%)";
                
                // Update company status to pending_review
                $update_sql = "UPDATE bg_companies 
                              SET status = 'pending_review', modify_dt = NOW() 
                              WHERE company_id = :company_id";
                $database->query($update_sql, ['company_id' => $company_id]);
                
                // Store review results
                $attr_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, value, status, create_dt)
                            VALUES 
                            (:company_id, 'auto_review', 'results', 'manual_review_required', :value, 'active', NOW())";
                $database->query($attr_sql, [
                    'company_id' => $company_id,
                    'value' => json_encode($company_result)
                ]);
                
                $result['manual_review']++;
            }
            
            // Update onboarding progress to completed
            $progress_sql = "UPDATE bg_company_attributes 
                            SET description = 'completed', modify_dt = NOW() 
                            WHERE company_id = :company_id 
                            AND type = 'onboarding_progress' 
                            AND name = 'abo_autoreview'";
            $database->query($progress_sql, ['company_id' => $company_id]);
            
            $database->commit();
            
        } catch (Exception $e) {
            $database->rollBack();
            $result['errors'][] = "Company {$company_id}: " . $e->getMessage();
            
            // Mark as error
            $error_sql = "UPDATE bg_company_attributes 
                         SET description = 'error', 
                             value = :error,
                             modify_dt = NOW() 
                         WHERE company_id = :company_id 
                         AND type = 'onboarding_progress' 
                         AND name = 'abo_autoreview'";
            $database->query($error_sql, [
                'company_id' => $company_id,
                'error' => json_encode(['error' => $e->getMessage()])
            ]);
        }
        
        $result['companies'][] = $company_result;
    }
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
}

// Output results
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);

/**
 * Check website content for business name matches
 */
function checkWebsiteContent($client, $url, $company_name) {
    $check = [
        'score' => 0,
        'max_score' => 30,
        'details' => []
    ];
    
    try {
        $response = $client->get($url);
        $html = (string) $response->getBody();
        $statusCode = $response->getStatusCode();
        
        if ($statusCode == 200) {
            $check['details']['accessible'] = true;
            $check['score'] += 5;
            
            // Check for company name in title
            if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
                $title = html_entity_decode($matches[1], ENT_QUOTES);
                if (stripos($title, $company_name) !== false) {
                    $check['details']['name_in_title'] = true;
                    $check['score'] += 10;
                }
            }
            
            // Check for company name in content (case insensitive)
            $name_count = substr_count(strtolower($html), strtolower($company_name));
            if ($name_count > 0) {
                $check['details']['name_in_content'] = true;
                $check['details']['name_occurrences'] = $name_count;
                // More occurrences = higher confidence, max 10 points
                $check['score'] += min(10, $name_count * 2);
            }
            
            // Check meta tags
            if (preg_match('/<meta[^>]+property=["\']og:site_name["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                if (stripos($matches[1], $company_name) !== false) {
                    $check['details']['name_in_meta'] = true;
                    $check['score'] += 5;
                }
            }
        } else {
            $check['details']['accessible'] = false;
            $check['details']['status_code'] = $statusCode;
        }
        
    } catch (Exception $e) {
        $check['details']['accessible'] = false;
        $check['details']['error'] = $e->getMessage();
    }
    
    return $check;
}

/**
 * Check signup URL validity and relevance
 */
function checkSignupUrl($client, $url, $company_name) {
    $check = [
        'score' => 0,
        'max_score' => 25,
        'details' => []
    ];
    
    try {
        $response = $client->get($url);
        $html = (string) $response->getBody();
        $statusCode = $response->getStatusCode();
        
        if ($statusCode == 200) {
            $check['details']['accessible'] = true;
            $check['score'] += 5;
            
            // Check for signup/registration keywords
            $signup_keywords = ['sign up', 'signup', 'register', 'join', 'create account', 'rewards', 'member'];
            $keyword_found = false;
            
            foreach ($signup_keywords as $keyword) {
                if (stripos($html, $keyword) !== false) {
                    $keyword_found = true;
                    $check['details']['signup_keywords'][] = $keyword;
                }
            }
            
            if ($keyword_found) {
                $check['score'] += 10;
            }
            
            // Check for form elements
            if (preg_match('/<form[^>]*>/i', $html)) {
                $check['details']['has_form'] = true;
                $check['score'] += 5;
            }
            
            // Check for email input fields
            if (preg_match('/<input[^>]+type=["\']email["\'][^>]*>/i', $html)) {
                $check['details']['has_email_field'] = true;
                $check['score'] += 5;
            }
        } else {
            $check['details']['accessible'] = false;
            $check['details']['status_code'] = $statusCode;
        }
        
    } catch (Exception $e) {
        $check['details']['accessible'] = false;
        $check['details']['error'] = $e->getMessage();
    }
    
    return $check;
}

/**
 * Check domain consistency between URLs
 */
function checkDomainConsistency($company_url, $signup_url) {
    $check = [
        'score' => 0,
        'max_score' => 20,
        'details' => []
    ];
    
    $company_domain = parse_url($company_url, PHP_URL_HOST);
    $signup_domain = parse_url($signup_url, PHP_URL_HOST);
    
    if ($company_domain && $signup_domain) {
        // Remove www. for comparison
        $company_domain = preg_replace('/^www\./', '', $company_domain);
        $signup_domain = preg_replace('/^www\./', '', $signup_domain);
        
        if ($company_domain === $signup_domain) {
            $check['details']['same_domain'] = true;
            $check['score'] = 20;
        } else {
            // Check if signup is on a subdomain
            if (strpos($signup_domain, $company_domain) !== false) {
                $check['details']['related_domain'] = true;
                $check['score'] = 15;
            } else {
                $check['details']['different_domain'] = true;
                $check['details']['company_domain'] = $company_domain;
                $check['details']['signup_domain'] = $signup_domain;
            }
        }
    }
    
    return $check;
}

/**
 * Check business legitimacy indicators
 */
function checkBusinessLegitimacy($client, $url) {
    $check = [
        'score' => 0,
        'max_score' => 25,
        'details' => []
    ];
    
    try {
        $response = $client->get($url);
        $html = (string) $response->getBody();
        
        // Check for SSL
        if (strpos($url, 'https://') === 0) {
            $check['details']['has_ssl'] = true;
            $check['score'] += 5;
        }
        
        // Check for contact information
        $contact_patterns = [
            'phone' => '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/',
            'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/'
        ];
        
        foreach ($contact_patterns as $type => $pattern) {
            if (preg_match($pattern, $html)) {
                $check['details']["has_{$type}"] = true;
                $check['score'] += 5;
            }
        }
        
        // Check for physical address indicators
        $address_keywords = ['address', 'location', 'headquarters', 'street', 'city', 'state', 'zip'];
        $address_found = 0;
        foreach ($address_keywords as $keyword) {
            if (stripos($html, $keyword) !== false) {
                $address_found++;
            }
        }
        
        if ($address_found >= 3) {
            $check['details']['has_address_info'] = true;
            $check['score'] += 5;
        }
        
        // Check for privacy policy and terms
        if (stripos($html, 'privacy policy') !== false) {
            $check['details']['has_privacy_policy'] = true;
            $check['score'] += 5;
        }
        
        if (stripos($html, 'terms') !== false && (stripos($html, 'conditions') !== false || stripos($html, 'service') !== false)) {
            $check['details']['has_terms'] = true;
            $check['score'] += 5;
        }
        
    } catch (Exception $e) {
        $check['details']['error'] = $e->getMessage();
    }
    
    return $check;
}

/**
 * Initialize remaining ABO processors for auto-approved companies
 */
function initializeAboProcessors($database, $company_id) {
    $processors = [
        'abo_grabgoogleapp',
        'abo_grabiosapp',
        'abo_grabsocialmedia',
        'abo_grabmetadata',
        'abo_grabimages',
        'abo_grablocations',
        'abo_grabbirthday',
        'abo_mapformfields',
        'abo_aienhance',
        'abo_aivalidate'
    ];
    
    foreach ($processors as $processor) {
        $sql = "INSERT INTO bg_company_attributes 
                (company_id, type, name, description, status, create_dt)
                VALUES 
                (:company_id, 'onboarding_progress', :processor, 'pending', 'active', NOW())
                ON DUPLICATE KEY UPDATE 
                description = 'pending', modify_dt = NOW()";
        
        $database->query($sql, [
            'company_id' => $company_id,
            'processor' => $processor
        ]);
    }
}