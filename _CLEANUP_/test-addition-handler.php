<?php
/**
 * Test Addition Handler - Separate file to handle form submission
 * This avoids any output/encoding issues
 */

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /myaccount/test-addition');
    exit();
}

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.allocationmanager.php');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login');
    exit();
}

// Only allow in development mode
if ($mode !== 'dev') {
    header('Location: /myaccount');
    exit();
}

// Get user data
$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

// Initialize AllocationManager
$allocationManager = new AllocationManager($database);

// Handle the allocation addition
if (isset($_POST['_token']) && $_POST['_token'] === $session->get('csrf_token')) {
    $result = $allocationManager->grantBonus($user_id, 2, 'Test bonus allocation (dev mode)', 'test');
    
    if ($result['success']) {
        header('Location: /myaccount/test-addition?success=1');
    } else {
        header('Location: /myaccount/test-addition?error=' . urlencode($result['message']));
    }
} else {
    header('Location: /myaccount/test-addition?error=invalid_token');
}
exit();
?>