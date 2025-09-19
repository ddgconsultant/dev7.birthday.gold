<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Analytics";

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get campaign statistics
$stats_sql = "SELECT 
    COUNT(*) as total_campaigns,
    COALESCE(SUM(CASE WHEN status = 'active' AND publish_dt <= NOW() AND (expire_dt IS NULL OR expire_dt > NOW()) THEN 1 ELSE 0 END), 0) as active_campaigns,
    COALESCE(SUM(CASE WHEN publish_dt > NOW() THEN 1 ELSE 0 END), 0) as scheduled_campaigns,
    COALESCE(SUM(CASE WHEN expire_dt < NOW() THEN 1 ELSE 0 END), 0) as expired_campaigns,
    COALESCE(SUM(views), 0) as total_views
    FROM bg_content 
    WHERE category = 'marketing' 
    AND type = 'campaign'
    AND create_dt BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)";

$stats = $database->getrow($stats_sql, ['start_date' => $start_date, 'end_date' => $end_date]);

// Make sure stats is not empty
if (!$stats) {
    $stats = [
        'total_campaigns' => 0,
        'active_campaigns' => 0,
        'scheduled_campaigns' => 0,
        'expired_campaigns' => 0,
        'total_views' => 0
    ];
}

// Get all campaigns for detailed analytics
$campaigns_sql = "SELECT * FROM bg_content 
                 WHERE category = 'marketing' 
                 AND type = 'campaign'
                 AND create_dt BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
                 ORDER BY views DESC";

$campaigns = $database->getrows($campaigns_sql, ['start_date' => $start_date, 'end_date' => $end_date]);

// Calculate budget totals
$total_budget = 0;
$budget_by_platform = [];
$campaigns_by_month = [];

foreach ($campaigns as &$campaign) {
    $campaign_data = json_decode($campaign['tags'], true) ?: [];
    $campaign['data'] = $campaign_data;
    
    // Add to total budget
    if (!empty($campaign_data['budget'])) {
        $total_budget += $campaign_data['budget'];
    }
    
    // Group by platform
    if (!empty($campaign_data['platforms'])) {
        foreach ($campaign_data['platforms'] as $platform) {
            if (!isset($budget_by_platform[$platform])) {
                $budget_by_platform[$platform] = ['count' => 0, 'budget' => 0];
            }
            $budget_by_platform[$platform]['count']++;
            $budget_by_platform[$platform]['budget'] += $campaign_data['budget'] ?? 0;
        }
    }
    
    // Group by month
    $month = date('Y-m', strtotime($campaign['publish_dt']));
    if (!isset($campaigns_by_month[$month])) {
        $campaigns_by_month[$month] = 0;
    }
    $campaigns_by_month[$month]++;
}

// Sort platforms by budget
arsort($budget_by_platform);

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 0.5rem;
    text-align: center;
    margin-bottom: 1.5rem;
}

.stat-card.green {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.stat-card.blue {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.orange {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.stat-card.red {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1rem;
    opacity: 0.95;
}

.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 2rem;
}

.platform-bar {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    height: 30px;
    margin-bottom: 10px;
    border-radius: 5px;
    position: relative;
    display: flex;
    align-items: center;
    padding: 0 15px;
    color: white;
    font-weight: 500;
}

.top-performer {
    border-left: 4px solid #28a745;
}

.metric-box {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    margin-bottom: 1rem;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #495057;
}

.metric-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-graph-up"></i> Marketing Analytics</h1>
        <p class="lead">Campaign performance and insights</p>
    </div>
</div>';

include('../includes/marketing-nav.php');

echo '
<div class="container mt-4 mb-5 pb-5">
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="' . htmlspecialchars($start_date) . '">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="' . htmlspecialchars($end_date) . '">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill"></i> Apply Filter
                    </button>
                    <a href="/staff/marketing-analytics" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number">' . number_format($stats['total_campaigns'] ?? 0) . '</div>
                <div class="stat-label">Total Campaigns</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card green">
                <div class="stat-number">' . number_format($stats['active_campaigns'] ?? 0) . '</div>
                <div class="stat-label">Active Campaigns</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card blue">
                <div class="stat-number">$' . number_format($total_budget ?? 0, 0) . '</div>
                <div class="stat-label">Total Budget</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card orange">
                <div class="stat-number">' . number_format($stats['total_views'] ?? 0) . '</div>
                <div class="stat-label">Total Views</div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-share-fill"></i> Platform Distribution</h5>
                </div>
                <div class="card-body">';

if (empty($budget_by_platform)) {
    echo '
                    <p class="text-muted">No platform data available</p>';
} else {
    $max_budget = max(array_column($budget_by_platform, 'budget'));
    foreach ($budget_by_platform as $platform => $data) {
        $width = $max_budget > 0 ? ($data['budget'] / $max_budget) * 100 : 0;
        echo '
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold">' . htmlspecialchars($platform) . '</span>
                            <span class="text-muted">
                                ' . $data['count'] . ' campaign' . ($data['count'] != 1 ? 's' : '') . ' 
                                ($' . number_format($data['budget'], 0) . ')
                            </span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                 style="width: ' . $width . '%">
                            </div>
                        </div>
                    </div>';
    }
}

echo '
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-calendar-fill"></i> Campaign Timeline</h5>
                </div>
                <div class="card-body">';

if (empty($campaigns_by_month)) {
    echo '
                    <p class="text-muted">No timeline data available</p>';
} else {
    echo '
                    <canvas id="timelineChart" height="250"></canvas>';
}

echo '
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-trophy-fill"></i> Top Performing Campaigns</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Status</th>
                            <th>Platforms</th>
                            <th>Budget</th>
                            <th>Views</th>
                            <th>Start Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

$top_campaigns = array_slice($campaigns, 0, 10);
foreach ($top_campaigns as $campaign) {
    $now = time();
    $start = strtotime($campaign['publish_dt']);
    $end = $campaign['expire_dt'] ? strtotime($campaign['expire_dt']) : null;
    
    if ($campaign['status'] == 'inactive') {
        $status = '<span class="badge bg-secondary">Draft</span>';
    } elseif ($now < $start) {
        $status = '<span class="badge bg-info">Scheduled</span>';
    } elseif ($end && $now > $end) {
        $status = '<span class="badge bg-danger">Expired</span>';
    } else {
        $status = '<span class="badge bg-success">Active</span>';
    }
    
    echo '
                        <tr' . ($campaign['views'] > 100 ? ' class="top-performer"' : '') . '>
                            <td>
                                <strong>' . htmlspecialchars($campaign['display_name']) . '</strong>';
    
    if ($campaign['description']) {
        echo '
                                <br><small class="text-muted">
                                    ' . htmlspecialchars(substr($campaign['description'], 0, 50)) . '...
                                </small>';
    }
    
    echo '
                            </td>
                            <td>' . $status . '</td>
                            <td>';
    
    if (!empty($campaign['data']['platforms'])) {
        foreach ($campaign['data']['platforms'] as $platform) {
            echo '
                                <span class="badge bg-light text-dark me-1">
                                    ' . htmlspecialchars($platform) . '
                                </span>';
        }
    } else {
        echo '
                                <span class="text-muted">-</span>';
    }
    
    echo '
                            </td>
                            <td>';
    
    if (!empty($campaign['data']['budget'])) {
        echo '$' . number_format($campaign['data']['budget'], 0);
    } else {
        echo '<span class="text-muted">-</span>';
    }
    
    echo '
                            </td>
                            <td>
                                <strong>' . number_format($campaign['views']) . '</strong>
                            </td>
                            <td>' . date('M j, Y', strtotime($campaign['publish_dt'])) . '</td>
                            <td>
                                <a href="/staff/marketing-view.php?id=' . $campaign['id'] . '" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                            </td>
                        </tr>';
}

echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-bar-chart-fill"></i> Performance Metrics</h5>
        </div>
        <div class="card-body">
            <div class="row">';

// Calculate average metrics
$total_impressions_goal = 0;
$total_clicks_goal = 0;
$total_conversions_goal = 0;
$campaigns_with_goals = 0;

foreach ($campaigns as $campaign) {
    if (!empty($campaign['data']['metrics'])) {
        $campaigns_with_goals++;
        $total_impressions_goal += $campaign['data']['metrics']['impressions_goal'] ?? 0;
        $total_clicks_goal += $campaign['data']['metrics']['clicks_goal'] ?? 0;
        $total_conversions_goal += $campaign['data']['metrics']['conversions_goal'] ?? 0;
    }
}

echo '
                <div class="col-md-3">
                    <div class="metric-box">
                        <div class="metric-value text-info">
                            ' . number_format($total_impressions_goal) . '
                        </div>
                        <div class="metric-label">Target Impressions</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-box">
                        <div class="metric-value text-primary">
                            ' . number_format($total_clicks_goal) . '
                        </div>
                        <div class="metric-label">Target Clicks</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-box">
                        <div class="metric-value text-success">
                            ' . number_format($total_conversions_goal) . '
                        </div>
                        <div class="metric-label">Target Conversions</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-box">
                        <div class="metric-value text-warning">
                            ' . ($total_budget > 0 ? number_format(($stats['total_views'] ?? 0) / ($total_budget / 1000), 2) : '0') . '
                        </div>
                        <div class="metric-label">Views per $1K Spent</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>';

if (!empty($campaigns_by_month)) {
    echo '
const ctx = document.getElementById("timelineChart").getContext("2d");
const timelineChart = new Chart(ctx, {
    type: "line",
    data: {
        labels: ' . json_encode(array_keys($campaigns_by_month)) . ',
        datasets: [{
            label: "Campaigns",
            data: ' . json_encode(array_values($campaigns_by_month)) . ',
            borderColor: "rgb(102, 126, 234)",
            backgroundColor: "rgba(102, 126, 234, 0.1)",
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});';
}

echo '
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>