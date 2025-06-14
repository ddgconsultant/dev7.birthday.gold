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
$selectedVersion = $_REQUEST['version'] ?? 'v7'; // Allow version switching for testing

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
    $session->unset('signup_process_data');
    
    $planid = ($qik->decodeId($signup_process['account_plan'] ?? false));
    
    if ($planid) {
        $signup_process['account_plan_id'] = $planid;
        $plandata = $productManager->getProduct($planid, 'id');
        
        if ($plandata) {
            $signup_process['plandata'] = $plandata;
            $signup_process['account_type'] = $signup_process['account_type'] ?? 'user';
            $signup_process['account_plan'] = $plandata['account_plan'];
            $signup_process['account_cost'] = $plandata['price'];
            $signup_process['account_verification'] = $plandata['account_verification'];
            $gotourl = $plandata['redirect_url'] ?? '/register';
            
            // Apply promo code if provided
            if (!empty($signup_process['promo_code'])) {
                $pricing = $productManager->calculatePrice($planid, $signup_process['promo_code']);
                $signup_process['account_cost'] = $pricing['final_price'];
                $signup_process['original_cost'] = $pricing['original_price'];
                $signup_process['discount_applied'] = $pricing['discount'];
            }
        } else {
            $transferpage['url'] = $_SERVER['PHP_SELF'];
            $transferpage['message'] = 'Plan not found';
            $system->endpostpage($transferpage);
            exit;
        }
    } else {
        $transferpage['url'] = $_SERVER['PHP_SELF'];
        $transferpage['message'] = 'Invalid plan selected';
        $system->endpostpage($transferpage);
        exit;
    }
    
    $session->set('signup_process_data', $signup_process);
    header('Location: ' . $gotourl);
    exit;
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
' . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/claudecode/signup_styles.css') . '
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

        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="progress-step active" id="progress1">1</div>
            <div class="progress-step" id="progress2">2</div>
        </div>

        <!-- Step 1: Account Type & Plan Selection -->
        <div class="step active" id="step1">
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

            <!-- Optional Promo Section -->
            <div class="promo-section">
                <div class="text-center mb-3">
                    <a href="#promoFields" class="promo-toggle" data-bs-toggle="collapse" aria-expanded="false" aria-controls="promoFields">
                        <i class="bi bi-plus-circle me-2"></i>
                        <span>Have a promo code or referral?</span>
                    </a>
                </div>

                <div class="collapse" id="promoFields">
                    <div class="promo-card">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="promoCode" class="form-label">Promo Code</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="promo_code" id="promoCode" placeholder="Enter promo code">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="validatePromoBtn">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </div>
                                <div class="form-text" id="promoCodeFeedback"></div>
                            </div>
                            <div class="col-12">
                                <label for="referralCode" class="form-label">Referral Code</label>
                                <input type="text" class="form-control" name="referral_code" id="referralCode" placeholder="Enter referral code">
                                <div class="form-text" id="referralCodeFeedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-md-6 col-lg-5">
                    <button type="button" class="btn-primary-custom w-100" id="continueToReview" disabled>
                        Continue to Review
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Review & Submit -->
        <div class="step" id="step2">
            <h2 class="h4 mb-3">Review & Submit</h2>
            <p class="text-muted text-center mb-4">Confirm your selections and start registration</p>
            
            <div class="summary-card">
                <div class="summary-row">
                    <div>
                        <div class="summary-title" id="finalPlanName">Select a plan</div>
                        <p class="summary-subtitle" id="finalAccountType">Select who this is for</p>
                    </div>
                    <div class="text-end">
                        <div class="summary-price" id="finalPrice">$0</div>
                        <div class="summary-note" id="finalPriceNote">one-time</div>
                        <div class="discount-info d-none" id="discountInfo">
                            <small class="text-success">Promo applied!</small>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="signupForm">
                <?php echo $display->inputcsrf_token(); ?>
                <input type="hidden" name="account_plan" id="hiddenPlan" value="">
                <input type="hidden" name="account_type" id="hiddenAccountType" value="">
                <input type="hidden" name="promo_code" id="hiddenPromoCode" value="">
                <input type="hidden" name="referral_code" id="hiddenReferralCode" value="">
                <input type="hidden" name="selector" value="mobile">
                
                <div class="step-nav">
                    <button type="button" class="btn-secondary-custom" id="backToStep1">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="bi bi-check-lg me-2"></i>Create Account
                    </button>
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
<script src="/claudecode/signup_flow_dynamic.js"></script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>