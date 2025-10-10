<?php
/**
 * Feature Birthday Reminders Component
 *
 * Reminders of upcoming birthday benefits
 *
 * MODES:
 * - 'setup' (default): Not needed - feature is auto-enabled
 * - 'settings': Returns HTML for settings page display line item
 */

$featuremode = $featuremode ?? 'setup';

if ($featuremode === 'settings') {
    // Return settings display HTML
    $is_configured = true; // Always active
    $status_badge = '<span class="badge bg-success-subtle text-success me-2">Active</span>';

    $button_html = '<a href="/myaccount/notifications#settings" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-bell"></i> Configure
    </a>';

    echo '
    <hr>
    <!-- Feature: Birthday Reminders -->
    <div class="d-flex align-items-center justify-content-between px-4">
        <div class="d-flex align-items-center flex-grow-1">
            <h1><i class="bi bi-alarm"></i></h1>
            <div class="ms-4 flex-grow-1">
                <div class="small fw-bold">Birthday Reminders ' . $status_badge . '</div>
                <div class="text-xs text-muted">Don\'t miss out on any freebies!</div>
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

// Birthday reminders are auto-enabled, no column in bg_users so just track in attributes

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
    'name' => 'feature_birthday_reminders_completed',
    'description' => json_encode([
        'feature' => 'feature_birthday_reminders',
        'completed_at' => date('Y-m-d H:i:s'),
        'auto_configured' => true,
        'note' => 'Auto-enabled - configured via notifications'
    ])
]);

session_tracking('feature_birthday_reminders auto-configured - forwarding to myaccount');

// Redirect back to myaccount to check for other features
header('location: /myaccount/');
exit;
