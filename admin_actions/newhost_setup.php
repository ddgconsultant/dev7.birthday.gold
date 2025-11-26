<?php
/**
 * New Host Setup - Server Deployment Tool
 *
 * MODE CONFIGURATION:
 * - Line ~262: $display_mode flag controls behavior
 * - TRUE  = Display commands for manual execution (safer, recommended)
 * - FALSE = Execute commands automatically via SSH (original behavior)
 */
$addClasses[] = 'api';
$addClasses[] = 'powerdns';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
require_once($dir['vendor'] . '/autoload.php');

use phpseclib3\Net\SSH2;

// Load systems early - needed for form processing and hostname extraction
try {
    $query = "SELECT id, name, url, create_dt, modify_dt FROM bg_system_availability where `status`='A' order by create_dt DESC";
    $stmt = $database->prepare($query);
    $stmt->execute();
    $systems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $systems = [];
    error_log("Error loading systems: " . $e->getMessage());
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# HANDLE DELETING A HOST
#-------------------------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'delete_host') {
    $host_id = intval($_POST['host_id']);

    try {
        $query = "DELETE FROM bg_system_availability WHERE id = :id";
        $stmt = $database->prepare($query);
        $stmt->execute([':id' => $host_id]);
        $alert_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Host deleted successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } catch (PDOException $e) {
        $alert_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Error deleting host: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# HANDLE ADDING A NEW HOST
#-------------------------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'add_host') {
    $host_name = trim($_POST['host_name']);
    $host_url = trim($_POST['host_url']);
    $host_port = !empty($_POST['host_port']) ? intval($_POST['host_port']) : 443;
    $host_type = !empty($_POST['host_type']) ? trim($_POST['host_type']) : 'web';
    $host_description = !empty($_POST['host_description']) ? trim($_POST['host_description']) : '';
    $host_ip = !empty($_POST['host_ip']) ? trim($_POST['host_ip']) : '';
    $dns_zone = !empty($_POST['dns_zone']) ? trim($_POST['dns_zone']) : 'bday.gold';
    $create_dns = isset($_POST['create_dns']) && $_POST['create_dns'] == '1';

    if (!empty($host_name) && !empty($host_url)) {
        // Check if host already exists
        try {
            $check_query = "SELECT COUNT(*) as count FROM bg_system_availability WHERE url = :url";
            $check_stmt = $database->prepare($check_query);
            $check_stmt->execute([':url' => $host_url]);
            $exists = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($exists['count'] > 0) {
                $alert_message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Host with URL "' . htmlspecialchars($host_url) . '" already exists. Please use a different URL or delete the existing entry first.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $dns_messages = [];

                // Create DNS records if requested and IP provided
                if ($create_dns && !empty($host_ip)) {
                    if (!isset($powerdns)) {
                        $dns_messages[] = '<span class="text-danger">✗ PowerDNS class not available. Check configuration.</span>';
                    } else {
                        // Extract hostname without domain (e.g., "july05" from "july05.bday.gold")
                        $hostname_only = explode('.', $host_url)[0];

                        // Create A record for bday.gold (internal domain)
                        $bday_hostname = $hostname_only . '.bday.gold';
                        $result = $powerdns->createARecord('bday.gold', $bday_hostname, $host_ip);
                        if ($result['success']) {
                            $dns_messages[] = '<span class="text-success">✓ Created A record: ' . htmlspecialchars($bday_hostname) . ' → ' . htmlspecialchars($host_ip) . '</span>';
                        } else {
                            $error_msg = $result['error'] . ' (HTTP ' . $result['http_code'] . ')';
                            $dns_messages[] = '<span class="text-danger">✗ Failed A record (bday.gold): ' . htmlspecialchars($error_msg) . '</span>';
                        }

                        // Create A record for birthday.gold (public domain)
                        $birthday_hostname = $hostname_only . '.birthday.gold';
                        $result = $powerdns->createARecord('birthday.gold', $birthday_hostname, $host_ip);
                        if ($result['success']) {
                            $dns_messages[] = '<span class="text-success">✓ Created A record: ' . htmlspecialchars($birthday_hostname) . ' → ' . htmlspecialchars($host_ip) . '</span>';
                        } else {
                            $error_msg = $result['error'] . ' (HTTP ' . $result['http_code'] . ')';
                            $dns_messages[] = '<span class="text-danger">✗ Failed A record (birthday.gold): ' . htmlspecialchars($error_msg) . '</span>';
                        }

                        // Create MySQL replication DNS records (december pattern)
                        // Convert month name: july->december, march->october, etc.
                        $month_map = [
                            'january' => 'october', 'february' => 'november', 'march' => 'december',
                            'april' => 'january', 'may' => 'february', 'june' => 'march',
                            'july' => 'december', 'august' => 'january', 'september' => 'february',
                            'october' => 'march', 'november' => 'april', 'december' => 'may'
                        ];

                        preg_match('/^([a-z]+)(\d+)$/', $hostname_only, $matches);
                        if (count($matches) == 3) {
                            $month = strtolower($matches[1]);
                            $day = $matches[2];

                            if (isset($month_map[$month])) {
                                $mysql_hostname = $month_map[$month] . $day;

                                // Create december**.bday.gold
                                $mysql_bday = $mysql_hostname . '.bday.gold';
                                $result = $powerdns->createARecord('bday.gold', $mysql_bday, $host_ip);
                                if ($result['success']) {
                                    $dns_messages[] = '<span class="text-success">✓ Created MySQL A record: ' . htmlspecialchars($mysql_bday) . ' → ' . htmlspecialchars($host_ip) . '</span>';
                                }

                                // Create december**.birthday.gold
                                $mysql_birthday = $mysql_hostname . '.birthday.gold';
                                $result = $powerdns->createARecord('birthday.gold', $mysql_birthday, $host_ip);
                                if ($result['success']) {
                                    $dns_messages[] = '<span class="text-success">✓ Created MySQL A record: ' . htmlspecialchars($mysql_birthday) . ' → ' . htmlspecialchars($host_ip) . '</span>';
                                }
                            }
                        }
                    }
                }

                try {
                    // Build name: "hostname / Type" (e.g., "july05.bday.gold / Production LAMP Stack")
                    $type_labels = [
                        'web' => 'Production LAMP Stack',
                        'db' => 'Production MySQL DB',
                        'mail' => 'Production Mail Server',
                        'queue' => 'Production Email Queue',
                        'haproxy' => 'Production HAProxy Server',
                        'other' => 'Production Server'
                    ];
                    $type_label = $type_labels[$host_type] ?? 'Production Server';
                    $full_name = $host_url . ' / ' . $type_label;

                    // Build description with specs
                    $full_description = "=== " . $type_label . "\n\n";
                    if (!empty($host_description)) {
                        $full_description .= $host_description . "\n";
                    }
                    $full_description .= "Ubuntu 24.04 LTS\n+ Apache 2.4\n+ PHP 8.3\n+ MySQL 8 (ID: ###)";

                    $query = "INSERT INTO bg_system_availability (system_id, name, description, url, port, system_status, status, create_dt, modify_dt, last_success_dt, last_failure_dt)
                              VALUES (180, :name, :description, :url, :port, 'unknown', 'A', NOW(), NOW(), NOW(), NOW())";
                    $stmt = $database->prepare($query);
                    $stmt->execute([
                        ':name' => $full_name,
                        ':description' => $full_description,
                        ':url' => $host_ip,
                        ':port' => $host_port
                    ]);

                    $success_msg = 'Host "' . htmlspecialchars($host_name) . '" added successfully!';
                    if (!empty($dns_messages)) {
                        $success_msg .= '<br><strong>DNS Records:</strong><br>' . implode('<br>', $dns_messages);
                    }
                    $alert_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $success_msg . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                } catch (PDOException $e) {
                    $alert_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Error adding host: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
            }
        } catch (PDOException $e) {
            // Error checking for duplicates
            $alert_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Database error: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } else {
        $alert_message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Please provide both host name and URL.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# HANDLE THE FORM POSTING ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted() && (!isset($_POST['action']) || ($_POST['action'] != 'add_host' && $_POST['action'] != 'delete_host'))) {

    $host = $_POST['host'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $api_key = $_POST['api_key'] ?? '';

    // Only proceed if we have the required SSH parameters
    if (empty($host) || empty($username) || empty($password) || empty($api_key)) {
        $alert_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Missing required SSH connection parameters.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $auth_response = $api->authenticate_api_key($api_key);

        if ($auth_response['success']) {
        function reconnect($host, $username, $password)
        {
            try {
                $ssh = new SSH2($host);
                $ssh->setTimeout(120); // Set timeout before login
                if (!$ssh->login($username, $password)) {
                    echo "<div class='mt-4 alert alert-danger'>Login Failed</div>";
                    return false;
                }
                return $ssh;
            } catch (\Exception $e) {
                echo "<div class='mt-4 alert alert-danger'>SSH Connection Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                error_log("SSH connection error to $host: " . $e->getMessage());
                return false;
            }
        }

        $ssh = reconnect($host, $username, $password);
        if ($ssh) {
            // Turn off ALL output buffering for real-time streaming
            while (ob_get_level()) {
                ob_end_clean();
            }
            ini_set('output_buffering', 'Off');
            ini_set('zlib.output_compression', 'Off');
            ini_set('implicit_flush', 'On');
            ob_implicit_flush(true);

            // Output minimal HTML header immediately
            echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSH Command Execution - Birthday.Gold</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; padding: 20px; padding-bottom: 100px; }
        .command-output { background: #000; color: #0f0; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; white-space: pre-wrap; }
        .command-header { background: #343a40; color: #fff; padding: 10px; border-radius: 5px; margin-top: 20px; }
        .timestamp { color: #6c757d; font-size: 0.9em; }
        #output-container { margin-bottom: 50px; }
        #scroll-control {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        #scroll-control button {
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .btn-stop { background: #dc3545; color: white; }
        .btn-start { background: #28a745; color: white; }
    </style>
    <script>
        let autoScrollEnabled = true;

        function scrollToBottom() {
            if (autoScrollEnabled) {
                window.scrollTo(0, document.body.scrollHeight);
            }
        }

        function toggleAutoScroll() {
            autoScrollEnabled = !autoScrollEnabled;
            const btn = document.getElementById(\'scroll-toggle-btn\');
            if (autoScrollEnabled) {
                btn.textContent = \'⏸ Stop Auto-Scroll\';
                btn.className = \'btn-stop\';
                scrollToBottom();
            } else {
                btn.textContent = \'▶ Resume Auto-Scroll\';
                btn.className = \'btn-start\';
            }
        }

        // Auto-scroll on page load and when new content appears
        window.onload = scrollToBottom;
        setInterval(scrollToBottom, 500);
    </script>
</head>
<body>
    <div id="scroll-control">
        <button id="scroll-toggle-btn" class="btn-stop" onclick="toggleAutoScroll()">⏸ Stop Auto-Scroll</button>
    </div>
    <div class="container" id="output-container">
        <h1 class="mb-4"><i class="bi bi-terminal"></i> SSH Command Execution</h1>
        <div class="alert alert-info">
            <strong>Executing commands on:</strong> ' . htmlspecialchars($host) . ' <br>
            <strong>Username:</strong> ' . htmlspecialchars($username) . '
        </div>
';
            flush();

            $doactions = $_REQUEST['serveraction'];

            // MODE SWITCH: Set to true to display commands, false to execute them
            $display_mode = true; // Change to false to execute commands directly

            if ($display_mode) {
                // Display mode: show commands for manual execution
                echo '<div class="alert alert-info mt-4">';
                echo '<h4><i class="bi bi-terminal"></i> Commands to Execute</h4>';
                echo '<p>Copy and paste these commands into your SSH session with <strong>' . htmlspecialchars($host) . '</strong></p>';
                echo '</div>';

                // Password Reference Table - Dynamically retrieve passwords
                $password_credentials = [
                    ['id' => 'DZCKHC-KJGBK-9DBGB-JBBJ97', 'label' => 'FTP - webinstall', 'var' => 'MYSUPERSECUREPASSWORD', 'usage' => 'env var, rdavis user, FTP auth', 'scripts' => 'install_webserver_full, install_emailqueue'],
                    ['id' => 'DVN3RN-OTMX3-Q7OSO-OQSNOS', 'label' => 'birthday_gold_admin', 'var' => 'MYSQL_ADMIN_PASSWORD', 'usage' => 'MySQL admin', 'scripts' => 'install_webserver_full, install_mysqldb, install_emailqueue'],
                    ['id' => 'DZCK9C-97J99-FKDKJ-9HHDFF', 'label' => 'bgdbreplicator1', 'var' => 'MYSQL_REPL_PASSWORD', 'usage' => 'MySQL replication', 'scripts' => 'install_webserver_full, install_mysqldb, install_emailqueue'],
                    ['id' => 'DYBJFB-ACB6A-6KFBB-AEAHBE', 'label' => 'KVM8-web-root LEGACY', 'var' => 'LEGACY_ROOT_PASSWORD', 'usage' => 'SSH root password', 'scripts' => 'install_webserver_full, install_mysqldb'],
                    ['id' => 'DJLTPL-RKLNL-TNTSM-LTLNTR', 'label' => 'birthday_gold_admin', 'var' => 'adminpass', 'usage' => 'Admin for emailqueue', 'scripts' => 'install_emailqueue_docker, install_emailqueue'],
                    ['id' => 'DTOWO8-UOONO-NTWNR-TTRT8T', 'label' => 'postmasterpass', 'var' => 'postmasterpass', 'usage' => 'Postmaster for emailqueue', 'scripts' => 'install_emailqueue_docker, install_emailqueue'],
                ];

                echo '<div class="card mb-3">';
                echo '<div class="card-header bg-primary text-white">';
                echo '<h5 class="mb-0"><i class="bi bi-key-fill"></i> Password Reference - Access Manager Credentials</h5>';
                echo '</div>';
                echo '<div class="card-body p-0">';
                echo '<div class="table-responsive">';
                echo '<table class="table table-sm table-striped mb-0">';
                echo '<thead class="table-dark">';
                echo '<tr>';
                echo '<th>Label</th>';
                echo '<th>Variable Name</th>';
                echo '<th>Password Value</th>';
                echo '<th>Usage</th>';
                echo '<th>Used In Scripts</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                foreach ($password_credentials as $cred) {
                    $password = @file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=' . $cred['id'] . '&apikey=' . $api_key . '&');
                    if ($password === false || empty($password)) {
                        $password_display = '<span class="text-danger">Failed to retrieve</span>';
                    } else {
                        $password_id = 'pwd_' . md5($cred['id']);
                        $password_display = '<code class="text-success" style="cursor: pointer; user-select: all;" onclick="copyPassword(\'' . $password_id . '\')" title="Click to copy">' . htmlspecialchars($password) . ' <i class="bi bi-clipboard"></i></code>';
                        $password_display .= '<input type="hidden" id="' . $password_id . '" value="' . htmlspecialchars($password) . '">';
                    }

                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($cred['label']) . '</strong><br><small class="text-muted">' . htmlspecialchars($cred['id']) . '</small></td>';
                    echo '<td><code>' . htmlspecialchars($cred['var']) . '</code></td>';
                    echo '<td>' . $password_display . '</td>';
                    echo '<td>' . htmlspecialchars($cred['usage']) . '</td>';
                    echo '<td><small>' . htmlspecialchars($cred['scripts']) . '</small></td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                echo '</div>';
                echo '</div>';

                // Disable auto-scroll by default in display mode
                echo '<script>autoScrollEnabled = false; document.getElementById("scroll-toggle-btn").textContent = "▶ Resume Auto-Scroll"; document.getElementById("scroll-toggle-btn").className = "btn-start";</script>';
            }

            $listofcommands = [];

            foreach ($doactions as $action) {
                ///==========================================================================
                if ($action == 'resume_webserver') {
                    $listofcommands[] = 'echo "Resuming webserver installation from saved state..."';
                    $listofcommands[] = 'cat ~/install_state_web 2>/dev/null || echo "No state file found - will start from beginning"';
                    $listofcommands[] = 'tail -20 ~/installhistory_web_*.log 2>/dev/null || echo "No log file found"';
                    // Create .my.cnf for password-less MySQL access
                    $listofcommands[] = 'printf "[client]\\nuser=root\\n\\n[mysql]\\nuser=root\\n" > $HOME/.my.cnf && chmod 600 $HOME/.my.cnf';
                    $listofcommands[] = 'echo "Created .my.cnf for MySQL access"';
                    $listofcommands[] = 'source ~/.profile';
                    $listofcommands[] = 'export AUTO_CONTINUE=1';
                    // AUTO_RESUME defaults to false (manual mode) in install_webserver.sh
                    // Set AUTO_RESUME=true before calling script if you want automatic systemd service resume
                    // Manual mode (default) writes instructions to .profile for better visibility
                    $listofcommands[] = './install_webserver.sh';
                    $listofcommands[] = 'echo "=== Installation phase complete ==="';
                    $listofcommands[] = 'tail -30 ~/installhistory_web_*.log';
                }
                ///==========================================================================
                if ($action == 'deploy_www') {
                    $listofcommands[] = 'ls -ltr';
                    $listofcommands[] = 'date';
                    $listofcommands[] = 'cd';
                    $listofcommands[] = './deploy_www.sh';
                }
                ///==========================================================================
                if ($action == 'install_webserver_full') {
                    // Only show this message in execution mode
                    if (!$display_mode) {
                        echo '<div class="alert alert-info"><h5>Full Web Server Installation Process Started</h5></div>';
                    }

                    // Create .passwordfile with FTP credentials (escape special chars)
                    $pass = @file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCKHC-KJGBK-9DBGB-JBBJ97&apikey=' . $api_key . '&');   // FTP - webinstall

                    // Validate password was retrieved
                    if ($pass === false || empty($pass) || strlen($pass) < 4) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Failed to retrieve FTP password from AccessManager. Password length: ' . strlen($pass) . ' bytes. Please check API key and credential ID.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        error_log("AccessManager password retrieval failed for FTP - webinstall. Length: " . strlen($pass) . ", Content: " . substr($pass, 0, 100));
                        exit;
                    }

                    // Use base64 encoding to safely pass password with special characters
                    $pass_b64 = base64_encode($pass);
                    $listofcommands[] = 'echo "export MYSUPERSECUREPASSWORD=\$(echo ' . $pass_b64 . ' | base64 -d)" > $HOME/.profile';
                    $listofcommands[] = 'source $HOME/.profile';

                    // Set hostname by parsing it from the selected system's name
                    // Load systems data to find the selected host
                    $query_systems = "SELECT id, name, url FROM bg_system_availability WHERE status='A' AND url = :host";
                    $stmt_systems = $database->prepare($query_systems);
                    $stmt_systems->execute([':host' => $host]);
                    $selected_system = $stmt_systems->fetch(PDO::FETCH_ASSOC);

                    if ($selected_system && !empty($selected_system['name'])) {
                        // Parse: "july05.bday.gold / Production LAMP Stack" -> "july05.bday.gold"
                        $hostname_full = explode(' / ', $selected_system['name'])[0];
                        // Use FQDN as hostname
                        $listofcommands[] = 'hostnamectl set-hostname ' . escapeshellarg($hostname_full);
                        $listofcommands[] = 'echo "Hostname set to: ' . $hostname_full . '"';
                    } else {
                        $listofcommands[] = 'echo "Warning: Could not determine hostname from selection, keeping default"';
                    }

                    // INSTALL WEB NODE
                    $listofcommands[] = '[ -f ~/install_webserver.sh ] && rm ~/install_webserver.sh';
                    $listofcommands[] = '[ -f ~/install_state ] && rm ~/install_state';
                    $listofcommands[] = '[ -f ~/install_state_web ] && rm ~/install_state_web';
                    $listofcommands[] = 'wget --no-cache https://dev.birthday.gold/admin_actions/install_webserver.sh';
                    $listofcommands[] = 'dos2unix install_webserver.sh 2>/dev/null || sed -i "s/\r$//" install_webserver.sh';
                    $listofcommands[] = 'chmod 700 install_webserver.sh';
                    $listofcommands[] = 'echo "Starting webserver installation (may take several minutes and cause reboots)..."';
                    $listofcommands[] = 'export AUTO_CONTINUE=1';
                    // AUTO_RESUME defaults to false (manual mode) in install_webserver.sh
                    // Set AUTO_RESUME=true before calling script if you want automatic systemd service resume
                    // Manual mode (default) writes instructions to .profile for better visibility
                    $listofcommands[] = './install_webserver.sh';
                    // INSTALL MYSQL DB
                    // Set MySQL passwords and SSH password in environment before running install
                    $mysql_admin_pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DVN3RN-OTMX3-Q7OSO-OQSNOS&apikey=' . $api_key . '&');   // birthday_gold_admin
                    $mysql_repl_pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCK9C-97J99-FKDKJ-9HHDFF&apikey=' . $api_key . '&');   // bgdbreplicator1
                    $legacy_root_pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DYBJFB-ACB6A-6KFBB-AEAHBE&apikey=' . $api_key . '&');   // KVM8-web-root LEGACY

                    $listofcommands[] = 'apt-get install -y sshpass';
                    // Set passwords as inline exports (will be available to the script)
                    $listofcommands[] = 'export MYSQL_ADMIN_PASSWORD=' . escapeshellarg($mysql_admin_pass);
                    $listofcommands[] = 'export MYSQL_REPL_PASSWORD=' . escapeshellarg($mysql_repl_pass);
                    $listofcommands[] = 'export LEGACY_ROOT_PASSWORD=' . escapeshellarg($legacy_root_pass);
                    $listofcommands[] = 'echo "Passwords set in environment"';

                    $listofcommands[] = '[ -f ~/install_mysqldb.sh ] && rm ~/install_mysqldb.sh';
                    $listofcommands[] = '[ -f ~/install_state_mysql ] && rm ~/install_state_mysql';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_mysqldb.sh';
                    $listofcommands[] = 'dos2unix install_mysqldb.sh 2>/dev/null || sed -i "s/\r$//" install_mysqldb.sh';
                    $listofcommands[] = 'chmod 700 install_mysqldb.sh';
                    $listofcommands[] = './install_mysqldb.sh';
                    // INSTALL HAPROXY
                    $listofcommands[] = '[ -f ~/install_haproxynode.sh ] && rm ~/install_haproxynode.sh';
                    $listofcommands[] = '[ -f ~/haproxy_add_state ] && rm ~/haproxy_add_state';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_haproxynode.sh';
                    $listofcommands[] = 'dos2unix install_haproxynode.sh 2>/dev/null || sed -i "s/\r$//" install_haproxynode.sh';
                    $listofcommands[] = 'chmod 700 install_haproxynode.sh';
                    $listofcommands[] = './install_haproxynode.sh';
                    // Add to Metabase
                    $listofcommands[] = '[ -f ~/install_addtometabase_web.sh ] && rm ~/install_addtometabase_web.sh';
                    $listofcommands[] = '[ -f ~/metabase_add_state_web ] && rm ~/metabase_add_state_web';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_addtometabase_web.sh';
                    $listofcommands[] = 'dos2unix install_addtometabase_web.sh 2>/dev/null || sed -i "s/\r$//" install_addtometabase_web.sh';
                    $listofcommands[] = 'chmod 700 install_addtometabase_web.sh';
                    $listofcommands[] = './install_addtometabase_web.sh';
                    // Add node to Uptime Kuma monitoring
                    $listofcommands[] = '[ -f ~/install_uptime_monitors_web.sh ] && rm ~/install_uptime_monitors_web.sh';
                    $listofcommands[] = '[ -f ~/uptime_kuma_add_state ] && rm ~/uptime_kuma_add_state';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_uptime_monitors_web.sh';
                    $listofcommands[] = 'dos2unix install_uptime_monitors_web.sh 2>/dev/null || sed -i "s/\r$//" install_uptime_monitors_web.sh';
                    $listofcommands[] = 'chmod 700 install_uptime_monitors_web.sh';
                    $listofcommands[] = './install_uptime_monitors_web.sh';
                    // Deploy WWW
                    $listofcommands[] = '[ -f ~/deploy_www.sh ] && rm ~/deploy_www.sh';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/deploy_www.sh';
                    $listofcommands[] = 'dos2unix deploy_www.sh 2>/dev/null || sed -i "s/\r$//" deploy_www.sh';
                    $listofcommands[] = 'chmod 700 deploy_www.sh';
                    $listofcommands[] = './deploy_www.sh';
                }
                ///==========================================================================
                if ($action == 'install_webserver') {
                    // INSTALL WEB NODE ONLY (no MySQL, no HAProxy, no monitoring)
                    $listofcommands[] = '[ -f ~/install_webserver.sh ] && rm ~/install_webserver.sh';
                    $listofcommands[] = '[ -f ~/install_state ] && rm ~/install_state';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_webserver.sh';
                    $listofcommands[] = 'dos2unix install_webserver.sh 2>/dev/null || sed -i "s/\r$//" install_webserver.sh';
                    $listofcommands[] = 'chmod 700 install_webserver.sh';
                    // Execute this install script three times
                    for ($i = 0; $i < 3; $i++) {
                        $listofcommands[] = './install_webserver.sh';
                        $listofcommands[] = function () use ($host, $username, $password) {
                            sleep(120); // Wait for 2 minutes for the server to reboot
                            return reconnect($host, $username, $password);
                        };
                    }
                }
                ///==========================================================================
                if ($action == 'install_mysqldb') {
                    // INSTALL MYSQL DB
                    // Get MySQL passwords from AccessManager
                    $admin_pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DVN3RN-OTMX3-Q7OSO-OQSNOS&apikey=' . $api_key . '&');   // birthday_gold_admin
                    $repl_pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCK9C-97J99-FKDKJ-9HHDFF&apikey=' . $api_key . '&');   // bgdbreplicator1
                    $legacy_root_pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DYBJFB-ACB6A-6KFBB-AEAHBE&apikey=' . $api_key . '&');   // KVM8-web-root LEGACY

                    // Install sshpass for SSH authentication
                    $listofcommands[] = 'apt-get install -y sshpass';

                    // Export passwords as environment variables for the install script
                    $listofcommands[] = 'export MYSQL_ADMIN_PASSWORD=' . escapeshellarg($admin_pass);
                    $listofcommands[] = 'export MYSQL_REPL_PASSWORD=' . escapeshellarg($repl_pass);
                    $listofcommands[] = 'export LEGACY_ROOT_PASSWORD=' . escapeshellarg($legacy_root_pass);
                    $listofcommands[] = 'echo "Passwords set in environment"';

                    $listofcommands[] = '[ -f ~/install_mysqldb.sh ] && rm ~/install_mysqldb.sh';
                    $listofcommands[] = '[ -f ~/install_state_mysql ] && rm ~/install_state_mysql';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_mysqldb.sh';
                    $listofcommands[] = 'dos2unix install_mysqldb.sh 2>/dev/null || sed -i "s/\r$//" install_mysqldb.sh';
                    $listofcommands[] = 'chmod 700 install_mysqldb.sh';
                    $listofcommands[] = './install_mysqldb.sh';
                }
                ///==========================================================================
                if ($action == 'install_mailserver') {
                    // INSTALL MAILSERVER
                    $listofcommands[] = '[ -f ~/install_mailserver.sh ] && rm ~/install_mailserver.sh';
                    $listofcommands[] = '[ -f ~/install_state_mail ] && rm ~/install_state_mail';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_mailserver.sh';
                    $listofcommands[] = 'dos2unix install_mailserver.sh 2>/dev/null || sed -i "s/\r$//" install_mailserver.sh';
                    $listofcommands[] = 'chmod 700 install_mailserver.sh';
                    $listofcommands[] = './install_mailserver.sh';
                    // Additional steps or messages can be added here if needed
                }
                ///==========================================================================
                if ($action == 'install_emailqueue_docker') {
                    // INSTALL EMAIL QUEUE
                    $listofcommands[] = 'apt-get update';
                    $listofcommands[] = 'apt-get -y install make';
                    $listofcommands[] = '[ -f ~/bg-emailqueue-docker/makefile ] && cd ~ && rm -rf ~/bg-emailqueue-docker';

                    $listofcommands[] = 'docker ps -q | xargs -r docker stop | xargs -r docker rm';

                    $listofcommands[] = 'git clone https://github.com/ddgconsultant/bg-emailqueue-docker';
                    $listofcommands[] = 'sleep 30'; // Sleep for 80 seconds to ensure the git clone command completes

                    $listofcommands[] = 'echo "apikey=\'' . $api_key . '\'" > $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DJLTPL-RKLNL-TNTSM-LTLNTR&apikey=' . $api_key . '&');   // birthday_gold_admin 
                    $listofcommands[] = 'echo "adminpass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DTOWO8-UOONO-NTWNR-TTRT8T&apikey=' . $api_key . '&');   // postmasterpass 
                    $listofcommands[] = 'echo "postmasterpass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCKHC-KJGBK-9DBGB-JBBJ97&apikey=' . $api_key . '&');   // FTP - webinstall 
                    $listofcommands[] = 'echo "ftppass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $listofcommands[] = 'make -C ~/bg-emailqueue-docker config';
                    $listofcommands[] = 'sleep 80'; // Sleep for 80 seconds to ensure the git clone command completes
                    $listofcommands[] = 'make -C ~/bg-emailqueue-docker up';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCK9C-97J99-FKDKJ-9HHDFF&apikey=' . $api_key . '&');   // bgdbreplicator1                   
                    $listofcommands[] = 'echo "ALTER USER \'bgdbreplicator1\'@\'%\' IDENTIFIED BY \'' . $pass . '\';"';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DVN3RN-OTMX3-Q7OSO-OQSNOS&apikey=' . $api_key . '&');   // birthday_gold_admin                   
                    $listofcommands[] = 'echo "ALTER USER \'birthday_gold_admin\'@\'%\' IDENTIFIED BY \'' . $pass . '\';"';

                    // Additional steps or messages can be added here if needed
                }

                ///==========================================================================
                if ($action == 'ftp_production_config_to_webservers') {
                 $listofcommands[] = 'ftp -inv dev.birthday.gold <<EOF user richard Hvm!7644; get "/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc" "/var/www/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc";bye;EOF;';
                }

                ///==========================================================================
                if ($action == 'remove_emailqueue_mysqlinstance') {
                $listofcommands[] = "systemctl stop mysql3316";
                $listofcommands[] = "systemctl disable mysql3316";
                $listofcommands[] = "rm /etc/systemd/system/mysql3316.service";
                $listofcommands[] = "systemctl daemon-reload";
                $listofcommands[] = "rm -rf /var/lib/mysql3316";
                $listofcommands[] = "rm -rf /var/log/mysql3316";
                $listofcommands[] = "rm -rf /etc/mysql/mysql3316";
                $listofcommands[] = "systemctl status mysql";
                $listofcommands[] = "systemctl status mysql3316";
                }

                ///==========================================================================
                if ($action == 'install_emailqueue_mysqlinstance') {
                $listofcommands[] = "apt update";
                $listofcommands[] = "apt install mysql-server";
                
               # $listofcommands[] = "systemctl status mysql";
                
        #        $listofcommands[] = "mkdir -p /var/lib/mysql3316";
        #        $listofcommands[] = "mkdir -p /var/log/mysql3316";
               $listofcommands[] = "mkdir -p /etc/mysql/mysql3316";

// Set appropriate permissions for the data and log directories
#$listofcommands[] = "chown mysql:mysql /var/lib/mysql3316";
#$listofcommands[] = "chmod 700 /var/lib/mysql3316";
#$listofcommands[] = "chown mysql:mysql /var/log/mysql3316";
#$listofcommands[] = "chmod 750 /var/log/mysql3316";

                $listofcommands[] = "cp -r /etc/mysql/* /etc/mysql/mysql3316/.";
                 
               # $listofcommands[] = "nano /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^port.*/port = 3316/' /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^datadir.*/datadir = \\/var\\/lib\\/mysql_additionalinstances\\/mysql3316/' /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^socket.*/socket = \\/var\\/run\\/mysqld\\/mysqld3316.sock/' /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^log_error.*/log_error = \\/var\\/log\\/mysql3316\\/error.log/' /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^pid_file.*/pid_file = \\/var\\/run\\/mysqld\\/mysqld3316.pid/' /etc/mysql/mysql3316/mysqld.cnf";
                
                $listofcommands[] = "mysqld --initialize --datadir=/var/lib/mysql3316 --user=mysql";
                
                $listofcommands[] = "cp /lib/systemd/system/mysql.service /etc/systemd/system/mysql3316.service";
                
           #     $listofcommands[] = "nano /etc/systemd/system/mysql3316.service";
                $listofcommands[] = "sed -i 's/^ExecStart=.*/ExecStart=\\/usr\\/sbin\\/mysqld --defaults-file=\\/etc\\/mysql\\/mysql3316\\/mysqld.cnf/' /etc/systemd/system/mysql3316.service";
                
                $listofcommands[] = "systemctl daemon-reload";
                $listofcommands[] = "systemctl start mysql3316";
                $listofcommands[] = "systemctl enable mysql3316";
                
                $listofcommands[] = "systemctl status mysql3316";
                
             #   $listofcommands[] = "mysql_secure_installation --socket=/var/run/mysqld/mysqld3316.sock";
                
              #  $listofcommands[] = "mysql -u root -p --socket=/var/run/mysqld/mysqld3316.sock";
                
                }

                ///==========================================================================
                if ($action == 'install_emailqueue') {
                    // INSTALL EMAIL QUEUE
                    $listofcommands[] = 'apt-get update';
                    $listofcommands[] = 'apt-get -y install make';
                    $listofcommands[] = '[ -f ~/bg-emailqueue ] && cd ~ && rm -rf ~/bg-emailqueue';

                    $listofcommands[] = 'git clone https://github.com/ddgconsultant/bg-emailqueue';
                    $listofcommands[] = 'sleep 30'; // Sleep for 80 seconds to ensure the git clone command completes

                    $listofcommands[] = 'echo "apikey=\'' . $api_key . '\'" > $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DJLTPL-RKLNL-TNTSM-LTLNTR&apikey=' . $api_key . '&');   // birthday_gold_admin 
                    $listofcommands[] = 'echo "adminpass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DTOWO8-UOONO-NTWNR-TTRT8T&apikey=' . $api_key . '&');   // postmasterpass 
                    $listofcommands[] = 'echo "postmasterpass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCKHC-KJGBK-9DBGB-JBBJ97&apikey=' . $api_key . '&');   // FTP - webinstall 
                    $listofcommands[] = 'echo "ftppass=\'' . $pass . '\'" >> $HOME/.passwordfile';




                    $listofcommands[] = 'make -C ~/bg-emailqueue-docker config';
                    $listofcommands[] = 'sleep 80'; // Sleep for 80 seconds to ensure the git clone command completes
                    $listofcommands[] = 'make -C ~/bg-emailqueue-docker up';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCK9C-97J99-FKDKJ-9HHDFF&apikey=' . $api_key . '&');   // bgdbreplicator1                   
                    $listofcommands[] = 'echo "ALTER USER \'bgdbreplicator1\'@\'%\' IDENTIFIED BY \'' . $pass . '\';"';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DVN3RN-OTMX3-Q7OSO-OQSNOS&apikey=' . $api_key . '&');   // birthday_gold_admin                   
                    $listofcommands[] = 'echo "ALTER USER \'birthday_gold_admin\'@\'%\' IDENTIFIED BY \'' . $pass . '\';"';

                    // Additional steps or messages can be added here if needed
                }


                #-------------------------------------------------------------------------------
                // MODE SWITCH: Display vs Execute

                if ($display_mode) {
                    // DISPLAY MODE: Show commands for manual execution
                    echo '<div class="card mt-3">';
                    echo '<div class="card-header bg-dark text-white">';
                    echo '<h5><i class="bi bi-code-square"></i> Execution Commands</h5>';
                    echo '</div>';
                    echo '<div class="card-body p-0">';
                    echo '<div style="background: #1e1e1e; color: #d4d4d4; padding: 20px; font-family: \'Courier New\', monospace; font-size: 14px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word;">';

                    $command_script = '';
                    $prev_command = '';
                    foreach ($listofcommands as $command) {
                        if (is_callable($command)) {
                            $command_script .= "\n# [Note: Reconnect to SSH if server reboots]\n\n";
                        } else {
                            // Add separator BEFORE cleanup that leads to installation
                            // Pattern: [ -f ~/install_*.sh ] or [ -f ~/deploy_*.sh ] marks start of new section
                            if (preg_match('/^\[ -f ~\/(install_|deploy_).*\.sh \]/', $command)) {
                                $command_script .= "\n" . str_repeat('=', 80) . "\n";
                                $command_script .= "# NEW INSTALLATION SECTION\n";
                                $command_script .= str_repeat('=', 80) . "\n\n";
                            }

                            // Display all commands including passwords (user is authenticated)
                            $command_script .= $command . "\n";
                            $prev_command = $command;
                        }
                    }

                    echo htmlspecialchars($command_script);
                    echo '</div>';
                    echo '<div class="card-footer">';
                    echo '<button class="btn btn-sm btn-primary" onclick="copyCommands()"><i class="bi bi-clipboard"></i> Copy All Commands</button>';
                    echo '<small class="text-muted ms-3">Total commands: ' . count($listofcommands) . '</small>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';

                    echo '<script>';
                    echo 'function copyCommands() {';
                    echo '  const text = ' . json_encode($command_script) . ';';
                    echo '  navigator.clipboard.writeText(text).then(function() {';
                    echo '    alert("Commands copied to clipboard!");';
                    echo '  }, function(err) {';
                    echo '    console.error("Could not copy text: ", err);';
                    echo '  });';
                    echo '}';
                    echo 'function copyPassword(elementId) {';
                    echo '  const password = document.getElementById(elementId).value;';
                    echo '  navigator.clipboard.writeText(password).then(function() {';
                    echo '    const notification = document.createElement("div");';
                    echo '    notification.className = "alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3";';
                    echo '    notification.style.zIndex = "9999";';
                    echo '    notification.textContent = "Password copied!";';
                    echo '    document.body.appendChild(notification);';
                    echo '    setTimeout(() => notification.remove(), 2000);';
                    echo '  }, function(err) {';
                    echo '    console.error("Could not copy password: ", err);';
                    echo '  });';
                    echo '}';
                    echo '</script>';

                    echo '</div>';
                    echo '<div class="alert alert-info mt-4">';
                    echo '<i class="bi bi-info-circle"></i> <strong>Next Steps:</strong><br>';
                    echo '1. Copy the commands above using the "Copy All Commands" button<br>';
                    echo '2. SSH into <strong>' . htmlspecialchars($host) . '</strong> as <strong>' . htmlspecialchars($username) . '</strong><br>';
                    echo '3. Paste and execute the commands in your SSH terminal<br>';
                    echo '4. Monitor the installation progress directly in your terminal';
                    echo '</div>';

                } else {
                    // EXECUTION MODE: Execute commands via SSH (original code)
                    foreach ($listofcommands as $command) {
                        if (is_callable($command)) {
                            $ssh = $command();
                            if (!$ssh) {
                                break;
                            }
                        } else {
                            try {
                                // Add visual separator BEFORE cleanup that leads to installation
                                // Pattern: [ -f ~/install_*.sh ] or [ -f ~/deploy_*.sh ] marks start of new section
                                if (preg_match('/^\[ -f ~\/(install_|deploy_).*\.sh \]/', $command)) {
                                    echo '<div class="alert alert-warning mt-4 mb-4" style="border: 2px solid #ffc107; background: #fff3cd;">';
                                    echo '<h5 style="margin: 0;"><i class="bi bi-arrow-right-circle-fill"></i> NEW INSTALLATION SECTION</h5>';
                                    echo '</div>';
                                }

                                $output = $ssh->exec($command);
                                // Display all commands including passwords (user is authenticated)
                                $displaycommand = $command;

                                echo '<div class="command-header"><span class="timestamp">' . date('H:i:s') . '</span> <strong>' . htmlspecialchars($displaycommand) . '</strong></div>';
                                echo '<div class="command-output">' . htmlspecialchars($output) . '</div>';

                                flush();
                                if (function_exists('apache_reset_timeout')) {
                                    apache_reset_timeout();
                                }
                            } catch (\phpseclib3\Exception\ConnectionClosedException $e) {
                                // Server rebooted - redirect to log viewer
                                echo '<div class="alert alert-warning mt-4">';
                                echo '<h5><i class="bi bi-arrow-clockwise"></i> Server Rebooting</h5>';
                                echo '<p>The server is rebooting as part of the installation process. The installation will continue automatically in the background.</p>';
                                echo '<p>Redirecting to installation log viewer in 5 seconds...</p>';
                                echo '</div>';
                                echo '<script>';
                                echo 'setTimeout(function() {';
                                echo '  window.location.href = "/admin_actions/view-install-log.php?host=' . urlencode($host) . '&type=web";';
                                echo '}, 5000);';
                                echo '</script>';
                                flush();
                                break; // Exit the loop
                            } catch (\Exception $e) {
                                echo '<div class="alert alert-danger mt-4">';
                                echo '<h5>Error</h5>';
                                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                                echo '</div>';
                                flush();
                                break;
                            }
                        }
                    }

                    echo '</div>';
                    echo '<div class="alert alert-success mt-4"><i class="bi bi-check-circle"></i> Command execution completed!</div>';
                }
                #-------------------------------------------------------------------------------

            }
            echo '</div></body></html>';
            flush();
            exit; // Stop execution
        }
    }
    } // Close else block for SSH parameter validation
}







#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');

try {
    // Query to fetch data
    $query = "SELECT id, name, url, create_dt, modify_dt FROM bg_system_availability where `status`='A' order by create_dt DESC";
    $stmt = $database->prepare($query);
    $stmt->execute();
    $systems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle the error
    echo "Error: " . $e->getMessage();
}

// Display alert message if there is one (below header)
if (!empty($alert_message)) {
    echo '<div class="container" style="margin-top: 100px;">' . $alert_message . '</div>';
}

echo '
    <div class="container mt-5">
        <!-- Add New Host Section -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h4>Add New Host</h4>
            </div>
            <div class="card-body">
                <form method="post" action="/admin_actions/newhost_setup">
                    ' . $display->inputcsrf_token() . '
                    <input type="hidden" name="action" value="add_host">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="host_name" class="form-label">Host Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="host_name" name="host_name" placeholder="e.g., March 2025 Server" required>
                        </div>
                        <div class="col-md-6">
                            <label for="host_url" class="form-label">Host URL <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="host_url" name="host_url" placeholder="e.g., march25.bday.gold" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="host_port" class="form-label">Port</label>
                            <input type="number" class="form-control" id="host_port" name="host_port" value="443" placeholder="443">
                            <small class="text-muted">Default: 443</small>
                        </div>
                        <div class="col-md-3">
                            <label for="host_type" class="form-label">Type</label>
                            <select class="form-select" id="host_type" name="host_type">
                                <option value="web" selected>Web Server</option>
                                <option value="db">Database</option>
                                <option value="mail">Mail Server</option>
                                <option value="queue">Email Queue</option>
                                <option value="haproxy">HAProxy</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="host_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="host_description" name="host_description" placeholder="Optional description">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="host_ip" class="form-label">IP Address</label>
                            <input type="text" class="form-control" id="host_ip" name="host_ip" placeholder="e.g., 192.168.1.100">
                            <small class="text-muted">Required for DNS creation</small>
                        </div>
                        <div class="col-md-3">
                            <label for="dns_zone" class="form-label">DNS Zone</label>
                            <select class="form-select" id="dns_zone" name="dns_zone">
                                <option value="bday.gold" selected>bday.gold</option>
                                <option value="birthday.gold">birthday.gold</option>
                                <option value="thedatadesigngroup.com">thedatadesigngroup.com</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label d-block">&nbsp;</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="create_dns" name="create_dns" value="1" checked>
                                <label class="form-check-label" for="create_dns">
                                    <i class="bi bi-globe"></i> Automatically create DNS records (A + www CNAME)
                                </label>
                            </div>
                            <small class="text-muted">Uses PowerDNS API to create records</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Add Host
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manage Existing Hosts Section -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4>Manage Existing Hosts</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>URL</th>
                                <th>Created</th>
                                <th>Modified</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>';

foreach ($systems as $system) {
    $id = htmlspecialchars($system['id']);
    $name = htmlspecialchars($system['name']);
    $url = htmlspecialchars($system['url']);
    $created = $system['create_dt'] ? date('M d, Y', strtotime($system['create_dt'])) : 'N/A';
    $modified = $system['modify_dt'] ? date('M d, Y', strtotime($system['modify_dt'])) : 'N/A';

    echo '<tr>';
    echo '<td>' . $id . '</td>';
    echo '<td>' . $name . '</td>';
    echo '<td>' . $url . '</td>';
    echo '<td>' . $created . '</td>';
    echo '<td>' . $modified . '</td>';
    echo '<td>';
    echo '<button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal' . $id . '">';
    echo '<i class="bi bi-trash"></i> Delete';
    echo '</button>';

    // Delete Modal for this host
    echo '
    <div class="modal fade" id="deleteModal' . $id . '" tabindex="-1" aria-labelledby="deleteModalLabel' . $id . '" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel' . $id . '">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Are you sure you want to delete this host?</p>
                    <div class="alert alert-warning mt-3">
                        <strong>' . $name . '</strong><br>
                        <small class="text-muted">' . $url . '</small>
                    </div>
                    <p class="text-danger small mb-0"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="/admin_actions/newhost_setup">
                        ' . $display->inputcsrf_token() . '
                        <input type="hidden" name="action" value="delete_host">
                        <input type="hidden" name="host_id" value="' . $id . '">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete Host</button>
                    </form>
                </div>
            </div>
        </div>
    </div>';

    echo '</td>';
    echo '</tr>';
}

echo '                  </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SSH Command Executor Section -->
        <h2>SSH Command Executor</h2>
        <form method="post" action="/admin_actions/newhost_setup">
' . $display->inputcsrf_token() . '
<div class="mb-3">
    <label for="host" class="form-label">Host</label>
    <select multiple class="form-select" name="host" id="host">
   ';

// Append options dynamically
foreach ($systems as $system) {
    $name = htmlspecialchars($system['name']);
    $url = htmlspecialchars($system['url']);
    // Extract hostname from name (e.g., "july05.bday.gold" from "july05.bday.gold / Production LAMP Stack")
    $hostname = explode(' ', $name)[0];
    echo '<option value="'.$url.'" data-hostname="'.$hostname.'">'.$name.'</option>';
}

echo '
    </select>
    <input type="hidden" name="host_name" id="host_name" value="">
</div>
<script>
// Capture hostname from selected option
document.getElementById("host").addEventListener("change", function() {
    var selectedOption = this.options[this.selectedIndex];
    var hostname = selectedOption.getAttribute("data-hostname");
    document.getElementById("host_name").value = hostname;
});
</script>
';
/*
            <div class="mb-3">
                <label for="host" class="form-label">Remote Server Host</label>
                <input type="text" class="form-control" id="host" name="host" value="march03.bday.gold" required>
            </div>
*/
// No default values - user must provide credentials
echo '
            <div class="mb-3">
                <label for="username" class="form-label">OS Username</label>
                <input type="text" class="form-control" id="username" name="username" value="root" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">OS User Password</label>
                <input type="password" class="form-control" id="password" name="password" value="" required>
            </div>
            <div class="mb-3">
                <label for="api_key" class="form-label">API Key</label>
                <input type="text" class="form-control" id="api_key" name="api_key" value="">
            </div>
';
$actions = [
    'deploy_www' => 'Deploy WWW',
    'resume_webserver' => 'RESUME Webserver Installation (after reboot)',
    'install_webserver_full' => 'FULL WEB SERVER SETUP (Web + MySQL + HAProxy + Monitoring)',
    'install_webserver' => 'Install Webserver Only',
    'install_mysqldb' => 'Install MySQL DB',
    'install_mailserver' => 'Install Mailserver',
    'install_emailqueue' => 'Install Email Queue Docker',
    'remove_emailqueue_mysqlinstance' => 'Remove Email Queue',
    'install_emailqueue_mysqlinstance' => 'Install Email Queue',
];

foreach ($actions as $act => $label) {
    echo '<div class="form-check">';
    echo '<input class="form-check-input" type="checkbox" name="serveraction[]" value="' . $act . '" id="serveraction_' . $act . '" />';
    echo '<label class="form-check-label" for="serveraction_' . $act . '"> ' . $label . ' </label>';
    echo '</div>';
}


echo '     <button type="submit" class="btn btn-primary">Execute Command</button>
        </form>

    
    </div>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/5.0.0-beta3/js/bootstrap.bundle.min.js"></script>';


#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.inc');
#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footerjs.inc');


$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();