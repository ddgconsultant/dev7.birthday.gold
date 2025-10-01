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
$where_conditions = ["type = 'analytics'", "create_dt BETWEEN :date_from AND :date_to"];
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

$additionalstyles .= '
<style>
.drill-down-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
}

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

<div class="drill-down-header">
    <div class="container">
        <div class="d-flex align-items-center">
            <a href="/admin/analytics-dashboard?date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
               class="btn btn-light me-3">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <div>
                <h1 class="mb-0">
                    <i class="bi bi-zoom-in"></i>
                    <?php if ($drill_down_type === 'page'): ?>
                        Page Analysis: <code><?php echo htmlspecialchars($drill_down_value); ?></code>
                    <?php else: ?>
                        Country Analysis: <?php echo htmlspecialchars($drill_down_value); ?>
                    <?php endif; ?>
                </h1>
                <p class="mb-0 mt-2 opacity-75">
                    <?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-4">
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
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
