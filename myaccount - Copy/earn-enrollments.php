<?php
/**
 * Earn More Enrollments Page
 * Shows users how they can earn more enrollment allocations
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/claudecode/enrollment_allocations/classes/class.allocationmanager.php');

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

// Get user's current allocation balance
$balance = $allocationManager->getUserBalance($user_id);

// Page setup
$pagetitle = 'Earn More Enrollments';
$additionalstyles = '
<style>
.earn-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
}

.balance-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    margin-bottom: 2rem;
}

.balance-number {
    font-size: 3rem;
    font-weight: 700;
    color: #667eea;
    margin: 0;
}

.balance-label {
    color: #6c757d;
    font-size: 1.1rem;
}

.earn-method-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 2px solid #f0f0f0;
    transition: all 0.3s ease;
}

.earn-method-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.allocation-amount {
    font-size: 2rem;
    font-weight: 700;
    color: #28a745;
}

.earn-icon {
    width: 60px;
    height: 60px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}
</style>
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="earn-header">
    <div class="container">
        <h1 class="mb-3">Earn More Enrollments</h1>
        <p class="lead mb-0">Discover ways to get more enrollment allocations for your favorite birthday rewards</p>
    </div>
</div>

<div class="container main-content">
    <!-- Current Balance -->
    <div class="balance-card">
        <p class="balance-label">Your Current Balance</p>
        <h2 class="balance-number"><?php echo $balance['available_allocations']; ?></h2>
        <p class="balance-label">enrollment allocations</p>
    </div>

    <!-- Ways to Earn -->
    <div class="row">
        <div class="col-lg-8">
            <h2 class="mb-4">Ways to Earn Allocations</h2>
            
            <!-- Bonus Allocations -->
            <div class="mb-5">
                <h3 class="h5 mb-3">🎁 Bonus Allocations</h3>
                
                <div class="earn-method-card">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="earn-icon">🎉</div>
                        </div>
                        <div class="col">
                            <h4 class="h5 mb-1">Welcome Bonus</h4>
                            <p class="text-muted mb-2">Get 5 bonus enrollments when you sign up</p>
                            <span class="badge bg-success">Available</span>
                        </div>
                        <div class="col-auto text-end">
                            <div class="allocation-amount">+5</div>
                            <p class="text-muted mb-0">allocations</p>
                        </div>
                    </div>
                </div>
                
                <div class="earn-method-card">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="earn-icon">🏆</div>
                        </div>
                        <div class="col">
                            <h4 class="h5 mb-1">Profile Completion</h4>
                            <p class="text-muted mb-2">Complete your profile to earn 3 bonus enrollments</p>
                            <span class="badge bg-success">Available</span>
                        </div>
                        <div class="col-auto text-end">
                            <div class="allocation-amount">+3</div>
                            <p class="text-muted mb-0">allocations</p>
                        </div>
                    </div>
                </div>
                
                <div class="earn-method-card">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="earn-icon">👥</div>
                        </div>
                        <div class="col">
                            <h4 class="h5 mb-1">Refer a Friend</h4>
                            <p class="text-muted mb-2">Earn 2 enrollments for each friend who signs up</p>
                            <span class="badge bg-success">Available</span>
                        </div>
                        <div class="col-auto text-end">
                            <div class="allocation-amount">+2</div>
                            <p class="text-muted mb-0">allocations</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Options -->
            <div class="mb-5">
                <h3 class="h5 mb-3">💳 Upgrade Your Plan</h3>
                <p class="text-muted mb-3">Get more allocations with our premium plans</p>
                
                <div class="earn-method-card">
                    <h4 class="h5 mb-1">Premium Plan</h4>
                    <p class="text-muted mb-2">Unlock 25 annual enrollments</p>
                    <p class="h4 mb-0">$49.99/year</p>
                    <a href="/myaccount/subscription" class="btn btn-primary mt-3">View Plans</a>
                </div>
            </div>

            <!-- Special Offers -->
            <div class="mb-5">
                <h3 class="h5 mb-3">⭐ Special Offers</h3>
                <div class="earn-method-card">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="earn-icon">📧</div>
                        </div>
                        <div class="col">
                            <h4 class="h5 mb-1">Subscribe to Newsletter</h4>
                            <p class="text-muted mb-0">Get 5 bonus allocations when you subscribe to our weekly newsletter</p>
                        </div>
                        <div class="col-auto">
                            <a href="/myaccount/preferences" class="btn btn-outline-primary">Subscribe</a>
                        </div>
                    </div>
                </div>
                
                <div class="earn-method-card">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="earn-icon">📱</div>
                        </div>
                        <div class="col">
                            <h4 class="h5 mb-1">Download Mobile App</h4>
                            <p class="text-muted mb-0">Get 10 bonus allocations when you download our mobile app</p>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-primary" disabled>Coming Soon</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Side Stats -->
        <div class="col-lg-4">
            <h3 class="h5 mb-3">Your Allocation Stats</h3>
            <div class="card">
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Current Balance:</strong> 
                            <span class="text-primary"><?php echo $balance['available_allocations']; ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Plan Allocations:</strong> 
                            <span><?php echo $balance['plan_allocations'] ?? 10; ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Used This Year:</strong> 
                            <span><?php echo $balance['total_used'] ?? 0; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-3 text-center">
                <a href="/myaccount/allocation-history" class="btn btn-sm btn-outline-secondary">View Full History</a>
            </div>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>