<?php
include('../core/site-controller.php');

$company_id = 6231; // 1UP Nutrition

// Check if the progress already exists
$check_sql = "SELECT * FROM bg_company_attributes 
              WHERE company_id = :company_id 
              AND type = 'onboarding_progress' 
              AND name = 'abo_mapformfields'";
$stmt = $database->query($check_sql, ['company_id' => $company_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // Update to pending
    $update_sql = "UPDATE bg_company_attributes 
                   SET description = 'pending', modify_dt = NOW() 
                   WHERE company_id = :company_id 
                   AND type = 'onboarding_progress' 
                   AND name = 'abo_mapformfields'";
    $database->query($update_sql, ['company_id' => $company_id]);
    echo "Updated existing progress record to pending\n";
} else {
    // Insert new progress record
    $insert_sql = "INSERT INTO bg_company_attributes 
                   (company_id, type, name, description, status, create_dt)
                   VALUES 
                   (:company_id, 'onboarding_progress', 'abo_mapformfields', 'pending', 'active', NOW())";
    $database->query($insert_sql, ['company_id' => $company_id]);
    echo "Added new progress record for form field mapping\n";
}

// Check current mappings
$mapping_sql = "SELECT COUNT(*) as count, MAX(version) as max_version 
                FROM bg_form_field_mappings 
                WHERE company_id = :company_id";
$stmt = $database->query($mapping_sql, ['company_id' => $company_id]);
$mapping_info = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Current mappings: {$mapping_info['count']} records, max version: " . ($mapping_info['max_version'] ?? 'none') . "\n";