<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Enable error logging
error_log('[CHECKOUT_COMPLETE] Script started');

#-------------------------------------------------------------------------------
# HELPER FUNCTION TO CHECK IF TABLE EXISTS
#-------------------------------------------------------------------------------
function tableExists($database, $tableName) {
    try {
        $sql = "SHOW TABLES LIKE :table";
        $result = $database->getrow($sql, ['table' => $tableName]);
        return !empty($result);
    } catch (Exception $e) {
        error_log('[CHECKOUT_COMPLETE] Error checking table existence: ' . $e->getMessage());
        return false;
    }
}

#-------------------------------------------------------------------------------
# GET PAYMENT DETAILS
#-------------------------------------------------------------------------------
$encoded_user_id = $_GET['user_id'] ?? '';
$payment_intent_id = $_GET['payment_intent'] ?? '';
$payment_intent_client_secret = $_GET['payment_intent_client_secret'] ?? '';

error_log('[CHECKOUT_COMPLETE] Parameters - User: ' . $encoded_user_id . ', PI: ' . $payment_intent_id);

if (empty($encoded_user_id)) {
    error_log('[CHECKOUT_COMPLETE] No user ID provided');
    header('Location: /signup.php');
    exit();
}

try {
    $user_id = $qik->decodeId($encoded_user_id);
    error_log('[CHECKOUT_COMPLETE] Decoded user ID: ' . $user_id);
} catch (Exception $e) {
    error_log('[CHECKOUT_COMPLETE] Failed to decode user ID: ' . $e->getMessage());
    header('Location: /signup.php');
    exit();
}

#-------------------------------------------------------------------------------
# VERIFY PAYMENT WITH STRIPE
#-------------------------------------------------------------------------------
// Load Composer autoloader for Stripe
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

// Get Stripe configuration from site settings
$STRIPECONFIG = $sitesettings['paymentgateway-stripe-live'] ?? [];
$stripe_secret = $STRIPECONFIG['STRIPE_SECRET'] ?? '';

if (empty($stripe_secret)) {
    error_log('[CHECKOUT_COMPLETE] Missing Stripe secret key');
    die('Payment configuration error. Please contact support.');
}

\Stripe\Stripe::setApiKey($stripe_secret);

$payment_success = false;
$error_message = '';
$payment_details = null;

try {
    if ($payment_intent_id) {
        // Retrieve by payment intent ID
        $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
    } elseif ($payment_intent_client_secret) {
        // Retrieve by client secret (from return_url)
        // Extract payment intent ID from client secret
        $parts = explode('_secret_', $payment_intent_client_secret);
        $payment_intent_id = $parts[0];
        $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
    } else {
        throw new Exception('No payment information provided');
    }
    
    if ($payment_intent->status === 'succeeded') {
        $payment_success = true;
        $payment_details = $payment_intent;
        
        error_log('[CHECKOUT_COMPLETE] Payment succeeded for intent: ' . $payment_intent->id);
        
        // Update user status to active
        if ($user_id) {
            $sql = "UPDATE bg_users 
                    SET status = 'active', 
                        modify_dt = NOW()
                    WHERE user_id = :user_id";
            
            $database->execute($sql, ['user_id' => $user_id]);
            error_log('[CHECKOUT_COMPLETE] User activated: ' . $user_id);
            
            // Update transaction record if table exists
            if (tableExists($database, 'bg_transactions')) {
                try {
                    $sql = "UPDATE bg_transactions 
                            SET status = 'completed',
                                stripe_payment_intent = :payment_intent_id,
                                stripe_amount = :amount,
                                completed_at = NOW()
                            WHERE user_id = :user_id 
                            AND status = 'pending'
                            ORDER BY created_at DESC
                            LIMIT 1";
                    
                    $database->execute($sql, [
                        'user_id' => $user_id,
                        'payment_intent_id' => $payment_intent->id,
                        'amount' => $payment_intent->amount
                    ]);
                    error_log('[CHECKOUT_COMPLETE] Transaction updated');
                } catch (Exception $e) {
                    error_log('[CHECKOUT_COMPLETE] Failed to update transaction: ' . $e->getMessage());
                }
            }
            
            // Update checkout session if table exists
            if (tableExists($database, 'bg_checkout_sessions')) {
                try {
                    $sql = "UPDATE bg_checkout_sessions 
                            SET status = 'completed',
                                completed_at = NOW()
                            WHERE user_id = :user_id 
                            AND stripe_session_id = :stripe_session_id";
                    
                    $database->execute($sql, [
                        'user_id' => $user_id,
                        'stripe_session_id' => $payment_intent->id
                    ]);
                    error_log('[CHECKOUT_COMPLETE] Checkout session updated');
                } catch (Exception $e) {
                    error_log('[CHECKOUT_COMPLETE] Failed to update checkout session: ' . $e->getMessage());
                }
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
                    error_log('[CHECKOUT_COMPLETE] Payment record created');
                } catch (Exception $e) {
                    // Payment record failed but user is activated
                    error_log('[CHECKOUT_COMPLETE] Failed to create payment record: ' . $e->getMessage());
                }
            }
            
            // Get user account type for proper redirect
            $user_sql = "SELECT account_type FROM bg_users WHERE user_id = :user_id";
            $user_data = $database->getrow($user_sql, ['user_id' => $user_id]);
            $account_type = $user_data['account_type'] ?? 'individual';
            
            // Log user in
            $session->set('user_id', $user_id);
            $session->set('logged_in', true);
            $session->set('account_type', $account_type);
            
            error_log('[CHECKOUT_COMPLETE] User logged in with account type: ' . $account_type);
            
            // Clear signup session data
            $session->delete('signup_process_data');
            $session->delete('userregistrationdata');
            $session->delete('accountcode');
        }
    } else {
        $error_message = "Payment verification failed. Status: " . $payment_intent->status;
    }
    
} catch (Exception $e) {
    $error_message = "Unable to verify payment. Please contact support.";
    error_log("Payment verification error: " . $e->getMessage());
}

#-------------------------------------------------------------------------------
# PAGE DISPLAY
#-------------------------------------------------------------------------------
$pagetitle = $payment_success ? 'Welcome to Birthday Gold!' : 'Payment Processing';
include($_SERVER['DOCUMENT_ROOT'].'/core/v7/header.inc');
?>

<style>
.success-container {
    max-width: 600px;
    margin: 60px auto;
    text-align: center;
}

.success-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 30px;
}

.checkmark {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: block;
    stroke-width: 2;
    stroke: #4bb71b;
    stroke-miterlimit: 10;
    box-shadow: inset 0px 0px 0px #4bb71b;
    animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
}

.checkmark__circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 2;
    stroke-miterlimit: 10;
    stroke: #4bb71b;
    fill: #fff;
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark__check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
}

@keyframes stroke {
    100% {
        stroke-dashoffset: 0;
    }
}

@keyframes scale {
    0%, 100% {
        transform: none;
    }
    50% {
        transform: scale3d(1.1, 1.1, 1);
    }
}

@keyframes fill {
    100% {
        box-shadow: inset 0px 0px 0px 60px #4bb71b;
    }
}

.error-icon {
    font-size: 72px;
    color: #dc3545;
    margin-bottom: 20px;
}

.next-steps {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 30px;
    margin-top: 30px;
    text-align: left;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
    flex-wrap: wrap;
}

.action-buttons .btn {
    min-width: 200px;
}
</style>

<div class="success-container">
    <?php if ($payment_success): ?>
        <div class="success-icon">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>
        
        <h1>Payment Successful!</h1>
        <p class="lead">Welcome to Birthday Gold - your account is now active.</p>
        
        <?php if ($payment_details): ?>
        <p class="text-muted">
            Amount paid: $<?php echo number_format($payment_details->amount / 100, 2); ?><br>
            <small>Transaction ID: <?php echo htmlspecialchars($payment_details->id); ?></small>
        </p>
        <?php endif; ?>
        
        <div class="next-steps">
            <h4>What happens next?</h4>
            <ol>
                <li><strong>Immediate Access:</strong> You can start using your Birthday Gold account right away.</li>
                <li><strong>Enrollment Process:</strong> We'll begin enrolling you in birthday reward programs from top brands.</li>
                <li><strong>Email Confirmations:</strong> You'll receive an email for each successful enrollment.</li>
                <li><strong>Birthday Rewards:</strong> Get ready to receive amazing birthday deals and freebies!</li>
            </ol>
        </div>
        
        <div class="action-buttons">
            <?php 
            // Determine dashboard URL based on account type
            $dashboard_url = '/myaccount/';
            if (isset($account_type) && $account_type === 'parental') {
                $dashboard_url = '/myaccount/parental-mode.php';
            }
            ?>
            <a href="<?php echo $dashboard_url; ?>" class="btn btn-success btn-lg">
                <i class="bi bi-speedometer2"></i> Go to Dashboard
            </a>
            <a href="/myaccount/enrollment.php" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-gift"></i> View Enrollments
            </a>
        </div>
        
    <?php else: ?>
        <div class="error-icon">
            <i class="bi bi-exclamation-circle-fill"></i>
        </div>
        
        <h1>Payment Processing</h1>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        
        <p>If you completed your payment, please wait a moment and refresh this page.</p>
        <p>If the problem persists, please contact our support team.</p>
        
        <div class="action-buttons">
            <button onclick="location.reload()" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i> Refresh Page
            </button>
            <a href="/contact" class="btn btn-outline-primary">
                <i class="bi bi-envelope"></i> Contact Support
            </a>
        </div>
        
        <?php if ($payment_intent_id): ?>
        <p class="text-muted mt-4">
            <small>Reference: <?php echo htmlspecialchars($payment_intent_id); ?></small>
        </p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
<?php if (!$payment_success && $payment_intent_id): ?>
// Auto-refresh page after 3 seconds if payment is still processing
setTimeout(() => {
    console.log('[CHECKOUT_COMPLETE] Auto-refreshing to check payment status...');
    location.reload();
}, 3000);
<?php endif; ?>

// Check session storage for payment success
document.addEventListener('DOMContentLoaded', () => {
    const paymentSuccess = sessionStorage.getItem('payment_success');
    if (paymentSuccess === 'true') {
        sessionStorage.removeItem('payment_success');
        sessionStorage.removeItem('account_type');
        console.log('[CHECKOUT_COMPLETE] Payment success confirmed from session');
    }
});
</script>

<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/v7/footer.inc');
?>