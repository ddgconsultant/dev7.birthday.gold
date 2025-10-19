<?php
/**
 * ErrorFixer Class
 * Automated error detection, analysis, and fixing system using AI
 *
 * Part of Birthday.Gold Auto Error Fixer System
 * Works with scheduler--auto-error-fixer.php
 */

class ErrorFixer {
    private $database;
    private $ai;
    private $system;
    private $config = [];
    private $log_path;

    /**
     * Constructor
     */
    public function __construct($database, $ai, $system) {
        $this->database = $database;
        $this->ai = $ai;
        $this->system = $system;
        $this->loadConfig();
        $this->determineLogPath();
    }

    /**
     * Load configuration from bg_config
     */
    private function loadConfig() {
        $sql = "SELECT config_key, config_value
                FROM bg_config
                WHERE config_type = 'auto_error_fixer'
                AND status = '1'";

        $stmt = $this->database->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->config[$row['config_key']] = $row['config_value'];
        }
    }

    /**
     * Determine PHP error log path using same logic as log-viewer.php
     */
    private function determineLogPath() {
        // Try to get from log viewer config first
        $sql = "SELECT config_value FROM bg_config
                WHERE config_type LIKE 'log_viewer_%'
                AND config_key = 'PHP_ERROR_LOG'
                AND status = '1'
                LIMIT 1";

        $stmt = $this->database->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->log_path = $this->resolveLogPath($row['config_value']);
        } else {
            // Fallback to default
            $this->log_path = $this->resolveLogPath('../_logs_/dev7_PHP_errors.log');
        }
    }

    /**
     * Resolve relative log paths (from log-viewer.php)
     */
    private function resolveLogPath($path) {
        if (file_exists($path)) {
            return $path;
        }

        // Try relative to DOCUMENT_ROOT
        if (!preg_match('/^([A-Z]:)?[\/\\\\]/', $path)) {
            $resolved = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/\\');
            if (file_exists($resolved)) {
                return $resolved;
            }

            $resolved = dirname($_SERVER['DOCUMENT_ROOT']) . '/' . ltrim($path, '/\\');
            if (file_exists($resolved)) {
                return $resolved;
            }
        }

        return $path;
    }

    /**
     * Get configuration value
     */
    public function getConfig($key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get last run timestamp
     */
    public function getLastRunTimestamp() {
        return $this->config['last_run_timestamp'] ?? '2025-01-01 00:00:00';
    }

    /**
     * Update last run timestamp
     */
    public function updateLastRunTimestamp() {
        $sql = "UPDATE bg_config
                SET config_value = :timestamp
                WHERE config_type = 'auto_error_fixer'
                AND config_key = 'last_run_timestamp'";

        $this->database->query($sql, ['timestamp' => date('Y-m-d H:i:s')]);
    }

    /**
     * Reset last run timestamp to a specific date
     */
    public function resetLastRunTimestamp($date = '2025-01-01 00:00:00') {
        $sql = "UPDATE bg_config
                SET config_value = :timestamp
                WHERE config_type = 'auto_error_fixer'
                AND config_key = 'last_run_timestamp'";

        $this->database->query($sql, ['timestamp' => $date]);
    }

    /**
     * STEP 1: Apply approved fixes
     */
    public function applyApprovedFixes() {
        $applied = [];
        $max_fixes = intval($this->getConfig('max_fixes_per_run', 10));

        $sql = "SELECT * FROM bg_auto_error_fixes
                WHERE fix_status = 'approved_pending_apply'
                ORDER BY reviewed_dt ASC
                LIMIT " . $max_fixes;

        $stmt = $this->database->query($sql);

        while ($fix = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result = $this->applySingleFix($fix);
            $applied[] = array_merge($fix, $result);
        }

        return $applied;
    }

    /**
     * Apply a single fix to a file
     */
    private function applySingleFix($fix) {
        $fix_id = $fix['fix_id'];
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($fix['error_file'], '/');

        $result = [
            'success' => false,
            'error' => null
        ];

        try {
            // Read current file
            if (!file_exists($file_path)) {
                throw new Exception("File not found: {$file_path}");
            }

            $current_content = file_get_contents($file_path);

            // Backup original code
            $backup_file = $file_path . '.errorfixer_backup_' . time();
            file_put_contents($backup_file, $current_content);

            // Read file as lines
            $lines = file($file_path);
            $line_start = $fix['line_start'] - 1; // 0-indexed
            $line_end = $fix['line_end'] - 1;

            // Replace the problematic section
            $new_lines = array_merge(
                array_slice($lines, 0, $line_start),
                [$fix['proposed_fix'] . "\n"],
                array_slice($lines, $line_end + 1)
            );

            $new_content = implode('', $new_lines);

            // Write new content
            file_put_contents($file_path, $new_content);

            // Syntax check
            $syntax_check = $this->checkSyntax($file_path);

            if (!$syntax_check['valid']) {
                // Rollback
                file_put_contents($file_path, $current_content);
                unlink($backup_file);
                throw new Exception("Syntax check failed: {$syntax_check['error']}");
            }

            // Git commit
            $commit_hash = $this->gitCommitFix($fix);

            // Update database
            $update_sql = "UPDATE bg_auto_error_fixes
                          SET fix_status = 'applied',
                              applied_dt = NOW(),
                              applied_by = 'auto_scheduler',
                              git_commit_hash = :commit_hash,
                              syntax_check_passed = 1
                          WHERE fix_id = :fix_id";

            $this->database->query($update_sql, [
                'commit_hash' => $commit_hash,
                'fix_id' => $fix_id
            ]);

            // Clean up backup
            unlink($backup_file);

            $result['success'] = true;
            $result['commit_hash'] = $commit_hash;

        } catch (Exception $e) {
            // Update database with error
            $update_sql = "UPDATE bg_auto_error_fixes
                          SET fix_status = 'failed_to_apply',
                              apply_error_message = :error
                          WHERE fix_id = :fix_id";

            $this->database->query($update_sql, [
                'error' => $e->getMessage(),
                'fix_id' => $fix_id
            ]);

            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Check PHP syntax of a file
     */
    private function checkSyntax($file_path) {
        $output = [];
        $return_var = 0;

        exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_var);

        return [
            'valid' => $return_var === 0,
            'error' => $return_var !== 0 ? implode("\n", $output) : null
        ];
    }

    /**
     * Create git commit for fix
     */
    private function gitCommitFix($fix) {
        $file = $fix['error_file'];
        $type = $fix['ai_fix_type'];
        $line = $fix['error_line'];

        $commit_msg = "AUTO-FIX: {$file}:{$line} - {$type}\n\n";
        $commit_msg .= "Fix ID: #{$fix['fix_id']}\n";
        $commit_msg .= "Error: {$fix['error_message']}\n";
        $commit_msg .= "AI Confidence: {$fix['ai_confidence']}%\n\n";
        $commit_msg .= "🤖 Generated with Auto Error Fixer\nReviewed-By: User #{$fix['reviewed_by']}";

        $doc_root = $_SERVER['DOCUMENT_ROOT'];

        // Git add
        exec("cd " . escapeshellarg($doc_root) . " && git add " . escapeshellarg($file), $output, $return_var);

        // Git commit
        $commit_cmd = "cd " . escapeshellarg($doc_root) . " && git commit --no-verify -m " . escapeshellarg($commit_msg);
        exec($commit_cmd, $output, $return_var);

        // Get commit hash
        exec("cd " . escapeshellarg($doc_root) . " && git rev-parse HEAD", $hash_output);

        return $hash_output[0] ?? 'unknown';
    }

    /**
     * STEP 2: Find new errors from log(s)
     */
    public function findNewErrors($since_timestamp) {
        $all_errors = [];
        $since_ts = strtotime($since_timestamp);

        // Get all log file paths from config
        $log_paths = $this->getLogPaths();

        foreach ($log_paths as $log_info) {
            $log_path = $log_info['path'];
            $host = $log_info['host'];

            if (!file_exists($log_path)) {
                continue;
            }

            $log_content = file_get_contents($log_path);
            $errors = $this->parseErrorLog($log_content, $since_ts, $host);

            // Merge errors
            foreach ($errors as $error) {
                $hash = $error['hash'];
                if (!isset($all_errors[$hash])) {
                    $all_errors[$hash] = $error;
                } else {
                    // Update if this occurrence is more recent
                    if (strtotime($error['last_seen']) > strtotime($all_errors[$hash]['last_seen'])) {
                        $all_errors[$hash]['last_seen'] = $error['last_seen'];
                    }
                    $all_errors[$hash]['count'] += $error['count'];
                }
            }
        }

        return array_values($all_errors);
    }

    /**
     * Get all log file paths from configuration
     */
    private function getLogPaths() {
        $paths = [];

        // Primary log path (from determineLogPath)
        if ($this->log_path && file_exists($this->log_path)) {
            $paths[] = [
                'path' => $this->log_path,
                'host' => 'dev7' // Default host
            ];
        }

        // Check for additional log paths in config
        $sql = "SELECT config_key, config_value, config_type
                FROM bg_config
                WHERE config_type LIKE 'log_viewer%'
                AND config_key = 'PHP_ERROR_LOG'
                AND status = '1'";

        $stmt = $this->database->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Extract host from config_type (e.g., log_viewer_env_production -> production)
            preg_match('/log_viewer_env_(.+)/', $row['config_type'], $matches);
            $host = $matches[1] ?? 'unknown';

            $log_path = $this->resolveLogPath($row['config_value']);

            if (file_exists($log_path)) {
                // Avoid duplicates
                $already_added = false;
                foreach ($paths as $existing) {
                    if ($existing['path'] === $log_path) {
                        $already_added = true;
                        break;
                    }
                }

                if (!$already_added) {
                    $paths[] = [
                        'path' => $log_path,
                        'host' => $host
                    ];
                }
            }
        }

        return $paths;
    }

    /**
     * Parse PHP error log
     */
    private function parseErrorLog($content, $since_timestamp, $host = 'unknown') {
        $errors = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $error_date = null;
            $error_type = null;
            $error_message = null;
            $error_file = null;
            $error_line = null;

            // Pattern 1: Standard PHP error format (including PHP Error [N]:)
            // [DD-Mon-YYYY HH:MM:SS ZONE] PHP Type: message in /path/file.php on line NN
            // [DD-Mon-YYYY HH:MM:SS ZONE] PHP Error [2]: message in /path/file.php on line NN
            if (preg_match('/\[([\d\-\w]+\s+[\d:]+)\s+[^\]]*\]\s+PHP\s+(.*?):\s+(.*?)\s+in\s+(.*?)\s+on\s+line\s+(\d+)/', $line, $matches)) {
                $error_date = $matches[1];
                $error_type = trim($matches[2]);
                $error_message = trim($matches[3]);
                $error_file = trim($matches[4]);
                // Normalize Windows paths
                $error_file = str_replace('\\', '/', $error_file);
                // Remove various path prefixes
                $error_file = preg_replace('#^W:/BIRTHDAY_SERVER/#', '', $error_file);
                $error_file = preg_replace('#^/mnt/w/BIRTHDAY_SERVER/#', '', $error_file);
                $error_file = preg_replace('#^mnt/w/BIRTHDAY_SERVER/#', '', $error_file);
                $error_line = intval($matches[5]);
            }
            // Pattern 2: Custom error_log format with file path
            // [DD-Mon-YYYY HH:MM:SS ZONE] Error message with W:\BIRTHDAY_SERVER\path\to\file.php
            elseif (preg_match('/\[([\d\-\w]+\s+[\d:]+)\s+[^\]]*\]\s+(.*?)(?:W:\\\\BIRTHDAY_SERVER\\\\|\/mnt\/w\/BIRTHDAY_SERVER\/)(.*?\.php)/', $line, $matches)) {
                $error_date = $matches[1];
                $error_message = trim($matches[2]);
                $error_file = str_replace('\\', '/', $matches[3]); // Normalize path
                $error_type = 'Application Error';
                $error_line = 1; // Default line since custom logs do not always include line numbers
            }
            // Pattern 3: PHP Error with Windows path and line number
            // [DD-Mon-YYYY HH:MM:SS] Error [N]: message in W:\BIRTHDAY_SERVER\path\file.php:LINE
            elseif (preg_match('/\[([\d\-\w]+\s+[\d:]+)\s+[^\]]*\]\s+Error\s+\[(\d+)\]:\s+(.*?)\s+in\s+(?:W:\\\\BIRTHDAY_SERVER\\\\|\/mnt\/w\/BIRTHDAY_SERVER\/)(.*?\.php):(\d+)/', $line, $matches)) {
                $error_date = $matches[1];
                $error_type = 'Error [' . $matches[2] . ']';
                $error_message = trim($matches[3]);
                $error_file = str_replace('\\', '/', $matches[4]);
                $error_line = intval($matches[5]);
            }
            // Pattern 4: Custom error_log with SQLSTATE errors
            // [DD-Mon-YYYY HH:MM:SS ZONE] Error processing user 123: SQLSTATE[...]: message
            elseif (preg_match('/\[([\d\-\w]+\s+[\d:]+)\s+[^\]]*\]\s+(.+SQLSTATE.+)/', $line, $matches)) {
                $error_date = $matches[1];
                $error_message = trim($matches[2]);
                $error_type = 'Database Error';
                $error_file = 'unknown'; // Cannot determine file from this format
                $error_line = 1;
            }

            // If we matched an error pattern, process it
            if ($error_date && $error_message) {
                // Convert error date to timestamp
                $error_ts = strtotime($error_date);

                // Skip if older than cutoff
                if ($error_ts < $since_timestamp) {
                    continue;
                }

                // Normalize file path (remove document root)
                if ($error_file && $error_file !== 'unknown') {
                    $doc_root = $_SERVER['DOCUMENT_ROOT'];
                    if (strpos($error_file, $doc_root) === 0) {
                        $error_file = substr($error_file, strlen($doc_root));
                    }
                    $error_file = ltrim($error_file, '/');
                }

                // Check blacklist
                if ($this->isBlacklisted($error_file, $error_message)) {
                    continue;
                }

                // Generate hash
                $hash = $this->generateErrorHash($error_file, $error_line, $error_type, $error_message);

                // Check if already exists
                if (!isset($errors[$hash])) {
                    $errors[$hash] = [
                        'hash' => $hash,
                        'file' => $error_file,
                        'line' => $error_line,
                        'type' => $error_type,
                        'message' => $error_message,
                        'host' => $host,
                        'first_seen' => date('Y-m-d H:i:s', $error_ts),
                        'last_seen' => date('Y-m-d H:i:s', $error_ts),
                        'count' => 1
                    ];
                } else {
                    $errors[$hash]['last_seen'] = date('Y-m-d H:i:s', $error_ts);
                    $errors[$hash]['count']++;
                }
            }
        }

        return array_values($errors);
    }

    /**
     * Generate unique error hash
     */
    private function generateErrorHash($file, $line, $type, $message) {
        // Normalize message (remove dynamic parts)
        $normalized = preg_replace('/\d+/', 'N', $message); // Replace numbers
        $normalized = preg_replace('/\'[^\']*\'/', 'X', $normalized); // Replace quoted strings

        return md5($file . ':' . $line . ':' . $type . ':' . $normalized);
    }

    /**
     * Check if error matches blacklist
     */
    private function isBlacklisted($file, $message) {
        $sql = "SELECT pattern FROM bg_auto_error_fixer_blacklist
                WHERE status = 'active'";

        $stmt = $this->database->query($sql);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pattern = $row['pattern'];

            // Convert SQL LIKE pattern to regex
            $regex_pattern = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';

            if (preg_match($regex_pattern, $file) || preg_match($regex_pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * STEP 3: Develop fixes using AI
     */
    public function developFixes($errors, $max_count = 5) {
        $fixes = [];
        $processed = 0;

        foreach ($errors as $error) {
            if ($processed >= $max_count) {
                break;
            }

            // Check if this error hash already exists in database
            $check_sql = "SELECT fix_id FROM bg_auto_error_fixes WHERE error_hash = :hash";
            $stmt = $this->database->query($check_sql, ['hash' => $error['hash']]);

            if ($stmt->fetch()) {
                // Update occurrence count
                $update_sql = "UPDATE bg_auto_error_fixes
                              SET last_seen = :last_seen,
                                  occurrence_count = occurrence_count + :count
                              WHERE error_hash = :hash";

                $this->database->query($update_sql, [
                    'last_seen' => $error['last_seen'],
                    'count' => $error['count'],
                    'hash' => $error['hash']
                ]);

                continue; // Skip AI analysis
            }

            // Get code context
            $context = $this->getCodeContext($error['file'], $error['line']);

            // Generate review token regardless
            $review_token = bin2hex(random_bytes(32));

            // Default values if AI analysis fails or context not available
            $ai_result = null;
            $fix_status = 'needs_manual_review';
            $review_reason = 'Unable to analyze automatically';

            if (!$context) {
                // File not readable - still record the error
                $review_reason = 'File not readable or does not exist';
            } else {
                // Call AI to analyze
                $ai_result = $this->analyzeWithAI($error, $context);

                if ($ai_result) {
                    $fix_status = $ai_result['fixable'] ? 'pending_review' : 'auto_ignored';
                    $review_reason = $ai_result['review_reason'] ?? null;
                } else {
                    // AI analysis failed - still record the error
                    $review_reason = 'AI analysis failed or returned invalid response';
                }
            }

            // Store in database regardless of AI result
            $insert_sql = "INSERT INTO bg_auto_error_fixes
                          (error_hash, error_file, error_line, error_message, error_type,
                           error_context, first_seen, last_seen, occurrence_count,
                           fix_status, ai_analyzed_dt, ai_model, ai_confidence, ai_fix_type,
                           ai_explanation, ai_review_reason, original_code, proposed_fix,
                           line_start, line_end, review_token)
                          VALUES
                          (:hash, :file, :line, :message, :type,
                           :context, :first_seen, :last_seen, :count,
                           :status, NOW(), :model, :confidence, :fix_type,
                           :explanation, :review_reason, :original_code, :proposed_fix,
                           :line_start, :line_end, :token)";

            $this->database->query($insert_sql, [
                'hash' => $error['hash'],
                'file' => $error['file'],
                'line' => $error['line'],
                'message' => $error['message'],
                'type' => $error['type'],
                'context' => $context ? $context['full_context'] : null,
                'first_seen' => $error['first_seen'],
                'last_seen' => $error['last_seen'],
                'count' => $error['count'],
                'status' => $fix_status,
                'model' => $ai_result ? ($ai_result['model'] ?? 'unknown') : null,
                'confidence' => $ai_result ? ($ai_result['confidence'] ?? 0) : 0,
                'fix_type' => $ai_result ? ($ai_result['fix_type'] ?? null) : null,
                'explanation' => $ai_result ? ($ai_result['explanation'] ?? null) : null,
                'review_reason' => $review_reason,
                'original_code' => $context ? $context['problem_code'] : null,
                'proposed_fix' => $ai_result ? ($ai_result['fixed_code'] ?? null) : null,
                'line_start' => $context ? $context['line_start'] : $error['line'],
                'line_end' => $context ? $context['line_end'] : $error['line'],
                'token' => $review_token
            ]);

            $fix_id = $this->database->lastInsertId();

            // Create fix array
            $fix_entry = array_merge($error, [
                'fix_id' => $fix_id,
                'review_token' => $review_token,
                'fixable' => $ai_result ? ($ai_result['fixable'] ?? false) : false,
                'confidence' => $ai_result ? ($ai_result['confidence'] ?? 0) : 0,
                'fix_type' => $ai_result ? ($ai_result['fix_type'] ?? 'manual_review') : 'manual_review',
                'review_reason' => $review_reason
            ]);

            if ($ai_result) {
                $fix_entry = array_merge($fix_entry, $ai_result);
            }

            $fixes[] = $fix_entry;
            $processed++;
        }

        return $fixes;
    }

    /**
     * Get code context around error line
     */
    private function getCodeContext($file, $error_line, $context_lines = 10) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($file, '/');

        if (!file_exists($file_path) || !is_readable($file_path)) {
            return null;
        }

        $lines = file($file_path);
        $total_lines = count($lines);

        // Calculate range
        $start = max(0, $error_line - $context_lines - 1);
        $end = min($total_lines - 1, $error_line + $context_lines - 1);

        // Extract context
        $context_lines_array = array_slice($lines, $start, $end - $start + 1, true);

        // Build context string with line numbers
        $context = '';
        foreach ($context_lines_array as $line_num => $line_content) {
            $actual_line = $line_num + 1;
            $marker = ($actual_line === $error_line) ? '→' : ' ';
            $context .= sprintf("%s%4d  %s", $marker, $actual_line, $line_content);
        }

        // Get the specific problem line(s)
        $problem_start = max(0, $error_line - 3);
        $problem_end = min($total_lines - 1, $error_line + 2);
        $problem_code = implode('', array_slice($lines, $problem_start, $problem_end - $problem_start + 1));

        return [
            'full_context' => $context,
            'problem_code' => $problem_code,
            'line_start' => $problem_start + 1,
            'line_end' => $problem_end + 1
        ];
    }

    /**
     * Analyze error with AI
     */
    private function analyzeWithAI($error, $context) {
        $min_confidence = intval($this->getConfig('min_ai_confidence', 85));

        $prompt = "You are an expert PHP debugger for the Birthday.Gold platform.

ERROR DETAILS:
File: {$error['file']}:{$error['line']}
Type: {$error['type']}
Message: {$error['message']}

CODE CONTEXT:
{$context['full_context']}

TASK:
1. Analyze if this error is auto-fixable
2. If YES: Provide the EXACT fixed code (only the lines that need changing)
3. If NO: Explain why human review is needed

SAFETY RULES:
- Never modify database queries without review
- Never change authentication/security code
- Never alter business logic calculations
- Never change payment processing logic
- Only fix: undefined vars, null checks, typos, syntax errors, missing array checks

OUTPUT REQUIREMENTS:
Respond with ONLY valid JSON, no markdown formatting:
{
  \"fixable\": true or false,
  \"confidence\": 0-100 (integer),
  \"fix_type\": \"null_check\" or \"typo\" or \"missing_var\" or \"array_key_check\" or \"other\",
  \"explanation\": \"Brief explanation of the issue and fix\",
  \"fixed_code\": \"The exact fixed code\" (only if fixable),
  \"review_reason\": \"Why human review needed\" (only if not fixable)
}";

        try {
            $response = $this->ai->ask($prompt, 'auto_error_fixer');

            // Clean response (remove markdown if present)
            $response = preg_replace('/```json\s*/', '', $response);
            $response = preg_replace('/```\s*$/', '', $response);
            $response = trim($response);

            $result = json_decode($response, true);

            if (!$result || !isset($result['fixable'])) {
                return null;
            }

            // Check confidence threshold
            if ($result['confidence'] < $min_confidence) {
                $result['fixable'] = false;
                $result['review_reason'] = "AI confidence ({$result['confidence']}%) below threshold ({$min_confidence}%)";
            }

            $result['model'] = $this->ai->getModelName() ?? 'unknown';

            return $result;

        } catch (Exception $e) {
            error_log("AI analysis failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * STEP 4: Send RocketChat notification
     */
    public function sendNotification($applied_fixes, $new_fixes, $total_errors_found = 0) {
        global $system;

        $dashboard_url = 'https://dev.birthday.gold/admin/error-fix-dashboard.php';

        $message = "**Auto Error Fixer Report**\n\n";

        // Applied fixes section (brief)
        if (count($applied_fixes) > 0) {
            $success_count = count(array_filter($applied_fixes, fn($f) => $f['success']));
            $failed_count = count(array_filter($applied_fixes, fn($f) => !$f['success']));

            $message .= "✅ **Applied:** {$success_count} fix(es)";
            if ($failed_count > 0) {
                $message .= " (⚠️ {$failed_count} failed)";
            }
            $message .= "\n";
        }

        // Count new errors found
        if (count($new_fixes) > 0) {
            $fixable = array_filter($new_fixes, fn($f) => $f['fixable']);
            $unfixable = array_filter($new_fixes, fn($f) => !$f['fixable']);

            $message .= "🔍 **Detected:** " . count($new_fixes) . " error(s)\n";

            if (count($fixable) > 0) {
                $message .= "  • " . count($fixable) . " auto-fixable (pending review)\n";
            }

            if (count($unfixable) > 0) {
                $message .= "  • " . count($unfixable) . " need manual review\n";
            }
        } elseif ($total_errors_found > 0) {
            $message .= "🔍 **Detected:** {$total_errors_found} error(s)\n";
        }

        // Single dashboard link
        $message .= "\n📊 [View Error Fix Dashboard]({$dashboard_url})";

        // Debug output
        echo "RocketChat Message:\n";
        echo "  Channel: #BG-Technical\n";
        echo "  Sender: Goldie\n";
        echo "  \$system object: " . (isset($system) ? get_class($system) : 'NOT SET') . "\n";
        echo "  Content:\n" . str_replace("\n", "\n    ", $message) . "\n\n";

        // Send via RocketChat to BG-Technical channel as Goldie
        if (isset($system) && method_exists($system, 'postToRocketChat')) {
            $system->postToRocketChat($message, '#BG-Technical', 'Goldie');
            echo "✓ RocketChat message sent via \$system->postToRocketChat()\n";
        } else {
            echo "✗ ERROR: \$system object or postToRocketChat method not available\n";
            echo "  Available variables: " . implode(', ', array_keys(get_defined_vars())) . "\n";
        }
    }
}
