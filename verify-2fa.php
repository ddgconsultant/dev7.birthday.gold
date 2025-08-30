<?php
$addClasses[] = 'twofactorauth';
$addClasses[] = 'sms';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is in a login session that requires 2FA
$pending_2fa_data = $session->get('pending_2fa', null);

if (!$pending_2fa_data || empty($pending_2fa_data['user_id'])) {
    header('Location: /login');
    exit;
}

// Check if 2FA session has expired (15 minutes timeout)
$session_timeout = 15 * 60;
if (isset($pending_2fa_data['timestamp']) && (time() - $pending_2fa_data['timestamp']) > $session_timeout) {
    $session->unset('pending_2fa');
    $transferpagedata['message'] = '<div class="alert alert-warning">Your verification session timed out. Please log in again.</div>';
    $transferpagedata['url'] = '/login';
    $system->endpostpage($transferpagedata);
    exit;
}

$user_id = $pending_2fa_data['user_id'];
$user_email = $pending_2fa_data['email'] ?? '';
$user_phone = $pending_2fa_data['phone'] ?? '';
$auth_method = $pending_2fa_data['method'] ?? '';
$code_sent = $pending_2fa_data['code_sent'] ?? false;

// Handle AJAX verification request
if (isset($_POST['ajax']) && $_POST['ajax'] == 'validate' && isset($_POST['code'])) {
    header('Content-Type: application/json');
    
    try {
        $submitted_code = trim($_POST['code']);
        
        error_log("2FA verify attempt: UserID=$user_id, Code='$submitted_code', Method='$auth_method'");
        
        if ($auth_method === 'Highly Secure') {
            $response = $twofactorauth->verifyCode($pending_2fa_data['secret'], $submitted_code, 2);
            $success = $response;
            error_log("2FA TOTP result: " . ($success ? 'SUCCESS' : 'FAILED'));
        } else {
            $response = $twofactorauth->verifyTempCode($user_id, $submitted_code);
            $success = $response['success'];
            error_log("2FA Email/SMS result: " . ($success ? 'SUCCESS' : 'FAILED') . " - " . ($response['error'] ?? 'no error'));
        }
        
        if ($success) {
            $account->login($user_id, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
            
            // If this is selective 2FA, store the verification timestamp for this device
            if (!empty($pending_2fa_data['selective_2fa']) && !empty($pending_2fa_data['device_id'])) {
                $device_id = $pending_2fa_data['device_id'];
                
                // Store or update selective 2FA verification record
                $sql = "INSERT INTO bg_user_attributes (user_id, type, name, description, status, create_dt, modify_dt) 
                        VALUES (:user_id, 'selective_2fa_verified', :device_id, 'Selective 2FA verified for trusted device', 'active', NOW(), NOW())
                        ON DUPLICATE KEY UPDATE 
                        modify_dt = NOW(), status = 'active'";
                
                $stmt = $database->prepare($sql);
                $stmt->execute([
                    'user_id' => $user_id,
                    'device_id' => $device_id
                ]);
                
                error_log("Selective 2FA: Stored verification for user $user_id, device $device_id");
            }
            
            $session->unset('pending_2fa');
            $redirect_url = $pending_2fa_data['redirect_url'] ?? '/myaccount';
            echo json_encode(['success' => true, 'redirect' => $redirect_url]);
            exit();
        } else {
            $error_message = $auth_method === 'Highly Secure' ? 
                'Invalid authenticator code. Please try again.' : 
                ($response['error'] ?? 'Invalid verification code. Please try again.');
            
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
    } catch (Exception $e) {
        error_log('2FA Verification error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred during verification.']);
        exit();
    }
}

// Handle resend request for "Secure" method
if (isset($_POST['ajax']) && $_POST['ajax'] == 'resend' && $auth_method === 'Secure') {
    header('Content-Type: application/json');
    
    try {
        $result = $twofactorauth->sendVerificationCode($user_id, $user_email, $user_phone);
        
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'New verification code sent successfully.']);
        } else {
            $error_msg = $result['error'] ?? 'Unknown error';
            error_log('2FA Resend failed: ' . $error_msg . ' - Email: ' . $user_email . ' - User ID: ' . $user_id);
            echo json_encode(['success' => false, 'message' => $error_msg]);
        }
        exit();
    } catch (Exception $e) {
        error_log('2FA Resend exception: ' . $e->getMessage() . ' - User ID: ' . $user_id);
        echo json_encode(['success' => false, 'message' => 'Failed to send new code: ' . $e->getMessage()]);
        exit();
    }
}

// Auto-send code for "Secure" method on first load
error_log("2FA Auto-send check: auth_method='$auth_method', code_sent='$code_sent'");
if ($auth_method === 'Secure' && !$code_sent) {
    try {
        error_log("2FA Auto-send: UserID=$user_id, Email='$user_email', Phone='$user_phone'");
        $result = $twofactorauth->sendVerificationCode($user_id, $user_email, $user_phone);
        error_log("2FA Auto-send result: " . json_encode($result));
        
        if ($result['success']) {
            $pending_2fa_data['code_sent'] = true;
            $session->set('pending_2fa', $pending_2fa_data);
            $code_sent = true;
            error_log("2FA Auto-send: Code sent successfully");
        } else {
            error_log("2FA Auto-send failed: " . ($result['error'] ?? 'Unknown error'));
        }
    } catch (Exception $e) {
        error_log('2FA Auto-send exception: ' . $e->getMessage());
    }
} else {
    error_log("2FA Auto-send skipped: auth_method='$auth_method', code_sent=" . ($code_sent ? 'true' : 'false'));
}

// Page styles
$additionalstyles = '
<style>
:root {
    --primary: #0d6efd;
    --primary-dark: #0a58ca;
    --success: #198754;
    --danger: #dc3545;
    --warning: #ffc107;
    --gray-50: #f8f9fa;
    --gray-100: #e9ecef;
    --gray-200: #dee2e6;
    --gray-600: #6c757d;
    --gray-700: #495057;
    --gray-900: #212529;
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
    background: linear-gradient(135deg, #0d6efd, #6610f2);
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
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    transform: scale(1.05);
}

.code-input.filled {
    border-color: var(--primary);
    background: #e7f1ff;
}

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
    cursor: pointer;
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
    content: "";
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
    background: linear-gradient(135deg, var(--success), #20c997);
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
</style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '<div class="page-wrapper">
    <div class="verification-container">
        <div class="verification-card" id="verificationCard">
            <div class="logo"><i class="bi bi-shield-lock me-2"></i>Security Verification</div>
            
            <h1 class="verification-title">';

if ($auth_method === 'Highly Secure') {
    echo 'Enter Authenticator Code';
} else {
    echo 'Check your ' . (!empty($user_phone) ? 'phone' : 'email');
}

echo '</h1>
            
            <p class="verification-subtitle">';

if ($auth_method === 'Highly Secure') {
    echo 'Open your authenticator app and enter the 6-digit code to complete your login.';
} else {
    echo 'A 6-digit verification code was sent to ';
    if (!empty($user_phone)) {
        echo 'your phone ending in <strong>' . substr($user_phone, -4) . '</strong>';
    } else {
        echo '<strong>' . htmlspecialchars($user_email) . '</strong>';
    }
}

echo '</p>';

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success mb-3" style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 8px;">
                <i class="bi bi-check-circle me-2"></i>
                ' . htmlspecialchars($_SESSION['success_message']) . '
            </div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger mb-3" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 8px;">
                <i class="bi bi-exclamation-circle me-2"></i>
                ' . htmlspecialchars($_SESSION['error_message']) . '
            </div>';
    unset($_SESSION['error_message']);
}

echo '<div class="error-message" id="errorMessage">
                <i class="bi bi-exclamation-circle me-2"></i>
                <span id="errorText">Invalid code. Please try again.</span>
            </div>

            <div class="code-inputs-container" id="codeContainer">
                <input type="text" class="code-input numeric" maxlength="1" data-index="0" autocomplete="off" inputmode="numeric">
                <input type="text" class="code-input numeric" maxlength="1" data-index="1" autocomplete="off" inputmode="numeric">
                <input type="text" class="code-input numeric" maxlength="1" data-index="2" autocomplete="off" inputmode="numeric">
                <input type="text" class="code-input numeric" maxlength="1" data-index="3" autocomplete="off" inputmode="numeric">
                <input type="text" class="code-input numeric" maxlength="1" data-index="4" autocomplete="off" inputmode="numeric">
                <input type="text" class="code-input numeric" maxlength="1" data-index="5" autocomplete="off" inputmode="numeric">
            </div>

            <button class="btn-primary-custom" id="verifyBtn" disabled>
                Verify Code
            </button>';

if ($auth_method === 'Secure') {
    echo '<div class="resend-section">
                <p class="resend-text">Did not receive the code?</p>
                <a href="#" class="resend-link" id="resendLink" onclick="resendCode(event)">
                    Send a new code
                </a>
                <div class="countdown" id="countdown" style="display: none;">
                    You can resend in <span id="countdownTimer">60</span> seconds
                </div>
            </div>';
}

echo '<p class="help-text">
                Having trouble? <a href="/contact">Contact support</a> | 
                <a href="/login" onclick="return confirm(\'This will cancel your login attempt. Continue?\')">Cancel & return to login</a>
            </p>
        </div>

        <!-- Success State (hidden initially) -->
        <div class="verification-card" id="successCard" style="display: none;">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h1 class="verification-title">Verified!</h1>
            <p class="verification-subtitle">
                You have been successfully logged in.
            </p>
            <button class="btn-primary-custom" id="continueBtn">
                Continue
            </button>
        </div>
    </div>
</div>

<script>
class TwoFactorVerification {
    constructor() {
        this.inputs = document.querySelectorAll(\'.code-input\');
        this.verifyBtn = document.getElementById(\'verifyBtn\');
        this.errorMessage = document.getElementById(\'errorMessage\');
        this.codeContainer = document.getElementById(\'codeContainer\');
        this.csrfToken = \'' . $csrf_token . '\';
        this.authMethod = \'' . $auth_method . '\';
        this.autoSubmitTimeout = null;
        this.hasAutoSubmitted = false;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.inputs[0].focus();
    }

    bindEvents() {
        this.inputs.forEach((input, index) => {
            input.addEventListener(\'input\', (e) => this.handleInput(e, index));
            input.addEventListener(\'keydown\', (e) => this.handleKeydown(e, index));
            input.addEventListener(\'paste\', (e) => this.handlePaste(e));
            input.addEventListener(\'focus\', (e) => e.target.select());
        });

        this.verifyBtn.addEventListener(\'click\', () => this.verifyCode(false)); // false = manual submit

        document.addEventListener(\'keydown\', (e) => {
            if (e.key === \'Enter\' && this.isCodeComplete()) {
                this.verifyCode(false); // false = manual submit
            }
        });
    }

    handleInput(e, index) {
        const input = e.target;
        let value = input.value.replace(/[^0-9]/g, \'\');
        
        input.value = value;
        this.clearError();

        if (value) {
            input.classList.add(\'filled\');
        } else {
            input.classList.remove(\'filled\');
        }

        // Auto-advance to next input
        if (value && index < this.inputs.length - 1) {
            this.inputs[index + 1].focus();
        }

        this.updateVerifyButton();
    }

    handleKeydown(e, index) {
        const input = e.target;

        if (e.key === \'Backspace\' && !input.value && index > 0) {
            e.preventDefault();
            this.inputs[index - 1].focus();
            this.inputs[index - 1].value = \'\';
            this.inputs[index - 1].classList.remove(\'filled\');
            this.updateVerifyButton();
        }

        if (e.key === \'ArrowLeft\' && index > 0) {
            e.preventDefault();
            this.inputs[index - 1].focus();
        } else if (e.key === \'ArrowRight\' && index < this.inputs.length - 1) {
            e.preventDefault();
            this.inputs[index + 1].focus();
        }
    }

    handlePaste(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData(\'text\');
        const cleanedData = pastedData.replace(/[^0-9]/g, \'\');
        const chars = cleanedData.split(\'\').slice(0, this.inputs.length);
        
        chars.forEach((char, index) => {
            this.inputs[index].value = char;
            this.inputs[index].classList.add(\'filled\');
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
        return Array.from(this.inputs).map(input => input.value).join(\'\');
    }

    updateVerifyButton() {
        const isComplete = this.isCodeComplete();
        this.verifyBtn.disabled = !isComplete;
        
        // Clear any existing auto-submit timeout
        if (this.autoSubmitTimeout) {
            clearTimeout(this.autoSubmitTimeout);
            this.autoSubmitTimeout = null;
        }
        
        // Auto-submit when all 6 digits are entered (but only once per form completion)
        if (isComplete && !this.hasAutoSubmitted) {
            // Add small delay to allow user to see the completed form
            this.autoSubmitTimeout = setTimeout(() => {
                this.hasAutoSubmitted = true;
                this.verifyCode(true); // true = auto-submit
            }, 300);
        } else if (!isComplete) {
            // Reset auto-submit flag if code is incomplete again
            this.hasAutoSubmitted = false;
        }
    }

    async verifyCode(isAutoSubmit = false) {
        const code = this.getCode();
        
        this.verifyBtn.classList.add(\'loading\');
        this.verifyBtn.disabled = true;
        this.codeContainer.classList.add(\'verifying\');

        try {
            const params = new URLSearchParams({
                ajax: \'validate\',
                code: code,
                _token: this.csrfToken
            });
            
            const response = await fetch(\'/verify-2fa.php\', {
                method: \'POST\',
                headers: {
                    \'Content-Type\': \'application/x-www-form-urlencoded\',
                },
                body: params.toString()
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.redirect);
            } else {
                // Only show errors for manual submit, not auto-submit
                if (!isAutoSubmit) {
                    this.showError(data.message || \'Invalid verification code. Please try again.\');
                }
            }
        } catch (error) {
            console.error(\'2FA Verification error:\', error);
            // Only show errors for manual submit, not auto-submit
            if (!isAutoSubmit) {
                this.showError(\'An error occurred. Please try again.\');
            }
        }

        this.verifyBtn.classList.remove(\'loading\');
        this.codeContainer.classList.remove(\'verifying\');
        this.updateVerifyButton();
    }

    showError(message) {
        const errorText = document.getElementById(\'errorText\');
        errorText.textContent = message;
        this.errorMessage.classList.add(\'show\');
        
        this.inputs.forEach(input => {
            input.classList.add(\'error\');
        });

        this.inputs[0].focus();
        this.inputs[0].select();
    }

    clearError() {
        this.errorMessage.classList.remove(\'show\');
        this.inputs.forEach(input => {
            input.classList.remove(\'error\');
        });
    }

    showSuccess(redirectUrl) {
        document.getElementById(\'verificationCard\').style.display = \'none\';
        document.getElementById(\'successCard\').style.display = \'block\';
        
        const continueBtn = document.getElementById(\'continueBtn\');
        continueBtn.onclick = () => {
            window.location.href = redirectUrl;
        };
        
        // Auto-redirect after 2 seconds
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 2000);
    }

    reset() {
        // Clear any pending auto-submit
        if (this.autoSubmitTimeout) {
            clearTimeout(this.autoSubmitTimeout);
            this.autoSubmitTimeout = null;
        }
        this.hasAutoSubmitted = false;
        
        this.inputs.forEach(input => {
            input.value = \'\';
            input.classList.remove(\'filled\', \'error\');
        });
        this.clearError();
        this.updateVerifyButton();
        this.inputs[0].focus();
    }
}

// Initialize
let twoFactorVerification;
document.addEventListener(\'DOMContentLoaded\', () => {
    twoFactorVerification = new TwoFactorVerification();
});

// Resend code functionality
let resendCountdown = null;

async function resendCode(e) {
    e.preventDefault();
    
    const resendLink = document.getElementById(\'resendLink\');
    const countdown = document.getElementById(\'countdown\');
    const countdownTimer = document.getElementById(\'countdownTimer\');
    
    if (resendLink.classList.contains(\'disabled\')) {
        return;
    }
    
    try {
        const params = new URLSearchParams({
            ajax: \'resend\',
            _token: twoFactorVerification.csrfToken
        });
        
        const response = await fetch(\'/verify-2fa.php\', {
            method: \'POST\',
            headers: {
                \'Content-Type\': \'application/x-www-form-urlencoded\',
            },
            body: params.toString()
        });

        const data = await response.json();
        
        if (data.success) {
            // Reset form
            twoFactorVerification.reset();
            
            // Start 60-second countdown
            resendLink.classList.add(\'disabled\');
            resendLink.textContent = \'Code sent\';
            countdown.style.display = \'block\';
            
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
                    resendLink.classList.remove(\'disabled\');
                    resendLink.textContent = \'Send a new code\';
                    countdown.style.display = \'none\';
                }
            }, 1000);
        } else {
            // Show error in the UI instead of alert
            const errorMessage = document.getElementById(\'errorMessage\');
            const errorText = document.getElementById(\'errorText\');
            errorText.textContent = data.message || \'Failed to send new code. Please try again.\';
            errorMessage.classList.add(\'show\');
            
            // Hide error after 5 seconds
            setTimeout(() => {
                errorMessage.classList.remove(\'show\');
            }, 5000);
        }
    } catch (error) {
        // Show error in the UI instead of alert
        const errorMessage = document.getElementById(\'errorMessage\');
        const errorText = document.getElementById(\'errorText\');
        errorText.textContent = \'An error occurred while sending new code. Please try again.\';
        errorMessage.classList.add(\'show\');
        
        // Hide error after 5 seconds
        setTimeout(() => {
            errorMessage.classList.remove(\'show\');
        }, 5000);
    }
}
</script>';

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>