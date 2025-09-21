<?php
/**
 * Upgrade Process Page
 * Handles the return from Stripe and processes the upgrade
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.upgrademanager.php');

// Check if user is logged in
if (!$account->isactive()) {
    header('Location: /login');
    exit();
}

// Get parameters
$upgradeSessionId = $_GET['session'] ?? '';
$paymentIntent = $_GET['payment_intent'] ?? '';
$paymentIntentClientSecret = $_GET['payment_intent_client_secret'] ?? '';
$redirectStatus = $_GET['redirect_status'] ?? '';

// Get upgrade session
$upgradeSession = $session->get('upgrade_session');

if (!$upgradeSession || $upgradeSession['upgrade_session_id'] !== $upgradeSessionId) {
    $errormessage = 'Invalid upgrade session';
    $session->set('errormessage', $errormessage);
    header('Location: /myaccount/billing');
    exit();
}

// Check if already processed (prevent double processing)
if ($upgradeSession['status'] === 'completed') {
    header('Location: /myaccount/upgrade-complete?id=' . $upgradeSession['upgrade_session_id']);
    exit();
}

// Initialize Stripe
require_once($_SERVER['DOCUMENT_ROOT'] . '/../ENV_CONFIGS/vendor/autoload.php');
\Stripe\Stripe::setApiKey($STRIPECONFIG['stripe_secret_key']);

// Initialize managers
$productManager = new ProductManager($database, $qik);
$upgradeManager = new UpgradeManager($database, $account, $productManager, $session, $qik);

// Process based on redirect status
if ($redirectStatus === 'succeeded' && $paymentIntent) {
    try {
        // Retrieve the payment intent from Stripe
        $pi = \Stripe\PaymentIntent::retrieve($paymentIntent);
        
        // Verify it matches our session
        if ($pi->metadata->upgrade_session_id !== $upgradeSessionId) {
            throw new Exception('Payment intent mismatch');
        }
        
        // Verify payment was successful
        if ($pi->status !== 'succeeded') {
            throw new Exception('Payment not completed');
        }
        
        // Process the upgrade
        $result = $upgradeManager->processUpgrade($upgradeSessionId, $paymentIntent);
        
        if ($result['success']) {
            // Clear the session to prevent reprocessing
            $session->unset('upgrade_session');
            
            // Redirect to success page
            header('Location: /myaccount/upgrade-complete?id=' . $result['confirmation_id']);
            exit();
        } else {
            throw new Exception($result['error']);
        }
        
    } catch (Exception $e) {
        $errormessage = 'Failed to process upgrade: ' . $e->getMessage();
        
        // Log the error
        $sql = "INSERT INTO bg_errors 
                (user_id, error_type, error_message, error_data, created_at)
                VALUES
                (:user_id, 'upgrade_processing_error', :message, :data, NOW())";
        
        $database->query($sql, [
            'user_id' => $account->getUserId(),
            'message' => $e->getMessage(),
            'data' => json_encode([
                'upgrade_session_id' => $upgradeSessionId,
                'payment_intent' => $paymentIntent
            ])
        ]);
    }
} else {
    // Payment was not successful
    $errormessage = 'Payment was not completed. Please try again.';
}

// Page configuration
$pagedata = [
    'pagetitle' => 'Processing Upgrade',
    'activepage' => 'billing'
];

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Initialize error message
$errormessage = $errormessage ?? '';
?>

<style>
.processing-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem;
    text-align: center;
}

.error-box {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.spinner-container {
    margin: 3rem 0;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>

<div class="processing-container">
    <?php if (!empty($errormessage)): ?>
        <div class="error-box">
            <i class="bi bi-exclamation-triangle-fill fs-1 mb-3 d-block"></i>
            <h3>Upgrade Processing Failed</h3>
            <p><?php echo htmlspecialchars($errormessage); ?></p>
        </div>
        
        <p class="mb-4">
            We encountered an issue processing your upgrade. Your payment may have been processed but the upgrade was not applied.
        </p>
        
        <div class="d-flex gap-3 justify-content-center">
            <a href="/myaccount/upgrade-plan" class="btn btn-primary">Try Again</a>
            <a href="/contact" class="btn btn-outline-primary">Contact Support</a>
        </div>
    <?php else: ?>
        <div class="spinner-container">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Processing...</span>
            </div>
        </div>
        
        <h3>Processing Your Upgrade</h3>
        <p class="text-muted">Please wait while we complete your plan upgrade...</p>
        
        <script>
        // If we're still here after 5 seconds, something went wrong
        setTimeout(function() {
            window.location.href = '/myaccount/billing';
        }, 5000);
        </script>
    <?php endif; ?>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>