<?php
// abo_grabimages.php - Extract logo and business images from website
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
    'processor' => 'abo_grabimages',
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
                AND ca.name = 'abo_grabimages'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending image collection
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabimages'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending image collection';
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
                        AND name = 'abo_grabimages'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Prepare CDN directory
        $cdn_path = "/mnt/w/BIRTHDAY_SERVER/cdn.birthday.gold/public/images/company_images/{$company_id}";
        if (!is_dir($cdn_path)) {
            mkdir($cdn_path, 0777, true);
        }
        
        // Fetch the company website
        $ch = curl_init($company['company_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $base_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        
        $images_found = [];
        $images_saved = 0;
        
        if ($httpCode === 200 && !empty($html)) {
            // Parse URL for base domain
            $parsed_url = parse_url($base_url);
            $base_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];
            $base_path = dirname($parsed_url['path'] ?? '/');
            if ($base_path === '.') $base_path = '/';
            
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            libxml_clear_errors();
            
            // Priority 1: Look for logo in meta tags
            $xpath = new DOMXPath($dom);
            
            // DISABLED: We don't want to scrape Open Graph/Twitter meta images
            // These are not actual company logos
            /*
            // Check Open Graph image
            $og_images = $xpath->query('//meta[@property="og:image"]/@content');
            foreach ($og_images as $og_img) {
                $img_url = $og_img->nodeValue;
                if (!empty($img_url)) {
                    $images_found['og_logo'] = $img_url;
                }
            }
            
            // Check Twitter image
            $twitter_images = $xpath->query('//meta[@name="twitter:image"]/@content');
            foreach ($twitter_images as $tw_img) {
                $img_url = $tw_img->nodeValue;
                if (!empty($img_url) && !isset($images_found['twitter_logo'])) {
                    $images_found['twitter_logo'] = $img_url;
                }
            }
            */
            
            // Priority 2: Look for logos in common locations
            $logo_selectors = [
                '//img[contains(@class, "logo")]/@src',
                '//img[contains(@id, "logo")]/@src',
                '//img[contains(@alt, "logo")]/@src',
                '//a[contains(@class, "logo")]//img/@src',
                '//header//img/@src',
                '//nav//img/@src',
                '//img[contains(@src, "logo")]/@src'
            ];
            
            foreach ($logo_selectors as $selector) {
                $logos = $xpath->query($selector);
                foreach ($logos as $logo) {
                    $img_url = $logo->nodeValue;
                    if (!empty($img_url) && !isset($images_found['site_logo'])) {
                        $images_found['site_logo'] = $img_url;
                        break 2; // Found one, stop looking
                    }
                }
            }
            
            // DISABLED: We don't want to scrape favicons
            // These are not actual company logos
            /*
            // Priority 3: Look for favicon
            $favicon_selectors = [
                '//link[@rel="icon"]/@href',
                '//link[@rel="shortcut icon"]/@href',
                '//link[@rel="apple-touch-icon"]/@href'
            ];
            
            foreach ($favicon_selectors as $selector) {
                $favicons = $xpath->query($selector);
                foreach ($favicons as $favicon) {
                    $img_url = $favicon->nodeValue;
                    if (!empty($img_url) && !isset($images_found['favicon'])) {
                        $images_found['favicon'] = $img_url;
                        break 2;
                    }
                }
            }
            */
            
            // DISABLED: We don't want to scrape hero/banner images
            // These are not company logos
            /*
            // Priority 4: Get hero/banner images
            $hero_selectors = [
                '//section[contains(@class, "hero")]//img/@src',
                '//div[contains(@class, "banner")]//img/@src',
                '//div[contains(@class, "hero")]//img/@src',
                '//main//img[1]/@src'
            ];
            
            $hero_count = 0;
            foreach ($hero_selectors as $selector) {
                $heroes = $xpath->query($selector);
                foreach ($heroes as $hero) {
                    $img_url = $hero->nodeValue;
                    if (!empty($img_url) && $hero_count < 3) {
                        $images_found['hero_' . $hero_count] = $img_url;
                        $hero_count++;
                    }
                }
                if ($hero_count >= 3) break;
            }
            */
            
            // Process and download found images
            foreach ($images_found as $type => $url) {
                // Make URL absolute
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    if (substr($url, 0, 2) === '//') {
                        $url = $parsed_url['scheme'] . ':' . $url;
                    } elseif (substr($url, 0, 1) === '/') {
                        $url = $base_domain . $url;
                    } else {
                        $url = $base_domain . $base_path . '/' . $url;
                    }
                }
                
                // Skip data URLs
                if (strpos($url, 'data:') === 0) {
                    continue;
                }
                
                // Determine file extension
                $path_info = pathinfo(parse_url($url, PHP_URL_PATH));
                $extension = isset($path_info['extension']) ? strtolower($path_info['extension']) : 'jpg';
                
                // Validate extension
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
                if (!in_array($extension, $allowed_extensions)) {
                    $extension = 'jpg'; // Default
                }
                
                // Generate filename
                $filename = "company_{$company_id}_{$type}.{$extension}";
                $filepath = $cdn_path . '/' . $filename;
                
                // Download image
                $ch = curl_init($url);
                $fp = fopen($filepath, 'wb');
                
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                
                $success = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                fclose($fp);
                
                if ($success && $http_code == 200 && filesize($filepath) > 0) {
                    // Save to database
                    $attr_sql = "INSERT INTO bg_company_attributes 
                                (company_id, type, name, description, category, status, create_dt)
                                VALUES 
                                (:company_id, 'image', :name, :description, 'company_logos', 'active', NOW())";
                    $database->query($attr_sql, [
                        'company_id' => $company_id,
                        'name' => $type,
                        'description' => $filename
                    ]);
                    
                    $images_saved++;
                    
                    // If this is the main logo, mark it as primary
                    if (in_array($type, ['og_logo', 'site_logo']) && !isset($primary_logo_set)) {
                        $primary_sql = "UPDATE bg_company_attributes 
                                      SET `grouping` = 'primary_logo' 
                                      WHERE company_id = :company_id 
                                      AND type = 'image' 
                                      AND name = :name";
                        $database->query($primary_sql, [
                            'company_id' => $company_id,
                            'name' => $type
                        ]);
                        $primary_logo_set = true;
                    }
                } else {
                    // Delete failed download
                    @unlink($filepath);
                }
            }
        }
        
        // Update status based on results
        if ($images_saved > 0) {
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = $images_saved . ' images downloaded';
            $result['images_types'] = array_keys($images_found);
        } else {
            $status = 'attempted';
            $result['successful'] = 1;
            $result['data_collected'] = 'No images could be extracted from website';
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabimages'";
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
                     AND name = 'abo_grabimages'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        session_tracking('ABO grab images error', "Company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    session_tracking('ABO grab images fatal error', $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);