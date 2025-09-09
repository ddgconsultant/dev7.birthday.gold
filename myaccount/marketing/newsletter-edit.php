<?PHP
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Create/Edit Newsletter";
$campaign_id = isset($_GET['id']) ? $qik->decodeId($_GET['id']) : 0;
$mk_campaign_id = isset($_GET['campaign_id']) ? $qik->decodeId($_GET['campaign_id']) : 0;
$campaign = null;
$mk_campaign = null;

// Check if coming from marketing campaign creation
if ($mk_campaign_id > 0) {
    $mk_campaign_sql = "SELECT * FROM mk_campaigns WHERE campaign_id = :campaign_id";
    $mk_campaign = $database->getrow($mk_campaign_sql, ['campaign_id' => $mk_campaign_id]);
    
    if ($mk_campaign) {
        $pagetitle = "Create Newsletter: " . $mk_campaign['campaign_name'];
    }
}

// If editing, load existing campaign from mk_campaigns
if ($campaign_id > 0) {
    $campaign_sql = "SELECT *, 
                            campaign_name as title,
                            email_subject as subject,
                            campaign_content as body_html,
                            start_date as send_dt,
                            newsletter_status as status
                     FROM mk_campaigns 
                     WHERE campaign_id = :campaign_id 
                     AND campaign_type = 'newsletter'";
    $campaign = $database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);
    
    if (!$campaign) {
        header('Location: /myaccount/marketing/campaigns.php');
        exit;
    }
    
    // We're already in mk_campaigns, so this IS the marketing campaign
    $mk_campaign = $campaign;
    $mk_campaign_id = $campaign_id;
    
    $pagetitle = "Edit Newsletter: " . $campaign['title'];
    
    // Debug recipient criteria
    error_log("Campaign ID: " . $campaign_id);
    error_log("Recipient Criteria from DB: " . ($campaign['recipient_criteria'] ?? 'NULL'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $body_html = $_POST['body_html'];
    $cta_category = $_POST['cta_category'];
    $cta_mode = isset($_POST['cta_mode']) ? $_POST['cta_mode'] : 'inclusive';
    $gen_specific_messaging = isset($_POST['gen_specific_messaging']) ? 1 : 0;
    $send_dt = $_POST['send_date'] . ' ' . $_POST['send_time'] . ':00';
    $status = $_POST['action'] == 'schedule' ? 'scheduled' : 'draft';
    $recipient_criteria = isset($_POST['recipient_criteria']) ? $_POST['recipient_criteria'] : '{"type":"all"}';
    
    if ($campaign_id > 0) {
        // Update existing campaign in mk_campaigns
        $update_sql = "UPDATE mk_campaigns SET 
            campaign_name = :campaign_name,
            email_subject = :email_subject,
            campaign_content = :campaign_content,
            cta_category = :cta_category,
            cta_mode = :cta_mode,
            gen_specific_messaging = :gen_specific_messaging,
            recipient_criteria = :recipient_criteria,
            start_date = :start_date,
            newsletter_status = :newsletter_status,
            modify_dt = NOW()
            WHERE campaign_id = :campaign_id
            AND campaign_type = 'newsletter'";
        
        $database->query($update_sql, [
            'campaign_name' => $title,
            'email_subject' => $subject,
            'campaign_content' => $body_html,
            'cta_category' => $cta_category,
            'cta_mode' => $cta_mode,
            'gen_specific_messaging' => $gen_specific_messaging,
            'recipient_criteria' => $recipient_criteria,
            'start_date' => $send_dt,
            'newsletter_status' => $status,
            'campaign_id' => $campaign_id
        ]);
        
        $_SESSION['message'] = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Newsletter updated successfully!</div>';
    } else {
        // Create new campaign directly in mk_campaigns
        $company_id = isset($current_user_data['company_id']) ? $current_user_data['company_id'] : 1;
        
        // Platform ID 1 is typically the Birthday.Gold platform
        $platform_id = 1;
        
        $insert_sql = "INSERT INTO mk_campaigns 
            (company_id, platform_id, campaign_type, campaign_name, email_subject, campaign_content, 
             cta_category, cta_mode, gen_specific_messaging, recipient_criteria, 
             start_date, newsletter_status, status, create_by, create_dt) 
            VALUES 
            (:company_id, :platform_id, 'newsletter', :campaign_name, :email_subject, :campaign_content,
             :cta_category, :cta_mode, :gen_specific_messaging, :recipient_criteria,
             :start_date, :newsletter_status, 'active', :create_by, NOW())";
        
        $database->query($insert_sql, [
            'company_id' => $company_id,
            'platform_id' => $platform_id,
            'campaign_name' => $title,
            'email_subject' => $subject,
            'campaign_content' => $body_html,
            'cta_category' => $cta_category,
            'cta_mode' => $cta_mode,
            'gen_specific_messaging' => $gen_specific_messaging,
            'recipient_criteria' => $recipient_criteria,
            'start_date' => $send_dt,
            'newsletter_status' => $status,
            'create_by' => isset($current_user_data['user_id']) ? $current_user_data['user_id'] : 0
        ]);
        
        $campaign_id = $database->lastInsertId();
        $mk_campaign_id = $campaign_id; // They're the same now!
        
        // If scheduled, populate the queue
        if ($status == 'scheduled') {
            // Get all active users who are not unsubscribed
            $users_sql = "SELECT user_id FROM bg_users 
                         WHERE status = 'active' 
                         AND user_id NOT IN (SELECT user_id FROM bg_unsubscribes)";
            $users = $database->getrows($users_sql);
            
            // Populate queue
            foreach ($users as $user) {
                $queue_sql = "INSERT INTO mk_newsletter_queue 
                             (campaign_id, user_id, scheduled_dt, status) 
                             VALUES 
                             (:campaign_id, :user_id, :scheduled_dt, 'pending')";
                
                $database->query($queue_sql, [
                    'campaign_id' => $campaign_id,
                    'user_id' => $user['user_id'],
                    'scheduled_dt' => $send_dt
                ]);
            }
        }
        
        $_SESSION['message'] = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Newsletter created successfully!</div>';
    }
    
    header('Location: /myaccount/marketing/campaigns.php');
    exit;
}

// Get categories for dropdown
$categories = ['Food', 'Retail', 'pizza', 'coffee', 'beauty', 'restaurant', 'entertainment', 'health', 'other'];

// Get TinyMCE API key - following same pattern as legal-policy-editor.php
$tinymce_api_key = '';
if (isset($sitesettings['tinymce']['api_key'])) {
    $tinymce_api_key = $sitesettings['tinymce']['api_key'];
} elseif (isset($sitesettings['tinymce_api_key'])) {
    $tinymce_api_key = $sitesettings['tinymce_api_key'];
} 

// Fallback to no-api-key if not configured
if (empty($tinymce_api_key)) {
    $tinymce_api_key = 'no-api-key';
}

// Add styles for bottom margin and consistent form styling
$additionalstyles = '
<style>
body { 
    margin-bottom: 100px !important; 
    padding-bottom: 50px !important; 
}

.tox-tinymce {
    border: 2px solid #e9ecef !important;
    border-radius: 8px !important;
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
        padding: 2.5rem;
    }
}

/* Form Controls - matching createaccount.php styling */
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

/* Input Groups */
.input-group .form-control {
    border-right: none;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-group .btn {
    border: 2px solid #e9ecef;
    border-left: none;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.input-group .form-control:focus ~ .btn {
    border-color: #0d6efd;
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

/* Buttons - matching createaccount.php */
.btn-primary, .btn-success {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.btn-primary:hover, .btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-outline-secondary {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-outline-secondary:hover {
    background: #f8f9fa;
    border-color: #6c757d;
}

/* Card styling enhancement */
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
}

/* Recipient builder specific */
#recipientBuilder {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
}

.segment-btn {
    border-radius: 6px;
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    transition: all 0.2s ease;
}

.segment-btn:hover {
    transform: translateY(-1px);
}

/* Small text helpers */
small.text-muted {
    font-size: 0.875rem;
    color: #6c757d !important;
}

/* Alert styling */
.alert {
    border-radius: 8px;
    border: 1px solid;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-envelope-fill"></i> Newsletter System</h1>
        <p class="lead">Design and schedule your newsletter</p>
    </div>
</div>';

// Include marketing navigation
include('nav.inc.php');

echo '
<div class="container mt-4">
    
    <form method="POST" id="campaignForm">';

if ($mk_campaign_id > 0) {
    echo '<input type="hidden" name="mk_campaign_id" value="' . $mk_campaign_id . '">';
}

echo '
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Newsletter Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Newsletter Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="' . ($campaign ? htmlspecialchars($campaign['title']) : ($mk_campaign ? htmlspecialchars($mk_campaign['campaign_name']) : 'Grab Dinner with Your Family')) . '" required>
                        <small class="text-muted">Internal reference only - not shown to recipients</small>
                    </div>
                    <div class="col-md-6">
                        <label for="subject" class="form-label">Email Subject</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="' . ($campaign ? htmlspecialchars($campaign['subject']) : '') . '" required>
                            <button type="button" class="btn btn-outline-success" id="aiGenerateBtn" title="AI Generate Content">
                                <i class="bi bi-magic"></i> AI Generate
                            </button>
                        </div>
                        <small class="text-muted">Available placeholders: [[first_name]], [[city]], [[birthday_month]]</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="cta_category" class="form-label">CTA '.ucwords($website['bizname']).' Category</label>
                        <select class="form-select" id="cta_category" name="cta_category" required>
                            <option value="">Select a category...</option>';

foreach ($categories as $cat) {
    // Default to 'restaurant' for testing (matches "Grab Dinner with Your Family")
    $selected = ($campaign && $campaign['cta_category'] == $cat) ? ' selected' : (!$campaign && $cat == 'restaurant' ? ' selected' : '');
    echo '
                            <option value="' . $cat . '"' . $selected . '>' . ucfirst($cat) . '</option>';
}

echo '
                        </select>
                        <small class="text-muted">Which '.$website['biznames'].' to show</small>
                    </div>
                    <div class="col-md-3">
                        <label for="cta_mode" class="form-label">'.ucwords($website['biznames']).'</label>
                        <select class="form-select" id="cta_mode" name="cta_mode">
                            <option value="inclusive" selected>Inclusive - Enrolled</option>
                            <option value="exclusive">Exclusive - Unenrolled</option>
                        </select>
                        <small class="text-muted">Use enrolled or unenrolled</small>
                    </div>
                    <div class="col-md-3">
                        <label for="send_date" class="form-label">Send Date</label>
                        <input type="date" class="form-control" id="send_date" name="send_date" 
                               value="' . ($campaign ? date('Y-m-d', strtotime($campaign['send_dt'])) : ($mk_campaign && $mk_campaign['start_date'] ? date('Y-m-d', strtotime($mk_campaign['start_date'])) : date('Y-m-d'))) . '" 
                               min="' . ($mk_campaign && $mk_campaign['start_date'] ? date('Y-m-d', strtotime($mk_campaign['start_date'])) : date('Y-m-d')) . '" required>
                        ' . ($mk_campaign && $mk_campaign['start_date'] ? '<small class="text-muted">Min: campaign start</small>' : '') . '
                    </div>
                    <div class="col-md-3">
                        <label for="send_time" class="form-label">Send Time</label>
                        <input type="time" class="form-control" id="send_time" name="send_time" 
                               value="' . ($campaign ? date('H:i', strtotime($campaign['send_dt'])) : '09:00') . '" required>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Recipients</h5>
            </div>
            <div class="card-body">
                <style>
                .recipient-builder {
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    padding: 15px;
                    background: #f8f9fa;
                    min-height: 80px;
                }
                .recipient-token {
                    display: inline-block;
                    background: #007bff;
                    color: white;
                    padding: 5px 12px;
                    border-radius: 20px;
                    margin: 3px;
                    font-size: 14px;
                }
                .recipient-token .remove-token {
                    margin-left: 8px;
                    cursor: pointer;
                    font-weight: bold;
                }
                .recipient-token.operator {
                    background: #6c757d;
                }
                .segment-buttons {
                    margin-top: 15px;
                }
                .segment-btn {
                    margin: 3px;
                    width: 100%;
                    text-align: left;
                    justify-content: flex-start;
                }
                .recipient-builder-main {
                    min-height: 120px;
                    max-height: 300px;
                    overflow-y: auto;
                    border: 2px solid #dee2e6;
                    border-radius: 8px;
                    padding: 10px;
                    background: #fff;
                    transition: all 0.3s ease;
                }
                .recipient-builder-main:focus-within {
                    border-color: #0d6efd;
                    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
                }
                .segment-accordion .accordion-button {
                    padding: 0.75rem 1rem;
                    font-size: 0.95rem;
                    font-weight: 600;
                }
                .segment-accordion .accordion-body {
                    padding: 0.5rem;
                }
                .segment-sidebar {
                    position: sticky;
                    top: 20px;
                    max-height: calc(100vh - 100px);
                    overflow-y: auto;
                }
                </style>
                
                <div class="row">
                    <!-- Left Sidebar - Segment Selectors -->
                    <div class="col-lg-4 col-md-5">
                        <div class="card segment-sidebar">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-funnel-fill"></i> Segment Filters</h6>
                            </div>
                            <div class="card-body p-0">
                                <!-- Accordion for segment categories -->
                                <div class="accordion accordion-flush segment-accordion" id="segmentAccordion">
                                    
                                    <!-- Basic Segments -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basicSegments">
                                                <i class="bi bi-star-fill me-2"></i> Basic Segments
                                            </button>
                                        </h2>
                                        <div id="basicSegments" class="accordion-collapse collapse show" data-bs-parent="#segmentAccordion">
                                            <div class="accordion-body">
                                                <button type="button" class="btn btn-sm btn-outline-primary segment-btn" onclick="addSegment(&#39;all&#39;, &#39;All Active Users&#39;)">
                                                    <i class="bi bi-people-fill me-2"></i> All Active Users
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning segment-btn" onclick="showTokenOptions(&#39;account_type&#39;)">
                                                    <i class="bi bi-person-check-fill me-2"></i> Account Type
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Demographics -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#demographics">
                                                <i class="bi bi-people-gear me-2"></i> Demographics
                                            </button>
                                        </h2>
                                        <div id="demographics" class="accordion-collapse collapse" data-bs-parent="#segmentAccordion">
                                            <div class="accordion-body">
                                                <button type="button" class="btn btn-sm btn-outline-primary segment-btn" onclick="showTokenOptions(&#39;gender&#39;)">
                                                    <i class="bi bi-gender-ambiguous me-2"></i> Gender
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary segment-btn" onclick="showTokenOptions(&#39;age_range&#39;)">
                                                    <i class="bi bi-person-clock me-2"></i> Age Range
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary segment-btn" onclick="showTokenOptions(&#39;birthday_month&#39;)">
                                                    <i class="bi bi-cake2-fill me-2"></i> Birthday Month
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary segment-btn" onclick="showTokenOptions(&#39;state&#39;)">
                                                    <i class="bi bi-geo-alt-fill me-2"></i> State/Location
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Account Details -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accountDetails">
                                                <i class="bi bi-person-circle me-2"></i> Account Details
                                            </button>
                                        </h2>
                                        <div id="accountDetails" class="accordion-collapse collapse" data-bs-parent="#segmentAccordion">
                                            <div class="accordion-body">
                                                <button type="button" class="btn btn-sm btn-outline-primary segment-btn" onclick="showTokenOptions(&#39;plan&#39;)">
                                                    <i class="bi bi-award-fill me-2"></i> Subscription Plan
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary segment-btn" onclick="showTokenOptions(&#39;profile_completeness&#39;)">
                                                    <i class="bi bi-percent me-2"></i> Profile Completeness
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary segment-btn" onclick="showTokenOptions(&#39;enrollment_count&#39;)">
                                                    <i class="bi bi-list-ol me-2"></i> Enrollment Count
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Business Interests -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#businessInterests">
                                                <i class="bi bi-building-fill me-2"></i> Business Interests
                                            </button>
                                        </h2>
                                        <div id="businessInterests" class="accordion-collapse collapse" data-bs-parent="#segmentAccordion">
                                            <div class="accordion-body">
                                                <button type="button" class="btn btn-sm btn-outline-primary segment-btn" onclick="showTokenOptions(&#39;business_category&#39;)">
                                                    <i class="bi bi-shop me-2"></i> Business Category
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                                
                                <!-- Operator buttons at bottom of card -->
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="btn-group btn-group-sm me-2">
                                                <button type="button" class="btn btn-outline-secondary" onclick="addOperatorToken(&#39;AND&#39;)">AND</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="addOperatorToken(&#39;OR&#39;)">OR</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="addOperatorToken(&#39;NOT&#39;)">NOT</button>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-dark" onclick="addParenthesis(&#39;(&#39;)" title="Open group">(</button>
                                                <button type="button" class="btn btn-outline-dark" onclick="addParenthesis(&#39;)&#39;)" title="Close group">)</button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearTokens()">
                                            <i class="bi bi-x-lg"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Content - Main Form -->
                    <div class="col-lg-8 col-md-7">
                        <label class="form-label">Recipient Selection</label>
                        
                        <!-- Vertically expanding recipient builder -->
                        <div class="recipient-builder-main" id="recipientBuilder">
                            <div id="recipientTokens">
                                <!-- Tokens will be dynamically added here -->
                            </div>
                        </div>
                        
                        <!-- Recipient count -->
                        <div class="mt-2 text-muted small" id="recipientCount">
                            <span class="spinner-border spinner-border-sm me-1"></span> Calculating recipients...
                        </div>
                    </div>
                </div>
                
                <!-- Hidden field to store recipient criteria -->
                ';
                
                // Debug what we're putting in the hidden field
                $recipientCriteriaValue = isset($campaign['recipient_criteria']) ? $campaign['recipient_criteria'] : '[]';
                error_log("Hidden field recipient_criteria value: " . $recipientCriteriaValue);
                
                echo '
                <input type="hidden" name="recipient_criteria" id="recipient_criteria" value="'. htmlspecialchars($recipientCriteriaValue) . '">
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Email Content</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="gen_specific_messaging" name="gen_specific_messaging" 
                           ' . (isset($campaign['gen_specific_messaging']) && $campaign['gen_specific_messaging'] ? 'checked' : '') . '
                           onchange="toggleGenSpecificMessaging(this)">
                    <label class="form-check-label" for="gen_specific_messaging">
                        <i class="bi bi-people-fill"></i> Gen-Specific Messaging
                    </label>
                </div>
            </div>
            <div class="card-body">
                <div id="genSpecificAlert" class="alert alert-info mb-3" style="display: ' . 
                    (isset($campaign['gen_specific_messaging']) && $campaign['gen_specific_messaging'] ? 'block' : 'none') . ';">
                    <i class="bi bi-sparkles"></i> 
                    <strong>AI Generation-Specific Messaging Enabled</strong><br>
                    When sending, AI will dynamically generate personalized content for each recipient based on their generation:
                    <ul class="mb-0 mt-2">
                        <li><strong>Gen Z</strong> (1997-2012): AI adapts tone for digital natives</li>
                        <li><strong>Millennials</strong> (1981-1996): AI creates value-focused messaging</li>
                        <li><strong>Gen X</strong> (1965-1980): AI generates practical, direct content</li>
                        <li><strong>Boomers</strong> (1946-1964): AI crafts detailed, traditional messaging</li>
                    </ul>
                    <div class="mt-2 p-2 bg-warning bg-opacity-10 rounded">
                        <small><i class="bi bi-exclamation-triangle"></i> <strong>Note:</strong> The base template below will be used as context for AI generation. Each recipient receives uniquely generated content optimized for their age group.</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="body_html" class="form-label">Email Body</label>
                    <textarea class="form-control" id="body_html" name="body_html" rows="15">' . 
                    ($campaign ? htmlspecialchars($campaign['body_html']) : '') . '</textarea>
                    <div class="mt-2">
                        <small class="text-muted">
                            <strong>Available Placeholders:</strong><br>
                            [[first_name]] - User first name<br>
                            [[city]] - User city<br>
                            [[birthday_month]] - User birthday month<br>
                            [[CTA_BLOCK]] - This will be replaced with personalized brand recommendations
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" name="action" value="draft" class="btn btn-secondary">
                            <i class="bi bi-save"></i> Save as Draft
                        </button>
                        <button type="submit" name="action" value="schedule" class="btn btn-primary">
                            <i class="bi bi-clock-fill"></i> Schedule Newsletter
                        </button>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-outline-primary" id="previewEmailBtn" onclick="previewEmail()" style="display:none;">
                            <i class="bi bi-eye-fill"></i> Preview
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="sendTestEmail()">
                            <i class="bi bi-envelope-fill"></i> Send Test
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h5 class="modal-title mb-0 me-3">Email Preview</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshPreview()" title="Show different recipient">
                        <i class="bi bi-arrow-clockwise"></i> Refresh Recipient
                    </button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="errorContent" style="font-family: monospace; background: #f5f5f5; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word;">
                    <!-- Error message will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="copyErrorMessage()">
                    <i class="bi bi-clipboard"></i> Copy Error
                </button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-envelope-fill"></i> Send Test Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="testEmailAddress" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="testEmailAddress" placeholder="name@example.com" required>
                    <small class="text-muted">Enter the email address to send a test newsletter to</small>
                </div>
                <div id="testEmailStatus" class="alert d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestEmailFromModal()">
                    <i class="bi bi-send-fill"></i> Send Test
                </button>
            </div>
        </div>
    </div>
</div>';

// Close the PHP echo and continue with HTML/JavaScript
?>

<!-- Token Field Styles -->
<link rel="stylesheet" href="/public/css/recipient-token-field.css">

<!-- TinyMCE with API Key -->
<script src="https://cdn.tiny.cloud/1/<?php echo htmlspecialchars($tinymce_api_key); ?>/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Token Field JavaScript -->
<script src="/public/js/recipient-token-field-simple.js?v=<?php echo time(); ?>"></script>

<script>
// Initialize TinyMCE
tinymce.init({
    selector: "#body_html",
    height: 500,
    menubar: true,
    plugins: [
        "advlist", "autolink", "lists", "link", "image", "charmap", "preview",
        "anchor", "searchreplace", "visualblocks", "code", "fullscreen",
        "insertdatetime", "media", "table", "help", "wordcount", "emoticons",
        "autoresize", "directionality", "pagebreak", "nonbreaking", "template"
    ],
    toolbar: "undo redo | formatselect | bold italic forecolor backcolor | " +
        "alignleft aligncenter alignright alignjustify | " +
        "bullist numlist outdent indent | link image media | " +
        "removeformat | code fullscreen preview | help",
    content_style: "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; line-height: 1.6; }",
    branding: false,
    promotion: false,
    relative_urls: false,
    remove_script_host: false,
    convert_urls: true,
    image_advtab: true,
    autoresize_bottom_margin: 50,
    autoresize_max_height: 800,
    autoresize_min_height: 400,
    toolbar_mode: "sliding",
    contextmenu: "link image table",
    quickbars_selection_toolbar: "bold italic | quicklink h2 h3 blockquote",
    setup: function(editor) {
        editor.on("change", function() {
            editor.save();
        });
    }
});

function previewEmail(randomize) {
    var content = tinymce.get("body_html").getContent();
    var subject = $("#subject").val();
    var ctaCategory = $("#cta_category").val();
    var ctaMode = $("#cta_mode").val() || 'inclusive';
    var genSpecificMessaging = $("#gen_specific_messaging").is(':checked');
    
    // Get the current recipient tokens
    var tokens = window.recipientTokens || [];
    
    console.log('Preview Email - Tokens:', tokens);
    console.log('Preview Email - CTA Category:', ctaCategory);
    console.log('Preview Email - CTA Mode:', ctaMode);
    console.log('Preview Email - Randomize:', randomize);
    
    // Show loading state
    $("#previewContent").html(
        '<div class="text-center p-5">' +
        '<div class="spinner-border text-primary" role="status">' +
        '<span class="visually-hidden">Loading preview...</span>' +
        '</div>' +
        '<p class="mt-3">Fetching ' + (randomize ? 'random ' : '') + 'recipient data for preview...</p>' +
        '</div>'
    );
    
    // Only show modal if not already shown (for refresh)
    if (!$('#previewModal').hasClass('show')) {
        var modal = new bootstrap.Modal(document.getElementById("previewModal"));
        modal.show();
    }
    
    // Debug the request data
    var requestData = {
        tokens: JSON.stringify(tokens),
        process: 'single',  // Use 'single' mode for preview
        cta_category: ctaCategory,
        cta_mode: ctaMode,
        debug: 'true',  // Enable debug mode
        randomize: randomize ? 'true' : 'false'  // Enable randomization
    };
    
    console.log('Preview Request Data:', requestData);
    console.log('Tokens being sent:', tokens);
    
    // Use the same endpoint as recipient count, but request single user data for preview
    $.ajax({
        url: '/myaccount/marketing/ajax/newsletter-recipients-count.php',
        method: 'POST',
        data: requestData,
        dataType: 'json',
        success: function(response) {
            console.log('===== PREVIEW RESPONSE DEBUG =====');
            console.log('Full Response:', response);
            console.log('Success?', response.success);
            console.log('Has user?', response.user ? 'YES' : 'NO');
            if (response.user) {
                console.log('User data:', response.user);
            }
            if (response.debug) {
                console.log('Debug info:', response.debug);
            }
            if (response.error) {
                console.error('Server error:', response.error);
            }
            console.log('==================================');
            
            if (response.success && response.user) {
                var user = response.user;
                var companies = response.companies || [];
                
                // Get the preview user ID (use actual user ID for testing, not 0)
                var previewUserId = user.user_id || 0;
                
                // Generate admin preview token if this is a Birthday Gold admin
                // The token allows bypass of login for testing
                var adminToken = '';
                <?php if (isset($current_user_data['company_id']) && $current_user_data['company_id'] == 1): ?>
                    // Birthday Gold admin - generate secure preview token
                    // Token format: hash(campaign_id + user_id + secret + timestamp)
                    var timestamp = Math.floor(Date.now() / 1000);
                    adminToken = '&admin_token=<?= md5($campaign_id . '_PREVIEW_' . date('Y-m-d') . '_BG_ADMIN_SECRET') ?>&ts=' + timestamp;
                    console.log('Admin preview mode enabled');
                <?php endif; ?>
                
                // Replace placeholders with actual user data
                var previewContent = content;
                var previewSubject = subject;
                
                // Store original content before replacements
                var originalContent = content;
                
                previewContent = previewContent.replace(/\[\[first_name\]\]/g, user.first_name);
                previewContent = previewContent.replace(/\[\[city\]\]/g, user.city);
                previewContent = previewContent.replace(/\[\[birthday_month\]\]/g, user.birthday_month);
                
                // Add generation-specific banner and content variations if enabled
                if (genSpecificMessaging) {
                    // Calculate user's generation based on birth year
                    var currentYear = new Date().getFullYear();
                    var userGeneration = 'Unknown';
                    var generationIcon = '👤';
                    var generationColor = '#667eea';
                    var generationStyle = null;
                    
                    // Use the age provided by the server
                    if (user.age) {
                        var birthYear = currentYear - user.age;
                        if (birthYear >= 1997 && birthYear <= 2012) {
                            userGeneration = 'Gen Z';
                            generationIcon = '📱';
                            generationColor = '#9333ea';
                            generationStyle = {
                                greeting: "Hey " + user.first_name + "! 🎉",
                                tone: "casual",
                                emojis: true,
                                phrases: ["It's giving birthday vibes", "No cap, these deals are fire", "Your birthday is about to be lit"],
                                cta: "Tap to claim →"
                            };
                        } else if (birthYear >= 1981 && birthYear <= 1996) {
                            userGeneration = 'Millennial';
                            generationIcon = '💻';
                            generationColor = '#3b82f6';
                            generationStyle = {
                                greeting: "Hi " + user.first_name + "!",
                                tone: "friendly",
                                emojis: true,
                                phrases: ["Treat yourself this birthday", "You deserve this", "Birthday goals achieved"],
                                cta: "Claim Your Reward"
                            };
                        } else if (birthYear >= 1965 && birthYear <= 1980) {
                            userGeneration = 'Gen X';
                            generationIcon = '📼';
                            generationColor = '#ef4444';
                            generationStyle = {
                                greeting: "Hello " + user.first_name + ",",
                                tone: "straightforward",
                                emojis: false,
                                phrases: ["Exclusive birthday offers", "Special birthday savings", "Limited-time birthday deals"],
                                cta: "View Offer"
                            };
                        } else if (birthYear >= 1946 && birthYear <= 1964) {
                            userGeneration = 'Baby Boomer';
                            generationIcon = '📺';
                            generationColor = '#f59e0b';
                            generationStyle = {
                                greeting: "Dear " + user.first_name + ",",
                                tone: "formal",
                                emojis: false,
                                phrases: ["Celebrate your special day", "Birthday appreciation offers", "Exclusive birthday benefits"],
                                cta: "Learn More"
                            };
                        } else if (birthYear < 1946) {
                            userGeneration = 'Silent Generation';
                            generationIcon = '📻';
                            generationColor = '#6b7280';
                            generationStyle = {
                                greeting: "Dear " + user.first_name + ",",
                                tone: "respectful",
                                emojis: false,
                                phrases: ["Birthday greetings and special offers", "Commemorating your birthday", "Special recognition"],
                                cta: "View Details"
                            };
                        } else {
                            userGeneration = 'Gen Alpha';
                            generationIcon = '🎮';
                            generationColor = '#10b981';
                            generationStyle = {
                                greeting: "Yo " + user.first_name + "! 🎮",
                                tone: "playful",
                                emojis: true,
                                phrases: ["Level up your birthday", "Epic birthday rewards unlocked", "Birthday achievement unlocked"],
                                cta: "Get it! 🎁"
                            };
                        }
                    }
                    
                    // Don't add a separate banner - we'll include generation info in the recipient header
                    var generationInfo = ' (Age ' + user.age + ') <span class="badge" style="background: ' + generationColor + '; color: white; margin-left: 8px;">' + 
                                          generationIcon + ' ' + userGeneration + '</span>';
                    
                    // Apply generation-specific content transformations
                    if (generationStyle) {
                        // REPLACE THE MAIN CONTENT but preserve CTA block position and footer
                        // Check if original content has CTA placeholder
                        var hasCTAPlaceholder = originalContent.indexOf('[[CTA_BLOCK]]') !== -1;
                        
                        // Extract footer from original content (look for hr tag or signature patterns)
                        var footerMatch = originalContent.match(/<hr[^>]*>[\s\S]*$/);
                        var savedFooter = '';
                        
                        if (footerMatch) {
                            savedFooter = footerMatch[0];
                            // Apply placeholder replacements to footer
                            savedFooter = savedFooter.replace(/\[\[first_name\]\]/g, user.first_name);
                            savedFooter = savedFooter.replace(/\[\[city\]\]/g, user.city);
                            savedFooter = savedFooter.replace(/\[\[birthday_month\]\]/g, user.birthday_month);
                        } else {
                            // Look for signature patterns including Birthday Gold Team
                            footerMatch = originalContent.match(/(Sincerely|Best regards|Thank you|Regards|Thanks|Cheers|Happy.*Birthday.*Team)[\s\S]*$/i);
                            if (footerMatch) {
                                savedFooter = footerMatch[0];
                                savedFooter = savedFooter.replace(/\[\[first_name\]\]/g, user.first_name);
                                savedFooter = savedFooter.replace(/\[\[city\]\]/g, user.city);
                                savedFooter = savedFooter.replace(/\[\[birthday_month\]\]/g, user.birthday_month);
                            } else {
                                // Add default signature if none found
                                savedFooter = '<hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">' +
                                            '<p style="text-align: center; color: #666; font-size: 14px;">Happy (almost) Birthday!<br>' +
                                            'The Birthday Gold Team</p>';
                            }
                        }
                        
                        // Generate completely new content based on generation
                        var aiGeneratedContent = '';
                        
                        if (userGeneration === 'Gen Z') {
                            aiGeneratedContent = '<h2 style="color: #333; font-size: 24px; margin: 20px 0;">' + generationStyle.greeting + '</h2>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Yooo it\'s your birthday month! 🎉🎂 No cap, ' + user.city + ' has some fire deals waiting for you.</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Your ' + user.birthday_month + ' birthday is about to be legendary! These brands are literally obsessed with making your day special. The vibes? Immaculate. The deals? Absolutely unmatched. 💯</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Don\'t sleep on these exclusive offers - they\'re giving main character energy and they\'re all yours! ✨</p>';
                        } else if (userGeneration === 'Millennial') {
                            aiGeneratedContent = '<h2 style="color: #333; font-size: 24px; margin: 20px 0;">' + generationStyle.greeting + '</h2>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Can you believe it\'s almost your birthday? 🎊 We\'ve partnered with amazing ' + user.city + ' businesses to help you celebrate in style!</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Your ' + user.birthday_month + ' birthday deserves to be extra special. You work hard all year - now it\'s time to treat yourself! These local favorites want to be part of your celebration with exclusive birthday perks just for you.</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Because adulting is hard, but birthdays should be easy. You deserve this! 🎁</p>';
                        } else if (userGeneration === 'Gen X') {
                            aiGeneratedContent = '<h2 style="color: #333; font-size: 24px; margin: 20px 0;">' + generationStyle.greeting + '</h2>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Your ' + user.birthday_month + ' birthday is approaching, and we\'ve arranged exclusive offers from premium ' + user.city + ' establishments.</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">These participating businesses value your patronage and want to make your birthday memorable with special discounts and complimentary items reserved just for you.</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Take advantage of these limited-time birthday offers - you\'ve earned them.</p>';
                        } else if (userGeneration === 'Baby Boomer') {
                            aiGeneratedContent = '<h2 style="color: #333; font-size: 24px; margin: 20px 0;">' + generationStyle.greeting + '</h2>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">As your ' + user.birthday_month + ' birthday approaches, we are pleased to present you with exclusive offers from distinguished ' + user.city + ' establishments.</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">These respected local businesses would like to honor your special day with carefully selected birthday benefits. Each offer has been chosen to provide genuine value and enhance your birthday celebration.</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">We invite you to explore these birthday privileges at your convenience. Thank you for being a valued member of our community.</p>';
                        } else if (userGeneration === 'Silent Generation') {
                            aiGeneratedContent = '<h2 style="color: #333; font-size: 24px; margin: 20px 0;">' + generationStyle.greeting + '</h2>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">In recognition of your upcoming ' + user.birthday_month + ' birthday, we are honored to extend these special offers from esteemed ' + user.city + ' establishments.</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">These distinguished businesses wish to commemorate your birthday with their compliments. Each establishment has prepared something special to mark this important occasion.</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Please accept these birthday courtesies with our warmest wishes for your continued health and happiness.</p>';
                        } else {
                            aiGeneratedContent = '<h2 style="color: #333; font-size: 24px; margin: 20px 0;">' + generationStyle.greeting + '</h2>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">IT\'S BIRTHDAY TIME! 🎮🎉 Level up your ' + user.birthday_month + ' birthday with epic rewards from ' + user.city + '!</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Achievement unlocked: BIRTHDAY HERO! These awesome places want to give you super cool birthday stuff. It\'s like getting bonus loot in your favorite game! 🏆</p>';
                            aiGeneratedContent += '<p style="font-size: 16px; line-height: 1.8;">Ready Player One? Your birthday quest starts here! Collect all the rewards! 🎁🎮</p>';
                        }
                        
                        // Add the CTA block placeholder back if it existed
                        if (hasCTAPlaceholder) {
                            aiGeneratedContent += '[[CTA_BLOCK]]';
                        }
                        
                        // Add the footer back if it existed
                        if (savedFooter) {
                            aiGeneratedContent += savedFooter;
                        }
                        
                        // Replace the preview content with AI version
                        previewContent = aiGeneratedContent;
                    }
                    
                    console.log('Gen-Specific Messaging enabled for preview');
                    console.log('User age:', user.age, 'Generation:', userGeneration);
                    console.log('Generation style:', generationStyle);
                }
                
                previewSubject = previewSubject.replace(/\[\[first_name\]\]/g, user.first_name);
                previewSubject = previewSubject.replace(/\[\[city\]\]/g, user.city);
                previewSubject = previewSubject.replace(/\[\[birthday_month\]\]/g, user.birthday_month);
                
                // Debug companies data
                console.log('Companies for CTA:', companies);
                console.log('Number of companies:', companies.length);
                console.log('CTA Category used:', ctaCategory);
                console.log('CTA Mode used:', ctaMode);
                
                /*
                 * PREVIEW/TEST MODE TRACKING URLS
                 * ================================
                 * In preview mode, we use special test values that the tracking system recognizes:
                 * - Campaign ID: "TEST-PREVIEW" - Special identifier for preview/test mode
                 * - User ID: "0" - Indicates a test/preview user (no real user)
                 * - Company ID: Actual company ID for tracking which CTA was clicked
                 * - Type: "cta" - Indicates this is a CTA click
                 * 
                 * The tracking endpoint at m.bd.gold should recognize TEST-PREVIEW as a test
                 * and either:
                 * 1. Log it separately as a test click (not counted in real analytics)
                 * 2. Redirect to a test landing page
                 * 3. Show a preview confirmation message
                 * 
                 * For production emails, these values would be replaced server-side with:
                 * - Real campaign_id from mk_campaigns
                 * - Real user_id from the recipient
                 */
                
                // Build CTA block with actual companies (2x2 grid)
                var ctaBlock = '';
                var ctaButtonText = 'Claim Reward →'; // Default CTA text
                
                // Use generation-specific CTA text if available
                if (genSpecificMessaging && typeof generationStyle !== 'undefined' && generationStyle && generationStyle.cta) {
                    ctaButtonText = generationStyle.cta;
                }
                
                if (companies && companies.length > 0) {
                    ctaBlock = '<div style="background: #f8f9fa; padding: 30px; margin: 20px 0; border-radius: 8px;">';
                    ctaBlock += '<h3 style="color: #333; margin-bottom: 20px; text-align: center;">🎁 Your Birthday Rewards Await!</h3>';
                    ctaBlock += '<table style="width: 100%; border-collapse: separate; border-spacing: 20px;">';
                    
                    // Create 2x2 grid
                    for (var i = 0; i < companies.length; i += 2) {
                        ctaBlock += '<tr>';
                        
                        // First column
                        if (companies[i]) {
                            ctaBlock += '<td style="width: 50%; background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; text-align: center; vertical-align: top;">';
                            if (companies[i].logo) {
                                // Use the logo directly - it's either base64 or a proper URL
                                var logoSrc = companies[i].logo;
                                ctaBlock += '<img src="' + logoSrc + '" style="max-width: 120px; max-height: 80px; margin-bottom: 10px;" alt="' + companies[i].company_name + '">';
                            } else {
                                ctaBlock += '<div style="height: 80px; background: #e9ecef; border-radius: 4px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #6c757d;">' + companies[i].company_name + '</div>';
                            }
                            ctaBlock += '<h4 style="color: #333; font-size: 16px; margin: 10px 0;">' + companies[i].company_name + '</h4>';
                            if (companies[i].offer_text) {
                                ctaBlock += '<p style="color: #666; font-size: 14px; margin: 10px 0; min-height: 40px;">' + companies[i].offer_text + '</p>';
                            }
                            // Use test values for preview mode that the tracking system can recognize
                            // TEST-PREVIEW is a special campaign ID that the system recognizes as preview mode
                            // Use actual preview user ID for deeper functionality testing
                            var trackUrl = 'https://m.bd.gold/TEST-PREVIEW/u=' + previewUserId + '&c=' + companies[i].company_id + '&t=cta' + adminToken;
                            ctaBlock += '<a href="' + trackUrl + '" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold;">' + ctaButtonText + '</a>';
                            ctaBlock += '</td>';
                        } else {
                            ctaBlock += '<td style="width: 50%;"></td>';
                        }
                        
                        // Second column
                        if (companies[i + 1]) {
                            ctaBlock += '<td style="width: 50%; background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; text-align: center; vertical-align: top;">';
                            if (companies[i + 1].logo) {
                                // Use the logo directly - it's either base64 or a proper URL
                                var logoSrc = companies[i + 1].logo;
                                ctaBlock += '<img src="' + logoSrc + '" style="max-width: 120px; max-height: 80px; margin-bottom: 10px;" alt="' + companies[i + 1].company_name + '">';
                            } else {
                                ctaBlock += '<div style="height: 80px; background: #e9ecef; border-radius: 4px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #6c757d;">' + companies[i + 1].company_name + '</div>';
                            }
                            ctaBlock += '<h4 style="color: #333; font-size: 16px; margin: 10px 0;">' + companies[i + 1].company_name + '</h4>';
                            if (companies[i + 1].offer_text) {
                                ctaBlock += '<p style="color: #666; font-size: 14px; margin: 10px 0; min-height: 40px;">' + companies[i + 1].offer_text + '</p>';
                            }
                            // Use test values for preview mode that the tracking system can recognize
                            // TEST-PREVIEW is a special campaign ID that the system recognizes as preview mode
                            // Use actual preview user ID for deeper functionality testing
                            var trackUrl = 'https://m.bd.gold/TEST-PREVIEW/u=' + previewUserId + '&c=' + companies[i + 1].company_id + '&t=cta' + adminToken;
                            ctaBlock += '<a href="' + trackUrl + '" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold;">' + ctaButtonText + '</a>';
                            ctaBlock += '</td>';
                        } else {
                            ctaBlock += '<td style="width: 50%;"></td>';
                        }
                        
                        ctaBlock += '</tr>';
                    }
                    
                    ctaBlock += '</table>';
                    ctaBlock += '</div>';
                } else {
                    ctaBlock = '<div style="border: 2px dashed #ccc; padding: 20px; margin: 20px 0; text-align: center;">' +
                               '<strong>CTA BLOCK</strong><br>Personalized brand recommendations will appear here</div>';
                }
                
                previewContent = previewContent.replace(/\[\[CTA_BLOCK\]\]/g, ctaBlock);
                
                // Display preview with user info header
                var html = '<div class="alert alert-info mb-3">';
                html += '<strong>Preview using recipient:</strong> ' + user.first_name + ' ' + user.last_name;
                html += ' (' + user.email + ') from ' + user.city;
                
                // Add generation info if gen-specific messaging is enabled
                if (genSpecificMessaging && typeof generationInfo !== 'undefined') {
                    html += generationInfo;
                }
                
                if (response.matched_criteria) {
                    html += ' <span class="badge bg-success ms-2">Matched Criteria</span>';
                } else {
                    html += ' <span class="badge bg-warning ms-2">Default User</span>';
                }
                html += '</div>';
                html += '<div class="mb-3"><strong>Subject:</strong> ' + previewSubject + '</div>';
                html += '<hr>';
                html += '<div>' + previewContent + '</div>';
                
                $("#previewContent").html(html);
            } else {
                // No recipients or unable to load - use fallback preview
                var fallbackContent = content;
                fallbackContent = fallbackContent.replace(/\[\[first_name\]\]/g, "John");
                fallbackContent = fallbackContent.replace(/\[\[city\]\]/g, "Seattle");
                fallbackContent = fallbackContent.replace(/\[\[birthday_month\]\]/g, "January");
                fallbackContent = fallbackContent.replace(/\[\[CTA_BLOCK\]\]/g, 
                    "<div style=\"border: 2px dashed #ccc; padding: 20px; margin: 20px 0; text-align: center;\">" +
                    "<strong>CTA BLOCK</strong><br>Personalized brand recommendations will appear here</div>");
                
                var warningMsg = tokens.length === 0 
                    ? 'No recipients selected. Using default preview values.' 
                    : 'Unable to load recipient data. Using default values.';
                
                $("#previewContent").html(
                    '<div class="alert alert-warning">' + warningMsg + '</div>' +
                    '<div class="mb-3"><strong>Subject:</strong> ' + subject + '</div>' +
                    '<hr>' +
                    '<div>' + fallbackContent + '</div>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.error('Preview AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                tokens: tokens
            });
            
            // Fallback to static preview
            var fallbackContent = content;
            fallbackContent = fallbackContent.replace(/\[\[first_name\]\]/g, "John");
            fallbackContent = fallbackContent.replace(/\[\[city\]\]/g, "Seattle");
            fallbackContent = fallbackContent.replace(/\[\[birthday_month\]\]/g, "January");
            fallbackContent = fallbackContent.replace(/\[\[CTA_BLOCK\]\]/g, 
                "<div style=\"border: 2px dashed #ccc; padding: 20px; margin: 20px 0; text-align: center;\">" +
                "<strong>CTA BLOCK</strong><br>Personalized brand recommendations will appear here</div>");
            
            var errorMsg = 'Unable to fetch recipient data. ';
            if (xhr.responseText) {
                errorMsg += 'Error: ' + xhr.responseText;
            } else {
                errorMsg += 'Status: ' + status + ', Error: ' + error;
            }
            
            $("#previewContent").html(
                '<div class="alert alert-warning">' + errorMsg + '</div>' +
                '<div class="mb-3"><strong>Subject:</strong> ' + subject + '</div>' +
                '<hr>' +
                '<div>' + fallbackContent + '</div>'
            );
        }
    });
}

function refreshPreview() {
    // Call previewEmail with randomize flag set to true
    previewEmail(true);
}

function sendTestEmail() {
    // Show the test email modal
    var modal = new bootstrap.Modal(document.getElementById("testEmailModal"));
    modal.show();
    
    // Clear any previous status
    $("#testEmailStatus").addClass("d-none").removeClass("alert-success alert-danger");
    $("#testEmailAddress").val("");
}

function sendTestEmailFromModal() {
    var email = $("#testEmailAddress").val();
    
    if (!email) {
        $("#testEmailStatus")
            .removeClass("d-none alert-success")
            .addClass("alert-danger")
            .html("<i class=\"bi bi-exclamation-circle-fill\"></i> Please enter an email address");
        return;
    }
    
    // Show loading state
    var btn = $("button:contains(\"Send Test\")");
    var originalHtml = btn.html();
    btn.html("<span class=\"spinner-border spinner-border-sm me-1\"></span> Sending...").prop("disabled", true);
    
    $.post("/myaccount/marketing/ajax/newsletter-test.php", {
        campaign_id: ' . $campaign_id . ',
        test_email: email,
        subject: $("#subject").val(),
        body: tinymce.get("body_html").getContent(),
        category: $("#cta_category").val()
    }, function(response) {
        if (response.success) {
            $("#testEmailStatus")
                .removeClass("d-none alert-danger")
                .addClass("alert-success")
                .html("<i class=\"bi bi-check-circle-fill\"></i> Test email sent successfully to " + email);
            
            // Clear the input and close modal after 2 seconds
            setTimeout(function() {
                bootstrap.Modal.getInstance(document.getElementById("testEmailModal")).hide();
            }, 2000);
        } else {
            $("#testEmailStatus")
                .removeClass("d-none alert-success")
                .addClass("alert-danger")
                .html("<i class=\"bi bi-exclamation-circle-fill\"></i> Error: " + response.message);
        }
    }, "json").fail(function(xhr, status, error) {
        $("#testEmailStatus")
            .removeClass("d-none alert-success")
            .addClass("alert-danger")
            .html("<i class=\"bi bi-exclamation-circle-fill\"></i> Failed to send test email: " + error);
    }).always(function() {
        // Restore button state
        btn.html(originalHtml).prop("disabled", false);
    });
}

// Error modal functions
function showErrorModal(message) {
    $("#errorContent").text(message);
    var modal = new bootstrap.Modal(document.getElementById("errorModal"));
    modal.show();
}

function copyErrorMessage() {
    var errorText = $("#errorContent").text();
    navigator.clipboard.writeText(errorText).then(function() {
        // Show success feedback
        var btn = $("button:contains('Copy Error')");
        var originalText = btn.html();
        btn.html('<i class="bi bi-check-lg"></i> Copied!');
        btn.removeClass("btn-secondary").addClass("btn-success");
        
        setTimeout(function() {
            btn.html(originalText);
            btn.removeClass("btn-success").addClass("btn-secondary");
        }, 2000);
    }).catch(function(err) {
        // Fallback for older browsers
        var textArea = document.createElement("textarea");
        textArea.value = errorText;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand("copy");
            alert("Error copied to clipboard!");
        } catch (err) {
            alert("Failed to copy. Please select and copy manually.");
        }
        document.body.removeChild(textArea);
    });
}

// Page-specific initialization
// Toggle Gen-Specific Messaging
function toggleGenSpecificMessaging(checkbox) {
    var alert = document.getElementById('genSpecificAlert');
    if (checkbox.checked) {
        alert.style.display = 'block';
        // Add a visual indicator to the email body label
        var bodyLabel = document.querySelector('label[for="body_html"]');
        if (bodyLabel && !bodyLabel.querySelector('.gen-badge')) {
            bodyLabel.innerHTML += ' <span class="gen-badge badge bg-info ms-2"><i class="bi bi-sparkles"></i> AI will personalize per generation</span>';
        }
    } else {
        alert.style.display = 'none';
        // Remove the visual indicator
        var genBadge = document.querySelector('.gen-badge');
        if (genBadge) {
            genBadge.remove();
        }
    }
}

$(document).ready(function() {
    
    // Initialize Gen-Specific Messaging visual indicator if already checked
    if ($("#gen_specific_messaging").is(':checked')) {
        var bodyLabel = document.querySelector('label[for="body_html"]');
        if (bodyLabel && !bodyLabel.querySelector('.gen-badge')) {
            bodyLabel.innerHTML += ' <span class="gen-badge badge bg-info ms-2"><i class="bi bi-sparkles"></i> AI will personalize per generation</span>';
        }
    }
    
    // AI Content Generation
    $("#aiGenerateBtn").on("click", function(e) {
        e.preventDefault();
        console.log("AI Generate button clicked!");
        
        var title = $("#title").val();
        var category = $("#cta_category").val();
        var sendDate = $("#send_date").val();
        
        if (!title) {
            alert("Please enter a Campaign Title first");
            $("#title").focus();
            return;
        }
        
        if (!category) {
            alert("Please select a CTA Category first");
            $("#cta_category").focus();
            return;
        }
        
        if (!sendDate) {
            alert("Please select a Send Date first");
            $("#send_date").focus();
            return;
        }
        
        // Create loading overlay with big visual indicator
        var loadingOverlay = $('<div id="aiLoadingOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center;">' +
            '<div class="card" style="padding: 40px; text-align: center; min-width: 400px;">' +
            '<div class="spinner-border text-primary mb-3" style="width: 4rem; height: 4rem;"></div>' +
            '<h3 class="mb-2">AI is Generating Content...</h3>' +
            '<p class="text-muted">Creating personalized newsletter content for:</p>' +
            '<p><strong>' + title + '</strong></p>' +
            '<p class="text-muted">Category: <strong>' + category + '</strong></p>' +
            '<p class="text-muted">Date: <strong>' + sendDate + '</strong></p>' +
            '<div class="progress mt-3" style="height: 25px;">' +
            '<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">Processing...</div>' +
            '</div>' +
            '</div>' +
            '</div>');
        $("body").append(loadingOverlay);
        
        // Show loading state on button too
        var btn = $(this);
        var originalHtml = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Generating...');
        btn.prop("disabled", true);
        btn.addClass("btn-warning");
        
        // Add timestamp for debugging
        console.log("Starting AI generation at:", new Date().toISOString());
        console.log("Request data:", {
            campaign_title: title,
            cta_category: category,
            send_date: sendDate
        });
        
        // Make AJAX request
        $.ajax({
            url: "/myaccount/marketing/ajax/newsletter-ai-generate.php",
            method: "POST",
            data: {
                campaign_title: title,
                cta_category: category,
                send_date: sendDate
            },
            dataType: "json",
            success: function(response) {
                console.log("AI Response received:", response);
                
                // Remove loading overlay
                $("#aiLoadingOverlay").fadeOut(300, function() {
                    $(this).remove();
                });
                
                if (response.success) {
                    // Flash the fields that are being updated
                    $("#subject").css("background-color", "#d4edda").val(response.subject);
                    setTimeout(function() {
                        $("#subject").css("background-color", "");
                    }, 2000);
                    
                    // Update body content in TinyMCE with visual effect
                    tinymce.get("body_html").setContent(response.body);
                    
                    // Show prominent success message at top
                    var successAlert = $('<div class="alert alert-success alert-dismissible fade show border-3 shadow-lg" role="alert" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 500px;">' +
                        '<h4 class="alert-heading"><i class="bi bi-check-circle-fill"></i> AI Generation Complete!</h4>' +
                        '<p class="mb-0">Content has been generated and inserted into the form. Please review and customize as needed.</p>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    $("body").append(successAlert);
                    
                    // Also add a permanent success message above the form
                    var formAlert = $('<div class="alert alert-info alert-dismissible fade show" role="alert">' +
                        '<i class="bi bi-info-circle-fill"></i> <strong>AI Generated Content:</strong> The subject and body have been populated with AI-generated content. Feel free to edit and customize.' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    $(".card").first().before(formAlert);
                    
                    // Auto-dismiss floating alert after 5 seconds
                    setTimeout(function() {
                        successAlert.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 5000);
                } else {
                    // Show error with more detail
                    var errorMessage = "Error: " + (response.message || "Failed to generate content");
                    if (response.debug) {
                        errorMessage += "\n\nDebug Information:\n" + JSON.stringify(response.debug, null, 2);
                    }
                    console.error("AI Generation Error:", errorMessage);
                    showErrorModal(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr);
                
                // Remove loading overlay
                $("#aiLoadingOverlay").fadeOut(300, function() {
                    $(this).remove();
                });
                
                var errorMessage = "Error generating content: " + error;
                if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMessage += "\n\nResponse:\n" + JSON.stringify(response, null, 2);
                    } catch(e) {
                        errorMessage += "\n\nResponse:\n" + xhr.responseText;
                    }
                }
                showErrorModal(errorMessage);
            },
            complete: function() {
                console.log("AI generation complete at:", new Date().toISOString());
                
                // Restore button state
                btn.html(originalHtml);
                btn.prop("disabled", false);
                btn.removeClass("btn-warning");
            }
        });
    }); // End of AI button click handler
}); // End of document.ready
</script>

<?php
// Add campaign date validation if needed
if ($mk_campaign && $mk_campaign['start_date']) {
?>
<script>
$(document).ready(function() {
    // Validate send date against campaign start date
    $("#send_date").on("change", function() {
        var sendDate = new Date($(this).val());
        var campaignStartDate = new Date("<?php echo date('Y-m-d', strtotime($mk_campaign['start_date'])); ?>");
        
        if (sendDate < campaignStartDate) {
            alert("Newsletter send date cannot be before the campaign start date (<?php echo date('M j, Y', strtotime($mk_campaign['start_date'])); ?>)");
            $(this).val("<?php echo date('Y-m-d', strtotime($mk_campaign['start_date'])); ?>");
        }
    });
});
</script>
<?php
}

include($dir['core_components'] . '/bg_footer.inc');

$app->outputpage();
?>