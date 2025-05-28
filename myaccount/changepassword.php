<?php

$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$redirectlink = '/myaccount/changepassword';
$errormessage = '';
#-------------------------------------------------------------------------------
# CHECK FOR VALID VALIDATION 
#-------------------------------------------------------------------------------
if (isset($_GET['t'])) {
    if (isset($_GET['t'])) $checkdata['long'] = $_GET['t'];
    if (isset($_POST['validationCode'])) $checkdata['mini'] = $_POST['validationCode'];
    $checkdata['type'] = 'forgotpassword';
    $response = $app->checkvalidationcodes($checkdata);
    #breakpoint($response);
    if ($response['validated']) {
        $session->set('passwordreset', $response);
        header('location: /myaccount/changepassword');
        exit;
    } else {

        $errormessage = '<div class="alert alert-danger">That link didn\'t work.  Please try again.</div>';
        $transferpage['url'] = '/forgot';
        $transferpage['message'] = $errormessage;
        $system->endpostpage($transferpage);
        exit;
    }
}

#-------------------------------------------------------------------------------
# HANDLE FORM POSTING
#-------------------------------------------------------------------------------
if ($formdata = $app->formposted()) {
    $checkdata = array();

    $passwordresetdata = $session->get('passwordreset', '');
    if (empty($passwordresetdata)) {

        ## THIS MEANS THAT THE USER IS USING THE REAL PASSWORD RESET FORM AND THE PROVIDED PASSWORD MUST MATCH


        $current_user_data = $session->get('current_user_data', '');
        if (empty($current_user_data['user_id'])) {
            $errormessage = '<div class="alert alert-danger">You need to log in to change your password.</div>';
            $transferpage['url'] = '/login';
            $transferpage['message'] = $errormessage;
            $system->endpostpage($transferpage);
            exit;
        }
        #  $userid= $userregistrationdata['user_id'];
        # $current_user_data=$account->getuserdata($userid, 'user_id');
        ## got a user, now check to see if old password matches (can also be )
        if (!password_verify($formdata['inputcurrentPassword'], $current_user_data['password'])) {
            # breakpoint($formdata['inputcurrentPassword'].'/'. $current_user_data['password']);
            ## we do not have matching old and existing passwords... 
            $errormessage = '<div class="alert alert-danger">Your old password does not match.<br>You use this <a href="/forgot">link</a> to reset your password.</div>';
            $transferpage['url'] = '/myaccount/changepassword';
            $transferpage['message'] = $errormessage;
            $system->endpostpage($transferpage);
            exit;
        }
    } else {

        if ($passwordresetdata['validated'] !== true) {
            ## new password doesn't match confirm password
            $session->unset('passwordreset');
            unset($current_user_data);
            $errormessage = '<div class="alert alert-danger">Hmmm... something weird happened.</div>';
            $transferpage['url'] = '/myaccount/changepassword';
            $transferpage['message'] = $errormessage;
            $system->endpostpage($transferpage);
            exit;
        }
        $redirectlink = '/login';
        $userid = $passwordresetdata[0]['user_id'];
    }

    if ($formdata['inputnewPassword'] != $formdata['inputconfirmPassword']) {
        ## new password doesn't match confirm password
        $errormessage = '<div class="alert alert-danger">Your New password and Confirm password does not match.</div>';
        $transferpage['url'] = '/myaccount/changepassword';
        $transferpage['message'] = $errormessage;
        $system->endpostpage($transferpage);
        exit;
    }

    ## we made it this far successfully... update the user password
    $session->unset('passwordreset');
    $userid = $current_user_data['user_id'];
    $password = $formdata['inputnewPassword'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $output = print_r($userid, 1) . '|' . print_r($hashed_password, 1) . '|' . print_r($password, 1) . '|' . print_r($passwordresetdata, 1) . '|' . print_r($userregistrationdata, 1) . '|' . print_r($current_user_data, 1);
    $response = $account->updateSettings($current_user_data['user_id'], ['password' => $hashed_password]);
    $current_user_data = $account->getuserdata($userid, 'user_id');
    $output .= "\n-----------------------------------\nPOSTUPDATE\n" . print_r($response, 1) . '|' . print_r($current_user_data, 1);
    $outputarrayelement['output'] = $output;
    $outputarrayelement['response'] = $response;
    $outputarrayelement['current_user_data'] = $current_user_data;
    $outputarrayelement['hashed_password'] = $hashed_password;
    session_tracking('CHANGEPASSWORD_SUCCESS', $outputarrayelement);
    $session->set('current_user_data', $current_user_data);
    $errormessage = '<div class="alert alert-success">Your password was successfully changed.</div>';
    $transferpage['url'] = $redirectlink;
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
    exit;
}


$transferpage = $system->startpostpage();

#-------------------------------------------------------------------------------
# ASK USER FOR NEW PASSWORDS
#-------------------------------------------------------------------------------
$passwordresetdata = $session->get('passwordreset', '');
$userfullname = '';
if (!empty($passwordresetdata)) {
    $tempuserdata = $account->getuserdata($passwordresetdata[0]['user_id'], 'user_id');
    $userfullname = '<div class="mb-3 text-center"><h3>' . $tempuserdata['first_name'] . ", welcome back.  Let's reset your password.</h3></div>";
}

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');



echo ' <div class="container  main-content">
<div class="card px-5  pb-5">
<h1 class="py-4">Change Password</h1>
<div class="row">
';
echo $display->formaterrormessage($transferpage['message']);
echo '
' . $userfullname . '
</div>
<form action="changepassword" method="post">                           
' . $display->inputcsrf_token() . '
<input name="returnto" type="hidden" value="/myaccount/changepassword">                   
';

if (empty($passwordresetdata['validated'])) {

    echo '      <!-- Form Group (current password)-->
<div class="mb-3">
<label class="small mb-1" for="inputcurrentPassword">Current Password</label>
<input class="form-control" name="inputcurrentPassword" id="inputcurrentPassword" type="password" placeholder="Enter current password">
</div>';
}


echo '
<!-- Form Group (new password)-->
<div class="mb-3">
<label class="small mb-1" for="inputnewPassword">New Password</label>
<input class="form-control"  name="inputnewPassword" id="newPassword" type="password" placeholder="Enter new password">
</div>
<!-- Form Group (confirm password)-->
<div class="mb-3">
<label class="small mb-1" for="inputconfirmPassword">Confirm Password</label>
<input class="form-control"  name="inputconfirmPassword"  id="inputconfirmPassword" type="password" placeholder="Confirm password">
</div>
<button class="btn btn-primary" type="submit">Save</button>
</form>

';


echo '</div></div></div></div></div>';

echo '  <script src="/public/js/passwordhelper.js" language="javascript"></script>';



include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
