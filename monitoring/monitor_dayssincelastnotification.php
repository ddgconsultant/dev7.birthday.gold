<?php
//monitor_dayssincelastnotification.php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: text/plain');

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
try {
    // Query to get days since last notification
    $query = "SELECT 
                DATEDIFF(NOW(), MAX(sent_dt)) as days_since_last
             FROM bg_user_notifications 
             WHERE status = 'sent' 
             AND type = 'email_notification'";
             
    $stmt = $database->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $days_since_last = (int)$result['days_since_last'];
    
    // Query to get count of pending notifications
    $pending_query = "SELECT COUNT(*) as pending_count
                     FROM bg_user_notifications 
                     WHERE status = 'notsent' 
                     AND type = 'email_notification'
                     AND start_dt <= NOW()
                     AND (end_dt IS NULL OR end_dt >= NOW())";
                     
    $stmt = $database->prepare($pending_query);
    $stmt->execute();
    $pending_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $pending_count = (int)$pending_result['pending_count'];
    
    // Check conditions and output appropriate response
    if ($days_since_last <= 1) {
        http_response_code(200);
        echo "ok - last notification sent {$days_since_last} days ago, {$pending_count} pending";
    } else {
        http_response_code(500);
        echo "error - no notifications sent in {$days_since_last} days, {$pending_count} pending";
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo "error - monitoring script exception: " . $e->getMessage();
}

// Exit early since this is an API endpoint
exit();

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
// Not needed for this endpoint as we exit early