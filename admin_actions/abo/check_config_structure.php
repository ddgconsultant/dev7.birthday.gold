<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get the config for abo_mapformfields
$sql = "SELECT config_key, config_value, config_data FROM bg_config 
        WHERE config_type = 'automation_processor' 
        AND config_key IN ('abo_mapformfields', 'abo_mapformfields_airtop')";
$stmt = $database->query($sql);
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');

$output = [];
foreach ($configs as $config) {
    $data = json_decode($config['config_data'], true);
    $output[$config['config_key']] = [
        'display_name' => $config['config_value'],
        'config_data' => $data,
        'has_retrigger' => isset($data['retrigger']),
        'retrigger_enabled' => $data['retrigger']['enabled'] ?? false,
        'allowed_statuses' => $data['retrigger']['allowed_statuses'] ?? []
    ];
}

echo json_encode($output, JSON_PRETTY_PRINT);
?>