<?php

$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Plan Details";

// Get user's company context
$company_id = $current_user_data['company_id'] ?? 99;

// Check for active company (for consultants who can switch context)
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

// Get plan details for active company
$company_plan_id = $account->getCompanyAttribute($active_company_id, 'product_plan_id');

// Get product and plan features
$plan_details = [];
$plan_name = 'Free Plan';
$plan_type = 'free';
$product_info = null;

if ($company_plan_id) {
    // Get the product details from bg_products
    $product_sql = "SELECT * FROM `bg_products` WHERE `id` = :product_id LIMIT 1";
    $product_info = $database->getrow($product_sql, ['product_id' => $company_plan_id]);
    
    if ($product_info) {
        // Set plan info from product
        $plan_name = $product_info['account_name'] ?? ucfirst($product_info['account_plan']) . ' Plan';
        $plan_type = $product_info['account_plan'] ?? 'free';
    }
    
    // Get all features for this plan from bg_product_features
    $features_sql = "SELECT `name`, `value`, `plan` 
                     FROM `bg_product_features` 
                     WHERE `product_id` = :product_id 
                     AND `version` = 'v7' 
                     AND `status` = 'active'
                     ORDER BY `name`";
    $features_result = $database->getrows($features_sql, ['product_id' => $company_plan_id]);
    
    foreach ($features_result as $feature) {
        $plan_details[$feature['name']] = $feature['value'];
        // Use plan from features if not set from product
        if (!empty($feature['plan']) && $plan_type == 'free') {
            $plan_type = $feature['plan'];
        }
    }
}

// Organize ALL features dynamically from database
$feature_categories = [];

// Group features by category based on feature name patterns
foreach ($plan_details as $feature_key => $feature_value) {
    $category = 'Other Features'; // Default category
    $display_name = $feature_key;
    
    // Clean up feature name for display
    $display_name = str_replace('feature_', '', $display_name);
    $display_name = str_replace('_value', '', $display_name);
    $display_name = str_replace('_', ' ', $display_name);
    $display_name = ucwords($display_name);
    
    // Categorize based on feature name patterns
    if (strpos($feature_key, 'feature_marketing_') === 0) {
        if (strpos($feature_key, '_campaigns_') !== false || strpos($feature_key, '_newsletters_') !== false || 
            strpos($feature_key, '_emails_') !== false || strpos($feature_key, '_text_messages_') !== false ||
            strpos($feature_key, '_social_') !== false || strpos($feature_key, '_drip_') !== false) {
            $category = 'Marketing Campaigns';
        } elseif (strpos($feature_key, '_platform') !== false || strpos($feature_key, '_api_') !== false || 
                  strpos($feature_key, '_webhook') !== false || strpos($feature_key, '_integration') !== false) {
            $category = 'Platform Integration';
        } elseif (strpos($feature_key, '_analytics') !== false || strpos($feature_key, '_report') !== false || 
                  strpos($feature_key, '_export') !== false || strpos($feature_key, '_roi_') !== false) {
            $category = 'Analytics & Reporting';
        } elseif (strpos($feature_key, '_segment') !== false || strpos($feature_key, '_automation') !== false || 
                  strpos($feature_key, '_ab_testing') !== false || strpos($feature_key, '_personalization') !== false ||
                  strpos($feature_key, '_audience_') !== false) {
            $category = 'Customer Engagement';
        } elseif (strpos($feature_key, '_team_') !== false || strpos($feature_key, '_approval_') !== false || 
                  strpos($feature_key, '_template') !== false) {
            $category = 'Team & Collaboration';
        } elseif (strpos($feature_key, '_support') !== false || strpos($feature_key, '_training') !== false || 
                  strpos($feature_key, '_consultation') !== false) {
            $category = 'Support & Services';
        } else {
            $category = 'Marketing Features';
        }
    } elseif (strpos($feature_key, 'feature_users_') === 0 || strpos($feature_key, 'feature_admin_') === 0 ||
              strpos($feature_key, 'feature_staff_') === 0) {
        $category = 'User Management';
    } elseif (strpos($feature_key, 'feature_api_') === 0 || strpos($feature_key, 'feature_webhook') === 0 ||
              strpos($feature_key, 'feature_integration') === 0 || strpos($feature_key, 'feature_zapier') === 0 ||
              strpos($feature_key, 'feature_shopify') === 0 || strpos($feature_key, 'feature_stripe') === 0) {
        $category = 'API & Integrations';
    } elseif (strpos($feature_key, 'feature_analytics_') === 0 || strpos($feature_key, 'feature_report') === 0 ||
              strpos($feature_key, 'feature_predictive_') === 0 || strpos($feature_key, 'feature_sentiment_') === 0 ||
              strpos($feature_key, 'feature_real_time_') === 0) {
        $category = 'Analytics & Intelligence';
    } elseif (strpos($feature_key, 'feature_security_') === 0 || strpos($feature_key, 'feature_data_encryption') === 0 ||
              strpos($feature_key, 'feature_fraud_') === 0 || strpos($feature_key, 'feature_gdpr_') === 0 ||
              strpos($feature_key, 'feature_ip_whitelisting') === 0 || strpos($feature_key, '_2fa') !== false ||
              strpos($feature_key, '_sso') !== false || strpos($feature_key, 'feature_audit_') === 0) {
        $category = 'Security & Compliance';
    } elseif (strpos($feature_key, 'feature_storage') !== false || strpos($feature_key, 'feature_file_') === 0 ||
              strpos($feature_key, 'feature_image_') === 0 || strpos($feature_key, 'feature_cdn_') === 0 ||
              strpos($feature_key, 'feature_backup_') === 0 || strpos($feature_key, 'feature_data_storage') === 0) {
        $category = 'Storage & Backup';
    } elseif (strpos($feature_key, 'feature_reward') !== false || strpos($feature_key, 'feature_loyalty') !== false ||
              strpos($feature_key, 'feature_points_') === 0 || strpos($feature_key, 'feature_gift_') === 0 ||
              strpos($feature_key, 'feature_birthday_') === 0) {
        $category = 'Rewards & Loyalty';
    } elseif (strpos($feature_key, 'feature_enrollment') !== false || strpos($feature_key, 'feature_customer_') === 0) {
        $category = 'Customer Management';
    } elseif (strpos($feature_key, 'feature_location') !== false || strpos($feature_key, 'feature_business_location') !== false ||
              strpos($feature_key, 'feature_geo_') === 0 || strpos($feature_key, 'feature_multi_timezone') === 0) {
        $category = 'Location Management';
    } elseif (strpos($feature_key, 'feature_automation_') === 0 || strpos($feature_key, 'feature_workflow') !== false ||
              strpos($feature_key, 'feature_trigger_') === 0 || strpos($feature_key, 'feature_conditional_') === 0) {
        $category = 'Automation & Workflows';
    } elseif (strpos($feature_key, 'feature_branding') !== false || strpos($feature_key, 'feature_white_label') !== false ||
              strpos($feature_key, 'feature_custom_domain') !== false || strpos($feature_key, 'feature_custom_css') !== false ||
              strpos($feature_key, 'feature_custom_template') !== false) {
        $category = 'Branding & Customization';
    } elseif (strpos($feature_key, 'feature_calendar') !== false || strpos($feature_key, 'feature_event') !== false ||
              strpos($feature_key, 'feature_booking') !== false || strpos($feature_key, 'feature_recurring_') === 0) {
        $category = 'Calendar & Events';
    } elseif (strpos($feature_key, 'feature_ai_') === 0 || strpos($feature_key, 'feature_predictive') === 0) {
        $category = 'AI & Machine Learning';
    } elseif (strpos($feature_key, '_title') !== false || strpos($feature_key, '_description') !== false) {
        continue; // Skip title and description fields
    }
    
    // Add to appropriate category
    if (!isset($feature_categories[$category])) {
        $feature_categories[$category] = [];
    }
    $feature_categories[$category][$feature_key] = $display_name;
}

// Sort categories for better display
$category_order = [
    'Marketing Campaigns',
    'Marketing Features',
    'Customer Engagement',
    'Customer Management', 
    'Rewards & Loyalty',
    'Analytics & Intelligence',
    'Analytics & Reporting',
    'Automation & Workflows',
    'Platform Integration',
    'API & Integrations',
    'Calendar & Events',
    'Location Management',
    'User Management',
    'Team & Collaboration',
    'Branding & Customization',
    'Storage & Backup',
    'Security & Compliance',
    'AI & Machine Learning',
    'Support & Services',
    'Other Features'
];

$ordered_categories = [];
foreach ($category_order as $cat) {
    if (isset($feature_categories[$cat]) && !empty($feature_categories[$cat])) {
        $ordered_categories[$cat] = $feature_categories[$cat];
    }
}
// Add any remaining categories not in the order
foreach ($feature_categories as $cat => $features) {
    if (!isset($ordered_categories[$cat])) {
        $ordered_categories[$cat] = $features;
    }
}
$feature_categories = $ordered_categories;

// Plan comparison data (for upgrade suggestions)
$plan_tiers = [
    'free' => [
        'name' => 'Free Plan',
        'price' => 0,
        'color' => 'secondary'
    ],
    'starter' => [
        'name' => 'Starter Plan',
        'price' => 49,
        'color' => 'info'
    ],
    'growth' => [
        'name' => 'Growth Plan',
        'price' => 149,
        'color' => 'success'
    ],
    'professional' => [
        'name' => 'Professional Plan',
        'price' => 299,
        'color' => 'primary'
    ],
    'birthday_gold' => [  // Special internal Business plan
        'name' => 'Business Plan',
        'price' => 0,
        'color' => 'warning'
    ],
    'business' => [
        'name' => 'Business Plan',
        'price' => 599,
        'color' => 'warning'
    ],
    'enterprise' => [
        'name' => 'Enterprise Plan',
        'price' => null,
        'color' => 'dark'
    ]
];

// Use actual price from product if available
$current_plan_info = $plan_tiers[$plan_type] ?? $plan_tiers['free'];
if ($product_info && isset($product_info['price'])) {
    $current_plan_info['price'] = $product_info['price'];
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}
.feature-category {
    margin-bottom: 30px;
}
.feature-item {
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
}
.feature-item:last-child {
    border-bottom: none;
}
.feature-value {
    font-weight: 600;
}
.feature-value.unlimited {
    color: #22c55e;
}
.feature-value.limited {
    color: #f97316;
}
.feature-value.disabled {
    color: #94a3b8;
}
.upgrade-card {
    border: 2px dashed #0d6efd;
    background: #f0f9ff;
}
.plan-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
}
.plan-price {
    background: rgba(255, 255, 255, 0.1);
    display: inline-block;
    padding: 10px 20px;
    border-radius: 10px;
    margin-top: 10px;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff">
    <div class="container text-center">
        <div class="plan-badge bg-' . $current_plan_info['color'] . ' text-white mb-3">
            ' . strtoupper($plan_type) . '
        </div>
        <h1 class="mb-3">' . htmlspecialchars($plan_name) . '</h1>
        <p class="lead mb-0">Complete details of your marketing plan features and limits</p>';

if ($current_plan_info['price'] !== null) {
    echo '
        <div class="plan-price">
            <span class="fs-3">$' . $current_plan_info['price'] . '</span>
            <span class="opacity-75">/month</span>
        </div>';
} else {
    echo '
        <div class="plan-price">
            <span class="fs-3">Custom Pricing</span>
        </div>';
}

echo '
    </div>
</div>';

// Include marketing tab navigation
include('nav.inc.php');

echo '
<div class="container mb-5">';

// Display total feature count
$total_features = count($plan_details);
$displayed_features = 0;
foreach ($feature_categories as $features) {
    $displayed_features += count($features);
}

echo '
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Plan Features:</strong> This plan includes ' . $total_features . ' features across ' . count($feature_categories) . ' categories.
    </div>
    
    <div class="row">
        <div class="col-lg-8">';

// Display features by category
foreach ($feature_categories as $category_name => $features) {
    echo '
            <div class="card feature-category">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>' . htmlspecialchars($category_name) . '</h5>
                </div>
                <div class="card-body">';
    
    foreach ($features as $feature_key => $feature_label) {
        $value = $plan_details[$feature_key] ?? 'Not included';
        
        // Determine the styling class based on value
        $value_class = 'disabled';
        $icon = 'x-circle';
        $icon_color = 'text-muted';
        
        if ($value === 'Unlimited' || $value === '∞') {
            $value_class = 'unlimited';
            $icon = 'check-circle-fill';
            $icon_color = 'text-success';
        } elseif ($value === 'Yes' || $value === 'Enabled' || $value === 'true') {
            $value_class = 'unlimited';
            $value = 'Included';
            $icon = 'check-circle-fill';
            $icon_color = 'text-success';
        } elseif (is_numeric($value) && $value > 0) {
            $value_class = 'limited';
            $icon = 'info-circle-fill';
            $icon_color = 'text-warning';
        } elseif ($value === 'Not included' || $value === 'No' || $value === 'false' || $value === '0') {
            $value_class = 'disabled';
            $value = 'Not included';
            $icon = 'x-circle';
            $icon_color = 'text-muted';
        } else {
            // For text values like "Premium Support"
            $value_class = 'unlimited';
            $icon = 'check-circle';
            $icon_color = 'text-primary';
        }
        
        echo '
                    <div class="feature-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-' . $icon . ' ' . $icon_color . ' me-3"></i>
                            <a href="#" class="text-decoration-none text-dark feature-link" 
                               data-feature="' . htmlspecialchars($feature_key) . '"
                               data-value="' . htmlspecialchars($value) . '"
                               data-plan="' . htmlspecialchars($plan_type) . '"
                               data-version="v7"
                               data-label="' . htmlspecialchars($feature_label) . '">
                                <span>' . htmlspecialchars($feature_label) . '</span>
                                <i class="bi bi-info-circle ms-1 text-muted small"></i>
                            </a>
                        </div>
                        <span class="feature-value ' . $value_class . '">' . htmlspecialchars($value) . '</span>
                    </div>';
    }
    
    echo '
                </div>
            </div>';
}

// Show complete feature list at the bottom
echo '
            <div class="card feature-category mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Complete Feature List (' . $total_features . ' features)</h5>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    <div class="small">';

// Sort features alphabetically for complete list
ksort($plan_details);
$feature_count = 0;

foreach ($plan_details as $feature_key => $feature_value) {
    // Skip title and description fields in the complete list
    if (strpos($feature_key, '_title') !== false || strpos($feature_key, '_description') !== false) {
        continue;
    }
    
    $feature_count++;
    
    // Clean up feature name for display
    $display_name = str_replace('feature_', '', $feature_key);
    $display_name = str_replace('_value', '', $display_name);
    $display_name = str_replace('_', ' ', $display_name);
    $display_name = ucwords($display_name);
    
    // Determine icon and color
    $icon = 'check-circle-fill';
    $icon_color = 'text-success';
    
    if ($feature_value === 'Not included' || $feature_value === 'No' || $feature_value === 'false' || $feature_value === '0') {
        $icon = 'x-circle';
        $icon_color = 'text-muted';
    }
    
    echo '
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-' . $icon . ' ' . $icon_color . ' me-2" style="font-size: 0.875rem;"></i>
                                <a href="#" class="text-decoration-none text-dark feature-link" 
                                   data-feature="' . htmlspecialchars($feature_key) . '"
                                   data-value="' . htmlspecialchars($feature_value) . '"
                                   data-plan="' . htmlspecialchars($plan_type) . '"
                                   data-version="v7"
                                   data-label="' . htmlspecialchars($display_name) . '">
                                    <span>' . htmlspecialchars($display_name) . '</span>
                                    <i class="bi bi-info-circle ms-1 text-muted" style="font-size: 0.75rem;"></i>
                                </a>
                            </div>
                            <span class="badge bg-light text-dark">' . htmlspecialchars($feature_value) . '</span>
                        </div>';
}

echo '
                    </div>
                    <div class="mt-3 text-center text-muted">
                        <small>Total: ' . $feature_count . ' active features</small>
                    </div>
                </div>
            </div>';

echo '
        </div>
        
        <div class="col-lg-4">
            <!-- Usage Summary -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Current Usage</h5>
                </div>
                <div class="card-body">';

// Get current usage statistics
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

// Active campaigns
$active_campaigns = $database->getrow(
    "SELECT COUNT(*) as count FROM mk_campaigns 
     WHERE company_id = :company_id AND status = 'active'",
    ['company_id' => $active_company_id]
)['count'] ?? 0;

// Monthly usage
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

$usage_by_type = [];
foreach ($monthly_usage as $usage) {
    $usage_by_type[$usage['campaign_type']] = $usage['count'];
}

// Display usage stats
$usage_items = [
    'Active Campaigns' => [$active_campaigns, $plan_details['feature_marketing_active_campaigns_value'] ?? 'N/A'],
    'Newsletters (Month)' => [$usage_by_type['newsletter'] ?? 0, $plan_details['feature_marketing_newsletters_value'] ?? 'N/A'],
    'Emails (Month)' => [$usage_by_type['email'] ?? 0, $plan_details['feature_marketing_emails_value'] ?? 'N/A'],
    'Text Messages (Month)' => [$usage_by_type['text_message'] ?? 0, $plan_details['feature_marketing_text_messages_value'] ?? 'N/A'],
];

foreach ($usage_items as $label => $data) {
    list($current, $limit) = $data;
    $percentage = $limit === 'Unlimited' || $limit === 'N/A' ? 0 : (($current / max(1, intval($limit))) * 100);
    $progress_color = $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'success');
    
    echo '
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>' . $label . '</small>
                            <small class="text-muted">' . $current . ' / ' . ($limit === 'Unlimited' ? '∞' : $limit) . '</small>
                        </div>';
    
    if ($limit !== 'Unlimited' && $limit !== 'N/A' && is_numeric($limit)) {
        echo '
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-' . $progress_color . '" style="width: ' . min(100, $percentage) . '%"></div>
                        </div>';
    }
    
    echo '
                    </div>';
}

echo '
                    <hr>
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>Usage resets on the 1st of each month
                    </small>
                </div>
            </div>';

// Upgrade suggestions (if not on highest plan)
if ($plan_type !== 'enterprise' && $plan_type !== 'business') {
    $next_plan = '';
    $next_plan_info = [];
    
    // Determine next plan tier
    $tier_order = ['free', 'starter', 'growth', 'professional', 'business', 'enterprise'];
    $current_index = array_search($plan_type, $tier_order);
    if ($current_index !== false && $current_index < count($tier_order) - 1) {
        $next_plan = $tier_order[$current_index + 1];
        $next_plan_info = $plan_tiers[$next_plan];
    }
    
    if (!empty($next_plan_info)) {
        echo '
            <div class="card upgrade-card">
                <div class="card-body text-center">
                    <i class="bi bi-rocket-takeoff display-4 text-primary mb-3"></i>
                    <h5>Ready to Grow?</h5>
                    <p class="mb-3">Upgrade to <strong>' . $next_plan_info['name'] . '</strong> for more features and higher limits.</p>
                    <a href="/myaccount/billing/upgrade.php?plan=' . $next_plan . '" class="btn btn-primary">
                        <i class="bi bi-arrow-up-circle me-2"></i>Upgrade Now
                    </a>';
        
        if ($next_plan_info['price'] !== null) {
            echo '
                    <div class="mt-2">
                        <small class="text-muted">Starting at $' . $next_plan_info['price'] . '/month</small>
                    </div>';
        }
        
        echo '
                </div>
            </div>';
    }
}

// Quick Actions
echo '
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group no-border">
                        <a href="/myaccount/marketing/" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-speedometer2 me-2"></i>Back to Dashboard</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/marketing/settings.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-gear me-2"></i>Marketing Settings</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/billing/" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-credit-card me-2"></i>Billing & Plans</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/support/contact.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-headset me-2"></i>Contact Support</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Help Card -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-question-circle me-2"></i>Need Help?</h6>
                    <p class="small text-muted mb-2">
                        Our support team is here to help you make the most of your marketing plan.
                    </p>
                    <a href="/support/marketing-guide.php" class="btn btn-sm btn-outline-secondary w-100">
                        View Marketing Guide
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feature Details Modal -->
<div class="modal fade" id="featureModal" tabindex="-1" aria-labelledby="featureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="featureModalLabel">Feature Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="featureContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle feature link clicks
    $(document).on("click", ".feature-link", function(e) {
        e.preventDefault();
        
        const $link = $(this);
        const feature = $link.data("feature");
        const value = $link.data("value");
        const plan = $link.data("plan");
        const version = $link.data("version");
        const label = $link.data("label");
        
        // Update modal title
        $("#featureModalLabel").html(`<i class="bi bi-info-circle me-2"></i>${label}`);
        
        // Show loading spinner
        $("#featureContent").html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById("featureModal"));
        modal.show();
        
        // Fetch feature details via AJAX
        $.ajax({
            url: "/myaccount/marketing/ajax/get-feature-details.php",
            method: "POST",
            data: {
                feature: feature,
                value: value,
                plan: plan,
                version: version,
                label: label
            },
            success: function(response) {
                if (response.success) {
                    let html = `
                        <div class="feature-details">
                            <div class="mb-3">
                                <span class="badge bg-primary me-2">${plan.toUpperCase()} PLAN</span>
                                <span class="badge bg-success">${value}</span>
                            </div>`;
                    
                    if (response.description) {
                        html += `
                            <div class="mb-3">
                                <h6 class="text-muted">Description</h6>
                                <p>${response.description}</p>
                            </div>`;
                    }
                    
                    if (response.content) {
                        html += `
                            <div class="mb-3">
                                <h6 class="text-muted">Details</h6>
                                <div>${response.content}</div>
                            </div>`;
                    }
                    
                    if (response.how_it_works) {
                        html += `
                            <div class="mb-3">
                                <h6 class="text-muted">How It Works</h6>
                                <p>${response.how_it_works}</p>
                            </div>`;
                    }
                    
                    if (response.benefits) {
                        html += `
                            <div class="mb-3">
                                <h6 class="text-muted">Benefits</h6>
                                <ul>`;
                        response.benefits.forEach(function(benefit) {
                            html += `<li>${benefit}</li>`;
                        });
                        html += `</ul>
                            </div>`;
                    }
                    
                    if (!response.description && !response.content) {
                        html += `
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Detailed information for this feature is coming soon.
                            </div>`;
                    }
                    
                    html += `
                            <div class="mt-3 pt-3 border-top text-muted small">
                                <strong>Feature ID:</strong> ${feature}<br>
                                <strong>Current Value:</strong> ${value}
                            </div>
                        </div>`;
                    
                    $("#featureContent").html(html);
                } else {
                    $("#featureContent").html(`
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            ${response.message || "Unable to load feature details."}
                        </div>
                    `);
                }
            },
            error: function() {
                $("#featureContent").html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-x-circle me-2"></i>
                        An error occurred while loading feature details.
                    </div>
                `);
            }
        });
    });
});
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>