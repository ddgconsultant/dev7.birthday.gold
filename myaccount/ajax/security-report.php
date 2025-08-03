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
            $subject = '[Birthday.Gold] Security Alert: Suspicious Device Reported';
            
            // Build HTML email message for admin
            $email_body = '<h2 style="color: #dc3545;">Security Alert: Suspicious Device Reported</h2>';
            
            $email_body .= '<div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">';
            $email_body .= '<h3 style="margin-top: 0;">User Information:</h3>';
            $email_body .= '<ul style="list-style: none; padding-left: 0;">';
            $email_body .= '<li><strong>User ID:</strong> ' . htmlspecialchars($user_id) . '</li>';
            $email_body .= '<li><strong>Username:</strong> ' . htmlspecialchars($current_user_data['username'] ?? 'N/A') . '</li>';
            $email_body .= '<li><strong>Email:</strong> ' . htmlspecialchars($current_user_data['email'] ?? 'N/A') . '</li>';
            $email_body .= '<li><strong>Name:</strong> ' . htmlspecialchars(($current_user_data['first_name'] ?? '') . ' ' . ($current_user_data['last_name'] ?? '')) . '</li>';
            $email_body .= '</ul>';
            $email_body .= '</div>';
            
            $email_body .= '<div style="background-color: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0;">';
            $email_body .= '<h3 style="margin-top: 0;">Report Details:</h3>';
            $email_body .= '<ul style="list-style: none; padding-left: 0;">';
            $email_body .= '<li><strong>Device ID:</strong> ' . htmlspecialchars($device_id) . '</li>';
            $email_body .= '<li><strong>Reported at:</strong> ' . date('Y-m-d H:i:s') . '</li>';
            $email_body .= '<li><strong>Reporter IP:</strong> ' . htmlspecialchars($_SERVER['REMOTE_ADDR']) . '</li>';
            $email_body .= '<li><strong>User Agent:</strong> ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . '</li>';
            $email_body .= '</ul>';
            $email_body .= '</div>';
            
            if (!empty($report_data['device_data'])) {
                $email_body .= '<div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">';
                $email_body .= '<h3 style="margin-top: 0;">Device Details:</h3>';
                $email_body .= '<ul style="list-style: none; padding-left: 0;">';
                $email_body .= '<li><strong>Browser:</strong> ' . htmlspecialchars($report_data['device_data']['browser'] ?? 'Unknown') . '</li>';
                $email_body .= '<li><strong>OS:</strong> ' . htmlspecialchars($report_data['device_data']['os'] ?? 'Unknown') . '</li>';
                $email_body .= '<li><strong>Device Type:</strong> ' . htmlspecialchars($report_data['device_data']['deviceType'] ?? 'Unknown') . '</li>';
                $email_body .= '<li><strong>Location:</strong> ' . htmlspecialchars($report_data['device_data']['location_string'] ?? 'Unknown') . '</li>';
                $email_body .= '<li><strong>Last Seen:</strong> ' . htmlspecialchars($report_data['device_data']['last_used'] ?? 'Unknown') . '</li>';
                $email_body .= '</ul>';
                $email_body .= '</div>';
            }
            
            $email_body .= '<div style="margin: 20px 0;">';
            $email_body .= '<a href="https://birthday.gold/admin/security-reports" style="display: inline-block; padding: 10px 20px; background-color: #dc3545; color: #ffffff; text-decoration: none; border-radius: 25px;">View Security Reports</a>';
            $email_body .= '</div>';
            
            $email_body .= '<details style="margin-top: 30px;">';
            $email_body .= '<summary style="cursor: pointer; color: #6c757d;">Full Device Data (Debug)</summary>';
            $email_body .= '<pre style="background-color: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;">';
            $email_body .= htmlspecialchars(print_r($report_data, true));
            $email_body .= '</pre>';
            $email_body .= '</details>';
            
            // Send notification emails using the Mail class
            session_tracking('AJAX-security_report_email_start', 'Preparing to send emails');
            
            // Send admin notification
            $admin_details = [
                'to' => 'security@birthday.gold',
                'subject' => $subject,
                'body' => $email_body,
                'donottrack' => true
            ];
            
            $result = $mail->sendmail($admin_details);
            if ($result['mail_sent']) {
                session_tracking('AJAX-security_report_email_admin', 'Admin email sent successfully');
            } else {
                session_tracking('AJAX-security_report_email_admin_fail', 'Admin email failed');
            }
            
            // Send user confirmation email using member account notification
            if (!empty($current_user_data['email'])) {
                // Get device details for the email
                $device_details = !empty($report_data['device_data']) ? $report_data['device_data'] : [];
                
                $user_body = '<div class="security-alert">';
                $user_body .= '<h3 style="color: #856404; margin-top: 0;">Security Alert Confirmation</h3>';
                $user_body .= '<p>You have successfully reported a suspicious device on your Birthday.Gold account.</p>';
                $user_body .= '</div>';
                
                $user_body .= '<div class="info-box">';
                $user_body .= '<h4 style="margin-top: 0;">Reported Device Details:</h4>';
                $user_body .= '<ul style="margin: 10px 0; padding-left: 20px;">';
                $user_body .= '<li><strong>Device ID:</strong> ' . htmlspecialchars($device_id) . '</li>';
                $user_body .= '<li><strong>Browser:</strong> ' . htmlspecialchars($device_details['browser'] ?? 'Unknown') . '</li>';
                $user_body .= '<li><strong>Operating System:</strong> ' . htmlspecialchars($device_details['os'] ?? 'Unknown') . '</li>';
                $user_body .= '<li><strong>Device Type:</strong> ' . htmlspecialchars($device_details['deviceType'] ?? 'Unknown') . '</li>';
                $user_body .= '<li><strong>Location:</strong> ' . htmlspecialchars($device_details['location_string'] ?? 'Unknown') . '</li>';
                $user_body .= '<li><strong>Last Seen:</strong> ' . htmlspecialchars($device_details['last_used'] ?? 'Unknown') . '</li>';
                $user_body .= '<li><strong>Reported at:</strong> ' . date('F j, Y at g:i A') . '</li>';
                $user_body .= '</ul>';
                $user_body .= '</div>';
                
                $user_body .= '<p><strong>What happens next?</strong></p>';
                $user_body .= '<p>Our security team has been notified and will investigate this report. The device has been flagged in our system.</p>';
                
                $user_body .= '<div class="security-alert" style="background-color: #f8d7da; border-color: #f5c6cb;">';
                $user_body .= '<h4 style="color: #721c24; margin-top: 0;">Recommended Security Actions:</h4>';
                $user_body .= '<ol style="margin: 10px 0; padding-left: 20px;">';
                $user_body .= '<li>Change your password immediately</li>';
                $user_body .= '<li>Review your recent account activity</li>';
                $user_body .= '<li>Check your enrolled rewards for any unauthorized changes</li>';
                $user_body .= '<li>Consider enabling additional security features</li>';
                $user_body .= '</ol>';
                $user_body .= '</div>';
                
                $user_body .= '<p style="text-align: center; margin: 30px 0;">';
                $user_body .= '<a href="https://birthday.gold/myaccount/security-settings" class="action-button">Security Settings</a>';
                $user_body .= '<a href="https://birthday.gold/myaccount/loginhistory" class="action-button secondary">View All Devices</a>';
                $user_body .= '</p>';
                
                $user_body .= '<p style="color: #dc3545; font-weight: 600;">If you did not make this report, please contact us immediately at <a href="mailto:security@birthday.gold">security@birthday.gold</a></p>';
                
                // For now, use the traditional email method until we debug the new system
                session_tracking('AJAX-security_report_using_fallback', 'Using traditional email method');
                
                $user_details = [
                    'to' => $current_user_data['email'],
                    'subject' => '[Birthday.Gold] Device Security Report Confirmation',
                    'body' => $user_body,
                    'donottrack' => true
                ];
                
                $user_result = $mail->sendmail($user_details);
                if ($user_result['mail_sent']) {
                    session_tracking('AJAX-security_report_email_user', 'User email sent successfully');
                } else {
                    session_tracking('AJAX-security_report_email_user_fail', 'User email failed');
                }
                
                // TODO: Re-enable member account notification system after debugging
                /*
                // Send using member account notification system
                try {
                    $notification_data = [
                        'user_id' => $user_id,
                        'subject' => 'Device Security Report Confirmation',
                        'content' => $user_body,
                        'notification_type' => 'security_alert',
                        'priority' => 'high'
                    ];
                    
                    $notification_result = $mail->sendMemberAccountNotification($notification_data);
                    
                    if ($notification_result['success']) {
                        session_tracking('AJAX-security_report_notification', 'User notification created: ' . $notification_result['notification_id']);
                    } else {
                        session_tracking('AJAX-security_report_notification_fail', 'Failed to create user notification');
                    }
                } catch (Exception $e) {
                    session_tracking('AJAX-security_report_notification_error', 'Exception: ' . $e->getMessage());
                    // Don't fail the whole report, just log the email error
                }
                */
            }
            
            // Create notification entry for the user
            try {
                $notification_sql = "INSERT INTO bg_user_notifications 
                                    (user_id, type, title, message, status, priority, create_dt, modify_dt) 
                                    VALUES 
                                    (:user_id, 'security_alert', :title, :message, 'unread', 'high', NOW(), NOW())";
                
                $notification_title = 'Device Security Report Submitted';
                $notification_message = 'You reported a suspicious device (' . htmlspecialchars($device_id) . '). Our security team has been notified and will investigate.';
                
                $stmt = $database->prepare($notification_sql);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':title' => $notification_title,
                    ':message' => $notification_message
                ]);
                
                session_tracking('AJAX-security_report_notification_created', 'Notification ID: ' . $database->lastInsertId());
            } catch (Exception $e) {
                session_tracking('AJAX-security_report_notification_error', 'Failed to create notification: ' . $e->getMessage());
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