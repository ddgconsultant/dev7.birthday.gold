<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$errormessage = '';

#-------------------------------------------------------------------------------
# PROCESS REDEMPTION ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $code = trim($_POST['code'] ?? '');
    $password = $sitesettings['app']['APP_GIFTCODEPASS'];
    
    if (!empty($code) && !empty($password)) {
        $response = $account->login($code, $password, 'giftcode');
        
        if ($response) {
            $session->set('generateGiftCertificateCode', $code);
            $session->set('generateGiftCertificateCode_user_id', $response);
            
            $errormessage = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Gift certificate successfully redeemed!</div>';
            $transferpagedata['message'] = $errormessage;
            $transferpagedata['url'] = '/register-giftcertificate';
            $transferpagedata = $system->endpostpage($transferpagedata);
            exit;
        } else {
            $errormessage = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Invalid code. Please check and try again.</div>';
        }
    } else {
        $errormessage = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Please enter a gift certificate code.</div>';
    }
}

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Redeem Gift Certificate - Birthday Gold";
$page_description = "Redeem your Birthday Gold gift certificate and start enjoying birthday rewards";

$additionalstyles = '<link href="/public/css/redeem_styles_v2.css" rel="stylesheet">';

$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="main-content">
    <!-- Desktop wrapper for side-by-side layout -->
    <div class="redeem-wrapper">
        <!-- Welcome content - Desktop only -->
        <div class="welcome-content d-none d-lg-block">
            <h2>Someone special thinks you deserve <span>more birthdays freebies</span></h2>
            <p>Your gift certificate unlocks a Gold Plan of birthday rewards and exclusive perks from hundreds of brands.</p>
            
            <div class="feature-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-gift"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Birthday Rewards</h3>
                        <p>Free treats and discounts all month long</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-magic"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Auto-Enrollment</h3>
                        <p>We handle all the birthday club signups</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Never Miss Out</h3>
                        <p>Timely reminders for every reward</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Exclusive Deals</h3>
                        <p>Member-only offers throughout the year</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Redeem Card -->
        <div class="redeem-container mb-md-5">
            <div class="redeem-card">
                <!-- Header Section -->
                <div class="redeem-header">
                    <div class="gift-badge">
                        <i class="bi bi-gift-fill"></i>
                        <span>Gift Certificate</span>
                    </div>
                    <h1>Redeem Your Gift</h1>
                    <p>Enter your code to get started</p>
                </div>
                
                <!-- Form Section -->
                <div class="redeem-body">
                    <?php if (!empty($errormessage)): ?>
                        <div class="alert-container">
                            <?php echo $errormessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="/redeem" id="redeemForm">
                        <?php echo $display->inputcsrf_token(); ?>
                        
                        <div class="form-group">
                            <label class="form-label" for="code">Certificate Code</label>
                            <div class="code-input-wrapper">
                                <input 
                                    type="text" 
                                    name="code" 
                                    id="code" 
                                    class="code-input" 
                                    placeholder="XXXX-XXXX-XXXX" 
                                    autocomplete="off"
                                    spellcheck="false"
                                    maxlength="20"
                                    required
                                >
                            </div>
                            <div class="help-text">
                                Enter code exactly as shown on your certificate
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-redeem" id="redeemBtn">
                            <span>Redeem Now</span>
                        </button>
                    </form>
                    
                    <!-- Divider -->
                    <div class="divider">
                        <span>or</span>
                    </div>
                    
                    <!-- Alternative Actions -->
                    <div class="alt-actions">
                        Already have an account? <a href="/login">Sign in</a>
                        <br>
                        Don't have a gift certificate? <a href="/signup">Create account</a>
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
    const codeInput = document.getElementById("code");
    const redeemForm = document.getElementById("redeemForm");
    const redeemBtn = document.getElementById("redeemBtn");
    
    // Auto-format the gift code input
    if (codeInput) {
        codeInput.addEventListener("input", function(e) {
            let value = e.target.value.replace(/[^A-Za-z0-9]/g, "").toUpperCase();
            let formatted = "";
            
            // Format as XXXX-XXXX-XXXX
            for (let i = 0; i < value.length && i < 12; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += "-";
                }
                formatted += value[i];
            }
            
            e.target.value = formatted;
            
            // Add visual feedback
            if (formatted.length === 14) { // Complete code
                codeInput.classList.add("is-valid");
                codeInput.classList.remove("is-invalid");
            } else {
                codeInput.classList.remove("is-valid");
            }
        });
        
        // Focus on the input field
        codeInput.focus();
    }
    
    // Form submission handling
    if (redeemForm) {
        redeemForm.addEventListener("submit", function(e) {
            const cleanCode = codeInput.value.replace(/[^A-Za-z0-9]/g, "");
            
            if (cleanCode.length < 8) {
                e.preventDefault();
                codeInput.classList.add("is-invalid");
                codeInput.focus();
                return false;
            }
            
            // Add loading state
            redeemBtn.classList.add("loading");
            redeemBtn.disabled = true;
        });
    }
    
    // Remove invalid class on input
    if (codeInput) {
        codeInput.addEventListener("input", function() {
            this.classList.remove("is-invalid");
        });
    }
});
</script>
';

#$display_footertype='mobilenonemin';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>