<?php
/**
 * Organization Information Form Module
 * Organization-specific fields for non-profit/organization accounts
 * 
 * Expected variables:
 * - $_POST: form submission data
 * - $errors: array of validation errors
 */
?>

<!-- Organization Account Fields -->
<div class="form-section">
    <h5 class="section-title">Organization Details</h5>
    
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="org_name" class="form-label">Organization Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="org_name" name="org_name" 
                   value="<?php echo htmlspecialchars($_POST['org_name'] ?? ''); ?>"
                   placeholder="Your Organization Name">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="org_type" class="form-label">Organization Type <span class="text-danger">*</span></label>
            <select class="form-control" id="org_type" name="org_type">
                <option value="">Select Type</option>
                <option value="nonprofit" <?php echo (($_POST['org_type'] ?? '') == 'nonprofit') ? 'selected' : ''; ?>>Non-Profit (501c3)</option>
                <option value="school" <?php echo (($_POST['org_type'] ?? '') == 'school') ? 'selected' : ''; ?>>School/Educational</option>
                <option value="church" <?php echo (($_POST['org_type'] ?? '') == 'church') ? 'selected' : ''; ?>>Religious Organization</option>
                <option value="club" <?php echo (($_POST['org_type'] ?? '') == 'club') ? 'selected' : ''; ?>>Club/Association</option>
                <option value="government" <?php echo (($_POST['org_type'] ?? '') == 'government') ? 'selected' : ''; ?>>Government Agency</option>
                <option value="other" <?php echo (($_POST['org_type'] ?? '') == 'other') ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="tax_id" class="form-label">Tax ID/EIN</label>
            <input type="text" class="form-control" id="tax_id" name="tax_id" 
                   value="<?php echo htmlspecialchars($_POST['tax_id'] ?? ''); ?>"
                   placeholder="XX-XXXXXXX">
            <small class="text-muted">For verification purposes (optional)</small>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="member_count" class="form-label">Estimated Number of Members</label>
            <select class="form-control" id="member_count" name="member_count">
                <option value="">Select Range</option>
                <option value="1-10" <?php echo (($_POST['member_count'] ?? '') == '1-10') ? 'selected' : ''; ?>>1-10</option>
                <option value="11-50" <?php echo (($_POST['member_count'] ?? '') == '11-50') ? 'selected' : ''; ?>>11-50</option>
                <option value="51-100" <?php echo (($_POST['member_count'] ?? '') == '51-100') ? 'selected' : ''; ?>>51-100</option>
                <option value="101-500" <?php echo (($_POST['member_count'] ?? '') == '101-500') ? 'selected' : ''; ?>>101-500</option>
                <option value="500+" <?php echo (($_POST['member_count'] ?? '') == '500+') ? 'selected' : ''; ?>>500+</option>
            </select>
            <small class="text-muted">Helps us provide appropriate features and support</small>
        </div>
    </div>
</div>