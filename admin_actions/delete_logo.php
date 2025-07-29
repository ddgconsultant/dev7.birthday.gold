<?php
// delete_logo.php
// AJAX endpoint for deleting a logo

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get POST data
$logo_id = $_POST['logo_id'] ?? null;

// Validate inputs
if (!$logo_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing logo ID']);
    exit;
}

try {
    // Get logo details before deletion
    $logo_sql = "SELECT * FROM bg_company_attributes WHERE attribute_id = :logo_id";
    $stmt = $database->prepare($logo_sql);
    $stmt->execute(['logo_id' => $logo_id]);
    $logo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$logo) {
        throw new Exception('Logo not found');
    }
    
    // Check if this is the primary logo
    if ($logo['grouping'] === 'primary_logo') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Cannot delete the primary logo. Please set another logo as primary first.']);
        exit;
    }
    
    // Start transaction
    $database->beginTransaction();
    
    // Soft delete the logo (set status to inactive)
    $delete_sql = "UPDATE bg_company_attributes 
                   SET status = 'inactive', modify_dt = NOW() 
                   WHERE attribute_id = :logo_id";
    $stmt = $database->prepare($delete_sql);
    $stmt->execute(['logo_id' => $logo_id]);
    
    // Log the deletion
    $log_sql = "INSERT INTO bg_company_attributes (company_id, category, type, name, description, status, create_dt) 
                VALUES (:company_id, 'audit_log', 'logo_delete', 'logo_deleted', :description, 'active', NOW())";
    $log_stmt = $database->prepare($log_sql);
    $log_stmt->execute([
        'company_id' => $logo['company_id'],
        'description' => "Logo deleted: {$logo['description']} (attribute_id: {$logo_id}) by user {$_SESSION['user']['user_id']}"
    ]);
    
    // Commit transaction
    $database->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Logo deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($database->inTransaction()) {
        $database->rollback();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}