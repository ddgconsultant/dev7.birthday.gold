<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Load Composer autoloader for Stripe
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

// Enable error logging for debugging
error_log('[CHECKOUT_API] Script started');

#-------------------------------------------------------------------------------
# HELPER FUNCTION TO CHECK IF TABLE EXISTS
#-------------------------------------------------------------------------------
function tableExists($database, $tableName) {
    try {
        $sql = "SHOW TABLES LIKE :table";
        $result = $database->getrow($sql, ['table' => $tableName]);
        return !empty($result);
    } catch (Exception $e) {
        error_log('[CHECKOUT_API] Error checking table existence: ' . $e->getMessage());
        return false;
    }
}

#-------------------------------------------------------------------------------
# HANDLE AJAX PAYMENT REQUEST
#-------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Get Stripe configuration
    $STRIPECONFIG = $sitesettings['paymentgateway-stripe-live'] ?? [];
    $stripe_secret = $STRIPECONFIG['STRIPE_SECRET'] ?? '';
    
    if (empty($stripe_secret)) {
        error_log('[CHECKOUT_API] Missing Stripe secret key');
        echo json_encode(['error' => 'Payment configuration error']);
        exit();
    }
    
    \Stripe\Stripe::setApiKey($stripe_secret);
    
    if ($_POST['action'] === 'confirm_payment') {
        $payment_intent_id = $_POST['payment_intent_id'] ?? '';
        $user_id = $_POST['user_id'] ?? '';
        
        error_log('[CHECKOUT_API] Confirming payment - Intent: ' . $payment_intent_id . ', User: ' . $user_id);
        
        try {
            // Retrieve payment intent
            $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            // Check various payment status conditions
            $is_payment_successful = in_array($payment_intent->status, ['succeeded', 'processing']);
            
            if ($is_payment_successful) {
                // Update user to active
                $sql = "UPDATE bg_users SET status = 'active', modify_dt = NOW() WHERE user_id = :user_id";
                $database->execute($sql, ['user_id' => $user_id]);
                error_log('[CHECKOUT_API] User status updated to active');
                
                // Log user in immediately
                $session->set('user_id', $user_id);
                $session->set('logged_in', true);
                
                // Check if bg_transactions table exists before updating
                if (tableExists($database, 'bg_transactions')) {
                    // Update transaction
                    $sql = "UPDATE bg_transactions 
                            SET status = 'completed', stripe_payment_intent = :pi_id, completed_at = NOW()
                            WHERE user_id = :user_id AND status = 'pending'
                            ORDER BY created_at DESC LIMIT 1";
                    $database->execute($sql, ['user_id' => $user_id, 'pi_id' => $payment_intent_id]);
                    error_log('[CHECKOUT_API] Transaction updated');
                } else {
                    error_log('[CHECKOUT_API] bg_transactions table does not exist, skipping transaction update');
                }
                
                // Check if bg_checkout_sessions table exists and update it
                if (tableExists($database, 'bg_checkout_sessions')) {
                    $sql = "UPDATE bg_checkout_sessions 
                            SET status = 'completed', completed_at = NOW()
                            WHERE user_id = :user_id AND status = 'pending'
                            ORDER BY created_at DESC LIMIT 1";
                    $database->execute($sql, ['user_id' => $user_id]);
                    error_log('[CHECKOUT_API] Checkout session updated');
                }
                
                // Create payment record if table exists
                if (tableExists($database, 'bg_payments')) {
                    try {
                        $sql = "INSERT INTO bg_payments 
                                (user_id, amount, stripe_payment_intent, status, payment_method, metadata, created_at) 
                                VALUES (:user_id, :amount, :payment_intent, 'completed', :payment_method, :metadata, NOW())";
                        
                        $database->execute($sql, [
                            'user_id' => $user_id,
                            'amount' => $payment_intent->amount,
                            'payment_intent' => $payment_intent->id,
                            'payment_method' => $payment_intent->payment_method_types[0] ?? 'card',
                            'metadata' => json_encode($payment_intent->metadata ?? [])
                        ]);
                        error_log('[CHECKOUT_API] Payment record created');
                    } catch (Exception $e) {
                        error_log('[CHECKOUT_API] Failed to create payment record: ' . $e->getMessage());
                    }
                }
                
                // Clear signup session data
                $session->delete('signup_process_data');
                $session->delete('userregistrationdata');
                
                // Determine redirect based on account type
                $user_sql = "SELECT account_type FROM bg_users WHERE user_id = :user_id";
                $user_data = $database->getrow($user_sql, ['user_id' => $user_id]);
                
                // Redirect to celebration page first
                $encoded_user_id = $qik->encodeId($user_id);
                $redirect_url = '/claudecode/checkout_celebration.php?u=' . $encoded_user_id;
                
                error_log('[CHECKOUT_API] Payment successful, redirecting to celebration page');
                echo json_encode(['success' => true, 'redirect' => $redirect_url]);
            } else {
                error_log('[CHECKOUT_API] Payment status not succeeded: ' . $payment_intent->status);
                echo json_encode(['error' => 'Payment not completed. Status: ' . $payment_intent->status]);
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('[CHECKOUT_API] Stripe API error: ' . $e->getMessage());
            // Check if this is just a retrieval error but payment might have succeeded
            echo json_encode(['error' => 'Unable to verify payment status. If payment was deducted, please refresh the page or contact support.']);
        } catch (Exception $e) {
            error_log('[CHECKOUT_API] General error: ' . $e->getMessage());
            echo json_encode(['error' => 'Unable to verify payment. If payment was deducted, please refresh the page or contact support.']);
        }
        exit();
    }
    
    // Handle payment status check (for heartbeat)
    if ($_POST['action'] === 'check_payment_status') {
        $payment_intent_id = $_POST['payment_intent_id'] ?? '';
        
        try {
            // Retrieve payment intent
            $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            // Get user from payment intent metadata
            $user_id = $payment_intent->metadata->user_id ?? '';
            
            if ($user_id) {
                // Check user status
                $sql = "SELECT status, account_type FROM bg_users WHERE user_id = :user_id";
                $user_data = $database->getrow($sql, ['user_id' => $user_id]);
                
                // If payment is succeeded or processing and user is not active yet, activate them
                if (in_array($payment_intent->status, ['succeeded', 'processing']) && 
                    $user_data && $user_data['status'] !== 'active') {
                    
                    $sql = "UPDATE bg_users SET status = 'active', modify_dt = NOW() WHERE user_id = :user_id";
                    $database->execute($sql, ['user_id' => $user_id]);
                    
                    // Re-fetch user data
                    $user_data = $database->getrow("SELECT status, account_type FROM bg_users WHERE user_id = :user_id", ['user_id' => $user_id]);
                    
                    error_log('[CHECKOUT_API] User activated via heartbeat check');
                }
                
                // Determine redirect
                $encoded_user_id = $qik->encodeId($user_id);
                $redirect_url = '/claudecode/checkout_celebration.php?u=' . $encoded_user_id;
                
                echo json_encode([
                    'status' => $payment_intent->status,
                    'user_active' => ($user_data && $user_data['status'] === 'active'),
                    'redirect' => $redirect_url
                ]);
            } else {
                echo json_encode(['status' => $payment_intent->status, 'user_active' => false]);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }
}

#-------------------------------------------------------------------------------
# GET USER DATA
#-------------------------------------------------------------------------------
$encoded_user_id = $_REQUEST['u'] ?? '';
if (empty($encoded_user_id)) {
    error_log('[CHECKOUT_API] No user ID provided, redirecting to signup complete');
    header('Location: /signup.php');
    exit();
}

try {
    $user_id = $qik->decodeId($encoded_user_id);
    error_log('[CHECKOUT_API] Processing checkout for user: ' . $user_id);
} catch (Exception $e) {
    error_log('[CHECKOUT_API] Invalid user ID encoding: ' . $encoded_user_id);
    header('Location: /signup.php');
    exit();
}

// Get user and product data
$sql = "SELECT u.*, p.* 
        FROM bg_users u 
        LEFT JOIN bg_products p ON u.account_product_id = p.id 
        WHERE u.user_id = :user_id";
$user_data = $database->getrow($sql, ['user_id' => $user_id]);

if (!$user_data) {
    error_log('[CHECKOUT_API] User not found: ' . $user_id);
    header('Location: /signup.php');
    exit();
}

error_log('[CHECKOUT_API] User data loaded - Type: ' . $user_data['account_type'] . ', Product: ' . $user_data['account_product_id']);

// Load ProductManager
if (!class_exists('ProductManager')) {
    include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
}
// Use standalone version that handles promo codes properly
include($_SERVER['DOCUMENT_ROOT'].'/claudecode/class.productmanager_promo.php');
$productManager = new ProductManagerPromo($database, $qik);

// Get pricing
$signup_data = $session->get('signup_process_data', []);
$promo_code = $signup_data['promo_code'] ?? '';

// Debug promo code
error_log('[CHECKOUT_API] Promo code from session: ' . $promo_code);

if (!empty($user_data['account_product_id'])) {
    // First check if product exists and allows promos
    $product = $productManager->getProduct($user_data['account_product_id']);
    
    if ($promo_code && (!isset($product['allow_promo']) || $product['allow_promo'] != 'yes')) {
        // Try to apply promo anyway for now
        error_log('[CHECKOUT_API] Product does not have allow_promo=yes, but trying promo anyway');
    }
    
    $pricing = $productManager->calculatePrice($user_data['account_product_id'], $promo_code);
    $amount = $pricing['final_price'] ?? $pricing['original_price'] ?? 2900;
    
    // Show promo validation message
    $promo_message = '';
    if ($promo_code && isset($pricing['promo_validation'])) {
        $promo_message = $pricing['promo_validation']['message'] ?? '';
    }
} else {
    $amount = 2900; // Default $29
    $promo_message = '';
}

#-------------------------------------------------------------------------------
# CREATE PAYMENT INTENT
#-------------------------------------------------------------------------------
$STRIPECONFIG = $sitesettings['paymentgateway-stripe-live'] ?? [];
$stripe_key = $STRIPECONFIG['STRIPE_KEY'] ?? '';
$stripe_secret = $STRIPECONFIG['STRIPE_SECRET'] ?? '';

if (empty($stripe_key) || empty($stripe_secret)) {
    error_log('[CHECKOUT_API] Missing Stripe configuration');
    die('Payment configuration error. Please contact support.');
}

\Stripe\Stripe::setApiKey($stripe_secret);

try {
    // Create payment intent with additional metadata
    $payment_intent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'usd',
        'automatic_payment_methods' => ['enabled' => true],
        'metadata' => [
            'user_id' => $user_id,
            'account_type' => $user_data['account_type'],
            'product_id' => $user_data['account_product_id'] ?? '',
            'email' => $user_data['email'] ?? ''
        ],
        'description' => 'Birthday Gold ' . ucfirst($user_data['account_type']) . ' Account'
    ]);
    
    error_log('[CHECKOUT_API] Payment intent created: ' . $payment_intent->id);
    
    // Create checkout session record if table exists
    if (tableExists($database, 'bg_checkout_sessions')) {
        $session_id = bin2hex(random_bytes(32));
        $recovery_token = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO bg_checkout_sessions 
                (session_id, stripe_session_id, user_id, product_id, amount, recovery_token, session_data, status)
                VALUES 
                (:session_id, :stripe_session_id, :user_id, :product_id, :amount, :recovery_token, :session_data, 'pending')";
        
        $session_data = json_encode([
            'promo_code' => $promo_code ?? '',
            'user_email' => $user_data['email'] ?? '',
            'account_type' => $user_data['account_type']
        ]);
        
        $database->execute($sql, [
            'session_id' => $session_id,
            'stripe_session_id' => $payment_intent->id,
            'user_id' => $user_id,
            'product_id' => $user_data['account_product_id'] ?? 0,
            'amount' => $amount,
            'recovery_token' => $recovery_token,
            'session_data' => $session_data
        ]);
        
        error_log('[CHECKOUT_API] Checkout session created: ' . $session_id);
    }
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('[CHECKOUT_API] Stripe API error: ' . $e->getMessage());
    die('Unable to create payment: ' . $e->getMessage() . ' Please try again later.');
} catch (Exception $e) {
    error_log('[CHECKOUT_API] General error creating payment: ' . $e->getMessage());
    die('Unable to create payment. Please contact support.');
}

#-------------------------------------------------------------------------------
# PAGE DISPLAY
#-------------------------------------------------------------------------------
$pagetitle = 'Complete Your Purchase - Birthday.Gold';
$page_description = 'Complete your Birthday Gold checkout';

// Include the same styles as createaccount
$additionalstyles = '
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
' . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/claudecode/createaccount_styles.css') . '
/* Additional checkout-specific styles */
.checkout-content {
    max-width: 100%;
    margin: 0 auto;
}

/* Minimal header for checkout */
.checkout-header {
    background: white;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 0;
    margin-bottom: 2rem;
    position: sticky;
    top: 0;
    z-index: 100;
}

.checkout-header .container {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.checkout-header .logo {
    height: 36px;
}

.checkout-header .security-badge {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    color: #6c757d;
}

.checkout-header .security-badge i {
    font-size: 1.1rem;
    margin-right: 0.5rem;
    color: #198754;
}

/* Override header for cleaner look */
.header {
    margin-bottom: 1rem;
}

/* Improved progress bar */
.progress-container {
    margin-bottom: 2rem;
}

.progress {
    height: 6px !important;
    background: #e9ecef;
}

/* Progress steps - better alignment */
.progress-steps {
    position: relative;
}

/* Ensure line is below icons */
.step-indicator {
    z-index: 2;
    position: relative;
}

.step-indicator:not(:last-child)::after {
    content: "";
    position: absolute;
    top: 1.5rem; /* Adjusted to center with icon */
    left: 50%;
    width: 100%;
    height: 2px;
    background: #dee2e6;
    z-index: -1;
}

/* Desktop: grid layout */
@media (min-width: 992px) {
    .checkout-grid {
        display: grid;
        grid-template-columns: 5fr 7fr;
        gap: 3rem;
        align-items: start;
    }
    
    .left-column {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
}

.price-display {
    background: linear-gradient(135deg, #f8fffe 0%, #f0f9ff 100%);
    border: 1px solid #e3f2fd;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.order-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    font-size: 0.9rem;
}

#payment-element {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    min-height: 280px;
    margin-bottom: 1.5rem;
}

#payment-element:focus-within {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.1);
}

.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #198754;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.promo-applied {
    background-color: #d1f2db;
    border: 1px solid #b8e7c3;
    color: #155724;
    padding: 12px;
    border-radius: 8px;
    margin-top: 1rem;
    font-size: 0.95rem;
}

.promo-applied i {
    color: #28a745;
}

/* Make form sections more compact on checkout */
.checkout-content .form-section {
    padding: 1.5rem;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}

.checkout-content .section-title {
    font-size: 1.1rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e9ecef;
}

/* Sticky order summary on desktop */
@media (min-width: 992px) {
    .order-summary-section {
        position: sticky;
        top: 80px; /* Account for header */
    }
}

/* Trust section styling */
.trust-section {
    padding: 1rem;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

/* Mobile: single column with reordering */
@media (max-width: 991px) {
    .checkout-grid {
        display: flex;
        flex-direction: column;
    }
    
    /* Order: 1. Order Summary, 2. Payment Form, 3. Trust Section */
    .order-summary-section {
        order: 1;
    }
    
    .trust-section {
        order: 3;
        margin-top: 1.5rem;
    }
    
    /* Payment form section */
    .checkout-grid > div:last-child {
        order: 2;
        margin-top: 1.5rem;
    }
    
    .left-column {
        display: contents; /* This makes children act as direct children of grid */
    }
}

/* Trust indicators */
.trust-badges {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    padding: 1.5rem 0;
    margin-top: 2rem;
    border-top: 1px solid #e9ecef;
}

.trust-badge {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    color: #6c757d;
}

.trust-badge i {
    font-size: 1.25rem;
    margin-right: 0.5rem;
}

/* Back link styling */
.back-link {
    display: inline-flex;
    align-items: center;
    color: #6c757d;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.2s;
}

.back-link:hover {
    color: #495057;
}

/* Hide main navigation on checkout page */
body.checkout-page .top-header {
    display: none;
}

body.checkout-page {
    padding-top: 0;
}
</style>
';

// Add checkout-page class to body
$bodyclass = 'class="checkout-page"';

include($dir['core_components'] . '/bg_pagestart.inc');
// Skip the main header for checkout
?>

<!-- Simplified Checkout Header -->
<div class="checkout-header">
    <div class="container">
        <a href="/" class="d-flex align-items-center">
            <img src="//cdn.birthday.gold/public/images/logo/birthday.gold_logo.png" alt="Birthday Gold" class="logo">
        </a>
        <div class="security-badge">
            <i class="bi bi-shield-lock-fill"></i>
            Secure Checkout
        </div>
    </div>
</div>

<div class="container main-content">
    <!-- Header -->
    <div class="header text-center">
        <h1>Complete Your Purchase</h1>
        <p class="text-muted">You're almost done! Just one more step.</p>
    </div>

    <!-- Progress Bar -->
    <div class="progress-container mb-4">
        <!-- Progress bar removed since we're at 100% -->
        <div class="progress-steps mt-3">
            <div class="step-indicator completed">
                <i class="bi bi-check-circle-fill"></i>
                <span>Choose Plan</span>
            </div>
            <div class="step-indicator completed">
                <i class="bi bi-check-circle-fill"></i>
                <span>Account Details</span>
            </div>
            <div class="step-indicator active">
                <i class="bi bi-3-circle-fill"></i>
                <span>Payment</span>
            </div>
        </div>
    </div>

    <div class="checkout-content">
        <div class="checkout-grid">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Order Summary -->
                <div class="form-section order-summary-section">
                    <h5 class="section-title">Order Summary</h5>
                    
                    <div class="price-display text-center">
                        <h4 class="mb-2"><?php echo htmlspecialchars($user_data['account_name'] ?? 'Birthday Gold'); ?></h4>
                        <p class="text-muted mb-3"><?php echo ucfirst($user_data['account_type']); ?> Account</p>
                        
                        <?php if ($promo_code && isset($pricing['discount']) && $pricing['discount'] > 0): ?>
                            <div class="mb-1">
                                <span style="text-decoration: line-through;" class="text-muted h5">
                                    $<?php echo number_format($pricing['original_price'] / 100, 2); ?>
                                </span>
                            </div>
                            <h2 class="text-success mb-0">$<?php echo number_format($amount / 100, 2); ?></h2>
                            <small class="text-muted">per year</small>
                            <div class="promo-applied">
                                <i class="bi bi-tag-fill me-1"></i>
                                Promo: <strong><?php echo htmlspecialchars($promo_code); ?></strong>
                                <br>Savings: <strong>$<?php echo number_format($pricing['discount'] / 100, 2); ?></strong>
                            </div>
                        <?php else: ?>
                            <h2 class="text-success mb-0">$<?php echo number_format($amount / 100, 2); ?></h2>
                            <small class="text-muted">per year</small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Account Details Summary -->
                    <div class="order-details mt-3">
                        <h6 class="mb-2 fw-bold">Account Details</h6>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Name:</span>
                            <span><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></span>
                        </div>
                        <?php if (!empty($user_data['email'])): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Email:</span>
                            <span><?php echo htmlspecialchars($user_data['email']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($user_data['phone_number'])): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Phone:</span>
                            <span><?php echo htmlspecialchars($user_data['phone_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Birthday:</span>
                            <span><?php echo date('F j', strtotime($user_data['birthday'] ?? '')); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Trust Indicators - Separate section for mobile ordering -->
                <div class="form-section mt-3 trust-section">
                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-3 text-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-lock text-success me-1"></i>
                            <small class="text-muted">SSL Encrypted</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-award text-primary me-1"></i>
                            <small class="text-muted">Trusted by 10,000+</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-arrow-counterclockwise text-info me-1"></i>
                            <small class="text-muted">Cancel Anytime</small>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg" height="20" alt="Powered by Stripe" style="opacity: 0.4;">
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Payment Form -->
            <div>
                <div class="form-section">
                    <h5 class="section-title">Payment Information</h5>
                    
                    <form id="payment-form">
                        <div id="payment-element"></div>
                        <div id="error-message" class="alert alert-danger d-none mt-3"></div>
                        
                        <button type="submit" class="btn-primary-custom w-100 mt-3" id="submit-button">
                            <span id="button-text">
                                <i class="bi bi-lock-fill me-2"></i>Complete Purchase
                            </span>
                        </button>
                        
                        <div class="text-center mt-3 mb-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Your payment information is secure and encrypted
                            </small>
                        </div>
                        
                        <div class="text-center">
                            <a href="/claudecode/createaccount.php" class="back-link">
                                <i class="bi bi-arrow-left me-1"></i>Back to account details
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="text-center mt-4 pb-4">
        <small class="text-muted">
            By completing this purchase, you agree to our 
            <a href="/terms" class="text-decoration-none text-success fw-medium">Terms of Service</a> and 
            <a href="/privacy" class="text-decoration-none text-success fw-medium">Privacy Policy</a>
        </small>
    </footer>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('<?php echo $stripe_key; ?>');
const elements = stripe.elements({
    clientSecret: '<?php echo $payment_intent->client_secret; ?>',
    appearance: {
        theme: 'stripe',
        variables: {
            colorPrimary: '#28a745',
        }
    }
});

const paymentElement = elements.create('payment');
paymentElement.mount('#payment-element');

const form = document.getElementById('payment-form');
const submitButton = document.getElementById('submit-button');
const buttonText = document.getElementById('button-text');
const errorMessage = document.getElementById('error-message');

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Disable button
    submitButton.disabled = true;
    buttonText.innerHTML = '<span class="spinner"></span>Processing...';
    errorMessage.classList.add('d-none');
    
    console.log('[CHECKOUT] Starting payment confirmation');
    
    try {
        const {error} = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: window.location.origin + '/claudecode/checkout_complete.php?user_id=<?php echo $encoded_user_id; ?>',
            },
            redirect: 'if_required'
        });
        
        if (error) {
            // Show error
            console.error('[CHECKOUT] Stripe error:', error);
            errorMessage.textContent = error.message;
            errorMessage.classList.remove('d-none');
            submitButton.disabled = false;
            buttonText.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Complete Purchase';
        } else {
            // Payment succeeded without redirect
            console.log('[CHECKOUT] Payment confirmed, verifying with backend');
            buttonText.innerHTML = '<span class="spinner"></span>Verifying payment...';
            
            // Confirm with backend
            const formData = new FormData();
            formData.append('action', 'confirm_payment');
            formData.append('payment_intent_id', '<?php echo $payment_intent->id; ?>');
            formData.append('user_id', '<?php echo $user_id; ?>');
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const result = await response.json();
                console.log('[CHECKOUT] Backend response:', result);
                
                if (result.success) {
                    buttonText.innerHTML = '✓ Payment Successful!';
                    // Store success in session storage for the redirect page
                    sessionStorage.setItem('payment_success', 'true');
                    sessionStorage.setItem('account_type', '<?php echo $user_data['account_type']; ?>');
                    
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                } else {
                    errorMessage.textContent = result.error || 'Payment verification failed';
                    errorMessage.classList.remove('d-none');
                    submitButton.disabled = false;
                    buttonText.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Complete Purchase';
                }
            } catch (fetchError) {
                console.error('[CHECKOUT] Fetch error:', fetchError);
                // For network errors, start checking payment status
                errorMessage.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Verifying payment status... If your payment was processed, you will be redirected automatically.';
                errorMessage.classList.remove('d-none');
                errorMessage.classList.remove('alert-danger');
                errorMessage.classList.add('alert-warning');
                
                // Start heartbeat to check payment status
                startHeartbeat();
                
                // Keep button disabled during verification
                submitButton.disabled = true;
                buttonText.innerHTML = '<span class="spinner"></span>Verifying...';
            }
        }
    } catch (generalError) {
        console.error('[CHECKOUT] General error:', generalError);
        errorMessage.textContent = 'An unexpected error occurred. Please try again.';
        errorMessage.classList.remove('d-none');
        submitButton.disabled = false;
        buttonText.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Complete Purchase';
    }
});
</script>

<script>
// Add heartbeat to check payment status periodically
let heartbeatInterval;
let checkCount = 0;
const maxChecks = 20; // Check for up to 1 minute (every 3 seconds)

function startHeartbeat() {
    heartbeatInterval = setInterval(async () => {
        checkCount++;
        
        if (checkCount > maxChecks) {
            clearInterval(heartbeatInterval);
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'check_payment_status');
            formData.append('payment_intent_id', '<?php echo $payment_intent->id; ?>');
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const result = await response.json();
                console.log('[CHECKOUT] Heartbeat result:', result);
                
                if ((result.status === 'succeeded' || result.status === 'processing') && result.user_active) {
                    clearInterval(heartbeatInterval);
                    
                    // Update UI to show success
                    if (errorMessage && !errorMessage.classList.contains('d-none')) {
                        errorMessage.innerHTML = '<i class="bi bi-check-circle me-2"></i>Payment confirmed! Redirecting...';
                        errorMessage.classList.remove('alert-warning', 'alert-danger');
                        errorMessage.classList.add('alert-success');
                    }
                    
                    buttonText.innerHTML = '✓ Payment Successful!';
                    sessionStorage.setItem('payment_success', 'true');
                    
                    setTimeout(() => {
                        window.location.href = result.redirect || '/myaccount/';
                    }, 1000);
                }
            }
        } catch (e) {
            console.error('[CHECKOUT] Heartbeat error:', e);
        }
    }, 3000); // Check every 3 seconds
}

// Start heartbeat when page loads
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're returning from a redirect
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('payment_intent') || urlParams.get('payment_intent_client_secret')) {
        console.log('[CHECKOUT] Returned from redirect, starting heartbeat');
        startHeartbeat();
        
        // Show status message
        const errorMessage = document.getElementById('error-message');
        if (errorMessage) {
            errorMessage.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Verifying your payment... Please wait.';
            errorMessage.classList.remove('d-none', 'alert-danger');
            errorMessage.classList.add('alert-info');
        }
    }
});

// Stop heartbeat when leaving page
window.addEventListener('beforeunload', () => {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }
});
</script>

<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/v7/footer.inc');
?>