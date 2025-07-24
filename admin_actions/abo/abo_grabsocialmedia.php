<?php
// abo_grabsocialmedia.php - Enhanced social media extraction with footer priority
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
    'processor' => 'abo_grabsocialmedia',
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
                AND ca.name = 'abo_grabsocialmedia'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending social media collection
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabsocialmedia'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending social media data collection';
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
                        AND name = 'abo_grabsocialmedia'";
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
        
        $social_media_found = [];
        
        if ($httpCode === 200 && !empty($html)) {
            // Method 1: Look for social media links in footer area (most reliable)
            // Extract footer content if possible
            $footer_html = '';
            if (preg_match('/<footer[^>]*>(.*?)<\/footer>/is', $html, $footer_match)) {
                $footer_html = $footer_match[1];
            }
            
            // If no footer tag, try common footer class/id patterns
            if (empty($footer_html)) {
                if (preg_match('/<div[^>]+(?:class|id)=["\'][^"\']*footer[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $footer_match)) {
                    $footer_html = $footer_match[1];
                }
            }
            
            // Search in footer first, then fall back to full HTML
            $search_areas = [];
            if (!empty($footer_html)) {
                $search_areas['footer'] = $footer_html;
            }
            $search_areas['full'] = $html;
            
            // Define social media patterns
            $social_patterns = [
                'facebook' => [
                    'domains' => ['facebook.com', 'fb.com'],
                    'path_pattern' => '/^\/([a-zA-Z0-9._-]+)\/?$/',
                    'skip_paths' => ['share', 'sharer', 'groups', 'pages', 'events', 'marketplace', 'watch', 'gaming', 'login', 'policies', 'help', 'about']
                ],
                'twitter' => [
                    'domains' => ['twitter.com', 'x.com'],
                    'path_pattern' => '/^\/([a-zA-Z0-9_]+)\/?$/',
                    'skip_paths' => ['share', 'intent', 'i', 'home', 'explore', 'notifications', 'messages', 'search', 'login', 'signup']
                ],
                'instagram' => [
                    'domains' => ['instagram.com', 'instagr.am'],
                    'path_pattern' => '/^\/([a-zA-Z0-9._]+)\/?$/',
                    'skip_paths' => ['p', 'reel', 'tv', 'explore', 'accounts', 'directory', 'developer', 'about', 'legal', 'privacy']
                ],
                'tiktok' => [
                    'domains' => ['tiktok.com'],
                    'path_pattern' => '/^\/@([a-zA-Z0-9._]+)\/?$/',
                    'skip_paths' => ['foryou', 'discover', 'upload', 'login', 'signup', 'about']
                ]
            ];
            
            // Search each area
            foreach ($search_areas as $area_name => $area_html) {
                // Look for all links
                if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $area_html, $link_matches)) {
                    foreach ($link_matches[1] as $link) {
                        $link = html_entity_decode(trim($link));
                        
                        // Skip if already found all platforms
                        if (count($social_media_found) >= 4) break;
                        
                        // Parse the URL
                        $parsed = parse_url($link);
                        if (!isset($parsed['host'])) continue;
                        
                        $host = strtolower(str_replace('www.', '', $parsed['host']));
                        $path = isset($parsed['path']) ? $parsed['path'] : '/';
                        
                        // Check each social platform
                        foreach ($social_patterns as $platform => $config) {
                            // Skip if already found
                            if (isset($social_media_found[$platform])) continue;
                            
                            // Check if it's this platform
                            if (!in_array($host, $config['domains'])) continue;
                            
                            // Validate the path
                            if (preg_match($config['path_pattern'], $path, $path_match)) {
                                $username = isset($path_match[1]) ? $path_match[1] : '';
                                
                                // Skip common non-profile paths
                                if (in_array(strtolower($username), $config['skip_paths'])) continue;
                                
                                // Skip if username is too short or numeric
                                if (strlen($username) < 2 || is_numeric($username)) continue;
                                
                                // Found valid social media link
                                $social_media_found[$platform] = $link;
                                
                                // If found in footer, we're more confident, so stop looking for this platform
                                if ($area_name === 'footer') {
                                    break;
                                }
                            }
                        }
                    }
                }
                
                // If we found links in footer, don't search full HTML
                if ($area_name === 'footer' && !empty($social_media_found)) {
                    break;
                }
            }
            
            // Method 2: Look for meta tags (Twitter cards, Open Graph)
            if (!isset($social_media_found['twitter'])) {
                if (preg_match('/<meta[^>]+(?:name|property)=["\']twitter:creator["\'][^>]+content=["\']@?([a-zA-Z0-9_]+)["\'][^>]*>/i', $html, $twitter_meta)) {
                    $social_media_found['twitter'] = 'https://twitter.com/' . $twitter_meta[1];
                }
            }
            
            if (!isset($social_media_found['facebook'])) {
                if (preg_match('/<meta[^>]+property=["\']article:author["\'][^>]+content=["\']([^"\']*facebook\.com\/[^"\']+)["\'][^>]*>/i', $html, $fb_meta)) {
                    $social_media_found['facebook'] = $fb_meta[1];
                }
            }
        }
        
        // Update the company record with found social media
        $update_fields = [];
        $update_params = ['company_id' => $company_id];
        
        foreach (['facebook', 'twitter', 'instagram', 'tiktok'] as $platform) {
            if (isset($social_media_found[$platform])) {
                $update_fields[] = "$platform = :$platform";
                $update_params[$platform] = $social_media_found[$platform];
            }
        }
        
        if (!empty($update_fields)) {
            $update_sql = "UPDATE bg_companies SET " . implode(', ', $update_fields) . ", modify_dt = NOW() WHERE company_id = :company_id";
            $database->query($update_sql, $update_params);
            
            // Store as attributes too for tracking
            foreach ($social_media_found as $platform => $url) {
                $attr_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, status, create_dt)
                            VALUES 
                            (:company_id, 'data_collection', :platform, :url, 'active', NOW())";
                $database->query($attr_sql, [
                    'company_id' => $company_id,
                    'platform' => $platform . '_url',
                    'url' => $url
                ]);
            }
            
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = count($social_media_found) . ' social media platform(s) found: ' . implode(', ', array_keys($social_media_found));
        } else {
            // No social media found - mark as attempted
            $status = 'attempted';
            $result['successful'] = 1;
            $result['data_collected'] = 'No social media links found on website';
            
            // Log that we checked
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'data_collection', 'social_media_search', 'checked_none_found', 'active', NOW())";
            $database->query($attr_sql, ['company_id' => $company_id]);
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabsocialmedia'";
        $database->query($complete_sql, [
            'status' => $status,
            'company_id' => $company_id
        ]);
        
        $database->commit();
        
        // Add found social media to result
        $result['social_media_found'] = $social_media_found;
        
    } catch (Exception $e) {
        $database->rollBack();
        
        // Update progress to error
        $error_sql = "UPDATE bg_company_attributes 
                     SET description = 'error', modify_dt = NOW() 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress' 
                     AND name = 'abo_grabsocialmedia'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        error_log("ABO grab social media error for company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO grab social media fatal error: " . $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);