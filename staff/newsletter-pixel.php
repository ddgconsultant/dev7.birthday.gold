<?PHP
// Newsletter Open Tracking Pixel
// Returns a 1x1 transparent pixel and logs the open event

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get parameters
$campaign_id = isset($_GET['c']) ? $qik->decodeId($_GET['c']) : 0;
$user_id = isset($_GET['u']) ? $qik->decodeId($_GET['u']) : 0;

// Log the open event (only once per user/campaign)
if ($campaign_id > 0 && $user_id > 0) {
    // Check if already logged
    $check_sql = "SELECT event_id FROM bg_newsletter_events 
                 WHERE campaign_id = :campaign_id 
                 AND user_id = :user_id 
                 AND event_type = 'open' 
                 LIMIT 1";
    
    $existing = $database->getrow($check_sql, [
        'campaign_id' => $campaign_id,
        'user_id' => $user_id
    ]);
    
    if (!$existing) {
        // Log the open
        $log_sql = "INSERT INTO bg_newsletter_events 
                   (campaign_id, user_id, event_type, event_dt, extra) 
                   VALUES 
                   (:campaign_id, :user_id, 'open', NOW(), :extra)";
        
        $database->query($log_sql, [
            'campaign_id' => $campaign_id,
            'user_id' => $user_id,
            'extra' => json_encode([
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ])
        ]);
    }
}

// Return 1x1 transparent GIF
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// 1x1 transparent GIF binary data
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;
?>