<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 


#-------------------------------------------------------------------------------
# HANDLE THE A REFRESH REQUEST
#-------------------------------------------------------------------------------
if ($app->formposted()){   
header('location: /legalhub/data_requestsubmitted');
exit;
}



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$headerattribute['additionalcss']='';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<!--  Start -->
<div class="container py-6 main-content">
<div class="container">
<div class="row">
<div class="col">
<h1 class="display-1">Your Data Rights</h1>
<p class="mb-4">Effective Date: October 12, 2023</p>

<p>At birthday.gold, we respect and prioritize your data rights. Here's a quick overview of what you can expect and how you can exercise your rights as a user of our site:</p>

<?PHP
$type='';

if (isset($_REQUEST['manage']) && !empty($current_user_data['user_id'])) $type='manage';

$managetag='
<h5 class="mt-5">Making Requests</h5>
<p>Use your existing account, and go to your Account page, and click "Data Request".</p>
';

$keytag='<h5 class="mt-5">Your Key Rights</h5>';

switch($type){
case 'manage':
echo '
<style>
.managebtn{
width:120px;
}
</style>
'.$keytag.'
<p class="fw-bold">To manage or exercise your rights, simply click on one of the action buttons below, and follow the instructions.</p>

<div class="accordion" id="manageAccordion">
<ul class="list-unstyled">
<li class="py-1">
<a class="btn btn-primary btn-sm mx-3 managebtn" data-bs-toggle="collapse" href="#collapseOptOut" role="button" aria-expanded="false" aria-controls="collapseOptOut">Opt Out</a> Don\'t want your data used for targeted ads or used in significant decisions about you? Just let us know!
<div class="collapse" id="collapseOptOut" data-bs-parent="#manageAccordion">
<div class="accordion-body">
<form method="POST">'.$display->inputcsrf_token().'
<input type="hidden" name="requesttype" value="optOut">
<div class="mb-3">
<label for="optOutReason" class="form-label">Reason for Opting Out:</label>
<textarea class="form-control" name="request_detail"  id="OutReason" rows="3" required></textarea>
</div>
<button type="submit" class="btn btn-sm btn-success">Submit</button>
</form>
</div>
</div>
</li>
<li class="py-1">
<a class="btn btn-primary btn-sm mx-3 managebtn" data-bs-toggle="collapse" href="#collapseAccess" role="button" aria-expanded="false" aria-controls="collapseAccess">Access</a> Curious about what data we have on you? Feel free to ask!
<div class="collapse" id="collapseAccess" data-bs-parent="#manageAccordion">
<div class="accordion-body">
<form method="POST">'.$display->inputcsrf_token().'
<input type="hidden" name="requesttype" value="accessRequest">
<div class="mb-3">
<label for="accessRequest" class="form-label">Details of Your Request:</label>
<textarea class="form-control" name="request_detail"  id="accessRequest" rows="3" required></textarea>
</div>
<button type="submit" class="btn btn-sm btn-success">Submit</button>
</form>
</div>
</div>
</li>
<li class="py-1">
<a class="btn btn-primary btn-sm mx-3 managebtn" data-bs-toggle="collapse" href="#collapseCorrection" role="button" aria-expanded="false" aria-controls="collapseCorrection">Correction</a> Found an error in your data? We\'ll fix it for you.
<div class="collapse" id="collapseCorrection" data-bs-parent="#manageAccordion">
<div class="accordion-body">
<form method="POST">'.$display->inputcsrf_token().'
<input type="hidden" name="requesttype" value="correctionDetails">
<div class="mb-3">
<label for="correctionDetails" class="form-label">Details of the Correction Needed:</label>
<textarea class="form-control" name="request_detail"  id="correctionDetails" rows="3" required></textarea>
</div>
<button type="submit" class="btn btn-sm btn-success">Submit</button>
</form>
</div>
</div>
</li>
<li class="py-1">
<a class="btn btn-primary btn-sm mx-3 managebtn" data-bs-toggle="collapse" href="#collapseDeletion" role="button" aria-expanded="false" aria-controls="collapseDeletion">Deletion</a> If you want us to forget you, just tell us, and we\'ll delete your data.
<div class="collapse" id="collapseDeletion" data-bs-parent="#manageAccordion">
<div class="accordion-body">
<form method="POST">'.$display->inputcsrf_token().'
<input type="hidden" name="requesttype" value="deletionReason">
<div class="mb-3">
<label for="deletionReason" class="form-label">Reason for Deletion:</label>
<small><br>Note:  We can only delete tracking/analytical data.  If you want to completely delete your account you must do so from your account page.</small>

<textarea class="form-control" name="request_detail"  id="deletionReason" rows="3" required></textarea>
</div>
<button type="submit" class="btn btn-sm btn-success">Submit</button>
</form>
</div>
</div>
</li>
<li class="py-1">
<a class="btn btn-primary btn-sm mx-3 managebtn" data-bs-toggle="collapse" href="#collapsePortability" role="button" aria-expanded="false" aria-controls="collapsePortability">Portability</a> Need your data in a user-friendly format? We can do that (once a year).
<div class="collapse" id="collapsePortability" data-bs-parent="#manageAccordion">
<div class="accordion-body">
<form method="POST">'.$display->inputcsrf_token().'
<input type="hidden" name="requesttype" value="portabilityRequest">
<div class="mb-3">
<label for="portabilityRequest" class="form-label">Details of Your Request:</label>
<textarea class="form-control" name="request_detail"  id="portabilityRequest" rows="3" required></textarea>
</div>
<div class="mb-3">
<label class="form-label">Data Format:</label>
<div class="d-flex flex-wrap">
<div class="form-check me-3">
<input class="form-check-input" type="radio" name="dataFormat" id="formatCSV" value="csv/text" required>
<label class="form-check-label" for="formatCSV">
CSV/Text
</label>
</div>
<div class="form-check me-3">
<input class="form-check-input" type="radio" name="dataFormat" id="formatJSON" value="json" required>
<label class="form-check-label" for="formatJSON">
JSON
</label>
</div>
<div class="form-check me-3">
<input class="form-check-input" type="radio" name="dataFormat" id="formatXML" value="xml" required>
<label class="form-check-label" for="formatXML">
XML
</label>
</div>
</div>
</div>
<button type="submit" class="btn btn-sm btn-success">Submit</button>
</form>
</div>
</div>
</li>

</ul>
</div>
';
break;


default:
echo ' 
'.$managetag.'
'.$keytag.'
<ul>
<li class="py-1"><strong>Opt Out:</strong> Don\'t want your data used for targeted ads or used in significant decisions about you? Just let us know!</li>
<li class="py-1"><strong>Access:</strong> Curious about what data we have on you? Feel free to ask!</li>
<li class="py-1"><strong>Correction:</strong> Found an error in your data? We\'ll fix it for you.</li>
<li class="py-1"><strong>Deletion:</strong> If you want us to forget you, just tell us and we\'ll delete your data.</li>
<li class="py-1"><strong>Portability:</strong> Need your data in a user-friendly format? We can do that (but max once a year).</li>
</ul>

';
break;
}
?>

<h5 class="mt-5">Response Time</h5>
<p>After you make a request, we'll respond as soon as we can, usually within 20 days. If it's a bit complicated, we might take a bit longer, but we'll always keep you in the loop, including notifing you if a request is excessive, impossible or involves disproportionate effort to fullfill.</p>


<h5 class="mt-5">Appeals</h5>
<p>If you're not happy with our response to your request, you can appeal. We've made the appeal process simple and straightforward. And if you're still not satisfied after the appeal, you can reach out to the attorney general.</p>


<p>Always remember, your data rights are paramount to us. If you have any questions, just reach out!</p>


<?PHP
if (isset($_REQUEST['register'])){
echo '                <a class="btn btn-primary py-3 px-5 no-print my-5" href="/signup">Go Back To Sign Up</a>';
} else {
echo '                <a class="btn btn-primary py-3 px-5 no-print my-5" href="/">Go Back To Home</a>';
}
?>
</div>
</div>
</div>
</div>
<!--  End -->



<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();

