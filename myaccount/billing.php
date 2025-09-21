<?php
/**
 * Billing History Page
 * Shows user's payment history and subscription status
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');

// Check if user is logged in
if (!$account->isactive()) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Require selective 2FA for billing access on trusted devices
$account->requireSelectiveTwoFactor();

// Page configuration
$pagedata = [
    'pagetitle' => 'Billing & Payment History',
    'activepage' => 'account'
];

// Add v7 theme CSS for content-header-dark class
$additionalstyles = '<link href="/public/css/v7/bg_theme.css" rel="stylesheet">';

// Use the current_user_data that's already properly set up in site-controller.php
// This ensures we're using the correct user data
$currentUserId = $current_user_data['user_id'];

// Get additional product info for the user's plan
// First try to match by product_id (most accurate), then fall back to account_plan
if (!empty($current_user_data['account_product_id'])) {
    $sql = "SELECT p.account_name as plan_name, p.price as plan_price
            FROM bg_products p
            WHERE p.id = :product_id
            AND p.status = 'active'
            LIMIT 1";

    $productData = $database->getrow($sql, ['product_id' => $current_user_data['account_product_id']]);
} else {
    // Fallback to account_plan matching if no product_id
    $sql = "SELECT p.account_name as plan_name, p.price as plan_price
            FROM bg_products p
            WHERE p.account_plan = :account_plan
            AND p.status = 'active'
            LIMIT 1";

    $productData = $database->getrow($sql, ['account_plan' => $current_user_data['account_plan']]);
}

// Debug output (uncomment to see what is happening)
/*
echo "<!-- DEBUG INFO:
Current User ID: " . $currentUserId . "
Session User ID: " . ($currentUserData['user_id'] ?? 'not set') . "
DB User ID: " . ($userData['user_id'] ?? 'not found') . "
DB Username: " . ($userData['username'] ?? 'not found') . "
DB Create Date: " . ($userData['create_dt'] ?? 'not found') . "
Session Create Date: " . ($currentUserData['create_dt'] ?? 'not set') . "
-->";
*/

// Get payment history
$sql = "SELECT * FROM bg_payments 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 50";

$payments = $database->getrows($sql, ['user_id' => $currentUserId]);

// Get current subscription status if using Stripe
$activeSubscription = null;
if (!empty($current_user_data['stripe_subscription_id'])) {
    $sql = "SELECT * FROM bg_subscriptions 
            WHERE user_id = :user_id 
            AND status IN ('active', 'trialing')
            ORDER BY created_at DESC 
            LIMIT 1";
    
    $activeSubscription = $database->getrow($sql, ['user_id' => $currentUserId]);
}

// Calculate next billing date if on subscription
$nextBillingDate = null;
if ($activeSubscription) {
    // Simple calculation - add 1 year to last payment date
    $lastPaymentDate = null;
    foreach ($payments as $payment) {
        if ($payment['payment_type'] === 'subscription' && $payment['status'] === 'completed') {
            $lastPaymentDate = $payment['created_at'];
            break;
        }
    }
    
    if ($lastPaymentDate) {
        $nextBillingDate = date('Y-m-d', strtotime($lastPaymentDate . ' +1 year'));
    }
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Dark Header Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>Billing & Payment History</h1>
        <p class="lead">Manage your subscription and view payment details</p>
    </div>
</div>

<style>
.billing-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem;
    margin-top: -1rem; /* Reduce gap after dark header */
}

.plan-status-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
}

.plan-status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.plan-badge {
    display: inline-block;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 1.1rem;
}

.plan-badge.free {
    background: #e9ecef;
    color: #495057;
}

.plan-badge.gold {
    background: #ffc107;
    color: #000;
}

.plan-badge.lifetime {
    background: #6f42c1;
    color: white;
}

.plan-badge.family {
    background: #20c997;
    color: white;
}

.billing-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.billing-info-item {
    border-left: 4px solid #0d6efd;
    padding-left: 1rem;
}

.billing-info-item h6 {
    color: #6c757d;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.billing-history-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.billing-history-header {
    background: #f8f9fa;
    padding: 1.5rem;
    border-bottom: 1px solid #dee2e6;
}

.payment-table {
    width: 100%;
}

.payment-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.payment-table td {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.payment-status {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 500;
}

.payment-status.completed {
    background: #d4edda;
    color: #155724;
}

.payment-status.pending {
    background: #fff3cd;
    color: #856404;
}

.payment-status.failed {
    background: #f8d7da;
    color: #721c24;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
</style>

<div class="billing-container">
    <?php if (isset($_GET['debug'])): ?>
    <div class="alert alert-info">
        <h5>Debug Information</h5>
        <pre><?php
        echo "User ID: " . $currentUserId . "\n";
        echo "Username: " . ($current_user_data['username'] ?? 'not found') . "\n";
        echo "Email: " . ($current_user_data['email'] ?? 'not found') . "\n";
        echo "Create Date: " . ($current_user_data['create_dt'] ?? 'not found') . "\n";
        echo "Account Plan: " . ($current_user_data['account_plan'] ?? 'not found') . "\n";
        echo "Account Type: " . ($current_user_data['account_type'] ?? 'not found') . "\n";
        echo "Plan Name: " . ($productData['plan_name'] ?? 'not found') . "\n";
        echo "Plan Price: $" . number_format(($productData['plan_price'] ?? 0) / 100, 2) . "\n";
        ?></pre>
    </div>
    <?php endif; ?>
    
    <!-- Current Plan Status -->
    <div class="plan-status-card">
        <div class="plan-status-header">
            <div>
                <h3>Current Plan</h3>
                <h2 class="mb-3">
                    <?php 
                    // Better plan name logic with proper fallbacks
                    $planName = $productData['plan_name'] ?? null;

                    // If no plan name from product, create a user-friendly name from account_plan
                    if (!$planName) {
                        $account_plan = $current_user_data['account_plan'] ?? '';
                        switch ($account_plan) {
                            case 'family_free':
                                $planName = 'Family Free';
                                break;
                            case 'family_gold':
                                $planName = 'Family Gold';
                                break;
                            case 'user_free':
                                $planName = 'Free Plan';
                                break;
                            case 'user_gold':
                                $planName = 'Gold Plan';
                                break;
                            case 'gold':
                                $planName = 'Gold Plan';
                                break;
                            case 'free':
                                $planName = 'Free Plan';
                                break;
                            case 'life':
                                $planName = 'Lifetime Plan';
                                break;
                            default:
                                $planName = ucfirst(str_replace('_', ' ', $account_plan)) ?: 'Unknown Plan';
                        }
                    }
                    echo htmlspecialchars($planName);
                    ?>
                </h2>
                <?php
                $planClass = 'free';
                if (strpos($current_user_data['account_plan'] ?? '', 'gold') !== false) {
                    $planClass = 'gold';
                } elseif (strpos($current_user_data['account_plan'] ?? '', 'life') !== false || strpos($current_user_data['account_plan'] ?? '', 'lifetime') !== false) {
                    $planClass = 'lifetime';
                } elseif (strpos($current_user_data['account_plan'] ?? '', 'family') !== false) {
                    $planClass = 'family';
                }
                ?>
                <span class="plan-badge <?php echo $planClass; ?>">
                    <?php echo strtoupper($current_user_data['account_plan'] ?? 'FREE'); ?>
                </span>
            </div>
            <div class="action-buttons">
                <a href="/myaccount/plan-details" class="btn btn-outline-primary">
                    <i class="bi bi-info-circle me-2"></i>Plan Details
                </a>
                <?php if ($current_user_data['account_plan'] !== 'lifetime' && $current_user_data['account_plan'] !== 'life'): ?>
                    <a href="/myaccount/upgrade-plan" class="btn btn-primary">
                        <i class="bi bi-arrow-up-circle me-2"></i>Upgrade Plan
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="billing-info-grid">
            <div class="billing-info-item">
                <h6>Plan Price</h6>
                <p class="mb-0 fs-5">
                    <?php 
                    if (($productData['plan_price'] ?? 0) > 0) {
                        echo '$' . number_format($productData['plan_price'] / 100, 2) . '/year';
                    } else {
                        echo 'Free';
                    }
                    ?>
                </p>
            </div>
            
            <div class="billing-info-item">
                <h6>Member Since</h6>
                <p class="mb-0 fs-5">
                    <?php 
                    if (!empty($current_user_data['create_dt']) && $current_user_data['create_dt'] != '0000-00-00 00:00:00') {
                        echo date('F j, Y', strtotime($current_user_data['create_dt']));
                    } else {
                        echo 'Unknown';
                    }
                    ?>
                </p>
            </div>
            
            <?php if ($nextBillingDate): ?>
            <div class="billing-info-item">
                <h6>Next Billing Date</h6>
                <p class="mb-0 fs-5">
                    <?php echo date('F j, Y', strtotime($nextBillingDate)); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($current_user_data['stripe_customer_id'])): ?>
            <div class="billing-info-item">
                <h6>Payment Method</h6>
                <p class="mb-0 fs-5">
                    <i class="bi bi-credit-card me-1"></i>
                    Card on file
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Payment History -->
    <div class="billing-history-card">
        <div class="billing-history-header">
            <h3 class="mb-0">Payment History</h3>
        </div>
        
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="bi bi-receipt"></i>
                <h4>No Payment History</h4>
                <p>You haven't made any payments yet.</p>
                <?php if ($current_user_data['account_plan'] === 'free'): ?>
                    <a href="/myaccount/upgrade-plan" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-up-circle me-2"></i>Upgrade to Gold
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <?php echo date('M j, Y', strtotime($payment['created_at'])); ?>
                                <br>
                                <small class="text-muted">
                                    <?php echo date('g:i A', strtotime($payment['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php 
                                $description = $payment['description'] ?? '';
                                if (empty($description)) {
                                    if ($payment['payment_type'] === 'subscription') {
                                        $description = 'Annual Subscription';
                                    } elseif ($payment['payment_type'] === 'upgrade') {
                                        $description = 'Plan Upgrade';
                                    } else {
                                        $description = 'Payment';
                                    }
                                }
                                echo htmlspecialchars($description);
                                ?>
                                <?php if (!empty($payment['invoice_id'])): ?>
                                    <br><small class="text-muted">Invoice: <?php echo htmlspecialchars($payment['invoice_id']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($payment['amount'] / 100, 2); ?></strong>
                            </td>
                            <td>
                                <?php
                                $statusClass = 'pending';
                                $statusText = ucfirst($payment['status'] ?? 'pending');
                                
                                if ($payment['status'] === 'completed' || $payment['status'] === 'succeeded') {
                                    $statusClass = 'completed';
                                    $statusText = 'Paid';
                                } elseif ($payment['status'] === 'failed') {
                                    $statusClass = 'failed';
                                    $statusText = 'Failed';
                                }
                                ?>
                                <span class="payment-status <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($payment['receipt_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($payment['receipt_url']); ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i>
                                    </a>
                                <?php elseif ($payment['status'] === 'completed'): ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                        <i class="bi bi-receipt"></i>
                                    </button>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Help Section -->
    <div class="mt-4 text-center text-muted">
        <p>
            Questions about your billing? 
            <a href="/contact">Contact Support</a>
        </p>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>