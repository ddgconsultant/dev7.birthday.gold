<?php
/**
 * Batch Process Enrollments - AJAX Handler
 * Processes both picked enrollments and tracked (user_owned) items in batch
 * This replaces individual AJAX calls for better performance
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
    'picked' => [],
    'tracked' => [],
    'message' => ''
];

// Get user ID from current_user_data
$user_id = $current_user_data['user_id'] ?? 0;

// Check if user is logged in
if (!$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get JSON input
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

// Debug logging
error_log("Batch process received: " . $raw_input);

if (!$input) {
    $response['message'] = 'Invalid request data';
    echo json_encode($response);
    exit;
}

$picked_ids = isset($input['picked']) ? array_map('intval', $input['picked']) : [];
$tracked_ids = isset($input['tracked']) ? array_map('intval', $input['tracked']) : [];

// Debug logging
error_log("Picked IDs: " . json_encode($picked_ids));
error_log("Tracked IDs: " . json_encode($tracked_ids));

// Check if we have any items to process
if (empty($picked_ids) && empty($tracked_ids)) {
    $response['message'] = 'No items to process';
    echo json_encode($response);
    exit;
}

// Start transaction
$database->query("START TRANSACTION");

try {
    // Process picked items (normal enrollments)
    foreach ($picked_ids as $company_id) {
        if (!$company_id) continue;
        
        // Check if company exists and is active
        $company_sql = "SELECT company_id, company_name 
                        FROM bg_companies 
                        WHERE company_id = :company_id 
                        AND status IN ('active', 'finalized')";
        $company = $database->getrow($company_sql, ['company_id' => $company_id]);
        
        if (!$company) {
            $response['picked'][] = [
                'company_id' => $company_id,
                'success' => false,
                'error' => 'Company not found or inactive'
            ];
            continue;
        }
        
        // Check for existing enrollment
        $existing_sql = "SELECT enrollment_id 
                         FROM bg_user_enrollments 
                         WHERE user_id = :user_id 
                         AND company_id = :company_id 
                         AND status IN ('active', 'pending', 'user_owned')";
        $existing = $database->getrow($existing_sql, [
            'user_id' => $user_id,
            'company_id' => $company_id
        ]);
        
        if ($existing) {
            $response['picked'][] = [
                'company_id' => $company_id,
                'company_name' => $company['company_name'],
                'success' => false,
                'error' => 'Already enrolled or tracked'
            ];
            continue;
        }
        
        // Create enrollment
        $insert_sql = "INSERT INTO bg_user_enrollments 
                       (user_id, company_id, status, enrollment_source, created_at, updated_at) 
                       VALUES 
                       (:user_id, :company_id, 'active', 'enrollment_picker', NOW(), NOW())";
        
        $database->query($insert_sql, [
            'user_id' => $user_id,
            'company_id' => $company_id
        ]);
        
        $response['picked'][] = [
            'company_id' => $company_id,
            'company_name' => $company['company_name'],
            'success' => true,
            'enrollment_id' => $database->last_id()
        ];
    }
    
    // Process tracked items (user_owned status)
    foreach ($tracked_ids as $company_id) {
        if (!$company_id) continue;
        
        // Check if company exists and is active
        $company_sql = "SELECT company_id, company_name 
                        FROM bg_companies 
                        WHERE company_id = :company_id 
                        AND status IN ('active', 'finalized')";
        $company = $database->getrow($company_sql, ['company_id' => $company_id]);
        
        if (!$company) {
            $response['tracked'][] = [
                'company_id' => $company_id,
                'success' => false,
                'error' => 'Company not found or inactive'
            ];
            continue;
        }
        
        // Check for existing enrollment or tracking
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
            $response['tracked'][] = [
                'company_id' => $company_id,
                'company_name' => $company['company_name'],
                'success' => false,
                'error' => $existing['status'] == 'user_owned' ? 'Already tracked' : 'Already enrolled'
            ];
            continue;
        }
        
        // Create user_owned enrollment
        $insert_sql = "INSERT INTO bg_user_enrollments 
                       (user_id, company_id, status, enrollment_source, created_at, updated_at) 
                       VALUES 
                       (:user_id, :company_id, 'user_owned', 'enrollment_picker_tracked', NOW(), NOW())";
        
        $database->query($insert_sql, [
            'user_id' => $user_id,
            'company_id' => $company_id
        ]);
        
        $response['tracked'][] = [
            'company_id' => $company_id,
            'company_name' => $company['company_name'],
            'success' => true,
            'enrollment_id' => $database->last_id()
        ];
    }
    
    // Commit transaction
    $database->query("COMMIT");
    
    // Update session tracking lists
    if (isset($session)) {
        // Update picked list
        $existingList = $session->get('goldmine_existingList', []);
        foreach ($picked_ids as $id) {
            if (!in_array($id, $existingList)) {
                $existingList[] = $id;
            }
        }
        
        // Update tracked list
        $trackedList = $session->get('goldmine_trackedList', []);
        foreach ($tracked_ids as $id) {
            if (!in_array($id, $trackedList)) {
                $trackedList[] = $id;
            }
            // Also add to existing list
            if (!in_array($id, $existingList)) {
                $existingList[] = $id;
            }
        }
        
        $session->set('goldmine_existingList', $existingList);
        $session->set('goldmine_trackedList', $trackedList);
    }
    
    $response['success'] = true;
    $response['message'] = 'Batch processing completed';
    
} catch (Exception $e) {
    // Rollback on error
    $database->query("ROLLBACK");
    
    // Log error
    error_log("Batch enrollment error for user $user_id: " . $e->getMessage());
    
    $response['message'] = 'An error occurred during processing';
}

// Output response
echo json_encode($response);
exit;
?>