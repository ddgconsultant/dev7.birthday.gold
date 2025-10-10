<?php
/**
 * Feature Business Enrollments Component
 *
 * Display business enrollment limits and usage
 *
 * MODES:
 * - 'setup' (default): Not needed - feature is auto-enabled
 * - 'settings': Returns HTML for settings page display line item
 */

$featuremode = $featuremode ?? 'setup';

if ($featuremode === 'settings') {
    // Get enrollment statistics
    $accountstats = $account->account_getstats();
    $plandatafeatures = $app->plandetail('details_id', $current_user_data['account_product_id']);

    $selectsused = ($accountstats['business_pending'] + $accountstats['business_selected'] + $accountstats['business_success']);
    $selectsleft = ($plandatafeatures['max_business_select'] - $selectsused);

    $tag1 = $plandatafeatures['max_business_select_tag'];

    // Return settings display HTML
    $is_configured = true; // Always active
    $status_badge = '<span class="badge bg-success-subtle text-success me-2">Active</span>';

    $button_html = '<a href="/myaccount/businesses" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-building"></i> Manage
    </a>';

    echo '
    <hr>
    <!-- Feature: Business Enrollments -->
    <div class="d-flex align-items-center justify-content-between px-4">
        <div class="d-flex align-items-center flex-grow-1">
            <h1><i class="bi bi-bag-heart"></i></h1>
            <div class="ms-4 flex-grow-1">
                <div class="small fw-bold">Business Enrollments ' . $status_badge . '</div>
                <div class="small">You can select up to ' . $plandatafeatures['max_business_select'] . ' ' . $website['biznames'] . ' in your plan. ' . $tag1 . '</div>
                <div class="text-xs text-muted">You are using ' . $selectsused . ' and have ' . ($selectsleft < 0 ? 0 : $selectsleft) . ' left.</div>
            </div>
        </div>
        <div class="ms-3">
            ' . $button_html . '
        </div>
    </div>
    ';

    return;
}

// Setup mode - auto-configure and redirect
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Business enrollments are auto-enabled, no column in bg_users so just track in attributes

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
    'name' => 'feature_business_enrollments_completed',
    'description' => json_encode([
        'feature' => 'feature_business_enrollments',
        'completed_at' => date('Y-m-d H:i:s'),
        'auto_configured' => true,
        'note' => 'Auto-enabled - managed via businesses page'
    ])
]);

session_tracking('feature_business_enrollments auto-configured - forwarding to myaccount');

// Redirect back to myaccount to check for other features
header('location: /myaccount/');
exit;
