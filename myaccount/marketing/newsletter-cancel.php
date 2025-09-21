<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$campaign_id = isset($_GET['id']) ? $qik->decodeId($_GET['id']) : 0;

if (!$campaign_id) {
    header('Location: /myaccount/marketing/campaigns.php');
    exit;
}

// Get campaign details
$campaign_sql = "SELECT * FROM mk_campaigns 
                 WHERE campaign_id = :campaign_id 
                 AND campaign_type = 'newsletter'";
$campaign = $database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);

if (!$campaign) {
    $_SESSION['message'] = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Newsletter not found.</div>';
    header('Location: /myaccount/marketing/campaigns.php');
    exit;
}

// Check if newsletter can be cancelled (only scheduled, queued, active, sending)
$cancellable_statuses = ['scheduled', 'queued', 'active', 'sending'];
if (!in_array($campaign['status'], $cancellable_statuses)) {
    $_SESSION['message'] = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> This newsletter cannot be cancelled. Current status: ' . $campaign['status'] . '</div>';
    header('Location: /myaccount/marketing/campaigns.php');
    exit;
}

// Cancel the newsletter
$update_sql = "UPDATE mk_campaigns 
               SET status = 'cancelled',
                   newsletter_status = 'cancelled',
                   modify_dt = NOW(),
                   campaign_config = JSON_SET(
                       COALESCE(campaign_config, '{}'),
                       '$.cancelled_at', :cancelled_at,
                       '$.cancelled_by', :cancelled_by,
                       '$.previous_status', :previous_status
                   )
               WHERE campaign_id = :campaign_id";

$database->query($update_sql, [
    'campaign_id' => $campaign_id,
    'cancelled_at' => date('Y-m-d H:i:s'),
    'cancelled_by' => $current_user_data['user_id'] ?? 0,
    'previous_status' => $campaign['status']
]);

// Cancel any pending notifications for this campaign
$notification_update = "UPDATE bg_user_notifications 
                       SET status = 'cancelled' 
                       WHERE category = :category 
                       AND type = 'newsletter'
                       AND status IN ('pending', 'notsent')";

try {
    $database->query($notification_update, ['category' => 'campaign_' . $campaign_id]);
} catch (Exception $e) {
    // Log error but continue
    error_log("Failed to cancel notifications for campaign $campaign_id: " . $e->getMessage());
}

// Log the cancellation in activities
$activity_sql = "INSERT INTO mk_activities 
                 (company_id, create_by, activity_type, activity_title, activity_description,
                  related_campaign_id, activity_date, metadata) 
                 VALUES 
                 (:company_id, :create_by, 'newsletter_cancelled', :title, :description,
                  :campaign_id, NOW(), :metadata)";

try {
    $database->query($activity_sql, [
        'company_id' => $campaign['company_id'],
        'create_by' => $current_user_data['user_id'] ?? 0,
        'title' => 'Newsletter Cancelled: ' . $campaign['campaign_name'],
        'description' => 'Newsletter "' . $campaign['campaign_name'] . '" was cancelled. Previous status: ' . $campaign['status'],
        'campaign_id' => $campaign_id,
        'metadata' => json_encode([
            'previous_status' => $campaign['status'],
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => $current_user_data['user_id'] ?? 0
        ])
    ]);
} catch (Exception $e) {
    // Activity logging failed, but newsletter is still cancelled
}

$_SESSION['message'] = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Newsletter "' . htmlspecialchars($campaign['campaign_name']) . '" has been cancelled successfully.</div>';
header('Location: /myaccount/marketing/campaigns.php');
exit;
?>