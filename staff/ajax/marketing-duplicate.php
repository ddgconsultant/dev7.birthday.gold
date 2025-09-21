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
    // Get original campaign
    $get_sql = "SELECT * FROM bg_content WHERE id = :id AND category = 'marketing' AND type = 'campaign'";
    $campaign = $database->getrow($get_sql, ['id' => $campaign_id]);
    
    if (!$campaign) {
        $response['message'] = 'Campaign not found';
        echo json_encode($response);
        exit;
    }
    
    // Create duplicate
    $insert_sql = "INSERT INTO bg_content 
        (name, category, type, display_name, description, content, tags, publish_dt, expire_dt, status, create_dt) 
        VALUES 
        (:name, 'marketing', 'campaign', :display_name, :description, :content, :tags, :publish_dt, :expire_dt, 'inactive', NOW())";
    
    $new_name = 'campaign_' . time() . '_copy';
    $new_display_name = $campaign['display_name'] . ' (Copy)';
    
    $database->query($insert_sql, [
        'name' => $new_name,
        'display_name' => $new_display_name,
        'description' => $campaign['description'],
        'content' => $campaign['content'],
        'tags' => $campaign['tags'],
        'publish_dt' => date('Y-m-d H:i:s'),
        'expire_dt' => null
    ]);
    
    $new_id = $database->lastInsertId();
    
    $response['success'] = true;
    $response['message'] = 'Campaign duplicated successfully';
    $response['new_id'] = $new_id;
    
} catch (Exception $e) {
    $response['message'] = 'Error duplicating campaign: ' . $e->getMessage();
}

echo json_encode($response);
?>