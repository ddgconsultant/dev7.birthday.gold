<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

header('Content-Type: text/plain');
echo "Business Hours Check for Company ID: $company_id\n";
echo "================================================\n\n";

// Check locations with business hours
$sql = "SELECT location_id, address, city, state, business_hours, source, create_dt 
        FROM bg_company_locations 
        WHERE company_id = :company_id 
        ORDER BY create_dt DESC";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($locations) . " locations:\n\n";

foreach ($locations as $i => $loc) {
    echo ($i + 1) . ". {$loc['address']}, {$loc['city']}, {$loc['state']}\n";
    echo "   Source: {$loc['source']}\n";
    echo "   Business Hours: " . (!empty($loc['business_hours']) ? $loc['business_hours'] : 'NOT CAPTURED') . "\n";
    echo "   Created: {$loc['create_dt']}\n\n";
}

// Check if abo_grabhours exists in config
echo "\nChecking if abo_grabhours is configured:\n";
$config_sql = "SELECT * FROM bg_config 
              WHERE config_type = 'automation_processor' 
              AND config_key = 'abo_grabhours'";
$stmt = $database->query($config_sql);
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if ($config) {
    echo "- Found in bg_config: " . $config['config_value'] . "\n";
    echo "- Status: " . (json_decode($config['config_data'], true)['enabled'] ?? 'unknown') . "\n";
} else {
    echo "- NOT found in bg_config\n";
}

// Check onboarding progress
echo "\nOnboarding progress for abo_grabhours:\n";
$progress_sql = "SELECT * FROM bg_company_attributes 
                WHERE company_id = :company_id 
                AND type = 'onboarding_progress' 
                AND name = 'abo_grabhours'";
$stmt = $database->query($progress_sql, ['company_id' => $company_id]);
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($progress as $p) {
    echo "- Status: {$p['description']}, Created: {$p['create_dt']}\n";
}