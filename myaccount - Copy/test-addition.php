<?php
/**
 * Test Addition Page - Add bonus allocations for testing
 * Development only
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/claudecode/enrollment_allocations/classes/class.allocationmanager.php');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login');
    exit;
}

// Only allow in development mode
if ($mode !== 'dev') {
    header('Location: /myaccount');
    exit;
}

// Get user data
$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

// Initialize AllocationManager
$allocationManager = new AllocationManager($database);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->formposted()) {
    // Use AllocationManager to grant bonus
    $result = $allocationManager->grantBonus($user_id, 2, 'Test bonus allocation (dev mode)', 'test');
    
    if ($result['success']) {
        // Redirect with success message
        header('Location: /myaccount/test-addition?success=1');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Get current allocation balance
$balance = $allocationManager->getUserBalance($user_id);

// Get allocation history for this user
$sql = "SELECT * FROM bg_user_allocations 
        WHERE user_id = :user_id 
        AND allocation_year = :year 
        AND allocation_type = 'bonus'
        AND reference_type = 'test'
        ORDER BY created_at DESC 
        LIMIT 10";

try {
    $test_allocations = $database->getrows($sql, [
        'user_id' => $user_id,
        'year' => date('Y')
    ]);
} catch (Exception $e) {
    $test_allocations = [];
}

// Get ALL allocations for debugging
$debug_sql = "SELECT allocation_type, SUM(amount) as total_amount, SUM(amount_used) as total_used, 
              SUM(amount - amount_used) as total_available
              FROM bg_user_allocations 
              WHERE user_id = :user_id 
              AND allocation_year = :year 
              AND status = 'active'
              GROUP BY allocation_type";

try {
    $debug_allocations = $database->getrows($debug_sql, [
        'user_id' => $user_id,
        'year' => date('Y')
    ]);
} catch (Exception $e) {
    $debug_allocations = [];
}

// Page setup
$pagetitle = 'Test Allocation Addition';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content">
    <h1>Test Allocation Addition</h1>
    <p class="text-muted">Development testing tool for enrollment allocations</p>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i> Successfully added +2 test bonus allocations!
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Current Allocation Balance</h5>
                    <p class="display-4 text-primary"><?php echo $balance['available_allocations']; ?></p>
                    <p class="text-muted">
                        Plan: <?php echo $balance['plan_allocations']; ?> | 
                        Used: <?php echo $balance['total_used']; ?>
                    </p>
                    
                    <form method="POST" action="/myaccount/test-addition">
                        <input type="hidden" name="csrf_token" value="<?php echo $session->get('csrf_token'); ?>">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-plus-circle"></i> Add +2 Test Allocations
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <p class="text-muted">
                        <i class="bi bi-info-circle"></i> Test allocations are added to the bg_user_allocations table as 'bonus' type with 'test' reference.
                    </p>
                    
                    <div class="mt-3">
                        <a href="/myaccount/earn-enrollments" class="btn btn-primary">View Earn Enrollments Page</a>
                        <a href="/claudecode/enrollment_allocations/enrollment-picker.php" class="btn btn-secondary">View Enrollment Picker</a>
                        <?php if ($mode === 'dev'): ?>
                        <a href="/myaccount/check-allocation-table" class="btn btn-outline-danger btn-sm mt-2">Check Table Structure</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($mode === 'dev'): ?>
                    <div class="mt-3">
                        <p class="text-muted small">Dev Mode: <?php echo $site; ?> | Mode: <?php echo $mode; ?></p>
                        
                        <!-- Debug info -->
                        <details class="mt-2">
                            <summary class="text-muted small">Debug: Allocation Details</summary>
                            <div class="small">
                                <?php if (!empty($debug_allocations)): ?>
                                    <?php foreach ($debug_allocations as $debug): ?>
                                        <div><?php echo $debug['allocation_type']; ?>: 
                                            Amount=<?php echo $debug['total_amount']; ?>, 
                                            Used=<?php echo $debug['total_used']; ?>, 
                                            Available=<?php echo $debug['total_available']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div>No allocations found in database</div>
                                <?php endif; ?>
                                <div class="mt-1">Balance Array: <pre><?php print_r($balance); ?></pre></div>
                            </div>
                        </details>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Test Allocations</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($test_allocations)): ?>
                        <p class="text-muted">No test allocations added yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Used</th>
                                        <th>Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($test_allocations as $alloc): ?>
                                    <tr>
                                        <td><?php echo date('M j, g:i A', strtotime($alloc['created_at'])); ?></td>
                                        <td>+<?php echo $alloc['amount']; ?></td>
                                        <td><?php echo $alloc['amount_used']; ?></td>
                                        <td><?php echo $alloc['amount_available'] ?? ($alloc['amount'] - $alloc['amount_used']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>