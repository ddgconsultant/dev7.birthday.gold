<?php
/**
 * Configure New Web Server - Post Installation Configuration
 * This script updates all necessary configuration files to add a new web server to the cluster
 *
 * Usage: Call this after successfully installing a new web server
 * It will update:
 * - HAProxy configuration (http and https backends)
 * - deploy_userkey.php (for SSH key deployment)
 * - deploy_env_sync.php (for ENV file synchronization)
 * - deploy_envchecksum.php (for ENV checksum verification)
 * - deploy-ssl-certificates.php (for SSL certificate deployment)
 */

$addClasses[] = 'api';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get parameters
$hostname = $_GET['hostname'] ?? '';  // e.g., july05
$ip = $_GET['ip'] ?? '';  // e.g., 72.60.121.193
$rootcred_id = $_GET['rootcred_id'] ?? 2;  // Root credential ID in accessmanager
$api_key = $_GET['api_key'] ?? '';

// Validate API key
$auth_response = $api->authenticate_api_key($api_key);
if (!$auth_response['success']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid API key']);
    exit;
}

// Validate inputs
if (empty($hostname) || empty($ip)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'hostname and ip are required']);
    exit;
}

$results = [];

//=============================================================================
// 1. Update HAProxy Configuration
//=============================================================================
$haproxy_file = $_SERVER['DOCUMENT_ROOT'] . '/admin_actions/haproxy.cfg';
$haproxy_content = file_get_contents($haproxy_file);

// Add to HTTP backend
$http_line = "    server {$hostname} {$ip}:80 check\n## END OF 80webservers";
$haproxy_content = str_replace(
    '## END OF 80webservers',
    $http_line,
    $haproxy_content
);

// Add to HTTPS backend
$https_line = "    server {$hostname} {$ip}:443 ssl verify none check\n## END OF 443webservers";
$haproxy_content = str_replace(
    '## END OF 443webservers',
    $https_line,
    $haproxy_content
);

if (file_put_contents($haproxy_file, $haproxy_content)) {
    $results['haproxy'] = ['success' => true, 'message' => 'HAProxy configuration updated'];
} else {
    $results['haproxy'] = ['success' => false, 'message' => 'Failed to update HAProxy configuration'];
}

//=============================================================================
// 2. Update deploy_userkey.php
//=============================================================================
$userkey_file = $_SERVER['DOCUMENT_ROOT'] . '/admin_actions/deploy_userkey.php';
$userkey_content = file_get_contents($userkey_file);

// Find the $servers array and add new entry
$new_server_entry = "    ['ip' => '{$ip}', 'hostname' => '{$hostname}.bday.gold', 'rootcred_id' => {$rootcred_id}],\n];";
$userkey_content = preg_replace(
    '/\];.*?\/\/ End of servers array/s',
    $new_server_entry . "\n// End of servers array",
    $userkey_content
);

if (file_put_contents($userkey_file, $userkey_content)) {
    $results['deploy_userkey'] = ['success' => true, 'message' => 'deploy_userkey.php updated'];
} else {
    $results['deploy_userkey'] = ['success' => false, 'message' => 'Failed to update deploy_userkey.php'];
}

//=============================================================================
// 3. Update deploy_env_sync.php
//=============================================================================
$env_sync_file = $_SERVER['DOCUMENT_ROOT'] . '/admin_actions/deploy_env_sync.php';
$env_sync_content = file_get_contents($env_sync_file);

// Add both bday.gold and birthday.gold entries
$new_entries = "    '{$hostname}.bday.gold',\n    '{$hostname}.birthday.gold',\n];";
$env_sync_content = preg_replace(
    '/\];.*?$/s',
    $new_entries,
    $env_sync_content,
    1
);

if (file_put_contents($env_sync_file, $env_sync_content)) {
    $results['deploy_env_sync'] = ['success' => true, 'message' => 'deploy_env_sync.php updated'];
} else {
    $results['deploy_env_sync'] = ['success' => false, 'message' => 'Failed to update deploy_env_sync.php'];
}

//=============================================================================
// 4. Update deploy_envchecksum.php
//=============================================================================
$env_checksum_file = $_SERVER['DOCUMENT_ROOT'] . '/admin_actions/deploy_envchecksum.php';
$env_checksum_content = file_get_contents($env_checksum_file);

// Add both bday.gold and birthday.gold entries
$env_checksum_content = preg_replace(
    '/\];.*?$/s',
    $new_entries,
    $env_checksum_content,
    1
);

if (file_put_contents($env_checksum_file, $env_checksum_content)) {
    $results['deploy_envchecksum'] = ['success' => true, 'message' => 'deploy_envchecksum.php updated'];
} else {
    $results['deploy_envchecksum'] = ['success' => false, 'message' => 'Failed to update deploy_envchecksum.php'];
}

//=============================================================================
// 5. Update deploy-ssl-certificates.php
//=============================================================================
$ssl_file = $_SERVER['DOCUMENT_ROOT'] . '/admin_actions/deploy-ssl-certificates.php';
$ssl_content = file_get_contents($ssl_file);

// Add birthday.gold entry (SSL is only for birthday.gold domain)
$ssl_new_entry = "    '{$hostname}.birthday.gold',\n];";
$ssl_content = preg_replace(
    '/\];.*?$/s',
    $ssl_new_entry,
    $ssl_content,
    1
);

if (file_put_contents($ssl_file, $ssl_content)) {
    $results['deploy_ssl'] = ['success' => true, 'message' => 'deploy-ssl-certificates.php updated'];
} else {
    $results['deploy_ssl'] = ['success' => false, 'message' => 'Failed to update deploy-ssl-certificates.php'];
}

//=============================================================================
// Output Results
//=============================================================================
$all_success = true;
foreach ($results as $result) {
    if (!$result['success']) {
        $all_success = false;
        break;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => $all_success,
    'hostname' => $hostname,
    'ip' => $ip,
    'results' => $results,
    'next_steps' => [
        'Deploy HAProxy configuration to all HAProxy nodes',
        'Run deploy_userkey.php to install SSH keys on new server',
        'Run deploy_env_sync.php to sync ENV files',
        'Run deploy-ssl-certificates.php to install SSL certificates',
        'Verify MySQL replication is working',
        'Add monitoring in Uptime Kuma',
        'Add to Metabase'
    ]
]);
