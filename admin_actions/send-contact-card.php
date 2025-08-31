<?php
/**
 * Admin Action: Send Birthday.Gold Contact Card via SMS
 * Sends a vCard contact that users can save to their phone
 */

$addClasses[] = 'sms';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Security check - require scheduler key or admin access
$scheduler_key = $_GET['key'] ?? '';
$expected_key = $sitesettings['scheduler']['SCHEDULER_KEY'] ?? '';

if ($scheduler_key !== $expected_key) {
    if (!$account->isactive() || !$account->isadmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized access']);
        exit;
    }
}

// Get parameters
$user_id = $_GET['user_id'] ?? $_GET['rawid'] ?? null;
$phone = $_GET['phone'] ?? null;
$force = $_GET['force'] ?? false;

// Response array
$response = [
    'status' => 'error',
    'timestamp' => date('Y-m-d H:i:s'),
    'processed' => 0,
    'sent' => 0,
    'errors' => []
];

// Generate vCard content using site configuration
function generateBirthdayGoldVCard() {
    global $website, $bg_phonenumbers;
    
    // Use real SMS sender number for Birthday.Gold
    $sms_phone = "2232004653"; // Real SMS sender number (223-200-4653)
    $formatted_phone = "+1-" . substr($sms_phone, 0, 3) . "-" . substr($sms_phone, 3, 3) . "-" . substr($sms_phone, 6, 4);
    
    // Use site configuration for dynamic data
    $org_name = "Birthday.Gold";
    $support_email = "support@birthday.gold";
    $website_url = $website['formalurl'] ?? "https://birthday.gold";
    $tollfree = $bg_phonenumbers['tollfree'] ?? "1-877-BDGOLD-2";
    
    // Android-compatible vCard format (version 2.1 is more universally supported)
    $vcard = "BEGIN:VCARD\r\n";
    $vcard .= "VERSION:2.1\r\n";
    $vcard .= "N:Support;Birthday.Gold;;;\r\n";
    $vcard .= "FN:Birthday.Gold Support\r\n";
    $vcard .= "ORG:{$org_name}\r\n";
    $vcard .= "TEL;TYPE=CELL:{$formatted_phone}\r\n";
    $vcard .= "TEL;TYPE=VOICE:{$tollfree}\r\n";
    $vcard .= "EMAIL;INTERNET:{$support_email}\r\n";
    $vcard .= "NOTE:Birthday.Gold - Your birthday reward specialist! Save this contact to easily identify our security codes and updates.\r\n";
    $vcard .= "END:VCARD\r\n";
    
    return $vcard;
}

// Check if we should send contact card to specific user
function shouldSendContactCard($user_id) {
    global $database;
    
    // Check if contact card was already sent
    $sql = "SELECT COUNT(*) as sent_count FROM bg_user_notifications 
            WHERE user_id = :user_id 
            AND type = 'contact_card_sent' 
            AND status != 'error'";
    
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['sent_count'] > 0) {
        return false; // Already sent
    }
    
    // Check SMS history - send after 1st or 2nd SMS
    $sql = "SELECT COUNT(*) as sms_count FROM bg_user_notifications 
            WHERE user_id = :user_id 
            AND type LIKE '%security_code%' 
            AND category = 'sms'
            AND create_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $sms_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sms_count = $sms_result['sms_count'] ?? 0;
    
    // Send after 1st or 2nd SMS (but not immediately after first to avoid spam)
    return ($sms_count >= 1 && $sms_count <= 2);
}

// Process specific user
if ($user_id && $phone) {
    try {
        $response['processed'] = 1;
        
        // Check if we should send (unless forced)
        if (!$force && !shouldSendContactCard($user_id)) {
            $response['status'] = 'skipped';
            $response['message'] = 'Contact card not needed for this user';
            echo json_encode($response);
            exit;
        }
        
        // Get user data for notification
        $user_data = $account->getuserdata($user_id, 'user_id');
        if (!$user_data) {
            throw new Exception('User not found');
        }
        
        // Generate dynamic vCard file
        $vcard_content = generateBirthdayGoldVCard();
        $vcard_filename = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/birthday-gold-contact.vcf';
        file_put_contents($vcard_filename, $vcard_content);
        
        // Create clean MMS message with vCard attachment  
        $contact_message = "Save our contact! 📞\n\nTap the attachment to add Birthday.Gold to your contacts. This helps you identify our security codes and updates.";
        
        // vCard attachment URL (with improved headers and format)
        $vcard_url = "https://dev7.birthday.gold/contact-card.php";
        
        // Send via MMS with vCard attachment
        if (isset($sms) && is_object($sms)) {
            // Clean phone number
            $clean_phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($clean_phone) === 11 && str_starts_with($clean_phone, '1')) {
                $clean_phone = substr($clean_phone, 1);
            }
            
            if (strlen($clean_phone) === 10) {
                $sms_result = $sms->sendSingleMessage(
                    $clean_phone, 
                    $contact_message, 
                    0,          // device
                    null,       // schedule 
                    true,       // isMMS = true (required for vCard attachments)
                    $vcard_url, // vCard attachment URL
                    false       // prioritize
                );
                
                // Check if SMS was sent successfully
                $sms_success = false;
                if (isset($sms_result['status']) && $sms_result['status'] === 'success') {
                    $sms_success = true;
                } elseif (isset($sms_result['success']) && $sms_result['success'] === true) {
                    $sms_success = true;
                } elseif (isset($sms_result['ID']) && !empty($sms_result['ID'])) {
                    $sms_success = true;
                }
                
                if ($sms_success) {
                    // Add notification to track that contact card was sent
                    if (isset($mail) && is_object($mail)) {
                        $mail->addNotification(
                            $user_id,
                            'contact_card_sent',
                            '📞 Contact Card Sent',
                            'We sent you our contact information so you can easily save Birthday.Gold to your phone contacts.',
                            [
                                'alert_class' => 'info',
                                'priority' => 'low',
                                'category' => 'system',
                                'end_dt' => '90d'
                            ]
                        );
                    }
                    
                    $response['status'] = 'success';
                    $response['sent'] = 1;
                    $response['message'] = 'Contact card sent successfully';
                } else {
                    throw new Exception('SMS sending failed: ' . json_encode($sms_result));
                }
            } else {
                throw new Exception('Invalid phone number format');
            }
        } else {
            throw new Exception('SMS service not available');
        }
        
    } catch (Exception $e) {
        $response['errors'][] = $e->getMessage();
        error_log('Contact card send error: ' . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Process users who need contact cards (bulk mode)
if (!$user_id) {
    try {
        // Find users who recently received SMS codes but haven't received contact card
        $sql = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.phone_number
                FROM bg_users u
                INNER JOIN bg_user_attributes ua ON u.user_id = ua.user_id
                WHERE ua.type = '2fa_temp_code'
                AND ua.create_dt >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND u.phone_number IS NOT NULL
                AND u.phone_number != ''
                AND u.status = 'active'
                AND NOT EXISTS (
                    SELECT 1 FROM bg_user_notifications 
                    WHERE user_id = u.user_id 
                    AND type = 'contact_card_sent'
                    AND status != 'error'
                )
                LIMIT 10"; // Process 10 at a time
        
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['processed'] = count($users);
        
        // Generate dynamic vCard file once for all users
        $vcard_content = generateBirthdayGoldVCard();
        $vcard_filename = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/birthday-gold-contact.vcf';
        file_put_contents($vcard_filename, $vcard_content);
        $vcard_url = "https://dev7.birthday.gold/contact-card.php";
        
        // Create RCS-optimized contact message once
        $sms_number = $bg_phonenumbers['text_numbers'] ?? "223-200-4653";
        $tollfree = $bg_phonenumbers['tollfree_numbers'] ?? "877-234-6532";
        
        $contact_message = "📞 Save Birthday.Gold Support:\n\n";
        $contact_message .= "Security Codes: {$sms_number}\n";
        $contact_message .= "Customer Service: {$tollfree}\n";
        $contact_message .= "Email: support@birthday.gold\n";
        $contact_message .= "Website: birthday.gold\n\n";
        $contact_message .= "💡 Tap and hold any number to copy and save to your contacts!\n\n";
        $contact_message .= "This helps you easily identify our security codes and updates.";
        
        foreach ($users as $user) {
            if (shouldSendContactCard($user['user_id'])) {
                // Send via regular SMS (RCS will auto-upgrade)
                $clean_phone = preg_replace('/[^0-9]/', '', $user['phone_number']);
                if (strlen($clean_phone) === 11 && str_starts_with($clean_phone, '1')) {
                    $clean_phone = substr($clean_phone, 1);
                }
                
                if (strlen($clean_phone) === 10) {
                    $sms_result = $sms->sendSingleMessage(
                        $clean_phone, 
                        $contact_message, 
                        0,          // device
                        null,       // schedule 
                        false,      // isMMS = false (let RCS handle rich features)
                        null,       // no attachments needed
                        false       // prioritize
                    );
                    
                    // Check success and add notification
                    $sms_success = isset($sms_result['ID']) && !empty($sms_result['ID']);
                    
                    if ($sms_success) {
                        $mail->addNotification(
                            $user['user_id'],
                            'contact_card_sent',
                            '📞 Contact Card Sent',
                            'We sent you our contact information so you can easily save Birthday.Gold to your phone contacts.',
                            [
                                'alert_class' => 'info',
                                'priority' => 'low',
                                'category' => 'system',
                                'end_dt' => '90d'
                            ]
                        );
                        
                        $response['sent']++;
                    }
                }
            }
        }
        
        $response['status'] = 'success';
        $response['message'] = "Processed {$response['processed']} users, sent {$response['sent']} contact cards";
        
    } catch (Exception $e) {
        $response['errors'][] = $e->getMessage();
        error_log('Bulk contact card send error: ' . $e->getMessage());
    }
}

echo json_encode($response);
?>