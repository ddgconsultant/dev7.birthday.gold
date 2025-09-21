<?php
/**
 * Gift Certificate Purchase Section with Handler
 * 
 * This section collects buyer and recipient information for gift certificates.
 * Includes delivery method selection and scheduling.
 * 
 * Expected variables:
 * - $values: Array of form values
 * - $errors: Array of validation errors
 * - $signup_process: Session data for signup process
 * - $app->formposted(): Boolean indicating if form was posted
 */

#-------------------------------------------------------------------------------
# HANDLER CODE - Process validation if flag is set
#-------------------------------------------------------------------------------
if (isset($process_handlers) && $process_handlers) {
    // Only validate gift info if this is a gift certificate account
    $account_type = $signup_process['account_type'] ?? 'user';
    
    // Debug logging
    if (isset($mode) && $mode === 'dev') {
        error_log('[GIFT_CERT] Handler executing for account type: ' . $account_type);
    }
    
    if ($account_type !== 'giftcertificate') {
        return;
    }
    
    // Recipient's name validation
    if (empty($values['recipient_firstnamefield'])) {
        $errors['recipient_firstnamefield'] = 'Recipient\'s first name is required';
    } elseif (strlen($values['recipient_firstnamefield']) < 2) {
        $errors['recipient_firstnamefield'] = 'First name must be at least 2 characters';
    }
    
    if (empty($values['recipient_lastnamefield'])) {
        $errors['recipient_lastnamefield'] = 'Recipient\'s last name is required';
    } elseif (strlen($values['recipient_lastnamefield']) < 2) {
        $errors['recipient_lastnamefield'] = 'Last name must be at least 2 characters';
    }
    
    // Recipient birthdate validation
    if (empty($values['recipient_birth_month']) || empty($values['recipient_birth_day']) || empty($values['recipient_birth_year'])) {
        $errors['recipient_birthdatefield'] = 'Please select recipient\'s complete birth date';
    } else {
        // Construct date from dropdowns
        $month = str_pad($values['recipient_birth_month'], 2, '0', STR_PAD_LEFT);
        $day = str_pad($values['recipient_birth_day'], 2, '0', STR_PAD_LEFT);
        $year = $values['recipient_birth_year'];
        
        $birthday_string = $year . '-' . $month . '-' . $day;
        $values['recipient_birthdatefield'] = $birthday_string;
        
        // Validate the date
        $birthdate = DateTime::createFromFormat('Y-m-d', $birthday_string);
        if (!$birthdate || $birthdate->format('Y-m-d') !== $birthday_string) {
            $errors['recipient_birthdatefield'] = 'Please select a valid date';
        } else {
            $today = new DateTime();
            $age = $today->diff($birthdate)->y;
            
            // Debug logging
            if (isset($mode) && $mode === 'dev') {
                error_log('[GIFT_CERT] Recipient age calculated: ' . $age . ' years');
                error_log('[GIFT_CERT] Birthdate: ' . $birthday_string);
            }
            
            // Check if birthday is in the future
            if ($birthdate > $today) {
                $errors['recipient_birthdatefield'] = 'Birthday cannot be in the future';
            } elseif ($age > 120) {
                $errors['recipient_birthdatefield'] = 'Please enter a valid birthdate';
            }
        }
    }
    
    // Delivery method validation
    if (empty($values['delivery_method'])) {
        $errors['delivery_method'] = 'Please select a delivery method';
    } else {
        $valid_methods = ['email', 'phone', 'print'];
        $selected_method = $values['delivery_method'];
        
        if (!in_array($selected_method, $valid_methods)) {
            $errors['delivery_method'] = 'Invalid delivery method selected';
        } else {
            // Validate based on selected method
            if ($selected_method === 'email') {
                // Email delivery validation
                if (empty($values['recipient_email_delivery'])) {
                    $errors['recipient_emailfield'] = 'Recipient email is required for email delivery';
                } elseif (!filter_var($values['recipient_email_delivery'], FILTER_VALIDATE_EMAIL)) {
                    $errors['recipient_emailfield'] = 'Please enter a valid email address';
                }
                
                // Store the email in the standard field for processing
                $values['recipient_emailfield'] = $values['recipient_email_delivery'] ?? '';
                
            } elseif ($selected_method === 'phone') {
                // Phone delivery validation
                if (empty($values['recipient_phone_delivery'])) {
                    $errors['recipient_phonefield'] = 'Recipient phone is required for SMS delivery';
                } else {
                    $phone = preg_replace('/\D/', '', $values['recipient_phone_delivery']);
                    if (strlen($phone) !== 10) {
                        $errors['recipient_phonefield'] = 'Please enter a valid 10-digit phone number';
                    } else {
                        // Format the phone number for storage
                        $formatted_phone = sprintf('(%s) %s-%s', 
                            substr($phone, 0, 3), 
                            substr($phone, 3, 3), 
                            substr($phone, 6, 4)
                        );
                        $values['recipient_phonefield'] = $formatted_phone;
                    }
                }
                
                // Store the phone in the standard field for processing if not already set
                if (!isset($values['recipient_phonefield'])) {
                    $values['recipient_phonefield'] = $values['recipient_phone_delivery'] ?? '';
                }
            }
            
            // Date validation for email and phone delivery
            if ($selected_method !== 'print') {
                if (empty($values['delivery_date'])) {
                    $errors['delivery_date'] = 'Please select a delivery date';
                } else {
                    $delivery_date = DateTime::createFromFormat('Y-m-d', $values['delivery_date']);
                    if (!$delivery_date) {
                        $errors['delivery_date'] = 'Please enter a valid date';
                    } else {
                        $today = new DateTime();
                        $today->setTime(0, 0, 0);
                        $delivery_date->setTime(0, 0, 0);
                        if ($delivery_date < $today) {
                            $errors['delivery_date'] = 'Delivery date cannot be in the past';
                        } elseif ($delivery_date > $today->modify('+1 year')) {
                            $errors['delivery_date'] = 'Delivery date must be within the next year';
                        }
                    }
                }
            }
        }
    }
    
    // If no errors, store the gift certificate data in session for later processing
    if (empty($errors) && $account_type === 'giftcertificate') {
        // Store recipient data in session
        $session->set('gift_recipient_firstname', $values['recipient_firstnamefield']);
        $session->set('gift_recipient_lastname', $values['recipient_lastnamefield']);
        $session->set('gift_recipient_birthdate', $values['recipient_birthdatefield']);
        $session->set('gift_recipient_email', $values['recipient_emailfield'] ?? '');
        $session->set('gift_recipient_phone', $values['recipient_phonefield'] ?? '');
        
        // Store delivery options (now single method instead of array)
        $session->set('gift_delivery_method', $selected_method);
        $session->set('gift_delivery_date', $values['delivery_date'] ?? '');
        $session->set('gift_message', $values['gift_message'] ?? '');
        
        // After successful account creation and payment, we'll store in bg_user_attributes
        // The handler after payment will create entries like:
        // type: 'gift_certificate'
        // name: 'gift_cert_[timestamp]_code', 'gift_cert_[timestamp]_recipient_name', etc.
        // string_value: the actual values
    }
    
    // Exit if only processing handlers
    return;
}

#-------------------------------------------------------------------------------
# DISPLAY CODE - Show the form section
#-------------------------------------------------------------------------------
?>
    
    <!-- Recipient Information -->
    <div class="mb-4">
        <h6 class="mb-3">Recipient Information</h6>
        <p class="text-muted small mb-3">Who will receive this birthday rewards membership</p>
        
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="text" 
                           class="form-control <?php echo !empty($errors['recipient_firstnamefield']) ? 'is-invalid' : ''; ?>" 
                           id="recipient_firstnamefield" 
                           name="recipient_firstnamefield" 
                           placeholder="First Name"
                           value="<?php echo htmlspecialchars($values['recipient_firstnamefield'] ?? ''); ?>" 
                           required>
                    <label for="recipient_firstnamefield">First Name <span class="text-danger">*</span></label>
                    <?php if (!empty($errors['recipient_firstnamefield'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['recipient_firstnamefield']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="text" 
                           class="form-control <?php echo !empty($errors['recipient_lastnamefield']) ? 'is-invalid' : ''; ?>" 
                           id="recipient_lastnamefield" 
                           name="recipient_lastnamefield" 
                           placeholder="Last Name"
                           value="<?php echo htmlspecialchars($values['recipient_lastnamefield'] ?? ''); ?>" 
                           required>
                    <label for="recipient_lastnamefield">Last Name <span class="text-danger">*</span></label>
                    <?php if (!empty($errors['recipient_lastnamefield'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['recipient_lastnamefield']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-12">
                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                <div class="row g-2 date-row">
                    <div class="col-auto">
                        <select class="form-select <?php echo !empty($errors['recipient_birthdatefield']) ? 'is-invalid' : ''; ?>" 
                                id="recipient_birth_month" 
                                name="recipient_birth_month"
                                style="width: auto; min-width: 160px;">
                            <option value="" class="text-primary">Month</option>
                            <?php
                            $months = [
                                '01' => '01 - January', '02' => '02 - February', '03' => '03 - March',
                                '04' => '04 - April', '05' => '05 - May', '06' => '06 - June',
                                '07' => '07 - July', '08' => '08 - August', '09' => '09 - September',
                                '10' => '10 - October', '11' => '11 - November', '12' => '12 - December'
                            ];
                            $selected_month = $values['recipient_birth_month'] ?? '';
                            if (empty($selected_month) && !empty($values['recipient_birthdatefield'])) {
                                $selected_month = date('m', strtotime($values['recipient_birthdatefield']));
                            }
                            foreach ($months as $value => $label) {
                                $selected = ($selected_month == $value) ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>$label</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select <?php echo !empty($errors['recipient_birthdatefield']) ? 'is-invalid' : ''; ?>" 
                                id="recipient_birth_day" 
                                name="recipient_birth_day"
                                style="width: auto; min-width: 110px;">
                            <option value="" class="text-primary">Day</option>
                            <?php
                            $selected_day = $values['recipient_birth_day'] ?? '';
                            if (empty($selected_day) && !empty($values['recipient_birthdatefield'])) {
                                $selected_day = date('d', strtotime($values['recipient_birthdatefield']));
                            }
                            for ($i = 1; $i <= 31; $i++) {
                                $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                                $selected = ($selected_day == $day) ? 'selected' : '';
                                echo "<option value=\"$day\" $selected>$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select <?php echo !empty($errors['recipient_birthdatefield']) ? 'is-invalid' : ''; ?>" 
                                id="recipient_birth_year" 
                                name="recipient_birth_year"
                                style="width: auto; min-width: 120px;">
                            <option value="" class="text-primary">Year</option>
                            <?php
                            $current_year = date('Y');
                            $selected_year = $values['recipient_birth_year'] ?? '';
                            if (empty($selected_year) && !empty($values['recipient_birthdatefield'])) {
                                $selected_year = date('Y', strtotime($values['recipient_birthdatefield']));
                            }
                            for ($i = $current_year; $i >= 1900; $i--) {
                                $selected = ($selected_year == $i) ? 'selected' : '';
                                echo "<option value=\"$i\" $selected>$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <small class="form-text text-muted">Enter the recipient's date of birth</small>
                <!-- Hidden field for combined birthday value -->
                <input type="hidden" id="recipient_birthdatefield" name="recipient_birthdatefield" value="<?php echo htmlspecialchars($values['recipient_birthdatefield'] ?? ''); ?>">
                <?php if (!empty($errors['recipient_birthdatefield'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['recipient_birthdatefield']; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <hr class="my-4">
    
    <!-- Delivery Options -->
    <div>
        <h6 class="mb-3">Delivery Options</h6>
        
        <!-- Delivery Method Tabs -->
        <div class="mb-4">
            <label class="form-label">How would you like to send the gift certificate?</label>
            <div class="delivery-method-tabs">
                <input type="radio" class="btn-check" name="delivery_method" id="delivery_email_radio" value="email" 
                       <?php echo ($values['delivery_method'] ?? '') === 'email' ? 'checked' : ''; ?>>
                <label class="btn btn-outline-primary delivery-tab" for="delivery_email_radio">
                    <i class="bi bi-envelope"></i>
                    <span>Email</span>
                </label>
                
                <input type="radio" class="btn-check" name="delivery_method" id="delivery_phone_radio" value="phone" 
                       <?php echo ($values['delivery_method'] ?? '') === 'phone' ? 'checked' : ''; ?>>
                <label class="btn btn-outline-primary delivery-tab" for="delivery_phone_radio">
                    <i class="bi bi-phone"></i>
                    <span>Text Message</span>
                </label>
                
                <input type="radio" class="btn-check" name="delivery_method" id="delivery_print_radio" value="print" 
                       <?php echo ($values['delivery_method'] ?? '') === 'print' ? 'checked' : ''; ?>>
                <label class="btn btn-outline-primary delivery-tab" for="delivery_print_radio">
                    <i class="bi bi-printer"></i>
                    <span>Print Myself</span>
                </label>
            </div>
            <?php if (!empty($errors['delivery_method'])): ?>
                <div class="text-danger small mt-1"><?php echo $errors['delivery_method']; ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Email Delivery Fields -->
        <div class="delivery-fields" id="email_delivery_fields" style="display: none;">
            <div class="row g-3">
                <div class="col-md-7">
                    <div class="form-floating">
                        <input type="email" 
                               class="form-control <?php echo !empty($errors['recipient_emailfield']) ? 'is-invalid' : ''; ?>" 
                               id="recipient_email_delivery" 
                               name="recipient_email_delivery" 
                               placeholder="recipient@email.com"
                               value="<?php echo htmlspecialchars($values['recipient_email_delivery'] ?? ''); ?>"
                               required>
                        <label for="recipient_email_delivery">Recipient's Email Address <span class="text-danger">*</span></label>
                        <?php if (!empty($errors['recipient_emailfield'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['recipient_emailfield']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="form-floating">
                        <input type="date" 
                               class="form-control <?php echo !empty($errors['delivery_date']) ? 'is-invalid' : ''; ?>" 
                               id="delivery_date_email" 
                               name="delivery_date" 
                               placeholder="Delivery Date"
                               value="<?php echo htmlspecialchars($values['delivery_date'] ?? date('Y-m-d')); ?>" 
                               min="<?php echo date('Y-m-d'); ?>"
                               max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                        <label for="delivery_date_email">Delivery Date</label>
                        <?php if (!empty($errors['delivery_date'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['delivery_date']; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($errors['delivery_date'])): ?>
                        <small class="text-muted">We'll send an email on this date</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Phone Delivery Fields -->
        <div class="delivery-fields" id="phone_delivery_fields" style="display: none;">
            <div class="row g-3">
                <div class="col-md-7">
                    <div class="form-floating">
                        <input type="tel" 
                               class="form-control <?php echo !empty($errors['recipient_phonefield']) ? 'is-invalid' : ''; ?>" 
                               id="recipient_phone_delivery" 
                               name="recipient_phone_delivery" 
                               placeholder="(123) 456-7890"
                               value="<?php echo htmlspecialchars($values['recipient_phone_delivery'] ?? ''); ?>"
                               pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}"
                               required>
                        <label for="recipient_phone_delivery">Recipient's Phone Number <span class="text-danger">*</span></label>
                        <?php if (!empty($errors['recipient_phonefield'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['recipient_phonefield']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="form-floating">
                        <input type="date" 
                               class="form-control <?php echo !empty($errors['delivery_date']) ? 'is-invalid' : ''; ?>" 
                               id="delivery_date_phone" 
                               name="delivery_date" 
                               placeholder="Delivery Date"
                               value="<?php echo htmlspecialchars($values['delivery_date'] ?? date('Y-m-d')); ?>" 
                               min="<?php echo date('Y-m-d'); ?>"
                               max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                        <label for="delivery_date_phone">Delivery Date</label>
                        <?php if (!empty($errors['delivery_date'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['delivery_date']; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($errors['delivery_date'])): ?>
                        <small class="text-muted">We'll send an SMS on this date</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Print Delivery Fields -->
        <div class="delivery-fields" id="print_delivery_fields" style="display: none;">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> You'll be able to download and print the gift certificate immediately after purchase.
            </div>
        </div>
        
        <div class="mb-3">
            <div class="form-floating">
                <textarea class="form-control" 
                          id="gift_message" 
                          name="gift_message" 
                          rows="3" 
                          maxlength="1000"
                          placeholder="Happy Birthday! Enjoy your special rewards..."
                          style="height: 100px"><?php echo htmlspecialchars($values['gift_message'] ?? ''); ?></textarea>
                <label for="gift_message">Personal Message (Optional)</label>
            </div>
            <div class="d-flex justify-content-between">
                <small class="text-muted">Add a personal message to include with the gift certificate</small>
                <small class="text-muted"><span id="message_char_count">0</span>/1000</small>
            </div>
        </div>
    </div>

<style>
/* Delivery method tabs styling */
.delivery-method-tabs {
    display: flex;
    gap: 0.5rem;
}

.delivery-tab {
    flex: 1;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    background-color: #f8f9fa; /* Light gray background for inactive state */
    border-color: #dee2e6;
    color: #495057;
}

.delivery-tab:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
}

.delivery-tab i {
    font-size: 1.25rem;
}

.delivery-tab span {
    font-size: 0.875rem;
}

/* Hide default radio buttons */
.delivery-method-tabs input[type="radio"] {
    position: absolute;
    opacity: 0;
}

/* Active state */
.delivery-method-tabs input[type="radio"]:checked + .delivery-tab {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

/* Hover state */
.delivery-tab:hover {
    background-color: rgba(13, 110, 253, 0.1);
}

.delivery-fields {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Mobile responsiveness */
@media (max-width: 576px) {
    .delivery-method-tabs {
        gap: 0.35rem;
    }
    
    .delivery-tab {
        padding: 0.5rem 0.75rem;
        gap: 0.375rem;
    }
    
    .delivery-tab i {
        font-size: 1rem;
    }
    
    .delivery-tab span {
        font-size: 0.8125rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all delivery method radio buttons
    const deliveryRadios = document.querySelectorAll('input[name="delivery_method"]');
    const deliveryFields = document.querySelectorAll('.delivery-fields');
    
    // Function to show/hide delivery fields based on selection
    function updateDeliveryFields() {
        // Hide all fields first
        deliveryFields.forEach(field => {
            field.style.display = 'none';
        });
        
        // Show the selected delivery method's fields
        const selectedMethod = document.querySelector('input[name="delivery_method"]:checked');
        if (selectedMethod) {
            const fieldId = selectedMethod.value + '_delivery_fields';
            const fieldToShow = document.getElementById(fieldId);
            if (fieldToShow) {
                fieldToShow.style.display = 'block';
            }
            
            // Update required fields based on selection
            updateRequiredFields(selectedMethod.value);
        }
    }
    
    // Function to update required fields based on delivery method
    function updateRequiredFields(method) {
        const emailField = document.getElementById('recipient_email_delivery');
        const phoneField = document.getElementById('recipient_phone_delivery');
        const dateFields = document.querySelectorAll('input[name="delivery_date"]');
        
        // Remove all required attributes first
        if (emailField) emailField.removeAttribute('required');
        if (phoneField) phoneField.removeAttribute('required');
        dateFields.forEach(field => field.removeAttribute('required'));
        
        // Set required based on method
        if (method === 'email') {
            if (emailField) emailField.setAttribute('required', '');
            const emailDateField = document.getElementById('delivery_date_email');
            if (emailDateField) emailDateField.setAttribute('required', '');
        } else if (method === 'phone') {
            if (phoneField) phoneField.setAttribute('required', '');
            const phoneDateField = document.getElementById('delivery_date_phone');
            if (phoneDateField) phoneDateField.setAttribute('required', '');
        }
        // Print method doesn't require any fields
    }
    
    // Add change event listeners to all radio buttons
    deliveryRadios.forEach(radio => {
        radio.addEventListener('change', updateDeliveryFields);
    });
    
    // Initialize on page load
    updateDeliveryFields();
    
    // If no method is selected, select email by default
    if (!document.querySelector('input[name="delivery_method"]:checked')) {
        const emailRadio = document.getElementById('delivery_email_radio');
        if (emailRadio) {
            emailRadio.checked = true;
            updateDeliveryFields();
        }
    }
    
    // Birthday dropdowns sync
    const birthMonth = document.getElementById('recipient_birth_month');
    const birthDay = document.getElementById('recipient_birth_day');
    const birthYear = document.getElementById('recipient_birth_year');
    const birthdateField = document.getElementById('recipient_birthdatefield');
    
    function updateBirthdateField() {
        if (birthMonth.value && birthDay.value && birthYear.value) {
            const month = birthMonth.value.padStart(2, '0');
            const day = birthDay.value.padStart(2, '0');
            const year = birthYear.value;
            birthdateField.value = `${year}-${month}-${day}`;
        } else {
            birthdateField.value = '';
        }
    }
    
    if (birthMonth && birthDay && birthYear) {
        birthMonth.addEventListener('change', updateBirthdateField);
        birthDay.addEventListener('change', updateBirthdateField);
        birthYear.addEventListener('change', updateBirthdateField);
    }
    
    // Phone number formatting
    const phoneInput = document.getElementById('recipient_phone_delivery');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            // Remove all non-numeric characters
            let value = e.target.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (value.length > 10) {
                value = value.substr(0, 10);
            }
            
            // Format the number
            if (value.length >= 6) {
                value = '(' + value.substr(0, 3) + ') ' + value.substr(3, 3) + '-' + value.substr(6);
            } else if (value.length >= 3) {
                value = '(' + value.substr(0, 3) + ') ' + value.substr(3);
            } else if (value.length > 0) {
                value = '(' + value;
            }
            
            e.target.value = value;
        });
        
        // Update pattern to match formatted phone
        phoneInput.setAttribute('pattern', '\\([0-9]{3}\\) [0-9]{3}-[0-9]{4}');
    }
    
    // Character counter for personal message
    const messageTextarea = document.getElementById('gift_message');
    const charCount = document.getElementById('message_char_count');
    
    if (messageTextarea && charCount) {
        // Update count on page load
        charCount.textContent = messageTextarea.value.length;
        
        // Update count on input
        messageTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            
            // Add visual feedback when approaching limit
            if (this.value.length > 900) {
                charCount.parentElement.classList.add('text-warning');
            } else {
                charCount.parentElement.classList.remove('text-warning');
            }
            
            if (this.value.length >= 1000) {
                charCount.parentElement.classList.add('text-danger');
                charCount.parentElement.classList.remove('text-warning');
            } else {
                charCount.parentElement.classList.remove('text-danger');
            }
        });
    }
});
</script>

<!-- Information Modals -->
<!-- Gift Certificate Info Modal -->
<div class="modal fade" id="giftCertificateInfoModal" tabindex="-1" aria-labelledby="giftCertificateInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="giftCertificateInfoModalLabel">
                    <i class="bi bi-gift text-primary me-2"></i>Gift Certificate Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Recipient Information</h6>
                <p>Please provide the following information about the person who will receive this gift certificate:</p>
                <ul>
                    <li><strong>Full Name:</strong> The recipient's first and last name as they would like it to appear on their account</li>
                    <li><strong>Birthdate:</strong> Enter the recipient's date of birth for their birthday rewards</li>
                </ul>
                
                <hr>
                
                <h6 class="mb-3">Delivery Options</h6>
                <p>Choose how you'd like to send the gift certificate:</p>
                <ul>
                    <li><strong>Email:</strong> We'll send the gift certificate directly to the recipient's email address on your chosen date</li>
                    <li><strong>Text Message:</strong> We'll send an SMS with the gift certificate code to the recipient's phone number</li>
                    <li><strong>Print Myself:</strong> You'll receive a printable gift certificate immediately after purchase that you can give in person</li>
                </ul>
                
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> The recipient will need to create their own account using the gift certificate code to activate their membership.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<!-- Additional Options Info Modal -->
<div class="modal fade" id="additionalOptionsInfoModal" tabindex="-1" aria-labelledby="additionalOptionsInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="additionalOptionsInfoModalLabel">
                    <i class="bi bi-gear text-primary me-2"></i>Additional Options
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Promo Codes</h6>
                <p>If you have a promotional code, enter it here to apply any available discounts to your purchase.</p>
                
                <hr>
                
                <h6 class="mb-3">Referral Codes</h6>
                <p>If someone referred you to Birthday Gold, enter their referral code to give them credit for the referral.</p>
                
                <hr>
                
                <h6 class="mb-3">Terms & Privacy</h6>
                <p>By creating an account, you agree to our Terms of Service and Privacy Policy. These documents outline:</p>
                <ul>
                    <li>How we protect your personal information</li>
                    <li>Your rights and responsibilities as a member</li>
                    <li>How the birthday rewards program works</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>