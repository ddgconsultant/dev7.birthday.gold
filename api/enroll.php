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

// SERVER-SIDE PLAN LIMIT ENFORCEMENT
// This is a critical security check - do not rely solely on available_allocations
// as allocation records may not be properly initialized
$plan_limit = $balance['plan_limit'] ?? 0;
$bonus_allocations = $balance['bonus_allocations'] ?? 0;
$total_allowed = $plan_limit + $bonus_allocations;
$current_enrollments = $balance['use_count'] ?? 0;

// If plan_limit is 0, get it directly from user's plan as a fail-safe
if ($plan_limit == 0) {
    $user_plan_data = $account->getuserdata($user_id, 'user_id');
    $plan_details = $app->plandetail('details_id', $user_plan_data['account_product_id'] ?? 0);

    if ($plan_details && isset($plan_details['max_business_select']['value'])) {
        $plan_limit = intval($plan_details['max_business_select']['value']);
    }

    // Default limits by plan type if still not found
    if ($plan_limit == 0) {
        $account_plan = strtolower($user_plan_data['account_plan'] ?? 'free');
        if (strpos($account_plan, 'free') !== false) {
            $plan_limit = 5;  // Free plan default
        } elseif (strpos($account_plan, 'basic') !== false) {
            $plan_limit = 10;  // Basic plan default
        } else {
            $plan_limit = 5;  // Absolute minimum fallback
        }
    }

    $total_allowed = $plan_limit + $bonus_allocations;
}

// Enforce the plan limit - this is the critical check that was missing
if ($current_enrollments >= $total_allowed) {
    error_log("ENROLLMENT LIMIT ENFORCED: User {$user_id} attempted enrollment #{" . ($current_enrollments + 1) . "} but limit is {$total_allowed} (plan: {$plan_limit}, bonus: {$bonus_allocations})");
    echo json_encode([
        'success' => false,
        'error' => 'You have reached your plan enrollment limit',
        'current' => $current_enrollments,
        'limit' => $total_allowed
    ]);
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