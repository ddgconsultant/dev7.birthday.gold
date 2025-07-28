<?php
// Update bg_config entries to include retrigger configuration
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Define the processors that need retrigger configuration
$processors_to_update = [
    'abo_mapformfields' => [
        'display_name' => 'Map Form Fields',
        'retrigger_statuses' => ['completed', 'error', 'attempted'],
        'retrigger_enabled' => true,
        'max_retrigger_count' => 3
    ],
    'abo_mapformfields_airtop' => [
        'display_name' => 'Map Form Fields (AIRTOP AI)',
        'retrigger_statuses' => ['completed', 'error', 'attempted'],
        'retrigger_enabled' => true,
        'max_retrigger_count' => 2
    ]
];

echo "Updating bg_config entries with retrigger configuration...\n\n";

foreach ($processors_to_update as $config_key => $settings) {
    // Fetch existing config
    $sql = "SELECT * FROM bg_config 
            WHERE config_type = 'automation_processor' 
            AND config_key = :config_key";
    $stmt = $database->prepare($sql);
    $stmt->execute(['config_key' => $config_key]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Parse existing config_data
        $config_data = json_decode($existing['config_data'], true) ?: [];
        
        // Add retrigger configuration
        $config_data['retrigger'] = [
            'enabled' => $settings['retrigger_enabled'],
            'allowed_statuses' => $settings['retrigger_statuses'],
            'max_attempts' => $settings['max_retrigger_count'],
            'cooldown_minutes' => 5, // Prevent rapid retriggers
            'description' => 'Allows manual retrigger when task is in specified statuses'
        ];
        
        // Update the config
        $update_sql = "UPDATE bg_config 
                      SET config_value = :config_value,
                          config_data = :config_data,
                          updated_at = NOW()
                      WHERE config_type = 'automation_processor' 
                      AND config_key = :config_key";
        
        $stmt = $database->prepare($update_sql);
        $stmt->execute([
            'config_value' => $settings['display_name'],
            'config_data' => json_encode($config_data),
            'config_key' => $config_key
        ]);
        
        echo "Updated: {$config_key}\n";
        echo "  Display Name: {$settings['display_name']}\n";
        echo "  Retrigger Enabled: " . ($settings['retrigger_enabled'] ? 'Yes' : 'No') . "\n";
        echo "  Allowed Statuses: " . implode(', ', $settings['retrigger_statuses']) . "\n";
        echo "  Max Attempts: {$settings['max_retrigger_count']}\n\n";
        
    } else if ($config_key === 'abo_mapformfields_airtop') {
        // Insert new AIRTOP processor with retrigger config
        $config_data = [
            'description' => 'Uses AIRTOP AI browser automation to intelligently map form fields when HTML scraping finds insufficient fields',
            'scheduler_file' => 'abo_mapformfields_airtop.php',
            'requires_approval' => false,
            'escalation_processor' => true,
            'retrigger' => [
                'enabled' => $settings['retrigger_enabled'],
                'allowed_statuses' => $settings['retrigger_statuses'],
                'max_attempts' => $settings['max_retrigger_count'],
                'cooldown_minutes' => 5,
                'description' => 'Allows manual retrigger when task is in specified statuses'
            ]
        ];
        
        $insert_sql = "INSERT INTO bg_config 
                      (config_type, config_key, config_value, config_data, display_order, is_active)
                      VALUES 
                      ('automation_processor', :config_key, :config_value, :config_data, 25, 1)";
        
        $stmt = $database->prepare($insert_sql);
        $stmt->execute([
            'config_key' => $config_key,
            'config_value' => $settings['display_name'],
            'config_data' => json_encode($config_data)
        ]);
        
        echo "Inserted: {$config_key}\n";
        echo "  Display Name: {$settings['display_name']}\n";
        echo "  Retrigger Enabled: " . ($settings['retrigger_enabled'] ? 'Yes' : 'No') . "\n";
        echo "  Allowed Statuses: " . implode(', ', $settings['retrigger_statuses']) . "\n";
        echo "  Max Attempts: {$settings['max_retrigger_count']}\n\n";
    } else {
        echo "Not found: {$config_key}\n\n";
    }
}

echo "Configuration update complete!\n";

// Display all automation processors with retrigger status
echo "\n--- All Automation Processors ---\n";
$all_sql = "SELECT config_key, config_value, config_data 
            FROM bg_config 
            WHERE config_type = 'automation_processor' 
            ORDER BY display_order";
$stmt = $database->query($all_sql);
$all_processors = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($all_processors as $proc) {
    $config_data = json_decode($proc['config_data'], true) ?: [];
    $has_retrigger = isset($config_data['retrigger']);
    $retrigger_enabled = $config_data['retrigger']['enabled'] ?? false;
    
    echo "{$proc['config_key']}: {$proc['config_value']}\n";
    echo "  Retrigger: " . ($has_retrigger ? ($retrigger_enabled ? 'Enabled' : 'Disabled') : 'Not configured') . "\n";
    if ($has_retrigger && $retrigger_enabled) {
        echo "  Allowed statuses: " . implode(', ', $config_data['retrigger']['allowed_statuses']) . "\n";
    }
    echo "\n";
}
?>