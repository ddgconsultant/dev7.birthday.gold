<?php
/**
 * Untrack Company - AJAX Handler
 * Removes "I Already Have This" tracking status from a company
 * This deletes the user_owned enrollment record
 */

// Include site controller for authentication
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set JSON response header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Check if user is logged in (authentication handled by site-controller.php)
if (!isset($user_id) || !$user_id) {
    $response['message'] = 'You must be logged in to manage tracked rewards';
    echo json_encode($response);
    exit;
}

// Get and validate company_id
$company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

if (!$company_id) {
    $response['message'] = 'Invalid company ID';
    echo json_encode($response);
    exit;
}

// Check if user has this company tracked
$check_sql = "SELECT enrollment_id 
              FROM bg_user_enrollments 
              WHERE user_id = :user_id 
              AND company_id = :company_id 
              AND status = 'user_owned'";
$enrollment = $database->get_row($check_sql, [
    'user_id' => $user_id,
    'company_id' => $company_id
]);

if (!$enrollment) {
    $response['message'] = 'This company is not currently tracked';
    echo json_encode($response);
    exit;
}

// Delete the tracking record
try {
    // Start transaction
    $database->query("START TRANSACTION");
    
    // Delete the user_owned enrollment
    $delete_sql = "DELETE FROM bg_user_enrollments 
                   WHERE enrollment_id = :enrollment_id 
                   AND user_id = :user_id 
                   AND status = 'user_owned'";
    
    $database->query($delete_sql, [
        'enrollment_id' => $enrollment['enrollment_id'],
        'user_id' => $user_id
    ]);
    
    // Remove from session lists
    if (isset($session)) {
        $trackedList = $session->get('goldmine_trackedList', []);
        $trackedList = array_diff($trackedList, [$company_id]);
        $session->set('goldmine_trackedList', $trackedList);
        
        // Also remove from existing list if present
        $existingList = $session->get('goldmine_existingList', []);
        $existingList = array_diff($existingList, [$company_id]);
        $session->set('goldmine_existingList', $existingList);
    }
    
    // Log activity (if activity table exists)
    try {
        $activity_sql = "INSERT INTO bg_user_activity 
                         (user_id, activity_type, activity_data, created_at) 
                         VALUES 
                         (:user_id, 'reward_untracked', :data, NOW())";
        
        $database->query($activity_sql, [
            'user_id' => $user_id,
            'data' => json_encode([
                'company_id' => $company_id,
                'enrollment_id' => $enrollment['enrollment_id']
            ])
        ]);
    } catch (Exception $e) {
        // Activity logging is optional, continue if table does not exist
    }
    
    // Commit transaction
    $database->query("COMMIT");
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Tracking removed successfully';
    
} catch (Exception $e) {
    // Rollback on error
    $database->query("ROLLBACK");
    
    // Log error
    error_log("Untrack company error for user $user_id, company $company_id: " . $e->getMessage());
    
    $response['message'] = 'An error occurred while removing tracking';
}

// Output response
echo json_encode($response);
exit;
?>