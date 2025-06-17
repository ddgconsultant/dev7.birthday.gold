<?php 
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.createaccount.php');
include($_SERVER['DOCUMENT_ROOT'].'/claudecode/class.productmanager_promo.php');

// Initialize ProductManager with promo support
$productManager = new ProductManagerPromo($database, $qik);
$createaccount = new createaccount($database, $session);

#-------------------------------------------------------------------------------
# GET SIGNUP DATA FROM SESSION
#-------------------------------------------------------------------------------
$signup_process = $session->get('signup_process_data', []);
if (empty($signup_process) || empty($signup_process['account_plan_id'])) {
    header('Location: /signup.php');
    exit();
}

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$plandata = $signup_process['plandata'] ?? [];
$account_type = $signup_process['account_type'] ?? 'user';
$account_plan = $signup_process['account_plan'] ?? '';
$account_cost = $signup_process['account_cost'] ?? 0;

// Debug output for development
if ($mode === 'dev') {
    error_log('[CREATEACCOUNT] Signup process data: ' . json_encode($signup_process));
    error_log('[CREATEACCOUNT] Account plan ID: ' . ($signup_process['account_plan_id'] ?? 'NOT SET'));
}

// Get URL parameters from signup data or form submission
$promo_code = $_POST['promo_code'] ?? $signup_process['promo'] ?? $signup_process['promo_code'] ?? '';
$referral_code = $_POST['referral_code'] ?? $signup_process['ref'] ?? $signup_process['referral'] ?? '';

// Check if we should auto-show promo/referral section
$show_promo_section = !empty($promo_code) || !empty($referral_code);

#-------------------------------------------------------------------------------
# HANDLE SOCIAL LOGIN CALLBACK - ARCHIVED
#-------------------------------------------------------------------------------
/* ARCHIVED: Social login disabled - providers don't give birthday data
if (isset($_GET['social_callback']) && $session->get('social_auth_data')) {
    $social_data = $session->get('social_auth_data');
    $session->unset('social_auth_data');
    
    // Pre-fill form with social data
    $_POST['firstname'] = $social_data['firstname'];
    $_POST['lastname'] = $social_data['lastname'];
    $_POST['email'] = $social_data['email'];
    $_POST['social_auth_id'] = $social_data['social_id'];
    $_POST['social_provider'] = $social_data['provider'];
    
    // Show success message
    $social_success_message = "Successfully connected with " . ucfirst($social_data['provider']) . "! Please complete your profile.";
}
*/

#-------------------------------------------------------------------------------
# HANDLE AJAX REQUESTS
#-------------------------------------------------------------------------------
if (isset($_REQUEST['ajax_action'])) {
    // Clear any output that might have been sent
    ob_clean();
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    switch ($_REQUEST['ajax_action']) {
        case 'validate_promo':
            try {
                $promoCode = $_REQUEST['promo_code'] ?? '';
                $productId = $signup_process['account_plan_id'] ?? null;
                
                error_log('[CREATEACCOUNT] Session data: ' . json_encode($signup_process));
                error_log('[CREATEACCOUNT] Validating promo: ' . $promoCode . ' for product: ' . $productId);
                
                if ($productId && $promoCode) {
                    $validation = $productManager->validatePromoCode($promoCode, $productId);
                    
                    error_log('[CREATEACCOUNT] Validation result: ' . json_encode($validation));
                    
                    if ($validation['valid']) {
                        // Calculate new price
                        $pricing = $productManager->calculatePrice($productId, $promoCode);
                        $validation['new_price'] = $pricing['formatted_final'] ?? '';
                        $validation['discount_amount'] = $pricing['formatted_discount'] ?? '';
                        
                        // Store in session
                        $signup_process['promo_code'] = $promoCode;
                        $signup_process['promo_validation'] = $validation;
                        $signup_process['final_price'] = $pricing['final_price'] ?? 0;
                        $session->set('signup_process_data', $signup_process);
                    }
                    
                    // Ensure clean JSON output
                    ob_clean();
                    echo json_encode($validation);
                } else {
                    ob_clean();
                    echo json_encode(['valid' => false, 'message' => 'Invalid request - missing product ID or promo code']);
                }
            } catch (Exception $e) {
                error_log('[CREATEACCOUNT] Exception in promo validation: ' . $e->getMessage());
                ob_clean();
                echo json_encode(['valid' => false, 'message' => 'Server error processing promo code']);
            }
            exit;
            
        case 'check_username':
            $username = $_REQUEST['username'] ?? '';
            $available = $createaccount->isavailable($username, 'username');
            echo json_encode(['available' => $available]);
            exit;
            
        case 'check_email':
            $email = $_REQUEST['email'] ?? '';
            $available = $createaccount->isavailable($email, 'email');
            echo json_encode(['available' => $available]);
            exit;
    }
}


#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSION
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $errors = [];
    
    // Validate required fields
    $required_fields = ['password', 'firstname', 'lastname', 'birthday'];
    
    // Check contact method
    $contact_method = $_POST['contact_method'] ?? 'phone';
    if ($contact_method == 'phone') {
        $required_fields[] = 'phone';
    } else {
        $required_fields[] = 'email';
    }
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . ' is required';
        }
    }
    
    // Validate phone
    if (!empty($_POST['phone'])) {
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        if (strlen($phone) !== 10) {
            $errors[] = 'Please enter a valid 10-digit phone number';
        }
    }
    
    // Validate email (optional)
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Check email availability - using the proper method
    if (!empty($_POST['email'])) {
        $email = trim(strtolower($_POST['email']));
        $response = $createaccount->isemailaccountavailable($email);
        
        if ($response !== true) {
            // We found an existing record
            $tempinfo = $response;
            
            // Check if it's a pending or validated user we can continue with
            if (!empty($tempinfo['status']) && in_array($tempinfo['status'], ['pending', 'validated'])) {
                // Store the existing user info and redirect appropriately
                $session->set('userregistrationdata', $tempinfo);
                
                if ($account_cost > 0) {
                    // Paid plan - go to checkout with existing user
                    $encoded_user_id = $qik->encodeId($tempinfo['user_id']);
                    header('Location: /claudecode/checkout_api.php?u=' . $encoded_user_id);
                    exit();
                } else {
                    // Free plan - check status
                    if ($tempinfo['status'] == 'validated' || $plandata['account_verification'] == 'notrequired') {
                        header('Location: /myaccount/welcome.php');
                    } else {
                        header('Location: /validate-account.php');
                    }
                    exit();
                }
            } else {
                // Email is truly unavailable
                $errors[] = 'This email is already registered';
            }
        }
    }
    
    
    // Validate password strength
    if (strlen($_POST['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    // Validate terms acceptance
    if (empty($_POST['terms'])) {
        $errors[] = 'You must agree to the Terms and Privacy Policy';
    }
    
    // Validate username (if required)
    if ($account_type == 'user' && !empty($_POST['username'])) {
        if (!$createaccount->isavailable($_POST['username'], 'username')) {
            $errors[] = 'This username is already taken';
        }
    }
    
    // Validate birthday from dropdowns
    if (!empty($_POST['birth_month']) && !empty($_POST['birth_day']) && !empty($_POST['birth_year'])) {
        // Combine the fields
        $_POST['birthday'] = $_POST['birth_year'] . '-' . $_POST['birth_month'] . '-' . $_POST['birth_day'];
        
        $birthdate = DateTime::createFromFormat('Y-m-d', $_POST['birthday']);
        if (!$birthdate) {
            $errors[] = 'Please select a valid date';
        } else {
            $age = $birthdate->diff(new DateTime())->y;
            if ($age < 13) {
                $errors[] = 'You must be at least 13 years old to create an account';
            }
        }
    } else {
        $errors[] = 'Please select your complete birth date';
    }
    
    if (empty($errors)) {
        // Prepare user data for creation
        $birthday_date = DateTime::createFromFormat('Y-m-d', $_POST['birthday']);
        $birthday_formatted = $birthday_date->format('Y-m-d');
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $first_name = ucfirst($_POST['firstname']);
        $last_name = ucfirst($_POST['lastname']);
        $email = !empty($_POST['email']) ? trim(strtolower($_POST['email'])) : '';
        $phone = preg_replace('/\D/', '', $_POST['phone']); // Strip non-digits
        
        // Generate username if not provided
        $username = $_POST['username'] ?? '';
        if (empty($username) && $account_type == 'user') {
            $username = $createaccount->generate_username($first_name, $last_name, $_POST['birthday']);
        }
        
        // Get location data from session
        $client_locationdata = $session->get('client_locationdata', []);
        $city = trim(!empty($client_locationdata['city']) ? $client_locationdata['city'] : '');
        $state = trim(!empty($client_locationdata['regionName']) ? $client_locationdata['regionName'] : '');
        $zip_code = trim(!empty($client_locationdata['zip']) ? $client_locationdata['zip'] : '');
        
        // Prepare input array for user creation
        $input = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'username' => $username,
            'email' => $email,
            'phone_number' => $phone,
            'profile_first_name' => $first_name,
            'profile_last_name' => $last_name,
            'profile_username' => $username,
            'profile_email' => $email,
            'profile_phone_type' => 'unknown',
            'hashed_password' => $hashed_password,
            'birthday' => $birthday_formatted,
            'birthday_month' => $birthday_date->format('m'),
            'city' => $city,
            'state' => $state,
            'zip_code' => $zip_code,
            'city2' => $city,
            'state2' => $state,
            'zip_code2' => $zip_code,
            'type' => 'real',
            'product_id' => $plandata['id'] ?? null,
            'account_plan' => $account_plan,
            'account_type' => $account_type,
            'account_cost' => $account_cost,
            'account_validation' => $plandata['account_verification'] ?? 'required',
            'avatar_file' => ''
        ];
        
        // Add promo code if provided
        if (!empty($_POST['promo_code'])) {
            $input['promocode'] = $_POST['promo_code'];
        }
        
        // Add referral code if provided
        if (!empty($_POST['referral_code'])) {
            $input['referral_code'] = $_POST['referral_code'];
        }
        
        // Create the user
        try {
            $user_id = $createaccount->create_user($input);
            
            if ($user_id) {
                // Store registration data in session for validation page
                $session->set('userregistrationdata', array_merge($input, ['user_id' => $user_id]));
                $session->set('accountcode', $user_id);
                
                // Also ensure signup_process_data is set for checkout
                if (empty($session->get('signup_process_data'))) {
                    $session->set('signup_process_data', [
                        'account_type' => $account_type,
                        'account_plan' => $account_plan,
                        'account_plan_id' => $plandata['id'] ?? 0,
                        'promo_code' => $_POST['promo_code'] ?? '',
                        'referrer_code' => $_POST['referral_code'] ?? ''
                    ]);
                }
                
                // Redirect based on account cost and validation requirements
                if ($account_cost > 0) {
                    // Paid plan - go to checkout
                    $encoded_user_id = $qik->encodeId($user_id);
                    header('Location: /claudecode/checkout_api.php?u=' . $encoded_user_id);
                } else {
                    // Free plan - check validation requirements
                    if ($plandata['account_verification'] == 'notrequired') {
                        // No validation required, go to welcome
                        header('Location: /myaccount/welcome.php');
                    } else {
                        // Validation required
                        header('Location: /validate-account.php');
                    }
                }
                exit();
            } else {
                $errors[] = 'Failed to create account. Please try again.';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred while creating your account. Please try again.';
            error_log('User creation error: ' . $e->getMessage());
        }
    }
}

#-------------------------------------------------------------------------------
# PAGE CONFIGURATION
#-------------------------------------------------------------------------------
$page_title = "Account Details - Birthday.Gold";
$page_description = "Complete your Birthday Gold account setup";

#-------------------------------------------------------------------------------
# ADDITIONAL STYLES
#-------------------------------------------------------------------------------
$additionalstyles .= '
<link href="/claudecode/createaccount_styles.css" rel="stylesheet">
<style>
/* Additional styles for promo/referral section */
.promo-referral-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-top: 1rem;
}
.promo-referral-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    user-select: none;
}
.promo-referral-header:hover {
    opacity: 0.8;
}
.promo-referral-content {
    margin-top: 1rem;
}
.code-input-group {
    position: relative;
}
.code-input-group input {
    padding-right: 80px;
}
.code-input-group .btn {
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    padding: 0.25rem 1rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}
.code-input-group .btn.btn-danger {
    animation: pulse 0.3s ease-in-out;
}
@keyframes pulse {
    0% { transform: translateY(-50%) scale(1); }
    50% { transform: translateY(-50%) scale(1.05); }
    100% { transform: translateY(-50%) scale(1); }
}

/* Disabled state for code buttons */
.code-input-group .btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: #e9ecef !important;
    border-color: #dee2e6 !important;
    color: #6c757d !important;
}

/* Override any theme styles that might affect input backgrounds */
input[type="text"],
input[type="email"],
input[type="tel"],
input[type="password"],
input[type="date"],
.form-control {
    background-color: #ffffff !important;
}

/* Ensure password field specifically has white background */
#password {
    background-color: #ffffff !important;
}

/* New Contact Method Tab Styles - matching signup page style */
.contact-method-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.tab-option {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: 12px;
    background: white;
    color: #6c757d;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid #e9ecef;
}

.tab-option:hover {
    border-color: #198754;
    background: #f0f9f0;
}

.tab-option.active {
    background: #e8f5e8;
    color: #198754;
    border: 3px solid #198754;
    box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.2);
}

.tab-option.active::after {
    content: "✓";
    position: absolute;
    top: -8px;
    right: -8px;
    background: #198754;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    z-index: 100;
}

.tab-option i {
    font-size: 1.2rem;
}

/* No validation styles - all validation is server-side */
.form-control {
    border-color: #e9ecef !important;
}

/* Blue focus state for all form fields */
.form-control:focus {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.15) !important;
}

/* Ensure password field has same styling */
#password {
    border-color: #e9ecef !important;
}

#password:focus {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.15) !important;
}

/* Prevent password field from showing red on invalid state */
#password:invalid {
    border-color: #e9ecef !important;
    box-shadow: none !important;
}

/* Never show browser validation */
.form-control:invalid {
    box-shadow: none !important;
    border-color: #e9ecef !important;
}

/* Hide all browser validation UI */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}

/* Disable browser validation tooltips */
input:invalid,
textarea:invalid,
select:invalid {
    box-shadow: none !important;
    outline: none !important;
}

/* Override Bootstraps validation styles - but allow server-side validation */
.form-control:invalid {
    border-color: #e9ecef !important;
    padding-right: calc(1.5em + 0.75rem) !important;
    background-image: none !important;
}

/* Allow red borders for server-side validation errors */
.form-control.is-invalid {
    border-color: #dc3545 !important;
    padding-right: calc(1.5em + 0.75rem) !important;
    background-image: none !important;
}

/* Red border for invalid checkbox */
.form-check-input.is-invalid {
    border-color: #dc3545 !important;
}

/* Red text for checkbox label when invalid */
.form-check-input.is-invalid ~ .form-check-label {
    color: #dc3545;
}

/* Input group styling for consistency */
.input-group-text {
    background-color: #f8f9fa;
    border: 2px solid #e9ecef;
    color: #6c757d;
}

.input-group .form-control:focus ~ .input-group-text,
.input-group .input-group-text:has(+ .form-control:focus) {
    border-color: #0d6efd;
}

/* Ensure email icon is properly sized */
.input-group-text i {
    font-size: 1rem;
}

/* Fix for autofill display issue */
.contact-field {
    position: relative;
    background: white;
    transition: all 0.3s ease;
}


/* Fix Chrome autofill background */
.contact-field input:-webkit-autofill,
.contact-field input:-webkit-autofill:hover,
.contact-field input:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0px 1000px white inset !important;
    box-shadow: 0 0 0px 1000px white inset !important;
    background-color: white !important;
    -webkit-text-fill-color: #495057 !important;
}

/* Make button layout responsive on large screens */
@media (min-width: 992px) {
    .step-nav .row {
        max-width: 600px;
        margin: 0 auto;
    }
}

@media (min-width: 1200px) {
    .step-nav .row {
        max-width: 700px;
    }
}

/* Container needs relative positioning and overflow visible for checkmarks */
.contact-method-tabs {
    overflow: visible !important;
    position: relative;
}

.mb-4 > .contact-method-tabs {
    margin-bottom: 1.5rem !important; /* Extra space for checkmark */
}

/* Mobile adjustments for contact tabs */
@media (max-width: 480px) {
    .contact-method-tabs {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .tab-option {
        width: 100%;
    }
}

/* Birthday dropdowns - ensure they stay horizontal on mobile */
@media (max-width: 576px) {
    #birth_month {
        font-size: 0.875rem;
    }
    
    #birth_day,
    #birth_year {
        font-size: 0.875rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}

/* Style for underage years in dropdown */
#birth_year option.text-danger {
    color: #dc3545 !important;
    font-weight: 500;
}

/* Some browsers need more specific targeting */
select#birth_year option[class*="danger"] {
    color: #dc3545 !important;
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
        <p>Step 2: Enter your account details</p>
    </div>

    <!-- Progress Bar -->
    <div class="progress-container mb-4">
        <div class="progress-steps">
            <div class="step-indicator completed">
                <i class="bi bi-check-circle-fill"></i>
                <span>Choose Plan</span>
            </div>
            <div class="step-indicator active">
                <i class="bi bi-2-circle-fill"></i>
                <span>Account Details</span>
            </div>
            <div class="step-indicator">
                <i class="bi bi-3-circle"></i>
                <span><?php echo ($account_cost > 0) ? 'Checkout' : 'Validate'; ?></span>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="content mx-auto" style="max-width: 900px;">
        <?php
        // Error Message Display
        if (!empty($errors)) {
            echo '<div class="alert alert-danger" role="alert">';
            echo '<ul class="mb-0">';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <!-- Plan Summary -->
        <?php if (!empty($plandata['account_name']) && $account_cost > 0): ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
            <div>
                <strong><?php echo htmlspecialchars($plandata['account_name']); ?></strong>
                <span class="text-muted ms-2"><?php echo ucfirst($account_type); ?> Account</span>
            </div>
            <div class="text-end">
                <span id="displayPrice" class="h5 mb-0">
                    $<?php echo number_format($account_cost / 100, 2); ?>
                </span>
                <span class="text-muted">/year</span>
            </div>
        </div>
        <?php endif; ?>


        <?php 
        /* ARCHIVED FEATURE: Social Login 
           Disabled because social providers don't give us birthday data,
           which is essential for the Birthday.Gold platform.
           Keeping code for potential future use if we find a solution.
           
        <!-- Social Login Options -->
        <div class="social-login-section mb-4">
            <h5 class="text-center mb-3">Sign up quickly with</h5>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-primary w-100 social-btn" id="googleSignup">
                        <i class="bi bi-google me-2"></i>Google
                    </button>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-primary w-100 social-btn" id="facebookSignup">
                        <i class="bi bi-facebook me-2"></i>Facebook
                    </button>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-dark w-100 social-btn" id="appleSignup">
                        <i class="bi bi-apple me-2"></i>Apple
                    </button>
                </div>
            </div>
            
            <div class="divider-container">
                <hr class="divider">
                <span class="divider-text">or sign up manually</span>
            </div>
        </div>
        */ ?>

        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="detailsForm" novalidate>
            <?php echo $display->inputcsrf_token(); ?>
            <?php if (isset($_POST['social_provider'])): ?>
            <input type="hidden" name="social_provider" value="<?php echo htmlspecialchars($_POST['social_provider']); ?>">
            <input type="hidden" name="social_auth_id" value="<?php echo htmlspecialchars($_POST['social_auth_id'] ?? ''); ?>">
            <input type="hidden" name="social_processed" value="1">
            <?php endif; ?>
            
            
            <!-- Name Section -->
            <div class="form-section">
                <h5 class="section-title">Your Name and Birthday</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="firstname" name="firstname" 
                               value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="lastname" name="lastname" 
                               value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>">
                    </div>
                </div>
          
                <div class="row">
                    <div class="col-md-12">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2" style="max-width: 400px;">
                            <div class="flex-fill">
                                <select class="form-control" id="birth_month" name="birth_month">
                                    <option value="">Month</option>
                                    <?php
                                    $months = [
                                        '01' => '01 - January', '02' => '02 - February', '03' => '03 - March',
                                        '04' => '04 - April', '05' => '05 - May', '06' => '06 - June',
                                        '07' => '07 - July', '08' => '08 - August', '09' => '09 - September',
                                        '10' => '10 - October', '11' => '11 - November', '12' => '12 - December'
                                    ];
                                    $selected_month = $_POST['birth_month'] ?? '';
                                    if (empty($selected_month) && !empty($_POST['birthday'])) {
                                        $selected_month = date('m', strtotime($_POST['birthday']));
                                    }
                                    foreach ($months as $value => $label) {
                                        $selected = ($selected_month == $value) ? 'selected' : '';
                                        echo "<option value=\"$value\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div style="width: 95px;">
                                <select class="form-control" id="birth_day" name="birth_day">
                                    <option value="">Day</option>
                                    <?php
                                    $selected_day = $_POST['birth_day'] ?? '';
                                    if (empty($selected_day) && !empty($_POST['birthday'])) {
                                        $selected_day = date('d', strtotime($_POST['birthday']));
                                    }
                                    for ($i = 1; $i <= 31; $i++) {
                                        $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                                        $selected = ($selected_day == $day) ? 'selected' : '';
                                        echo "<option value=\"$day\" $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div style="width: 120px;">
                                <select class="form-control" id="birth_year" name="birth_year">
                                    <option value="">Year</option>
                                    <?php
                                    $current_year = date('Y');
                                    $selected_year = $_POST['birth_year'] ?? '';
                                    if (empty($selected_year) && !empty($_POST['birthday'])) {
                                        $selected_year = date('Y', strtotime($_POST['birthday']));
                                    }
                                    $min_age_year = $current_year - 13;
                                    for ($i = $current_year; $i >= 1900; $i--) {
                                        $selected = ($selected_year == $i) ? 'selected' : '';
                                        $class = ($i > $min_age_year) ? 'class="text-danger"' : '';
                                        echo "<option value=\"$i\" $selected $class>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <small class="text-muted">We'll use this to notify you of birthday rewards</small>
                        <!-- Hidden field for combined birthday value -->
                        <input type="hidden" id="birthday" name="birthday" value="<?php echo htmlspecialchars($_POST['birthday'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <!-- Empty column for balance on larger screens -->
                        <input type="hidden" id="altContact" name="alt_contact" value="">
                    </div>
                </div>
            </div>
            
            <!-- Account Info Section -->
            <div class="form-section">
                <h5 class="section-title">Account Information</h5>
                
                <!-- Contact Method Selection -->
                <div class="mb-4">
                    <label class="form-label">Start Your Account with?</label>
                    <div class="contact-method-tabs">
                        <div class="tab-option active" data-method="phone">
                            <i class="bi bi-phone"></i>
                            <span>Use Phone Number</span>
                        </div>
                        <div class="tab-option" data-method="email">
                            <i class="bi bi-envelope"></i>
                            <span>Use Email Address</span>
                        </div>
                    </div>
                    
                    <!-- Unified Input Field Container -->
                    <div class="contact-input-wrapper mt-3" id="contactInputContainer">
                        <!-- Input field will be dynamically inserted here -->
                    </div>
                    
                    <!-- Hidden radio buttons for form submission -->
                    <div class="d-none">
                        <input type="radio" name="contact_method" id="usePhone" value="phone" checked>
                        <input type="radio" name="contact_method" id="useEmail" value="email">
                    </div>
                </div>
        
                
                <!-- Hidden username field to satisfy password managers -->
                <input type="text" name="username_dummy" id="username_dummy" style="display: none !important; position: absolute; left: -9999px;" tabindex="-1" autocomplete="username">
                
                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="form-label">Create Password <span class="text-danger">*</span></label>
                    <div class="password-input-group">
                        <input type="password" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength mt-2">
                        <div class="strength-bar"></div>
                    </div>
                    <small class="text-muted">At least 8 characters with a mix of letters and numbers</small>
                </div>
                
                <!-- Terms and Privacy -->
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" <?php echo (!empty($_POST['terms'])) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="/legalhub/terms" class="text-underline">Terms</a> and <a href="/legalhub/privacy" class="text-underline">Privacy Policy</a>
                        </label>
                    </div>
                </div>
                
                <!-- Promo & Referral Codes (inside Account Info section) -->
                <div class="promo-referral-section">
                    <div class="promo-referral-header" id="togglePromoReferral">
                        <span>
                            <i class="bi bi-gift me-1"></i>
                            <strong>Promo or Referral Code?</strong>
                        </span>
                        <i class="bi bi-chevron-down" id="promoReferralChevron"></i>
                    </div>
                    
                    <div class="promo-referral-content collapse <?php echo $show_promo_section ? 'show' : ''; ?>" id="promoReferralSection">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="promo_code" class="form-label">Promo Code</label>
                                <div class="code-input-group">
                                    <input type="text" class="form-control" id="promo_code" name="promo_code" 
                                           placeholder="Enter promo code" value="<?php echo htmlspecialchars($promo_code); ?>">
                                    <button type="button" class="btn btn-success me-2" id="applyPromo">Apply</button>
                                </div>
                                <div id="promoMessage" class="mt-1"></div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="referral_code" class="form-label">Referral Code</label>
                                <div class="code-input-group">
                                    <input type="text" class="form-control" id="referral_code" name="referral_code" 
                                           placeholder="Friend's referral code" value="<?php echo htmlspecialchars($referral_code); ?>">
                                    <button type="button" class="btn btn-success me-2" id="verifyReferral">Verify</button>
                                </div>
                                <div id="referralMessage" class="mt-1"></div>
                                <small class="text-muted ms-1">Enter the code of the person who referred you</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
<!-- Navigation Buttons -->
<div class="step-nav mt-4">
    <table style="width: 100%;">
        <tr>
            <td style="width: 1%; white-space: nowrap; padding-right: 8px;">
                <a href="/signup" class="btn-secondary-custom">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </td>
            <td>
                <button type="submit" class="btn-primary-custom w-100">
                    <?php if ($account_cost > 0): ?>
                        Continue to Checkout <i class="bi bi-arrow-right ms-2"></i>
                    <?php else: ?>
                        Continue to Validate <i class="bi bi-arrow-right ms-2"></i>
                    <?php endif; ?>
                </button>
            </td>
        </tr>
    </table>
</div>

        </form>
    </div>

</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Store page data for JavaScript
const pageData = {
    ajaxUrl: '<?php echo $_SERVER['PHP_SELF']; ?>',
    csrfToken: '<?php echo $session->get('csrf_token'); ?>',
    productId: <?php echo isset($signup_process['account_plan_id']) ? $signup_process['account_plan_id'] : '0'; ?>,
    originalPrice: <?php echo $account_cost; ?>
};
console.log('[CREATEACCOUNT] Page data:', pageData);

// Handle new tab-style contact method switching
document.addEventListener('DOMContentLoaded', function() {
    const tabOptions = document.querySelectorAll('.tab-option');
    const container = document.getElementById('contactInputContainer');
    const phoneRadio = document.getElementById('usePhone');
    const emailRadio = document.getElementById('useEmail');
    
    // Store values to preserve when switching
    let phoneValue = '<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>';
    let emailValue = '<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>';
    
    // Create phone field HTML
    function createPhoneField() {
        return `
            <div class="contact-field" id="phoneField">
                <div class="input-group">
                    <span class="input-group-text">+1</span>
                    <input type="text" 
                           class="form-control" 
                           id="phone" 
                           name="phone" 
                           placeholder="555-123-4567"
                           value="${phoneValue}"
                           inputmode="tel">
                </div>
                <small class="text-muted mt-1 d-block">We'll send you a verification code via SMS</small>
            </div>
        `;
    }
    
    // Create email field HTML
    function createEmailField() {
        return `
            <div class="contact-field" id="emailField">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="text" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           placeholder="your@email.com"
                           value="${emailValue}"
                           inputmode="email">
                </div>
                <small class="text-muted mt-1 d-block">We'll send you a verification link via email</small>
            </div>
        `;
    }
    
    // Switch contact method
    function switchContactMethod(method) {
        // Save current value before switching
        const currentPhone = document.getElementById('phone');
        const currentEmail = document.getElementById('email');
        
        if (currentPhone) phoneValue = currentPhone.value;
        if (currentEmail) emailValue = currentEmail.value;
        
        // Clear container
        container.innerHTML = '';
        
        // Create new field
        if (method === 'phone') {
            container.innerHTML = createPhoneField();
            phoneRadio.checked = true;
            
            // Attach event handlers
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                // Update stored value on input
                phoneInput.addEventListener('input', function(e) {
                    phoneValue = e.target.value;
                    
                    // Format phone number
                    if (typeof formatPhoneNumber === 'function') {
                        formatPhoneNumber(e);
                    }
                });
            }
        } else {
            container.innerHTML = createEmailField();
            emailRadio.checked = true;
            
            // Attach event handlers
            const emailInput = document.getElementById('email');
            if (emailInput) {
                // Update stored value on input
                emailInput.addEventListener('input', function(e) {
                    emailValue = e.target.value;
                });
            }
        }
    }
    
    // Attach click handlers
    tabOptions.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active from all tabs
            tabOptions.forEach(t => t.classList.remove('active'));
            
            // Add active to clicked tab
            this.classList.add('active');
            
            // Switch fields
            switchContactMethod(this.dataset.method);
        });
    });
    
    // Initialize with default selection
    switchContactMethod(phoneRadio.checked ? 'phone' : 'email');
    
    // Highlight error fields if there are errors
    <?php if (!empty($errors)): ?>
    // Fields that might have errors
    const errorFieldMap = {
        'First name is required': 'firstname',
        'Lastname is required': 'lastname',
        'Last name is required': 'lastname',
        'Birthday is required': 'birthday',
        'Please select your complete birth date': ['birth_month', 'birth_day', 'birth_year'],
        'Please select a valid date': ['birth_month', 'birth_day', 'birth_year'],
        'Password is required': 'password',
        'Password must be at least 8 characters': 'password',
        'Phone is required': 'phone',
        'Please enter a valid 10-digit phone number': 'phone',
        'Email is required': 'email',
        'Please enter a valid email address': 'email',
        'This email is already registered': 'email',
        'You must agree to the Terms and Privacy Policy': 'terms'
    };
    
    // Parse errors and highlight fields
    const errors = <?php echo json_encode($errors); ?>;
    errors.forEach(error => {
        // Find the field ID for this error
        Object.keys(errorFieldMap).forEach(errorText => {
            if (error.includes(errorText.replace(' is required', '')) || error === errorText) {
                const fieldIds = errorFieldMap[errorText];
                
                // Handle both single field and array of fields
                const fieldsToHighlight = Array.isArray(fieldIds) ? fieldIds : [fieldIds];
                
                fieldsToHighlight.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.classList.add('is-invalid');
                        field.style.borderColor = '#dc3545';
                    }
                });
            }
        });
    });
    <?php endif; ?>
});
</script>
<!-- Load embedded promo validation to avoid 403 errors -->
<script src="/promo_validate_embedded.php"></script>
<script src="/claudecode/createaccount_flow.js"></script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>