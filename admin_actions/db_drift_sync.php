#!/usr/bin/env php
<?php
/**
 * Database Drift Sync Tool
 * ========================
 * Syncs missing records from source to target by comparing primary keys.
 * Uses SET sql_log_bin=0 on target to prevent replication cascade.
 *
 * Usage:
 *   php db_drift_sync.php                          # Dry run (default)
 *   php db_drift_sync.php --execute                # Actually insert records
 *   php db_drift_sync.php --table=bg_users         # Sync a different table
 *   php db_drift_sync.php --batch-size=250         # Adjust batch size
 *   php db_drift_sync.php --start-id=50000         # Resume from a specific ID
 *   php db_drift_sync.php --end-id=100000          # Stop at a specific ID
 *   php db_drift_sync.php --source-host=X          # Override source host
 *   php db_drift_sync.php --target-host=X          # Override target host
 *   php db_drift_sync.php --source-db=X            # Override source database
 *   php db_drift_sync.php --target-db=X            # Override target database
 *   php db_drift_sync.php --log=sync.log           # Log to file
 */

// ============================================================
// CONFIGURATION DEFAULTS
// ============================================================

$defaults = [
    'source' => [
        'host'     => 'july05.birthday.gold',
        'port'     => 3306,
        'database' => 'birthday_gold_www',
        'username' => 'bdgold_syncer',
        'password' => 'NScw1dDaEXgJ0psq',
    ],
    'target' => [
        'host'     => 'december26.bday.gold',
        'port'     => 3306,
        'database' => 'birthday_gold_www',
        'username' => 'bdgold_syncer',
        'password' => 'NScw1dDaEXgJ0psq',
    ],
    'table'              => 'bg_sessiontracking',
    'primary_key'        => 'id',
    'batch_size'         => 500,    // IDs per comparison batch
    'insert_chunk'       => 50,     // Rows per INSERT statement
    'sleep_ms'           => 100,    // Milliseconds between insert chunks
    'dry_run'            => true,
    'log_file'           => null,
    'start_id'           => null,
    'end_id'             => null,
];


// ============================================================
// CLI ARGUMENT PARSING
// ============================================================

$config = $defaults;

$opts = getopt('', [
    'execute',
    'table:',
    'batch-size:',
    'insert-chunk:',
    'sleep-ms:',
    'start-id:',
    'end-id:',
    'source-host:',
    'target-host:',
    'source-db:',
    'target-db:',
    'primary-key:',
    'log:',
    'help',
]);

if (isset($opts['help'])) {
    echo file_get_contents(__FILE__);
    exit(0);
}

if (isset($opts['execute']))        $config['dry_run']             = false;
if (isset($opts['table']))          $config['table']               = $opts['table'];
if (isset($opts['batch-size']))     $config['batch_size']          = (int)$opts['batch-size'];
if (isset($opts['insert-chunk']))   $config['insert_chunk']        = (int)$opts['insert-chunk'];
if (isset($opts['sleep-ms']))       $config['sleep_ms']            = (int)$opts['sleep-ms'];
if (isset($opts['start-id']))       $config['start_id']            = (int)$opts['start-id'];
if (isset($opts['end-id']))         $config['end_id']              = (int)$opts['end-id'];
if (isset($opts['source-host']))    $config['source']['host']      = $opts['source-host'];
if (isset($opts['target-host']))    $config['target']['host']      = $opts['target-host'];
if (isset($opts['source-db']))      $config['source']['database']  = $opts['source-db'];
if (isset($opts['target-db']))      $config['target']['database']  = $opts['target-db'];
if (isset($opts['primary-key']))    $config['primary_key']         = $opts['primary-key'];
if (isset($opts['log']))            $config['log_file']            = $opts['log'];


// ============================================================
// LOGGING & METRICS
// ============================================================

// Auto-generate log directory and run-specific log file
$log_dir = __DIR__ . '/sync_logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$run_id = date('Ymd_His') . '_' . $config['table'];
$auto_log_file = "$log_dir/{$run_id}.log";
$metrics_file  = "$log_dir/{$run_id}_metrics.json";
$resume_file   = "$log_dir/{$config['table']}_last_id.txt";

// Open log handles
$log_handle = fopen($auto_log_file, 'a');
$user_log_handle = null;

if ($config['log_file']) {
    $user_log_handle = fopen($config['log_file'], 'a');
    if (!$user_log_handle) {
        echo "ERROR: Could not open log file: {$config['log_file']}\n";
        exit(1);
    }
}

function logmsg($msg, $level = 'INFO') {
    global $log_handle, $user_log_handle;
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] [$level] $msg\n";
    echo $line;
    if ($log_handle) {
        fwrite($log_handle, $line);
        fflush($log_handle);
    }
    if ($user_log_handle) {
        fwrite($user_log_handle, $line);
        fflush($user_log_handle);
    }
}

// Metrics tracker
$metrics = [
    'run_id'            => $run_id,
    'started_at'        => date('c'),
    'finished_at'       => null,
    'table'             => $config['table'],
    'source'            => $config['source']['host'] . '/' . $config['source']['database'],
    'target'            => $config['target']['host'] . '/' . $config['target']['database'],
    'mode'              => $config['dry_run'] ? 'dry_run' : 'execute',
    'id_range_start'    => null,
    'id_range_end'      => null,
    'source_row_count'  => null,
    'target_row_count'  => null,
    'batches_processed' => 0,
    'total_missing'     => 0,
    'total_inserted'    => 0,
    'total_skipped'     => 0,
    'total_errors'      => 0,
    'errors'            => [],
    'elapsed_seconds'   => 0,
    'insert_rate_per_s' => 0,
    'scan_rate_ids_per_s' => 0,
    'peak_memory_mb'    => 0,
    'last_processed_id' => null,
    'checkpoints'       => [],
];

// Checkpoint interval (log detailed metrics every N batches)
$checkpoint_interval = 50;

function save_metrics() {
    global $metrics, $metrics_file;
    file_put_contents($metrics_file, json_encode($metrics, JSON_PRETTY_PRINT));
}

function save_resume_point($id) {
    global $resume_file, $config;
    file_put_contents($resume_file, $id . "\n" . date('c') . "\n" . $config['table']);
}

function format_eta($seconds) {
    if ($seconds <= 0) return 'calculating...';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    if ($h > 0) return sprintf('%dh %dm %ds', $h, $m, $s);
    if ($m > 0) return sprintf('%dm %ds', $m, $s);
    return sprintf('%ds', $s);
}

function format_number($n) {
    return number_format($n, 0, '.', ',');
}

function get_memory_mb() {
    return round(memory_get_usage(true) / 1024 / 1024, 1);
}

logmsg("Log file: $auto_log_file");
logmsg("Metrics:  $metrics_file");


// ============================================================
// BANNER
// ============================================================

logmsg("========================================");
logmsg("Database Drift Sync Tool");
logmsg("========================================");
logmsg("Table:       {$config['table']}");
logmsg("Primary Key: {$config['primary_key']}");
logmsg("Source:      {$config['source']['host']}:{$config['source']['port']}/{$config['source']['database']}");
logmsg("Target:      {$config['target']['host']}:{$config['target']['port']}/{$config['target']['database']}");
logmsg("Batch Size:  {$config['batch_size']} (comparison) / {$config['insert_chunk']} (insert)");
logmsg("Sleep:       {$config['sleep_ms']}ms between insert chunks");
logmsg("Mode:        " . ($config['dry_run'] ? "DRY RUN (no changes)" : "EXECUTE (will insert records)"));
if ($config['start_id'] !== null) logmsg("Start ID:    {$config['start_id']}");
if ($config['end_id'] !== null)   logmsg("End ID:      {$config['end_id']}");
logmsg("========================================");

if (!$config['dry_run']) {
    logmsg("WARNING: Execute mode is ON. Records WILL be inserted into the target.", 'WARN');
    logmsg("You have 5 seconds to cancel (Ctrl+C)...", 'WARN');
    sleep(5);
}


// ============================================================
// DATABASE CONNECTIONS
// ============================================================

function connect_db($label, $cfg) {
    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);
        logmsg("Connected to $label: {$cfg['host']}:{$cfg['port']}/{$cfg['database']}");
        return $pdo;
    } catch (PDOException $e) {
        logmsg("FATAL: Could not connect to $label: " . $e->getMessage(), 'ERROR');
        exit(1);
    }
}

$source = connect_db('SOURCE', $config['source']);
$target = connect_db('TARGET', $config['target']);

// Disable binary logging on the target connection
try {
    $target->exec("SET sql_log_bin = 0");
    logmsg("Disabled binary logging on target connection (sql_log_bin=0)");
} catch (PDOException $e) {
    logmsg("WARNING: Could not disable sql_log_bin: " . $e->getMessage(), 'WARN');
    logmsg("This may require SUPER or REPLICATION_CLIENT privilege.", 'WARN');
    logmsg("Proceeding anyway - but inserts MAY replicate.", 'WARN');
}


// ============================================================
// SCHEMA DISCOVERY
// ============================================================

function get_table_columns($pdo, $table, $database) {
    $stmt = $pdo->prepare(
        "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl
         ORDER BY ORDINAL_POSITION"
    );
    $stmt->execute(['db' => $database, 'tbl' => $table]);
    return $stmt->fetchAll();
}

$source_cols = get_table_columns($source, $config['table'], $config['source']['database']);
$target_cols = get_table_columns($target, $config['table'], $config['target']['database']);

if (empty($source_cols)) {
    logmsg("FATAL: Table '{$config['table']}' not found in source database.", 'ERROR');
    exit(1);
}
if (empty($target_cols)) {
    logmsg("FATAL: Table '{$config['table']}' not found in target database.", 'ERROR');
    exit(1);
}

$source_col_names = array_column($source_cols, 'COLUMN_NAME');
$target_col_names = array_column($target_cols, 'COLUMN_NAME');

// Use only columns that exist in BOTH source and target
$common_cols = array_values(array_intersect($source_col_names, $target_col_names));
$source_only = array_diff($source_col_names, $target_col_names);
$target_only = array_diff($target_col_names, $source_col_names);

logmsg("Source columns: " . count($source_col_names) . " | Target columns: " . count($target_col_names) . " | Common: " . count($common_cols));

if (!empty($source_only)) {
    logmsg("Columns in SOURCE only (will be skipped): " . implode(', ', $source_only), 'WARN');
}
if (!empty($target_only)) {
    logmsg("Columns in TARGET only (will use defaults): " . implode(', ', $target_only), 'WARN');
}

if (!in_array($config['primary_key'], $common_cols)) {
    logmsg("FATAL: Primary key '{$config['primary_key']}' not found in common columns.", 'ERROR');
    exit(1);
}

$pk = $config['primary_key'];
$table = $config['table'];
$quoted_cols = array_map(function($c) { return "`$c`"; }, $common_cols);
$select_cols = implode(', ', $quoted_cols);


// ============================================================
// DETERMINE ID RANGE
// ============================================================

$source_range = $source->query("SELECT MIN(`$pk`) as min_id, MAX(`$pk`) as max_id, COUNT(*) as total FROM `$table`")->fetch();
$target_range = $target->query("SELECT MIN(`$pk`) as min_id, MAX(`$pk`) as max_id, COUNT(*) as total FROM `$table`")->fetch();

logmsg("Source: " . format_number($source_range['total']) . " rows, ID range [{$source_range['min_id']} - {$source_range['max_id']}]");
logmsg("Target: " . format_number($target_range['total']) . " rows, ID range [{$target_range['min_id']} - {$target_range['max_id']}]");
logmsg("Approximate missing: " . format_number($source_range['total'] - $target_range['total']) . " rows");

$start_id = $config['start_id'] ?? (int)$source_range['min_id'];
$end_id   = $config['end_id']   ?? (int)$source_range['max_id'];

logmsg("Scanning ID range: [$start_id - $end_id]");

$metrics['source_row_count'] = (int)$source_range['total'];
$metrics['target_row_count'] = (int)$target_range['total'];
$metrics['id_range_start']   = $start_id;
$metrics['id_range_end']     = $end_id;


// ============================================================
// SYNC LOOP
// ============================================================

$total_missing  = 0;
$total_inserted = 0;
$total_skipped  = 0;
$total_errors   = 0;
$batch_num      = 0;
$scan_start     = microtime(true);
$last_checkpoint_time = $scan_start;
$checkpoint_missing   = 0;
$checkpoint_inserted  = 0;

$current_id = $start_id;

while ($current_id <= $end_id) {
    $batch_num++;
    $batch_start_time = microtime(true);
    $batch_end = min($current_id + $config['batch_size'] - 1, $end_id);

    // Get IDs that exist in source for this range
    $stmt = $source->prepare(
        "SELECT `$pk` FROM `$table` WHERE `$pk` >= :start AND `$pk` <= :end ORDER BY `$pk`"
    );
    $stmt->execute(['start' => $current_id, 'end' => $batch_end]);
    $source_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($source_ids)) {
        $current_id = $batch_end + 1;
        continue;
    }

    // Get IDs that exist in target for this range
    $stmt = $target->prepare(
        "SELECT `$pk` FROM `$table` WHERE `$pk` >= :start AND `$pk` <= :end ORDER BY `$pk`"
    );
    $stmt->execute(['start' => $current_id, 'end' => $batch_end]);
    $target_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Find IDs missing from target
    $missing_ids = array_values(array_diff($source_ids, $target_ids));
    $batch_missing = count($missing_ids);
    $batch_inserted = 0;

    if ($batch_missing > 0) {
        $total_missing += $batch_missing;
        $checkpoint_missing += $batch_missing;

        $pct = round(($current_id - $start_id) / max(1, ($end_id - $start_id)) * 100, 1);
        logmsg("Batch #$batch_num [ID $current_id-$batch_end] ({$pct}%): $batch_missing missing IDs found");

        if (!$config['dry_run']) {
            // Fetch and insert in chunks
            $chunks = array_chunk($missing_ids, $config['insert_chunk']);

            foreach ($chunks as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));

                // Fetch full records from source
                $fetch_sql = "SELECT $select_cols FROM `$table` WHERE `$pk` IN ($placeholders)";
                $stmt = $source->prepare($fetch_sql);
                $stmt->execute($chunk);
                $rows = $stmt->fetchAll();

                if (empty($rows)) continue;

                // Build multi-row INSERT
                $value_placeholders = [];
                $insert_values = [];

                foreach ($rows as $row) {
                    $row_ph = [];
                    foreach ($common_cols as $col) {
                        $row_ph[] = '?';
                        $insert_values[] = $row[$col];
                    }
                    $value_placeholders[] = '(' . implode(',', $row_ph) . ')';
                }

                $insert_sql = "INSERT IGNORE INTO `$table` (" . implode(',', $quoted_cols) . ") VALUES "
                            . implode(', ', $value_placeholders);

                try {
                    $stmt = $target->prepare($insert_sql);
                    $stmt->execute($insert_values);
                    $affected = $stmt->rowCount();
                    $batch_inserted += $affected;
                    $total_inserted += $affected;
                    $checkpoint_inserted += $affected;

                    $skipped = count($rows) - $affected;
                    if ($skipped > 0) {
                        $total_skipped += $skipped;
                        logmsg("  Chunk: sent " . count($rows) . " rows, inserted $affected, skipped $skipped (already existed)", 'WARN');
                    }
                } catch (PDOException $e) {
                    $total_errors++;
                    $err_detail = [
                        'batch'   => $batch_num,
                        'ids'     => implode(',', $chunk),
                        'message' => $e->getMessage(),
                        'time'    => date('c'),
                    ];
                    $metrics['errors'][] = $err_detail;
                    logmsg("  INSERT ERROR: " . $e->getMessage(), 'ERROR');
                    logmsg("  Failed chunk IDs: " . implode(',', $chunk), 'ERROR');
                }

                // Throttle
                if ($config['sleep_ms'] > 0) {
                    usleep($config['sleep_ms'] * 1000);
                }
            }

            if ($batch_inserted > 0) {
                logmsg("  -> Inserted $batch_inserted rows for this batch");
            }
        }
    }

    // Track last processed ID for resume
    $metrics['last_processed_id'] = $batch_end;

    // Periodic checkpoint metrics
    if ($batch_num % $checkpoint_interval === 0) {
        $now = microtime(true);
        $total_elapsed = $now - $scan_start;
        $checkpoint_elapsed = $now - $last_checkpoint_time;
        $ids_scanned = $batch_end - $start_id;
        $ids_remaining = $end_id - $batch_end;

        // Rates
        $scan_rate = $ids_scanned / max(0.001, $total_elapsed);
        $eta_seconds = ($scan_rate > 0) ? (int)($ids_remaining / $scan_rate) : 0;
        $insert_rate = ($total_elapsed > 0) ? round($total_inserted / $total_elapsed, 1) : 0;
        $checkpoint_rate = ($checkpoint_elapsed > 0) ? round($checkpoint_inserted / $checkpoint_elapsed, 1) : 0;

        $pct = round($ids_scanned / max(1, ($end_id - $start_id)) * 100, 1);
        $mem = get_memory_mb();

        logmsg("--- CHECKPOINT [Batch #$batch_num | {$pct}% | ID $batch_end] ---");
        logmsg("  Elapsed: " . format_eta((int)$total_elapsed) . " | ETA: " . format_eta($eta_seconds));
        logmsg("  Scan rate: " . format_number((int)$scan_rate) . " IDs/s");
        logmsg("  Missing found: " . format_number($total_missing) . " total, $checkpoint_missing since last checkpoint");
        if (!$config['dry_run']) {
            logmsg("  Inserted: " . format_number($total_inserted) . " total ($insert_rate/s avg, $checkpoint_rate/s current)");
            logmsg("  Skipped: " . format_number($total_skipped) . " | Errors: $total_errors");
        }
        logmsg("  Memory: {$mem}MB | Peak: " . round(memory_get_peak_usage(true) / 1024 / 1024, 1) . "MB");

        // Record checkpoint
        $metrics['checkpoints'][] = [
            'batch'             => $batch_num,
            'id'                => $batch_end,
            'pct'               => $pct,
            'elapsed_s'         => round($total_elapsed, 1),
            'eta_s'             => $eta_seconds,
            'total_missing'     => $total_missing,
            'total_inserted'    => $total_inserted,
            'checkpoint_missing'=> $checkpoint_missing,
            'checkpoint_inserted'=> $checkpoint_inserted,
            'scan_rate'         => round($scan_rate),
            'insert_rate'       => $insert_rate,
            'memory_mb'         => $mem,
            'time'              => date('c'),
        ];

        // Reset checkpoint counters
        $checkpoint_missing  = 0;
        $checkpoint_inserted = 0;
        $last_checkpoint_time = $now;

        // Save metrics & resume point periodically
        $metrics['batches_processed'] = $batch_num;
        $metrics['total_missing']     = $total_missing;
        $metrics['total_inserted']    = $total_inserted;
        $metrics['total_skipped']     = $total_skipped;
        $metrics['total_errors']      = $total_errors;
        $metrics['peak_memory_mb']    = round(memory_get_peak_usage(true) / 1024 / 1024, 1);
        save_metrics();
        save_resume_point($batch_end);

    } else if ($batch_missing == 0 && $batch_num % 100 === 0) {
        // Lightweight progress line when nothing is missing
        $pct = round(($current_id - $start_id) / max(1, ($end_id - $start_id)) * 100, 1);
        logmsg("Batch #$batch_num [ID $current_id-$batch_end] ({$pct}%): in sync");
    }

    $current_id = $batch_end + 1;
}


// ============================================================
// POST-SYNC VERIFICATION
// ============================================================

if (!$config['dry_run'] && $total_inserted > 0) {
    logmsg("Running post-sync row count verification...");
    $target_count_after = $target->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    $net_change = $target_count_after - $metrics['target_row_count'];
    logmsg("Target row count: " . format_number($metrics['target_row_count']) . " -> " . format_number($target_count_after) . " (net +" . format_number($net_change) . ")");
    $metrics['target_row_count_after'] = (int)$target_count_after;
    $metrics['net_rows_added'] = $net_change;
}


// ============================================================
// FINAL SUMMARY
// ============================================================

$elapsed = round(microtime(true) - $scan_start, 2);
$insert_rate = ($elapsed > 0 && $total_inserted > 0) ? round($total_inserted / $elapsed, 1) : 0;
$scan_rate = ($elapsed > 0) ? round(($end_id - $start_id) / $elapsed) : 0;

logmsg("========================================");
logmsg("SYNC COMPLETE");
logmsg("========================================");
logmsg("Run ID:          $run_id");
logmsg("Table:           {$config['table']}");
logmsg("ID Range:        " . format_number($start_id) . " - " . format_number($end_id));
logmsg("Batches:         " . format_number($batch_num));
logmsg("Missing Found:   " . format_number($total_missing));
if (!$config['dry_run']) {
    logmsg("Rows Inserted:   " . format_number($total_inserted) . " ($insert_rate/s avg)");
    logmsg("Rows Skipped:    " . format_number($total_skipped) . " (INSERT IGNORE duplicates)");
}
logmsg("Errors:          $total_errors");
logmsg("Elapsed:         " . format_eta((int)$elapsed) . " ($elapsed s)");
logmsg("Scan Rate:       " . format_number($scan_rate) . " IDs/s");
logmsg("Peak Memory:     " . round(memory_get_peak_usage(true) / 1024 / 1024, 1) . "MB");
logmsg("Mode:            " . ($config['dry_run'] ? "DRY RUN" : "EXECUTE"));
logmsg("Log File:        $auto_log_file");
logmsg("Metrics File:    $metrics_file");
logmsg("========================================");

if ($config['dry_run'] && $total_missing > 0) {
    logmsg("To actually insert these records, run again with --execute");
    logmsg("To resume from last position: --start-id=" . ($metrics['last_processed_id'] ?? $start_id));
}

// Finalize metrics
$metrics['finished_at']         = date('c');
$metrics['batches_processed']   = $batch_num;
$metrics['total_missing']       = $total_missing;
$metrics['total_inserted']      = $total_inserted;
$metrics['total_skipped']       = $total_skipped;
$metrics['total_errors']        = $total_errors;
$metrics['elapsed_seconds']     = $elapsed;
$metrics['insert_rate_per_s']   = $insert_rate;
$metrics['scan_rate_ids_per_s'] = $scan_rate;
$metrics['peak_memory_mb']      = round(memory_get_peak_usage(true) / 1024 / 1024, 1);
save_metrics();
save_resume_point($end_id);

logmsg("Final metrics saved to: $metrics_file");

// Cleanup
$source = null;
$target = null;
if ($log_handle) fclose($log_handle);
if ($user_log_handle) fclose($user_log_handle);

exit($total_errors > 0 ? 1 : 0);
