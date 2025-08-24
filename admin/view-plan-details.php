<?php
/**
 * Admin View Plan Details
 * 
 * Allows admins to view how plan details appear to users
 * Can be linked from plan_editor.php with ?product_id=X parameter
 * Access control is handled by site-controller.php
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get product_id from URL parameter
$view_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;

if (!$view_product_id) {
    // If no product_id, redirect to plan editor
    header('Location: /admin/plan_editor.php');
    exit();
}

// Get product details
$sql = "SELECT * FROM bg_products WHERE id = :id AND status = 'active'";
$stmt = $database->prepare($sql);
$stmt->execute(array('id' => $view_product_id));
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die('Product not found');
}

// Build display name for the product
$display_name = !empty($product['account_name']) ? $product['account_name'] : strtoupper($product['account_plan'] ?? 'UNNAMED');
$plan_type = $product['account_type'] ?? 'user';
$version = $product['version'] ?? 'v3';

// Page setup
$pagetitle = "View Plan Details - " . $display_name;

// Add custom styles
$additionalstyles .= '<style>
.admin-notice {
    background: #fff3cd;
    border: 1px solid #ffc107;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.admin-notice h5 {
    margin: 0 0 10px 0;
    color: #856404;
}
.admin-tools {
    position: fixed;
    top: 100px;
    right: 20px;
    background: white;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 100;
    max-width: 200px;
}
.admin-tools h6 {
    margin-bottom: 10px;
    color: #333;
}
.admin-tools .btn {
    width: 100%;
    margin-bottom: 5px;
}

/* Plan summary styling from plan-details.php */
.plan-summary {
    background: #1a1a1a;
    color: white;
    border-radius: 20px;
    padding: 3rem;
    margin-bottom: 3rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.plan-summary::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255, 215, 0, 0.1) 50%, transparent 70%);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.plan-summary h2 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #FFD700;
    position: relative;
    z-index: 1;
}

.plan-summary .price {
    font-size: 3.5rem;
    font-weight: 900;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.plan-summary .billing-cycle {
    font-size: 1.1rem;
    opacity: 0.8;
    position: relative;
    z-index: 1;
}

.plan-summary .plan-description {
    max-width: 600px;
    margin: 0 auto;
    font-size: 1.1rem;
    line-height: 1.6;
    opacity: 0.95;
    position: relative;
    z-index: 1;
}

@media (max-width: 768px) {
    .plan-summary h2 {
        font-size: 1.5rem;
    }
    
    .plan-summary .price {
        font-size: 2.5rem;
    }
}
</style>';

// Include header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Admin content header
echo '<div class="content-header-admin">';
echo '<div class="container">';
echo '<h1>View Plan Details</h1>';
echo '<p class="lead">Viewing: <strong>' . htmlspecialchars($display_name) . '</strong> (' . htmlspecialchars($version) . ' - ' . htmlspecialchars($plan_type) . ' - ID: ' . $view_product_id . ')</p>';
echo '</div>';
echo '</div>';

echo '<div class="container mt-4">';

// Admin notice
echo '<div class="admin-notice">';
echo '<h5><i class="bi bi-info-circle"></i> Admin View Mode</h5>';
echo '<p class="mb-2">You are viewing how the plan details page appears for: <strong>' . htmlspecialchars($display_name) . '</strong></p>';
echo '<p class="mb-0">This shows the actual user view with all card overrides applied.</p>';
echo '</div>';

// Get plan details for the summary
$price_sql = "SELECT value FROM bg_product_features WHERE product_id = :product_id AND name = 'price' AND status = 'active' LIMIT 1";
$price_stmt = $database->prepare($price_sql);
$price_stmt->execute(array('product_id' => $view_product_id));
$price_result = $price_stmt->fetch(PDO::FETCH_ASSOC);
$price = $price_result ? $price_result['value'] : '$0.00';

$billing_sql = "SELECT value FROM bg_product_features WHERE product_id = :product_id AND name = 'billing_period' AND status = 'active' LIMIT 1";
$billing_stmt = $database->prepare($billing_sql);
$billing_stmt->execute(array('product_id' => $view_product_id));
$billing_result = $billing_stmt->fetch(PDO::FETCH_ASSOC);
$billing_period = $billing_result ? $billing_result['value'] : 'per year';

$desc_sql = "SELECT value FROM bg_product_features WHERE product_id = :product_id AND name = 'description' AND status = 'active' LIMIT 1";
$desc_stmt = $database->prepare($desc_sql);
$desc_stmt->execute(array('product_id' => $view_product_id));
$desc_result = $desc_stmt->fetch(PDO::FETCH_ASSOC);
$description = $desc_result ? $desc_result['value'] : 'Experience Birthday Gold with the ' . $display_name . ' plan.';

// Plan Summary Card (matching plan-details.php styling)
echo '<div class="plan-summary">';
echo '<h2>' . htmlspecialchars($display_name) . '</h2>';
echo '<div class="price">' . htmlspecialchars($price) . '</div>';
echo '<div class="billing-cycle mb-3">' . htmlspecialchars($billing_period) . '</div>';

if (!empty($description)) {
    echo '<div class="plan-description mb-3">';
    echo htmlspecialchars($description);
    echo '</div>';
}

// Get and display feature list if available
$features_sql = "SELECT value FROM bg_product_features WHERE product_id = :product_id AND name = 'feature_list' AND status = 'active' LIMIT 1";
$features_stmt = $database->prepare($features_sql);
$features_stmt->execute(array('product_id' => $view_product_id));
$features_result = $features_stmt->fetch(PDO::FETCH_ASSOC);

if ($features_result && $features_result['value']) {
    $features = explode("\n", $features_result['value']);
    if (count($features) > 0) {
        echo '<div class="mt-3" style="position: relative; z-index: 1;">';
        foreach ($features as $feature) {
            $feature = trim($feature);
            if (!empty($feature)) {
                echo '<div class="mb-2" style="font-size: 1rem; opacity: 0.9;">';
                echo '<i class="bi bi-check-circle-fill me-2" style="color: #FFD700;"></i>';
                echo htmlspecialchars($feature);
                echo '</div>';
            }
        }
        echo '</div>';
    }
}

echo '</div>';

// Admin tools sidebar
echo '<div class="admin-tools d-none d-lg-block">';
echo '<h6>Admin Tools</h6>';
echo '<a href="/admin/plan_editor.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Editor</a>';
echo '<a href="/admin/manage_plan_cards.php?selected_plan=' . $view_product_id . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-gear"></i> Manage Cards</a>';
echo '<a href="/myaccount/plan-details.php?product_id=' . $view_product_id . '" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> User View</a>';
echo '<hr>';
echo '<div class="small text-muted">';
echo '<strong>Product ID:</strong> ' . $view_product_id . '<br>';
echo '<strong>Plan:</strong> ' . htmlspecialchars($product['account_plan'] ?? 'N/A') . '<br>';
echo '<strong>Type:</strong> ' . htmlspecialchars($plan_type) . '<br>';
echo '<strong>Version:</strong> ' . htmlspecialchars($version);
echo '</div>';
echo '</div>';

// Now include the actual plan details display
// We will set the variables that plan-details.php expects
$user_plan = $product['account_plan'];
$user_product_id = $view_product_id;
$current_user_data = array(
    'account_plan' => $product['account_plan'],
    'account_product_id' => $view_product_id,
    'account_type' => $product['account_type'],
    'version' => $product['version']
);

// Include the plan details display logic
echo '<div class="row">';
echo '<div class="col-lg-10">';

// Get and display the plan feature cards
$sql = "SELECT config_key, config_data, display_order 
        FROM bg_config 
        WHERE config_type = 'plan_feature_card' 
        AND status = 'active' 
        ORDER BY display_order";
$cards = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Get overrides for this product
$sql = "SELECT name, value 
        FROM bg_product_features 
        WHERE product_id = :product_id 
        AND name LIKE 'card_%' 
        AND status = 'active'";
$stmt = $database->prepare($sql);
$stmt->execute(array('product_id' => $view_product_id));
$overrides = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Display the cards
echo '<div class="row g-4 mb-5">';
foreach ($cards as $card) {
    $card_data = json_decode($card['config_data'], true);
    $card_key = $card['config_key'];
    
    // Check if card is hidden for this plan
    if (isset($overrides['card_' . $card_key . '_status']) && $overrides['card_' . $card_key . '_status'] === 'hidden') {
        continue; // Skip hidden cards
    }
    
    // Check if card has plan restrictions
    if (isset($card_data['plans']) && is_array($card_data['plans']) && !empty($card_data['plans'])) {
        $allowed_plans = array_map('strtolower', $card_data['plans']);
        $current_plan_type = strtolower($plan_type);
        $current_plan_name = strtolower($product['account_plan'] ?? '');
        
        $is_allowed = false;
        if (in_array($current_plan_type, $allowed_plans) || 
            in_array($current_plan_name, $allowed_plans) ||
            ($current_plan_type === 'user' && in_array('individual', $allowed_plans)) ||
            ($current_plan_type === 'individual' && in_array('user', $allowed_plans))) {
            $is_allowed = true;
        }
        
        if (!$is_allowed) {
            continue; // Skip cards not allowed for this plan
        }
    }
    
    // Apply overrides
    $prefix = 'card_' . $card_key . '_';
    if (isset($overrides[$prefix . 'title'])) {
        $card_data['title'] = $overrides[$prefix . 'title'];
    }
    if (isset($overrides[$prefix . 'value'])) {
        $card_data['value'] = $overrides[$prefix . 'value'];
    }
    if (isset($overrides[$prefix . 'description'])) {
        $card_data['description'] = $overrides[$prefix . 'description'];
    }
    if (isset($overrides[$prefix . 'icon'])) {
        $card_data['icon'] = $overrides[$prefix . 'icon'];
    }
    if (isset($overrides[$prefix . 'icon_color'])) {
        $card_data['icon_color'] = $overrides[$prefix . 'icon_color'];
    }
    
    // Display the card
    echo '<div class="col-md-6 col-lg-4">';
    echo '<div class="card h-100 shadow-sm">';
    echo '<div class="card-body">';
    
    // Icon
    $icon_class = $card_data['icon'] ?? 'bi-star';
    if (strpos($icon_class, 'bi-') === 0 && strpos($icon_class, 'bi ') !== 0) {
        $icon_class = 'bi ' . $icon_class;
    }
    $icon_color = $card_data['icon_color'] ?? 'primary';
    echo '<div class="text-' . htmlspecialchars($icon_color) . ' mb-3">';
    echo '<i class="' . htmlspecialchars($icon_class) . '" style="font-size: 2.5rem;"></i>';
    echo '</div>';
    
    // Title
    echo '<h5 class="card-title">' . htmlspecialchars($card_data['title'] ?? '') . '</h5>';
    
    // Value
    echo '<p class="text-primary fw-bold mb-2" style="font-size: 1.25rem;">' . htmlspecialchars($card_data['value'] ?? '') . '</p>';
    
    // Description - allow HTML for links and formatting
    echo '<p class="card-text text-muted">' . ($card_data['description'] ?? '') . '</p>';
    
    // Show if this card has overrides (admin only)
    $has_overrides = false;
    foreach (array('title', 'value', 'description', 'icon', 'icon_color') as $field) {
        if (isset($overrides[$prefix . $field])) {
            $has_overrides = true;
            break;
        }
    }
    
    if ($has_overrides) {
        echo '<div class="mt-3 pt-3 border-top">';
        echo '<small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Has overrides</small>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
echo '</div>';

echo '</div>'; // Close col-lg-10
echo '</div>'; // Close row

echo '</div>'; // Close container

// Include footer
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();