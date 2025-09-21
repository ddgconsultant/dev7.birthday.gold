<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Verify the request has a valid token
if (!$app->formposted('GET')) {
    header('Location: /myaccount/parental-mode');
    exit;
}

try {
    $child_id = intval($_GET['id'] ?? 0);
    
    if ($child_id <= 0) {
        throw new Exception('Invalid child account ID');
    }
    
    // Verify this child belongs to the current user
    $verify_stmt = $database->prepare("SELECT user_id FROM bg_users WHERE user_id = :child_id AND feature_parent_id = :parent_id AND account_type = 'minor'");
    $verify_stmt->execute([
        ':child_id' => $child_id,
        ':parent_id' => $current_user_data['user_id']
    ]);
    
    if (!$verify_stmt->fetch()) {
        throw new Exception('Child account not found or access denied');
    }
    
    // Make the account inactive instead of deleting
    $update_stmt = $database->prepare("UPDATE bg_users SET status = 'inactive', modify_dt = NOW() WHERE user_id = :child_id");
    $update_stmt->execute([':child_id' => $child_id]);
    
    // Log the action
    session_tracking('Child account deactivated', 'child_id: ' . $child_id . ' by parent_id: ' . $current_user_data['user_id']);
    
    $session->set('ALERT_MESSAGE', 'Child account has been deactivated successfully');
    
} catch (Exception $e) {
    $session->set('ALERT_MESSAGE', 'Error deactivating child account: ' . $e->getMessage());
    session_tracking('Child account deactivation error', $e->getMessage());
}

header('Location: /myaccount/parental-mode');
exit;