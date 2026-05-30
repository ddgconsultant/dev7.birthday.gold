<?php
/**
 * Track Enrollment Selection - AJAX Handler
 * Records selection/unselection events for debugging allocation issues
 *
 * This creates a session_tracking entry with all relevant state data
 * to help troubleshoot why users exceed their allocation allowance
 */

// Include site controller for authentication and session_tracking function
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set content type
header('Content-Type: application/json');

// Get user_id from current_user_data
$user_id = $current_user_data['user_id'] ?? 0;

// Check if user is logged in
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get the JSON payload
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Build tracking data with all relevant state variables
$trackingData = [
    // Action info
    'action' => $data['action'] ?? 'unknown',
    'company_id' => $data['company_id'] ?? null,
    'company_name' => $data['company_name'] ?? null,

    // Basket state at time of action
    'picked_basket_count' => $data['picked_basket_count'] ?? 0,
    'tracked_basket_count' => $data['tracked_basket_count'] ?? 0,
    'picked_basket_ids' => $data['picked_basket_ids'] ?? [],
    'tracked_basket_ids' => $data['tracked_basket_ids'] ?? [],

    // Allocation state
    'available_allocations' => $data['available_allocations'] ?? null,
    'enrolled_company_ids_count' => is_array($data['enrolled_company_ids'] ?? null) ? count($data['enrolled_company_ids']) : 0,

    // Validation checks performed
    'was_already_enrolled' => $data['was_already_enrolled'] ?? false,
    'was_already_in_basket' => $data['was_already_in_basket'] ?? false,
    'had_enough_allocations' => $data['had_enough_allocations'] ?? true,

    // Extra data for clear_all action
    'cleared_picked_ids' => $data['cleared_picked_ids'] ?? null,
    'cleared_tracked_ids' => $data['cleared_tracked_ids'] ?? null,

    // Extra data for confirm_submit action
    'submitting_picked_ids' => $data['submitting_picked_ids'] ?? null,
    'submitting_tracked_ids' => $data['submitting_tracked_ids'] ?? null,
    'submitting_picked_count' => $data['submitting_picked_count'] ?? null,
    'submitting_tracked_count' => $data['submitting_tracked_count'] ?? null,

    // Timestamp from client
    'client_timestamp' => $data['client_timestamp'] ?? null,

    // Browser info for debugging
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
];

// Create tracking name based on action
$trackingName = 'enrollment_picker_' . ($data['action'] ?? 'unknown');

// Call session_tracking to record this event
session_tracking($trackingName, $trackingData, 'enrollment-picker.php');

// Return success
echo json_encode([
    'success' => true,
    'message' => 'Selection tracked',
    'tracked_action' => $data['action'] ?? 'unknown'
]);
