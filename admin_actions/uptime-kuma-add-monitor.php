<?php
/**
 * Admin Endpoint: Add Monitor to Uptime Kuma
 *
 * Purpose: Reusable script to add HTTP monitors to Uptime Kuma via API
 *
 * Usage:
 *   Browser: https://dev.birthday.gold/admin_actions/uptime-kuma-add-monitor.php?name=MyMonitor&url=/path/to/script.php
 *   CLI: php uptime-kuma-add-monitor.php name=MyMonitor url=/path/to/script.php
 */

// Set document root for CLI execution
if (php_sapi_name() === 'cli') {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    $is_cli = true;
} else {
    $is_cli = false;
    include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
}

// Uptime Kuma API configuration
$KUMA_API_BASE = 'http://april21.bday.gold:5000';
$PARENT_ID = 70; // Parent group for Birthday.gold monitors
$NOTIFICATION_ID = 1; // Default notification

/**
 * Check if monitor already exists
 */
function checkMonitorExists($name) {
    global $KUMA_API_BASE;

    $data = json_encode(['name' => $name]);
    $ch = curl_init("{$KUMA_API_BASE}/check_monitor_exists");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return false;
    }

    $result = json_decode($response, true);
    return isset($result['exists']) && $result['exists'] === true;
}

/**
 * Create a new monitor in Uptime Kuma
 */
function createMonitor($config) {
    global $KUMA_API_BASE;

    $data = json_encode($config);
    $ch = curl_init("{$KUMA_API_BASE}/create_monitor");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'success' => $httpCode === 200,
        'http_code' => $httpCode,
        'response' => $response
    ];
}

// ============================================================================
// HANDLE REQUEST
// ============================================================================

$name = $_GET['name'] ?? '';
$url = $_GET['url'] ?? '';
$type = $_GET['type'] ?? 'keyword'; // keyword, json, status
$interval = (int)($_GET['interval'] ?? 3600); // Default: 1 hour
$timeout = (int)($_GET['timeout'] ?? 300); // Default: 5 minutes
$keyword = $_GET['keyword'] ?? 'success'; // For keyword monitoring
$description = $_GET['description'] ?? '';

// Validate required parameters
if (empty($name) || empty($url)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required parameters',
        'required' => ['name', 'url'],
        'example' => '/admin_actions/uptime-kuma-add-monitor.php?name=Mail Sync&url=/admin_actions/scheduler--sync-mail-counts.php'
    ]);
    exit;
}

// Build full URL
$fullUrl = (strpos($url, 'http') === 0) ? $url : "https://dev.birthday.gold{$url}";

// Check if monitor already exists
if (checkMonitorExists($name)) {
    echo json_encode([
        'status' => 'skipped',
        'message' => "Monitor '{$name}' already exists in Uptime Kuma",
        'name' => $name
    ]);
    exit;
}

// Build monitor configuration based on type
$monitorConfig = [
    'name' => $name,
    'url' => $fullUrl,
    'interval' => $interval,
    'retryInterval' => $interval,
    'timeout' => $timeout,
    'maxretries' => 2,
    'resendInterval' => 0,
    'parent' => $PARENT_ID,
    'description' => $description ?: "Monitor for {$name}",
    'notificationIDList' => [$NOTIFICATION_ID],
    'expiryNotification' => true,
    'ignoreTls' => false,
    'upsideDown' => false,
    'accepted_statuscodes' => ['200-299']
];

switch ($type) {
    case 'keyword':
        $monitorConfig['type'] = 'HTTP(s) - Keyword';
        $monitorConfig['keyword'] = $keyword;
        $monitorConfig['invertKeyword'] = false;
        break;

    case 'json':
        $monitorConfig['type'] = 'HTTP(s) - Json Query';
        $monitorConfig['expectedValue'] = 'true';
        $monitorConfig['jsonPath'] = '$.success';
        break;

    case 'status':
    default:
        $monitorConfig['type'] = 'HTTP(s)';
        break;
}

// Create the monitor
$result = createMonitor($monitorConfig);

// Output result
if (!$is_cli) header('Content-Type: application/json');

$output = [];
if ($result['success']) {
    $output = [
        'status' => 'created',
        'message' => "Monitor '{$name}' successfully added to Uptime Kuma",
        'name' => $name,
        'url' => $fullUrl,
        'type' => $monitorConfig['type'],
        'interval' => $interval . ' seconds'
    ];

    echo $is_cli ? print_r($output, true) : json_encode($output);

    // Log the action (only if not CLI)
    if (!$is_cli && function_exists('session_tracking')) {
        session_tracking('uptime_kuma_monitor_added', [
            'name' => $name,
            'url' => $fullUrl,
            'type' => $monitorConfig['type']
        ]);
    }
} else {
    if (!$is_cli) http_response_code(500);

    $output = [
        'status' => 'failed',
        'message' => "Failed to create monitor '{$name}'",
        'http_code' => $result['http_code'],
        'response' => $result['response']
    ];

    echo $is_cli ? print_r($output, true) : json_encode($output);
}
?>
