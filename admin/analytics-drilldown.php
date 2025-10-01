<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Analytics Drill-Down - Birthday.Gold";
$page_description = "Detailed analytics drill-down";

// Get drill-down parameters
$page_filter = $_GET['page'] ?? null;
$country_filter = $_GET['country'] ?? null;
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Determine drill-down type
$drill_down_type = null;
$drill_down_value = null;

if ($page_filter) {
    $drill_down_type = 'page';
    $drill_down_value = $page_filter;
} elseif ($country_filter) {
    $drill_down_type = 'country';
    $drill_down_value = $country_filter;
}

if (!$drill_down_type) {
    header('Location: /admin/analytics-dashboard');
    exit;
}

// Build WHERE clause
// Use end of day for date_to to include full day
$where_conditions = ["type = 'analytics'", "create_dt BETWEEN :date_from AND DATE_ADD(:date_to, INTERVAL 1 DAY)"];
$query_params = ['date_from' => $date_from, 'date_to' => $date_to];

if ($drill_down_type === 'page') {
    $where_conditions[] = "JSON_EXTRACT(tracking_data, '$.page.path') = :page_path";
    $query_params['page_path'] = $page_filter;
} elseif ($drill_down_type === 'country') {
    $where_conditions[] = "JSON_EXTRACT(tracking_data, '$.geo.country_code') = :country_code";
    $query_params['country_code'] = $country_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get detailed event data
$events_sql = "
SELECT
    create_dt,
    ip,
    user_id,
    username,
    sessionid,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.path')) as page_path,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.page.referrer')) as referrer,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.event')) as event_type,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.device.mobile')) as is_mobile,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.device.userAgent')) as user_agent,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.geo.country')) as country,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.geo.city')) as city,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.traffic_source')) as traffic_source
FROM bg_sessiontracking
WHERE $where_clause
ORDER BY create_dt DESC
LIMIT 500
";
$events = $database->query($events_sql, $query_params)->fetchAll();

// Get summary stats
$stats_sql = "
SELECT
    COUNT(*) as total_events,
    COUNT(DISTINCT sessionid) as unique_sessions,
    COUNT(DISTINCT ip) as unique_ips,
    COUNT(DISTINCT user_id) as unique_users
FROM bg_sessiontracking
WHERE $where_clause
";
$stats = $database->query($stats_sql, $query_params)->fetch();

// Get event breakdown
$event_breakdown_sql = "
SELECT
    REPLACE(name, 'analytics:', '') as event_name,
    COUNT(*) as count
FROM bg_sessiontracking
WHERE $where_clause
GROUP BY event_name
ORDER BY count DESC
";
$event_breakdown = $database->query($event_breakdown_sql, $query_params)->fetchAll();

// Get scroll depth breakdown
$scroll_depth_sql = "
SELECT
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.event_data.depth')) as depth,
    COUNT(*) as count
FROM bg_sessiontracking
WHERE $where_clause
    AND name = 'analytics:scroll_depth'
    AND JSON_EXTRACT(tracking_data, '$.event_data.depth') IS NOT NULL
GROUP BY depth
ORDER BY CAST(depth AS UNSIGNED)
";
$scroll_depth_data = $database->query($scroll_depth_sql, $query_params)->fetchAll();

$additionalstyles .= '
<style>
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #212529;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
}

.chart-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    margin-bottom: 1.5rem;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin" style="padding: 1.5rem 0;">
    <div class="container">
        <h1 class="mt-2 mb-2 text-white">
            <i class="bi bi-zoom-in"></i>
            <?php if ($drill_down_type === 'page'): ?>
                Page Analysis
            <?php else: ?>
                Country Analysis
            <?php endif; ?>
        </h1>
        <p class="lead mb-2 text-white">
            <?php if ($drill_down_type === 'page'): ?>
                <code class="text-white bg-dark px-2 py-1 rounded"><?php echo htmlspecialchars($drill_down_value); ?></code>
            <?php else: ?>
                <?php echo htmlspecialchars($drill_down_value); ?>
            <?php endif; ?>
            <span class="opacity-75 ms-3">
                <?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>
            </span>
        </p>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Back Button -->
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="/admin/analytics-dashboard?date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
               class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
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
                <p class="stat-value"><?php echo number_format($stats['unique_ips']); ?></p>
                <p class="stat-label">Unique IPs</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <p class="stat-value"><?php echo number_format($stats['unique_users']); ?></p>
                <p class="stat-label">Unique Users</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Event Breakdown -->
        <div class="col-md-4">
            <div class="chart-card">
                <h3>Event Breakdown</h3>
                <canvas id="eventChart"></canvas>
            </div>

            <!-- Scroll Depth Breakdown -->
            <?php if (!empty($scroll_depth_data)): ?>
            <div class="chart-card mt-3">
                <h3>Scroll Depth Distribution</h3>
                <canvas id="scrollChart"></canvas>
                <div class="mt-3">
                    <small class="text-muted">
                        Shows how far users scrolled on this page
                    </small>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Detailed Events Table -->
        <div class="col-md-8">
            <div class="chart-card">
                <h3>Recent Events (Last 500)</h3>
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover table-sm">
                        <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                            <tr>
                                <th>Time</th>
                                <th>Event</th>
                                <?php if ($drill_down_type === 'country'): ?>
                                <th>Page</th>
                                <?php endif; ?>
                                <th>User</th>
                                <th>Location</th>
                                <th>Device</th>
                                <th>Session</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo date('M j, H:i:s', strtotime($event['create_dt'])); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($event['event_type']); ?></span>
                                </td>
                                <?php if ($drill_down_type === 'country'): ?>
                                <td><code><?php echo htmlspecialchars($event['page_path'] ?? '-'); ?></code></td>
                                <?php endif; ?>
                                <td><?php echo $event['username'] ? htmlspecialchars($event['username']) : 'Anonymous'; ?></td>
                                <td>
                                    <?php if ($event['city']): ?>
                                    <?php echo htmlspecialchars($event['city']); ?>, <?php echo htmlspecialchars($event['country']); ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($event['is_mobile'] === 'true'): ?>
                                    <i class="bi bi-phone"></i> Mobile
                                    <?php else: ?>
                                    <i class="bi bi-laptop"></i> Desktop
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo substr($event['sessionid'], 0, 8); ?>...</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Event Breakdown Chart
const eventCtx = document.getElementById('eventChart').getContext('2d');
new Chart(eventCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($event_breakdown, 'event_name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($event_breakdown, 'count')); ?>,
            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#ffc107']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

<?php if (!empty($scroll_depth_data)): ?>
// Scroll Depth Chart
const scrollCtx = document.getElementById('scrollChart').getContext('2d');
new Chart(scrollCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($d) { return $d['depth'] . '%'; }, $scroll_depth_data)); ?>,
        datasets: [{
            label: 'Users',
            data: <?php echo json_encode(array_column($scroll_depth_data, 'count')); ?>,
            backgroundColor: [
                '#ffc107', // 25% - yellow
                '#ff9800', // 50% - orange
                '#ff5722', // 75% - deep orange
                '#4caf50'  // 100% - green (success!)
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
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
});
<?php endif; ?>
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
