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
$session_filter = $_GET['session'] ?? null;
$user_filter = $_GET['user'] ?? null;
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Pagination parameters
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 250;
$current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($current_page - 1) * $per_page;

// Validate per_page
if (!in_array($per_page, [250, 1000, 5000])) {
    $per_page = 250;
}

// Determine drill-down type
$drill_down_type = null;
$drill_down_value = null;

if ($page_filter) {
    $drill_down_type = 'page';
    $drill_down_value = $page_filter;
} elseif ($country_filter) {
    $drill_down_type = 'country';
    $drill_down_value = $country_filter;
} elseif ($session_filter) {
    $drill_down_type = 'session';
    $drill_down_value = $session_filter;
} elseif ($user_filter) {
    $drill_down_type = 'user';
    $drill_down_value = $user_filter;
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
} elseif ($drill_down_type === 'session') {
    $where_conditions[] = "sessionid = :session_id";
    $query_params['session_id'] = $session_filter;
} elseif ($drill_down_type === 'user') {
    $where_conditions[] = "user_id = :user_id";
    $query_params['user_id'] = $user_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "
SELECT COUNT(*) as total
FROM bg_sessiontracking
WHERE $where_clause
";
$total_events = $database->query($count_sql, $query_params)->fetchColumn();
$total_pages = ceil($total_events / $per_page);

// Get detailed event data with pagination
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
LIMIT $per_page OFFSET $offset
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

// Helper function to build pagination URLs
function buildPaginationUrl($page_num, $per_page, $params) {
    $query = array_merge($params, ['p' => $page_num, 'per_page' => $per_page]);
    return '/admin/analytics-drilldown?' . http_build_query($query);
}

// Build base params for pagination
$base_params = array_filter([
    'page' => $page_filter,
    'country' => $country_filter,
    'session' => $session_filter,
    'user' => $user_filter,
    'date_from' => $date_from,
    'date_to' => $date_to
]);

// Use no footer for this page
$display_footertype = 'none';

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
<div class="content-header-admin" style="padding: 1.5rem 0;">
    <div class="container">
        <h1 class="mt-2 mb-2 text-white">
            <i class="bi bi-zoom-in"></i>
            <?php if ($drill_down_type === 'page'): ?>
                Page Analysis
            <?php elseif ($drill_down_type === 'country'): ?>
                Country Analysis
            <?php elseif ($drill_down_type === 'session'): ?>
                Session Journey
            <?php else: ?>
                User Activity
            <?php endif; ?>
        </h1>
        <p class="lead mb-2 text-white">
            <?php if ($drill_down_type === 'page'): ?>
                <code class="text-white bg-dark px-2 py-1 rounded"><?php echo htmlspecialchars($drill_down_value); ?></code>
            <?php elseif ($drill_down_type === 'session'): ?>
                Session ID: <code class="text-white bg-dark px-2 py-1 rounded"><?php echo htmlspecialchars(substr($drill_down_value, 0, 16)); ?>...</code>
            <?php elseif ($drill_down_type === 'user'): ?>
                User ID: <span class="badge bg-light text-dark"><?php echo htmlspecialchars($drill_down_value); ?></span>
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Events (<?php echo number_format($total_events); ?> total)</h3>
                    <div class="d-flex align-items-center gap-2">
                        <label class="mb-0 me-2">Per page:</label>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="location.href=this.value">
                            <option value="<?php echo buildPaginationUrl($current_page, 250, $base_params); ?>" <?php echo $per_page === 250 ? 'selected' : ''; ?>>250</option>
                            <option value="<?php echo buildPaginationUrl($current_page, 1000, $base_params); ?>" <?php echo $per_page === 1000 ? 'selected' : ''; ?>>1,000</option>
                            <option value="<?php echo buildPaginationUrl($current_page, 5000, $base_params); ?>" <?php echo $per_page === 5000 ? 'selected' : ''; ?>>5,000</option>
                        </select>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Event pagination">
                    <ul class="pagination pagination-sm mb-3">
                        <!-- Previous -->
                        <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildPaginationUrl($current_page - 1, $per_page, $base_params); ?>">Previous</a>
                        </li>
                        <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">Previous</span>
                        </li>
                        <?php endif; ?>

                        <!-- Page numbers -->
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);

                        if ($start_page > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?php echo buildPaginationUrl(1, $per_page, $base_params); ?>">1</a></li>
                            <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaginationUrl($i, $per_page, $base_params); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item"><a class="page-link" href="<?php echo buildPaginationUrl($total_pages, $per_page, $base_params); ?>"><?php echo $total_pages; ?></a></li>
                        <?php endif; ?>

                        <!-- Next -->
                        <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildPaginationUrl($current_page + 1, $per_page, $base_params); ?>">Next</a>
                        </li>
                        <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">Next</span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

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
                                <td>
                                    <?php if ($event['username']): ?>
                                        <?php if ($event['user_id'] && $drill_down_type !== 'user'): ?>
                                        <a href="/admin/analytics-drilldown?user=<?php echo $event['user_id']; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                                           class="analytics-drill-link">
                                            <?php echo htmlspecialchars($event['username']); ?>
                                        </a>
                                        <?php else: ?>
                                        <?php echo htmlspecialchars($event['username']); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    Anonymous
                                    <?php endif; ?>
                                </td>
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
                                    <?php if ($drill_down_type !== 'session'): ?>
                                    <a href="/admin/analytics-drilldown?session=<?php echo urlencode($event['sessionid']); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                                       class="analytics-drill-link">
                                        <small><?php echo substr($event['sessionid'], 0, 8); ?>...</small>
                                    </a>
                                    <?php else: ?>
                                    <small><?php echo substr($event['sessionid'], 0, 8); ?>...</small>
                                    <?php endif; ?>
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
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
