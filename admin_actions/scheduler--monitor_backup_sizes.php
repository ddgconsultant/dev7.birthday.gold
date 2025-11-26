<?php
/**
 * Database Backup Size Monitor
 *
 * This script monitors Backblaze B2 bucket for database backups and alerts
 * when backup sizes are abnormally small (indicating failed backups).
 *
 * A backup file of ~120 bytes or less typically indicates a mysqldump failure
 * (e.g., credential issues, permission problems, etc.)
 *
 * Bucket: birthdaygold202306-technical
 * Path: december20.bday.gold/
 *
 * Expected successful backup sizes:
 * - mysql: ~300KB+
 * - birthday_gold_www: ~700MB+
 *
 * Usage: Run as a scheduled job (e.g., daily at 7:00 AM to check overnight backups)
 */

$addClasses[] = 'fileuploader';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Configuration
$bucketName = "birthdaygold202306-technical";
$bucketId = "0ea9d48bdfd51b1397050c1c";
$pathPrefix = "december20.bday.gold/";

// Minimum expected file sizes in bytes
$minSizes = [
    'mysql' => 100 * 1024,           // 100 KB minimum for mysql backup
    'birthday_gold_www' => 100 * 1024 * 1024  // 100 MB minimum for main db backup
];

// Alert threshold - files smaller than this are considered failed
$failedSizeThreshold = 1024; // 1 KB

// How many days back to check (URL param: ?days=2)
$daysToCheck = 7;
if (isset($_GET['days']) && is_numeric($_GET['days'])) {
    $daysToCheck = max(1, min(90, (int)$_GET['days'])); // Clamp between 1-90 days
}

// Output format: 'json' for API/n8n consumption, 'text' for CLI
$outputFormat = isset($_GET['format']) ? $_GET['format'] : 'text';
if (php_sapi_name() === 'cli') {
    // CLI: php script.php [format] [days]
    $outputFormat = isset($argv[1]) ? $argv[1] : 'text';
    if (isset($argv[2]) && is_numeric($argv[2])) {
        $daysToCheck = max(1, min(90, (int)$argv[2]));
    }
}

// Results array
$results = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'bucket' => $bucketName,
    'path' => $pathPrefix,
    'daysChecked' => $daysToCheck,
    'backups' => [],
    'alerts' => [],
    'summary' => [
        'totalFiles' => 0,
        'successfulBackups' => 0,
        'failedBackups' => 0,
        'suspiciousBackups' => 0
    ]
];

// Helper function for output
function output($message, $format) {
    if ($format === 'text') {
        echo $message . "\n";
    }
}

output("=== Database Backup Size Monitor ===", $outputFormat);
output("Checking backups in: $bucketName / $pathPrefix", $outputFormat);
output("Date: " . date('Y-m-d H:i:s'), $outputFormat);
output("", $outputFormat);

// Get B2 authorization
$authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
$b2Credentials = $sitesettings['storage'];
$auth = base64_encode($b2Credentials['BACKBLAZE_ACCOUNT_ID'] . ':' . $b2Credentials['BACKBLAZE_APP_KEY']);
$headers = ['Authorization: Basic ' . $auth];

$response = $system->curlRequest($authUrl, $headers, [], 'GET');
if (!$response || !isset($response['decoded']['authorizationToken'])) {
    $results['success'] = false;
    $results['error'] = 'Authorization failed';

    if ($outputFormat === 'json') {
        header('Content-Type: application/json');
        echo json_encode($results, JSON_PRETTY_PRINT);
    } else {
        output("ERROR: Authorization failed", $outputFormat);
    }
    exit(1);
}

$authData = $response['decoded'];
output("Authorization successful", $outputFormat);

// List files in the bucket with our prefix
$allFiles = [];
$startFileName = null;

do {
    $url = $authData['apiUrl'] . '/b2api/v2/b2_list_file_names';
    $requestHeaders = ['Authorization: ' . $authData['authorizationToken']];

    $postData = [
        'bucketId' => $bucketId,
        'prefix' => $pathPrefix,
        'maxFileCount' => 1000
    ];

    if ($startFileName !== null) {
        $postData['startFileName'] = $startFileName;
    }

    $response = $system->curlRequest($url, $requestHeaders, $postData, 'POST');

    if (!$response || !isset($response['decoded']['files'])) {
        break;
    }

    $files = $response['decoded']['files'];
    $allFiles = array_merge($allFiles, $files);

    $startFileName = $response['decoded']['nextFileName'] ?? null;

    // Safety limit
    if (count($allFiles) >= 5000) {
        break;
    }

} while ($startFileName !== null);

output("Found " . count($allFiles) . " total files in bucket path", $outputFormat);
output("", $outputFormat);

// Filter to recent backup files and analyze
$cutoffTime = strtotime("-$daysToCheck days");
$recentBackups = [];

foreach ($allFiles as $file) {
    $fileName = basename($file['fileName']);

    // Skip non-backup files
    if (!preg_match('/\.(sql\.gz|sql)$/', $fileName)) {
        continue;
    }

    // Parse upload time (B2 uses milliseconds)
    $uploadTime = (int)($file['uploadTimestamp'] / 1000);

    // Only process recent files
    if ($uploadTime < $cutoffTime) {
        continue;
    }

    // Determine database name from filename
    $dbName = 'unknown';
    if (strpos($fileName, 'birthday_gold_www') !== false) {
        $dbName = 'birthday_gold_www';
    } elseif (strpos($fileName, 'mysql_') !== false) {
        $dbName = 'mysql';
    }

    // B2 API uses 'contentLength' for file size
    $size = $file['contentLength'] ?? $file['size'] ?? 0;
    $sizeFormatted = formatBytes($size);
    $uploadDate = date('Y-m-d H:i:s', $uploadTime);

    // Determine status
    $status = 'success';
    $statusMessage = 'OK';

    if ($size <= $failedSizeThreshold) {
        $status = 'failed';
        $statusMessage = 'FAILED - File too small (likely mysqldump error)';
    } elseif (isset($minSizes[$dbName]) && $size < $minSizes[$dbName]) {
        $status = 'suspicious';
        $statusMessage = 'WARNING - Smaller than expected';
    }

    $backupInfo = [
        'fileName' => $fileName,
        'fullPath' => $file['fileName'],
        'database' => $dbName,
        'size' => $size,
        'sizeFormatted' => $sizeFormatted,
        'uploadTime' => $uploadDate,
        'uploadTimestamp' => $uploadTime,
        'status' => $status,
        'statusMessage' => $statusMessage
    ];

    $recentBackups[] = $backupInfo;
    $results['backups'][] = $backupInfo;

    // Track alerts
    if ($status === 'failed') {
        $results['alerts'][] = [
            'type' => 'FAILED_BACKUP',
            'severity' => 'critical',
            'message' => "Backup failed: $fileName ($sizeFormatted)",
            'file' => $fileName,
            'date' => $uploadDate
        ];
        $results['summary']['failedBackups']++;
    } elseif ($status === 'suspicious') {
        $results['alerts'][] = [
            'type' => 'SUSPICIOUS_SIZE',
            'severity' => 'warning',
            'message' => "Backup size suspicious: $fileName ($sizeFormatted)",
            'file' => $fileName,
            'date' => $uploadDate
        ];
        $results['summary']['suspiciousBackups']++;
    } else {
        $results['summary']['successfulBackups']++;
    }

    $results['summary']['totalFiles']++;
}

// Sort by upload time (newest first)
usort($recentBackups, function($a, $b) {
    return $b['uploadTimestamp'] - $a['uploadTimestamp'];
});

// Output results
output("=== Recent Backups (Last $daysToCheck days) ===", $outputFormat);
output("", $outputFormat);

// Group by date for easier reading
$byDate = [];
foreach ($recentBackups as $backup) {
    $date = date('Y-m-d', $backup['uploadTimestamp']);
    if (!isset($byDate[$date])) {
        $byDate[$date] = [];
    }
    $byDate[$date][] = $backup;
}

foreach ($byDate as $date => $backups) {
    output("--- $date ---", $outputFormat);
    foreach ($backups as $backup) {
        $icon = $backup['status'] === 'success' ? '[OK]' :
               ($backup['status'] === 'failed' ? '[FAILED]' : '[WARN]');
        output("  $icon {$backup['database']}: {$backup['sizeFormatted']} - {$backup['fileName']}", $outputFormat);
    }
    output("", $outputFormat);
}

// Summary
output("=== Summary ===", $outputFormat);
output("Total backups checked: " . $results['summary']['totalFiles'], $outputFormat);
output("Successful: " . $results['summary']['successfulBackups'], $outputFormat);
output("Failed: " . $results['summary']['failedBackups'], $outputFormat);
output("Suspicious: " . $results['summary']['suspiciousBackups'], $outputFormat);
output("", $outputFormat);

// Alerts
if (!empty($results['alerts'])) {
    output("=== ALERTS ===", $outputFormat);
    foreach ($results['alerts'] as $alert) {
        $severity = strtoupper($alert['severity']);
        output("[$severity] {$alert['message']}", $outputFormat);
    }
    output("", $outputFormat);
}

// Check if we have today's backup
$today = date('Y-m-d');
$hasTodayBackup = false;
$hasTodayMainDb = false;

foreach ($recentBackups as $backup) {
    if (date('Y-m-d', $backup['uploadTimestamp']) === $today) {
        $hasTodayBackup = true;
        if ($backup['database'] === 'birthday_gold_www' && $backup['status'] === 'success') {
            $hasTodayMainDb = true;
        }
    }
}

if (!$hasTodayBackup) {
    $results['alerts'][] = [
        'type' => 'MISSING_BACKUP',
        'severity' => 'warning',
        'message' => "No backup found for today ($today) - may not have run yet",
        'date' => $today
    ];
}

// Update overall success status
$results['success'] = ($results['summary']['failedBackups'] === 0);
$results['hasCriticalAlerts'] = ($results['summary']['failedBackups'] > 0);

// JSON output for API consumption
if ($outputFormat === 'json') {
    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// Final status message for text output
if ($results['summary']['failedBackups'] > 0) {
    output("STATUS: CRITICAL - " . $results['summary']['failedBackups'] . " failed backup(s) detected!", $outputFormat);
    exit(2);
} elseif ($results['summary']['suspiciousBackups'] > 0) {
    output("STATUS: WARNING - " . $results['summary']['suspiciousBackups'] . " suspicious backup(s) detected", $outputFormat);
    exit(1);
} else {
    output("STATUS: OK - All recent backups appear healthy", $outputFormat);
    exit(0);
}

/**
 * Format bytes to human-readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}
