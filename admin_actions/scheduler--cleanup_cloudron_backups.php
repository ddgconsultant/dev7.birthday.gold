<?php
exit;
/**
 * Cloudron Backup Cleanup Script
 * 
 * This script manages the retention of Cloudron app backups in Backblaze B2 bucket.
 * It keeps backups according to the following rules:
 * - Keep all backups from January 1st of any year
 * - Keep backups from the 15th of any month within the last year
 * - Keep all backups from the last 30 days
 * - Delete everything else
 * 
 * Bucket: birthdaygold202306-technical (22,714 files, 3.3TB)
 * Path: june27.bday.gold_
 * 
 * Cloudron backup structure:
 * - Each backup is a folder: YYYY-MM-DD-HHMMSS-NNN
 * - Contains multiple files per backup (main file, hidden files, etc.)
 * - ALL files in an expired backup folder must be deleted together
 * 
 * Note: Uses b2_list_file_versions API to find all files including hidden ones
 */

$addClasses[] = 'fileuploader';
include(__DIR__ . '/../core/site-controller.php');
echo "<pre>";
// Configuration
$bucketName = "birthdaygold202306-technical";
$bucketId = "0ea9d48bdfd51b1397050c1c";
$pathPrefix = "june27.bday.gold_/";
$testMode = false; // Set to false to actually delete files
$maxBackupsToProcess = 500; // Limit for safety
$maxBackupsToDelete = 50; // Maximum number of backups to delete in one run
$termDaysAgotag='90';
$termYearsAgotag='2';
// Log file setup
$logDir = $dir['configs'] . "/DB_BACKUPS";
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . "/cloudron_cleanup_" . date('Y-m-d_H-i-s') . ".log";

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

logMessage("=== Cloudron Backup Cleanup Script Started ===", $logHandle);
logMessage("Bucket: $bucketName", $logHandle);
logMessage("Path prefix: $pathPrefix", $logHandle);
logMessage("Test mode: " . ($testMode ? "ENABLED" : "DISABLED"), $logHandle);

// Arrays to track backups
$allBackups = [];
$backupsToKeep = [];
$backupsToDelete = [];
$deletedFiles = [];
$failedDeletions = [];

// Get current date info
$currentDate = new DateTime();
$termDaysAgo = (clone $currentDate)->sub(new DateInterval('P'.$termDaysAgotag.'D'));
$termYearAgo = (clone $currentDate)->sub(new DateInterval('P'.$termYearsAgotag.'Y'));

logMessage("Current date: " . $currentDate->format('Y-m-d'), $logHandle);
logMessage($termDaysAgotag." day(s) ago: " . $termDaysAgo->format('Y-m-d'), $logHandle);
logMessage($termYearsAgotag." year(s) ago: " . $termYearAgo->format('Y-m-d'), $logHandle);

// Initialize B2 client
logMessage("Using bucket ID: $bucketId", $logHandle);
logMessage("Processing Cloudron backups in path: $pathPrefix", $logHandle);

// SSL fix - disable SSL verification for B2 connection
// This is required for cURL to work with Backblaze B2 in this environment
global $sitesettings;
$sitesettings['ssl_cert'] = []; // Forces Guzzle to use verify => false for cURL

// Get authorization for B2 API
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

// Fetch all files using file versions API to include hidden files
$allFiles = [];
$startFileName = null;
$startFileId = null;
$totalApiCalls = 0;

do {
    $totalApiCalls++;
    logMessage("API call #$totalApiCalls - Fetching files...", $logHandle);
    
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
        logMessage("ERROR: Failed to fetch files in API call #$totalApiCalls", $logHandle);
        break;
    }
    
    $files = $response['decoded']['files'];
    $fileCount = count($files);
    logMessage("  Retrieved $fileCount files", $logHandle);
    
    // Add files to our collection
    $allFiles = array_merge($allFiles, $files);
    
    // Check for next batch
    $startFileName = $response['decoded']['nextFileName'] ?? null;
    $startFileId = $response['decoded']['nextFileId'] ?? null;
    
    // Safety limit
    if (count($allFiles) >= 50000 || $totalApiCalls >= 10) {
        logMessage("Reached safety limit. Total files: " . count($allFiles), $logHandle);
        break;
    }
    
} while ($startFileName !== null);

logMessage("Total files retrieved: " . count($allFiles), $logHandle);

// Process files and group by backup folder
$backupGroups = [];
$hiddenFiles = 0;
$uploadedFiles = 0;

foreach ($allFiles as $file) {
    // Count file actions
    if ($file['action'] === 'hide') {
        $hiddenFiles++;
    } else {
        $uploadedFiles++;
    }
    
    // Skip if not our prefix
    if (strpos($file['fileName'], $pathPrefix) !== 0) {
        continue;
    }
    
    // Extract backup folder
    $relativePath = substr($file['fileName'], strlen($pathPrefix));
    
    // Check if this is a folder entry itself (ends with folder name only, no file inside)
    if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{6}-[0-9]{3})$/', $relativePath, $matches)) {
        // This is the folder entry itself
        $backupFolder = $matches[1];
        
        if (!isset($backupGroups[$backupFolder])) {
            $backupGroups[$backupFolder] = [
                'folder' => $backupFolder,
                'files' => [],
                'totalSize' => 0,
                'fileCount' => 0,
                'hasActiveFiles' => false,
                'folderEntry' => null
            ];
        }
        
        // Store the folder entry itself
        $backupGroups[$backupFolder]['folderEntry'] = $file;
        if ($file['action'] === 'upload') {
            $backupGroups[$backupFolder]['hasActiveFiles'] = true;
        }
    }
    // Check if this is a file within a backup folder
    elseif (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{6}-[0-9]{3})\//', $relativePath, $matches)) {
        $backupFolder = $matches[1];
        
        if (!isset($backupGroups[$backupFolder])) {
            $backupGroups[$backupFolder] = [
                'folder' => $backupFolder,
                'files' => [],
                'totalSize' => 0,
                'fileCount' => 0,
                'hasActiveFiles' => false,
                'folderEntry' => null
            ];
        }
        
        $backupGroups[$backupFolder]['files'][] = $file;
        $backupGroups[$backupFolder]['totalSize'] += $file['size'] ?? 0;
        $backupGroups[$backupFolder]['fileCount']++;
        
        // Track if this backup has any active (non-hidden) files
        if ($file['action'] === 'upload') {
            $backupGroups[$backupFolder]['hasActiveFiles'] = true;
        }
    }
}

logMessage("File actions - Uploaded: $uploadedFiles, Hidden: $hiddenFiles", $logHandle);
logMessage("Unique backup folders found: " . count($backupGroups), $logHandle);

// Analyze year distribution
$yearCounts = [];
foreach ($backupGroups as $folder => $data) {
    if (preg_match('/^(\d{4})-/', $folder, $matches)) {
        $year = $matches[1];
        $yearCounts[$year] = ($yearCounts[$year] ?? 0) + 1;
    }
}
logMessage("Backups by year: " . json_encode($yearCounts), $logHandle);

// Process each backup folder (with safety limit)
$processedCount = 0;
foreach ($backupGroups as $backupFolder => $backupData) {
    // Apply safety limit
    if ($processedCount >= $maxBackupsToProcess) {
        logMessage("Reached maximum backup processing limit ($maxBackupsToProcess). Stopping.", $logHandle);
        break;
    }
    $processedCount++;
    
    $allBackups[] = $backupFolder;
    
    // Parse date from backup folder name
    // Format: YYYY-MM-DD-HHMMSS-NNN
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})-(\d{2})(\d{2})(\d{2})-\d{3}$/', $backupFolder, $matches)) {
        $year = $matches[1];
        $month = $matches[2];
        $day = $matches[3];
        $hour = $matches[4];
        $minute = $matches[5];
        $second = $matches[6];
        
        $backupDate = DateTime::createFromFormat('Y-m-d H:i:s', "$year-$month-$day $hour:$minute:$second");
        
        if ($backupDate === false) {
            logMessage("WARNING: Could not parse date from backup folder: $backupFolder", $logHandle);
            continue;
        }
        
        $shouldKeep = false;
        $keepReason = "";
        
        // Rule 1: Keep if January 1st
        if ($backupDate->format('m-d') === '01-01') {
            $shouldKeep = true;
            $keepReason = "January 1st backup";
        }
        // Rule 2: Keep if 15th of month within last year
        elseif ($backupDate->format('d') === '15' && $backupDate >= $termYearAgo) {
            $shouldKeep = true;
            $keepReason = "15th of month within ".$termYearsAgotag." year(s)";
        }
        // Rule 3: Keep if within last 30 days
        elseif ($backupDate >= $termDaysAgo) {
            $shouldKeep = true;
            $keepReason = 'Within last '.$termDaysAgotag.' days';
        }
        
        if ($shouldKeep) {
            $backupsToKeep[] = [
                'folder' => $backupFolder,
                'date' => $backupDate->format('Y-m-d H:i:s'),
                'reason' => $keepReason,
                'fileCount' => $backupData['fileCount'],
                'hasActiveFiles' => $backupData['hasActiveFiles'],
                'size' => round($backupData['totalSize'] / 1024 / 1024, 2) . ' MB'
            ];
        } else {
            // Only delete if there are files to delete (not already all hidden)
            if ($backupData['hasActiveFiles']) {
                $backupsToDelete[] = [
                    'folder' => $backupFolder,
                    'files' => $backupData['files'],
                    'folderEntry' => $backupData['folderEntry'],
                    'date' => $backupDate->format('Y-m-d H:i:s'),
                    'age' => $currentDate->diff($backupDate)->days . ' days old',
                    'fileCount' => $backupData['fileCount'],
                    'size' => round($backupData['totalSize'] / 1024 / 1024, 2) . ' MB'
                ];
            }
        }
    } else {
        logMessage("WARNING: Backup folder doesn't match expected pattern: $backupFolder", $logHandle);
    }
}

// Log summary
logMessage("\n=== SUMMARY ===", $logHandle);
logMessage("Total backup folders found: " . count($allBackups), $logHandle);
logMessage("Backup folders to keep: " . count($backupsToKeep), $logHandle);
logMessage("Backup folders to delete: " . count($backupsToDelete), $logHandle);

// Calculate total files and size to delete
$totalFilesToDelete = 0;
$totalSizeToDelete = 0;
foreach ($backupsToDelete as $backup) {
    $totalFilesToDelete += $backup['fileCount'];
    $totalSizeToDelete += floatval($backup['size']);
}
logMessage("Total files to delete: $totalFilesToDelete", $logHandle);
logMessage("Total size to delete: " . round($totalSizeToDelete, 2) . " MB", $logHandle);

// Log backups to keep
logMessage("\n=== BACKUP FOLDERS TO KEEP ===", $logHandle);
foreach ($backupsToKeep as $backup) {
    logMessage("KEEP: {$backup['folder']} (Date: {$backup['date']}, Files: {$backup['fileCount']}, Size: {$backup['size']}, Reason: {$backup['reason']})", $logHandle);
}

// Log backups to delete
logMessage("\n=== BACKUP FOLDERS TO DELETE ===", $logHandle);
// Only show first 20 for brevity
$deleteCount = 0;
foreach ($backupsToDelete as $backup) {
    logMessage("DELETE: {$backup['folder']} (Date: {$backup['date']}, Files: {$backup['fileCount']}, Size: {$backup['size']}, Age: {$backup['age']})", $logHandle);
    $deleteCount++;
    if ($deleteCount >= 20) {
        logMessage("... and " . (count($backupsToDelete) - 20) . " more backup folders", $logHandle);
        break;
    }
}

// Delete backups if not in test mode
if (count($backupsToDelete) > 0 && !$testMode) {
    logMessage("\n=== STARTING DELETION PROCESS ===", $logHandle);
    
    // Apply deletion limit for safety
    $backupsToDeleteLimited = array_slice($backupsToDelete, 0, $maxBackupsToDelete);
    if (count($backupsToDelete) > $maxBackupsToDelete) {
        logMessage("SAFETY LIMIT: Only deleting first $maxBackupsToDelete backup folders out of " . count($backupsToDelete) . " total", $logHandle);
        logMessage("Run the script again to delete more backups", $logHandle);
    }
    
    foreach ($backupsToDeleteLimited as $backup) {
        logMessage("\nDeleting backup folder: {$backup['folder']} ({$backup['fileCount']} files)...", $logHandle);
        
        $folderDeletedCount = 0;
        $folderFailedCount = 0;
        
        foreach ($backup['files'] as $file) {
            // Only delete active files (not already hidden)
            if ($file['action'] !== 'upload') {
                continue;
            }
            
            $result = $fileuploader->deleteFile($system, $file['fileId'], $file['fileName']);
            
            // B2 delete API returns fileId and fileName on success
            if (is_array($result) && isset($result['fileId'])) {
                $deletedFiles[] = $file['fileName'];
                $folderDeletedCount++;
            } else {
                $failedDeletions[] = $file['fileName'];
                $folderFailedCount++;
                logMessage("ERROR: Failed to delete {$file['fileName']}", $logHandle);
            }
        }
        
        // Also delete the folder entry itself if it exists
        if (isset($backup['folderEntry']) && $backup['folderEntry'] !== null && $backup['folderEntry']['action'] === 'upload') {
            $folderFile = $backup['folderEntry'];
            $result = $fileuploader->deleteFile($system, $folderFile['fileId'], $folderFile['fileName']);
            
            if (is_array($result) && isset($result['fileId'])) {
                $deletedFiles[] = $folderFile['fileName'];
                $folderDeletedCount++;
                logMessage("  Deleted folder entry: {$folderFile['fileName']}", $logHandle);
            } else {
                $failedDeletions[] = $folderFile['fileName'];
                $folderFailedCount++;
                logMessage("ERROR: Failed to delete folder entry {$folderFile['fileName']}", $logHandle);
            }
        }
        
        logMessage("Folder {$backup['folder']}: Deleted $folderDeletedCount files, Failed $folderFailedCount files", $logHandle);
    }
} elseif ($testMode && count($backupsToDelete) > 0) {
    logMessage("\n=== TEST MODE - NO FILES DELETED ===", $logHandle);
    logMessage("Run with \$testMode = false to actually delete files", $logHandle);
    
    // Check if deletion limit would apply
    if (count($backupsToDelete) > $maxBackupsToDelete) {
        logMessage("\nSAFETY LIMIT: When run in production mode, only $maxBackupsToDelete backups", $logHandle);
        logMessage("will be deleted per run (out of " . count($backupsToDelete) . " total)", $logHandle);
    }
    
    // Show sample of backup folders that would be deleted
    logMessage("\nSample backup folders that would be deleted:", $logHandle);
    logMessage("(Each folder contains multiple files that will all be deleted together)", $logHandle);
    $sampleCount = 0;
    foreach ($backupsToDelete as $backup) {
        logMessage("  - Folder: {$backup['folder']} ({$backup['fileCount']} files, {$backup['age']})", $logHandle);
        $sampleCount++;
        if ($sampleCount >= 10) {
            logMessage("  ... and " . (count($backupsToDelete) - 10) . " more backup folders", $logHandle);
            break;
        }
    }
}

// Final summary
logMessage("\n=== FINAL SUMMARY ===", $logHandle);
if (!$testMode) {
    logMessage("Successfully deleted: " . count($deletedFiles) . " files", $logHandle);
    logMessage("Failed deletions: " . count($failedDeletions) . " files", $logHandle);
    logMessage("NOTE: This bucket has 'Keep all versions' lifecycle enabled.", $logHandle);
    logMessage("Deleted files may still exist as previous versions.", $logHandle);
} else {
    logMessage("Test mode - would have deleted: " . count($backupsToDelete) . " backup folders", $logHandle);
    logMessage("Test mode - would have deleted: $totalFilesToDelete files", $logHandle);
}
logMessage("Script completed at: " . date('Y-m-d H:i:s'), $logHandle);

// Close log file
fclose($logHandle);

// Output final message
echo "\n";
echo "Cloudron backup cleanup completed.\n";
echo "Log file: $logFile\n";
if ($testMode) {
    echo "TEST MODE was enabled - no files were actually deleted.\n";
    echo "Set \$testMode = false to perform actual deletions.\n";
}