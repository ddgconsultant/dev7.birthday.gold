<?php
/**
 * Feature Celebration Tour Component
 *
 * Celebration planning and tour scheduling
 *
 * MODES:
 * - 'setup' (default): Not needed - feature is auto-enabled
 * - 'settings': Returns HTML for settings page display line item
 */

$featuremode = $featuremode ?? 'setup';

if ($featuremode === 'settings') {
    // Get celebration tour details
    $plandatafeatures = $app->plandetail('details_id', $current_user_data['account_product_id']);

    $daysouttag = $plandatafeatures['celebration_tour_option_tag'];
    $daysout = $plandatafeatures['celebration_planning_days'];

    $nextDate = $app->calculateNextOccurrence($current_user_data['birthdate'], $daysout);
    $outdays = $app->getTimeTilBirthday($nextDate['date']);

    // Return settings display HTML
    $is_configured = true; // Always active if they have the feature
    $status_badge = '<span class="badge bg-success-subtle text-success me-2">Active</span>';

    $button_html = '<a href="/myaccount/celebration-tour" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-calendar-event"></i> Plan Tour
    </a>';

    echo '
    <hr>
    <!-- Feature: Celebration Tour -->
    <div class="d-flex align-items-center justify-content-between px-4">
        <div class="d-flex align-items-center flex-grow-1">
            <h1><i class="bi bi-calendar3"></i></h1>
            <div class="ms-4 flex-grow-1">
                <div class="small fw-bold">Celebration Tour ' . $status_badge . '</div>
                <div class="small">' . $plandatafeatures['celebration_tour_option_tag'] . '</div>
                <div class="text-xs text-muted">You can start your planning in ' . $outdays['days'] . ' ' . $qik->plural('day', $outdays['days']) . '</div>
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

// Celebration tour is auto-enabled, no column in bg_users so just track in attributes

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
    'name' => 'feature_celebration_tour_completed',
    'description' => json_encode([
        'feature' => 'feature_celebration_tour',
        'completed_at' => date('Y-m-d H:i:s'),
        'auto_configured' => true,
        'note' => 'Auto-enabled - managed via celebration-tour page'
    ])
]);

session_tracking('feature_celebration_tour auto-configured - forwarding to myaccount');

// Redirect back to myaccount to check for other features
header('location: /myaccount/');
exit;
