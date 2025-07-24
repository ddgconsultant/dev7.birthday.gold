<?php
include('../core/site-controller.php');

// Check if the form field mapping processor already exists
$check_sql = "SELECT * FROM bg_config 
              WHERE config_key = 'abo_mapformfields' 
              AND config_type = 'automation_processor'";
$stmt = $database->query($check_sql);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    echo "Form field mapping processor already exists in bg_config\n";
} else {
    // Get the next display order
    $order_sql = "SELECT MAX(display_order) as max_order 
                  FROM bg_config 
                  WHERE config_type = 'automation_processor'";
    $stmt = $database->query($order_sql);
    $order_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_order = ($order_data['max_order'] ?? 0) + 1;
    
    // We want this after AI enhancement but before AI validation
    // So let's insert it with order 145 (between AI enhancement at 140 and AI validation at 150)
    $display_order = 145;
    
    $config_data = [
        'description' => 'Maps user profile fields to website form fields',
        'scheduler_file' => 'abo_mapformfields.php',
        'frequency' => 'on_demand',
        'dependencies' => ['abo_grabbirthday', 'abo_aienhance'],
        'creates_data' => ['form_field_mappings'],
        'requires_data' => ['signup_form', 'birthday_program']
    ];
    
    $insert_sql = "INSERT INTO bg_config 
                   (config_key, config_value, config_type, config_data, display_order, is_active, created_at)
                   VALUES 
                   (:key, :value, :type, :data, :order, 1, NOW())";
    
    $params = [
        'key' => 'abo_mapformfields',
        'value' => 'Map Form Fields',
        'type' => 'automation_processor',
        'data' => json_encode($config_data),
        'order' => $display_order
    ];
    
    try {
        $database->query($insert_sql, $params);
        echo "Successfully added form field mapping processor to bg_config\n";
        echo "Display order: $display_order\n";
        
        // Update display orders to make room
        $update_sql = "UPDATE bg_config 
                      SET display_order = display_order + 1 
                      WHERE config_type = 'automation_processor' 
                      AND display_order >= :order 
                      AND config_key != 'abo_mapformfields'";
        $database->query($update_sql, ['order' => $display_order]);
        
        echo "Updated display orders for other processors\n";
    } catch (Exception $e) {
        echo "Error adding config: " . $e->getMessage() . "\n";
    }
}

// Show current processor order
echo "\nCurrent processor order:\n";
$list_sql = "SELECT config_key, config_value, display_order 
             FROM bg_config 
             WHERE config_type = 'automation_processor' 
             ORDER BY display_order";
$stmt = $database->query($list_sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo str_pad($row['display_order'], 4) . " - " . $row['config_value'] . " (" . $row['config_key'] . ")\n";
}