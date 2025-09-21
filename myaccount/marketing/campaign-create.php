<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Create Campaign";

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// Get user's company context
$company_id = $current_user_data['company_id'] ?? 99;
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

// Get platforms for the active company
$platforms_sql = "SELECT * FROM mk_platforms 
                  WHERE company_id = :company_id AND status = 'active'
                  ORDER BY display_order ASC, platform_name ASC";
$platforms = $database->getrows($platforms_sql, ['company_id' => $active_company_id]);

// Check if external platforms exist
$has_external_platforms = false;
if (!empty($platforms)) {
    foreach ($platforms as $platform) {
        if ($platform['platform_type'] != 'birthday_gold_internal') {
            $has_external_platforms = true;
            break;
        }
    }
}

#-------------------------------------------------------------------------------
# DEFINE CAMPAIGN FORM SECTIONS
#-------------------------------------------------------------------------------
$default_sections = [
    'platform' => '/core/forms/marketing/campaign_section_platform.inc',
    'basics' => '/core/forms/marketing/campaign_section_basics.inc'
];

// Conditionally add budget or schedule section based on platform availability
if ($has_external_platforms) {
    $default_sections['budget'] = '/core/forms/marketing/campaign_section_budget.inc';
} else {
    $default_sections['schedule'] = '/core/forms/marketing/campaign_section_schedule.inc';
}

$default_sections['content'] = '/core/forms/marketing/campaign_section_content.inc';
$default_sections['tracking'] = '/core/forms/marketing/campaign_section_tracking.inc';

// Birthday Gold is always available by default, so no need to check for empty platforms
// Users can create campaigns even without external platforms

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $campaign_name = trim($_POST['campaign_name']);
    $platform_id = intval($_POST['platform_id']);
    $campaign_type = trim($_POST['campaign_type']);
    $description = trim($_POST['description'] ?? '');
    $budget = floatval($_POST['budget'] ?? 0);
    $budget_type = trim($_POST['budget_type'] ?? 'total');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');
    $target_audience = trim($_POST['target_audience'] ?? '');
    $campaign_goals = trim($_POST['campaign_goals'] ?? '');
    $campaign_content = trim($_POST['campaign_content'] ?? '');
    $tracking_link_action = trim($_POST['tracking_link_action'] ?? 'forward_signup');
    $destination_url = trim($_POST['destination_url'] ?? '');
    $destination_campaign_id = intval($_POST['destination_campaign_id'] ?? 0) ?: null;
    
    // Verify platform belongs to active company (platform_id 0 is Birthday.Gold internal)
    if ($platform_id == 0) {
        // Birthday.Gold platform is always valid
        $platform_check = true;
    } else {
        $platform_check = $database->getrow(
            "SELECT platform_id FROM mk_platforms WHERE platform_id = :id AND company_id = :company_id",
            ['id' => $platform_id, 'company_id' => $active_company_id]
        );
    }
    
    if (!$platform_check) {
        $error_message = 'Invalid platform selection';
    } else {
        // Generate tracking URL for new campaign  
        $temp_campaign_id = 'new_' . time() . '_' . rand(1000, 9999);
        $generated_tracking_url = 'https://m.bd.gold/?' . base64_encode($temp_campaign_id);
        
        // Insert campaign directly using new schema
        $insert_sql = "INSERT INTO mk_campaigns 
            (company_id, platform_id, create_by, campaign_name, campaign_type, description, 
             campaign_content, target_audience, campaign_goals, budget_amount, budget_type, 
             start_date, end_date, status, tracking_link_action, destination_url, 
             destination_campaign_id, generated_tracking_url) 
            VALUES 
            (:company_id, :platform_id, :create_by, :campaign_name, :campaign_type, :description,
             :campaign_content, :target_audience, :campaign_goals, :budget_amount, :budget_type,
             :start_date, :end_date, :status, :tracking_link_action, :destination_url,
             :destination_campaign_id, :generated_tracking_url)";
        
        try {
            $database->query($insert_sql, [
                'company_id' => $active_company_id,
                'platform_id' => $platform_id,
                'create_by' => $current_user_data['user_id'],
                'campaign_name' => $campaign_name,
                'campaign_type' => $campaign_type,
                'description' => $description,
                'campaign_content' => $campaign_content,
                'target_audience' => $target_audience,
                'campaign_goals' => $campaign_goals,
                'budget_amount' => $budget,
                'budget_type' => $budget_type,
                'start_date' => $start_date ?: null,
                'end_date' => $end_date ?: null,
                'status' => $status,
                'tracking_link_action' => $tracking_link_action,
                'destination_url' => $destination_url ?: null,
                'destination_campaign_id' => $destination_campaign_id,
                'generated_tracking_url' => $generated_tracking_url
            ]);
            
            $campaign_id = $database->lastInsertId();
            
            // Update tracking URL with real campaign ID
            $real_tracking_url = 'https://m.bd.gold/?' . base64_encode($campaign_id);
            $database->query(
                "UPDATE mk_campaigns SET generated_tracking_url = :url WHERE campaign_id = :id",
                ['url' => $real_tracking_url, 'id' => $campaign_id]
            );
            
            // Log activity
            $marketing->logActivity(
                'campaign_created',
                'Campaign Created: ' . $campaign_name,
                'New campaign created for platform',
                $campaign_id,
                'campaign',
                null,
                [
                    'platform_id' => $platform_id,
                    'campaign_type' => $campaign_type,
                    'budget' => $budget,
                    'status' => $status,
                    'company_id' => $active_company_id
                ]
            );
            
            // Check if this is a newsletter campaign and redirect to newsletter creation
            if ($campaign_type === 'newsletter') {
                // Store campaign ID in session for newsletter creation
                $_SESSION['newsletter_campaign_id'] = $campaign_id;
                $encoded_id = $qik->encodeId($campaign_id);
                header("Location: /myaccount/marketing/newsletter-edit.php?campaign_id=" . $encoded_id);
                exit;
            }
            
            header("Location: /myaccount/marketing/campaigns.php");
            exit;
            
        } catch (Exception $e) {
            $error_message = 'Failed to create campaign: ' . $e->getMessage();
        }
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff">
    <div class="container text-center">
        <h1><i class="bi bi-plus-circle"></i> Create New Campaign</h1>
        <p class="lead">Set up a new marketing campaign with tracking and analytics</p>
    </div>
</div>';

// Include marketing tab navigation
include('nav.inc.php');

$additionalstyles .= '
<style>
body {
    margin-bottom: 100px !important;
}

/* Form Section Styling - matching createaccount.php */
.form-section {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.75rem;
    margin-bottom: 1.25rem;
}

@media (min-width: 768px) {
    .form-section {
        padding: 2rem;
    }
}

/* Section Title */
.section-title {
    font-size: 1.15rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e9ecef;
}

/* Form Controls - matching createaccount.php */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.2s ease;
    background-color: #ffffff !important;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-control::placeholder {
    color: #adb5bd;
    opacity: 1;
}

/* Platform Choice Cards */
.platform-choice {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
}

.platform-choice:hover {
    border-color: #0d6efd;
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.15);
    transform: translateY(-2px);
    background: #f8f9ff;
}

.platform-choice.border-primary {
    border-color: #0d6efd !important;
    background-color: #f0f7ff;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.2);
}

.platform-choice .form-check {
    pointer-events: none;
}

/* Card Styling */
.card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.card-header {
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    padding: 1rem 1.5rem;
    border-radius: 12px 12px 0 0 !important;
}

.card-body {
    padding: 2rem;
}

/* Buttons - matching createaccount.php */
.btn {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.btn-lg {
    padding: 0.875rem 2rem;
    font-size: 1.1rem;
}

.btn-success {
    background: #198754;
    border: none;
}

.btn-success:hover {
    background: #157347;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3);
}

.btn-outline-secondary {
    border: 2px solid #e9ecef;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background: #f8f9fa;
    border-color: #6c757d;
    color: #495057;
}

/* Alert Styling */
.alert {
    border-radius: 8px;
    border: 1px solid;
}

/* Small text helpers */
.text-muted {
    color: #6c757d !important;
}

small.text-muted {
    font-size: 0.875rem;
}

/* Radio and Checkbox styling */
.form-check-input {
    width: 1.2rem;
    height: 1.2rem;
    border: 2px solid #e9ecef;
    margin-top: 0.25rem;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.form-check-label {
    margin-left: 0.5rem;
    color: #495057;
    font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem;
    }
    
    .form-section {
        padding: 1.25rem;
    }
    
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
}
</style>
';

echo '
<div class="container mb-5">
    <div class="row mb-3 mt-4">
        <div class="col-12 text-end">
            <a href="/myaccount/marketing/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Campaign Configuration</h5>
                </div>
                <div class="card-body">';

if (isset($error_message)) {
    echo '
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>' . htmlspecialchars($error_message) . '
                    </div>';
}

echo '
                    <form method="POST">';

// Display form sections using modular approach
foreach ($default_sections as $section_name => $section_file) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $section_file)) {
        include($_SERVER['DOCUMENT_ROOT'] . $section_file);
    }
}

echo '
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="/myaccount/marketing/" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-plus-circle me-2"></i>Create Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>