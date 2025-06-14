<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
if (!empty($current_user_data['user_id']))  $current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$accountstats = $account->account_getstats();

$selectsused = 0;
$selectsleft = 0;
$selectsremoved = 0;

$plandatafeatures = $app->plandetail('details_id', $current_user_data['account_product_id']);

if (!$plandatafeatures) {
    // Handle error, e.g., log it or set default values
    session_tracking('$plandatafeatures error', 'no details found');
    $plandatafeatures = [];
} else {
    $selectsused = ($accountstats['business_pending'] + $accountstats['business_selected'] + $accountstats['business_success']);
    $selectsleft = ($plandatafeatures['max_business_select'] - $selectsused);

    $daysouttag = $plandatafeatures['celebration_tour_option_tag'];
    $daysout = $plandatafeatures['celebration_planning_days'];

    $userplan = $current_user_data['account_plan'];

    $selectstotal = $plandatafeatures['max_business_select']; // Start with the plan total

    // Calculate the used and removed selections
    $selectsused = $accountstats['business_pending'] + $accountstats['business_selected'] + $accountstats['business_success'];
    $selectsleft = $plandatafeatures['max_business_select'] - ($selectsused) + $accountstats['business_removed'];
    $selectsremoved = $accountstats['business_removed'];
}

// Adjust the total if the used selections and removed selections plus the plan total exceed a certain threshold
if ($selectsused + $selectsremoved > $selectstotal) {
    $selectstotal = $selectsused + $selectsremoved;
}

$addresslongtag = $display->formataddress();
$errormessage = '';

$total_enrollments = $selectsused;
$pending_percentage = 0;
$completed_percentage = 0;

if ($total_enrollments > 0) {
    $pending_percentage = ($selectsleft > 0) ? ($selectsused / $selectsleft) * 100 : 0;
    $completed_percentage = ($total_enrollments > 0) ? ($accountstats['business_success'] / $total_enrollments) * 100 : 0;
}

$profilecompletion = $account->profilecompletionratio($current_user_data);
$completiontag = str_replace('class="', 'class="', $profilecompletion['required_percentage_tag']);

// Calculate the percentage of enrollments used
$selectsused = isset($selectsused) ? $selectsused : 0;
$selectstotal = isset($selectstotal) && $selectstotal > 0 ? $selectstotal : 1;
$percentage_used = ($selectsused / $selectstotal) * 100;
// Calculate the percentage of enrollments remaining
$percentage_remaining = (($selectstotal - $selectsused) / $selectstotal) * 100;

// Calculate remaining enrollments
$selectsleft = $selectstotal - $selectsused;

$selectist = $session->get('goldmine_selectionList', '');
if ($selectist != '') {
    $count = count($selectist);
    $errormessage = '
  <div class="row">
  <div class="col-12">
  <div class="alert alert-info alert-dismissible fade show" role="alert">
  Your selection has been successfully recorded. You will receive an automated email to let you know when our system starts to process your ' . $qik->plural('enrollement', $count) . '.
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
</div>
</div>';

    $session->unset('goldmine_selectionList');
}

if ($current_user_data['account_plan'] != 'free') {
    $wizardmode = false;
    $wizard['section'] = 'enrollment';
    $wizard['step'] = 1;
    if (!empty($current_user_data['enrollment_mode']) && $current_user_data['enrollment_mode'] == 'wizard') {
        $wizardmode = true;
        $wizard['section'] = 'enrollment';
        $wizard['step'] = 1;

        $errormessage = '<div class="alert alert-success">We will walk you through the three step enrollment process.</div>';
        $transferpagedata['message'] = $errormessage;
        $transferpagedata['url'] = '/myaccount/profile';
        $transferpagedata = $system->endpostpage($transferpagedata);
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');
echo '
<div class="container mt-0 pt-0 main-content">
' .
    $display->formaterrormessage($errormessage) . '
  ';



    echo '    
<div class="container main-content mt-0 pt-0">
  <div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Enrollment Dashboard</h2>
  <a href="/myaccount/" class="btn btn-sm btn-outline-secondary">Back To Account Home</a>
</div>
';

#-------------------------------------------------------------------------------
# SHOW ENROLLMENT DETAILS
#-------------------------------------------------------------------------------

### ------------------------------------------------------------------------------
echo '<div class="content-panel mx-0 ">
<div class="col-12 mb-4">
    <!-- Enrollment Details Card -->
    <div class="card h-100 border-start-lg border-start-primary">
        <div class="card-body">
            <div class="h4 text-muted">Your Enrollment Profile</div>
            <div class="h3 text-center my-4">' . $completiontag . '</div>
            <div class="progress mb-3">
                <div class="progress-bar bg-' . $profilecompletion['required_percentage_color'] . '" role="progressbar" style="width: ' . $profilecompletion['required_percentage'] . '%;" aria-valuenow="' . $profilecompletion['required_percentage'] . '" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <a class="btn btn-outline-primary btn-sm" href="/myaccount/profile">
                    Edit Enrollment Profile
                </a>
                <a class="btn btn-outline-primary btn-sm ms-auto" href="/myaccount/enrollment-schedule" data-bs-toggle="tooltip" data-bs-placement="top" title="Change your enrollment schedule">
                    Change Schedule
                </a>
            </div>
        </div>
    </div>
</div>';

### ------------------------------------------------------------------------------

echo '<div class="col-12 mb-4">
    <!-- Brand Enrollment Card -->
    <div class="card h-100 border-start-lg border-start-secondary">
        <div class="card-body">
            <div class="h4 text-muted">Number of Enrollments</div>
            <div class="progress mb-3">
                <div class="progress-bar bg-warning" role="progressbar" style="width: ' . $pending_percentage . '%;" aria-valuenow="' . $pending_percentage . '" aria-valuemin="0" aria-valuemax="100"></div>
                <div class="progress-bar bg-success" role="progressbar" style="width: ' . $completed_percentage . '%;" aria-valuenow="' . $completed_percentage . '" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="text-center my-4">' . $selectsused . ' Pending / ' . $accountstats['business_success'] . ' Completed</div>
            <div class="mt-3 text-center">
                <a class="btn btn-outline-primary btn-sm" href="/myaccount/enrollment-history">
                    View enrollment history
                </a>
            </div>
        </div>
    </div>
</div>';

### ------------------------------------------------------------------------------

echo '<div class="col-12 mb-4">
    <!-- Available Enrollments Card -->
    <div class="card h-100 border-start-lg border-start-success">
        <div class="card-body">
            <div class="h4 text-muted" title="Used: ' . $selectsused . ' / Left: ' . $selectsleft . ' / Total:' . $selectstotal . '">Enrollments Available</div>
            <div class="progress mb-3">
                <div class="progress-bar bg-success" role="progressbar" style="width: ' . $percentage_remaining . '%;" aria-valuenow="' . $percentage_remaining . '" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="text-center my-4">' . ($selectsleft < 0 ? 0 : $selectsleft) . ' Remaining in your plan</div>';

if ($plandatafeatures['upgradeable'] == 1 || $plandatafeatures['upgradeable'] == 'Y') {
    echo '<div class="text-center mt-3">
                    <a class="btn btn-outline-success btn-sm" href="/myaccount/upgrade">
                        Upgrade plan
                    </a>
                </div>';
} else {
    echo '<div class="text-center mt-3">
                    <a class="btn button btn-primary" href="/myaccount/select">Select more '.$website['biznames'].' for enrollment</a>
                </div>';
}

echo '    </div>
    </div>
</div>
</div>

</div>
</div>
</div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();