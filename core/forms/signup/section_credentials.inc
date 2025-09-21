<?php
/**
 * Credentials Section with Handler
 * 
 * This section handles phone/email toggle selector and password field.
 * Uses the existing Birthday Gold UI patterns from createaccount.php
 * 
 * Expected variables:
 * - $values: Array of form values
 * - $errors: Array of validation errors
 * - $signup_process: Session data for signup process
 * - $process_handlers: Boolean flag indicating if handlers should process
 * - $createaccount: CreateAccount class instance for availability checks
 */

#-------------------------------------------------------------------------------
# HANDLER CODE - Process validation if flag is set
#-------------------------------------------------------------------------------
if (isset($process_handlers) && $process_handlers) {
    // Check contact method
    $contact_method = $values['contact_method'] ?? 'phone';
    
    if ($contact_method == 'phone') {
        // Validate phone
        if (empty($values['phone'])) {
            $errors['phone'] = 'Phone number is required';
        } else {
            $phone = preg_replace('/\D/', '', $values['phone']);
            if (strlen($phone) !== 10) {
                $errors['phone'] = 'Please enter a valid 10-digit phone number';
            } else {
                // Store cleaned phone number
                $values['phone_clean'] = $phone;
            }
        }
    } else {
        // Validate email
        if (empty($values['email'])) {
            $errors['email'] = 'Email address is required';
        } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            // Check email availability if createaccount instance is available
            if (isset($createaccount)) {
                $email = trim(strtolower($values['email']));
                $response = $createaccount->isemailaccountavailable($email);
                
                if ($response !== true) {
                    // We found an existing record
                    $tempinfo = $response;
                    
                    // Check if it's a pending or validated user we can continue with
                    if (!empty($tempinfo['status']) && in_array($tempinfo['status'], ['pending', 'validated'])) {
                        // Store the existing user info for later processing
                        $values['existing_user_info'] = $tempinfo;
                    } else {
                        // Email is truly unavailable
                        $errors['email'] = 'This email is already registered';
                    }
                }
            }
        }
    }
    
    // Validate password using centralized requirements
    if (empty($values['password'])) {
        $errors['password'] = 'Password is required';
    } else {
        // Use the centralized password requirements (check if account object exists)
        if (isset($account) && is_object($account)) {
            $password_requirements = $account->getPasswordRequirements();
            $password_strength = $account->calculatePasswordStrength($values['password']);
            
            if ($password_strength['score'] < $password_requirements['minimum_score_for_submit']) {
                $errors['password'] = 'Password must meet the security requirements below';
            }
        } else {
            // Fallback to basic validation if account object not available
            if (strlen($values['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            }
        }
    }
    
    // Username validation removed - will be auto-generated
    
    // Exit if only processing handlers
    return;
}

#-------------------------------------------------------------------------------
# DISPLAY CODE - Show the form section
#-------------------------------------------------------------------------------
// Determine initial contact method
$contact_method = $values['contact_method'] ?? 'phone';
?>
    <!-- Contact Method Selection -->
    <div class="mb-4">
        <label class="form-label">Start Your Account with?</label>
        <div class="contact-method-tabs">
            <div class="tab-option <?php echo $contact_method == 'phone' ? 'active' : ''; ?>" data-method="phone">
                <i class="bi bi-phone"></i>
                <span class="d-none d-sm-inline">Use Phone Number</span>
                <span class="d-inline d-sm-none">Phone</span>
            </div>
            <div class="tab-option <?php echo $contact_method == 'email' ? 'active' : ''; ?>" data-method="email">
                <i class="bi bi-envelope"></i>
                <span class="d-none d-sm-inline">Use Email Address</span>
                <span class="d-inline d-sm-none">Email</span>
            </div>
        </div>
        
        <!-- Unified Input Field Container -->
        <div class="contact-input-wrapper mt-3" id="contactInputContainer">
            <!-- Input field will be dynamically inserted here by JavaScript -->
        </div>
        
        <!-- Hidden radio buttons for form submission -->
        <div class="d-none">
            <input type="radio" name="contact_method" id="usePhone" value="phone" <?php echo $contact_method == 'phone' ? 'checked' : ''; ?>>
            <input type="radio" name="contact_method" id="useEmail" value="email" <?php echo $contact_method == 'email' ? 'checked' : ''; ?>>
        </div>
    </div>

    <!-- Hidden username field to satisfy password managers -->
    <input type="text" name="username_dummy" id="username_dummy" style="display: none !important; position: absolute; left: -9999px;" tabindex="-1" autocomplete="username">
    
    <!-- Password -->
    <div class="mb-4">
        <div class="input-group">
            <div class="form-floating flex-grow-1">
                <input type="password" 
                       class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" 
                       id="password" 
                       name="password" 
                       placeholder="Create Password"
                       value="<?php echo htmlspecialchars($values['password'] ?? ''); ?>" 
                       autocomplete="off"
                       required>
                <label for="password">Create Password <span class="text-danger">*</span></label>
            </div>
            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password" tabindex="-1">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <div class="password-strength mt-2">
            <div class="strength-bar"></div>
        </div>
        <small class="text-muted">At least 8 characters with uppercase, lowercase, number & special character</small>
        <?php if (!empty($errors['password'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['password']; ?></div>
        <?php endif; ?>
    </div>
<!-- Section remains open for additional content -->

<script>
// Initialize contact method values for the section
window.credentialsData = {
    phoneValue: '<?php echo htmlspecialchars($values['phone'] ?? ''); ?>',
    emailValue: '<?php echo htmlspecialchars($values['email'] ?? ''); ?>',
    contactMethod: '<?php echo $contact_method; ?>'
};
</script>