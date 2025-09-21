<?php
$addClasses[] = 'productmanager';
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// ProductManager is auto-instantiated as $productmanager by site-controller

#-------------------------------------------------------------------------------
# HANDLE FOREIGN COUNTRIES
#-------------------------------------------------------------------------------
$system->checkCountrySupport();

#-------------------------------------------------------------------------------
# HANDLE INITIALIZE
#-------------------------------------------------------------------------------
$gotorouter = false;
if (isset($_REQUEST['reset'])) {
    $gotorouter = true; 
    $session->unset('force_error_message');
}

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$signup_process = $_REQUEST;
$plan = false;
$pagemessage = '';
$selectedAccountType = $_REQUEST['account_type'] ?? 'user';

// Get the product version from site configuration
// This determines which version of products to show
$selectedVersion = $_REQUEST['version'] ?? $website['plan_version']; // Uses site's plan version, allow override for testing

// Check if we're coming back from createaccount (back button)
// Retrieve previously selected values from session
$existingSignupData = $session->get('signup_process_data', []);
if (!empty($existingSignupData) && empty($_REQUEST['account_type']) && empty($_REQUEST['account_plan'])) {
    // Restore previous selections if no new selections in request
    if (isset($existingSignupData['account_type'])) {
        $selectedAccountType = $existingSignupData['account_type'];
    }
    if (isset($existingSignupData['account_plan'])) {
        // We need to get the encoded ID for the plan
        $selectedPlanCode = $existingSignupData['account_plan'];
    }
    if (isset($existingSignupData['account_plan_id'])) {
        $selectedPlanId = $qik->encodeId($existingSignupData['account_plan_id']);
    }
}

// Store URL plan_id for later processing after plans are loaded
$urlPlanId = null;
if (isset($_GET['plan_id']) && !empty($_GET['plan_id'])) {
    $urlPlanId = $_GET['plan_id'];
}

// Capture URL parameters to carry forward
$urlParams = [];
if (isset($_REQUEST['promo'])) $urlParams['promo'] = $_REQUEST['promo'];
if (isset($_REQUEST['promo_code'])) $urlParams['promo_code'] = $_REQUEST['promo_code'];
if (isset($_REQUEST['ref'])) $urlParams['ref'] = $_REQUEST['ref'];

// Debug logging
if ($mode === 'dev') {
    error_log('[NEWSIGNUP] Initial signup_process data: ' . json_encode($signup_process));
    error_log('[NEWSIGNUP] URL params: ' . json_encode($urlParams));
}
if (isset($_REQUEST['referral'])) $urlParams['referral'] = $_REQUEST['referral'];
if (isset($_REQUEST['source'])) $urlParams['source'] = $_REQUEST['source'];
if (isset($_REQUEST['utm_source'])) $urlParams['utm_source'] = $_REQUEST['utm_source'];
if (isset($_REQUEST['utm_medium'])) $urlParams['utm_medium'] = $_REQUEST['utm_medium'];
if (isset($_REQUEST['utm_campaign'])) $urlParams['utm_campaign'] = $_REQUEST['utm_campaign'];

#-------------------------------------------------------------------------------
# HANDLE AJAX REQUESTS
#-------------------------------------------------------------------------------
if (isset($_REQUEST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_REQUEST['ajax_action']) {
        case 'get_plans':
            $accountType = $_REQUEST['account_type'] ?? 'user';
            $plans = $productmanager->getProductsWithFeatures($accountType, $selectedVersion);
            
            // Format for frontend
            $response = [];
            foreach ($plans as $plan) {
                $response[] = [
                    'id' => $plan['encoded_id'],
                    'plan_code' => $plan['account_plan'],
                    'name' => $plan['account_name'],
                    'description' => $plan['description'],
                    'price' => $plan['price'],
                    'price_formatted' => $qik->convertamount($plan['price']),
                    'features' => array_column($plan['features'], 'value'),
                    'is_recommended' => (strpos(strtolower($plan['account_plan']), 'gold') !== false)
                ];
            }
            
            echo json_encode(['success' => true, 'plans' => $response]);
            exit;
            
        case 'validate_promo':
            $promoCode = $_REQUEST['promo_code'] ?? '';
            $productId = $qik->decodeId($_REQUEST['product_id'] ?? '');
            
            if ($productId && $promoCode) {
                $validation = $productmanager->validatePromoCode($promoCode, $productId);
                echo json_encode($validation);
            } else {
                echo json_encode(['valid' => false, 'message' => 'Invalid request']);
            }
            exit;
            
        case 'calculate_price':
            $productId = $qik->decodeId($_REQUEST['product_id'] ?? '');
            $promoCode = $_REQUEST['promo_code'] ?? null;
            
            if ($productId) {
                $pricing = $productmanager->calculatePrice($productId, $promoCode);
                echo json_encode($pricing);
            } else {
                echo json_encode(['error' => 'Invalid product']);
            }
            exit;
            
        case 'get_account_info':
            $accountType = $_REQUEST['account_type'] ?? '';
            
            if ($accountType) {
                $config = $productmanager->getAccountTypeConfig($accountType);
                echo json_encode([
                    'success' => true,
                    'context_text' => $config['context_text'] ?? '',
                    'description' => $config['description'] ?? ''
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid account type']);
            }
            exit;
    }
}

#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSION
#-------------------------------------------------------------------------------
if ($app->formposted() && empty($signup_process['account_plan'])) {
    $pagemessage = '<div class="alert alert-danger alert-dismissible show" role="alert">Please select a plan.</div>';
    $session->set('force_error_message', $pagemessage);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

#-------------------------------------------------------------------------------
# HANDLE PLAN LINKS (for direct linking to plans)
#-------------------------------------------------------------------------------
if (isset($_REQUEST['plan'])) {
    $planbynamedata = $productmanager->getProduct($_REQUEST['plan'], 'plan_name');
    if ($planbynamedata) {
        $signup_process['account_plan'] = $planbynamedata['encoded_id'];
        $selectedAccountType = $planbynamedata['account_type'];
    }
}

#-------------------------------------------------------------------------------
# FORWARD USER TO REGISTRATION
#-------------------------------------------------------------------------------
if ($app->formposted() && !empty($signup_process['account_plan'])) {
    // Don't unset the data yet - we need to preserve it!
    
    $planid = ($qik->decodeId($signup_process['account_plan'] ?? false));
    
    if ($planid) {
        // Clear old data but preserve the new data we're building
        $new_signup_data = [];
        $new_signup_data['account_plan_id'] = $planid;
        $plandata = $productmanager->getProduct($planid, 'id');
        
        // Security validation: Ensure product is valid for current version and account type
        if ($plandata && 
            $plandata['version'] === $selectedVersion && 
            $plandata['status'] === 'active' &&
            $plandata['account_type'] === ($signup_process['account_type'] ?? 'user')) {
            $new_signup_data['plandata'] = $plandata;
            $new_signup_data['account_type'] = $signup_process['account_type'] ?? 'user';
            $new_signup_data['account_plan'] = $plandata['account_plan'];
            $new_signup_data['account_cost'] = $plandata['price'];
            $new_signup_data['account_verification'] = $plandata['account_verification'];
            $gotourl = $plandata['redirect_url'] ?? '/createaccount.php';
            
            // DEBUG: Force the URL for testing
            if (strpos($gotourl, '/register') !== false) {
                $gotourl = '/createaccount.php';
            }
            
            // Pass along URL parameters
            foreach ($urlParams as $key => $value) {
                $new_signup_data[$key] = $value;
            }
        } else {
            // Log potential security issue
            session_tracking('invalid_product_selection', [
                'attempted_plan_id' => $planid,
                'version' => $selectedVersion,
                'account_type' => $signup_process['account_type'] ?? 'unknown'
            ]);
            
            $transferpage['url'] = $_SERVER['PHP_SELF'];
            $transferpage['message'] = 'Invalid plan selection. Please choose from available options.';
            $system->endpostpage($transferpage);
            exit;
        }
        
        // Debug log the session data being set
        error_log('[NEWSIGNUP] Setting session data: ' . json_encode($new_signup_data));
        
        // Set the NEW signup data with account_plan_id properly set
        $session->set('signup_process_data', $new_signup_data);
        header('Location: ' . $gotourl);
        exit;
    } else {
        $transferpage['url'] = $_SERVER['PHP_SELF'];
        $transferpage['message'] = 'Invalid plan selected';
        $system->endpostpage($transferpage);
        exit;
    }
}

#-------------------------------------------------------------------------------
# GET DYNAMIC DATA
#-------------------------------------------------------------------------------
$accountTypes = $productmanager->getAvailableAccountTypes($selectedVersion);

// Load ALL plans for ALL account types upfront to eliminate AJAX delays
$allPlansByType = [];
foreach ($accountTypes as $accountType) {
    $typePlans = $productmanager->getProductsWithFeatures($accountType['account_type'], $selectedVersion);
    $allPlansByType[$accountType['account_type']] = $typePlans;
}

// Now process URL plan_id if provided (after plans are loaded)
if ($urlPlanId) {
    foreach ($allPlansByType as $type => $plans) {
        foreach ($plans as $plan) {
            if ($plan['encoded_id'] == $urlPlanId) {
                $selectedAccountType = $type;
                $selectedPlanId = $urlPlanId;
                break 2;
            }
        }
    }
}

// Get plans for the currently selected account type
$availablePlans = $allPlansByType[$selectedAccountType] ?? [];
$accountTypeConfig = $productmanager->getAccountTypeConfig($selectedAccountType);
$planCount = count($availablePlans);

#-------------------------------------------------------------------------------
# HANDLE SIGNUP MODE
#-------------------------------------------------------------------------------
$signupmode = $session->get('signupmode', isset($_GET['signupmode']) ? $_GET['signupmode'] : '');
$buttonsize = '';
$signupexit = '';

switch ($signupmode) {
    case 'upgrade':
        $kioskmode = false;
        $signup = false;
        break;
    case 'tabletkiosk':
        $kioskmode = true;
        $signup = true;
        break;
    default:
        $kioskmode = false;
        $signup = true;
        break;
}

if ($signupmode != '') {
    $headerattribute['rawheader'] = true;
    $buttonsize = 'btn-lg';
    $signupexit = '<a href="/logout"><i class="bi bi-x-square text-info m-1"></i></a>';
    $footerattribute['rawfooter'] = true;
    $session->set('signupmode', $signupmode);
    if ($session->get('referral_userid', '') == '') 
        $session->set('referral_userid', $current_user_data['user_id']);
}

#-------------------------------------------------------------------------------
# ERROR MESSAGE HANDLING
#-------------------------------------------------------------------------------
$transferpage = $system->startpostpage();
if (empty($transferpage['message'])) {
    $transferpage['message'] = $session->get('force_error_message', '');
}
$session->unset('force_error_message');

#-------------------------------------------------------------------------------
# PAGE CONFIGURATION
#-------------------------------------------------------------------------------
$page_title = "Create Your Account - Birthday.Gold";
$page_description = "Sign up for Birthday.Gold and start receiving birthday rewards from hundreds of brands";

#-------------------------------------------------------------------------------
# ADDITIONAL STYLES
#-------------------------------------------------------------------------------
$additionalstyles .= '
<link href="/public/css/signup_styles.css" rel="stylesheet">
<style>
/* Hide skip to main content link */
.sr-only {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0,0,0,0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

.sr-only-focusable:focus {
    position: absolute !important;
    width: auto !important;
    height: auto !important;
    padding: 0.5rem 1rem !important;
    margin: 0 !important;
    overflow: visible !important;
    clip: auto !important;
    white-space: normal !important;
    z-index: 9999 !important;
    background: #000 !important;
    color: #fff !important;
    text-decoration: none !important;
    top: 0 !important;
    left: 0 !important;
}
/* Responsive headline sizing */
.header h1 {
    font-size: 1.75rem !important; /* Default for mobile - keep readable */
    font-weight: 700 !important;
    color: #212529 !important;
    margin-bottom: 0.5rem !important;
    line-height: 1.2 !important;
}

/* Tablet and up */
@media (min-width: 768px) {
    .header h1 {
        font-size: 2.25rem !important;
    }
}

/* Desktop and up */
@media (min-width: 992px) {
    .header h1 {
        font-size: 2.75rem !important;
    }
}

/* Large desktop */
@media (min-width: 1200px) {
    .header h1 {
        font-size: 3.25rem !important;
    }
}

/* XL desktop */
@media (min-width: 1400px) {
    .header h1 {
        font-size: 3.5rem !important;
    }
}

/* Subtitle/byline styling */
.header p {
    font-size: 1rem !important; /* Mobile */
    color: #6c757d !important;
}

@media (min-width: 768px) {
    .header p {
        font-size: 1.5rem !important;
    }
}

@media (min-width: 992px) {
    .header p {
        font-size: 1.75rem !important;
    }
}





/* Ensure Bootstrap grid works properly */
.plan-card-wrapper {
    height: 100%;
    position: relative;
    padding: 10px;
    overflow: visible; /* Allow checkmark to extend outside */
    z-index: 1;
}

.plan-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative; /* For absolute positioning of checkmark */
    overflow: visible; /* Allow checkmark to extend outside */
}

.plan-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.plan-features {
    flex-grow: 1;
}

/* Checkmark badge for selected plans */
.plan-checkmark-badge {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 30px;
    height: 30px;
    background: #198754;
    color: white;
    border-radius: 50%;
    display: none; /* Start with display none instead of opacity */
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    opacity: 0;
    transform: scale(0);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

/* Show checkmark when plan is selected - sibling selector */
.plan-radio:checked ~ .plan-checkmark-badge {
    display: flex !important;
    opacity: 1 !important;
    transform: scale(1) !important;
}

/* Position checkmark relative to wrapper */
.plan-card-wrapper {
    position: relative;
}

.plan-card-wrapper .plan-checkmark-badge {
    position: absolute;
    top: 0;
    right: 0;
    z-index: 9999 !important;
    pointer-events: none; /* Prevent blocking clicks */
}

/* Override Bootstrap column overflow */
.row, .col, [class*="col-"] {
    overflow: visible !important;
}

/* Ensure containers allow overflow */
.container, .container-fluid {
    overflow: visible !important;
}

/* Radio button visibility */

/* Modern Tab Interface with Radio Buttons */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 1.5rem;
    gap: 0;
    overflow: visible; /* Changed from hidden to visible */
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
    cursor: pointer;
    white-space: nowrap;
    display: inline-block;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
    border-bottom-color: #adb5bd;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.nav-tab-item:hover:not(.active) {
    border-bottom-color: #adb5bd;
}

/* Active tab styling using class */
.nav-tab-item.active {
    color: #198754 !important;
    border-bottom: 3px solid #198754 !important;
    margin-bottom: -2px !important; /* Ensure it overlaps the container border */
    background: none;
    z-index: 1; /* Bring it forward */
    position: relative;
}

/* Do not move active tab on hover */
.nav-tab-item.active:hover {
    transform: none;
    box-shadow: none;
    background: none;
    border-bottom-color: #198754 !important;
}

.nav-tab-item i {
    font-size: 1.1rem;
}

/* Settings tab (if needed) */
.nav-tab-item.settings-tab {
    margin-left: auto;
}

/* Mobile responsive tabs */
@media (max-width: 767px) {
    .nav-tab-item {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .nav-tab-item span {
        /* Keep text visible on mobile for better UX */
    }
    
    .nav-tab-item i {
        margin-right: 0.5rem;
    }
}

/* Tab content with radio button control */
.tab-content {
    margin-top: 1rem;
    overflow: visible; /* Allow checkmarks to extend outside */
}

.tab-content .tab-pane {
    display: none;
    overflow: visible; /* Allow checkmarks to extend outside */
}

/* Show tab pane when corresponding tab radio is checked */
#tab-user:checked ~ .tab-content .tab-pane[data-account-type="user"],
#tab-parental:checked ~ .tab-content .tab-pane[data-account-type="parental"],
#tab-business:checked ~ .tab-content .tab-pane[data-account-type="business"],
#tab-family:checked ~ .tab-content .tab-pane[data-account-type="family"],
#tab-personal:checked ~ .tab-content .tab-pane[data-account-type="personal"],
#tab-giftcertificate:checked ~ .tab-content .tab-pane[data-account-type="giftcertificate"],
#tab-gift:checked ~ .tab-content .tab-pane[data-account-type="gift"] {
    display: block !important;
    animation: fadeIn 0.3s ease-in-out;
}

/* All plan cards have light gray border and background by default */
.plan-card {
    display: block;
    width: 100%;
    text-align: left;
    border: 2px solid #dee2e6; /* Light gray border for all plans */
    background: rgba(248, 249, 250, 0.3); /* Very light gray background */
    position: relative;
    transition: all 0.3s ease;
}

/* Subtle green tint for popular/recommended plans */
.plan-card.recommended {
    border-color: #c3e6cb; /* Very light green-gray for popular plans */
    background: linear-gradient(135deg, rgba(248, 249, 250, 0.3) 0%, rgba(199, 230, 203, 0.1) 100%); /* Subtle green gradient */
}

/* Green selection when radio button is checked OR has selected class - with checkmark */
.plan-radio:checked + .plan-card,
.plan-card.selected {
    border-color: #198754 !important; /* Dark green border */
    border-width: 3px !important;
    box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.15);
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.08) 0%, rgba(25, 135, 84, 0.15) 100%) !important;
}

/* Hover effect for plan cards */
.plan-card:hover {
    border-color: #adb5bd;
    background: rgba(248, 249, 250, 0.5);
    cursor: pointer;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

// Array of welcoming headlines - randomly selected
$headlines = [
    "Awesome! Let's Get Started 🎉",
    "Welcome! You're Gonna Love This",
    "Sweet! Let's Create Your Account",
    "Perfect! Let's Do This Together",
    "Exciting! Your Birthday Rewards Await",
    "Yes! Let's Make This Happen",
    "Fantastic! Ready to Get Started?"
];

// Select a random headline
$selectedHeadline = $headlines[array_rand($headlines)];

// Professional byline that explains the process
$byline = "Choose your account type and plan below. Takes less than 60 seconds!";
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="header text-center mt-5 mb-4">
                <h1><?php echo $selectedHeadline; ?></h1>
                <p class="mb-0"><?php echo $byline; ?></p>
            </div>

            <!-- Account Type & Plan Selection -->
            <div class="card p-3 p-md-4 p-lg-5">
        <?php
        // Error Message Display
        if (!empty($transferpage['message'])) {
          
            echo $display->formaterrormessage($transferpage['message']);
        }
        ?>

            <h3 class="pt-0 mt-0">Pick who this is for:</h3>
            
            <!-- Single Form wrapping everything -->
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="signupForm">
                <?php echo $display->inputcsrf_token(); ?>
                <input type="hidden" name="selector" value="mobile">
                <?php 
                // Include URL parameters as hidden fields
                foreach ($urlParams as $key => $value) {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }
                ?>
                
                <!-- Tab Radio Buttons (hidden) placed first -->
                <?php
                $isFirst = true;
                session_tracking('selected_account_type', $accountTypes);
                foreach ($accountTypes as $accountType) {
                    $isChecked = ($accountType['account_type'] == $selectedAccountType) ? ' checked' : '';
                    
                    // For first tab, if nothing is selected, make it active
                    if ($isFirst && empty($selectedAccountType)) {
                        $isChecked = ' checked';
                        $selectedAccountType = $accountType['account_type'];
                    }
                    
                    echo '<input type="radio" name="account_type_tab" id="tab-' . $accountType['account_type'] . '" 
                            value="' . $accountType['account_type'] . '"' . $isChecked . ' 
                            class="tab-radio" style="display: none;">';
                    $isFirst = false;
                }
                ?>
                
                <!-- Tab Labels -->
                <div class="nav-tabs-modern" id="accountTypeTabs">
                    <?php
                    foreach ($accountTypes as $accountType) {
                        $config = $productmanager->getAccountTypeConfig($accountType['account_type']);
                        echo '<label for="tab-' . $accountType['account_type'] . '" class="nav-tab-item">
                                <i class="bi ' . $config['icon'] . ' me-2"></i>' . $config['short_label'] . '
                              </label>';
                    }
                    ?>
                </div>

                <!-- Context Info with Learn More button -->
                <div class="context-info" id="contextInfo">
                    <div class="info-text">
                        <i class="bi bi-info-circle info-icon"></i>
                        <span id="contextText"><?php echo $accountTypeConfig['context_text']; ?></span>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#accountTypeInfoModal" title="Learn more">
                        <i class="bi bi-info-circle learn-more-icon d-inline d-md-none"></i>
                        <span class="learn-more-text d-none d-md-inline">Learn More</span>
                    </button>
                </div>

                <!-- Tab Content Panels with Plans -->
                <div class="tab-content" id="accountTypeTabContent">
    <?php 
    $isFirstTab = true;
    foreach ($accountTypes as $accountType): 
        $accountTypePlans = $allPlansByType[$accountType['account_type']] ?? [];
    ?>
    <div class="tab-pane" 
         data-account-type="<?php echo $accountType['account_type']; ?>">
        
        <h3 class="mt-4 mb-3">Choose your plan:</h3>
        
        <?php if (count($accountTypePlans) == 1): 
    ?>
        <!-- 1 Card: Centered with ~70% width on desktop -->
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8">
                    <?php
                    // Single plan - render without additional column wrapper
                    $plan = $accountTypePlans[0]; // Get the single plan
                    $isRecommended = (strpos(strtolower($plan['account_plan']), 'gold') !== false);
                    
                    // Check if this plan was previously selected
                    $isSelected = false;
                    if (isset($selectedPlanId) && $plan['encoded_id'] == $selectedPlanId) {
                        $isSelected = true;
                    } elseif (isset($selectedPlanCode) && $plan['account_plan'] == $selectedPlanCode) {
                        $isSelected = true;
                    }
                    ?>
                    <div class="plan-card-wrapper<?php echo $isSelected ? ' selected' : ''; ?>">
                        <input type="radio" 
                               name="account_plan_selection" 
                               id="plan-<?php echo $accountType['account_type'] . '-' . $plan['encoded_id']; ?>" 
                               value="<?php echo $accountType['account_type'] . ':' . $plan['encoded_id']; ?>"
                               <?php echo $isSelected ? 'checked' : ''; ?>
                               class="plan-radio" 
                               style="display: none;">
                        <label for="plan-<?php echo $accountType['account_type'] . '-' . $plan['encoded_id']; ?>" 
                               class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?><?php echo $isSelected ? ' selected' : ''; ?>" 
                               data-plan="<?php echo $plan['account_plan']; ?>" 
                               data-plan-id="<?php echo $plan['encoded_id']; ?>"
                               data-price="<?php echo $plan['price']; ?>">
                            
                            <?php if ($isRecommended): ?>
                                <div class="recommended-badge">POPULAR</div>
                            <?php endif; ?>
                            
                            <div class="plan-header">
                                <div class="plan-icon">
                                    <?php
                                    // Dynamic icon based on plan name
                                    $planIcon = 'bi-award'; // default
                                    if (strpos($plan['account_plan'], 'free') !== false) {
                                        $planIcon = 'bi-person';
                                    } elseif (strpos($plan['account_plan'], 'gold') !== false) {
                                        $planIcon = 'bi-star-fill';
                                    } elseif (strpos($plan['account_plan'], 'life') !== false) {
                                        $planIcon = 'bi-infinity';
                                    } elseif (strpos($plan['account_plan'], 'business') !== false) {
                                        $planIcon = 'bi-building';
                                    } elseif (strpos($plan['account_plan'], 'family') !== false) {
                                        $planIcon = 'bi-people';
                                    }
                                    ?>
                                    <i class="bi <?php echo $planIcon; ?>"></i>
                                </div>
                                <h3 class="plan-title"><?php echo htmlspecialchars($plan['account_name']); ?></h3>
                            </div>
                            <div class="plan-price"><?php echo $qik->convertamount($plan['price']); ?></div>
                            <div class="plan-price-note">
                                <?php
                                // Dynamic price note
                                if ($plan['price'] == 0) {
                                    echo 'Forever free';
                                } elseif (strpos(strtolower($plan['account_plan']), 'life') !== false) {
                                    echo 'Lifetime access';
                                } else {
                                    echo 'One-time payment';
                                }
                                ?>
                            </div>
                            
                            <?php if (!empty($plan['features'])): ?>
                                <ul class="plan-features">
                                    <?php foreach ($plan['features'] as $feature): ?>
                                        <li><?php echo htmlspecialchars($feature['value']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </label>
                        <div class="plan-checkmark-badge">✓</div>
                    </div>
                </div>
            </div>
        </div>
    
    <?php else: ?>
        <!-- Multiple Cards: Use appropriate grid layout -->
        <div class="container">
            <div class="row g-4 justify-content-center">
                <?php
                // Determine column classes based on plan count
                $colClasses = '';
                if ($planCount == 2) {
                    $colClasses = 'col-12 col-md-6'; // 2 columns on desktop
                } elseif ($planCount == 3) {
                    $colClasses = 'col-12 col-md-4'; // 3 columns on desktop
                } else {
                    // 4+ cards: 2 columns on desktop
                    $colClasses = 'col-12 col-md-6';
                }
                
                foreach ($accountTypePlans as $plan):
                    $isRecommended = (strpos(strtolower($plan['account_plan']), 'gold') !== false);
                    
                    // Check if this plan was previously selected
                    $isSelected = false;
                    if (isset($selectedPlanId) && $plan['encoded_id'] == $selectedPlanId) {
                        $isSelected = true;
                    } elseif (isset($selectedPlanCode) && $plan['account_plan'] == $selectedPlanCode) {
                        $isSelected = true;
                    }
                ?>
                    <div class="<?php echo $colClasses; ?>">
                        <div class="plan-card-wrapper h-100<?php echo $isSelected ? ' selected' : ''; ?>">
                            <input type="radio" 
                                   name="account_plan_selection" 
                                   id="plan-<?php echo $accountType['account_type'] . '-' . $plan['encoded_id']; ?>" 
                                   value="<?php echo $accountType['account_type'] . ':' . $plan['encoded_id']; ?>"
                                   <?php echo $isSelected ? 'checked' : ''; ?>
                                   class="plan-radio" 
                                   style="display: none;">
                            <label for="plan-<?php echo $accountType['account_type'] . '-' . $plan['encoded_id']; ?>" 
                                   class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?><?php echo $isSelected ? ' selected' : ''; ?>" 
                                   data-plan="<?php echo $plan['account_plan']; ?>" 
                                   data-plan-id="<?php echo $plan['encoded_id']; ?>"
                                   data-price="<?php echo $plan['price']; ?>">
                                
                                <?php if ($isRecommended): ?>
                                    <div class="recommended-badge">POPULAR</div>
                                <?php endif; ?>
                                
                                <div class="plan-header">
                                    <div class="plan-icon">
                                        <?php
                                        // Dynamic icon based on plan name
                                        $planIcon = 'bi-award'; // default
                                        if (strpos($plan['account_plan'], 'free') !== false) {
                                            $planIcon = 'bi-person';
                                        } elseif (strpos($plan['account_plan'], 'gold') !== false) {
                                            $planIcon = 'bi-star-fill';
                                        } elseif (strpos($plan['account_plan'], 'life') !== false) {
                                            $planIcon = 'bi-infinity';
                                        } elseif (strpos($plan['account_plan'], 'business') !== false) {
                                            $planIcon = 'bi-building';
                                        } elseif (strpos($plan['account_plan'], 'family') !== false) {
                                            $planIcon = 'bi-people';
                                        }
                                        ?>
                                        <i class="bi <?php echo $planIcon; ?>"></i>
                                    </div>
                                    <h3 class="plan-title"><?php echo htmlspecialchars($plan['account_name']); ?></h3>
                                </div>
                                <div class="plan-price"><?php echo $qik->convertamount($plan['price']); ?></div>
                                <div class="plan-price-note">
                                    <?php
                                    // Dynamic price note
                                    if ($plan['price'] == 0) {
                                        echo 'Forever free';
                                    } elseif (strpos(strtolower($plan['account_plan']), 'life') !== false) {
                                        echo 'Lifetime access';
                                    } else {
                                        echo 'One-time payment';
                                    }
                                    ?>
                                </div>
                                
                                <?php if (!empty($plan['features'])): ?>
                                    <ul class="plan-features">
                                        <?php foreach ($plan['features'] as $feature): ?>
                                            <li><?php echo htmlspecialchars($feature['value']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </label>
                            <div class="plan-checkmark-badge">✓</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    </div>
    <?php 
    $isFirstTab = false;
    endforeach; 
    ?>
</div>

                <div class="row justify-content-center mt-4">
                    <div class="col-12 col-md-6 col-lg-5">
                        <button type="submit" class="btn-primary-custom w-100" id="continueBtn" style="border-radius: 25px;" disabled>
                            Select a Plan to Continue
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <footer class="border-top mt-4">
            <div class="container py-4">
                <div class="row text-center">
                    <div class="col-12 mb-2">
                    <small class="text-muted" style="font-size: 0.9rem;">
                            Already have an account? 
                            <a href="/login" class="text-decoration-none text-success fw-medium">Sign in</a>
                </small>
                    </div>
                    <div class="col-12">
                    <small class="text-muted" style="font-size: 0.9rem;">
                            Have a gift certificate? 
                            <a href="/redeem" class="text-decoration-none text-success fw-medium">Redeem here</a>
                </small>
                    </div>
                </div>
            </div>
        </footer>
        
        </div> <!-- End col -->
    </div> <!-- End row -->
</div> <!-- End container-fluid -->

<!-- Other Account Types Modal -->
<div class="modal fade" id="otherAccountsModal" tabindex="-1" aria-labelledby="otherAccountsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="otherAccountsModalLabel">Other Account Types</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group list-group-flush">
                    <?php
                    // Show remaining account types in modal
                    $displayedInModal = 0;
                    foreach ($accountTypes as $accountType) {
                        if ($displayedInModal >= 3) { // Skip first 3 that are already displayed
                            $config = $productmanager->getAccountTypeConfig($accountType['account_type']);
                            echo '<button type="button" class="list-group-item list-group-item-action d-flex align-items-center" data-account-type="' . $accountType['account_type'] . '">
                                    <div class="me-3">
                                        <i class="bi ' . $config['icon'] . ' fs-4 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">' . $config['label'] . '</h6>
                                        <small class="text-muted">' . $config['description'] . '</small>
                                    </div>
                                    <div class="selection-check d-none">
                                        <i class="bi bi-check-circle-fill text-primary"></i>
                                    </div>
                                  </button>';
                        }
                        $displayedInModal++;
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="modalSelectBtn" disabled>
                    <i class="bi bi-check-lg me-2"></i>Select Account Type
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Account Type Information Modal -->
<div class="modal fade" id="accountTypeInfoModal" tabindex="-1" aria-labelledby="accountTypeInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountTypeInfoModalLabel">Account Type Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="accountTypeInfoContent">
                <div class="account-type-details">
                    <?php
                    foreach ($accountTypes as $accountType) {
                        $config = $productmanager->getAccountTypeConfig($accountType['account_type']);
                        echo '<div class="mb-4">
                                <h6><i class="bi ' . $config['icon'] . ' me-2"></i>' . $config['label'] . '</h6>
                                <p>' . $config['description'] . '</p>
                                <small class="text-muted">Available plans: ' . $accountType['plan_count'] . '</small>
                              </div>';
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<?php echo $signupexit; ?>

<!-- Enhanced JavaScript -->
<script>
// Store page data for JavaScript
const pageData = {
    ajaxUrl: '<?php echo $_SERVER['PHP_SELF']; ?>',
    csrfToken: '<?php echo $session->get('csrf_token'); ?>',
    selectedVersion: '<?php echo $selectedVersion; ?>',
    preselectedAccountType: '<?php echo $selectedAccountType; ?>',
    preselectedPlanId: '<?php echo isset($selectedPlanId) ? $selectedPlanId : ''; ?>'
};

// Checkmark badge management
const CheckmarkManager = {
    // Add checkmark to a plan card wrapper
    addCheckmark: function(wrapper) {
        // Remove all existing checkmarks first
        this.removeAllCheckmarks();
        
        // Create new checkmark element
        const checkmark = document.createElement('div');
        checkmark.className = 'plan-checkmark-badge';
        checkmark.innerHTML = '✓';
        
        // Append to wrapper (outside the overflow:hidden card)
        wrapper.appendChild(checkmark);
    },
    
    // Remove all checkmarks from the page
    removeAllCheckmarks: function() {
        document.querySelectorAll('.plan-checkmark-badge').forEach(badge => {
            badge.remove();
        });
    },
    
    // Initialize checkmark for preselected plan
    initializePreselected: function() {
        const preselectedWrapper = document.querySelector('.plan-card-wrapper.selected');
        if (preselectedWrapper) {
            this.addCheckmark(preselectedWrapper);
        }
    }
};

// Simple form handler for radio button approach
document.addEventListener('DOMContentLoaded', function() {
    // Fallback JavaScript to show/hide tabs if CSS doesn't work
    const tabRadios = document.querySelectorAll('input[name="account_type_tab"]');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    function showTabPane(accountType) {
        // Hide all panes
        tabPanes.forEach(pane => {
            pane.style.display = 'none';
        });
        
        // Show the selected pane
        const selectedPane = document.querySelector('.tab-pane[data-account-type="' + accountType + '"]');
        if (selectedPane) {
            selectedPane.style.display = 'block';
        }
    }
    
    // Initialize - show the checked tab
    const checkedTab = document.querySelector('input[name="account_type_tab"]:checked');
    if (checkedTab) {
        showTabPane(checkedTab.value);
        // Set initial active tab styling
        const activeLabel = document.querySelector('label[for="tab-' + checkedTab.value + '"]');
        if (activeLabel) {
            activeLabel.classList.add('active');
        }
    }
    
    // Handle tab changes
    tabRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const accountType = this.value;
            
            // Show the corresponding tab pane
            showTabPane(accountType);
            
            // Update active tab styling
            const allTabLabels = document.querySelectorAll('.nav-tab-item');
            allTabLabels.forEach(label => {
                label.classList.remove('active');
            });
            const activeLabel = document.querySelector('label[for="tab-' + accountType + '"]');
            if (activeLabel) {
                activeLabel.classList.add('active');
            }
            
            // Disable Continue button when switching tabs
            const continueBtn = document.getElementById('continueBtn');
            if (continueBtn) {
                continueBtn.disabled = true;
                continueBtn.textContent = 'Select a Plan to Continue';
            }
            
            // Uncheck any previously selected plans in other tabs
            const allPlanRadios = document.querySelectorAll('input[name="account_plan_selection"]');
            allPlanRadios.forEach(planRadio => {
                planRadio.checked = false;
            });
            
            // Instantly hide ALL checkmarks when switching tabs
            const allCheckmarks = document.querySelectorAll('.plan-checkmark-badge');
            allCheckmarks.forEach(badge => {
                badge.style.display = 'none';
                badge.style.opacity = '0';
                badge.style.transform = 'scale(0)';
            });
            
            // Remove selected class from all cards
            const allPlanCards = document.querySelectorAll('.plan-card');
            allPlanCards.forEach(card => {
                card.classList.remove('selected');
            });
            
            // Update context info
            const contextText = document.getElementById('contextText');
            if (contextText) {
                fetch('<?php echo $_SERVER['PHP_SELF']; ?>?ajax=1&action=get_account_info&account_type=' + accountType)
                    .then(response => response.json())
                    .then(data => {
                        if (data.context_text) {
                            contextText.textContent = data.context_text;
                        }
                    })
                    .catch(error => console.error('Error fetching account info:', error));
            }
        });
    });
    
    // Handle plan selection to enable/disable continue button using event delegation
    const continueBtn = document.getElementById('continueBtn');
    
    // Use event delegation to catch dynamically loaded radio buttons
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'account_plan_selection') {
            if (e.target.checked && continueBtn) {
                continueBtn.disabled = false;
                continueBtn.textContent = 'Continue';
            }
            
            // Force update all checkmarks visibility
            const allCheckmarks = document.querySelectorAll('.plan-checkmark-badge');
            const allPlanCards = document.querySelectorAll('.plan-card');
            
            // Remove all active states first (instantly)
            allCheckmarks.forEach(badge => {
                badge.style.display = 'none';
                badge.style.opacity = '0';
                badge.style.transform = 'scale(0)';
            });
            
            // Remove green styling from all cards
            allPlanCards.forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add active state to the selected plan's checkmark (with animation)
            const selectedRadio = e.target;
            const selectedWrapper = selectedRadio.closest('.plan-card-wrapper');
            if (selectedWrapper) {
                const checkmark = selectedWrapper.querySelector('.plan-checkmark-badge');
                if (checkmark) {
                    // Show checkmark with animation
                    checkmark.style.display = 'flex';
                    setTimeout(() => {
                        checkmark.style.opacity = '1';
                        checkmark.style.transform = 'scale(1)';
                    }, 10);
                }
                const card = selectedWrapper.querySelector('.plan-card');
                if (card) {
                    card.classList.add('selected');
                }
            }
        }
    });
    
    // Check if a plan is already selected on page load (including URL pre-selection)
    setTimeout(function() {
        const selectedPlan = document.querySelector('input[name="account_plan_selection"]:checked');
        if (selectedPlan && continueBtn) {
            continueBtn.disabled = false;
            continueBtn.textContent = 'Continue';
            
            // Ensure visual state is correct for pre-selected plan
            const selectedWrapper = selectedPlan.closest('.plan-card-wrapper');
            if (selectedWrapper) {
                const checkmark = selectedWrapper.querySelector('.plan-checkmark-badge');
                if (checkmark) {
                    checkmark.style.display = 'flex';
                    checkmark.style.opacity = '1';
                    checkmark.style.transform = 'scale(1)';
                }
                const card = selectedWrapper.querySelector('.plan-card');
                if (card) {
                    card.classList.add('selected');
                }
            }
        }
    }, 100);
    
    // Handle form submission to parse the combined value
    const form = document.getElementById('signupForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Get the selected plan radio button
            const selectedPlan = document.querySelector('input[name="account_plan_selection"]:checked');
            
            if (!selectedPlan) {
                e.preventDefault();
                alert('Please select a plan to continue.');
                return false;
            }
            
            // Parse the combined value (account_type:plan_id)
            const [accountType, planId] = selectedPlan.value.split(':');
            
            // Create hidden inputs for the parsed values
            let accountTypeInput = document.getElementById('hiddenAccountType');
            if (!accountTypeInput) {
                accountTypeInput = document.createElement('input');
                accountTypeInput.type = 'hidden';
                accountTypeInput.id = 'hiddenAccountType';
                accountTypeInput.name = 'account_type';
                form.appendChild(accountTypeInput);
            }
            accountTypeInput.value = accountType;
            
            let planInput = document.getElementById('hiddenPlan');
            if (!planInput) {
                planInput = document.createElement('input');
                planInput.type = 'hidden';
                planInput.id = 'hiddenPlan';
                planInput.name = 'account_plan';
                form.appendChild(planInput);
            }
            planInput.value = planId;
        });
    }
});

// Clean up any old references to SignupFlow since we're not using AJAX anymore
// All plans are loaded upfront in tabs
if (window.SignupFlow) {
    delete window.SignupFlow;
}

// Include the enhanced JavaScript
</script>
<!-- Commenting out signup_flow_dynamic.js as it conflicts with our new radio button approach -->
<!-- <script src="/public/js/signup_flow_dynamic.js"></script> -->
<script>
    // Add this AFTER loading signup_flow_dynamic.js
// This will override any checkmark creation from that file

(function() {
    // Store original functions that might create checkmarks
    const originalFunctions = {};
    
    // Common function names that might create checkmarks
    const checkmarkFunctions = [
        'addCheckmark', 
        'showCheckmark', 
        'createCheckmark', 
        'selectPlan',
        'updatePlanSelection',
        'markAsSelected'
    ];
    
    // Override any global functions that might create checkmarks
    checkmarkFunctions.forEach(funcName => {
        if (window[funcName]) {
            originalFunctions[funcName] = window[funcName];
            window[funcName] = function(...args) {
                // Call original function
                const result = originalFunctions[funcName].apply(this, args);
                
                // Then remove any non-badge checkmarks
                setTimeout(() => {
                    document.querySelectorAll('.plan-card .checkmark, .plan-card-wrapper .checkmark').forEach(el => {
                        if (!el.classList.contains('plan-checkmark-badge')) {
                            el.remove();
                        }
                    });
                }, 0);
                
                return result;
            };
        }
    });
    
    // Also override any jQuery event handlers if jQuery is present
    if (typeof $ !== 'undefined') {
        $(document).on('click', '.plan-card', function(e) {
            // After any click, clean up residual checkmarks
            setTimeout(() => {
                $('.plan-card .checkmark, .plan-card-wrapper .checkmark').not('.plan-checkmark-badge').remove();
            }, 50);
        });
    }
})();
</script>
<?php
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>