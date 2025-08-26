<?php
/**
 * Track As Owned - AJAX Handler
 * Allows users to track that they already have a birthday reward
 * without using an allocation/enrollment pick
 * 
 * This creates a special enrollment record with status "user_owned"
 * that tracks the reward but doesn't count against allocations
 */

error_reporting(0);
ini_set('display_errors', 0);

// Include site controller for authentication
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

ob_clean();
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Authentication is handled by site-controller.php
// The myaccount folder requires login, so we should have user data
// Get user_id from current_user_data like other pages
$user_id = $current_user_data['user_id'] ?? 0;

// Check if user is logged in
if (!$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get and validate company_id
$company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

if (!$company_id) {
    $response['message'] = 'Invalid company ID';
    echo json_encode($response);
    exit;
}

// Check if company exists and is active
$company_sql = "SELECT company_id, company_name, status 
                FROM bg_companies 
                WHERE company_id = :company_id 
                AND status IN ('active', 'finalized')";
$company = $database->getrow($company_sql, ['company_id' => $company_id]);

if (!$company) {
    $response['message'] = 'Company not found or inactive';
    echo json_encode($response);
    exit;
}

// Check if user already has this company enrolled or tracked
$existing_sql = "SELECT enrollment_id, status 
                 FROM bg_user_enrollments 
                 WHERE user_id = :user_id 
                 AND company_id = :company_id 
                 AND status IN ('active', 'pending', 'user_owned')";
$existing = $database->getrow($existing_sql, [
    'user_id' => $user_id,
    'company_id' => $company_id
]);

if ($existing) {
    if ($existing['status'] == 'user_owned') {
        $response['message'] = 'You have already tracked this reward';
    } else {
        $response['message'] = 'You are already enrolled in this reward';
    }
    echo json_encode($response);
    exit;
}

// Create the "user_owned" enrollment record
try {
    // Start transaction
    $database->query("START TRANSACTION");
    
    // Insert enrollment record with user_owned status
    $insert_sql = "INSERT INTO bg_user_enrollments 
                   (user_id, company_id, status, enrollment_source, created_at, updated_at) 
                   VALUES 
                   (:user_id, :company_id, 'user_owned', 'enrollment_picker_tracked', NOW(), NOW())";
    
    $database->query($insert_sql, [
        'user_id' => $user_id,
        'company_id' => $company_id
    ]);
    
    $enrollment_id = $database->lastInsertId();
    
    // Log this action in session tracking
    if (isset($session)) {
        $session->set('last_tracked_company', [
            'company_id' => $company_id,
            'company_name' => $company['company_name'],
            'timestamp' => time()
        ]);
    }
    
    // Add to user's session lists
    $existingList = $session->get('goldmine_existingList', []);
    if (!in_array($company_id, $existingList)) {
        $existingList[] = $company_id;
        $session->set('goldmine_existingList', $existingList);
    }
    
    // Log activity
    $activity_sql = "INSERT INTO bg_user_activity 
                     (user_id, activity_type, activity_data, created_at) 
                     VALUES 
                     (:user_id, 'reward_tracked', :data, NOW())";
    
    $database->query($activity_sql, [
        'user_id' => $user_id,
        'data' => json_encode([
            'company_id' => $company_id,
            'company_name' => $company['company_name'],
            'enrollment_id' => $enrollment_id
        ])
    ]);
    
    // Commit transaction
    $database->query("COMMIT");
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Reward tracked successfully';
    $response['enrollment_id'] = $enrollment_id;
    $response['status'] = 'user_owned';
    
} catch (Exception $e) {
    // Rollback on error
    $database->query("ROLLBACK");
    
    // Log error
    error_log("Track as owned error for user $user_id, company $company_id: " . $e->getMessage());
    
    $response['message'] = 'An error occurred while tracking the reward';
}

// Output response
echo json_encode($response);
exit;
?>