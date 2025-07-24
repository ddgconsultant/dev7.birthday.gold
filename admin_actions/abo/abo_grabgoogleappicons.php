<?php
// abo_grabgoogleappicons.php - Extract Google Play app icons
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
    'processor' => 'abo_grabgoogleappicons',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => [],
    'images_downloaded' => []
];

try {
    // Get companies to process - those that have appgoogle URL but no icons yet
    if ($specific_company_id) {
        $sql = "SELECT c.* FROM bg_companies c 
                WHERE c.company_id = :company_id 
                AND c.appgoogle IS NOT NULL
                AND c.appgoogle != ''
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // For automatic processing
        $sql = "SELECT c.* FROM bg_companies c 
                WHERE c.appgoogle IS NOT NULL
                AND c.appgoogle != ''
                AND NOT EXISTS (
                    SELECT 1 FROM bg_company_attributes ca 
                    WHERE ca.company_id = c.company_id 
                    AND ca.type = 'image' 
                    AND ca.category = 'google_app_icon'
                )
                ORDER BY c.create_dt DESC
                LIMIT 5";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($companies as $company) {
        $result['processed']++;
        $company_id = $company['company_id'];
        $app_url = $company['appgoogle'];
        
        try {
            // Extract app ID from URL
            if (!preg_match('/[?&]id=([a-zA-Z0-9._]+)/', $app_url, $id_match)) {
                throw new Exception("Could not extract app ID from URL: $app_url");
            }
            
            $app_id = $id_match[1];
            
            // Fetch the app page
            $ch = curl_init($app_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception("Failed to fetch app page: HTTP $httpCode");
            }
            
            $icons_found = [];
            
            // Method 1: Look for the main app icon (usually in a specific img tag)
            // Google Play uses various patterns for app icons
            $patterns = [
                // Pattern 1: Direct img with T75of class
                '/<img[^>]*class="[^"]*T75of[^"]*"[^>]*src="([^"]+)"[^>]*>/i',
                // Pattern 2: Icon with alt attribute
                '/<img[^>]*alt="[^"]*(?:icon|logo)[^"]*"[^>]*src="([^"]+)"[^>]*>/i',
                // Pattern 3: Any img with =w240 or =s180 sizing (common for Play Store icons)
                '/<img[^>]*src="([^"]+(?:=w240|=s180|=w512)[^"]*)"[^>]*>/i',
                // Pattern 4: Look in meta tags
                '/<meta[^>]*property="og:image"[^>]*content="([^"]+)"[^>]*>/i'
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $html, $matches)) {
                    foreach ($matches[1] as $icon_url) {
                        // Clean up the URL
                        if (strpos($icon_url, '//') === 0) {
                            $icon_url = 'https:' . $icon_url;
                        } elseif (strpos($icon_url, '/') === 0) {
                            $icon_url = 'https://play.google.com' . $icon_url;
                        }
                        
                        // Skip if it's not an image
                        if (!preg_match('/\.(jpg|jpeg|png|webp|gif)/i', $icon_url) && 
                            !preg_match('/googleusercontent\.com/i', $icon_url)) {
                            continue;
                        }
                        
                        $icons_found[] = $icon_url;
                    }
                }
            }
            
            // Also try to find different sizes by modifying known patterns
            if (!empty($icons_found)) {
                $base_icon = $icons_found[0];
                
                // Google often uses size parameters like =w240, =s180
                $sizes = ['=w48', '=w96', '=w192', '=w240', '=w512'];
                
                foreach ($sizes as $size) {
                    // Replace existing size parameter or add it
                    if (preg_match('/=[ws]\d+/', $base_icon)) {
                        $sized_url = preg_replace('/=[ws]\d+/', $size, $base_icon);
                    } else {
                        $sized_url = $base_icon . $size;
                    }
                    
                    if (!in_array($sized_url, $icons_found)) {
                        $icons_found[] = $sized_url;
                    }
                }
            }
            
            // Remove duplicates
            $icons_found = array_unique($icons_found);
            
            if (empty($icons_found)) {
                // Try one more method - search for app ID in image URLs
                if (preg_match_all('/<img[^>]*src="([^"]*' . preg_quote($app_id, '/') . '[^"]*)"[^>]*>/i', $html, $matches)) {
                    $icons_found = array_unique($matches[1]);
                }
            }
            
            // Directory setup
            $directoryPath = 'W:/BIRTHDAY_SERVER/cdn.birthday.gold/public/images/company_images/' . $company_id;
            if (!is_dir($directoryPath)) {
                mkdir($directoryPath, 0777, true);
            }
            
            $downloaded_count = 0;
            $grouping = 'google_app_icon';
            
            foreach ($icons_found as $index => $icon_url) {
                // Skip if we already have enough icons
                if ($downloaded_count >= 5) break;
                
                // Determine size from URL if possible
                $size_tag = 'unknown';
                if (preg_match('/[=_](?:w|s)(\d+)/', $icon_url, $size_match)) {
                    $size_tag = $size_match[1] . 'px';
                } elseif (preg_match('/(\d+)x\d+/', $icon_url, $size_match)) {
                    $size_tag = $size_match[1] . 'px';
                }
                
                $extension = 'png'; // default
                if (preg_match('/\.([a-z]+)(?:\?|$)/i', $icon_url, $ext_match)) {
                    $extension = strtolower($ext_match[1]);
                }
                
                $destinationFileName = 'googleapp_' . $company_id . '_' . $index . '_' . $size_tag . '.' . $extension;
                $filePath = $directoryPath . '/' . $destinationFileName;
                
                // Download the image
                $ch = curl_init($icon_url);
                $fp = fopen($filePath, 'wb');
                
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                
                curl_exec($ch);
                $curl_error = curl_error($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                fclose($fp);
                
                // Check if download was successful
                if ($curl_error || $http_code !== 200 || filesize($filePath) < 100) {
                    unlink($filePath);
                    continue;
                }
                
                // Insert into database
                $insert_sql = "INSERT INTO bg_company_attributes 
                              (company_id, name, description, category, `grouping`, `rank`, type, create_dt, modify_dt) 
                              VALUES 
                              (:company_id, :name, :description, :category, :grouping, :rank, 'image', NOW(), NOW())";
                
                $database->query($insert_sql, [
                    'company_id' => $company_id,
                    'name' => $size_tag,
                    'description' => $destinationFileName,
                    'category' => 'google_app_icon',
                    'grouping' => $grouping,
                    'rank' => $index + 1
                ]);
                
                $downloaded_count++;
                $result['images_downloaded'][] = [
                    'company_id' => $company_id,
                    'file' => $destinationFileName,
                    'size' => $size_tag,
                    'source_url' => $icon_url
                ];
            }
            
            if ($downloaded_count > 0) {
                $result['successful']++;
                
                // Log success
                $log_sql = "INSERT INTO bg_company_attributes 
                           (company_id, type, name, description, status, create_dt)
                           VALUES 
                           (:company_id, 'data_collection', 'google_app_icons_collected', :count, 'active', NOW())";
                $database->query($log_sql, [
                    'company_id' => $company_id,
                    'count' => $downloaded_count . ' icons downloaded'
                ]);
            } else {
                throw new Exception("No icons could be downloaded");
            }
            
        } catch (Exception $e) {
            $result['failed']++;
            $result['errors'][] = "Company $company_id: " . $e->getMessage();
            error_log("ABO grab Google app icons error for company $company_id: " . $e->getMessage());
        }
    }
    
    $result['message'] = "Processed {$result['processed']} companies: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO grab Google app icons fatal error: " . $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);