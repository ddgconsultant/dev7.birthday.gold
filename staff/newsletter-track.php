<?PHP
// Newsletter Click Tracking
// Tracks link clicks and redirects to destination

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get parameters
$campaign_id = isset($_GET['c']) ? $qik->decodeId($_GET['c']) : 0;
$user_id = isset($_GET['u']) ? $qik->decodeId($_GET['u']) : 0;
$brand_id = isset($_GET['b']) ? $qik->decodeId($_GET['b']) : 0;
$destination_url = isset($_GET['url']) ? urldecode($_GET['url']) : '';

// Validate parameters
if ($campaign_id > 0 && $user_id > 0) {
    // Log the click event
    $event_data = ['url' => $destination_url];
    
    if ($brand_id > 0) {
        $event_data['brand_id'] = $brand_id;
        $event_type = 'cta_click';
    } else {
        $event_type = 'click';
    }
    
    $log_sql = "INSERT INTO bg_newsletter_events 
               (campaign_id, user_id, event_type, event_dt, extra) 
               VALUES 
               (:campaign_id, :user_id, :event_type, NOW(), :extra)";
    
    $database->query($log_sql, [
        'campaign_id' => $campaign_id,
        'user_id' => $user_id,
        'event_type' => $event_type,
        'extra' => json_encode($event_data)
    ]);
}

// Redirect to destination or home if no URL
if (!empty($destination_url) && filter_var($destination_url, FILTER_VALIDATE_URL)) {
    header('Location: ' . $destination_url);
} else {
    header('Location: https://birthday.gold');
}
exit;
?>