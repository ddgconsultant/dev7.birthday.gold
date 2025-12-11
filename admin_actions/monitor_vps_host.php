<?php
/**
 * monitor_vps_host.php - Centralized VPS Host Health Monitor
 *
 * Checks: CPU, Disk, Memory, I/O, Services, Network
 * Returns: ALL_GOOD or ALERT with details
 * Works on: Linux (VPS) and Windows
 *
 * Features:
 * - Spike tolerance via "since last check" threshold
 * - Configurable thresholds
 * - State persistence for trend analysis
 * - JSON or text output
 *
 * Usage:
 *   curl https://yoursite.com/admin_actions/monitor_vps_host.php
 *   curl https://yoursite.com/admin_actions/monitor_vps_host.php?format=json
 *   curl https://yoursite.com/admin_actions/monitor_vps_host.php?format=json&pretty=1
 */

// Detect OS
define('IS_WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
define('IS_LINUX', PHP_OS === 'Linux');

// Configuration - Thresholds (adjust as needed)
$CONFIG = [
    // CPU: percentage (warn, critical)
    'cpu_warn' => 80,
    'cpu_critical' => 95,

    // Memory: percentage (warn, critical)
    'mem_warn' => 85,
    'mem_critical' => 95,

    // Disk: percentage (warn, critical)
    'disk_warn' => 80,
    'disk_critical' => 90,

    // Load average: multiplier of CPU cores (warn, critical) - Linux only
    'load_warn_multiplier' => 1.5,
    'load_critical_multiplier' => 3.0,

    // I/O wait: percentage (warn, critical) - Linux only
    'iowait_warn' => 30,
    'iowait_critical' => 50,

    // Network: errors threshold
    'net_errors_warn' => 10,
    'net_errors_critical' => 100,

    // Spike tolerance: number of consecutive checks before alerting
    'spike_tolerance' => 2,

    // State file for persistence (cross-platform temp dir)
    'state_file' => sys_get_temp_dir() . '/monitor_vps_host_state.json',

    // Critical services to check (set to empty array to skip service checks)
    'critical_services_linux' => ['apache2', 'mysql', 'php-fpm', 'cron'],
    'critical_services_windows' => []  // Windows services vary - configure as needed: ['Apache2.4', 'MySQL80', 'W3SVC']
];

// Output format
$format = $_GET['format'] ?? 'text';
$pretty = isset($_GET['pretty']);

// Initialize results
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'hostname' => gethostname() ?: php_uname('n'),
    'os' => IS_WINDOWS ? 'Windows' : 'Linux',
    'status' => 'ALL_GOOD',
    'alerts' => [],
    'warnings' => [],
    'metrics' => [],
    'spike_counts' => []
];

// Load previous state
$previous_state = [];
if (file_exists($CONFIG['state_file'])) {
    $content = @file_get_contents($CONFIG['state_file']);
    if ($content) {
        $previous_state = json_decode($content, true) ?: [];
    }
}

/**
 * Safe shell execution
 */
function safe_exec($cmd) {
    if (!function_exists('shell_exec')) {
        return null;
    }
    $disabled = explode(',', ini_get('disable_functions'));
    if (in_array('shell_exec', array_map('trim', $disabled))) {
        return null;
    }
    return @shell_exec($cmd);
}

/**
 * Check if metric should alert based on spike tolerance
 */
function should_alert($metric_name, $is_problem, &$spike_counts, $tolerance) {
    global $previous_state;
    $prev_count = $previous_state['spike_counts'][$metric_name] ?? 0;

    if ($is_problem) {
        $spike_counts[$metric_name] = $prev_count + 1;
        return $spike_counts[$metric_name] >= $tolerance;
    } else {
        $spike_counts[$metric_name] = 0;
        return false;
    }
}

/**
 * Get CPU usage - Cross-platform
 */
function get_cpu_usage() {
    if (IS_LINUX) {
        // Linux: read from /proc/stat
        if (file_exists('/proc/stat')) {
            $stat1 = @file('/proc/stat');
            if ($stat1) {
                usleep(100000); // 100ms sample
                $stat2 = @file('/proc/stat');

                if ($stat1 && $stat2 && isset($stat1[0]) && isset($stat2[0])) {
                    $info1 = preg_split('/\s+/', trim($stat1[0]));
                    $info2 = preg_split('/\s+/', trim($stat2[0]));

                    // Skip 'cpu' label
                    array_shift($info1);
                    array_shift($info2);

                    $diff = [];
                    $diff['user'] = (intval($info2[0] ?? 0) - intval($info1[0] ?? 0));
                    $diff['nice'] = (intval($info2[1] ?? 0) - intval($info1[1] ?? 0));
                    $diff['system'] = (intval($info2[2] ?? 0) - intval($info1[2] ?? 0));
                    $diff['idle'] = (intval($info2[3] ?? 0) - intval($info1[3] ?? 0));
                    $diff['iowait'] = (intval($info2[4] ?? 0) - intval($info1[4] ?? 0));

                    $total = array_sum($diff);
                    if ($total > 0) {
                        return [
                            'usage' => round(100 - (($diff['idle'] / $total) * 100), 1),
                            'iowait' => round(($diff['iowait'] / $total) * 100, 1)
                        ];
                    }
                }
            }
        }
    }

    if (IS_WINDOWS) {
        // Windows: use wmic
        $output = safe_exec('wmic cpu get loadpercentage /value 2>nul');
        if ($output && preg_match('/LoadPercentage=(\d+)/', $output, $m)) {
            return ['usage' => intval($m[1]), 'iowait' => 0];
        }
    }

    return ['usage' => 0, 'iowait' => 0, 'note' => 'Unable to read CPU'];
}

/**
 * Get memory usage - Cross-platform
 */
function get_memory_usage() {
    if (IS_LINUX && file_exists('/proc/meminfo')) {
        $meminfo = @file_get_contents('/proc/meminfo');
        if ($meminfo) {
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
            preg_match('/SwapTotal:\s+(\d+)/', $meminfo, $swap_total);
            preg_match('/SwapFree:\s+(\d+)/', $meminfo, $swap_free);

            if (!empty($total[1])) {
                $total_mb = round($total[1] / 1024);
                $available_mb = isset($available[1]) ? round($available[1] / 1024) : 0;
                $used_mb = $total_mb - $available_mb;

                return [
                    'total_mb' => $total_mb,
                    'used_mb' => $used_mb,
                    'available_mb' => $available_mb,
                    'percent' => $total_mb > 0 ? round(($used_mb / $total_mb) * 100, 1) : 0,
                    'swap_total_mb' => isset($swap_total[1]) ? round($swap_total[1] / 1024) : 0,
                    'swap_used_mb' => isset($swap_total[1], $swap_free[1]) ? round(($swap_total[1] - $swap_free[1]) / 1024) : 0,
                    'swap_percent' => (isset($swap_total[1]) && $swap_total[1] > 0) ? round((($swap_total[1] - ($swap_free[1] ?? 0)) / $swap_total[1]) * 100, 1) : 0
                ];
            }
        }
    }

    if (IS_WINDOWS) {
        $output = safe_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /value 2>nul');
        if ($output) {
            preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $total);
            preg_match('/FreePhysicalMemory=(\d+)/', $output, $free);
            if (!empty($total[1])) {
                $total_mb = round($total[1] / 1024);
                $free_mb = isset($free[1]) ? round($free[1] / 1024) : 0;
                $used_mb = $total_mb - $free_mb;
                return [
                    'total_mb' => $total_mb,
                    'used_mb' => $used_mb,
                    'available_mb' => $free_mb,
                    'percent' => round(($used_mb / $total_mb) * 100, 1),
                    'swap_total_mb' => 0, 'swap_used_mb' => 0, 'swap_percent' => 0
                ];
            }
        }
    }

    return [
        'total_mb' => 0, 'used_mb' => 0, 'available_mb' => 0, 'percent' => 0,
        'swap_total_mb' => 0, 'swap_used_mb' => 0, 'swap_percent' => 0,
        'note' => 'Unable to read memory'
    ];
}

/**
 * Get disk usage - Cross-platform
 */
function get_disk_usage() {
    $disks = [];

    if (IS_LINUX) {
        $output = safe_exec('df -P 2>/dev/null');
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (strpos($line, 'Filesystem') === 0) continue;
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 6 && strpos($parts[0], '/dev/') === 0) {
                    $mount = $parts[5];
                    if (preg_match('#^(/|/home|/var|/tmp|/mnt|/mnt/.*)$#', $mount)) {
                        $disks[$mount] = [
                            'device' => $parts[0],
                            'size' => format_bytes($parts[1] * 1024),
                            'used' => format_bytes($parts[2] * 1024),
                            'available' => format_bytes($parts[3] * 1024),
                            'percent' => intval($parts[4])
                        ];
                    }
                }
            }
        }
    }

    if (IS_WINDOWS) {
        $output = safe_exec('wmic logicaldisk get size,freespace,caption 2>nul');
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (preg_match('/^([A-Z]:)\s+(\d+)\s+(\d+)/', trim($line), $m)) {
                    $size = intval($m[3]);
                    $free = intval($m[2]);
                    $used = $size - $free;
                    $disks[$m[1]] = [
                        'device' => $m[1],
                        'size' => format_bytes($size),
                        'used' => format_bytes($used),
                        'available' => format_bytes($free),
                        'percent' => $size > 0 ? round(($used / $size) * 100) : 0
                    ];
                }
            }
        }
    }

    // Fallback using PHP disk_free_space
    if (empty($disks)) {
        $root = IS_WINDOWS ? 'C:' : '/';
        $total = @disk_total_space($root);
        $free = @disk_free_space($root);
        if ($total) {
            $used = $total - $free;
            $disks[$root] = [
                'device' => $root,
                'size' => format_bytes($total),
                'used' => format_bytes($used),
                'available' => format_bytes($free),
                'percent' => round(($used / $total) * 100)
            ];
        }
    }

    return $disks ?: ['/' => ['device' => '?', 'size' => '?', 'used' => '?', 'available' => '?', 'percent' => 0]];
}

function format_bytes($bytes) {
    $units = ['B', 'K', 'M', 'G', 'T'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), 1) . $units[$pow];
}

/**
 * Get load average - Linux only
 */
function get_load_average() {
    $cpu_count = 1;

    if (IS_LINUX) {
        $load = @sys_getloadavg();
        if ($load) {
            // Get CPU count
            if (file_exists('/proc/cpuinfo')) {
                $cpuinfo = @file_get_contents('/proc/cpuinfo');
                $cpu_count = substr_count($cpuinfo, 'processor');
            }
            if ($cpu_count < 1) $cpu_count = 1;

            return [
                'load_1m' => round($load[0], 2),
                'load_5m' => round($load[1], 2),
                'load_15m' => round($load[2], 2),
                'cpu_count' => $cpu_count,
                'load_per_cpu' => round($load[0] / $cpu_count, 2)
            ];
        }
    }

    if (IS_WINDOWS) {
        $output = safe_exec('wmic cpu get numberofcores /value 2>nul');
        if ($output && preg_match('/NumberOfCores=(\d+)/', $output, $m)) {
            $cpu_count = intval($m[1]);
        }
    }

    // Windows doesn't have load average concept
    return [
        'load_1m' => 0, 'load_5m' => 0, 'load_15m' => 0,
        'cpu_count' => $cpu_count, 'load_per_cpu' => 0,
        'note' => IS_WINDOWS ? 'N/A on Windows' : 'Unable to read'
    ];
}

/**
 * Get network stats - Linux focused
 */
function get_network_stats() {
    $stats = [];

    if (IS_LINUX && file_exists('/proc/net/dev')) {
        $output = @file_get_contents('/proc/net/dev');
        if ($output) {
            foreach (explode("\n", $output) as $line) {
                if (preg_match('/^\s*(eth\d+|ens\d+|enp\d+s\d+|wlan\d+|veth\w+):(.*)$/', $line, $matches)) {
                    $data = preg_split('/\s+/', trim($matches[2]));
                    if (count($data) >= 11) {
                        $stats[$matches[1]] = [
                            'rx_bytes' => intval($data[0]),
                            'tx_bytes' => intval($data[8]),
                            'rx_errors' => intval($data[2]),
                            'tx_errors' => intval($data[10]),
                            'total_errors' => intval($data[2]) + intval($data[10])
                        ];
                    }
                }
            }
        }
    }

    if (empty($stats)) {
        $stats['default'] = [
            'rx_bytes' => 0, 'tx_bytes' => 0, 'rx_errors' => 0,
            'tx_errors' => 0, 'total_errors' => 0,
            'note' => IS_WINDOWS ? 'N/A on Windows' : 'Unable to read'
        ];
    }

    return $stats;
}

/**
 * Check service status - Cross-platform
 */
function get_service_status($config) {
    $services_to_check = IS_LINUX ? $config['critical_services_linux'] : $config['critical_services_windows'];
    $status = [];

    foreach ($services_to_check as $service) {
        $running = false;

        if (IS_LINUX) {
            // Try pgrep
            $output = safe_exec("pgrep -x '$service' 2>/dev/null");
            if (!$running && !empty(trim($output ?? ''))) $running = true;

            // Try pgrep -f
            if (!$running) {
                $output = safe_exec("pgrep -f '$service' 2>/dev/null");
                if (!empty(trim($output ?? ''))) $running = true;
            }

            // Try systemctl
            if (!$running) {
                $output = safe_exec("systemctl is-active '$service' 2>/dev/null");
                if (trim($output ?? '') === 'active') $running = true;
            }
        }

        if (IS_WINDOWS) {
            $output = safe_exec("sc query \"$service\" 2>nul");
            if ($output && strpos($output, 'RUNNING') !== false) {
                $running = true;
            }
        }

        $status[$service] = $running;
    }

    return $status;
}

/**
 * Get uptime - Cross-platform
 */
function get_uptime() {
    $uptime = 0;

    if (IS_LINUX && file_exists('/proc/uptime')) {
        $content = @file_get_contents('/proc/uptime');
        if ($content) {
            $uptime = floatval(explode(' ', trim($content))[0]);
        }
    }

    if (IS_WINDOWS && $uptime <= 0) {
        $output = safe_exec('wmic os get lastbootuptime /value 2>nul');
        if ($output && preg_match('/LastBootUpTime=(\d{14})/', $output, $m)) {
            $boot = DateTime::createFromFormat('YmdHis', $m[1]);
            if ($boot) {
                $uptime = time() - $boot->getTimestamp();
            }
        }
    }

    $uptime_int = intval($uptime);
    $days = floor($uptime_int / 86400);
    $hours = floor(($uptime_int % 86400) / 3600);
    $minutes = floor(($uptime_int % 3600) / 60);

    return [
        'seconds' => $uptime,
        'formatted' => "{$days}d {$hours}h {$minutes}m"
    ];
}

// ============================================
// COLLECT ALL METRICS
// ============================================

$cpu = get_cpu_usage();
$results['metrics']['cpu'] = $cpu;

$memory = get_memory_usage();
$results['metrics']['memory'] = $memory;

$disks = get_disk_usage();
$results['metrics']['disk'] = $disks;

$load = get_load_average();
$results['metrics']['load'] = $load;

$network = get_network_stats();
$results['metrics']['network'] = $network;

$services = get_service_status($CONFIG);
$results['metrics']['services'] = $services;

$uptime = get_uptime();
$results['metrics']['uptime'] = $uptime;

// ============================================
// EVALUATE THRESHOLDS
// ============================================

$spike_counts = [];

// CPU Check (only if we got valid data)
if ($cpu['usage'] >= 0) {
    if ($cpu['usage'] >= $CONFIG['cpu_critical']) {
        if (should_alert('cpu', true, $spike_counts, $CONFIG['spike_tolerance'])) {
            $results['alerts'][] = "CPU CRITICAL: {$cpu['usage']}% (threshold: {$CONFIG['cpu_critical']}%)";
            $results['status'] = 'ALERT';
        }
    } elseif ($cpu['usage'] >= $CONFIG['cpu_warn']) {
        if (should_alert('cpu_warn', true, $spike_counts, $CONFIG['spike_tolerance'])) {
            $results['warnings'][] = "CPU HIGH: {$cpu['usage']}% (threshold: {$CONFIG['cpu_warn']}%)";
        }
    } else {
        should_alert('cpu', false, $spike_counts, $CONFIG['spike_tolerance']);
        should_alert('cpu_warn', false, $spike_counts, $CONFIG['spike_tolerance']);
    }
}

// I/O Wait Check (Linux only)
if (IS_LINUX && isset($cpu['iowait']) && $cpu['iowait'] >= 0) {
    if ($cpu['iowait'] >= $CONFIG['iowait_critical']) {
        if (should_alert('iowait', true, $spike_counts, $CONFIG['spike_tolerance'])) {
            $results['alerts'][] = "I/O WAIT CRITICAL: {$cpu['iowait']}% (threshold: {$CONFIG['iowait_critical']}%)";
            $results['status'] = 'ALERT';
        }
    } elseif ($cpu['iowait'] >= $CONFIG['iowait_warn']) {
        if (should_alert('iowait_warn', true, $spike_counts, $CONFIG['spike_tolerance'])) {
            $results['warnings'][] = "I/O WAIT HIGH: {$cpu['iowait']}% (threshold: {$CONFIG['iowait_warn']}%)";
        }
    } else {
        should_alert('iowait', false, $spike_counts, $CONFIG['spike_tolerance']);
        should_alert('iowait_warn', false, $spike_counts, $CONFIG['spike_tolerance']);
    }
}

// Memory Check
if ($memory['percent'] >= 0) {
    if ($memory['percent'] >= $CONFIG['mem_critical']) {
        if (should_alert('memory', true, $spike_counts, $CONFIG['spike_tolerance'])) {
            $results['alerts'][] = "MEMORY CRITICAL: {$memory['percent']}% ({$memory['used_mb']}MB / {$memory['total_mb']}MB)";
            $results['status'] = 'ALERT';
        }
    } elseif ($memory['percent'] >= $CONFIG['mem_warn']) {
        if (should_alert('memory_warn', true, $spike_counts, $CONFIG['spike_tolerance'])) {
            $results['warnings'][] = "MEMORY HIGH: {$memory['percent']}% ({$memory['used_mb']}MB / {$memory['total_mb']}MB)";
        }
    } else {
        should_alert('memory', false, $spike_counts, $CONFIG['spike_tolerance']);
        should_alert('memory_warn', false, $spike_counts, $CONFIG['spike_tolerance']);
    }
}

// Disk Check
foreach ($disks as $mount => $disk) {
    if ($disk['percent'] >= 0) {
        $key = 'disk_' . preg_replace('/[^a-z0-9]/i', '_', $mount);
        if ($disk['percent'] >= $CONFIG['disk_critical']) {
            if (should_alert($key, true, $spike_counts, $CONFIG['spike_tolerance'])) {
                $results['alerts'][] = "DISK CRITICAL: $mount at {$disk['percent']}% ({$disk['available']} free)";
                $results['status'] = 'ALERT';
            }
        } elseif ($disk['percent'] >= $CONFIG['disk_warn']) {
            if (should_alert($key . '_warn', true, $spike_counts, $CONFIG['spike_tolerance'])) {
                $results['warnings'][] = "DISK WARNING: $mount at {$disk['percent']}% ({$disk['available']} free)";
            }
        } else {
            should_alert($key, false, $spike_counts, $CONFIG['spike_tolerance']);
            should_alert($key . '_warn', false, $spike_counts, $CONFIG['spike_tolerance']);
        }
    }
}

// Load Check (Linux only)
if (IS_LINUX && $load['load_1m'] > 0) {
    $load_critical = $load['cpu_count'] * $CONFIG['load_critical_multiplier'];
    $load_warn = $load['cpu_count'] * $CONFIG['load_warn_multiplier'];

    if ($load['load_1m'] >= $load_critical) {
        if (should_alert('load', true, $spike_counts, $CONFIG['spike_tolerance'])) {
            $results['alerts'][] = "LOAD CRITICAL: {$load['load_1m']} (threshold: $load_critical for {$load['cpu_count']} CPUs)";
            $results['status'] = 'ALERT';
        }
    } elseif ($load['load_1m'] >= $load_warn) {
        if (should_alert('load_warn', true, $spike_counts, $CONFIG['spike_tolerance'])) {
            $results['warnings'][] = "LOAD HIGH: {$load['load_1m']} (threshold: $load_warn for {$load['cpu_count']} CPUs)";
        }
    } else {
        should_alert('load', false, $spike_counts, $CONFIG['spike_tolerance']);
        should_alert('load_warn', false, $spike_counts, $CONFIG['spike_tolerance']);
    }
}

// Network Errors Check (Linux only)
if (IS_LINUX) {
    foreach ($network as $iface => $stats) {
        if (!isset($stats['note'])) {
            $key = 'net_' . $iface;
            $prev_errors = $previous_state['metrics']['network'][$iface]['total_errors'] ?? 0;
            $new_errors = max(0, $stats['total_errors'] - $prev_errors);

            if ($new_errors >= $CONFIG['net_errors_critical']) {
                if (should_alert($key, true, $spike_counts, $CONFIG['spike_tolerance'])) {
                    $results['alerts'][] = "NETWORK ERRORS CRITICAL: $iface has $new_errors new errors";
                    $results['status'] = 'ALERT';
                }
            } elseif ($new_errors >= $CONFIG['net_errors_warn']) {
                if (should_alert($key . '_warn', true, $spike_counts, $CONFIG['spike_tolerance'])) {
                    $results['warnings'][] = "NETWORK ERRORS: $iface has $new_errors new errors";
                }
            } else {
                should_alert($key, false, $spike_counts, $CONFIG['spike_tolerance']);
                should_alert($key . '_warn', false, $spike_counts, $CONFIG['spike_tolerance']);
            }
        }
    }
}

// Service Check (immediate alert for down services)
foreach ($services as $service => $running) {
    if (!$running) {
        $results['alerts'][] = "SERVICE DOWN: $service is not running";
        $results['status'] = 'ALERT';
    }
}

$results['spike_counts'] = $spike_counts;

// ============================================
// SAVE STATE
// ============================================

$state_to_save = [
    'timestamp' => $results['timestamp'],
    'metrics' => $results['metrics'],
    'spike_counts' => $spike_counts
];
@file_put_contents($CONFIG['state_file'], json_encode($state_to_save));

// ============================================
// OUTPUT
// ============================================

if ($format === 'json') {
    header('Content-Type: application/json');
    echo $pretty ? json_encode($results, JSON_PRETTY_PRINT) : json_encode($results);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

echo "VPS Host Monitor - " . $results['hostname'] . " (" . $results['os'] . ")\n";
echo "==========================================================\n";
echo "Timestamp: " . $results['timestamp'] . "\n";
echo "Uptime: " . $uptime['formatted'] . "\n\n";

echo "STATUS: " . ($results['status'] === 'ALL_GOOD' ? "ALL_GOOD" : "ALERT") . "\n\n";

if (!empty($results['alerts'])) {
    echo "ALERTS:\n";
    echo "-------\n";
    foreach ($results['alerts'] as $alert) {
        echo "  [!] $alert\n";
    }
    echo "\n";
}

if (!empty($results['warnings'])) {
    echo "WARNINGS:\n";
    echo "---------\n";
    foreach ($results['warnings'] as $warning) {
        echo "  [~] $warning\n";
    }
    echo "\n";
}

echo "METRICS:\n";
echo "--------\n";
echo "CPU:      {$cpu['usage']}%";
if (IS_LINUX && isset($cpu['iowait'])) echo " (I/O wait: {$cpu['iowait']}%)";
echo "\n";
echo "Memory:   {$memory['percent']}% ({$memory['used_mb']}MB / {$memory['total_mb']}MB)\n";
if ($memory['swap_total_mb'] > 0) {
    echo "Swap:     {$memory['swap_percent']}% ({$memory['swap_used_mb']}MB / {$memory['swap_total_mb']}MB)\n";
}
if (IS_LINUX) {
    echo "Load:     {$load['load_1m']} / {$load['load_5m']} / {$load['load_15m']} ({$load['cpu_count']} CPUs)\n";
}
echo "\n";

echo "DISK:\n";
foreach ($disks as $mount => $disk) {
    echo "  $mount: {$disk['percent']}% ({$disk['available']} free)\n";
}
echo "\n";

echo "SERVICES:\n";
foreach ($services as $service => $running) {
    echo "  $service: " . ($running ? 'running' : 'DOWN') . "\n";
}
echo "\n";

if (IS_LINUX && !isset($network['default']['note'])) {
    echo "NETWORK:\n";
    foreach ($network as $iface => $stats) {
        $rx_mb = round($stats['rx_bytes'] / 1048576, 2);
        $tx_mb = round($stats['tx_bytes'] / 1048576, 2);
        echo "  $iface: RX {$rx_mb}MB / TX {$tx_mb}MB (errors: {$stats['total_errors']})\n";
    }
    echo "\n";
}

if (!empty($spike_counts)) {
    $active_spikes = array_filter($spike_counts, fn($c) => $c > 0);
    if (!empty($active_spikes)) {
        echo "SPIKE COUNTERS (tolerance: {$CONFIG['spike_tolerance']}):\n";
        foreach ($active_spikes as $metric => $count) {
            echo "  $metric: $count/{$CONFIG['spike_tolerance']}\n";
        }
    }
}
