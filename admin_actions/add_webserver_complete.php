<?php
/**
 * Complete Webserver Addition Script
 * Adds a new webserver to the entire Birthday.Gold infrastructure
 *
 * This script handles:
 * 1. Database system registration
 * 2. HAProxy configuration update
 * 3. MySQL replication setup
 * 4. DNS record creation
 * 5. Monitoring setup
 */

$addClasses[] = 'api';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$page_title = "Add Complete Webserver Setup";
$page_description = "Comprehensive webserver addition to infrastructure";

// Process form submission
if ($app->formposted()) {
    $server_name = $_POST['server_name'] ?? '';  // e.g., "july05"
    $server_ip = $_POST['server_ip'] ?? '';      // e.g., "72.60.121.193"
    $mysql_id = $_POST['mysql_id'] ?? '705';     // e.g., "705"
    $expires_date = $_POST['expires_date'] ?? ''; // e.g., "2027-11-11"

    $results = [];
    $errors = [];

    try {
        // ========================================================================
        // STEP 1: Add to bg_system_availability (LAMP Stack)
        // ========================================================================
        $description = "=== Production LAMP Stack\n\n";
        $description .= "Ubuntu 22.04 64bit\n";
        $description .= "+ Apache 2.4\n";
        $description .= "+ PHP 8.3\n";
        $description .= "+ MySQL 8 (ID: $mysql_id)\n";
        $description .= "Expires: $expires_date";

        $sql = "INSERT INTO bg_system_availability
                (system_id, name, description, url, port, system_status, status, create_dt, modify_dt)
                VALUES (180, :name, :description, :url, 80, 'green', 'A', NOW(), NOW())";

        $database->query($sql, [
            'name' => "{$server_name}.bday.gold / Production LAMP Stack",
            'description' => $description,
            'url' => $server_ip
        ]);

        $lamp_id = $database->pdo->lastInsertId();
        $results[] = "✓ Added LAMP Stack entry (ID: $lamp_id)";

        // ========================================================================
        // STEP 2: Add to bg_system_availability (MySQL DB)
        // ========================================================================
        $mysql_description = "=== Production MySQL DB\n\n";
        $mysql_description .= "Ubuntu 22.04\n";
        $mysql_description .= "+ Apache 2.4\n";
        $mysql_description .= "+ PHP 8.3\n";
        $mysql_description .= "+ MySQL 8 (ID: $mysql_id)";

        $sql = "INSERT INTO bg_system_availability
                (system_id, name, description, url, port, system_status, status, create_dt, modify_dt)
                VALUES (320, :name, :description, :url, 3306, 'green', 'A', NOW(), NOW())";

        $database->query($sql, [
            'name' => "{$server_name}.bday.gold / Production MySQL DB",
            'description' => $mysql_description,
            'url' => $server_ip
        ]);

        $mysql_entry_id = $database->pdo->lastInsertId();
        $results[] = "✓ Added MySQL DB entry (ID: $mysql_entry_id)";

        // ========================================================================
        // STEP 3: Update HAProxy Configuration
        // ========================================================================
        $haproxy_config_path = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/admin_actions/haproxy.cfg';
        $haproxy_config = file_get_contents($haproxy_config_path);

        // Add to HTTP backend (port 80)
        $http_line = "    server $server_name $server_ip:80 check\n";
        $haproxy_config = str_replace(
            "## END OF 80webservers-do not delete this line - it is used to add new webservers",
            $http_line . "## END OF 80webservers-do not delete this line - it is used to add new webservers",
            $haproxy_config
        );

        // Add to HTTPS backend (port 443)
        $https_line = "    server $server_name $server_ip:443 ssl verify none check\n";
        $haproxy_config = str_replace(
            "## END OF 443webservers-do not delete this line - it is used to add new webservers",
            $https_line . "## END OF 443webservers-do not delete this line - it is used to add new webservers",
            $haproxy_config
        );

        // Save updated config
        file_put_contents($haproxy_config_path, $haproxy_config);
        $results[] = "✓ Updated HAProxy configuration";
        $results[] = "&nbsp;&nbsp;&nbsp;→ Added HTTP backend: $server_name $server_ip:80";
        $results[] = "&nbsp;&nbsp;&nbsp;→ Added HTTPS backend: $server_name $server_ip:443";

        // ========================================================================
        // STEP 4: Generate HAProxy Deployment Command
        // ========================================================================
        $haproxy_deploy_cmd = "# Deploy to HAProxy server:\n";
        $haproxy_deploy_cmd .= "scp {$haproxy_config_path} root@april21.bday.gold:/etc/haproxy/haproxy.cfg\n";
        $haproxy_deploy_cmd .= "ssh root@april21.bday.gold 'systemctl reload haproxy'";

        $results[] = "✓ HAProxy deployment command generated";
        $results[] = "<pre class='bg-dark text-light p-3 mt-2'>" . htmlspecialchars($haproxy_deploy_cmd) . "</pre>";

        // ========================================================================
        // STEP 5: Generate MySQL Replication Setup
        // ========================================================================
        $mysql_commands = "# MySQL Replication Setup Commands:\n\n";
        $mysql_commands .= "# On the new server ($server_name):\n";
        $mysql_commands .= "mysql -u root -p\n\n";
        $mysql_commands .= "-- Create replication user\n";
        $mysql_commands .= "CREATE USER 'bgdbreplicator1'@'%' IDENTIFIED BY '[PASSWORD_FROM_ACCESS_MANAGER]';\n";
        $mysql_commands .= "GRANT REPLICATION SLAVE ON *.* TO 'bgdbreplicator1'@'%';\n";
        $mysql_commands .= "FLUSH PRIVILEGES;\n\n";
        $mysql_commands .= "-- Configure as replica\n";
        $mysql_commands .= "CHANGE MASTER TO\n";
        $mysql_commands .= "  MASTER_HOST='[MASTER_IP]',\n";
        $mysql_commands .= "  MASTER_USER='bgdbreplicator1',\n";
        $mysql_commands .= "  MASTER_PASSWORD='[PASSWORD]',\n";
        $mysql_commands .= "  MASTER_LOG_FILE='[CURRENT_LOG_FILE]',\n";
        $mysql_commands .= "  MASTER_LOG_POS=[CURRENT_POS];\n\n";
        $mysql_commands .= "START SLAVE;\n";
        $mysql_commands .= "SHOW SLAVE STATUS\\G\n";

        $results[] = "✓ MySQL replication commands generated";
        $results[] = "<pre class='bg-dark text-light p-3 mt-2'>" . htmlspecialchars($mysql_commands) . "</pre>";

        // ========================================================================
        // STEP 6: DNS Configuration (Manual - needs API key)
        // ========================================================================
        $dns_info = "# DNS Records to Create:\n\n";
        $dns_info .= "Domain: bday.gold\n";
        $dns_info .= "  A Record: {$server_name} → {$server_ip}\n\n";
        $dns_info .= "Domain: birthday.gold\n";
        $dns_info .= "  A Record: {$server_name} → {$server_ip}\n";

        $results[] = "⚠ DNS records need to be created manually:";
        $results[] = "<pre class='bg-warning bg-opacity-10 p-3 mt-2'>" . htmlspecialchars($dns_info) . "</pre>";

        // ========================================================================
        // STEP 7: Post-Installation Checklist
        // ========================================================================
        $checklist = "# Post-Installation Checklist:\n\n";
        $checklist .= "□ Deploy HAProxy configuration\n";
        $checklist .= "□ Setup MySQL replication\n";
        $checklist .= "□ Create DNS records\n";
        $checklist .= "□ Deploy website files to new server\n";
        $checklist .= "□ Install SSL certificates\n";
        $checklist .= "□ Configure PHP/Apache settings\n";
        $checklist .= "□ Add to monitoring (Uptime Kuma)\n";
        $checklist .= "□ Add to Metabase\n";
        $checklist .= "□ Test HTTP/HTTPS access\n";
        $checklist .= "□ Verify MySQL replication status\n";

        $results[] = "<h5 class='mt-4'>Post-Installation Tasks:</h5>";
        $results[] = "<pre class='bg-info bg-opacity-10 p-3'>" . htmlspecialchars($checklist) . "</pre>";

    } catch (Exception $e) {
        $errors[] = "✗ Error: " . $e->getMessage();
        error_log("Add webserver error: " . $e->getMessage());
    }
}

// Display page
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3><i class="bi bi-server"></i> Add Complete Webserver to Infrastructure</h3>
        </div>
        <div class="card-body">

            <?php if (!empty($results)): ?>
            <div class="alert alert-success">
                <h4>✓ Webserver Addition Complete!</h4>
                <?php foreach ($results as $result): ?>
                    <div><?php echo $result; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="post" action="/admin_actions/add_webserver_complete">
                <?php echo $display->inputcsrf_token(); ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Server Name:</strong></label>
                        <input type="text" class="form-control" name="server_name"
                               placeholder="july05" value="july05" required>
                        <small class="text-muted">Without domain extension (e.g., july05)</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><strong>Server IP Address:</strong></label>
                        <input type="text" class="form-control" name="server_ip"
                               placeholder="72.60.121.193" value="72.60.121.193" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>MySQL Instance ID:</strong></label>
                        <input type="text" class="form-control" name="mysql_id"
                               placeholder="705" value="705" required>
                        <small class="text-muted">Unique MySQL identifier (e.g., 705)</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><strong>Server Expires Date:</strong></label>
                        <input type="date" class="form-control" name="expires_date"
                               value="2027-11-11" required>
                    </div>
                </div>

                <div class="alert alert-info">
                    <h5>This script will:</h5>
                    <ul>
                        <li>Add server to bg_system_availability (LAMP + MySQL entries)</li>
                        <li>Update HAProxy configuration (HTTP + HTTPS backends)</li>
                        <li>Generate MySQL replication setup commands</li>
                        <li>Generate DNS configuration instructions</li>
                        <li>Provide post-installation checklist</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle"></i> Add Webserver to Infrastructure
                </button>
            </form>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
