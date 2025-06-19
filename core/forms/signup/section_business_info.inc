<?php
/**
 * Business Information Section with Handler
 * 
 * This section collects and validates business details for business accounts.
 * Uses the existing Birthday Gold UI patterns
 * 
 * Expected variables:
 * - $values: Array of form values
 * - $errors: Array of validation errors
 * - $signup_process: Session data for signup process
 * - $process_handlers: Boolean flag indicating if handlers should process
 */

#-------------------------------------------------------------------------------
# HANDLER CODE - Process validation if flag is set
#-------------------------------------------------------------------------------
if (isset($process_handlers) && $process_handlers) {
    // Only validate business info if this is a business account
    $account_type = $signup_process['account_type'] ?? 'user';
    if ($account_type !== 'business') {
        return;
    }
    
    // Business name validation
    if (empty($values['businessnamefield'])) {
        $errors['businessnamefield'] = 'Business name is required';
    } elseif (strlen($values['businessnamefield']) < 2) {
        $errors['businessnamefield'] = 'Business name must be at least 2 characters';
    } elseif (strlen($values['businessnamefield']) > 100) {
        $errors['businessnamefield'] = 'Business name must be less than 100 characters';
    }
    
    // Business type validation
    if (empty($values['businesstypefield'])) {
        $errors['businesstypefield'] = 'Please select a business type';
    } else {
        $valid_types = ['restaurant', 'retail', 'service', 'entertainment', 'fitness', 'other'];
        if (!in_array($values['businesstypefield'], $valid_types)) {
            $errors['businesstypefield'] = 'Please select a valid business type';
        }
    }
    
    // Business phone validation (optional but validate format if provided)
    if (!empty($values['businessphonefield'])) {
        $phone = preg_replace('/\D/', '', $values['businessphonefield']);
        if (strlen($phone) !== 10) {
            $errors['businessphonefield'] = 'Please enter a valid 10-digit phone number';
        }
    }
    
    // Business address validation (optional)
    if (!empty($values['businessaddressfield'])) {
        if (strlen($values['businessaddressfield']) < 5) {
            $errors['businessaddressfield'] = 'Please enter a complete address';
        } elseif (strlen($values['businessaddressfield']) > 200) {
            $errors['businessaddressfield'] = 'Address is too long';
        }
    }
    
    // Business website validation (optional)
    if (!empty($values['businesswebsitefield'])) {
        // Basic URL validation
        if (!filter_var($values['businesswebsitefield'], FILTER_VALIDATE_URL)) {
            // Try adding https:// if missing
            $url_with_protocol = 'https://' . $values['businesswebsitefield'];
            if (!filter_var($url_with_protocol, FILTER_VALIDATE_URL)) {
                $errors['businesswebsitefield'] = 'Please enter a valid website URL';
            }
        }
    }
    
    // Business owner confirmation
    if (empty($values['businessownerfield'])) {
        $errors['businessownerfield'] = 'You must confirm you are authorized to represent this business';
    }
    
    // Exit if only processing handlers
    return;
}

#-------------------------------------------------------------------------------
# DISPLAY CODE - Show the form section
#-------------------------------------------------------------------------------
?>
<div class="bg-white rounded-3 shadow-sm p-4 mb-4">
    <h4 class="mb-3">Business Information</h4>
    
    <div class="row g-3">
        <div class="col-md-12">
            <label for="businessnamefield" class="form-label">Business Name</label>
            <input type="text" 
                   class="form-control" 
                   id="businessnamefield" 
                   name="businessnamefield" 
                   value="<?php echo htmlspecialchars($values['businessnamefield'] ?? ''); ?>" 
                   required>
            <?php if (!empty($errors['businessnamefield'])): ?>
                <div class="text-danger small mt-1"><?php echo $errors['businessnamefield']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <label for="businesstypefield" class="form-label">Business Type</label>
            <select class="form-select" 
                    id="businesstypefield" 
                    name="businesstypefield" 
                    required>
                <option value="">Select business type...</option>
                <option value="restaurant" <?php echo ($values['businesstypefield'] ?? '') === 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                <option value="retail" <?php echo ($values['businesstypefield'] ?? '') === 'retail' ? 'selected' : ''; ?>>Retail Store</option>
                <option value="service" <?php echo ($values['businesstypefield'] ?? '') === 'service' ? 'selected' : ''; ?>>Service Business</option>
                <option value="entertainment" <?php echo ($values['businesstypefield'] ?? '') === 'entertainment' ? 'selected' : ''; ?>>Entertainment Venue</option>
                <option value="fitness" <?php echo ($values['businesstypefield'] ?? '') === 'fitness' ? 'selected' : ''; ?>>Fitness/Wellness</option>
                <option value="other" <?php echo ($values['businesstypefield'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
            <?php if (!empty($errors['businesstypefield'])): ?>
                <div class="text-danger small mt-1"><?php echo $errors['businesstypefield']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <label for="businessphonefield" class="form-label">Business Phone</label>
            <input type="tel" 
                   class="form-control" 
                   id="businessphonefield" 
                   name="businessphonefield" 
                   value="<?php echo htmlspecialchars($values['businessphonefield'] ?? ''); ?>" 
                   placeholder="(123) 456-7890">
            <?php if (!empty($errors['businessphonefield'])): ?>
                <div class="text-danger small mt-1"><?php echo $errors['businessphonefield']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-12">
            <label for="businessaddressfield" class="form-label">Business Address</label>
            <input type="text" 
                   class="form-control" 
                   id="businessaddressfield" 
                   name="businessaddressfield" 
                   value="<?php echo htmlspecialchars($values['businessaddressfield'] ?? ''); ?>" 
                   placeholder="123 Main St, City, State ZIP">
            <?php if (!empty($errors['businessaddressfield'])): ?>
                <div class="text-danger small mt-1"><?php echo $errors['businessaddressfield']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-12">
            <label for="businesswebsitefield" class="form-label">Website (Optional)</label>
            <input type="url" 
                   class="form-control" 
                   id="businesswebsitefield" 
                   name="businesswebsitefield" 
                   value="<?php echo htmlspecialchars($values['businesswebsitefield'] ?? ''); ?>" 
                   placeholder="https://www.example.com">
            <?php if (!empty($errors['businesswebsitefield'])): ?>
                <div class="text-danger small mt-1"><?php echo $errors['businesswebsitefield']; ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-3">
        <div class="form-check">
            <input class="form-check-input" 
                   type="checkbox" 
                   id="businessownerfield" 
                   name="businessownerfield" 
                   value="1"
                   <?php echo !empty($values['businessownerfield']) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="businessownerfield">
                I am the owner or authorized representative of this business
            </label>
            <?php if (!empty($errors['businessownerfield'])): ?>
                <div class="text-danger small mt-1"><?php echo $errors['businessownerfield']; ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>