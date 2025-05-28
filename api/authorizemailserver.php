<?php
// Get the IP address from the query string
$ipAddress = $_GET['ip'] ?? '';

// Get the hostname of the requesting server
#$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
$hostname = $_GET['hostname'] ?? '';
// Validate the hostname
if (strpos($hostname, 'bday.gold') === false) {
    http_response_code(403);
    die('Unauthorized access - hostname: '.$hostname);
}

// Define the .htaccess file path
$htaccessFile = __DIR__ . '/mailserver/.htaccess';

// Sanitize the IP address
$ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP);

if ($ipAddress === false) {
    http_response_code(400);
    die('Invalid IP address: '.$ipAddress);
}

// Check if the IP address is already in the .htaccess file
$htaccessContent = file_get_contents($htaccessFile);
if (strpos($htaccessContent, "Allow from $ipAddress") !== false) {
    http_response_code(200);
    die('IP address already authorized: '.$ipAddress);
}

// Get the current date and time
$date = date('Y-m-d H:i:s');

// Append the IP address, hostname, and date to the .htaccess file
$htaccessEntry = "\n# Added by $hostname on $date\nAllow from $ipAddress\n";
file_put_contents($htaccessFile, $htaccessEntry, FILE_APPEND | LOCK_EX);



echo 'IP address authorized: '.$ipAddress;
echo "";
