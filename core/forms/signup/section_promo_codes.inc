<?php
/**
 * Promo and Referral Codes Section with Handler
 * 
 * This section handles promo code and referral code inputs and validation.
 * Uses the existing Birthday Gold UI patterns from createaccount.php
 * 
 * Expected variables:
 * - $values: Array of form values
 * - $errors: Array of validation errors
 * - $signup_process: Session data for signup process
 * - $process_handlers: Boolean flag indicating if handlers should process
 * - $productManager: ProductManager instance for promo validation (optional)
 */

#-------------------------------------------------------------------------------
# HANDLER CODE - Process validation if flag is set
#-------------------------------------------------------------------------------
if (isset($process_handlers) && $process_handlers) {
    // Promo code validation (if provided)
    if (!empty($values['promo_code'])) {
        // Basic format validation
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $values['promo_code'])) {
            $errors['promo_code'] = 'Invalid promo code format';
        } else {
            // If we have productManager instance, validate the promo code
            if (isset($productManager) && isset($signup_process['account_plan_id'])) {
                $validation = $productManager->validatePromoCode($values['promo_code'], $signup_process['account_plan_id']);
                
                if (!$validation['valid']) {
                    $errors['promo_code'] = $validation['message'] ?? 'Invalid promo code';
                } else {
                    // Store validation result for later use
                    $values['promo_validation'] = $validation;
                }
            }
        }
    }
    
    // Referral code validation (if provided)
    if (!empty($values['referral_code'])) {
        // Basic format validation
        if (!preg_match('/^[A-Za-z0-9]+$/', $values['referral_code'])) {
            $errors['referral_code'] = 'Invalid referral code format';
        } else {
            // Additional validation could be done here
            // e.g., check if referral code exists in database
            // For now, we'll just accept valid format
        }
    }
    
    // Validate terms acceptance
    if (empty($values['terms'])) {
        $errors['terms'] = 'You must agree to the Terms and Privacy Policy';
    }
    
    // Exit if only processing handlers
    return;
}

#-------------------------------------------------------------------------------
# DISPLAY CODE - Show the form section
#-------------------------------------------------------------------------------
// Get promo and referral codes from values or signup process (prioritize POST values for form resubmission)
$promo_code = $values['promo_code'] ?? $signup_process['promo_code'] ?? $signup_process['promo'] ?? '';
$referral_code = $values['referral_code'] ?? $signup_process['referral_code'] ?? $signup_process['ref'] ?? $signup_process['referral'] ?? '';

// Check if we should auto-show promo/referral section
$show_promo_section = !empty($promo_code) || !empty($referral_code) || !empty($errors['promo_code']) || !empty($errors['referral_code']);
?>

<!-- Promo & Referral Codes (continues Account Information section) -->
    <!-- Collapsible Promo/Referral Section -->
    <div class="promo-referral-section mt-4">
        <!-- Desktop header (hidden on mobile) -->
        <div class="promo-referral-header d-none d-md-flex" id="togglePromoReferral">
            <span>
                <i class="bi bi-gift me-1"></i>
                <strong>Promo or Referral Code?</strong>
            </span>
            <i class="bi bi-chevron-<?php echo $show_promo_section ? 'up' : 'down'; ?>" id="promoReferralChevron"></i>
        </div>
        
        <!-- Mobile header (shown only on mobile) -->
        <div class="promo-referral-header-mobile d-md-none" id="togglePromoReferralMobile">
            <span class="<?php echo $show_promo_section ? 'text-black' : 'text-muted'; ?>">Add promo or referral code</span>
            <i class="bi bi-<?php echo $show_promo_section ? 'dash-circle' : 'plus-circle'; ?> fs-5 text-primary"></i>
        </div>
        
        <div class="promo-referral-content collapse <?php echo $show_promo_section ? 'show' : ''; ?>" id="promoReferralSection">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="form-floating flex-grow-1">
                            <input type="text" 
                                   class="form-control <?php echo !empty($errors['promo_code']) ? 'is-invalid' : ''; ?>" 
                                   id="promo_code" 
                                   name="promo_code" 
                                   placeholder="Promo Code" 
                                   value="<?php echo htmlspecialchars($promo_code); ?>">
                            <label for="promo_code">Promo Code</label>
                        </div>
                        <button type="button" class="btn btn-primary-subtle" id="applyPromo">
                            <i class="bi bi-check-circle me-1"></i>Apply
                        </button>
                    </div>
                    <div id="promoMessage" class="mt-1">
                        <?php if (!empty($errors['promo_code'])): ?>
                            <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['promo_code']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="form-floating flex-grow-1">
                            <input type="text" 
                                   class="form-control <?php echo !empty($errors['referral_code']) ? 'is-invalid' : ''; ?>" 
                                   id="referral_code" 
                                   name="referral_code" 
                                   placeholder="Referral Code" 
                                   value="<?php echo htmlspecialchars($referral_code); ?>">
                            <label for="referral_code">Referral Code</label>
                        </div>
                        <button type="button" class="btn btn-primary-subtle" id="verifyReferral">
                            <i class="bi bi-check-circle me-1"></i>Verify
                        </button>
                    </div>
                    <div id="referralMessage" class="mt-1">
                        <?php if (!empty($errors['referral_code'])): ?>
                            <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['referral_code']); ?></div>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">Enter the code you were provided</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Terms and Privacy Agreement -->
    <div class="mt-3">
        <div class="form-check">
            <input type="checkbox" 
                   class="form-check-input <?php echo !empty($errors['terms']) ? 'is-invalid' : ''; ?>" 
                   id="terms" 
                   name="terms" 
                   value="1" 
                   <?php echo (!empty($values['terms'])) ? 'checked' : ''; ?>>
            <label class="form-check-label text-black" for="terms">
                I agree to the <a href="/legalhub/terms" class="text-underline">Terms</a> and 
                <a href="/legalhub/privacy" class="text-underline">Privacy Policy</a>
            </label>
            <?php if (!empty($errors['terms'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['terms']); ?></div>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div> <!-- Close card -->