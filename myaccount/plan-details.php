<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------

// Include user account details to get proper plan information
include_once($dir['core_components'] . '/user_getaccountdetails.inc');

// Check for URL parameter to view a specific plan (admin feature)
$view_product_id = null;
$view_plan = null;
if (isset($_GET['product_id']) && $account->isadmin()) {
    $view_product_id = intval($_GET['product_id']);
    // Also allow plan parameter for convenience
} elseif (isset($_GET['plan']) && $account->isadmin()) {
    $view_plan = $_GET['plan'];
}

// Get user actual plan from their account data
$user_plan = $view_plan ?? $current_user_data['account_plan'] ?? 'free';
$user_product_id = $view_product_id ?? $current_user_data['account_product_id'] ?? null;

$outputm='';



#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------

if ($app->formposted()) {
    // Handle card override updates
    if (isset($_POST['action']) && $_POST['action'] == 'update_card_override') {
        $card_key = $_POST['card_key'] ?? '';
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($card_key && $product_id && $account->isadmin()) {
            // Process each override field
            $fields = ['title', 'value', 'description', 'icon', 'icon_color', 'excluded'];
            
            foreach ($fields as $field) {
                $override_value = $_POST['override_' . $field] ?? '';
                $feature_name = 'card_' . $card_key . '_' . $field;
                
                if ($override_value !== '') {
                    // Check if override exists
                    $sql = "SELECT id FROM bg_product_features WHERE product_id = :product_id AND name = :name";
                    $stmt = $database->prepare($sql);
                    $stmt->execute(['product_id' => $product_id, 'name' => $feature_name]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        // Update existing
                        $sql = "UPDATE bg_product_features SET value = :value, modify_dt = NOW() WHERE id = :id";
                        $stmt = $database->prepare($sql);
                        $stmt->execute(['value' => $override_value, 'id' => $existing['id']]);
                    } else {
                        // Insert new
                        $sql = "INSERT INTO bg_product_features (product_id, name, value, status, create_dt) 
                                VALUES (:product_id, :name, :value, 'active', NOW())";
                        $stmt = $database->prepare($sql);
                        $stmt->execute(['product_id' => $product_id, 'name' => $feature_name, 'value' => $override_value]);
                    }
                } else {
                    // Remove override if empty
                    $sql = "DELETE FROM bg_product_features WHERE product_id = :product_id AND name = :name";
                    $stmt = $database->prepare($sql);
                    $stmt->execute(['product_id' => $product_id, 'name' => $feature_name]);
                }
            }
            
            // Redirect back with success message
            $redirect_url = $_SERVER['PHP_SELF'];
            if (isset($_GET['product_id'])) {
                $redirect_url .= '?product_id=' . $_GET['product_id'];
            }
            header('Location: ' . $redirect_url);
            exit;
        }
    }
    
    // Handle legacy feature updates
    if (isset($_POST['feature_id'])) {
        $feature_id = $_POST['feature_id'];
        $feature_value = $_POST['feature_value'];
        
        // Update the database with the new value
        $sql = 'UPDATE bg_product_features SET value = :value WHERE id = :id';
        $stmt = $database->prepare($sql);
        $stmt->execute(['value' => $feature_value, 'id' => $feature_id]);
        
        // Optionally, reload the page to reflect changes or handle success messages
        header('Location: '.$_SERVER['PHP_SELF']);
        exit;
    }
}


#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------

// Add v7 theme CSS for content-header-dark class
$additionalstyles = '<link href="/public/css/v7/bg_theme.css" rel="stylesheet">';

$additionalstyles .= '
<style>
/* Modern tab navigation */
.nav-tabs-modern {
    display: flex;
    align-items: center;
    border-bottom: 2px solid #dee2e6;
    gap: 0;
    flex-wrap: wrap;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 1.25rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    background: transparent;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
    border-radius: 0;
    position: relative;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd !important;
    background: none;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .nav-tab-item {
        padding: 0.75rem 0.75rem;
        font-size: 0.875rem;
    }
}

/* Enhanced Plan Details Styles */
.plan-details-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 3rem 1rem;
}

.feature-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 2rem;
    height: 100%;
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
}

.feature-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: #cbd5e0;
}

.feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
}

.feature-icon.primary {
    background: #2d3748;
    color: #FFD700;
}

.feature-icon.success {
    background: #1a1a1a;
    color: #48bb78;
}

.feature-icon.info {
    background: #2d3748;
    color: #4299e1;
}

.feature-icon.warning {
    background: #1a1a1a;
    color: #FFD700;
}

.feature-icon.danger {
    background: #2d3748;
    color: #f56565;
}

.feature-icon.dark {
    background: #1a1a1a;
    color: #a0aec0;
}

.feature-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #2d3748;
}

.feature-value {
    font-size: 2rem;
    font-weight: 800;
    color: #1a1a1a;
    margin-bottom: 1rem;
}

.feature-description {
    color: #718096;
    line-height: 1.7;
    font-size: 1rem;
}

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

.upgrade-section {
    text-align: center;
    margin-top: 3rem;
    padding: 2rem;
    background: #f7fafc;
    border-radius: 15px;
}

@media (max-width: 768px) {
    .feature-card {
        margin-bottom: 1.5rem;
    }
    
    .plan-summary h2 {
        font-size: 2rem;
    }
    
    .plan-summary .price {
        font-size: 2.5rem;
    }
}
</style>
';


$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Dark Header Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>Plan Details</h1>
        <p class="lead">Everything included in your <?php echo htmlspecialchars($userplanname ?? 'current'); ?> plan</p>
    </div>
</div>

<?php

     
// Get plan details based on user actual product ID
if ($user_product_id) {
    $plandatafeatures = $app->plandetail('detailsfull_id', $user_product_id);
} else {
    // Fallback - get plan details by plan name if no product ID
    $plandatafeatures = $app->plandetail('detailsfull_plan', $user_plan);
}

// Ensure we have valid plan data
if (empty($plandatafeatures)) {
    // Set some defaults if plan data is not found
    $plandatafeatures = [
        'plan_pricetag' => ['value' => 'Free'],
        'plan_pricedescription' => ['value' => 'No charge'],
        'max_business_select_tag' => ['value' => 'Limited'],
        'max_business_select_description' => ['value' => 'Limited brand selections available']
    ];
}

// Get the correct plan name and price when viewing different plans
if (isset($_GET['product_id']) && $account->isadmin()) {
    // Get plan details from the database for the viewed product
    $sql = "SELECT account_name, account_plan, price, billing_cycle, description FROM bg_products WHERE id = :product_id";
    $stmt = $database->prepare($sql);
    $stmt->execute(['product_id' => $user_product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $userplanname = $result['account_name'] ?? $result['account_plan'];
        // Override price if we got it from database
        if ($result['price'] !== null) {
            $db_price = $result['price'] / 100; // Convert from cents to dollars
            $plandatafeatures['plan_pricetag']['value'] = '$' . number_format($db_price, 2);
            
            // Set price description based on billing cycle
            $billing_text = '';
            switch($result['billing_cycle']) {
                case 'monthly':
                    $billing_text = 'per month';
                    break;
                case 'annual':
                case 'yearly':
                    $billing_text = 'per year';
                    break;
                case 'one-time':
                    $billing_text = 'one-time payment';
                    break;
                default:
                    $billing_text = $result['billing_cycle'];
            }
            $plandatafeatures['plan_pricedescription']['value'] = $billing_text;
        }
        // Get plan description
        if (!empty($result['description'])) {
            $plandatafeatures['plan_fulldescription']['value'] = $result['description'];
        }
    }
} elseif (!isset($userplanname) || empty($userplanname)) {
    // Set from plan data features if available
    if (isset($plandatafeatures['plan_name'])) {
        $userplanname = $plandatafeatures['plan_name']['value'] ?? ucfirst($user_plan);
    } else {
        $userplanname = ucfirst($user_plan);
    }
}
#breakpoint($plandatafeatures);
function editbuttons($front=[], $back=[]) {
    global $account , $outputm;
    if (!$account->isadmin()) {
        return '';
    }
    $outputx = '<div class="text-end d-flex g-2 ms-auto">';

   # $outputm='';
    if (!empty($front['id'])) {
        $outputx .= '<button type="button" class="btn btn-sm btn-primary fs-12 m-0 me-2 p-0 px-1" data-bs-toggle="modal" data-bs-target="#editModal'.$front['id'].'">Edit Front</button>';

        $outputm .= generateModal($front);
    }
    if (!empty($back['id'])) {
       $outputx .= '<button type="button" class="btn btn-sm btn-primary fs-12 m-0 p-0 px-1" data-bs-toggle="modal" data-bs-target="#editModal'.$back['id'].'">Edit Back</button>';

       
       $outputm .= generateModal($back);
    }
    $outputx.='</div>';
   # breakpoint('<pre>'.$outputm.'</pre>');
    return $outputx.'';

}

function generateModal($item) {
    global $display;
    return '
    <!-- ============================================================================================================================================================= -->
    <div class="modal modal-lg fade" id="editModal'.$item['id'].'" tabindex="-1"  role="dialog" aria-labelledby="editModalLabel'.$item['id'].'" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel'.$item['id'].'">'.$item['title'].'</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="">
                    '.$display->inputcsrf_token().'
                        <input type="hidden" name="feature_id" value="'.$item['id'].'">
                        <div class="mb-3">
                            <label for="featureValue'.$item['id'].'" class="form-label">Edit Value</label>
                            <!-- Pre-populate the input field with the current value -->
                              <textarea class="form-control" id="featureValue'.$item['id'].'" name="feature_value" rows="4">'.htmlspecialchars($item['value']).'</textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    ';
}





// Get plan price for display
// If price is still not set or is $0, try to get it from the database
if (!isset($plandatafeatures['plan_pricetag']['value']) || $plandatafeatures['plan_pricetag']['value'] == '$0' || $plandatafeatures['plan_pricetag']['value'] == 'Free') {
    if ($user_product_id) {
        $sql = "SELECT price, billing_cycle FROM bg_products WHERE id = :product_id";
        $stmt = $database->prepare($sql);
        $stmt->execute(['product_id' => $user_product_id]);
        $price_result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($price_result && $price_result['price'] > 0) {
            $db_price = $price_result['price'] / 100;
            $planPrice = '$' . number_format($db_price, 2);
            
            switch($price_result['billing_cycle']) {
                case 'monthly':
                    $planPriceDesc = 'per month';
                    break;
                case 'annual':
                case 'yearly':
                    $planPriceDesc = 'per year';
                    break;
                case 'one-time':
                    $planPriceDesc = 'one-time payment';
                    break;
                default:
                    $planPriceDesc = $price_result['billing_cycle'];
            }
        } else {
            $planPrice = 'Free';
            $planPriceDesc = 'No charge';
        }
    } else {
        $planPrice = isset($plandatafeatures['plan_pricetag']['value']) ? $plandatafeatures['plan_pricetag']['value'] : 'Free';
        $planPriceDesc = isset($plandatafeatures['plan_pricedescription']['value']) ? $plandatafeatures['plan_pricedescription']['value'] : 'No charge';
    }
} else {
    $planPrice = $plandatafeatures['plan_pricetag']['value'];
    $planPriceDesc = isset($plandatafeatures['plan_pricedescription']['value']) ? $plandatafeatures['plan_pricedescription']['value'] : '';
}

$maxBrands = isset($plandatafeatures['max_business_select_tag']['value']) ? $plandatafeatures['max_business_select_tag']['value'] : 'Unlimited';
$maxBrandsDesc = isset($plandatafeatures['max_business_select_description']['value']) ? $plandatafeatures['max_business_select_description']['value'] : '';

// Helper function for time ago display
if (!function_exists('human_time_diff')) {
    function human_time_diff($timestamp) {
        $diff = time() - $timestamp;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . ' min ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
        return floor($diff / 86400) . ' day ago';
    }
}

// Admin controls and debug info
if ($account->isadmin()) {
    // Track recently viewed plans in session
    if (!isset($_SESSION['admin_viewed_plans'])) {
        $_SESSION['admin_viewed_plans'] = [];
    }
    
    // Add current viewed plan to recent history
    if (isset($_GET['product_id'])) {
        $viewed_id = intval($_GET['product_id']);
        $viewed_time = time();
        
        // Update or add to recently viewed
        $_SESSION['admin_viewed_plans'][$viewed_id] = $viewed_time;
        
        // Clean up old entries (older than 24 hours)
        $cutoff_time = time() - (24 * 60 * 60);
        $_SESSION['admin_viewed_plans'] = array_filter($_SESSION['admin_viewed_plans'], function($time) use ($cutoff_time) {
            return $time > $cutoff_time;
        });
        
        // Keep only last 10 viewed
        arsort($_SESSION['admin_viewed_plans']);
        $_SESSION['admin_viewed_plans'] = array_slice($_SESSION['admin_viewed_plans'], 0, 10, true);
    }
    
    // Get all available plans for the selector
    $sql = "SELECT DISTINCT id as product_id, account_plan, account_name, account_type, billing_cycle, price, status 
            FROM bg_products 
            WHERE status = 'active' 
            ORDER BY account_type, billing_cycle, price";
    $available_plans = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Group plans by account type
    $grouped_plans = [];
    $type_order = ['individual', 'family', 'gift', 'business', 'parental']; // Define order
    
    foreach ($available_plans as $plan) {
        $type_key = strtolower($plan['account_type']);
        if (!isset($grouped_plans[$type_key])) {
            $grouped_plans[$type_key] = [];
        }
        $grouped_plans[$type_key][] = $plan;
    }
    
    // Sort groups by defined order
    $sorted_groups = [];
    foreach ($type_order as $type) {
        if (isset($grouped_plans[$type])) {
            $sorted_groups[$type] = $grouped_plans[$type];
        }
    }
    // Add any remaining types not in our order
    foreach ($grouped_plans as $type => $plans) {
        if (!isset($sorted_groups[$type])) {
            $sorted_groups[$type] = $plans;
        }
    }
    $grouped_plans = $sorted_groups;
    
    // Determine active tab
    $active_tab = 'individual'; // default
    if (isset($_GET['product_id'])) {
        foreach ($available_plans as $plan) {
            if ($plan['product_id'] == $user_product_id) {
                $active_tab = strtolower($plan['account_type']);
                break;
            }
        }
    }
    
    // Get version info for the current plan if viewing
    $version_info = '';
    if ($user_product_id) {
        $sql = "SELECT version FROM bg_product_features WHERE product_id = :product_id LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->execute(['product_id' => $user_product_id]);
        $version_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $version_info = $version_result['version'] ?? 'v3';
    }
    
    echo '<div class="alert alert-warning mb-3 container-fluid" >
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5 class="mb-2"><i class="bi bi-shield-lock"></i> Admin Plan Viewer</h5>';
    
    if ($user_product_id) {
        echo '<div class="mb-2">
                <span class="badge bg-dark me-2">Plan ID: ' . $user_product_id . '</span>
                <span class="badge bg-secondary">Version: ' . htmlspecialchars($version_info) . '</span>
              </div>';
    }
    
    echo '</div>
        </div>';
    
    // Modern tabs for plan categories
    echo '<nav class="nav-tabs-modern mb-3">';
    
    foreach ($grouped_plans as $type => $plans) {
        $tab_active = ($type == $active_tab) ? ' active' : '';
        $type_display = ucfirst($type);
        $count = count($plans);
        
        // Icon for each type
        $icons = [
            'individual' => 'bi-person',
            'family' => 'bi-people',
            'gift' => 'bi-gift',
            'business' => 'bi-building',
            'parental' => 'bi-person-hearts'
        ];
        $icon = $icons[$type] ?? 'bi-box';
        
        echo '<a href="#" class="nav-tab-item' . $tab_active . '" data-tab-target="' . $type . '-panel">
                <i class="' . $icon . ' me-2"></i>' . $type_display . ' 
                <span class="badge bg-secondary ms-1">' . $count . '</span>
            </a>';
    }
    
    echo '</nav>';
    
    // Tab content with pill buttons
    echo '<div class="tab-content">';
    
    foreach ($grouped_plans as $type => $plans) {
        $pane_active = ($type == $active_tab) ? ' active' : ' d-none';
        
        echo '<div class="tab-pane' . $pane_active . '" id="' . $type . '-panel">
            <div class="d-flex flex-wrap gap-3">';
        
        foreach ($plans as $plan) {
            $plan_display_name = $plan['account_name'] ?? $plan['account_plan'];
            $price_display = $plan['price'] > 0 ? '$' . number_format($plan['price'] / 100, 2) : 'Free';
            $billing_display = str_replace('-', ' ', $plan['billing_cycle']);
            $is_active = ($user_product_id == $plan['product_id']);
            
            // Get version for this plan
            $sql_version = "SELECT version FROM bg_product_features WHERE product_id = :product_id LIMIT 1";
            $stmt_version = $database->prepare($sql_version);
            $stmt_version->execute(['product_id' => $plan['product_id']]);
            $plan_version = $stmt_version->fetch(PDO::FETCH_ASSOC);
            $version_display = $plan_version['version'] ?? 'v3';
            
            // Check if this version matches the website plan version
            $is_current_version = ($version_display === ($website['plan_version'] ?? 'v3'));
            $version_class = $is_current_version ? 'fw-bold badge bg-success text-white' : '';
            
            // Button styling
            if ($is_active) {
                $btn_class = 'btn-primary';
                $badge_class = 'bg-white text-primary';
                $text_class = 'text-white';
            } elseif (isset($_SESSION['admin_viewed_plans'][$plan['product_id']])) {
                $btn_class = 'btn-outline-primary';
                $badge_class = 'bg-primary';
                $text_class = 'text-muted';
            } else {
                $btn_class = 'btn-outline-secondary';
                $badge_class = 'bg-secondary';
                $text_class = 'text-muted';
            }
            
            echo '<a href="?product_id=' . $plan['product_id'] . '" class="btn ' . $btn_class . ' position-relative px-3 py-2">
                <div>
                    <strong class="d-block mb-1">' . htmlspecialchars($plan_display_name) . '</strong>
                    <small class="text-nowrap">' . $price_display . ' / ' . ucfirst($billing_display) . '</small>
                    <div class="mt-1">
                        <small class="' . $text_class . '">ID: ' . $plan['product_id'] . ' <span class="mx-4">•</span> <span class="' . $version_class . '">' . htmlspecialchars($version_display) . '</span></small>
                    </div>
                </div>
            </a>';
        }
        
        echo '</div>
        </div>';
    }
    
    echo '</div>'; // End tab content
    
    // Bottom controls
    echo '<hr class="mt-3 mb-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>';
    
    if (isset($_GET['product_id']) || isset($_GET['plan'])) {
        echo '<span class="text-info"><i class="bi bi-info-circle"></i> Viewing: <strong>' . 
             htmlspecialchars($userplanname) . '</strong> (ID: ' . htmlspecialchars($user_product_id ?? 'N/A') . ')</span>';
    }
    
    echo '</div>
            <div>
                <a href="?" class="btn btn-sm btn-secondary">View My Plan</a>';
    
    if (isset($_GET['debug'])) {
        echo ' <a href="?" class="btn btn-sm btn-outline-secondary">Hide Debug</a>';
    } else {
        echo ' <a href="?debug=1' . (isset($_GET['product_id']) ? '&product_id=' . $_GET['product_id'] : '') . '" class="btn btn-sm btn-outline-secondary">Show Debug</a>';
    }
    
    echo '
            </div>
        </div>';
    
    if (isset($_GET['product_id']) || isset($_GET['plan'])) {
        echo '<hr>
        <p class="mb-0 text-info"><i class="bi bi-info-circle"></i> Currently viewing: <strong>' . htmlspecialchars($userplanname) . '</strong> plan (Product ID: ' . htmlspecialchars($user_product_id ?? 'N/A') . ')</p>';
    }
    
    if (isset($_GET['debug'])) {
        echo '<hr>
        <h6>Debug Information:</h6>
        <small>
        <p class="mb-1">User ID: ' . htmlspecialchars($current_user_data['user_id'] ?? 'Not set') . '</p>
        <p class="mb-1">User Actual Plan: ' . htmlspecialchars($current_user_data['account_plan'] ?? 'Not set') . '</p>
        <p class="mb-1">User Product ID: ' . htmlspecialchars($current_user_data['account_product_id'] ?? 'Not set') . '</p>
        <p class="mb-1">Viewing Plan: ' . htmlspecialchars($user_plan) . '</p>
        <p class="mb-1">Viewing Product ID: ' . htmlspecialchars($user_product_id ?? 'Not set') . '</p>
        <p class="mb-0">Plan Features Loaded: ' . (empty($plandatafeatures) ? 'No' : 'Yes (' . count($plandatafeatures) . ' features)') . '</p>
        </small>';
    }
    
    echo '</div>';
}
// Remove left panel and use full width
echo '    
<div class="plan-details-container">
        ';
// Get ALL plan features from bg_product_features
$planDescription = '';
$plan_highlights = [];
$feature_cards = [];

// STEP 1: Load default feature cards from bg_config
$sql = "SELECT config_key, config_data, display_order 
        FROM bg_config 
        WHERE config_type = 'plan_feature_card' 
        AND status = 'active' 
        ORDER BY display_order, config_key";
$default_cards_result = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$default_cards = [];
foreach ($default_cards_result as $card) {
    $card_data = json_decode($card['config_data'], true);
    if ($card_data) {
        $card_data['card_key'] = $card['config_key']; // Store the key for override matching
        $default_cards[$card['config_key']] = $card_data;
    }
}

// STEP 2: Fetch plan-specific overrides if we have a product ID
$features_by_name = [];
if ($user_product_id) {
    $sql = "SELECT name, value FROM bg_product_features 
            WHERE product_id = :product_id 
            AND status = 'active' 
            ORDER BY name";
    $stmt = $database->prepare($sql);
    $stmt->execute(['product_id' => $user_product_id]);
    $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize features by name
    foreach ($features as $feature) {
        $features_by_name[$feature['name']] = $feature['value'];
    }
    
    // Extract plan description
    if (isset($features_by_name['plan_description'])) {
        $planDescription = $features_by_name['plan_description'];
    }
    
    // Extract plan highlights
    for ($i = 1; $i <= 6; $i++) {
        if (isset($features_by_name['plan_highlight_' . $i])) {
            $plan_highlights[] = $features_by_name['plan_highlight_' . $i];
        }
    }
}

// STEP 3: Build feature cards by merging defaults with overrides
if (!empty($default_cards)) {
    // Get the account type for this plan to filter cards
    $account_type = 'individual'; // default
    if ($user_product_id) {
        $sql = "SELECT account_type FROM bg_products WHERE id = :product_id";
        $stmt = $database->prepare($sql);
        $stmt->execute(['product_id' => $user_product_id]);
        $product_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product_info) {
            $account_type = strtolower($product_info['account_type']);
        }
    }
    
    // Use default cards from bg_config
    foreach ($default_cards as $card_key => $card_data) {
        // Check if this card is excluded for this specific plan
        // Look for card_{key}_excluded override
        if (isset($features_by_name['card_' . $card_key . '_excluded']) && 
            $features_by_name['card_' . $card_key . '_excluded'] === 'true') {
            continue; // Skip this card - it's explicitly excluded
        }
        
        // Check if this card is restricted to certain plans
        if (isset($card_data['plans']) && is_array($card_data['plans'])) {
            // This card is plan-specific, check if current plan is in the list
            $allowed = false;
            foreach ($card_data['plans'] as $allowed_plan) {
                if (stripos($allowed_plan, $account_type) !== false || 
                    stripos($user_plan, $allowed_plan) !== false) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                continue; // Skip this card for this plan
            }
        }
        
        $card = [
            'title' => $card_data['title'] ?? '',
            'value' => $card_data['value'] ?? '',
            'description' => $card_data['description'] ?? '',
            'icon' => $card_data['icon'] ?? 'bi-star',
            'icon_color' => $card_data['icon_color'] ?? 'primary',
            'card_key' => $card_key // Store key for editing
        ];
        
        // Apply plan-specific overrides from bg_product_features
        // Format: card_{card_key}_{property}
        // Example: card_brands_value, card_brands_description
        $override_prefix = 'card_' . $card_key . '_';
        
        if (isset($features_by_name[$override_prefix . 'title'])) {
            $card['title'] = $features_by_name[$override_prefix . 'title'];
        }
        if (isset($features_by_name[$override_prefix . 'value'])) {
            $card['value'] = $features_by_name[$override_prefix . 'value'];
        }
        if (isset($features_by_name[$override_prefix . 'description'])) {
            $card['description'] = $features_by_name[$override_prefix . 'description'];
        }
        if (isset($features_by_name[$override_prefix . 'icon'])) {
            $card['icon'] = $features_by_name[$override_prefix . 'icon'];
        }
        if (isset($features_by_name[$override_prefix . 'icon_color'])) {
            $card['icon_color'] = $features_by_name[$override_prefix . 'icon_color'];
        }
        
        $feature_cards[] = $card;
    }
} else {
    // Fallback to legacy format if no bg_config cards defined
    for ($i = 1; $i <= 6; $i++) {
        if (isset($features_by_name['feature_' . $i . '_title'])) {
            $feature_cards[] = [
                'title' => $features_by_name['feature_' . $i . '_title'] ?? '',
                'value' => $features_by_name['feature_' . $i . '_value'] ?? '',
                'description' => $features_by_name['feature_' . $i . '_description'] ?? '',
                'icon' => $features_by_name['feature_' . $i . '_icon'] ?? 'bi-star',
                'icon_color' => $features_by_name['feature_' . $i . '_icon_color'] ?? 'primary'
            ];
        }
    }
}

// Fallback descriptions if not in database
if (empty($planDescription)) {
    $plan_descriptions = [
        'Free' => 'Get started with Birthday Gold at no cost. Perfect for trying out our service with limited features.',
        'Plus' => 'Our most popular plan for individuals who want to maximize their birthday rewards with full access to all features.',
        'Premium' => 'The ultimate birthday experience with VIP support and exclusive perks.',
        'Life' => 'You pay a one-time $40. One and Done and you get lifelong access to all the features we provide and will get any new features we add to the service automatically with no other charges/fees ever.',
        'Business Gold' => 'Professional plan for businesses to manage employee birthday programs and customer engagement.',
        'Individual Gold' => 'Enhanced individual plan with premium features and priority support.',
        'Family Gold' => 'Perfect for families to manage multiple birthday celebrations with shared features and family-friendly rewards.'
    ];
    
    if (isset($plan_descriptions[$userplanname])) {
        $planDescription = $plan_descriptions[$userplanname];
    }
}

// Fallback highlights if not in database
if (empty($plan_highlights)) {
    if ($planPrice == 'Free' || $planPrice == '$0.00') {
        $plan_highlights = [
            'Basic enrollment features',
            'Limited brand selections',
            'Email reminders',
            'Community support'
        ];
    } elseif (stripos($userplanname, 'business') !== false) {
        $plan_highlights = [
            'Unlimited employee accounts',
            'Bulk enrollment management',
            'Analytics dashboard',
            'Priority business support',
            'Custom branding options'
        ];
    } elseif (stripos($userplanname, 'life') !== false) {
        $plan_highlights = [
            'Lifetime access - never pay again',
            'All current and future features',
            'Unlimited brand enrollments',
            'Priority support forever',
            'Early access to new features'
        ];
    } else {
        $plan_highlights = [
            'Unlimited brand enrollments',
            'Advanced reminder system',
            'Birthday tour planner',
            'Priority email support',
            'Exclusive partner offers'
        ];
    }
}

echo '
    <!-- Plan Summary Card -->
    <div class="plan-summary">
        <h2>' . htmlspecialchars($userplanname) . '</h2>
        <div class="price">' . htmlspecialchars($planPrice) . '</div>
        <div class="billing-cycle mb-3">' . htmlspecialchars($planPriceDesc) . '</div>';

if (!empty($planDescription)) {
    echo '<div class="plan-description mb-3" style="max-width: 600px; margin: 0 auto; font-size: 1.1rem; line-height: 1.6; opacity: 0.95;">
            ' . htmlspecialchars($planDescription) . '
          </div>';
}

if (!empty($plan_highlights)) {
    echo '<div class="plan-highlights mt-4" style="max-width: 500px; margin: 0 auto;">
            <div class="row g-2">';
    foreach ($plan_highlights as $highlight) {
        echo '<div class="col-12">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="bi bi-check-circle-fill text-warning me-2"></i>
                    <span style="opacity: 0.9;">' . htmlspecialchars($highlight) . '</span>
                </div>
              </div>';
    }
    echo '</div>
          </div>';
}

echo '
    </div>

    <!-- Features Grid -->
    <div class="row g-4">';

// If no feature cards found in database, use defaults based on plan
if (empty($feature_cards)) {
    // Default feature cards for backward compatibility
    $feature_cards = [
        [
            'title' => 'Brands You Can Register',
            'value' => $maxBrands,
            'description' => $maxBrandsDesc,
            'icon' => 'bi-building-check',
            'icon_color' => 'success',
            'card_key' => 'brands'
        ],
        [
            'title' => 'Birthday Reminders',
            'value' => 'Automated',
            'description' => 'Never miss out on your birthday rewards! We will send you timely reminders throughout your birthday month so you can claim every reward.',
            'icon' => 'bi-bell-fill',
            'icon_color' => 'warning',
            'card_key' => 'reminders'
        ],
        [
            'title' => 'Celebration Planner',
            'value' => 'Tour Maps',
            'description' => 'Plan your perfect birthday celebration! Generate custom tour schedules and maps to maximize your birthday rewards collection.',
            'icon' => 'bi-calendar-event',
            'icon_color' => 'info',
            'card_key' => 'planner'
        ],
        [
            'title' => 'Priority Support',
            'value' => 'Email',
            'description' => 'Get help when you need it. Our support team is ready to assist you with any questions about your birthday rewards.',
            'icon' => 'bi-headset',
            'icon_color' => 'primary',
            'card_key' => 'support'
        ],
        [
            'title' => 'Reward Tracking',
            'value' => 'Dashboard',
            'description' => 'Keep track of all your birthday rewards in one place. See what is available, what you have claimed, and what is coming up.',
            'icon' => 'bi-gift-fill',
            'icon_color' => 'danger',
            'card_key' => 'tracking'
        ],
        [
            'title' => 'Social Celebrations',
            'value' => 'Coming Soon',
            'description' => 'Connect with other birthday celebrants! Share your experiences and celebrate together in our upcoming social features.',
            'icon' => 'bi-people-fill',
            'icon_color' => 'dark',
            'card_key' => 'social'
        ]
    ];
}

// Get upgrade options to determine if we should show upgrade cards
$should_show_upgrade_cards = !empty($upgrade_options['available_plans']);

// Display feature cards
foreach ($feature_cards as $index => $card) {
    // Skip upgrade-related cards if user has no available upgrades
    if (!$should_show_upgrade_cards) {
        // Check if this is an upgrade card (by title or key)
        $card_title_lower = strtolower($card['title'] ?? '');
        $card_key_lower = strtolower($card['card_key'] ?? '');
        if (strpos($card_title_lower, 'upgrade') !== false || 
            strpos($card_key_lower, 'upgrade') !== false) {
            continue; // Skip this card
        }
    }
    
    echo '
        <div class="col-lg-4 col-md-6">
            <div class="feature-card position-relative">';
    
    // Add admin edit button if admin
    if ($account->isadmin() && isset($card['card_key']) && $user_product_id) {
        echo '<div class="position-absolute top-0 end-0 p-2">';
        echo '<button type="button" class="btn btn-sm btn-outline-primary" 
                data-bs-toggle="modal" 
                data-bs-target="#editCardModal_' . $card['card_key'] . '"
                title="Edit card overrides">
                <i class="bi bi-pencil"></i>
              </button>';
        echo '</div>';
    }
    
    echo '
                <div class="feature-icon ' . htmlspecialchars($card['icon_color'] ?? 'primary') . '">
                    <i class="' . htmlspecialchars($card['icon']) . '"></i>
                </div>
                <h3 class="feature-title">' . htmlspecialchars($card['title']) . '</h3>
                <div class="feature-value">' . htmlspecialchars($card['value']) . '</div>
                <p class="feature-description">' . $card['description'] . '</p>';
    
    echo '
            </div>
        </div>';
}

echo '    </div>';

    // Upgrade Section - Use the new getUpgradeOptions function to determine if we should show upgrade options
    $upgrade_options = $account->getUpgradeOptions();
    
    // Only show upgrade section if there are available upgrades
    if ($upgrade_options['is_upgradeable'] && !empty($upgrade_options['available_plans'])) {
        // Customize message based on current plan type
        if ($upgrade_options['is_free_plan']) {
            echo '
        <div class="upgrade-section">
            <h3 class="mb-3">Ready to unlock more birthday rewards?</h3>
            <p class="mb-4">Upgrade your plan to get access to more brands, priority support, and exclusive features!</p>
            <a href="/myaccount/upgrade" class="btn btn-primary btn-lg">View Upgrade Options</a>
        </div>';
        } else {
            // For non-free plans with available upgrades
            echo '
        <div class="upgrade-section">
            <h3 class="mb-3">Want even more benefits?</h3>
            <p class="mb-4">Explore our premium plans for additional features and rewards!</p>
            <a href="/myaccount/upgrade" class="btn btn-primary btn-lg">View Upgrade Options</a>
        </div>';
        }
    } elseif (!$upgrade_options['is_upgradeable'] && !empty($upgrade_options['upgrade_message'])) {
        // Show custom message for non-upgradeable plans
        echo '
        <div class="upgrade-section">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                ' . htmlspecialchars($upgrade_options['upgrade_message']) . '
            </div>
        </div>';
    } elseif ($upgrade_options['is_top_tier']) {
        // User is at the highest tier - celebrate!
        echo '
        <div class="upgrade-section text-center">
            <i class="bi bi-trophy-fill text-warning" style="font-size: 2.5rem;"></i>
            <h3 class="mt-3 mb-3">You have our best plan!</h3>
            <p class="text-muted">Thank you for being a premium member. You are enjoying all available features and benefits.</p>
        </div>';
    }
    // If none of the above conditions are met, don't show an upgrade section at all

echo '
</div>';

// Generate edit modals for admin
if ($account->isadmin() && $user_product_id && !empty($feature_cards)) {
    foreach ($feature_cards as $card) {
        if (!isset($card['card_key'])) continue;
        
        $card_key = $card['card_key'];
        echo '
        <!-- Edit Modal for ' . htmlspecialchars($card_key) . ' -->
        <div class="modal fade" id="editCardModal_' . $card_key . '" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Card Override: ' . htmlspecialchars($card['title']) . '</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="" id="editCardForm_' . $card_key . '">
                            ' . $display->inputcsrf_token() . '
                            <input type="hidden" name="action" value="update_card_override">
                            <input type="hidden" name="card_key" value="' . htmlspecialchars($card_key) . '">
                            <input type="hidden" name="product_id" value="' . $user_product_id . '">
                            
                            <div class="alert alert-info">
                                <small>Leave fields empty to use default values. Only fill in what you want to override for this plan.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Title Override</label>
                                <input type="text" class="form-control" name="override_title" 
                                    value="' . htmlspecialchars($features_by_name['card_' . $card_key . '_title'] ?? '') . '"
                                    placeholder="Default: ' . htmlspecialchars($card['title']) . '">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Value Override</label>
                                <input type="text" class="form-control" name="override_value"
                                    value="' . htmlspecialchars($features_by_name['card_' . $card_key . '_value'] ?? '') . '"
                                    placeholder="Default: ' . htmlspecialchars($card['value']) . '">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description Override</label>
                                <textarea class="form-control" name="override_description" rows="3"
                                    placeholder="Default: ' . htmlspecialchars($card['description']) . '">' . 
                                    htmlspecialchars($features_by_name['card_' . $card_key . '_description'] ?? '') . '</textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Icon Override (Bootstrap Icons class)</label>
                                        <input type="text" class="form-control" name="override_icon"
                                            value="' . htmlspecialchars($features_by_name['card_' . $card_key . '_icon'] ?? '') . '"
                                            placeholder="Default: ' . htmlspecialchars($card['icon']) . '">
                                        <small class="text-muted">e.g., bi-star-fill, bi-trophy, etc.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Icon Color Override</label>
                                        <select class="form-select" name="override_icon_color">
                                            <option value="">Default: ' . htmlspecialchars($card['icon_color'] ?? 'primary') . '</option>
                                            <option value="primary"' . ($features_by_name['card_' . $card_key . '_icon_color'] ?? '' == 'primary' ? ' selected' : '') . '>Primary (Blue)</option>
                                            <option value="success"' . ($features_by_name['card_' . $card_key . '_icon_color'] ?? '' == 'success' ? ' selected' : '') . '>Success (Green)</option>
                                            <option value="warning"' . ($features_by_name['card_' . $card_key . '_icon_color'] ?? '' == 'warning' ? ' selected' : '') . '>Warning (Yellow)</option>
                                            <option value="danger"' . ($features_by_name['card_' . $card_key . '_icon_color'] ?? '' == 'danger' ? ' selected' : '') . '>Danger (Red)</option>
                                            <option value="info"' . ($features_by_name['card_' . $card_key . '_icon_color'] ?? '' == 'info' ? ' selected' : '') . '>Info (Light Blue)</option>
                                            <option value="dark"' . ($features_by_name['card_' . $card_key . '_icon_color'] ?? '' == 'dark' ? ' selected' : '') . '>Dark (Gray)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="override_excluded" value="true" id="exclude_' . $card_key . '"
                                        ' . (isset($features_by_name['card_' . $card_key . '_excluded']) && $features_by_name['card_' . $card_key . '_excluded'] === 'true' ? 'checked' : '') . '>
                                    <label class="form-check-label" for="exclude_' . $card_key . '">
                                        <strong>Exclude this card from this plan</strong><br>
                                        <small>Check this to completely hide this card for this specific plan</small>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="editCardForm_' . $card_key . '" class="btn btn-primary">Save Overrides</button>
                    </div>
                </div>
            </div>
        </div>';
    }
}

echo $outputm;

// Add JavaScript for tab switching
echo '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handle modern tab navigation
    const tabItems = document.querySelectorAll(".nav-tab-item");
    const tabPanes = document.querySelectorAll(".tab-pane");
    
    tabItems.forEach(function(tab) {
        tab.addEventListener("click", function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            tabItems.forEach(function(t) {
                t.classList.remove("active");
            });
            
            // Add active class to clicked tab
            this.classList.add("active");
            
            // Get target panel
            const targetId = this.getAttribute("data-tab-target");
            
            // Hide all panels
            tabPanes.forEach(function(pane) {
                pane.classList.add("d-none");
                pane.classList.remove("active");
            });
            
            // Show target panel
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.remove("d-none");
                targetPane.classList.add("active");
            }
        });
    });
});
</script>
';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
