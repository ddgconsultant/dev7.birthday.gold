<?php

$addClasses[] = 'Mail';

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$sendcount = 0;
$local_validationcode = '';


if (isset($_GET['sid']) && !empty($_GET['sid'])) {
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_abort();
  }
  $sid = $_GET['sid'];
  session_name($sid);
  session_start();
  #print_r($_SESSION);
  #echo '<hr>';
}


#-------------------------------------------------------------------------------
# CHECK FOR VALID VALIDATION AND GO TO PLANS/CHECKOUT
#-------------------------------------------------------------------------------
if ($app->formposted('GET') || (isset($_GET['_xtoken']) && isset($_GET['uid']) && sha1($_GET['uid']) == $_GET['_xtoken'])) {
  $userid = $_GET['uid'];
  $userdata = $account->getuserdata($userid, 'user_id');

  $email = $userdata['email'];
  $fullname = $userdata['first_name'];
  // Send notification email
  #   global $website;
  $message['toemail'] = $email;
  $message['fullname'] = $fullname;
  # $link= $appclass->getshortcode( $website['fullurl'].'/validate-account?t='.sha1($email) );
  #$message['validatelink']=$link['shorturl'];
  $validatedata['rawdata'] = $email;
  $validatedata['user_id'] = $userdata['user_id'];
  $validatedata['sendcount'] = $sendcount;
  $validatedata['type'] = 'enrollment';
  $validationcodes = $app->getvalidationcodes($validatedata);

  $link = $website['fullurl'] . '/enrollment?t=' . $validationcodes['long'];
  $message['validatelink'] = $link;
  $message['validationcode'] = $local_validationcode = $validationcodes['mini'];

  $userregistrationdata['setEnrollmentStatus_count'] = $account->setEnrollmentStatus($userdata['user_id'], 'pending');
  $userregistrationdata['userdata'] = $userdata;
  $userregistrationdata['validationdata'] = $validationcodes;
  $result = $mail->sendEnrollmentQueueEmail($message);
  $userregistrationdata['sendEnrollmentQueueEmail'] = date('r');
  $userregistrationdata['sendEnrollmentQueueEmail_count'] = ($sendcount++);

  $session->set('queueuserenrollmentdata', $userregistrationdata);

  
  #-------------------------------------------------------------------------------
  # DISPLAY OOPS MESSAGE
  #-------------------------------------------------------------------------------

  include($dir['core_components'] . '/bg_pagestart.inc');
  include($dir['core_components'] . '/bg_header.inc');

  echo '
<!-- Start -->
<div class="container main-content py-6">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg">  
<h1>&#129335;</h1>
<h3>Email sent to user: ' . $userdata['first_name'] . '</h3>
<p>' . $email . '<p>
<p>return to register</p>
</div>
</div>
<div class="row justify-content-center mt-4">
<div class="col-4">
</div>
</div>
</div>
</div>
</div>
</div>
</div>
<!--  End -->
  ';
  #print_r($userregistrationdata);

  include($dir['core_components'] . '/bg_footer.inc');
  $app->outputpage();



  exit;
}



#-------------------------------------------------------------------------------
# CHECK FOR VALID VALIDATION DISPLAY MESSAGE
#-------------------------------------------------------------------------------
if (isset($_GET['t'])) {
  $checkdata = array();
  if (isset($_GET['t'])) $checkdata['long'] = $_GET['t'];
  if (isset($_POST['validationCode'])) $checkdata['mini'] = $_POST['validationCode'];
  $response = $app->checkvalidationcodes($checkdata);

  if ($response['validated']) {
    $userid = $response['user_id'];

    $tomorrow = date('Y-m-d H:i:s', strtotime('+1 day'));
    $account->updateSettings($userid, ['enrollmentstart_dt' => $tomorrow]);



    $account->updateSettings($userid, ['enrollmentstart_dt' => $tommorrow]);



    #-------------------------------------------------------------------------------
    # DISPLAY DELAYED MESSAGE
    #-------------------------------------------------------------------------------

    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');
    echo '
<!-- Start -->
<div class="container main-content py-6">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg">  
<i class="bi bi-patch-check display-1 text-success"></i>
<h1>You have successfully delayed your enrollment by one day.</h1>
<p class="">We will send you another email tomorrow when your place in the enrollment line comes up.</p>
<p class="text-info">Don\'t forget to check your spam/junk folder.</p>
<p class="mb-4">No other action is needed.  You can close this browser.</p>
<a class="btn btn-primary" href="/login">Or log into your account</a>

</div>
</div>
<div class="row justify-content-center mt-4">
<div class="col-4">
</div>
</div>
</div>
</div>
<!--  End -->
  ';
    #print_r($userregistrationdata);

    include($dir['core_components'] . '/bg_footer.inc');
    $app->outputpage();


    exit;
  }
}



#-------------------------------------------------------------------------------
# DISPLAY OOPS MESSAGE
#-------------------------------------------------------------------------------

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<!-- Start -->
<div class="container main-content py-6">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg">  
<h1>&#129335;</h1>
<h3>Hmm... something unexpected happened.</h3>
<a class="btn btn-primary" href="/login">Log into your account</a>
</div>
</div>
<div class="row justify-content-center mt-4">
<div class="col-4">
</div>
</div>
</div>
</div>
<!--  End -->
  ';
#print_r($userregistrationdata);


#include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php'); 
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
