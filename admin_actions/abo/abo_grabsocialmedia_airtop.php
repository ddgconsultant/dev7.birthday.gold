<?php
/**
 * abo_grabsocialmedia_airtop.php - Enhanced social media extraction using AIRTOP
 * This escalated version uses browser automation to find social media links
 * that may be hidden in JavaScript, footers, or require interaction
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Ensure we have AIRTOP credentials
if (!isset($sitesettings_ai['airtop_api_key'])) {
    die(json_encode(['status' => 'error', 'message' => 'AIRTOP API key not configured']));
}

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
    'processor' => 'abo_grabsocialmedia_airtop',
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
        // Get companies that have failed regular social media extraction
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                LEFT JOIN bg_company_attributes ca2 ON c.company_id = ca2.company_id 
                    AND ca2.type = 'social_media' AND ca2.name = 'profiles'
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabsocialmedia'
                AND ca.description IN ('completed', 'attempted')
                AND (ca2.id IS NULL OR JSON_LENGTH(ca2.value) < 3)
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies need AIRTOP social media extraction';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    $company_url = $company['company_url'];
    
    try {
        $database->beginTransaction();
        
        // Update progress to indicate AIRTOP processing
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'airtop_processing', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabsocialmedia'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Prepare AIRTOP API request
        $airtop_prompt = [
            "url" => $company_url,
            "task" => "Find all social media links for this business. Look for Facebook, Twitter/X, Instagram, LinkedIn, YouTube, and TikTok profiles.",
            "instructions" => [
                "1. Start at the homepage and look for social media icons or links (often in header or footer)",
                "2. If not found on homepage, check these pages in order: Contact, About, Footer links",
                "3. Look for social media icons that might be images with links",
                "4. Check for JavaScript-rendered content by waiting for page to fully load",
                "5. Extract the full URL for each social media profile found",
                "6. Return results as JSON with platform names as keys"
            ],
            "expected_output" => [
                "facebook" => "full URL to Facebook page/profile",
                "twitter" => "full URL to Twitter/X profile",
                "instagram" => "full URL to Instagram profile",
                "linkedin" => "full URL to LinkedIn company page",
                "youtube" => "full URL to YouTube channel",
                "tiktok" => "full URL to TikTok profile"
            ],
            "wait_for" => "networkidle",
            "timeout" => 30000,
            "viewport" => ["width" => 1920, "height" => 1080]
        ];
        
        // Call AIRTOP API
        $ch = curl_init('https://api.airtop.ai/v1/browser/extract');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $sitesettings_ai['airtop_api_key'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($airtop_prompt));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("AIRTOP API error: HTTP $http_code - $response");
        }
        
        $airtop_result = json_decode($response, true);
        
        if (!$airtop_result || isset($airtop_result['error'])) {
            throw new Exception("AIRTOP API error: " . ($airtop_result['error'] ?? 'Invalid response'));
        }
        
        // Process the results
        $social_media_found = [];
        $social_media_data = $airtop_result['data'] ?? [];
        
        // Define the 6 platforms we care about
        $target_platforms = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok'];
        
        foreach ($target_platforms as $platform) {
            if (!empty($social_media_data[$platform])) {
                $url = $social_media_data[$platform];
                
                // Validate the URL
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    // Additional validation based on platform
                    $valid = false;
                    switch ($platform) {
                        case 'facebook':
                            $valid = strpos($url, 'facebook.com') !== false || strpos($url, 'fb.com') !== false;
                            break;
                        case 'twitter':
                            $valid = strpos($url, 'twitter.com') !== false || strpos($url, 'x.com') !== false;
                            break;
                        case 'instagram':
                            $valid = strpos($url, 'instagram.com') !== false;
                            break;
                        case 'linkedin':
                            $valid = strpos($url, 'linkedin.com') !== false;
                            break;
                        case 'youtube':
                            $valid = strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false;
                            break;
                        case 'tiktok':
                            $valid = strpos($url, 'tiktok.com') !== false;
                            break;
                    }
                    
                    if ($valid) {
                        $social_media_found[$platform] = [
                            'url' => $url,
                            'handle' => extractHandleFromUrl($url, $platform),
                            'verified' => false,
                            'source' => 'airtop'
                        ];
                    }
                }
            }
        }
        
        // Store the enhanced results
        $social_json = json_encode($social_media_found);
        
        // Check if we already have social media data
        $check_sql = "SELECT id FROM bg_company_attributes 
                     WHERE company_id = :company_id 
                     AND type = 'social_media' 
                     AND name = 'profiles'";
        $check_stmt = $database->query($check_sql, ['company_id' => $company_id]);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing record
            $update_sql = "UPDATE bg_company_attributes 
                          SET value = :value, 
                              description = CONCAT('AIRTOP enhanced: ', :count, ' platforms found'),
                              modify_dt = NOW() 
                          WHERE id = :id";
            $database->query($update_sql, [
                'value' => $social_json,
                'count' => count($social_media_found),
                'id' => $existing['id']
            ]);
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO bg_company_attributes 
                          (company_id, type, name, value, description, status, create_dt) 
                          VALUES (:company_id, 'social_media', 'profiles', :value, :description, 'active', NOW())";
            $database->query($insert_sql, [
                'company_id' => $company_id,
                'value' => $social_json,
                'description' => 'AIRTOP enhanced: ' . count($social_media_found) . ' platforms found'
            ]);
        }
        
        // Update progress
        $final_status = count($social_media_found) >= 3 ? 'airtop_completed' : 'airtop_partial';
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabsocialmedia'";
        $database->query($progress_sql, [
            'status' => $final_status,
            'company_id' => $company_id
        ]);
        
        $database->commit();
        
        $result['successful'] = 1;
        $result['company'] = $company_name;
        $result['social_media_found'] = count($social_media_found);
        $result['platforms'] = array_keys($social_media_found);
        
    } catch (Exception $e) {
        $database->rollback();
        
        // Log error
        $error_sql = "INSERT INTO bg_company_attributes 
                     (company_id, type, name, value, description, status, create_dt) 
                     VALUES (:company_id, 'abo_error', 'abo_grabsocialmedia_airtop', :error, 'AIRTOP social media extraction failed', 'active', NOW())";
        $database->query($error_sql, [
            'company_id' => $company_id,
            'error' => json_encode(['error' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')])
        ]);
        
        // Update progress to error
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'airtop_error', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabsocialmedia'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'error' => $e->getMessage()
        ];
    }
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
}

// Output results
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);

/**
 * Extract social media handle from URL
 */
function extractHandleFromUrl($url, $platform) {
    $parsed = parse_url($url);
    $path = isset($parsed['path']) ? trim($parsed['path'], '/') : '';
    
    switch ($platform) {
        case 'facebook':
            // Remove common prefixes
            $path = preg_replace('/^(pages\/|pg\/)/', '', $path);
            return explode('/', $path)[0] ?? '';
            
        case 'twitter':
            return str_replace('@', '', explode('/', $path)[0] ?? '');
            
        case 'instagram':
            return explode('/', $path)[0] ?? '';
            
        case 'linkedin':
            // Extract company name from /company/name format
            if (preg_match('/company\/([^\/]+)/', $path, $matches)) {
                return $matches[1];
            }
            return '';
            
        case 'youtube':
            // Handle various YouTube URL formats
            if (preg_match('/(c|channel|user)\/([^\/]+)/', $path, $matches)) {
                return $matches[2];
            }
            return '';
            
        case 'tiktok':
            // TikTok uses @ in URLs
            return str_replace('@', '', explode('/', $path)[0] ?? '');
    }
    
    return '';
}
?>