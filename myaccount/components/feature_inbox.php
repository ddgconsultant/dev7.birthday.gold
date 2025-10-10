<?php
/**
 * Feature Inbox Component
 *
 * Email Management Dashboard - View and manage birthday emails
 *
 * MODES:
 * - 'setup' (default): Full setup page
 * - 'settings': Returns HTML for settings page display line item
 */

$featuremode = $featuremode ?? 'setup';

if ($featuremode === 'settings') {
    // Return settings display HTML
    $is_configured = !empty($current_user_data['feature_inbox']);

    $status_badge = $is_configured
        ? '<span class="badge bg-success-subtle text-success me-2">Configured</span>'
        : '<span class="badge bg-warning-subtle text-warning me-2">Not Configured</span>';

    $button_html = '';
    if ($is_configured) {
        $button_html = '<a href="/myaccount/mail-box" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-gear"></i> Configure
        </a>';
    } else {
        $button_html = '<a href="/myaccount/components/feature_inbox.php" class="btn btn-sm btn-primary">
            <i class="bi bi-wrench"></i> Setup Required
        </a>';
    }

    echo '
    <hr>
    <!-- Feature: Email Management Dashboard -->
    <div class="d-flex align-items-center justify-content-between px-4">
        <div class="d-flex align-items-center flex-grow-1">
            <h1><i class="bi bi-inbox-fill"></i></h1>
            <div class="ms-4 flex-grow-1">
                <div class="small fw-bold">Email Management Dashboard ' . $status_badge . '</div>
                <div class="text-xs text-muted">View and manage all birthday-related emails in one place</div>
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

// Since inbox doesn't need manual setup and the column doesn't exist in bg_users,
// we just track it as completed in bg_user_attributes
// The actual inbox functionality is at /myaccount/mail-box

// Track feature completion in bg_user_attributes (this is our persistent tracking)
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
    'name' => 'feature_inbox_completed',
    'description' => json_encode([
        'feature' => 'feature_inbox',
        'completed_at' => date('Y-m-d H:i:s'),
        'auto_configured' => true,
        'note' => 'Auto-enabled - uses mail-box interface'
    ])
]);

session_tracking('feature_inbox auto-configured - forwarding to myaccount');

// Redirect back to myaccount to check for other features
header('location: /myaccount/');
exit;
