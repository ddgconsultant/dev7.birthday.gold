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
// Remove left panel and use full width
echo '    
<div class="plan-details-container">
        ';
     
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

// Get the correct plan name when viewing different plans
if (isset($_GET['product_id']) && $account->isadmin()) {
    // Get plan name from the database for the viewed product
    $sql = "SELECT plan_name FROM bg_products WHERE product_id = :product_id";
    $stmt = $database->prepare($sql);
    $stmt->execute(['product_id' => $user_product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $userplanname = $result['plan_name'];
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
$planPrice = isset($plandatafeatures['plan_pricetag']['value']) ? $plandatafeatures['plan_pricetag']['value'] : '$0';
$planPriceDesc = isset($plandatafeatures['plan_pricedescription']['value']) ? $plandatafeatures['plan_pricedescription']['value'] : '';
$maxBrands = isset($plandatafeatures['max_business_select_tag']['value']) ? $plandatafeatures['max_business_select_tag']['value'] : 'Unlimited';
$maxBrandsDesc = isset($plandatafeatures['max_business_select_description']['value']) ? $plandatafeatures['max_business_select_description']['value'] : '';

// Admin controls and debug info
if ($account->isadmin()) {
    // Get all available plans for the selector
    $sql = "SELECT DISTINCT product_id, plan_name, status FROM bg_products WHERE status = 'active' ORDER BY product_id";
    $available_plans = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="alert alert-warning mb-3">
        <h5><i class="bi bi-shield-lock"></i> Admin Plan Viewer</h5>
        <div class="row align-items-center">
            <div class="col-md-8">
                <p class="mb-2"><strong>View Different Plans:</strong></p>
                <div class="btn-group" role="group">';
    
    foreach ($available_plans as $plan) {
        $active = ($user_product_id == $plan['product_id']) ? 'btn-primary' : 'btn-outline-primary';
        echo '<a href="?product_id=' . $plan['product_id'] . '" class="btn btn-sm ' . $active . '">' . 
             htmlspecialchars($plan['plan_name']) . ' (ID: ' . $plan['product_id'] . ')</a>';
    }
    
    echo '
                </div>
            </div>
            <div class="col-md-4 text-end">
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

echo '
    <!-- Plan Summary Card -->
    <div class="plan-summary">
        <h2>' . htmlspecialchars($userplanname) . ' Plan</h2>
        <div class="price">' . htmlspecialchars($planPrice) . '</div>
        <div class="billing-cycle">' . htmlspecialchars($planPriceDesc) . '</div>
    </div>

    <!-- Features Grid -->
    <div class="row g-4">
        <!-- Brands Registered -->
        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon success">
                    <i class="bi bi-building-check"></i>
                </div>
                <h3 class="feature-title">Brands You Can Register</h3>
                <div class="feature-value">' . htmlspecialchars($maxBrands) . '</div>
                <p class="feature-description">' . htmlspecialchars($maxBrandsDesc) . '</p>
                '.editbuttons($plandatafeatures['max_business_select_tag'] ?? [], $plandatafeatures['max_business_select_description'] ?? []).'
            </div>
        </div>

        <!-- Birthday Reminders -->
        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon warning">
                    <i class="bi bi-bell-fill"></i>
                </div>
                <h3 class="feature-title">Birthday Reminders</h3>
                <div class="feature-value">Automated</div>
                <p class="feature-description">Never miss out on your birthday rewards! We will send you timely reminders throughout your birthday month so you can claim every reward.</p>
            </div>
        </div>

        <!-- Celebration Planning -->
        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon info">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <h3 class="feature-title">Celebration Planner</h3>
                <div class="feature-value">Tour Maps</div>
                <p class="feature-description">Plan your perfect birthday celebration! Generate custom tour schedules and maps to maximize your birthday rewards collection.</p>
            </div>
        </div>

        <!-- Email Support -->
        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon primary">
                    <i class="bi bi-headset"></i>
                </div>
                <h3 class="feature-title">Priority Support</h3>
                <div class="feature-value">Email</div>
                <p class="feature-description">Get help when you need it. Our support team is ready to assist you with any questions about your birthday rewards.</p>
            </div>
        </div>

        <!-- Reward Tracking -->
        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon danger">
                    <i class="bi bi-gift-fill"></i>
                </div>
                <h3 class="feature-title">Reward Tracking</h3>
                <div class="feature-value">Dashboard</div>
                <p class="feature-description">Keep track of all your birthday rewards in one place. See what is available, what you have claimed, and what is coming up.</p>
            </div>
        </div>

        <!-- Coming Soon: Social Features -->
        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon dark">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h3 class="feature-title">Social Celebrations</h3>
                <div class="feature-value">Coming Soon</div>
                <p class="feature-description">Connect with other birthday celebrants! Share your experiences and celebrate together in our upcoming social features.</p>
            </div>
        </div>
    </div>';

    // Upgrade Section for Free Users
    if ($user_plan === 'free') {
        echo '
        <div class="upgrade-section">
            <h3 class="mb-3">Ready to unlock more birthday rewards?</h3>
            <p class="mb-4">Upgrade to Gold and get access to unlimited brands, priority support, and exclusive features!</p>
            <a href="/myaccount/upgrade-plan" class="btn btn-primary btn-lg">Upgrade to Gold</a>
        </div>';
    } elseif ($user_plan === 'gold') {
        echo '
        <div class="upgrade-section">
            <h3 class="mb-3">Want lifetime access?</h3>
            <p class="mb-4">Upgrade to our Lifetime plan and never worry about renewals again!</p>
            <a href="/myaccount/upgrade-plan" class="btn btn-primary btn-lg">Get Lifetime Access</a>
        </div>';
    }

echo '
</div>';
echo $outputm;

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
