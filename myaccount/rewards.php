<?php
/**
 * My Rewards Dashboard
 * Shows enrollment allocation counts, plan info, and links to key features
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
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
$user_id = $current_user_data['user_id'];

// Initialize AllocationManager
$allocationManager = new AllocationManager($database);

// Get user's allocation balance
$balance = $allocationManager->getUserBalance($user_id);

// Get user's plan details
$plandetails = $app->plandetail('details_id', $current_user_data['account_product_id']);

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
$pagetitle = 'My Rewards Dashboard';
$bodycontentclass = '';
$additionalstyles = '
<style>
/* Dashboard Styles */
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-radius: 0 0 1rem 1rem;
}

.dashboard-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 1.5rem;
    height: 100%;
    transition: transform 0.2s, box-shadow 0.2s;
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
    padding: 2rem;
}

.action-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

.action-card .btn {
    margin-top: 1rem;
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

/* Mobile optimization */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem 0;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .dashboard-card {
        margin-bottom: 1rem;
    }
}
</style>
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="dashboard-header">
    <div class="container">
        <h1 class="mb-2">My Rewards Dashboard</h1>
        <p class="lead mb-0">Welcome back, <?php echo htmlspecialchars($current_user_data['first_name'] ?? $current_user_data['username'] ?? 'Member'); ?>!</p>
        <?php if ($current_user_data['account_plan'] !== 'free'): ?>
        <div class="plan-badge">
            <i class="bi bi-star-fill me-1"></i><?php echo ucfirst($current_user_data['account_plan']); ?> Plan
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="container main-content">
    <!-- Main Stats Row -->
    <div class="row mb-4">
        <!-- Enrollment Allocations -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="dashboard-card">
                <h5 class="mb-3">Enrollment Allocations</h5>
                <div class="d-flex justify-content-between align-items-baseline">
                    <div>
                        <p class="stat-number"><?php echo $balance['available_allocations']; ?></p>
                        <p class="stat-label">Available</p>
                    </div>
                    <div class="text-end">
                        <p class="text-muted mb-0">of <?php echo $plandetails['max_business_select'] ?? 10; ?> total</p>
                        <p class="text-muted mb-0"><small>Used: <?php echo $balance['total_used']; ?></small></p>
                    </div>
                </div>
                <div class="allocation-meter">
                    <?php 
                    $percentage = ($plandetails['max_business_select'] > 0) 
                        ? (($balance['total_used'] / $plandetails['max_business_select']) * 100) 
                        : 0;
                    ?>
                    <div class="allocation-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                </div>
                <?php if ($balance['expiring_soon_count'] > 0): ?>
                <p class="text-warning mt-2 mb-0">
                    <i class="bi bi-clock-history"></i> <?php echo $balance['expiring_soon_count']; ?> expiring soon
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Rewards -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="dashboard-card">
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
            <div class="dashboard-card">
                <h5 class="mb-3">Total Enrollments</h5>
                <p class="stat-number"><?php echo $total_enrollments; ?></p>
                <p class="stat-label">Birthday programs joined</p>
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
                <h5>Add More Rewards</h5>
                <p class="text-muted">Browse and enroll in more birthday reward programs</p>
                <a href="/myaccount/enrollment-picker" class="btn btn-primary">
                    Browse Programs
                </a>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="dashboard-card action-card">
                <i class="bi bi-gift text-success action-icon"></i>
                <h5>Redeem Rewards</h5>
                <p class="text-muted">View and redeem your active birthday rewards</p>
                <a href="/myaccount/redeem" class="btn btn-success">
                    Redeem Now
                </a>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="dashboard-card action-card">
                <i class="bi bi-star text-warning action-icon"></i>
                <h5>Earn More</h5>
                <p class="text-muted">Discover ways to earn more enrollment allocations</p>
                <a href="/myaccount/earn-enrollments" class="btn btn-warning">
                    Earn Allocations
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($active_rewards)): ?>
    <!-- Recent Rewards Preview -->
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
    <?php endif; ?>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>