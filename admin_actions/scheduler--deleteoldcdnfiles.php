<?php
$addClasses[] = 'fileuploader';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Backblaze S3 details
$bucketName = "birthdaygold202306-technical";


// Log file
$logFile =  $dir['configs'] ."/DB_BACKUPS/deleted_files.log";

// Current date
$currentDate = time();

// Threshold in seconds for 60 days
$threshold = 60 * 24 * 60 * 60;

// Arrays to hold file details
$allFiles = [];
$filesToDelete = [];
$deletedFiles = [];
$failedDeletions = [];

// Test mode flag
$testMode = true; // Set to true to enable test mode
#breakpoint($bucketName);
// Fetch the bucketId for the given bucketName
$buckets = $fileuploader->listBuckets($system);
if (!$buckets || !isset($buckets['buckets'])) {
    echo "Error fetching bucket list.\n";
    exit(1);
}
breakpoint($buckets);
$bucketId = null;
foreach ($buckets['buckets'] as $bucket) {
    if ($bucket['bucketName'] === $bucketName) {
        $bucketId = $bucket['bucketId'];
        break;
    }
}

if (!$bucketId) {
    echo "Bucket ID not found for bucket name: $bucketName\n";
    exit(1);
}


// List all files in the bucket
$fileList = $fileuploader->listFiles($system, $bucketName);
if (!$fileList) {
    echo "Error fetching file list from S3 bucket.\n";
    exit(1);
}


breakpoint($fileList);

// Process the file list
foreach ($fileList as $file) {
    $filePath = $file['fileName'];
    $fileDate = strtotime($file['uploadTimestamp']);
    $fileAge = $currentDate - $fileDate;

    // Add to all files list
    $allFiles[] = [
        'path' => $filePath,
        'date' => date('Y-m-d H:i:s', $fileDate),
        'age' => floor($fileAge / (24 * 60 * 60))
    ];

    // Check if the file should be deleted
    if (!preg_match('/_15/', $filePath) && $fileAge > $threshold) {
        $filesToDelete[] = $filePath;
    }
}

// Open log file for appending
$logHandle = fopen($logFile, 'a');
if (!$logHandle) {
    echo "Unable to open log file for writing.\n";
    exit(1);
}

// Log all files found
fwrite($logHandle, "\n==== All Files Found ====\n");
foreach ($allFiles as $file) {
    fwrite($logHandle, "Path: {$file['path']}, Date: {$file['date']}, Age: {$file['age']} days\n");
}

// Log files to be deleted
fwrite($logHandle, "\n==== Files Marked for Deletion ====\n");
foreach ($filesToDelete as $filePath) {
    fwrite($logHandle, "Path: $filePath\n");
}

// Delete files
foreach ($filesToDelete as $filePath) {
    if ($testMode) {
        // Simulate successful deletion in test mode
        $deletedFiles[] = $filePath;
        fwrite($logHandle, "Test mode: Simulated deletion of $filePath\n");
    } else {
        try {
            $cdn->delete_object($filePath);
            $deletedFiles[] = $filePath;
        } catch (Exception $e) {
            $failedDeletions[] = $filePath;
            fwrite($logHandle, "Error deleting file: $filePath - {$e->getMessage()}\n");
        }
    }
}

// Log successfully deleted files
fwrite($logHandle, "\n==== Successfully Deleted Files ====\n");
foreach ($deletedFiles as $filePath) {
    fwrite($logHandle, "Path: $filePath\n");
}

// Log failed deletions
fwrite($logHandle, "\n==== Failed Deletions ====\n");
foreach ($failedDeletions as $filePath) {
    fwrite($logHandle, "Path: $filePath\n");
}

// Close log file
fclose($logHandle);

echo "File cleanup process completed. Logs saved to $logFile.\n";
