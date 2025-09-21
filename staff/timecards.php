<?php

$addClasses[] = 'timeclock';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');





#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
#$timeclock = new timeclock($database, $session); // Pass actual $database and $session objects




#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------

if ($app->formposted() ) {

    
#if (isset($_POST['submit'])) {
    $entry_id = $_POST['entry_id'];
    $clock_in = $_POST['clock_in'];
    $clock_out = $_POST['clock_out'];
    $reason = $_POST['reason'];

    $timeclock->update_time_record($entry_id, $clock_in, $clock_out, $reason, $current_user_data['user_id']);

    // Redirect to avoid form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}



$user_id = $current_user_data['user_id']; // Ensure you have the current user's ID
$timeRecords = $timeclock->get_time_records($user_id);








#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------

$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$additionalstyles.='
<style>
.feature {
width: 90px;  /* Set width */
height: 90px;  /* Set height */
display: flex;
align-items: center;
justify-content: center;
}

.feature i {
font-size: 48px;  /* Increase icon size */
}

.tooltip {
  z-index: 1039 !important;  /* Assuming the modal z-index is 1040 */
}

.monospace {
font-family: monospace;
}

</style>
';


#include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 



/*
echo '
<div class="container-xl px-4 mt-4 mb-5 pb-5">
    <!-- Account page navigation-->
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php'); 

*/
include($dir['core_components'] . '/bg_user_profileheader.inc');
?>

<?php
$timecard_total=0;
$entrycount=0;

echo '
<div class="container main-content">
';
echo '
    <div class="card mt-5">
        <h1 class="ps-3 pt-3 mb-0 pb-0">Time Cards</h1>
        <div class="accordion" id="timeRecordsAccordion">';

foreach ($timeRecords as $record) {
    $accordionAction = '';
    $editicon='<i class="bi bi-pencil-square text-primary" title="Click To Edit"></i>';
    if ($record['reason_for_change'] !== '') {
        $accordionAction = ' disabled ';
        $editicon='<i class="bi bi-ban-fill text-danger" title="Already Edited"></i>';
    }
    $entrycount++;

    $display_calendar_day=explode('-', date('M-j-D', strtotime($record['clock_in']))); 
#breakpoint($display_calendar_day);
$timecard_total=$timecard_total+$record['timeformatted'];
    echo '<div class="accordion-item">
            <h2 class="accordion-header" id="heading' . $record['entry_id'] . '">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $record['entry_id'] . '" aria-expanded="false" aria-controls="collapse' . $record['entry_id'] . '" '. $accordionAction.'>
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12 col-md-2 d-none">
                                Record #' . $record['entry_id'] . '
                            </div>
                            <div class="col-1 col-md-1 d-none d-lg-block">
                            '.$display->calendar_day($display_calendar_day).'                                          
                              </div>

                            <div class="col-12 col-md-4 font-monospace">
                                <span class="d-none d-md-inline-block bi-box-arrow-in-right pe-2 fw-bold" title="Clocked In"></span>
                                <span class="d-md-none fw-bold">I:</span>
                                ' . date('Y-m-d H:i', strtotime($record['clock_in'])) . '
                            </div>
                            <div class="col-12 col-md-4 font-monospace">
                                <span class="d-none d-md-inline-block bi-box-arrow-in-left pe-2 fw-bold" title="Clocked Out"></span>
                                <span class="d-md-none fw-bold">O:</span>
                                ' . (!empty($record['clock_out']) ? date('Y-m-d H:i', strtotime($record['clock_out'])) : 'N/A') . '
                            </div>
                            <div class="col-12 col-md-2 fw-bold">
                                Total Hours: ' . number_format($record['timeformatted'], 2) . '
                            </div>
                            <div class="col-12 col-md-1 fw-bold">
                            '. $editicon.'
                        </div>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapse' . $record['entry_id'] . '" class="accordion-collapse collapse" aria-labelledby="heading' . $record['entry_id'] . '" data-bs-parent="#timeRecordsAccordion">
                <div class="accordion-body bg-light">
                    <form class="time-record-form mb-3" method="POST" action="'.$_SERVER['PHP_SELF'].'">
                    '. $display->inputcsrf_token().'
                        <div class="row g-2">   

                            <div class="col-md">
                                <div class="form-floating">
                                    <input type="datetime-local" class="form-control" name="clock_in" value="' . date('Y-m-d\TH:i', strtotime($record['clock_in'])) . '" placeholder="Clock In" required ' . $accordionAction . '>
                                    <label>Clock In</label>
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-floating">
                                    <input type="datetime-local" class="form-control" name="clock_out" value="' . ($record['clock_out'] ? date('Y-m-d\TH:i', strtotime($record['clock_out'])) : '') . '" placeholder="Clock Out" required ' . $accordionAction . '>
                                    <label>Clock Out</label>
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="reason" placeholder="Reason for Change" required value="' . $record['reason_for_change'] . '" ' . $accordionAction . '>
                                    <label>Reason for Change</label>
                                </div>
                            </div>
                            <input type="hidden" name="entry_id" value="' . $record['entry_id'] . '">
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary btn-sm">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
}

echo '

<div class="accordion-footer bg-light p-3">
<div class="d-flex justify-content-between align-items-center  fw-bold">
    <span>Entries: ' .$entrycount . '</span>
</div>
<div class="d-flex justify-content-between align-items-center  fw-bold">
    <span>* Estimated Total Hours: ' . number_format($timecard_total, 2) . '</span>
</div>
<div>
<i class="small fs-6">* Estimated Total Hours is for display purposes only.  Final calculations of total hours worked will be displayed on your payroll check.</i>
</div>
</div>
</div>
</div>
</div>';
?>




    <script>
        document.querySelectorAll('.reason-input, .form-control').forEach(input => {
            input.addEventListener('input', function() {
                const form = this.closest('.time-record-form');
                const submitBtn = form.querySelector('.submit-btn');
                const reasonInput = form.querySelector('.reason-input');
                if (reasonInput.value.trim() !== '') {
                    submitBtn.style.display = 'block';
                    document.querySelectorAll('.time-record-form').forEach(otherForm => {
                        if (otherForm !== form) {
                            otherForm.querySelectorAll('input, button').forEach(elem => elem.disabled = true);
                        }
                    });
                } else {
                    submitBtn.style.display = 'none';
                    document.querySelectorAll('.time-record-form input, .time-record-form button').forEach(elem => elem.disabled = false);
                }
            });
        });
    </script>


    
    

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();

