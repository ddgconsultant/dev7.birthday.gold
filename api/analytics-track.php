<?php
/**
 * Birthday.Gold Analytics Tracking Endpoint
 *
 * Receives client-side analytics events and stores them
 * in the bg_sessiontracking table.
 *
 * @version 1.0.0
 */

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Load core
$nosessiontracking = true; // Prevent recursive tracking
require_once(__DIR__ . '/../core/site-controller.php');

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Get raw POST data
    $rawData = file_get_contents('php://input');
    if (empty($rawData)) {
        http_response_code(400);
        echo json_encode(['error' => 'No data received']);
        exit;
    }

    // Parse JSON
    $data = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // Validate required fields
    if (empty($data['event']) || empty($data['timestamp'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Extract data
    $event = $data['event'];
    $timestamp = $data['timestamp'];
    $sessionId = $data['session_id'] ?? null;
    $visitId = $data['visit_id'] ?? null;
    $pageInfo = $data['page'] ?? [];
    $deviceInfo = $data['device'] ?? [];
    $eventData = $data['data'] ?? [];

    // Detect bots and crawlers
    $userAgent = $deviceInfo['userAgent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isBot = preg_match('/(bot|crawler|spider|facebook|twitter|linkedin|pinterest|slack|telegram|whatsapp|preview)/i', $userAgent);
    $isFacebookBot = preg_match('/facebookexternalhit|facebot/i', $userAgent);
    $isGoogleBot = preg_match('/googlebot|google-structured-data/i', $userAgent);

    // Detect test/internal traffic
    $isTestUser = isset($current_user_data['user_type']) && $current_user_data['user_type'] === 'test';
    $isInternalIP = in_array($client_ip, ['127.0.0.1', '::1']) || strpos($client_ip, '192.168.') === 0 || strpos($client_ip, '10.') === 0;

    // Determine traffic source category
    $trafficSource = 'organic';
    if ($isBot) $trafficSource = 'bot';
    if ($isFacebookBot) $trafficSource = 'facebook_bot';
    if ($isGoogleBot) $trafficSource = 'google_bot';
    if ($isTestUser) $trafficSource = 'test_user';
    if ($isInternalIP) $trafficSource = 'internal';

    // Get geolocation data (already loaded in site-controller)
    $geoLocation = null;
    if (!empty($client_locationdata) && is_array($client_locationdata)) {
        $geoLocation = [
            'country' => $client_locationdata['country'] ?? $client_locationdata['countryCode'] ?? null,
            'country_code' => $client_locationdata['countryCode'] ?? null,
            'region' => $client_locationdata['regionName'] ?? null,
            'city' => $client_locationdata['city'] ?? null,
            'zip' => $client_locationdata['zip'] ?? null,
            'lat' => $client_locationdata['lat'] ?? null,
            'lon' => $client_locationdata['lon'] ?? null,
            'timezone' => $client_locationdata['timezone'] ?? null
        ];
    }

    // Prepare tracking data
    $trackingData = [
        'event' => $event,
        'timestamp' => $timestamp,
        'client_session_id' => $sessionId,
        'visit_id' => $visitId,
        'page' => $pageInfo,
        'device' => $deviceInfo,
        'event_data' => $eventData,
        'traffic_source' => $trafficSource,
        'is_bot' => $isBot,
        'is_test' => $isTestUser,
        'is_internal' => $isInternalIP,
        'geo' => $geoLocation
    ];

    // Determine event name for database
    $eventName = 'analytics:' . $event;

    // Get current page from data
    $currentPage = $pageInfo['path'] ?? '/api/analytics-track';

    // Store in session tracking
    // Note: We're bypassing normal session_tracking() to avoid circular reference
    // and to store analytics-specific data structure

    $sql = "INSERT INTO bg_sessiontracking (
        `name`,
        `page`,
        `sessionid`,
        `ip`,
        `user_id`,
        `username`,
        `type`,
        `tracking_data`,
        `site`,
        `server`,
        `version`,
        `create_dt`
    ) VALUES (
        :name,
        :page,
        :sessionid,
        :ip,
        :user_id,
        :username,
        :type,
        :tracking_data,
        :site,
        :server,
        :version,
        NOW()
    )";

    $stmt = $database->prepare($sql);
    $stmt->execute([
        'name' => $eventName,
        'page' => $currentPage,
        'sessionid' => session_id() ?: $sessionId,
        'ip' => $client_ip,
        'user_id' => $current_user_data['user_id'] ?? null,
        'username' => $current_user_data['username'] ?? $current_user_data['user_username'] ?? null,
        'type' => 'analytics',
        'tracking_data' => json_encode($trackingData, JSON_PRETTY_PRINT),
        'site' => $site,
        'server' => $_SERVER['SERVER_ADDR'],
        'version' => $footerappversion ?? 'v1.0'
    ]);

    // Return success (204 No Content for efficiency)
    http_response_code(204);
    exit;

} catch (Exception $e) {
    // Log error
    error_log('Analytics tracking error: ' . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    exit;
}
