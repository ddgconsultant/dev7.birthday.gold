<?php 
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');

// Initialize ProductManager
$productManager = new ProductManager($database, $qik);

#-------------------------------------------------------------------------------
# HANDLE FOREIGN COUNTRIES
#-------------------------------------------------------------------------------
$approvedCountries = ['US'];
$countryCode = $session->get('countrynotsupported', '');
$getcountryviaip_data = $session->get('client_locationdata', '');
if ($countryCode == '') {
    if ($getcountryviaip_data == '' || $getcountryviaip_data == 'notset') {
        $client_locationdata = $system->getcountryviaip($client_ip, 'reset');
        if (!empty($client_locationdata['countryCode']))
            $countryCode = $client_locationdata['countryCode'];
    } else {
        if (!empty($getcountryviaip_data['countryCode']))
            $countryCode = $getcountryviaip_data['countryCode'];
    }

    $override = $session->get('country_not_supported_override', false);
    if (!in_array($countryCode, $approvedCountries) && $countryCode != '' && !$override) {
        $session->set('countrynotsupported', $countryCode);
        $session->set('countrynotsupportedtag', '[' . $countryCode . ']');
        header('Location: /country-not-supported');
        exit();
    }
}

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
global $website;
$selectedVersion = $_REQUEST['version'] ?? $website['plan_version']; // Uses site's plan version, allow override for testing

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
            $plans = $productManager->getProductsWithFeatures($accountType, $selectedVersion);
            
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
                $validation = $productManager->validatePromoCode($promoCode, $productId);
                echo json_encode($validation);
            } else {
                echo json_encode(['valid' => false, 'message' => 'Invalid request']);
            }
            exit;
            
        case 'calculate_price':
            $productId = $qik->decodeId($_REQUEST['product_id'] ?? '');
            $promoCode = $_REQUEST['promo_code'] ?? null;
            
            if ($productId) {
                $pricing = $productManager->calculatePrice($productId, $promoCode);
                echo json_encode($pricing);
            } else {
                echo json_encode(['error' => 'Invalid product']);
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
    $planbynamedata = $productManager->getProduct($_REQUEST['plan'], 'plan_name');
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
        $plandata = $productManager->getProduct($planid, 'id');
        
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
$accountTypes = $productManager->getAvailableAccountTypes($selectedVersion);
$availablePlans = $productManager->getProductsWithFeatures($selectedAccountType, $selectedVersion);
$accountTypeConfig = $productManager->getAccountTypeConfig($selectedAccountType);

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
$page_description = "Sign up for Birthday Gold and start receiving birthday rewards from hundreds of brands";

#-------------------------------------------------------------------------------
# ADDITIONAL STYLES
#-------------------------------------------------------------------------------
$additionalstyles = '
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
/* Include all the CSS from the original file */
' . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/public/css/signup_styles.css') . '

/* Page-specific compact styles */
.main-content {
    max-width: 1000px !important;
    margin: 1rem auto !important;
}
.header {
    text-align: center;
    margin-bottom: 1.5rem !important;
}
.header h1 {
    font-size: 1.75rem !important;
    margin-bottom: 0.25rem !important;
}
.header p {
    font-size: 1rem !important;
    margin: 0 !important;
}
.content h3 {
    font-size: 1.25rem !important;
    margin-bottom: 1rem !important;
}
.section-label {
    font-size: 0.95rem !important;
    margin-bottom: 0.75rem !important;
}
.plan-section-title {
    margin-top: 1.5rem !important;
    margin-bottom: 0.75rem !important;
}
.plan-grid {
    margin-bottom: 1.5rem !important;
}
footer.border-top {
    margin-top: 2rem !important;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
?>
<div class="container main-content">
    <!-- Header -->
    <div class="header">
        <h1>Create Your Account</h1>
        <p>Start celebrating with birthday freebies and VIP experiences</p>
    </div>

    <!-- Content -->
    <div class="content">
        <?php
        // Error Message Display
        if (!empty($transferpage['message'])) {
            echo '<div class="alert alert-danger mb-3" role="alert">';
            echo $display->formaterrormessage($transferpage['message']);
            echo '</div>';
        }
        ?>

        <!-- Account Type & Plan Selection -->
        <div>
            <h3 class="mb-4">Choose Your Account Type & Plan</h3>
            
            <!-- Who is this for? Section -->
            <div class="section-label">Who is this for?</div>
            
            <!-- Dynamic Account Type Selector -->
            <div class="account-type-selector" id="accountTypeSelector">
                <?php
                $displayedTypes = 0;
                foreach ($accountTypes as $accountType) {
                    $config = $productManager->getAccountTypeConfig($accountType['account_type']);
                    $isActive = ($accountType['account_type'] == $selectedAccountType) ? 'active' : '';
                    
                    // Only show first 3 types directly, rest go in "Other" modal
                    if ($displayedTypes < 3) {
                        echo '<button class="account-type-btn ' . $isActive . '" data-account-type="' . $accountType['account_type'] . '">
                                <i class="bi ' . $config['icon'] . ' me-1"></i>' . $config['short_label'] . '
                              </button>';
                        $displayedTypes++;
                    }
                }
                
                // Add "Other" button if there are more account types
                if (count($accountTypes) > 3) {
                    echo '<button class="account-type-btn" data-modal-trigger="otherAccountsModal">
                            <i class="bi bi-plus-circle me-1"></i>Other
                          </button>';
                }
                ?>
            </div>

            <!-- Context Info with Learn More button -->
            <div class="context-info" id="contextInfo">
                <div class="info-text">
                    <i class="bi bi-info-circle info-icon"></i>
                    <span id="contextText"><?php echo $accountTypeConfig['context_text']; ?></span>
                </div>
                <button type="button" class="learn-more-btn" data-bs-toggle="modal" data-bs-target="#accountTypeInfoModal" title="Learn more">
                    <i class="bi bi-info-circle learn-more-icon d-inline d-md-none"></i>
                    <span class="learn-more-text d-none d-md-inline">More info</span>
                </button>
            </div>

            <!-- Choose your plan Section -->
            <div class="plan-section-title">Choose your plan</div>

            <!-- Dynamic Plan Grid -->
            <div class="plan-grid" id="planGrid">
                <?php
                foreach ($availablePlans as $plan) {
                    $isRecommended = (strpos(strtolower($plan['account_plan']), 'gold') !== false);
                    
                    echo '<div class="plan-card' . ($isRecommended ? ' recommended' : '') . '" 
                              data-plan="' . $plan['account_plan'] . '" 
                              data-plan-id="' . $plan['encoded_id'] . '"
                              data-price="' . $plan['price'] . '">';
                    
                    if ($isRecommended) {
                        echo '<div class="recommended-badge">POPULAR</div>';
                    }
                    
                    echo '<div class="plan-header">
                            <div class="plan-icon">';
                    
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
                    
                    echo '<i class="bi ' . $planIcon . '"></i>
                          </div>
                          <h3 class="plan-title">' . htmlspecialchars($plan['account_name']) . '</h3>
                        </div>
                        <div class="plan-price">' . $qik->convertamount($plan['price']) . '</div>
                        <div class="plan-price-note">';
                    
                    // Dynamic price note
                    if ($plan['price'] == 0) {
                        echo 'Forever free';
                    } elseif (strpos(strtolower($plan['account_plan']), 'life') !== false) {
                        echo 'Lifetime access';
                    } else {
                        echo 'One-time payment';
                    }
                    
                    echo '</div>';
                    
                    // Display features from database
                    if (!empty($plan['features'])) {
                        echo '<ul class="plan-features">';
                        foreach ($plan['features'] as $feature) {
                            echo '<li>' . htmlspecialchars($feature['value']) . '</li>';
                        }
                        echo '</ul>';
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>


            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="signupForm">
                <?php echo $display->inputcsrf_token(); ?>
                <input type="hidden" name="account_plan" id="hiddenPlan" value="">
                <input type="hidden" name="account_type" id="hiddenAccountType" value="">
                <input type="hidden" name="selector" value="mobile">
                
                <?php 
                // Include URL parameters as hidden fields
                foreach ($urlParams as $key => $value) {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }
                ?>
                
                <div class="row justify-content-center mt-4">
                    <div class="col-12 col-md-6 col-lg-5">
                        <button type="submit" class="btn-primary-custom w-100" id="continueBtn" disabled>
                            Select a Plan to Continue
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-top mt-4">
        <div class="container py-4">
            <div class="row text-center">
                <div class="col-12 mb-2">
                    <small class="text-muted">
                        Already have an account? 
                        <a href="/login" class="text-decoration-none text-success fw-medium">Sign in</a>
                    </small>
                </div>
                <div class="col-12">
                    <small class="text-muted">
                        Have a gift certificate? 
                        <a href="/redeem" class="text-decoration-none text-success fw-medium">Redeem here</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>
</div>

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
                            $config = $productManager->getAccountTypeConfig($accountType['account_type']);
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
                        $config = $productManager->getAccountTypeConfig($accountType['account_type']);
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
    selectedVersion: '<?php echo $selectedVersion; ?>'
};

// Include the enhanced JavaScript
</script>
<script src="/public/js/signup_flow_dynamic.js"></script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>