<?php

$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$redirectlink = '/resetpassword';
$errormessage = '';
#-------------------------------------------------------------------------------
# CHECK FOR VALID VALIDATION 
#-------------------------------------------------------------------------------
if (isset($_GET['t'])) {
    if (isset($_GET['t'])) $checkdata['long'] = $_GET['t'];
    if (isset($_POST['validationCode'])) $checkdata['mini'] = $_POST['validationCode'];
    $checkdata['type'] = 'forgotpassword';
    $response = $app->checkvalidationcodes($checkdata);
    if ($response['validated']) {
        $session->set('passwordreset', $response);
        header('location: /resetpassword');
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
            $transferpage['url'] = '/resetpassword';
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
            $transferpage['url'] = '/resetpassword';
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
        $transferpage['url'] = '/resetpassword';
        $transferpage['message'] = $errormessage;
        $system->endpostpage($transferpage);
        exit;
    }

    ## we made it this far successfully... update the user password
    $session->unset('passwordreset');
    $password = $formdata['inputnewPassword'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $output = print_r($userid, 1) . '|' . print_r($hashed_password, 1) . '|' . print_r($password, 1) . '|' . print_r($passwordresetdata, 1) . '|' . print_r($userregistrationdata, 1) . '|' . print_r($current_user_data, 1);
    $response = $account->updateSettings($userid, ['password' => $hashed_password]);
    $current_user_data = $account->getuserdata($userid, 'user_id');
    $output .= "\n-----------------------------------\nPOSTUPDATE\n" . print_r($response, 1) . '|' . print_r($current_user_data, 1);
    $outputarrayelement['output'] = $output;
    $outputarrayelement['response'] = $response;
    $outputarrayelement['current_user_data'] = $current_user_data;
    $outputarrayelement['hashed_password'] = $hashed_password;
    session_tracking('RESETPASSWORD_SUCCESS', $outputarrayelement);
    $session->set('current_user_data', $current_user_data);
    $errormessage = '<div class="alert alert-success">Your password was successfully changed.</div>';
    $transferpage['url'] = $redirectlink;
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
    exit;
}


$transferpage = $system->startpostpage();
$additionalstyles.='
<style>
/* Floating Label Styles */
.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label,
.form-floating > .form-select ~ label {
    transform: scale(0.85) translateY(-0.7rem) translateX(0.15rem);
}

.form-floating .form-control {
    height: calc(3.5rem + 2px);
    line-height: 1.25;
    padding: 1rem 0.75rem;
}

.form-floating > label {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    padding: 1rem 0.75rem;
    overflow: hidden;
    text-align: start;
    text-overflow: ellipsis;
    white-space: nowrap;
    pointer-events: none;
    border: 1px solid transparent;
    transform-origin: 0 0;
    transition: opacity .1s ease-in-out,transform .1s ease-in-out;
}

/* Form Control Styles */
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: white;
    color: #212529;
}

.form-control:focus {
    outline: none;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-control::placeholder {
    color: transparent;
}

.form-control:focus::placeholder {
    color: #adb5bd;
}

/* Remove Chrome autofill blue background */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 30px white inset !important;
    -webkit-text-fill-color: inherit !important;
    transition: background-color 5000s ease-in-out 0s;
}

/* Card styling to match login page */
.reset-container {
    width: 100%;
    max-width: 480px;
    margin: 1rem auto 2rem;
}

.reset-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    padding: 2rem;
}

/* Mobile spacing */
@media (max-width: 767px) {
    .reset-container {
        margin-top: 50px;
        margin-bottom: 2rem;
    }
    
    .main-content {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
}

/* Tablet/Desktop */
@media (min-width: 768px) {
    .reset-container {
        margin: 1.5rem auto 3rem;
    }
    
    .reset-card {
        padding: 3rem;
    }
}
    
    </style>
    ';
#-------------------------------------------------------------------------------
# ASK USER FOR NEW PASSWORDS
#-------------------------------------------------------------------------------
$passwordresetdata = $session->get('passwordreset', '');
$current_user_data = $session->get('current_user_data', '');

// Check if user has access to this page
if (empty($passwordresetdata) && empty($current_user_data['user_id']) && empty($_GET['t'])) {
    $errormessage = '<div class="alert alert-danger">You need to access this page from a secured link that was sent to you, or login first to change your password.</div>';
    $transferpage['url'] = '/login';
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
    exit;
}

$userfullname = '';
if (!empty($passwordresetdata)) {
    $tempuserdata = $account->getuserdata($passwordresetdata[0]['user_id'], 'user_id');
    $userfullname = '<div class="mb-3 text-center"><h3>' . $tempuserdata['first_name'] . ", welcome back.  Let's reset your password.</h3></div>";
}

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '<div class="main-content">
<div class="reset-container">
<div class="reset-card">';


$formhead='
<h1 class="py-4 text-center">Reset Password</h1>
<div class="row">'.
$display->formaterrormessage($transferpage['message']).'
' . $userfullname . '
</div>
<form action="resetpassword" method="post">                           
' . $display->inputcsrf_token() . '
<input name="returnto" type="hidden" value="/resetpassword">                   
';
$addfield='';


if (empty($passwordresetdata['validated'])) {
$formhead='
<h1 class="py-4">Change Password</h1>
<div class="row">'.
$display->formaterrormessage($transferpage['message']).'
' . $userfullname . '
</div>
<form action="/myaccount/changepassword" method="post">                           
' . $display->inputcsrf_token() . '
<input name="returnto" type="hidden" value="/myaccount/changepassword">                   
';

$addfield= '      <!-- Form Group (current password)-->
<div class="mb-4 form-floating">
<input class="form-control" name="inputcurrentPassword" id="inputcurrentPassword" type="password" placeholder="Current Password">
<label for="inputcurrentPassword">Current Password</label>
</div>';
}


echo $formhead;

echo $addfield;
echo '
<!-- Form Group (new password)-->
<div class="mb-4 form-floating">
<input class="form-control"  name="inputnewPassword" id="newPassword" type="password" placeholder="New Password">
<label for="newPassword">New Password</label>
</div>
<!-- Form Group (confirm password)-->
<div class="mb-4 form-floating">
<input class="form-control"  name="inputconfirmPassword"  id="inputconfirmPassword" type="password" placeholder="Confirm Password">
<label for="inputconfirmPassword">Confirm Password</label>
</div>
<div class="text-end">
<button class="btn btn-lg btn-primary px-5 mt-3" type="submit">Save</button>
</div>
</form>

';


echo '</div></div></div>';  // Close reset-card, reset-container, main-content

echo '  <script src="/public/js/passwordhelper.js" language="javascript"></script>';



include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
