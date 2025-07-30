<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Location data should be available from parent page
if (!isset($location) || !is_array($location) || !isset($location_id)) {
    echo '<div class="alert alert-danger">Location data not available</div>';
    exit;
}

// Get location enrollment settings - check if table exists first
$enrollment_settings = [];
try {
    $settings_sql = "SELECT * FROM bg_location_enrollment_settings 
                    WHERE location_id = :location_id";
    $settings_stmt = $database->prepare($settings_sql);
    $settings_stmt->execute(['location_id' => $location_id]);
    $enrollment_settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table doesn't exist, use empty array
    $enrollment_settings = [];
}

// Get company enrollment URL
$company_signup_url = '';
try {
    $company_sql = "SELECT signup_url FROM bg_companies WHERE company_id = :company_id";
    $company_stmt = $database->prepare($company_sql);
    $company_stmt->execute(['company_id' => $company_id]);
    $company_signup_url = $company_stmt->fetchColumn();
} catch (Exception $e) {
    // Error getting company signup URL
    $company_signup_url = '';
}
?>

<div class="container-fluid px-0">
    <h3 class="mb-4">Enrollment Settings</h3>
    
    <form id="enrollmentSettingsForm" method="POST" action="/admin/ajax/save-location-enrollment.php">
        <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
        
        <!-- Enrollment URL Override -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Location-Specific Enrollment URL</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Override the company-wide enrollment URL for this specific location. 
                    Leave blank to use the company default.
                </p>
                
                <div class="mb-3">
                    <label class="form-label">Company Default URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($company_signup_url ?? 'Not set'); ?>" readonly>
                        <a href="<?php echo htmlspecialchars($company_signup_url ?? '#'); ?>" 
                           class="btn btn-outline-secondary" target="_blank" 
                           <?php echo empty($company_signup_url) ? 'disabled' : ''; ?>>
                            <i class="bi bi-box-arrow-up-right"></i> Visit
                        </a>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Location Override URL</label>
                    <input type="url" class="form-control" name="enrollment_url" 
                           value="<?php echo htmlspecialchars($enrollment_settings['enrollment_url'] ?? ''); ?>"
                           placeholder="https://example.com/signup?location=12345">
                    <small class="form-text text-muted">
                        Use this if the location has a unique signup page or requires special parameters
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Enrollment Options -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Enrollment Options</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="allowOnlineEnrollment" 
                                   name="allow_online_enrollment" value="1"
                                   <?php echo ($enrollment_settings['allow_online_enrollment'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="allowOnlineEnrollment">
                                Allow Online Enrollment
                                <small class="d-block text-muted">Customers can sign up online for this location</small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="allowInStoreEnrollment" 
                                   name="allow_instore_enrollment" value="1"
                                   <?php echo ($enrollment_settings['allow_instore_enrollment'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="allowInStoreEnrollment">
                                Allow In-Store Enrollment
                                <small class="d-block text-muted">Customers can sign up at the physical location</small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="requireStoreCode" 
                                   name="require_store_code" value="1"
                                   <?php echo ($enrollment_settings['require_store_code'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="requireStoreCode">
                                Require Store Code
                                <small class="d-block text-muted">Customers must enter a location code during signup</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Store Code</label>
                            <input type="text" class="form-control" name="store_code" 
                                   value="<?php echo htmlspecialchars($enrollment_settings['store_code'] ?? ''); ?>"
                                   placeholder="e.g., STORE123">
                            <small class="form-text text-muted">
                                Unique code for this location used during enrollment
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">QR Code Redirect</label>
                            <input type="url" class="form-control" name="qr_code_url" 
                                   value="<?php echo htmlspecialchars($enrollment_settings['qr_code_url'] ?? ''); ?>"
                                   placeholder="https://example.com/signup-qr">
                            <small class="form-text text-muted">
                                Special URL for QR code signups at this location
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enrollment Requirements -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Location-Specific Requirements</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">Required Fields</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="reqPhone" 
                                   name="requirements[phone]" value="1"
                                   <?php echo ($enrollment_settings['require_phone'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="reqPhone">Phone Number</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="reqEmail" 
                                   name="requirements[email]" value="1"
                                   <?php echo ($enrollment_settings['require_email'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="reqEmail">Email Address</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="reqBirthdate" 
                                   name="requirements[birthdate]" value="1"
                                   <?php echo ($enrollment_settings['require_birthdate'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="reqBirthdate">Birth Date</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="reqAddress" 
                                   name="requirements[address]" value="1"
                                   <?php echo ($enrollment_settings['require_address'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="reqAddress">Full Address</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="mb-3">Age Requirements</h6>
                        <div class="mb-3">
                            <label class="form-label">Minimum Age</label>
                            <input type="number" class="form-control" name="minimum_age" 
                                   value="<?php echo htmlspecialchars($enrollment_settings['minimum_age'] ?? '13'); ?>"
                                   min="0" max="100">
                            <small class="form-text text-muted">
                                Set to 0 for no age restriction
                            </small>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="parentalConsent" 
                                   name="require_parental_consent" value="1"
                                   <?php echo ($enrollment_settings['require_parental_consent'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="parentalConsent">
                                Require Parental Consent
                                <small class="d-block text-muted">For users under 18</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Custom Enrollment Messages -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Custom Messages</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Welcome Message</label>
                    <textarea class="form-control" name="welcome_message" rows="3"
                              placeholder="Welcome to our rewards program at this location!"><?php echo htmlspecialchars($enrollment_settings['welcome_message'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">
                        Displayed after successful enrollment at this location
                    </small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Special Instructions</label>
                    <textarea class="form-control" name="special_instructions" rows="3"
                              placeholder="Please show this confirmation to our staff for your welcome gift!"><?php echo htmlspecialchars($enrollment_settings['special_instructions'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">
                        Location-specific instructions for new members
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Enrollment Tracking -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Tracking & Analytics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">UTM Source</label>
                            <input type="text" class="form-control" name="utm_source" 
                                   value="<?php echo htmlspecialchars($enrollment_settings['utm_source'] ?? ''); ?>"
                                   placeholder="location_page">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">UTM Campaign</label>
                            <input type="text" class="form-control" name="utm_campaign" 
                                   value="<?php echo htmlspecialchars($enrollment_settings['utm_campaign'] ?? ''); ?>"
                                   placeholder="store_signup_2024">
                        </div>
                    </div>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="trackConversions" 
                           name="track_conversions" value="1"
                           <?php echo ($enrollment_settings['track_conversions'] ?? 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="trackConversions">
                        Track Location-Specific Conversions
                        <small class="d-block text-muted">
                            Enable detailed analytics for enrollments at this location
                        </small>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-2"></i>Save Enrollment Settings
            </button>
        </div>
    </form>
</div>

<script>
// Handle form submission
document.getElementById('enrollmentSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Enrollment settings saved successfully!');
        } else {
            throw new Error(data.message || 'Failed to save enrollment settings');
        }
    })
    .catch(error => {
        showAlert('danger', error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function showAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alert.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    this.parentElement.insertBefore(alert, this);
    setTimeout(() => alert.remove(), 3000);
}

// Toggle store code field based on requirement checkbox
document.getElementById('requireStoreCode').addEventListener('change', function() {
    const storeCodeField = document.querySelector('input[name="store_code"]');
    storeCodeField.closest('.mb-3').style.display = this.checked ? 'block' : 'none';
    if (this.checked) {
        storeCodeField.setAttribute('required', '');
    } else {
        storeCodeField.removeAttribute('required');
    }
});

// Trigger on load
document.getElementById('requireStoreCode').dispatchEvent(new Event('change'));
</script>