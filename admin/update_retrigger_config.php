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

echo "<h1>Updating Retrigger Configuration</h1><pre>";

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
            'cooldown_minutes' => 5,
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
            'config_data' => json_encode($config_data, JSON_PRETTY_PRINT),
            'config_key' => $config_key
        ]);
        
        echo "✅ Updated: {$config_key}\n";
        echo "   Display Name: {$settings['display_name']}\n";
        echo "   Retrigger Enabled: " . ($settings['retrigger_enabled'] ? 'Yes' : 'No') . "\n";
        echo "   Allowed Statuses: " . implode(', ', $settings['retrigger_statuses']) . "\n";
        echo "   Max Attempts: {$settings['max_retrigger_count']}\n\n";
        
    } else {
        echo "❌ Not found: {$config_key}\n";
        echo "   This processor needs to be added to bg_config first.\n\n";
    }
}

echo "</pre>";

// Display all automation processors with retrigger status
echo "<h2>All Automation Processors</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Key</th><th>Display Name</th><th>Retrigger</th><th>Allowed Statuses</th></tr>";

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
    $allowed_statuses = $config_data['retrigger']['allowed_statuses'] ?? [];
    
    echo "<tr>";
    echo "<td>{$proc['config_key']}</td>";
    echo "<td>{$proc['config_value']}</td>";
    echo "<td>" . ($has_retrigger ? ($retrigger_enabled ? '✅ Enabled' : '❌ Disabled') : '⚠️ Not configured') . "</td>";
    echo "<td>" . ($has_retrigger && $retrigger_enabled ? implode(', ', $allowed_statuses) : '-') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='/admin/abo-status'>Back to ABO Status</a></p>";
?>