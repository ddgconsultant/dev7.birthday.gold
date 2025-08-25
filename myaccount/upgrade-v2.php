<?php
/**
 * Plan Upgrade Page V2 - Using Account::getUpgradeOptions() function
 * Test implementation of the refactored upgrade logic
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page setup
$pagetitle = "Upgrade Your Plan (V2)";

// Debug mode
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Setup options for the function
$options = [];
if ($debug_mode) {
    // Allow testing with different parameters
    if (isset($_GET['plan_id'])) {
        $options['product_id'] = (int)$_GET['plan_id'];
        
        // Fetch the test plan details to get account type
        $sql = "SELECT * FROM bg_products WHERE id = :id AND status = 'active'";
        $stmt = $database->prepare($sql);
        $stmt->execute(['id' => $options['product_id']]);
        $test_plan = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($test_plan) {
            $options['account_plan'] = $test_plan['account_plan'];
            $options['account_type'] = $_GET['account_type'] ?? $test_plan['account_type'];
        }
    }
    if (isset($_GET['account_type'])) {
        $options['account_type'] = $_GET['account_type'];
    }
    $options['debug'] = true;
}

// Get upgrade options using the new function
$upgrade_data = $account->getUpgradeOptions($options);

// Extract data from the function result
$available_plans = $upgrade_data['available_plans'];
$is_upgradeable = $upgrade_data['is_upgradeable'];
$upgrade_message = $upgrade_data['upgrade_message'];
$is_grandfathered = $upgrade_data['is_grandfathered'];
$is_free_plan = $upgrade_data['is_free_plan'];
$is_top_tier = $upgrade_data['is_top_tier'];
$current_plan_display = $upgrade_data['current_plan_display'];
$debug_info = $upgrade_data['debug_info'] ?? [];

// Get current plan details for display (if not in debug mode, use actual user data)
$current_product_id = $options['product_id'] ?? ($current_user_data['account_product_id'] ?? null);
$current_plan = $options['account_plan'] ?? ($current_user_data['account_plan'] ?? 'free');
$current_type = $options['account_type'] ?? ($current_user_data['account_type'] ?? 'user');

// Get additional current plan details for the left panel
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
        $price_in_cents = $result['price'] ?? 0;
        $current_price = number_format($price_in_cents / 100, 2, '.', '');
        $current_period = $result['billing_period'] ?? ($result['billing_cycle'] == 'one_time' ? 'lifetime' : 'month');
        $current_enrollments = $result['enrollments'] ?? '0';
        $current_description = $result['description'] ?? '';
    }
}

// Minimal custom styles
$additionalstyles .= '
<style>
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

.v2-badge {
    position: fixed;
    top: 60px;
    right: 20px;
    z-index: 1000;
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

// V2 indicator badge
echo '
<div class="v2-badge">
    <span class="badge bg-info text-white">
        <i class="bi bi-flask me-1"></i>V2 Testing - Using getUpgradeOptions()
    </span>
</div>';

// Show debug banner if in debug mode
if ($debug_mode) {
    echo '
    <div class="alert alert-warning mb-0 rounded-0 text-center">
        <i class="bi bi-bug-fill me-2"></i>
        <strong>DEBUG MODE</strong> - Testing upgrade from: 
        <strong>' . htmlspecialchars($current_plan_display ?? '') . '</strong> 
        (ID: ' . ($current_product_id ?? 'N/A') . ', Type: ' . htmlspecialchars($current_type) . ')
        <a href="/myaccount/upgrade-v2" class="ms-3 btn btn-sm btn-outline-dark">Exit Debug</a>
    </div>';
    
    // Quick test links
    echo '
    <div class="alert alert-secondary mb-0 rounded-0 text-center py-2">
        <small><strong>Quick Tests:</strong></small>
        <div class="btn-group btn-group-sm ms-3" role="group">
            <a href="?debug=1&plan_id=' . ($current_product_id ?? 431) . '&account_type=user" 
               class="btn btn-outline-primary ' . ($current_type == 'user' ? 'active' : '') . '">Test as User</a>
            <a href="?debug=1&plan_id=' . ($current_product_id ?? 431) . '&account_type=parental" 
               class="btn btn-outline-primary ' . ($current_type == 'parental' ? 'active' : '') . '">Test as Parental</a>
            <a href="?debug=1&plan_id=' . ($current_product_id ?? 431) . '&account_type=family" 
               class="btn btn-outline-primary ' . ($current_type == 'family' ? 'active' : '') . '">Test as Family</a>
            <a href="?debug=1&plan_id=' . ($current_product_id ?? 431) . '&account_type=business" 
               class="btn btn-outline-primary ' . ($current_type == 'business' ? 'active' : '') . '">Test as Business</a>
        </div>
        <small class="ms-3 text-muted">(Changes what plans you can upgrade to)</small>
    </div>';
    
    // Debug info panel
    if (!empty($debug_info)) {
        echo '
        <div class="alert alert-info mb-0 rounded-0">
            <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#debugPanel">
                <i class="bi bi-chevron-down"></i> Show Debug Info
            </button>
            <div class="collapse mt-3" id="debugPanel">
                <pre class="bg-white p-3 rounded">' . htmlspecialchars(json_encode($debug_info, JSON_PRETTY_PRINT)) . '</pre>
            </div>
        </div>';
    }
}

echo '
<div class="container mt-5 py-5">
    <div class="row justify-content-center gx-lg-5">
        
        <!-- Desktop welcome content (hidden on mobile) - Shows current plan details -->
        <div class="col-lg-5 d-none d-lg-block welcome-content pe-lg-4">
            <h2>Upgrade Your <span>Plan</span></h2>
            <p>You are currently on the <strong>' . htmlspecialchars($current_plan_display ?? '') . '</strong> plan</p>
            
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
                    <p class="mb-0 small">You have special legacy pricing that will be lost if you upgrade.</p>
                </div>
            </div>';
}

echo '
        </div>
        
        <!-- Upgrade card -->
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm">';

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
    // Show upgrade options
    echo '
                    <form method="POST" action="/myaccount/upgrade-checkout.php" id="upgradeForm">
                        ' . $display->inputcsrf_token() . '
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Available Plans</label>
                            <select id="planSelector" name="plan_id" class="form-select form-select-lg">
                                <option value="">-- Select a plan --</option>';
    
    foreach ($available_plans as $plan) {
        $price_text = ($plan['billing_cycle'] == 'one_time') 
            ? '$' . $plan['price_formatted'] . ' one-time' 
            : '$' . $plan['price_formatted'] . '/' . $plan['period_display'];
        
        $option_text = htmlspecialchars($plan['account_name'] ?? '') . ' - ' . $price_text;
        
        echo '
                                <option value="' . $plan['id'] . '" 
                                        data-name="' . htmlspecialchars($plan['account_name'] ?? '') . '" 
                                        data-price="' . htmlspecialchars($plan['price_formatted']) . '" 
                                        data-period="' . htmlspecialchars($plan['period_display']) . '" 
                                        data-billing-cycle="' . htmlspecialchars($plan['billing_cycle'] ?? 'recurring') . '"
                                        data-enrollments="' . htmlspecialchars($plan['enrollments'] ?? '0') . '" 
                                        data-description="' . htmlspecialchars($plan['description'] ?? '') . '">
                                    ' . $option_text . '
                                </option>';
    }
    
    echo '
                            </select>
                        </div>
                        
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
                            </ul>';
    
    if ($is_grandfathered) {
        echo '
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Important: You will lose your grandfathered pricing</strong>
                            </div>';
    }
    
    echo '
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg w-100" id="upgradeBtn" disabled>
                            Upgrade Now
                        </button>
                    </form>
                    
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

// Add JavaScript for plan selection
echo '<script>
$(document).ready(function() {
    $("#planSelector").on("change", function() {
        const selectedOption = $(this).find("option:selected");
        const planId = $(this).val();
        
        if (planId) {
            const price = selectedOption.data("price");
            const period = selectedOption.data("period");
            const billingCycle = selectedOption.data("billing-cycle");
            const enrollments = selectedOption.data("enrollments");
            const description = selectedOption.data("description");
            
            $("#planDetails").slideDown();
            $("#planPrice").text("$" + price);
            
            if (billingCycle === "one_time") {
                $("#planPeriod").text(" one-time");
                $("#planEnrollments").text(enrollments + " enrollments");
            } else {
                $("#planPeriod").text("/" + period);
                $("#planEnrollments").text(enrollments + " enrollments per " + period);
            }
            
            $("#planDescription").text(description || "Access to premium features");
            $("#upgradeBtn").prop("disabled", false);
        } else {
            $("#planDetails").slideUp();
            $("#upgradeBtn").prop("disabled", true);
        }
    });
});
</script>';

// Include footer
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();