<?php
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 




$wizardmode=false;
$wizard['section']='enrollment';
$wizard['step']=2;
if (!empty($current_user_data['enrollment_mode']) && $current_user_data['enrollment_mode']=='wizard'){ 
$wizardmode=true;
$wizard['section']='enrollment';
$wizard['step']=2;
} 
$errormessage='';
#-------------------------------------------------------------------------------
# HANDLED POSTED FORM
#-------------------------------------------------------------------------------
if  ($formdata=$app->formposted()) {
$schedule = $_POST['schedule'];
$userId = $current_user_data['user_id'];  // Fetch the user ID from the session

// Remove the old schedule
$sql = "update bg_user_schedules set status='inactive', modify_dt=now() WHERE user_id = $userId AND status='active'";

$stmt = $database->prepare($sql);
$stmt->execute();

$validschedule=false;
$transferpagedata['message']='
<div class="alert alert-info alert-dismissible fade show" role="alert">
<b>Tip:</b> Your enrollment schedule tells our system when to process your enrollments. Some businesses require you to click confirmation messages to complete your enrollments and other may send welcome messages. To avoid receiving these messages at inconvenient times like when you are asleep or at work, you can set a preferred schedule.
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
';

// Insert the new schedule
foreach ($schedule as $day => $blocks) {
foreach ($blocks as $block) {
$sql = "INSERT INTO bg_user_schedules (user_id, day, time_block, create_dt, modify_dt, status)  VALUES (:user_id, :day, :time_block, NOW(), NOW(), 'active')";
$params = array(
':user_id' => $userId,
':day' => $day,
':time_block' => $block
);

$stmt = $database->query($sql, $params);
}

$validschedule = true;
$transferpagedata['message'] =$errormessage= '<div class="alert alert-success" role="alert">Your enrollment schedule has been updated</div>';

}

$transferpage['url']='/myaccount/enrollment-schedule';
$wizardmode=false;
$wizard['section']='enrollment';
$wizard['step']=2;


if ((!empty($current_user_data['enrollment_mode']) && $current_user_data['enrollment_mode']=='wizard') || 
   (!empty($wizardmode) && !empty($nextpage) && !isset($_GET['review'])))
{ 
$wizardmode=true;
$nextpage='/myaccount/select';
$transferpage['url']=$nextpage;
} 
if (!$validschedule) {  
$transferpage['message']=$transferpagedata['message'];
$qik->endpostpage($transferpage);

exit;
}
}




#-------------------------------------------------------------------------------
# DISPLAY FORM
#-------------------------------------------------------------------------------
$transferpagedata['message']=$errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

if (empty($transferpagedata['message'])) {
$transferpagedata['message']=$display->formaterrormessage('
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <b>Tip: Your enrollment schedule tells our system when to process your enrollments.<br></b>
    Some businesses require you to click confirmation messages to complete your enrollments and other may send welcome messages.
    To avoid receiving these messages at inconvenient times like when you are asleep or at work, you can set a preferred schedule.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>');
}

$userId = $current_user_data['user_id'];  // Fetch the user ID from the session
$existingSchedule = [];

#$query = "SELECT * FROM bg_user_schedules WHERE user_id = $userId";
#$result = $database->query($query);

$sql="SELECT * FROM bg_user_schedules WHERE user_id = $userId and status='active'";
$stmt = $database->prepare($sql);


$stmt->execute();
$schedulecount=$stmt->rowCount();
if ($schedulecount> 0 && $wizardmode) {
## USER HAS COMPLETED SCHEDULE AND IS PICKING UP 
## FORWARD THEM TO THE NEXT STEP

$transferpage['url']='/myaccount/select';
$transferpage['message']=$transferpagedata['message'];
$qik->endpostpage($transferpage);
}




// Use rowCount() instead of num_rows
if ($schedulecount > 0) {

  // Fetch results
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row){
        $scheduledtimezone=$row['timezone'];
        #while ($row = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
        $existingSchedule[$row['day']][] = $row['time_block'];
    }
}

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];


$bodycontentclass='';

// Add v7 theme CSS
$additionalstyles .= '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-calendar3 me-3"></i>Enrollment Schedule</h1>
            <p class="lead mb-0">Set your preferred times for enrollment processing</p>
        </div>
    </div>
</div>

<!-- Tip Section at the Top -->
<div class="container my-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <?php echo $display->formaterrormessage($transferpagedata['message']); ?>
        </div>
    </div>
</div>

<!-- Main Content Section -->
<div class="container mb-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">

<?php

// Start the form
echo '<form method="post" id="scheduleform" action="/myaccount/enrollment-schedule">';
echo $display->inputcsrf_token();

// QUICK SELECT FORM
echo '<div class="row align-items-center">'; // Remove gaps between columns and align items to the center

## --- QUICK SELECT BUTTONS
echo '<div class="col-12 col-md-8 d-flex align-items-center my-sm-3">';
echo '<div class="card w-100"><div class="card-header fw-bold p-1 ps-3">Quick Select: ';

$buttons = array(
    array('name' => 'Anytime', 'id'=> 'anytimeButton', 'onclick' => 'enableAllCheckboxes()'),
    array('name' => 'Business Hours', 'id'=> 'businessHoursButton','onclick' => 'enableBusinessHours()'),
    array('name' => 'Weekends Only', 'id'=> 'weekendsButton','onclick' => 'enableWeekends()'),
    array('name' => 'After Work','id'=> 'afterWorkButton', 'onclick' => 'enableAfterWork()'),
    array('name' => 'None','id'=> 'noneButton', 'onclick' => 'enableNone()'),
);

echo '<div class="d-flex flex-wrap justify-content-start">';
foreach ($buttons as $button) {
    echo '<button type="button" class="btn btn-sm btn-primary mx-1 my-1" id="' . $button['id'] . '" style="min-width: 100px;" onclick="' . $button['onclick'] . '">' . $button['name'] . '</button>';
}
echo '</div>';


echo '</div></div></div>'; // end quick select buttons column



## --- TIME ZONE
echo '<div class="col-12 col-md-3 d-flex align-items-center justify-content-center my-3 my-md-0">';

// Time zone options for USA
$timezones = array(
    'America/Denver' => 'Select Your Timezone',
    'America/New_York' => 'Eastern Time (ET)',
    'America/Chicago' => 'Central Time (CT)',
    'America/Denver' => 'Mountain Time (MT)',
    'America/Los_Angeles' => 'Pacific Time (PT)',
);
$timezoneselected = '';
if (!empty($client_locationdata['timezone'])) {
    $timezoneselected = $client_locationdata['timezone'];
}
if (!empty($scheduledtimezone)) {
    $timezoneselected = $scheduledtimezone;
}

echo '<select class="form-select py-2 w-100" name="timezone" id="timezone">';
foreach ($timezones as $zone => $label) {
    echo '<option value="' . $zone . '" '.($timezoneselected == $zone ? 'selected' : '').'>' . $label . '</option>';
}
echo '</select></div>';

## --- SAVE BUTTON
echo '<div class="col-12 col-md-1 d-flex align-items-center justify-content-center  my-md-0 d-none d-md-block">';
echo '<button class="btn btn-success w-100" type="submit" value="Save Schedule">Save</button>';
echo '</div>'; // End of Save Button Column

echo '</div>'; // End of Row

// SCHEDULE FORM
echo '<div class="mt-3">';
// SELECTORS FOR EACH DAY
foreach ($days as $day) {
    echo '<div class="card mb-3">';
    echo '<div class="card-header h3 fw-bold">' . $day . '  <button type="button" class="btn btn-sm btn-primary ms-5 mx-3 p-1 d-none" onclick="enableAllDay(\'' . $day . '\')">All Day</button></div>';
    echo '<div class="card-body">';
    echo '<div class="row ms-5">';
    
    for ($i = 0; $i < 6; $i++) {
        $start_time = ($i === 0) ? 'midnight' : (($i * 4) % 12 === 0 ? 12 : ($i * 4) % 12) . ':00 ' . ($i < 3 ? 'AM' : 'PM');
        $end_time = (($i * 4 + 4) % 24 === 0) ? 'midnight' : ((($i * 4 + 4) % 12) === 0 ? 12 : (($i * 4 + 4) % 12)) . ':00 ' . ((($i * 4 + 4) / 12) >= 1 ? 'PM' : 'AM');
        
        echo '<div class="col-md-4">';
        echo '<div class="form-check form-switch">';
        echo '<input class="form-check-input" type="checkbox" role="switch" id="schedule_'.$day.'_'.$i.'" name="schedule[' . $day . '][]" value="' . $i . '"';
        if (!empty($existingSchedule[$day])) {
            echo in_array($i, $existingSchedule[$day]) ? ' checked' : '';
        }
        echo '> ';
        
        echo '<label class="form-check-label" for="schedule_'.$day.'_'.$i.'">'.$start_time . '-' . $end_time.'</label>';
        echo '</div></div>';
    }
    
    echo '</div></div></div>';
}

echo '<div class="text-center mt-4"><button class="btn btn-success btn-lg px-5" type="submit" value="Save Schedule">Save Schedule</button></div>';

// Close schedule form div
echo '</div><!-- End schedule form -->';
?>
            </form>
        </div><!-- End col-lg-10 -->
    </div><!-- End row -->
</div><!-- End container -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Button Elements
    const anytimeButton = document.getElementById('anytimeButton');
    const businessHoursButton = document.getElementById('businessHoursButton');
    const weekendsButton = document.getElementById('weekendsButton');
    const afterWorkButton = document.getElementById('afterWorkButton');
    const noneButton = document.getElementById('noneButton');

    // Event Listeners
    anytimeButton.addEventListener('click', enableAllCheckboxes);
    businessHoursButton.addEventListener('click', enableBusinessHours);
    weekendsButton.addEventListener('click', enableWeekends);
    afterWorkButton.addEventListener('click', enableAfterWork);
    noneButton.addEventListener('click', enableNone);
});
    // Function Definitions
    function enableAllCheckboxes() {
        const checkboxes = document.querySelectorAll('#scheduleform input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
    }

    function enableBusinessHours() {
        // First, uncheck all
        enableNone();
        // Then, check the business hours (typically 9 AM to 5 PM)
        const businessHours = ['_2', '_3']; // Assuming these suffixes correspond to business hours
        checkSpecificCheckboxes(businessHours);
    }

    function enableWeekends() {
        enableNone();
        const weekendDays = ['Saturday', 'Sunday'];
        weekendDays.forEach(day => {
            checkDay(day);
        });
    }

    function enableAfterWork() {
        enableNone();
        const afterWorkHours = ['_0', '_4', '_5']; // Assuming these suffixes correspond to after-work hours
        checkSpecificCheckboxes(afterWorkHours);
    }

    function enableNone() {
        const checkboxes = document.querySelectorAll('#scheduleform input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }

    function checkSpecificCheckboxes(suffixes) {
        suffixes.forEach(suffix => {
            const checkboxes = document.querySelectorAll(`#scheduleform input[id$="${suffix}"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });
    }

    function checkDay(day) {
        const checkboxes = document.querySelectorAll(`#scheduleform input[id*="${day}"]`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
    }

    function enableAllDay(day) {
        const checkboxes = document.querySelectorAll(`#scheduleform input[id*="${day}"]`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
    }

</script>
<?PHP

$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();