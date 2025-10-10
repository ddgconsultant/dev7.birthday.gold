<?php
/**
 * Feature Advanced Analytics Component
 *
 * Detailed insights and statistics about birthday rewards
 *
 * MODES:
 * - 'setup' (default): Redirects to myaccount (no setup needed)
 * - 'settings': Returns HTML for settings page display line item
 */

$featuremode = $featuremode ?? 'setup';

if ($featuremode === 'settings') {
    // Return settings display HTML
    $is_configured = true; // Always configured if they have access

    $status_badge = '<span class="badge bg-warning-subtle text-warning me-2">Coming Soon</span>';

    $button_html = '<span class="btn btn-sm btn-outline-secondary disabled">
        <i class="bi bi-graph-up-arrow"></i> Coming Soon
    </span>';

    echo '
    <hr>
    <!-- Feature: Advanced Birthday Analytics -->
    <div class="d-flex align-items-center justify-content-between px-4">
        <div class="d-flex align-items-center flex-grow-1">
            <h1><i class="bi bi-graph-up-arrow"></i></h1>
            <div class="ms-4 flex-grow-1">
                <div class="small fw-bold">Advanced Birthday Analytics ' . $status_badge . '</div>
                <div class="text-xs text-muted">Portfolio insights and reward statistics (coming soon)</div>
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

// Since advanced analytics doesn't need setup and has no column in bg_users,
// just track completion in bg_user_attributes

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
    'name' => 'feature_advanced_analytics_completed',
    'description' => json_encode([
        'feature' => 'feature_advanced_analytics',
        'completed_at' => date('Y-m-d H:i:s'),
        'auto_configured' => true,
        'note' => 'Auto-enabled - feature in development'
    ])
]);

session_tracking('feature_advanced_analytics auto-configured - forwarding to myaccount');

// Redirect back to myaccount to check for other features
header('Location: /myaccount/');
exit;
