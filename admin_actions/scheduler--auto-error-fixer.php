<?php
/**
 * scheduler--auto-error-fixer.php
 * Auto Error Fixer - Finds, analyzes, and fixes PHP errors
 *
 * Triggered by Uptime Kuma 2-3 times daily
 *
 * Workflow:
 * 1. Apply any approved fixes from previous runs
 * 2. Find new errors in PHP error log
 * 3. Develop fixes using AI analysis
 * 4. Send RocketChat notification with review links
 *
 * URL: /admin_actions/scheduler--auto-error-fixer.php
 */

// Load required classes
$addClasses[] = 'ai';
$addClasses[] = 'chat';
$addClasses[] = 'errorfixer';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set execution limits
set_time_limit(600); // 10 minutes max
ini_set('memory_limit', '512M');

// Output as plain text
header('Content-Type: text/plain; charset=utf-8');

// Check for reset parameter
$reset_timestamp = !empty($_GET['reset']);
$reset_date = $_GET['reset_date'] ?? '2025-01-01 00:00:00';

// Log start
$start_time = microtime(true);
$timestamp = date('Y-m-d H:i:s');
echo "═══════════════════════════════════════════════════════\n";
echo "  AUTO ERROR FIXER - SCHEDULED RUN\n";
echo "  Started: {$timestamp}\n";
if ($reset_timestamp) {
    echo "  MODE: RESET (timestamp will be set to {$reset_date})\n";
}
echo "═══════════════════════════════════════════════════════\n\n";

try {
    // Initialize ErrorFixer
    echo "Initializing Error Fixer...\n";
    echo "✓ Initialized\n\n";

    // Handle reset if requested
    if ($reset_timestamp) {
        echo "───────────────────────────────────────────────────────\n";
        echo "RESET TIMESTAMP\n";
        echo "───────────────────────────────────────────────────────\n";
        $errorfixer->resetLastRunTimestamp($reset_date);
        echo "✓ Last run timestamp reset to: {$reset_date}\n\n";
        echo "✓ Reloaded configuration\n\n";
    }

    // Check if enabled
    if ($errorfixer->getConfig('enabled') !== 'true') {
        echo "⚠ Auto Error Fixer is DISABLED in configuration\n";
        echo "To enable: UPDATE bg_config SET config_value='true' WHERE config_type='auto_error_fixer' AND config_key='enabled'\n";
        echo "\nSTATUS: SKIPPED\n";
        exit(0);
    }

    // ═══════════════════════════════════════════════════════
    // STEP 1: Apply approved fixes
    // ═══════════════════════════════════════════════════════
    echo "───────────────────────────────────────────────────────\n";
    echo "STEP 1: APPLYING APPROVED FIXES\n";
    echo "───────────────────────────────────────────────────────\n";

    $applied = $errorfixer->applyApprovedFixes();
    $applied_count = count($applied);

    if ($applied_count > 0) {
        echo "Found {$applied_count} approved fix(es) to apply:\n\n";

        foreach ($applied as $idx => $fix) {
            $num = $idx + 1;
            echo "[{$num}/{$applied_count}] {$fix['error_file']}:{$fix['error_line']}\n";
            echo "        Type: {$fix['ai_fix_type']}\n";

            if ($fix['success']) {
                echo "        Status: ✓ APPLIED\n";
                echo "        Commit: {$fix['commit_hash']}\n";
            } else {
                echo "        Status: ✗ FAILED\n";
                echo "        Error: {$fix['error']}\n";
            }
            echo "\n";
        }

        $successful = array_filter($applied, fn($f) => $f['success']);
        $failed = array_filter($applied, fn($f) => !$f['success']);

        echo "Summary: " . count($successful) . " applied, " . count($failed) . " failed\n";
    } else {
        echo "No approved fixes pending application\n";
    }
    echo "\n";

    // ═══════════════════════════════════════════════════════
    // STEP 2: Find new errors
    // ═══════════════════════════════════════════════════════
    echo "───────────────────────────────────────────────────────\n";
    echo "STEP 2: FINDING NEW ERRORS\n";
    echo "───────────────────────────────────────────────────────\n";

    $last_run = $errorfixer->getLastRunTimestamp();
    echo "Reading errors since: {$last_run}\n";

    $errors = $errorfixer->findNewErrors($last_run);
    $error_count = count($errors);

    echo "Found {$error_count} unique error(s)\n";

    if ($error_count > 0) {
        echo "\nError Summary:\n";
        foreach ($errors as $idx => $error) {
            $num = $idx + 1;
            echo "  {$num}. {$error['file']}:{$error['line']}\n";
            echo "     Type: {$error['type']}\n";
            echo "     Message: " . substr($error['message'], 0, 80) . "...\n";
            echo "     Occurrences: {$error['count']}\n";
        }
    }
    echo "\n";

    // ═══════════════════════════════════════════════════════
    // STEP 3: Develop fixes
    // ═══════════════════════════════════════════════════════
    echo "───────────────────────────────────────────────────────\n";
    echo "STEP 3: DEVELOPING FIXES WITH AI\n";
    echo "───────────────────────────────────────────────────────\n";

    $max_per_run = intval($errorfixer->getConfig('max_errors_per_run', 5));
    echo "Max errors to analyze per run: {$max_per_run}\n";

    if ($error_count > 0) {
        echo "Analyzing errors with AI...\n\n";

        $fixes = $errorfixer->developFixes($errors, $max_per_run);
        $fix_count = count($fixes);

        echo "AI Analysis Results: {$fix_count} fix(es) developed\n";

        if ($fix_count > 0) {
            echo "\nFix Details:\n";
            foreach ($fixes as $idx => $fix) {
                $num = $idx + 1;
                echo "  [{$num}] Fix ID #{$fix['fix_id']}\n";
                echo "      File: {$fix['file']}:{$fix['line']}\n";
                echo "      Fixable: " . ($fix['fixable'] ? 'YES' : 'NO') . "\n";
                echo "      Confidence: {$fix['confidence']}%\n";
                echo "      Type: {$fix['fix_type']}\n";

                if ($fix['fixable']) {
                    echo "      Status: Pending review\n";
                } else {
                    echo "      Reason: {$fix['review_reason']}\n";
                }
                echo "\n";
            }
        }
    } else {
        echo "No errors to analyze\n";
        $fixes = [];
    }
    echo "\n";

    // ═══════════════════════════════════════════════════════
    // STEP 4: Send notification
    // ═══════════════════════════════════════════════════════
    echo "───────────────────────────────────────────────────────\n";
    echo "STEP 4: SENDING NOTIFICATION\n";
    echo "───────────────────────────────────────────────────────\n";

    // Send notification if there's any activity (applied fixes, fixable errors, or unfixable errors)
    $has_applied = $applied_count > 0;
    $has_fixes = count(array_filter($fixes, fn($f) => $f['fixable'])) > 0;
    $has_unfixable = $error_count > 0 && count($fixes) === 0;

    if ($has_applied || $has_fixes || $has_unfixable) {
        echo "Sending RocketChat notification...\n";

        try {
            $errorfixer->sendNotification($applied, $fixes, $error_count);
            echo "✓ Notification sent successfully\n";
        } catch (Exception $e) {
            echo "✗ Failed to send notification: {$e->getMessage()}\n";
            // Don't fail the job if notification fails
        }
    } else {
        echo "No activity to report, skipping notification\n";
    }
    echo "\n";

    // ═══════════════════════════════════════════════════════
    // FINALIZATION
    // ═══════════════════════════════════════════════════════
    echo "───────────────────────────────────────────────────────\n";
    echo "FINALIZATION\n";
    echo "───────────────────────────────────────────────────────\n";

    // Update last run timestamp
    $errorfixer->updateLastRunTimestamp();
    echo "✓ Updated last run timestamp\n";

    // Calculate execution time
    $elapsed = round(microtime(true) - $start_time, 2);
    $end_timestamp = date('Y-m-d H:i:s');

    echo "\n═══════════════════════════════════════════════════════\n";
    echo "  COMPLETED SUCCESSFULLY\n";
    echo "  Finished: {$end_timestamp}\n";
    echo "  Execution Time: {$elapsed} seconds\n";
    echo "═══════════════════════════════════════════════════════\n\n";

    // Statistics summary
    echo "STATISTICS:\n";
    echo "  • Fixes Applied: {$applied_count}\n";
    echo "  • New Errors Found: {$error_count}\n";
    echo "  • Fixes Developed: " . count($fixes) . "\n";
    echo "  • Pending Review: " . count(array_filter($fixes, fn($f) => $f['fixable'])) . "\n";
    echo "\n";

    echo "STATUS: SUCCESS\n";
    exit(0);

} catch (Exception $e) {
    // Fatal error handling
    $elapsed = round(microtime(true) - $start_time, 2);

    echo "\n═══════════════════════════════════════════════════════\n";
    echo "  FATAL ERROR\n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "Error: {$e->getMessage()}\n\n";
    echo "Stack Trace:\n{$e->getTraceAsString()}\n\n";
    echo "Execution Time: {$elapsed} seconds\n";
    echo "═══════════════════════════════════════════════════════\n";

    // Log error
    error_log("Auto Error Fixer - Fatal Error: " . $e->getMessage());

    echo "\nSTATUS: FAILURE\n";
    exit(1);
}
