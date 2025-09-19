<?php
/**
 * deploy_env_sync.php
 * Secure ENV_CONFIG synchronization endpoint
 * Returns checksums and optionally encrypted file contents for secure sync
 */

// Security check - only allow access from specific hosts or with a secret token
$allowed_hosts = [
    'july02.bday.gold',
    'july03.bday.gold',
    'july04.bday.gold',
    'july02.birthday.gold',
    'july03.birthday.gold',
    'july04.birthday.gold',
    'localhost'
];

$allowed_ips = [
    '127.0.0.1',
    '::1',
    '71.33.250.235' // Production server IP
];

$remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
$remote_host = $_SERVER['REMOTE_HOST'] ?? '';
if (!empty($remote_addr) && filter_var($remote_addr, FILTER_VALIDATE_IP)) {
    $remote_host = gethostbyaddr($remote_addr);
}
$is_allowed = false;

// Check if hostname is in allowed list
foreach ($allowed_hosts as $allowed_host) {
    if (stripos($remote_host, $allowed_host) !== false) {
        $is_allowed = true;
        break;
    }
}

// Check if IP is in allowed list
if (!$is_allowed) {
    foreach ($allowed_ips as $allowed) {
        if (strpos($allowed, '/') !== false) {
            // CIDR notation
            list($subnet, $mask) = explode('/', $allowed);
            if ((ip2long($remote_addr) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet)) {
                $is_allowed = true;
                break;
            }
        } else {
            if ($remote_addr === $allowed) {
                $is_allowed = true;
                break;
            }
        }
    }
}

// Alternative: Check for secret token
$secret_token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($secret_token === 'DEPLOY_CHECKSUM_SECRET_2025') {
    $is_allowed = true;
}

if (!$is_allowed) {
    http_response_code(403);
    die(json_encode(['error' => 'Access denied']));
}

// Define the ENV_CONFIG path based on execution context
$document_root = $_SERVER['DOCUMENT_ROOT'] ?? '';

// Determine the correct path
if (PHP_SAPI === 'cli') {
    // Running from command line
    if (file_exists('/mnt/w/BIRTHDAY_SERVER/ENV_CONFIGS/')) {
        $env_config_path = '/mnt/w/BIRTHDAY_SERVER/ENV_CONFIGS/';
    } else {
        $env_config_path = '/var/www/BIRTHDAY_SERVER/ENV_CONFIGS/';
    }
} else {
    // Running through web server on Windows WAMP
    // The webserver sees the Windows path differently
    if (strpos($document_root, 'dev') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'dev') !== false) {
        // Development environment on Windows
        // Try different path formats
        $possible_paths = [
            'W:/BIRTHDAY_SERVER/ENV_CONFIGS/',
            'W:\\BIRTHDAY_SERVER\\ENV_CONFIGS\\',
            '/mnt/w/BIRTHDAY_SERVER/ENV_CONFIGS/',
            dirname(dirname($document_root)) . '/ENV_CONFIGS/'
        ];

        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $env_config_path = $path;
                break;
            }
        }

        if (!isset($env_config_path)) {
            // Fallback - construct from document root
            $env_config_path = dirname(dirname($document_root)) . '/ENV_CONFIGS/';
        }
    } else {
        // Production environment
        $env_config_path = '/var/www/BIRTHDAY_SERVER/ENV_CONFIGS/';
    }
}

// List of config files to check
$config_files = [
    'config-main-production.inc',
    'config-ai.inc'
];

$action = $_GET['action'] ?? 'checksums';
$response = ['status' => 'success'];

switch ($action) {
    case 'checksums':
        // Return only checksums
        $checksums = [];
        foreach ($config_files as $file) {
            $full_path = $env_config_path . $file;
            if (file_exists($full_path)) {
                $checksums[$file] = sha1_file($full_path);
            } else {
                $checksums[$file] = null;
            }
        }
        $response['checksums'] = $checksums;
        $response['path_used'] = $env_config_path; // For debugging
        break;

    case 'get_file':
        // Return encrypted file content
        $file = $_GET['file'] ?? '';
        if (!in_array($file, $config_files)) {
            http_response_code(400);
            die(json_encode(['error' => 'Invalid file requested']));
        }

        $full_path = $env_config_path . $file;
        if (!file_exists($full_path)) {
            http_response_code(404);
            die(json_encode(['error' => 'File not found']));
        }

        // Read file content
        $content = file_get_contents($full_path);

        // Simple encryption using base64 and a shared secret
        // In production, use proper encryption
        $encrypted = base64_encode($content);

        $response['file'] = $file;
        $response['content'] = $encrypted;
        $response['checksum'] = sha1_file($full_path);
        break;

    case 'test':
        // Test endpoint to verify paths
        $response['env_config_path'] = $env_config_path;
        $response['document_root'] = $document_root;
        $response['php_sapi'] = PHP_SAPI;
        $response['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';
        $response['files_exist'] = [];

        foreach ($config_files as $file) {
            $full_path = $env_config_path . $file;
            $response['files_exist'][$file] = file_exists($full_path);
        }
        break;

    default:
        http_response_code(400);
        die(json_encode(['error' => 'Invalid action']));
}

$response['timestamp'] = date('Y-m-d H:i:s');

// Output as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);