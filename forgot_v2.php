<?php
$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$errormessage = '';

#-------------------------------------------------------------------------------
# PROCESS POST ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $email = trim($_POST['email'] ?? '');
    $sendcount = 1;
    $response = $account->getuserdata($email, 'email');
    
    if (!empty($response['user_id'])) {
        $fullname = $response['first_name'];
        $message['toemail'] = $email;
        $message['fullname'] = $fullname;
        
        $validatedata['rawdata'] = $email;
        $validatedata['user_id'] = $response['user_id'];
        $validatedata['sendcount'] = $sendcount;
        $validatedata['type'] = 'forgotpassword';
        $validationcodes = $app->getvalidationcodes($validatedata);

        $link = $website['fullurl'] . '/resetpassword?t=' . $validationcodes['long'];
        $message['validatelink'] = $link;
        $message['validationcode'] = $local_validationcode = $validationcodes['mini'];

        $mail->sendPasswordResetEmail($message);

        #-------------------------------------------------------------------------------
        # DISPLAY SUCCESS PAGE
        #-------------------------------------------------------------------------------   
        $page_title = "Password Reset Email Sent - Birthday Gold";
        
        $additionalstyles = '
        <style>
        .success-container {
            width: 100%;
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .success-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #e8f5e8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2.5rem;
            color: var(--bs-primary);
        }
        
        .success-card h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 1rem;
        }
        
        .email-display {
            background: #f0f9ff;
            border: 1px solid #cfe2ff;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: #0c63e4;
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        
        .success-text {
            font-size: 1.125rem;
            color: #6c757d;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .tip-box {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            color: #664d03;
            font-size: 0.875rem;
            text-align: left;
        }
        
        .tip-box strong {
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--bs-primary);
            text-decoration: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--bs-primary);
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .back-link:hover {
            background: var(--bs-primary);
            color: white;
            text-decoration: none;
        }
        </style>
        ';
        
        include($dir['core_components'] . '/bg_pagestart.inc');
        include($dir['core_components'] . '/bg_header.inc');
        
        echo '
        <div class="success-container">
            <div class="success-card">
                <div class="success-icon">
                    <i class="bi bi-envelope-check"></i>
                </div>
                <h1>Email Sent Successfully</h1>
                <p class="success-text">We have sent password reset instructions to:</p>
                <div class="email-display">' . htmlspecialchars($email) . '</div>
                <p class="success-text">Please check your email and follow the instructions to reset your password.</p>
                
                <div class="tip-box">
                    <strong>Didn\'t receive the email?</strong>
                    Check your spam folder or try again in a few minutes. The reset link will expire in 1 hour for security.
                </div>
                
                <a href="/login_v2" class="back-link">
                    <i class="bi bi-arrow-left"></i>
                    Back to Login
                </a>
            </div>
        </div>
        ';
        
        include($dir['core_components'] . '/bg_footer.inc');
        $app->outputpage();
        exit();
    } else {
        $errormessage = '<div class="alert alert-danger">No account found with that email address.</div>';
    }
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles = '
<style>
/* Floating Label Styles */
.floating-label-group {
    position: relative;
    margin-bottom: 1.5rem;
}

.floating-input {
    background: transparent !important;
    border: none;
    border-bottom: 2px solid #e9ecef;
    border-radius: 0;
    padding: 1.5rem 0.75rem 0.5rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    transition: all 0.3s ease;
    width: 100%;
    min-height: 44px; /* Touch target */
    caret-color: #212529;
}

.floating-input:focus {
    outline: none;
    border: 1px solid #e9ecef;
    border-bottom-color: var(--bs-primary);
    border-radius: 8px 8px 0 0;
    box-shadow: none;
    background: transparent !important;
}

.floating-input.is-invalid {
    border-bottom-color: #dc3545;
}

/* Remove autofill styling - most aggressive approach */
.floating-input:-webkit-autofill,
.floating-input:-webkit-autofill:hover,
.floating-input:-webkit-autofill:focus,
.floating-input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
    -webkit-text-fill-color: #212529 !important;
    background-color: transparent !important;
    background-image: none !important;
    transition: background-color 600000s ease-in-out 0s !important;
    -webkit-transition: background-color 600000s ease-in-out 0s !important;
    transition-delay: 600000s !important;
}

/* Additional autofill removal */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
    -webkit-text-fill-color: #212529 !important;
    background: transparent !important;
    transition: background-color 600000s ease-in-out 0s !important;
    -webkit-transition: background-color 600000s ease-in-out 0s !important;
    transition-delay: 600000s !important;
}

/* Force transparency for all autofill states */
input[data-autocompleted],
input:-internal-autofill-selected {
    background-color: transparent !important;
    background-image: none !important;
}

/* Desktop: Traditional form field look */
@media (min-width: 992px) {
    .floating-input {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 2rem 1rem 0.375rem 1rem;
        background: white !important;
        transition: all 0.2s ease;
    }
    
    .floating-input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }
    
    /* Desktop: Placeholder at normal position, form value much lower */
    .floating-input::placeholder {
        transform: translateY(2.5rem);
        opacity: 0.6;
        transition: all 0.3s ease;
        line-height: 1;
    }
    
    .floating-input:focus::placeholder {
        opacity: 0;
    }
}

.floating-label {
    position: absolute;
    left: 0.75rem;
    top: 1.5rem;
    color: #6c757d;
    font-size: 1rem;
    transition: all 0.3s ease;
    pointer-events: none;
    transform-origin: left top;
}

/* Desktop: Adjust label positioning for traditional form fields */
@media (min-width: 992px) {
    .floating-label {
        left: 1rem;
        top: 1.125rem;
    }
    
    .floating-input:focus + .floating-label,
    .floating-input:not(:placeholder-shown) + .floating-label {
        transform: translateY(-1.1rem) scale(0.85);
    }
}

/* Float label when input is focused or has content */
.floating-input:focus + .floating-label,
.floating-input:not(:placeholder-shown) + .floating-label {
    transform: translateY(-1.25rem) scale(0.85);
    color: var(--bs-primary);
}

.floating-input:focus.is-invalid + .floating-label,
.floating-input:not(:placeholder-shown).is-invalid + .floating-label {
    color: #dc3545;
}

/* Error States */
.invalid-feedback {
    display: none;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

.floating-input.is-invalid ~ .invalid-feedback,
.invalid-feedback.d-block {
    display: block;
}

/* Focus and Hover States */
.floating-input:hover:not(:focus) {
    border-bottom-color: #adb5bd;
}

/* Accessibility */
.floating-input:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
}

/* Animation for better UX */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.invalid-feedback {
    animation: fadeInUp 0.3s ease;
}

/* Mobile Optimizations */
@media (max-width: 576px) {
    .floating-input {
        font-size: 16px; /* Prevent zoom on iOS */
    }
}

/* Main Content Container */
.main-content {
    max-width: 480px;
    margin: 2rem auto;
    padding: 0 1rem;
}

/* Card Container */
.forgot-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

/* Header Section */
.forgot-header {
    text-align: center;
    padding: 2rem 1.5rem 1rem;
}

.forgot-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.forgot-header p {
    font-size: 1rem;
    color: #6c757d;
    margin: 0;
    line-height: 1.5;
}

/* Forgot Badge */
.forgot-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #fff3cd;
    color: #664d03;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.forgot-badge i {
    font-size: 1rem;
}

/* Form Section */
.forgot-body {
    padding: 0 1.5rem 2rem;
}

/* Submit Button */
.btn-submit {
    width: 100%;
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    background: var(--bs-primary);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    min-height: 44px;
    margin-bottom: 1.5rem;
}

.btn-submit:hover:not(:disabled) {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

.btn-submit:active {
    transform: translateY(0);
}

.btn-submit:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.65;
}

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--bs-primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
    font-size: 0.875rem;
}

.back-link:hover {
    color: #0b5ed7;
    text-decoration: underline;
}

/* Info Box */
.info-box {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    color: #495057;
    font-size: 0.875rem;
    line-height: 1.5;
}

.info-box i {
    color: var(--bs-primary);
    margin-right: 0.5rem;
}

/* Loading State */
.btn-submit.loading {
    pointer-events: none;
}

.btn-submit.loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    margin: auto;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    border: 2px solid transparent;
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

.btn-submit.loading span {
    opacity: 0;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Tablet & Desktop Styles */
@media (min-width: 768px) {
    .main-content {
        max-width: 480px;
        margin: 3rem auto;
    }
    
    .forgot-header {
        padding: 3rem 2rem 1.5rem;
    }
    
    .forgot-header h1 {
        font-size: 2rem;
    }
    
    .forgot-body {
        padding: 0 2rem 3rem;
    }
}
</style>
';

?>

<div class="main-content">
    <div class="forgot-card">
        <!-- Header Section -->
        <div class="forgot-header">
            <div class="forgot-badge">
                <i class="bi bi-key"></i>
                <span>Floating Labels Demo</span>
            </div>
            <h1>Reset Password</h1>
            <p>Enter your email address and we'll send you instructions to reset your password</p>
        </div>
        
        <!-- Form Section -->
        <div class="forgot-body">
            <?php if (!empty($errormessage)): ?>
                <div class="alert-container">
                    <?php echo $errormessage; ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <i class="bi bi-info-circle"></i>
                Enter the email address associated with your account and we'll send you a secure link to reset your password.
            </div>
            
            <form method="POST" action="/forgot_v2" id="forgotForm">
                <?php echo $display->inputcsrf_token(); ?>
                
                <!-- Email with Floating Label -->
                <div class="floating-label-group">
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="floating-input" 
                        placeholder=" "
                        autocomplete="email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autofocus
                    >
                    <label for="email" class="floating-label">Email Address</label>
                </div>
                
                <button type="submit" class="btn-submit" id="submitBtn">
                    <span>Send Reset Instructions</span>
                </button>
                
                <div style="text-align: center;">
                    <a href="/login_v2" class="back-link">
                        <i class="bi bi-arrow-left"></i>
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$footerattribute['postfooter'] = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const forgotForm = document.getElementById("forgotForm");
    const submitBtn = document.getElementById("submitBtn");
    const emailInput = document.getElementById("email");
    
    // Form submission handling
    if (forgotForm) {
        forgotForm.addEventListener("submit", function(e) {
            // Add loading state and disable button to prevent double submit
            submitBtn.classList.add("loading");
            submitBtn.disabled = true;
        });
    }
    
    // Auto-focus email field
    if (emailInput && !emailInput.value) {
        emailInput.focus();
    }
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();