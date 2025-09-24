<?php


include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

/**
 * Admin Log Viewer
 * Secure production log viewer with tail functionality
 *
 * BIRTHDAY GOLD DEVELOPMENT STANDARDS COMPLIANT
 * - Secure log path configuration via ENV_CONFIGS
 * - Admin access only
 * - Real-time log tailing with auto-refresh
 */

// Removed AJAX handling - will use standard HTML form POST instead

// Page setup
$page_title = "Log Viewer - Birthday.Gold Admin";
$page_description = "View production log files";

// Determine server environment
$server_environment = '';
if (isset($website['server_environment'])) {
    $server_environment = $website['server_environment'];
} elseif (strpos($_SERVER['HTTP_HOST'] ?? '', 'dev7') !== false) {
    $server_environment = 'dev7';
} elseif (strpos($_SERVER['HTTP_HOST'] ?? '', 'dev') !== false) {
    $server_environment = 'dev';
} else {
    $server_environment = 'production';
}

// === DB CONFIG (host first, then env fallback) ===============================
$host_identifier   = strtolower(preg_replace('/[^a-z0-9_\-]/i', '', gethostname() ?: 'unknown'));
$ENV_CONFIG_TYPE   = 'log_viewer_env_'  . $server_environment;
$HOST_CONFIG_TYPE  = 'log_viewer_host_' . $host_identifier;

$settings_host = [];
$settings_env  = [];
$log_settings  = [];

// Use framework DB wrapper (do NOT open new connection)
if (is_object($database) && method_exists($database, 'prepare')) {
    try {
        $sql  = "SELECT config_type, config_key, config_value
                 FROM bg_config
                 WHERE config_type IN (?, ?) AND status='1'";
        // Host first so we can optionally short-circuit later if desired
        $stmt = $database->prepare($sql);
        $stmt->execute([$HOST_CONFIG_TYPE, $ENV_CONFIG_TYPE]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['config_type'] === $HOST_CONFIG_TYPE) {
                $settings_host[$row['config_key']] = $row['config_value'];
            } else {
                $settings_env[$row['config_key']] = $row['config_value'];
            }
        }
    } catch (Throwable $e) {
        // silent; will fall back
    }
}

// Coalesce (host wins). Start with host, then fill gaps from env.
$log_settings = $settings_host;
foreach ($settings_env as $k => $v) {
    if (!array_key_exists($k, $log_settings)) {
        $log_settings[$k] = $v;
    }
}

// Legacy file fallback ONLY if both host+env empty
$config_file = '/mnt/w/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-' . $server_environment . '.inc';
if (empty($log_settings) && file_exists($config_file)) {
    $legacy = @parse_ini_file($config_file, true);
    if (!empty($legacy['logs'])) {
        $log_settings   = $legacy['logs'];
        $legacy_seeded  = true;
    }
}

// Helper: upsert (always writes host scope)
if (!function_exists('lv_upsert_config')) {
    function lv_upsert_config($database, string $type, string $key, string $value, ?int $userId = null): bool {
        if (!is_object($database) || !method_exists($database, 'prepare')) return false;
        try {
            $sql = "INSERT INTO bg_config
                        (config_type, config_key, config_value, status, display_order, created_by, updated_by)
                    VALUES (?, ?, ?, '1', 0, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        config_value = VALUES(config_value),
                        updated_by   = VALUES(updated_by),
                        updated_at   = NOW()";
            $stmt = $database->prepare($sql);
            return $stmt->execute([$type, $key, $value, $userId, $userId]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

// Seed legacy into host scope once
if (!empty($legacy_seeded)) {
    $uid = $account->userid ?? null;
    foreach ($log_settings as $k => $v) {
        lv_upsert_config($database, $HOST_CONFIG_TYPE, $k, (string)$v, $uid);
    }
}

// Map settings (defaults)
$apache_access_log = $log_settings['APACHE_ACCESS_LOG']        ?? '/var/log/apache2/access.log';
$apache_error_log  = $log_settings['APACHE_ERROR_LOG']         ?? '/var/log/apache2/error.log';
$php_error_log     = $log_settings['PHP_ERROR_LOG']            ?? '../_logs_/dev7_PHP_errors.log';
$default_lines     = (int)($log_settings['LOG_TAIL_LINES']     ?? 25);
$max_lines         = (int)($log_settings['LOG_MAX_LINES']      ?? 500);
$refresh_interval  = (int)($log_settings['LOG_REFRESH_INTERVAL'] ?? 10);

// Messaging
if (empty($settings_host) && empty($settings_env)) {
    $info_message = ($info_message ?? '') . ' DB config empty; using ' . (!empty($legacy_seeded) ? 'legacy file seed.' : 'defaults.');
} elseif (!empty($settings_host) && !empty($settings_env)) {
    // both present (host precedence)
} elseif (!empty($settings_host)) {
    $info_message = ($info_message ?? '') . ' Using host-only configuration.';
} elseif (!empty($settings_env)) {
    $info_message = ($info_message ?? '') . ' Using environment configuration (no host overrides).';
}

// PRE-EXISTING vars for messages (ensure defined)
$success_message = $success_message ?? '';
$error_message   = $error_message   ?? '';

// Handle form POST for updating log paths (DB only now)
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'update_paths') {

    if ($account->isadmin()) {
        if (!is_object($database) || !method_exists($database, 'prepare')) {
            $error_message = "Database handle not available.";
        } else {
            $uid = $account->userid ?? null;
            $new_vals = [
                'APACHE_ACCESS_LOG'     => trim($_POST['apache_access'] ?? $apache_access_log),
                'APACHE_ERROR_LOG'      => trim($_POST['apache_error'] ?? $apache_error_log),
                'PHP_ERROR_LOG'         => trim($_POST['php_error'] ?? $php_error_log),
                'LOG_TAIL_LINES'        => (string)max(10, intval($_POST['tail_lines'] ?? $default_lines)),
                'LOG_MAX_LINES'         => (string)max(100, intval($_POST['max_lines'] ?? $max_lines)),
                'LOG_REFRESH_INTERVAL'  => (string)max(5, intval($_POST['refresh_interval'] ?? $refresh_interval)),
            ];

            $all_ok = true;
            foreach ($new_vals as $k => $v) {
                if (!lv_upsert_config($database, $HOST_CONFIG_TYPE, $k, $v, $uid)) {
                    $all_ok = false;
                    break;
                }
            }

            if ($all_ok) {
                $success_message = "Log configuration saved (host scope: {$HOST_CONFIG_TYPE}).";
                // Refresh in-memory (host overrides)
                foreach ($new_vals as $k => $v) {
                    $log_settings[$k] = $v;
                }
                $apache_access_log = $new_vals['APACHE_ACCESS_LOG'];
                $apache_error_log  = $new_vals['APACHE_ERROR_LOG'];
                $php_error_log     = $new_vals['PHP_ERROR_LOG'];
                $default_lines     = intval($new_vals['LOG_TAIL_LINES']);
                $max_lines         = intval($new_vals['LOG_MAX_LINES']);
                $refresh_interval  = intval($new_vals['LOG_REFRESH_INTERVAL']);
            } else {
                $error_message = "Error: failed to persist one or more keys.";
            }
        }
    } else {
        $error_message = "Admin access required.";
    }
}

// Get requested log and lines
$requested_log = $_GET['log'] ?? 'php';
$requested_lines = min(intval($_GET['lines'] ?? $default_lines), $max_lines);
$auto_refresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';
$search_term = $_GET['search'] ?? '';
$show_settings = isset($_GET['settings']) && $_GET['settings'] === 'true';

// Map log types to files
$log_files = [
    'apache_access' => $apache_access_log,
    'apache_error' => $apache_error_log,
    'php' => $php_error_log
];

// Validate requested log
if (!isset($log_files[$requested_log])) {
    $requested_log = 'php';
}

$current_log_file = $log_files[$requested_log];

// Function to resolve relative paths
function resolve_log_path($path) {
    // If already absolute and exists, return it
    if (file_exists($path)) {
        return $path;
    }

    // Try relative to DOCUMENT_ROOT
    if (!preg_match('/^([A-Z]:)?[\/\\\\]/', $path)) {
        // Path appears to be relative
        $resolved = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/\\');
        if (file_exists($resolved)) {
            return $resolved;
        }

        // Try relative to parent of DOCUMENT_ROOT (BIRTHDAY_SERVER)
        $resolved = dirname($_SERVER['DOCUMENT_ROOT']) . '/' . ltrim($path, '/\\');
        if (file_exists($resolved)) {
            return $resolved;
        }

        // Try relative to /mnt/w/BIRTHDAY_SERVER
        $resolved = '/mnt/w/BIRTHDAY_SERVER/' . ltrim($path, '/\\');
        if (file_exists($resolved)) {
            return $resolved;
        }
    }

    // Try Windows path conversion
    if (strpos($path, '/mnt/w/') === 0) {
        $winPath = str_replace('/mnt/w/', 'W:/', $path);
        $winPath = str_replace('/', '\\', $winPath);
        if (file_exists($winPath)) {
            return $winPath;
        }
    }

    // Return original path if nothing worked
    return $path;
}

// Function to detect if running on Windows
function is_windows() {
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

// Function to get human-readable time difference
function human_time_diff($timestamp) {
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return round($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return round($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return round($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

// Function to efficiently read last N lines of a file without loading entire file
function read_tail_php($filepath, $lines = 100, $buffer_size = 4096) {
    // Open file
    $f = @fopen($filepath, "rb");
    if ($f === false) {
        return false;
    }

    // Jump to last character
    fseek($f, -1, SEEK_END);

    // Read it and check if it's a newline, if not add to line count
    if (fread($f, 1) != "\n") {
        $lines -= 1;
    }

    // Start reading from the end
    $output = '';
    $chunk = '';
    $line_count = 0;

    // Read chunks from the end until we have enough lines
    while (ftell($f) > 0 && $line_count < $lines) {
        // Calculate how much to read
        $seek = min($buffer_size, ftell($f));

        // Move back and read
        fseek($f, -$seek, SEEK_CUR);
        $chunk = fread($f, $seek);
        fseek($f, -$seek, SEEK_CUR);

        // Count lines in this chunk
        $chunk_lines = substr_count($chunk, "\n");
        $line_count += $chunk_lines;

        // Prepend chunk to output
        $output = $chunk . $output;

        // If we have too many lines, trim from the beginning
        if ($line_count >= $lines) {
            $output_lines = explode("\n", $output);
            $start = count($output_lines) - $lines - 1;
            if ($start > 0) {
                $output_lines = array_slice($output_lines, $start);
            }
            $output = implode("\n", $output_lines);
            break;
        }
    }

    fclose($f);

    // Trim to exact number of lines
    $output_lines = explode("\n", trim($output));
    if (count($output_lines) > $lines) {
        $output_lines = array_slice($output_lines, -$lines);
    }

    return implode("\n", $output_lines);
}

// Function to get tail of file
function get_log_tail($file, $lines, $search = '') {
    // Resolve relative paths
    $resolved_file = resolve_log_path($file);

    // Provide user-friendly messages
    if (!file_exists($resolved_file)) {
        $msg = "⚠️ Log file not found\n\n" .
               "Configured path: " . htmlspecialchars($file) . "\n";
        if ($file !== $resolved_file) {
            $msg .= "Resolved to: " . htmlspecialchars($resolved_file) . "\n";
        }
        $msg .= "\nThis could mean:\n" .
                "• The log file hasn't been created yet\n" .
                "• The path configuration needs to be updated\n" .
                "• Try using a relative path like: ../_logs_/dev7_PHP_errors.log\n\n" .
                "You can update the path using the Settings button above.";
        return $msg;
    }

    if (!is_readable($resolved_file)) {
        return "⚠️ Log file exists but is not readable\n\n" .
               "File path: " . htmlspecialchars($resolved_file) . "\n\n" .
               "This is likely a permission issue.\n" .
               "The web server user may not have read access to this file.\n\n" .
               "Please check file permissions or contact your system administrator.";
    }

    // Get file size for display
    $file_size = filesize($resolved_file);
    $size_mb = round($file_size / 1024 / 1024, 2);

    $output = '';

    // Check if we're on Windows or Linux
    if (is_windows()) {
        // Windows/WAMP environment - use efficient PHP tail reading
        // For search, we need more lines to search through
        $read_lines = !empty($search) ? min($lines * 10, 5000) : $lines;

        $output = read_tail_php($resolved_file, $read_lines);

        if ($output === false) {
            return "⚠️ Unable to read log file\n\n" .
                   "File: " . htmlspecialchars($resolved_file) . "\n" .
                   "Size: " . $size_mb . " MB\n" .
                   "Could not read file contents. Please check file permissions.";
        }

        // Apply search filter if provided
        if (!empty($search)) {
            $output_lines = explode("\n", $output);
            $filtered_lines = [];
            foreach ($output_lines as $line) {
                if (stripos($line, $search) !== false) {
                    $filtered_lines[] = $line;
                }
            }
            // Limit to requested number of lines
            if (count($filtered_lines) > $lines) {
                $filtered_lines = array_slice($filtered_lines, -$lines);
            }
            $output = implode("\n", $filtered_lines);
        }

    } else {
        // Linux/LAMP environment - use tail command
        $command = "tail -n " . escapeshellarg($lines) . " " . escapeshellarg($resolved_file);

        // Add grep if search term provided
        if (!empty($search)) {
            // Get more lines for searching, then limit results
            $search_lines = min($lines * 10, 5000);
            $command = "tail -n " . escapeshellarg($search_lines) . " " . escapeshellarg($resolved_file);
            $command .= " | grep -i " . escapeshellarg($search);
            $command .= " | tail -n " . escapeshellarg($lines);
        }

        $output = shell_exec($command . " 2>&1");

        // Check if tail command failed (shouldn't happen on Linux)
        if ($output === null || strpos($output, "tail: ") === 0 || strpos($output, "'tail'") !== false) {
            // Fallback to efficient PHP method
            $read_lines = !empty($search) ? min($lines * 10, 5000) : $lines;
            $output = read_tail_php($resolved_file, $read_lines);

            if ($output !== false && !empty($search)) {
                $output_lines = explode("\n", $output);
                $filtered_lines = [];
                foreach ($output_lines as $line) {
                    if (stripos($line, $search) !== false) {
                        $filtered_lines[] = $line;
                    }
                }
                if (count($filtered_lines) > $lines) {
                    $filtered_lines = array_slice($filtered_lines, -$lines);
                }
                $output = implode("\n", $filtered_lines);
            }
        }
    }

    if (empty(trim($output))) {
        if (!empty($search)) {
            return "ℹ️ No matches found for: " . htmlspecialchars($search) . "\n\n" .
                   "Try adjusting your search term or clearing the filter.\n" .
                   "File size: " . $size_mb . " MB";
        } else {
            return "ℹ️ Log file is empty or no recent entries\n\n" .
                   "File: " . htmlspecialchars($resolved_file) . "\n" .
                   "Size: " . $size_mb . " MB";
        }
    }

    // Prepend file size info if large
    if ($size_mb > 10) {
        $output = "ℹ️ File size: " . $size_mb . " MB - Showing last " . $lines . " lines\n" .
                 "────────────────────────────────────────\n" . $output;
    }

    return $output;
}

// Get log content if AJAX request
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    header('Content-Type: text/plain');
    echo get_log_tail($current_log_file, $requested_lines, $search_term);
    exit;
}

// CSS for log viewer
$additionalstyles .= '
<style>
.log-viewer-container {
    max-width: 100%;
    margin: 2rem auto;
    padding: 0 1rem;
}

.log-controls {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Modern minimal tab navigation - matching loginhistory.php style */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    overflow: hidden;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
    border: none;
    cursor: pointer;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd !important;
    border-bottom: 3px solid #0d6efd !important;
    background: none;
}

/* Settings tab aligned to the right */
.nav-tab-item.settings-tab {
    margin-left: auto;
}

/* Log info section */
.log-info-section {
    border-left: 3px solid #0d6efd;
    height: 100%;
}

/* Ensure equal height columns */
.log-controls .row {
    align-items: stretch;
}

@media (max-width: 768px) {
    /* Stack the controls on mobile */
    .log-controls > .d-flex {
        flex-direction: column;
        align-items: stretch !important;
    }

    .log-controls .flex-grow-1 {
        margin-bottom: 1rem;
    }

    .log-controls .flex-shrink-0 {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 576px) {
    .nav-tab-item {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }

    /* Adjust search positioning on mobile */
    .search-wrapper {
        margin: 1.5rem auto 1.5rem;
        padding: 0 15px;
    }
}

.log-options {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.settings-panel {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.settings-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1rem;
    align-items: center;
}

.settings-grid label {
    font-weight: 500;
    color: #495057;
}

/* Search Box Styling - matching admin/help pages */
.search-wrapper {
    position: relative;
    max-width: 600px;
    margin: -.1rem auto 3rem;
    z-index: 10;
}

.search-input {
    width: 100%;
    padding: 1rem 1.5rem 1rem 3rem;
    font-size: 1.125rem;
    border: 1px solid #dee2e6;
    border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.search-icon {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
    font-size: 1.2rem;
}

.settings-actions {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

/* Settings tab integrated into nav tabs */

.log-output {
    background: #1e1e1e;
    color: #d4d4d4;
    border-radius: 12px;
    padding: 1.5rem;
    font-family: "Consolas", "Monaco", "Courier New", monospace;
    font-size: 0.875rem;
    line-height: 1.6;
    overflow-x: auto;
    min-height: 400px;
    max-height: 70vh;
    overflow-y: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.log-output::-webkit-scrollbar {
    width: 12px;
    height: 12px;
}

.log-output::-webkit-scrollbar-track {
    background: #2d2d2d;
    border-radius: 6px;
}

.log-output::-webkit-scrollbar-thumb {
    background: #555;
    border-radius: 6px;
}

.log-output::-webkit-scrollbar-thumb:hover {
    background: #666;
}

.log-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #6c757d;
}

.refresh-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.refresh-indicator.active {
    color: #28a745;
    font-weight: 500;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.2em;
}

/* Highlight search terms */
.highlight {
    background-color: #ffeb3b;
    color: #000;
    padding: 0 2px;
}

/* Error lines in red */
.error-line {
    color: #f44336;
}

/* Warning lines in orange */
.warning-line {
    color: #ff9800;
}

/* Info lines in blue */
.info-line {
    color: #2196f3;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .log-viewer-container {
        padding: 0 0.5rem;
    }

    .log-controls {
        padding: 1rem;
    }

    .log-output {
        font-size: 0.75rem;
        padding: 1rem;
    }

    .log-options {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>';

// JavaScript for auto-refresh and functionality
$additionalstyles .= '
<script>
let refreshTimer;
let isRefreshing = false;

function loadLogContent() {
    if (isRefreshing) return;
    isRefreshing = true;

    const logType = "' . $requested_log . '";
    const lines = document.getElementById("lineCount").value;
    const search = document.getElementById("searchTerm").value;
    const url = `/admin/log-viewer.php?ajax=true&log=${logType}&lines=${lines}&search=${encodeURIComponent(search)}`;

    fetch(url)
        .then(response => response.text())
        .then(data => {
            const logOutput = document.getElementById("logOutput");
            logOutput.textContent = data;

            // Highlight search terms if present
            if (search) {
                highlightSearchTerms(logOutput, search);
            }

            // Colorize log levels
            colorizeLogLevels(logOutput);

            // Scroll to bottom if auto-refresh is on
            if (document.getElementById("autoRefresh").checked) {
                logOutput.scrollTop = logOutput.scrollHeight;
            }

            // Update last refresh time
            document.getElementById("lastRefresh").textContent = new Date().toLocaleTimeString();
        })
        .catch(error => {
            console.error("Error loading log:", error);
        })
        .finally(() => {
            isRefreshing = false;
        });
}

function highlightSearchTerms(element, search) {
    const text = element.textContent;
    const regex = new RegExp(`(${search})`, "gi");
    element.innerHTML = text.replace(regex, "<span class=\"highlight\">$1</span>");
}

function colorizeLogLevels(element) {
    let html = element.innerHTML;

    // Colorize error patterns
    html = html.replace(/(\[error\]|ERROR|Fatal|Exception)/gi, "<span class=\"error-line\">$1</span>");

    // Colorize warning patterns
    html = html.replace(/(\[warn\]|WARNING|Warning)/gi, "<span class=\"warning-line\">$1</span>");

    // Colorize info patterns
    html = html.replace(/(\[info\]|INFO|Notice)/gi, "<span class=\"info-line\">$1</span>");

    element.innerHTML = html;
}

function toggleAutoRefresh() {
    const checkbox = document.getElementById("autoRefresh");
    const indicator = document.querySelector(".refresh-indicator");

    if (checkbox.checked) {
        indicator.classList.add("active");
        startAutoRefresh();
    } else {
        indicator.classList.remove("active");
        stopAutoRefresh();
    }
}

function startAutoRefresh() {
    stopAutoRefresh();
    refreshTimer = setInterval(loadLogContent, ' . ($refresh_interval * 1000) . ');
}

function stopAutoRefresh() {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
}

function changeLogType(logType) {
    const search = document.getElementById("searchTerm").value;
    const lines = document.getElementById("lineCount").value;
    const refresh = document.getElementById("autoRefresh").checked;

    window.location.href = `/admin/log-viewer.php?log=${logType}&lines=${lines}&refresh=${refresh}&search=${encodeURIComponent(search)}`;
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    loadLogContent();

    // Start auto-refresh if enabled
    if (document.getElementById("autoRefresh").checked) {
        startAutoRefresh();
    }

    // Add event listeners
    document.getElementById("lineCount").addEventListener("change", loadLogContent);
    document.getElementById("searchTerm").addEventListener("input", loadLogContent);
    document.getElementById("refreshButton").addEventListener("click", loadLogContent);
});

// Clean up on page unload
window.addEventListener("beforeunload", function() {
    stopAutoRefresh();
});

function toggleSettings() {
    const panel = document.getElementById("settingsPanel");
    const logContentArea = document.getElementById("logContentArea");
    const isVisible = panel.style.display !== "none";

    if (isVisible) {
        // Hide settings, show log output
        panel.style.display = "none";
        logContentArea.style.display = "block";
        // Remove active class from settings tab
        document.querySelectorAll(".nav-tab-item").forEach(tab => {
            if (tab.classList.contains("settings-tab")) {
                tab.classList.remove("active");
            }
        });
        // Activate the current log tab
        loadLogContent();
    } else {
        // Show settings, hide log output
        panel.style.display = "block";
        logContentArea.style.display = "none";
        // Add active class to settings tab
        document.querySelectorAll(".nav-tab-item").forEach(tab => {
            tab.classList.remove("active");
            if (tab.classList.contains("settings-tab")) {
                tab.classList.add("active");
            }
        });
    }
}

// saveSettings function removed - using standard HTML form POST instead
</script>';

// Include Birthday Gold header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Admin content header with search
echo '
<div class="content-header-admin bg-primary text-white py-4">
    <div class="container-fluid">
        <h1 class="h2 mb-0">
            <i class="bi bi-terminal"></i> Production Log Viewer
        </h1>
        <p class="mb-3">Monitor and analyze server logs in real-time</p>
    </div>
</div>

<!-- Search Box - matching admin/help style -->
<div class="container-fluid" style="margin-top: -2rem; position: relative; z-index: 1000;">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="searchTerm" class="form-control search-input"
                       placeholder="Search logs..." value="' . htmlspecialchars($search_term) . '">
            </div>
        </div>
    </div>
</div>

<div class="log-viewer-container" style="margin-top: 2rem;">';

// Display any configuration errors
if (!empty($error_message)) {
    echo '
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> ' . $error_message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

// Display info message if set
if (!empty($info_message)) {
    echo '
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle"></i> ' . $info_message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

// Display success message if set
if (!empty($success_message)) {
    echo '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> ' . $success_message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

// Settings panel (hidden by default, toggled by Settings tab)
echo '
    <div id="settingsPanel" class="settings-panel" style="display: none;">
        <h5 class="mb-3">
            <i class="bi bi-gear-fill"></i> Log Path Configuration
            <span class="badge bg-info ms-2">' . htmlspecialchars($server_environment) . '</span>
        </h5>

        <form id="settingsForm" method="POST" action="/admin/log-viewer.php">
            <input type="hidden" name="action" value="update_paths">
            <div class="settings-grid">
                <label for="phpErrorPath">PHP Error Log:</label>
                <input type="text" id="phpErrorPath" name="php_error" class="form-control"
                       value="' . htmlspecialchars($php_error_log) . '"
                       placeholder="../_logs_/dev7_PHP_errors.log"
                       title="Can be absolute or relative path. Relative paths are resolved from document root.">

                <label for="apacheErrorPath">Apache Error Log:</label>
                <input type="text" id="apacheErrorPath" name="apache_error" class="form-control"
                       value="' . htmlspecialchars($apache_error_log) . '"
                       placeholder="/var/log/apache2/error.log">

                <label for="apacheAccessPath">Apache Access Log:</label>
                <input type="text" id="apacheAccessPath" name="apache_access" class="form-control"
                       value="' . htmlspecialchars($apache_access_log) . '"
                       placeholder="/var/log/apache2/access.log">

                <label for="tailLines">Default Lines:</label>
                <input type="number" id="tailLines" name="tail_lines" class="form-control"
                       value="' . $default_lines . '" min="10" max="1000" placeholder="25">

                <label for="maxLines">Max Lines:</label>
                <input type="number" id="maxLines" name="max_lines" class="form-control"
                       value="' . $max_lines . '" min="100" max="10000">

                <label for="refreshInterval">Refresh Interval (seconds):</label>
                <input type="number" id="refreshInterval" name="refresh_interval" class="form-control"
                       value="' . $refresh_interval . '" min="5" max="60">
            </div>

            <div class="settings-actions">
                <button type="button" class="btn btn-secondary" onclick="toggleSettings()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </form>

        <div class="mt-3 text-muted small">
            <i class="bi bi-info-circle"></i>
            Settings precedence: host overrides environment.<br>
            Host config_type: <code><?=htmlspecialchars($HOST_CONFIG_TYPE)?></code><br>
            Env  config_type: <code><?=htmlspecialchars($ENV_CONFIG_TYPE)?></code>
        </div>
    </div>

    <!-- Modern Tab Navigation (matching loginhistory.php style) -->
    <nav class="nav-tabs-modern">
        <button class="nav-tab-item' . ($requested_log === 'php' ? ' active' : '') . '"
                onclick="changeLogType(\'php\')"
                type="button">
            <i class="bi bi-code-slash me-2"></i>PHP Errors
            ' . (file_exists(resolve_log_path($php_error_log)) ?
                '<span class="badge bg-success ms-1" style="font-size: 0.7rem;">●</span>' :
                '<span class="badge bg-warning ms-1" style="font-size: 0.7rem;">○</span>') . '
        </button>

        <button class="nav-tab-item' . ($requested_log === 'apache_error' ? ' active' : '') . '"
                onclick="changeLogType(\'apache_error\')"
                type="button">
            <i class="bi bi-exclamation-triangle me-2"></i>Apache Errors
            ' . (file_exists(resolve_log_path($apache_error_log)) ?
                '<span class="badge bg-success ms-1" style="font-size: 0.7rem;">●</span>' :
                '<span class="badge bg-warning ms-1" style="font-size: 0.7rem;">○</span>') . '
        </button>

        <button class="nav-tab-item' . ($requested_log === 'apache_access' ? ' active' : '') . '"
                onclick="changeLogType(\'apache_access\')"
                type="button">
            <i class="bi bi-globe me-2"></i>Apache Access
            ' . (file_exists(resolve_log_path($apache_access_log)) ?
                '<span class="badge bg-success ms-1" style="font-size: 0.7rem;">●</span>' :
                '<span class="badge bg-warning ms-1" style="font-size: 0.7rem;">○</span>') . '
        </button>

        <button class="nav-tab-item settings-tab"
                onclick="toggleSettings()"
                type="button"
                title="Configure log paths and settings">
            <i class="bi bi-gear"></i>
        </button>
    </nav>

    <div class="log-controls">
        <div class="d-flex align-items-center gap-3">
            <!-- File Information (flexible width) -->
            <div class="flex-grow-1">
                <div class="log-info-section h-100 p-2 bg-light rounded small">';

$resolved_path = resolve_log_path($current_log_file);

// Build comprehensive log info
if (file_exists($resolved_path)) {
    $file_size = filesize($resolved_path);
    $size_text = '';

    if ($file_size >= 1073741824) {
        $size_text = round($file_size / 1073741824, 2) . ' GB';
        $badge_class = 'bg-danger';
    } elseif ($file_size >= 1048576) {
        $size_text = round($file_size / 1048576, 2) . ' MB';
        $badge_class = ($file_size > 104857600) ? 'bg-warning text-dark' : 'bg-info';
    } elseif ($file_size >= 1024) {
        $size_text = round($file_size / 1024, 2) . ' KB';
        $badge_class = 'bg-success';
    } else {
        $size_text = $file_size . ' bytes';
        $badge_class = 'bg-success';
    }

    $last_modified = date('Y-m-d H:i:s', filemtime($resolved_path));
    $time_ago = human_time_diff(filemtime($resolved_path));

    echo '
                    <div class="mb-2">
                        <span class="text-muted">File Path:</span>
                        <code class="small">' . htmlspecialchars($current_log_file) . '</code>
                        <span class="badge bg-secondary ms-2">' . (is_windows() ? 'WAMP' : 'LAMP') . '</span>
                    </div>
                    <div class="small">
                        <span class="text-muted">Size:</span>
                        <span class="badge ' . $badge_class . ' ms-1">' . $size_text . '</span>
                        <span class="text-muted ms-3">|</span>
                        <span class="text-muted ms-3">Modified:</span>
                        <span title="' . $last_modified . '">' . $time_ago . '</span>
                    </div>';
} else {
    echo '
                    <div class="mb-2">
                        <span class="text-muted">File Path:</span>
                        <code class="small">' . htmlspecialchars($current_log_file) . '</code>
                        <span class="badge bg-secondary ms-2">' . (is_windows() ? 'WAMP' : 'LAMP') . '</span>
                    </div>
                    <div class="small">
                        <span class="badge bg-danger">File Not Found</span>
                    </div>';
}

echo '
                </div>
            </div>

            <!-- Log Control Options (auto-width) -->
            <div class="d-flex align-items-center gap-3 flex-shrink-0">
                <label for="lineCount" class="form-label mb-0">Lines:</label>
                <select id="lineCount" class="form-select form-select-sm" style="width: auto;">
                    <option value="25"' . ($requested_lines == 25 ? ' selected' : '') . '>25</option>
                    <option value="50"' . ($requested_lines == 50 ? ' selected' : '') . '>50</option>
                    <option value="100"' . ($requested_lines == 100 ? ' selected' : '') . '>100</option>
                    <option value="200"' . ($requested_lines == 200 ? ' selected' : '') . '>200</option>
                    <option value="500"' . ($requested_lines == 500 ? ' selected' : '') . '>500</option>
                </select>

                <div class="vr"></div>

                <label class="form-check-label mb-0" for="autoRefresh">Auto-refresh</label>
                <input type="checkbox" class="form-check-input mb-0" id="autoRefresh"' . ($auto_refresh ? ' checked' : '') . ' onchange="toggleAutoRefresh()">
                <button id="refreshButton" class="btn btn-sm btn-primary">
                    Refresh Now
                </button>
            </div>
        </div>
    </div>

    <div id="logContentArea">
        <div class="log-output" id="logOutput">
            <div class="text-center p-5">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-light">Loading log content...</p>
            </div>
        </div>
    </div>

    <div class="log-status">
        <div class="refresh-indicator' . ($auto_refresh ? ' active' : '') . '">
            <i class="bi bi-clock-history"></i>
            <span>Last refresh: <span id="lastRefresh">-</span></span>
        </div>
    </div>
</div>';



// Include Birthday Gold footer
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>