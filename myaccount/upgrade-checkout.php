<?php
/**
 * Upgrade Checkout Page
 * Simple checkout for plan upgrades
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Page setup
$pagetitle = "Upgrade Checkout";

// Get parameters
$plan_name = $_GET['plan'] ?? '';
$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    header('Location: /myaccount/upgrade');
    exit();
}

// Get current user data from global variable set by site-controller
$current_plan = $current_user_data['account_plan'] ?? 'free';
$current_product_id = $current_user_data['account_product_id'] ?? null;
$user_id = $current_user_data['user_id'] ?? 0;

// Get new product details
$sql = "SELECT p.*, 
        (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'price' AND status = 'active' LIMIT 1) as price,
        (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'billing_period' AND status = 'active' LIMIT 1) as billing_period,
        (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'description' AND status = 'active' LIMIT 1) as description,
        (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'stripe_price_id' AND status = 'active' LIMIT 1) as stripe_price_id
        FROM bg_products p
        WHERE p.id = :product_id AND p.status = 'active'";

$stmt = $database->prepare($sql);
$stmt->execute(['product_id' => $product_id]);
$new_product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$new_product) {
    $session->set('errormessage', 'Invalid product selected');
    header('Location: /myaccount/upgrade');
    exit();
}

// Get current product price for pro-rating
$current_price = 0;
if ($current_product_id) {
    $sql = "SELECT value FROM bg_product_features WHERE product_id = :id AND name = 'price' AND status = 'active' LIMIT 1";
    $stmt = $database->prepare($sql);
    $stmt->execute(['id' => $current_product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $current_price = floatval(str_replace(['$', ','], '', $result['value']));
    }
}

// Calculate pricing
$new_price = floatval(str_replace(['$', ','], '', $new_product['price'] ?? '0'));
$upgrade_cost = max(0, $new_price - $current_price);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For now, just update the user's plan in the database
    // In production, this would integrate with Stripe
    
    $sql = "UPDATE bg_users SET 
            account_plan = :plan,
            account_product_id = :product_id,
            modify_dt = NOW()
            WHERE user_id = :user_id";
    
    $stmt = $database->prepare($sql);
    $result = $stmt->execute([
        'plan' => $new_product['account_plan'],
        'product_id' => $product_id,
        'user_id' => $user_id
    ]);
    
    if ($result) {
        // Log the upgrade
        $sql = "INSERT INTO bg_user_events (user_id, event_type, event_description, event_data, create_dt)
                VALUES (:user_id, 'upgrade', :description, :data, NOW())";
        
        $event_data = json_encode([
            'from_plan' => $current_plan,
            'to_plan' => $new_product['account_plan'],
            'from_product_id' => $current_product_id,
            'to_product_id' => $product_id,
            'amount' => $upgrade_cost
        ]);
        
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'description' => 'Upgraded to ' . $new_product['account_name'],
            'data' => $event_data
        ]);
        
        // Redirect to success page
        header('Location: /myaccount/upgrade-complete?plan=' . urlencode($new_product['account_plan']));
        exit();
    } else {
        $errormessage = 'Failed to process upgrade. Please try again.';
    }
}

// Add custom styles
$additionalstyles .= '<style>
.checkout-container {
    max-width: 800px;
    margin: 40px auto;
}

.checkout-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.checkout-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.checkout-body {
    padding: 40px;
}

.plan-summary {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 30px;
}

.plan-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.plan-row:last-child {
    border-bottom: none;
    font-weight: 700;
    font-size: 1.2rem;
    margin-top: 10px;
    padding-top: 20px;
    border-top: 2px solid #dee2e6;
}

.upgrade-benefits {
    margin: 30px 0;
}

.benefit-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.benefit-item i {
    color: #28a745;
    font-size: 1.2rem;
    margin-right: 15px;
}

.checkout-actions {
    text-align: center;
    margin-top: 30px;
}

.btn-upgrade {
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    text-transform: uppercase;
}

.secure-notice {
    text-align: center;
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 20px;
}

.secure-notice i {
    color: #28a745;
    margin-right: 5px;
}
</style>';

// Include header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '<div class="checkout-container">';

// Display any error messages
if (!empty($errormessage)) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($errormessage) . '</div>';
}

echo '<div class="checkout-card">';

// Header
echo '<div class="checkout-header">';
echo '<h2>Complete Your Upgrade</h2>';
echo '<p class="mb-0">Upgrade to ' . htmlspecialchars($new_product['account_name'] ?? ucfirst($new_product['account_plan'])) . '</p>';
echo '</div>';

// Body
echo '<div class="checkout-body">';

// Plan summary
echo '<div class="plan-summary">';
echo '<h4 class="mb-3">Order Summary</h4>';

echo '<div class="plan-row">';
echo '<span>Current Plan</span>';
echo '<span>' . ucfirst(str_replace(['user_', 'parental_', 'minor_', 'business_'], '', $current_plan)) . '</span>';
echo '</div>';

echo '<div class="plan-row">';
echo '<span>New Plan</span>';
echo '<span>' . htmlspecialchars($new_product['account_name'] ?? ucfirst($new_product['account_plan'])) . '</span>';
echo '</div>';

if ($current_price > 0) {
    echo '<div class="plan-row">';
    echo '<span>Current Plan Price</span>';
    echo '<span>-$' . number_format($current_price, 2) . '</span>';
    echo '</div>';
}

echo '<div class="plan-row">';
echo '<span>New Plan Price</span>';
echo '<span>$' . number_format($new_price, 2) . '</span>';
echo '</div>';

echo '<div class="plan-row">';
echo '<span>Amount Due Today</span>';
echo '<span>$' . number_format($upgrade_cost, 2) . '</span>';
echo '</div>';

echo '</div>'; // End plan-summary

// Benefits of upgrading
echo '<div class="upgrade-benefits">';
echo '<h4 class="mb-3">What You Will Get</h4>';

$benefits = [
    'Immediate access to all ' . htmlspecialchars($new_product['account_name'] ?? 'premium') . ' features',
    'Priority customer support',
    'Ad-free experience',
    'Exclusive rewards and offers'
];

foreach ($benefits as $benefit) {
    echo '<div class="benefit-item">';
    echo '<i class="bi bi-check-circle-fill"></i>';
    echo '<span>' . $benefit . '</span>';
    echo '</div>';
}

echo '</div>'; // End upgrade-benefits

// Checkout form
echo '<form method="POST" action="">';
echo $display->inputcsrf_token();

// Payment method section (placeholder for now)
if ($upgrade_cost > 0) {
    echo '<div class="alert alert-info">';
    echo '<i class="bi bi-info-circle"></i> ';
    echo 'Payment processing will be handled by Stripe. Your card will be charged $' . number_format($upgrade_cost, 2) . '.';
    echo '</div>';
}

echo '<div class="checkout-actions">';
if ($upgrade_cost > 0) {
    echo '<button type="submit" class="btn btn-primary btn-upgrade">';
    echo 'Complete Upgrade - $' . number_format($upgrade_cost, 2);
    echo '</button>';
} else {
    echo '<button type="submit" class="btn btn-success btn-upgrade">';
    echo 'Complete Free Upgrade';
    echo '</button>';
}

echo '<div class="mt-3">';
echo '<a href="/myaccount/upgrade" class="btn btn-link">Cancel and go back</a>';
echo '</div>';
echo '</div>';

echo '</form>';

// Secure notice
echo '<div class="secure-notice">';
echo '<i class="bi bi-shield-check"></i>';
echo 'Your payment information is secure and encrypted';
echo '</div>';

echo '</div>'; // End checkout-body
echo '</div>'; // End checkout-card

// FAQ section
echo '<div class="mt-5">';
echo '<h4>Questions?</h4>';
echo '<p><strong>When will my upgrade take effect?</strong><br>';
echo 'Your upgrade will be activated immediately after payment is processed.</p>';
echo '<p><strong>Can I cancel or change my plan later?</strong><br>';
echo 'Yes, you can modify or cancel your subscription at any time from your account settings.</p>';
echo '<p><strong>Is this a recurring charge?</strong><br>';
echo 'Yes, you will be charged ' . htmlspecialchars($new_product['billing_period'] ?? 'annually') . ' unless you cancel.</p>';
echo '</div>';

echo '</div>'; // End container

// Include footer
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();