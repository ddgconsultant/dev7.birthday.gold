<?php
include('../core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

header('Content-Type: text/plain');
echo "Field Mappings for Company ID: $company_id\n";
echo "==========================================\n\n";

// Get current mappings
$sql = "SELECT * FROM bg_form_field_mappings 
        WHERE company_id = :company_id 
        AND version_status = 'active'
        ORDER BY `rank`, user_field_name";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Active Version: " . ($mappings[0]['version'] ?? 'none') . "\n";
echo "Total Mappings: " . count($mappings) . "\n\n";

echo str_pad("User Field", 30) . " => " . str_pad("Website Field", 30) . " [Rank] Format\n";
echo str_repeat("-", 80) . "\n";

foreach ($mappings as $mapping) {
    echo str_pad($mapping['user_field_name'], 30) . " => ";
    echo str_pad($mapping['website_field_name'], 30) . " ";
    echo "[" . str_pad($mapping['rank'], 2) . "] ";
    if ($mapping['fieldformattype']) {
        echo $mapping['fieldformattype'] . ": " . $mapping['fieldformat'];
    }
    echo "\n";
}

// Check if there's signup form data
echo "\n\nSignup Form Data:\n";
echo "-----------------\n";
$form_sql = "SELECT * FROM bg_company_attributes 
             WHERE company_id = :company_id 
             AND type = 'signup_form'
             AND status = 'active'";
$stmt = $database->query($form_sql, ['company_id' => $company_id]);
$form_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($form_data) {
    $form_info = json_decode($form_data['description'], true);
    if (isset($form_info['fields'])) {
        echo "Form has " . count($form_info['fields']) . " fields:\n";
        foreach ($form_info['fields'] as $field) {
            echo "  - " . ($field['name'] ?? 'unnamed') . " (" . ($field['type'] ?? 'unknown') . ")\n";
        }
    } else {
        echo "No field data available in signup form\n";
    }
} else {
    echo "No signup form data found\n";
}