<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
#breakpoint($_REQUEST);


#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$user_id = $current_user_data['user_id'];
#$plandetails_all = $app->plandetail('details');

$plandatafeatures=$plandetails_all=$app->plandetail('details_id', $current_user_data['account_product_id']);

$userplan = $current_user_data['account_plan'];
$plandetails = $plandatafeatures;
#breakpoint($plandetails);
$birthdate = new DateTime($current_user_data['birthdate']);
if ($birthdate === false) {
    // Handle invalid date format
    die('Invalid birthdate format');
}
$errormessage = '';
$finaloutput = '';
$birthdate = new DateTime($current_user_data['birthdate']);
$currentYear = (new DateTime())->format('Y');
$birthdate->setDate($currentYear, $birthdate->format('m'), $birthdate->format('d'));

$icalendar_start_date = clone $birthdate;
$icalendar_start_date->modify('-' . $plandetails['celebration_tour_days_before'] . ' days');

$icalendar_end_date = clone $birthdate;
$icalendar_end_date->modify('+' . $plandetails['celebration_tour_days_after'] . ' days');


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
$showbusinesses = false;



#-------------------------------------------------------------------------------
# SAVE TOUR
#-------------------------------------------------------------------------------

if ($app->formposted()) {
    #breakpoint($_REQUEST);
    $tourdt = $_POST['calendar_date'] ?? '';
    $listofcompanies = $_POST['selectedCompanies'] ?? '';
    if (!empty($tourdt) && !empty($listofcompanies)) {
        $startdt = $tourdt . ' 00:00:01';
        $enddt = $tourdt . ' 23:59:59';
        foreach ($listofcompanies as $companyid) {
            $stmt = $database->prepare("insert bg_user_tours (user_id, company_id, calendar_dt, tour_start_dt, tour_end_dt, create_dt, modify_dt, status) 
values (:user_id, :company_id, :calendar_dt, :start_dt, :end_dt,  now(), now(), 'active')");
            $stmt->execute([':user_id' => $user_id, ':company_id' => $companyid, ':calendar_dt' => $tourdt, ':start_dt' => $startdt, ':end_dt' => $enddt]);
        }
        $errormessage = '<div class="alert alert-success">Your tour has been created.</div>';
        $transferpage['url'] = '/myaccount/celebrate';
        $transferpage['message'] = $errormessage;
        $system->endpostpage($transferpage);
        exit;
    }
    $errormessage = '<div class="alert alert-danger">You have to select some '.$website['biznames'].' to add to your tour.</div>';
    $transferpage['url'] = '/myaccount/tour-build';
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
    exit;
}



#-------------------------------------------------------------------------------
# RETURN ENROLLED BUSINESES
#-------------------------------------------------------------------------------
// Build the prepared statement with the IN clause using named placeholders
$apptype = $current_user_data['profile_phone_type'];
#$apptype='iphone';
#
if (!empty($_GET['date'])) {
    #if (!empty($_GET['date']) && !empty($_GET['_token'])) {
    $sql = "SELECT uc.*, c.company_name , c.appgoogle, c.appapple , c.description, c.short_description 
FROM bg_user_companies uc, bg_companies c WHERE uc.company_id=c.company_id 
and user_id = " . $current_user_data['user_id'] . ' and uc.status in ("success", "existing")  order by uc.modify_dt desc ';
    $output = '';


    // Prepare the statement
    $stmt = $database->prepare($sql);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $showbusinesses = true;

        $showcompany = true;
        $removetag = '<p><a class="text-danger remove-link" href="#" data-id="' . $row['user_company_id'] . '">Remove</a></p>';

        $applink = $display->applink($apptype, $row);
        $appicon = $applink['applink'];
        $qrcode = $applink['qrlink'];

        $status_sign = '<i class="bi bi-question-diamond-fill text-warning"></i>';
        $statusmessage = '<p class="text-warning p-0 m-0"></p>';
        $removetag = '';


        // Query to check if a record exists in bg_user_tours for the given date and company_id
        $date = $_GET['date'];
        $company_id = $row['company_id'];
        $checkEnrollmentQuery = "SELECT * FROM bg_user_tours WHERE calendar_dt = :date AND company_id = :company_id and user_id= " . $current_user_data['user_id'] . "";
        $checkStmt = $database->prepare($checkEnrollmentQuery);
        $checkStmt->execute([':date' => $date, ':company_id' => $company_id]);

        // If a matching record exists, set checkbox as checked and update the label
        // If a matching record exists, set checkbox as checked and update the label
        $isChecked = $checkStmt->rowCount() > 0;

        $iconHTML = $isChecked
            ? '<i class="bi bi-check-square-fill text-success"></i>'
            : '<i class="bi bi-check2-square text-black-50"></i>';

        $labelClass = $isChecked ? "" : "";

        $status_sign = '
<input class="form-check-input addcompany d-none p-0 m-0" type="checkbox" name="addcompany' . $row['company_id'] . '" value="' . $row['company_id'] . '"  id="addcompany' . $row['company_id'] . '" data-company="' . $row['company_name'] . '"' . ($isChecked ? ' checked' : '') . '>
<label class="h1 form-check-label ' . $labelClass . '" for="addcompany' . $row['company_id'] . '">' . $iconHTML . '</label>
';


        // Now you can use $statusCounters to get the count for each status.
        if ($showcompany) {
            $timetag = $qik->timeago($row['modify_dt']);
            $statusmessage = $timetag['message'] = '';
            $output .= '
<tr>
<td scope="row px-1 mx-1" class="align-middle align-items-center text-center" style="width:45px">' . str_replace('class="', 'class="h3 ', $status_sign) . '' . $removetag . '</td>
';

            #$output.='<td>   <img src="'. $display->companyimage($item_company['company_id'] . '/' . $row['company_logo']).'" class="card-img-top img-responsive" alt="" /></td>';
            $output .= '<td class="text-left align-middle">
<h4 class="mb-0 pb-0 pe-5">' . $row['company_name'] . '</h4>
' . $statusmessage . '  
<p class="p-0 m-0 small">' . $row['description'] . '</p>
<p class="p-0 m-0">' . $timetag['message'] . '</p>
</td>
<td class="text-center align-middle">' . $appicon . $qrcode . '</td>
</tr>
';
        }
    }

    $finaloutput = '
<div class="table-responsive">
<table class="table table-hover">
<thead class="table-secondary my-0 py-0">
<tr class="my-0 py-0">
<th scope="col" class="text-center"><i class="bi bi-check2-square text-black-50"></i></th>
<th scope="col" class="text-left">Company</th>
<th scope="col" class="text-center" style="width:145px;">  
<a href="#" id="showlinks">
<i class="bi bi-link-45deg text-black"></i>
</a> / <a href="#" id="showqrcodes">
<i class="bi bi-qr-code-scan text-black"></i>
</a>
</th>

</tr>
</thead>
<tbody>   
' . $output . '
</tbody>
</table>
</div>

';

    if (!$showbusinesses) {
        $showbusinesses = true;
        $finaloutput = 'No enrolled '.$website['biznames'].' found in to create tour.';
    }
    #echo $finaloutput;
    #exit;
}



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$userbirthdate = $current_user_data['birthdate'];
#$userbirthdate='1923-09-14';
$birthdates = $account->getBirthdates($userbirthdate, $plandetails);

$today = new DateTime();
$birthDate = new DateTime($userbirthdate);
$birthDateThisYear = new DateTime($today->format('Y') . '-' . $birthDate->format('m-d'));
$birthDateNextYear = new DateTime(($today->format('Y') + 1) . '-' . $birthDate->format('m-d'));

if ($today < $birthDateThisYear) {
    $interval = $today->diff($birthDateThisYear);
    $currentAge = $today->format('Y') - $birthDate->format('Y') - 1;
} else {
    $interval = $today->diff($birthDateNextYear);
    $currentAge = $today->format('Y') - $birthDate->format('Y');
}

$daysUntilNextBirthday = $interval->days;

if ($daysUntilNextBirthday === 0) {
    $tag = "Happy Birthday, today you turned {$currentAge}!";
} elseif ($daysUntilNextBirthday > 335) {
    $tag = "On {$birthDateThisYear->format('Y-m-d')}, you turned {$currentAge}.";
} elseif ($daysUntilNextBirthday <= 30 && $today > $birthDateThisYear) {
    $tag = "On {$birthDateThisYear->format('Y-m-d')}, you just turned {$currentAge}.";
} elseif ($daysUntilNextBirthday <= 395) {
    $newAge = $currentAge + 1;
    $tag = "On {$birthDateNextYear->format('Y-m-d')}, you'll be {$newAge}.";
} else {
    $tag = 'Error';
}



$input['plandetails'] = $plandetails;
if ($mode == 'dev') {
    $input['plandetails_override']['celebration_tour_days_before'] = 300;
    $input['plandetails_override']['celebration_tour_days_after'] = 300;
}
$input['current_user_data'] = $current_user_data;
$input['birthdate'] = $userbirthdate;
$input['plan'] = $userplan;
$input['loopstop'] = 'dates';
$input['linkhref'] = '/myaccount/tour-build?date=';
#$input['loopstop']='tours';
#$input['navigation']='off';
$calendar_start_date = $birthdates['today_formatted'];
if (!empty($_REQUEST['previous'])) {
    $calendar_start_date = $_REQUEST['previous'];
}
if (!empty($_REQUEST['next'])) {
    $calendar_start_date = $_REQUEST['next'];
}
if (!empty($_REQUEST['date'])) {
    $calendar_start_date = $_REQUEST['date'];
}
if (!empty($_GET['csd'])) {
    $calendar_start_date = $_GET['csd'];
    # breakpoint($calendar_start_date);
    #  $calendar_start_date = substr($calendar_start_date, 0, 4) . '-' . substr($calendar_start_date, 4, 2) . '-' . substr($calendar_start_date, 6, 2);

}
$tourdatedetails = $account->generatetourcalendar($calendar_start_date, $length = 14, $input);
#breakpoint($tourdatedetails);




#-------------------------------------------------------------------------------
# START PAGE
#-------------------------------------------------------------------------------
$transferpage = $system->startpostpage();
$additionalstyles .= '
<style>
.calendarbtn {
width:85px;
margin-bottom: 4px;
}
</style>';

$bodycontentclass = '';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');


echo '
<div class="container main-content mt-5">
<div class="row">

';



// MAIN Column: 8 parts wide
echo '<div class="col-lg-8 mb-4">';

echo '
<div class="mb-5">
<h1 class="fw-bold">Build Your Tour
 <i class="bi bi-info-circle ms-2 fs-3" data-bs-toggle="modal" data-bs-target="#infoModal" style="cursor: pointer;"></i>
 </h1>
<h3 class="bg-success text-white px-3 py-2">1. Select a date for your tour</h3>
';

    $planend_date = new DateTime($birthdates['planend_formatted']);
    $planend_datestr = $planend_date->modify('-' . (14 - 1) . ' days')->format('Y-m-d');
    echo '


<!-- Selection card -->
<div class="card h-100 border-start-lg border-start-primary" id="selectionCard">

<div class="card-body">
<div class="small text-muted d-flex justify-content-between ">

<span>Plan Range: <a href="?date=' . $birthdates['planstart_formatted'] . '">' . $birthdates['planstart_shortformatted'] . '</a> - 
<a href="?date=' . $planend_datestr . '">' . $birthdates['planend_shortformatted'] . '</a></span>
<span>Your Birthday: <a href="?date=' . $birthdates['recent'] . '">' . $birthdates['recent'] . '</a></span>
<span>Today: ' . $birthdates['today_formatted'] . '</span>
</div>


<form name="myTourForm" id="myTourForm" action="/myaccount/tour-build" method="POST">
' . $display->inputcsrf_token() . '
<input type="hidden" name="calendar_date" value="' . ($_GET['date'] ?? '') . '">
';

echo '<div class="row">';
    echo $tourdatedetails;
echo '</div>';

    echo '
    <h3 class="bg-success text-white px-3 py-2">2. Select the '.$website['biznames'].' you want to visit</h3>
<div class="mb-4 showcompaniesdivs">

<!-- Selection card -->
<div class="card h-100 border-start-lg border-start-primary" id="selectionCard">
<div class="card-body">
<div class="small text-muted d-none">Select from your available Enrolled '.ucfirst($website['biznames']).'</div>
<div class="container m-0 p-0">
<div class="row">
<div id="datecontent m-0 p-0">
' . $finaloutput . '
</div>
</form>
';



echo '</div></div>';
echo '</div>';
echo '</div>';
echo '</div></div>';


// SIDE Column: 4 parts wide
echo '<!-- Left Column: 4 parts wide -->
<div class="col-md-4">';
echo '
<div class="container mb-5">
<div class="row">' . $display->formaterrormessage($transferpage['message']);
echo '<h1>&nbsp;</h1>';
echo '<h3 class=" px-3 py-2">&nbsp;</h3>';


$tag = '<h3 class="mb-5">Tours: ' . count($tourlistdates) . ' of ' . $plandetails['celebration_max_tour_count'] . ' used</h3>';
// Display the message
echo '<div class=" mb-5">
<!-- Selection card -->
<div class="card h-100 border-start-lg border-start-primary mb-2" id="selectionCard">
<div class="card-body">
<div class="small text-muted">Your Details</div>
<div class="h5 text-center my-3">' . $tag . '</div>
<div class="d-flex justify-content-between">
Plan: ' . $plandetails['displayname'] . '
</div>
</div>
</div>
</div>
';


if ($showbusinesses) {
    echo '    <h3 class="bg-success text-white px-3 py-2">3. Save your tour</h3>
    <div class="mb-4 showcompaniesdivs ">
<!-- Selection card -->
<div class="card h-100 border-start-lg border-start-primary " id="selectionCard">
<div class="card-body ">
<div class="small text-muted">Tour Selection</div>
<div class="h3 text-center mb-3" id="tourselectiondate"></div>
<hr>
<form action="myaccount-tour-build" method="post">
' . $display->inputcsrf_token() . '
<div class="my-4">
<ul id="listoftourcompanies">
</ul>
</div>
<hr>
<div class="d-flex justify-content-between">
Number of '.$website['biznames'].' on this tour: <span id="companycount">0</span>
</div>
<!-- Submit button -->
<div class="d-flex justify-content-center align-items-center">
<button type="submit" class="btn btn-success mt-3 d-none" name="savetourbutton" id="savetourbutton">Save This Tour</button>
</div>
</form>

</div>
</div>
</div>';


?>
    <script>
        function formatDate(dateString) {
            const [year, month, day] = dateString.split('-').map(Number);
            const date = new Date(year, month - 1, day);
            const options = {
                weekday: 'long'
            };
            const weekday = new Intl.DateTimeFormat('en-US', options).format(date);
            const monthDay = date.toLocaleString('en-US', {
                month: 'long',
                day: 'numeric'
            });
            return `${weekday}<br>${monthDay}, ${year}`;
        }

        function updateCompanyCount() {
            const companyCount = document.querySelectorAll('#listoftourcompanies li').length;
            document.getElementById('companycount').textContent = companyCount;

            const saveTourButton = document.getElementById('savetourbutton');
            if (companyCount > 0) {
                saveTourButton.classList.remove('d-none');
                saveTourButton.classList.add('d-block');
            } else {
                saveTourButton.classList.remove('d-block');
                saveTourButton.classList.add('d-none');
            }
        }

        function handleCheckboxChange(event) {
            const checkbox = event.target;
            const companyID = checkbox.value;
            const companyName = checkbox.getAttribute('data-company');
            const label = document.querySelector(`label[for="${checkbox.id}"]`);
            const form = document.getElementById('myTourForm');

            if (checkbox.checked) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selectedCompanies[]';
                input.value = companyID;
                form.appendChild(input);

                if (label) {
                    label.innerHTML = '<i class="bi bi-check-square-fill text-success"></i>';
                }

                const li = document.createElement('li');
                li.textContent = companyName;
                li.setAttribute('data-id', companyID);
                document.getElementById('listoftourcompanies').appendChild(li);
            } else {
                const input = [...form.elements].find(el => el.name === 'selectedCompanies[]' && el.value === companyID);
                if (input) {
                    form.removeChild(input);
                }

                if (label) {
                    label.innerHTML = '<i class="bi bi-check2-square text-black-50"></i>';
                }

                const listItems = document.querySelectorAll('#listoftourcompanies li');
                listItems.forEach(item => {
                    if (item.getAttribute('data-id') === companyID) {
                        item.remove();
                    }
                });
            }

            updateCompanyCount();
        }

        // Adding event listener to checkboxes
        document.addEventListener('DOMContentLoaded', (event) => {
            const checkboxes = document.querySelectorAll('.form-check-input');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', handleCheckboxChange);

                // Initialize listoftourcompanies list with pre-checked boxes
                if (checkbox.checked) {
                    const companyID = checkbox.value;
                    const companyName = checkbox.getAttribute('data-company');
                    const li = document.createElement('li');
                    li.textContent = companyName;
                    li.setAttribute('data-id', companyID);
                    document.getElementById('listoftourcompanies').appendChild(li);

                    const label = document.querySelector(`label[for="${checkbox.id}"]`);
                    if (label) {
                        label.innerHTML = '<i class="bi bi-check-square-fill text-success"></i>';
                        //  label.classList.add('bg-secondary');
                    }
                }
            });

            updateCompanyCount(); // Update the company count after initializing the list
        });
    </script>



<?php
}

echo '</div></div>
</div></div>
</div>';



echo '<!-- Modal -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg">

    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="infoModalLabel">More Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

        <div class="modal-body">
        <p>Follow these steps to create your personalized tour for birthday rewards:</p>
        
        <h6>1. Select a Date for Your Tour</h6>
        <p>Use the date selector at the top to choose the date range for your tour. This will help determine the available rewards around your birthday. Navigate through the calendar using the arrows to find the best dates that align with your birthday.</p>

        <h6>2. Select the '.ucfirst($website['biznames']).' You Want to Visit</h6>
        <p>Browse through the list of '.$website['biznames'].' and select the ones you want to visit during your tour. Each '.$website['bizname'].' offers different rewards, so pick the ones that excite you the most! You can see a brief description of each reward and click on the icons to download their apps for more details.</p>

        <h6>3. Save Your Tour</h6>
        <p>Once you’ve selected your preferred '.$website['biznames'].', you can save your tour by entering a name for your tour in the “Tour Selection” box. The number of '.$website['biznames'].' you’ve selected will be displayed. Be sure to review your selections before saving.</p>

        <p>If you need to make changes to your tour later, you can always come back and edit your selections. Enjoy planning your birthday tour and have a fantastic time visiting your favorite places!</p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
