<?php
$addClasses[] = 'api';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$host = $_GET['host'] ?? '';
$log_type = $_GET['type'] ?? 'web'; // web, mysql, mail, etc.

if (empty($host)) {
    die('Host parameter required');
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

$log_file = $log_files[$log_type] ?? $log_files['web'];

// Get state file path
$state_files = [
    'web' => '~/install_state_web',
    'mysql' => '~/install_state_mysql',
    'mail' => '~/mail_server_setup_state',
    'haproxy' => '~/haproxy_add_state',
    'metabase' => '~/metabase_add_state_web',
    'uptime' => '~/uptime_kuma_add_state'
];

$state_file = $state_files[$log_type] ?? $state_files['web'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Progress - <?php echo htmlspecialchars($host); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        #log-container {
            background: #000;
            color: #0f0;
            padding: 20px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            height: 70vh;
            overflow-y: auto;
        }
        .status-badge { font-size: 1.2em; }
        .auto-scroll-toggle { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="bi bi-terminal"></i> Installation Progress</h1>
        <div class="alert alert-info">
            <strong>Host:</strong> <?php echo htmlspecialchars($host); ?><br>
            <strong>Installation Type:</strong> <?php echo htmlspecialchars(strtoupper($log_type)); ?><br>
            <strong>Status:</strong> <span id="status" class="status-badge badge bg-warning">Checking...</span>
        </div>

        <div class="auto-scroll-toggle">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="autoScroll" checked>
                <label class="form-check-label" for="autoScroll">Auto-scroll to bottom</label>
            </div>
        </div>

        <div id="log-container">Loading...</div>

        <div class="mt-3">
            <button class="btn btn-primary" onclick="fetchLog()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <a href="/admin_actions/newhost_setup" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Setup
            </a>
        </div>
    </div>

    <script>
        const host = <?php echo json_encode($host); ?>;
        const logType = <?php echo json_encode($log_type); ?>;
        const logFile = <?php echo json_encode($log_file); ?>;
        const stateFile = <?php echo json_encode($state_file); ?>;
        let lastLogSize = 0;

        function scrollToBottom() {
            if (document.getElementById('autoScroll').checked) {
                const container = document.getElementById('log-container');
                container.scrollTop = container.scrollHeight;
            }
        }

        function updateStatus(state) {
            const statusEl = document.getElementById('status');
            if (state === 'completed') {
                statusEl.className = 'status-badge badge bg-success';
                statusEl.textContent = 'Completed';
            } else if (state === 'pre') {
                statusEl.className = 'status-badge badge bg-info';
                statusEl.textContent = 'Starting...';
            } else {
                statusEl.className = 'status-badge badge bg-warning';
                statusEl.textContent = 'In Progress: ' + state;
            }
        }

        async function fetchLog() {
            try {
                const response = await fetch('view-install-log-ajax.php?host=' + encodeURIComponent(host) + '&type=' + encodeURIComponent(logType));
                const data = await response.json();

                if (data.log) {
                    document.getElementById('log-container').textContent = data.log;
                    scrollToBottom();
                }

                if (data.state) {
                    updateStatus(data.state);
                }

                // If not completed, continue polling
                if (data.state !== 'completed') {
                    setTimeout(fetchLog, 3000); // Poll every 3 seconds
                }
            } catch (error) {
                console.error('Error fetching log:', error);
                document.getElementById('log-container').textContent = 'Error loading log: ' + error.message;
            }
        }

        // Start fetching immediately
        fetchLog();
    </script>
</body>
</html>
