<?php
/**
 * Initialize ABO Auto Review Processor Configuration
 * Run this once to add the auto review processor to the ABO pipeline
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Scheduler key check
$key = $_GET['key'] ?? '';
$schedulerkey = $_SERVER['SCHEDULERKEY'] ?? 'SCHEDULERKEY_HERE';

if ($key !== $schedulerkey) {
    header('HTTP/1.1 403 Forbidden');
    die('Invalid scheduler key');
}

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'actions' => []
];

try {
    // Check if processor already exists
    $check_sql = "SELECT * FROM bg_config 
                  WHERE config_type = 'automation_processor' 
                  AND config_key = 'abo_autoreview'";
    $existing = $database->query($check_sql)->fetch();
    
    if ($existing) {
        $result['actions'][] = 'Processor already exists in configuration';
    } else {
        // Insert the auto review processor configuration
        $config_data = [
            'description' => 'Auto Review Submissions',
            'scheduler' => 'abo_autoreview.php',
            'frequency' => '*/5 * * * *', // Every 5 minutes
            'order' => 0.5, // Run before processsubmission
            'dependencies' => [],
            'enabled' => true
        ];
        
        $insert_sql = "INSERT INTO bg_config 
                       (config_type, config_key, config_value, config_data, create_dt, modify_dt)
                       VALUES 
                       ('automation_processor', 'abo_autoreview', 'Auto Review Submissions', :config_data, NOW(), NOW())";
        
        $database->query($insert_sql, ['config_data' => json_encode($config_data)]);
        
        $result['actions'][] = 'Added abo_autoreview processor to configuration';
    }
    
    // Update existing processsubmission to have dependency on autoreview
    $update_sql = "UPDATE bg_config 
                   SET config_data = JSON_SET(
                       config_data, 
                       '$.dependencies', 
                       JSON_ARRAY('abo_autoreview')
                   ),
                   modify_dt = NOW()
                   WHERE config_type = 'automation_processor' 
                   AND config_key = 'abo_processsubmission'";
    
    $database->query($update_sql);
    $result['actions'][] = 'Updated abo_processsubmission to depend on abo_autoreview';
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['error'] = $e->getMessage();
}

// Output results
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);