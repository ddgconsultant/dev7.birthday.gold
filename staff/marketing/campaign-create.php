<?php
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$platform_id = intval($_GET['platform_id'] ?? 0);

if (!$platform_id) {
    header('Location: /staff/marketing/marketing-platforms.php');
    exit;
}

// Get platform details
$platform_sql = "SELECT * FROM bg_content WHERE id = :id AND type = 'platform_link'";
$platform = $database->getrow($platform_sql, ['id' => $platform_id]);

if (!$platform) {
    header('Location: /staff/marketing/marketing-platforms.php');
    exit;
}

$platform_data = json_decode($platform['tags'], true) ?: [];
$pagetitle = "Create Campaign - " . $platform['display_name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $campaign_name = trim($_POST['campaign_name']);
    $campaign_type = trim($_POST['campaign_type']);
    $budget = floatval($_POST['budget'] ?? 0);
    $budget_type = trim($_POST['budget_type'] ?? 'total');
    $status = trim($_POST['status'] ?? 'draft');
    $description = trim($_POST['description'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $target_audience = trim($_POST['target_audience'] ?? '');
    $campaign_goals = trim($_POST['campaign_goals'] ?? '');
    $ad_content = trim($_POST['ad_content'] ?? '');
    
    $campaign_config = [
        'display_name' => $campaign_name,
        'description' => $description,
        'type' => $campaign_type,
        'budget' => $budget,
        'budget_type' => $budget_type,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'target_audience' => $target_audience,
        'goals' => $campaign_goals,
        'ad_content' => $ad_content,
        'status' => $status
    ];
    
    // Use Marketing class to create campaign (includes automatic activity logging)
    $campaign_id = $marketing->createCampaign($platform_id, $campaign_config);
    
    if ($campaign_id) {
        header("Location: /staff/marketing/platform-manage.php?platform_id=" . $platform_id);
        exit;
    } else {
        $error_message = 'Failed to create campaign';
    }
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
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
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-plus-circle me-3"></i>Create Campaign</h1>
        <p class="lead">Create a new campaign for ' . htmlspecialchars($platform['display_name']) . '</p>
    </div>
</div>';

include('../includes/marketing-nav.php');

echo '
<div class="container mt-4 mb-5 pb-5">
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="/staff/marketing/platform-manage.php?platform_id=' . $platform_id . '" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to ' . htmlspecialchars($platform['display_name']) . '
            </a>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="' . ($platform_data['icon'] ?? 'bi bi-link') . ' me-2"></i>' . 
                        htmlspecialchars($platform['display_name']) . ' Campaign
                    </h5>
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
                                       value="' . htmlspecialchars($_POST['campaign_name'] ?? '') . '">
                                <small class="text-muted">Clear, descriptive name for this campaign</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="campaign_type" class="form-label">Campaign Type *</label>
                                        <select class="form-control" id="campaign_type" name="campaign_type" required>
                                            <option value="">Select type...</option>
                                            <option value="awareness"' . (($_POST['campaign_type'] ?? '') == 'awareness' ? ' selected' : '') . '>Brand Awareness</option>
                                            <option value="traffic"' . (($_POST['campaign_type'] ?? '') == 'traffic' ? ' selected' : '') . '>Website Traffic</option>
                                            <option value="engagement"' . (($_POST['campaign_type'] ?? '') == 'engagement' ? ' selected' : '') . '>Engagement</option>
                                            <option value="leads"' . (($_POST['campaign_type'] ?? '') == 'leads' ? ' selected' : '') . '>Lead Generation</option>
                                            <option value="conversions"' . (($_POST['campaign_type'] ?? '') == 'conversions' ? ' selected' : '') . '>Conversions</option>
                                            <option value="app_installs"' . (($_POST['campaign_type'] ?? '') == 'app_installs' ? ' selected' : '') . '>App Installs</option>
                                            <option value="video_views"' . (($_POST['campaign_type'] ?? '') == 'video_views' ? ' selected' : '') . '>Video Views</option>
                                            <option value="reach"' . (($_POST['campaign_type'] ?? '') == 'reach' ? ' selected' : '') . '>Reach</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Initial Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="draft"' . (($_POST['status'] ?? 'draft') == 'draft' ? ' selected' : '') . '>Draft</option>
                                            <option value="active"' . (($_POST['status'] ?? '') == 'active' ? ' selected' : '') . '>Active</option>
                                            <option value="paused"' . (($_POST['status'] ?? '') == 'paused' ? ' selected' : '') . '>Paused</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Campaign Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">' . 
                                htmlspecialchars($_POST['description'] ?? '') . '</textarea>
                                <small class="text-muted">Brief overview of campaign objectives and approach</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6 class="text-success mb-3"><i class="bi bi-currency-dollar me-2"></i>Budget & Timeline</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget" class="form-label">Budget ($)</label>
                                        <input type="number" class="form-control" id="budget" name="budget" min="0" step="0.01"
                                               value="' . htmlspecialchars($_POST['budget'] ?? '') . '">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget_type" class="form-label">Budget Type</label>
                                        <select class="form-control" id="budget_type" name="budget_type">
                                            <option value="total"' . (($_POST['budget_type'] ?? 'total') == 'total' ? ' selected' : '') . '>Total Budget</option>
                                            <option value="daily"' . (($_POST['budget_type'] ?? '') == 'daily' ? ' selected' : '') . '>Daily Budget</option>
                                            <option value="monthly"' . (($_POST['budget_type'] ?? '') == 'monthly' ? ' selected' : '') . '>Monthly Budget</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                               value="' . htmlspecialchars($_POST['start_date'] ?? '') . '">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                               value="' . htmlspecialchars($_POST['end_date'] ?? '') . '">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6 class="text-info mb-3"><i class="bi bi-people me-2"></i>Targeting & Content</h6>
                            
                            <div class="mb-3">
                                <label for="target_audience" class="form-label">Target Audience</label>
                                <textarea class="form-control" id="target_audience" name="target_audience" rows="2">' . 
                                htmlspecialchars($_POST['target_audience'] ?? '') . '</textarea>
                                <small class="text-muted">Demographics, interests, behaviors, custom audiences</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="campaign_goals" class="form-label">Campaign Goals</label>
                                <textarea class="form-control" id="campaign_goals" name="campaign_goals" rows="2">' . 
                                htmlspecialchars($_POST['campaign_goals'] ?? '') . '</textarea>
                                <small class="text-muted">Specific objectives and success metrics</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ad_content" class="form-label">Ad Content & Creative Notes</label>
                                <textarea class="form-control" id="ad_content" name="ad_content" rows="4">' . 
                                htmlspecialchars($_POST['ad_content'] ?? '') . '</textarea>
                                <small class="text-muted">Ad copy, creative concepts, image/video specifications</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/staff/marketing/platform-manage.php?platform_id=' . $platform_id . '" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-plus-circle me-2"></i>Create Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Campaign Tips</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <h6>Campaign Types</h6>
                        <ul class="list-unstyled text-muted">
                            <li><strong>Brand Awareness:</strong> Build recognition</li>
                            <li><strong>Traffic:</strong> Drive website visits</li>
                            <li><strong>Engagement:</strong> Likes, shares, comments</li>
                            <li><strong>Lead Generation:</strong> Collect contact info</li>
                            <li><strong>Conversions:</strong> Sales, signups</li>
                        </ul>
                        
                        <h6>Budget Guidelines</h6>
                        <ul class="list-unstyled text-muted">
                            <li><strong>Test campaigns:</strong> $50-200</li>
                            <li><strong>Small campaigns:</strong> $200-1,000</li>
                            <li><strong>Medium campaigns:</strong> $1,000-5,000</li>
                            <li><strong>Large campaigns:</strong> $5,000+</li>
                        </ul>
                        
                        <h6>Best Practices</h6>
                        <ul class="list-unstyled text-muted">
                            <li>Start with draft status for review</li>
                            <li>Define clear, measurable goals</li>
                            <li>Test with smaller budgets first</li>
                            <li>Target specific audiences</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>