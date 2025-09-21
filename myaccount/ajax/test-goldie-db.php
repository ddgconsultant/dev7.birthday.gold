<?php
// Test endpoint to debug Goldie Mail issues
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

$response = [
    'user_id' => $current_user_data['user_id'] ?? 'not logged in',
    'mail_class_exists' => class_exists('Mail'),
    'mail_object_exists' => isset($mail),
    'method_exists' => method_exists($mail, 'getMessagesForAI'),
    'database_connected' => isset($database)
];

// Test database table
try {
    $stmt = $database->query("SHOW TABLES LIKE 'bg_user_message_summaries'");
    $result = $stmt->fetch();
    $response['summary_table_exists'] = !empty($result);
} catch (Exception $e) {
    $response['summary_table_exists'] = false;
    $response['table_error'] = $e->getMessage();
}

// Test getMessagesForAI
if ($response['method_exists'] && isset($current_user_data['user_id'])) {
    try {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $result = $mail->getMessagesForAI($current_user_data['user_id'], $start_date, $end_date);
        $response['messages_test'] = [
            'success' => true,
            'count' => count($result['messages'] ?? [])
        ];
    } catch (Exception $e) {
        $response['messages_test'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>