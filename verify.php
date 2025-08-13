<?php
$addClasses[] = 'Mail';
#$usemailsender='amazonses';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$sendcount = 0;
$local_validationcode = '';
$verbosetracking = true;

// Test mode parameters
$test_mode = isset($_GET['test']) ? true : false;
$test_email = isset($_GET['email']) ? $_GET['email'] : null;
$test_code_type = isset($_GET['type']) ? $_GET['type'] : 'alphanumeric'; // 'numeric' or 'alphanumeric'
$test_autofill = isset($_GET['autofill']) ? $_GET['autofill'] : null;
$test_error = isset($_GET['error']) ? $_GET['error'] : null;
// Removed hardcoded test codes - now using real validation codes from database

// Get user registration data from session
$userregistrationdata = $session->get('userregistrationdata', '');

// In test mode, create fake registration data if none exists
if ($test_mode) {
    if (empty($userregistrationdata) || empty($userregistrationdata['email'])) {
        $userregistrationdata = array(
            'email' => $test_email ?: 'RICHARD@DDG.MX',
            'first_name' => 'Richrard',
            'last_name' => 'Davis-testing',
            'user_id' => 999999, // Fixed numeric user_id for test mode
            'account_type' => 'test',
            'validationemailsent' => date('r'),
            'validationemailsent_count' => 1
        );
        // Store in session for consistency
        $session->set('userregistrationdata', $userregistrationdata);
    }
}

// Only redirect to signup if not in test mode
// Check if we have valid registration data - must have either email or phone_number
if (!$test_mode && (empty($userregistrationdata) || 
    (empty($userregistrationdata['email']) && empty($userregistrationdata['phone_number'])))) {
    $session->set('force_error_message', 'No registration data found. Please sign up again.');
    header('location: /signup');
    exit;
}

// Handle AJAX validation request
if (isset($_POST['ajax']) && $_POST['ajax'] == 'validate' && isset($_POST['code'])) {
    // Suppress error output for AJAX requests
    ini_set('display_errors', 0);
    error_reporting(0);
    
    // Start output buffering to catch any unwanted output
    ob_start();
    
    // Check for any output before setting header
    if (ob_get_length()) {
        ob_clean();
    }
    
    header('Content-Type: application/json');
    
    try {
        $submitted_code = strtoupper(trim($_POST['code']));
        
        // Check if test mode is passed via POST
        $ajax_test_mode = isset($_POST['test']) && $_POST['test'] == '1';
        $ajax_test_type = isset($_POST['type']) ? $_POST['type'] : 'alphanumeric';
        
        // Debug logging
        error_log('[VERIFY] Submitted code: ' . $submitted_code);
        
        // Always use real validation (no fake test validation)
        $checkdata = array();
        $checkdata['mini'] = $submitted_code;
        $checkdata['type'] = 'email';
        
        // Add user_id if available
        if (!empty($userregistrationdata['user_id'])) {
            $checkdata['user_id'] = $userregistrationdata['user_id'];
        }
        
        $response = $app->checkvalidationcodes($checkdata);
        error_log('[VERIFY] Validation response: ' . print_r($response, true));
    } catch (Exception $e) {
        error_log('[VERIFY] Exception: ' . $e->getMessage());
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'An error occurred during validation.']);
        exit();
    }
    
    if ($response !== false && isset($response['validated']) && $response['validated'] && isset($response[0]['user_id']) && !empty($response[0]['user_id'])) {
        // Validation successful
        $userid = $response[0]['user_id'];
        
        // Merge registration data
        $userregistrationdata = array_merge($response, $response[0], $userregistrationdata);
        $userregistrationdata = array_map('unserialize', array_unique(array_map('serialize', $userregistrationdata)));
        $session->set('userregistrationdata', $userregistrationdata);
        
        // Update user status
        $account->updateSettings($userid, ['status' => 'validated']);
        
        // Insert validation timeline
        $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt)
                VALUES (:user_id, 'validated', 'timeline', NOW(), 'active', 200, NOW(), NOW())";
        $stmt = $database->query($sql, [':user_id' => $userid]);
        
        // Determine redirect URL based on account type
        $signup_process = $account->getUserAttribute($userid, 'userregistrationdata');
        $process_user_data = $account->getuserdata($userid, 'user_id', 'validated');
        
        if (!empty($signup_process['description'])) {
            $signup_process = json_decode($signup_process['description'], true);
        }
        
        // Debug logging
        error_log('[VERIFY] User data for redirect: ' . json_encode([
            'user_id' => $userid,
            'status' => $process_user_data['status'] ?? 'unknown',
            'account_plan' => $process_user_data['account_plan'] ?? 'unknown',
            'account_type' => $process_user_data['account_type'] ?? 'unknown'
        ]));
        error_log('[VERIFY] Signup process data: ' . json_encode($signup_process));
        error_log('[VERIFY] Registration data account_cost: ' . ($userregistrationdata['account_cost'] ?? 'not set'));
        error_log('[VERIFY] Registration data account_plan: ' . ($userregistrationdata['account_plan'] ?? 'not set'));
        
        $gotourl = '/checkout'; // Default
        
        // Check if this is a free account
        $is_free_account = false;
        
        // Check account_plan in database
        if ($process_user_data['account_plan'] == 'free') {
            $is_free_account = true;
            error_log('[VERIFY] Free account detected via database account_plan');
        }
        
        // Check account_cost in session data
        if (isset($userregistrationdata['account_cost']) && ($userregistrationdata['account_cost'] == 0 || $userregistrationdata['account_cost'] == '0')) {
            $is_free_account = true;
            error_log('[VERIFY] Free account detected via session account_cost: ' . $userregistrationdata['account_cost']);
        }
        
        // Check account_plan in session data
        if (isset($userregistrationdata['account_plan']) && $userregistrationdata['account_plan'] == 'free') {
            $is_free_account = true;
            error_log('[VERIFY] Free account detected via session account_plan');
        }
        
        switch (true) {
            case isset($signup_process['parental']):
                $gotourl = '/setup-parental';
                break;
            case isset($signup_process['giftcertificate']):
                $gotourl = '/setup-giftcertificate';
                break;
            case isset($signup_process['business']):
                $gotourl = '/setup-business';
                break;
            case ($process_user_data['status'] == 'validated' && $is_free_account):
                error_log('[VERIFY] Processing free account activation');
                $params = ['status' => 'active'];
                $account->updateSettings($process_user_data['user_id'], $params);
                $account->login($process_user_data['user_id'], $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
                $gotourl = '/myaccount/welcome';
                break;
            default:
                $transaction_data = $account->getTransactionData($userid);
                if (!empty($transaction_data) && isset($transaction_data[0]['transaction_id'])) {
                    $transaction_id = $transaction_data[0]['transaction_id'];
                    $checkoutlink = '?t=' . $qik->encodeId($transaction_id);
                    $gotourl = '/checkout' . $checkoutlink;
                } else {
                    $checkoutlink = '?u=' . $qik->encodeId($userid);
                    $gotourl = '/checkout' . $checkoutlink;
                }
                break;
        }
        
        // Clear any output that might have been generated
        ob_clean();
        echo json_encode(['success' => true, 'redirect' => $gotourl]);
        exit();
    } else {
        // Validation failed
        error_log('[VERIFY] Validation failed - response: ' . print_r($response, true));
        // Clear any output that might have been generated
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
        exit();
    }
}

// Handle resend request (both AJAX and GET)
if ((isset($_POST['ajax']) && $_POST['ajax'] == 'resend') || (isset($_GET['action']) && $_GET['action'] == 'resend')) {
    $isAjax = isset($_POST['ajax']);
    
    if ($isAjax) {
        header('Content-Type: application/json');
    }
    
    // Check if test mode is passed via POST or GET
    $ajax_test_mode = (isset($_POST['test']) && $_POST['test'] == '1') || (isset($_GET['test']) && $_GET['test'] == '1');
    
    // Always send the actual email, even in test mode
    $sendcount = isset($userregistrationdata['validationemailsent_count']) ? ($userregistrationdata['validationemailsent_count'] + 1) : 1;
    
    if ($sendcount > 15) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Maximum resend attempts reached.']);
            exit;
        } else {
            $_SESSION['error_message'] = 'Maximum resend attempts reached.';
            $redirect_url = '/verify' . (($test_mode || $ajax_test_mode) ? '?test=1' : '');
            
            // Debug: Check if headers already sent
            if (headers_sent($filename, $linenum)) {
                die("Headers already sent in $filename on line $linenum. Cannot redirect to: $redirect_url");
            }
            
            header('Location: ' . $redirect_url);
            exit;
        }
    }
    
    // Determine contact method - email or phone
    $email = $userregistrationdata['email'] ?? '';
    $phone = $userregistrationdata['phone_number'] ?? $userregistrationdata['phone'] ?? '';
    $fullname = ($userregistrationdata['first_name'] ?? '') . ' ' . ($userregistrationdata['last_name'] ?? '');
    
    // Determine preferred contact method and code type
    // Priority order: 1) Phone (if available), 2) Email
    // Allow user preference via URL parameter (e.g., ?method=email)
    $requested_method = $_GET['method'] ?? '';
    
    $contact_method = '';
    $contact_value = '';
    $use_numeric_code = false; // Default based on method
    
    // Determine which contact method to use based on availability and preference
    if ($requested_method === 'email' && !empty($email)) {
        // User specifically requested email
        $contact_method = 'email';
        $contact_value = $email;
        // For future: we're moving towards numeric codes even for email
        $use_numeric_code = true;
    } elseif ($requested_method === 'phone' && !empty($phone)) {
        // User specifically requested phone
        $contact_method = 'phone';
        $contact_value = $phone;
        $use_numeric_code = true;
    } elseif (!empty($phone)) {
        // Phone takes precedence when both are available
        $contact_method = 'phone';
        $contact_value = $phone;
        $use_numeric_code = true;
    } elseif (!empty($email)) {
        // Fall back to email if no phone
        $contact_method = 'email';
        $contact_value = $email;
        // Moving towards numeric codes as default
        $use_numeric_code = true;
    } else {
        // No contact method available
        $_SESSION['error_message'] = 'No contact information available for verification.';
        header('Location: /signup');
        exit;
    }
    
    // Handle phone verification not yet implemented
    if ($contact_method === 'phone') {
        // TODO: Implement SMS verification
        $_SESSION['error_message'] = 'Phone verification is not yet implemented. Please use email.';
        header('Location: /signup');
        exit;
    }
    
    // Send verification email
    $message = array();
    $message['toemail'] = $email;
    $message['fullname'] = $fullname;
    
    // Always use real validation code generation
    $validatedata = array();
    $validatedata['rawdata'] = $contact_value;
    $validatedata['sendcount'] = $sendcount;
    $validatedata['type'] = 'email'; // Explicitly set type
    
    // In test mode, use a numeric user_id for database compatibility
    if ($test_mode || $ajax_test_mode || (isset($userregistrationdata['account_type']) && $userregistrationdata['account_type'] == 'test')) {
        // Convert test user_id to a numeric value for database
        $validatedata['user_id'] = 999999; // Fixed test user_id
    } else {
        $validatedata['user_id'] = $userregistrationdata['user_id'];
    }
    
    // Set code type based on our determination above
    if ($use_numeric_code) {
        $validatedata['numeric_only'] = true;
    }
    
    // Allow test mode to explicitly set code type
    if ($test_code_type === 'numeric') {
        $validatedata['numeric_only'] = true;
    } elseif ($test_code_type === 'alphanumeric') {
        // Allow override to alphanumeric for scaling/testing
        unset($validatedata['numeric_only']);
    }
    
    $validationcodes = $app->getvalidationcodes($validatedata);
    
    // Use verify endpoint instead of validate-account
    $link = $website['formalurl'] . '/verify?code=' . urlencode($validationcodes['mini']);
    $message['validatelink'] = $link;
    $message['validationcode'] = $validationcodes['mini'];
    
    // Also provide direct verify link with code pre-filled (same as validatelink now)
    $direct_verify_link = $link;
    $message['directverifylink'] = $direct_verify_link;
    
    $result = $mail->sendVerificationEmail($message);
    
    if ($result) {
        $userregistrationdata['validationemailsent'] = date('r');
        $userregistrationdata['validationemailsent_count'] = $sendcount;
        // Store whether we used numeric code
        $userregistrationdata['used_numeric_code'] = isset($validatedata['numeric_only']) && $validatedata['numeric_only'];
        $session->set('userregistrationdata', $userregistrationdata);
        
        if ($isAjax) {
            $message = 'New code sent successfully' . (($test_mode || $ajax_test_mode) ? ' (test mode).' : '.');
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        } else {
            $_SESSION['success_message'] = 'New code sent successfully' . (($test_mode || $ajax_test_mode) ? ' (test mode).' : '.');
            $redirect_url = '/verify' . (($test_mode || $ajax_test_mode) ? '?test=1' : '');
            
            // Debug: Check if headers already sent
            if (headers_sent($filename, $linenum)) {
                die("Headers already sent in $filename on line $linenum. Cannot redirect to: $redirect_url");
            }
            
            header('Location: ' . $redirect_url);
            exit;
        }
    } else {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Failed to send new code. Please try again.']);
            exit;
        } else {
            $_SESSION['error_message'] = 'Failed to send new code. Please try again.';
            $redirect_url = '/verify' . (($test_mode || $ajax_test_mode) ? '?test=1' : '');
            
            // Debug: Check if headers already sent
            if (headers_sent($filename, $linenum)) {
                die("Headers already sent in $filename on line $linenum. Cannot redirect to: $redirect_url");
            }
            
            header('Location: ' . $redirect_url);
            exit;
        }
    }
}

// Get validation code from URL parameter if provided
$url_code = isset($_GET['code']) ? trim($_GET['code']) : '';
if (!empty($url_code)) {
    $local_validationcode = $url_code;
} elseif ($test_mode && $test_autofill && $test_autofill !== 'valid') {
    // Use specific test code for auto-fill if provided in test mode ONLY
    $local_validationcode = $test_autofill;
}
// Note: Removed the automatic display of validation codes for security

// Add page-specific styles
$additionalstyles .= '
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
            --danger: #dc3545;
            --warning: #ffc107;
        }

        body {
            background-color: var(--gray-50);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .page-wrapper {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 0;
        }
        
        .content-wrapper {
            width: 100%;
        }
        
        /* Footer specific styles */
        footer, .footer {
            flex-shrink: 0;
            margin-top: auto;
        }

        .verification-container {
            max-width: 480px;
            margin: 0 auto;
            padding: 0 1rem;
            width: 100%;
        }

        .verification-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 25px rgba(0,0,0,0.1);
            text-align: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
        }

        .verification-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .verification-subtitle {
            color: var(--gray-600);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .code-inputs-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .code-input {
            width: 60px;
            height: 70px;
            text-align: center;
            font-size: 2.5rem !important;
            font-weight: 600;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            background: white;
            color: var(--gray-900);
            transition: all 0.2s ease;
            text-transform: uppercase;
            line-height: 1;
            padding: 0;
        }

        .code-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
            transform: scale(1.05);
        }

        .code-input.filled {
            border-color: var(--primary);
            background: #f0fdf4;
        }

        /* For numeric-only codes */
        .code-input.numeric {
            width: 55px;
        }

        .code-input.error {
            border-color: var(--danger);
            background: #fef2f2;
            animation: shake 0.3s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: none;
        }

        .error-message.show {
            display: block;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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
            position: relative;
            overflow: hidden;
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

        .btn-primary-custom.loading {
            color: transparent;
        }

        .btn-primary-custom.loading::after {
            content: \'\';
            position: absolute;
            width: 20px;
            height: 20px;
            margin: auto;
            border: 2px solid transparent;
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .resend-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
        }

        .resend-text {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .resend-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .resend-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .resend-link.disabled {
            color: var(--gray-600);
            cursor: not-allowed;
            text-decoration: none;
        }

        .countdown {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: scaleIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .help-text {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-top: 1.5rem;
        }

        .help-text a {
            color: var(--primary);
            text-decoration: none;
        }

        .help-text a:hover {
            text-decoration: underline;
        }

        .code-inputs-container.verifying .code-input {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        @media (max-width: 480px) {
            .code-input {
                width: 48px;
                height: 56px;
                font-size: 2rem !important;
            }

            .code-input.numeric {
                width: 44px;
            }

            .code-inputs-container {
                gap: 0.375rem;
            }
        }
    </style>
';

// Start page output
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

    <div class="page-wrapper">
        <div class="verification-container">
        <div class="verification-card" id="verificationCard">
            <div class="logo">Time to Verify</div>
            
            <?php
            // Determine contact method for dynamic title
            $phone = $userregistrationdata['phone_number'] ?? $userregistrationdata['phone'] ?? '';
            $email = $userregistrationdata['email'] ?? '';
            $using_phone = !empty($phone) && !empty($userregistrationdata['sent_to_phone']);
            ?>
            <h1 class="verification-title">Check your <?php echo $using_phone ? 'phone' : 'email'; ?></h1>
            <p class="verification-subtitle">
                <?php 
                // Determine if code is numeric based on what was actually sent
                $is_numeric_code = !empty($userregistrationdata['used_numeric_code']) || ($test_mode && $test_code_type === 'numeric');
                
                if ($is_numeric_code) {
                    echo "A 6-digit code";
                } else {
                    echo "A 6-character code";
                }
                ?> was sent to 
                <?php if ($using_phone): ?>
                    your phone ending in <strong><?php echo htmlspecialchars(substr($phone, -4)); ?></strong>
                <?php else: ?>
                    <strong><?php echo htmlspecialchars($userregistrationdata['email']); ?></strong>
                <?php endif; ?>
            </p>

            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success mb-3" style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 8px; margin-top: 16px;">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger mb-3" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 8px; margin-top: 16px;">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
            <?php endif; ?>

            <div class="error-message" id="errorMessage">
                <i class="bi bi-exclamation-circle me-2"></i>
                <span id="errorText">Invalid code. Please try again.</span>
            </div>

            <div class="code-inputs-container" id="codeContainer">
                <input type="text" class="code-input" maxlength="1" data-index="0" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" data-index="1" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" data-index="2" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" data-index="3" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" data-index="4" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" data-index="5" autocomplete="off">
            </div>

            <button class="btn-primary-custom" id="verifyBtn" disabled>
                Verify Code
            </button>

            <div class="resend-section">
                <p class="resend-text">Didn't receive the code? 
                <a href="/verify?action=resend<?php echo $test_mode ? '&test=1' : ''; ?>" class="resend-link" id="resendLink">
                    Send a new code
                </a>
                <div class="countdown" id="countdown" style="display: none;">
                    You can resend in <span id="countdownTimer">60</span> seconds
                </div>
            </div>
            
            <?php 
            // For automated testing: expose verification code when using @bdtest.xyz domain
            if (!empty($userregistrationdata['email']) && strpos($userregistrationdata['email'], '@bdtest.xyz') !== false) {
                // Get the actual verification code from the database
                $test_validation_data = [
                    'email' => $userregistrationdata['email'],
                    'user_id' => $userregistrationdata['user_id'],
                    'type' => 'email'
                ];
                $test_codes = $app->getvalidationcodes($test_validation_data);
                if (!empty($test_codes['mini'])) {
                    // Use a span element with obscure data attribute (ctc = "code test container")
                    // TEMPORARILY VISIBLE for debugging - shows the code in small text
                    echo '<span data-ctc-value="' . htmlspecialchars($test_codes['mini']) . '" style="position:fixed; bottom:5px; right:5px; font-size:10px; color:#999; background:#fff; padding:2px 5px; border:1px solid #ddd; z-index:9999;">Test Code: ' . htmlspecialchars($test_codes['mini']) . '</span>';
                    // Also add a debug comment for testing
                    echo '<!-- Cypress test code generated for: ' . htmlspecialchars($userregistrationdata['email']) . ' -->';
                } else {
                    // Debug: no code found
                    echo '<!-- Cypress test: No code found for email: ' . htmlspecialchars($userregistrationdata['email'] ?? 'no email') . ' -->';
                }
            } else {
                // Debug: conditions not met
                $debug_email = $userregistrationdata['email'] ?? 'no email in session';
                $is_bdtest = strpos($debug_email, '@bdtest.xyz') !== false ? 'yes' : 'no';
                echo '<!-- Cypress test: Email=' . htmlspecialchars($debug_email) . ', IsBdtest=' . $is_bdtest . ' -->';
            }
            ?>

            <p class="help-text">
                Having trouble? <a href="/contact">Contact support</a>
            </p>
        </div>

        <!-- Success State (hidden initially) -->
        <div class="verification-card" id="successCard" style="display: none;">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h1 class="verification-title">Verified!</h1>
            <p class="verification-subtitle">
                Your account has been successfully verified.
            </p>
            <button class="btn-primary-custom" id="continueBtn">
                Continue
            </button>
        </div>
    </div>
</div>

    <script>
        class VerificationCodeInput {
            constructor() {
                this.inputs = document.querySelectorAll('.code-input');
                this.verifyBtn = document.getElementById('verifyBtn');
                this.errorMessage = document.getElementById('errorMessage');
                this.codeContainer = document.getElementById('codeContainer');
                this.csrfToken = '<?php global $csrf_token; echo $csrf_token; ?>';
                this.testMode = <?php echo $test_mode ? 'true' : 'false'; ?>;
                this.isNumeric = <?php echo ($test_mode && $test_code_type === 'numeric') ? 'true' : 'false'; ?>;
                this.testError = <?php echo ($test_mode && $test_error) ? 'true' : 'false'; ?>;
                
                this.init();
            }

            init() {
                this.bindEvents();
                this.setupInputType();
                this.inputs[0].focus();
                
                <?php if (!empty($local_validationcode)): ?>
                // Auto-fill code from URL parameter
                const urlCode = '<?php echo htmlspecialchars($local_validationcode); ?>';
                urlCode.split('').forEach((char, index) => {
                    if (this.inputs[index]) {
                        this.inputs[index].value = char.toUpperCase();
                        this.inputs[index].classList.add('filled');
                    }
                });
                this.updateVerifyButton();
                
                <?php if (!empty($url_code)): ?>
                // Auto-submit when code is provided via URL
                setTimeout(() => {
                    if (this.verifyBtn && !this.verifyBtn.disabled) {
                        this.verifyCode();
                    }
                }, 500);
                <?php endif; ?>
                <?php endif; ?>
                
                // Show error on load if test error mode
                if (this.testError) {
                    setTimeout(() => {
                        this.showError('This code has expired. Please request a new one.');
                    }, 500);
                }
            }
            
            setupInputType() {
                // Configure inputs based on type
                this.inputs.forEach(input => {
                    if (this.isNumeric) {
                        input.classList.add('numeric');
                        input.setAttribute('inputmode', 'numeric');
                        input.setAttribute('pattern', '[0-9]');
                    } else {
                        input.classList.remove('numeric');
                        input.setAttribute('inputmode', 'text');
                        input.setAttribute('pattern', '[A-Za-z0-9]');
                    }
                });
            }

            bindEvents() {
                this.inputs.forEach((input, index) => {
                    input.addEventListener('input', (e) => this.handleInput(e, index));
                    input.addEventListener('keydown', (e) => this.handleKeydown(e, index));
                    input.addEventListener('paste', (e) => this.handlePaste(e));
                    input.addEventListener('focus', (e) => e.target.select());
                });

                this.verifyBtn.addEventListener('click', () => this.verifyCode());

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && this.isCodeComplete()) {
                        this.verifyCode();
                    }
                });
            }

            handleInput(e, index) {
                const input = e.target;
                let value = input.value;

                this.clearError();

                // Validate input based on type
                if (this.isNumeric) {
                    value = value.replace(/[^0-9]/g, '');
                } else {
                    value = value.replace(/[^0-9A-Za-z]/g, '').toUpperCase();
                }
                
                input.value = value;

                if (value) {
                    input.classList.add('filled');
                } else {
                    input.classList.remove('filled');
                }

                // Auto-advance to next input
                if (value && index < this.inputs.length - 1) {
                    this.inputs[index + 1].focus();
                }

                this.updateVerifyButton();
            }

            handleKeydown(e, index) {
                const input = e.target;

                if (e.key === 'Backspace' && !input.value && index > 0) {
                    e.preventDefault();
                    this.inputs[index - 1].focus();
                    this.inputs[index - 1].value = '';
                    this.inputs[index - 1].classList.remove('filled');
                    this.updateVerifyButton();
                }

                if (e.key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    this.inputs[index - 1].focus();
                } else if (e.key === 'ArrowRight' && index < this.inputs.length - 1) {
                    e.preventDefault();
                    this.inputs[index + 1].focus();
                }
            }

            handlePaste(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text');
                const cleanedData = this.isNumeric 
                    ? pastedData.replace(/[^0-9]/g, '')
                    : pastedData.replace(/[^0-9A-Za-z]/g, '').toUpperCase();

                const chars = cleanedData.split('').slice(0, this.inputs.length);
                chars.forEach((char, index) => {
                    this.inputs[index].value = char;
                    this.inputs[index].classList.add('filled');
                });

                const lastFilledIndex = chars.length - 1;
                if (lastFilledIndex < this.inputs.length - 1) {
                    this.inputs[lastFilledIndex + 1].focus();
                } else {
                    this.inputs[this.inputs.length - 1].focus();
                }

                this.clearError();
                this.updateVerifyButton();
            }

            isCodeComplete() {
                return Array.from(this.inputs).every(input => input.value);
            }

            getCode() {
                return Array.from(this.inputs).map(input => input.value).join('');
            }

            updateVerifyButton() {
                this.verifyBtn.disabled = !this.isCodeComplete();
            }

            async verifyCode() {
                const code = this.getCode();
                
                this.verifyBtn.classList.add('loading');
                this.verifyBtn.disabled = true;
                this.codeContainer.classList.add('verifying');

                try {
                    const params = new URLSearchParams({
                        ajax: 'validate',
                        code: code,
                        _token: this.csrfToken
                    });
                    
                    if (this.testMode) {
                        params.append('test', '1');
                        params.append('type', this.isNumeric ? 'numeric' : 'alphanumeric');
                    }
                    
                    const response = await fetch('/verify.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: params.toString()
                    });

                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // Try to parse JSON
                    let data;
                    const responseText = await response.text();
                    
                    try {
                        data = JSON.parse(responseText);
                    } catch (jsonError) {
                        console.error('JSON parse error:', jsonError);
                        console.error('Response text:', responseText);
                        
                        // Check if it's a PHP error
                        if (responseText.includes('PHP Error') || responseText.includes('Warning:') || responseText.includes('Notice:')) {
                            throw new Error('Server error occurred. Please check server logs.');
                        }
                        throw new Error('Invalid response format');
                    }

                    if (data.success) {
                        this.showSuccess(data.redirect);
                    } else {
                        this.showError(data.message || 'Invalid verification code. Please try again.');
                    }
                } catch (error) {
                    console.error('Verification error:', error);
                    this.showError('An error occurred. Please try again.');
                }

                this.verifyBtn.classList.remove('loading');
                this.codeContainer.classList.remove('verifying');
                this.updateVerifyButton();
            }

            showError(message) {
                const errorText = document.getElementById('errorText');
                errorText.textContent = message;
                this.errorMessage.classList.add('show');
                
                this.inputs.forEach(input => {
                    input.classList.add('error');
                });

                this.inputs[0].focus();
                this.inputs[0].select();
            }

            clearError() {
                this.errorMessage.classList.remove('show');
                this.inputs.forEach(input => {
                    input.classList.remove('error');
                });
            }

            showSuccess(redirectUrl) {
                document.getElementById('verificationCard').style.display = 'none';
                document.getElementById('successCard').style.display = 'block';
                
                const continueBtn = document.getElementById('continueBtn');
                continueBtn.onclick = () => {
                    window.location.href = redirectUrl;
                };
                
                // Auto-redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 2000);
            }

            reset() {
                this.inputs.forEach(input => {
                    input.value = '';
                    input.classList.remove('filled', 'error');
                });
                this.clearError();
                this.updateVerifyButton();
                this.inputs[0].focus();
                
                document.getElementById('verificationCard').style.display = 'block';
                document.getElementById('successCard').style.display = 'none';
            }
        }

        // Initialize
        let verificationInput;
        document.addEventListener('DOMContentLoaded', () => {
            verificationInput = new VerificationCodeInput();
            
            // Handle PHP success message for non-AJAX resend
            const phpSuccessAlert = document.querySelector('.alert-success');
            const resendLink = document.getElementById('resendLink');
            
            if (phpSuccessAlert && phpSuccessAlert.textContent.includes('New code sent successfully')) {
                // Immediately disable the resend link
                if (resendLink) {
                    resendLink.classList.add('disabled');
                    resendLink.style.pointerEvents = 'none';
                    resendLink.style.opacity = '0.5';
                    resendLink.textContent = 'Please wait...';
                }
                
                // After 8 seconds, change to spam folder warning
                setTimeout(() => {
                    phpSuccessAlert.innerHTML = '<i class="bi bi-exclamation-triangle text-warning me-2"></i>Don\'t forget to check your spam folder';
                    phpSuccessAlert.style.backgroundColor = '#fff3cd';
                    phpSuccessAlert.style.borderColor = '#ffeaa7';
                    phpSuccessAlert.style.color = '#856404';
                    
                    // After another 5 seconds, hide the alert and re-enable link
                    setTimeout(() => {
                        phpSuccessAlert.style.transition = 'opacity 0.5s ease-out';
                        phpSuccessAlert.style.opacity = '0';
                        setTimeout(() => {
                            phpSuccessAlert.style.display = 'none';
                            
                            // Re-enable the resend link
                            if (resendLink) {
                                resendLink.classList.remove('disabled');
                                resendLink.style.pointerEvents = '';
                                resendLink.style.opacity = '';
                                resendLink.textContent = 'Send a new code';
                            }
                        }, 500);
                    }, 5000);
                }, 8000);
            }
        });

        // Resend code functionality
        let resendCountdown = null;
        
        async function resendCode(e) {
            e.preventDefault();
            
            const resendLink = document.getElementById('resendLink');
            const countdown = document.getElementById('countdown');
            const countdownTimer = document.getElementById('countdownTimer');
            
            if (resendLink.classList.contains('disabled')) {
                return;
            }
            
            try {
                const params = new URLSearchParams({
                    ajax: 'resend',
                    _token: verificationInput.csrfToken
                });
                
                if (verificationInput.testMode) {
                    params.append('test', '1');
                }
                
                const response = await fetch('/verify.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params.toString()
                });

                const data = await response.json();
                
                if (data.success) {
                    // Disable link immediately
                    resendLink.classList.add('disabled');
                    resendLink.style.pointerEvents = 'none';
                    resendLink.style.opacity = '0.5';
                    resendLink.textContent = 'Please wait...';
                    countdown.style.display = 'none'; // Hide countdown initially
                    
                    // Reset form
                    verificationInput.reset();
                    
                    // Show success message
                    const subtitle = document.querySelector('.verification-subtitle');
                    const originalText = subtitle.innerHTML;
                    subtitle.innerHTML = '<i class="bi bi-check-circle text-success me-2"></i>New code sent successfully';
                    
                    // After 8 seconds, show spam folder warning
                    setTimeout(() => {
                        subtitle.innerHTML = '<i class="bi bi-exclamation-triangle text-warning me-2"></i>Don\'t forget to check your spam folder';
                        
                        // After another 5 seconds, restore original text and start countdown
                        setTimeout(() => {
                            subtitle.innerHTML = originalText;
                            
                            // Now start the 60-second countdown
                            countdown.style.display = 'block';
                            resendLink.textContent = 'Send a new code';
                            resendLink.style.opacity = '';
                            
                            let seconds = 60;
                            countdownTimer.textContent = seconds;
                            
                            if (resendCountdown) {
                                clearInterval(resendCountdown);
                            }
                            
                            resendCountdown = setInterval(() => {
                                seconds--;
                                countdownTimer.textContent = seconds;
                                
                                if (seconds <= 0) {
                                    clearInterval(resendCountdown);
                                    resendLink.classList.remove('disabled');
                                    resendLink.style.pointerEvents = '';
                                    countdown.style.display = 'none';
                                }
                            }, 1000);
                        }, 5000);
                    }, 8000);
                } else {
                    alert(data.message || 'Failed to send new code. Please try again.');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        }
    </script>
    
</div> <!-- Close page-wrapper -->

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>