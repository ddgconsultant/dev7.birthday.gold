<?php
// scheduler--goldie-daily-summaries.php
// Nightly job to generate Goldie AI summaries for users with birthday messages
// Can be triggered via URL: /admin_actions/scheduler--goldie-daily-summaries.php
// Optional override: ?date=2025-07-18

// Set up environment
$addClasses[] = 'mail';
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set execution limits for batch processing
set_time_limit(3600); // 1 hour
ini_set('memory_limit', '512M');

// Output as plain text
header('Content-Type: text/plain; charset=utf-8');

// Log start
$start_time = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Starting Goldie daily summaries batch job\n";

// Get date to process (default: today, unless overridden)
$process_date = date('Y-m-d');
if (!empty($_GET['date'])) {
    // Allow overriding date via GET parameter
    $process_date = date('Y-m-d', strtotime($_GET['date']));
}

// Check if force regeneration is requested
$force_regenerate = !empty($_GET['force']);

echo "Processing summaries for date: $process_date\n";
if ($force_regenerate) {
    echo "FORCE MODE: Will regenerate existing summaries\n";
}

// Initialize mail object if not exists
if (!isset($mail)) {
    // Create Mail instance with proper config
    $mail = new Mail($sitesettings['mail'] ?? []);
}

try {
    // Get all users who received messages on this date from mail servers
    $users_to_process = $mail->getUsersWithMessagesForDate($process_date);
    
    $total_users = count($users_to_process);
    echo "Found $total_users users with messages on $process_date\n\n";
    
    if ($total_users === 0) {
        echo "No users to process. Exiting.\n";
        exit(0);
    }
    
    // Check if AI is available
    if (!isset($ai) || !is_object($ai)) {
        echo "ERROR: AI service not available. Exiting.\n";
        exit(1);
    }
    
    // Process each user
    $processed = 0;
    $successful = 0;
    $failed = 0;
    $skipped = 0;
    
    foreach ($users_to_process as $user) {
        $processed++;
        $user_id = $user['user_id'];
        $email = $user['email'];
        $message_count = $user['message_count'];
        
        echo "[$processed/$total_users] Processing user $user_id ($email) - $message_count messages... ";
        
        // Check if summary already exists (unless in force mode)
        if (!$force_regenerate) {
            $check_sql = "SELECT summary_id FROM bg_user_message_summaries 
                         WHERE user_id = :user_id 
                         AND summary_date = :date 
                         AND summary_type = 'daily'
                         AND processing_status = 'completed'";
            
            $check_stmt = $database->query($check_sql, [
                'user_id' => $user_id,
                'date' => $process_date
            ]);
            
            if ($check_stmt->fetch()) {
                echo "SKIPPED (already exists)\n";
                $skipped++;
                continue;
            }
        }
        
        // Generate summary using the mail class method
        $result = $mail->summarizeDailyMessages($user_id, $process_date);
        
        if ($result['success']) {
            echo "SUCCESS\n";
            $successful++;
            
            // Log summary details
            if (!empty($result['summary'])) {
                echo "  Summary: " . substr($result['summary'], 0, 100) . "...\n";
                echo "  Offers: " . count($result['offers']) . " found\n";
            }
        } else {
            echo "FAILED - " . $result['error'] . "\n";
            $failed++;
            
            // Log failure in database
            $log_sql = "INSERT INTO bg_user_message_summaries 
                       (user_id, summary_date, summary_type, message_count, 
                        processing_status, processing_error, processed_by)
                       VALUES (:user_id, :summary_date, 'daily', :message_count,
                               'failed', :error, 'scheduled_job')
                       ON DUPLICATE KEY UPDATE
                       processing_status = 'failed',
                       processing_error = VALUES(processing_error),
                       updated_at = NOW()";
            
            $database->query($log_sql, [
                'user_id' => $user_id,
                'summary_date' => $process_date,
                'message_count' => $message_count,
                'error' => $result['error']
            ]);
        }
        
        // Add delay to avoid rate limiting
        if ($processed % 10 == 0) {
            echo "\n--- Processed $processed users, pausing for rate limit ---\n\n";
            sleep(2);
        } else {
            usleep(500000); // 0.5 second between users
        }
    }
    
    // Summary statistics
    $elapsed_time = round(microtime(true) - $start_time, 2);
    echo "\n========================================\n";
    echo "Batch job completed in {$elapsed_time} seconds\n";
    echo "Total users processed: $processed\n";
    echo "Successful: $successful\n";
    echo "Failed: $failed\n";
    echo "Skipped (already existed): $skipped\n";
    echo "========================================\n";
    
    // Determine overall status for Uptime Kuma
    $failure_threshold = 0.2; // Allow up to 20% failures
    $failure_rate = $processed > 0 ? ($failed / $processed) : 0;
    
    if ($failed === 0) {
        echo "\n[" . date('Y-m-d H:i:s') . "] Job finished\n";
        echo "\nSTATUS: SUCCESS - All summaries generated successfully\n";
    } elseif ($failure_rate <= $failure_threshold) {
        echo "\n[" . date('Y-m-d H:i:s') . "] Job finished with acceptable failure rate ({$failed} failures)\n";
        echo "\nSTATUS: SUCCESS - Job completed within acceptable thresholds\n";
    } else {
        echo "\n[" . date('Y-m-d H:i:s') . "] Job finished with HIGH FAILURE RATE\n";
        echo "\nSTATUS: FAILURE - Too many failed summaries ($failed out of $processed)\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Log error
    error_log("Goldie Daily Summaries Scheduler Error: " . $e->getMessage());
    
    echo "\nSTATUS: FAILURE - Fatal error occurred\n";
    exit(1);
}

exit(0);
?>