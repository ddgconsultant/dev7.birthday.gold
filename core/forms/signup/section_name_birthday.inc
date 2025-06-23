<?php
/**
 * Name and Birthday Section with Handler
 * 
 * This section collects and validates the user's first name, last name, and birthday.
 * Uses the existing Birthday Gold UI patterns with dropdowns for birthday.
 * 
 * Expected variables:
 * - $values: Array of form values
 * - $errors: Array of validation errors
 * - $process_handlers: Boolean flag indicating if handlers should process
 * - $signup_process: Session data for signup process
 */

#-------------------------------------------------------------------------------
# HANDLER CODE - Process validation if flag is set
#-------------------------------------------------------------------------------
if (isset($process_handlers) && $process_handlers) {
    // First name validation
    if (empty($values['firstname'])) {
        $errors['firstname'] = 'First name is required';
    } elseif (strlen($values['firstname']) < 2) {
        $errors['firstname'] = 'First name must be at least 2 characters';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $values['firstname'])) {
        $errors['firstname'] = 'First name contains invalid characters';
    }
    
    // Last name validation
    if (empty($values['lastname'])) {
        $errors['lastname'] = 'Last name is required';
    } elseif (strlen($values['lastname']) < 2) {
        $errors['lastname'] = 'Last name must be at least 2 characters';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $values['lastname'])) {
        $errors['lastname'] = 'Last name contains invalid characters';
    }
    
    // Birthday validation
    if (empty($values['birth_month']) || empty($values['birth_day']) || empty($values['birth_year'])) {
        $errors['birthday'] = 'Please select your complete birth date';
    } else {
        // Construct date from dropdowns
        $month = str_pad($values['birth_month'], 2, '0', STR_PAD_LEFT);
        $day = str_pad($values['birth_day'], 2, '0', STR_PAD_LEFT);
        $year = $values['birth_year'];
        
        $birthday_string = $year . '-' . $month . '-' . $day;
        $values['birthday'] = $birthday_string;
        
        // Validate the date
        $birthdate = DateTime::createFromFormat('Y-m-d', $birthday_string);
        if (!$birthdate || $birthdate->format('Y-m-d') !== $birthday_string) {
            $errors['birthday'] = 'Please select a valid date';
        } else {
            $age = $birthdate->diff(new DateTime())->y;
            if ($age < 13) {
                $errors['birthday'] = 'You must be at least 13 years old to create an account';
            } elseif ($age > 120) {
                $errors['birthday'] = 'Please enter a valid birth year';
            }
        }
    }
    
    // Exit if only processing handlers
    return;
}

#-------------------------------------------------------------------------------
# DISPLAY CODE - Show the form section
#-------------------------------------------------------------------------------
?>
<div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Your Name and Birthday</h5>
        <i class="bi bi-info-circle text-muted" 
           style="font-size: 1rem; cursor: pointer;" 
           data-bs-toggle="modal" 
           data-bs-target="#nameBirthdayInfoModal"
           title="Click for more information"></i>
    </div>
    <div class="card-body">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" 
                       class="form-control <?php echo !empty($errors['firstname']) ? 'is-invalid' : ''; ?>" 
                       id="firstname" 
                       name="firstname" 
                       placeholder="First Name"
                       value="<?php echo htmlspecialchars($values['firstname'] ?? ''); ?>"
                       required>
                <label for="firstname">First Name <span class="text-danger">*</span></label>
                <?php if (!empty($errors['firstname'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['firstname']; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" 
                       class="form-control <?php echo !empty($errors['lastname']) ? 'is-invalid' : ''; ?>" 
                       id="lastname" 
                       name="lastname" 
                       placeholder="Last Name"
                       value="<?php echo htmlspecialchars($values['lastname'] ?? ''); ?>"
                       required>
                <label for="lastname">Last Name <span class="text-danger">*</span></label>
                <?php if (!empty($errors['lastname'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['lastname']; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
  
    <div class="row g-3 mt-3">
        <div class="col-12">
            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
            <div class="row g-2 date-row">
                <div class="col-md-5">
                    <select class="form-control <?php echo !empty($errors['birthday']) ? 'is-invalid' : ''; ?>" 
                            id="birth_month" 
                            name="birth_month">
                        <option value="">Month</option>
                        <?php
                        $months = [
                            '01' => '01 - January', '02' => '02 - February', '03' => '03 - March',
                            '04' => '04 - April', '05' => '05 - May', '06' => '06 - June',
                            '07' => '07 - July', '08' => '08 - August', '09' => '09 - September',
                            '10' => '10 - October', '11' => '11 - November', '12' => '12 - December'
                        ];
                        $selected_month = $values['birth_month'] ?? '';
                        if (empty($selected_month) && !empty($values['birthday'])) {
                            $selected_month = date('m', strtotime($values['birthday']));
                        }
                        foreach ($months as $value => $label) {
                            $selected = ($selected_month == $value) ? 'selected' : '';
                            echo "<option value=\"$value\" $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control <?php echo !empty($errors['birthday']) ? 'is-invalid' : ''; ?>" 
                            id="birth_day" 
                            name="birth_day">
                        <option value="">Day</option>
                        <?php
                        $selected_day = $values['birth_day'] ?? '';
                        if (empty($selected_day) && !empty($values['birthday'])) {
                            $selected_day = date('d', strtotime($values['birthday']));
                        }
                        for ($i = 1; $i <= 31; $i++) {
                            $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $selected = ($selected_day == $day) ? 'selected' : '';
                            echo "<option value=\"$day\" $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-control <?php echo !empty($errors['birthday']) ? 'is-invalid' : ''; ?>" 
                            id="birth_year" 
                            name="birth_year">
                        <option value="">Year</option>
                        <?php
                        $current_year = date('Y');
                        $selected_year = $values['birth_year'] ?? '';
                        if (empty($selected_year) && !empty($values['birthday'])) {
                            $selected_year = date('Y', strtotime($values['birthday']));
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
            <small class="form-text text-muted">We'll use this to notify you of birthday rewards</small>
            <!-- Hidden field for combined birthday value -->
            <input type="hidden" id="birthday" name="birthday" value="<?php echo htmlspecialchars($values['birthday'] ?? ''); ?>">
            <?php if (!empty($errors['birthday'])): ?>
                <div class="invalid-feedback d-block"><?php echo $errors['birthday']; ?></div>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>

<!-- Name and Birthday Info Modal -->
<div class="modal fade" id="nameBirthdayInfoModal" tabindex="-1" aria-labelledby="nameBirthdayInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nameBirthdayInfoModalLabel">
                    <i class="bi bi-cake text-primary me-2"></i>Your Name and Birthday
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php 
                $account_type = $signup_process['account_type'] ?? 'user';
                
                if ($account_type === 'giftcertificate'): ?>
                    <h6 class="mb-3">Gift Buyer Information</h6>
                    <p>As the purchaser of a gift certificate, we need your name and birthday for:</p>
                    <ul>
                        <li><strong>Account Creation:</strong> Your account allows you to manage and track gift certificates</li>
                        <li><strong>Purchase Records:</strong> We'll keep a record of your gift purchases</li>
                        <li><strong>Your Own Rewards:</strong> As a Birthday Gold member, you'll also receive birthday rewards!</li>
                    </ul>
                    
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> This is YOUR information as the buyer. You'll enter the recipient's information in the next section.
                    </div>
                    
                <?php elseif ($account_type === 'parental' || $account_type === 'family'): ?>
                    <h6 class="mb-3">Parent/Guardian Information</h6>
                    <p>Please provide your information as the parent or guardian managing this account:</p>
                    <ul>
                        <li><strong>Your Name:</strong> As the primary account holder</li>
                        <li><strong>Your Birthday:</strong> Parents get birthday rewards too!</li>
                        <li><strong>Age Requirement:</strong> You must be at least 18 years old to manage a family account</li>
                    </ul>
                    <p class="mb-0">You'll add your children's information after creating your account.</p>
                    
                <?php elseif ($account_type === 'business'): ?>
                    <h6 class="mb-3">Business Account Manager</h6>
                    <p>Please provide your personal information as the business account manager:</p>
                    <ul>
                        <li><strong>Your Name:</strong> As the primary contact for this business account</li>
                        <li><strong>Your Birthday:</strong> Even business managers deserve birthday rewards!</li>
                        <li><strong>Business Name:</strong> Will be added in the account details section</li>
                    </ul>
                    
                <?php else: ?>
                    <h6 class="mb-3">Personal Information</h6>
                    <p>To get started with Birthday Gold, we need:</p>
                    <ul>
                        <li><strong>Your Full Name:</strong> This will appear on your birthday rewards and certificates</li>
                        <li><strong>Your Birthday:</strong> So we know when to celebrate with you!</li>
                        <li><strong>Age Requirement:</strong> You must be at least 13 years old to create an account</li>
                    </ul>
                    
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-gift me-2"></i>
                        <strong>Birthday Rewards:</strong> We'll automatically enroll you in birthday programs at participating businesses based on your birthdate.
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>