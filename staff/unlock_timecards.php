<?php

$addClasses[] = 'TimeClock';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');





#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$timeClock = new TimeClock($database, $session); // Pass actual $database and $session objects




#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------

if ($app->formposted() ) {

    
#if (isset($_POST['submit'])) {
    $entry_id = $_POST['entry_id'];
   # $clock_in = $_POST['clock_in'];
 #   $clock_out = $_POST['clock_out'];
    $reason = $_POST['message'];

    $timeClock->timecard_unlock($current_user_data['user_id'], $entry_id, $reason );



    // Redirect to avoid form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}



$user_id = $current_user_data['user_id']; // Ensure you have the current user's ID
$timeRecords = $timeClock->get_locked_records();








#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------

$additionalstyles.='<link rel="stylesheet" href="/public/css/myaccount.css">
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

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="container-xl main-content">
    <!-- Account page navigation-->
';

#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php'); 

?>

<?php
$timecard_total=0;
$entrycount=0;
echo '<div class="container mt-4">
    <div class="card my-4">
        <h1 class="ps-3 pt-3 mb-0 pb-0">Time Cards</h1>
        <div class="accordion" id="timeRecordsAccordion">';

foreach ($timeRecords as $record) {
    $accordionAction = '';
    $editicon='<i class="bi bi-pencil-square text-primary" title="Click To Edit"></i>';
   if ($record['msg'] !== '') {
        $accordionAction = ' disabled ';
        $editicon='<i class="bi bi-ban-fill text-danger" title="Already Unlocked"></i>';
    }
    $entrycount++;
$timecard_total=$timecard_total+$record['timeformatted'];
    echo '<div class="accordion-item">
            <h2 class="accordion-header" id="heading' . $record['entry_id'] . '">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $record['entry_id'] . '" aria-expanded="false" aria-controls="collapse' . $record['entry_id'] . '" '. $accordionAction.'>
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12 col-md-3 d-none">
                                Record #' . $record['entry_id'] . '
                            </div>
                            <div class="col-12 col-md-3 font-monospace">
                            <span class="d-none d-md-inline-block bi bi-file-person pe-2 fw-bold" title="Staff Name"></span>
                            <span class="d-md-none fw-bold">N:</span>
                            ' .$record['first_name'].' '.$record['last_name'] . '
                        </div>
                   
                            <div class="col-12 col-md-2 font-monospace">
                                <span class="d-none d-md-inline-block bi bi-lock-fill pe-2 fw-bold" title="Lock Time"></span>
                                <span class="d-md-none fw-bold">I:</span>
                                ' . date('Y-m-d H:i', strtotime($record['clock_in'])) . '
                            </div>                            
                            <div class="col-12 col-md-6 fw-bold">
                                Reason for Lock: ' .  $record['reason_for_lock'] . '
                            </div>
                            <div class="col-12 col-md-1 fw-bold">
                            '. $editicon.'
                        </div>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapse' . $record['entry_id'] . '" class="accordion-collapse collapse" aria-labelledby="heading' . $record['entry_id'] . '" data-bs-parent="#timeRecordsAccordion">
                <div class="accordion-body">
                    <form class="time-record-form mb-3" method="POST" action="'.$_SERVER['PHP_SELF'].'">
                    '. $display->inputcsrf_token().'
                        <div class="row g-2">                    
                            <div class="col-md">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="message" placeholder="Unlock Message" required value="' . $record['msg'] . '" ' . $accordionAction . '>
                                    <label>Unlock Message</label>
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
</div>

</div>
    </div>
</div>';
?>





    </div>
    </div>
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

