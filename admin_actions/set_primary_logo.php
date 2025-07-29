<?php
// set_primary_logo.php
// AJAX endpoint for setting a logo as primary

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get POST data
$company_id = $_POST['company_id'] ?? null;
$logo_id = $_POST['logo_id'] ?? null;

// Validate inputs
if (!$company_id || !$logo_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Start transaction
    $database->beginTransaction();
    
    // First, remove primary_logo grouping from all existing logos for this company
    $update_sql = "UPDATE bg_company_attributes 
                   SET `grouping` = 'logo', modify_dt = NOW() 
                   WHERE company_id = :company_id 
                   AND category = 'company_logos' 
                   AND `grouping` = 'primary_logo'";
    $stmt = $database->prepare($update_sql);
    $stmt->execute(['company_id' => $company_id]);
    
    // Then set the selected logo as primary
    $set_primary_sql = "UPDATE bg_company_attributes 
                        SET `grouping` = 'primary_logo', modify_dt = NOW() 
                        WHERE attribute_id = :logo_id 
                        AND company_id = :company_id";
    $stmt = $database->prepare($set_primary_sql);
    $stmt->execute([
        'logo_id' => $logo_id,
        'company_id' => $company_id
    ]);
    
    // Log the change
    $user_id = $current_user_data['user_id'] ?? 'unknown';
    $log_sql = "INSERT INTO bg_company_attributes (company_id, category, type, name, description, status, create_dt) 
                VALUES (:company_id, 'audit_log', 'logo_change', 'primary_logo_set', :description, 'active', NOW())";
    $log_stmt = $database->prepare($log_sql);
    $log_stmt->execute([
        'company_id' => $company_id,
        'description' => "Primary logo set to attribute_id: {$logo_id} by user {$user_id}"
    ]);
    
    // Commit transaction
    $database->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Primary logo updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $database->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}