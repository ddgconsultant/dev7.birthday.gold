<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// using $sitesettings['ftp_to_cdn1']

if (!$system->is_production()) {

echo 'Not on a production system... exiting.'; exit;

}

$localDirectory = '/var/www/BIRTHDAY_SERVER/cdn.birthday.gold/public/useravatars';
$remoteDirectory = '/BIRTHDAY_SERVER/cdn.birthday.gold/public/useravatars'; // Adjust this to your remote directory structure
$server = $sitesettings['ftp_to_cdn1']['HOST'];
$username = $sitesettings['ftp_to_cdn1']['USER'];
$password = $sitesettings['ftp_to_cdn1']['PASS']; // Consider a more secure way to handle this
$logFile = '../admin_actions/LOG-transfer_to_cdn.log'; // Adjust the path to where you want to log transfers

// Function to scan the directory and return a list of files
function scanDirectory($directory) {
    $files = [];
    $iterator = new DirectoryIterator($directory);
    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isFile()) {
            $files[] = $fileinfo->getFilename();
        }
    }
    return $files;
}

// Function to transfer files via FTP
function ftpTransfer($server, $username, $password, $localFile, $remoteFile) {
    // Set up basic connection
    $connId = ftp_connect($server);

    if (!$connId) {
        // Handle error if connection fails
        throw new Exception("Could not connect to FTP server at $server.");
    }

    $loginResult = ftp_login($connId, $username, $password);

    if (!$loginResult) {
        // Close the FTP connection if login fails
        ftp_close($connId);
        throw new Exception("Could not log in to FTP server as $username.");
    }

    // Turn on passive mode
    if (!ftp_pasv($connId, true)) {
        ftp_close($connId);
        throw new Exception("Could not switch to passive mode.");
    }

    // Upload the file
    $upload = ftp_put($connId, $remoteFile, $localFile, FTP_BINARY);

    if (!$upload) {
        // Handle error if file upload fails
        ftp_close($connId);
        throw new Exception("Could not upload file $localFile to $remoteFile.");
    }

    // Close the FTP connection
    ftp_close($connId);

    return true;
}



// Function to log the transfer
function logTransfer($file, $logFile) {
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] Transferred $file\n", FILE_APPEND);
}

$processedFiles = file_exists($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

$files = scanDirectory($localDirectory);

foreach ($files as $file) {
    if (!in_array($file, $processedFiles)) {
        $localFile = $localDirectory . '/' . $file;
        $remoteFile = $remoteDirectory . '/' . $file;

        // Attempt to transfer the file via FTP
        if (ftpTransfer($server, $username, $password, $localFile, $remoteFile)) {
            // Log the successful transfer
            logTransfer($file, $logFile);
        }
    }
}
