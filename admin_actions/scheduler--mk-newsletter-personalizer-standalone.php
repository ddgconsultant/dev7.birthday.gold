<?php
/**
 * MK Newsletter Personalizer - Standalone Version
 * Independent of site-controller for reliability
 */

// Set execution limits
set_time_limit(300);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Disable output buffering
@ini_set('output_buffering', 'Off');
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

while (ob_get_level()) {
    ob_end_clean();
}
ob_implicit_flush(true);

// Headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

// Output
echo '<pre style="font-family: monospace; font-size: 12px; line-height: 1.4; background: #f5f5f5; padding: 20px;">';
echo date('Y-m-d H:i:s') . " - MK Newsletter Personalizer (Standalone)\n";
echo str_repeat('=', 80) . "\n";
flush();

// Status tracking
$statusOutput = false;
register_shutdown_function(function() use (&$statusOutput) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\nFatal Error: " . $error['message'] . "\n";
        if (!$statusOutput) {
            echo "\nStatus: Error\n";
        }
    } else if (!$statusOutput) {
        echo "\nStatus: Error - Unexpected termination\n";
    }
    echo '</pre>';
    @flush();
});

try {
    // Database connection
    $db = new PDO('mysql:host=10.251.0.14;dbname=bg_prod;charset=utf8mb4', 'bguser', 'pr_cur3pYHYhcxgV');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connected\n";

    // Configuration
    $max_batch_size = 50;
    $ai_batch_size = 5;
    $template_batch_size = 20;

    // Check for pending newsletters
    $sql = "SELECT
                JSON_UNQUOTE(JSON_EXTRACT(options, '$.campaign_id')) as campaign_id,
                JSON_UNQUOTE(JSON_EXTRACT(options, '$.gen_specific_messaging')) as gen_specific,
                JSON_UNQUOTE(JSON_EXTRACT(options, '$.user_generation')) as generation,
                COUNT(*) as count
            FROM bg_user_notifications
            WHERE type = 'newsletter'
            AND status = 'pending'
            AND sent_to IS NOT NULL
            AND options IS NOT NULL
            GROUP BY campaign_id, gen_specific, generation
            ORDER BY count DESC";

    $workload = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    if (empty($workload)) {
        echo "No pending newsletters to personalize\n";

        // Show status breakdown
        $status_sql = "SELECT status, COUNT(*) as count
                      FROM bg_user_notifications
                      WHERE type = 'newsletter'
                      GROUP BY status";
        $statuses = $db->query($status_sql)->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($statuses)) {
            echo "\nCurrent newsletter status:\n";
            foreach ($statuses as $status) {
                echo "  - {$status['status']}: {$status['count']}\n";
            }
        }

        echo "\nStatus: Ok\n";
        $statusOutput = true;
        echo '</pre>';
        exit(0);
    }

    // Display workload
    echo "\nWORKLOAD ANALYSIS\n";
    echo str_repeat('-', 80) . "\n";
    $total_pending = 0;
    foreach ($workload as $work) {
        $mode = ($work['gen_specific'] == '1') ? 'AI' : 'Template';
        echo "Campaign {$work['campaign_id']} | {$mode} | Gen: {$work['generation']} | Count: {$work['count']}\n";
        $total_pending += $work['count'];
    }
    echo "Total pending: $total_pending\n";
    echo str_repeat('=', 80) . "\n\n";
    flush();

    $processed_count = 0;
    $error_count = 0;

    // Process each group
    foreach ($workload as $work) {
        if ($processed_count >= $max_batch_size) {
            echo "\nReached batch limit ($max_batch_size)\n";
            break;
        }

        $campaign_id = $work['campaign_id'];
        $generation = $work['generation'] ?: 'millennial';
        $needs_ai = ($work['gen_specific'] == '1');

        $group_batch_size = $needs_ai ? $ai_batch_size : $template_batch_size;
        $batch_to_process = min($group_batch_size, $max_batch_size - $processed_count, $work['count']);

        echo "\nProcessing Campaign $campaign_id, Generation: $generation, Batch: $batch_to_process\n";
        flush();

        // Get notifications for this group
        $group_sql = "SELECT * FROM bg_user_notifications
                      WHERE type = 'newsletter'
                      AND status = 'pending'
                      AND sent_to IS NOT NULL
                      AND JSON_UNQUOTE(JSON_EXTRACT(options, '$.campaign_id')) = ?
                      AND (JSON_UNQUOTE(JSON_EXTRACT(options, '$.user_generation')) = ?
                           OR (JSON_EXTRACT(options, '$.user_generation') IS NULL AND ? = 'millennial'))
                      ORDER BY create_dt ASC
                      LIMIT " . intval($batch_to_process);

        $stmt = $db->prepare($group_sql);
        $stmt->execute([$campaign_id, $generation, $generation]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $group_processed = 0;
        $group_errors = 0;

        foreach ($notifications as $notification) {
            try {
                $campaign_data = json_decode($notification['options'], true);
                $user_data = $campaign_data['user_data'];

                // Simple personalization (no AI for standalone)
                $email_subject = $campaign_data['email_subject'] ?? 'Birthday Gold Newsletter';
                $email_content = $campaign_data['campaign_content'] ?? '';

                // Replace placeholders
                $replacements = [
                    '[[first_name]]' => $user_data['first_name'] ?? '',
                    '[[last_name]]' => $user_data['last_name'] ?? '',
                    '[[city]]' => $user_data['city'] ?? '',
                    '[[state]]' => $user_data['state'] ?? '',
                    '[[birthday_month]]' => !empty($user_data['birth_month']) ?
                        date('F', mktime(0, 0, 0, $user_data['birth_month'], 1)) : ''
                ];

                foreach ($replacements as $placeholder => $value) {
                    $email_subject = str_replace($placeholder, $value, $email_subject);
                    $email_content = str_replace($placeholder, $value, $email_content);
                }

                // Simple CTA block
                if (strpos($email_content, '[[CTA_BLOCK]]') !== false) {
                    $cta_html = '<div style="text-align:center;padding:20px;">
                        <a href="https://birthday.gold/myaccount" style="background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">
                            View Your Birthday Rewards
                        </a>
                    </div>';
                    $email_content = str_replace('[[CTA_BLOCK]]', $cta_html, $email_content);
                }

                // Add footer
                $footer = '<div style="margin-top:40px;padding-top:20px;border-top:1px solid #e9ecef;font-size:12px;color:#6c757d;text-align:center;">
                    <p>Birthday Gold - Automated Birthday Rewards</p>
                    <p><a href="https://m.bd.gold/unsubscribe">Unsubscribe</a></p>
                </div>';

                $final_html = '<!DOCTYPE html><html><head>
                    <meta charset="UTF-8">
                    <title>' . htmlspecialchars($email_subject) . '</title>
                    </head><body style="font-family:sans-serif;line-height:1.6;color:#333;max-width:600px;margin:0 auto;padding:20px;">
                    ' . $email_content . $footer . '</body></html>';

                // Update notification
                $update_sql = "UPDATE bg_user_notifications
                              SET title = ?, message = ?, status = 'notsent', modify_dt = NOW()
                              WHERE notification_id = ?";

                $stmt = $db->prepare($update_sql);
                $stmt->execute([$email_subject, $final_html, $notification['notification_id']]);

                $group_processed++;
                echo "  ✓ Processed notification {$notification['notification_id']}\n";

                if ($group_processed % 5 == 0) {
                    flush();
                }

            } catch (Exception $e) {
                $group_errors++;
                echo "  ✗ Error: " . $e->getMessage() . "\n";

                // Mark as failed
                $db->prepare("UPDATE bg_user_notifications SET status = 'failed', modify_dt = NOW() WHERE notification_id = ?")
                   ->execute([$notification['notification_id']]);
            }
        }

        $processed_count += $group_processed;
        $error_count += $group_errors;

        echo "Group complete: $group_processed processed, $group_errors errors\n";
        flush();
    }

    // Summary
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "PERSONALIZER SUMMARY\n";
    echo "Processed: $processed_count\n";
    echo "Errors: $error_count\n";
    echo str_repeat('=', 80) . "\n";

    if ($error_count > 0 && $processed_count == 0) {
        echo "\nStatus: Error - All attempts failed\n";
    } else {
        echo "\nStatus: Ok\n";
    }
    $statusOutput = true;

} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "\nStatus: Error\n";
    $statusOutput = true;
}

echo '</pre>';
flush();
exit(0);
?>