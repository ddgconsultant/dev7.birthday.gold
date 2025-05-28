<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Get company ID from the URL parameter and validate
$company_id = $cid = isset($_GET['cid']) ? $qik->decodeId($_GET['cid']) : null;

// Redirect if no company ID is provided
if (empty($company_id)) {
    header('Location: /admin/businesses');
    exit;
}

// Fetch the company details
$company = $app->getcompanydetails($company_id);

// Default iframe style
$iframestyletagnoscale = 'style="width:100%; height:80vh; border:none; overflow-y:auto;"';

// Assign safe values if $company is null or fields are missing
$company_name = $company['company_name'] ?? 'Unknown Company';
$company_display_name = $company['company_display_name'] ?? $company_name;
$category = $company['category'] ?? '';
$display_category = $company['display_category'] ?? '';
$company_url = $company['company_url'] ?? '';
$signup_url = $company['signup_url'] ?? '';

// URLs for app stores
$google_targetUrl = 'https://play.google.com/store/search?q=' . urlencode($company_name) . '&c=apps&hl=en_US&gl=US';
$apple_targetUrl = 'https://www.apple.com/us/search/' . urlencode($company_name) . '?src=serp';

// Additional styles - with fixed panel - no apostrophes in comments
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
    flex-shrink: 0; /* Prevents shrinking the button */
}

.list-group-item .d-flex {
    justify-content: start;
    align-items: center;
}

.main-panel {
    display: flex;
    flex-direction: column;
    justify-content: flex-start; /* Align content to the top */
    padding-top: 0 !important;   /* Remove any unwanted padding at the top */
    margin-top: 0 !important;    /* Remove any unwanted margin at the top */
}

/* Fixed left panel styles */
.fixed-sidebar {
    position: fixed;
    width: 25%; /* Match the width of a col-3 */
    top: 80px; /* Adjust based on your header height */
    bottom: 0;
    overflow-y: auto;
    z-index: 100;
    padding-right: 15px;
}

/* Sidebar title */
.sidebar-title {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

/* Make the right panel have proper margin to avoid overlap */
.content-wrapper {
    margin-left: 25%; /* Match the width of the fixed sidebar */
    width: 75%; /* Take the remaining width */
    padding-left: 15px; /* Add some spacing between sidebar and content */
    padding-right: 15px;
}

/* Override link style for active items */
.list-group-item.active {
    background-color: #0d6efd;
    color: white;
}

/* Ensure the content container has full-width */
.main-layout {
    width: 100%;
}

/* For smaller screens, disable fixed positioning */
@media (max-width: 991px) {
    .fixed-sidebar {
        position: relative;
        width: 100%;
        top: auto;
        margin-bottom: 20px;
    }

    .content-wrapper {
        margin-left: 0;
        width: 100%;
    }
}
</style>';

// Get selected section
$selectedSection = isset($_GET['section']) ? $_GET['section'] : 'general';

// Render page content - new structure with title in sidebar
echo '
<div class="container-fluid main-content">
    <div class="main-layout">
        <div class="row">
            <!-- Left Fixed Navigation Panel with Title -->
            <div class="fixed-sidebar">
                <!-- Title in sidebar -->
                <div class="sidebar-title">
                    <h1>BUSINESS EDITOR</h1>
                </div>
                
                <div class="list-group" id="companyTab" role="tablist">
                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'general' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=general">
                        <i class="bi bi-house-door me-3"></i> General
                    </a>

                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'companywebsite' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=companywebsite">
                        <i class="bi bi-globe me-3"></i> Company Website
                        <button class="btn btn-link p-0 ms-auto" onclick="openCompanyWebsite()">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </button>
                    </a>

                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'viewsource' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=viewsource">
                        <i class="bi bi-code-slash me-3"></i> View Source
                    </a>

                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'editbusiness' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=editbusiness">
                        <i class="bi bi-pencil-square me-3"></i> Edit Company
                    </a>

                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'formfieldedit' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=formfieldedit">
                        <i class="bi bi-file-earmark-text me-3"></i> Form Field Mapping
                    </a>

                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'rewardeditor' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=rewardeditor">
                        <i class="bi bi-gift me-3"></i> Reward Editor
                    </a>

                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'policies' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=policies">
                        <i class="bi bi-file-earmark-text me-3"></i> Policies
                    </a>

                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'chartsandgraphs' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=chartsandgraphs">
                        <i class="bi bi-bar-chart me-3"></i> Charts and Graphs
                    </a>

                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'googleplaystore' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=googleplaystore">
                        <i class="bi bi-google-play me-3"></i> Google Play Store
                        <button class="btn btn-link p-0 ms-auto" onclick="openGooglePlayStore(\'' . $google_targetUrl . '\')">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </button>
                    </a>

                    <a class="list-group-item list-group-item-action d-flex text-start ' . ($selectedSection == 'appleappstore' ? 'active' : '') . '" href="?cid=' . $_GET['cid'] . '&section=appleappstore">
                        <i class="bi bi-apple me-3"></i> Apple App Store
                        <button class="btn btn-link p-0 ms-auto" onclick="openAppleAppStore(\'' . $apple_targetUrl . '\')">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </button>
                    </a>
                </div>
            </div>
            
            <!-- Right Content Area -->
            <div class="content-wrapper">
                <div class="main-panel bg-white">';

// Including the appropriate content based on the selected section
$bid = $company_id;
$business_id = $company_id;
$business = $company;
$componentmode = 'include';

switch($selectedSection) {
    case 'general':
        echo '<h3>General</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/business-generaldetails.php');
        break;
    case 'companywebsite':
        echo '<h3>Business Website</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/business-website.php');
        break;
    case 'viewsource':
        echo '<h3>View Source</h3>';
        $url = $business['signup_url'];
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/viewsource.php');
        break;
    case 'editbusiness':
        echo '<h3>Edit Business</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/business-editbusiness.php');
        break;
    case 'formfieldedit':
     #   echo '<h3>Form Field Edit</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/business-fieldmappings.php');
        break;
    case 'rewardeditor':
        echo '<h3>Reward Editor</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/rewardeditor.php');
        break;
    case 'policies':
        echo '<h3>Policies</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/policies.php');
        break;
    case 'chartsandgraphs':
        echo '<h3>Charts and Graphs</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/business-stats.php');
        break;
    case 'googleplaystore':
        echo '<h3>Google Play Store</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/app_google.php');
        break;
    case 'appleappstore':
        echo '<h3>Apple App Store</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/app_apple.php');
        break;
    default:
        echo '<h3>General</h3>';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/businesseditor_components/business-generaldetails.php');
}

echo '
                </div>
            </div>
        </div>
    </div>
</div>';
?>

<script>
// Calculate and adjust fixed panel height on page load and resize
document.addEventListener('DOMContentLoaded', function() {
    adjustSidebarPosition();
    window.addEventListener('resize', adjustSidebarPosition);
});

function adjustSidebarPosition() {
    const headerHeight = document.querySelector('header') ? document.querySelector('header').offsetHeight : 60;
    const sidebar = document.querySelector('.fixed-sidebar');
    
    if (sidebar && window.innerWidth > 991) {
        sidebar.style.top = (headerHeight + 20) + 'px'; // Add some padding
    } else if (sidebar) {
        sidebar.style.top = 'auto'; // Reset for mobile
    }
}

function openGooglePlayStore(url) {
    const top = window.screenY + 100;
    window.open(url, '_googleplaystore', `width=1000,height=700,left=500,top=${top}`);
}

function openAppleAppStore(url) {
    const top = window.screenY + 100;
    window.open(url, '_appleappstore', `width=1000,height=700,left=1000,top=${top}`);
}

function openViewSource() {
    const url = '<?php echo $dir['bge_web'] . "/viewsource.php?url=" . urlencode($business['signup_url']) . "&bid=" . $business_id; ?>';
    const top = window.screenY + 100;
    window.open(url, '_viewsource', `width=1000,height=1200,left=2000,top=${top}`);
}

function openCompanyWebsite() {
    const url = '<?php echo $company['signup_url']; ?>';
    const top = window.screenY + 100;
    window.open(url, '_companywebsite', `width=1200,height=1200,left=1500,top=${top}`);
}
</script>
<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>