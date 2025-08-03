<?php
/**
 * Debug Allocations - See what's actually in the database
 * Development only
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.allocationmanager.php');

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
$year = date('Y');

// Initialize AllocationManager
$allocationManager = new AllocationManager($database);

// Get raw allocation data
$sql = "SELECT * FROM bg_user_allocations WHERE user_id = :user_id AND allocation_year = :year ORDER BY created_at DESC";
$allocations = $database->getrows($sql, ['user_id' => $user_id, 'year' => $year]);

// Get the summary query result
$summary_sql = "SELECT 
                    SUM(CASE WHEN status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) THEN (amount - COALESCE(amount_used, 0)) ELSE 0 END) as total_available,
                    SUM(CASE WHEN allocation_type = 'plan' THEN amount ELSE 0 END) as plan_allocations,
                    SUM(CASE WHEN allocation_type = 'bonus' THEN amount ELSE 0 END) as bonus_allocations,
                    SUM(amount) as total_allocated,
                    SUM(COALESCE(amount_used, 0)) as total_used_from_allocations
                FROM bg_user_allocations
                WHERE user_id = :user_id
                AND allocation_year = :year
                AND status IN ('active', 'depleted')";
$summary = $database->getrow($summary_sql, ['user_id' => $user_id, 'year' => $year]);

// Get balance from AllocationManager
$balance = $allocationManager->getUserBalance($user_id);

// Get enrollments count
$enrollment_sql = "SELECT COUNT(*) as count FROM bg_user_companies WHERE user_id = :user_id AND YEAR(create_dt) = :year AND status NOT IN ('failed', 'removed')";
$enrollment_count = $database->getrow($enrollment_sql, ['user_id' => $user_id, 'year' => $year]);

$pagetitle = 'Debug Allocations';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content">
    <h1>Debug Allocations for User <?php echo $user_id; ?></h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Raw Allocations in Database</div>
                <div class="card-body">
                    <?php if (empty($allocations)): ?>
                        <p>No allocations found</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Used</th>
                                    <th>Available</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allocations as $alloc): ?>
                                <tr>
                                    <td><?php echo $alloc['allocation_id']; ?></td>
                                    <td><?php echo $alloc['allocation_type']; ?></td>
                                    <td><?php echo $alloc['amount']; ?></td>
                                    <td><?php echo $alloc['amount_used']; ?></td>
                                    <td><?php echo $alloc['amount'] - $alloc['amount_used']; ?></td>
                                    <td><?php echo $alloc['status']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($alloc['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Summary Query Result</div>
                <div class="card-body">
                    <pre><?php print_r($summary); ?></pre>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header">AllocationManager Balance</div>
                <div class="card-body">
                    <pre><?php print_r($balance); ?></pre>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header">Enrollments This Year</div>
                <div class="card-body">
                    <p>Count: <?php echo $enrollment_count['count']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="/myaccount/test-addition" class="btn btn-primary">Back to Test Addition</a>
        <button class="btn btn-warning" onclick="if(confirm('Create plan allocation?')) { window.location.href='?create_plan=1'; }">Create Plan Allocation</button>
    </div>
</div>

<?php
// Handle creating plan allocation
if (isset($_GET['create_plan'])) {
    $result = $allocationManager->ensurePlanAllocation($user_id, $year);
    echo '<script>alert("Plan allocation created!"); window.location.href="/myaccount/debug-allocations";</script>';
}

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>