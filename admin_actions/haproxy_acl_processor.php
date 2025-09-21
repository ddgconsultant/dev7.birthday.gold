<?php
$nosessiontracking = true;
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

// DEBUG flag
$DEBUG = true;

// Whitelist and blacklist arrays
$whitelist_urls = [
    // Add non-malicious URL patterns here
    "/images/",
    "/css/",
    // etc.
];

$blacklist_urls = [
    // Add malicious URL patterns here
    ".git/objects/",
    // etc.
];

// Path to the blocked IPs list
$dir['companyserverbase'] = '/var/www/BIRTHDAY_SERVER';
$blockedIpsFile = $dir['companyserverbase'] . '/blocked_ips.lst';

if ($DEBUG) echo "<pre>Blocked IPs file: $blockedIpsFile<br>";

try {
    // Get current time and time 10 minutes ago
    $now = new DateTime('now');
    $tenMinutesAgo = clone $now;
    $tenMinutesAgo->modify('-300 minutes');

    // Convert to MySQL datetime format
    $nowFormatted = $now->format('Y-m-d H:i:s');
    $tenMinutesAgoFormatted = $tenMinutesAgo->format('Y-m-d H:i:s');

    if ($DEBUG) {
        echo "Current time: $nowFormatted<br>";
        echo "Time ago: $tenMinutesAgoFormatted<br>";
    }

    // Query to find IP addresses that show up more than 3 times in the last 10 minutes
    $query = "SELECT cip, COUNT(*) as count 
              FROM bg_errors 
              WHERE create_dt BETWEEN :tenMinutesAgo AND :now
              GROUP BY cip 
              HAVING count > 3";

if ($DEBUG) {
    echo "Current time: $nowFormatted<br>";
    echo "Time ago: $tenMinutesAgoFormatted<br>";
    echo "Query: $query<br>";
}
    $stmt = $database->prepare($query);
    $stmt->execute([':tenMinutesAgo' => $tenMinutesAgoFormatted, ':now' => $nowFormatted]);

    $ipsToBlock = [];
    $recordsFound = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recordsFound++;
        $cip = $row['cip'];
        if ($DEBUG) echo "IP to check: $cip<br>";

        // Query to get the location data for the IP
        $locationQuery = "SELECT location FROM bg_errors WHERE cip = :cip";
        $locationStmt = $database->prepare($locationQuery);
        $locationStmt->execute([':cip' => $cip]);

        $isBlacklisted = false;
        $isWhitelisted = false;

        while ($locationRow = $locationStmt->fetch(PDO::FETCH_ASSOC)) {
            $locationData = json_decode($locationRow['location'], true);
            $uri = $locationData['entrypoint_first']['uri'];
            $uriStripped = $locationData['entrypoint_first']['uri_stripped'];

            if ($DEBUG) {
                echo "Checking URI: $uri<br>";
                echo "Checking stripped URI: $uriStripped<br>";
            }

            // Check if the URI or stripped URI matches any blacklist patterns
            foreach ($blacklist_urls as $blacklist) {
                $isBlacklisted = true;
                if (strpos($uri, $blacklist) !== false || strpos($uriStripped, $blacklist) !== false) {
                    $isBlacklisted = true;
                    if ($DEBUG) echo "Blacklisted URI: $uri<br>";
                    break;
                }
            }

            // Check if the URI or stripped URI matches any whitelist patterns
            foreach ($whitelist_urls as $whitelist) {
                if (strpos($uri, $whitelist) !== false || strpos($uriStripped, $whitelist) !== false) {
                    $isWhitelisted = true;
                    if ($DEBUG) echo "Whitelisted URI: $uri<br>";
                    break;
                }
            }

            if ($isBlacklisted && !$isWhitelisted) {
                $ipsToBlock[] = $cip;
                if ($DEBUG) echo "IP to block: $cip<br>";
                break;
            }
        }
    }

    if ($DEBUG) echo "Number of records found: $recordsFound<br>";

    // Read current blocked IPs
    $blockedIps = file_exists($blockedIpsFile) ? file($blockedIpsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    if ($DEBUG) echo "Current blocked IPs: " . implode(", ", $blockedIps) . "<br>";

    // Add new IPs to the blocked list
    foreach ($ipsToBlock as $ip) {
        if (!in_array($ip, $blockedIps)) {
            $blockedIps[] = $ip;
            if ($DEBUG) echo "Added IP to block list: $ip<br>";
        }
    }

    // Write the updated blocked IPs back to the file
    file_put_contents($blockedIpsFile, implode(PHP_EOL, $blockedIps) . PHP_EOL);

    if ($DEBUG) echo "Blocked IPs updated successfully.<br></pre>";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
