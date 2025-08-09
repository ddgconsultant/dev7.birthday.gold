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
$page_description = "Sign up for Birthday Gold and start receiving birthday rewards from hundreds of brands";

#-------------------------------------------------------------------------------
# ADDITIONAL STYLES
#-------------------------------------------------------------------------------
$additionalstyles .= '
<link href="/public/css/signup_styles.css" rel="stylesheet">
<style>
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
}

.plan-card {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.plan-features {
    flex-grow: 1;
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
                                <i class="bi ' . $config['icon'] . '"></i><span>' . $config['short_label'] . '</span>
                              </button>';
                        $displayedTypes++;
                    }
                }
                
                // Add "Other" button if there are more account types
                if (count($accountTypes) > 3) {
                    echo '<button class="account-type-btn" data-modal-trigger="otherAccountsModal">
                            <i class="bi bi-plus-circle"></i><span>Other</span>
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
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#accountTypeInfoModal" title="Learn more">
                    <i class="bi bi-info-circle learn-more-icon d-inline d-md-none"></i>
                    <span class="learn-more-text d-none d-md-inline">Learn More</span>
                </button>
            </div>

<!-- Choose your plan Section -->
<h3 class="mt-5 pt-md-5 pt-sm-2">Pick the plan:</h3>

<!-- Dynamic Plan Grid Container using Bootstrap 5 -->
<div id="planGrid">
    <?php 
    // Determine container structure based on plan count
    if ($planCount == 1): 
    ?>
        <!-- 1 Card: Centered with ~70% width on desktop -->
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8">
                    <?php
                    // Single plan - render without additional column wrapper
                    $plan = $availablePlans[0]; // Get the single plan
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
                        <div class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?><?php echo $isSelected ? ' selected' : ''; ?>" 
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
                        </div>
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
                
                foreach ($availablePlans as $plan):
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
                            <div class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?><?php echo $isSelected ? ' selected' : ''; ?>" 
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
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
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
    selectedVersion: '<?php echo $selectedVersion; ?>',
    preselectedAccountType: '<?php echo $selectedAccountType; ?>',
    preselectedPlanId: '<?php echo isset($selectedPlanId) ? $selectedPlanId : ''; ?>'
};

// Handle preselection on page load
document.addEventListener('DOMContentLoaded', function() {
    // If we have a preselected plan, update the form
    if (pageData.preselectedPlanId) {
        const selectedPlanCard = document.querySelector('.plan-card.selected');
        if (selectedPlanCard) {
            const planId = selectedPlanCard.getAttribute('data-plan-id');
            const accountType = pageData.preselectedAccountType;
            
            // Update hidden form fields
            document.getElementById('hiddenPlan').value = planId;
            document.getElementById('hiddenAccountType').value = accountType;
            
            // Update continue button
            const continueBtn = document.getElementById('continueBtn');
            if (continueBtn) {
                continueBtn.disabled = false;
                continueBtn.textContent = 'Continue to Account Details';
            }
        }
    }
});


// Add this JavaScript after your existing JavaScript in the page
// It should be placed right after the pageData declaration and DOMContentLoaded handler

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

// Enhanced plan selection handler
document.addEventListener('DOMContentLoaded', function() {
    // Initialize checkmark for preselected plan
    CheckmarkManager.initializePreselected();
    
    // Handle plan card clicks - using event delegation for dynamic content
    document.getElementById('planGrid').addEventListener('click', function(e) {
        const card = e.target.closest('.plan-card');
        if (!card) return;
        
        e.preventDefault();
        
        // Get the wrapper element
        const wrapper = card.closest('.plan-card-wrapper');
        
        // Remove selected class from all elements
        document.querySelectorAll('.plan-card-wrapper').forEach(w => {
            w.classList.remove('selected');
        });
        document.querySelectorAll('.plan-card').forEach(c => {
            c.classList.remove('selected');
        });
        
        // Add selected class to clicked elements
        wrapper.classList.add('selected');
        card.classList.add('selected');
        
        // Add checkmark badge
        CheckmarkManager.addCheckmark(wrapper);
        
        // Update form values
        const planId = card.getAttribute('data-plan-id');
        const accountType = document.querySelector('.account-type-btn.active')?.getAttribute('data-account-type') || 'user';
        
        document.getElementById('hiddenPlan').value = planId;
        document.getElementById('hiddenAccountType').value = accountType;
        
        // Enable and update continue button
        const continueBtn = document.getElementById('continueBtn');
        continueBtn.disabled = false;
        continueBtn.textContent = 'Continue to Account Details';
    });
});

// Override the loadPlans function to maintain checkmarks after AJAX updates
const originalSignupFlow = window.SignupFlow || {};
if (originalSignupFlow.loadPlans) {
    const originalLoadPlans = originalSignupFlow.loadPlans;
    originalSignupFlow.loadPlans = function(accountType) {
        return originalLoadPlans.call(this, accountType).then(result => {
            // After plans are loaded, check if we need to restore checkmark
            setTimeout(() => {
                CheckmarkManager.initializePreselected();
            }, 100);
            return result;
        });
    };
}

// Include the enhanced JavaScript
</script>
<script src="/public/js/signup_flow_dynamic.js"></script>
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