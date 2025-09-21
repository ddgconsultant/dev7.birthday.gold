<?php
$addClasses[] = 'Mail';
$addClasses[] = 'SMS';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$errormessage = '';

// Get user's login method preference
$preferred_method = $account->getLoginMethodPreference();

#-------------------------------------------------------------------------------
# PROCESS POST ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    // Check throttle - 15 second limit between requests
    $throttle_key = 'forgot_password_throttle';
    $current_time = time();
    
    if (isset($_SESSION[$throttle_key]) && ($current_time - $_SESSION[$throttle_key]) < 15) {
        $seconds_remaining = 15 - ($current_time - $_SESSION[$throttle_key]);
        $errormessage = '<div class="alert alert-warning"><i class="bi bi-clock"></i> Please wait ' . $seconds_remaining . ' seconds before requesting another password reset.</div>';
    } else {
        // Update throttle timestamp
        $_SESSION[$throttle_key] = $current_time;
        
        $reset_type = $_POST['reset_type'] ?? 'email';
        $sendcount = 1;
        
        if ($reset_type === 'phone') {
            // Phone number reset
            $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
            
            if (strlen($phone) >= 10) {
                $response = $account->getUserByPhone($phone);
                
                if (!empty($response['user_id'])) {
                    $fullname = $response['first_name'];
                    
                    $validatedata['rawdata'] = $phone;
                    $validatedata['user_id'] = $response['user_id'];
                    $validatedata['sendcount'] = $sendcount;
                    $validatedata['type'] = 'forgotpassword';
                    $validationcodes = $app->getvalidationcodes($validatedata);
                    
                    // Create reset URL for SMS (same format as email)
                    $reset_link = $website['fullurl'] . '/resetpassword?t=' . $validationcodes['long'];
                    
                    // Get shortened URL for SMS
                    $short_data = $app->getshortcode($reset_link);
                    
                    // Send SMS via SMS service
                    if ($short_data && isset($short_data['shorturl'])) {
                        $sms_message = "Birthday Gold: Reset your password: " . $short_data['shorturl'];
                    } else {
                        // Fallback to full URL if shortening fails
                        $sms_message = "Birthday Gold: Reset your password: " . $reset_link;
                    }
                    
                    try {
                        $sms->sendSingleMessage($phone, $sms_message);
                    } catch (Exception $e) {
                        // Log error but continue showing success
                        error_log("SMS sending failed: " . $e->getMessage());
                    }
                    
                    // For now, show success page with the link
                    $sent_to = 'Phone ending in ' . substr($phone, -4);
                    $show_success = true;
                    
                    // Set preference for phone
                    $account->setLoginMethodPreference('phone');
                } else {
                    $errormessage = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Unable to find an account with that phone number.</div>';
                }
            } else {
                $errormessage = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Please enter a valid phone number.</div>';
            }
        } else {
            // Email reset (existing code)
            $email = trim($_POST['email'] ?? '');
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
                
                $sent_to = htmlspecialchars($email);
                $show_success = true;
                
                // Set preference for email
                $account->setLoginMethodPreference('email');
            } else {
                $errormessage = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Unable to find an account with that email address.</div>';
            }
        }
        
        if (!empty($show_success)) {
            #-------------------------------------------------------------------------------
            # DISPLAY SUCCESS PAGE
            #-------------------------------------------------------------------------------   
            $page_title = "Password Reset Sent - Birthday Gold";
        
        $additionalstyles = '
        <style>
        /* Keep only essential custom styles that Bootstrap can't handle */
        .success-icon {
            width: 80px;
            height: 80px;
            background: #e8f5e8;
            font-size: 2.5rem;
        }
        
        @media (max-width: 767px) {
            .main-content {
                padding-top: 0 !important;
                margin-top: 0 !important;
            }
        }
        </style>
        ';
        
        include($dir['core_components'] . '/bg_pagestart.inc');
        include($dir['core_components'] . '/bg_header.inc');
        ?>
        
        <div class="main-content">
            <div class="container" style="max-width: 600px;">
                <div class="card shadow-sm rounded-3 p-4 p-md-5 text-center bg-white mt-4 mt-md-5">
                    <div class="success-icon rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 text-primary">
                        <i class="bi bi-<?php echo ($reset_type === 'phone') ? 'phone' : 'envelope-check'; ?>"></i>
                    </div>
                    
                    <h1 class="fs-2 fw-bold text-dark mb-3"><?php echo ($reset_type === 'phone') ? 'Check Your Phone' : 'Check Your Email'; ?></h1>
                    
                    <p class="fs-5 text-muted mb-3">We've sent a password reset link to:</p>
                    
                    <div class="bg-light border border-primary rounded-2 px-4 py-2 fw-semibold text-primary d-inline-block mb-3">
                        <?php echo $sent_to; ?>
                    </div>
                    
                    <p class="fs-5 text-muted mb-3">
                        <?php if ($reset_type === 'phone'): ?>
                            Click the link in the text message to reset your password.
                        <?php else: ?>
                            Click the link in the email to reset your password.
                        <?php endif; ?>
                    </p>
                    
                    <div class="alert alert-warning text-start mb-4">
                        <i class="bi bi-lightbulb text-warning me-2"></i>
                        <?php if ($reset_type === 'phone'): ?>
                            <strong>Tip:</strong> The link will expire in 24 hours. If you don't receive the text, check your phone number and try again.
                        <?php else: ?>
                            <strong>Tip:</strong> Don't forget to check your spam or junk folder if you don't see the email in your inbox.
                        <?php endif; ?>
                    </div>
                    
                    <a href="/login" class="btn btn-primary px-4 py-2 fw-semibold">Back to Login</a>
                </div>
            </div>
        </div>
        
        <?php
            include($dir['core_components'] . '/bg_footer.inc');
            $app->outputpage();
            exit;
        }
    }
}

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Forgot Password - Birthday Gold";
$page_description = "Reset your Birthday Gold account password";

// Minimal CSS - Using Bootstrap 5 utilities
$additionalstyles = '
<style>
/* Essential custom styles only */
.forgot-container {
    max-width: 480px;
}

@media (max-width: 767px) {
    .main-content {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
    
    /* Mobile underline style */
    .form-floating .form-control {
        border: none;
        border-bottom: 2px solid #dee2e6;
        border-radius: 0;
        background-color: transparent;
    }
    
    .form-floating .form-control:focus {
        border-bottom-color: var(--bs-primary);
        box-shadow: none;
    }
}

/* Tab styles - essential for functionality */
.reset-tabs {
    display: flex;
    background: #f1f3f5;
    border-radius: 8px;
    padding: 4px;
    margin-bottom: 1.5rem;
}

.reset-tab {
    flex: 1;
    padding: 0.75rem;
    border: none;
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.reset-tab.active {
    background: white;
    color: var(--bs-primary);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

/* Chrome autofill fix */
input:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 30px white inset !important;
}

/* Loading spinner */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.btn-submit.loading::after {
    content: "";
    position: absolute;
    width: 20px;
    height: 20px;
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
    visibility: hidden;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="main-content">
    <!-- Desktop wrapper for side-by-side layout -->
    <div class="forgot-wrapper">
        <!-- Welcome content - Desktop only -->
        <div class="welcome-content d-none d-lg-block">
            <h2>Locked out? <span>No worries</span></h2>
            <p>We'll help you get back into your account quickly and securely.</p>
            
            <div class="feature-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Secure Reset</h3>
                        <p>Password reset links expire for your security</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Quick Delivery</h3>
                        <p>Reset email sent within seconds</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="feature-text">
                        <h3>24 Hour Valid</h3>
                        <p>Links remain active for one day</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-headset"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Need Help?</h3>
                        <p>Support team ready to assist you</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Forgot Card -->
        <div class="forgot-container">
            <div class="forgot-card">
                <!-- Header Section -->
                <div class="forgot-header">
                    <div class="reset-badge">
                        <i class="bi bi-key-fill"></i>
                        <span>Password Reset</span>
                    </div>
                    <h1>Forgot Your Password?</h1>
                    <p>Enter your email to reset it</p>
                </div>
                
                <!-- Form Section -->
                <div class="forgot-body">
                    <?php if (!empty($errormessage)): ?>
                        <div class="alert-container">
                            <?php echo $errormessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="/forgot" id="forgotForm">
                        <?php echo $display->inputcsrf_token(); ?>
                        
                        <!-- Tab Switch -->
                        <div class="reset-tabs">
                            <button type="button" class="reset-tab <?php echo $preferred_method === 'email' ? 'active' : ''; ?>" data-type="email">
                                <i class="bi bi-envelope"></i>
                                Email
                            </button>
                            <button type="button" class="reset-tab <?php echo $preferred_method === 'phone' ? 'active' : ''; ?>" data-type="phone">
                                <i class="bi bi-phone"></i>
                                Phone
                            </button>
                        </div>
                        
                        <input type="hidden" name="reset_type" id="reset_type" value="<?php echo $preferred_method; ?>">
                        
                        <!-- Email Input -->
                        <div class="form-group" id="email-group" style="<?php echo $preferred_method === 'email' ? '' : 'display: none;'; ?>">
                            <div class="form-floating">
                                <input 
                                    type="email" 
                                    name="email" 
                                    id="email" 
                                    class="form-control" 
                                    placeholder="Email Address" 
                                    autocomplete="email"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                    <?php echo $preferred_method === 'email' ? 'required' : ''; ?>
                                    <?php echo $preferred_method === 'email' ? 'autofocus' : ''; ?>
                                >
                                <label for="email">Email Address</label>
                            </div>
                            <div class="help-text">
                                We'll send a password reset link to this email
                            </div>
                        </div>
                        
                        <!-- Phone Input -->
                        <div class="form-group" id="phone-group" style="<?php echo $preferred_method === 'phone' ? '' : 'display: none;'; ?>">
                            <div class="form-floating">
                                <input 
                                    type="tel" 
                                    name="phone" 
                                    id="phone" 
                                    class="form-control" 
                                    placeholder="Phone Number" 
                                    autocomplete="tel"
                                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                    <?php echo $preferred_method === 'phone' ? 'required' : 'disabled'; ?>
                                >
                                <label for="phone">Phone Number</label>
                            </div>
                            <div class="help-text">
                                We'll send a password reset link via SMS
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <span>Send Reset Link</span>
                        </button>
                    </form>
                    
                    <!-- Divider -->
                    <div class="divider">
                        <span>or</span>
                    </div>
                    
                    <!-- Alternative Actions -->
                    <div class="alt-actions">
                        Remember your password? <a href="/login">Sign in</a>
                        <br>
                        New to Birthday Gold? <a href="/signup">Create account</a>
                    </div>
                </div>
            </div>
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
    const phoneInput = document.getElementById("phone");
    const resetTypeInput = document.getElementById("reset_type");
    const emailGroup = document.getElementById("email-group");
    const phoneGroup = document.getElementById("phone-group");
    const tabs = document.querySelectorAll(".reset-tab");
    
    // Tab switching
    tabs.forEach(tab => {
        tab.addEventListener("click", function() {
            tabs.forEach(t => t.classList.remove("active"));
            this.classList.add("active");
            
            const type = this.getAttribute("data-type");
            resetTypeInput.value = type;
            
            // Update header text and button based on type
            const headerText = document.querySelector(".forgot-header p");
            const submitBtnSpan = submitBtn.querySelector("span");
            
            if (type === "email") {
                emailGroup.style.display = "block";
                phoneGroup.style.display = "none";
                emailInput.required = true;
                emailInput.disabled = false;
                phoneInput.required = false;
                phoneInput.disabled = true;
                emailInput.focus();
                if (headerText) {
                    headerText.textContent = "Enter your email to reset it";
                }
                if (submitBtnSpan && !submitBtn.disabled) {
                    submitBtnSpan.textContent = "Send Reset Link";
                }
            } else {
                emailGroup.style.display = "none";
                phoneGroup.style.display = "block";
                emailInput.required = false;
                emailInput.disabled = true;
                phoneInput.required = true;
                phoneInput.disabled = false;
                phoneInput.focus();
                if (headerText) {
                    headerText.textContent = "Enter your phone number to reset it";
                }
                if (submitBtnSpan && !submitBtn.disabled) {
                    submitBtnSpan.textContent = "Send Reset SMS";
                }
            }
        });
    });
    
    // Phone number formatting
    if (phoneInput) {
        phoneInput.addEventListener("input", function(e) {
            let value = e.target.value.replace(/\D/g, "");
            let formattedValue = "";
            
            if (value.length > 0) {
                if (value.length <= 3) {
                    formattedValue = `(${value}`;
                } else if (value.length <= 6) {
                    formattedValue = `(${value.slice(0, 3)}) ${value.slice(3)}`;
                } else {
                    formattedValue = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
                }
            }
            
            e.target.value = formattedValue;
            this.classList.remove("is-invalid");
        });
    }
    
    // Check if there is a throttle message and start countdown
    const alertContainer = document.querySelector(".alert-warning");
    if (alertContainer && alertContainer.textContent.includes("Please wait")) {
        const match = alertContainer.textContent.match(/(\d+) seconds/);
        if (match) {
            let seconds = parseInt(match[1]);
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span>Wait ${seconds}s</span>`;
            
            const countdown = setInterval(function() {
                seconds--;
                if (seconds > 0) {
                    submitBtn.innerHTML = `<span>Wait ${seconds}s</span>`;
                } else {
                    clearInterval(countdown);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<span>Send Reset Link</span>`;
                    alertContainer.style.display = "none";
                }
            }, 1000);
        }
    }
    
    if (forgotForm) {
        forgotForm.addEventListener("submit", function(e) {
            const resetType = resetTypeInput.value;
            
            if (resetType === "email") {
                // Basic email validation
                const email = emailInput.value.trim();
                if (!email || !email.includes("@")) {
                    e.preventDefault();
                    emailInput.classList.add("is-invalid");
                    emailInput.focus();
                    return false;
                }
            } else {
                // Phone validation
                const phone = phoneInput.value.replace(/\D/g, "");
                if (phone.length < 10) {
                    e.preventDefault();
                    phoneInput.classList.add("is-invalid");
                    phoneInput.focus();
                    return false;
                }
            }
            
            // Add loading state
            submitBtn.classList.add("loading");
            submitBtn.disabled = true;
            // Dont clear textContent to maintain button height
        });
    }
    
    // Remove invalid state on input
    if (emailInput) {
        emailInput.addEventListener("input", function() {
            this.classList.remove("is-invalid");
        });
    }
});
</script>
';

#$display_footertype='mobilenonemin';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
