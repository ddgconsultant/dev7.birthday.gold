<?php
/**
 * Earn More Enrollments Page V2
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

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

// Get plan data with safe defaults
$plan_allocations = 10; // Default
try {
    $plandatafeatures = $app->plandetail('details_id', $current_user_data['account_product_id']);
    if (isset($plandatafeatures['max_business_select'])) {
        $plan_allocations = $plandatafeatures['max_business_select'];
    }
} catch (Exception $e) {
    // Use default
}

// Calculate used allocations
$used_allocations = 0;
try {
    $sql = "SELECT COUNT(*) as count FROM bg_user_companies WHERE user_id = :user_id AND YEAR(create_dt) = :year AND status NOT IN ('failed', 'removed')";
    $result = $database->getrow($sql, ['user_id' => $user_id, 'year' => date('Y')]);
    if ($result && isset($result['count'])) {
        $used_allocations = $result['count'];
    }
} catch (Exception $e) {
    // Use 0
}

// Get test bonus from session
$test_bonus = $session->get('test_bonus_allocations', 0);

// Calculate balance
$total_allocations = $plan_allocations + $test_bonus;
$available_allocations = max(0, $total_allocations - $used_allocations);

// Page setup
$pagetitle = 'Earn More Enrollments';
$additionalstyles = '<style>
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
.earn-method-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 2px solid #f0f0f0;
}
</style>';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="earn-header">
    <div class="container">
        <h1 class="mb-3">Earn More Enrollments</h1>
        <p class="lead mb-0">Discover ways to get more enrollment allocations</p>
    </div>
</div>

<div class="container main-content">
    <!-- Current Balance -->
    <div class="balance-card">
        <p class="balance-label">Your Current Balance</p>
        <h2 class="balance-number"><?php echo $available_allocations; ?></h2>
        <p class="balance-label">enrollment allocations</p>
        
        <?php if ($test_bonus > 0): ?>
        <p class="text-info mt-2">
            <i class="bi bi-gift"></i> Includes <?php echo $test_bonus; ?> test bonus allocations
        </p>
        <?php endif; ?>
        
        <!-- Test Button for Development -->
        <?php if ($app->isdevelopment()): ?>
        <div class="mt-3">
            <form method="POST" action="/myaccount/ajax-test-bonus.php" id="testBonusForm">
                <input type="hidden" name="csrf_token" value="<?php echo $session->get('csrf_token'); ?>">
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="bi bi-bug"></i> Add +2 Test Bonus
                </button>
            </form>
            <small class="text-muted d-block mt-1">Dev only: Test bonus allocation feature</small>
        </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <h2 class="mb-4">Ways to Earn Allocations</h2>
            
            <!-- Placeholder content -->
            <div class="earn-method-card">
                <h4>Welcome Bonus</h4>
                <p>Get 5 bonus enrollments when you sign up</p>
            </div>
            
            <div class="earn-method-card">
                <h4>Profile Completion</h4>
                <p>Complete your profile to earn 3 bonus enrollments</p>
            </div>
            
            <div class="earn-method-card">
                <h4>Refer a Friend</h4>
                <p>Earn 2 enrollments for each friend who signs up</p>
            </div>
        </div>
        
        <div class="col-lg-4">
            <h3 class="h5 mb-3">Quick Stats</h3>
            <div class="card">
                <div class="card-body">
                    <p>Plan Allocations: <?php echo $plan_allocations; ?></p>
                    <p>Bonus Allocations: <?php echo $test_bonus; ?></p>
                    <p>Used This Year: <?php echo $used_allocations; ?></p>
                    <p>Available: <?php echo $available_allocations; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($app->isdevelopment()): ?>
<script>
document.getElementById('testBonusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const btn = form.querySelector('button');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
    
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test bonus added! Reloading page...');
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to add bonus'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-bug"></i> Add +2 Test Bonus';
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-bug"></i> Add +2 Test Bonus';
    });
});
</script>
<?php endif; ?>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>