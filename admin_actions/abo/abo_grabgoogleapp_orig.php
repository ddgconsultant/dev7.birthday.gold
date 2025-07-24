<?php
// abo_grabgoogleapp.php - Extract Google Play app information
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
                AND ca.description = 'pending'
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
        
        // Fetch the company website
        $ch = curl_init($company['company_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BirthdayGold/1.0)');
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $found_apps = [];
        
        if ($httpCode === 200 && !empty($html)) {
            // Look for Google Play links
            preg_match_all('/href=["\']([^"\']*play\.google\.com\/store\/apps\/details[^"\']*)/i', $html, $matches);
            
            foreach ($matches[1] as $play_url) {
                // Extract app ID from URL
                if (preg_match('/[?&]id=([a-zA-Z0-9._]+)/', $play_url, $id_match)) {
                    $app_id = $id_match[1];
                    $found_apps[$app_id] = [
                        'id' => $app_id,
                        'url' => 'https://play.google.com/store/apps/details?id=' . $app_id,
                        'found_url' => $play_url
                    ];
                }
            }
            
            // Also look for app smart banners in meta tags
            if (preg_match('/<meta[^>]+name=["\']google-play-app["\'][^>]+content=["\'](app-id=)?([a-zA-Z0-9._]+)/i', $html, $meta_match)) {
                $app_id = $meta_match[2];
                if (!isset($found_apps[$app_id])) {
                    $found_apps[$app_id] = [
                        'id' => $app_id,
                        'url' => 'https://play.google.com/store/apps/details?id=' . $app_id,
                        'found_in' => 'meta_tag'
                    ];
                }
            }
        }
        
        // Store results
        if (!empty($found_apps)) {
            foreach ($found_apps as $app) {
                // Store app ID
                $attr_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, status, create_dt)
                            VALUES 
                            (:company_id, 'data_collection', 'google_app_id', :app_id, 'active', NOW())
                            ON DUPLICATE KEY UPDATE
                            description = VALUES(description),
                            modify_dt = NOW()";
                $database->query($attr_sql, [
                    'company_id' => $company_id,
                    'app_id' => $app['id']
                ]);
                
                // Store app URL
                $url_sql = "INSERT INTO bg_company_attributes 
                           (company_id, type, name, description, status, create_dt)
                           VALUES 
                           (:company_id, 'data_collection', 'google_app_url', :app_url, 'active', NOW())
                           ON DUPLICATE KEY UPDATE
                           description = VALUES(description),
                           modify_dt = NOW()";
                $database->query($url_sql, [
                    'company_id' => $company_id,
                    'app_url' => $app['url']
                ]);
            }
            
            // Update main company record if field exists
            $update_sql = "UPDATE bg_companies 
                          SET google_app_id = :app_id,
                              modify_dt = NOW()
                          WHERE company_id = :company_id";
            $database->query($update_sql, [
                'app_id' => array_keys($found_apps)[0], // Use first app found
                'company_id' => $company_id
            ]);
            
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = count($found_apps) . ' Google Play app(s) found';
        } else {
            // No apps found - mark as completed anyway
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = 'No Google Play apps found';
            
            // Log that we checked but found nothing
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'data_collection', 'google_app_search', 'checked_no_apps_found', 'active', NOW())";
            $database->query($attr_sql, ['company_id' => $company_id]);
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabgoogleapp'";
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
                     AND name = 'abo_grabgoogleapp'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        error_log("ABO grab Google app error for company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO grab Google app fatal error: " . $e->getMessage());
}

// Output JSON response for monitoring
header('Content-Type: application/json');
echo json_encode($result);