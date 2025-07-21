<?php
// mail-goldie-regenerate.php - Regenerate summary for a specific date
$addClasses[] = 'mail';
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

// Check if user is logged in
$uid = $current_user_data['user_id'] ?? 0;
if (!$uid) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Check CSRF
if (!$app->formposted()) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$date = $input['date'] ?? '';

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit;
}

// Check if admin/dev for non-own accounts
$is_admin_dev = $account->isadmin() || $mode === 'dev';
$target_uid = $uid;

// If viewing another user's mail (admin only)
if ($is_admin_dev && !empty($input['target_uid'])) {
    $target_uid = intval($input['target_uid']);
}

// Use the mail class method to generate/regenerate summary
$result = $mail->summarizeDailyMessages($target_uid, $date);

// Return the result as JSON
echo json_encode($result);
?>