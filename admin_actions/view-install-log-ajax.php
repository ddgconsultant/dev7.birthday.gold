<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
use phpseclib3\Net\SSH2;

header('Content-Type: application/json');

$host = $_GET['host'] ?? '';
$log_type = $_GET['type'] ?? 'web';

if (empty($host)) {
    echo json_encode(['error' => 'Host parameter required']);
    exit;
}

// Map log types to log file names
$log_files = [
    'web' => '~/installhistory_web_current.log',
    'mysql' => '~/installhistory_mysql_current.log',
    'mail' => '~/mail_server_setup_current.log',
    'haproxy' => '~/haproxy_add_webserver_current.log',
    'metabase' => '~/metabase_add_db_current.log',
    'uptime' => '~/uptime_kuma_add_node_current.log'
];

$state_files = [
    'web' => '~/install_state_web',
    'mysql' => '~/install_state_mysql',
    'mail' => '~/mail_server_setup_state',
    'haproxy' => '~/haproxy_add_state',
    'metabase' => '~/metabase_add_state_web',
    'uptime' => '~/uptime_kuma_add_state'
];

$log_file = $log_files[$log_type] ?? $log_files['web'];
$state_file = $state_files[$log_type] ?? $state_files['web'];

// Default credentials - in production you'd want to store these securely
// For now, we'll just try to connect with the standard password
$username = 'root';
$password = 'Hvm@7644Hvm@7644'; // This should come from AccessManager in production

try {
    $ssh = new SSH2($host);
    $ssh->setTimeout(10);

    if (!$ssh->login($username, $password)) {
        echo json_encode([
            'error' => 'SSH authentication failed',
            'log' => 'Unable to connect to ' . $host . ' - please check credentials'
        ]);
        exit;
    }

    // Get the log file content
    $log_content = $ssh->exec("cat $log_file 2>/dev/null || echo 'Log file not found'");

    // Get the current state
    $state = trim($ssh->exec("cat $state_file 2>/dev/null || echo 'pre'"));

    echo json_encode([
        'log' => $log_content,
        'state' => $state,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'log' => 'Error connecting to server: ' . $e->getMessage()
    ]);
}
