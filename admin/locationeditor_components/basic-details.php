<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Location data should be available from parent page
if (!isset($location) || !is_array($location) || !isset($location_id)) {
    echo '<div class="alert alert-danger">Location data not available</div>';
    exit;
}
?>

<div class="container-fluid px-0">
    <h3 class="mb-4">Basic Location Details</h3>
    
    <form id="locationDetailsForm" method="POST" action="/admin/ajax/save-location-details.php">
        <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="location_name" class="form-label">Location Name</label>
                    <input type="text" class="form-control" id="location_name" name="location_name" 
                           value="<?php echo htmlspecialchars($location['location_name'] ?? ''); ?>"
                           placeholder="e.g., Downtown Austin">
                    <small class="form-text text-muted">Optional - will use City, State if not provided</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="location_url" class="form-label">Location URL</label>
                    <input type="url" class="form-control" id="location_url" name="location_url" 
                           value="<?php echo htmlspecialchars($location['location_url'] ?? ''); ?>"
                           placeholder="https://example.com/locations/austin">
                    <small class="form-text text-muted">Link to this specific location's page</small>
                </div>
            </div>
        </div>
        
        <h4 class="mt-4 mb-3">Address Information</h4>
        
        <div class="mb-3">
            <label for="address" class="form-label">Street Address <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="address" name="address" 
                   value="<?php echo htmlspecialchars($location['address'] ?? ''); ?>" required>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="city" name="city" 
                           value="<?php echo htmlspecialchars($location['city'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="state" class="form-label">State</label>
                    <select class="form-select" id="state" name="state">
                        <option value="">Select State</option>
                        <?php
                        $states = [
                            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
                            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
                            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
                            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
                            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
                            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
                            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
                            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
                            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
                            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
                            'WI' => 'Wisconsin', 'WY' => 'Wyoming'
                        ];
                        
                        foreach ($states as $code => $name) {
                            $selected = ($location['state'] ?? '') === $code ? 'selected' : '';
                            echo "<option value=\"$code\" $selected>$name</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="zip_code" class="form-label">ZIP Code</label>
                    <input type="text" class="form-control" id="zip_code" name="zip_code" 
                           value="<?php echo htmlspecialchars($location['zip_code'] ?? ''); ?>"
                           pattern="[0-9]{5}(-[0-9]{4})?">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="county" class="form-label">County</label>
                    <input type="text" class="form-control" id="county" name="county" 
                           value="<?php echo htmlspecialchars($location['county'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" class="form-control" id="country" name="country" 
                           value="<?php echo htmlspecialchars($location['country'] ?? 'United States'); ?>">
                </div>
            </div>
        </div>
        
        <h4 class="mt-4 mb-3">Contact Information</h4>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                           value="<?php echo htmlspecialchars($location['phone_number'] ?? ''); ?>"
                           placeholder="555-123-4567">
                </div>
            </div>
        </div>
        
        <h4 class="mt-4 mb-3">Location Coordinates</h4>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="latitude" class="form-label">Latitude</label>
                    <input type="number" class="form-control" id="latitude" name="latitude" 
                           value="<?php echo htmlspecialchars($location['latitude'] ?? ''); ?>"
                           step="0.000001" min="-90" max="90">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="longitude" class="form-label">Longitude</label>
                    <input type="number" class="form-control" id="longitude" name="longitude" 
                           value="<?php echo htmlspecialchars($location['longitude'] ?? ''); ?>"
                           step="0.000001" min="-180" max="180">
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <button type="button" class="btn btn-sm btn-secondary" id="geocodeBtn">
                <i class="bi bi-geo-alt me-2"></i>Get Coordinates from Address
            </button>
        </div>
        
        <h4 class="mt-4 mb-3">Additional Information</h4>
        
        <div class="mb-3">
            <label for="commentary" class="form-label">Internal Notes</label>
            <textarea class="form-control" id="commentary" name="commentary" rows="3"
                      placeholder="Internal notes about this location"><?php echo htmlspecialchars($location['commentary'] ?? ''); ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($location['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($location['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="closed" <?php echo ($location['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="is_verified" name="is_verified" value="1"
                               <?php echo ($location['is_verified'] ?? 0) == 1 ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_verified">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Verified Location
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                <p class="mb-1">Created: <?php echo !empty($location['create_dt']) ? date('M j, Y g:i A', strtotime($location['create_dt'])) : 'Unknown'; ?></p>
                <?php if (!empty($location['modify_dt'])): ?>
                <p class="mb-0">Last Modified: <?php echo date('M j, Y g:i A', strtotime($location['modify_dt'])); ?></p>
                <?php endif; ?>
                <p class="mb-0">Source: <?php echo htmlspecialchars($location['source'] ?? 'Unknown'); ?></p>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Save Changes
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission
    const form = document.getElementById('locationDetailsForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show mt-3';
                alert.innerHTML = `
                    <i class="bi bi-check-circle me-2"></i>${data.message || 'Location details saved successfully!'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                form.parentElement.insertBefore(alert, form);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    alert.remove();
                }, 3000);
            } else {
                throw new Error(data.message || 'Failed to save location details');
            }
        })
        .catch(error => {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show mt-3';
            alert.innerHTML = `
                <i class="bi bi-exclamation-circle me-2"></i>${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            form.parentElement.insertBefore(alert, form);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
    
    // Geocode button functionality
    document.getElementById('geocodeBtn').addEventListener('click', function() {
        const address = document.getElementById('address').value;
        const city = document.getElementById('city').value;
        const state = document.getElementById('state').value;
        const zip = document.getElementById('zip_code').value;
        
        if (!address || !city) {
            alert('Please enter at least an address and city to geocode.');
            return;
        }
        
        const fullAddress = `${address}, ${city}, ${state} ${zip}`;
        
        // This is a placeholder - you would need to implement actual geocoding
        alert('Geocoding functionality would be implemented here for: ' + fullAddress);
        
        // Example of how you might use Google Geocoding API or similar
        // fetch(`/admin/ajax/geocode.php?address=${encodeURIComponent(fullAddress)}`)
        // .then(response => response.json())
        // .then(data => {
        //     if (data.lat && data.lng) {
        //         document.getElementById('latitude').value = data.lat;
        //         document.getElementById('longitude').value = data.lng;
        //     }
        // });
    });
});
</script>