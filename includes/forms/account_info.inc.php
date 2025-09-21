<?php
/**
 * Account Information Form Module
 * Common account fields used by all account types
 * 
 * Expected variables:
 * - $_POST: form submission data
 * - $errors: array of validation errors
 * - $show_promo_section: boolean to show/hide promo section
 * - $promo_code: promo code value
 * - $referral_code: referral code value
 * - $session: session object for CSRF token
 * - $account_type: current account type
 */
?>

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
            <input type="radio" name="contact_method" id="usePhone" value="phone" <?php echo (($_POST['contact_method'] ?? 'phone') == 'phone') ? 'checked' : ''; ?>>
            <input type="radio" name="contact_method" id="useEmail" value="email" <?php echo (($_POST['contact_method'] ?? '') == 'email') ? 'checked' : ''; ?>>
        </div>
    </div>

    <!-- Username field (optional for some account types) -->
    <?php if ($account_type == 'user'): ?>
    <div class="mb-4">
        <label for="username" class="form-label">Username <span class="text-muted">(optional)</span></label>
        <div class="input-group">
            <span class="input-group-text">@</span>
            <input type="text" class="form-control" id="username" name="username" 
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                   placeholder="username">
        </div>
        <small class="text-muted">Leave blank to auto-generate based on your name</small>
    </div>
    <?php endif; ?>
    
    <!-- Hidden username field to satisfy password managers -->
    <input type="text" name="username_dummy" id="username_dummy" style="display: none !important; position: absolute; left: -9999px;" tabindex="-1" autocomplete="username">
    
    <!-- Password -->
    <div class="mb-4">
        <label for="password" class="form-label">Create Password <span class="text-danger">*</span></label>
        <div class="password-input-group">
            <input type="password" class="form-control" id="password" name="password" 
                   value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" 
                   autocomplete="new-password">
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
            <input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" 
                   <?php echo (!empty($_POST['terms'])) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="terms">
                I agree to the <a href="/legalhub/terms" class="text-underline">Terms</a> and 
                <a href="/legalhub/privacy" class="text-underline">Privacy Policy</a>
            </label>
        </div>
    </div>
    
    <!-- Promo & Referral Codes -->
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
                               placeholder="Enter promo code" 
                               value="<?php echo htmlspecialchars($promo_code); ?>">
                        <button type="button" class="btn btn-success me-2" id="applyPromo">Apply</button>
                    </div>
                    <div id="promoMessage" class="mt-1"></div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="referral_code" class="form-label">Referral Code</label>
                    <div class="code-input-group">
                        <input type="text" class="form-control" id="referral_code" name="referral_code" 
                               placeholder="Friend's referral code" 
                               value="<?php echo htmlspecialchars($referral_code); ?>">
                        <button type="button" class="btn btn-success me-2" id="verifyReferral">Verify</button>
                    </div>
                    <div id="referralMessage" class="mt-1"></div>
                    <small class="text-muted ms-1">Enter the code of the person who referred you</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Method JavaScript -->
<script>
// Only initialize if we haven't already
if (typeof window.accountInfoInitialized === 'undefined') {
    window.accountInfoInitialized = true;
    
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
                        
                        // Format phone number if function exists
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
        
        // Initialize with current selection
        const currentMethod = phoneRadio.checked ? 'phone' : 'email';
        
        // Set active tab based on current selection
        tabOptions.forEach(tab => {
            if (tab.dataset.method === currentMethod) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
        
        // Initialize contact field
        switchContactMethod(currentMethod);
    });
}
</script>