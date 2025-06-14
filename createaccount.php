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

// Get URL parameters from signup data
$promo_code = $signup_process['promo'] ?? $signup_process['promo_code'] ?? '';
$referral_code = $signup_process['ref'] ?? $signup_process['referral'] ?? '';

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
    
    // Validate username (if required)
    if ($account_type == 'user' && !empty($_POST['username'])) {
        if (!$createaccount->isavailable($_POST['username'], 'username')) {
            $errors[] = 'This username is already taken';
        }
    }
    
    // Validate birthday
    if (!empty($_POST['birthday'])) {
        $birthdate = DateTime::createFromFormat('Y-m-d', $_POST['birthday']);
        if (!$birthdate) {
            $errors[] = 'Please enter a valid date';
        } else {
            $age = $birthdate->diff(new DateTime())->y;
            if ($age < 13) {
                $errors[] = 'You must be at least 13 years old to create an account';
            }
        }
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
$additionalstyles = '
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
' . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/claudecode/createaccount_styles.css') . '
/* Additional styles for promo/referral section */
.promo-referral-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
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

/* Fix for autofill display issue */
.contact-field {
    position: relative;
    background: white;
    transition: all 0.3s ease;
}

/* Completely hide inactive fields */
.contact-field.d-none {
    display: none !important;
    visibility: hidden !important;
    position: absolute !important;
    left: -9999px !important;
    top: -9999px !important;
    z-index: -999 !important;
    opacity: 0 !important;
    pointer-events: none !important;
    height: 0 !important;
    overflow: hidden !important;
}

.contact-field.d-none * {
    visibility: hidden !important;
    display: none !important;
}

/* Ensure active field is visible and on top */
.contact-field:not(.d-none) {
    display: block !important;
    visibility: visible !important;
    position: relative !important;
    z-index: 10 !important;
    opacity: 1 !important;
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
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: 66%;" aria-valuenow="66" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
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
                <span>Checkout</span>
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

        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="detailsForm">
            <?php echo $display->inputcsrf_token(); ?>
            <?php if (isset($_POST['social_provider'])): ?>
            <input type="hidden" name="social_provider" value="<?php echo htmlspecialchars($_POST['social_provider']); ?>">
            <input type="hidden" name="social_auth_id" value="<?php echo htmlspecialchars($_POST['social_auth_id'] ?? ''); ?>">
            <input type="hidden" name="social_processed" value="1">
            <?php endif; ?>
            
            
            <!-- Account Information Section (Combined) -->
            <div class="form-section">
                <h5 class="section-title">Account Information</h5>
                
                <!-- Phone/Email Toggle -->
                <div class="contact-toggle mb-3">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="contact_method" id="usePhone" value="phone" checked>
                        <label class="btn btn-outline-success" for="usePhone">
                            <i class="bi bi-phone"></i> Use Phone Number
                        </label>
                        
                        <input type="radio" class="btn-check" name="contact_method" id="useEmail" value="email">
                        <label class="btn btn-outline-success" for="useEmail">
                            <i class="bi bi-envelope"></i> Use Email Address
                        </label>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Phone Field -->
                    <div class="col-md-12 mb-3 contact-field" id="phoneField">
                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">+1</span>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="(555) 123-4567"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                   autocomplete="tel">
                        </div>
                        <div class="invalid-feedback">Please enter a valid phone number</div>
                        <small class="text-muted">We'll send you a verification code via SMS</small>
                    </div>
                    
                    <!-- Email Field (hidden by default) -->
                    <div class="col-md-12 mb-3 contact-field d-none" id="emailField">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="your@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               autocomplete="email">
                        <div class="invalid-feedback">Please enter a valid email address</div>
                        <small class="text-muted">We'll send you a verification link via email</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="password-input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength mt-2">
                            <div class="strength-bar"></div>
                        </div>
                        <small class="text-muted">At least 8 characters with a mix of letters and numbers</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="firstname" name="firstname" 
                               value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="lastname" name="lastname" 
                               value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="birthday" class="form-label">Birthday <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="birthday" name="birthday" 
                               value="<?php echo htmlspecialchars($_POST['birthday'] ?? ''); ?>" required>
                        <small class="text-muted">We'll use this to notify you of birthday rewards</small>
                    </div>
                    
                    <!-- Hidden field for alternative contact method -->
                    <div class="col-md-6 mb-3">
                        <input type="hidden" id="altContact" name="alt_contact" value="">
                    </div>
                </div>
            </div>
            
            <!-- Promo & Referral Codes Section (Combined and Collapsible) -->
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
                                <button type="button" class="btn btn-success" id="applyPromo">Apply</button>
                            </div>
                            <div id="promoMessage" class="mt-1"></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="referral_code" class="form-label">Referral Code</label>
                            <input type="text" class="form-control" id="referral_code" name="referral_code" 
                                   placeholder="Friend's referral code" value="<?php echo htmlspecialchars($referral_code); ?>">
                            <small class="text-muted">Enter the code of the person who referred you</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Newsletter Opt-in -->
            <div class="form-section">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" value="1" checked>
                    <label class="form-check-label" for="newsletter">
                        Send me birthday reward reminders and special offers
                    </label>
                </div>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="step-nav mt-4">
                <a href="/newsignup.php" class="btn-secondary-custom">
                    <i class="bi bi-arrow-left me-2"></i>Back to Plans
                </a>
                <button type="submit" class="btn-primary-custom">
                    <?php if ($account_cost > 0): ?>
                        Continue to Checkout <i class="bi bi-arrow-right ms-2"></i>
                    <?php else: ?>
                        Create Free Account <i class="bi bi-check-lg ms-2"></i>
                    <?php endif; ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="border-top mt-4">
        <div class="container py-4">
            <div class="row text-center">
                <div class="col-12">
                    <small class="text-muted">
                        By creating an account, you agree to our 
                        <a href="/terms" class="text-decoration-none text-success fw-medium">Terms of Service</a> and 
                        <a href="/privacy" class="text-decoration-none text-success fw-medium">Privacy Policy</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>
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
</script>
<!-- Load embedded promo validation to avoid 403 errors -->
<script src="/promo_validate_embedded.php"></script>
<script src="/claudecode/createaccount_flow.js"></script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>