<?php
/**
 * deploy_envchecksum.php
 * Returns SHA1 checksums for ENV_CONFIG files
 * Used by deploy_www.sh to verify if config files need to be synced
 */

// Security check - only allow access from specific hosts or with a secret token
$allowed_hosts = [
    'july02.bday.gold',
    'july03.bday.gold',
    'july04.bday.gold',
    'july02.birthday.gold',
    'july03.birthday.gold',
    'july04.birthday.gold'
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
$secret_token = $_GET['token'] ?? '';
if ($secret_token === 'DEPLOY_CHECKSUM_SECRET_2025') {
    $is_allowed = true;
}

if (!$is_allowed) {
    http_response_code(403);
    die(json_encode(['error' => 'Access denied']));
}

// Define the ENV_CONFIG path
// When accessed through webserver, use relative path from document root
// The webserver can access W: drive as /mnt/w in PHP on WSL
$document_root = $_SERVER['DOCUMENT_ROOT'] ?? '';

// Determine the correct path based on execution context
if (PHP_SAPI === 'cli') {
    // Running from command line (direct PHP execution)
    if (file_exists('/mnt/w/BIRTHDAY_SERVER/ENV_CONFIGS/')) {
        $env_config_path = '/mnt/w/BIRTHDAY_SERVER/ENV_CONFIGS/';
    } else {
        $env_config_path = '/var/www/BIRTHDAY_SERVER/ENV_CONFIGS/';
    }
} else {
    // Running through web server
    // Try to determine the correct path based on server environment
    if (strpos($document_root, 'dev') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'dev') !== false) {
        // Development environment - use Windows path accessible to PHP
        $env_config_path = 'W:/BIRTHDAY_SERVER/ENV_CONFIGS/';
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

$checksums = [];

foreach ($config_files as $file) {
    $full_path = $env_config_path . $file;
    if (file_exists($full_path)) {
        $checksums[$file] = sha1_file($full_path);
    } else {
        $checksums[$file] = null; // File doesn't exist
    }
}

// Output as JSON for easy parsing
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'checksums' => $checksums,
    'timestamp' => date('Y-m-d H:i:s')
]);