<?php
/**
 * Enrollment API endpoint
 * Processes company enrollment using allocations
 */

header('Content-Type: application/json');

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.allocationmanager.php');

// Check if user is logged in
$activeuser = $account->isactive();
if (empty($activeuser)) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$company_id = $input['company_id'] ?? null;

if (!$company_id) {
    echo json_encode(['success' => false, 'error' => 'No company specified']);
    exit;
}

// Get user data
$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

// Initialize AllocationManager
$allocationManager = new AllocationManager($database);

// Check if user has available allocations
$balance = $allocationManager->getUserBalance($user_id);
if ($balance['available_allocations'] < 1) {
    echo json_encode(['success' => false, 'error' => 'No allocations available']);
    exit;
}

// Check if already enrolled
$check_sql = "SELECT * FROM bg_user_enrollments WHERE user_id = :user_id AND company_id = :company_id AND status NOT IN ('failed', 'removed')";
$existing = $database->getrow($check_sql, ['user_id' => $user_id, 'company_id' => $company_id]);

if ($existing) {
    echo json_encode(['success' => false, 'error' => 'Already enrolled in this company']);
    exit;
}

// Get company details
$company_sql = "SELECT * FROM bg_companies WHERE company_id = :company_id AND status = 'finalized'";
$company = $database->getrow($company_sql, ['company_id' => $company_id]);

if (!$company) {
    echo json_encode(['success' => false, 'error' => 'Company not found']);
    exit;
}

try {
    // Start transaction
    $database->beginTransaction();
    
    // Insert enrollment with status 'selected'
    $insert_sql = "INSERT INTO bg_user_enrollments (
                    user_id, 
                    company_id, 
                    status, 
                    create_dt
                ) VALUES (
                    :user_id,
                    :company_id,
                    'selected',
                    NOW()
                )";
    
    $stmt = $database->prepare($insert_sql);
    $stmt->execute([
        'user_id' => $user_id,
        'company_id' => $company_id
    ]);
    
    $enrollment_id = $database->lastInsertId();
    
    // Use an allocation
    $allocationResult = $allocationManager->useAllocation($user_id, $company_id, $enrollment_id);
    
    if (!$allocationResult['success']) {
        throw new Exception($allocationResult['error'] ?? 'Failed to use allocation');
    }
    
    // Commit transaction
    $database->commit();
    
    // Get new balance
    $new_balance = $allocationManager->getUserBalance($user_id);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully enrolled in {$company['company_name']}",
        'enrollment_id' => $enrollment_id,
        'new_balance' => $new_balance
    ]);
    
} catch (Exception $e) {
    $database->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>