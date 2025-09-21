<?php
// Include your site controller and any required files
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
// Define your Rocket.Chat recipient
$rocketchatuser = "#BG-Technical"; // Default to technical channel
$send_notifications = true; // Flag to control notifications
$check_interval = 'daily'; // daily, weekly
$day = date('l');

// Override recipient if provided as parameter
if (isset($_GET['recipient'])) {
    $rocketchatuser = $_GET['recipient'];
}

// Check interval parameter
if (isset($_GET['interval'])) {
    $check_interval = $_GET['interval'];
}

// Allow monitoring mode - just return JSON data without notifications
if (isset($_GET['monitor']) && $_GET['monitor'] == 'true') {
    $send_notifications = false;
    header('Content-Type: application/json');
}

// For weekly checks, only run on Monday
if ($check_interval === 'weekly' && $day !== 'Monday') {
    echo "Weekly check scheduled for Monday only. Today is: " . $day;
    exit;
}

// Random message intros for variety
$intro_messages = [
    "Security Alert: {count} pending security reports require attention.",
    "System Security Check: Found {count} unresolved security reports.",
    "Security Report Summary: {count} reports need review.",
    "Attention Required: {count} security incidents pending resolution.",
    "Security Dashboard Alert: {count} reports awaiting action."
];

#-------------------------------------------------------------------------------
# CHECK SECURITY REPORTS
#-------------------------------------------------------------------------------
// Get pending security reports count
$sql = "SELECT 
        status, 
        COUNT(*) as count 
    FROM bg_user_attributes 
    WHERE type = 'security_report' 
    AND status IN ('pending', 'active')
    GROUP BY status";

$stmt = $database->query($sql);
$status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending_count = 0;
foreach ($status_counts as $row) {
    $pending_count += $row['count'];
}

// Get detailed pending reports if any exist
if ($pending_count > 0) {
    $sql = "SELECT 
            ua.attribute_id,
            ua.user_id,
            ua.description,
            ua.string_value as device_id,
            ua.status,
            ua.create_dt,
            u.username,
            u.email,
            u.first_name,
            u.last_name
        FROM bg_user_attributes ua
        LEFT JOIN bg_users u ON ua.user_id = u.user_id
        WHERE ua.type = 'security_report'
        AND ua.status IN ('pending', 'active')
        ORDER BY ua.create_dt DESC
        LIMIT 10"; // Limit to most recent 10 for notification

    $stmt = $database->query($sql);
    $pending_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build notification message
    if ($send_notifications && !empty($pending_reports)) {
        // Pick a random intro message
        $random_index = array_rand($intro_messages);
        $intro = str_replace('{count}', $pending_count, $intro_messages[$random_index]);
        
        $message = ":warning: **" . $intro . "**\n\n";
        $message .= "Review at: https://birthday.gold/admin/security-reports.php\n\n";
        
        // Add summary of reports
        $message .= "**Recent Reports:**\n";
        
        $report_count = 0;
        foreach ($pending_reports as $report) {
            $report_count++;
            $report_data = json_decode($report['description'], true);
            
            // Format the report summary
            $message .= "\n" . $report_count . ". **User:** " . $report['username'] . " (" . $report['email'] . ")\n";
            
            if (is_array($report_data)) {
                // Extract key information from the report
                if (isset($report_data['type'])) {
                    $message .= "   **Type:** " . $report_data['type'] . "\n";
                }
                if (isset($report_data['reason'])) {
                    $message .= "   **Reason:** " . $report_data['reason'] . "\n";
                }
                if (isset($report_data['ip'])) {
                    $message .= "   **IP:** " . $report_data['ip'] . "\n";
                }
                if (isset($report_data['location'])) {
                    $message .= "   **Location:** " . $report_data['location'] . "\n";
                }
            } else {
                // Fallback for non-JSON descriptions
                $message .= "   **Details:** " . substr($report['description'], 0, 100) . "...\n";
            }
            
            $message .= "   **Reported:** " . date('M j, Y g:i A', strtotime($report['create_dt'])) . "\n";
        }
        
        if ($pending_count > 10) {
            $message .= "\n... and " . ($pending_count - 10) . " more reports.\n";
        }
        
        // Add action reminder
        $message .= "\n:point_right: **Action Required:** Please review and resolve these security reports promptly.";
        
        // Send to Rocket.Chat
        $system->postToRocketChat($message, $rocketchatuser);
        
        // If sent to a channel, also notify Richard
        if (strpos($rocketchatuser, '#') === 0 && $rocketchatuser !== '@Richard') {
            $personal_message = ":lock: Richard, there are " . $pending_count . " pending security reports that need your attention.\n";
            $personal_message .= "Review at: https://birthday.gold/admin/security-reports.php";
            $system->postToRocketChat($personal_message, '@Richard');
        }
        
        echo "Security report notification sent. Found " . $pending_count . " pending reports.\n";
    }
} else {
    // No pending reports
    echo "No pending security reports found.\n";
    
    // Optionally send an all-clear message (only on weekly checks)
    if ($check_interval === 'weekly' && $send_notifications) {
        $message = ":white_check_mark: **Security Report Status:** All clear! No pending security reports.";
        $system->postToRocketChat($message, $rocketchatuser);
    }
}

// Log the check
$log_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'pending_reports' => $pending_count,
    'notification_sent' => ($pending_count > 0 && $send_notifications),
    'recipient' => $rocketchatuser,
    'check_type' => $check_interval
];

// Optional: Store check history
$sql = "INSERT INTO bg_system_logs (type, description, create_dt) 
        VALUES ('security_check', :log_data, NOW())";
$database->query($sql, [':log_data' => json_encode($log_entry)]);

// If in monitoring mode, output JSON
if (isset($_GET['monitor']) && $_GET['monitor'] == 'true') {
    $output = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'pending_reports' => $pending_count,
        'check_type' => $check_interval,
        'reports' => isset($pending_reports) ? $pending_reports : []
    ];
    echo json_encode($output, JSON_PRETTY_PRINT);
} else {
    echo "\nSecurity report check completed at " . date('Y-m-d H:i:s');
}
?>