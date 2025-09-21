<?php 
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

// Access control
if ($current_user_data['username'] != 'ddgconsultant') {
    header('Location: /');
    exit();
}

// Page setup
$page_title = "Payroll Management - Birthday.Gold";
$page_description = "Process monthly payroll for contractors and team members";

// Custom styles for payroll page
$additionalstyles .= '
<style>
/* Payroll Page Styles */
.payroll-container {
    max-width: 1200px;
    margin: 0 auto;
    padding-top: 2rem;
}

.payroll-card {
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
}

.payroll-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transform: translateY(-2px);
    border-color: #667eea;
}

.card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #212529;
}

.user-details {
    color: #6c757d;
    font-size: 0.875rem;
}

.payment-info {
    text-align: right;
    min-width: 200px;
}

/* Use Bootstrap utility classes instead of custom colors */
.payment-platform i {
    font-size: 0.875rem;
}

.summary-section {
    margin-top: 3rem;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.summary-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.summary-item {
    text-align: center;
}

/* Summary section uses Bootstrap classes */

.summary-label {
    color: #6c757d;
    font-size: 0.875rem;
}

/* Remove custom colors - use Bootstrap utilities instead */

/* Mobile responsive */
@media (max-width: 768px) {
    .payroll-user {
        flex-direction: column;
        text-align: center;
    }
    
    .payment-info {
        text-align: center;
        width: 100%;
    }
}
</style>
';

// Set suppress output flag before including payout file
$suppressoutput = true;

// Include the payout configuration to get the $users array
$payout_file = $dir['configs'] . '/payout/payout.php';
if (file_exists($payout_file)) {
    include $payout_file;
    
    // Now we have access to the $users array from the payout file
    if (!isset($users) || empty($users)) {
        // No data available
        $users = [];
    }
} else {
    $users = [];
}

// Clear the suppress output flag
unset($suppressoutput);

$paymenttag = 'Birthday.gold ' . date('F Y');

// Sort users by platform and name
$platform = array_column($users, 'platform');
$name = array_column($users, 'name');
array_multisort($platform, SORT_ASC, $name, SORT_ASC, $users);

// Calculate totals
$total_amount = array_sum(array_column($users, 'amount'));
$total_contractors = count($users);
$platforms_count = count(array_unique(array_column($users, 'platform')));

// Set header flush for admin pages
$header_flush = true;

// Ensure we have the correct include path
if (!isset($dir['core_components'])) {
    $dir['core_components'] = $_SERVER['DOCUMENT_ROOT'] . '/core/components/v3';
}

// Include the standard admin page components
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Admin Content Header -->
<div class="content-header-admin">
    <div class="container">
        <h1 class="mt-3">Payroll Management</h1>
        <p class="lead mb-4">Process monthly payroll for contractors and team members</p>
    </div>
</div>

<div class="container payroll-container">
    <!-- Back to Admin Dashboard -->
    <div class="mb-4">
        <a href="/admin/" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Admin Dashboard
        </a>
    </div>
    <!-- Summary Section -->
    <div class="summary-section">
        <h2 class="summary-title">Payroll Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="display-6 text-primary">$<?php echo number_format($total_amount); ?></div>
                <div class="text-muted small">Total Payroll</div>
            </div>
            <div class="summary-item">
                <div class="display-6 text-info"><?php echo $total_contractors; ?></div>
                <div class="text-muted small">Active Contractors</div>
            </div>
            <div class="summary-item">
                <div class="display-6 text-secondary"><?php echo $platforms_count; ?></div>
                <div class="text-muted small">Payment Platforms</div>
            </div>
        </div>
    </div>

    <!-- Contractors List -->
    <div class="mt-5">
        <h3 class="mb-4">Contractor Payments</h3>
        
        <?php if (empty($users)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> No payroll data available.
        </div>
        <?php else: ?>
        <div class="row">
        <?php foreach ($users as $user): 
            $url = '';
            switch (strtolower($user['platform'])) {
                case 'venmo':
                    $url = 'https://account.venmo.com/pay?recipients=' . urlencode($user['account']) .
                           '&amount=' . $user['amount'] .
                           '&note=' . urlencode($paymenttag) .
                           '&audience=private';
                    break;
                case 'paypal':
                    $url = 'https://www.paypal.com/';
                    break;
                default:
                    $url = '#';
                    break;
            }
        ?>
        <div class="col-12 col-md-6 col-lg-4 mb-4">
            <div class="card h-100 payroll-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-circle-fill text-success small me-2" style="font-size: 0.5rem;"></i>
                            <?php echo htmlspecialchars($user['name']); ?>
                        </h5>
                        <span class="badge <?php echo strtolower($user['platform']) == 'venmo' ? 'bg-info' : 'bg-primary'; ?>">
                            <?php if (strtolower($user['platform']) == 'venmo'): ?>
                                <i class="bi bi-cash-coin"></i>
                            <?php elseif (strtolower($user['platform']) == 'paypal'): ?>
                                <i class="bi bi-paypal"></i>
                            <?php else: ?>
                                <i class="bi bi-wallet2"></i>
                            <?php endif; ?>
                            <?php echo $user['platform']; ?>
                        </span>
                    </div>
                    
                    <div class="user-details mb-3">
                        <div><i class="bi bi-clock text-muted"></i> <?php echo htmlspecialchars($user['details']); ?></div>
                        <div><i class="bi bi-person text-muted"></i> <?php echo htmlspecialchars($user['account']); ?></div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="h3 text-success mb-0">$<?php echo number_format($user['amount']); ?></div>
                        <a href="<?php echo $url; ?>" target="_payout" class="btn btn-sm btn-success px-4">
                            <i class="bi bi-send"></i> Pay
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Instructions -->
    <div class="alert alert-info mt-5">
        <h5 class="alert-heading"><i class="bi bi-info-circle"></i> Payment Instructions</h5>
        <ul class="mb-0">
            <li>Click "Process Payment" to open the payment platform with pre-filled information</li>
            <li>Venmo payments will include the amount and "Birthday.gold <?php echo date('F Y'); ?>" as the note</li>
            <li>PayPal payments require manual entry of amount and reference</li>
            <li>All payments should be marked as private/friends & family where applicable</li>
        </ul>
    </div>
</div>

<?php
// Include footer with correct path
if (!isset($dir['core_components'])) {
    $dir['core_components'] = $_SERVER['DOCUMENT_ROOT'] . '/core/components/v3';
}
include($dir['core_components'] . '/bg_footer.inc');

// Output the page if app object exists
if (isset($app) && method_exists($app, 'outputpage')) {
    $app->outputpage();
}
?>