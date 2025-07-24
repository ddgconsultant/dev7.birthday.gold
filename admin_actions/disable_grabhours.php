<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: text/plain');
echo "Disabling abo_grabhours processor\n";
echo "==================================\n\n";

// Check current config
$check_sql = "SELECT * FROM bg_config 
             WHERE config_type = 'automation_processor' 
             AND config_key = 'abo_grabhours'";
$stmt = $database->query($check_sql);
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if ($config) {
    $config_data = json_decode($config['config_data'], true);
    echo "Current config:\n";
    echo "- Name: " . $config['config_value'] . "\n";
    echo "- Enabled: " . ($config_data['enabled'] ?? 'unknown') . "\n\n";
    
    // Update to disabled
    $config_data['enabled'] = false;
    $config_data['disabled_reason'] = 'Business hours are already captured by abo_grablocations processor';
    $config_data['disabled_date'] = date('Y-m-d H:i:s');
    
    $update_sql = "UPDATE bg_config 
                  SET config_data = :data 
                  WHERE config_type = 'automation_processor' 
                  AND config_key = 'abo_grabhours'";
    $database->query($update_sql, ['data' => json_encode($config_data)]);
    
    echo "✓ Disabled abo_grabhours processor\n";
    echo "✓ Reason: Business hours are already captured by abo_grablocations\n\n";
    
    // Update any pending progress records to skip
    $progress_sql = "UPDATE bg_company_attributes 
                    SET description = 'skipped', 
                        modify_dt = NOW() 
                    WHERE type = 'onboarding_progress' 
                    AND name = 'abo_grabhours' 
                    AND description = 'pending'";
    $stmt = $database->query($progress_sql);
    $updated = $stmt->rowCount();
    
    echo "✓ Updated $updated pending progress records to 'skipped'\n";
} else {
    echo "✗ abo_grabhours not found in bg_config\n";
}

echo "\nRecommendation:\n";
echo "- Business hours are captured from:\n";
echo "  1. Website structured data (JSON-LD)\n";
echo "  2. Google Places API (when available)\n";
echo "- Data is stored in bg_company_locations.business_hours\n";
echo "- No need for separate business hours processor\n";