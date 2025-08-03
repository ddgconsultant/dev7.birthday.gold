<?php
$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
/*
// Initialize mail object
$mail = null;
try {
    if (isset($sitesettings['mail']) && !empty($sitesettings['mail'])) {
        $mail = new Mail($sitesettings['mail']);
    }
} catch (Exception $e) {
    // Log but don't fail - email is optional
    session_tracking('AJAX-security_report_warning', 'Mail init failed: ' . $e->getMessage());
}
*/
// Log the request
session_tracking('AJAX-security_report_start', 'Request received');

// Log POST data
session_tracking('AJAX-security_report_post', json_encode($_POST));

// Log user status
if (isset($current_user_data)) {
    session_tracking('AJAX-security_report_user', 'User ID: ' . ($current_user_data['user_id'] ?? 'NOT SET'));
} else {
    session_tracking('AJAX-security_report_user', 'No current_user_data');
}
/*
// Check if user is logged in
$isLoggedIn = false;
try {
    $isLoggedIn = $account->isloggedin();
    session_tracking('AJAX-security_report_auth_check', 'isloggedin() returned: ' . ($isLoggedIn ? 'true' : 'false'));
} catch (Exception $e) {
    session_tracking('AJAX-security_report_auth_error', 'Auth check error: ' . $e->getMessage());
}

if (!$isLoggedIn) {
    session_tracking('AJAX-security_report_auth_fail', 'User not logged in - returning 401');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
*/
session_tracking('AJAX-security_report_auth_pass', 'User logged in successfully');

// Verify this is a valid POST request with CSRF token
if (!$app->formposted()) {
    session_tracking('AJAX-security_report_csrf', 'Form validation failed');
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

session_tracking('AJAX-security_report_csrf', 'CSRF validation passed');

$action = $_POST['action'] ?? '';
$response = ['success' => false];

session_tracking('AJAX-security_report_action', 'Action: ' . $action);

if ($action === 'report_device') {
    $device_id = $_POST['device_id'] ?? '';
    $user_id = $current_user_data['user_id'] ?? null;
    
    session_tracking('AJAX-security_report_params', 'Device: ' . $device_id . ', User: ' . $user_id);
    
    if (!empty($device_id) && !empty($user_id)) {
        session_tracking('AJAX-security_report_query', 'Starting device lookup');
        
        // Get device details
        $sql = "SELECT * FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = 'bg_rememberme_set' 
                AND name = :device_id 
                AND status = 'A' 
                LIMIT 1";
        
        try {
            $stmt = $database->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':device_id' => $device_id
            ]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            
            session_tracking('AJAX-security_report_query_result', 'Device found: ' . ($device ? 'YES' : 'NO'));
        } catch (Exception $e) {
            session_tracking('AJAX-security_report_query_error', 'Database error: ' . $e->getMessage());
            $device = false;
        }
        
        if ($device) {
            session_tracking('AJAX-security_report_device_found', 'Processing device: ' . $device_id);
            // Store the security report
            $report_data = [
                'device_id' => $device_id,
                'device_data' => json_decode($device['description'], true),
                'reported_at' => date('Y-m-d H:i:s'),
                'user_ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ];
            
            session_tracking('AJAX-security_report_data', 'Report data prepared');
            
            // Insert into bg_user_attributes as a security report
            $sql = "INSERT INTO bg_user_attributes 
                    (user_id, type, name, description, string_value, status, create_dt, modify_dt) 
                    VALUES 
                    (:user_id, 'security_report', 'device_report', :description, :device_id, 'pending', NOW(), NOW())";
            
            try {
                $stmt = $database->prepare($sql);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':description' => json_encode($report_data),
                    ':device_id' => $device_id
                ]);
                session_tracking('AJAX-security_report_insert1', 'Report inserted into bg_user_attributes');
            } catch (Exception $e) {
                session_tracking('AJAX-security_report_insert1_error', 'Insert failed: ' . $e->getMessage());
            }
            
            // Log the security event
            $sql = "INSERT INTO bg_errors 
                    (create_dt, type, data_string, cip, status) 
                    VALUES 
                    (NOW(), 'security_report', :data_string, :cip, 'A')";
            
            try {
                $stmt = $database->prepare($sql);
                $stmt->execute([
                    ':data_string' => json_encode([
                        'user_id' => $user_id,
                        'device_id' => $device_id,
                        'message' => 'User reported suspicious device'
                    ]),
                    ':cip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
                session_tracking('AJAX-security_report_insert2', 'Report logged in bg_errors');
            } catch (Exception $e) {
                session_tracking('AJAX-security_report_insert2_error', 'Error log failed: ' . $e->getMessage());
            }
            
            // Send notification email to admin
            $admin_email = 'security@birthday.gold';
            $subject = 'Security Alert: Suspicious Device Reported';
            
            // Build email message
            $email_body = "Security Alert: A user has reported a suspicious device.\n\n";
            $email_body .= "User Information:\n";
            $email_body .= "- User ID: $user_id\n";
            $email_body .= "- Username: " . ($current_user_data['username'] ?? 'N/A') . "\n";
            $email_body .= "- Email: " . ($current_user_data['email'] ?? 'N/A') . "\n\n";
            
            $email_body .= "Device Information:\n";
            $email_body .= "- Device ID: $device_id\n";
            $email_body .= "- Reported at: " . date('Y-m-d H:i:s') . "\n";
            $email_body .= "- Reporter IP: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
            
            $email_body .= "Device Details:\n";
            if (!empty($report_data['device_data'])) {
                $email_body .= "- Browser: " . ($report_data['device_data']['browser'] ?? 'Unknown') . "\n";
                $email_body .= "- OS: " . ($report_data['device_data']['os'] ?? 'Unknown') . "\n";
                $email_body .= "- Device Type: " . ($report_data['device_data']['deviceType'] ?? 'Unknown') . "\n";
                $email_body .= "- Location: " . ($report_data['device_data']['location_string'] ?? 'Unknown') . "\n";
                $email_body .= "- Last Seen: " . ($report_data['device_data']['last_used'] ?? 'Unknown') . "\n";
            }
            
            $email_body .= "\nFull Device Data:\n" . print_r($report_data['device_data'], true);
            
            // Send notification emails using the Mail class
            session_tracking('AJAX-security_report_email_start', 'Preparing to send emails');
            
            // Send admin notification
            $admin_details = [
                'to' => 'security@birthday.gold',
                'subject' => $subject,
                'body' => nl2br($email_body),
                'donottrack' => true
            ];
            
            $result = $mail->sendmail($admin_details);
            if ($result['mail_sent']) {
                session_tracking('AJAX-security_report_email_admin', 'Admin email sent successfully');
            } else {
                session_tracking('AJAX-security_report_email_admin_fail', 'Admin email failed');
            }
            
            // Send user confirmation email
            if (!empty($current_user_data['email'])) {
                $user_subject = 'Security Report Confirmation';
                $user_body = "Hello,<br><br>";
                $user_body .= "This email confirms that you have reported a suspicious device on your Birthday Gold account.<br><br>";
                $user_body .= "<strong>Device Details:</strong><br>";
                $user_body .= "- Device ID: $device_id<br>";
                $user_body .= "- Reported at: " . date('Y-m-d H:i:s') . "<br><br>";
                $user_body .= "We take security seriously and will investigate this report. ";
                $user_body .= "As a precaution, we recommend you:<br>";
                $user_body .= "1. Change your password immediately<br>";
                $user_body .= "2. Review your recent account activity<br>";
                $user_body .= "3. Enable two-factor authentication if available<br><br>";
                $user_body .= "If you did not make this report, please contact us immediately.<br><br>";
                $user_body .= "Thank you,<br>Birthday Gold Security Team";
                
                $user_details = [
                    'to' => $current_user_data['email'],
                    'subject' => $user_subject,
                    'body' => $user_body,
                    'donottrack' => true
                ];
                
                $user_result = $mail->sendmail($user_details);
                if ($user_result['mail_sent']) {
                    session_tracking('AJAX-security_report_email_user', 'User email sent successfully');
                } else {
                    session_tracking('AJAX-security_report_email_user_fail', 'User email failed');
                }
            }
            
            // Log successful report
            session_tracking('AJAX-security_report_success', 'Device reported: ' . $device_id);
            
            $response = [
                'success' => true,
                'message' => 'Security report submitted successfully',
                'report_id' => $database->lastInsertId()
            ];
            
            session_tracking('AJAX-security_report_response', 'Success response prepared');
        } else {
            session_tracking('AJAX-security_report_device_not_found', 'Device not found: ' . $device_id);
            
            // Try to see what devices exist for debugging
            $debugSql = "SELECT name, status FROM bg_user_attributes 
                        WHERE user_id = :user_id 
                        AND type = 'bg_rememberme_set' 
                        ORDER BY create_dt DESC 
                        LIMIT 5";
            $debugStmt = $database->prepare($debugSql);
            $debugStmt->execute([':user_id' => $user_id]);
            $existingDevices = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log to error table
            $sql = "INSERT INTO bg_errors (create_dt, type, data_string, cip, status) 
                    VALUES (NOW(), 'security_report_device_not_found', :data_string, :cip, 'A')";
            $stmt = $database->prepare($sql);
            $stmt->execute([
                ':data_string' => json_encode([
                    'device_id' => $device_id,
                    'user_id' => $user_id,
                    'message' => 'Device not found',
                    'existing_devices' => $existingDevices
                ]),
                ':cip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            $response = [
                'success' => false,
                'error' => 'Device not found',
                'debug' => [
                    'device_id' => $device_id,
                    'user_id' => $user_id,
                    'existing_devices' => $existingDevices
                ]
            ];
        }
    } else {
        session_tracking('AJAX-security_report_empty_data', 'Empty device ID or user ID');
        $response = [
            'success' => false,
            'error' => 'Invalid device ID or user not logged in',
            'debug' => [
                'device_id' => $device_id,
                'user_id' => $user_id
            ]
        ];
    }
} else {
    session_tracking('AJAX-security_report_wrong_action', 'Invalid action: ' . $action);
    $response = [
        'success' => false,
        'error' => 'Invalid action',
        'provided_action' => $action
    ];
}

session_tracking('AJAX-security_report_final', 'Sending response: ' . json_encode($response));

header('Content-Type: application/json');
echo json_encode($response);

session_tracking('AJAX-security_report_complete', 'Request completed');
?>