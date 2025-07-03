<?php
/**
 * Send tour to phone via SMS
 */

// Security check
if (!isset($database) || !isset($current_user_data) || !isset($sms)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid request']));
}

$phoneNumber = $_POST['phone_number'] ?? '';
$navigationUrl = $_POST['navigation_url'] ?? '';
$tourDate = $_POST['tour_date'] ?? date('Y-m-d');

// Clean phone number
$phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

if (strlen($phoneNumber) !== 10) {
    exit(json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit phone number']));
}

if (empty($navigationUrl)) {
    exit(json_encode(['success' => false, 'message' => 'No navigation URL provided']));
}

try {
    // Get short URL
    $shortcode = $app->getshortcode($navigationUrl);
    if ($shortcode && isset($shortcode['shorturl'])) {
        $shortUrl = $shortcode['shorturl'];
    } else {
        // Fallback if shortener fails
        $shortUrl = $navigationUrl;
        error_log("URL shortener failed for tour SMS, using full URL");
    }
    
    // Format date
    $dateObj = new DateTime($tourDate);
    $formattedDate = $dateObj->format('F j');
    
    // Build SMS message
    $message = "Your Birthday Tour for {$formattedDate}: {$shortUrl}";
    
    // Send SMS
    $smsResult = $sms->sendsms($phoneNumber, $message);
    
    if ($smsResult) {
        // Log the SMS send
        $logSql = "INSERT INTO bg_sms_logs (user_id, phone_number, message, type, status, create_dt) 
                   VALUES (:user_id, :phone, :message, 'tour', 'sent', NOW())";
        $logStmt = $database->prepare($logSql);
        $logStmt->execute([
            ':user_id' => $current_user_data['user_id'],
            ':phone' => $phoneNumber,
            ':message' => $message
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tour link sent successfully to ' . substr($phoneNumber, 0, 3) . '-' . substr($phoneNumber, 3, 3) . '-' . substr($phoneNumber, 6)
        ]);
    } else {
        throw new Exception("SMS send failed");
    }
    
} catch (Exception $e) {
    error_log("Error sending tour SMS: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send SMS. Please try again.']);
}