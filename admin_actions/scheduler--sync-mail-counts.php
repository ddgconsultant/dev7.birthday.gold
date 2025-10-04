<?php
/**
 * SCHEDULED JOB: Sync Mail Counts from Remote Servers
 *
 * Purpose: Pre-compute email counts from remote mail servers and store in bg_user_attributes
 * This eliminates the need to query remote servers on every page load (517ms → <5ms)
 *
 * Run: Hourly via cron
 * Example crontab: 0 * * * * php /path/to/scheduler--sync-mail-counts.php
 */

// Set document root for proper includes
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$start_time = microtime(true);
$stats = [
    'users_processed' => 0,
    'users_updated' => 0,
    'users_skipped' => 0,
    'errors' => 0,
    'total_time' => 0
];

echo "=== MAIL COUNT SYNC JOB STARTED ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Get all users who have feature email addresses
    $sql = "SELECT user_id, feature_email
            FROM bg_users
            WHERE feature_email IS NOT NULL
            AND feature_email != ''
            AND status = 'active'
            ORDER BY user_id";

    $stmt = $database->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($users) . " users with feature emails\n\n";

    // Initialize mail class
    require_once($dir['classes'] . '/class.mail.php');
    $mail = new mail($sitesettings['mail']);

    // Process each user
    foreach ($users as $user) {
        $stats['users_processed']++;

        try {
            // Get mail count from remote servers
            $mailcount = $mail->mailcount($user['user_id'], 'unread', 0);

            if ($mailcount && isset($mailcount['total'])) {
                // Prepare data for storage
                $mail_data = [
                    'total' => $mailcount['total'],
                    'unread' => $mailcount['unread'],
                    'read' => $mailcount['read'],
                    'inbox' => $mailcount['inbox'],
                    'last_synced' => date('Y-m-d H:i:s')
                ];

                // Store in bg_user_attributes
                $check_sql = "SELECT id FROM bg_user_attributes
                             WHERE user_id = :user_id
                             AND type = 'mail_count_cache'
                             AND name = 'feature_email_count'";
                $check_stmt = $database->prepare($check_sql);
                $check_stmt->execute(['user_id' => $user['user_id']]);
                $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    // Update existing record
                    $update_sql = "UPDATE bg_user_attributes
                                  SET value = :value,
                                      description = :description,
                                      modify_dt = NOW()
                                  WHERE id = :id";
                    $update_stmt = $database->prepare($update_sql);
                    $update_stmt->execute([
                        'id' => $existing['id'],
                        'value' => $mailcount['total'],
                        'description' => json_encode($mail_data)
                    ]);
                } else {
                    // Insert new record
                    $insert_sql = "INSERT INTO bg_user_attributes
                                  (user_id, type, name, value, description, status, create_dt, modify_dt)
                                  VALUES
                                  (:user_id, 'mail_count_cache', 'feature_email_count', :value, :description, 'active', NOW(), NOW())";
                    $insert_stmt = $database->prepare($insert_sql);
                    $insert_stmt->execute([
                        'user_id' => $user['user_id'],
                        'value' => $mailcount['total'],
                        'description' => json_encode($mail_data)
                    ]);
                }

                $stats['users_updated']++;
                echo "."; // Progress indicator

                // Output details every 50 users
                if ($stats['users_processed'] % 50 == 0) {
                    echo "\n[{$stats['users_processed']}] User {$user['user_id']}: {$mailcount['total']} messages\n";
                }

            } else {
                $stats['users_skipped']++;
            }

            // Small delay to avoid overwhelming mail servers
            usleep(10000); // 10ms delay

        } catch (Exception $e) {
            $stats['errors']++;
            error_log("Error processing user {$user['user_id']}: " . $e->getMessage());
            echo "E"; // Error indicator
        }
    }

    echo "\n\n=== JOB COMPLETED ===\n";

} catch (Exception $e) {
    echo "\n\n=== JOB FAILED ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Mail count sync job failed: " . $e->getMessage());
    $stats['errors']++;
}

// Calculate final stats
$stats['total_time'] = round((microtime(true) - $start_time), 2);

// Output summary
echo "\nSummary:\n";
echo "- Users processed: {$stats['users_processed']}\n";
echo "- Users updated: {$stats['users_updated']}\n";
echo "- Users skipped: {$stats['users_skipped']}\n";
echo "- Errors: {$stats['errors']}\n";
echo "- Total time: {$stats['total_time']} seconds\n";

// Log to database
session_tracking('mail_count_sync_completed', $stats);

echo "\nDone!\n";
?>
