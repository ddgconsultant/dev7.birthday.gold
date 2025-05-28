<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// No form handling needed for this page

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_admin_leftpanel.inc');

$additionalstyles .= '
<style>
.system-icon {
    max-height: 75%;
    max-width: 75%;
}
.system-spacer {
    display: inline-block;
    width: 24px;
    height: 24px;
}
</style>
';

echo '    
<div class="container main-content mt-0 pt-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">System Status</h2>
        <a href="/admin/" class="btn btn-sm btn-outline-secondary">Back to Dashboard</a>
    </div>';

echo '<div class="card">
    <div class="card-body">';

// Fetch system availability data
$sql = "SELECT a.*, ifnull(b.icon, '') icon
        FROM bg_system_availability a
        JOIN bg_systems b ON a.system_id = b.id
        WHERE a.status = 'A' AND b.status = 'A'";
$stmt = $database->prepare($sql);
$stmt->execute();
$logrows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($logrows as $row) {
    $state = $system->checkAvailabilityAndUpdateStatus($row['url'], $row['port']);
    
    // Define icon and color based on the state
    $icon = $state ? 'bi-check-circle' : 'bi-exclamation-triangle';
    $color = $state ? 'text-success' : 'text-danger';

    // Prepare icon HTML
    $iconHtml = !empty($row['icon']) 
        ? '<img class="img-fluid p-2 system-icon" src="/public/images/system_icons/' . str_replace('/store/icons/', '', $row['icon']) . '" alt="">' 
        : '<span class="system-spacer"></span>';

    // Output server status
    echo '<div class="d-flex align-items-center mb-3 row">
            <div class="col-1">' . $iconHtml . '</div>
            <div class="col-11">
                <i class="bi ' . $icon . ' ' . $color . ' me-2 fw-bold"></i>
                <span class="' . $color . ' fs-4">' . 
                    htmlspecialchars($row['name']) . ': <b>' . 
                    ($state ? 'Available' : 'Unavailable') . '</b>
                </span>
            </div>
          </div>';
    
    flush();
}

echo '</div></div></div>';
echo '</div></div></div>';
$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();