<?php
// Add AIRTOP processor to bg_config if it doesn't exist
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if abo_mapformfields_airtop exists in bg_config
$sql = "SELECT * FROM bg_config WHERE config_type = 'automation_processor' AND config_key = 'abo_mapformfields_airtop'";
$stmt = $database->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "AIRTOP processor already exists in bg_config:\n";
    echo "Key: " . $result['config_key'] . "\n";
    echo "Value: " . $result['config_value'] . "\n";
    echo "Status: " . $result['status'] . "\n";
} else {
    // Insert AIRTOP processor
    $insert_sql = "INSERT INTO bg_config 
                   (config_type, config_key, config_value, config_data, display_order, `status`)
                   VALUES 
                   ('automation_processor', 'abo_mapformfields_airtop', 'Map Form Fields (AIRTOP AI)', 
                    :config_data, 25, 'active')";
    
    $config_data = json_encode([
        'description' => 'Uses AIRTOP AI browser automation to intelligently map form fields when HTML scraping finds insufficient fields',
        'scheduler_file' => 'abo_mapformfields_airtop.php',
        'requires_approval' => false,
        'escalation_processor' => true
    ]);
    
    $stmt = $database->prepare($insert_sql);
    $stmt->execute(['config_data' => $config_data]);
    
    echo "SUCCESS: AIRTOP processor has been added to bg_config\n";
    echo "Companies with insufficient form fields will now automatically escalate to AIRTOP AI.\n";
}
?>