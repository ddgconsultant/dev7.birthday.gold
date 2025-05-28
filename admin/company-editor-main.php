<?php
//company-editor-main.php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$company_id = $cid = $_GET['cid'] ?? null;

// Fetch the company details
$company = $app->getcompanydetails($company_id);

// Assign safe values if $company is null or fields are missing
$company_name = $company['company_name'] ?? 'Unknown Company';
$company_display_name = $company['company_display_name'] ?? $company_name;
$category = $company['category'] ?? '';
$company_url = $company['company_url'] ?? '';
$signup_url = $company['signup_url'] ?? '';

// URLs for app stores
$google_targetUrl = 'https://play.google.com/store/search?q=' . urlencode($company_name) . '&c=apps&hl=en_US&gl=US';
$apple_targetUrl = 'https://www.apple.com/us/search/' . urlencode($company_name) . '?src=serp';

// Additional styles
$additionalstyles .= '
<style>
.list-group-item {
    display: flex;
    justify-content: start;
    align-items: center;
    white-space: nowrap;
}

.list-group-item .bi {
    margin-right: 10px;
}

.list-group-item button {
    flex-shrink: 0;
}

.list-group-item .d-flex {
    justify-content: start;
    align-items: center;
}

.tab-pane {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    padding-top: 0 !important;
    margin-top: 0 !important;
}
</style>';

// Render page content
    echo '    
    <div class="container main-content mt-0 pt-0">
      <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Company Editor</h2>
      <a href="/admin/brands" class="btn btn-sm btn-outline-secondary">Back To List of Businesses</a>
    </div>
    ';
    echo '    
   <div class="row">';

// TABS - LEFT SIDE
echo '
    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-12">
        <div class="list-group" id="companyTab" role="tablist">
            <a class="list-group-item list-group-item-action active d-flex text-start" id="general-tab" data-bs-toggle="list" href="#general" role="tab" aria-controls="general">
                <i class="bi bi-house-door me-3"></i> General
            </a>

            <a class="list-group-item list-group-item-action d-flex text-start" id="locations-tab" data-bs-toggle="list" href="#locations" role="tab" aria-controls="locations">
                <i class="bi bi-geo-alt me-3"></i> Locations
            </a>

            <a class="list-group-item list-group-item-action d-flex text-start" id="formfieldedit-tab" data-bs-toggle="list" href="#formfieldedit" role="tab" aria-controls="formfieldedit">
                <i class="bi bi-file-earmark-text me-3"></i> Form Field Edit
            </a>
            
            <a class="list-group-item list-group-item-action d-flex text-start" id="rewardeditor-tab" data-bs-toggle="list" href="#rewardeditor" role="tab" aria-controls="rewardeditor">
                <i class="bi bi-pencil-square me-3"></i> Reward Editor
            </a>


            <a class="list-group-item list-group-item-action d-flex text-start" id="policies-tab" data-bs-toggle="list" href="#policies" role="tab" aria-controls="policies">
                <i class="bi bi-shield-check me-3"></i> Policies
            </a>

            <a class="list-group-item list-group-item-action d-flex text-start" id="analytics-tab" data-bs-toggle="list" href="#analytics" role="tab" aria-controls="analytics">
                <i class="bi bi-graph-up me-3"></i> Analytics
            </a>

            <a class="list-group-item list-group-item-action d-flex text-start" id="googleplaystore-tab" data-bs-toggle="list" href="#googleplaystore" role="tab" aria-controls="googleplaystore">
                <i class="bi bi-google me-3"></i> Google Play Store
                <button class="btn btn-link p-0 ms-auto" onclick="openGooglePlayStore(\'' . $google_targetUrl . '\')">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
            </a>

            <a class="list-group-item list-group-item-action d-flex text-start" id="appleappstore-tab" data-bs-toggle="list" href="#appleappstore" role="tab" aria-controls="appleappstore">
                <i class="bi bi-apple me-3"></i> Apple App Store
                <button class="btn btn-link p-0 ms-auto" onclick="openAppleAppStore(\'' . $apple_targetUrl . '\')">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
            </a>
        </div>
    </div>';

// TAB CONTENT - RIGHT SIDE
echo '
    <div class="col-xl-9 col-lg-9 col-md-8 col-sm-12">
        <div class="tab-content" id="companyTabContent">
            <!-- General -->
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">';
                include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/general-details.php');
echo '
           

            <!-- Locations -->
            <div class="tab-pane fade" id="locations" role="tabpanel" aria-labelledby="locations-tab">';
                include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/location-manager.php');
echo '
            </div>

            <!-- Form Field Edit -->
            <div class="tab-pane fade" id="formfieldedit" role="tabpanel" aria-labelledby="formfieldedit-tab">';
                include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/field-mappings.php');
echo '
            </div>

                        <!-- Reward Edit -->
            <div class="tab-pane fade" id="rewardeditor" role="tabpanel" aria-labelledby="rewardeditor-tab">';
                include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/reward-editor.php');
echo '
            </div>

            <!-- Policies -->
            <div class="tab-pane fade" id="policies" role="tabpanel" aria-labelledby="policies-tab">';
                include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/policy-manager.php');
echo '
            </div>

            <!-- Analytics -->
            <div class="tab-pane fade" id="analytics" role="tabpanel" aria-labelledby="analytics-tab">';
                include($_SERVER['DOCUMENT_ROOT'] . '/admin/companyeditor_components/analytics-dashboard.php');
echo '
            </div>

            <!-- Google Play Store -->
            <div class="tab-pane fade" id="googleplaystore" role="tabpanel" aria-labelledby="googleplaystore-tab">
                <div class="embed-responsive" style="height: 80vh;">
                    <iframe class="embed-responsive-item w-100 h-100" src="' . $google_targetUrl . '" style="border:none;"></iframe>
                </div>
            </div>

            <!-- Apple App Store -->
            <div class="tab-pane fade" id="appleappstore" role="tabpanel" aria-labelledby="appleappstore-tab">
                <div class="embed-responsive" style="height: 80vh;">
                    <iframe class="embed-responsive-item w-100 h-100" src="' . $apple_targetUrl . '" style="border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>';
?>

<script>
// Window opener functions
function openGooglePlayStore(url) {
    const top = window.screenY + 100;
    window.open(url, '_googleplaystore', `width=1000,height=700,left=500,top=${top}`);
}

function openAppleAppStore(url) {
    const top = window.screenY + 100;
    window.open(url, '_appleappstore', `width=1000,height=700,left=1000,top=${top}`);
}

function openCompanyWebsite() {
    const url = '<?php echo $company['signup_url']; ?>';
    const top = window.screenY + 100;
    window.open(url, '_companywebsite', `width=1200,height=1200,left=1500,top=${top}`);
}

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
});
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>