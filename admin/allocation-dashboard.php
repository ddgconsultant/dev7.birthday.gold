<?php
/**
 * Admin Allocation Dashboard
 * Analytics and management for the allocation system
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.allocationmanager.php');

// Admin check
if (!$admin->isadmin() && !$admin->isstaff()) {
    die('Admin access required');
}

$allocationManager = new AllocationManager($database);

// Get date range
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');

// Get overview stats
$sql = "SELECT 
            COUNT(DISTINCT user_id) as total_users,
            SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_granted,
            SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_revoked,
            SUM(amount_used) as total_used,
            AVG(CASE WHEN amount > 0 THEN amount_used / NULLIF(amount, 0) * 100 ELSE 0 END) as avg_usage_rate
        FROM bg_user_allocations
        WHERE created_at BETWEEN :start AND :end";

$overview = $database->getrow($sql, ['start' => $start_date, 'end' => $end_date]);

// Get stats by type
$sql = "SELECT 
            allocation_type,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            SUM(amount_used) as total_used,
            AVG(amount_used / NULLIF(amount, 0) * 100) as usage_rate,
            COUNT(DISTINCT user_id) as unique_users
        FROM bg_user_allocations
        WHERE created_at BETWEEN :start AND :end
        AND amount > 0
        GROUP BY allocation_type
        ORDER BY total_amount DESC";

$by_type = $database->getrows($sql, ['start' => $start_date, 'end' => $end_date]);

// Get top performing allocations
$sql = "SELECT 
            a.allocation_comment,
            a.allocation_type,
            COUNT(DISTINCT a.allocation_id) as enrollments,
            COUNT(DISTINCT a.user_id) as users,
            AVG(DATEDIFF(NOW(), a.created_at)) as avg_days_to_use
        FROM bg_user_allocations a
        WHERE a.created_at BETWEEN :start AND :end
        AND a.amount_used > 0
        GROUP BY a.allocation_comment, a.allocation_type
        ORDER BY enrollments DESC
        LIMIT 10";

$top_performing = $database->getrows($sql, ['start' => $start_date, 'end' => $end_date]);

// Get expiration impact
$sql = "SELECT 
            CASE 
                WHEN expires_at IS NULL THEN 'Non-expiring'
                WHEN DATEDIFF(expires_at, created_at) <= 30 THEN '< 30 days'
                WHEN DATEDIFF(expires_at, created_at) <= 90 THEN '30-90 days'
                ELSE '> 90 days'
            END as expiry_window,
            COUNT(*) as count,
            AVG(amount_used / NULLIF(amount, 0) * 100) as usage_rate
        FROM bg_user_allocations
        WHERE created_at BETWEEN :start AND :end
        AND amount > 0
        GROUP BY expiry_window
        ORDER BY FIELD(expiry_window, '< 30 days', '30-90 days', '> 90 days', 'Non-expiring')";

$expiration_impact = $database->getrows($sql, ['start' => $start_date, 'end' => $end_date]);

$pagetitle = 'Allocation Dashboard';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container-fluid my-4">
    <h1>Allocation Analytics Dashboard</h1>
    
    <!-- Date Range Filter -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-auto">
            <input type="date" name="start" class="form-control" value="<?php echo $start_date; ?>">
        </div>
        <div class="col-auto">
            <input type="date" name="end" class="form-control" value="<?php echo $end_date; ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
    
    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h2><?php echo number_format($overview['total_users'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Allocations Granted</h5>
                    <h2><?php echo number_format($overview['total_granted'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Allocations Used</h5>
                    <h2><?php echo number_format($overview['total_used'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Usage Rate</h5>
                    <h2><?php echo number_format($overview['avg_usage_rate'] ?? 0, 1); ?>%</h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- By Type Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Allocations by Type</h5>
                </div>
                <div class="card-body">
                    <canvas id="byTypeChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Expiration Impact -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Usage Rate by Expiration Window</h5>
                </div>
                <div class="card-body">
                    <canvas id="expirationChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tables Row -->
    <div class="row">
        <!-- By Type Table -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Detailed Stats by Type</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Count</th>
                                <th>Granted</th>
                                <th>Used</th>
                                <th>Usage Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($by_type)): ?>
                            <?php foreach ($by_type as $type): ?>
                            <tr>
                                <td><?php echo ucfirst($type['allocation_type']); ?></td>
                                <td><?php echo number_format($type['count']); ?></td>
                                <td><?php echo number_format($type['total_amount']); ?></td>
                                <td><?php echo number_format($type['total_used']); ?></td>
                                <td><?php echo number_format($type['usage_rate'] ?? 0, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No data available</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Top Performing -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Top Performing Allocations</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Allocation</th>
                                <th>Type</th>
                                <th>Enrollments</th>
                                <th>Avg Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_performing)): ?>
                            <?php foreach ($top_performing as $perf): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($perf['allocation_comment']); ?></td>
                                <td><?php echo $perf['allocation_type']; ?></td>
                                <td><?php echo number_format($perf['enrollments']); ?></td>
                                <td><?php echo number_format($perf['avg_days_to_use'] ?? 0, 1); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No data available</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// By Type Chart
const byTypeCtx = document.getElementById('byTypeChart').getContext('2d');
new Chart(byTypeCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($by_type ?? [], 'allocation_type')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($by_type ?? [], 'total_amount')); ?>,
            backgroundColor: ['#6c63ff', '#28a745', '#ffc107', '#dc3545', '#17a2b8']
        }]
    }
});

// Expiration Chart
const expCtx = document.getElementById('expirationChart').getContext('2d');
new Chart(expCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($expiration_impact ?? [], 'expiry_window')); ?>,
        datasets: [{
            label: 'Usage Rate %',
            data: <?php echo json_encode(array_column($expiration_impact ?? [], 'usage_rate')); ?>,
            backgroundColor: '#6c63ff'
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>