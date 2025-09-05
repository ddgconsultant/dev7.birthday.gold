<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Create Marketing Platform";

// Get user's company context
$company_id = $current_user_data['company_id'] ?? 0;
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $platform_name = trim($_POST['platform_name'] ?? '');
    $platform_type = trim($_POST['platform_type'] ?? '');
    $platform_url = trim($_POST['platform_url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon_class = trim($_POST['icon_class'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 50);
    $status = trim($_POST['status'] ?? 'active');
    
    // Basic validation
    if (empty($platform_name) || empty($platform_type) || empty($platform_url)) {
        $error_message = 'Platform name, type, and URL are required fields.';
    } else {
        // Insert into mk_platforms table
        $insert_sql = "INSERT INTO mk_platforms 
                      (company_id, platform_name, platform_type, platform_url, description, 
                       icon_class, display_order, status, created_dt, updated_dt) 
                      VALUES 
                      (:company_id, :platform_name, :platform_type, :platform_url, :description, 
                       :icon_class, :display_order, :status, NOW(), NOW())";
        
        $insert_params = [
            'company_id' => $active_company_id,
            'platform_name' => $platform_name,
            'platform_type' => $platform_type, 
            'platform_url' => $platform_url,
            'description' => $description,
            'icon_class' => $icon_class,
            'display_order' => $display_order,
            'status' => $status
        ];
        
        try {
            if ($database->query($insert_sql, $insert_params)) {
                header('Location: /myaccount/marketing/platforms.php?created=1');
                exit;
            } else {
                $error_message = 'Failed to create platform. Please try again.';
            }
        } catch (Exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}
.form-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.icon-preview {
    font-size: 2rem;
    padding: 20px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    text-align: center;
    margin-top: 10px;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-plus-circle me-3"></i>Create Marketing Platform</h1>
        <p class="lead">Add a new marketing platform to your toolkit</p>';

// Show company context
if ($active_company_id == 0) {
    echo '
        <div class="badge bg-primary fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Birthday Gold (Internal Marketing)
        </div>';
} else {
    echo '
        <div class="badge bg-info fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Company ID: ' . $active_company_id . '
        </div>';
}

echo '
    </div>
</div>';

// Include marketing tab navigation
include('nav.inc.php');

echo '
<div class="container mb-5">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="d-flex justify-content-end">
                <a href="/myaccount/marketing/platforms.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Platforms
                </a>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Platform Details</h5>
                </div>
                <div class="card-body">';

if (isset($error_message)) {
    echo '
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> ' . htmlspecialchars($error_message) . '
                    </div>';
}

echo '
                    <form method="POST" id="platformForm">
                        <div class="form-section">
                            <h6 class="text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>
                            
                            <div class="mb-3">
                                <label for="platform_name" class="form-label">Platform Name *</label>
                                <input type="text" class="form-control" id="platform_name" name="platform_name" 
                                       value="' . htmlspecialchars($_POST['platform_name'] ?? '') . '" required>
                                <small class="text-muted">e.g., Facebook Ads, Google Analytics, Mailchimp</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="platform_type" class="form-label">Platform Type *</label>
                                        <select class="form-control" id="platform_type" name="platform_type" required>';

$platform_types = [
    '' => 'Select type...',
    'social_media' => 'Social Media',
    'advertising' => 'Advertising',
    'email_marketing' => 'Email Marketing', 
    'analytics' => 'Analytics',
    'automation' => 'Marketing Automation',
    'content_management' => 'Content Management',
    'crm' => 'CRM',
    'search_engine' => 'Search Engine',
    'other' => 'Other'
];

$current_type = $_POST['platform_type'] ?? '';
foreach ($platform_types as $value => $label) {
    $selected = ($current_type == $value) ? ' selected' : '';
    echo '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
}

echo '
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="display_order" class="form-label">Display Order</label>
                                        <input type="number" class="form-control" id="display_order" name="display_order" 
                                               value="' . htmlspecialchars($_POST['display_order'] ?? '50') . '" min="0" max="999">
                                        <small class="text-muted">Lower numbers appear first</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="platform_url" class="form-label">Platform URL *</label>
                                <input type="url" class="form-control" id="platform_url" name="platform_url" 
                                       value="' . htmlspecialchars($_POST['platform_url'] ?? '') . '" required>
                                <small class="text-muted">Full URL including https://</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">' . 
                                htmlspecialchars($_POST['description'] ?? '') . '</textarea>
                                <small class="text-muted">Brief description of how you use this platform</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6 class="text-success mb-3"><i class="bi bi-palette me-2"></i>Visual Settings</h6>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="icon_class" class="form-label">Icon Class</label>
                                        <input type="text" class="form-control" id="icon_class" name="icon_class" 
                                               value="' . htmlspecialchars($_POST['icon_class'] ?? '') . '" 
                                               placeholder="bi bi-globe">
                                        <small class="text-muted">
                                            <a href="https://icons.getbootstrap.com/" target="_blank">Browse Bootstrap Icons</a> 
                                            - Use format: bi bi-icon-name
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Icon Preview</label>
                                        <div class="icon-preview" id="iconPreview">
                                            <i class="bi bi-globe text-muted"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">';

$status_options = [
    'active' => 'Active',
    'inactive' => 'Inactive'
];

$current_status = $_POST['status'] ?? 'active';
foreach ($status_options as $value => $label) {
    $selected = ($current_status == $value) ? ' selected' : '';
    echo '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
}

echo '
                                </select>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/myaccount/marketing/platforms.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-plus-circle me-2"></i>Create Platform
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Common Platforms</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <h6>Social Media</h6>
                        <ul class="list-unstyled text-muted mb-3">
                            <li><i class="bi bi-facebook me-1"></i>Facebook - bi bi-facebook</li>
                            <li><i class="bi bi-instagram me-1"></i>Instagram - bi bi-instagram</li>
                            <li><i class="bi bi-twitter me-1"></i>Twitter/X - bi bi-twitter</li>
                            <li><i class="bi bi-linkedin me-1"></i>LinkedIn - bi bi-linkedin</li>
                            <li><i class="bi bi-youtube me-1"></i>YouTube - bi bi-youtube</li>
                        </ul>
                        
                        <h6>Advertising</h6>
                        <ul class="list-unstyled text-muted mb-3">
                            <li><i class="bi bi-google me-1"></i>Google Ads - bi bi-google</li>
                            <li><i class="bi bi-meta me-1"></i>Meta Ads - bi bi-meta</li>
                            <li><i class="bi bi-microsoft me-1"></i>Microsoft Ads - bi bi-microsoft</li>
                        </ul>
                        
                        <h6>Email & CRM</h6>
                        <ul class="list-unstyled text-muted mb-3">
                            <li><i class="bi bi-envelope me-1"></i>Email - bi bi-envelope</li>
                            <li><i class="bi bi-person-rolodex me-1"></i>CRM - bi bi-person-rolodex</li>
                        </ul>
                        
                        <h6>Analytics</h6>
                        <ul class="list-unstyled text-muted">
                            <li><i class="bi bi-graph-up me-1"></i>Analytics - bi bi-graph-up</li>
                            <li><i class="bi bi-bar-chart me-1"></i>Reporting - bi bi-bar-chart</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="small text-muted">
                        <li>Choose descriptive names for easy identification</li>
                        <li>Use the correct platform type for better organization</li>
                        <li>Lower display order numbers appear first in lists</li>
                        <li>Test your URLs to make sure they work correctly</li>
                        <li>Choose appropriate icons to make platforms visually distinct</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const iconInput = document.getElementById("icon_class");
    const iconPreview = document.getElementById("iconPreview");
    
    function updateIconPreview() {
        const iconClass = iconInput.value.trim();
        if (iconClass) {
            iconPreview.innerHTML = `<i class="${iconClass}"></i>`;
        } else {
            iconPreview.innerHTML = `<i class="bi bi-globe text-muted"></i>`;
        }
    }
    
    iconInput.addEventListener("input", updateIconPreview);
    updateIconPreview(); // Initial preview
});
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>