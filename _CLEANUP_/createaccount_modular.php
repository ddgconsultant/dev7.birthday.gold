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
$errors = [];
$processed_data = [];

if ($app->formposted()) {
    // Process form modules based on account type
    
    // 1. Always process name and birthday
    $name_birthday_data = include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/process_name_birthday.inc.php');
    if (is_array($name_birthday_data)) {
        $processed_data = array_merge($processed_data, $name_birthday_data);
    }
    
    // 2. Process business info if business account
    if ($account_type == 'business') {
        $business_data = include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/process_business_info.inc.php');
        if (is_array($business_data)) {
            $processed_data = array_merge($processed_data, $business_data);
        }
    }
    
    // 3. Process family members if family account
    if ($account_type == 'family') {
        $family_data = include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/process_family_members.inc.php');
        if (is_array($family_data)) {
            $processed_data = array_merge($processed_data, $family_data);
        }
    }
    
    // 4. Always process account info
    $account_data = include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/process_account_info.inc.php');
    if (is_array($account_data)) {
        $processed_data = array_merge($processed_data, $account_data);
    }
    
    // Check if we have an existing user situation
    if (!empty($processed_data['existing_user'])) {
        $tempinfo = $processed_data['existing_user'];
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
    }
    
    // If no errors, create the account
    if (empty($errors)) {
        // Get location data from session
        $client_locationdata = $session->get('client_locationdata', []);
        $city = trim(!empty($client_locationdata['city']) ? $client_locationdata['city'] : '');
        $state = trim(!empty($client_locationdata['regionName']) ? $client_locationdata['regionName'] : '');
        $zip_code = trim(!empty($client_locationdata['zip']) ? $client_locationdata['zip'] : '');
        
        // Generate username if not provided
        $username = $processed_data['username'] ?? '';
        if (empty($username) && $account_type == 'user') {
            $username = $createaccount->generate_username(
                $processed_data['firstname'], 
                $processed_data['lastname'], 
                $processed_data['birthday']
            );
        }
        
        // Prepare input array for user creation
        $input = [
            'first_name' => $processed_data['firstname'],
            'last_name' => $processed_data['lastname'],
            'username' => $username,
            'email' => $processed_data['email'] ?? '',
            'phone_number' => $processed_data['phone_number'] ?? '',
            'profile_first_name' => $processed_data['firstname'],
            'profile_last_name' => $processed_data['lastname'],
            'profile_username' => $username,
            'profile_email' => $processed_data['email'] ?? '',
            'profile_phone_type' => 'unknown',
            'hashed_password' => $processed_data['hashed_password'],
            'birthday' => $processed_data['birthday'],
            'birthday_month' => $processed_data['birthday_month'],
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
        
        // Add business fields if applicable
        if ($account_type == 'business') {
            $input['business_name'] = $processed_data['business_name'] ?? '';
            $input['business_type'] = $processed_data['business_type'] ?? '';
            $input['business_phone'] = $processed_data['business_phone'] ?? '';
            $input['business_website'] = $processed_data['business_website'] ?? '';
        }
        
        // Add promo/referral codes
        if (!empty($processed_data['promo_code'])) {
            $input['promocode'] = $processed_data['promo_code'];
        }
        if (!empty($processed_data['referral_code'])) {
            $input['referral_code'] = $processed_data['referral_code'];
        }
        
        // Create the user
        try {
            $user_id = $createaccount->create_user($input);
            
            if ($user_id) {
                // Handle family account children
                if ($account_type == 'family' && !empty($processed_data['children'])) {
                    // Store children data in session for processing after account validation
                    $session->set('pending_children', $processed_data['children']);
                }
                
                // Store registration data in session for validation page
                $session->set('userregistrationdata', array_merge($input, ['user_id' => $user_id]));
                $session->set('accountcode', $user_id);
                
                // Also ensure signup_process_data is set for checkout
                if (empty($session->get('signup_process_data'))) {
                    $session->set('signup_process_data', [
                        'account_type' => $account_type,
                        'account_plan' => $account_plan,
                        'account_plan_id' => $plandata['id'] ?? 0,
                        'promo_code' => $processed_data['promo_code'] ?? '',
                        'referrer_code' => $processed_data['referral_code'] ?? ''
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

/* Family account child entries */
.child-entry {
    background-color: #f8f9fa;
    position: relative;
}

.child-entry .remove-child {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.child-entry h6 {
    color: #495057;
    font-weight: 600;
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

        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="detailsForm" novalidate>
            <?php echo $display->inputcsrf_token(); ?>
            
            <?php
            // Include form modules based on account type
            
            // 1. Always include name and birthday fields
            include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/name_birthday.inc.php');
            
            // 2. Include business fields if business account
            if ($account_type == 'business') {
                include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/business_info.inc.php');
            }
            
            // 3. Include family member fields if family account
            if ($account_type == 'family') {
                include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/family_members.inc.php');
            }
            
            // 4. Always include account information fields
            include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/account_info.inc.php');
            ?>
            
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

// Highlight error fields if there are errors
document.addEventListener('DOMContentLoaded', function() {
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
        'Phone number is required': 'phone',
        'Please enter a valid 10-digit phone number': 'phone',
        'Email is required': 'email',
        'Email address is required': 'email',
        'Please enter a valid email address': 'email',
        'This email is already registered': 'email',
        'You must agree to the Terms and Privacy Policy': 'terms',
        'Business name is required': 'business_name'
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