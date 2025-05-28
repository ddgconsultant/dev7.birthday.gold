<?php
// track-checkout.php

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Get the raw POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON data');
}

// Add server timestamp
$data['server_timestamp'] = date('Y-m-d H:i:s');

// You might want to validate the session ID and other data here

// Set base directory one level up from web root
$baseDir = dirname(__DIR__) . '/tracking_logs';  // Using __DIR__ to get current script's directory, then go up one level
// Alternative approach: $baseDir = '../tracking_logs';

if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0777, true)) {
        http_response_code(500);
        exit('Failed to create tracking_logs directory');
    }
}

// Get page name from URL
$pageName = basename(parse_url($data['metadata']['url'], PHP_URL_PATH), '.php');
if (empty($pageName)) {
    $pageName = 'index';
}

// Clean the page name to ensure it's safe for filesystem
$pageName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $pageName);

// Build the full path
$filename = $baseDir . '/' . $pageName . '/' . $data['sessionId'] . '.json';

// Ensure page directory exists
if (!is_dir(dirname($filename))) {
    if (!mkdir(dirname($filename), 0777, true)) {
        http_response_code(500);
        exit('Failed to create date directory');
    }
}

// Append to existing file or create new one
$existingData = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];
$existingData[] = $data;

file_put_contents($filename, json_encode($existingData, JSON_PRETTY_PRINT));

// Send success response
http_response_code(200);
echo json_encode(['status' => 'success']);