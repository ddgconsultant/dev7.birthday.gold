<?php
// abo_grabiosapp.php - Extract iOS App Store app information
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
    'processor' => 'abo_grabiosapp',
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
                AND ca.name = 'abo_grabiosapp'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending iOS app collection
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabiosapp'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending iOS app data collection';
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
                        AND name = 'abo_grabiosapp'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        $found_apps = [];
        
        // Method 1: Search Apple App Store using iTunes Search API
        // Apple provides a proper API for searching apps
        $search_query = urlencode($company['company_name']);
        $search_url = "https://itunes.apple.com/search?term={$search_query}&country=us&entity=software&limit=25";
        
        $result['debug'] = [
            'company_name' => $company['company_name'],
            'search_url' => $search_url
        ];
        
        $ch = curl_init($search_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BirthdayGold/1.0)');
        
        $api_response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && !empty($api_response)) {
            $api_data = json_decode($api_response, true);
            
            if (isset($api_data['results']) && !empty($api_data['results'])) {
                $result['debug']['api_results_found'] = count($api_data['results']);
                
                // Check each result for relevance
                $company_name_lower = strtolower($company['company_name']);
                
                // Remove common words for better matching
                $company_words = array_filter(explode(' ', $company_name_lower), function($word) {
                    return strlen($word) > 2 && !in_array($word, ['the', 'and', 'inc', 'llc', 'ltd', 'corp', 'company']);
                });
                
                foreach ($api_data['results'] as $app) {
                    $app_name_lower = strtolower($app['trackName'] ?? '');
                    $developer_lower = strtolower($app['sellerName'] ?? '');
                    $bundle_id_lower = strtolower($app['bundleId'] ?? '');
                    
                    $is_relevant = false;
                    
                    // Check if any significant word from company name appears in app name, developer, or bundle ID
                    foreach ($company_words as $word) {
                        if (strpos($app_name_lower, $word) !== false || 
                            strpos($developer_lower, $word) !== false ||
                            strpos($bundle_id_lower, $word) !== false) {
                            $is_relevant = true;
                            break;
                        }
                    }
                    
                    // Special case for specific companies
                    if ($company_name_lower === '1up nutrition') {
                        if (strpos($app_name_lower, '1up') !== false || 
                            strpos($developer_lower, '1up') !== false ||
                            strpos($app_name_lower, 'oneup') !== false || 
                            strpos($developer_lower, 'oneup') !== false) {
                            $is_relevant = true;
                        }
                    }
                    
                    if ($is_relevant) {
                        $found_apps[] = [
                            'id' => $app['trackId'],
                            'bundle_id' => $app['bundleId'],
                            'url' => $app['trackViewUrl'],
                            'name' => $app['trackName'],
                            'developer' => $app['sellerName'],
                            'icon_url' => $app['artworkUrl512'] ?? $app['artworkUrl100'] ?? '',
                            'description' => $app['description'] ?? '',
                            'found_via' => 'itunes_api_search'
                        ];
                        
                        // For now, just take the first relevant app
                        break;
                    }
                }
            }
        }
        
        // Method 2: Check company website for App Store links
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
                // Look for App Store links
                preg_match_all('/href=["\'"]([^"\']*(?:apps\.apple\.com|itunes\.apple\.com)\/[^"\']*app[^"\'"]*)/i', $html, $matches);
                
                foreach ($matches[1] as $app_url) {
                    // Clean up URL
                    $app_url = html_entity_decode($app_url);
                    
                    // Extract app ID from URL
                    if (preg_match('/id(\d+)/', $app_url, $id_match)) {
                        $app_id = $id_match[1];
                        
                        // Standardize URL format
                        $clean_url = "https://apps.apple.com/us/app/id{$app_id}";
                        
                        $found_apps[] = [
                            'id' => $app_id,
                            'url' => $clean_url,
                            'found_via' => 'website_link'
                        ];
                        
                        // Just take the first one for now
                        break;
                    }
                }
            }
        }
        
        // Store results
        if (!empty($found_apps)) {
            // Store the first app in the main table
            $first_app = reset($found_apps);
            $update_sql = "UPDATE bg_companies 
                          SET appapple = :app_url,
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
                                (:company_id, 'data_collection', 'ios_app_data', :app_data, 'active', NOW())";
                $database->query($app_data_sql, [
                    'company_id' => $company_id,
                    'app_data' => json_encode($app)
                ]);
            }
            
            // Successfully found and stored app URL - that's the main goal
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = count($found_apps) . ' iOS app(s) found and URL stored successfully';
            
            if (!empty($first_app['name'])) {
                $result['data_collected'] .= ' - ' . $first_app['name'];
            }
        } else {
            // No apps found - mark as attempted since we tried but didn't achieve the goal
            $status = 'attempted';
            $result['successful'] = 1;
            $result['data_collected'] = 'Searched but no iOS apps found';
            
            // Log that we checked but found nothing
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'data_collection', 'ios_app_search', 'checked_no_apps_found', 'active', NOW())";
            $database->query($attr_sql, ['company_id' => $company_id]);
        }
        
        // Update progress status with details
        $status_details = json_encode([
            'status' => $status,
            'apps_found' => count($found_apps),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, 
                            category = :details,
                            modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabiosapp'";
        $database->query($complete_sql, [
            'status' => $status,
            'details' => $status_details,
            'company_id' => $company_id
        ]);
        
        $database->commit();
        
        // Add matched app to debug if found
        if (!empty($found_apps)) {
            $result['debug']['matched_app'] = reset($found_apps);
        }
        
    } catch (Exception $e) {
        $database->rollBack();
        
        // Update progress to error
        try {
            $error_sql = "UPDATE bg_company_attributes 
                         SET description = 'error', modify_dt = NOW() 
                         WHERE company_id = :company_id 
                         AND type = 'onboarding_progress' 
                         AND name = 'abo_grabiosapp'";
            $database->query($error_sql, ['company_id' => $company_id]);
            
            // Log the error details as an attribute
            $error_log_sql = "INSERT INTO bg_company_attributes 
                             (company_id, type, name, description, status, create_dt)
                             VALUES 
                             (:company_id, 'error_log', :error_type, :error_msg, 'active', NOW())";
            $database->query($error_log_sql, [
                'company_id' => $company_id,
                'error_type' => 'abo_grabiosapp_error',
                'error_msg' => $e->getMessage() . ' at line ' . $e->getLine()
            ]);
        } catch (Exception $updateError) {
            // If we cannot even update the error status, log it
            error_log("Failed to update error status for company $company_id: " . $updateError->getMessage());
        }
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        error_log("ABO grab iOS app error for company $company_id: " . $e->getMessage() . " at line " . $e->getLine());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO grab iOS app fatal error: " . $e->getMessage());
}

// Output JSON response for monitoring
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);