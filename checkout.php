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
                $database->query($sql, ['user_id' => $user_id]);
                error_log('[CHECKOUT_API] User status updated to active');
                
                // Log user in using proper account login method (matches free account flow)
                $account->login($user_id, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
                
                // Check if bg_transactions table exists before updating
                if (tableExists($database, 'bg_transactions')) {
                    // Update transaction
                    $sql = "UPDATE bg_transactions 
                            SET status = 'completed', stripe_payment_intent = :pi_id, completed_at = NOW()
                            WHERE user_id = :user_id AND status = 'pending'
                            ORDER BY created_at DESC LIMIT 1";
                    $database->query($sql, ['user_id' => $user_id, 'pi_id' => $payment_intent_id]);
                    error_log('[CHECKOUT_API] Transaction updated');
                } else {
                    error_log('[CHECKOUT_API] bg_transactions table does not exist, skipping transaction update');
                }
                
                // Track checkout completion using sessiontracking function
                $tracking_data = [
                    'action' => 'checkout_completed',
                    'payment_intent_id' => $payment_intent_id,
                    'amount' => $payment_intent->amount,
                    'status' => 'completed'
                ];
                session_tracking('checkout_complete', $tracking_data);
                error_log('[CHECKOUT_API] Checkout completion tracked in sessiontracking');
                
                // Create payment record if table exists
                if (tableExists($database, 'bg_payments')) {
                    try {
                        $sql = "INSERT INTO bg_payments 
                                (user_id, amount, stripe_payment_intent, status, payment_method, metadata, created_at) 
                                VALUES (:user_id, :amount, :payment_intent, 'completed', :payment_method, :metadata, NOW())";
                        
                        $database->query($sql, [
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
                $session->unset('signup_process_data');
                $session->unset('userregistrationdata');
                
                // Determine redirect based on account type
                $user_sql = "SELECT account_type FROM bg_users WHERE user_id = :user_id";
                $user_data = $database->getrow($user_sql, ['user_id' => $user_id]);
                
                // Store celebration data in session
                $session->set('celebration_data', [
                    'user_id' => $user_id,
                    'product_id' => $user_data['account_product_id'] ?? '',
                    'completed_at' => time()
                ]);
                
                // Redirect to celebration page
                $redirect_url = '/checkout_celebration.php';
                
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
                    $database->query($sql, ['user_id' => $user_id]);
                    
                    // Re-fetch user data
                    $user_data = $database->getrow("SELECT status, account_type FROM bg_users WHERE user_id = :user_id", ['user_id' => $user_id]);
                    
                    error_log('[CHECKOUT_API] User activated via heartbeat check');
                }
                
                // Store celebration data in session
                $session->set('celebration_data', [
                    'user_id' => $user_id,
                    'product_id' => $payment_intent->metadata->product_id ?? '',
                    'completed_at' => time()
                ]);
                
                // Determine redirect
                $redirect_url = '/checkout_celebration.php';
                
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

if ($user_data && !empty($user_data['account_product_id'])) {
    // First check if product exists and allows promos
    $product = $productManager->getProduct($user_data['account_product_id']);
    
    if ($promo_code && $product && (!isset($product['allow_promo']) || $product['allow_promo'] != 'yes')) {
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
# HANDLE FREE ACCOUNTS - No payment needed
#-------------------------------------------------------------------------------
// Check if this is a free account (amount is 0 or account_plan is 'free')
$is_free_account = false;
if ($amount == 0 || $amount === 0 || $user_data['account_plan'] == 'free') {
    $is_free_account = true;
    error_log('[CHECKOUT_API] Free account detected - amount: ' . $amount . ', plan: ' . $user_data['account_plan']);
}

if ($is_free_account) {
    error_log('[CHECKOUT_API] Processing free account activation for user: ' . $user_id);
    
    // Update user to active status
    $sql = "UPDATE bg_users SET status = 'active', modify_dt = NOW() WHERE user_id = :user_id";
    $database->query($sql, ['user_id' => $user_id]);
    
    // Log user in
    $account->login($user_id, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
    
    // Clear signup session data
    $session->unset('signup_process_data');
    $session->unset('userregistrationdata');
    
    // Redirect to welcome page
    error_log('[CHECKOUT_API] Free account activated, redirecting to welcome');
    header('Location: /myaccount/welcome');
    exit();
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
    // Create payment intent with additional metadata and explicit payment methods
    $payment_intent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'usd',
        'payment_method_types' => ['card', 'cashapp', 'link'],
        'metadata' => [
            'user_id' => $user_id,
            'account_type' => $user_data['account_type'],
            'product_id' => $user_data['account_product_id'] ?? '',
            'email' => $user_data['email'] ?? ''
        ],
        'description' => 'Birthday.Gold ' . ucfirst($user_data['account_type']) . ' Account'
    ]);
    
    error_log('[CHECKOUT_API] Payment intent created: ' . $payment_intent->id);
    
    // Track checkout session start using sessiontracking function
    $tracking_data = [
        'action' => 'checkout_started',
        'stripe_session_id' => $payment_intent->id,
        'user_id' => $user_id,
        'product_id' => $user_data['account_product_id'] ?? 0,
        'amount' => $amount,
        'promo_code' => $promo_code ?? '',
        'user_email' => $user_data['email'] ?? '',
        'account_type' => $user_data['account_type'],
        'status' => 'pending'
    ];
    session_tracking('checkout_start', $tracking_data);
    error_log('[CHECKOUT_API] Checkout session tracked in sessiontracking');
    
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
$page_title = 'Complete Your Purchase - Birthday.Gold';
$page_description = 'Complete your Birthday.Gold checkout';
$errormessage = '';

// Modern Minimalist CSS - Matching /login and /forgot style
$additionalstyles = '
<style>
/* Modern Minimalist Checkout Styles - Clean & Modern */
* {
    box-sizing: border-box !important;
}

/* Card Container */
.checkout-container {
    width: 100%;
    max-width: 480px;
    margin: 2rem auto;
}

.checkout-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

/* Header Section - Minimal */
.checkout-header {
    text-align: center;
    padding: 2rem 1.5rem 1rem;
}

.checkout-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.checkout-header p {
    font-size: 1rem;
    color: #6c757d;
    margin: 0;
}

/* Checkout Badge */
.checkout-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #e8f5e8;
    color: var(--bs-primary);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.checkout-badge i {
    font-size: 1rem;
}

/* Form Section */
.checkout-body {
    padding: 0 1.5rem 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* Price Display */
.price-display {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.price-original {
    text-decoration: line-through;
    color: #6c757d;
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}

.price-current {
    font-size: 2.5rem;
    font-weight: 700;
    color: #28a745;
    line-height: 1;
}

.price-current span {
    font-size: 1rem;
    font-weight: 400;
    color: #6c757d;
}

.price-savings {
    margin-top: 0.5rem;
    color: #28a745;
    font-size: 0.875rem;
    font-weight: 600;
}

/* Account Summary */
.account-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

/* Submit Button */
.btn-submit {
    font-weight: 600;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
    }
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    animation: none;
}

.btn-submit:active {
    transform: translateY(0);
}

.btn-submit:disabled {
    animation: none;
}

/* Divider - Subtle */
.divider {
    margin: 2rem 0;
    text-align: center;
    position: relative;
}

.divider::before {
    content: "";
    position: absolute;
    left: 20%;
    right: 20%;
    top: 50%;
    height: 1px;
    background: #e9ecef;
}

.divider span {
    background: white;
    padding: 0 0.75rem;
    position: relative;
    color: #adb5bd;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Alternative Actions */
.alt-actions {
    text-align: center;
    font-size: 0.875rem;
    color: #6c757d;
}

.alt-actions a {
    color: var(--bs-primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}

.alt-actions a:hover {
    color: #0b5ed7;
    text-decoration: underline;
}

/* Alert Messages */
.alert-container {
    margin-bottom: 1.5rem;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid transparent;
}

.alert-danger {
    background: #f8d7da;
    color: #842029;
    border-color: #f5c2c7;
}

/* Loading State */
.btn-submit.loading {
    pointer-events: none;
}

.btn-submit.loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    margin: auto;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    border: 2px solid transparent;
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

.btn-submit.loading span {
    opacity: 0;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Order Summary */
.order-summary {
    margin-top: 2rem;
}

/* Value Proposition */
.value-proposition {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.value-proposition h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 1.5rem;
}

.benefit-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.benefit-item i {
    font-size: 1.5rem;
    color: var(--bs-primary);
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.benefit-item div {
    flex: 1;
}

.benefit-item strong {
    display: block;
    font-size: 0.9375rem;
    color: #212529;
    margin-bottom: 0.25rem;
}

.benefit-item span {
    font-size: 0.8125rem;
    color: #6c757d;
    line-height: 1.4;
}

/* Price Summary */
.price-summary {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
}

.price-row:last-child {
    margin-bottom: 0;
}

.price-row.promo {
    color: #28a745;
    padding-bottom: 0.75rem;
}

.price-row.total {
    border-top: 2px solid #dee2e6;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
    font-weight: 600;
}

.price-original {
    text-decoration: line-through;
    color: #6c757d;
}

.price-total {
    font-size: 1.25rem;
    color: #212529;
}

/* Account Info */
.account-info {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    display: flex;
    gap: 1rem;
    align-items: center;
}

.info-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}

.info-details {
    display: flex;
    flex-direction: column;
}

.info-details strong {
    font-size: 0.9375rem;
    color: #212529;
}

.info-details span {
    font-size: 0.8125rem;
    color: #6c757d;
}

/* Security Info - Left Side */
.security-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding: 0;
}

.security-info i {
    font-size: 1.75rem;
    color: #28a745;
    flex-shrink: 0;
}

.security-info p {
    margin: 0;
    font-size: 0.6875rem;
    color: #6c757d;
    line-height: 1.2;
    font-weight: 400;
}

/* Payment Element Styling */
#payment-element {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    min-height: 200px;
}

#payment-element:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* Skeleton Loading Animation */
.payment-skeleton {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    min-height: 200px;
}

.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

.skeleton-line {
    height: 20px;
    border-radius: 4px;
    margin-bottom: 12px;
}

.skeleton-line.short {
    width: 60%;
}

.skeleton-line.medium {
    width: 80%;
}

.skeleton-line.long {
    width: 100%;
}

.skeleton-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}

.skeleton-tab {
    height: 40px;
    width: 80px;
    border-radius: 6px;
}

.skeleton-input {
    height: 44px;
    border-radius: 6px;
    margin-bottom: 16px;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* Tablet & Desktop Styles */
@media (min-width: 768px) {
    .checkout-container {
        max-width: 480px;
        margin: 3rem auto;
    }
    
    .checkout-header {
        padding: 3rem 2rem 1.5rem;
    }
    
    .checkout-header h1 {
        font-size: 2rem;
    }
    
    .checkout-body {
        padding: 0 2rem 3rem;
    }
}

/* Large Desktop - Enhanced Layout */
@media (min-width: 992px) {
    .checkout-wrapper {
        width: 100%;
        max-width: 1200px;
        display: grid;
        grid-template-columns: 1fr 480px;
        gap: 4rem;
        align-items: start;
        padding: 0 2rem;
    }
    
    /* Welcome content for desktop */
    .welcome-content {
        color: #212529;
        margin-top: -2rem;
        padding-top: 1rem;
    }
    
    .welcome-content h2 {
        font-size: 2.25rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        line-height: 1.2;
    }
    
    .welcome-content h2 span {
        color: var(--bs-primary);
    }
    
    .welcome-content p.lead {
        font-size: 1.125rem;
        color: #495057;
        font-weight: 400;
        line-height: 1.5;
    }
    
    .order-context {
        margin-top: 2.5rem;
    }
    
    .context-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.75rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .context-card h4 {
        color: #212529;
        font-weight: 600;
        margin-bottom: 1rem;
    }
    
    .context-card p {
        font-size: 0.9375rem;
        line-height: 1.6;
        margin: 0;
    }
    
    .price-context {
        display: flex;
        align-items: baseline;
        padding-top: 0.5rem;
    }
    
    .price-context .h3 {
        font-weight: 700;
        margin: 0;
    }
    
    .trust-signals {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .trust-signals p {
        display: flex;
        align-items: flex-start;
        line-height: 1.5;
    }
    
    .trust-signals i {
        margin-top: 0.125rem;
        font-size: 1.125rem;
    }
    
    .checkout-container {
        margin: 0;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
}

@media (min-width: 1200px) {
    .checkout-wrapper {
        gap: 6rem;
    }
    
    .welcome-content h2 {
        font-size: 3rem;
    }
}

/* Order Details Card */
.order-details-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-top: 2rem;
}

.order-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-header h4 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.order-body {
    padding: 1.5rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.order-item:last-child {
    margin-bottom: 0;
}

.order-item-label {
    color: #6c757d;
    font-size: 0.875rem;
}

.order-item-value {
    text-align: right;
    font-weight: 500;
}

.order-total {
    border-top: 2px solid #e9ecef;
    padding-top: 1rem;
    margin-top: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-footer {
    background: #f8f9fa;
    padding: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.feature-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.feature-list-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #495057;
}

.feature-list-item i {
    font-size: 1rem;
}

/* Mobile Order Summary */
.mobile-order-summary {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.mobile-summary-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
}

.mobile-summary-header h5 {
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
}

.mobile-summary-header p {
    font-size: 0.8125rem;
}

.mobile-summary-price {
    text-align: right;
}

.price-label {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.125rem;
}

.price-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--bs-primary);
}

@media (max-width: 575px) {
    .mobile-summary-card {
        padding: 1rem 1.25rem;
    }
    
    .price-amount {
        font-size: 1.25rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content">
    <!-- Desktop wrapper for side-by-side layout -->
    <div class="checkout-wrapper">
        <!-- Welcome content - Desktop only -->
        <div class="welcome-content d-none d-lg-block">
            <?php
            // Fun encouraging phrases for the final step
            $final_step_phrases = [
                "Almost there, %s!",
                "%s, you're on the home stretch!",
                "Last step, %s!",
                "Let's finish this, %s!",
                "%s, just one more click!",
                "Final step, %s!",
                "You're so close, %s!",
                "%s, let's wrap this up!",
                "One more step, %s!",
                "%s, time to celebrate soon!"
            ];
            
            // Pick a random phrase
            $phrase = $final_step_phrases[array_rand($final_step_phrases)];
            $heading = sprintf($phrase, '<span>' . htmlspecialchars($user_data['first_name']) . '</span>');
            ?>
            <h2><?php echo $heading; ?></h2>
            <p class="lead mb-2">You're moments away from never missing another birthday reward!</p>
            
            <div class="order-context">
                <div class="context-card mb-3">
                    <h4 class="h5 mb-3">Your <?php echo htmlspecialchars($user_data['name'] ?? 'Birthday.Gold'); ?> membership includes:</h4>
                    <?php
                    // Dynamic description based on account type
                    $account_descriptions = [
                        'individual' => 'Automatic enrollment in birthday programs from your favorite brands, personalized reminders so you never miss a reward, and exclusive member perks throughout the year.',
                        'parental' => 'Manage birthday rewards for your entire family in one place. Track multiple birthdays, get organized reminders, and ensure no one misses their special rewards.',
                        'giftcertificate' => 'The perfect gift! Give someone special the joy of never missing their birthday rewards with this thoughtful certificate.',
                        'business' => 'Professional birthday reward management for your team. Track employee birthdays, automate reward enrollment, and boost workplace morale.'
                    ];
                    
                    $description = $account_descriptions[$user_data['account_type']] ?? $account_descriptions['individual'];
                    
                    // Get billing cycle from product data
                    $billing_text = 'one-time payment'; // default
                    
                    if (!empty($user_data['billing_cycle'])) {
                        switch($user_data['billing_cycle']) {
                            case 'monthly':
                                $billing_text = 'per month';
                                break;
                            case 'yearly':
                                $billing_text = 'per year';
                                break;
                            case 'one-time':
                            case 'one_time':
                                $billing_text = 'one-time payment';
                                break;
                            default:
                                // For any unrecognized value, default to one-time
                                $billing_text = 'one-time payment';
                                break;
                        }
                    }
                    ?>
                    <p class="text-muted mb-2"><?php echo $description; ?></p>
                    <div class="price-context mt-3">
                        <span class="h3 text-primary">$<?php echo number_format($amount / 100, 2); ?></span>
                        <span class="text-muted ms-2"><?php echo $billing_text; ?></span>
                    </div>
                </div>
                
                <div class="trust-signals">
                    <p class="small text-muted mb-3">
                        <i class="bi bi-shield-check text-success me-2"></i>
                        Your payment is secured with bank-level encryption and processed by Stripe, the same trusted platform used by millions of businesses worldwide.
                    </p>
                    <p class="small text-muted mb-0">
                        <i class="bi bi-person-check text-primary me-2"></i>
                        Billing as <strong><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></strong> • <?php echo ucfirst($user_data['account_type']); ?> account
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Checkout Card -->
        <div class="checkout-container">
            <!-- Mobile Order Summary - Only visible on mobile -->
            <div class="mobile-order-summary d-lg-none mb-3">
                <div class="mobile-summary-card">
                    <div class="mobile-summary-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($user_data['name'] ?? 'Birthday.Gold'); ?></h5>
                        <p class="mb-0 text-muted small"><?php echo ucfirst($user_data['account_type']); ?> Plan</p>
                    </div>
                    <div class="mobile-summary-price">
                        <span class="price-label">Total:</span>
                        <span class="price-amount">$<?php echo number_format($amount / 100, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="checkout-card">
                <!-- Header Section -->
                <div class="checkout-header">
                    <div class="checkout-badge">
                        <i class="bi bi-credit-card"></i>
                        <span>Secure Checkout</span>
                    </div>
                    <h1>Payment Information</h1>
                    <p>Enter your payment details below</p>
                </div>
                
                <!-- Form Section -->
                <div class="checkout-body">
                    <?php if (!empty($errormessage)): ?>
                        <div class="alert-container">
                            <?php echo $errormessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="/checkout" id="checkoutForm">
                        <?php echo $display->inputcsrf_token(); ?>
                        
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>

                            <!-- Skeleton loader shown initially -->
                            <div id="payment-skeleton" class="payment-skeleton">
                                <div class="skeleton-tabs">
                                    <div class="skeleton skeleton-tab"></div>
                                    <div class="skeleton skeleton-tab"></div>
                                    <div class="skeleton skeleton-tab"></div>
                                </div>
                                <div class="skeleton skeleton-input skeleton-line long"></div>
                                <div class="skeleton skeleton-input skeleton-line medium"></div>
                                <div class="skeleton skeleton-line short"></div>
                            </div>

                            <!-- Actual payment element (hidden initially) -->
                            <div id="payment-element" style="display: none;"></div>
                        </div>
                        
                        <div id="error-message" class="alert alert-danger d-none mt-3"></div>
                        
                        <button type="submit" class="btn btn-success btn-lg w-100 btn-submit" id="submitBtn">
                            <span>Complete Payment</span>
                        </button>
                        
                    </form>
                    
                    <!-- Divider -->
                    <div class="divider">
                        <span>need help?</span>
                    </div>
                    
                    <!-- Alternative Actions -->
                    <div class="alt-actions">
                        <a href="/createaccount">← Back to account details</a>
                        <br>
                        Questions? <a href="/contact">Contact support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Ensure payment intent exists before generating JavaScript
if (!isset($payment_intent) || !$payment_intent) {
    error_log('[CHECKOUT] Payment intent not set when generating JavaScript');
    $footerattribute['postfooter'] = '<script>console.error("[CHECKOUT] Payment system not properly initialized");</script>';
} else {
    $footerattribute['postfooter'] = '
<link rel="preconnect" href="https://js.stripe.com">
<link rel="dns-prefetch" href="https://api.stripe.com">
<script src="https://js.stripe.com/v3/"></script>
<script>
// Check if Stripe is loaded
if (typeof Stripe === \'undefined\') {
    console.error(\'[CHECKOUT] Stripe.js not loaded!\');
    document.getElementById(\'error-message\').textContent = \'Payment system not loaded. Please refresh the page.\';
    document.getElementById(\'error-message\').classList.remove(\'d-none\');
    throw new Error(\'Stripe.js not loaded\');
}

const stripe = Stripe(\'' . $stripe_key . '\');
console.log(\'[CHECKOUT] Stripe initialized with key:\', \'' . substr($stripe_key, 0, 10) . '...\');

// Immediately start creating elements to reduce loading time
const elements = stripe.elements({
    clientSecret: \'' . $payment_intent->client_secret . '\',
    appearance: {
        theme: \'stripe\',
        variables: {
            colorPrimary: \'#0d6efd\',
            colorBackground: \'#ffffff\',
            colorText: \'#30313d\',
            colorDanger: \'#df1b41\',
            fontFamily: \'system-ui, sans-serif\',
            spacingUnit: \'4px\',
            borderRadius: \'8px\',
            fontSizeBase: \'16px\'
        }
    }
});

// Use payment element with tabs layout and optimized settings
const paymentElement = elements.create(\'payment\', {
    layout: \'tabs\',
    business: {
        name: \'Birthday.Gold\'
    },
    defaultValues: {
        billingDetails: {
            name: \'' . addslashes($user_data['first_name'] . ' ' . $user_data['last_name']) . '\',
            email: \'' . addslashes($user_data['email'] ?? '') . '\',
            address: {
                country: \'US\'
            }
        }
    },
    fields: {
        billingDetails: {
            address: {
                country: \'never\'
            }
        }
    }
});

// Mount immediately for fastest loading
console.log(\'[CHECKOUT] Mounting payment element immediately...\');
paymentElement.mount(\'#payment-element\');

// Track selected payment method and update button text
let selectedPaymentMethod = \'card\';

// Listen for payment method changes
paymentElement.on(\'change\', (event) => {
    console.log(\'[CHECKOUT] Payment method change event:\', event);
    const submitButton = document.getElementById(\'submitBtn\');
    const buttonText = submitButton.querySelector(\'span\');

    // Check if Cash App is selected
    if (event.value && event.value.type === \'cashapp\') {
        selectedPaymentMethod = \'cashapp\';
        buttonText.textContent = \'Show QR Code to Pay\';
    } else {
        selectedPaymentMethod = event.value ? event.value.type : \'card\';
        buttonText.textContent = \'Complete Payment\';
    }
});

// Add event listener for when the payment element is ready
paymentElement.on(\'ready\', () => {
    console.log(\'[CHECKOUT] Payment element is ready\');
    // Hide skeleton and show actual payment element once loaded
    const skeleton = document.getElementById(\'payment-skeleton\');
    const paymentEl = document.getElementById(\'payment-element\');

    if (skeleton) skeleton.style.display = \'none\';
    if (paymentEl) paymentEl.style.display = \'block\';

    console.log(\'[CHECKOUT] Payment element displayed, skeleton hidden\');
});

// Handle loading state changes
paymentElement.on(\'loaderstart\', () => {
    console.log(\'[CHECKOUT] Payment element loader started\');
});

paymentElement.on(\'loaderend\', () => {
    console.log(\'[CHECKOUT] Payment element loader ended\');
});

// Fallback timeout in case the ready event doesn\'t fire
setTimeout(() => {
    const skeleton = document.getElementById(\'payment-skeleton\');
    const paymentEl = document.getElementById(\'payment-element\');

    if (skeleton && skeleton.style.display !== \'none\') {
        console.log(\'[CHECKOUT] Fallback: Hiding skeleton after 3 seconds\');
        skeleton.style.display = \'none\';
        if (paymentEl) paymentEl.style.display = \'block\';
    }
}, 3000);

const form = document.getElementById(\'checkoutForm\');
const submitButton = document.getElementById(\'submitBtn\');
const errorMessage = document.getElementById(\'error-message\');

form.addEventListener(\'submit\', async (e) => {
    e.preventDefault();
    
    // Add loading state
    submitButton.classList.add(\'loading\');
    submitButton.disabled = true;
    errorMessage.classList.add(\'d-none\');
    
    console.log(\'[CHECKOUT] Starting payment confirmation\');
    console.log(\'[CHECKOUT] Stripe object:\', typeof stripe, stripe);
    console.log(\'[CHECKOUT] Elements object:\', typeof elements, elements);

    try {
        const {error} = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: window.location.origin + \'/checkout_complete.php?user_id=' . $encoded_user_id . '\',
                payment_method_data: {
                    billing_details: {
                        name: \'' . addslashes($user_data['first_name'] . ' ' . $user_data['last_name']) . '\',
                        email: \'' . addslashes($user_data['email'] ?? '') . '\',
                        address: {
                            country: \'US\'
                        }
                    }
                }
            },
            redirect: \'if_required\'
        });
        
        if (error) {
            // Show error
            console.error(\'[CHECKOUT] Stripe error:\', error);
            errorMessage.textContent = error.message;
            errorMessage.classList.remove(\'d-none\');
            submitButton.classList.remove(\'loading\');
            submitButton.disabled = false;
        } else {
            // Payment succeeded without redirect
            console.log(\'[CHECKOUT] Payment confirmed, verifying with backend\');
            
            // Confirm with backend
            const formData = new FormData();
            formData.append(\'action\', \'confirm_payment\');
            formData.append(\'payment_intent_id\', \'' . $payment_intent->id . '\');
            formData.append(\'user_id\', \'' . $user_id . '\');
            
            try {
                const response = await fetch(window.location.href, {
                    method: \'POST\',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(\'Network response was not ok\');
                }
                
                const responseText = await response.text();
                console.log(\'[CHECKOUT] Raw response:\', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                    console.log(\'[CHECKOUT] Backend response:\', result);
                } catch (parseError) {
                    console.error(\'[CHECKOUT] JSON parse error:\', parseError);
                    console.error(\'[CHECKOUT] Response was:\', responseText);
                    throw new Error(\'Invalid JSON response from server\');
                }
                
                if (result.success) {
                    submitButton.classList.remove(\'loading\');
                    submitButton.textContent = \'✓ Payment Successful!\';
                    // Store success in session storage for the redirect page
                    sessionStorage.setItem(\'payment_success\', \'true\');
                    sessionStorage.setItem(\'account_type\', \'' . $user_data['account_type'] . '\');
                    
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                } else {
                    errorMessage.textContent = result.error || \'Payment verification failed\';
                    errorMessage.classList.remove(\'d-none\');
                    submitButton.classList.remove(\'loading\');
                    submitButton.disabled = false;
                }
            } catch (fetchError) {
                console.error(\'[CHECKOUT] Fetch error:\', fetchError);
                // For network errors, start checking payment status
                errorMessage.innerHTML = \'<i class="bi bi-exclamation-triangle me-2"></i>Verifying payment status... If your payment was processed, you will be redirected automatically.\';
                errorMessage.classList.remove(\'d-none\');
                errorMessage.classList.remove(\'alert-danger\');
                errorMessage.classList.add(\'alert-warning\');
                
                // Start heartbeat to check payment status
                startHeartbeat();
                
                // Keep button disabled during verification
                submitButton.disabled = true;
            }
        }
    } catch (generalError) {
        console.error(\'[CHECKOUT] General error:\', generalError);
        errorMessage.textContent = \'An unexpected error occurred. Please try again.\';
        errorMessage.classList.remove(\'d-none\');
        submitButton.classList.remove(\'loading\');
        submitButton.disabled = false;
    }
});

// Add heartbeat to check payment status periodically
let heartbeatInterval;
let checkCount = 0;
const maxChecks = 60; // Check for up to 30 seconds (every 500ms)

function startHeartbeat() {
    heartbeatInterval = setInterval(async () => {
        checkCount++;
        
        if (checkCount > maxChecks) {
            clearInterval(heartbeatInterval);
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append(\'action\', \'check_payment_status\');
            formData.append(\'payment_intent_id\', \'' . $payment_intent->id . '\');
            
            const response = await fetch(window.location.href, {
                method: \'POST\',
                body: formData
            });
            
            if (response.ok) {
                const responseText = await response.text();
                console.log(\'[CHECKOUT] Heartbeat raw response:\', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                    console.log(\'[CHECKOUT] Heartbeat result:\', result);
                } catch (parseError) {
                    console.error(\'[CHECKOUT] Heartbeat JSON parse error:\', parseError);
                    console.error(\'[CHECKOUT] Heartbeat response was:\', responseText);
                    return; // Skip this heartbeat check
                }
                
                if ((result.status === \'succeeded\' || result.status === \'processing\') && result.user_active) {
                    clearInterval(heartbeatInterval);
                    
                    // Update UI to show success
                    const errorMsg = document.getElementById(\'error-message\');
                    if (errorMsg && !errorMsg.classList.contains(\'d-none\')) {
                        errorMsg.innerHTML = \'<i class="bi bi-check-circle me-2"></i>Payment confirmed! Redirecting...\'
                        errorMsg.classList.remove(\'alert-warning\', \'alert-danger\');
                        errorMsg.classList.add(\'alert-success\');
                    }
                    
                    submitButton.classList.remove(\'loading\');
                    submitButton.textContent = \'✓ Payment Successful!\';
                    sessionStorage.setItem(\'payment_success\', \'true\');
                    
                    setTimeout(() => {
                        window.location.href = result.redirect || \'/myaccount/\';
                    }, 1000);
                }
            }
        } catch (e) {
            console.error(\'[CHECKOUT] Heartbeat error:\', e);
        }
    }, 500); // Check every 500ms for faster response
}

// Start heartbeat when page loads
document.addEventListener(\'DOMContentLoaded\', () => {
    // Check if we\'re returning from a redirect
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get(\'payment_intent\') || urlParams.get(\'payment_intent_client_secret\')) {
        console.log(\'[CHECKOUT] Returned from redirect, starting heartbeat\');
        startHeartbeat();
        
        // Show status message
        const errorMessage = document.getElementById(\'error-message\');
        if (errorMessage) {
            errorMessage.innerHTML = \'<i class="bi bi-hourglass-split me-2"></i>Verifying your payment... Please wait.\';
            errorMessage.classList.remove(\'d-none\', \'alert-danger\');
            errorMessage.classList.add(\'alert-info\');
        }
    }
});

// Stop heartbeat when leaving page
window.addEventListener(\'beforeunload\', () => {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }
});
</script>
';
}  // End of else block for payment intent check

?>

<?php
echo $display->submitbuttoncolorjs('checkoutForm');
$display_footertype='mobilenonemin';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();