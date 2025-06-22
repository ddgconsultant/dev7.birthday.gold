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
if (empty($userregistrationdata['email']) && !$test_mode) {
    $session->set('force_error_message', 'No registration data found. Please sign up again.');
    header('location: /signup');
    exit;
}

// Handle AJAX validation request
if (isset($_POST['ajax']) && $_POST['ajax'] == 'validate' && isset($_POST['code'])) {
    header('Content-Type: application/json');
    
    $submitted_code = strtoupper(trim($_POST['code']));
    
    // Check if test mode is passed via POST
    $ajax_test_mode = isset($_POST['test']) && $_POST['test'] == '1';
    $ajax_test_type = isset($_POST['type']) ? $_POST['type'] : 'alphanumeric';
    
    // Always use real validation (no fake test validation)
    $checkdata = array();
    $checkdata['mini'] = $submitted_code;
    $checkdata['type'] = 'email';
    
    $response = $app->checkvalidationcodes($checkdata);
    
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
        
        $gotourl = '/checkout'; // Default
        
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
            case ($process_user_data['status'] == 'validated' && $process_user_data['account_plan'] == 'free'):
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
        
        echo json_encode(['success' => true, 'redirect' => $gotourl]);
        exit;
    } else {
        // Validation failed
        echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
        exit;
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
    
    $email = $userregistrationdata['email'] ?? '';
    $fullname = ($userregistrationdata['first_name'] ?? '') . ' ' . ($userregistrationdata['last_name'] ?? '');
    
    // Send verification email
    $message = array();
    $message['toemail'] = $email;
    $message['fullname'] = $fullname;
    
    // Always use real validation code generation
    $validatedata = array();
    $validatedata['rawdata'] = $email;
    $validatedata['sendcount'] = $sendcount;
    
    // In test mode, use a numeric user_id for database compatibility
    if ($test_mode || $ajax_test_mode || (isset($userregistrationdata['account_type']) && $userregistrationdata['account_type'] == 'test')) {
        // Convert test user_id to a numeric value for database
        $validatedata['user_id'] = 999999; // Fixed test user_id
    } else {
        $validatedata['user_id'] = $userregistrationdata['user_id'];
    }
    
    // Check if numeric code is requested (from test mode type parameter)
    if ($test_code_type === 'numeric') {
        $validatedata['numeric_only'] = true;
    }
    
    $validationcodes = $app->getvalidationcodes($validatedata);
    
    $link = $website['formalurl'] . '/validate-account?t=' . $validationcodes['long'];
    $message['validatelink'] = $link;
    $message['validationcode'] = $validationcodes['mini'];
    
    // Also provide direct verify link with code pre-filled
    $direct_verify_link = $website['formalurl'] . '/verify?code=' . urlencode($validationcodes['mini']);
    $message['directverifylink'] = $direct_verify_link;
    
    $result = $mail->sendVerificationEmail($message);
    
    if ($result) {
        $userregistrationdata['validationemailsent'] = date('r');
        $userregistrationdata['validationemailsent_count'] = $sendcount;
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
            content: '';
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
            
            <h1 class="verification-title">Check your email</h1>
            <p class="verification-subtitle">
                We sent a <?php echo ($test_mode && $test_code_type === 'numeric') ? 'numeric' : ''; ?> code to <strong><?php echo htmlspecialchars($userregistrationdata['email']); ?></strong>
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
                <p class="resend-text">Didn't receive the code?</p>
                <a href="/verify?action=resend<?php echo $test_mode ? '&test=1' : ''; ?>" class="resend-link" id="resendLink">
                    Send a new code
                </a>
                <div class="countdown" id="countdown" style="display: none;">
                    You can resend in <span id="countdownTimer">60</span> seconds
                </div>
            </div>

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

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.redirect);
                    } else {
                        this.showError(data.message || 'Invalid verification code. Please try again.');
                    }
                } catch (error) {
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
                    // Disable link and show countdown
                    resendLink.classList.add('disabled');
                    countdown.style.display = 'block';
                    
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
                            countdown.style.display = 'none';
                        }
                    }, 1000);
                    
                    // Reset form
                    verificationInput.reset();
                    
                    // Show success message
                    const subtitle = document.querySelector('.verification-subtitle');
                    const originalText = subtitle.innerHTML;
                    subtitle.innerHTML = '<i class="bi bi-check-circle text-success me-2"></i>New code sent!';
                    
                    setTimeout(() => {
                        subtitle.innerHTML = originalText;
                    }, 3000);
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