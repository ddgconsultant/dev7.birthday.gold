<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Analytics Dashboard - Birthday.Gold";
$page_description = "Real-time analytics and user behavior insights";

// Get filters from query params
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$event_filter = $_GET['event'] ?? 'all';
$site_filter = $_GET['site'] ?? 'all';
$traffic_filter = $_GET['traffic'] ?? 'all'; // Changed default to 'all' since old data lacks traffic_source
$server_filter = $_GET['server'] ?? 'all';

// Build WHERE clause based on filters
// Use end of day for date_to to include full day
$where_conditions = ["type = 'analytics'", "create_dt BETWEEN :date_from AND DATE_ADD(:date_to, INTERVAL 1 DAY)"];
$query_params = ['date_from' => $date_from, 'date_to' => $date_to];

// Site filter
if ($site_filter !== 'all') {
    $where_conditions[] = "site = :site";
    $query_params['site'] = $site_filter;
}

// Server filter (for load balanced production)
if ($server_filter !== 'all') {
    $where_conditions[] = "server = :server";
    $query_params['server'] = $server_filter;
}

// Traffic source filter
if ($traffic_filter === 'organic_only') {
    $where_conditions[] = "(JSON_EXTRACT(tracking_data, '$.traffic_source') = 'organic' OR JSON_EXTRACT(tracking_data, '$.traffic_source') IS NULL)";
} elseif ($traffic_filter === 'no_bots') {
    $where_conditions[] = "(JSON_EXTRACT(tracking_data, '$.is_bot') = false OR JSON_EXTRACT(tracking_data, '$.is_bot') IS NULL)";
} elseif ($traffic_filter === 'no_test') {
    $where_conditions[] = "(JSON_EXTRACT(tracking_data, '$.is_test') = false OR JSON_EXTRACT(tracking_data, '$.is_test') IS NULL)";
} elseif ($traffic_filter === 'bots_only') {
    $where_conditions[] = "JSON_EXTRACT(tracking_data, '$.is_bot') = true";
} elseif ($traffic_filter === 'test_only') {
    $where_conditions[] = "JSON_EXTRACT(tracking_data, '$.is_test') = true";
}

$where_clause = implode(' AND ', $where_conditions);

// DEBUG: Log the query parameters
if ($mode === 'dev') {
    error_log("Analytics Dashboard - Where Clause: $where_clause");
    error_log("Analytics Dashboard - Params: " . json_encode($query_params));
}

#-------------------------------------------------------------------------------
# ANALYTICS QUERIES
#-------------------------------------------------------------------------------

// 1. Page Views Over Time
$pageview_sql = "
SELECT
    DATE(create_dt) as date,
    COUNT(*) as views
FROM bg_sessiontracking
WHERE $where_clause
    AND name = 'analytics:pageview'
GROUP BY DATE(create_dt)
ORDER BY date ASC
";
$pageview_data = $database->query($pageview_sql, $query_params)->fetchAll();

// 2. Top Pages
$top_pages_sql = "
SELECT
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.path')) as page_path,
    COUNT(*) as views,
    COUNT(DISTINCT sessionid) as unique_sessions
FROM bg_sessiontracking
WHERE $where_clause
    AND name = 'analytics:pageview'
    AND JSON_EXTRACT(tracking_data, '$.page.path') IS NOT NULL
GROUP BY page_path
ORDER BY views DESC
LIMIT 20
";
$top_pages = $database->query($top_pages_sql, $query_params)->fetchAll();

// 3. Event Breakdown
$events_sql = "
SELECT
    REPLACE(name, 'analytics:', '') as event_name,
    COUNT(*) as count
FROM bg_sessiontracking
WHERE $where_clause
GROUP BY event_name
ORDER BY count DESC
";
$event_breakdown = $database->query($events_sql, $query_params)->fetchAll();

// 4. Device Breakdown
$device_sql = "
SELECT
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.device.mobile')) as is_mobile,
    COUNT(*) as count
FROM bg_sessiontracking
WHERE $where_clause
    AND name = 'analytics:pageview'
    AND JSON_EXTRACT(tracking_data, '$.device.mobile') IS NOT NULL
GROUP BY is_mobile
";
$device_data = $database->query($device_sql, $query_params)->fetchAll();

// 5. Summary Stats
$stats_sql = "
SELECT
    COUNT(*) as total_events,
    COUNT(DISTINCT sessionid) as unique_sessions,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT ip) as unique_ips
FROM bg_sessiontracking
WHERE $where_clause
";
$stats = $database->query($stats_sql, $query_params)->fetch();

// 6. Referrer Sources
$referrer_sql = "
SELECT
    CASE
        WHEN JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.referrer')) = 'direct' THEN 'Direct'
        WHEN JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.referrer')) LIKE '%birthday.gold%' THEN 'Internal'
        WHEN JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.referrer')) LIKE '%google%' THEN 'Google'
        WHEN JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.referrer')) LIKE '%facebook%' THEN 'Facebook'
        WHEN JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.referrer')) LIKE '%twitter%' THEN 'Twitter'
        WHEN JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.referrer')) LIKE '%linkedin%' THEN 'LinkedIn'
        ELSE 'Other External'
    END as source,
    COUNT(*) as count
FROM bg_sessiontracking
WHERE $where_clause
    AND name = 'analytics:pageview'
    AND JSON_EXTRACT(tracking_data, '$.data.entrypoint') = 'true'
GROUP BY source
ORDER BY count DESC
";
$referrer_data = $database->query($referrer_sql, $query_params)->fetchAll();

// 7. Recent Events (Live Feed)
$recent_events_sql = "
SELECT
    id,
    name,
    create_dt,
    ip,
    user_id,
    username,
    sessionid,
    site,
    server,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.path')) as page_path,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.event')) as event_type,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.traffic_source')) as traffic_source
FROM bg_sessiontracking
WHERE $where_clause
ORDER BY create_dt DESC
LIMIT 50
";
$recent_events = $database->query($recent_events_sql, $query_params)->fetchAll();

// 8. Get available sites and servers for filters
$sites_sql = "SELECT DISTINCT site FROM bg_sessiontracking WHERE type = 'analytics' ORDER BY site";
$available_sites = $database->query($sites_sql)->fetchAll(PDO::FETCH_COLUMN);

$servers_sql = "SELECT DISTINCT server FROM bg_sessiontracking WHERE type = 'analytics' ORDER BY server";
$available_servers = $database->query($servers_sql)->fetchAll(PDO::FETCH_COLUMN);

// 9. Geolocation breakdown
$geo_sql = "
SELECT
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.geo.country')) as country,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.geo.country_code')) as country_code,
    COUNT(*) as events,
    COUNT(DISTINCT sessionid) as sessions
FROM bg_sessiontracking
WHERE $where_clause
    AND JSON_EXTRACT(tracking_data, '$.geo.country') IS NOT NULL
GROUP BY country, country_code
ORDER BY events DESC
LIMIT 20
";
$geo_data = $database->query($geo_sql, $query_params)->fetchAll();

// 10. Traffic source breakdown (for pie chart)
$traffic_breakdown_sql = "
SELECT
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.traffic_source')) as source,
    COUNT(*) as count
FROM bg_sessiontracking
WHERE $where_clause
    AND JSON_EXTRACT(tracking_data, '$.traffic_source') IS NOT NULL
GROUP BY source
ORDER BY count DESC
";
$traffic_breakdown = $database->query($traffic_breakdown_sql, $query_params)->fetchAll();

$additionalstyles .= '
<style>
.analytics-dashboard {
    background: #f8f9fa;
    min-height: 100vh;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    text-align: center;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #212529;
    margin: 0;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.5rem;
}

.chart-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    margin-bottom: 1.5rem;
}

.chart-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.table-responsive {
    max-height: 400px;
    overflow-y: auto;
}

.event-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.event-pageview { background: #e3f2fd; color: #1976d2; }
.event-click { background: #f3e5f5; color: #7b1fa2; }
.event-form_submit { background: #e8f5e9; color: #388e3c; }
.event-scroll_depth { background: #fff3e0; color: #f57c00; }
.event-page_exit { background: #fce4ec; color: #c2185b; }

.filter-bar {
    background: white;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}

.live-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #4caf50;
    border-radius: 50%;
    margin-right: 0.5rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Analytics drill-down links */
.analytics-drill-link {
    color: #0d6efd;
    text-decoration: underline;
    cursor: pointer;
}

.analytics-drill-link:hover {
    color: #0a58ca;
    text-decoration: underline;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin">
    <div class="container">
        <h1 class="mt-3"><i class="bi bi-graph-up"></i> Analytics Dashboard</h1>
        <p class="lead mb-4">Real-time insights powered by Birthday.Gold Analytics</p>
    </div>
</div>

<div class="analytics-dashboard">
    <div class="container-fluid py-4">

        <!-- Enhanced Filter Bar -->
        <div class="filter-bar">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Site</label>
                    <select name="site" class="form-select">
                        <option value="all">All Sites</option>
                        <?php foreach ($available_sites as $site_option): ?>
                        <option value="<?php echo htmlspecialchars($site_option); ?>" <?php echo $site_filter === $site_option ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($site_option); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Server</label>
                    <select name="server" class="form-select">
                        <option value="all">All Servers</option>
                        <?php foreach ($available_servers as $server_option): ?>
                        <option value="<?php echo htmlspecialchars($server_option); ?>" <?php echo $server_filter === $server_option ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($server_option); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Traffic Type</label>
                    <select name="traffic" class="form-select">
                        <option value="all" <?php echo $traffic_filter === 'all' ? 'selected' : ''; ?>>All Traffic</option>
                        <option value="organic_only" <?php echo $traffic_filter === 'organic_only' ? 'selected' : ''; ?>>Organic Only</option>
                        <option value="no_bots" <?php echo $traffic_filter === 'no_bots' ? 'selected' : ''; ?>>No Bots</option>
                        <option value="no_test" <?php echo $traffic_filter === 'no_test' ? 'selected' : ''; ?>>No Test Users</option>
                        <option value="bots_only" <?php echo $traffic_filter === 'bots_only' ? 'selected' : ''; ?>>Bots Only</option>
                        <option value="test_only" <?php echo $traffic_filter === 'test_only' ? 'selected' : ''; ?>>Test Users Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <p class="stat-value"><?php echo number_format($stats['total_events']); ?></p>
                    <p class="stat-label">Total Events</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <p class="stat-value"><?php echo number_format($stats['unique_sessions']); ?></p>
                    <p class="stat-label">Unique Sessions</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <p class="stat-value"><?php echo number_format($stats['unique_users']); ?></p>
                    <p class="stat-label">Unique Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <p class="stat-value"><?php echo number_format($stats['unique_ips']); ?></p>
                    <p class="stat-label">Unique IPs</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pageviews Chart -->
            <div class="col-md-8">
                <div class="chart-card">
                    <h3 class="chart-title">Page Views Over Time</h3>
                    <canvas id="pageviewChart" height="80"></canvas>
                </div>
            </div>

            <!-- Event Breakdown -->
            <div class="col-md-4">
                <div class="chart-card">
                    <h3 class="chart-title">Event Breakdown</h3>
                    <canvas id="eventChart"></canvas>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Pages -->
            <div class="col-md-6">
                <div class="chart-card">
                    <h3 class="chart-title">Top Pages <small class="text-muted">(<?php echo count($top_pages); ?> pages)</small></h3>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Views</th>
                                    <th>Sessions</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_pages as $page): ?>
                                <tr>
                                    <td>
                                        <a href="/admin/analytics-drilldown?page=<?php echo urlencode($page['page_path']); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                                           class="analytics-drill-link">
                                            <code><?php echo htmlspecialchars($page['page_path']); ?></code>
                                        </a>
                                    </td>
                                    <td><?php echo number_format($page['views']); ?></td>
                                    <td><?php echo number_format($page['unique_sessions']); ?></td>
                                    <td>
                                        <a href="/admin/analytics-drilldown?page=<?php echo urlencode($page['page_path']); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-zoom-in"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Traffic Sources -->
            <div class="col-md-6">
                <div class="chart-card">
                    <h3 class="chart-title">Traffic Sources</h3>
                    <canvas id="referrerChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Geolocation & Traffic Breakdown Row -->
        <div class="row mt-3">
            <!-- Geographic Distribution -->
            <div class="col-md-6">
                <div class="chart-card">
                    <h3 class="chart-title">Geographic Distribution <small class="text-muted">(<?php echo count($geo_data); ?> countries)</small></h3>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Country</th>
                                    <th>Events</th>
                                    <th>Sessions</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($geo_data)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i class="bi bi-globe"></i> No geolocation data available yet
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($geo_data as $geo): ?>
                                <tr>
                                    <td>
                                        <a href="/admin/analytics-drilldown?country=<?php echo urlencode($geo['country_code']); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                                           class="analytics-drill-link">
                                            <?php if ($geo['country_code']): ?>
                                            <span class="fi fi-<?php echo strtolower($geo['country_code']); ?>"></span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($geo['country'] ?? 'Unknown'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo number_format($geo['events']); ?></td>
                                    <td><?php echo number_format($geo['sessions']); ?></td>
                                    <td>
                                        <a href="/admin/analytics-drilldown?country=<?php echo urlencode($geo['country_code']); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-zoom-in"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Traffic Type Breakdown -->
            <div class="col-md-6">
                <div class="chart-card">
                    <h3 class="chart-title">Traffic Type Breakdown <small class="text-muted">(<?php echo array_sum(array_column($traffic_breakdown, 'count')); ?> events)</small></h3>
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Live Events Feed -->
        <div class="row">
            <div class="col-12">
                <div class="chart-card">
                    <h3 class="chart-title">
                        <span class="live-indicator"></span>
                        Recent Events <small class="text-muted">(last <?php echo count($recent_events); ?>)</small>
                    </h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>Page</th>
                                    <th>User</th>
                                    <th>Session</th>
                                    <th>Site</th>
                                    <th>Server</th>
                                    <th>Type</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_events as $event): ?>
                                <tr>
                                    <td><?php echo date('H:i:s', strtotime($event['create_dt'])); ?></td>
                                    <td>
                                        <span class="event-badge event-<?php echo htmlspecialchars($event['event_type']); ?>">
                                            <?php echo htmlspecialchars($event['event_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($event['page_path']): ?>
                                        <a href="/admin/analytics-drilldown?page=<?php echo urlencode($event['page_path']); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                                           class="analytics-drill-link">
                                            <code><?php echo htmlspecialchars($event['page_path']); ?></code>
                                        </a>
                                        <?php else: ?>
                                        <code>-</code>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($event['user_id']): ?>
                                        <a href="/admin/analytics-drilldown?user=<?php echo $event['user_id']; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                                           class="analytics-drill-link">
                                            <?php echo htmlspecialchars($event['username']); ?>
                                        </a>
                                        <?php else: ?>
                                        Anonymous
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/analytics-drilldown?session=<?php echo urlencode($event['sessionid']); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                                           class="analytics-drill-link">
                                            <small><?php echo htmlspecialchars(substr($event['sessionid'], 0, 8)); ?>...</small>
                                        </a>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($event['site']); ?></span></td>
                                    <td><small><?php echo htmlspecialchars(substr($event['server'], 0, 12)); ?></small></td>
                                    <td>
                                        <?php if ($event['traffic_source']): ?>
                                        <span class="badge bg-<?php echo $event['traffic_source'] === 'organic' ? 'success' : ($event['traffic_source'] === 'bot' || $event['traffic_source'] === 'facebook_bot' || $event['traffic_source'] === 'google_bot' ? 'warning' : 'info'); ?>">
                                            <?php echo htmlspecialchars($event['traffic_source']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['ip']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Pageview Chart
const pageviewCtx = document.getElementById('pageviewChart').getContext('2d');
new Chart(pageviewCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($pageview_data, 'date')); ?>,
        datasets: [{
            label: 'Page Views',
            data: <?php echo json_encode(array_column($pageview_data, 'views')); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        }
    }
});

// Event Breakdown Chart
const eventCtx = document.getElementById('eventChart').getContext('2d');
new Chart(eventCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($event_breakdown, 'event_name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($event_breakdown, 'count')); ?>,
            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b']
        }]
    }
});

// Referrer Chart
const referrerCtx = document.getElementById('referrerChart').getContext('2d');
new Chart(referrerCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($referrer_data, 'source')); ?>,
        datasets: [{
            label: 'Visits',
            data: <?php echo json_encode(array_column($referrer_data, 'count')); ?>,
            backgroundColor: '#667eea'
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});

// Traffic Type Chart
const trafficCtx = document.getElementById('trafficChart').getContext('2d');
new Chart(trafficCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($traffic_breakdown, 'source')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($traffic_breakdown, 'count')); ?>,
            backgroundColor: [
                '#28a745', // organic - green
                '#ffc107', // bot - yellow
                '#fd7e14', // facebook_bot - orange
                '#6f42c1', // google_bot - purple
                '#17a2b8', // test_user - cyan
                '#6c757d'  // internal - gray
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return {
                                    text: `${label}: ${percentage}%`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            }
        }
    }
});

// Auto-refresh every 30 seconds
setTimeout(() => location.reload(), 30000);
</script>

<?php

$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>