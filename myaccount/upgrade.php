<?php
/**
 * Plan Upgrade Page
 * Uses Bootstrap utilities for styling
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page setup
$pagetitle = "Upgrade Your Plan";

// Debug mode - allows testing with different base plans
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
$test_plan_id = null;
$test_account_type = null;

if ($debug_mode) {
    // Allow overriding account type for testing
    if (isset($_GET['account_type']) && in_array($_GET['account_type'], ['user', 'parental', 'family', 'business', 'minor'])) {
        $test_account_type = $_GET['account_type'];
    }
    
    if (isset($_GET['plan_id'])) {
        // In debug mode, allow testing with a different plan ID
        $test_plan_id = (int)$_GET['plan_id'];
        
        // Fetch the test plan details
        $sql = "SELECT p.* FROM bg_products p 
                WHERE p.id = :id AND p.status = 'active'";
        $stmt = $database->prepare($sql);
        $stmt->execute(['id' => $test_plan_id]);
        $test_plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_plan) {
            // Override current user data with test plan
            $current_plan = $test_plan['account_plan'];
            $current_type = $test_account_type ?? $test_plan['account_type'];  // Use override type if provided
            $current_product_id = $test_plan_id;
            $debug_plan_name = $test_plan['account_name'];
            $debug_version = $test_plan['version'] ?? 'unknown';
        } else {
            // Invalid plan ID, fall back to actual user data
            $debug_mode = false;
        }
    } elseif ($test_account_type) {
        // Just override the account type for testing
        $current_type = $test_account_type;
    }
}

// Get current user data from global variable set by site-controller (if not in debug mode)
if (!$debug_mode) {
    $current_plan = $current_user_data['account_plan'] ?? 'free';
    $current_type = $current_user_data['account_type'] ?? 'user';
    $current_product_id = $current_user_data['account_product_id'] ?? null;
}
$user_id = $current_user_data['user_id'] ?? 0;

// Get current plan display name
$current_plan_display = ucfirst(str_replace(['user_', 'parental_', 'minor_', 'business_'], '', $current_plan));
if ($current_product_id) {
    $sql = "SELECT account_name FROM bg_products WHERE id = :id AND status = 'active'";
    $stmt = $database->prepare($sql);
    $stmt->execute(['id' => $current_product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $current_plan_display = $result['account_name'];
    }
}

// Use the system's plan version setting
$version_to_use = $website['plan_version'] ?? 'v7';  // Default to v7 if not set

// Check if any products have this version in the bg_products table
$sql_check = "SELECT COUNT(*) as count FROM bg_products 
              WHERE version = :version
              AND status = 'active'";
$stmt = $database->prepare($sql_check);
$stmt->execute(['version' => $version_to_use]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$has_version_products = $result['count'] > 0;

// If no products have this version, don't filter by version
if (!$has_version_products) {
    $version_to_use = null;  // Will fetch all plans regardless of version
}

// Check if current plan has upgradeable restrictions or is non-upgradeable
$allowed_upgrades = [];
$is_upgradeable = true;  // Default to upgradeable
$upgrade_message = '';   // Custom message for non-upgradeable plans

if ($current_product_id) {
    // First check if plan is explicitly non-upgradeable
    $sql = "SELECT value FROM bg_product_features 
            WHERE product_id = :product_id 
            AND name = 'upgradeable' 
            AND status = 'active' 
            LIMIT 1";
    $stmt = $database->prepare($sql);
    $stmt->execute(['product_id' => $current_product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['value']) {
        $value = trim(strtolower($result['value']));
        // Check for explicit non-upgradeable values
        if ($value === 'false' || $value === 'no' || $value === '0' || $value === 'none') {
            $is_upgradeable = false;
        } elseif (strpos($value, '[') === 0) {
            $allowed_upgrades = json_decode($value, true) ?? [];
        } else {
            $allowed_upgrades = array_map('trim', explode(',', $value));
        }
    }
    
    // Check for upgrade message
    $sql = "SELECT value FROM bg_product_features 
            WHERE product_id = :product_id 
            AND name = 'upgrade_message' 
            AND status = 'active' 
            LIMIT 1";
    $stmt = $database->prepare($sql);
    $stmt->execute(['product_id' => $current_product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['value']) {
        $upgrade_message = $result['value'];
    }
}

// Determine target account type for upgrades
// Hierarchy: user < parental/family < business
if ($current_type == 'minor') {
    // Minor can upgrade to user, parental, family, or business
    $target_account_types = ['user', 'parental', 'family', 'business'];
} elseif ($current_type == 'user') {
    // User can upgrade to anything (move up in hierarchy)
    $target_account_types = ['user', 'parental', 'family', 'business'];
} elseif ($current_type == 'parental') {
    // Parental can upgrade to parental, family, or business (not downgrade to user)
    $target_account_types = ['parental', 'family', 'business'];
} elseif ($current_type == 'family') {
    // Family can upgrade to family or business (not downgrade to user)
    $target_account_types = ['family', 'parental', 'business'];
} elseif ($current_type == 'business') {
    // Business can only upgrade within business tier
    $target_account_types = ['business'];
} else {
    // Default to all types
    $target_account_types = ['user', 'parental', 'family', 'business'];
}

// For backward compatibility with single type queries
$target_account_type = $target_account_types[0];

// Fetch available upgrade plans
// Build IN clause for multiple account types
$type_placeholders = array_map(function($i) { return ':type' . $i; }, range(0, count($target_account_types) - 1));
$type_in_clause = implode(', ', $type_placeholders);

if ($version_to_use) {
    // If versions are in use, filter by version
    $sql = "SELECT DISTINCT p.id, p.account_name, p.account_plan, p.account_type, p.version, 
            p.price, p.billing_cycle,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'billing_period' AND status = 'active' LIMIT 1) as billing_period,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'description' AND status = 'active' LIMIT 1) as description,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'enrollments_per_period' AND status = 'active' LIMIT 1) as enrollments,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'upgradeable_from' AND status = 'active' LIMIT 1) as upgradeable_from
            FROM bg_products p
            WHERE p.status = 'active' 
            AND p.account_type IN (" . $type_in_clause . ")
            AND p.version = :version
            AND p.id != :current_product_id";
    $params = [
        'version' => $version_to_use,
        'current_product_id' => $current_product_id ?? 0
    ];
    // Add type parameters
    foreach ($target_account_types as $i => $type) {
        $params['type' . $i] = $type;
    }
} else {
    // No versions in use, get all plans of target types
    $sql = "SELECT DISTINCT p.id, p.account_name, p.account_plan, p.account_type, p.version,
            p.price, p.billing_cycle,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'billing_period' AND status = 'active' LIMIT 1) as billing_period,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'description' AND status = 'active' LIMIT 1) as description,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'enrollments_per_period' AND status = 'active' LIMIT 1) as enrollments,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'upgradeable_from' AND status = 'active' LIMIT 1) as upgradeable_from
            FROM bg_products p
            WHERE p.status = 'active' 
            AND p.account_type IN (" . $type_in_clause . ")
            AND p.id != :current_product_id
            AND p.account_name IS NOT NULL";  // Only get plans with names
    $params = [
        'current_product_id' => $current_product_id ?? 0
    ];
    // Add type parameters
    foreach ($target_account_types as $i => $type) {
        $params['type' . $i] = $type;
    }
}

$sql .= " ORDER BY 
            CASE p.account_plan 
                WHEN 'parental_free' THEN 1
                WHEN 'user_free' THEN 1
                WHEN 'family_free' THEN 1
                WHEN 'parental_plus' THEN 2
                WHEN 'user_plus' THEN 2  
                WHEN 'parental_gold' THEN 3
                WHEN 'user_gold' THEN 3
                WHEN 'family_gold' THEN 3
                WHEN 'parental_platinum' THEN 4
                WHEN 'user_platinum' THEN 4
                WHEN 'business_bronze' THEN 1
                WHEN 'business_silver' THEN 2
                WHEN 'business_gold' THEN 3
                WHEN 'business_platinum' THEN 4
                ELSE 5
            END";

// params already set above in the if/else block

$stmt = $database->prepare($sql);
$stmt->execute($params);
$all_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter available plans based on upgrade logic
$available_plans = [];

// If plan is not upgradeable, don't show any plans
if (!$is_upgradeable) {
    $available_plans = [];  // No upgrades available
} else {
    foreach ($all_plans as $plan) {
        // Determine current tier
        $current_tier = 0;
        if (strpos($current_plan, 'free') !== false || $current_plan == 'free') $current_tier = 1;
        elseif (strpos($current_plan, 'plus') !== false) $current_tier = 2;
        elseif (strpos($current_plan, 'gold') !== false) $current_tier = 3;
        elseif (strpos($current_plan, 'platinum') !== false) $current_tier = 4;
        
        // Determine plan tier - check both account_plan and account_name for tier keywords
        $plan_tier = 0;
        $plan_check = strtolower($plan['account_plan'] . ' ' . ($plan['account_name'] ?? ''));
        
        if (strpos($plan_check, 'trial') !== false) $plan_tier = 0.5; // Trial is below free
        elseif (strpos($plan_check, 'free') !== false) $plan_tier = 1;
        elseif (strpos($plan_check, 'plus') !== false || strpos($plan_check, 'bronze') !== false) $plan_tier = 2;
        elseif (strpos($plan_check, 'gold') !== false || strpos($plan_check, 'silver') !== false) $plan_tier = 3;
        elseif (strpos($plan_check, 'platinum') !== false) $plan_tier = 4;
        
        // Business plans should typically be considered premium regardless of naming
        // If it's a business plan and tier wasn't set, default to tier 3 (gold level)
        if ($plan_tier == 0 && $plan['account_type'] == 'business') {
            $plan_tier = 3; // Default business plans to gold tier
        }
        
        // Only show plans that are upgrades (higher tier)
        // If current tier is 0 (unknown/legacy), DON'T show plans unless explicitly allowed
        if ($current_tier > 0 && $plan_tier > $current_tier) {
            $available_plans[] = $plan;
        }
    }
}

// Apply upgradeable restrictions if any (from current plan's upgradeable field)
// Exception: Free plans should show all upgrades unless explicitly set to 'false'/'no'
$is_free_plan = (strpos($current_plan, 'free') !== false || $current_plan == 'free');

if (!empty($allowed_upgrades) && !$is_free_plan) {
    // Apply restrictions for non-free plans
    $available_plans = array_filter($available_plans, function($plan) use ($allowed_upgrades) {
        return in_array($plan['id'], $allowed_upgrades) || in_array($plan['account_plan'], $allowed_upgrades);
    });
} elseif (!empty($allowed_upgrades) && $is_free_plan) {
    // For free plans, only apply restrictions if they seem reasonable (more than 1 option)
    if (count($allowed_upgrades) > 1) {
        $available_plans = array_filter($available_plans, function($plan) use ($allowed_upgrades) {
            return in_array($plan['id'], $allowed_upgrades) || in_array($plan['account_plan'], $allowed_upgrades);
        });
    }
    // If free plan has only 1 upgrade option, ignore it and show all valid upgrades
}

// Check if this is a grandfathered plan
$is_grandfathered = false;
if ($current_product_id) {
    $sql = "SELECT version FROM bg_products WHERE id = :product_id AND status = 'active' LIMIT 1";
    $stmt = $database->prepare($sql);
    $stmt->execute(['product_id' => $current_product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['version'] && !in_array($result['version'], ['v7', 'v3'])) {
        $is_grandfathered = true;
    }
}

// Get current plan details for display
$current_price = '0';
$current_period = 'month';
$current_enrollments = '0';
$current_description = '';

if ($current_product_id) {
    $sql = "SELECT p.price, p.billing_cycle,
            (SELECT value FROM bg_product_features WHERE product_id = :product_id1 AND name = 'billing_period' AND status = 'active' LIMIT 1) as billing_period,
            (SELECT value FROM bg_product_features WHERE product_id = :product_id2 AND name = 'enrollments_per_period' AND status = 'active' LIMIT 1) as enrollments,
            (SELECT value FROM bg_product_features WHERE product_id = :product_id3 AND name = 'description' AND status = 'active' LIMIT 1) as description
            FROM bg_products p
            WHERE p.id = :product_id";
    $stmt = $database->prepare($sql);
    $stmt->execute([
        'product_id' => $current_product_id,
        'product_id1' => $current_product_id,
        'product_id2' => $current_product_id,
        'product_id3' => $current_product_id
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        // Convert price from cents to dollars
        $price_in_cents = $result['price'] ?? 0;
        $current_price = number_format($price_in_cents / 100, 2, '.', '');
        // Use billing_period from features first, fallback to billing_cycle
        $current_period = $result['billing_period'] ?? ($result['billing_cycle'] == 'one_time' ? 'lifetime' : 'month');
        $current_enrollments = $result['enrollments'] ?? '0';
        $current_description = $result['description'] ?? '';
    }
}

// Minimal custom styles only for things Bootstrap cannot handle
$additionalstyles .= '
<style>
/* Welcome content styling to match login page */
.welcome-content h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.welcome-content h2 span {
    color: var(--bs-primary);
}

.welcome-content p {
    font-size: 1.25rem;
    color: #6c757d;
    margin-bottom: 2rem;
    line-height: 1.6;
}

/* Feature grid matching login page */
.feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.feature-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    background: var(--bs-success);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.feature-text h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.feature-text p {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

/* Custom gradient button - extends Bootstrap button */
.btn-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
}

.btn-gradient:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b4299 100%);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.btn-gradient:disabled {
    background: #dee2e6;
    opacity: 0.6;
}

@media (min-width: 1200px) {
    .welcome-content h2 {
        font-size: 3rem;
    }
}
</style>
';

// Include header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Show debug banner if in debug mode
if ($debug_mode) {
    echo '
    <div class="alert alert-warning mb-0 rounded-0 text-center">
        <i class="bi bi-bug-fill me-2"></i>
        <strong>DEBUG MODE</strong> - Testing upgrade from: 
        <strong>' . htmlspecialchars($debug_plan_name ?? $current_plan_display ?? '') . '</strong> 
        (ID: ' . $current_product_id . ', Type: ' . htmlspecialchars($current_type) . ', Version: ' . htmlspecialchars($debug_version ?? 'unknown') . ')
        <a href="/myaccount/upgrade" class="ms-3 btn btn-sm btn-outline-dark">Exit Debug</a>
    </div>';
    
    // Show quick test links for account types
    echo '
    <div class="alert alert-secondary mb-0 rounded-0 text-center py-2">
        <small><strong>Quick Tests:</strong></small>
        <div class="btn-group btn-group-sm ms-3" role="group">
            <a href="?debug=1&plan_id=' . ($test_plan_id ?? $current_product_id) . '&account_type=user" 
               class="btn btn-outline-primary ' . ($current_type == 'user' ? 'active' : '') . '">Test as User</a>
            <a href="?debug=1&plan_id=' . ($test_plan_id ?? $current_product_id) . '&account_type=parental" 
               class="btn btn-outline-primary ' . ($current_type == 'parental' ? 'active' : '') . '">Test as Parental</a>
            <a href="?debug=1&plan_id=' . ($test_plan_id ?? $current_product_id) . '&account_type=family" 
               class="btn btn-outline-primary ' . ($current_type == 'family' ? 'active' : '') . '">Test as Family</a>
            <a href="?debug=1&plan_id=' . ($test_plan_id ?? $current_product_id) . '&account_type=business" 
               class="btn btn-outline-primary ' . ($current_type == 'business' ? 'active' : '') . '">Test as Business</a>
        </div>
        <small class="ms-3 text-muted">(Changes what plans you can upgrade to)</small>
    </div>';
    
    // Additional debug info with collapsible panel
    echo '
    <div class="alert alert-info mb-0 rounded-0">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#debugPanel" aria-expanded="false" aria-controls="debugPanel">
                    <i class="bi bi-chevron-down"></i> Show Debug Info
                </button>
                <span class="ms-3">
                    <small>
                        <strong>Quick Info:</strong> 
                        Plans Found: ' . count($all_plans ?? []) . ' | 
                        Available: ' . count($available_plans) . ' | 
                        Version: ' . htmlspecialchars($version_to_use ?? 'none') . '
                    </small>
                </span>
            </div>
            <button class="btn btn-sm btn-outline-dark" onclick="navigator.clipboard.writeText(document.getElementById(\'debugText\').innerText)">
                <i class="bi bi-clipboard"></i> Copy All
            </button>
        </div>
        <div class="collapse mt-3" id="debugPanel">
            <div id="debugText">
                <small>
                    <strong>Debug Info:</strong><br>
                    Current Plan: ' . htmlspecialchars($current_plan) . '<br>
                    Current Type: ' . htmlspecialchars($current_type) . '<br>
                    Is Upgradeable: ' . ($is_upgradeable ? 'Yes' : 'No') . '<br>
                    Target Types: ' . htmlspecialchars(implode(', ', $target_account_types)) . '<br>
                    Website Version: ' . htmlspecialchars($website['plan_version'] ?? 'not set') . '<br>
                    Version to Use: ' . htmlspecialchars($version_to_use ?? 'none') . '<br>
                    Found Plans: ' . count($all_plans ?? []) . '<br>
                    Available After Filter: ' . count($available_plans) . '<br>
                    Allowed Upgrades: ' . (empty($allowed_upgrades) ? 'None' : implode(', ', $allowed_upgrades)) . '<br>
                    Upgrade Message: ' . (!empty($upgrade_message) ? htmlspecialchars($upgrade_message) : 'None') . '
                </small>';
    
    // Show the SQL query being used
    echo '<br><small><strong>SQL Query:</strong><br>';
    echo '<code style="user-select: all;">' . htmlspecialchars($sql) . '</code></small>';
    echo '<br><small><strong>SQL Parameters:</strong> ';
    echo '<code style="user-select: all;">' . htmlspecialchars(json_encode($params)) . '</code></small>';
    
    // Show found plans details
    if (!empty($all_plans)) {
        echo '<br><small><strong>Found Plans Before Filtering:</strong><br>';
        foreach ($all_plans as $p) {
            echo '[ID:' . $p['id'] . ' Name:' . htmlspecialchars($p['account_name'] ?? 'unnamed') . ' Ver:' . htmlspecialchars($p['version'] ?? 'no-ver') . ' Type:' . htmlspecialchars($p['account_type'] ?? '') . ']<br>';
        }
        echo '</small>';
    }
    
    // Show available plans after filtering
    if (!empty($available_plans)) {
        echo '<br><small><strong>Available Plans After Filtering:</strong><br>';
        foreach ($available_plans as $p) {
            echo '[ID:' . $p['id'] . ' Name:' . htmlspecialchars($p['account_name'] ?? 'unnamed') . ' Ver:' . htmlspecialchars($p['version'] ?? 'no-ver') . ' Type:' . htmlspecialchars($p['account_type'] ?? '') . ']<br>';
        }
        echo '</small>';
    }
    
    echo '
            </div>
        </div>
    </div>';
}

echo '
<div class="container mt-5 py-5">
    <div class="row justify-content-center gx-lg-5">
        
        <!-- Desktop welcome content (hidden on mobile) - Shows current plan details -->
        <div class="col-lg-5 d-none d-lg-block welcome-content pe-lg-4">
            <h2>Upgrade Your <span>Plan</span></h2>
            <p>You are currently on the <strong>' . htmlspecialchars($current_plan_display ?? '') . '</strong> plan' . 
            ($debug_mode ? ' <span class="badge bg-warning text-dark ms-2">DEBUG</span>' : '') . '</p>
            
            <div class="feature-grid">
                <!-- Price -->
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Current Price</h3>
                        <p>$' . htmlspecialchars($current_price) . '/' . htmlspecialchars($current_period) . '</p>
                    </div>
                </div>
                
                <!-- Enrollments -->
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-gift"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Enrollments</h3>
                        <p>' . htmlspecialchars($current_enrollments) . ' per ' . htmlspecialchars($current_period) . '</p>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Account Status</h3>
                        <p>Active and ready to use</p>
                    </div>
                </div>
                
                <!-- Type -->
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Plan Type</h3>
                        <p>' . ($is_grandfathered ? 'Grandfathered pricing' : 'Standard pricing') . '</p>
                    </div>
                </div>
            </div>';

if ($is_grandfathered) {
    echo '
            <div class="alert alert-warning d-flex align-items-start" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                <div>
                    <strong>Grandfathered Plan</strong>
                    <p class="mb-0 small">You have special legacy pricing that will be lost if you upgrade. This pricing is no longer available to new customers.</p>
                </div>
            </div>';
}

echo '
        </div>
        
        <!-- Upgrade card -->
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm">
                
                <!-- Header -->';

// Only show header if there are upgrade options available
if ($is_upgradeable && !empty($available_plans)) {
    echo '
                <div class="card-body text-center pt-4 pb-3">
                    <span class="badge rounded-pill bg-primary bg-gradient px-3 py-2 mb-3">
                        <i class="bi bi-rocket-takeoff me-1"></i>
                        Select New Plan
                    </span>
                    <h3 class="card-title mb-2">Choose Your New Plan</h3>
                    <p class="text-muted">Select an upgraded plan below</p>
                </div>';
}

echo '
                
                <!-- Body -->
                <div class="card-body ' . (($is_upgradeable && !empty($available_plans)) ? 'pt-0' : 'pt-4') . '">';

if (!$is_upgradeable) {
    // Show special message for non-upgradeable plans
    if (!empty($upgrade_message)) {
        echo '
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        ' . $upgrade_message . '
                    </div>';
    } else {
        echo '
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Special Plan Notice</strong><br>
                        Your current plan has special pricing and features that cannot be upgraded through this interface. 
                        Please contact member support if you need to make changes to your account.
                    </div>';
    }
    echo '
                    <div class="d-grid gap-2">
                        <a href="/contact" class="btn btn-primary">Contact Member Support</a>
                        <a href="/myaccount/plan-details" class="btn btn-secondary">View Current Plan Details</a>
                    </div>';
} elseif (empty($available_plans)) {
    // User is at the highest tier or no upgrades available
    // Determine if they're at the top tier
    $is_top_tier = false;
    if (strpos($current_plan, 'platinum') !== false || strpos($current_plan, 'gold') !== false) {
        $is_top_tier = true;
    }
    
    if ($is_top_tier) {
        echo '
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="bi bi-trophy-fill text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-success mb-3">You have our best plan!</h5>
                        <p class="text-muted">
                            You are already enjoying our premium ' . htmlspecialchars($current_plan_display ?? '') . ' plan with maximum benefits. 
                            Thank you for being a valued member!
                        </p>
                    </div>
                    
                    <div class="bg-light rounded p-3 mb-3">
                        <h6 class="mb-3"><i class="bi bi-star-fill text-warning me-2"></i>Your Premium Benefits Include:</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Maximum enrollment allocations
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Priority customer support
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                All premium features unlocked
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Ad-free experience
                            </li>
                        </ul>
                    </div>';
    } else {
        // Not top tier but no upgrades available (possibly due to restrictions)
        echo '
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="mb-3">Your plan is optimized</h5>
                        <p class="text-muted">
                            Your current ' . htmlspecialchars($current_plan_display ?? '') . ' plan is perfectly suited for your account type. 
                            No changes are needed at this time.
                        </p>
                    </div>
                    
                    <div class="alert alert-light border" role="alert">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>Did you know?</strong><br>
                        Your plan automatically includes all the features you need based on your account configuration.
                    </div>';
    }
    
    echo '
                    <div class="d-grid gap-2">
                        <a href="/myaccount/plan-details" class="btn btn-primary">
                            <i class="bi bi-list-check me-2"></i>View Full Plan Details
                        </a>
                        <a href="/myaccount/" class="btn btn-outline-secondary">
                            Return to Dashboard
                        </a>
                    </div>';
} else {
    // Plan selector form
    echo '
                    <form method="POST" action="/myaccount/upgrade-checkout.php" id="upgradeForm">
                        ' . $display->inputcsrf_token() . '
                        
                        <!-- Plan dropdown -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Available Plans</label>
                            <select id="planSelector" name="plan_id" class="form-select form-select-lg">
                                <option value="">-- Select a plan --</option>';
    
    foreach ($available_plans as $plan) {
        // Convert price from cents to dollars for display
        $price_in_cents = $plan['price'] ?? 0;
        $price_formatted = number_format($price_in_cents / 100, 2, '.', '');
        
        // Determine billing period display
        $period_display = $plan['billing_period'] ?? '';
        if (empty($period_display)) {
            // Use billing_cycle if billing_period not in features
            $period_display = ($plan['billing_cycle'] == 'one_time') ? 'lifetime' : 'month';
        }
        
        // Build option text
        $price_text = ($plan['billing_cycle'] == 'one_time') ? '$' . $price_formatted . ' one-time' : '$' . $price_formatted . '/' . $period_display;
        $option_text = htmlspecialchars($plan['account_name'] ?? '') . ' - ' . $price_text;
        
        // Add debug info if in debug mode
        if ($debug_mode) {
            $option_text .= ' [ID:' . $plan['id'] . ', Ver:' . htmlspecialchars($plan['version'] ?? 'none') . ', Type:' . htmlspecialchars($plan['account_type'] ?? '') . ']';
        }
        
        echo '
                                <option value="' . $plan['id'] . '" 
                                        data-name="' . htmlspecialchars($plan['account_name'] ?? '') . '" 
                                        data-price="' . htmlspecialchars($price_formatted) . '" 
                                        data-period="' . htmlspecialchars($period_display) . '" 
                                        data-billing-cycle="' . htmlspecialchars($plan['billing_cycle'] ?? 'one_time') . '"
                                        data-enrollments="' . htmlspecialchars($plan['enrollments'] ?? '0') . '" 
                                        data-description="' . htmlspecialchars($plan['description'] ?? '') . '">
                                    ' . $option_text . '
                                </option>';
    }
    
    echo '
                            </select>
                        </div>
                        
                        <!-- New plan details (hidden by default) -->
                        <div id="planDetails" class="bg-light rounded p-3 mb-3" style="display: none;">
                            <h5 class="mb-3">New Plan Details</h5>
                            <div class="d-flex align-items-end mb-3">
                                <span class="display-6 text-primary fw-bold" id="planPrice">$0</span>
                                <span class="text-muted ms-1" id="planPeriod">/month</span>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span id="planEnrollments">0 enrollments per month</span>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span id="planDescription">Plan description</span>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Priority customer support</span>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Cancel anytime</span>
                                </li>
                            </ul>';
    
    // Grandfathered warning if applicable (only shows when plan selected)
    if ($is_grandfathered) {
        echo '
                            <div class="alert alert-warning mb-0" id="grandfatheredWarning" style="display: none;">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <div>
                                        <strong>Important Notice</strong>
                                        <ul class="mb-0 ps-3 mt-2">
                                            <li>You will lose your grandfathered pricing</li>
                                            <li>You cannot return to your current plan</li>
                                            <li>New pricing will apply immediately</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>';
    }
    
    echo '
                        </div>
                        
                        <!-- Submit button -->
                        <button type="submit" class="btn btn-success btn-lg w-100" id="upgradeBtn" disabled>
                            Upgrade Now
                        </button>
                    </form>
                    
                    <!-- Cancel link -->
                    <div class="text-center mt-3">
                        <a href="/myaccount/" class="text-muted text-decoration-none">
                            Cancel and return to dashboard
                        </a>
                    </div>';
}

echo '
                </div><!-- End card-body -->
            </div><!-- End card -->
        </div><!-- End col -->
        
    </div><!-- End row -->
</div><!-- End container -->';

// Add JavaScript for dynamic plan selection
echo '<script>
$(document).ready(function() {
    // Handle debug panel toggle (only in debug mode)
    $("#debugPanel").on("shown.bs.collapse", function() {
        $("button[data-bs-target=\"#debugPanel\"]").html("<i class=\"bi bi-chevron-up\"></i> Hide Debug Info");
    });
    $("#debugPanel").on("hidden.bs.collapse", function() {
        $("button[data-bs-target=\"#debugPanel\"]").html("<i class=\"bi bi-chevron-down\"></i> Show Debug Info");
    });
    
    $("#planSelector").on("change", function() {
        const selectedOption = $(this).find("option:selected");
        const planId = $(this).val();
        
        if (planId) {
            // Get plan data
            const name = selectedOption.data("name");
            const price = selectedOption.data("price");
            const period = selectedOption.data("period");
            const billingCycle = selectedOption.data("billing-cycle");
            const enrollments = selectedOption.data("enrollments");
            const description = selectedOption.data("description");
            
            // Update display
            $("#planDetails").slideDown();
            $("#planPrice").text("$" + price);
            
            // Handle one-time vs recurring billing
            if (billingCycle === "one_time") {
                $("#planPeriod").text(" one-time");
                $("#planEnrollments").text(enrollments + " enrollments");
            } else {
                $("#planPeriod").text("/" + period);
                $("#planEnrollments").text(enrollments + " enrollments per " + period);
            }
            
            $("#planDescription").text(description || "Access to premium features");
            
            // Show grandfathered warning if applicable
            $("#grandfatheredWarning").slideDown();
            
            // Enable button
            $("#upgradeBtn").prop("disabled", false);
        } else {
            // Hide details and disable button
            $("#planDetails").slideUp();
            $("#grandfatheredWarning").slideUp();
            $("#upgradeBtn").prop("disabled", true);
        }
    });
});
</script>';

// Add debug helper section if in debug mode
if ($debug_mode) {
    echo '
    <div class="container mt-5 mb-5">
        <div class="card bg-light">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-bug-fill me-2"></i>Debug Helper - Test Different Plans</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">Click any plan below to test how it would upgrade:</p>
                <div class="row">';
    
    // Fetch all available plans for testing
    $sql = "SELECT p.id, p.account_name, p.account_plan, p.account_type, p.status, p.version, p.price
            FROM bg_products p 
            WHERE p.status = 'active'
            AND p.account_name IS NOT NULL
            ORDER BY p.account_type, p.account_plan, p.id";
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $test_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($test_plans as $plan) {
        $is_current = ($plan['id'] == $current_product_id);
        // Convert price from cents to dollars for display
        $price_in_cents = $plan['price'] ?? 0;
        $price_formatted = number_format($price_in_cents / 100, 2, '.', '');
        
        echo '
                    <div class="col-md-4 mb-3">
                        <a href="/myaccount/upgrade?debug=1&plan_id=' . $plan['id'] . '" 
                           class="btn btn-sm ' . ($is_current ? 'btn-success' : 'btn-outline-secondary') . ' w-100 text-start">
                            <strong>' . htmlspecialchars($plan['account_name'] ?? '') . '</strong>
                            <span class="badge bg-success ms-1">Active</span><br>
                            <small>
                                ID: ' . $plan['id'] . ' | 
                                Type: ' . htmlspecialchars($plan['account_type'] ?? '') . ' | 
                                Version: ' . htmlspecialchars($plan['version'] ?? 'N/A') . '<br>
                                Price: $' . $price_formatted . 
                                ($is_current ? ' <span class="badge bg-white text-success ms-1">CURRENT TEST</span>' : '') . '
                            </small>
                        </a>
                    </div>';
    }
    
    echo '
                </div>
                <hr>
                <p class="text-muted mb-0">
                    <small>
                        <strong>Note:</strong> This debug mode simulates having different base plans to test upgrade paths. 
                        Your actual plan remains unchanged. Only plans with proper upgrade configurations will appear in the dropdown above.
                    </small>
                </p>
            </div>
        </div>
    </div>';
}

// Include footer
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();