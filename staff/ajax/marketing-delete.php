<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

$campaign_id = intval($_POST['campaign_id'] ?? 0);

if (!$campaign_id) {
    $response['message'] = 'Invalid campaign ID';
    echo json_encode($response);
    exit;
}

try {
    // Check if campaign exists
    $check_sql = "SELECT id FROM bg_content WHERE id = :id AND category = 'marketing' AND type = 'campaign'";
    $exists = $database->getrow($check_sql, ['id' => $campaign_id]);
    
    if (!$exists) {
        $response['message'] = 'Campaign not found';
        echo json_encode($response);
        exit;
    }
    
    // Delete the campaign
    $delete_sql = "DELETE FROM bg_content WHERE id = :id";
    $database->query($delete_sql, ['id' => $campaign_id]);
    
    $response['success'] = true;
    $response['message'] = 'Campaign deleted successfully';
    
} catch (Exception $e) {
    $response['message'] = 'Error deleting campaign: ' . $e->getMessage();
}

echo json_encode($response);
?>