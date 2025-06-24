<?php

$addClasses[] = 'Mail';

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$sendcount = 0;
$local_validationcode = '';


#-------------------------------------------------------------------------------
# CHECK FOR VALID VALIDATION AND GO TO PLANS/CHECKOUT
#-------------------------------------------------------------------------------
$verbosetracking = true; // Set this to false to disable verbose tracking

if (isset($_GET['t']) || $app->formposted()) {
  $checkdata = array();
  if (isset($_GET['t'])) {
    $checkdata['long'] = $_GET['t'];
    if ($verbosetracking) session_tracking('Validation token received via GET: ' . $_GET['t']);
  }
  if (isset($_POST['validationCode'])) {
    $checkdata['mini'] = $_POST['validationCode'];
    if ($verbosetracking) session_tracking('Validation code received via POST: ' . $_POST['validationCode']);
  }
  $checkdata['type'] = 'email';
  $response = $app->checkvalidationcodes($checkdata);
  $gotourl = '/validate-failed';

  if ($verbosetracking) {
    session_tracking('Validation check response: ' . json_encode($response));
  }

  ## USER IS ALREADY VALIDATED AND ACTIVE ACCOUNT -- navigate to MyHome - which may redirect to login
  ## --------------------------------------------
  if ($response !== false && isset($response['validated']) && $response['validated'] && isset($response[0]['user_id']) && !empty($response[0]['user_id'])) {
    if ($verbosetracking) session_tracking('User is validated and user ID found: ' . $response[0]['user_id']);

    $process_user_data = $account->getuserdata($response[0]['user_id'], 'user_id');
    if ($verbosetracking) session_tracking('User data retrieved for user ID: ' . $response[0]['user_id'] . ' - ' . json_encode($process_user_data));

    if (!empty($process_user_data)) {
      if ($process_user_data['status'] == 'active' && !empty($process_user_data['account_plan'])) {
        if ($verbosetracking) session_tracking('User account is active and has a plan. Initiating auto-login.');

        $account->login($response[0]['user_id'], $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
        header('location: /myaccount/');
        exit;
      }
    }



    ## SUCCESSFUL Validation -- continue on
    ## --------------------------------------------
    $userregistrationdata = $session->get('userregistrationdata');
    if (empty($userregistrationdata)) $userregistrationdata = array();

    if ($verbosetracking) session_tracking('Initial user registration data: ' . json_encode($userregistrationdata));
    $userregistrationdata = array_merge($response, $response[0], $userregistrationdata);
    if ($verbosetracking) session_tracking('Merged user registration data: ' . json_encode($userregistrationdata));

    ## special handler if some user keeps clicking validate.
    $userregistrationdata = array_map('unserialize', array_unique(array_map('serialize', $userregistrationdata)));
    if ($verbosetracking) session_tracking('Deduplicated user registration data: ' . json_encode($userregistrationdata));

    $session->set('userregistrationdata', $userregistrationdata);
    if ($verbosetracking) session_tracking('User registration data set in session.');

    $userid = $userregistrationdata['user_id'];


    ## Check if an existing record is found
    ## --------------------------------------------
    if (!empty($userregistrationdata['ALERT_existing_record'] == 'found' && !empty($userid))) {
      session_tracking('Existing record found', $userregistrationdata);
      // existing user data -- login if record is valid
      $loginresponse = $account->login($userregistrationdata['ALERT_existing_record-user'], $userregistrationdata['ALERT_existing_record-pass'], 'email');
      if ($loginresponse) {
        session_tracking('User logged in with existing record', $userregistrationdata['ALERT_existing_record-user']);
        header('location: /myaccount/');
        exit;
      }
    }


    $account->updateSettings($userid, ['status' => 'validated']);
    if ($verbosetracking) session_tracking('User status updated to validated for user ID: ' . $userid);

    $signup_process = $account->getUserAttribute($userid, 'userregistrationdata');
    if ($verbosetracking) session_tracking('Signup process data retrieved: ' . json_encode($signup_process));

    $process_user_data = $account->getuserdata($userid, 'user_id', 'validated');
    if ($verbosetracking) session_tracking('User data retrieved after validation: ' . json_encode($process_user_data));

    if (!empty($signup_process['description'])) {
      $signup_process = json_decode($signup_process['description'], true);
      if ($verbosetracking) session_tracking('Signup process description decoded: ' . json_encode($signup_process));
    }



    $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt)
VALUES (:user_id, 'validated', 'timeline', NOW(), 'active', 200, NOW(), NOW())";
    $stmt = $database->query($sql, [':user_id' => $userregistrationdata['user_id']]);



    switch (true) {
        #-----------------------------------
      case isset($signup_process['parental']):
        $gotourl = '/setup-parental';
        break;
        #-----------------------------------
      case isset($signup_process['giftcertificate']):
        $gotourl = '/setup-giftcertificate';
        break;
        #-----------------------------------
      case isset($signup_process['business']):
        $gotourl = '/setup-business';
        break;
        #-----------------------------------
      case ($process_user_data['status'] == 'validated' && $process_user_data['account_plan'] == 'free'):
        #   breakpoint($userid);
        $userid = $process_user_data['user_id'];
        $params = [
          'status' => 'active',
        ];
        $result =   $account->updateSettings($userid, $params);
        $account->login($userid, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
        header('location: /myaccount/welcome');
        exit;
        break;
        #-----------------------------------
      default:
        # !empty($_REQUEST['individual']):
        #  $signup_process['account_plan']=$_REQUEST['account_plan'] ?? false;

        //get invoice data
        $transaction_data = $account->getTransactionData($userid);
        // Check if the invoice data is valid and contains an transaction_id
        if (!empty($transaction_data) && isset($transaction_data[0]['transaction_id'])) {
          $transaction_id = $transaction_data[0]['transaction_id']; // Get the transaction_id from the first record
          $checkoutlink = '?t=' . $qik->encodeId($transaction_id);

          // You can now use $checkoutlink for further processing
          $gotourl = '/checkout' . $checkoutlink;
        } else {
          // Handle the case where no valid transaction_id is found
          $checkoutlink = '?u=' . $qik->encodeId($userid);
          $gotourl = '/checkout' . $checkoutlink;
        }

        break;
    }
    header('location: ' . $gotourl);
    exit;
  }
}




#-------------------------------------------------------------------------------
## Check if an existing record is found -- the user is using the signup form to login
#-------------------------------------------------------------------------------


## --------------------------------------------
$userregistrationdata = $session->get('userregistrationdata', []);
if (isset($userregistrationdata['user_id']) && (isset($userregistrationdata['ALERT_existing_record']) && $userregistrationdata['ALERT_existing_record'] == 'found')) {
  session_tracking('Existing record found from signup', $userregistrationdata);
  // existing user data -- login if record is valid
  $loginresponse = $account->login($userregistrationdata['ALERT_existing_record-user'], $userregistrationdata['ALERT_existing_record-pass'], 'email');
  if ($loginresponse) {
    session_tracking('User logged in with existing record', $userregistrationdata['ALERT_existing_record-user']);
    header('location: /myaccount/');
    exit;
  }
}




#-------------------------------------------------------------------------------
# adminsendagainrequest - RESEND REQUEST - from userlist
#-------------------------------------------------------------------------------
if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'resend' && isset($_REQUEST['id'])) {
  $resenddetail['userid'] = $_REQUEST['id'];

  $tmpsettings['status'] = ['pending', 'validated'];
  $resenddetailuser = $account->getuserdata($resenddetail['userid'], 'user_id', $tmpsettings);

  $userregistrationdata = $session->set('userregistrationdata', $resenddetailuser);
  session_tracking('adminsendagainrequest', $resenddetailuser);
}


#-------------------------------------------------------------------------------
# ENSURE A VALID REGISTRATION EXISTS IN SESSION
#-------------------------------------------------------------------------------
$userregistrationdata = $session->get('userregistrationdata', '');
$sentagainmessage = '';
if (empty($userregistrationdata['email'])) {
  $session->set('force_error_message', 'No registration data found. Please sign up again.');
  header('location: /signup');
  exit;
}


#-------------------------------------------------------------------------------
# HANDLE SENDING REPEAT VALIDATION CODE
#-------------------------------------------------------------------------------
if (!isset($userregistrationdata['validationemailsent']) || isset($_GET['adminsendagainrequest']) || (isset($_GET['sendagain']) && (!isset($userregistrationdata['validationemailsent_count']) || (isset($userregistrationdata['validationemailsent_count']) && $userregistrationdata['validationemailsent_count'] < 5)))) {
  $message = array();

  if (isset($_GET['sendagain'])) {
    $sendcount = isset($userregistrationdata['validationemailsent_count']) ? ($userregistrationdata['validationemailsent_count'] + 1) : 1;
    $colortag = '';
    switch ($sendcount) {
      case 1:
        $colortag = 'text-success';
        break;
      case 2:
        $colortag = 'text-bg-success';
        break;
      case 3:
        $colortag = 'text-danger';
        break;
      case 4:
        $colortag = 'text-bg-danger';
        break;
    }
    $sentagainmessage = '<p class="' . $colortag . '">We have sent it again at ' . date('r') . '. Please check your spam/junk folders.</p>';
  } else {
    $sendcount = 0;
  }

  $email = $userregistrationdata['email'] ?? '';
  $fullname = (isset($userregistrationdata['first_name']) ? $userregistrationdata['first_name'] : '') . ' ' . (isset($userregistrationdata['last_name']) ? $userregistrationdata['last_name'] : '');

  # Send verification email
  $message['toemail'] = $email;
  $message['fullname'] = $fullname;

  # $link = $appclass->getshortcode($website['fullurl'].'/validate-account?t='.sha1($email));
  # $message['validatelink'] = $link['shorturl'];
  $validatedata['rawdata'] = $email;
  $validatedata['user_id'] = $userregistrationdata['user_id'];
  $validatedata['sendcount'] = $sendcount;

  $validationcodes = $app->getvalidationcodes($validatedata);

  $link = $website['formalurl'] . '/validate-account?t=' . $validationcodes['long'];
  $message['validatelink'] = $link;
  $message['validationcode'] = $local_validationcode = $validationcodes['mini'];

  # print_r($message);
  $result = $mail->sendVerificationEmail($message);

  $userregistrationdata['validationemailsent'] = date('r');
  $userregistrationdata['validationemailsent_count'] = $sendcount;

  $session->set('userregistrationdata', $userregistrationdata);
}
?>



<?PHP
if ($mode != 'dev' && $userregistrationdata['account_type'] != 'test') $local_validationcode = '';
#-------------------------------------------------------------------------------
# ASK USER FOR VALIDATION CODE
#-------------------------------------------------------------------------------
#include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


if (isset($_GET['adminsendagainrequest'])) {
  $content = '<h1 id="validateEmailTitle" class="display-1">Validation Email Sent</h1>
  <p class="mb-4">We\'ve sent a 6-character code to <strong>' . htmlspecialchars($userregistrationdata["email"]) . '</strong></p>
  <p class="alert alert-info py-2" role="alert">Remind User to check your spam/junk folder if they don\'t see our email.</p>
</div>
</div>
<div class="row justify-content-center mt-4">
<div class="col-4">
<a href="/myaccount/" class="btn btn-lg btn-primary">Account</a>
</div>';
} else {

  $content = '
  <h1 id="validateEmailTitle" class="display-2">Validate Your Email</h1>
  <p class="mt-4">We\'ve sent a 6-character code to: <strong>' . htmlspecialchars($userregistrationdata["email"]) . '</strong>. </p>
  <p>Click the link in the email or enter it below to confirm your email and complete the setup.</p>
  ' . ($sendcount < 5 ? '<a class="text-success btn btn-sm" href="validate-account?sendagain">Resend Code</a>' : '') . '
  <p class="alert alert-info py-2" role="alert">Check your spam/junk folder if you don\'t see our email.</p>
</div>
</div>
<div class="row justify-content-center mt-4">
<div class="col-4">
  <form method="post" action="/validate-account">
    ' . $display->inputcsrf_token() . '
    <div class="form-group">
      <label for="validationCode">Validation Code:</label>
      <input type="text" class="form-control form-control-sm" id="validationCode" name="validationCode" placeholder="Enter code" autocomplete="off" value="' . htmlspecialchars($local_validationcode) . '">
    </div>
    <button type="submit" class="btn btn-success btn-lg mt-3 p-3">Validate</button>
  </form>
</div>
';
}


echo '
<!-- Start -->
<section class="container-xxl py-6 main-content "  aria-labelledby="validateEmailTitle ">
  <div class="container text-center card p-5">
    <div class="row justify-content-center ">
      <div class="col-lg ">
        <i class="bi bi-exclamation-triangle display-1 text-primary" aria-hidden="true"></i>
       ' . $content . '
    </div>
';

#-------------------------------------------------------------------------------
# DISPLAY VALIDATION QR CODE in MODAL
#-------------------------------------------------------------------------------
if (!empty($validationcodes)) {
  echo '  <div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-12 my-5">
            <button id="showqrcode" data-bs-toggle="modal" data-bs-target="#qrCodeModal" class="btn btn-sm btn-primary mt-3 mx-auto">Can\'t Wait? Show QR Code</button>
        </div>
    </div>
</div>
<!-- Modal Structure for QR Code -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrCodeModalLabel">QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
            <h3 class="mb-5">' . $userregistrationdata['first_name'] . ', scan to validate your account:</h3>
                <img class="m-5" id="qrCodeImage" src="' . $website['formalurl'] . '/qr?i=' . urlencode($website['formalurl'] . '/validate-account?t=' . $validationcodes['long']) . '" alt="QR Code" style="width: 200px; height: 200px;">
            </div>
        </div>
    </div>
</div>
';
}

echo '
</div>
</section>
<!-- End -->
';

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
