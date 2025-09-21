<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if we have abo_processor entries
$sql = "SELECT config_key, config_value, display_order 
        FROM bg_config 
        WHERE config_type = 'abo_processor' 
        ORDER BY display_order";
$stmt = $database->query($sql);
$processors = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/plain');
echo "ABO Processors in bg_config:\n";
echo "============================\n";
if (empty($processors)) {
    echo "No processors found. You need to run abo_setup.php first.\n";
} else {
    foreach ($processors as $proc) {
        echo sprintf("%-30s %-40s %d\n", 
            $proc['config_key'], 
            $proc['config_value'], 
            $proc['display_order']
        );
    }
}

// Check company attributes
echo "\n\nCompany 6231 ABO Progress:\n";
echo "==========================\n";
$attr_sql = "SELECT name, description, modify_dt 
             FROM bg_company_attributes 
             WHERE company_id = 6231 
             AND type = 'onboarding_progress'
             ORDER BY create_dt";
$attr_stmt = $database->query($attr_sql);
$attributes = $attr_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($attributes as $attr) {
    echo sprintf("%-30s %-15s %s\n", 
        $attr['name'], 
        $attr['description'], 
        $attr['modify_dt'] ?? 'Never'
    );
}