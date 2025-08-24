<?php
$addClasses[]='allocationmanager';

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
/*
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.allocationmanager.php');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login');
    exit;
}
    

// Get user data
$current_user_data = $session->get('current_user_data');
$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');

// Initialize allocationmanager
$allocationmanager = new allocationmanager($database);
*/

$user_id = $current_user_data['user_id'];

// Get user's allocation balance
$balance = $allocationmanager->getUserBalance($user_id);

// Get user's plan details
$plandetails = $app->plandetail('details_id', $current_user_data['account_product_id']);

// Get the most recent plan allocation to determine next cycle date
$sql = "SELECT created_at, expires_at FROM bg_user_allocations 
        WHERE user_id = :user_id 
        AND allocation_type = 'plan' 
        ORDER BY created_at DESC 
        LIMIT 1";
$lastPlanAllocation = $database->getrow($sql, ['user_id' => $user_id]);

// Get account stats
$accountstats = $account->account_getstats();

// Get active rewards for display
$active_rewards = $account->getbusinesslist_rewards($current_user_data, 'card', '"success", "success-btn"', 5, true);
$active_rewards = is_array($active_rewards) ? $active_rewards : [];
$total_rewards_count = count($active_rewards);

// Get recent enrollments
$sql = "SELECT COUNT(*) as count FROM bg_user_companies 
        WHERE user_id = :user_id 
        AND status IN ('success', 'success-btn', 'pending')";
$enrollment_result = $database->getrow($sql, ['user_id' => $user_id]);
$total_enrollments = $enrollment_result['count'] ?? 0;

// Page setup
$pagetitle = 'Rewards Dashboard';
$bodycontentclass = '';

// Add vendor CSS that was in header3.inc
$additionalstyles = '<script src="/public/assets/js/config.js"></script>';
$additionalstyles .= '<script src="/public/assets/vendors/simplebar/simplebar.min.js"></script>';
$additionalstyles .= '<link href="/public/assets/vendors/swiper/swiper-bundle.min.css" rel="stylesheet">';


$additionalstyles .= '
<style>
/* Dashboard Styles */
.dashboard-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 1.5rem;
    height: 100%;
    transition: transform 0.2s, box-shadow 0.2s;
}

/* Larger cards for main stats */
.stats-card {
    padding: 2rem;
    min-height: 280px;
}

.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    margin: 0;
    line-height: 1.2;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0.25rem 0 0 0;
}

.allocation-meter {
    background: #f0f0f0;
    border-radius: 1rem;
    height: 1rem;
    overflow: hidden;
    margin: 1rem 0;
}

.allocation-fill {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    height: 100%;
    transition: width 0.5s ease;
}

.action-card {
    text-align: center;
    padding: 1.25rem;
    min-height: auto;
}

.action-icon {
    font-size: 2rem;
    margin-bottom: 0.75rem;
    display: block;
}

.action-card h5 {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.action-card p {
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
}

.action-card .btn {
    margin-top: 0.5rem;
    padding: 0.375rem 1rem;
}

.plan-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: rgba(255,255,255,0.2);
    border-radius: 2rem;
    font-weight: 600;
    margin-top: 0.5rem;
}

.rewards-preview {
    margin-top: 2rem;
}

.reward-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
    margin-bottom: 0.75rem;
    transition: background 0.2s;
}

.reward-item:hover {
    background: #e9ecef;
}

.reward-logo {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 0.5rem;
    margin-right: 1rem;
}

.reward-info h6 {
    margin: 0;
    font-weight: 600;
}

.reward-info p {
    margin: 0;
    font-size: 0.875rem;
    color: #6c757d;
}

/* Picks Breakdown Styling */
.picks-breakdown {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.picks-breakdown .stat-number {
    margin: 0;
    line-height: 1;
}

/* Mobile optimization */
@media (max-width: 768px) {
    .stat-number {
        font-size: 2rem;
    }
    
    .dashboard-card {
        margin-bottom: 1rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="page-wrapper">
<div class="content-header-dark">
    <div class="container">
        <h1>Rewards Dashboard</h1>
        <p class="lead">Manage picks, track enrollments, and redeem birthday rewards</p>
    </div>
</div>

<div class="container mt-4">
    <!-- Main Stats Row -->
    <div class="row mb-4">
        <!-- Picks -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="dashboard-card stats-card">
                <?php 
                // Calculate days until next cycle
                $today = new DateTime();
                $daysUntilReset = 30; // Default
                
                if (!empty($lastPlanAllocation['expires_at'])) {
                    // If we have expiry date, use that
                    $expiryDate = new DateTime($lastPlanAllocation['expires_at']);
                    $daysUntilReset = $today->diff($expiryDate)->days;
                } elseif (!empty($lastPlanAllocation['created_at'])) {
                    // Otherwise calculate based on creation date + 1 year
                    $createdDate = new DateTime($lastPlanAllocation['created_at']);
                    $nextCycle = clone $createdDate;
                    $nextCycle->modify('+1 year');
                    $daysUntilReset = $today->diff($nextCycle)->days;
                }
                
                // Get actual allocation data from database
                $planPicks = $balance['plan_allocations'] ?? 0;
                $bonusPicks = $balance['bonus_allocations'] ?? 0;
                $usedPicks = $balance['total_used'] ?? 0;
                $remainingPicks = $balance['available_allocations'] ?? 0;
                ?>
                <h5 class="mb-3">✅ Picks</h5>
                
                <!-- Picks Breakdown -->
                <div class="picks-breakdown">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">
                            Plan Picks
                            <i class="bi bi-info-circle text-muted ms-1" 
                               data-bs-toggle="tooltip" 
                               data-bs-placement="top" 
                               title="<?php echo $daysUntilReset; ?> days until new picks assigned"></i>
                        </span>
                        <span class="text-success fw-semibold">+<?php echo $planPicks; ?></span>
                    </div>
                    <?php if ($bonusPicks > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Bonus Picks</span>
                        <span class="text-success fw-semibold">+<?php echo $bonusPicks; ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Used Picks</span>
                        <span class="text-danger fw-semibold">-<?php echo $usedPicks; ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Remaining Picks</span>
                        <span class="stat-number text-primary" style="font-size: 1.5rem;"><?php echo $remainingPicks; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Rewards -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="dashboard-card stats-card">
                <h5 class="mb-3">Active Rewards</h5>
                <p class="stat-number"><?php echo $total_rewards_count; ?></p>
                <p class="stat-label">Ready to redeem</p>
                <?php if ($total_rewards_count > 0): ?>
                <a href="/myaccount/redeem" class="btn btn-sm btn-outline-primary mt-3">
                    <i class="bi bi-gift me-1"></i>View Rewards
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Total Enrollments -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="dashboard-card stats-card">
                <h5 class="mb-3">Enrollments</h5>
                <p class="stat-number"><?php echo $total_enrollments; ?></p>
                <p class="stat-label">Programs joined with picks</p>
                <a href="/myaccount/enrollment-history" class="btn btn-sm btn-outline-secondary mt-3">
                    <i class="bi bi-clock-history me-1"></i>View History
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="dashboard-card action-card">
                <i class="bi bi-plus-circle text-primary action-icon"></i>
                <h5>Use Picks</h5>
                <p class="text-muted">Browse programs and use picks to get enrollments</p>
                <a href="/myaccount/enrollment-picker" class="btn btn-primary">
                    Browse Programs
                </a>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="dashboard-card action-card">
                <i class="bi bi-gift text-success action-icon"></i>
                <h5>Redeem Rewards</h5>
                <p class="text-muted">View and redeem active birthday rewards</p>
                <a href="/myaccount/redeem" class="btn btn-success">
                    Redeem Now
                </a>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="dashboard-card action-card">
                <i class="bi bi-star text-warning action-icon"></i>
                <h5>Earn More</h5>
                <p class="text-muted">Discover ways to earn more picks</p>
                <a href="/myaccount/earn-enrollments" class="btn btn-warning">
                    Earn Picks
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($active_rewards)): ?>
    <!-- Recent Rewards Preview -->
    <div class="row">
        <div class="col-12">
            <div class="rewards-preview">
                <h4 class="mb-3">Recent Rewards</h4>
                <div class="dashboard-card">
            <?php 
            $preview_count = 0;
            foreach ($active_rewards as $reward): 
                if ($preview_count >= 5) break;
                $preview_count++;
                
                // Get company image
                $company_image = '';
                if (!empty($reward['company_logo'])) {
                    $company_image = $display->companyimage($reward['company_id'] . '/' . $reward['company_logo']);
                }
            ?>
            <div class="reward-item">
                <?php if ($company_image): ?>
                <img src="<?php echo $company_image; ?>" alt="<?php echo htmlspecialchars($reward['company_name']); ?>" class="reward-logo">
                <?php else: ?>
                <div class="reward-logo bg-light d-flex align-items-center justify-content-center">
                    <i class="bi bi-building text-muted"></i>
                </div>
                <?php endif; ?>
                
                <div class="reward-info flex-grow-1">
                    <h6><?php echo htmlspecialchars($reward['company_name']); ?></h6>
                    <p><?php echo htmlspecialchars($reward['spinner_description'] ?? 'Birthday reward available'); ?></p>
                </div>
                
                <a href="/myaccount/redeem-details?id=<?php echo $qik->encodeId($reward['reward_id']); ?>" class="btn btn-sm btn-outline-primary">
                    View
                </a>
            </div>
            <?php endforeach; ?>
            
            <?php if ($total_rewards_count > 5): ?>
            <div class="text-center mt-3">
                <a href="/myaccount/redeem-list" class="btn btn-outline-secondary">
                    View All <?php echo $total_rewards_count; ?> Rewards
                </a>
            </div>
            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

</div><!-- end page-wrapper -->


<?php
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();