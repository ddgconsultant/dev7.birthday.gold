<?php
/**
 * Parental Basic Signup Form Display
 * 
 * Variables available:
 * - $values: Array of form values (for pre-filling)
 * - $errors: Array of validation errors
 * - $signup_process: Session data from step 1
 */

// Get values
$firstname = $values['firstname'] ?? '';
$lastname = $values['lastname'] ?? '';
$email = $values['email'] ?? '';
$phone = $values['phone'] ?? '';
$birthday = $values['birthday'] ?? '';
$num_children = $values['num_children'] ?? '1';
$newsletter = $values['newsletter'] ?? 1;

?>

<!-- Parent Information Section -->
<div class="form-section">
    <h5 class="section-title">Parent Account Information</h5>
    
    <div class="row">
        <!-- Parent Name -->
        <div class="col-md-6 mb-3">
            <label for="firstname" class="form-label">Parent First Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['firstname']) ? 'is-invalid' : ''; ?>" 
                   id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
            <?php if (isset($errors['firstname'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['firstname']); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="lastname" class="form-label">Parent Last Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['lastname']) ? 'is-invalid' : ''; ?>" 
                   id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
            <?php if (isset($errors['lastname'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['lastname']); ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <!-- Email -->
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                   id="email" name="email" placeholder="parent@email.com"
                   value="<?php echo htmlspecialchars($email); ?>" required>
            <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
            <?php endif; ?>
            <small class="text-muted">We'll send birthday reminders here</small>
        </div>
        
        <!-- Phone -->
        <div class="col-md-6 mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <div class="input-group">
                <span class="input-group-text">+1</span>
                <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                       id="phone" name="phone" placeholder="(555) 123-4567"
                       value="<?php echo htmlspecialchars($phone); ?>">
            </div>
            <?php if (isset($errors['phone'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['phone']); ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <!-- Parent Birthday -->
        <div class="col-md-6 mb-3">
            <label for="birthday" class="form-label">Parent's Birthday <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="date" 
                       class="form-control <?php echo isset($errors['birthday']) ? 'is-invalid' : ''; ?>" 
                       id="birthday" 
                       name="birthday" 
                       value="<?php echo htmlspecialchars($birthday); ?>" 
                       max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                       min="<?php echo date('Y-m-d', strtotime('-120 years')); ?>"
                       placeholder="mm/dd/yyyy"
                       required>
                <span class="input-group-text">
                    <i class="bi bi-calendar-event"></i>
                </span>
            </div>
            <?php if (isset($errors['birthday'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['birthday']); ?></div>
            <?php endif; ?>
            <small class="text-muted">Must be 18 or older to create a parental account</small>
        </div>
        
        <!-- Number of Children -->
        <div class="col-md-6 mb-3">
            <label for="num_children" class="form-label">Number of Children <span class="text-danger">*</span></label>
            <select class="form-select <?php echo isset($errors['num_children']) ? 'is-invalid' : ''; ?>" 
                    id="num_children" name="num_children" required>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $num_children == $i ? 'selected' : ''; ?>>
                        <?php echo $i; ?> <?php echo $i == 1 ? 'child' : 'children'; ?>
                    </option>
                <?php endfor; ?>
                <option value="10+" <?php echo $num_children == '10+' ? 'selected' : ''; ?>>More than 10</option>
            </select>
            <?php if (isset($errors['num_children'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['num_children']); ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Password -->
    <div class="row">
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
</div>

<!-- Children Information Notice -->
<div class="form-section">
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Note:</strong> You'll be able to add your children's information after creating your account.
        This helps us ensure proper parental consent and privacy protection.
    </div>
</div>

<!-- Newsletter Opt-in -->
<div class="form-section">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" value="1" <?php echo $newsletter ? 'checked' : ''; ?>>
        <label class="form-check-label" for="newsletter">
            Send me family birthday reminders and special offers
        </label>
    </div>
</div>

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

/* Style for underage years in dropdown */
#birth_year option.text-danger {
    color: #dc3545 !important;
    font-weight: 500;
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
    
    // Number of children change handler
    const numChildrenSelect = document.getElementById('num_children');
    if (numChildrenSelect) {
        numChildrenSelect.addEventListener('change', function(e) {
            // Could be used to show/hide child input fields dynamically
            console.log('Selected children count:', e.target.value);
        });
    }
})();
</script>