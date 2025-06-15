<?php
/**
 * API Endpoint: Create Upgrade Payment Intent
 * Creates a Stripe PaymentIntent for plan upgrades
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!$account->isactive()) {
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$upgradeSessionId = $input['upgrade_session_id'] ?? '';
$amount = intval($input['amount'] ?? 0);
$customerId = $input['customer_id'] ?? '';

// Validate upgrade session
$upgradeSession = $session->get('upgrade_session');

if (!$upgradeSession || $upgradeSession['upgrade_session_id'] !== $upgradeSessionId) {
    echo json_encode(['error' => 'Invalid upgrade session']);
    exit();
}

// Validate amount
if ($amount <= 0) {
    echo json_encode(['error' => 'Invalid amount']);
    exit();
}

// Initialize Stripe
require_once($_SERVER['DOCUMENT_ROOT'] . '/../ENV_CONFIGS/vendor/autoload.php');
\Stripe\Stripe::setApiKey($STRIPECONFIG['stripe_secret_key']);

try {
    // Create Payment Intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'usd',
        'customer' => $customerId,
        'metadata' => [
            'type' => 'plan_upgrade',
            'upgrade_session_id' => $upgradeSessionId,
            'user_id' => $account->getUserId(),
            'from_plan' => $upgradeSession['from_product_id'],
            'to_plan' => $upgradeSession['to_product_id']
        ],
        'description' => 'Plan upgrade',
        'statement_descriptor' => 'BIRTHDAY GOLD UPGRADE'
    ]);
    
    // Log the payment intent creation
    $sql = "INSERT INTO bg_sessiontracking 
            (user_id, session_id, event_type, event_action, event_data, ip_address, created_at)
            VALUES
            (:user_id, :session_id, 'upgrade', 'payment_intent_created', :data, :ip, NOW())";
    
    $database->query($sql, [
        'user_id' => $account->getUserId(),
        'session_id' => session_id(),
        'data' => json_encode(['payment_intent_id' => $paymentIntent->id, 'amount' => $amount]),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    
    echo json_encode([
        'clientSecret' => $paymentIntent->client_secret
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Log the error
    $sql = "INSERT INTO bg_errors 
            (user_id, error_type, error_message, error_data, created_at)
            VALUES
            (:user_id, 'stripe_error', :message, :data, NOW())";
    
    $database->query($sql, [
        'user_id' => $account->getUserId(),
        'message' => $e->getMessage(),
        'data' => json_encode(['upgrade_session_id' => $upgradeSessionId])
    ]);
    
    echo json_encode([
        'error' => 'Payment initialization failed. Please try again.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'An unexpected error occurred. Please try again.'
    ]);
}
?>