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
    <div class="contact-toggle mb-3">
        <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="contact_method" id="usePhone" value="phone" <?php echo $contact_method === 'phone' ? 'checked' : ''; ?>>
            <label class="btn btn-outline-success" for="usePhone">
                <i class="bi bi-phone"></i> Use Phone Number
            </label>
            
            <input type="radio" class="btn-check" name="contact_method" id="useEmail" value="email" <?php echo $contact_method === 'email' ? 'checked' : ''; ?>>
            <label class="btn btn-outline-success" for="useEmail">
                <i class="bi bi-envelope"></i> Use Email Address
            </label>
        </div>
    </div>
    
    <div class="row">
        <!-- Phone Field -->
        <div class="col-12 mb-3 <?php echo $contact_method !== 'phone' ? 'd-none' : ''; ?>" id="phoneField">
            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">+1</span>
                <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                       id="phone" name="phone" placeholder="(555) 123-4567"
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
            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                   id="email" name="email" placeholder="your@email.com"
                   value="<?php echo htmlspecialchars($email); ?>">
            <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
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
                       id="password" name="password" required>
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
        <div class="col-md-6 mb-3">
            <label for="birthday" class="form-label">Birthday <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="date" 
                       class="form-control <?php echo isset($errors['birthday']) ? 'is-invalid' : ''; ?>" 
                       id="birthday" 
                       name="birthday" 
                       value="<?php echo htmlspecialchars($birthday); ?>" 
                       max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>"
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
            <small class="text-muted">We'll use this to notify you of birthday rewards</small>
        </div>
        
        <!-- Optional: Alternative date input for better mobile support -->
        <div class="col-md-6 mb-3 d-none" id="birthday-alt-container">
            <label class="form-label">Or enter your birthday:</label>
            <div class="row g-2">
                <div class="col-4">
                    <select class="form-select" id="birth-month">
                        <option value="">Month</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>">
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-4">
                    <select class="form-select" id="birth-day">
                        <option value="">Day</option>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?php echo str_pad($d, 2, '0', STR_PAD_LEFT); ?>"><?php echo $d; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-4">
                    <select class="form-select" id="birth-year">
                        <option value="">Year</option>
                        <?php 
                        $currentYear = date('Y');
                        $minYear = $currentYear - 120;
                        $maxYear = $currentYear - 13;
                        for ($y = $maxYear; $y >= $minYear; $y--): 
                        ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
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
                    formattedValue = `(${value}`;
                } else if (value.length <= 6) {
                    formattedValue = `(${value.slice(0, 3)}) ${value.slice(3)}`;
                } else {
                    formattedValue = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
                }
            }
            
            e.target.value = formattedValue;
        });
    }
    
    // Birthday date handling
    const birthdayInput = document.getElementById('birthday');
    const birthMonth = document.getElementById('birth-month');
    const birthDay = document.getElementById('birth-day');
    const birthYear = document.getElementById('birth-year');
    const altContainer = document.getElementById('birthday-alt-container');
    
    // Check if browser supports date input properly
    function checkDateInputSupport() {
        const test = document.createElement('input');
        test.type = 'date';
        test.value = '2000-01-01';
        return test.value === '2000-01-01';
    }
    
    // Show alternative dropdowns for browsers with poor date support
    if (!checkDateInputSupport() && altContainer) {
        altContainer.classList.remove('d-none');
        
        // Sync dropdown values to hidden date input
        function updateDateFromDropdowns() {
            if (birthMonth.value && birthDay.value && birthYear.value) {
                birthdayInput.value = `${birthYear.value}-${birthMonth.value}-${birthDay.value}`;
            }
        }
        
        birthMonth.addEventListener('change', updateDateFromDropdowns);
        birthDay.addEventListener('change', updateDateFromDropdowns);
        birthYear.addEventListener('change', updateDateFromDropdowns);
        
        // Pre-populate dropdowns if date exists
        if (birthdayInput.value) {
            const parts = birthdayInput.value.split('-');
            if (parts.length === 3) {
                birthYear.value = parts[0];
                birthMonth.value = parts[1];
                birthDay.value = parts[2];
            }
        }
    }
    
    // Add better date validation and formatting
    if (birthdayInput) {
        // Add visual feedback for date selection
        birthdayInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            const age = Math.floor((today - selectedDate) / (365.25 * 24 * 60 * 60 * 1000));
            
            // Remove any existing age display
            const existingAge = this.parentElement.parentElement.querySelector('.age-display');
            if (existingAge) {
                existingAge.remove();
            }
            
            // Show age if valid
            if (age >= 0 && age <= 120) {
                const ageDisplay = document.createElement('small');
                ageDisplay.className = 'text-muted age-display d-block mt-1';
                ageDisplay.textContent = `Age: ${age} years old`;
                this.parentElement.parentElement.appendChild(ageDisplay);
            }
        });
        
        // Trigger change event if value exists
        if (birthdayInput.value) {
            birthdayInput.dispatchEvent(new Event('change'));
        }
    }
    
    // Contact method toggle
    const phoneRadio = document.getElementById('usePhone');
    const emailRadio = document.getElementById('useEmail');
    const phoneField = document.getElementById('phoneField');
    const emailField = document.getElementById('emailField');
    
    function handleContactMethodChange() {
        if (phoneRadio.checked) {
            phoneField.classList.remove('d-none');
            emailField.classList.add('d-none');
            document.getElementById('phone').setAttribute('required', 'required');
            document.getElementById('email').removeAttribute('required');
        } else {
            emailField.classList.remove('d-none');
            phoneField.classList.add('d-none');
            document.getElementById('email').setAttribute('required', 'required');
            document.getElementById('phone').removeAttribute('required');
        }
    }
    
    if (phoneRadio && emailRadio) {
        phoneRadio.addEventListener('change', handleContactMethodChange);
        emailRadio.addEventListener('change', handleContactMethodChange);
    }
})();
</script>