<?php
/**
 * Add July05 Server - Complete Setup
 * One-click addition of july05.bday.gold to the entire infrastructure
 */

$addClasses[] = 'powerdns';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Ensure admin access
if (!$account->isadmin()) {
    header('Location: /');
    exit;
}

$page_title = "Add July05 Server - Complete Setup";

// Server details
$server_details = [
    'short_name' => 'july05',
    'ip_address' => '72.60.121.193',
    'mysql_id' => '705',
    'expires' => '2027-11-11',
    'system_id_lamp' => 180,
    'system_id_mysql' => 320
];

$results = [];
$errors = [];

// Process form submission
if ($app->formposted()) {
    try {
        // ====================================================================
        // STEP 1: Add LAMP Stack Entry
        // ====================================================================
        $lamp_description = "=== Production LAMP Stack\n\n";
        $lamp_description .= "Ubuntu 22.04 64bit\n";
        $lamp_description .= "+ Apache 2.4\n";
        $lamp_description .= "+ PHP 8.3\n";
        $lamp_description .= "+ MySQL 8 (ID: {$server_details['mysql_id']})\n";
        $lamp_description .= "Expires: {$server_details['expires']}";

        $sql = "INSERT INTO bg_system_availability
                (system_id, name, description, url, port, system_status, status, create_dt, modify_dt)
                VALUES (:system_id, :name, :description, :url, :port, :system_status, 'A', NOW(), NOW())";

        $database->query($sql, [
            'system_id' => $server_details['system_id_lamp'],
            'name' => "{$server_details['short_name']}.bday.gold / Production LAMP Stack",
            'description' => $lamp_description,
            'url' => $server_details['ip_address'],
            'port' => 80,
            'system_status' => 'green'
        ]);

        $lamp_id = $database->pdo->lastInsertId();
        $results[] = "✓ Added LAMP Stack entry (ID: $lamp_id)";

        // ====================================================================
        // STEP 2: Add MySQL DB Entry
        // ====================================================================
        $mysql_description = "=== Production MySQL DB\n\n";
        $mysql_description .= "Ubuntu 22.04\n";
        $mysql_description .= "+ Apache 2.4\n";
        $mysql_description .= "+ PHP 8.3\n";
        $mysql_description .= "+ MySQL 8 (ID: {$server_details['mysql_id']})";

        $database->query($sql, [
            'system_id' => $server_details['system_id_mysql'],
            'name' => "{$server_details['short_name']}.bday.gold / Production MySQL DB",
            'description' => $mysql_description,
            'url' => $server_details['ip_address'],
            'port' => 3306,
            'system_status' => 'green'
        ]);

        $mysql_id = $database->pdo->lastInsertId();
        $results[] = "✓ Added MySQL DB entry (ID: $mysql_id)";

        // ====================================================================
        // STEP 3: Update HAProxy Configuration
        // ====================================================================
        $haproxy_file = $dir['document_root'] . '/admin_actions/haproxy.cfg';
        $haproxy_config = file_get_contents($haproxy_file);

        // Add to HTTP backend (port 80)
        $http_line = "    server {$server_details['short_name']} {$server_details['ip_address']}:80 check\n";
        $haproxy_config = str_replace(
            "## END OF 80webservers-do not delete this line - it is used to add new webservers",
            $http_line . "## END OF 80webservers-do not delete this line - it is used to add new webservers",
            $haproxy_config
        );

        // Add to HTTPS backend (port 443)
        $https_line = "    server {$server_details['short_name']} {$server_details['ip_address']}:443 ssl verify none check\n";
        $haproxy_config = str_replace(
            "## END OF 443webservers-do not delete this line - it is used to add new webservers",
            $https_line . "## END OF 443webservers-do not delete this line - it is used to add new webservers",
            $haproxy_config
        );

        // Save updated config
        file_put_contents($haproxy_file, $haproxy_config);
        $results[] = "✓ Updated HAProxy configuration file";
        $results[] = "&nbsp;&nbsp;&nbsp;&nbsp;→ Added HTTP: {$server_details['short_name']} {$server_details['ip_address']}:80";
        $results[] = "&nbsp;&nbsp;&nbsp;&nbsp;→ Added HTTPS: {$server_details['short_name']} {$server_details['ip_address']}:443";

        // ====================================================================
        // STEP 4: DNS Records (via PowerDNS API)
        // ====================================================================
        $dns_results = [];

        if (isset($powerdns)) {
            // Create A record for bday.gold
            $result = $powerdns->createARecord('bday.gold', "{$server_details['short_name']}.bday.gold", $server_details['ip_address']);
            if ($result['success']) {
                $dns_results[] = "✓ bday.gold: {$server_details['short_name']}.bday.gold → {$server_details['ip_address']}";
            } else {
                $dns_results[] = "✗ bday.gold A record failed: " . ($result['error'] ?? 'Unknown error') . " (HTTP {$result['http_code']})";
            }

            // Create A record for birthday.gold
            $result = $powerdns->createARecord('birthday.gold', "{$server_details['short_name']}.birthday.gold", $server_details['ip_address']);
            if ($result['success']) {
                $dns_results[] = "✓ birthday.gold: {$server_details['short_name']}.birthday.gold → {$server_details['ip_address']}";
            } else {
                $dns_results[] = "✗ birthday.gold A record failed: " . ($result['error'] ?? 'Unknown error') . " (HTTP {$result['http_code']})";
            }

            $results[] = "<strong>DNS Records:</strong>";
            $results = array_merge($results, $dns_results);
        } else {
            $errors[] = "⚠ PowerDNS not available - DNS records must be created manually";
        }

    } catch (Exception $e) {
        $errors[] = "✗ Error: " . $e->getMessage();
        error_log("July05 setup error: " . $e->getMessage());
    }
}

// Display page
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3><i class="bi bi-server"></i> Add July05 Server to Infrastructure</h3>
            <p class="mb-0">Complete setup for july05.bday.gold (72.60.121.193)</p>
        </div>
        <div class="card-body">

            <?php if (!empty($results)): ?>
            <div class="alert alert-success">
                <h4><i class="bi bi-check-circle"></i> Setup Complete!</h4>
                <hr>
                <?php foreach ($results as $result): ?>
                    <div class="mb-1"><?php echo $result; ?></div>
                <?php endforeach; ?>

                <hr>
                <h5 class="mt-4">Next Steps:</h5>
                <ol class="mb-0">
                    <li>Deploy HAProxy config: <code>scp haproxy.cfg root@april21.bday.gold:/etc/haproxy/</code></li>
                    <li>Reload HAProxy: <code>ssh root@april21.bday.gold 'systemctl reload haproxy'</code></li>
                    <li>Setup MySQL replication on july05 server</li>
                    <li>Deploy website files to july05</li>
                    <li>Install SSL certificates</li>
                    <li>Test HTTP/HTTPS access</li>
                </ol>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h4>Errors Occurred:</h4>
                <?php foreach ($errors as $error): ?>
                    <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($results) && empty($errors)): ?>
            <div class="alert alert-info">
                <h5>Server Details:</h5>
                <table class="table table-sm mb-0">
                    <tr><th>Short Name:</th><td>july05</td></tr>
                    <tr><th>IP Address:</th><td>72.60.121.193</td></tr>
                    <tr><th>MySQL ID:</th><td>705</td></tr>
                    <tr><th>Expires:</th><td>2027-11-11</td></tr>
                    <tr><th>Stack:</th><td>Ubuntu 22.04, Apache 2.4, PHP 8.3, MySQL 8</td></tr>
                </table>
            </div>

            <div class="alert alert-warning">
                <h5>This will:</h5>
                <ul class="mb-0">
                    <li>Add 2 entries to bg_system_availability (LAMP + MySQL)</li>
                    <li>Update HAProxy config with HTTP + HTTPS backends</li>
                    <li>Create DNS A records for bday.gold and birthday.gold</li>
                </ul>
            </div>

            <form method="post" action="/admin_actions/add_july05_complete">
                <?php echo $display->inputcsrf_token(); ?>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle"></i> Add July05 to Infrastructure
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Manual Instructions if needed -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Manual DNS Instructions (if PowerDNS fails)</h5>
        </div>
        <div class="card-body">
            <h6>Create these A records:</h6>
            <ul>
                <li><strong>bday.gold</strong>: july05.bday.gold → 72.60.121.193</li>
                <li><strong>birthday.gold</strong>: july05.birthday.gold → 72.60.121.193</li>
            </ul>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
