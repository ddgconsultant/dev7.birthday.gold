<?php
/**
 * Plan Upgrade Page
 * Allows users to upgrade their current plan to a higher tier
 */

$addClasses[] = 'productmanager';
$addClasses[] = 'upgrademanager';
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if user is logged in
if (!$account->isactive()) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Managers are auto-instantiated by site-controller
// productmanager and upgrademanager are available as lowercase variables

// Get upgrade options
$upgradeData = $upgrademanager->getUpgradeOptions();

// Handle different response types
if (isset($upgradeData['error'])) {
    // Error case - no plan found
    $canUpgrade = false;
    $noUpgradeReason = $upgradeData['error'];
    $upgradeData['can_upgrade'] = false;
    $upgradeData['current_plan'] = null;
    $upgradeData['upgrade_options'] = [];
} else {
    // Normal case - check if user can upgrade
    $canUpgrade = $upgradeData['can_upgrade'] ?? false;
    if (!$canUpgrade) {
        $noUpgradeReason = $upgradeData['reason'] ?? 'You cannot upgrade your current plan';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'apply_promo') {
        // Apply promo code
        $promoCode = $_POST['promo_code'] ?? '';
        $upgradeSessionId = $_POST['upgrade_session_id'] ?? '';
        
        $result = $upgrademanager->applyPromoCode($promoCode, $upgradeSessionId);
        
        if ($result['success']) {
            $messages[] = 'Promo code applied successfully!';
        } else {
            $errormessage = $result['error'];
        }
        
    } elseif ($action === 'start_upgrade') {
        // Initialize upgrade session
        $toProductId = $_POST['product_id'] ?? '';
        $upgradeToken = $_POST['upgrade_token'] ?? '';
        
        // Validate token
        $tokenValidation = $upgrademanager->validateUpgradeToken($upgradeToken);
        if (!$tokenValidation['valid']) {
            $errormessage = $tokenValidation['error'];
            $session->set('errormessage', $errormessage);
            header('Location: /myaccount/upgrade-plan');
            exit();
        }
        
        // Initialize session
        $upgradeSessionId = $upgrademanager->initializeUpgradeSession($toProductId);
        
        // Get the selected product details
        $selectedProduct = null;
        foreach ($upgradeData['upgrade_options'] as $option) {
            if ($option['product']['id'] == $toProductId) {
                $selectedProduct = $option;
                break;
            }
        }
        
        if (!$selectedProduct) {
            $errormessage = 'Invalid product selected';
            $session->set('errormessage', $errormessage);
            header('Location: /myaccount/upgrade-plan');
            exit();
        }
        
        // If no payment required, process immediately
        if ($selectedProduct['pricing']['total_due'] <= 0) {
            $result = $upgrademanager->processUpgrade($upgradeSessionId);
            if ($result['success']) {
                header('Location: /myaccount/upgrade-complete?id=' . $result['confirmation_id']);
                exit();
            } else {
                $errormessage = $result['error'];
            }
        } else {
            // Redirect to Stripe checkout
            header('Location: /myaccount/upgrade-checkout?session=' . $upgradeSessionId);
            exit();
        }
    }
}

// Page configuration
$pagedata = [
    'pagetitle' => 'Upgrade Your Plan',
    'activepage' => 'billing'
];

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Initialize messages array
$messages = $messages ?? [];
$errormessage = $errormessage ?? '';

// Check for session error message
$sessionError = $session->get('errormessage');
if ($sessionError) {
    $errormessage = $sessionError;
    $session->unset('errormessage');
}
?>

<style>
.upgrade-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.current-plan-card {
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 3rem;
}

.upgrade-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.upgrade-option-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 2rem;
    position: relative;
    transition: all 0.3s ease;
}

.upgrade-option-card:hover {
    border-color: #198754;
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.15);
}

.upgrade-option-card.recommended {
    border-color: #198754;
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.1);
}

.recommended-badge {
    position: absolute;
    top: -12px;
    right: 20px;
    background: #198754;
    color: white;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.plan-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.plan-price {
    font-size: 2.5rem;
    font-weight: 700;
    color: #198754;
    margin-bottom: 1rem;
}

.plan-price small {
    font-size: 1rem;
    color: #6c757d;
    font-weight: 400;
}

.upgrade-cost {
    background: #e8f5e9;
    border: 1px solid #c8e6c9;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 1.5rem 0;
}

.feature-list li {
    padding: 0.5rem 0;
    display: flex;
    align-items: center;
}

.feature-list li i {
    color: #198754;
    margin-right: 0.75rem;
}

.promo-code-section {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.comparison-table {
    width: 100%;
    margin-top: 3rem;
}

.comparison-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
}

.comparison-table td {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.check-icon {
    color: #198754;
}

.times-icon {
    color: #dc3545;
}
</style>

<div class="upgrade-container">
    <h1 class="mb-4">Upgrade Your Plan</h1>
    
    <?php if (!empty($errormessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errormessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!$canUpgrade): ?>
        <!-- No Upgrades Available Message -->
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading"><i class="bi bi-trophy-fill me-2"></i>You Have the Best Plan Available!</h4>
            <p><?php echo htmlspecialchars($noUpgradeReason); ?></p>
            <hr>
            <p class="mb-0">
                <?php if (strpos($upgradeData['current_plan']['account_plan'] ?? '', 'life') !== false || strpos($upgradeData['current_plan']['account_plan'] ?? '', 'lifetime') !== false): ?>
                    You have a lifetime plan - enjoy unlimited access forever!
                <?php else: ?>
                    Your current plan provides maximum features and benefits. Thank you for being a valued member!
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($upgradeData['current_plan']): ?>
        <!-- Show current plan details -->
        <div class="current-plan-card mt-4">
            <h3>Your Current Plan</h3>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($upgradeData['current_plan']['account_name'] ?? 'Current Plan'); ?></h4>
                    <p class="text-muted mb-0">
                        <?php echo htmlspecialchars($upgradeData['current_plan']['description'] ?? ''); ?>
                    </p>
                </div>
                <div class="text-end">
                    <div class="plan-price">
                        $<?php echo number_format(($upgradeData['current_plan']['price'] ?? 0) / 100, 2); ?>
                        <small>/<?php echo $upgradeData['current_plan']['billing_period'] ?? 'year'; ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="/myaccount/" class="btn btn-primary">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
            <a href="/myaccount/billing" class="btn btn-outline-primary">
                <i class="bi bi-credit-card me-2"></i>View Billing
            </a>
        </div>
    <?php else: ?>
    
    <!-- Current Plan -->
    <div class="current-plan-card">
        <h3>Your Current Plan</h3>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1"><?php echo htmlspecialchars($upgradeData['current_plan']['name']); ?></h4>
                <p class="text-muted mb-0">
                    <?php echo htmlspecialchars($upgradeData['current_plan']['description'] ?? ''); ?>
                </p>
            </div>
            <div class="text-end">
                <div class="plan-price">
                    $<?php echo number_format($upgradeData['current_plan']['price'] / 100, 2); ?>
                    <small>/<?php echo $upgradeData['current_plan']['billing_period'] ?? 'year'; ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upgrade Options -->
    <h2 class="mb-4">Available Upgrades</h2>
    
    <div class="upgrade-options">
        <?php foreach ($upgradeData['upgrade_options'] as $index => $option): 
            $product = $option['product'];
            $pricing = $option['pricing'];
            $isRecommended = ($index === 0); // First option is recommended
        ?>
        <div class="upgrade-option-card <?php echo $isRecommended ? 'recommended' : ''; ?>">
            <?php if ($isRecommended): ?>
                <span class="recommended-badge">Recommended</span>
            <?php endif; ?>
            
            <h3 class="plan-name"><?php echo htmlspecialchars($product['name']); ?></h3>
            
            <div class="plan-price">
                $<?php echo number_format($product['price'] / 100, 2); ?>
                <small>/<?php echo $product['billing_period'] ?? 'year'; ?></small>
            </div>
            
            <div class="upgrade-cost">
                <strong>Upgrade Cost:</strong> 
                $<?php echo number_format($pricing['total_due'] / 100, 2); ?>
                <?php if ($pricing['proration'] > 0): ?>
                    <small class="text-muted">(includes proration credit)</small>
                <?php endif; ?>
            </div>
            
            <p class="text-muted"><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
            
            <!-- Features -->
            <ul class="feature-list">
                <?php 
                $features = $product['features'] ?? [];
                foreach ($features as $feature): 
                    if (strpos($feature['name'], '_sys_') === 0) continue; // Skip system features
                ?>
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo htmlspecialchars($feature['display_value'] ?? $feature['value']); ?>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="start_upgrade">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="upgrade_token" value="<?php echo htmlspecialchars($option['upgrade_token']); ?>">
                <?php echo $display->inputcsrf_token(); ?>
                
                <button type="submit" class="btn btn-primary w-100">
                    Upgrade to <?php echo htmlspecialchars($product['name']); ?>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Comparison Table -->
    <div class="mt-5">
        <h3 class="mb-4">Plan Comparison</h3>
        <table class="comparison-table table">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th><?php echo htmlspecialchars($upgradeData['current_plan']['account_name'] ?? 'Current Plan'); ?> (Current)</th>
                    <?php foreach ($upgradeData['upgrade_options'] as $option): ?>
                    <th><?php echo htmlspecialchars($option['product']['name']); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Price</td>
                    <td>$<?php echo number_format($upgradeData['current_plan']['price'] / 100, 2); ?>/year</td>
                    <?php foreach ($upgradeData['upgrade_options'] as $option): ?>
                    <td>$<?php echo number_format($option['product']['price'] / 100, 2); ?>/year</td>
                    <?php endforeach; ?>
                </tr>
                <!-- Add more comparison rows based on features -->
            </tbody>
        </table>
    </div>
    
    <!-- FAQ Section -->
    <div class="mt-5">
        <h3 class="mb-4">Frequently Asked Questions</h3>
        <div class="accordion" id="upgradeFAQ">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        When will my upgrade take effect?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#upgradeFAQ">
                    <div class="accordion-body">
                        Your upgrade will take effect immediately after payment is processed. You'll have instant access to all features of your new plan.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        Will I be charged the full amount?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#upgradeFAQ">
                    <div class="accordion-body">
                        You'll only be charged the difference between your current plan and the new plan. If you have time remaining on your current plan, we'll provide a prorated credit.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        Can I downgrade later?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#upgradeFAQ">
                    <div class="accordion-body">
                        Plan downgrades will be available in the future. Please contact support if you need to make changes to your plan.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; // End of can_upgrade check ?>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>