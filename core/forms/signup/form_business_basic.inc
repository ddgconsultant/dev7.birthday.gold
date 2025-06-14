<?php
/**
 * Business Basic Signup Form Display
 * 
 * Variables available:
 * - $values: Array of form values (for pre-filling)
 * - $errors: Array of validation errors
 * - $signup_process: Session data from step 1
 */

// Get values
$business_name = $values['business_name'] ?? '';
$business_type = $values['business_type'] ?? '';
$tax_id = $values['tax_id'] ?? '';
$num_locations = $values['num_locations'] ?? '1';
$website = $values['website'] ?? '';
$contact_firstname = $values['contact_firstname'] ?? '';
$contact_lastname = $values['contact_lastname'] ?? '';
$contact_title = $values['contact_title'] ?? '';
$email = $values['email'] ?? '';
$phone = $values['phone'] ?? '';
$newsletter = $values['newsletter'] ?? 1;

// Business types
$business_types = [
    'restaurant' => 'Restaurant',
    'retail' => 'Retail Store',
    'service' => 'Service Provider',
    'entertainment' => 'Entertainment',
    'health_beauty' => 'Health & Beauty',
    'other' => 'Other'
];

// Location options
$location_options = [
    '1' => '1 Location',
    '2-5' => '2-5 Locations',
    '6-10' => '6-10 Locations',
    '11-50' => '11-50 Locations',
    '50+' => '50+ Locations'
];
?>

<!-- Business Information Section -->
<div class="form-section">
    <h5 class="section-title">Business Information</h5>
    <div class="row">
        <!-- Business Name -->
        <div class="col-12 mb-3">
            <label for="business_name" class="form-label">Business Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['business_name']) ? 'is-invalid' : ''; ?>" 
                   id="business_name" name="business_name" 
                   value="<?php echo htmlspecialchars($business_name); ?>" required>
            <?php if (isset($errors['business_name'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['business_name']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Business Type -->
        <div class="col-md-6 mb-3">
            <label for="business_type" class="form-label">Business Type <span class="text-danger">*</span></label>
            <select class="form-select <?php echo isset($errors['business_type']) ? 'is-invalid' : ''; ?>" 
                    id="business_type" name="business_type" required>
                <option value="">Select business type</option>
                <?php foreach ($business_types as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $business_type === $value ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['business_type'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['business_type']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Tax ID -->
        <div class="col-md-6 mb-3">
            <label for="tax_id" class="form-label">EIN/Tax ID</label>
            <input type="text" class="form-control <?php echo isset($errors['tax_id']) ? 'is-invalid' : ''; ?>" 
                   id="tax_id" name="tax_id" placeholder="XX-XXXXXXX"
                   value="<?php echo htmlspecialchars($tax_id); ?>">
            <?php if (isset($errors['tax_id'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['tax_id']); ?></div>
            <?php endif; ?>
            <small class="text-muted">Federal Employer Identification Number</small>
        </div>
        
        <!-- Number of Locations -->
        <div class="col-md-6 mb-3">
            <label for="num_locations" class="form-label">Number of Locations <span class="text-danger">*</span></label>
            <select class="form-select <?php echo isset($errors['num_locations']) ? 'is-invalid' : ''; ?>" 
                    id="num_locations" name="num_locations" required>
                <?php foreach ($location_options as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $num_locations === $value ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['num_locations'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['num_locations']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Website -->
        <div class="col-md-6 mb-3">
            <label for="website" class="form-label">Website</label>
            <input type="text" class="form-control <?php echo isset($errors['website']) ? 'is-invalid' : ''; ?>" 
                   id="website" name="website" placeholder="https://www.example.com"
                   value="<?php echo htmlspecialchars($website); ?>">
            <?php if (isset($errors['website'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['website']); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Contact Information Section -->
<div class="form-section">
    <h5 class="section-title">Contact Information</h5>
    <div class="row">
        <!-- Contact First Name -->
        <div class="col-md-6 mb-3">
            <label for="contact_firstname" class="form-label">Contact First Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['contact_firstname']) ? 'is-invalid' : ''; ?>" 
                   id="contact_firstname" name="contact_firstname" 
                   value="<?php echo htmlspecialchars($contact_firstname); ?>" required>
            <?php if (isset($errors['contact_firstname'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['contact_firstname']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Contact Last Name -->
        <div class="col-md-6 mb-3">
            <label for="contact_lastname" class="form-label">Contact Last Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['contact_lastname']) ? 'is-invalid' : ''; ?>" 
                   id="contact_lastname" name="contact_lastname" 
                   value="<?php echo htmlspecialchars($contact_lastname); ?>" required>
            <?php if (isset($errors['contact_lastname'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['contact_lastname']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Title -->
        <div class="col-md-6 mb-3">
            <label for="contact_title" class="form-label">Title/Position</label>
            <input type="text" class="form-control <?php echo isset($errors['contact_title']) ? 'is-invalid' : ''; ?>" 
                   id="contact_title" name="contact_title" placeholder="e.g., Marketing Manager"
                   value="<?php echo htmlspecialchars($contact_title); ?>">
            <?php if (isset($errors['contact_title'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['contact_title']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Business Email -->
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Business Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                   id="email" name="email" placeholder="contact@business.com"
                   value="<?php echo htmlspecialchars($email); ?>" required>
            <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Business Phone -->
        <div class="col-md-6 mb-3">
            <label for="phone" class="form-label">Business Phone <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">+1</span>
                <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                       id="phone" name="phone" placeholder="(555) 123-4567" required
                       value="<?php echo htmlspecialchars($phone); ?>">
            </div>
            <?php if (isset($errors['phone'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['phone']); ?></div>
            <?php endif; ?>
        </div>
        
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
            <small class="text-muted">At least 8 characters</small>
        </div>
    </div>
</div>

<!-- Newsletter Opt-in -->
<div class="form-section">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" value="1" <?php echo $newsletter ? 'checked' : ''; ?>>
        <label class="form-check-label" for="newsletter">
            Send me birthday reward program updates and special offers
        </label>
    </div>
</div>

<!-- Hidden birthday field (businesses don't have birthdays but the system requires it) -->
<input type="hidden" name="birthday" value="2000-01-01">

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
    
    // Tax ID formatting
    const taxIdInput = document.getElementById('tax_id');
    if (taxIdInput) {
        taxIdInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.slice(0, 2) + '-' + value.slice(2, 9);
            }
            e.target.value = value;
        });
    }
    
    // Website URL auto-prefix
    const websiteInput = document.getElementById('website');
    if (websiteInput) {
        websiteInput.addEventListener('blur', function(e) {
            let value = e.target.value.trim();
            if (value && !value.match(/^https?:\/\//)) {
                e.target.value = 'https://' + value;
            }
        });
    }
})();
</script>