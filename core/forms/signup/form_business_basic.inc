<?php
/**
 * Business Basic Signup Form Display
 * 
 * Variables available:
 * - $values: Array of form values (for pre-filling)
 * - $errors: Array of validation errors
 * - $signup_process: Session data from step 1
 */

// Get values
$business_name = $values['business_name'] ?? '';
$business_type = $values['business_type'] ?? '';
$tax_id = $values['tax_id'] ?? '';
$num_locations = $values['num_locations'] ?? '1';
$website = $values['website'] ?? '';
$contact_firstname = $values['contact_firstname'] ?? '';
$contact_lastname = $values['contact_lastname'] ?? '';
$contact_title = $values['contact_title'] ?? '';
$email = $values['email'] ?? '';
$phone = $values['phone'] ?? '';
$newsletter = $values['newsletter'] ?? 1;

// Business types
$business_types = [
    'restaurant' => 'Restaurant',
    'retail' => 'Retail Store',
    'service' => 'Service Provider',
    'entertainment' => 'Entertainment',
    'health_beauty' => 'Health & Beauty',
    'other' => 'Other'
];

// Location options
$location_options = [
    '1' => '1 Location',
    '2-5' => '2-5 Locations',
    '6-10' => '6-10 Locations',
    '11-50' => '11-50 Locations',
    '50+' => '50+ Locations'
];
?>

<!-- Business Information Section -->
<div class="form-section">
    <h5 class="section-title">Business Information</h5>
    <div class="row">
        <!-- Business Name -->
        <div class="col-12 mb-3">
            <label for="business_name" class="form-label">Business Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['business_name']) ? 'is-invalid' : ''; ?>" 
                   id="business_name" name="business_name" 
                   value="<?php echo htmlspecialchars($business_name); ?>" required>
            <?php if (isset($errors['business_name'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['business_name']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Business Type -->
        <div class="col-md-6 mb-3">
            <label for="business_type" class="form-label">Business Type <span class="text-danger">*</span></label>
            <select class="form-select <?php echo isset($errors['business_type']) ? 'is-invalid' : ''; ?>" 
                    id="business_type" name="business_type" required>
                <option value="">Select business type</option>
                <?php foreach ($business_types as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $business_type === $value ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['business_type'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['business_type']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Tax ID -->
        <div class="col-md-6 mb-3">
            <label for="tax_id" class="form-label">EIN/Tax ID</label>
            <input type="text" class="form-control <?php echo isset($errors['tax_id']) ? 'is-invalid' : ''; ?>" 
                   id="tax_id" name="tax_id" placeholder="XX-XXXXXXX"
                   value="<?php echo htmlspecialchars($tax_id); ?>">
            <?php if (isset($errors['tax_id'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['tax_id']); ?></div>
            <?php endif; ?>
            <small class="text-muted">Federal Employer Identification Number</small>
        </div>
        
        <!-- Number of Locations -->
        <div class="col-md-6 mb-3">
            <label for="num_locations" class="form-label">Number of Locations <span class="text-danger">*</span></label>
            <select class="form-select <?php echo isset($errors['num_locations']) ? 'is-invalid' : ''; ?>" 
                    id="num_locations" name="num_locations" required>
                <?php foreach ($location_options as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $num_locations === $value ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['num_locations'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['num_locations']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Website -->
        <div class="col-md-6 mb-3">
            <label for="website" class="form-label">Website</label>
            <input type="text" class="form-control <?php echo isset($errors['website']) ? 'is-invalid' : ''; ?>" 
                   id="website" name="website" placeholder="https://www.example.com"
                   value="<?php echo htmlspecialchars($website); ?>">
            <?php if (isset($errors['website'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['website']); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Contact Information Section -->
<div class="form-section">
    <h5 class="section-title">Contact Information</h5>
    <div class="row">
        <!-- Contact First Name -->
        <div class="col-md-6 mb-3">
            <label for="contact_firstname" class="form-label">Contact First Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['contact_firstname']) ? 'is-invalid' : ''; ?>" 
                   id="contact_firstname" name="contact_firstname" 
                   value="<?php echo htmlspecialchars($contact_firstname); ?>" required>
            <?php if (isset($errors['contact_firstname'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['contact_firstname']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Contact Last Name -->
        <div class="col-md-6 mb-3">
            <label for="contact_lastname" class="form-label">Contact Last Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['contact_lastname']) ? 'is-invalid' : ''; ?>" 
                   id="contact_lastname" name="contact_lastname" 
                   value="<?php echo htmlspecialchars($contact_lastname); ?>" required>
            <?php if (isset($errors['contact_lastname'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['contact_lastname']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Title -->
        <div class="col-md-6 mb-3">
            <label for="contact_title" class="form-label">Title/Position</label>
            <input type="text" class="form-control <?php echo isset($errors['contact_title']) ? 'is-invalid' : ''; ?>" 
                   id="contact_title" name="contact_title" placeholder="e.g., Marketing Manager"
                   value="<?php echo htmlspecialchars($contact_title); ?>">
            <?php if (isset($errors['contact_title'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['contact_title']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Business Email -->
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Business Email <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                       id="email" name="email" placeholder="contact@business.com"
                       value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['email']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Business Phone -->
        <div class="col-md-6 mb-3">
            <label for="phone" class="form-label">Business Phone <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">+1</span>
                <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                       id="phone" name="phone" placeholder="555-123-4567" required
                       value="<?php echo htmlspecialchars($phone); ?>">
            </div>
            <?php if (isset($errors['phone'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['phone']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Password -->
        <div class="col-12 mb-3">
            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
            <div class="password-input-group">
                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                       id="password" name="password" value="<?php echo htmlspecialchars($values['password'] ?? ''); ?>" required>
                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div class="password-strength mt-2">
                <div class="strength-bar"></div>
            </div>
            <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['password']); ?></div>
            <?php endif; ?>
            <small class="text-muted">At least 8 characters</small>
        </div>
    </div>
</div>

<!-- Newsletter Opt-in -->
<div class="form-section">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" value="1" <?php echo $newsletter ? 'checked' : ''; ?>>
        <label class="form-check-label" for="newsletter">
            Send me birthday reward program updates and special offers
        </label>
    </div>
</div>

<!-- Terms and Privacy -->
<div class="form-section">
    <div class="form-check">
        <input class="form-check-input <?php echo isset($errors['terms']) ? 'is-invalid' : ''; ?>" type="checkbox" id="terms" name="terms" value="1" <?php echo (!empty($values['terms'])) ? 'checked' : ''; ?>>
        <label class="form-check-label <?php echo isset($errors['terms']) ? 'text-danger' : ''; ?>" for="terms">
            I agree to the <a href="/legalhub/terms" class="text-underline">Terms</a> and <a href="/legalhub/privacy" class="text-underline">Privacy Policy</a>
        </label>
        <?php if (isset($errors['terms'])): ?>
            <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['terms']); ?></div>
        <?php endif; ?>
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
    
    <div class="promo-referral-content collapse" id="promoReferralSection">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="promo_code" class="form-label">Promo Code</label>
                <div class="code-input-group">
                    <input type="text" class="form-control" id="promo_code" name="promo_code" 
                           placeholder="Enter promo code" value="<?php echo htmlspecialchars($values['promo_code'] ?? ''); ?>">
                    <button type="button" class="btn btn-success me-2" id="applyPromo">Apply</button>
                </div>
                <div id="promoMessage" class="mt-1"></div>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="referral_code" class="form-label">Referral Code</label>
                <div class="code-input-group">
                    <input type="text" class="form-control" id="referral_code" name="referral_code" 
                           placeholder="Friend's referral code" value="<?php echo htmlspecialchars($values['referral_code'] ?? ''); ?>">
                    <button type="button" class="btn btn-success me-2" id="verifyReferral">Verify</button>
                </div>
                <div id="referralMessage" class="mt-1"></div>
                <small class="text-muted ms-1">Enter the code of the person who referred you</small>
            </div>
        </div>
    </div>
</div>

<!-- Hidden birthday field (businesses don't have birthdays but the system requires it) -->
<input type="hidden" name="birthday" value="2000-01-01">

<!-- Form-specific Styles -->
<style>
/* Form Controls Focus State */
.form-control:focus {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.15) !important;
}

/* Ensure all form fields have white background */
.form-control {
    background-color: #ffffff !important;
    border-color: #e9ecef !important;
}

/* Password Input Group */
.password-input-group {
    position: relative;
    display: flex;
}

.password-input-group .form-control {
    border-right: none;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.password-input-group .btn {
    border: 2px solid #e9ecef;
    border-left: none;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    background: white;
    color: #6c757d;
}

.password-input-group .form-control:focus ~ .btn {
    border-color: #0d6efd;
}

/* Password Strength */
.password-strength {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    width: 0;
    transition: all 0.3s ease;
    background: #dc3545;
}

.strength-bar.weak {
    width: 33%;
    background: #dc3545;
}

.strength-bar.medium {
    width: 66%;
    background: #ffc107;
}

.strength-bar.strong {
    width: 100%;
    background: #198754;
}

/* Input group styling */
.input-group-text {
    background-color: #f8f9fa;
    border: 2px solid #e9ecef;
    color: #6c757d;
}

.input-group .form-control:focus ~ .input-group-text,
.input-group .input-group-text:has(+ .form-control:focus) {
    border-color: #0d6efd;
}

/* Promo/Referral Section */
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
</style>

<!-- Form-specific JavaScript -->
<script>
(function() {
    // Phone formatting
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            
            if (value.length > 0) {
                if (value.length <= 3) {
                    formattedValue = value;
                } else if (value.length <= 6) {
                    formattedValue = `${value.slice(0, 3)}-${value.slice(3)}`;
                } else {
                    formattedValue = `${value.slice(0, 3)}-${value.slice(3, 6)}-${value.slice(6, 10)}`;
                }
            }
            
            e.target.value = formattedValue;
        });
    }
    
    // Tax ID formatting
    const taxIdInput = document.getElementById('tax_id');
    if (taxIdInput) {
        taxIdInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.slice(0, 2) + '-' + value.slice(2, 9);
            }
            e.target.value = value;
        });
    }
    
    // Website URL auto-prefix
    const websiteInput = document.getElementById('website');
    if (websiteInput) {
        websiteInput.addEventListener('blur', function(e) {
            let value = e.target.value.trim();
            if (value && !value.match(/^https?:\/\//)) {
                e.target.value = 'https://' + value;
            }
        });
    }
    
    // Password toggle
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.querySelector('.strength-bar');
            
            if (!strengthBar) return;
            
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Complexity checks
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Update strength bar
            strengthBar.className = 'strength-bar';
            if (password.length === 0) {
                strengthBar.style.width = '0';
            } else if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });
    }
    
    // Promo/Referral toggle
    const togglePromoReferral = document.getElementById('togglePromoReferral');
    const promoReferralSection = document.getElementById('promoReferralSection');
    const promoReferralChevron = document.getElementById('promoReferralChevron');
    
    if (togglePromoReferral && promoReferralSection) {
        // Initialize Bootstrap collapse
        const promoReferralCollapse = new bootstrap.Collapse(promoReferralSection, {
            toggle: false
        });
        
        togglePromoReferral.addEventListener('click', function(e) {
            e.preventDefault();
            promoReferralCollapse.toggle();
        });
        
        // Listen for show/hide events to update chevron
        promoReferralSection.addEventListener('shown.bs.collapse', function() {
            promoReferralChevron.classList.remove('bi-chevron-down');
            promoReferralChevron.classList.add('bi-chevron-up');
        });
        
        promoReferralSection.addEventListener('hidden.bs.collapse', function() {
            promoReferralChevron.classList.remove('bi-chevron-up');
            promoReferralChevron.classList.add('bi-chevron-down');
        });
    }
})();
</script>