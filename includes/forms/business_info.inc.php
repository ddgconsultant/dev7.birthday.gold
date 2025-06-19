<?php
/**
 * Business Information Form Module
 * Business-specific fields for business accounts
 * 
 * Expected variables:
 * - $_POST: form submission data
 * - $errors: array of validation errors
 */
?>

<!-- Business Account Fields -->
<div class="form-section">
    <h5 class="section-title">Business Details</h5>
    
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="business_name" class="form-label">Business Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="business_name" name="business_name" 
                   value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>"
                   placeholder="Your Business Name">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="business_type" class="form-label">Business Type</label>
            <select class="form-control" id="business_type" name="business_type">
                <option value="">Select Type</option>
                <option value="restaurant" <?php echo (($_POST['business_type'] ?? '') == 'restaurant') ? 'selected' : ''; ?>>Restaurant</option>
                <option value="retail" <?php echo (($_POST['business_type'] ?? '') == 'retail') ? 'selected' : ''; ?>>Retail Store</option>
                <option value="service" <?php echo (($_POST['business_type'] ?? '') == 'service') ? 'selected' : ''; ?>>Service Business</option>
                <option value="entertainment" <?php echo (($_POST['business_type'] ?? '') == 'entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                <option value="other" <?php echo (($_POST['business_type'] ?? '') == 'other') ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="business_phone" class="form-label">Business Phone</label>
            <div class="input-group">
                <span class="input-group-text">+1</span>
                <input type="text" class="form-control" id="business_phone" name="business_phone" 
                       value="<?php echo htmlspecialchars($_POST['business_phone'] ?? ''); ?>"
                       placeholder="555-123-4567"
                       inputmode="tel">
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="business_website" class="form-label">Business Website</label>
            <input type="url" class="form-control" id="business_website" name="business_website" 
                   value="<?php echo htmlspecialchars($_POST['business_website'] ?? ''); ?>"
                   placeholder="https://www.yourbusiness.com">
            <small class="text-muted">Optional - We'll link to your business in our directory</small>
        </div>
    </div>
</div>