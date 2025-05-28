<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$company_id = $cid = $_GET['cid'] ?? 1994;
$section = $_GET['s'] ?? 'general'; // Default to general section if none specified

// Fetch the company details
$company = $app->getcompanydetails($company_id);

// Assign safe values if $company is null or fields are missing
$company_name = $company['company_name'] ?? 'Unknown Company';
$company_display_name = $company['company_display_name'] ?? $company_name;
$company_url = $company['company_url'] ?? '';
$signup_url = $company['signup_url'] ?? '';

// Generate store URLs
$google_targetUrl = 'https://play.google.com/store/search?q=' . urlencode($company_name) . '&c=apps&hl=en_US&gl=US';
$apple_targetUrl = 'https://www.apple.com/us/search/' . urlencode($company_name) . '?src=serp';

// Define navigation items
$nav_items = [
    'general' => ['icon' => 'house-door', 'label' => 'General'],
    'companywebsite' => [
        'icon' => 'globe', 
        'label' => 'Company Website',
        'external_url' => $company['signup_url']
    ],
    'viewsource' => ['icon' => 'code-slash', 'label' => 'View Source'],
    'editcompany' => ['icon' => 'pencil-square', 'label' => 'Edit Company'],
    'formfieldedit' => ['icon' => 'file-earmark-text', 'label' => 'Form Field Edit'],
    'rewardeditor' => ['icon' => 'gift', 'label' => 'Reward Editor'],
    'policies' => ['icon' => 'file-earmark-text', 'label' => 'Policies'],
    'chartsandgraphs' => ['icon' => 'bar-chart', 'label' => 'Charts and Graphs'],
    'googleplaystore' => [
        'icon' => 'google-play', 
        'label' => 'Google Play Store',
        'external_url' => $google_targetUrl
    ],
    'appleappstore' => [
        'icon' => 'apple', 
        'label' => 'Apple App Store',
        'external_url' => $apple_targetUrl
    ]
];

// Additional styles
$additionalstyles .= '
<style>
.list-group-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    white-space: nowrap;
}

.list-group-item .bi {
    margin-right: 10px;
}

.list-group-item .btn-link {
    padding: 0;
    margin-left: auto;
}

.section-content {
    background: white;
    padding: 20px;
    border-radius: 0.375rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
</style>';

// Start page content
echo '<div class="container-fluid main-content">
    <h1>BUSINESS EDITOR</h1>
    <div class="row">';

// Left Navigation Panel
echo '<div class="col-xl-2 col-lg-2 col-md-4 col-sm-12">
    <div class="list-group">';

foreach ($nav_items as $key => $item) {
    $active = ($section === $key) ? ' active' : '';
    $text_color = ($section === $key) ? ' text-white' : ' text-dark';
    
    echo '<div class="list-group-item list-group-item-action' . $active . '">';
    
    // Main link
    echo '<a href="?cid=' . $company_id . '&s=' . $key . '" 
             class="text-decoration-none' . $text_color . ' flex-grow-1">
             <i class="bi bi-' . $item['icon'] . ' me-3"></i>' . 
             $item['label'] . 
          '</a>';
    
    // External link button if applicable
    if (isset($item['external_url'])) {
        echo '<a href="' . $item['external_url'] . '" 
                 target="_blank" 
                 class="btn btn-link text-' . ($active ? 'white' : 'dark') . '">
                 <i class="bi bi-box-arrow-up-right"></i>
              </a>';
    }
    
    echo '</div>';
}

echo '</div></div>';

// Right Content Panel
echo '<div class="col-xl-10 col-lg-10 col-md-8 col-sm-12 main-content mt-0 pt-0">
    <div class="section-content">';

// Include the appropriate content file based on section
switch($section) {
    case 'general':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/brand-generaldetails.php');
        break;
    case 'companywebsite':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/brand-companywebsite.php');
        break;
    case 'viewsource':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/viewsource.php');
        break;
    case 'editcompany':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/brand-editcompany.php');
        break;
    case 'formfieldedit':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/brand-fieldmappings.php');
        break;
    case 'rewardeditor':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/rewardeditor.php');
        break;
    case 'policies':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/policies.php');
        break;
    case 'chartsandgraphs':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/brand-stats.php');
        break;
    case 'googleplaystore':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/app_google.inc');
        break;
    case 'appleappstore':
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/brandeditor_components/app_apple.inc');
        break;
}

echo '</div></div></div></div>';
echo '</div></div></div></div>';
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>