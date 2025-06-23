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
# HANDLER CODE - Process validation if form was posted
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    // Only validate gift info if this is a gift certificate account
    $account_type = $signup_process['account_type'] ?? 'user';
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
    if (empty($values['recipient_birthdatefield'])) {
        $errors['recipient_birthdatefield'] = 'Recipient\'s birthdate is required';
    } else {
        $birthdate = DateTime::createFromFormat('Y-m-d', $values['recipient_birthdatefield']);
        if (!$birthdate) {
            $errors['recipient_birthdatefield'] = 'Please enter a valid date';
        } else {
            $today = new DateTime();
            $age = $today->diff($birthdate)->y;
            if ($age < 13) {
                $errors['recipient_birthdatefield'] = 'Recipient must be at least 13 years old';
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
                    }
                }
                
                // Store the phone in the standard field for processing
                $values['recipient_phonefield'] = $values['recipient_phone_delivery'] ?? '';
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
}

#-------------------------------------------------------------------------------
# DISPLAY CODE - Show the form section
#-------------------------------------------------------------------------------
?>
</div><!-- Close Account Information Section -->

<!-- Gift Certificate Details Section (Recipient + Delivery combined) -->
<div class="form-section">
    <h5 class="section-title d-flex justify-content-between align-items-center">
        Gift Certificate Details
        <i class="bi bi-info-circle text-muted" 
           style="font-size: 1rem; cursor: pointer;" 
           data-bs-toggle="modal" 
           data-bs-target="#giftCertificateInfoModal"
           title="Click for more information"></i>
    </h5>
    
    <!-- Recipient Information -->
    <div class="mb-4">
        <h6 class="mb-3">Recipient Information</h6>
        <p class="text-muted small mb-3">Who will receive this birthday rewards membership</p>
        
        <div class="row g-3">
            <div class="col-12">
                <label for="recipient_firstnamefield" class="form-label">First Name</label>
                <input type="text" 
                       class="form-control" 
                       id="recipient_firstnamefield" 
                       name="recipient_firstnamefield" 
                       value="<?php echo htmlspecialchars($values['recipient_firstnamefield'] ?? ''); ?>" 
                       required>
                <?php if (!empty($errors['recipient_firstnamefield'])): ?>
                    <div class="text-danger small mt-1"><?php echo $errors['recipient_firstnamefield']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="col-12">
                <label for="recipient_lastnamefield" class="form-label">Last Name</label>
                <input type="text" 
                       class="form-control" 
                       id="recipient_lastnamefield" 
                       name="recipient_lastnamefield" 
                       value="<?php echo htmlspecialchars($values['recipient_lastnamefield'] ?? ''); ?>" 
                       required>
                <?php if (!empty($errors['recipient_lastnamefield'])): ?>
                    <div class="text-danger small mt-1"><?php echo $errors['recipient_lastnamefield']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="col-12">
                <label for="recipient_birthdatefield" class="form-label">Birthdate</label>
                <input type="date" 
                       class="form-control" 
                       id="recipient_birthdatefield" 
                       name="recipient_birthdatefield" 
                       value="<?php echo htmlspecialchars($values['recipient_birthdatefield'] ?? ''); ?>" 
                       max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>"
                       required>
                <?php if (!empty($errors['recipient_birthdatefield'])): ?>
                    <div class="text-danger small mt-1"><?php echo $errors['recipient_birthdatefield']; ?></div>
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
                    <label for="recipient_email_delivery" class="form-label">Recipient's Email Address</label>
                    <input type="email" 
                           class="form-control" 
                           id="recipient_email_delivery" 
                           name="recipient_email_delivery" 
                           value="<?php echo htmlspecialchars($values['recipient_emailfield'] ?? ''); ?>" 
                           placeholder="recipient@email.com">
                    <?php if (!empty($errors['recipient_emailfield'])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors['recipient_emailfield']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-5">
                    <label for="delivery_date_email" class="form-label">Delivery Date</label>
                    <input type="date" 
                           class="form-control" 
                           id="delivery_date_email" 
                           name="delivery_date" 
                           value="<?php echo htmlspecialchars($values['delivery_date'] ?? date('Y-m-d')); ?>" 
                           min="<?php echo date('Y-m-d'); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                    <?php if (!empty($errors['delivery_date'])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors['delivery_date']; ?></div>
                    <?php else: ?>
                        <small class="text-muted">We'll send an email on this date</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Phone Delivery Fields -->
        <div class="delivery-fields" id="phone_delivery_fields" style="display: none;">
            <div class="row g-3">
                <div class="col-md-7">
                    <label for="recipient_phone_delivery" class="form-label">Recipient's Phone Number</label>
                    <input type="tel" 
                           class="form-control" 
                           id="recipient_phone_delivery" 
                           name="recipient_phone_delivery" 
                           value="<?php echo htmlspecialchars($values['recipient_phonefield'] ?? ''); ?>" 
                           placeholder="(123) 456-7890">
                    <?php if (!empty($errors['recipient_phonefield'])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors['recipient_phonefield']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-5">
                    <label for="delivery_date_phone" class="form-label">Delivery Date</label>
                    <input type="date" 
                           class="form-control" 
                           id="delivery_date_phone" 
                           name="delivery_date" 
                           value="<?php echo htmlspecialchars($values['delivery_date'] ?? date('Y-m-d')); ?>" 
                           min="<?php echo date('Y-m-d'); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                    <?php if (!empty($errors['delivery_date'])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors['delivery_date']; ?></div>
                    <?php else: ?>
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
            <label for="gift_message" class="form-label">Personal Message (Optional)</label>
            <textarea class="form-control" 
                      id="gift_message" 
                      name="gift_message" 
                      rows="3" 
                      maxlength="1000"
                      placeholder="Happy Birthday! Enjoy your special rewards..."><?php echo htmlspecialchars($values['gift_message'] ?? ''); ?></textarea>
            <div class="d-flex justify-content-between">
                <small class="text-muted">Add a personal message to include with the gift certificate</small>
                <small class="text-muted"><span id="message_char_count">0</span>/1000</small>
            </div>
        </div>
    </div>
</div>

<!-- Open the Additional Options Section for promo/opt-in -->
<div class="form-section">
    <h5 class="section-title d-flex justify-content-between align-items-center">
        Additional Options
        <i class="bi bi-info-circle text-muted" 
           style="font-size: 1rem; cursor: pointer;" 
           data-bs-toggle="modal" 
           data-bs-target="#additionalOptionsInfoModal"
           title="Click for more information"></i>
    </h5>

<style>
/* Delivery method tabs styling */
.delivery-method-tabs {
    display: flex;
    gap: 0.5rem;
}

.delivery-tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 1rem 0.5rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.delivery-tab i {
    font-size: 1.5rem;
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
        padding: 0.75rem 0.25rem;
    }
    
    .delivery-tab i {
        font-size: 1.25rem;
    }
    
    .delivery-tab span {
        font-size: 0.75rem;
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
                    <li><strong>Birthdate:</strong> Must be at least 13 years old to receive a Birthday Gold membership</li>
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