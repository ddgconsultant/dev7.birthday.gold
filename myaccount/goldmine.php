<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');




#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$accountstats = $account->account_getstats();
$plandetails = $app->plandetail('details');

$userplan = $current_user_data['account_plan'];



$selectsused = ($accountstats['business_pending'] + $accountstats['business_selected'] + $accountstats['business_success']);
$selectsleft = ($plandetails[$userplan]['max_business_select'] - ($selectsused) + $accountstats['business_removed']);

$addresslongtag = $display->formataddress();
$errormessage = '';
$selectist = $session->get('goldmine_selectionList', '');
#breakpoint($selectist);
if ($selectist != '') {
    $count = count($selectist);
    $errormessage = '<div class="alert alert-info">Your selection has been successfully recorded. 
You will receive an automated email to let you know when our system starts to process your ' . $qik->plural('enrollement', $count) . '</div>';
    $session->unset('goldmine_selectionList');
}



$headerattribute['additionalcss'] = '<link rel="stylesheet" href="/public/css/myaccount.css">';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');



echo '
<div class="container-xl px-4 mt-4">
<!-- Account page navigation-->
';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php');


echo '<hr class="mt-0 mb-4">
<div class="container">
<div class="row">' .
    $display->formaterrormessage($errormessage);






#-------------------------------------------------------------------------------
# SHOW ENROLLMENT SCHEDULE
#-------------------------------------------------------------------------------
$schedulestatus = 'notset';

$sql = "SELECT date_format(max(create_dt), '%m/%d/%Y') as lastupdate  FROM bg_user_schedules WHERE user_id = " . $current_user_data['user_id'] . " and status='active'";
$stmt = $database->prepare($sql);
$stmt->execute();

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($results[0]['lastupdate']) && $current_user_data['account_plan'] != 'free') {

    echo '<div class="col-lg-4 mb-4">
<!-- Selection card -->
<div class="card h-100 border-start-lg border-start-primary" >
<div class="card-body">
<div class="small text-muted">Enrollment Schedule</div>
<div class="h3 text-center">Please Set Your Schedule</div>';

    echo '<a class="text-arrow-icon small text-success fw-bold" href="/myaccount/enrollmentschedule">
Set schedule
<i class="bi bi-arrow-right-square"></i>
</a>';

    echo '</div></div></div>';
} else {


    #-------------------------------------------------------------------------------
    # SELECTIONS
    #-------------------------------------------------------------------------------

    switch ($current_user_data['account_plan']) {
        case 'free':
            echo '<div class="col-lg-4 mb-4">
    <!-- Selection card -->
    <div class="card h-100 border-start-lg border-start-primary" id="selectionCard">
    <div class="card-body">
    <div class="small text-muted">Free Account</div>
    <div class="h3 text-center">   <a class="text-arrow-icon small text-success fw-bold" href="/myaccount/select">
    Browse Businesses <i class="bi bi-arrow-right-square"></i>
</a></div>';
            break;

        default:
            echo '<div class="col-lg-4 mb-4">
<!-- Selection card -->
<div class="card h-100 border-start-lg border-start-primary" id="selectionCard">
<div class="card-body">
<div class="small text-muted">Number of Businesses Selected</div>
<div class="h3 text-center">' . $accountstats['business_selected'] . ' Selected</div>';
            break;
    }


    if ($selectsleft > 0) {
        echo '<div class="d-flex justify-content-between">
    <a class="text-arrow-icon small text-success fw-bold" href="/myaccount/select">
        Add more businesses <i class="bi bi-arrow-right-square"></i>
    </a>

    <a class="text-arrow-icon small text-success fw-bold ms-auto" href="/myaccount/enrollmentschedule" data-toggle="tooltip" data-placement="top" title="Updated: ' . $results[0]['lastupdate'] . '">
        Change Schedule <i class="bi bi-arrow-right-square"></i>
    </a>
</div>';
    }

    echo '</div></div></div>';
}

echo '
<div class="col-lg-4 mb-4">
<!-- Billing card 2-->
<div class="card h-100 border-start-lg border-start-secondary">
<div class="card-body">
<div class="small text-muted">Number of Businesses Enrollment</div>
<div class="h3 text-center">' . ($accountstats['business_pending'] + $accountstats['business_selected']) . ' Pending / ' . $accountstats['business_success'] . ' Completed</div>
<a class="text-arrow-icon small text-secondary" href="myaccount-enrollmenthistory">
View enrollment history
<i class="bi bi-arrow-right-square"></i>
</a>
</div>
</div>
</div>
<div class="col-lg-4 mb-4">
<!-- Billing card 3-->
<div class="card h-100 border-start-lg border-start-success">
<div class="card-body">
<div class="small text-muted">Enrollments Available in your plan</div>

';
echo '<div class="h3 text-center">' . ($selectsleft < 0 ? 0 : $selectsleft) . ' Remaining</div>';


if ($plandetails[$userplan]['upgradeable'] == 1) {
    echo ' <a class="text-arrow-icon small text-success" href="/myaccount/upgrade">
Upgrade plan   <i class="bi bi-arrow-right-square"></i>
</a>
';
}
echo '
</div>
</div>
</div>
</div>

<hr class="mt-0 mb-4">

';

switch ($userplan) {
    case 'free':
        $daysouttag = $plandetails[$userplan]['celebration_tour_option_tag'] . ' - Click Here to upgade.';
        $daysout = $plandetails[$userplan]['celebration_planning_days'];
        break;
    case 'gold':
        $daysouttag = $plandetails[$userplan]['celebration_tour_option_tag'];
        $daysout = $plandetails[$userplan]['celebration_planning_days'];
        break;
    case 'life':
        $daysouttag = $plandetails[$userplan]['celebration_tour_option_tag'];
        $daysout = $plandetails[$userplan]['celebration_planning_days'];
        break;
    default:
        $daysouttag = 'This feature is not available on the FREE plan - Click Here to upgade.';
        $daysout = 0;
        break;
}
$nextDate = $app->calculateNextOccurrence($current_user_data['birthdate'], $daysout);




echo '

<div class="card mb-4">
<div class="card-header text-danger fw-bold ">
<i class="bi bi-exclamation-triangle  text-primary"></i> Your birthday is too far out to view your Celebration Tour Information.
</div>

<div class="card-body">
<!-- Billing history table-->
<div class="">
<p>' . $daysouttag . '</p>
<p>Your birthday is: ' . $current_user_data['birthdate'] . '</p>
<p class="text-success">Please check back on ' . $nextDate['long_date'] . '</p>
</div>
</div>

';


#### errors in data
// Check if any required fields are missing
$requiredFields = array(
    'mailing_address' => 'Address',
    'city' => 'City',
    'state' => 'State',
    'zip_code' => 'Zip'
);
$missingFields = array();

foreach ($requiredFields as $field => $label) {
    if (empty($current_user_data[$field])) {
        $missingFields[] = $label; // Use the label instead of the field name for display
    }
}

// If there are missing fields, display the alert message
if (!empty($missingFields)) {
    echo '<div class="alert alert-danger m-2" role="alert">';
    echo 'The "Celebration Tour" feature requires your account details to be provided:';
    echo '<ul>';
    foreach ($missingFields as $field) {
        echo '<li>' . $field . '</li>';
    }
    echo '</ul>';
    echo '<div class="d-flex justify-content-end m-2">';
    echo '<a href="/myaccount/account" class="btn btn-dark">Complete Account Details</a>';
    echo '</div>';
    echo '</div>';
}


echo '

</div>
';

$dateresult = $app->calculateNextOccurrence($current_user_data['birthdate'], 0);

echo '

<hr class="mt-5 mb-4">
<div class="card card-header-actions mb-4">
<div class="card-header">
<h2 class="mt-3">Celebration Tour Example</h2>
</div><div class="card-body p-5 m-5">

<!-- Payment methods card-->
<div class="card card-header-actions mb-4">
<div class="card-header">
Your Scheduled Celebration Tour - ' . $dateresult['long_date'] . '
<button class="btn btn-sm btn-primary ms-3" type="button">Add Stops</button>
</div>
<div class="card-body px-0">
<!-- Payment method 1-->
<div class="d-flex align-items-center justify-content-between px-4">
<div class="d-flex align-items-center">
<i class="bi bi-buildings-fill h3"></i>
<div class="ms-4">
<div class="small">Your Home</div>
<div class="text-xs text-muted">' . $addresslongtag . '</div>
</div>
</div>
<div class="ms-4 small">
<!--  <div class="badge bg-light text-dark me-3">Default</div> -->
<a href="#!">Edit Address</a>
</div>
</div>
<hr>

<!-- Payment method 1-->
<div class="d-flex align-items-center justify-content-between px-4">
<div class="d-flex align-items-center">
<i class="bi bi-buildings-fill h3"></i>
<div class="ms-4">
<div class="small">Company 123</div>
<div class="text-xs text-muted">Parker, CO</div>
</div>
</div>
<div class="ms-4 small">
<div class="badge bg-light text-dark me-3">Closest Location</div>
<a href="#!">Pick Different Location</a>
</div>
</div>
<hr>
<!-- Payment method 2-->
<div class="d-flex align-items-center justify-content-between px-4">
<div class="d-flex align-items-center">
<i class="bi bi-buildings-fill h3"></i>
<div class="ms-4">
<div class="small">Resturant ABC</div>
<div class="text-xs text-muted">Aurora, Colorado</div>
</div>
</div>
<div class="ms-4 small">
<div class="badge bg-light text-dark me-3">Closest Location</div>
<a href="#!">Pick Different Location</a>
</div>
</div>
<hr>
<!-- Payment method 3-->
<div class="d-flex align-items-center justify-content-between px-4">
<div class="d-flex align-items-center">
<i class="bi bi-buildings-fill h3"></i>
<div class="ms-4">
<div class="small">Cafe 567</div>
<div class="text-xs text-muted">Denver, Colorado</div>
</div>
</div>
<div class="ms-4 small">
<div class="badge bg-light text-dark me-3">Closest Location</div>
<a href="#!">Pick Different Location</a>
</div>
</div>
</div>
</div>
<!-- Billing history card-->
<div class="card mb-4">
<div class="card-header">Map and Directions</div>
<div class="card-body p-0">
<!-- Billing history table-->
<div class="table-responsive table-billing-history">
<table class="table mb-0">                   
<tbody>
<tr>
<td>
<img src="/public/images/samplemap.jpg" height=1000 width="100%">
</td>

</tr>

</tbody>
</table>
</div>
</div>

</div>

</div>  </div>  <!-- end accordian-item -->

</div>    </div>  <!-- end tour -->
</div>
</div>
';


if ($selectsleft > 0) {


    $footerattribute['postfooter'] = '
<script>
// Function to apply the flash effect
function applyFlashEffect() {
const selectionCard = document.getElementById("selectionCard");
selectionCard.classList.add("flash");
setTimeout(() => {
selectionCard.classList.remove("flash");
}, 1000); // Remove the flash class after 1 second (same duration as the CSS animation)
}

// Call the function to apply the flash effect
applyFlashEffect();
</script>
';
}

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
