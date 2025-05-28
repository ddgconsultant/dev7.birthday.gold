<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Get company id if not already set
if (empty($company_id)) {
    $company_id = $_GET['cid'] ?? null;
}

// Fetch analytics data
$sql = "SELECT 
            COUNT(DISTINCT u.user_id) as total_users,
            COUNT(DISTINCT CASE WHEN u.status = 'active' THEN u.user_id END) as active_users,
            COUNT(DISTINCT r.reward_id) as total_rewards,
            SUM(r.reward_value) as total_reward_value
        FROM bg_companies c
        LEFT JOIN bg_user_companies uc ON c.company_id = uc.company_id
        LEFT JOIN bg_users u ON uc.user_id = u.user_id
        LEFT JOIN bg_company_rewards r ON c.company_id = r.company_id
        WHERE c.company_id = :company_id";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$metrics = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user growth over time
$sql = "SELECT 
            DATE_FORMAT(u.create_dt, '%Y-%m') as month,
            COUNT(DISTINCT u.user_id) as new_users
        FROM bg_users u
        JOIN bg_user_companies uc ON u.user_id = uc.user_id
        WHERE uc.company_id = :company_id
        GROUP BY DATE_FORMAT(u.create_dt, '%Y-%m')
        ORDER BY month";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$user_growth = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch reward distribution
$sql = "SELECT 
            r.category,
            COUNT(*) as count,
            SUM(r.reward_value) as total_value
        FROM bg_company_rewards r
        WHERE r.company_id = :company_id
        GROUP BY r.category";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$reward_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add required styles
$additionalstyles .= '
<style>
.metric-card {
    transition: all 0.2s ease-in-out;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
</style>';
?>

<div class="container-fluid px-4 py-5">
    <!-- Header Section -->
    <div class="mb-4">
        <h2 class="mb-1">Analytics Dashboard</h2>
        <p class="text-muted mb-0">Track engagement and reward metrics</p>
    </div>

    <!-- Key Metrics -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card metric-card bg-primary bg-opacity-10">
                <div class="card-body">
                    <h6 class="text-primary mb-2">Total Users</h6>
                    <h3 class="mb-0"><?php echo number_format($metrics['total_users']); ?></h3>
                    <p class="text-muted small mb-0">
                        <?php echo number_format($metrics['active_users']); ?> active
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card bg-success bg-opacity-10">
                <div class="card-body">
                    <h6 class="text-success mb-2">Total Rewards</h6>
                    <h3 class="mb-0"><?php echo number_format($metrics['total_rewards']); ?></h3>
                    <p class="text-muted small mb-0">Across all categories</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card bg-warning bg-opacity-10">
                <div class="card-body">
                    <h6 class="text-warning mb-2">Reward Value</h6>
                    <h3 class="mb-0">$<?php echo number_format($metrics['total_reward_value'], 2); ?></h3>
                    <p class="text-muted small mb-0">Total value distributed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card bg-info bg-opacity-10">
                <div class="card-body">
                    <h6 class="text-info mb-2">Avg. Per User</h6>
                    <h3 class="mb-0">
                        $<?php 
                            echo number_format(
                                $metrics['total_users'] ? 
                                $metrics['total_reward_value'] / $metrics['total_users'] : 
                                0, 
                                2
                            ); 
                        ?>
                    </h3>
                    <p class="text-muted small mb-0">Average reward value</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4">
        <!-- User Growth Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">User Growth</h5>
                        <div class="btn-group">
                            <button type="button" 
                                    class="btn btn-sm btn-outline-secondary active time-range"
                                    data-range="6m">
                                6M
                            </button>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-secondary time-range"
                                    data-range="1y">
                                1Y
                            </button>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-secondary time-range"
                                    data-range="all">
                                All
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reward Distribution Chart -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="card-title mb-0">Reward Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="rewardDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Data -->
    <script>
        window.analyticsData = {
            userGrowth: <?php echo json_encode($user_growth); ?>,
            rewardDistribution: <?php echo json_encode($reward_distribution); ?>
        };
    </script>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

<!-- Include analytics scripts -->
<script src="/admin/companyeditor_components/analytics-scripts.js"></script>
