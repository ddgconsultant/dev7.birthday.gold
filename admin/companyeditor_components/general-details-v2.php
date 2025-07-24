<?php
/**
 * Enhanced General Details Component for Company Editor
 * Version 2.0 - Complete business management with real-time updates
 */

if (!isset($company_id)) {
    $company_id = $_GET['cid'] ?? null;
    
    if (!isset($company)) {
        $company = $app->getcompanydetails($company_id);
    }
}

// Ensure we have basic company data
$company_name = $company['company_name'] ?? 'Unknown Company';
$company_display_name = $company['company_display_name'] ?? $company_name;
$company_joined = date('F d, Y', strtotime($company['create_dt'] ?? 'now'));
$company_status = $company['status'] ?? 'unknown';

// Enhanced data fields
$business_details = [
    'ein' => $company['ein'] ?? '',
    'dba_name' => $company['dba_name'] ?? '',
    'founded_year' => $company['founded_year'] ?? '',
    'employee_count' => $company['employee_count'] ?? '',
    'annual_revenue' => $company['annual_revenue'] ?? '',
    'business_type' => $company['business_type'] ?? '',
    'parent_company' => $company['parent_company'] ?? ''
];

// Contact information
$contact_info = [
    'primary_contact_name' => $company['primary_contact_name'] ?? '',
    'primary_contact_email' => $company['primary_contact_email'] ?? '',
    'primary_contact_phone' => $company['primary_contact_phone'] ?? '',
    'support_email' => $company['support_email'] ?? '',
    'support_phone' => $company['support_phone'] ?? '',
    'billing_email' => $company['billing_email'] ?? ''
];

// Social media and online presence
$online_presence = [
    'facebook' => $company['facebook'] ?? '',
    'twitter' => $company['twitter'] ?? '',
    'instagram' => $company['instagram'] ?? '',
    'tiktok' => $company['tiktok'] ?? '',
    'youtube' => $company['youtube'] ?? '',
    'linkedin' => $company['linkedin'] ?? '',
    'pinterest' => $company['pinterest'] ?? '',
    'app_store_url' => $company['app_store_url'] ?? '',
    'play_store_url' => $company['play_store_url'] ?? ''
];

// Birthday program details
$birthday_program = [
    'program_name' => $company['birthday_program_name'] ?? '',
    'enrollment_type' => $company['enrollment_type'] ?? 'manual',
    'verification_required' => $company['verification_required'] ?? 0,
    'advance_notice_days' => $company['advance_notice_days'] ?? 30,
    'reward_validity_days' => $company['reward_validity_days'] ?? 30,
    'multi_location_redemption' => $company['multi_location_redemption'] ?? 0,
    'online_redemption' => $company['online_redemption'] ?? 0
];

// Get business categories
$categories_sql = "SELECT DISTINCT category FROM bg_ref_categories ORDER BY category";
$categories_stmt = $database->query($categories_sql);
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get verification status
$verification_sql = "SELECT * FROM bg_company_verifications 
                    WHERE company_id = :company_id 
                    ORDER BY verification_date DESC LIMIT 1";
$verification_stmt = $database->query($verification_sql, ['company_id' => $company_id]);
$verification = $verification_stmt->fetch(PDO::FETCH_ASSOC);

// Status configurations
$status_configs = [
    'submitted' => ['color' => 'secondary', 'icon' => 'clock', 'next' => 'pending_review'],
    'pending_review' => ['color' => 'warning', 'icon' => 'hourglass-split', 'next' => 'approved_pending_data'],
    'approved_pending_data' => ['color' => 'info', 'icon' => 'gear', 'next' => 'pending_final_review'],
    'pending_final_review' => ['color' => 'primary', 'icon' => 'check-circle', 'next' => 'active'],
    'active' => ['color' => 'success', 'icon' => 'check-circle-fill', 'next' => null],
    'inactive' => ['color' => 'danger', 'icon' => 'x-circle', 'next' => 'active'],
    'rejected' => ['color' => 'danger', 'icon' => 'x-octagon', 'next' => 'pending_review']
];

$current_status_config = $status_configs[$company_status] ?? ['color' => 'secondary', 'icon' => 'question'];
?>

<div class="container-fluid px-4">
    <!-- Enhanced Header with Quick Actions -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-auto text-center">
                    <div class="position-relative">
                        <img src="<?php echo $display->companyimage($company_id . '/' . $company['company_logo']); ?>" 
                             class="img-fluid rounded-circle border" 
                             style="width: 120px; height: 120px; object-fit: cover;" 
                             alt="<?php echo htmlspecialchars($company_name); ?> Logo"
                             id="companyLogo">
                        <button class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" 
                                onclick="uploadLogo()"
                                data-bs-toggle="tooltip" 
                                title="Update Logo">
                            <i class="bi bi-camera"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="h3 mb-1">
                                <span id="companyName" class="editable" data-field="company_name" data-type="text">
                                    <?php echo htmlspecialchars($company_name); ?>
                                </span>
                                <?php if ($verification && $verification['is_verified']): ?>
                                <i class="bi bi-patch-check-fill text-primary" data-bs-toggle="tooltip" 
                                   title="Verified Business"></i>
                                <?php endif; ?>
                            </h1>
                            <?php if ($company_display_name !== $company_name): ?>
                            <p class="text-muted mb-2">
                                Display: <span class="editable" data-field="company_display_name" data-type="text">
                                    <?php echo htmlspecialchars($company_display_name); ?>
                                </span>
                            </p>
                            <?php endif; ?>
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="bi bi-hash"></i> ID: <?php echo $company_id; ?></span>
                                <span><i class="bi bi-calendar"></i> Joined: <?php echo $company_joined; ?></span>
                                <span><i class="bi bi-people"></i> <?php echo number_format($company['usage_count'] ?? 0); ?> enrollments</span>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-column align-items-end gap-2">
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-primary btn-sm" onclick="viewPublicProfile()">
                                    <i class="bi bi-eye"></i> View Profile
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="previewEnrollment()">
                                    <i class="bi bi-person-plus"></i> Test Enrollment
                                </button>
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn btn-lg badge bg-<?php echo $current_status_config['color']; ?> dropdown-toggle" 
                                        type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="bi bi-<?php echo $current_status_config['icon']; ?>"></i>
                                    <?php echo ucwords(str_replace('_', ' ', $company_status)); ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach ($status_configs as $status => $config): ?>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="updateStatus('<?php echo $status; ?>')">
                                            <i class="bi bi-<?php echo $config['icon']; ?> text-<?php echo $config['color']; ?>"></i>
                                            <?php echo ucwords(str_replace('_', ' ', $status)); ?>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav Pills for Sections -->
    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#businessInfo" type="button">
                <i class="bi bi-building"></i> Business Info
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#contactInfo" type="button">
                <i class="bi bi-person-lines-fill"></i> Contacts
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#onlinePresence" type="button">
                <i class="bi bi-globe"></i> Online Presence
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#birthdayProgram" type="button">
                <i class="bi bi-gift"></i> Birthday Program
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#businessHours" type="button">
                <i class="bi bi-clock"></i> Hours & Holidays
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#integrations" type="button">
                <i class="bi bi-plug"></i> Integrations
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Business Information Tab -->
        <div class="tab-pane fade show active" id="businessInfo" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Business Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Business Category</label>
                            <select class="form-select editable" data-field="category" data-type="select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $company['category'] == $cat ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($cat); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Business Type</label>
                            <select class="form-select editable" data-field="business_type" data-type="select">
                                <option value="single_location">Single Location</option>
                                <option value="multi_location">Multi-Location</option>
                                <option value="franchise">Franchise</option>
                                <option value="online_only">Online Only</option>
                                <option value="hybrid">Hybrid (Online + Physical)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">EIN/Tax ID</label>
                            <input type="text" class="form-control editable" 
                                   data-field="ein" 
                                   data-type="text"
                                   value="<?php echo htmlspecialchars($business_details['ein']); ?>"
                                   placeholder="XX-XXXXXXX">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">DBA Name</label>
                            <input type="text" class="form-control editable" 
                                   data-field="dba_name" 
                                   data-type="text"
                                   value="<?php echo htmlspecialchars($business_details['dba_name']); ?>"
                                   placeholder="Doing Business As">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Founded Year</label>
                            <input type="number" class="form-control editable" 
                                   data-field="founded_year" 
                                   data-type="number"
                                   value="<?php echo $business_details['founded_year']; ?>"
                                   min="1800" 
                                   max="<?php echo date('Y'); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Company Website</label>
                            <div class="input-group">
                                <input type="url" class="form-control editable" 
                                       data-field="company_url" 
                                       data-type="url"
                                       value="<?php echo htmlspecialchars($company['company_url'] ?? ''); ?>">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="window.open('<?php echo $company['company_url']; ?>', '_blank')">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Signup URL</label>
                            <div class="input-group">
                                <input type="url" class="form-control editable" 
                                       data-field="signup_url" 
                                       data-type="url"
                                       value="<?php echo htmlspecialchars($company['signup_url'] ?? ''); ?>">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="window.open('<?php echo $company['signup_url']; ?>', '_blank')">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Business Description</label>
                            <textarea class="form-control editable" 
                                      data-field="description" 
                                      data-type="textarea"
                                      rows="3"
                                      placeholder="Brief description of the business..."><?php echo htmlspecialchars($company['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information Tab -->
        <div class="tab-pane fade" id="contactInfo" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <h6 class="text-muted">Primary Contact</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Name</label>
                            <input type="text" class="form-control editable" 
                                   data-field="primary_contact_name" 
                                   data-type="text"
                                   value="<?php echo htmlspecialchars($contact_info['primary_contact_name']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-control editable" 
                                   data-field="primary_contact_email" 
                                   data-type="email"
                                   value="<?php echo htmlspecialchars($contact_info['primary_contact_email']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Phone</label>
                            <input type="tel" class="form-control editable" 
                                   data-field="primary_contact_phone" 
                                   data-type="tel"
                                   value="<?php echo htmlspecialchars($contact_info['primary_contact_phone']); ?>">
                        </div>
                        
                        <div class="col-12 mt-4">
                            <h6 class="text-muted">Support Contacts</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Support Email</label>
                            <input type="email" class="form-control editable" 
                                   data-field="support_email" 
                                   data-type="email"
                                   value="<?php echo htmlspecialchars($contact_info['support_email']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Support Phone</label>
                            <input type="tel" class="form-control editable" 
                                   data-field="support_phone" 
                                   data-type="tel"
                                   value="<?php echo htmlspecialchars($contact_info['support_phone']); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Online Presence Tab -->
        <div class="tab-pane fade" id="onlinePresence" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Online Presence & Social Media</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($online_presence as $platform => $url): ?>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-<?php echo $platform; ?>"></i> 
                                <?php echo ucfirst(str_replace('_', ' ', $platform)); ?>
                            </label>
                            <div class="input-group">
                                <input type="url" class="form-control editable" 
                                       data-field="<?php echo $platform; ?>" 
                                       data-type="url"
                                       value="<?php echo htmlspecialchars($url); ?>"
                                       placeholder="https://...">
                                <?php if ($url): ?>
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="window.open('<?php echo $url; ?>', '_blank')">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Birthday Program Tab -->
        <div class="tab-pane fade" id="birthdayProgram" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Birthday Program Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Program Name</label>
                            <input type="text" class="form-control editable" 
                                   data-field="birthday_program_name" 
                                   data-type="text"
                                   value="<?php echo htmlspecialchars($birthday_program['program_name']); ?>"
                                   placeholder="e.g., Birthday Club, Birthday Rewards">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Enrollment Type</label>
                            <select class="form-select editable" data-field="enrollment_type" data-type="select">
                                <option value="manual" <?php echo $birthday_program['enrollment_type'] == 'manual' ? 'selected' : ''; ?>>
                                    Manual (Customer signs up)
                                </option>
                                <option value="automatic" <?php echo $birthday_program['enrollment_type'] == 'automatic' ? 'selected' : ''; ?>>
                                    Automatic (With account creation)
                                </option>
                                <option value="opt_in" <?php echo $birthday_program['enrollment_type'] == 'opt_in' ? 'selected' : ''; ?>>
                                    Opt-in during checkout
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Advance Notice (Days)</label>
                            <input type="number" class="form-control editable" 
                                   data-field="advance_notice_days" 
                                   data-type="number"
                                   value="<?php echo $birthday_program['advance_notice_days']; ?>"
                                   min="0" max="60"
                                   data-bs-toggle="tooltip"
                                   title="How many days before birthday to send reward">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Reward Validity (Days)</label>
                            <input type="number" class="form-control editable" 
                                   data-field="reward_validity_days" 
                                   data-type="number"
                                   value="<?php echo $birthday_program['reward_validity_days']; ?>"
                                   min="1" max="365"
                                   data-bs-toggle="tooltip"
                                   title="How long the reward is valid after birthday">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Minimum Age</label>
                            <input type="number" class="form-control editable" 
                                   data-field="minage" 
                                   data-type="number"
                                   value="<?php echo $company['minage'] ?? 0; ?>"
                                   min="0" max="100">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input editable" type="checkbox" 
                                       data-field="verification_required" 
                                       data-type="checkbox"
                                       <?php echo $birthday_program['verification_required'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">
                                    Require age verification for enrollment
                                </label>
                            </div>
                            
                            <div class="form-check form-switch">
                                <input class="form-check-input editable" type="checkbox" 
                                       data-field="multi_location_redemption" 
                                       data-type="checkbox"
                                       <?php echo $birthday_program['multi_location_redemption'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">
                                    Allow redemption at any location
                                </label>
                            </div>
                            
                            <div class="form-check form-switch">
                                <input class="form-check-input editable" type="checkbox" 
                                       data-field="online_redemption" 
                                       data-type="checkbox"
                                       <?php echo $birthday_program['online_redemption'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">
                                    Allow online redemption
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Hours Tab -->
        <div class="tab-pane fade" id="businessHours" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Business Hours & Holidays</h5>
                    <button class="btn btn-sm btn-primary" onclick="addHolidaySchedule()">
                        <i class="bi bi-plus"></i> Add Holiday
                    </button>
                </div>
                <div class="card-body">
                    <!-- Business hours component would go here -->
                    <div id="businessHoursContainer">
                        <p class="text-muted">Business hours management coming soon...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integrations Tab -->
        <div class="tab-pane fade" id="integrations" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Third-Party Integrations</h5>
                </div>
                <div class="card-body">
                    <!-- Integration settings would go here -->
                    <p class="text-muted">Integration management coming soon...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ABO Progress Section (Enhanced) -->
    <div class="mt-5">
        <h4 class="mb-3">Automation Progress</h4>
        <div id="aboProgressContainer">
            <!-- ABO progress will be loaded here via AJAX -->
        </div>
    </div>
</div>

<!-- Logo Upload Modal -->
<div class="modal fade" id="logoUploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Company Logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="logoUploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Select Logo Image</label>
                        <input type="file" class="form-control" name="logo" accept="image/*" required>
                        <div class="form-text">Recommended: Square image, at least 500x500px</div>
                    </div>
                    <div id="logoPreview" class="text-center mb-3"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitLogoUpload()">Upload</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize editable fields
document.addEventListener('DOMContentLoaded', function() {
    initializeEditableFields();
    loadABOProgress();
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Make fields editable with real-time save
function initializeEditableFields() {
    document.querySelectorAll('.editable').forEach(field => {
        const fieldType = field.dataset.type;
        const fieldName = field.dataset.field;
        
        if (fieldType === 'text' || fieldType === 'email' || fieldType === 'tel' || fieldType === 'url') {
            // For input fields
            field.addEventListener('blur', function() {
                saveField(fieldName, this.value);
            });
            
            field.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.blur();
                }
            });
        } else if (fieldType === 'select') {
            // For select fields
            field.addEventListener('change', function() {
                saveField(fieldName, this.value);
            });
        } else if (fieldType === 'checkbox') {
            // For checkboxes
            field.addEventListener('change', function() {
                saveField(fieldName, this.checked ? 1 : 0);
            });
        } else if (fieldType === 'textarea') {
            // For textareas
            field.addEventListener('blur', function() {
                saveField(fieldName, this.value);
            });
        }
    });
}

// Save field via AJAX
function saveField(fieldName, value) {
    const data = new FormData();
    data.append('company_id', <?php echo $company_id; ?>);
    data.append('field', fieldName);
    data.append('value', value);
    
    fetch('/admin/ajax/update_company_field.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('success', 'Saved successfully');
        } else {
            showToast('error', result.message || 'Error saving field');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Network error');
    });
}

// Update company status
function updateStatus(newStatus) {
    if (!confirm('Are you sure you want to change the company status?')) {
        return;
    }
    
    const data = new FormData();
    data.append('company_id', <?php echo $company_id; ?>);
    data.append('status', newStatus);
    
    fetch('/admin/ajax/update_company_status.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            showToast('error', result.message || 'Error updating status');
        }
    });
}

// Load ABO progress
function loadABOProgress() {
    fetch('/admin/ajax/get_abo_progress.php?company_id=<?php echo $company_id; ?>')
        .then(response => response.text())
        .then(html => {
            document.getElementById('aboProgressContainer').innerHTML = html;
        });
}

// Upload logo
function uploadLogo() {
    const modal = new bootstrap.Modal(document.getElementById('logoUploadModal'));
    modal.show();
}

function submitLogoUpload() {
    const form = document.getElementById('logoUploadForm');
    const formData = new FormData(form);
    
    fetch('/admin/ajax/upload_company_logo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            document.getElementById('companyLogo').src = result.logo_url + '?t=' + Date.now();
            bootstrap.Modal.getInstance(document.getElementById('logoUploadModal')).hide();
            showToast('success', 'Logo uploaded successfully');
        } else {
            showToast('error', result.message || 'Error uploading logo');
        }
    });
}

// Utility functions
function showToast(type, message) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Add to container
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove after hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// Preview functions
function viewPublicProfile() {
    window.open('/business/<?php echo $company_id; ?>', '_blank');
}

function previewEnrollment() {
    window.open('/test-enrollment?company_id=<?php echo $company_id; ?>', '_blank');
}
</script>