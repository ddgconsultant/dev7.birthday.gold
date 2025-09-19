<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$campaign_id = intval($_GET['id'] ?? 0);

if (!$campaign_id) {
    header('Location: /myaccount/marketing/campaigns.php');
    exit;
}

// Get campaign details with platform info
$campaign_sql = "SELECT c.*, p.platform_name, p.icon_class, p.platform_url
                FROM mk_campaigns c
                LEFT JOIN mk_platforms p ON c.platform_id = p.platform_id
                WHERE c.campaign_id = :id";
$campaign = $database->getrow($campaign_sql, ['id' => $campaign_id]);

if (!$campaign) {
    header('Location: /myaccount/marketing/campaigns.php');
    exit;
}

// Get user's company context and verify access
$company_id = $current_user_data['company_id'] ?? 99;
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

// Verify access to this campaign
if ($campaign['company_id'] != $active_company_id && !$account->isstaff()) {
    header('Location: /myaccount/marketing/campaigns.php');
    exit;
}

$pagetitle = "Campaign Analytics - " . $campaign['campaign_name'];

// Get metrics for this campaign
$metrics_sql = "SELECT * FROM mk_metrics 
               WHERE campaign_id = :campaign_id 
               ORDER BY metric_date DESC 
               LIMIT 30";
$metrics = $database->getrows($metrics_sql, ['campaign_id' => $campaign_id]);

// Calculate summary metrics
$total_spend = 0;
$total_impressions = 0;
$total_clicks = 0;
$total_conversions = 0;

foreach ($metrics as $metric) {
    switch ($metric['metric_type']) {
        case 'spend':
            $total_spend += $metric['metric_value'];
            break;
        case 'impressions':
            $total_impressions += $metric['metric_value'];
            break;
        case 'clicks':
            $total_clicks += $metric['metric_value'];
            break;
        case 'conversions':
            $total_conversions += $metric['metric_value'];
            break;
    }
}

$ctr = $total_impressions > 0 ? ($total_clicks / $total_impressions) * 100 : 0;
$cpc = $total_clicks > 0 ? $total_spend / $total_clicks : 0;
$conversion_rate = $total_clicks > 0 ? ($total_conversions / $total_clicks) * 100 : 0;

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}
.campaign-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #0d6efd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.metric-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.2s ease;
}
.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.metric-value {
    font-size: 2rem;
    font-weight: bold;
    color: #0d6efd;
}
.metric-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 0.5rem;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-graph-up me-3"></i>Campaign Analytics</h1>
        <p class="lead">Performance metrics and insights for your campaign</p>';

// Show company context
if ($campaign['company_id'] == 0) {
    echo '
        <div class="badge bg-primary fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Birthday Gold (Internal Marketing)
        </div>';
} else {
    echo '
        <div class="badge bg-info fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Company ID: ' . $campaign['company_id'] . '
        </div>';
}

echo '
    </div>
</div>';

// Include marketing tab navigation  
include('nav.inc.php');

echo '
<div class="container mb-5">
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="/myaccount/marketing/campaigns.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Campaigns
            </a>
        </div>
    </div>
    
    <div class="campaign-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-1">
                    <i class="' . ($campaign['icon_class'] ?? 'bi bi-megaphone') . ' me-2"></i>
                    ' . htmlspecialchars($campaign['campaign_name'] ?? 'Unknown Campaign') . '
                </h3>
                <p class="mb-2 text-muted">' . htmlspecialchars($campaign['description'] ?? '') . '</p>
                <div class="d-flex gap-3">
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-tag me-1"></i>' . htmlspecialchars($campaign['campaign_type'] ?? 'unknown') . '
                    </span>
                    <span class="badge bg-' . ($status_colors[$campaign['status']] ?? 'secondary') . '">
                        ' . htmlspecialchars(ucfirst($campaign['status'] ?? 'unknown')) . '
                    </span>
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-currency-dollar me-1"></i>' . number_format($campaign['budget_amount'] ?? 0, 2) . '
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <a href="' . htmlspecialchars($campaign['platform_url'] ?? '#') . '" target="_blank" class="btn btn-primary">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Open ' . htmlspecialchars($campaign['platform_name'] ?? 'Platform') . '
                </a>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-value">' . number_format($total_impressions) . '</div>
                <div class="metric-label">Impressions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-value">' . number_format($total_clicks) . '</div>
                <div class="metric-label">Clicks</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-value">' . number_format($ctr, 2) . '%</div>
                <div class="metric-label">Click Rate</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-value">$' . number_format($total_spend, 2) . '</div>
                <div class="metric-label">Total Spend</div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-value">' . number_format($total_conversions) . '</div>
                <div class="metric-label">Conversions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-value">' . number_format($conversion_rate, 2) . '%</div>
                <div class="metric-label">Conversion Rate</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-value">$' . number_format($cpc, 2) . '</div>
                <div class="metric-label">Cost Per Click</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-value">' . ($campaign['start_date'] ? date('M j, Y', strtotime($campaign['start_date'])) : 'Not Set') . '</div>
                <div class="metric-label">Start Date</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Performance Timeline</h5>
                </div>
                <div class="card-body">';

if (empty($metrics)) {
    echo '
                    <div class="text-center py-5">
                        <i class="bi bi-graph-up display-4 text-muted"></i>
                        <h5 class="mt-3 text-muted">No metrics data yet</h5>
                        <p class="text-muted">Performance data will appear here once the campaign is running and syncing data</p>
                    </div>';
} else {
    echo '
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Metric</th>
                                    <th>Value</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    foreach ($metrics as $metric) {
        $value_display = $metric['metric_value'];
        if ($metric['metric_type'] == 'spend') {
            $value_display = '$' . number_format($metric['metric_value'], 2);
        } elseif (in_array($metric['metric_type'], ['ctr', 'conversion_rate'])) {
            $value_display = number_format($metric['metric_value'], 2) . '%';
        } else {
            $value_display = number_format($metric['metric_value']);
        }
        
        echo '
                                <tr>
                                    <td>' . date('M j, Y', strtotime($metric['metric_date'])) . '</td>
                                    <td>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $metric['metric_type']))) . '</td>
                                    <td><strong>' . $value_display . '</strong></td>
                                    <td>' . htmlspecialchars($metric['external_source'] ?? 'Manual') . '</td>
                                </tr>';
    }
    
    echo '
                            </tbody>
                        </table>
                    </div>';
}

echo '
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Campaign Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Platform:</strong><br>
                        <span class="text-muted">' . htmlspecialchars($campaign['platform_name'] ?? 'Unknown') . '</span>
                    </div>
                    <div class="mb-3">
                        <strong>Budget Type:</strong><br>
                        <span class="text-muted">' . htmlspecialchars(ucfirst($campaign['budget_type'] ?? 'unknown')) . '</span>
                    </div>
                    <div class="mb-3">
                        <strong>Duration:</strong><br>
                        <span class="text-muted">
                            ' . ($campaign['start_date'] ? date('M j, Y', strtotime($campaign['start_date'])) : 'Not set') . '
                            ' . ($campaign['end_date'] ? ' - ' . date('M j, Y', strtotime($campaign['end_date'])) : '') . '
                        </span>
                    </div>
                    <div class="mb-3">
                        <strong>Created:</strong><br>
                        <span class="text-muted">' . date('M j, Y g:i A', strtotime($campaign['create_dt'])) . '</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="/myaccount/marketing/campaign-edit.php?id=' . $campaign_id . '" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i>Edit Campaign
                        </a>
                        <a href="' . htmlspecialchars($campaign['platform_url'] ?? '#') . '" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right me-2"></i>Open Platform
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>