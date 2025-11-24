<?php
/**
 * Test PowerDNS API Connection
 * Quick test to verify PowerDNS API authentication and connectivity
 */
$addClasses[] = 'powerdns';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

if (!isset($powerdns)) {
    echo json_encode([
        'success' => false,
        'error' => 'PowerDNS class not loaded',
        'check' => 'Verify powerdns configuration in ENV_CONFIGS',
        'debug' => [
            'class_exists' => class_exists('PowerDNS'),
            'sitesettings_exists' => isset($sitesettings),
            'powerdns_config_exists' => isset($sitesettings['powerdns'])
        ]
    ]);
    exit;
}

// Get config details for debugging
$config_api_url = $sitesettings['powerdns']['api_url'] ?? 'NOT SET';
$config_api_key = $sitesettings['powerdns']['API_KEY'] ?? 'NOT SET';
$config_server_id = $sitesettings['powerdns']['server_id'] ?? 'NOT SET';

// Test: List all zones
$result = $powerdns->listZones();

echo json_encode([
    'success' => $result['success'],
    'http_code' => $result['http_code'] ?? 0,
    'zones' => $result['success'] ? $result['data'] : null,
    'error' => $result['success'] ? null : $result['error'],
    'raw_response' => !$result['success'] ? $result['error'] : null,
    'config_check' => [
        'api_url' => $config_api_url,
        'api_key_configured' => $config_api_key !== 'NOT SET',
        'api_key_length' => strlen($config_api_key),
        'api_key_first_chars' => $config_api_key !== 'NOT SET' ? substr($config_api_key, 0, 8) . '...' : 'N/A',
        'server_id' => $config_server_id
    ]
], JSON_PRETTY_PRINT);
