<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$accountstats = $account->account_getstats();
#$plandetails = $plandetails_all = $app->plandetail('details');

$plandatafeatures=$app->plandetail('details_id', $current_user_data['account_product_id']);

$userplan = $current_user_data['account_plan'];

$user_id = $current_user_data['user_id'];

$userbirthdate = $current_user_data['birthdate'];
#$userbirthdate='1923-10-12';
$birthdates = $account->getBirthdates($userbirthdate, $plandatafeatures);


$selectsused = ($accountstats['business_pending'] + $accountstats['business_selected'] + $accountstats['business_success']);
$selectsleft = ($plandatafeatures['max_business_select'] - ($selectsused) + $accountstats['business_removed']);

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
$transferpage['message'] = $errormessage;




#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$transferpage = $system->startpostpage();

$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
$additionalstyles .= '
<style>.calendarbtn {
    display: inline-block;
    text-align: center;
    background-color: #ffffff;
    padding: 0; /* Remove padding to fix alignment */
    transition: background-color 0.3s ease;
    width: 90px; /* Keep width */
    height: 100px; /* Adjust height for proper space */
}

.calendarbtn:hover {
    background-color: #f7f7f7;
}

.calendar {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    padding: 0; /* Remove padding to align the content better */
    background-color: #f9f9f9;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
     height: 100%; /* Ensure the calendar block fills the button */
    width: 100%; /* Ensure full width */
}

.calendar-month {
    display: block;
    background-color: var(--bs-secondary); /* Use Bootstraps secondary color */
    color: white;
    font-weight: bold;
    padding: 6px 0; /* Add vertical padding */
    font-size: 16px;
    text-align: center;
    width: 100%;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    box-sizing: border-box; /* Ensure padding doesnt affect the size */
}

.calendar-day {
    display: block;
    font-size: 22px;
    color: #555;
    padding: 10px 0; /* Add some padding to center it vertically */
    text-align: center;
    width: 100%;
}

/* Align the form-check for better vertical alignment */
.form-check-inline {
    align-items: flex-start;
}

</style>';

echo '
<div class="container main-content">
<div class="row mt-5">' .
    $display->formaterrormessage($transferpage['message']);



    $daysouttag = $plandatafeatures['celebration_tour_option_tag'] ;
    $daysout = $plandatafeatures['celebration_planning_days'];
    /*
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
        */
$nextDate = $app->calculateNextOccurrence($userbirthdate, $daysout);



#-------------------------------------------------------------------------------
# CELEBRATE
#-------------------------------------------------------------------------------
echo '<div class="container">
<div class="row">
<div class="col-md-4">';

$birthdate = new DateTime($userbirthdate);
$currentYear = (new DateTime())->format('Y');
$birthdate->setDate($currentYear, $birthdate->format('m'), $birthdate->format('d'));

$icalendar_start_date = clone $birthdate;
$icalendar_start_date->modify('-' . $plandatafeatures['celebration_tour_days_before'] . ' days');

$icalendar_end_date = clone $birthdate;
$icalendar_end_date->modify('+' . $plandatafeatures['celebration_tour_days_after'] . ' days');

$icalendar_start_date_str = $icalendar_start_date->format('Y-m-d');
$icalendar_end_date_str = $icalendar_end_date->format('Y-m-d');
$tourlistdates = [];
$stmt = $database->prepare("SELECT * FROM bg_user_tours WHERE user_id = :user_id AND calendar_dt BETWEEN :start_date AND :end_date and status='active'");
$stmt->execute([':user_id' => $user_id, ':start_date' => $icalendar_start_date_str, ':end_date' => $icalendar_end_date_str]);
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($tours as $tour) {
    $tourlistdates[] = $tour['calendar_dt'];
}
$tourlistdates = array_unique($tourlistdates);

$enrollments = $account->getEnrollments($current_user_data['user_id'], 'active');


echo '
<div class="mb-4">
<!-- Celebration Tours card 1-->
<div class="card h-100 border-start-lg border-start-secondary">
<div class="card-body">
<div class="card-title">Your Celebration Tours</div>';


### DETERMINE BUILD AVAILABILITY
$buildable = false;
$tag = '';
$profilecompletion = $account->profilecompletionratio($current_user_data);
#breakpoint($profilecompletion);
if ($birthdates['birthday_in_plan']) $buildable = true;
if (empty($profilecompletion['required_percentage']))   $buildable = false;
if (!empty($profilecompletion['required_percentage']) &&  $profilecompletion['required_percentage'] < 100) {
    $buildable = false;
    $tag = '<p class="mt-3"><a href="/myaccount/profile"><small><i class="bi bi-exclamation-triangle-fill text-danger "></i> You need to complete your profile.</small></a></p>';
}

if ($buildable) {
    echo '
<div class="h3 text-center my-4"><a class="btn btn-primary button px-3" type="button" href="/myaccount/tour-build">Build A Celebration Tour</a></div>
';
} else {
    echo '
<div class="text-center my-4">
<a class="btn btn-secondard button px-5 disabled" type="button" href="#">Build A Tour (unavailable)</a>
' . $tag . '
</div>
';
}




echo '
<hr>
<div class="d-flex justify-content-between align-items-end">

<div>
Enrollments: <span class="badge rounded-pill bg-secondary">' . $enrollments['count'] . '</span><br>
Tours: <span class="badge rounded-pill bg-secondary">' . count($tourlistdates) . ' of ' . $plandatafeatures['celebration_max_tour_count'] . '</span>
</div>
<div>
<a class="icon-link icon-link-hover" href="/myaccount/tour-list"> View Tour List <span class="bi fas fa-chevron-right"></span></a>
</div>
</div>


</a>
</div>
</div>
</div>
';


echo '</div><div class="col-md-8">';

#echo print_r($dates,1);




if (!$birthdates['birthday_in_plan']) {
    echo '

<div class="card mb-4">
<div class="card-header text-danger fw-bold ">
<i class="bi bi-exclamation-triangle  text-primary"></i> Your birthday is too far out to view your Celebration Tour Information.
</div>

<div class="card-body">
<!-- Billing history table-->
<div class="">
<p>' . $daysouttag . '</p>
';

    if ($birthdates['recent'] == $birthdates['next']) {
        echo '
    <p>Your birthday: ' . $birthdates['recent_longformatted'] . '</p>
';
    } else {
        echo '
    <p>Your next birthday is: ' . $birthdates['recent'] . '</p>
    ';
    }

    if (!$birthdates['birthday_in_plan'])
        echo '
<p class="text-success">Please check back on ' . $nextDate['long_date'] . '</p>
';

    echo '
</div>
</div>

';
} else {
    $input['plandetails'] = $plandatafeatures;
    $input['current_user_data'] = $current_user_data;
    $input['birthdate'] = $userbirthdate;
    $input['plan'] = $userplan;
    #$input['loopstop']='dates';
    $input['loopstop'] = 'tours';
    $input['linkhref'] = '/myaccount/tour?date=';
    $input['navigation'] = 'off';
    $tourdatedetails = $account->generatetourcalendar($birthdates['today'], $length = 5, $input);
    echo '

    <div class="card mb-4">
    <div class="card-header  bg-body-tertiary">
    <h5 class="mb-0 text-success "><i class="bi bi-balloon-fill text-primary"></i> It\'s Celebration time.</h5>
  </div>
  
    
    <div class="card-body">
    <!-- Billing history table-->
    <div class="">
    <p>' . $daysouttag . '</p>
    ';

    if ($birthdates['recent'] == $birthdates['next']) {
        echo '
        <p>Your birthday is on: ' . $birthdates['recent_longformatted'] . '</p>
    ';
    } else {
        echo '    
        <p>Your next birthday is: ' . $birthdates['recent_longformatted'] . '</p>
        ';
    }

    if (!$birthdates['birthday_in_plan'])
        echo '
    <p class="text-success">Please check back on ' . $nextDate['long_date'] . '</p>
    ';

    echo '<div><span class="h6">View Your Upcoming Tours: </span>';
    echo $tourdatedetails;
    echo '
    </div>
    </div>
    </div>    
    ';
}
echo '</div></div>';


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

$dateresult = $app->calculateNextOccurrence($userbirthdate, 0);

echo '
<hr class="mt-5 mb-4">
<div class="card card-header-actions mb-4 d-none">
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
echo '</div>
</div>';



include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();