<?php
/**
 * Grab Logos from Apple App Store
 * Admin action to fetch and save company logos
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php';

$cid = $_REQUEST['cid'] ?? null;

if (!$cid) {
    die('Company ID required');
}

// Query to get the Apple App Store URL
$query = "SELECT company_id, company_name, appapple FROM bg_companies WHERE company_id = :cid";
$stmt = $database->prepare($query);
$stmt->execute(['cid' => $cid]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company || !$company['appapple']) {
    die('<div class="alert alert-warning">No Apple App Store URL found for this company.</div>');
}

// Set up error handling
libxml_use_internal_errors(true);
$primaryflag = 'primary_logo';

echo '<div class="logo-fetch-results">';
echo '<p><strong>Company:</strong> ' . htmlspecialchars($company['company_name']) . '</p>';
echo '<p><strong>Apple App Store URL:</strong> <a href="' . htmlspecialchars($company['appapple']) . '" target="_blank">' . htmlspecialchars($company['appapple']) . '</a></p>';
echo '<hr>';

try {
    $company_id = $company['company_id'];
    $url = $company['appapple'];
    
    // Get the page content
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    $pageContent = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }
    curl_close($ch);
    
    if (!$pageContent) {
        throw new Exception('Failed to fetch page content');
    }
    
    // Parse the content
    $dom = new DOMDocument();
    @$dom->loadHTML($pageContent, LIBXML_NOERROR);
    $sourceTags = $dom->getElementsByTagName('source');
    libxml_clear_errors();
    
    $sourceGrouping = 0;
    $totalImagesProcessed = 0;
    
    foreach ($sourceTags as $tag) {
        $sourceGrouping++;
        $srcset = $tag->getAttribute('srcset');
        
        if (!$srcset) continue;
        
        $srcsetValues = explode(',', $srcset);
        
        // Loop through each srcset value
        foreach ($srcsetValues as $index => $srcsetVal) {
            $srcsetParts = explode(' ', trim($srcsetVal));
            if (count($srcsetParts) < 2) continue;
            
            $imageUrl = $srcsetParts[0];
            $sizeTag = str_replace('.', '', $srcsetParts[1]);
            
            // Generate filename
            $destinationFileName = 'company_' . $company_id . '_cat-' . $sourceGrouping . '_set-' . $index . '_' . $sizeTag . '.webp';
            
            // Set up directory path
            $directoryPath = $_SERVER['DOCUMENT_ROOT'] . '/../cdn.birthday.gold/public/images/company_images/' . $company_id;
            
            // Create directory if it doesn't exist
            if (!is_dir($directoryPath)) {
                mkdir($directoryPath, 0777, true);
            }
            
            $filePath = $directoryPath . '/' . $destinationFileName;
            
            // Download and save the image
            $ch = curl_init($imageUrl);
            $fp = fopen($filePath, 'wb');
            
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            
            curl_exec($ch);
            
            if (curl_errno($ch)) {
                fclose($fp);
                echo '<div class="text-danger">Error downloading ' . htmlspecialchars($imageUrl) . ': ' . curl_error($ch) . '</div>';
                curl_close($ch);
                continue;
            }
            
            curl_close($ch);
            fclose($fp);
            
            // Check if file was created successfully
            if (!file_exists($filePath) || filesize($filePath) == 0) {
                echo '<div class="text-danger">Failed to save ' . htmlspecialchars($destinationFileName) . '</div>';
                continue;
            }
            
            // Check if this logo already exists in the database
            $check_sql = "SELECT COUNT(*) FROM bg_company_attributes 
                         WHERE company_id = :company_id 
                         AND description = :description 
                         AND category = 'company_logos'";
            $check_stmt = $database->prepare($check_sql);
            $check_stmt->execute([
                'company_id' => $company_id,
                'description' => $destinationFileName
            ]);
            
            if ($check_stmt->fetchColumn() > 0) {
                echo '<div class="text-warning">Skipped (already exists): ' . htmlspecialchars($destinationFileName) . '</div>';
                continue;
            }
            
            // Insert into the database
            $insert_query = "INSERT INTO bg_company_attributes(company_id, `name`, `description`, category, `grouping`, `rank`, `type`, status, create_dt, modify_dt) 
                            VALUES (:company_id, :name, :description, :category, :grouping, :rank, 'image', 'active', NOW(), NOW())";
            
            $stmt = $database->prepare($insert_query);
            $result = $stmt->execute([
                'company_id' => $company_id,
                'name' => $sizeTag,
                'description' => $destinationFileName,
                'category' => 'company_logos',
                'grouping' => $primaryflag,
                'rank' => $index + 1
            ]);
            
            if ($result) {
                echo '<div class="text-success">✓ Saved: ' . htmlspecialchars($destinationFileName) . '</div>';
                $totalImagesProcessed++;
                // Only the first image should be primary
                $primaryflag = 'logo';
            } else {
                echo '<div class="text-danger">Database error for ' . htmlspecialchars($destinationFileName) . '</div>';
            }
        }
    }
    
    echo '<hr>';
    echo '<div class="alert alert-success">';
    echo '<strong>Process completed!</strong><br>';
    echo 'Total images processed: ' . $totalImagesProcessed;
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">';
    echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}

echo '</div>';

// Log the action
if (isset($company_id)) {
    $user_id = $_SESSION['user']['user_id'] ?? 'unknown';
    $log_sql = "INSERT INTO bg_company_attributes (company_id, category, type, name, description, status, create_dt) 
                VALUES (:company_id, 'audit_log', 'logo_fetch', 'logos_fetched', :description, 'active', NOW())";
    $log_stmt = $database->prepare($log_sql);
    $log_stmt->execute([
        'company_id' => $company_id,
        'description' => "Logos fetched from Apple App Store by user {$user_id}. Total processed: " . ($totalImagesProcessed ?? 0)
    ]);
}
?>