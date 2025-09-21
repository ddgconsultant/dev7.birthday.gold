<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

// Check if progress record exists
$check_sql = "SELECT * FROM bg_company_attributes 
             WHERE company_id = :company_id 
             AND type = 'onboarding_progress' 
             AND name = 'abo_aienhance'";
$stmt = $database->query($check_sql, ['company_id' => $company_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$existing) {
    // Create progress record
    $insert_sql = "INSERT INTO bg_company_attributes 
                  (company_id, type, name, description, status, create_dt)
                  VALUES 
                  (:company_id, 'onboarding_progress', 'abo_aienhance', 'pending', 'active', NOW())";
    $database->query($insert_sql, ['company_id' => $company_id]);
    echo "Created AI enhancement progress record for company $company_id\n";
} else {
    // Update to pending
    $update_sql = "UPDATE bg_company_attributes 
                  SET description = 'pending', modify_dt = NOW() 
                  WHERE company_id = :company_id 
                  AND type = 'onboarding_progress' 
                  AND name = 'abo_aienhance'";
    $database->query($update_sql, ['company_id' => $company_id]);
    echo "Updated AI enhancement progress to pending for company $company_id\n";
}