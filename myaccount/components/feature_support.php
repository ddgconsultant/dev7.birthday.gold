<?php
/**
 * Feature Support Component
 *
 * Support access (varies by plan - email/chat/community)
 *
 * MODES:
 * - 'setup' (default): Not needed - feature is auto-enabled
 * - 'settings': Returns HTML for settings page display line item
 */

$featuremode = $featuremode ?? 'setup';

if ($featuremode === 'settings') {
    // Get support details from plan
    $plandatafeatures = $app->plandetail('details_id', $current_user_data['account_product_id']);

    // Return settings display HTML
    $is_configured = true; // Always active
    $status_badge = '<span class="badge bg-success-subtle text-success me-2">Active</span>';

    $button_html = '<a href="' . $plandatafeatures['support_link'] . '" target="_blank" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-chat-dots"></i> Get Support
    </a>';

    echo '
    <hr>
    <!-- Feature: Support -->
    <div class="d-flex align-items-center justify-content-between px-4">
        <div class="d-flex align-items-center flex-grow-1">
            <h1><i class="bi bi-wechat"></i></h1>
            <div class="ms-4 flex-grow-1">
                <div class="small fw-bold">Support Access ' . $status_badge . '</div>
                <div class="small">Support through ' . $plandatafeatures['support_tag'] . '</div>
                <div class="text-xs text-muted"><a target="_blank" href="' . $plandatafeatures['support_link'] . '">Click here to get support now.</a></div>
            </div>
        </div>
        <div class="ms-3">
            ' . $button_html . '
        </div>
    </div>
    ';

    return;
}

// Setup mode - this feature auto-configures
// Since support doesn't need actual setup, mark it as configured and redirect to /myaccount/
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Mark the feature as configured
$plandatafeatures = $app->plandetail('details_id', $current_user_data['account_product_id']);
$support_type = $plandatafeatures['support_tag'] ?? 'community';

// Since feature_support column doesn't exist in bg_users, only track in bg_user_attributes
// Track feature completion in bg_user_attributes
$sql = "INSERT INTO bg_user_attributes
        (user_id, type, name, description, status, create_dt, modify_dt)
        VALUES (:user_id, 'feature_completion', :name, :description, 'completed', NOW(), NOW())
        ON DUPLICATE KEY UPDATE
        description = VALUES(description),
        status = 'completed',
        modify_dt = NOW()";
$stmt = $database->prepare($sql);
$stmt->execute([
    'user_id' => $current_user_data['user_id'],
    'name' => 'feature_support_completed',
    'description' => json_encode([
        'feature' => 'feature_support',
        'completed_at' => date('Y-m-d H:i:s'),
        'auto_configured' => true,
        'support_type' => $support_type
    ])
]);

session_tracking('feature_support auto-configured as ' . $support_type . ' - forwarding to myaccount');

// Redirect back to myaccount to check for other features
header('location: /myaccount/');
exit;
