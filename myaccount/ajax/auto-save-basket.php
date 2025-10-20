<?php
/**
 * Auto-Save Basket - AJAX Handler
 * Saves user's picks to bg_user_enrollments with status='cart' for persistence
 */

error_reporting(0);
ini_set('display_errors', 0);

// Include site controller for authentication
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

ob_clean();
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Get user ID from current_user_data
$user_id = $current_user_data['user_id'] ?? 0;

// Check if user is logged in
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get JSON input
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input) {
    $response['message'] = 'Invalid request data';
    echo json_encode($response);
    exit;
}

$picked_ids = isset($input['picked']) ? array_map('intval', $input['picked']) : [];
$tracked_ids = isset($input['tracked']) ? array_map('intval', $input['tracked']) : [];

// Start transaction
$database->query("START TRANSACTION");

try {
    // First, get current cart items for this user
    $existing_cart_sql = "SELECT company_id, status
                          FROM bg_user_enrollments
                          WHERE user_id = :user_id
                          AND status IN ('cart', 'cart_tracked')";
    $existing_cart = $database->getrows($existing_cart_sql, ['user_id' => $user_id]);
    $existing_cart_ids = [];
    $existing_tracked_ids = [];

    foreach ($existing_cart as $item) {
        if ($item['status'] == 'cart') {
            $existing_cart_ids[] = $item['company_id'];
        } else if ($item['status'] == 'cart_tracked') {
            $existing_tracked_ids[] = $item['company_id'];
        }
    }

    // Find items to remove (no longer in basket)
    $to_remove_picked = array_diff($existing_cart_ids, $picked_ids);
    $to_remove_tracked = array_diff($existing_tracked_ids, $tracked_ids);

    // Find items to add (new in basket)
    $to_add_picked = array_diff($picked_ids, $existing_cart_ids);
    $to_add_tracked = array_diff($tracked_ids, $existing_tracked_ids);

    // Remove items no longer in cart
    if (!empty($to_remove_picked) || !empty($to_remove_tracked)) {
        $all_to_remove = array_merge($to_remove_picked, $to_remove_tracked);
        $placeholders = array_fill(0, count($all_to_remove), '?');
        $delete_sql = "DELETE FROM bg_user_enrollments
                       WHERE user_id = ?
                       AND company_id IN (" . implode(',', $placeholders) . ")
                       AND status IN ('cart', 'cart_tracked')";

        $params = array_merge([$user_id], $all_to_remove);
        $stmt = $database->prepare($delete_sql);
        $stmt->execute($params);
    }

    // Add new picked items to cart
    foreach ($to_add_picked as $company_id) {
        if (!$company_id) continue;

        // Check if already enrolled with a different status
        $check_sql = "SELECT user_company_id, status
                      FROM bg_user_enrollments
                      WHERE user_id = :user_id
                      AND company_id = :company_id
                      AND status NOT IN ('cart', 'cart_tracked')";
        $existing = $database->getrow($check_sql, [
            'user_id' => $user_id,
            'company_id' => $company_id
        ]);

        if ($existing) {
            continue; // Skip if already enrolled
        }

        // Insert as cart item
        $insert_sql = "INSERT INTO bg_user_enrollments
                       (user_id, company_id, status, status_method, create_dt, modify_dt)
                       VALUES
                       (:user_id, :company_id, 'cart', 'auto_save', NOW(), NOW())
                       ON DUPLICATE KEY UPDATE
                       status = 'cart',
                       modify_dt = NOW()";

        $database->query($insert_sql, [
            'user_id' => $user_id,
            'company_id' => $company_id
        ]);
    }

    // Add new tracked items to cart
    foreach ($to_add_tracked as $company_id) {
        if (!$company_id) continue;

        // Check if already enrolled with a different status
        $check_sql = "SELECT user_company_id, status
                      FROM bg_user_enrollments
                      WHERE user_id = :user_id
                      AND company_id = :company_id
                      AND status NOT IN ('cart', 'cart_tracked')";
        $existing = $database->getrow($check_sql, [
            'user_id' => $user_id,
            'company_id' => $company_id
        ]);

        if ($existing) {
            continue; // Skip if already tracked/enrolled
        }

        // Insert as cart_tracked item
        $insert_sql = "INSERT INTO bg_user_enrollments
                       (user_id, company_id, status, status_method, create_dt, modify_dt)
                       VALUES
                       (:user_id, :company_id, 'cart_tracked', 'auto_save', NOW(), NOW())
                       ON DUPLICATE KEY UPDATE
                       status = 'cart_tracked',
                       modify_dt = NOW()";

        $database->query($insert_sql, [
            'user_id' => $user_id,
            'company_id' => $company_id
        ]);
    }

    // Commit transaction
    $database->query("COMMIT");

    $response['success'] = true;
    $response['message'] = 'Basket auto-saved successfully';
    $response['saved'] = [
        'picked' => count($picked_ids),
        'tracked' => count($tracked_ids)
    ];

} catch (Exception $e) {
    // Rollback on error
    $database->query("ROLLBACK");

    error_log("Auto-save basket error for user $user_id: " . $e->getMessage());

    $response['message'] = 'Failed to auto-save basket';
}

// Output response
echo json_encode($response);
exit;
?>