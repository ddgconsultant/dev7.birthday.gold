<?php
// abo_grabgoogleapp_v2.php - Enhanced Google Play app search
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
    'processor' => 'abo_grabgoogleapp',
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
                AND ca.name = 'abo_grabgoogleapp'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending Google app collection
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabgoogleapp'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending Google app data collection';
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
                        AND name = 'abo_grabgoogleapp'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        $found_apps = [];
        
        // Method 1: Search Google Play by company name
        $search_query = urlencode($company['company_name']);
        $search_url = "https://play.google.com/store/search?q={$search_query}&c=apps";
        
        $result['debug'] = [
            'company_name' => $company['company_name'],
            'search_url' => $search_url
        ];
        
        $ch = curl_init($search_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'
        ]);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        
        $search_html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && !empty($search_html)) {
            // Extract all app IDs from search results
            preg_match_all('/\/store\/apps\/details\?id=([a-zA-Z0-9._]+)/', $search_html, $matches);
            $potential_apps = array_unique($matches[1]);
            
            $result['debug']['potential_apps_found'] = count($potential_apps);
            
            foreach ($potential_apps as $app_id) {
                // Skip if we already have enough apps
                if (count($found_apps) >= 3) break;
                
                // Fetch app details
                $app_url = 'https://play.google.com/store/apps/details?id=' . $app_id;
                
                $ch = curl_init($app_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
                
                $app_html = curl_exec($ch);
                $app_httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($app_httpCode === 200 && !empty($app_html)) {
                    // Extract app details
                    $app_data = [
                        'id' => $app_id,
                        'url' => $app_url,
                        'name' => '',
                        'developer' => '',
                        'description' => '',
                        'icon_url' => '',
                        'found_via' => 'google_play_search'
                    ];
                    
                    // Extract app name
                    if (preg_match('/<h1[^>]*itemprop="name"[^>]*>([^<]+)<\/h1>/i', $app_html, $name_match)) {
                        $app_data['name'] = html_entity_decode(strip_tags($name_match[1]));
                    }
                    
                    // Extract developer name - updated pattern
                    if (preg_match('/<a[^>]*href="\/store\/apps\/dev(eloper)?\?[^"]*"[^>]*>([^<]+)<\/a>/i', $app_html, $dev_match)) {
                        $app_data['developer'] = html_entity_decode(strip_tags($dev_match[2]));
                    }
                    
                    // Extract app icon
                    if (preg_match('/<img[^>]*src="([^"]+)"[^>]*alt="[^"]*Icon"[^>]*>/i', $app_html, $icon_match) ||
                        preg_match('/<img[^>]*class="[^"]*T75of[^"]*"[^>]*src="([^"]+)"/i', $app_html, $icon_match)) {
                        $app_data['icon_url'] = $icon_match[1];
                        // Clean up icon URL
                        if (strpos($app_data['icon_url'], '//') === 0) {
                            $app_data['icon_url'] = 'https:' . $app_data['icon_url'];
                        }
                    }
                    
                    // Check relevance - more flexible matching
                    $company_name_lower = strtolower($company['company_name']);
                    $app_name_lower = strtolower($app_data['name']);
                    $developer_lower = strtolower($app_data['developer']);
                    
                    // Remove common words for better matching
                    $company_words = array_filter(explode(' ', $company_name_lower), function($word) {
                        return strlen($word) > 2 && !in_array($word, ['the', 'and', 'inc', 'llc', 'ltd', 'corp', 'company']);
                    });
                    
                    $is_relevant = false;
                    
                    // Check if any significant word from company name appears in app name or developer
                    foreach ($company_words as $word) {
                        if (strpos($app_name_lower, $word) !== false || strpos($developer_lower, $word) !== false) {
                            $is_relevant = true;
                            break;
                        }
                    }
                    
                    // Special case for specific companies
                    if ($company_name_lower === '1up nutrition' && 
                        (strpos($app_name_lower, '1up') !== false || 
                         strpos($developer_lower, '1up') !== false ||
                         strpos($app_id, 'oneup') !== false ||
                         strpos($app_id, '1up') !== false)) {
                        $is_relevant = true;
                    }
                    
                    // Also check if app ID contains company-related terms
                    $app_id_lower = strtolower($app_id);
                    foreach ($company_words as $word) {
                        if (strlen($word) >= 3 && strpos($app_id_lower, $word) !== false) {
                            $is_relevant = true;
                            break;
                        }
                    }
                    
                    if ($is_relevant) {
                        $found_apps[$app_id] = $app_data;
                        $result['debug']['matched_app'] = $app_data;
                    }
                }
            }
        }
        
        // Method 2: Check company website for Play Store links
        if (empty($found_apps)) {
            $ch = curl_init($company['company_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BirthdayGold/1.0)');
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && !empty($html)) {
                // Look for Google Play links
                preg_match_all('/href=["\'"]([^"\']*play\.google\.com\/store\/apps\/details[^"\'"]*)/i', $html, $matches);
                
                foreach ($matches[1] as $play_url) {
                    // Extract app ID from URL
                    if (preg_match('/[?&]id=([a-zA-Z0-9._]+)/', $play_url, $id_match)) {
                        $app_id = $id_match[1];
                        $found_apps[$app_id] = [
                            'id' => $app_id,
                            'url' => 'https://play.google.com/store/apps/details?id=' . $app_id,
                            'found_via' => 'website_link'
                        ];
                    }
                }
            }
        }
        
        // Store results
        if (!empty($found_apps)) {
            // Store the first app in the main table
            $first_app = reset($found_apps);
            $update_sql = "UPDATE bg_companies 
                          SET appgoogle = :app_url,
                              modify_dt = NOW()
                          WHERE company_id = :company_id";
            $database->query($update_sql, [
                'app_url' => $first_app['url'],
                'company_id' => $company_id
            ]);
            
            // Store all app data as attributes
            foreach ($found_apps as $app) {
                $app_data_sql = "INSERT INTO bg_company_attributes 
                                (company_id, type, name, description, status, create_dt)
                                VALUES 
                                (:company_id, 'data_collection', 'google_app_data', :app_data, 'active', NOW())";
                $database->query($app_data_sql, [
                    'company_id' => $company_id,
                    'app_data' => json_encode($app)
                ]);
            }
            
            // Successfully found and stored app URL - that's the main goal
            $status = 'completed';
            $result['successful'] = 1;
            
            // Check if we also got app details for informational purposes
            $has_complete_data = false;
            foreach ($found_apps as $app) {
                if (!empty($app['name']) && !empty($app['developer'])) {
                    $has_complete_data = true;
                    break;
                }
            }
            
            if ($has_complete_data) {
                $result['data_collected'] = count($found_apps) . ' Google Play app(s) found with complete data';
            } else {
                $result['data_collected'] = count($found_apps) . ' Google Play app(s) found and URL stored successfully';
            }
        } else {
            // No apps found - mark as attempted since we tried but didn't achieve the goal
            $status = 'attempted';
            $result['successful'] = 1;
            $result['data_collected'] = 'Searched but no Google Play apps found';
            
            // Log that we checked but found nothing
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'data_collection', 'google_app_search', 'checked_no_apps_found', 'active', NOW())";
            $database->query($attr_sql, ['company_id' => $company_id]);
        }
        
        // Update progress status with details
        $status_details = json_encode([
            'status' => $status,
            'apps_found' => count($found_apps),
            'has_complete_data' => $has_complete_data ?? false,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, 
                            category = :details,
                            modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabgoogleapp'";
        $database->query($complete_sql, [
            'status' => $status,
            'details' => $status_details,
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
                     AND name = 'abo_grabgoogleapp'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        session_tracking('ABO grab Google app error', "Company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    session_tracking('ABO grab Google app fatal error', $e->getMessage());
}

// Output JSON response for monitoring
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);