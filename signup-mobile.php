<?php 
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

#-------------------------------------------------------------------------------
# MOBILE-FIRST SIGNUP PAGE
# Location: /signup-mobile.php
# Integrates seamlessly with existing Birthday.Gold infrastructure
#-------------------------------------------------------------------------------

# Initialize step variable to avoid undefined variable error
$step = 1; // Default to step 1

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
# HANDLE INITIALIZATION
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

#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSION
#-------------------------------------------------------------------------------
if ($app->formposted() && empty($signup_process['account_plan'])) {
    $pagemessage = '<div class="alert alert-danger alert-dismissible show" role="alert">Please select a plan.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    $session->set('force_error_message', $pagemessage);
    header('Location: /signup-mobile');
    exit;
}

#-------------------------------------------------------------------------------
# HANDLE PLAN LINKS (for direct linking to plans)
#-------------------------------------------------------------------------------
if (isset($_REQUEST['plan'])) {
    $planbynamedata = $app->getProduct($_REQUEST['plan'], 'user');
    if ($planbynamedata) {
        $signup_process['account_plan'] = $qik->encodeId($planbynamedata['id']);
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
        $plandata = $app->getProduct($planid, 'PRODUCT_ID');
        
        if ($plandata) {
            $signup_process['plandata'] = $plandata;
            $signup_process['account_type'] = $signup_process['account_type'] ?? 'user';
            $signup_process['account_plan'] = $plandata['account_plan'];
            $signup_process['account_cost'] = $plandata['price'];
            $signup_process['account_verification'] = $plandata['account_verification'];
            
            // Store promo/referral codes if provided
            if (!empty($signup_process['promo_code'])) {
                $signup_process['promo_code'] = trim($signup_process['promo_code']);
            }
            if (!empty($signup_process['referral_code'])) {
                $signup_process['referral_code'] = trim($signup_process['referral_code']);
            }
            
            $gotourl = $plandata['redirect_url'] ?? '/register';
        } else {
            $transferpage['url'] = '/signup-mobile';
            $transferpage['message'] = 'Plan not found';
            $system->endpostpage($transferpage);
            exit;
        }
    } else {
        $transferpage['url'] = '/signup-mobile';
        $transferpage['message'] = 'Invalid plan selected';
        $system->endpostpage($transferpage);
        exit;
    }
    
    $session->set('signup_process_data', $signup_process);
    header('Location: ' . $gotourl);
    exit;
}

#-------------------------------------------------------------------------------
# GET AVAILABLE PLANS
#-------------------------------------------------------------------------------
$freePlan = $app->getProduct('free', 'user', '*', 1);
$goldPlan = $app->getProduct('gold', 'user', '*', 1);
$lifePlan = $app->getProduct('life', 'user', '*', 1);

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
    $signupexit = '<a href="/logout" class="signup-exit-btn"><i class="bi bi-x-square text-info m-1"></i></a>';
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
# ADD STYLES & SETUP PAGE
#-------------------------------------------------------------------------------
$additionalstyles = '
<style>
:root {
    --primary: #198754;
    --primary-light: #20c997;
    --primary-dark: #157347;
    --gray-50: #f8f9fa;
    --gray-100: #e9ecef;
    --gray-200: #dee2e6;
    --gray-600: #6c757d;
    --gray-700: #495057;
    --gray-900: #212529;
    --success: #198754;
    --warning: #ffc107;
}

body {
    background-color: var(--gray-50);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.6;
}

.container-main {
    max-width: 480px;
    margin: 0 auto;
    min-height: 100vh;
    background: white;
    box-shadow: 0 0 0 1px rgba(0,0,0,0.05);
}

.header {
    background: white;
    padding: 2rem 1.5rem 1rem;
    text-align: center;
    border-bottom: 1px solid var(--gray-100);
}

.header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.header p {
    color: var(--gray-600);
    margin-bottom: 0;
    font-size: 0.95rem;
}

.content {
    padding: 1.5rem;
}

/* Progress Indicator */
.progress-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
}

.progress-step {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--gray-200);
    color: var(--gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    margin: 0 8px;
    position: relative;
}

.progress-step.active {
    background: var(--primary);
    color: white;
}

.progress-step.completed {
    background: var(--success);
    color: white;
}

.progress-step::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 100%;
    width: 16px;
    height: 2px;
    background: var(--gray-200);
    transform: translateY(-50%);
}

.progress-step:last-child::after {
    display: none;
}

.progress-step.completed::after {
    background: var(--success);
}

/* Account Type Toggle Buttons */
.account-type-selector {
    background: var(--gray-100);
    border-radius: 12px;
    padding: 4px;
    margin-bottom: 1rem;
    display: flex;
}

.account-type-btn {
    flex: 1;
    padding: 12px 6px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--gray-600);
    transition: all 0.2s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.account-type-btn.active {
    background: white;
    color: var(--primary);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.account-type-btn:hover:not(.active) {
    color: var(--gray-700);
}

/* Other Account Types Link */
.btn-link-custom {
    color: var(--gray-600);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s ease;
}

.btn-link-custom:hover {
    color: var(--primary);
    text-decoration: none;
}

/* Context Info Box */
.context-info {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f9ff 100%);
    border: 1px solid var(--primary-light);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    color: var(--gray-700);
    display: flex;
    align-items: flex-start;
}

/* Plan Grid */
.plan-grid {
    display: grid;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.plan-card {
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
    position: relative;
}

.plan-card:hover {
    border-color: var(--primary-light);
    background-color: #f8fffe;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.plan-card.selected {
    border-color: var(--primary);
    background-color: #f0fdf4;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.1);
}

.plan-card.recommended::before {
    content: "Most Popular";
    position: absolute;
    top: -1px;
    right: 12px;
    background: var(--primary);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 0 0 8px 8px;
}

.plan-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.plan-icon {
    width: 44px;
    height: 44px;
    background: var(--gray-100);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1.2rem;
    color: var(--gray-600);
    transition: all 0.2s ease;
}

.plan-card.selected .plan-icon {
    background: var(--primary);
    color: white;
}

.plan-title {
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--gray-900);
    margin: 0;
}

.plan-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.25rem;
}

.plan-price-note {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 1rem;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.plan-features li {
    font-size: 0.9rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
    position: relative;
    padding-left: 1.25rem;
}

.plan-features li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--primary);
    font-weight: 600;
}

/* Other Account Cards */
.account-type-card {
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    padding: 1.25rem;
    cursor: pointer;
    transition: all 0.15s ease;
    background: white;
    margin-bottom: 1rem;
}

.account-type-card:hover {
    border-color: var(--primary-light);
    background-color: #f8fffe;
}

.account-type-card.selected {
    border-color: var(--primary);
    background-color: #f0fdf4;
}

.account-type-header {
    display: flex;
    align-items: center;
}

.account-type-icon {
    width: 44px;
    height: 44px;
    background: var(--gray-100);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.2rem;
    color: var(--gray-600);
    transition: all 0.2s ease;
}

.account-type-card.selected .account-type-icon,
.account-type-card:hover .account-type-icon {
    background: var(--primary);
    color: white;
}

.account-type-title {
    font-weight: 600;
    color: var(--gray-900);
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
}

.account-type-desc {
    font-size: 0.9rem;
    color: var(--gray-600);
    margin: 0;
    line-height: 1.4;
}

/* Promo Section */
.promo-section {
    margin: 1.5rem 0;
}

.promo-toggle {
    color: var(--gray-600);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.15s ease;
}

.promo-toggle:hover {
    color: var(--primary);
}

.promo-toggle i {
    transition: transform 0.2s ease;
}

.promo-toggle[aria-expanded="true"] i {
    transform: rotate(45deg);
}

.promo-card {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 0.75rem;
}

.promo-card .form-control {
    border-radius: 6px;
    font-size: 0.875rem;
    border: 1px solid var(--gray-200);
}

.promo-card .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.promo-card .form-text {
    margin-top: 0.25rem;
    font-size: 0.8rem;
}

.promo-card .form-text.text-success {
    color: var(--success) !important;
}

.promo-card .form-label {
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

/* Buttons */
.btn-primary-custom {
    background: var(--primary);
    border: 1px solid var(--primary);
    color: white;
    border-radius: 8px;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
    font-size: 0.95rem;
    width: 100%;
    transition: all 0.2s ease;
}

.btn-primary-custom:hover:not(:disabled) {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
    color: white;
    transform: translateY(-1px);
}

.btn-primary-custom:disabled {
    background: var(--gray-200);
    border-color: var(--gray-200);
    color: var(--gray-600);
    cursor: not-allowed;
}

.btn-secondary-custom {
    background: white;
    border: 2px solid var(--gray-200);
    color: var(--gray-600);
    border-radius: 8px;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
    font-size: 0.95rem;
    width: 100%;
    transition: all 0.2s ease;
}

.btn-secondary-custom:hover {
    border-color: var(--gray-600);
    color: var(--gray-900);
}

/* Summary */
.summary-card {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-title {
    font-weight: 600;
    color: var(--gray-900);
}

.summary-subtitle {
    font-size: 0.9rem;
    color: var(--gray-600);
    margin: 0;
}

.summary-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
}

.summary-note {
    font-size: 0.85rem;
    color: var(--gray-600);
}

/* Step Navigation */
.step-nav {
    display: flex;
    gap: 0.75rem;
    margin-top: 2rem;
}

.step {
    display: none;
}

.step.active {
    display: block;
}

/* Footer */
.footer-links {
    text-align: center;
    padding: 1.5rem;
    border-top: 1px solid var(--gray-100);
    color: var(--gray-600);
    font-size: 0.9rem;
}

.footer-links a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}

.footer-links a:hover {
    text-decoration: underline;
}

/* Exit Button */
.signup-exit-btn {
    position: fixed;
    top: 15px;
    right: 15px;
    font-size: 1.5rem;
    z-index: 1050;
    text-decoration: none;
}

/* Responsive Design */
@media (min-width: 768px) {
    .container-main {
        max-width: 520px;
        margin-top: 2rem;
        margin-bottom: 2rem;
        border-radius: 16px;
        box-shadow: 0 4px 25px rgba(0,0,0,0.1);
    }
    
    .header {
        border-radius: 16px 16px 0 0;
    }

    .account-type-btn {
        font-size: 0.9rem;
        padding: 12px 16px;
    }
}

@media (min-width: 992px) {
    .plan-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.step.active {
    animation: slideIn 0.3s ease-out;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 1rem;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
echo '
<div class="container-main">
    <!-- Header -->
    <div class="header">
        <h1>Create Your Account</h1>
        <p>Start celebrating with birthday freebies and VIP experiences</p>
    </div>

    <!-- Content -->
    <div class="content">';

// Display error message if present
if (!empty($transferpage['message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo $display->formaterrormessage($transferpage['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

echo '
        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="progress-step active" id="progress1">1</div>
            <div class="progress-step" id="progress2">2</div>
        </div>

        <!-- Step 1: Account Type & Plan Selection -->
        <div class="step active" id="step1">
            <!-- Account Type Selector -->
            <div class="account-type-selector">
                <button class="account-type-btn active" data-account-type="individual">
                    <i class="bi bi-person me-1"></i>Myself
                </button>
                <button class="account-type-btn" data-account-type="family">
                    <i class="bi bi-people me-1"></i>Family
                </button>
                <button class="account-type-btn" data-account-type="giftcertificate">
                    <i class="bi bi-gift me-1"></i>Gift
                </button>
            </div>

            <!-- Other Account Types Link -->
            <div class="text-center mb-3">
                <a href="#" class="btn-link-custom" data-bs-toggle="modal" data-bs-target="#otherAccountsModal">
                    <i class="bi bi-plus-circle me-1"></i>
                    <span>Other Account Types</span>
                </a>
            </div>

            <!-- Context Info -->
            <div class="context-info" id="contextInfo">
                <i class="bi bi-info-circle me-2"></i>
                <span id="contextText">Choose the plan that works best for your personal birthday rewards</span>
            </div>

            <!-- Plan Grid -->
            <div class="plan-grid" id="planGrid">';

// Display Free Plan
if (!empty($freePlan) && $freePlan['display_grouping_status'] == 'active') {
    echo '
                <div class="plan-card" data-plan="free" data-plan-id="' . $qik->encodeId($freePlan['id']) . '" data-account-type="individual">
                    <div class="plan-header">
                        <div class="plan-icon">
                            <i class="bi bi-person"></i>
                        </div>
                        <h3 class="plan-title">' . htmlspecialchars($freePlan['account_name']) . '</h3>
                    </div>
                    <div class="plan-price">' . $qik->convertamount($freePlan['price']) . '</div>
                    <div class="plan-price-note">Forever</div>
                    <ul class="plan-features">
                        <li>Basic birthday tracking</li>
                        <li>Limited offers</li>
                        <li>Email support</li>
                    </ul>
                </div>';
}

// Display Gold Plan
if (!empty($goldPlan) && $goldPlan['display_grouping_status'] == 'active') {
    echo '
                <div class="plan-card recommended" data-plan="gold" data-plan-id="' . $qik->encodeId($goldPlan['id']) . '" data-account-type="individual">
                    <div class="plan-header">
                        <div class="plan-icon">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <h3 class="plan-title">' . htmlspecialchars($goldPlan['account_name']) . '</h3>
                    </div>
                    <div class="plan-price">' . $qik->convertamount($goldPlan['price']) . '</div>
                    <div class="plan-price-note">One-time payment</div>
                    <ul class="plan-features">
                        <li>All birthday freebies</li>
                        <li>VIP experiences</li>
                        <li>Priority support</li>
                        <li>Year-round deals</li>
                    </ul>
                </div>';
}

echo '
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
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="validatePromoCode()">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </div>
                                <div class="form-text" id="promoCodeFeedback"></div>
                            </div>
                            <div class="col-12">
                                <label for="referralCode" class="form-label">Referral Code</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="referral_code" id="referralCode" placeholder="Enter referral code">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="validateReferralCode()">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </div>
                                <div class="form-text" id="referralCodeFeedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-primary-custom" id="continueToReview" disabled>
                <i class="bi bi-arrow-right me-2"></i>
                Continue to Review
            </button>
        </div>

        <!-- Step 2: Review & Submit -->
        <div class="step" id="step2">
            <h2 class="section-title">Review & Submit</h2>
            <p class="text-muted text-center mb-4">Confirm your selections and start registration</p>
            
            <div class="summary-card">
                <div class="summary-row">
                    <div>
                        <div class="summary-title" id="finalPlanName">Select a plan</div>
                        <p class="summary-subtitle" id="finalAccountType">Select account type</p>
                    </div>
                    <div class="text-end">
                        <div class="summary-price" id="finalPrice">$0</div>
                        <div class="summary-note" id="finalPriceNote">one-time</div>
                    </div>
                </div>
            </div>

            <form method="POST" action="/signup-mobile" id="signupForm">
                ' . $display->inputcsrf_token() . '
                <input type="hidden" name="account_plan" id="hiddenPlan" value="">
                <input type="hidden" name="account_type" id="hiddenAccountType" value="individual">
                <input type="hidden" name="promo_code" id="hiddenPromoCode" value="">
                <input type="hidden" name="referral_code" id="hiddenReferralCode" value="">
                <input type="hidden" name="selector" value="mobile">
                
                <div class="step-nav">
                    <button type="button" class="btn-secondary-custom" id="backToStep1">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="bi bi-check-lg me-2"></i>Start Registration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer Links -->
    <div class="footer-links">
        <p>Already have an account? <a href="/login">Sign in</a></p>
        <p>Have a gift certificate? <a href="/redeem">Redeem here</a></p>
    </div>
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
                <div class="other-account-options">
                    <div class="account-type-card" data-account-type="business">
                        <div class="account-type-header">
                            <div class="account-type-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <h5 class="account-type-title">Business Account</h5>
                                <p class="account-type-desc">Employee birthday management and bulk enrollment</p>
                            </div>
                        </div>
                    </div>
                    <div class="account-type-card" data-account-type="nonprofit">
                        <div class="account-type-header">
                            <div class="account-type-icon">
                                <i class="bi bi-heart"></i>
                            </div>
                            <div>
                                <h5 class="account-type-title">Non-Profit Account</h5>
                                <p class="account-type-desc">Special pricing for registered non-profit organizations</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

echo $signupexit;

$footerattribute['postfooter'] = '
<script>
// Initialize global variables for validation functions
let validPromoCode = null;
let validReferralCode = null;

class MobileSignupFlow {
    constructor() {
        this.currentStep = 1;
        this.selectedAccountType = "individual";
        this.selectedPlan = null;
        this.selectedPlanData = null;
        this.validPromoCode = null;
        this.validReferralCode = null;
        
        this.planData = {
            individual: {
                free: {
                    name: "' . (isset($freePlan['account_name']) ? addslashes($freePlan['account_name']) : 'Free Plan') . '",
                    price: "' . (isset($freePlan) ? $qik->convertamount($freePlan['price']) : '$0') . '",
                    priceNote: "Forever"
                },
                gold: {
                    name: "' . (isset($goldPlan['account_name']) ? addslashes($goldPlan['account_name']) : 'Gold Plan') . '",
                    price: "' . (isset($goldPlan) ? $qik->convertamount($goldPlan['price']) : '$40') . '",
                    priceNote: "One-time payment"
                }
            },
            family: {
                gold: {
                    name: "Family Plan",
                    price: "' . (isset($goldPlan) ? $qik->convertamount($goldPlan['price']) : '$40') . '",
                    priceNote: "Parent + discounted children"
                }
            },
            giftcertificate: {
                gold: {
                    name: "Gold Gift Certificate",
                    price: "' . (isset($goldPlan) ? $qik->convertamount($goldPlan['price']) : '$40') . '",
                    priceNote: "One-time payment"
                }
            },
            business: {
                gold: {
                    name: "Business Plan",
                    price: "Custom",
                    priceNote: "Contact for pricing"
                }
            },
            nonprofit: {
                gold: {
                    name: "Non-Profit Plan",
                    price: "' . (isset($goldPlan) ? number_format($goldPlan['price'] * 0.5, 2) : '$20') . '",
                    priceNote: "Special non-profit pricing"
                }
            }
        };
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateContextInfo();
    }

    bindEvents() {
        // Account type buttons
        document.querySelectorAll(".account-type-btn").forEach(btn => {
            btn.addEventListener("click", (e) => this.selectAccountType(e.currentTarget.dataset.accountType));
        });

        // Plan cards
        document.querySelectorAll(".plan-card").forEach(card => {
            card.addEventListener("click", () => this.selectPlan(card));
        });

        // Navigation buttons
        document.getElementById("continueToReview").addEventListener("click", () => this.goToStep(2));
        document.getElementById("backToStep1").addEventListener("click", () => this.goToStep(1));

        // Modal account types
        document.querySelectorAll(".other-account-options .account-type-card").forEach(card => {
            card.addEventListener("click", () => this.selectOtherAccountType(card.dataset.accountType));
        });

        // Promo toggle
        document.querySelector(".promo-toggle").addEventListener("click", (e) => {
            const icon = e.currentTarget.querySelector("i");
            const expanded = e.currentTarget.getAttribute("aria-expanded") === "true";
            
            if (expanded) {
                icon.className = "bi bi-plus-circle me-2";
            } else {
                icon.className = "bi bi-dash-circle me-2";
            }
        });
    }

    selectAccountType(accountType) {
        this.selectedAccountType = accountType;
        this.selectedPlan = null;
        this.selectedPlanData = null;
        
        // Update button states
        document.querySelectorAll(".account-type-btn").forEach(btn => {
            btn.classList.toggle("active", btn.dataset.accountType === accountType);
        });
        
        this.updateContextInfo();
        this.updatePlansDisplay();
        this.updateButtons();
    }

    selectOtherAccountType(accountType) {
        this.selectedAccountType = accountType;
        this.selectedPlan = null;
        this.selectedPlanData = null;
        
        // Remove active from main buttons
        document.querySelectorAll(".account-type-btn").forEach(btn => {
            btn.classList.remove("active");
        });
        
        this.updateContextInfo();
        this.updatePlansDisplay();
        this.updateButtons();
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById("otherAccountsModal"));
        if (modal) modal.hide();
    }

    selectPlan(card) {
        // Remove previous selections
        document.querySelectorAll(".plan-card").forEach(c => c.classList.remove("selected"));
        
        // Add selection to clicked card
        card.classList.add("selected");
        
        this.selectedPlan = card.dataset.plan;
        this.selectedPlanData = this.planData[this.selectedAccountType][this.selectedPlan];
        
        this.updateButtons();
    }

    updateContextInfo() {
        const contextText = document.getElementById("contextText");
        const messages = {
            individual: "Choose the plan that works best for your personal birthday rewards",
            family: "Family plans include Gold features plus discounted rates for children",
            giftcertificate: "Gift certificates automatically include our premium Gold Plan features",
            business: "Business plans offer employee management and bulk enrollment tools",
            nonprofit: "Special pricing and features designed for non-profit organizations"
        };
        
        contextText.textContent = messages[this.selectedAccountType] || messages.individual;
    }
';

$footerattribute['postfooter'] .= <<< EOL
    updatePlansDisplay() {
        const planGrid = document.getElementById("planGrid");
        let planHtml = "";
        
        if (this.selectedAccountType === "individual") {
            // Show both free and gold plans for individual accounts
            if (document.querySelector(".plan-card[data-plan='free']")) {
                const freeCard = document.querySelector(".plan-card[data-plan='free']");
                freeCard.style.display = "block";
            }
            if (document.querySelector(".plan-card[data-plan='gold']")) {
                const goldCard = document.querySelector(".plan-card[data-plan='gold']");
                goldCard.style.display = "block";
            }
        } else {
            // For non-individual accounts, we need to adjust the display
            // Hide free plan for non-individual accounts
            if (document.querySelector(".plan-card[data-plan='free']")) {
                const freeCard = document.querySelector(".plan-card[data-plan='free']");
                freeCard.style.display = "none";
            }
            
            // Customize the gold plan display based on account type
            if (document.querySelector(".plan-card[data-plan='gold']")) {
                const goldCard = document.querySelector(".plan-card[data-plan='gold']");
                const planData = this.planData[this.selectedAccountType].gold;
                
                goldCard.querySelector(".plan-title").textContent = planData.name;
                goldCard.querySelector(".plan-price").textContent = planData.price;
                goldCard.querySelector(".plan-price-note").textContent = planData.priceNote;
                
                // Update data attributes
                goldCard.dataset.accountType = this.selectedAccountType;
                
                goldCard.style.display = "block";
            }
        }
    }

    updateButtons() {
        const continueBtn = document.getElementById("continueToReview");
        continueBtn.disabled = !this.selectedPlan;
    }

    goToStep(step) {
        // Update progress indicator
        document.querySelectorAll(".progress-step").forEach((stepEl, index) => {
            stepEl.classList.remove("active", "completed");
            if (index + 1 === step) {
                stepEl.classList.add("active");
            } else if (index + 1 < step) {
                stepEl.classList.add("completed");
            }
        });

        // Show/hide steps
        document.querySelectorAll(".step").forEach(stepEl => {
            stepEl.classList.remove("active");
        });
        document.getElementById(`step${step}`).classList.add("active");
        
        this.currentStep = step;

        if (step === 2) {
            this.updateSummary();
        }
    }

    updateSummary() {
        if (!this.selectedPlanData) return;
        
        const accountTypeNames = {
            individual: "Individual Account",
            family: "Family Account", 
            giftcertificate: "Gift Certificate",
            business: "Business Account",
            nonprofit: "Non-Profit Account"
        };
        
        document.getElementById("finalPlanName").textContent = this.selectedPlanData.name;
        document.getElementById("finalAccountType").textContent = accountTypeNames[this.selectedAccountType];
        document.getElementById("finalPrice").textContent = this.selectedPlanData.price;
        document.getElementById("finalPriceNote").textContent = this.selectedPlanData.priceNote;

        // Update hidden form fields
        const planCard = document.querySelector(".plan-card.selected");
        if (planCard) {
            document.getElementById("hiddenPlan").value = planCard.dataset.planId;
        }
        document.getElementById("hiddenAccountType").value = this.selectedAccountType;
        document.getElementById("hiddenPromoCode").value = validPromoCode || this.validPromoCode || "";
        document.getElementById("hiddenReferralCode").value = validReferralCode || this.validReferralCode || "";
    }
}

// Validation functions
function validatePromoCode() {
    const input = document.getElementById("promoCode");
    const feedback = document.getElementById("promoCodeFeedback");
    const code = input.value.trim().toUpperCase();
    
    const validCodes = ["SAVE10", "WELCOME", "BIRTHDAY"];
    
    if (validCodes.includes(code)) {
        feedback.textContent = "✓ Valid promo code applied!";
        feedback.className = "form-text text-success";
        input.classList.remove("is-invalid");
        input.classList.add("is-valid");
        validPromoCode = code;
        window.signupFlow.validPromoCode = code;
    } else if (code) {
        feedback.textContent = "✗ Invalid promo code";
        feedback.className = "form-text text-danger";
        input.classList.remove("is-valid");
        input.classList.add("is-invalid");
        validPromoCode = null;
        window.signupFlow.validPromoCode = null;
    } else {
        feedback.textContent = "";
        input.classList.remove("is-valid", "is-invalid");
        validPromoCode = null;
        window.signupFlow.validPromoCode = null;
    }
}

function validateReferralCode() {
    const input = document.getElementById("referralCode");
    const feedback = document.getElementById("referralCodeFeedback");
    const code = input.value.trim();
    
    if (code.length >= 6 && /^[A-Za-z0-9]+$/.test(code)) {
        feedback.textContent = "✓ Valid referral code applied!";
        feedback.className = "form-text text-success";
        input.classList.remove("is-invalid");
        input.classList.add("is-valid");
        validReferralCode = code;
        window.signupFlow.validReferralCode = code;
    } else if (code) {
        feedback.textContent = "✗ Invalid referral code format";
        feedback.className = "form-text text-danger";
        input.classList.remove("is-valid");
        input.classList.add("is-invalid");
        validReferralCode = null;
        window.signupFlow.validReferralCode = null;
    } else {
        feedback.textContent = "";
        input.classList.remove("is-valid", "is-invalid");
        validReferralCode = null;
        window.signupFlow.validReferralCode = null;
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    window.signupFlow = new MobileSignupFlow();
});
</script>
EOL;

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>