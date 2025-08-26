<?php
//company-editor-main.php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $cid = $_GET['cid'] ?? null;

if (!$company_id) {
    header('Location: /admin/brands.php');
    exit;
}

// Fetch the company details
$company = $app->getcompanydetails($company_id);

if (!$company) {
    header('Location: /admin/brands.php');
    exit;
}

// Check if company has APP only rewards
$app_only_check = $database->prepare("SELECT COUNT(*) FROM bg_company_rewards WHERE company_id = ? AND reward_type = 'APP' AND status = 'active'");
$app_only_check->execute([$company_id]);
$has_app_only_rewards = $app_only_check->fetchColumn() > 0;

// Check if company is APP ONLY (signup_url = 'APP ONLY')
$isAppOnly = ($company['signup_url'] ?? '') === $website['apponlytag'];

// Assign safe values if fields are missing
$company_name = $company['company_name'] ?? 'Unknown Company';
$company_display_name = $company['company_display_name'] ?? $company_name;
$category = $company['category'] ?? '';
$company_url = $company['company_url'] ?? '';
$signup_url = $company['signup_url'] ?? '';

// URLs for app stores
$google_targetUrl = 'https://play.google.com/store/search?q=' . urlencode($company_name) . '&c=apps&hl=en_US&gl=US';
$apple_targetUrl = 'https://www.apple.com/us/search/' . urlencode($company_name) . '?src=serp';

// Page setup
$pagetitle = "Company Editor - " . $company_name;
#$bodycontentclass = '';
#$header_flush = true;

// Additional styles
$additionalstyles .= '
<style>
.nav-pills .nav-link {
    color: #6c757d;
    background: #f8f9fa;
    margin-bottom: 0.5rem;
    border-radius: 0.25rem;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    transition: all 0.2s;
    text-align: left;
    justify-content: flex-start;
    width: 100%;
}

.nav-pills .nav-link:hover {
    background: #e9ecef;
    color: #495057;
}

.nav-pills .nav-link.active {
    background-color: var(--bs-primary);
    color: white;
}

.nav-pills .nav-link .bi {
    font-size: 1.1rem;
}

/* APP ONLY indicator styling */
.nav-pills .nav-link .bi-phone-x {
    font-size: 0.9rem;
}

.nav-pills .nav-link.text-danger {
    color: #dc3545 !important;
}

.nav-pills .nav-link.text-danger:hover {
    background: #f8d7da;
    color: #dc3545 !important;
}

.tab-content {
    background: white;
    border-radius: 0.5rem;
    min-height: 500px;
}

.content-section {
    padding: 1.25rem;
}
</style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin no-rounded-corners">
    <div class="container text-center">
        <h1 class="mb-1">Company Editor</h1>
        <p class="lead mb-0"><?php echo htmlspecialchars($company_name); ?></p>
    </div>
</div>

<div class="main-content py-4">
    <div class="container">
        <div class="row">
            <!-- Left Navigation -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="nav flex-column nav-pills" id="company-tabs" role="tablist">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                        <i class="bi bi-house-door me-2"></i>General
                    </button>
                    
                    <button class="nav-link" id="details-tab" data-bs-toggle="pill" data-bs-target="#details" type="button" role="tab">
                        <i class="bi bi-info-circle me-2"></i>Details
                    </button>
                    
                    <button class="nav-link" id="locations-tab" data-bs-toggle="pill" data-bs-target="#locations" type="button" role="tab">
                        <i class="bi bi-geo-alt me-2"></i>Locations
                    </button>
                    
                    <button class="nav-link" id="logos-tab" data-bs-toggle="pill" data-bs-target="#logos" type="button" role="tab">
                        <i class="bi bi-image me-2"></i>Logo Management
                    </button>
                    
                    <?php if (!$has_app_only_rewards): ?>
                    <button class="nav-link <?php echo $isAppOnly ? 'text-danger' : ''; ?>" id="formfieldedit-tab" data-bs-toggle="pill" data-bs-target="#formfieldedit" type="button" role="tab">
                        <i class="bi bi-file-earmark-text me-2"></i>Form Field Mappings
                        <?php if ($isAppOnly): ?>
                            <i class="bi bi-phone text-danger ms-2" title="APP ONLY"></i>
                        <?php endif; ?>
                    </button>
                    <?php else: ?>
                    <button class="nav-link disabled" type="button" role="tab" data-bs-toggle="tooltip" data-bs-placement="right" title="Not available for APP only rewards">
                        <i class="bi bi-phone me-2"></i>APP Only
                    </button>
                    <?php endif; ?>
                    
                    <button class="nav-link" id="rewardeditor-tab" data-bs-toggle="pill" data-bs-target="#rewardeditor" type="button" role="tab">
                        <i class="bi bi-gift me-2"></i>Reward Management
                    </button>
                    
                    <button class="nav-link" id="policies-tab" data-bs-toggle="pill" data-bs-target="#policies" type="button" role="tab">
                        <i class="bi bi-shield-check me-2"></i>Policies
                    </button>
                    
                    <button class="nav-link" id="analytics-tab" data-bs-toggle="pill" data-bs-target="#analytics" type="button" role="tab">
                        <i class="bi bi-graph-up me-2"></i>Analytics
                    </button>
                    
                    <a class="nav-link" href="<?php echo $google_targetUrl; ?>" target="_blank">
                        <i class="bi bi-google me-2"></i>Google Play Store
                        <i class="bi bi-box-arrow-up-right ms-auto"></i>
                    </a>
                    
                    <a class="nav-link" href="<?php echo $apple_targetUrl; ?>" target="_blank">
                        <i class="bi bi-apple me-2"></i>Apple App Store
                        <i class="bi bi-box-arrow-up-right ms-auto"></i>
                    </a>
                </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Content -->
            <div class="col-md-9">
                <div class="mb-3 text-end">
                    <a href="/admin/brands" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back To List of Businesses
                    </a>
                </div>
                <div class="tab-content" id="company-tab-content">
                    <!-- General Tab -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <div class="content-section">
                            <?php 
                            $componentmode = 'include';
                            include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/general-details.php'); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Details Tab -->
                    <div class="tab-pane fade" id="details" role="tabpanel">
                        <div class="content-section">
                            <?php 
                            $componentmode = 'include';
                            include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/company-details.php'); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Locations Tab -->
                    <div class="tab-pane fade" id="locations" role="tabpanel">
                        <div class="content-section">
                            <?php 
                            $componentmode = 'include';
                            include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/location-manager.php'); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Logo Management Tab -->
                    <div class="tab-pane fade" id="logos" role="tabpanel">
                        <div class="content-section">
                            <?php 
                            $componentmode = 'include';
                            include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/logo-manager.php'); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Form Field Mappings Tab -->
                    <div class="tab-pane fade" id="formfieldedit" role="tabpanel">
                        <div class="content-section">
                            <?php 
                            $componentmode = 'include';
                            include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/business-fieldmappings.php'); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Reward Editor Tab -->
                    <div class="tab-pane fade" id="rewardeditor" role="tabpanel">
                        <div class="content-section">
                            <?php 
                            $componentmode = 'include';
                            include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/reward-editor.php'); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Policies Tab -->
                    <div class="tab-pane fade" id="policies" role="tabpanel">
                        <div class="content-section">
                            <?php 
                            $componentmode = 'include';
                            include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/policy-manager.php'); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Analytics Tab -->
                    <div class="tab-pane fade" id="analytics" role="tabpanel">
                        <div class="content-section">
                            <?php 
                            $componentmode = 'include';
                            include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/analytics-dashboard.php'); 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap initializations
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize all popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Handle section parameter in URL to activate correct tab
    var urlParams = new URLSearchParams(window.location.search);
    var section = urlParams.get('section');
    if (section) {
        var tabTrigger = document.querySelector('#' + section + '-tab');
        if (tabTrigger) {
            var tab = new bootstrap.Tab(tabTrigger);
            tab.show();
        }
    }
    
    // Check for location extraction completion message
    var message = urlParams.get('message');
    if (message === 'location_extracted') {
        var locationModal = new bootstrap.Modal(document.getElementById('locationExtractionModal'));
        locationModal.show();
        
        // Switch to locations tab after modal is closed
        document.getElementById('locationExtractionModal').addEventListener('hidden.bs.modal', function () {
            var locationsTab = document.querySelector('#locations-tab');
            if (locationsTab) {
                var tab = new bootstrap.Tab(locationsTab);
                tab.show();
            }
        });
    }
});
</script>

<!-- Location Extraction Completion Modal -->
<div class="modal fade" id="locationExtractionModal" tabindex="-1" aria-labelledby="locationExtractionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="locationExtractionModalLabel">
                    <i class="bi bi-check-circle-fill me-2"></i>Location Extraction Complete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-3">
                    <i class="bi bi-geo-alt-fill text-success" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Location extraction completed successfully!</h4>
                    <p class="text-muted">The store locations for <?php echo htmlspecialchars($company_name); ?> have been extracted and saved.</p>
                    <hr>
                    <p class="mb-0">
                        <small class="text-muted">Click below to view the extracted locations or close this dialog to continue.</small>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="document.querySelector('#locations-tab').click();">
                    <i class="bi bi-geo-alt me-1"></i>View Locations
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>