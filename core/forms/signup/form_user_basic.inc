<?php
/**
 * User Basic Signup Form Display
 * 
 * Variables available:
 * - $values: Array of form values (for pre-filling)
 * - $errors: Array of validation errors
 * - $signup_process: Session data from step 1
 */

// Get values
$contact_method = $values['contact_method'] ?? 'phone';
$phone = $values['phone'] ?? '';
$email = $values['email'] ?? '';
$firstname = $values['firstname'] ?? '';
$lastname = $values['lastname'] ?? '';
$birthday = $values['birthday'] ?? '';
$newsletter = $values['newsletter'] ?? 1;

?>

<!-- Account Information Section -->
<div class="form-section">
    <h5 class="section-title">Account Information</h5>
    
    <!-- Phone/Email Toggle -->
    <div class="mb-4">
        <label class="form-label">Start Your Account with?</label>
        <div class="contact-method-tabs">
            <div class="tab-option <?php echo $contact_method === 'phone' ? 'active' : ''; ?>" data-method="phone">
                <i class="bi bi-phone"></i>
                <span>Use Phone Number</span>
            </div>
            <div class="tab-option <?php echo $contact_method === 'email' ? 'active' : ''; ?>" data-method="email">
                <i class="bi bi-envelope"></i>
                <span>Use Email Address</span>
            </div>
        </div>
        
        <!-- Hidden radio buttons for form submission -->
        <div class="d-none">
            <input type="radio" name="contact_method" id="usePhone" value="phone" <?php echo $contact_method === 'phone' ? 'checked' : ''; ?>>
            <input type="radio" name="contact_method" id="useEmail" value="email" <?php echo $contact_method === 'email' ? 'checked' : ''; ?>>
        </div>
    </div>
    
    <div class="row">
        <!-- Phone Field -->
        <div class="col-12 mb-3 <?php echo $contact_method !== 'phone' ? 'd-none' : ''; ?>" id="phoneField">
            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">+1</span>
                <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                       id="phone" name="phone" placeholder="555-123-4567"
                       value="<?php echo htmlspecialchars($phone); ?>">
            </div>
            <?php if (isset($errors['phone'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['phone']); ?></div>
            <?php endif; ?>
            <small class="text-muted">We'll send you a verification code via SMS</small>
        </div>
        
        <!-- Email Field -->
        <div class="col-12 mb-3 <?php echo $contact_method !== 'email' ? 'd-none' : ''; ?>" id="emailField">
            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                       id="email" name="email" placeholder="your@email.com"
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['email']); ?></div>
            <?php endif; ?>
            <small class="text-muted">We'll send you a verification link via email</small>
        </div>
    </div>
    
    <div class="row">
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
            <small class="text-muted">At least 8 characters with a mix of letters and numbers</small>
        </div>
    </div>
    
    <div class="row">
        <!-- First Name -->
        <div class="col-md-6 mb-3">
            <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['firstname']) ? 'is-invalid' : ''; ?>" 
                   id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
            <?php if (isset($errors['firstname'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['firstname']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Last Name -->
        <div class="col-md-6 mb-3">
            <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['lastname']) ? 'is-invalid' : ''; ?>" 
                   id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
            <?php if (isset($errors['lastname'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['lastname']); ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <!-- Birthday -->
        <div class="col-md-12 mb-3">
            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
            <div class="d-flex gap-2" style="max-width: 400px;">
                <div class="flex-fill">
                    <select class="form-control <?php echo isset($errors['birthday']) ? 'is-invalid' : ''; ?>" id="birth_month" name="birth_month">
                        <option value="">Month</option>
                        <?php
                        $months = [
                            '01' => '01 - January', '02' => '02 - February', '03' => '03 - March',
                            '04' => '04 - April', '05' => '05 - May', '06' => '06 - June',
                            '07' => '07 - July', '08' => '08 - August', '09' => '09 - September',
                            '10' => '10 - October', '11' => '11 - November', '12' => '12 - December'
                        ];
                        $selected_month = $values['birth_month'] ?? '';
                        if (empty($selected_month) && !empty($birthday)) {
                            $selected_month = date('m', strtotime($birthday));
                        }
                        foreach ($months as $value => $label) {
                            $selected = ($selected_month == $value) ? 'selected' : '';
                            echo "<option value=\"$value\" $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="width: 95px;">
                    <select class="form-control <?php echo isset($errors['birthday']) ? 'is-invalid' : ''; ?>" id="birth_day" name="birth_day">
                        <option value="">Day</option>
                        <?php
                        $selected_day = $values['birth_day'] ?? '';
                        if (empty($selected_day) && !empty($birthday)) {
                            $selected_day = date('d', strtotime($birthday));
                        }
                        for ($i = 1; $i <= 31; $i++) {
                            $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $selected = ($selected_day == $day) ? 'selected' : '';
                            echo "<option value=\"$day\" $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="width: 120px;">
                    <select class="form-control <?php echo isset($errors['birthday']) ? 'is-invalid' : ''; ?>" id="birth_year" name="birth_year">
                        <option value="">Year</option>
                        <?php
                        $current_year = date('Y');
                        $selected_year = $values['birth_year'] ?? '';
                        if (empty($selected_year) && !empty($birthday)) {
                            $selected_year = date('Y', strtotime($birthday));
                        }
                        $min_age_year = $current_year - 13;
                        for ($i = $current_year; $i >= 1900; $i--) {
                            $selected = ($selected_year == $i) ? 'selected' : '';
                            $class = ($i > $min_age_year) ? 'class="text-danger"' : '';
                            echo "<option value=\"$i\" $selected $class>$i</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <?php if (isset($errors['birthday'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['birthday']); ?></div>
            <?php endif; ?>
            <small class="text-muted">We'll use this to notify you of birthday rewards</small>
            <!-- Hidden field for combined birthday value -->
            <input type="hidden" id="birthday" name="birthday" value="<?php echo htmlspecialchars($birthday); ?>">
        </div>
    </div>
</div>

<!-- Newsletter Opt-in -->
<div class="form-section">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" value="1" <?php echo $newsletter ? 'checked' : ''; ?>>
        <label class="form-check-label" for="newsletter">
            Send me birthday reward reminders and special offers
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

<!-- Form-specific Styles -->
<style>
/* Contact Method Tab Styles */
.contact-method-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
    overflow: visible !important;
    position: relative;
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

/* Style for underage years in dropdown */
#birth_year option.text-danger {
    color: #dc3545 !important;
    font-weight: 500;
}

/* Mobile adjustments */
@media (max-width: 480px) {
    .contact-method-tabs {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .tab-option {
        width: 100%;
    }
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
    
    // Birthday dropdowns handling
    const birthMonth = document.getElementById('birth_month');
    const birthDay = document.getElementById('birth_day');
    const birthYear = document.getElementById('birth_year');
    const birthdayHidden = document.getElementById('birthday');
    
    function updateBirthdayField() {
        if (birthMonth && birthDay && birthYear && birthdayHidden) {
            if (birthMonth.value && birthDay.value && birthYear.value) {
                // Combine into YYYY-MM-DD format
                birthdayHidden.value = `${birthYear.value}-${birthMonth.value}-${birthDay.value}`;
            } else {
                birthdayHidden.value = '';
            }
        }
    }
    
    // Update hidden field when any dropdown changes
    if (birthMonth) birthMonth.addEventListener('change', updateBirthdayField);
    if (birthDay) birthDay.addEventListener('change', updateBirthdayField);
    if (birthYear) birthYear.addEventListener('change', updateBirthdayField);
    
    // Dynamic day adjustment based on month
    if (birthMonth && birthDay) {
        birthMonth.addEventListener('change', function() {
            const month = parseInt(this.value);
            const year = birthYear ? parseInt(birthYear.value) : new Date().getFullYear();
            const currentDay = parseInt(birthDay.value) || 1;
            
            // Days in each month
            const daysInMonth = {
                1: 31, 2: 28, 3: 31, 4: 30, 5: 31, 6: 30,
                7: 31, 8: 31, 9: 30, 10: 31, 11: 30, 12: 31
            };
            
            // Check for leap year
            if (month === 2 && year && ((year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0))) {
                daysInMonth[2] = 29;
            }
            
            // Update day options
            const maxDays = month ? daysInMonth[month] : 31;
            
            // Clear current options
            birthDay.innerHTML = '<option value="">Day</option>';
            
            // Add new options
            for (let i = 1; i <= maxDays; i++) {
                const day = i.toString().padStart(2, '0');
                const option = document.createElement('option');
                option.value = day;
                option.textContent = i;
                if (i === currentDay && currentDay <= maxDays) {
                    option.selected = true;
                }
                birthDay.appendChild(option);
            }
            
            updateBirthdayField();
        });
    }
    
    // Contact method toggle with tab-style interface
    const tabOptions = document.querySelectorAll('.tab-option');
    const phoneRadio = document.getElementById('usePhone');
    const emailRadio = document.getElementById('useEmail');
    const phoneField = document.getElementById('phoneField');
    const emailField = document.getElementById('emailField');
    
    tabOptions.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active from all tabs
            tabOptions.forEach(t => t.classList.remove('active'));
            
            // Add active to clicked tab
            this.classList.add('active');
            
            // Update radio buttons and fields
            const method = this.dataset.method;
            if (method === 'phone') {
                phoneRadio.checked = true;
                phoneField.classList.remove('d-none');
                emailField.classList.add('d-none');
                document.getElementById('phone').setAttribute('required', 'required');
                document.getElementById('email').removeAttribute('required');
            } else {
                emailRadio.checked = true;
                emailField.classList.remove('d-none');
                phoneField.classList.add('d-none');
                document.getElementById('email').setAttribute('required', 'required');
                document.getElementById('phone').removeAttribute('required');
            }
        });
    });
    
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