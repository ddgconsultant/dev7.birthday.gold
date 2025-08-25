<?php
/**
 * Test script for admin plan change functionality
 * This verifies that the set_accounttypeplan.php properly updates all plan-related data
 */

include('../core/site-controller.php');

// Check admin access
if (!$account->isadmin()) {
    die("Admin access required");
}

// Get test user ID from parameter
$test_user_id = $_GET['user_id'] ?? null;

if (!$test_user_id) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test Plan Change</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h2>Test Plan Change Functionality</h2>
            <form method="get" class="mt-4">
                <div class="mb-3">
                    <label for="user_id" class="form-label">Enter User ID to Test:</label>
                    <input type="number" class="form-control" id="user_id" name="user_id" required>
                </div>
                <button type="submit" class="btn btn-primary">Load User</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Get user data
$user_data = $account->getuserdata($test_user_id, 'user_id');
if (!$user_data) {
    die("User not found");
}

// Get current plan details
$current_product_id = $user_data['account_product_id'] ?? null;
$current_plan_details = null;
if ($current_product_id) {
    $plan_sql = "SELECT p.*, 
                (SELECT GROUP_CONCAT(CONCAT(name, ':', value) SEPARATOR ', ') 
                 FROM bg_product_features 
                 WHERE product_id = p.id AND status = 'active') as features
                FROM bg_products p 
                WHERE p.id = :product_id";
    $current_plan_details = $database->getrow($plan_sql, ['product_id' => $current_product_id]);
}

// Get allocations
$allocation_sql = "SELECT * FROM bg_user_allocations 
                  WHERE user_id = :user_id 
                  AND allocation_year = :year 
                  ORDER BY allocation_type, create_dt DESC";
$allocations = $database->getrows($allocation_sql, [
    'user_id' => $test_user_id,
    'year' => date('Y')
]);

// Get plan attributes
$attributes_sql = "SELECT * FROM bg_user_attributes 
                  WHERE user_id = :user_id 
                  AND type IN ('plan', 'plan_change') 
                  AND status = 'active' 
                  ORDER BY create_dt DESC 
                  LIMIT 10";
$plan_attributes = $database->getrows($attributes_sql, ['user_id' => $test_user_id]);

// Get available plans for testing
$plans_sql = "SELECT p.id, p.account_name, p.account_type, p.account_plan, p.version
             FROM bg_products p
             WHERE p.status = 'active'
             AND p.version = :version
             ORDER BY p.account_type, p.id";
$available_plans = $database->getrows($plans_sql, ['version' => $website['plan_version'] ?? 'v7']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Plan Change - User <?php echo $test_user_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .data-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        pre {
            background: white;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Test Plan Change for User: <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?> (ID: <?php echo $test_user_id; ?>)</h2>
        
        <div class="data-section">
            <h4>Current User Data</h4>
            <table class="table table-sm">
                <tr><th>Account Type:</th><td><?php echo $user_data['account_type']; ?></td></tr>
                <tr><th>Account Plan:</th><td><?php echo $user_data['account_plan']; ?></td></tr>
                <tr><th>Product ID:</th><td><?php echo $user_data['account_product_id'] ?? 'Not set'; ?></td></tr>
                <tr><th>Status:</th><td><?php echo $user_data['status']; ?></td></tr>
            </table>
            
            <?php if ($current_plan_details): ?>
            <h5>Current Plan Details</h5>
            <pre><?php echo htmlspecialchars(json_encode($current_plan_details, JSON_PRETTY_PRINT)); ?></pre>
            <?php endif; ?>
        </div>
        
        <div class="data-section">
            <h4>Current Allocations (<?php echo date('Y'); ?>)</h4>
            <?php if ($allocations): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Used</th>
                            <th>Available</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allocations as $alloc): ?>
                        <tr>
                            <td><?php echo $alloc['allocation_type']; ?></td>
                            <td><?php echo $alloc['amount']; ?></td>
                            <td><?php echo $alloc['amount_used'] ?? 0; ?></td>
                            <td><?php echo ($alloc['amount'] - ($alloc['amount_used'] ?? 0)); ?></td>
                            <td><?php echo htmlspecialchars($alloc['allocation_comment'] ?? ''); ?></td>
                            <td><?php echo $alloc['status']; ?></td>
                            <td><?php echo $alloc['create_dt']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No allocations found for this year</p>
            <?php endif; ?>
        </div>
        
        <div class="data-section">
            <h4>Plan Attributes</h4>
            <?php if ($plan_attributes): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Value</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plan_attributes as $attr): ?>
                        <tr>
                            <td><?php echo $attr['type']; ?></td>
                            <td><?php echo $attr['name']; ?></td>
                            <td><?php echo htmlspecialchars(substr($attr['string_value'] ?? '', 0, 100)); ?></td>
                            <td><?php echo $attr['create_dt']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No plan attributes found</p>
            <?php endif; ?>
        </div>
        
        <div class="data-section">
            <h4>Test Plan Change</h4>
            <form action="user_components/set_accounttypeplan.php" method="post">
                <input type="hidden" name="uid" value="<?php echo $test_user_id; ?>">
                
                <div class="mb-3">
                    <label for="product_id" class="form-label">Select New Plan:</label>
                    <select name="product_id" id="product_id" class="form-control" required>
                        <option value="">-- Select Plan --</option>
                        <?php foreach ($available_plans as $plan): ?>
                            <option value="<?php echo $plan['id']; ?>" 
                                    <?php echo ($plan['id'] == $current_product_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($plan['account_name']); ?> 
                                (<?php echo $plan['account_type']; ?>/<?php echo $plan['account_plan']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="accountstatus" class="form-label">Account Status:</label>
                    <select name="accountstatus" id="accountstatus" class="form-control">
                        <option value="active" <?php echo ($user_data['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($user_data['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo ($user_data['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="send_mail" name="send_mail" value="1">
                    <label class="form-check-label" for="send_mail">
                        Send notification email
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Plan</button>
                <a href="?user_id=<?php echo $test_user_id; ?>" class="btn btn-secondary">Refresh Data</a>
            </form>
        </div>
        
        <div class="mt-3">
            <a href="user-details-v2.php?u=<?php echo urlencode(base64_encode($test_user_id)); ?>" class="btn btn-info">
                View in User Details Page
            </a>
        </div>
    </div>
</body>
</html>