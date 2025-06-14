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

<!-- Form-specific JavaScript -->
<script>
(function() {
    // Birthday date handling with age display
    const birthdayInput = document.getElementById('birthday');
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
                
                // Add warning if under 18
                if (age < 18) {
                    ageDisplay.className += ' text-danger';
                    ageDisplay.textContent += ' (Must be 18 or older)';
                }
                
                this.parentElement.parentElement.appendChild(ageDisplay);
            }
        });
        
        // Trigger change event if value exists
        if (birthdayInput.value) {
            birthdayInput.dispatchEvent(new Event('change'));
        }
    }
    
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