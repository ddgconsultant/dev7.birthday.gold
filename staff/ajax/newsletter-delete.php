<?PHP
// AJAX handler for deleting newsletter campaigns
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;

if ($campaign_id > 0) {
    // Check if campaign is draft
    $check_sql = "SELECT status FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
    $campaign = $database->getrow($check_sql, ['campaign_id' => $campaign_id]);
    
    if ($campaign && $campaign['status'] == 'draft') {
        // Delete campaign
        $delete_sql = "DELETE FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
        $database->query($delete_sql, ['campaign_id' => $campaign_id]);
        
        $response['success'] = true;
        $response['message'] = 'Campaign deleted successfully';
    } else {
        $response['message'] = 'Only draft campaigns can be deleted';
    }
} else {
    $response['message'] = 'Invalid campaign ID';
}

echo json_encode($response);
?>