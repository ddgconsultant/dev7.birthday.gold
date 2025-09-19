<?php

$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Dashboard";

// Check for first visit to marketing platform
$first_marketing_visit = $account->getUserAttribute($current_user_data['user_id'], 'first_marketing_visit');
if (!$first_marketing_visit) {
    // Redirect to How It Works for first-time users
    header('Location: /myaccount/marketing/how-it-works.php');
    exit;
}

// Get user's company context
$company_id = $current_user_data['company_id'] ?? 99;

// Check if user has consultant access to multiple companies OR is Birthday Gold staff
$consultant_companies = [];

if ($account->isstaff()) {
    // Birthday Gold staff can see ALL companies (not just ones with marketing data)
    $all_companies_sql = "SELECT DISTINCT company_id FROM bg_company_locations WHERE company_id > 0
                         UNION SELECT 0 as company_id
                         ORDER BY company_id ASC";
    $all_companies = $database->getrows($all_companies_sql);
    
    foreach ($all_companies as $comp) {
        $consultant_companies[] = [
            'company_id' => $comp['company_id'],
            'access_type' => $comp['company_id'] == 0 ? 'internal' : 'staff_admin'
        ];
    }
} else {
    // Regular consultant access
    $consultant_access_sql = "SELECT company_id, access_type FROM mk_company_access 
                             WHERE user_id = :user_id AND status = 'active' 
                             ORDER BY access_type DESC";
    $consultant_companies = $database->getrows($consultant_access_sql, ['user_id' => $current_user_data['user_id']]);
}

// Determine active company (for consultants who can switch context)
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

// Validate access if switching companies
if ($active_company_id != $company_id) {
    $has_access = $account->isstaff(); // Staff can access any company
    
    if (!$has_access) {
        foreach ($consultant_companies as $access) {
            if ($access['company_id'] == $active_company_id) {
                $has_access = true;
                break;
            }
        }
    }
    
    if (!$has_access) {
        $active_company_id = $company_id; // Fallback to default
    }
}

// Handle company switching
if ($_POST['switch_company'] ?? false) {
    $requested_company = intval($_POST['company_id']);
    // Validate access
    $can_switch = ($requested_company == $company_id) || $account->isstaff();
    
    if (!$can_switch) {
        foreach ($consultant_companies as $access) {
            if ($access['company_id'] == $requested_company) {
                $can_switch = true;
                break;
            }
        }
    }
    if ($can_switch) {
        $_SESSION['active_company_id'] = $requested_company;
        $active_company_id = $requested_company;
    }
}

// Get marketing stats for active company
$stats_sql = "SELECT 
    (SELECT COUNT(*) + 1 FROM mk_platforms WHERE company_id = :company_id AND status = 'active') as platform_count,
    (SELECT COUNT(*) FROM mk_campaigns WHERE company_id = :company_id2) as campaign_count,
    (SELECT COUNT(*) FROM mk_campaigns WHERE company_id = :company_id3 AND status = 'active') as active_campaigns,
    (SELECT SUM(budget_amount) FROM mk_campaigns WHERE company_id = :company_id4 AND status IN ('active', 'paused')) as total_budget";

$stats = $database->getrow($stats_sql, [
    'company_id' => $active_company_id,
    'company_id2' => $active_company_id,
    'company_id3' => $active_company_id,
    'company_id4' => $active_company_id
]);

// Get recent activities
$recent_activities = $marketing->getActivitiesForCalendar(
    date('Y-m-d', strtotime('-30 days')), 
    date('Y-m-d'),
    []
);

// Filter activities for active company
$recent_activities = array_filter($recent_activities, function($activity) use ($active_company_id) {
    return $activity['metadata']['company_id'] ?? 99 == $active_company_id;
});

// Get plan limits for active company
$company_plan_id = $account->getCompanyAttribute($active_company_id, 'product_plan_id');

// Get marketing plan limits from bg_products and bg_product_features
$plan_limits = [];
$plan_name = 'Free Plan'; // Default plan name
$product_info = null;

if ($company_plan_id) {
    // Get the product/plan details from bg_products
    $product_sql = "SELECT * FROM `bg_products` WHERE `id` = :product_id LIMIT 1";
    $product_info = $database->getrow($product_sql, ['product_id' => $company_plan_id]);
    
    if ($product_info) {
        // Set plan name from product info
        $plan_name = $product_info['account_name'] ?? ucfirst($product_info['account_plan']) . ' Plan';
    }
    
    // Get the feature limits from bg_product_features
    $limits_sql = "SELECT `name`, `value` FROM `bg_product_features` 
                   WHERE `product_id` = :product_id 
                   AND `version` = 'v7' 
                   AND `name` LIKE 'feature_marketing_%_value' 
                   AND `status` = 'active'";
    $limits_result = $database->getrows($limits_sql, ['product_id' => $company_plan_id]);
    
    foreach ($limits_result as $limit) {
        $plan_limits[$limit['name']] = $limit['value'];
    }
}

// Get current usage for the active company
$current_usage = [];
$current_usage['active_campaigns'] = $database->getrow(
    "SELECT COUNT(*) as count FROM mk_campaigns WHERE company_id = :company_id AND status = 'active'",
    ['company_id' => $active_company_id]
)['count'] ?? 0;

// Get monthly campaign type usage (current month)
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

$monthly_usage_sql = "SELECT campaign_type, COUNT(*) as count 
                      FROM mk_campaigns 
                      WHERE company_id = :company_id 
                      AND create_dt BETWEEN :start_date AND :end_date
                      GROUP BY campaign_type";
$monthly_usage = $database->getrows($monthly_usage_sql, [
    'company_id' => $active_company_id,
    'start_date' => $current_month_start,
    'end_date' => $current_month_end
]);

foreach ($monthly_usage as $usage) {
    $current_usage['monthly_' . $usage['campaign_type']] = $usage['count'];
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}
.stats-card {
    background: #ffffff;
    border: 1px solid #dee2e6;
    color: #212529;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
    transition: all 0.3s ease;
    cursor: pointer;
}
.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.15), 0 2px 4px rgba(0,0,0,0.12);
    background: #f8f9fa;
}
.company-switcher {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 20px;
}
.activity-item {
    border-left: 4px solid #0d6efd;
    padding-left: 15px;
    margin-bottom: 15px;
}
.activity-item.platform_created { border-left-color: #0ea5e9; }
.activity-item.campaign_created { border-left-color: #eab308; }
.activity-item.campaign_launched { border-left-color: #22c55e; }
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-megaphone me-3"></i>Marketing Dashboard</h1>
        <p class="lead">Manage your marketing platforms, campaigns, and performance</p>';

// Show company context
if ($active_company_id == 0) {
    echo '
        <div class="badge bg-primary fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Birthday Gold (Internal Marketing)
        </div>';
} else {
    echo '
        <div class="badge bg-info fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Company ID: ' . $active_company_id . '
        </div>';
}

echo '
    </div>
</div>';

// Include marketing tab navigation
include('nav.inc.php');

echo '
<div class="container mb-5">';

// Company switcher for consultants
if (count($consultant_companies) > 1) {
    echo '
    <div class="company-switcher">
        <form method="POST" class="d-flex align-items-center">
            <label class="form-label me-3 mb-0"><strong>Active Company:</strong></label>
            <select name="company_id" class="form-select me-3" style="width: auto;" onchange="this.form.submit()">
                <option value="' . $company_id . '"' . ($active_company_id == $company_id ? ' selected' : '') . '>
                    ' . ($company_id == 99 ? 'Birthday Gold (Internal)' : 'My Company') . '
                </option>';
    
    foreach ($consultant_companies as $access) {
        if ($access['company_id'] != $company_id) {
            if ($access['company_id'] == 0) {
                $company_name = 'Birthday Gold (Internal)';
            } else {
                $company_name = 'Company #' . $access['company_id']; // TODO: Get actual company name from bg_companies table
            }
            
            $access_label = '';
            if ($account->isstaff()) {
                $access_label = $access['company_id'] == 0 ? 'Internal' : 'Staff Admin';
            } else {
                $access_label = ucfirst($access['access_type']);
            }
            
            echo '
                <option value="' . $access['company_id'] . '"' . ($active_company_id == $access['company_id'] ? ' selected' : '') . '>
                    ' . htmlspecialchars($company_name) . ' (' . $access_label . ')
                </option>';
        }
    }
    
    echo '
            </select>
            <input type="hidden" name="switch_company" value="1">
            <small class="text-muted">Consultant Access</small>
        </form>
    </div>';
}

echo '
    <div class="row">
        <div class="col-lg-8">
            <div class="row mb-4">
                <div class="col-md-3">
                    <a href="/myaccount/marketing/platforms.php" class="text-decoration-none">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <h3>' . ($stats['platform_count'] ?? 0) . '</h3>
                                <p class="mb-0 text-muted">Platforms</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="/myaccount/marketing/campaigns.php" class="text-decoration-none">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <h3>' . ($stats['campaign_count'] ?? 0) . '</h3>
                                <p class="mb-0 text-muted">Campaigns</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="/myaccount/marketing/campaigns.php?filter=active" class="text-decoration-none">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <h3>' . ($stats['active_campaigns'] ?? 0) . '</h3>
                                <p class="mb-0 text-muted">Active</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="/myaccount/marketing/reports.php" class="text-decoration-none">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <h3>$' . number_format($stats['total_budget'] ?? 0) . '</h3>
                                <p class="mb-0 text-muted">Budget</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activity</h5>
                    <a href="/myaccount/marketing/calendar.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-calendar me-2"></i>View Calendar
                    </a>
                </div>
                <div class="card-body">';

if (empty($recent_activities)) {
    echo '
                    <div class="text-center py-4">
                        <i class="bi bi-rocket display-4 text-primary"></i>
                        <h5 class="mt-3">Ready to Start Marketing!</h5>
                        <p class="text-muted">You have access to the Birthday.Gold Marketing Platform.<br>
                        Create your first campaign or add external platforms to expand your reach.</p>
                        <div class="btn-group">
                            <a href="/myaccount/marketing/campaign-create.php" class="btn btn-primary">
                                <i class="bi bi-plus me-2"></i>Create First Campaign
                            </a>
                            <a href="/myaccount/marketing/how-it-works.php" class="btn btn-outline-info">
                                <i class="bi bi-question-circle me-2"></i>How It Works
                            </a>
                        </div>
                    </div>';
} else {
    foreach (array_slice($recent_activities, 0, 8) as $activity) {
        $activity_data = $activity['metadata'] ?? [];
        $time_ago = date('M j, g:i A', strtotime($activity['activity_date']));
        
        echo '
                    <div class="activity-item ' . $activity['activity_type'] . '">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">' . htmlspecialchars($activity['display_name']) . '</h6>
                                <p class="mb-0 text-muted small">' . htmlspecialchars($activity['description']) . '</p>
                            </div>
                            <small class="text-muted">' . $time_ago . '</small>
                        </div>
                    </div>';
    }
    
    if (count($recent_activities) > 8) {
        echo '
                    <div class="text-center">
                        <a href="/myaccount/marketing/activities.php" class="btn btn-outline-secondary btn-sm">
                            View All Activities
                        </a>
                    </div>';
    }
}

echo '
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group no-border">
                        <a href="/myaccount/marketing/campaign-create.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-plus me-2"></i>Create Campaign</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/marketing/campaigns.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-megaphone me-2"></i>View Campaigns</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/marketing/newsletter-report.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-envelope-paper me-2"></i>Manage Newsletters</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/marketing/platforms.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-link me-2"></i>Platforms</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/marketing/calendar.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-calendar me-2"></i>Marketing Calendar</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>';

            // Admin Actions Section - only for staff
            if ($account->isstaff()) {
                echo '
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Admin Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group no-border">
                        <a href="/admin_actions/scheduler--mk-newsletter-queue.php" target="_blank" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-inbox-fill me-2"></i>Newsletter Queue</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/admin_actions/scheduler--mk-newsletter-personalizer.php" target="_blank" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-magic me-2"></i>Newsletter Personalizer</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/admin_actions/scheduler--mk-newsletter-sender.php" target="_blank" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-send-fill me-2"></i>Email Sender</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>';
            }

            echo '

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Training & Resources</h5>
                </div>
                <div class="card-body">
                    <div class="list-group no-border">
                        <a href="/myaccount/marketing/how-it-works.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-play-circle me-2"></i>How It Works</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="#" class="list-group-item-action d-flex justify-content-between align-items-center py-2" onclick="showBestPractices()">
                            <div><i class="bi bi-lightbulb me-2"></i>Marketing Best Practices</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="#" class="list-group-item-action d-flex justify-content-between align-items-center py-2" onclick="showTemplates()">
                            <div><i class="bi bi-bookmark me-2"></i>Campaign Templates</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/marketing/reports.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-graph-up me-2"></i>Performance Insights</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Plan Limits Card -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-speedometer me-2"></i>Plan Limits</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-primary mb-2">' . htmlspecialchars($plan_name) . '</h6>
                        <a href="/myaccount/marketing/plan-details.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-info-circle me-1"></i>View Plan Details
                        </a>
                    </div>
                    <hr>
                    <div class="small">';

// Helper function to format limit display
function formatLimitDisplay($current, $limit, $label) {
    $percentage = $limit === 'Unlimited' ? 0 : (($current / max(1, intval($limit))) * 100);
    $color_class = $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'success');
    $display_limit = $limit === 'Unlimited' ? '∞' : $limit;
    
    return '
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>' . $label . '</span>
            <span class="text-' . $color_class . '">' . $current . ' / ' . $display_limit . '</span>
        </div>';
}

// Display plan limits if available
if (!empty($plan_limits)) {
    echo formatLimitDisplay(
        $current_usage['active_campaigns'], 
        $plan_limits['feature_marketing_active_campaigns_value'] ?? 'N/A',
        'Active Campaigns'
    );
    
    echo formatLimitDisplay(
        $current_usage['monthly_newsletter'] ?? 0, 
        $plan_limits['feature_marketing_newsletters_value'] ?? 'N/A',
        'Newsletters (This Month)'
    );
    
    echo formatLimitDisplay(
        $current_usage['monthly_email'] ?? 0, 
        $plan_limits['feature_marketing_emails_value'] ?? 'N/A',
        'Emails (This Month)'
    );
    
    echo formatLimitDisplay(
        $current_usage['monthly_text_message'] ?? 0, 
        $plan_limits['feature_marketing_text_messages_value'] ?? 'N/A',
        'Text Messages (This Month)'
    );
} else {
    echo '<div class="text-muted text-center py-3">
            <i class="bi bi-info-circle me-2"></i>Plan limits not available
          </div>';
}

echo '
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-calendar me-1"></i>Limits reset monthly on the 1st
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

// Add JavaScript for placeholder functions
?>
<script>
function showBestPractices() {
    alert('Marketing Best Practices guide coming soon!');
}

function showTemplates() {
    alert('Campaign Templates library coming soon!');
}
</script>
<?php

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>