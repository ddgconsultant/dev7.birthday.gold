<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set JSON header
header('Content-Type: application/json');

// Get event ID
$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    echo json_encode(['success' => false, 'error' => 'No event ID provided']);
    exit;
}

// Query the event data
$event_sql = "
SELECT
    id,
    create_dt,
    name,
    tracking_data,
    session_data,
    server_data,
    request_data
FROM bg_sessiontracking
WHERE id = :id
";

try {
    $event = $database->getrow($event_sql, ['id' => $event_id]);

    if (!$event) {
        echo json_encode(['success' => false, 'error' => 'Event not found']);
        exit;
    }

    // Parse JSON data (handle null values)
    $tracking_data = !empty($event['tracking_data']) ? json_decode($event['tracking_data'], true) : [];
    $session_data = !empty($event['session_data']) ? json_decode($event['session_data'], true) : [];
    $server_data = !empty($event['server_data']) ? json_decode($event['server_data'], true) : [];
    $request_data = !empty($event['request_data']) ? json_decode($event['request_data'], true) : [];

    // Ensure arrays
    $tracking_data = is_array($tracking_data) ? $tracking_data : [];
    $session_data = is_array($session_data) ? $session_data : [];
    $server_data = is_array($server_data) ? $server_data : [];
    $request_data = is_array($request_data) ? $request_data : [];

    // Return the data
    echo json_encode([
        'success' => true,
        'event_id' => $event['id'],
        'event_name' => $event['name'],
        'create_dt' => $event['create_dt'],
        'tracking_data' => $tracking_data,
        'session_data' => $session_data,
        'server_data' => $server_data,
        'request_data' => $request_data
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
