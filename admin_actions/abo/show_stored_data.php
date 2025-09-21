<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

// Get company basic data
$company = $database->query("SELECT company_id, company_name, company_url, 
    appgoogle, appapple, facebook, twitter, instagram, tiktok,
    status, create_dt, modify_dt 
    FROM bg_companies WHERE company_id = :id", 
    ['id' => $company_id])->fetch(PDO::FETCH_ASSOC);

// Get all attributes grouped by type
$sql = "SELECT type, name, description, status, create_dt 
        FROM bg_company_attributes 
        WHERE company_id = :id 
        ORDER BY type, create_dt DESC";
$stmt = $database->query($sql, ['id' => $company_id]);
$attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by type
$grouped = [];
foreach ($attributes as $attr) {
    $grouped[$attr['type']][] = $attr;
}

// Output formatted results
header('Content-Type: text/plain');
echo "=== COMPANY DATA STORAGE SUMMARY ===\n";
echo "Company ID: {$company['company_id']}\n";
echo "Company Name: {$company['company_name']}\n";
echo "Status: {$company['status']}\n\n";

echo "=== MAIN TABLE (bg_companies) ===\n";
echo "Website: {$company['company_url']}\n";
echo "Google Play App: " . ($company['appgoogle'] ?: 'Not found') . "\n";
echo "Apple App Store: " . ($company['appapple'] ?: 'Not found') . "\n";
echo "Facebook: " . ($company['facebook'] ?: 'Not found') . "\n";
echo "Twitter: " . ($company['twitter'] ?: 'Not found') . "\n";
echo "Instagram: " . ($company['instagram'] ?: 'Not found') . "\n";
echo "TikTok: " . ($company['tiktok'] ?: 'Not found') . "\n\n";

echo "=== ATTRIBUTES TABLE (bg_company_attributes) ===\n";
foreach ($grouped as $type => $items) {
    echo "\n--- Type: $type ---\n";
    foreach ($items as $item) {
        $desc = $item['description'];
        if (strlen($desc) > 100) {
            $desc = substr($desc, 0, 97) . '...';
        }
        echo "  [{$item['name']}]: $desc\n";
    }
}

// Show image files if they exist
$cdn_path = "/mnt/w/BIRTHDAY_SERVER/cdn.birthday.gold/public/images/company_images/{$company_id}/";
if (is_dir($cdn_path)) {
    echo "\n=== CDN FILES ===\n";
    echo "Directory: $cdn_path\n";
    $files = scandir($cdn_path);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $size = filesize($cdn_path . $file);
            echo "  - $file (" . number_format($size) . " bytes)\n";
        }
    }
} else {
    echo "\n=== CDN FILES ===\n";
    echo "No CDN directory found for this company\n";
}