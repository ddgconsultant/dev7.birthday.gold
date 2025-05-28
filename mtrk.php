<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

if (isset($_GET['i'])) {
    $notification_id = $qik->decodeId($_GET['i']);
    
    // Update the read history in the database
    $query = "UPDATE bg_user_notifications SET `status` ='read', read_history = CONCAT(IFNULL(read_history, ''), 'Read on ', now(), ' from IP: ', :ip_address, '\n') , 
              modify_dt = NOW() WHERE notification_id = :notification_id";
    $stmt = $database->prepare($query);
    $stmt->execute([
        'ip_address' => $client_ip,
        'notification_id' => $notification_id
    ]);
    // Return a 1x1 transparent pixel
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=');
    exit;
}

