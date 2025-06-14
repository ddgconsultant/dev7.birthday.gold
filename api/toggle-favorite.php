<?php
/**
 * Toggle Favorite API Endpoint
 */

header('Content-Type: application/json');

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check login
$activeuser = $account->isactive();
if (empty($activeuser)) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$company_id = $input['company_id'] ?? 0;
$action = $input['action'] ?? '';

// Get user data from session
$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

if (!$company_id || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    if ($action === 'add') {
        $sql = "INSERT IGNORE INTO bg_user_favorite_companies 
                (user_id, company_id) VALUES (:user_id, :company_id)";
    } else {
        $sql = "DELETE FROM bg_user_favorite_companies 
                WHERE user_id = :user_id AND company_id = :company_id";
    }
    
    $database->query($sql, [
        'user_id' => $user_id,
        'company_id' => $company_id
    ]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to update favorite']);
}