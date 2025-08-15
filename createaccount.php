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

// Initialize form values and errors
$values = $_POST;
$errors = [];

// Debug POST data for gift certificate
if ($mode === 'dev' && $app->formposted() && $account_type === 'giftcertificate') {
    error_log('[CREATENEWACCOUNT] POST data keys: ' . json_encode(array_keys($_POST)));
    error_log('[CREATENEWACCOUNT] Recipient fields in POST: ' . json_encode(array_filter($_POST, function($key) {
        return strpos($key, 'recipient_') === 0 || strpos($key, 'delivery_') === 0 || strpos($key, 'gift_') === 0;
    }, ARRAY_FILTER_USE_KEY)));
}

// Debug output for development
if ($mode === 'dev') {
    error_log('[CREATENEWACCOUNT] Signup process data: ' . json_encode($signup_process));
    error_log('[CREATENEWACCOUNT] Account plan ID: ' . ($signup_process['account_plan_id'] ?? 'NOT SET'));
}

#-------------------------------------------------------------------------------
# DEFINE FORM SECTIONS FOR EACH ACCOUNT TYPE
#-------------------------------------------------------------------------------
// Default sections array
$default_sections = [
    'section_open' => '/core/forms/signup/section_open.inc',
    'section_close' => '/core/forms/signup/section_close.inc',
    'name_birthday' => '/core/forms/signup/section_name_birthday.inc',
    'account_opener' => '/core/forms/signup/section_account_opener.inc',
    'credentials' => '/core/forms/signup/section_credentials.inc',
    'promo_codes' => '/core/forms/signup/section_promo_codes.inc'
];

// Define section configurations for each account type
// Each section can have configuration passed through $section_config variable
$section_configs = [
    'user' => [
        'sections' => [
            // Name and Birthday is already in its own card
            'name_birthday',
            // Account Information group
            'account_opener', 
            'credentials', 
            'promo_codes'
        ]
    ],
    'parental' => [
        'sections' => [
            'name_birthday',
            // Family section is already in its own card
            'family_children',
            // Account Information group
            'account_opener',
            'credentials',
            'promo_codes'
        ]
    ],
    'family' => [
        'sections' => [
            'name_birthday',
            'family_children',
            'account_opener',
            'credentials',
            'promo_codes'
        ]
    ],
    'business' => [
        'sections' => [
            'name_birthday',
            // Business info is already in its own card
            'business_info',
            // Account Information group
            'account_opener',
            'credentials',
            'promo_codes'
        ]
    ],
    'giftcertificate' => [
        'sections' => [
            'name_birthday',
            // Account Information group (shorter for gift cert)
            'account_opener',
            'credentials',
            'section_close',
            // Gift Certificate is its own card with special styling
            ['section_open', ['section_title' => 'Gift Certificate Details', 'section_info_modal' => 'giftCertificateInfoModal', 'section_class' => 'gift-certificate-section']],
            'gift_certificate',
            'section_close',
            // Additional Options card
            ['section_open', ['section_title' => 'Additional Options', 'section_info_modal' => 'additionalOptionsInfoModal']],
            'promo_codes',
            'section_close'
        ]
    ]
];

// Get configuration for current account type
$config = $section_configs[$account_type] ?? $section_configs['user'];

// Function to process a section (handles both string and array formats)
function processSection($section_item, $default_sections, $process_handlers = false, &$values = null, &$errors = null, $signup_process = null, $app = null, $session = null) {
    $section_key = '';
    $section_vars = [];
    
    // Handle array format [section_key, variables]
    if (is_array($section_item)) {
        $section_key = $section_item[0] ?? '';
        $section_vars = $section_item[1] ?? [];
    } else {
        $section_key = $section_item;
    }
    
    if (isset($default_sections[$section_key])) {
        $section_file = $_SERVER['DOCUMENT_ROOT'] . $default_sections[$section_key];
        if (file_exists($section_file)) {
            // Extract section variables into current scope
            extract($section_vars);
            include($section_file);
        }
    }
}

// Debug logging
if ($mode === 'dev') {
    error_log('[CREATENEWACCOUNT] Account type: ' . $account_type);
    error_log('[CREATENEWACCOUNT] Config sections: ' . json_encode($config['sections']));
    error_log('[CREATENEWACCOUNT] Default sections: ' . json_encode(array_keys($default_sections)));
}

// Add all possible sections to default array
$default_sections['family_children'] = '/core/forms/signup/section_family_children.inc';
$default_sections['business_info'] = '/core/forms/signup/section_business_info.inc';
$default_sections['gift_certificate'] = '/core/forms/signup/section_gift_certificate.inc';

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
                
                error_log('[CREATENEWACCOUNT] Session data: ' . json_encode($signup_process));
                error_log('[CREATENEWACCOUNT] Validating promo: ' . $promoCode . ' for product: ' . $productId);
                
                if ($productId && $promoCode) {
                    $validation = $productManager->validatePromoCode($promoCode, $productId);
                    
                    error_log('[CREATENEWACCOUNT] Validation result: ' . json_encode($validation));
                    
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
                error_log('[CREATENEWACCOUNT] Exception in promo validation: ' . $e->getMessage());
                ob_clean();
                echo json_encode(['valid' => false, 'message' => 'Server error processing promo code']);
            }
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
    error_log('[CREATENEWACCOUNT] Form posted - processing submission');
    error_log('[CREATENEWACCOUNT] Initial values: ' . json_encode(array_keys($values)));
    
    // Set flag to process handlers
    $process_handlers = true;
    
    // Run all section handlers for this account type
    foreach ($config['sections'] as $section_item) {
        processSection($section_item, $default_sections, true, $values, $errors, $signup_process, $app, $session);
    }
    
    error_log('[CREATENEWACCOUNT] After handlers - Errors: ' . json_encode($errors));
    error_log('[CREATENEWACCOUNT] After handlers - Birthday: ' . ($values['birthday'] ?? 'NOT SET'));
    
    // Unset process_handlers flag after validation
    $process_handlers = false;
    
    // Debug logging for form values
    if ($mode === 'dev' && !empty($errors)) {
        error_log('[CREATENEWACCOUNT] Form errors: ' . json_encode($errors));
        error_log('[CREATENEWACCOUNT] Birthday value: ' . ($values['birthday'] ?? 'NOT SET'));
        error_log('[CREATENEWACCOUNT] Birth month: ' . ($values['birth_month'] ?? 'NOT SET'));
        error_log('[CREATENEWACCOUNT] Birth day: ' . ($values['birth_day'] ?? 'NOT SET'));
        error_log('[CREATENEWACCOUNT] Birth year: ' . ($values['birth_year'] ?? 'NOT SET'));
        
        // Log gift certificate fields
        error_log('[CREATENEWACCOUNT] Recipient first name: ' . ($values['recipient_firstnamefield'] ?? 'NOT SET'));
        error_log('[CREATENEWACCOUNT] Recipient last name: ' . ($values['recipient_lastnamefield'] ?? 'NOT SET'));
        error_log('[CREATENEWACCOUNT] Delivery method: ' . ($values['delivery_method'] ?? 'NOT SET'));
    }
    
    // If no errors, process the account creation
    if (empty($errors)) {
        // Debug logging
        error_log('[CREATENEWACCOUNT] No errors, processing account creation');
        error_log('[CREATENEWACCOUNT] Account cost: ' . $account_cost);
        error_log('[CREATENEWACCOUNT] Account type: ' . $account_type);
        error_log('[CREATENEWACCOUNT] Birthday value: ' . ($values['birthday'] ?? 'NOT SET'));
        
        // Ensure birthday is set from the dropdown values if not already set
        if (!isset($values['birthday']) && !empty($values['birth_month']) && !empty($values['birth_day']) && !empty($values['birth_year'])) {
            $month = str_pad($values['birth_month'], 2, '0', STR_PAD_LEFT);
            $day = str_pad($values['birth_day'], 2, '0', STR_PAD_LEFT);
            $year = $values['birth_year'];
            $values['birthday'] = $year . '-' . $month . '-' . $day;
            error_log('[CREATENEWACCOUNT] Birthday constructed from dropdowns: ' . $values['birthday']);
        }
        
        // Check for existing user (from credentials handler)
        if (!empty($values['existing_user_info'])) {
            // We have an existing pending/validated user
            $tempinfo = $values['existing_user_info'];
            
            // Ensure userregistrationdata has required fields for validate-account.php
            if (!isset($tempinfo['email']) && isset($tempinfo['phone'])) {
                $tempinfo['phone_number'] = $tempinfo['phone'];
            }
            
            $session->set('userregistrationdata', $tempinfo);
            error_log('[CREATENEWACCOUNT] Existing user found, setting session data');
            
            if ($account_cost > 0) {
                // Paid plan - go to checkout with existing user
                $encoded_user_id = $qik->encodeId($tempinfo['user_id']);
                header('Location: /checkout.php?u=' . $encoded_user_id);
                exit();
            } else {
                // Free plan - check status
                if ($tempinfo['status'] == 'validated' || $plandata['account_verification'] == 'notrequired') {
                    header('Location: /myaccount/welcome.php');
                } else {
                    header('Location: /verify');
                }
                exit();
            }
        }
        
        // Prepare user data for creation
        if (!isset($values['birthday']) || empty($values['birthday'])) {
            $errors['general'] = 'Please complete your date of birth selection.';
        } else {
            $birthday_date = DateTime::createFromFormat('Y-m-d', $values['birthday']);
            if (!$birthday_date) {
                $errors['general'] = 'Invalid birthday format. Please check your date of birth.';
            } else {
            $birthday_formatted = $birthday_date->format('Y-m-d');
            $hashed_password = password_hash($values['password'], PASSWORD_DEFAULT);
            $first_name = ucfirst($values['firstname']);
            $last_name = ucfirst($values['lastname']);
            $email = !empty($values['email']) ? trim(strtolower($values['email'])) : '';
            $phone = !empty($values['phone_clean']) ? $values['phone_clean'] : preg_replace('/\D/', '', $values['phone'] ?? '');
            
            // Generate username if not provided
            $username = $values['username'] ?? '';
            if (empty($username) && $account_type == 'user') {
                $username = $createaccount->generate_username($first_name, $last_name, $values['birthday']);
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
            
            // Generate default avatar based on user initials
            if (!empty($values['firstnamefield']) && !empty($values['lastnamefield'])) {
                $initials = strtoupper(substr($values['firstnamefield'], 0, 1) . substr($values['lastnamefield'], 0, 1));
                $hash = md5($initials . $values['emailfield']); // Create unique hash
                $avatar_url = "https://files.birthday.gold/public/defaultavatars/{$hash}.svg";
                $input['avatar_file'] = $avatar_url;
            }
            
            // Add business fields if business account
            if ($account_type === 'business') {
                $input['business_name'] = $values['businessnamefield'] ?? '';
                $input['business_type'] = $values['businesstypefield'] ?? '';
                $input['business_phone'] = $values['businessphonefield'] ?? '';
                $input['business_address'] = $values['businessaddressfield'] ?? '';
                $input['business_website'] = $values['businesswebsitefield'] ?? '';
            }
            
            // Add promo code if provided
            if (!empty($values['promo_code'])) {
                $input['promocode'] = $values['promo_code'];
            }
            
            // Add referral code if provided
            if (!empty($values['referral_code'])) {
                $input['referral_code'] = $values['referral_code'];
            }
            
            // Create the user
            try {
                $user_id = $createaccount->create_user($input);
                
                if ($user_id) {
                    // Handle children for parental accounts
                    if (($account_type === 'parental' || $account_type === 'family') && !empty($values['children'])) {
                        foreach ($values['children'] as $child) {
                            if (!empty($child['firstname']) && !empty($child['lastname']) && !empty($child['birthday'])) {
                                // Add child account logic here
                                // This would typically involve creating linked child accounts
                            }
                        }
                    }
                    
                    // Store registration data in session for validation page
                    $userregistrationdata = array_merge($input, ['user_id' => $user_id]);
                    $session->set('userregistrationdata', $userregistrationdata);
                    $session->set('accountcode', $user_id);
                    
                    error_log('[CREATENEWACCOUNT] User created with ID: ' . $user_id);
                    error_log('[CREATENEWACCOUNT] Setting userregistrationdata in session with email: ' . ($userregistrationdata['email'] ?? 'NO EMAIL'));
                    error_log('[CREATENEWACCOUNT] Account verification requirement: ' . ($plandata['account_verification'] ?? 'NOT SET'));
                    
                    // Also ensure signup_process_data is set for checkout
                    $signup_process['promo_code'] = $values['promo_code'] ?? '';
                    $signup_process['referrer_code'] = $values['referral_code'] ?? '';
                    $session->set('signup_process_data', $signup_process);
                    
                    // Redirect based on account cost and validation requirements
                    if ($account_cost > 0) {
                        // Paid plan - go to checkout
                        $encoded_user_id = $qik->encodeId($user_id);
                        error_log('[CREATENEWACCOUNT] Redirecting to checkout for paid plan');
                        header('Location: /checkout.php?u=' . $encoded_user_id);
                    } else {
                        // Free plan - check validation requirements
                        error_log('[CREATENEWACCOUNT] Free plan detected, checking validation requirements');
                        if ($plandata['account_verification'] == 'notrequired') {
                            // No validation required, go to welcome
                            error_log('[CREATENEWACCOUNT] No validation required, redirecting to welcome');
                            header('Location: /myaccount/welcome.php');
                        } else {
                            // Validation required - use new verify system
                            error_log('[CREATENEWACCOUNT] Validation required, redirecting to /verify');
                            header('Location: /verify');
                        }
                    }
                    exit();
                } else {
                    $errors['general'] = 'Failed to create account. Please try again.';
                }
            } catch (Exception $e) {
                $errors['general'] = 'An error occurred while creating your account. Please try again.';
                error_log('User creation error: ' . $e->getMessage());
            }
            } // End of birthday validation else block
        } // End of birthday exists check
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
/* Responsive headline sizing - matching signup.php */
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

/* Additional styles for modular sections */
.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

/* Raise floating labels higher when active */
.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label,
.form-floating > .form-select ~ label {
    transform: scale(0.85) translateY(-0.7rem) translateX(0.15rem);
}

/* Ensure date dropdowns stay on one line on all screens */
.date-row {
    display: flex;
    gap: 0.5rem;
    flex-wrap: nowrap;
}

/* Style the placeholder options in dropdowns */
select option[value=""] {
    color: var(--bs-primary) !important;
    font-weight: 500;
}

/* Alternative: Make them lighter gray */
/* select option[value=""] {
    color: #adb5bd !important;
} */

/* Gift Certificate Section Styling */
.gift-certificate-section {
    background-color: #fffcf4; /* Light gold/cream background - 10% lighter */
    border-color: #f8ecdb;
}

.gift-certificate-section .card-header {
    background-color: #fceed7 !important; /* Light gold for header - 10% lighter */
    border-bottom-color: #f8ecdb;
}

.gift-certificate-section .card-body {
    background-color: transparent;
}

/* Remove browser autofill blue background */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 30px white inset !important;
    -webkit-text-fill-color: inherit !important;
    transition: background-color 5000s ease-in-out 0s;
}

/* For floating label inputs specifically */
.form-floating input:-webkit-autofill,
.form-floating input:-webkit-autofill:hover,
.form-floating input:-webkit-autofill:focus,
.form-floating input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 30px white inset !important;
    -webkit-text-fill-color: inherit !important;
}

/* Style all input group buttons - lighter blue using opacity */
.toggle-password,
#applyPromo,
#verifyReferral,
.input-group .btn-primary-subtle {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;  /* 10% opacity of primary */
    color: var(--bs-primary) !important;  /* Full primary color for icon/text */
    border-color: rgba(var(--bs-primary-rgb), 0.3) !important;  /* 30% opacity for border */
}

.toggle-password:hover,
#applyPromo:hover,
#verifyReferral:hover,
.input-group .btn-primary-subtle:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.2) !important;  /* 20% opacity on hover */
    border-color: rgba(var(--bs-primary-rgb), 0.4) !important;
    color: var(--bs-primary) !important;
}

/* Fix border issues on desktop only */
@media (min-width: 768px) {
    .input-group .toggle-password,
    .input-group #applyPromo,
    .input-group #verifyReferral,
    .input-group .btn-primary-subtle {
        border-left: 1px solid rgba(var(--bs-primary-rgb), 0.3) !important;
    }
    
    /* Keep border when input is focused */
    .input-group:focus-within .toggle-password,
    .input-group:focus-within #applyPromo,
    .input-group:focus-within #verifyReferral,
    .input-group:focus-within .btn-primary-subtle {
        border-color: rgba(var(--bs-primary-rgb), 0.4) !important;
    }
}

/* Mobile/Small screens - underline style */
@media (max-width: 767px) {
    /* Form inputs with underline only */
    .form-floating .form-control,
    .form-control,
    .form-select {
        border: none;
        border-bottom: 2px solid #dee2e6;
        border-radius: 0;
        background-color: transparent;
        padding-left: 0;
        padding-right: 0;
    }
    
    .form-floating .form-control:focus,
    .form-control:focus,
    .form-select:focus {
        border-bottom-color: #0d6efd;
        box-shadow: none;
        background-color: transparent;
    }
    
    /* Invalid state */
    .form-floating .form-control.is-invalid,
    .form-control.is-invalid,
    .form-select.is-invalid {
        border-bottom-color: #dc3545;
    }
    
    /* Remove autofill background on mobile with transparent background */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px transparent inset !important;
        -webkit-text-fill-color: inherit !important;
    }
    
    /* Remove all borders from input group buttons on mobile */
    .toggle-password,
    #applyPromo,
    #verifyReferral,
    .input-group .btn-primary-subtle {
        border: none !important;
        border-radius: 0 !important;
        padding: 0.375rem 0.75rem !important;
    }
    
    /* Match the underline style when input is focused */
    .input-group:focus-within .toggle-password,
    .input-group:focus-within #applyPromo,
    .input-group:focus-within #verifyReferral,
    .input-group:focus-within .btn-primary-subtle {
        border: none !important;
    }
    
    /* Input groups need special handling */
    .input-group .form-control {
        border-bottom: 2px solid #dee2e6;
        border-right: none !important;
    }
    
    .input-group .input-group-text {
        border: none;
        border-bottom: 2px solid #dee2e6;
        border-radius: 0;
        background-color: transparent;
        padding-left: 0;
    }
    
    /* Floating labels adjustment for mobile */
    .form-floating > label {
        padding-left: 0;
    }
    
    /* Add some transition for smooth effect */
    .form-control,
    .form-select {
        transition: border-color 0.15s ease-in-out;
    }
    
    /* Date row specific adjustments */
    .date-row .col-md-5 {
        flex: 2; /* Month gets more space */
    }
    .date-row .col-md-3 {
        flex: 1; /* Day gets less space */
    }
    .date-row .col-md-4 {
        flex: 1.5; /* Year gets medium space */
    }
    
    /* Make select text smaller on mobile to fit better */
    .date-row select {
        font-size: 0.875rem;
        padding: 0.375rem 0.5rem;
    }
}

/* Desktop/Large screens - keep full borders */
@media (min-width: 768px) {
    .form-floating .form-control,
    .form-control,
    .form-select {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #fff;
    }
    
    .form-floating .form-control:focus,
    .form-control:focus,
    .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
}

.form-section:last-of-type {
    border-bottom: none;
}

/* Make form captions/help text lighter grey */
small.text-muted,
.form-text,
.text-muted {
    color: #adb5bd !important; /* Lighter grey - Bootstraps gray-500 */
}

/* Fix input group with floating labels */
.input-group > .form-floating {
    flex: 1 1 auto;
    width: 1%;
    min-width: 0;
}

.input-group > .form-floating > .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-group > .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.section-title {
    margin-bottom: 1.5rem;
    color: #333;
    font-weight: 600;
}

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
    padding: 0.5rem 0;
}

.promo-referral-header:hover {
    opacity: 0.8;
}

/* Mobile-specific promo header styles */
.promo-referral-header-mobile {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    user-select: none;
    padding: 0.25rem 0;
}

.promo-referral-header-mobile:hover .bi {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

.promo-referral-header-mobile .bi {
    cursor: pointer;
    transition: transform 0.2s ease;
}

/* Mobile: Make promo section minimal */
@media (max-width: 767px) {
    .promo-referral-section {
        background: transparent;
        border-radius: 0;
        padding: 0.5rem 0;
        margin-top: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .promo-referral-header-mobile {
        padding: 0.75rem 0;
    }
    
    .promo-referral-content {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 0.5rem;
    }
}

.promo-referral-content {
    margin-top: 1rem;
    padding-top: 0.5rem;
}

.promo-referral-content.collapsing {
    transition: height 0.35s ease;
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

/* Contact method tab styles */
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

/* Password input group */
.password-input-group {
    position: relative;
    display: flex;
}

.password-input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.password-input-group .toggle-password {
    border: 1px solid #ced4da;
    border-left: none;
    border-top-right-radius: 0.25rem;
    border-bottom-right-radius: 0.25rem;
    background: #fff;
    padding: 0.375rem 0.75rem;
    color: #6c757d;
    transition: all 0.15s ease-in-out;
}

.password-input-group .toggle-password:hover {
    background-color: #e9ecef;
    color: #495057;
}

.password-input-group .toggle-password:focus {
    box-shadow: none;
    outline: none;
}

/* Match border color when input is focused */
.password-input-group .form-control:focus ~ .toggle-password {
    border-color: #0d6efd;
}

.password-strength {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}

.password-strength .strength-bar {
    height: 100%;
    width: 0;
    transition: all 0.3s ease;
    background: #dc3545;
}

.password-strength .strength-bar.weak { width: 33%; background: #dc3545; }
.password-strength .strength-bar.medium { width: 66%; background: #ffc107; }
.password-strength .strength-bar.strong { width: 100%; background: #28a745; }

/* Child entry cards */
.child-entry .card {
    border: 1px solid #dee2e6;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.child-entry .card:hover {
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Style for underage years in dropdown */
#birth_year option.text-danger {
    color: #dc3545 !important;
    font-weight: 500;
}

/* Style for when a red year is selected - only the selected value */
#birth_year.has-danger-selection {
    color: #dc3545 !important;
}

/* Ensure non-danger options stay normal color */
#birth_year option:not(.text-danger) {
    color: #495057 !important;
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

/* Hide step wizard on mobile screens */
@media (max-width: 767px) {
    .progress-container {
        display: none !important;
    }
}

/* Loading state for submit button */
.btn-loading {
    position: relative;
    color: transparent !important;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin-left: -10px;
    margin-top: -10px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spinner 0.8s linear infinite;
}

@keyframes spinner {
    to { transform: rotate(360deg); }
}

/* Accessibility helper for screen readers */
.visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

/* Dynamic date toggle link styling */
#date-validation-toggle {
    color: #6c757d;
    transition: color 0.2s ease;
}

#date-validation-toggle:hover {
    color: #0d6efd;
}

#date-validation-toggle i {
    margin-right: 0.25rem;
}

/* Date adjustment message */
#date-adjustment-message {
    transition: opacity 0.15s ease-in-out;
}

#date-adjustment-message.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

</style>
';

// Make content flush with header
$header_flush = true;

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
?>
<div class="container">
    <!-- Header -->
    <div class="header text-center mt-5 mb-4">
        <h1>Now, Let's Create Your Account</h1>
        <p class="mb-0">Step 2: Enter your account details</p>
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
    <div class="card p-3 p-lg-5">
        <?php
        // General error message display
        if (!empty($errors['general'])) {
            echo '<div class="alert alert-danger" role="alert">';
            echo htmlspecialchars($errors['general']);
            echo '</div>';
        }
        
        // Multiple error message display
        $display_errors = array_filter($errors, function($key) {
            return !in_array($key, ['general']) && !str_contains($key, '.');
        }, ARRAY_FILTER_USE_KEY);
        
        if (!empty($display_errors)) {
            echo '<div class="alert alert-danger" role="alert">';
            echo '<ul class="mb-0">';
            foreach ($display_errors as $error) {
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
                Your Plan: <strong><?php echo htmlspecialchars($plandata['account_name']); ?></strong>
                <span class="text-muted ms-2"><?php echo ucfirst($account_type); ?> Account</span>
            </div>
            <div class="text-end">
                <span id="displayPrice" class="h5 mb-0">
                    $<?php echo number_format($account_cost / 100, 2); ?>
                </span>
                <?php 
                $billing_cycle = $plandata['billing_cycle'] ?? '';
                switch($billing_cycle) {
                    case 'monthly': echo '<span class="text-muted">/month</span>'; break;
                    case 'yearly': echo '<span class="text-muted">/year</span>'; break;
                    case 'one_time': echo '<span class="text-muted"> (one-time)</span>'; break;
                    case 'lifetime': echo '<span class="text-muted"> (lifetime)</span>'; break;
                    default: echo '<span class="text-muted">/' . $billing_cycle . '</span>';
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="detailsForm" novalidate>
            <?php echo $display->inputcsrf_token(); ?>
            
            <!-- Include form sections based on account type -->
            <?php 
            foreach ($config['sections'] as $section_item) {
                processSection($section_item, $default_sections, false, $values, $errors, $signup_process, $app, $session);
            }
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
    originalPrice: <?php echo $account_cost; ?>,
    accountType: '<?php echo $account_type; ?>'
};

// Initialize contact method switching (from credentials section)
document.addEventListener('DOMContentLoaded', function() {
    // Handle tab-style contact method switching
    const tabOptions = document.querySelectorAll('.tab-option');
    const container = document.getElementById('contactInputContainer');
    const phoneRadio = document.getElementById('usePhone');
    const emailRadio = document.getElementById('useEmail');
    
    // Use values from credentialsData if available
    let phoneValue = window.credentialsData?.phoneValue || '';
    let emailValue = window.credentialsData?.emailValue || '';
    
    // Create phone field HTML
    function createPhoneField() {
        return `
            <div class="contact-field" id="phoneField">
                <div class="input-group">
                    <span class="input-group-text">+1</span>
                    <div class="form-floating flex-grow-1">
                        <input type="text" 
                               class="form-control" 
                               id="phone" 
                               name="phone" 
                               placeholder="Phone Number"
                               value="${phoneValue}"
                               inputmode="tel"
                               required>
                        <label for="phone">Phone Number</label>
                    </div>
                </div>
                <small class="text-muted mt-1 d-block">We'll send you a verification code via SMS</small>
            </div>
        `;
    }
    
    // Create email field HTML
    function createEmailField() {
        return `
            <div class="contact-field" id="emailField">
                <div class="form-floating">
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           placeholder="Email Address"
                           value="${emailValue}"
                           inputmode="email"
                           required>
                    <label for="email">Email Address</label>
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
        if (container) {
            container.innerHTML = '';
            
            // Create new field
            if (method === 'phone') {
                container.innerHTML = createPhoneField();
                if (phoneRadio) phoneRadio.checked = true;
                
                // Attach event handlers
                const phoneInput = document.getElementById('phone');
                if (phoneInput) {
                    phoneInput.addEventListener('input', function(e) {
                        phoneValue = e.target.value;
                        // Format phone number
                        formatPhoneNumber(e);
                    });
                }
            } else {
                container.innerHTML = createEmailField();
                if (emailRadio) emailRadio.checked = true;
                
                // Attach event handlers
                const emailInput = document.getElementById('email');
                if (emailInput) {
                    emailInput.addEventListener('input', function(e) {
                        emailValue = e.target.value;
                    });
                }
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
    const initialMethod = window.credentialsData?.contactMethod || 'phone';
    switchContactMethod(phoneRadio && phoneRadio.checked ? 'phone' : initialMethod);
    
    // Handle password toggle
    setTimeout(function() {
        const togglePasswordBtn = document.querySelector('.toggle-password');
        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const passwordInput = document.getElementById('password');
                const icon = this.querySelector('i');
                
                if (passwordInput && passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else if (passwordInput) {
                    passwordInput.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        }
    }, 100); // Small delay to ensure DOM is ready
    
    // Handle promo/referral toggle with Bootstrap collapse
    const togglePromo = document.getElementById('togglePromoReferral');
    const promoSection = document.getElementById('promoReferralSection');
    const promoChevron = document.getElementById('promoReferralChevron');
    
    if (togglePromo && promoSection) {
        // Initialize Bootstrap collapse
        const bsCollapse = new bootstrap.Collapse(promoSection, {
            toggle: false
        });
        
        togglePromo.addEventListener('click', function(e) {
            e.preventDefault();
            bsCollapse.toggle();
        });
        
        // Listen for Bootstrap collapse events to update chevron
        promoSection.addEventListener('shown.bs.collapse', function() {
            promoChevron.classList.remove('bi-chevron-down');
            promoChevron.classList.add('bi-chevron-up');
        });
        
        promoSection.addEventListener('hidden.bs.collapse', function() {
            promoChevron.classList.remove('bi-chevron-up');
            promoChevron.classList.add('bi-chevron-down');
        });
    }
    
    // Birthday dropdown sync
    const birthMonth = document.getElementById('birth_month');
    const birthDay = document.getElementById('birth_day');
    const birthYear = document.getElementById('birth_year');
    const birthdayHidden = document.getElementById('birthday');
    
    function updateBirthdayField() {
        if (birthMonth && birthDay && birthYear && 
            birthMonth.value && birthDay.value && birthYear.value) {
            birthdayHidden.value = birthYear.value + '-' + birthMonth.value + '-' + birthDay.value;
        }
    }
    
    if (birthMonth) birthMonth.addEventListener('change', updateBirthdayField);
    if (birthDay) birthDay.addEventListener('change', updateBirthdayField);
    if (birthYear) birthYear.addEventListener('change', updateBirthdayField);
    
    // Handle birth year dropdown styling for underage years
    const birthYearSelect = document.getElementById('birth_year');
    if (birthYearSelect) {
        // Function to check if selected year has text-danger class
        function updateYearStyling() {
            const selectedOption = birthYearSelect.options[birthYearSelect.selectedIndex];
            if (selectedOption && selectedOption.classList.contains('text-danger')) {
                birthYearSelect.classList.add('has-danger-selection');
            } else {
                birthYearSelect.classList.remove('has-danger-selection');
            }
        }
        
        // Check on page load
        updateYearStyling();
        
        // Check on change
        birthYearSelect.addEventListener('change', updateYearStyling);
    }
});

// Phone number formatting
function formatPhoneNumber(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 0) {
        if (value.length <= 3) {
            value = value;
        } else if (value.length <= 6) {
            value = value.slice(0, 3) + '-' + value.slice(3);
        } else {
            value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
        }
    }
    e.target.value = value;
}

// Password strength checker
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.querySelector('.strength-bar');
    
    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.classList.remove('weak', 'medium', 'strong');
            
            if (strength <= 1) {
                strengthBar.classList.add('weak');
            } else if (strength === 2) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });
    }
});
</script>

<!-- Dynamic Date Validation -->
<script>
// Feature toggle configuration
// Can be controlled via: URL params (?dynamic_dates=on/off), this variable, or user preference cookie
window.DYNAMIC_DATE_VALIDATION = <?php 
    // Check for URL parameter override
    $dynamic_dates = $_GET['dynamic_dates'] ?? null;
    if ($dynamic_dates === 'off' || $dynamic_dates === 'false') {
        echo 'false';
    } elseif ($dynamic_dates === 'on' || $dynamic_dates === 'true') {
        echo 'true';
    } else {
        // Default to enabled, but can be configured in site settings
        echo isset($features['dynamic_date_dropdowns']) ? ($features['dynamic_date_dropdowns'] ? 'true' : 'false') : 'true';
    }
?>;
</script>
<script src="/public/js/dynamic-date-validation.js?v=<?php echo time(); ?>"></script>

<!-- Load embedded promo validation to avoid 403 errors -->
<script src="/promo_validate_embedded.php"></script>
<script src="/claudecode/createaccount_flow.js"></script>

<?php
$display_footertype='mobilenonemin';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>