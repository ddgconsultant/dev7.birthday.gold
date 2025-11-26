<?php
/**
 * Generate MySQL Replication Channel Setup Commands
 *
 * This script generates the SQL commands needed to set up replication channels
 * from a master server to all active slave servers in the mesh.
 *
 * Usage: php generate_replication_channels.php <master_hostname> [api_key]
 * Example: php generate_replication_channels.php december05 YOUR_API_KEY
 */

// Include database configuration
require_once('/mnt/w/BIRTHDAY_SERVER/ENV_CONFIGS/config-database.inc');

// Check command line arguments
if ($argc < 2) {
    echo "Usage: php generate_replication_channels.php <master_hostname> [api_key]\n";
    echo "Example: php generate_replication_channels.php december05 YOUR_API_KEY\n";
    echo "\nNote: API key is optional but required to fetch actual passwords from AccessManager\n";
    exit(1);
}

$master_hostname = $argv[1];
$api_key = $argv[2] ?? '';
$master_fqdn = strpos($master_hostname, '.') !== false ? $master_hostname : $master_hostname . '.bday.gold';

// Extract just the hostname part (e.g., "december05" from "december05.bday.gold")
$master_short = preg_replace('/\.bday\.gold$/', '', $master_fqdn);

// Fetch passwords from AccessManager if API key provided
$mysql_admin_pass = '';
$mysql_repl_pass = '';

if (!empty($api_key)) {
    // birthday_gold_admin password
    $mysql_admin_pass = @file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DVN3RN-OTMX3-Q7OSO-OQSNOS&apikey=' . $api_key . '&');
    // bgdbreplicator1 password
    $mysql_repl_pass = @file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCK9C-97J99-FKDKJ-9HHDFF&apikey=' . $api_key . '&');

    if ($mysql_admin_pass === false || empty($mysql_admin_pass)) {
        echo "WARNING: Failed to fetch birthday_gold_admin password from AccessManager\n";
        echo "         Check your API key and network connection\n\n";
        $mysql_admin_pass = '';
    }

    if ($mysql_repl_pass === false || empty($mysql_repl_pass)) {
        echo "WARNING: Failed to fetch bgdbreplicator1 password from AccessManager\n";
        echo "         Check your API key and network connection\n\n";
        $mysql_repl_pass = '';
    }
}

echo "========================================================================\n";
echo "Generating Replication Setup for Master: $master_fqdn\n";
echo "========================================================================\n\n";

// Connect to database
try {
    $pdo = new PDO($db['host'], $db['user'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Determine which month we're working with based on the master
$master_month = '';
if (preg_match('/^(july|december)/', $master_short, $matches)) {
    $master_month = $matches[1];
}

// Get active servers from database (same month as master OR july if master is december)
if ($master_month === 'december') {
    // For december masters, get other december servers AND july servers
    $sql = "SELECT DISTINCT
                SUBSTRING_INDEX(name, ' ', 1) as hostname
            FROM bg_system_availability
            WHERE status = 'A'
            AND (name LIKE 'july%' OR name LIKE 'december%')
            ORDER BY hostname";
} else {
    // For july masters, get only july servers
    $sql = "SELECT DISTINCT
                SUBSTRING_INDEX(name, ' ', 1) as hostname
            FROM bg_system_availability
            WHERE status = 'A'
            AND name LIKE 'july%'
            ORDER BY hostname";
}

$stmt = $pdo->query($sql);
$servers = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Clean up server names and remove duplicates
$cleaned_servers = [];
foreach ($servers as $server) {
    // Remove everything after the hostname (e.g., " / Production LAMP Stack")
    $server = preg_replace('/\s+\/.*$/', '', $server);
    // Add .bday.gold if not present
    if (strpos($server, '.') === false) {
        $server .= '.bday.gold';
    }
    $cleaned_servers[] = $server;
}

// Remove duplicates and filter out the master itself
$servers = array_unique($cleaned_servers);
$servers = array_filter($servers, function($server) use ($master_short) {
    // Normalize server name (remove .bday.gold if present)
    $server_short = preg_replace('/\.bday\.gold$/', '', $server);
    return $server_short !== $master_short;
});
sort($servers);

echo "Found " . count($servers) . " active slave servers:\n";
foreach ($servers as $server) {
    echo "  - $server\n";
}
echo "\n";

// Get master status from the new server
echo "========================================================================\n";
echo "STEP 1: Get Master Status from $master_fqdn\n";
echo "========================================================================\n\n";

$master_user = 'birthday_gold_admin';
$has_passwords = !empty($mysql_admin_pass) && !empty($mysql_repl_pass);

if (!$has_passwords) {
    echo "NOTE: No API key provided or failed to fetch passwords.\n";
    echo "      Commands will include password placeholders.\n";
    echo "      Rerun with: php generate_replication_channels.php $master_short YOUR_API_KEY\n\n";
} else {
    echo "✓ Successfully fetched passwords from AccessManager\n\n";
}

echo "Connect to master and get status:\n";
if ($has_passwords) {
    echo "MYSQL_PWD=\"$mysql_admin_pass\" mysql -u $master_user -h $master_fqdn -e \"SHOW MASTER STATUS\\G\"\n\n";
} else {
    echo "mysql -u $master_user -p -h $master_fqdn\n\n";
    echo "Then run:\n";
    echo "SHOW MASTER STATUS\\G\n\n";
}

echo "========================================================================\n";
echo "STEP 2: Create Replication Channels on Each Slave Server\n";
echo "========================================================================\n\n";

if (!$has_passwords) {
    echo "NOTE: No passwords available. Using placeholders.\n";
    echo "      Provide API key to get actual passwords.\n\n";
}

foreach ($servers as $server) {
    $server_fqdn = strpos($server, '.') !== false ? $server : $server . '.bday.gold';
    $server_short = preg_replace('/\.bday\.gold$/', '', $server_fqdn);

    $channel_name = "channelsource_{$master_short}_to_{$server_short}";

    echo "----------------------------------------\n";
    echo "Server: $server_fqdn\n";
    echo "Channel: $channel_name\n";
    echo "----------------------------------------\n\n";

    echo "# Connect to slave server:\n";
    echo "mysql -u root -p -h $server_fqdn\n\n";

    echo "# Or using SSH:\n";
    echo "ssh root@$server_fqdn\n";
    echo "mysql -u root\n\n";

    echo "# SQL Commands:\n";
    echo "-- Stop slave if running\n";
    echo "STOP SLAVE FOR CHANNEL '$channel_name';\n\n";

    echo "-- Configure the replication channel\n";
    echo "CHANGE MASTER TO\n";
    echo "  MASTER_HOST='$master_fqdn',\n";
    echo "  MASTER_USER='bgdbreplicator1',\n";

    if ($has_passwords) {
        echo "  MASTER_PASSWORD='$mysql_repl_pass',\n";
    } else {
        echo "  MASTER_PASSWORD='<REPLICATION_PASSWORD>',  -- Replace with actual password\n";
    }

    echo "  MASTER_PORT=3306,\n";
    echo "  MASTER_AUTO_POSITION=1,\n";
    echo "  MASTER_CONNECT_RETRY=60,\n";
    echo "  MASTER_RETRY_COUNT=86400\n";
    echo "FOR CHANNEL '$channel_name';\n\n";

    echo "-- Start the slave\n";
    echo "START SLAVE FOR CHANNEL '$channel_name';\n\n";

    echo "-- Check status\n";
    echo "SHOW SLAVE STATUS FOR CHANNEL '$channel_name'\\G\n\n";

    if ($has_passwords) {
        echo "# Execute remotely from any server with mysql client:\n";
        echo "mysql -u root -h $server_fqdn -e \"STOP SLAVE FOR CHANNEL '$channel_name'; ";
        echo "CHANGE MASTER TO MASTER_HOST='$master_fqdn', MASTER_USER='bgdbreplicator1', ";
        echo "MASTER_PASSWORD='$mysql_repl_pass', MASTER_PORT=3306, MASTER_AUTO_POSITION=1, ";
        echo "MASTER_CONNECT_RETRY=60, MASTER_RETRY_COUNT=86400 FOR CHANNEL '$channel_name'; ";
        echo "START SLAVE FOR CHANNEL '$channel_name';\"\n\n";
    }

    echo "\n";
}

echo "========================================================================\n";
echo "STEP 3: Verify All Channels\n";
echo "========================================================================\n\n";

echo "Run on each slave server to verify:\n";
echo "mysql -u root -p -e \"SHOW SLAVE STATUS\\G\" | grep -E '(Channel_Name|Slave_IO_Running|Slave_SQL_Running|Seconds_Behind_Master)'\n\n";

echo "========================================================================\n";
echo "STEP 4: Cleanup Commands (if needed)\n";
echo "========================================================================\n\n";

echo "If you need to remove/reset any channel, use:\n\n";
foreach ($servers as $server) {
    $server_fqdn = strpos($server, '.') !== false ? $server : $server . '.bday.gold';
    $server_short = preg_replace('/\.bday\.gold$/', '', $server_fqdn);
    $channel_name = "channelsource_{$master_short}_to_{$server_short}";

    echo "-- On $server_fqdn:\n";
    echo "STOP SLAVE FOR CHANNEL '$channel_name';\n";
    echo "RESET SLAVE ALL FOR CHANNEL '$channel_name';\n\n";
}

echo "\n========================================================================\n";
echo "Summary: Setting up replication from $master_fqdn to " . count($servers) . " slave servers\n";
echo "========================================================================\n";
