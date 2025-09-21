<?php
// company-details.php - Edit additional fields from bg_companies table
// This component allows editing of detailed company information not shown in General tab

// Ensure we have access to required variables
if (!isset($company_id) || !$company_id) {
    echo '<div class="alert alert-danger">Company ID is required</div>';
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company_details'])) {
    try {
        // Prepare the UPDATE query with only the fields not in General tab
        $update_sql = "UPDATE bg_companies SET 
            display_category = :display_category,
            company_status = :company_status,
            source = :source,
            email_domain = :email_domain,
            bgrab_domain = :bgrab_domain,
            description = :description,
            short_description = :short_description,
            spinner_description = :spinner_description,
            howto = :howto,
            terms1 = :terms1,
            terms2 = :terms2,
            modify_dt = NOW()
        WHERE company_id = :company_id";
        
        // Prepare parameters
        $params = [
            ':company_id' => $company_id,
            ':display_category' => $_POST['display_category'] ?? '',
            ':company_status' => $_POST['company_status'] ?? 'active',
            ':source' => $_POST['source'] ?? '',
            ':email_domain' => $_POST['email_domain'] ?? '',
            ':bgrab_domain' => $_POST['bgrab_domain'] ?? '',
            ':description' => $_POST['description'] ?? '',
            ':short_description' => $_POST['short_description'] ?? '',
            ':spinner_description' => $_POST['spinner_description'] ?? '',
            ':howto' => $_POST['howto'] ?? '',
            ':terms1' => $_POST['terms1'] ?? '',
            ':terms2' => $_POST['terms2'] ?? ''
        ];
        
        // Execute the update
        $stmt = $database->prepare($update_sql);
        $stmt->execute($params);
        
        // Refresh company data
        $company = $app->getcompanydetails($company_id);
        
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo 'Company details updated successfully!';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo 'Error updating company details: ' . htmlspecialchars($e->getMessage());
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Fetch fresh company data
$company_sql = "SELECT * FROM bg_companies WHERE company_id = :company_id";
$stmt = $database->prepare($company_sql);
$stmt->execute([':company_id' => $company_id]);
$company_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company_data) {
    echo '<div class="alert alert-danger">Company not found</div>';
    exit;
}

// Start form output
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">Additional Company Details</h5>';
echo '<p class="text-muted mb-0 small">Extended fields not shown in the General tab</p>';
echo '</div>';
echo '<div class="card-body">';

echo '<form method="POST" action="" class="needs-validation" novalidate>';
echo '<input type="hidden" name="update_company_details" value="1">';

// Descriptions Section (expanded fields)
echo '<h6 class="border-bottom pb-2 mb-3">Extended Descriptions</h6>';
echo '<div class="row g-3 mb-4">';

echo '<div class="col-12">';
echo '<label for="description" class="form-label">Full Description</label>';
echo '<textarea class="form-control" id="description" name="description" rows="3">' . htmlspecialchars($company_data['description'] ?? '') . '</textarea>';
echo '<div class="form-text">Detailed company description for display purposes (max 1000 characters)</div>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<label for="short_description" class="form-label">Short Description</label>';
echo '<textarea class="form-control" id="short_description" name="short_description" rows="2">' . htmlspecialchars($company_data['short_description'] ?? '') . '</textarea>';
echo '<div class="form-text">Brief description for listings (max 500 characters)</div>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<label for="spinner_description" class="form-label">Spinner Description</label>';
echo '<input type="text" class="form-control" id="spinner_description" name="spinner_description" value="' . htmlspecialchars($company_data['spinner_description'] ?? '') . '" maxlength="255">';
echo '<div class="form-text">Very short description for loading screens (max 255 characters)</div>';
echo '</div>';

echo '<div class="col-12">';
echo '<label for="howto" class="form-label">How To Instructions</label>';
echo '<textarea class="form-control" id="howto" name="howto" rows="2">' . htmlspecialchars($company_data['howto'] ?? '') . '</textarea>';
echo '<div class="form-text">Step-by-step instructions for enrollment process (max 500 characters)</div>';
echo '</div>';

echo '</div>'; // row

// Note: Social media fields (facebook, twitter, instagram, tiktok, youtube, linkedin) are handled in General tab

// Domain Configuration
echo '<h6 class="border-bottom pb-2 mb-3">Domain Configuration</h6>';
echo '<div class="row g-3 mb-4">';

echo '<div class="col-md-6">';
echo '<label for="email_domain" class="form-label">Email Domain</label>';
echo '<input type="text" class="form-control" id="email_domain" name="email_domain" value="' . htmlspecialchars($company_data['email_domain'] ?? '') . '" placeholder="example.com">';
echo '<div class="form-text">Domain used for email communications (e.g., marketing@domain.com)</div>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<label for="bgrab_domain" class="form-label">BGrab Domain</label>';
echo '<input type="text" class="form-control" id="bgrab_domain" name="bgrab_domain" value="' . htmlspecialchars($company_data['bgrab_domain'] ?? '') . '" placeholder="bgrab.example.com">';
echo '<div class="form-text">Special domain for background data collection processes</div>';
echo '</div>';

echo '</div>'; // row

// Additional Settings
echo '<h6 class="border-bottom pb-2 mb-3">Additional Settings</h6>';
echo '<div class="row g-3 mb-4">';

echo '<div class="col-md-4">';
echo '<label for="display_category" class="form-label">Display Category</label>';
echo '<input type="text" class="form-control" id="display_category" name="display_category" value="' . htmlspecialchars($company_data['display_category'] ?? '') . '">';
echo '<div class="form-text">Public-facing category name</div>';
echo '</div>';

echo '<div class="col-md-4">';
echo '<label for="company_status" class="form-label">Company Status</label>';
echo '<select class="form-control" id="company_status" name="company_status">';
$company_statuses = ['active', 'inactive', 'pending', 'suspended'];
foreach ($company_statuses as $status) {
    $selected = ($company_data['company_status'] ?? 'active') === $status ? ' selected' : '';
    echo '<option value="' . $status . '"' . $selected . '>' . ucfirst($status) . '</option>';
}
echo '</select>';
echo '<div class="form-text">Business operational status</div>';
echo '</div>';

echo '<div class="col-md-4">';
echo '<label for="source" class="form-label">Data Source</label>';
echo '<input type="text" class="form-control" id="source" name="source" value="' . htmlspecialchars($company_data['source'] ?? '') . '" placeholder="manual, import, api, etc.">';
echo '<div class="form-text">How this company was added</div>';
echo '</div>';

echo '</div>'; // row

// Terms and Conditions
echo '<h6 class="border-bottom pb-2 mb-3">Terms and Legal Text</h6>';
echo '<div class="row g-3 mb-4">';

echo '<div class="col-md-6">';
echo '<label for="terms1" class="form-label">Terms Line 1</label>';
echo '<input type="text" class="form-control" id="terms1" name="terms1" value="' . htmlspecialchars($company_data['terms1'] ?? '') . '" maxlength="255">';
echo '<div class="form-text">First line of terms (max 255 characters)</div>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<label for="terms2" class="form-label">Terms Line 2</label>';
echo '<input type="text" class="form-control" id="terms2" name="terms2" value="' . htmlspecialchars($company_data['terms2'] ?? '') . '" maxlength="255">';
echo '<div class="form-text">Second line of terms (max 255 characters)</div>';
echo '</div>';

echo '</div>'; // row

// Read-only Information Section
echo '<h6 class="border-bottom pb-2 mb-3">System Information (Read-only)</h6>';
echo '<div class="row g-3 mb-4">';

echo '<div class="col-md-3">';
echo '<label class="form-label">Company ID</label>';
echo '<input type="text" class="form-control" value="' . $company_data['company_id'] . '" readonly disabled>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<label class="form-label">Usage Count</label>';
echo '<input type="number" class="form-control" value="' . intval($company_data['usage_count'] ?? 0) . '" readonly disabled>';
echo '<div class="form-text">Total enrollments</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<label class="form-label">Created Date</label>';
echo '<input type="text" class="form-control" value="' . ($company_data['create_dt'] ? date('M d, Y', strtotime($company_data['create_dt'])) : 'N/A') . '" readonly disabled>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<label class="form-label">Last Modified</label>';
echo '<input type="text" class="form-control" value="' . ($company_data['modify_dt'] ? date('M d, Y g:i A', strtotime($company_data['modify_dt'])) : 'N/A') . '" readonly disabled>';
echo '</div>';

echo '</div>'; // row

// Current Values Reference (for debugging/verification)
echo '<details class="mb-3">';
echo '<summary class="text-muted small" style="cursor: pointer;">View All Database Fields (Debug)</summary>';
echo '<div class="card mt-2">';
echo '<div class="card-body">';
echo '<pre class="small mb-0" style="max-height: 300px; overflow-y: auto;">';

// Fields handled in General tab (shown for reference only)
$general_fields = [
    'parent_company', 'company_name', 'company_display_name', 
    'category', 'status', 'region_type', 'company_url', 
    'signup_url', 'info_url', 'appgoogle', 'appapple', 
    'minimum_age', 'maximum_age', 'facebook', 'twitter', 'instagram', 'tiktok',
    'youtube', 'linkedin'
];

echo "=== FIELDS IN GENERAL TAB (not editable here) ===\n";
foreach ($general_fields as $field) {
    if (isset($company_data[$field])) {
        echo htmlspecialchars($field) . ': ' . htmlspecialchars($company_data[$field] ?: '(empty)') . "\n";
    }
}

echo "\n=== FIELDS IN THIS DETAILS TAB ===\n";
$details_fields = [
    'display_category', 'company_status', 'source', 'email_domain', 
    'bgrab_domain', 'description', 'short_description', 
    'spinner_description', 'howto', 'terms1', 'terms2'
];

foreach ($details_fields as $field) {
    if (isset($company_data[$field])) {
        $value = $company_data[$field];
        if (strlen($value) > 100) {
            $value = substr($value, 0, 100) . '...';
        }
        echo htmlspecialchars($field) . ': ' . htmlspecialchars($value ?: '(empty)') . "\n";
    }
}

echo '</pre>';
echo '</div>';
echo '</div>';
echo '</details>';

// Submit buttons
echo '<div class="d-grid gap-2 d-md-flex justify-content-md-end">';
echo '<button type="submit" class="btn btn-primary">';
echo '<i class="bi bi-save me-2"></i>Save Changes';
echo '</button>';
echo '<button type="button" class="btn btn-secondary" onclick="location.reload()">';
echo '<i class="bi bi-arrow-clockwise me-2"></i>Reset Form';
echo '</button>';
echo '</div>';

echo '</form>';
echo '</div>'; // card-body
echo '</div>'; // card

// Add form validation JavaScript
echo '<script>
(function() {
    "use strict";
    
    var forms = document.querySelectorAll(".needs-validation");
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener("submit", function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add("was-validated");
        }, false);
    });
})();
</script>';