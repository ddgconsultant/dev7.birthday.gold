<?php
/**
 * Cleanup Hidden Files in B2
 * 
 * This script permanently deletes files marked as "hidden" in Backblaze B2.
 * These are files that were soft-deleted but still exist as previous versions.
 */

$addClasses[] = 'fileuploader';
include(__DIR__ . '/../core/site-controller.php');
echo "<pre>";

// Configuration
$bucketId = "0ea9d48bdfd51b1397050c1c";
$pathPrefix = "june27.bday.gold_/";
$testMode = true; // Set to false to actually delete files
$maxFilesToDelete = 100; // Safety limit

// Log file setup
$logDir = $dir['configs'] . "/DB_BACKUPS";
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . "/hidden_cleanup_" . date('Y-m-d_H-i-s') . ".log";

// Initialize logging
$logHandle = fopen($logFile, 'w');
if (!$logHandle) {
    die("Unable to create log file at: $logFile\n");
}

// Log function
function logMessage($message, $logHandle) {
    $timestamp = date('Y-m-d H:i:s');
    fwrite($logHandle, "[$timestamp] $message\n");
    echo "[$timestamp] $message\n";
}

logMessage("=== Hidden Files Cleanup Script Started ===", $logHandle);
logMessage("Path prefix: $pathPrefix", $logHandle);
logMessage("Test mode: " . ($testMode ? "ENABLED" : "DISABLED"), $logHandle);

// SSL fix
global $sitesettings;
$sitesettings['ssl_cert'] = [];

// Get authorization
$authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
$b2Credentials = $sitesettings['storage'];
$auth = base64_encode($b2Credentials['BACKBLAZE_ACCOUNT_ID'] . ':' . $b2Credentials['BACKBLAZE_APP_KEY']);
$headers = ['Authorization: Basic ' . $auth];

$response = $system->curlRequest($authUrl, $headers, [], 'GET');
if (!$response || !isset($response['decoded']['authorizationToken'])) {
    logMessage("ERROR: Authorization failed", $logHandle);
    fclose($logHandle);
    exit(1);
}

$authData = $response['decoded'];
logMessage("Authorization successful", $logHandle);

// Fetch files using file versions API
$hiddenFiles = [];
$startFileName = null;
$startFileId = null;
$totalApiCalls = 0;

do {
    $totalApiCalls++;
    logMessage("API call #$totalApiCalls - Fetching file versions...", $logHandle);
    
    $url = $authData['apiUrl'] . '/b2api/v2/b2_list_file_versions';
    $headers = ['Authorization: ' . $authData['authorizationToken']];
    
    $postData = [
        'bucketId' => $bucketId,
        'prefix' => $pathPrefix,
        'maxFileCount' => 10000
    ];
    
    if ($startFileName !== null) {
        $postData['startFileName'] = $startFileName;
        $postData['startFileId'] = $startFileId;
    }
    
    $response = $system->curlRequest($url, $headers, $postData, 'POST');
    
    if (!$response || !isset($response['decoded']['files'])) {
        logMessage("ERROR: Failed to fetch files", $logHandle);
        break;
    }
    
    $files = $response['decoded']['files'];
    
    // Filter for hidden files only
    foreach ($files as $file) {
        if ($file['action'] === 'hide') {
            $hiddenFiles[] = $file;
        }
    }
    
    // Check for next batch
    $startFileName = $response['decoded']['nextFileName'] ?? null;
    $startFileId = $response['decoded']['nextFileId'] ?? null;
    
    // Stop if we have enough hidden files
    if (count($hiddenFiles) >= 1000) {
        logMessage("Found enough hidden files. Stopping search.", $logHandle);
        break;
    }
    
} while ($startFileName !== null && $totalApiCalls < 5);

logMessage("Total hidden files found: " . count($hiddenFiles), $logHandle);

// Group hidden files by folder
$hiddenByFolder = [];
foreach ($hiddenFiles as $file) {
    $relativePath = substr($file['fileName'], strlen($pathPrefix));
    
    // Extract folder name
    if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{6}-[0-9]{3})/', $relativePath, $matches)) {
        $folder = $matches[1];
        if (!isset($hiddenByFolder[$folder])) {
            $hiddenByFolder[$folder] = [];
        }
        $hiddenByFolder[$folder][] = $file;
    }
}

logMessage("Hidden files found in " . count($hiddenByFolder) . " folders", $logHandle);

// Show summary
logMessage("\n=== FOLDERS WITH HIDDEN FILES ===", $logHandle);
$count = 0;
foreach ($hiddenByFolder as $folder => $files) {
    logMessage("Folder: $folder - " . count($files) . " hidden files", $logHandle);
    $count++;
    if ($count >= 20) {
        logMessage("... and " . (count($hiddenByFolder) - 20) . " more folders", $logHandle);
        break;
    }
}

// Delete hidden files if not in test mode
if (!$testMode && count($hiddenFiles) > 0) {
    logMessage("\n=== STARTING DELETION OF HIDDEN FILES ===", $logHandle);
    
    // Apply safety limit
    $filesToDelete = array_slice($hiddenFiles, 0, $maxFilesToDelete);
    if (count($hiddenFiles) > $maxFilesToDelete) {
        logMessage("SAFETY LIMIT: Only deleting first $maxFilesToDelete files out of " . count($hiddenFiles), $logHandle);
    }
    
    $deleted = 0;
    $failed = 0;
    
    foreach ($filesToDelete as $file) {
        // For hidden files, we need to permanently delete them
        $result = $fileuploader->deleteFile($system, $file['fileId'], $file['fileName']);
        
        if (is_array($result) && isset($result['fileId'])) {
            $deleted++;
            if ($deleted <= 10) {
                logMessage("Deleted hidden file: " . $file['fileName'], $logHandle);
            }
        } else {
            $failed++;
            logMessage("ERROR: Failed to delete hidden file: " . $file['fileName'], $logHandle);
        }
    }
    
    logMessage("\nDeleted $deleted hidden files, Failed: $failed", $logHandle);
    
} elseif ($testMode) {
    logMessage("\n=== TEST MODE - NO FILES DELETED ===", $logHandle);
    logMessage("Would delete " . count($hiddenFiles) . " hidden files", $logHandle);
    logMessage("Set \$testMode = false to actually delete hidden files", $logHandle);
    
    // Show sample files
    logMessage("\nSample hidden files that would be deleted:", $logHandle);
    $count = 0;
    foreach ($hiddenFiles as $file) {
        logMessage("  - " . $file['fileName'] . " (hidden on " . date('Y-m-d', $file['uploadTimestamp']/1000) . ")", $logHandle);
        $count++;
        if ($count >= 10) {
            logMessage("  ... and " . (count($hiddenFiles) - 10) . " more files", $logHandle);
            break;
        }
    }
}

logMessage("\n=== SCRIPT COMPLETED ===", $logHandle);
fclose($logHandle);

echo "\n";
echo "Hidden files cleanup completed.\n";
echo "Log file: $logFile\n";