<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$campaign_id = isset($_GET['id']) ? $qik->decodeId($_GET['id']) : 0;
$company_id = $current_user_data['company_id'] ?? 99;
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

if (!$campaign_id) {
    header('Location: /myaccount/marketing/campaigns.php');
    exit;
}

// Get campaign details
$campaign_sql = "SELECT c.*, p.platform_name, p.icon_class 
                 FROM mk_campaigns c
                 LEFT JOIN mk_platforms p ON c.platform_id = p.platform_id
                 WHERE c.campaign_id = :campaign_id AND c.company_id = :company_id";
$campaign = $database->getrow($campaign_sql, [
    'campaign_id' => $campaign_id,
    'company_id' => $active_company_id
]);

if (!$campaign) {
    header('Location: /myaccount/marketing/campaigns.php');
    exit;
}

// Check if this is a newsletter campaign and redirect to newsletter editor
if ($campaign['campaign_type'] === 'newsletter') {
    // Find the associated newsletter
    $newsletter_sql = "SELECT campaign_id FROM bg_newsletter_campaigns WHERE mk_campaign_id = :mk_campaign_id LIMIT 1";
    $newsletter = $database->getrow($newsletter_sql, ['mk_campaign_id' => $campaign_id]);
    
    if ($newsletter) {
        // Redirect to newsletter edit page with encoded ID
        $newsletter_id = $qik->encodeId($newsletter['campaign_id']);
        header('Location: /myaccount/marketing/newsletter-edit.php?id=' . $newsletter_id);
        exit;
    } else {
        // No newsletter created yet, redirect to create one
        $encoded_campaign_id = $qik->encodeId($campaign_id);
        header('Location: /myaccount/marketing/newsletter-edit.php?campaign_id=' . $encoded_campaign_id);
        exit;
    }
}

$pagetitle = "Edit Campaign - " . ($campaign['campaign_name'] ?? 'Unknown');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $campaign_name = trim($_POST['campaign_name'] ?? '');
    $campaign_type = trim($_POST['campaign_type'] ?? '');
    $budget_amount = floatval($_POST['budget_amount'] ?? 0);
    $budget_type = trim($_POST['budget_type'] ?? 'total');
    $status = trim($_POST['status'] ?? 'draft');
    $description = trim($_POST['description'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $target_audience = trim($_POST['target_audience'] ?? '');
    $campaign_goals = trim($_POST['campaign_goals'] ?? '');
    $ad_content = trim($_POST['ad_content'] ?? '');
    $tracking_link_action = trim($_POST['tracking_link_action'] ?? 'forward_signup');
    $destination_url = trim($_POST['destination_url'] ?? '');
    $destination_campaign_id = intval($_POST['destination_campaign_id'] ?? 0) ?: null;
    
    // Generate tracking URL if not already exists
    $generated_tracking_url = $campaign['generated_tracking_url'];
    if (empty($generated_tracking_url)) {
        // Generate a unique tracking URL using short bd.gold domain
        $generated_tracking_url = 'https://m.bd.gold/?' . base64_encode($campaign_id);
    }
    
    $update_sql = "UPDATE mk_campaigns SET 
                   campaign_name = :campaign_name,
                   campaign_type = :campaign_type,
                   budget_amount = :budget_amount,
                   budget_type = :budget_type,
                   status = :status,
                   description = :description,
                   start_date = :start_date,
                   end_date = :end_date,
                   target_audience = :target_audience,
                   campaign_goals = :campaign_goals,
                   ad_content = :ad_content,
                   tracking_link_action = :tracking_link_action,
                   destination_url = :destination_url,
                   destination_campaign_id = :destination_campaign_id,
                   generated_tracking_url = :generated_tracking_url,
                   updated_dt = NOW()
                   WHERE campaign_id = :campaign_id AND company_id = :company_id";
    
    $update_params = [
        'campaign_name' => $campaign_name,
        'campaign_type' => $campaign_type,
        'budget_amount' => $budget_amount,
        'budget_type' => $budget_type,
        'status' => $status,
        'description' => $description,
        'start_date' => $start_date ?: null,
        'end_date' => $end_date ?: null,
        'target_audience' => $target_audience,
        'campaign_goals' => $campaign_goals,
        'ad_content' => $ad_content,
        'tracking_link_action' => $tracking_link_action,
        'destination_url' => $destination_url ?: null,
        'destination_campaign_id' => $destination_campaign_id,
        'generated_tracking_url' => $generated_tracking_url,
        'campaign_id' => $campaign_id,
        'company_id' => $active_company_id
    ];
    
    if ($database->query($update_sql, $update_params)) {
        header("Location: /myaccount/marketing/campaigns.php?updated=1");
        exit;
    } else {
        $error_message = 'Failed to update campaign';
    }
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}
.form-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-pencil me-3"></i>Edit Campaign</h1>
        <p class="lead">Update campaign details and settings</p>
    </div>
</div>';

// Include marketing tab navigation
include('nav.inc.php');

echo '
<div class="container mb-5">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>' . htmlspecialchars($campaign['campaign_name'] ?? '') . '</h2>
                    <div class="text-muted">
                        <i class="' . htmlspecialchars($campaign['icon_class'] ?? 'bi bi-link') . ' me-1"></i>
                        ' . htmlspecialchars($campaign['platform_name'] ?? 'Unknown Platform') . '
                    </div>
                </div>
                <div>
                    <a href="/myaccount/marketing/campaigns" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Campaigns
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Campaign Details</h5>
                </div>
                <div class="card-body">';

if (isset($error_message)) {
    echo '
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> ' . htmlspecialchars($error_message) . '
                    </div>';
}

echo '
                    <form method="POST">
                        <div class="form-section">
                            <h6 class="text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Campaign Details</h6>
                            
                            <div class="mb-3">
                                <label for="campaign_name" class="form-label">Campaign Name *</label>
                                <input type="text" class="form-control" id="campaign_name" name="campaign_name" required
                                       value="' . htmlspecialchars($campaign['campaign_name'] ?? '') . '">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="campaign_type" class="form-label">Campaign Type *</label>
                                        <select class="form-control" id="campaign_type" name="campaign_type" required>';

$campaign_types = [
    'awareness' => 'Brand Awareness',
    'traffic' => 'Website Traffic', 
    'engagement' => 'Engagement',
    'leads' => 'Lead Generation',
    'conversions' => 'Conversions',
    'app_installs' => 'App Installs',
    'video_views' => 'Video Views',
    'reach' => 'Reach'
];

foreach ($campaign_types as $value => $label) {
    $selected = ($campaign['campaign_type'] == $value) ? ' selected' : '';
    echo '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
}

echo '
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-control" id="status" name="status">';

$status_options = [
    'draft' => 'Draft',
    'active' => 'Active',
    'paused' => 'Paused', 
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'archived' => 'Archived'
];

foreach ($status_options as $value => $label) {
    $selected = ($campaign['status'] == $value) ? ' selected' : '';
    echo '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
}

echo '
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Campaign Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">' . 
                                htmlspecialchars($campaign['description'] ?? '') . '</textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6 class="text-success mb-3"><i class="bi bi-currency-dollar me-2"></i>Budget & Timeline</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget_amount" class="form-label">Budget ($)</label>
                                        <input type="number" class="form-control" id="budget_amount" name="budget_amount" 
                                               min="0" step="0.01" value="' . htmlspecialchars($campaign['budget_amount'] ?? '') . '">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget_type" class="form-label">Budget Type</label>
                                        <select class="form-control" id="budget_type" name="budget_type">';

$budget_types = [
    'total' => 'Total Budget',
    'daily' => 'Daily Budget',
    'monthly' => 'Monthly Budget'
];

foreach ($budget_types as $value => $label) {
    $selected = ($campaign['budget_type'] == $value) ? ' selected' : '';
    echo '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
}

echo '
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                               value="' . htmlspecialchars($campaign['start_date'] ?? '') . '">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                               value="' . htmlspecialchars($campaign['end_date'] ?? '') . '">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6 class="text-info mb-3"><i class="bi bi-people me-2"></i>Targeting & Content</h6>
                            
                            <div class="mb-3">
                                <label for="target_audience" class="form-label">Target Audience</label>
                                <textarea class="form-control" id="target_audience" name="target_audience" rows="2">' . 
                                htmlspecialchars($campaign['target_audience'] ?? '') . '</textarea>
                                <small class="text-muted">Demographics, interests, behaviors, custom audiences</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="campaign_goals" class="form-label">Campaign Goals</label>
                                <textarea class="form-control" id="campaign_goals" name="campaign_goals" rows="2">' . 
                                htmlspecialchars($campaign['campaign_goals'] ?? '') . '</textarea>
                                <small class="text-muted">Specific objectives and success metrics</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ad_content" class="form-label">Ad Content & Creative Notes</label>
                                <textarea class="form-control" id="ad_content" name="ad_content" rows="4">' . 
                                htmlspecialchars($campaign['ad_content'] ?? '') . '</textarea>
                                <small class="text-muted">Ad copy, creative concepts, image/video specifications</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6 class="text-warning mb-3"><i class="bi bi-link-45deg me-2"></i>Tracking & Destination</h6>
                            
                            <div class="mb-3">
                                <label for="tracking_link_action" class="form-label">When someone clicks your tracking link *</label>
                                <select class="form-control" id="tracking_link_action" name="tracking_link_action" required>';

$tracking_actions = [
    'forward_signup' => 'Forward to Birthday.Gold Signup Page',
    'forward_url' => 'Forward to Custom URL',
    'forward_campaign' => 'Forward to Another Campaign',
    'track_only' => 'Just Track (Show Confirmation Page)'
];

$current_action = $campaign['tracking_link_action'] ?? 'forward_signup';
foreach ($tracking_actions as $value => $label) {
    $selected = ($current_action == $value) ? ' selected' : '';
    echo '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
}

echo '
                                </select>
                                <small class="text-muted">This determines where people go after clicking your campaign link</small>
                            </div>
                            
                            <div class="mb-3" id="destination_url_field" style="display: none;">
                                <label for="destination_url" class="form-label">Destination URL</label>
                                <input type="url" class="form-control" id="destination_url" name="destination_url" 
                                       value="' . htmlspecialchars($campaign['destination_url'] ?? '') . '">
                                <small class="text-muted">The URL where people should be redirected</small>
                            </div>
                            
                            <div class="mb-3" id="destination_campaign_field" style="display: none;">
                                <label for="destination_campaign_id" class="form-label">Destination Campaign</label>
                                <select class="form-control" id="destination_campaign_id" name="destination_campaign_id">';

// Get other campaigns for this company
$other_campaigns = $database->getrows(
    "SELECT campaign_id, campaign_name FROM mk_campaigns 
     WHERE company_id = :company_id AND campaign_id != :current_id AND status != 'archived'
     ORDER BY campaign_name", 
    ['company_id' => $active_company_id, 'current_id' => $campaign_id]
);

echo '<option value="">Select campaign...</option>';
$current_dest_campaign = $campaign['destination_campaign_id'] ?? 0;
foreach ($other_campaigns as $other_campaign) {
    $selected = ($current_dest_campaign == $other_campaign['campaign_id']) ? ' selected' : '';
    echo '<option value="' . $other_campaign['campaign_id'] . '"' . $selected . '>' . htmlspecialchars($other_campaign['campaign_name']) . '</option>';
}

echo '
                                </select>
                                <small class="text-muted">Chain campaigns together</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Your Tracking Link</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="generated_tracking_url" 
                                           value="' . htmlspecialchars($campaign['generated_tracking_url'] ?? 'https://m.bd.gold/?' . base64_encode($campaign_id)) . '" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyTrackingLink()">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </div>
                                <small class="text-muted">Use this link in your marketing campaigns to track performance</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/myaccount/marketing/campaigns" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-lg me-2"></i>Update Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const trackingActionSelect = document.getElementById("tracking_link_action");
    const destinationUrlField = document.getElementById("destination_url_field");
    const destinationCampaignField = document.getElementById("destination_campaign_field");
    
    function updateDestinationFields() {
        const action = trackingActionSelect.value;
        
        // Hide all conditional fields first
        destinationUrlField.style.display = "none";
        destinationCampaignField.style.display = "none";
        
        // Show appropriate field based on selection
        if (action === "forward_url") {
            destinationUrlField.style.display = "block";
        } else if (action === "forward_campaign") {
            destinationCampaignField.style.display = "block";
        }
    }
    
    // Initialize field visibility
    updateDestinationFields();
    
    // Update when selection changes
    trackingActionSelect.addEventListener("change", updateDestinationFields);
});

function copyTrackingLink() {
    const trackingUrl = document.getElementById("generated_tracking_url");
    trackingUrl.select();
    trackingUrl.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand("copy");
        // Show temporary success message
        const button = event.target.closest("button");
        const originalText = button.innerHTML;
        button.innerHTML = "<i class=\"bi bi-check\"></i> Copied!";
        button.classList.remove("btn-outline-secondary");
        button.classList.add("btn-success");
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove("btn-success");
            button.classList.add("btn-outline-secondary");
        }, 2000);
    } catch (err) {
        console.error("Failed to copy: ", err);
    }
}
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>