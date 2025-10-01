<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Analytics Dashboard - Birthday.Gold";
$page_description = "Real-time analytics and user behavior insights";

// Get date range from query params
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$event_filter = $_GET['event'] ?? 'all';

#-------------------------------------------------------------------------------
# ANALYTICS QUERIES
#-------------------------------------------------------------------------------

// 1. Page Views Over Time
$pageview_sql = "
SELECT
    DATE(create_dt) as date,
    COUNT(*) as views
FROM bg_sessiontracking
WHERE type = 'analytics'
    AND name = 'analytics:pageview'
    AND create_dt BETWEEN :date_from AND :date_to
GROUP BY DATE(create_dt)
ORDER BY date ASC
";
$pageview_data = $database->query($pageview_sql, ['date_from' => $date_from, 'date_to' => $date_to])->fetchAll();

// 2. Top Pages
$top_pages_sql = "
SELECT
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.path')) as page_path,
    COUNT(*) as views,
    COUNT(DISTINCT sessionid) as unique_sessions
FROM bg_sessiontracking
WHERE type = 'analytics'
    AND name = 'analytics:pageview'
    AND create_dt BETWEEN :date_from AND :date_to
    AND JSON_EXTRACT(tracking_data, '$.page.path') IS NOT NULL
GROUP BY page_path
ORDER BY views DESC
LIMIT 20
";
$top_pages = $database->query($top_pages_sql, ['date_from' => $date_from, 'date_to' => $date_to])->fetchAll();

// 3. Event Breakdown
$events_sql = "
SELECT
    REPLACE(name, 'analytics:', '') as event_name,
    COUNT(*) as count
FROM bg_sessiontracking
WHERE type = 'analytics'
    AND create_dt BETWEEN :date_from AND :date_to
GROUP BY event_name
ORDER BY count DESC
";
$event_breakdown = $database->query($events_sql, ['date_from' => $date_from, 'date_to' => $date_to])->fetchAll();

// 4. Device Breakdown
$device_sql = "
SELECT
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.device.mobile')) as is_mobile,
    COUNT(*) as count
FROM bg_sessiontracking
WHERE type = 'analytics'
    AND name = 'analytics:pageview'
    AND create_dt BETWEEN :date_from AND :date_to
    AND JSON_EXTRACT(tracking_data, '$.device.mobile') IS NOT NULL
GROUP BY is_mobile
";
$device_data = $database->query($device_sql, ['date_from' => $date_from, 'date_to' => $date_to])->fetchAll();

// 5. Summary Stats
$stats_sql = "
SELECT
    COUNT(*) as total_events,
    COUNT(DISTINCT sessionid) as unique_sessions,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT ip) as unique_ips
FROM bg_sessiontracking
WHERE type = 'analytics'
    AND create_dt BETWEEN :date_from AND :date_to
";
$stats = $database->query($stats_sql, ['date_from' => $date_from, 'date_to' => $date_to])->fetch();

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
WHERE type = 'analytics'
    AND name = 'analytics:pageview'
    AND create_dt BETWEEN :date_from AND :date_to
    AND JSON_EXTRACT(tracking_data, '$.data.entrypoint') = 'true'
GROUP BY source
ORDER BY count DESC
";
$referrer_data = $database->query($referrer_sql, ['date_from' => $date_from, 'date_to' => $date_to])->fetchAll();

// 7. Recent Events (Live Feed)
$recent_events_sql = "
SELECT
    id,
    name,
    create_dt,
    ip,
    user_id,
    username,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.path')) as page_path,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.event')) as event_type
FROM bg_sessiontracking
WHERE type = 'analytics'
    AND create_dt BETWEEN :date_from AND :date_to
ORDER BY create_dt DESC
LIMIT 50
";
$recent_events = $database->query($recent_events_sql, ['date_from' => $date_from, 'date_to' => $date_to])->fetchAll();

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
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="analytics-dashboard">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="mb-2"><i class="bi bi-graph-up"></i> Analytics Dashboard</h1>
                <p class="text-muted">Real-time insights powered by Birthday.Gold Analytics</p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="filter-bar">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Event Type</label>
                    <select name="event" class="form-select">
                        <option value="all">All Events</option>
                        <option value="pageview" <?php echo $event_filter === 'pageview' ? 'selected' : ''; ?>>Page Views</option>
                        <option value="click" <?php echo $event_filter === 'click' ? 'selected' : ''; ?>>Clicks</option>
                        <option value="form_submit" <?php echo $event_filter === 'form_submit' ? 'selected' : ''; ?>>Form Submissions</option>
                    </select>
                </div>
                <div class="col-md-3">
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
                    <h3 class="chart-title">Top Pages</h3>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Views</th>
                                    <th>Sessions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_pages as $page): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($page['page_path']); ?></code></td>
                                    <td><?php echo number_format($page['views']); ?></td>
                                    <td><?php echo number_format($page['unique_sessions']); ?></td>
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

        <!-- Live Events Feed -->
        <div class="row">
            <div class="col-12">
                <div class="chart-card">
                    <h3 class="chart-title">
                        <span class="live-indicator"></span>
                        Recent Events
                    </h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>Page</th>
                                    <th>User</th>
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
                                    <td><code><?php echo htmlspecialchars($event['page_path'] ?? '-'); ?></code></td>
                                    <td><?php echo $event['username'] ? htmlspecialchars($event['username']) : 'Anonymous'; ?></td>
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

// Auto-refresh every 30 seconds
setTimeout(() => location.reload(), 30000);
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
?>
