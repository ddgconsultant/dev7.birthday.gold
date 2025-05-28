<?php
// admin/mouse-tracker_getscreenshot.php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

if (!isset($_GET['path'])) {
    http_response_code(400);
    exit('No path specified');
}

// Clean and construct the path
$sessionPath = str_replace(['..', '\\'], ['', '/'], $_GET['path']);
$dir = dirname($sessionPath);
$screenshotPath = dirname(__DIR__) . '/../tracking_logs/' . $dir . '/screenshot.jpg';

// Debug info (remove in production)
if (isset($_GET['debug'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'requested_path' => $_GET['path'],
        'cleaned_session_path' => $sessionPath,
        'directory' => $dir,
        'full_screenshot_path' => $screenshotPath,
        'exists' => file_exists($screenshotPath),
        'readable' => is_readable($screenshotPath),
        'size' => file_exists($screenshotPath) ? filesize($screenshotPath) : 0
    ]);
    exit;
}

if (file_exists($screenshotPath) && is_readable($screenshotPath)) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Content-Length: ' . filesize($screenshotPath));
    readfile($screenshotPath);
} else {
    http_response_code(404);
    exit('Screenshot not found at: ' . $screenshotPath);
}