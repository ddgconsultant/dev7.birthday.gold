<?php
/**
 * Enrollment Summary Notifications Scheduler
 *
 * Sends periodic summary emails to members about their enrollment progress.
 * Triggered every 10 minutes via UptimeKuma scheduler.
 *
 * Groups enrollments by user and sends summaries of:
 * - Successful enrollments
 * - Failed enrollments (with help links)
 * - Still pending enrollments
 *
 * Uses bg_user_attributes to track last summary send time
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$time_window_minutes = 10;
$counters = [
    'users_processed' => 0,
    'summaries_sent' => 0,
    'summaries_failed' => 0,
    'total_success_enrollments' => 0,
    'total_failed_enrollments' => 0,
    'total_pending_enrollments' => 0
];

echo "<h1>Enrollment Summary Notifications - " . date('Y-m-d H:i:s') . "</h1>\n";

// Query users who have had enrollment activity since last summary
// Join with bg_user_attributes to get last summary send time
// Only count enrollments that changed to success/failed status since last summary
$query = "SELECT
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.type as user_type,
    COALESCE(ua.string_value, '1900-01-01 00:00:00') as last_summary_dt,
    COUNT(CASE
        WHEN ue.status = 'success'
        AND ue.modify_dt > COALESCE(ua.string_value, '1900-01-01')
        THEN 1
    END) as success_count,
    COUNT(CASE
        WHEN ue.status = 'failed'
        AND ue.modify_dt > COALESCE(ua.string_value, '1900-01-01')
        THEN 1
    END) as failed_count,
    COUNT(CASE WHEN ue.status IN ('selected', 'pending', 'queued') THEN 1 END) as pending_count,
    MAX(ue.modify_dt) as last_activity
FROM bg_users u
INNER JOIN bg_user_enrollments ue ON u.user_id = ue.user_id
LEFT JOIN bg_user_attributes ua ON u.user_id = ua.user_id
    AND ua.type = 'enrollment-summary'
    AND ua.name = 'last-sent-datetime'
    AND ua.status = 'active'
WHERE u.status = 'active'
    AND u.type = 'real'
GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.type, ua.string_value
HAVING (success_count > 0 OR failed_count > 0)";

$stmt = $database->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($users) . " users with enrollment activity to summarize</p>\n";

// Process each user
foreach ($users as $user) {
    $counters['users_processed']++;
    echo "\n<h3>Processing user: {$user['first_name']} {$user['last_name']} (ID: {$user['user_id']})</h3>\n";

    // Get detailed enrollment information for this user
    $detail_query = "SELECT
        c.company_display_name,
        c.company_name,
        ue.status,
        ue.reason,
        ue.modify_dt
    FROM bg_user_enrollments ue
    INNER JOIN bg_companies c ON ue.company_id = c.company_id
    WHERE ue.user_id = :user_id
        AND (
            (ue.status IN ('success', 'failed') AND ue.modify_dt > :last_summary_dt)
            OR ue.status IN ('selected', 'pending', 'queued')
        )
    ORDER BY
        FIELD(ue.status, 'success', 'failed', 'queued', 'pending', 'selected'),
        c.company_display_name";

    $detail_stmt = $database->prepare($detail_query);
    $detail_stmt->execute([
        ':user_id' => $user['user_id'],
        ':last_summary_dt' => $user['last_summary_dt']
    ]);
    $enrollments = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build email content
    $email_body = build_summary_email($user, $enrollments);

    // Create notification using the log_notification pattern
    $notification_data = [
        'user_id' => $user['user_id'],
        'type' => 'enrollment_summary',
        'title' => 'Birthday Gold Enrollment Update - ' . ($user['success_count'] + $user['failed_count']) . ' Enrollments Processed',
        'message' => $email_body,
        'status' => 'notsent',
        'sent_to' => $user['email'],
        'alert_class' => 'info',
        'priority' => 50,
        'category' => 'enrollment_summary',
        'notify_user_data' => [
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email']
        ]
    ];

    // Insert notification into bg_user_notifications
    $insert_query = "INSERT INTO bg_user_notifications
        (user_id, `type`, title, message, `status`, sent_to, alert_class, priority, category, create_dt, modify_dt, sent_dt, start_dt)
        VALUES
        (:user_id, :type, :title, :message, :status, :sent_to, :alert_class, :priority, :category, NOW(), NOW(), NOW(), NOW())";

    $insert_stmt = $database->prepare($insert_query);
    $insert_result = $insert_stmt->execute([
        'user_id' => $notification_data['user_id'],
        'type' => $notification_data['type'],
        'title' => $notification_data['title'],
        'message' => $notification_data['message'],
        'status' => $notification_data['status'],
        'sent_to' => $notification_data['sent_to'],
        'alert_class' => $notification_data['alert_class'],
        'priority' => $notification_data['priority'],
        'category' => $notification_data['category']
    ]);

    if ($insert_result) {
        echo "<p style='color: green;'>✅ Notification queued for {$user['email']}</p>\n";
        $counters['summaries_sent']++;
        $counters['total_success_enrollments'] += $user['success_count'];
        $counters['total_failed_enrollments'] += $user['failed_count'];
        $counters['total_pending_enrollments'] += $user['pending_count'];

        // Update or insert the last summary datetime in bg_user_attributes
        update_last_summary_datetime($user['user_id']);

        // Log the activity
        session_tracking('enrollment_summary_queued', [
            'user_id' => $user['user_id'],
            'success_count' => $user['success_count'],
            'failed_count' => $user['failed_count'],
            'pending_count' => $user['pending_count']
        ]);
    } else {
        echo "<p style='color: red;'>❌ Failed to queue notification for {$user['email']}</p>\n";
        $counters['summaries_failed']++;

        session_tracking('enrollment_summary_queue_failed', [
            'user_id' => $user['user_id'],
            'error' => 'Database insert failed'
        ]);
    }
}

echo "\n<h2>Summary</h2>\n";
echo "<ul>\n";
echo "<li>Users Processed: {$counters['users_processed']}</li>\n";
echo "<li>Summaries Sent: {$counters['summaries_sent']}</li>\n";
echo "<li>Summaries Failed: {$counters['summaries_failed']}</li>\n";
echo "<li>Total Success Enrollments: {$counters['total_success_enrollments']}</li>\n";
echo "<li>Total Failed Enrollments: {$counters['total_failed_enrollments']}</li>\n";
echo "<li>Total Pending Enrollments: {$counters['total_pending_enrollments']}</li>\n";
echo "</ul>\n";

session_tracking('enrollment_summary_notifications_complete', $counters);

// Output status for UptimeKuma monitoring
if ($counters['summaries_failed'] > 0) {
    echo "<p><strong>⚠️ Error: {$counters['summaries_failed']} notification(s) failed to queue</strong></p>\n";
} else {
    echo "<p><strong>✅ Enrollment summary notification processing complete</strong></p>\n";
}

/**
 * Update the last summary datetime in bg_user_attributes
 */
function update_last_summary_datetime($user_id) {
    global $database;

    // Check if attribute already exists
    $check_query = "SELECT attribute_id FROM bg_user_attributes
                    WHERE user_id = :user_id
                    AND type = 'enrollment-summary'
                    AND name = 'last-sent-datetime'
                    AND status = 'active'";

    $check_stmt = $database->prepare($check_query);
    $check_stmt->execute([':user_id' => $user_id]);
    $existing = $check_stmt->fetch();

    if ($existing) {
        // Update existing record
        $update_query = "UPDATE bg_user_attributes
                        SET string_value = NOW(),
                            modify_dt = NOW()
                        WHERE attribute_id = :attribute_id";

        $update_stmt = $database->prepare($update_query);
        $update_stmt->execute([':attribute_id' => $existing['attribute_id']]);
    } else {
        // Insert new record
        $insert_query = "INSERT INTO bg_user_attributes
                        (user_id, type, name, description, string_value, status, create_dt, modify_dt)
                        VALUES
                        (:user_id, 'enrollment-summary', 'last-sent-datetime', 'Last enrollment summary notification sent', NOW(), 'active', NOW(), NOW())";

        $insert_stmt = $database->prepare($insert_query);
        $insert_stmt->execute([':user_id' => $user_id]);
    }
}

/**
 * Build the HTML email body for enrollment summary
 */
function build_summary_email($user, $enrollments) {
    global $qik;
    $successful = [];
    $failed = [];
    $pending = [];

    foreach ($enrollments as $enrollment) {
        if ($enrollment['status'] === 'success') {
            $successful[] = $enrollment;
        } elseif ($enrollment['status'] === 'failed') {
            $failed[] = $enrollment;
        } else {
            $pending[] = $enrollment;
        }
    }

    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .section { margin-bottom: 25px; padding: 15px; background-color: white; border-radius: 5px; }
            .section-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 2px solid #ddd; }
            .success-title { color: #28a745; }
            .failed-title { color: #dc3545; }
            .pending-title { color: #ffc107; }
            .company-item { padding: 8px 0; border-bottom: 1px solid #eee; }
            .company-item:last-child { border-bottom: none; }
            .company-name { font-weight: 600; color: #333; }
            .failure-reason { color: #666; font-size: 14px; margin-top: 4px; }
            .help-link { display: inline-block; margin-top: 4px; color: #007bff; text-decoration: none; font-size: 13px; }
            .help-link:hover { text-decoration: underline; }
            .footer { text-align: center; padding: 15px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎂 Enrollment Progress Update</h1>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($user['first_name']) . ',</p>
                <p>Here\'s an update on your Birthday Gold enrollments:</p>';

    // Successful enrollments section
    if (count($successful) > 0) {
        $success_count = count($successful);
        $html .= '
                <div class="section">
                    <div class="section-title success-title">✅ SUCCESSFUL ' . strtoupper($qik->plural2($success_count, 'ENROLLMENT')) . ' (' . $success_count . ')</div>';

        foreach ($successful as $enrollment) {
            $company_display = htmlspecialchars($enrollment['company_display_name'] ?: $enrollment['company_name']);
            $html .= '
                    <div class="company-item">
                        <div class="company-name">• ' . $company_display . '</div>
                    </div>';
        }

        $company_word = $qik->plural2($success_count, 'company', 'companies');
        $html .= '
                    <p style="margin-top: 10px; font-size: 14px; color: #666;">
                        <em>You should receive confirmation emails from ' . ($success_count == 1 ? 'this ' . $company_word : 'these ' . $company_word) . '. Please check your inbox and verify your email if requested.</em>
                    </p>
                </div>';
    }

    // Failed enrollments section
    if (count($failed) > 0) {
        $failed_count = count($failed);
        $html .= '
                <div class="section">
                    <div class="section-title failed-title">❌ UNSUCCESSFUL ' . strtoupper($qik->plural2($failed_count, 'ENROLLMENT')) . ' (' . $failed_count . ')</div>';

        foreach ($failed as $enrollment) {
            $company_display = htmlspecialchars($enrollment['company_display_name'] ?: $enrollment['company_name']);
            $reason = htmlspecialchars($enrollment['reason'] ?: 'Unknown reason');
            $help_url = get_failure_help_url($enrollment['reason']);

            $html .= '
                    <div class="company-item">
                        <div class="company-name">• ' . $company_display . '</div>
                        <div class="failure-reason">Reason: ' . $reason . '</div>';

            if ($help_url) {
                $html .= '
                        <a href="' . $help_url . '" class="help-link" target="_blank">→ What does this mean and what can I do? ℹ️</a>';
            }

            $html .= '
                    </div>';
        }

        $html .= '
                </div>';
    }

    // Pending enrollments section
    if (count($pending) > 0) {
        $pending_count = count($pending);
        $html .= '
                <div class="section">
                    <div class="section-title pending-title">⏳ STILL PROCESSING (' . $pending_count . ' ' . strtoupper($qik->plural2($pending_count, 'ENROLLMENT')) . ')</div>';

        foreach ($pending as $enrollment) {
            $company_display = htmlspecialchars($enrollment['company_display_name'] ?: $enrollment['company_name']);
            $html .= '
                    <div class="company-item">
                        <div class="company-name">• ' . $company_display . '</div>
                    </div>';
        }

        $enrollment_word = $qik->plural2($pending_count, 'enrollment');
        $these_this = $pending_count == 1 ? 'this' : 'these';
        $html .= '
                    <p style="margin-top: 10px; font-size: 14px; color: #666;">
                        <em>We\'re still working on ' . $these_this . ' ' . $enrollment_word . '. You\'ll receive another update as we make progress.</em>
                    </p>
                </div>';
    }

    $html .= '
                <p style="margin-top: 20px;">If you have any questions about your enrollments, please don\'t hesitate to contact our support team.</p>
                <p>Best regards,<br>The Birthday Gold Team</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' Birthday Gold. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}

/**
 * Get help URL for specific failure reasons
 */
function get_failure_help_url($reason) {
    $base_url = 'https://birthday.gold/help_enrollment_failed-';

    $reason_lower = strtolower($reason);

    if (strpos($reason_lower, 'account') !== false && strpos($reason_lower, 'exists') !== false) {
        return $base_url . 'account-exists';
    } elseif (strpos($reason_lower, 'password') !== false) {
        return $base_url . 'password-validation';
    } elseif (strpos($reason_lower, 'missing') !== false && strpos($reason_lower, 'data') !== false) {
        return $base_url . 'missing-data';
    } elseif (strpos($reason_lower, 'form') !== false && strpos($reason_lower, 'failure') !== false) {
        return $base_url . 'form-failure';
    }

    // Default help page for unknown/other reasons
    return $base_url . 'general';
}
