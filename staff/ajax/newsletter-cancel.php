<?PHP
// AJAX handler for cancelling scheduled newsletter campaigns
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;

if ($campaign_id > 0) {
    // Check if campaign is scheduled
    $check_sql = "SELECT status FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
    $campaign = $database->getrow($check_sql, ['campaign_id' => $campaign_id]);
    
    if ($campaign && $campaign['status'] == 'scheduled') {
        // Cancel campaign
        $update_sql = "UPDATE bg_newsletter_campaigns 
                      SET status = 'cancelled' 
                      WHERE campaign_id = :campaign_id";
        
        $database->query($update_sql, ['campaign_id' => $campaign_id]);
        
        // Remove from queue
        $delete_queue_sql = "DELETE FROM bg_newsletter_queue WHERE campaign_id = :campaign_id";
        $database->query($delete_queue_sql, ['campaign_id' => $campaign_id]);
        
        $response['success'] = true;
        $response['message'] = 'Campaign cancelled successfully';
    } else {
        $response['message'] = 'Only scheduled campaigns can be cancelled';
    }
} else {
    $response['message'] = 'Invalid campaign ID';
}

echo json_encode($response);
?>