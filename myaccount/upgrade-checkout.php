<?php
/**
 * Upgrade Checkout Page
 * Handles Stripe payment for plan upgrades
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.upgrademanager.php');

// Check if user is logged in
if (!$account->isactive()) {
    header('Location: /login');
    exit();
}

// Get upgrade session
$upgradeSessionId = $_GET['session'] ?? '';
$upgradeSession = $session->get('upgrade_session');

if (!$upgradeSession || $upgradeSession['upgrade_session_id'] !== $upgradeSessionId) {
    $errormessage = 'Invalid upgrade session. Please start again.';
    $session->set('errormessage', $errormessage);
    header('Location: /myaccount/upgrade-plan');
    exit();
}

// Check if session is still valid (1 hour timeout)
if ((time() - $upgradeSession['created_at']) > 3600) {
    $errormessage = 'Your upgrade session has expired. Please start again.';
    $session->set('errormessage', $errormessage);
    $session->unset('upgrade_session');
    header('Location: /myaccount/upgrade-plan');
    exit();
}

// Initialize managers
$productManager = new ProductManager($database, $qik);
$upgradeManager = new UpgradeManager($database, $account, $productManager, $session, $qik);

// Get product details
$newProduct = $productManager->getProduct($upgradeSession['to_product_id']);
$currentProduct = $productManager->getProduct($upgradeSession['from_product_id']);

if (!$newProduct || !$currentProduct) {
    $errormessage = 'Invalid product configuration';
    $session->set('errormessage', $errormessage);
    header('Location: /myaccount/upgrade-plan');
    exit();
}

// Calculate pricing
$baseUpgradeCost = $newProduct['price'] - $currentProduct['price'];
$discount = 0;

// Apply promo code if present
if (!empty($upgradeSession['promo_code'])) {
    if ($upgradeSession['promo_type'] === 'percentage') {
        $discount = ($baseUpgradeCost * $upgradeSession['promo_discount']) / 100;
    } else {
        $discount = $upgradeSession['promo_discount'];
    }
}

$finalAmount = max(0, $baseUpgradeCost - $discount);

// Handle promo code form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'apply_promo') {
    $promoCode = $_POST['promo_code'] ?? '';
    $result = $upgradeManager->applyPromoCode($promoCode, $upgradeSessionId);
    
    if ($result['success']) {
        $session->set('messages', ['Promo code applied successfully!']);
        header('Location: /myaccount/upgrade-checkout?session=' . $upgradeSessionId);
        exit();
    } else {
        $errormessage = $result['error'];
    }
}

// If amount is 0, process without payment
if ($finalAmount <= 0) {
    $result = $upgradeManager->processUpgrade($upgradeSessionId);
    if ($result['success']) {
        header('Location: /myaccount/upgrade-complete?id=' . $result['confirmation_id']);
        exit();
    } else {
        $session->set('errormessage', $result['error']);
        header('Location: /myaccount/upgrade-plan');
        exit();
    }
}

// Initialize Stripe
require_once($_SERVER['DOCUMENT_ROOT'] . '/../ENV_CONFIGS/vendor/autoload.php');
\Stripe\Stripe::setApiKey($STRIPECONFIG['stripe_secret_key']);

// Create or retrieve Stripe customer
$stripeCustomerId = null;
$customerEmail = $current_user_data['email'] ?? '';

// Check if user already has a Stripe customer ID
$sql = "SELECT stripe_customer_id FROM bg_users WHERE user_id = :user_id";
$userData = $database->getrow($sql, ['user_id' => $account->getUserId()]);

if (!empty($userData['stripe_customer_id'])) {
    $stripeCustomerId = $userData['stripe_customer_id'];
} else {
    // Create new Stripe customer
    try {
        $customer = \Stripe\Customer::create([
            'email' => $customerEmail,
            'metadata' => [
                'user_id' => $account->getUserId(),
                'username' => $current_user_data['username'] ?? ''
            ]
        ]);
        $stripeCustomerId = $customer->id;
        
        // Save to database
        $database->query(
            "UPDATE bg_users SET stripe_customer_id = :stripe_id WHERE user_id = :user_id",
            ['stripe_id' => $stripeCustomerId, 'user_id' => $account->getUserId()]
        );
    } catch (Exception $e) {
        $errormessage = 'Error creating payment profile: ' . $e->getMessage();
    }
}

// Page configuration
$pagedata = [
    'pagetitle' => 'Upgrade Checkout',
    'activepage' => 'billing'
];

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Initialize messages
$messages = $session->get('messages', []);
$session->unset('messages');
$errormessage = $errormessage ?? '';
?>

<style>
.checkout-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

.checkout-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.checkout-header {
    background: #f8f9fa;
    padding: 2rem;
    border-bottom: 1px solid #dee2e6;
}

.checkout-body {
    padding: 2rem;
}

.plan-comparison {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 2rem;
    align-items: center;
    margin-bottom: 2rem;
}

.plan-box {
    text-align: center;
    padding: 1.5rem;
    border: 2px solid #dee2e6;
    border-radius: 8px;
}

.plan-box.current {
    background: #f8f9fa;
}

.plan-box.new {
    border-color: #198754;
    background: #f1f8f4;
}

.arrow-icon {
    font-size: 2rem;
    color: #198754;
}

.pricing-breakdown {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.pricing-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.pricing-row.total {
    border-top: 2px solid #dee2e6;
    margin-top: 1rem;
    padding-top: 1rem;
    font-weight: bold;
    font-size: 1.2rem;
}

.promo-section {
    background: #fff3cd;
    border: 1px solid #ffeeba;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.promo-applied {
    background: #d4edda;
    border-color: #c3e6cb;
}

#payment-element {
    margin-bottom: 2rem;
}

.guarantee-box {
    background: #e8f5e9;
    border: 1px solid #c8e6c9;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 2rem;
    text-align: center;
}

.security-badges {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 2rem;
    opacity: 0.7;
}
</style>

<div class="checkout-container">
    <?php if (!empty($errormessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <?php echo htmlspecialchars($errormessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div class="checkout-card">
        <div class="checkout-header">
            <h1 class="h3 mb-0">Complete Your Upgrade</h1>
        </div>
        
        <div class="checkout-body">
            <!-- Plan Comparison -->
            <div class="plan-comparison">
                <div class="plan-box current">
                    <h5>Current Plan</h5>
                    <h4><?php echo htmlspecialchars($currentProduct['name']); ?></h4>
                    <div class="text-muted">
                        $<?php echo number_format($currentProduct['price'] / 100, 2); ?>/year
                    </div>
                </div>
                
                <div class="arrow-icon">
                    <i class="bi bi-arrow-right-circle-fill"></i>
                </div>
                
                <div class="plan-box new">
                    <h5>New Plan</h5>
                    <h4><?php echo htmlspecialchars($newProduct['name']); ?></h4>
                    <div class="text-success">
                        $<?php echo number_format($newProduct['price'] / 100, 2); ?>/year
                    </div>
                </div>
            </div>
            
            <!-- Pricing Breakdown -->
            <div class="pricing-breakdown">
                <h5 class="mb-3">Pricing Details</h5>
                
                <div class="pricing-row">
                    <span><?php echo htmlspecialchars($newProduct['name']); ?> Annual Price</span>
                    <span>$<?php echo number_format($newProduct['price'] / 100, 2); ?></span>
                </div>
                
                <div class="pricing-row">
                    <span>Less: <?php echo htmlspecialchars($currentProduct['name']); ?> Credit</span>
                    <span>-$<?php echo number_format($currentProduct['price'] / 100, 2); ?></span>
                </div>
                
                <?php if ($discount > 0): ?>
                <div class="pricing-row text-success">
                    <span>Promo Discount</span>
                    <span>-$<?php echo number_format($discount / 100, 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="pricing-row total">
                    <span>Total Due Today</span>
                    <span>$<?php echo number_format($finalAmount / 100, 2); ?></span>
                </div>
            </div>
            
            <!-- Promo Code Section -->
            <div class="promo-section <?php echo !empty($upgradeSession['promo_code']) ? 'promo-applied' : ''; ?>">
                <?php if (!empty($upgradeSession['promo_code'])): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Promo code applied: <strong><?php echo htmlspecialchars($upgradeSession['promo_code']); ?></strong>
                        </div>
                        <div>
                            Discount: $<?php echo number_format($discount / 100, 2); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" class="d-flex gap-2">
                        <input type="hidden" name="action" value="apply_promo">
                        <?php echo $display->inputcsrf_token(); ?>
                        <input type="text" name="promo_code" class="form-control" placeholder="Enter promo code">
                        <button type="submit" class="btn btn-outline-primary">Apply</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Payment Form -->
            <form id="payment-form">
                <div id="payment-element">
                    <!-- Stripe Elements will be inserted here -->
                </div>
                
                <button type="submit" id="submit-button" class="btn btn-primary btn-lg w-100">
                    <span id="button-text">Complete Upgrade - $<?php echo number_format($finalAmount / 100, 2); ?></span>
                    <span id="spinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                        <span class="visually-hidden">Processing...</span>
                    </span>
                </button>
                
                <div id="payment-message" class="alert mt-3 d-none"></div>
            </form>
            
            <!-- Money Back Guarantee -->
            <div class="guarantee-box">
                <i class="bi bi-shield-check fs-3 text-success mb-2"></i>
                <h6>30-Day Money Back Guarantee</h6>
                <p class="mb-0 text-muted small">
                    If you're not completely satisfied with your upgrade, contact us within 30 days for a full refund.
                </p>
            </div>
            
            <!-- Security Badges -->
            <div class="security-badges">
                <img src="/public/images/stripe-badge.png" alt="Powered by Stripe" height="30">
                <img src="/public/images/ssl-secure.png" alt="SSL Secure" height="30">
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
// Initialize Stripe
const stripe = Stripe('<?php echo $STRIPECONFIG['stripe_publishable_key']; ?>');

// Create Payment Intent
let elements;

initialize();

async function initialize() {
    const response = await fetch('/api/create-upgrade-payment-intent', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            upgrade_session_id: '<?php echo $upgradeSessionId; ?>',
            amount: <?php echo $finalAmount; ?>,
            customer_id: '<?php echo $stripeCustomerId; ?>'
        })
    });
    
    const { clientSecret, error } = await response.json();
    
    if (error) {
        showMessage(error);
        return;
    }
    
    const appearance = {
        theme: 'stripe',
        variables: {
            colorPrimary: '#198754',
        }
    };
    
    elements = stripe.elements({ appearance, clientSecret });
    
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');
}

// Handle form submission
const form = document.getElementById('payment-form');
form.addEventListener('submit', handleSubmit);

async function handleSubmit(e) {
    e.preventDefault();
    setLoading(true);
    
    const { error } = await stripe.confirmPayment({
        elements,
        confirmParams: {
            return_url: '<?php echo $website['baseurl']; ?>/myaccount/upgrade-process?session=<?php echo $upgradeSessionId; ?>',
        },
    });
    
    if (error) {
        if (error.type === "card_error" || error.type === "validation_error") {
            showMessage(error.message);
        } else {
            showMessage("An unexpected error occurred.");
        }
    }
    
    setLoading(false);
}

function showMessage(messageText) {
    const messageContainer = document.querySelector('#payment-message');
    messageContainer.classList.remove('d-none', 'alert-success', 'alert-danger');
    messageContainer.classList.add('alert-danger');
    messageContainer.textContent = messageText;
}

function setLoading(isLoading) {
    if (isLoading) {
        document.querySelector('#submit-button').disabled = true;
        document.querySelector('#spinner').classList.remove('d-none');
        document.querySelector('#button-text').textContent = 'Processing...';
    } else {
        document.querySelector('#submit-button').disabled = false;
        document.querySelector('#spinner').classList.add('d-none');
        document.querySelector('#button-text').textContent = 'Complete Upgrade - $<?php echo number_format($finalAmount / 100, 2); ?>';
    }
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>