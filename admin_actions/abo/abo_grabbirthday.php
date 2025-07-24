<?php
// abo_grabbirthday.php - Extract birthday reward program specifics and requirements
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
    'processor' => 'abo_grabbirthday',
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
                AND ca.name = 'abo_grabbirthday'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending birthday data collection
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabbirthday'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending birthday program collection';
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
                        AND name = 'abo_grabbirthday'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        $birthday_data = [
            'has_program' => false,
            'program_type' => null, // 'none', 'week', 'month', 'exact'
            'requirements' => [],
            'rewards' => [],
            'signup_method' => null,
            'age_restrictions' => []
        ];
        
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
        
        if ($httpCode === 200 && !empty($html)) {
            // Look for birthday program keywords
            $birthday_keywords = [
                'birthday', 'birth day', 'bday', 'b-day',
                'anniversary', 'special day', 'special occasion',
                'birthday club', 'birthday reward',
                'birthday offer', 'birthday freebie',
                'birthday gift', 'birthday treat',
                'birthday perk', 'birthday benefit',
                'celebrate', 'annual', 'yearly',
                'once a year', 'every year'
            ];
            
            // First, look for rewards/loyalty program links
            $program_patterns = [
                '/<a[^>]+href=["\']([^"\']*(?:rewards?|loyalty|club|member|perks?|benefits?|vip|program)[^"\']*)["\'][^>]*>/i',
                '/<a[^>]+(?:rewards?|loyalty|club|program)[^>]+href=["\']([^"\']+)["\'][^>]*>/i'
            ];
            
            $program_urls = [];
            foreach ($program_patterns as $pattern) {
                if (preg_match_all($pattern, $html, $matches)) {
                    foreach ($matches[1] as $url) {
                        // Make URL absolute
                        $parsed_base = parse_url($company['company_url']);
                        if (!filter_var($url, FILTER_VALIDATE_URL)) {
                            if (substr($url, 0, 2) === '//') {
                                $url = $parsed_base['scheme'] . ':' . $url;
                            } elseif (substr($url, 0, 1) === '/') {
                                $url = $parsed_base['scheme'] . '://' . $parsed_base['host'] . $url;
                            } else {
                                $url = $parsed_base['scheme'] . '://' . $parsed_base['host'] . '/' . $url;
                            }
                        }
                        
                        // Skip external links
                        $url_host = parse_url($url, PHP_URL_HOST);
                        if ($url_host && $url_host === $parsed_base['host'] && !in_array($url, $program_urls)) {
                            $program_urls[] = $url;
                        }
                    }
                }
            }
            
            // Sort URLs by priority (birthday-related URLs first)
            $priority_urls = [];
            $other_urls = [];
            
            foreach ($program_urls as $url) {
                if (preg_match('/birthday|bday|birth/i', $url)) {
                    $priority_urls[] = $url;
                } else {
                    $other_urls[] = $url;
                }
            }
            
            // Combine with priority URLs first
            $sorted_program_urls = array_merge($priority_urls, $other_urls);
            
            // Check main page first
            $pages_to_check = [$html];
            $urls_checked = [$company['company_url']];
            
            // Fetch and check rewards program pages (up to 7)
            foreach (array_slice($sorted_program_urls, 0, 7) as $program_url) {
                if (!in_array($program_url, $urls_checked)) {
                    $ch = curl_init($program_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                    
                    $program_html = curl_exec($ch);
                    $program_httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($program_httpCode === 200 && !empty($program_html)) {
                        $pages_to_check[] = $program_html;
                        $urls_checked[] = $program_url;
                        
                        // Log that we checked this URL
                        $url_log_sql = "INSERT INTO bg_company_attributes 
                                       (company_id, type, name, description, status, create_dt)
                                       VALUES 
                                       (:company_id, 'data_collection', 'birthday_urls_checked', :url, 'active', NOW())";
                        $database->query($url_log_sql, [
                            'company_id' => $company_id,
                            'url' => $program_url
                        ]);
                    }
                }
            }
            
            // Now check all pages for birthday mentions
            $mentions_birthday = false;
            $birthday_page_content = '';
            
            foreach ($pages_to_check as $page_content) {
                $page_lower = strtolower($page_content);
                foreach ($birthday_keywords as $keyword) {
                    if (strpos($page_lower, $keyword) !== false) {
                        $mentions_birthday = true;
                        $birthday_page_content = $page_content;
                        $html = $page_content; // Use this page for extraction
                        break 2;
                    }
                }
            }
            
            if ($mentions_birthday) {
                $birthday_data['has_program'] = true;
                
                // Extract reward details
                $reward_patterns = [
                    // Free item patterns
                    '/(?:free|complimentary)\s+(?:birthday)?\s*([a-zA-Z\s]+?)(?:on|during|for)\s+your\s+birthday/i',
                    '/birthday\s+(?:gift|reward|treat|offer)[\s:]+([^.!?]+)/i',
                    '/get\s+(?:a\s+)?([^.!?]+?)\s+(?:free|on us|complimentary).*?birthday/i',
                    // Discount patterns
                    '/(\d+%?)\s*(?:off|discount).*?birthday/i',
                    '/birthday.*?(\d+%?)\s*(?:off|discount)/i',
                    // Dollar off patterns
                    '/\$(\d+)\s*(?:off|credit|bonus).*?birthday/i',
                    '/birthday.*?\$(\d+)\s*(?:off|credit|bonus)/i'
                ];
                
                foreach ($reward_patterns as $pattern) {
                    if (preg_match_all($pattern, $html, $matches)) {
                        foreach ($matches[1] as $reward) {
                            $reward_clean = trim(strip_tags($reward));
                            if (strlen($reward_clean) > 3 && strlen($reward_clean) < 200) {
                                $birthday_data['rewards'][] = $reward_clean;
                            }
                        }
                    }
                }
                
                // Determine program timing type
                if (preg_match('/birthday\s+week/i', $html)) {
                    $birthday_data['program_type'] = 'week';
                } elseif (preg_match('/birthday\s+month/i', $html)) {
                    $birthday_data['program_type'] = 'month';
                } elseif (preg_match('/(?:on|exact)\s+(?:your\s+)?birthday/i', $html)) {
                    $birthday_data['program_type'] = 'exact';
                } elseif ($birthday_data['program_type'] !== 'loyalty_platform') {
                    $birthday_data['program_type'] = 'week'; // Default assumption
                }
                
                // Look for signup requirements
                $signup_patterns = [
                    '/(?:join|sign\s*up|register|enroll).*?(?:rewards?|club|program|app)/i',
                    '/download\s+(?:our|the)\s+app/i',
                    '/create\s+an?\s+account/i',
                    '/(?:email|text|sms)\s+(?:list|club|alerts)/i'
                ];
                
                foreach ($signup_patterns as $pattern) {
                    if (preg_match($pattern, $html, $match)) {
                        $birthday_data['signup_method'] = strtolower(trim($match[0]));
                        break;
                    }
                }
                
                // Extract requirements
                if (preg_match_all('/(?:must|need to|required to|have to)\s+([^.!?]+?)(?:to\s+(?:receive|get|claim|redeem))/i', $html, $req_matches)) {
                    foreach ($req_matches[1] as $requirement) {
                        $req_clean = trim(strip_tags($requirement));
                        if (strlen($req_clean) > 5 && strlen($req_clean) < 200) {
                            $birthday_data['requirements'][] = $req_clean;
                        }
                    }
                }
                
                // Look for age restrictions
                if (preg_match('/(?:must be|age)\s+(\d+)(?:\+|\s+(?:or|and)\s+(?:older|above))/i', $html, $age_match)) {
                    $birthday_data['age_restrictions']['minimum'] = intval($age_match[1]);
                }
                
                if (preg_match('/(?:children|kids|under)\s+(\d+)/i', $html, $child_match)) {
                    $birthday_data['age_restrictions']['child_max'] = intval($child_match[1]);
                }
            }
            
            // Check for loyalty platform indicators
            $loyalty_platforms = [
                'yotpo' => ['yotpo', 'data-yotpo', 'yotpo-widget'],
                'smile' => ['smile.io', 'smile-launcher', 'smile-ui'],
                'loyalty_lion' => ['loyaltylion', 'lion-loyalty'],
                'stamped' => ['stamped.io', 'stamped-loyalty'],
                'swell' => ['swell.store', 'swell-campaign'],
                'rewards_program' => ['rewards program', 'loyalty program', 'vip program']
            ];
            
            $detected_platform = null;
            $has_loyalty_program = false;
            
            foreach ($loyalty_platforms as $platform => $indicators) {
                foreach ($indicators as $indicator) {
                    if (stripos($html, $indicator) !== false) {
                        $detected_platform = $platform;
                        $has_loyalty_program = true;
                        break 2;
                    }
                }
            }
            
            // If we detected a loyalty platform, assume birthday rewards might be available
            if ($has_loyalty_program && !$mentions_birthday) {
                // Many loyalty programs include birthday rewards by default
                $birthday_data['has_program'] = true;
                $birthday_data['program_type'] = 'loyalty_platform';
                $birthday_data['signup_method'] = 'join ' . str_replace('_', ' ', $detected_platform) . ' rewards program';
                $birthday_data['requirements'][] = 'Join the rewards program';
                $birthday_data['requirements'][] = 'Provide birth date during signup';
                $birthday_data['rewards'][] = 'Birthday reward (check program for details)';
                
                // Store platform detection
                $platform_sql = "INSERT INTO bg_company_attributes 
                               (company_id, type, name, description, status, create_dt)
                               VALUES 
                               (:company_id, 'data_collection', 'loyalty_platform_detected', :platform, 'active', NOW())";
                $database->query($platform_sql, [
                    'company_id' => $company_id,
                    'platform' => $detected_platform
                ]);
            }
            
            // Store found program URLs for reference
            if (!empty($program_urls)) {
                $urls_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, status, create_dt)
                            VALUES 
                            (:company_id, 'data_collection', 'program_urls_found', :urls, 'active', NOW())
                            ON DUPLICATE KEY UPDATE description = VALUES(description), modify_dt = NOW()";
                $database->query($urls_sql, [
                    'company_id' => $company_id,
                    'urls' => json_encode($program_urls)
                ]);
            }
        }
        
        // Save birthday program data
        if ($birthday_data['has_program']) {
            // Check if we need to create or update reward record
            $reward_check_sql = "SELECT reward_id FROM bg_company_rewards 
                               WHERE company_id = :company_id 
                               AND reward_name LIKE '%Birthday%' 
                               LIMIT 1";
            $reward_check = $database->query($reward_check_sql, ['company_id' => $company_id]);
            
            if ($reward_check->rowCount() == 0 && !empty($birthday_data['rewards'])) {
                // Create a new reward record
                $reward_name = 'Birthday Reward';
                $reward_desc = implode(', ', array_slice($birthday_data['rewards'], 0, 3));
                
                $reward_sql = "INSERT INTO bg_company_rewards 
                              (company_id, category, reward_type, reward_name, 
                               reward_description_short, reward_description_long,
                               status, create_dt)
                              VALUES 
                              (:company_id, 'birthday', 'physical', :name, 
                               :short_desc, :long_desc,
                               'active', NOW())";
                $database->query($reward_sql, [
                    'company_id' => $company_id,
                    'name' => $reward_name,
                    'short_desc' => substr($reward_desc, 0, 1000),
                    'long_desc' => json_encode($birthday_data)
                ]);
            }
            
            // Store detailed birthday data as attributes
            $birthday_sql = "INSERT INTO bg_company_attributes 
                           (company_id, type, name, description, status, create_dt)
                           VALUES 
                           (:company_id, 'birthday_program', :name, :description, 'active', NOW())
                           ON DUPLICATE KEY UPDATE description = VALUES(description), modify_dt = NOW()";
            
            // Store program data
            $database->query($birthday_sql, [
                'company_id' => $company_id,
                'name' => 'program_data',
                'description' => json_encode($birthday_data)
            ]);
            
            // Store program type
            $database->query($birthday_sql, [
                'company_id' => $company_id,
                'name' => 'program_type',
                'description' => $birthday_data['program_type']
            ]);
            
            // Store signup method
            if (!empty($birthday_data['signup_method'])) {
                $database->query($birthday_sql, [
                    'company_id' => $company_id,
                    'name' => 'signup_method',
                    'description' => $birthday_data['signup_method']
                ]);
            }
            
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = 'Birthday program detected: ' . $birthday_data['program_type'];
            $result['birthday_data'] = $birthday_data;
        } else {
            // No birthday program found
            $status = 'attempted';
            $result['successful'] = 1;
            $result['data_collected'] = 'No birthday program detected';
            
            // Log the attempt
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'birthday_program', 'search_result', 'no_program_found', 'active', NOW())";
            $database->query($attr_sql, ['company_id' => $company_id]);
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabbirthday'";
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
                     AND name = 'abo_grabbirthday'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        error_log("ABO grab birthday error for company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO grab birthday fatal error: " . $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);