<?php 

$addClasses[]='Mail';

include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 
$sendcount=0;
$local_validationcode='';


#-------------------------------------------------------------------------------
# CHECK FOR VALID VALIDATION AND GO TO PLANS/CHECKOUT
#-------------------------------------------------------------------------------
if (isset($_GET['t']) || $app->formposted()) {
    $checkdata = array();
    if (isset($_GET['t'])) $checkdata['long'] = $_GET['t'];
    if (isset($_POST['validationCode'])) $checkdata['mini'] = $_POST['validationCode'];
    $response = $app->checkvalidationcodes($checkdata);
      


## USER IS ALREADY VALIDATED -- navigate to MyHome - which may redirect to login
## --------------------------------------------
if ($response['validated'] && !empty($response[0]['user_id'] )) {
$process_user_data=$account->getuserdata($response[0]['user_id'], 'user_id');
if ($process_user_data['status']=='active' && !empty($process_user_data['account_plan'])) {
header('location: /myaccount/welcome');
exit;   
}


## SUCCESSFUL Validation -- continue on
## --------------------------------------------
$userregistrationdata=$session->get('userregistrationdata');
if (empty($userregistrationdata)) $userregistrationdata=array();
$userregistrationdata=array_merge($response, $response[0], $userregistrationdata);
## special handler if some user keeps clicking validate.
$userregistrationdata = array_map('unserialize', array_unique(array_map('serialize', $userregistrationdata)));
$session->set('userregistrationdata', $userregistrationdata);        
# print_r($process_user_data); exit;
$userid= $userregistrationdata['user_id'];
$account->updateSettings($userid, ['status'=>'validated']);
$signup_process=$account->getUserAttribute($userid, 'userregistrationdata');
if (!empty($signup_process['description'])) {
$signup_process=json_decode($signup_process['description'], true);
}

switch (true) {
    #-----------------------------------
    case isset($signup_process['parental']):
    $gotourl='/setup-parental';
    break;
    #-----------------------------------
    case isset($signup_process['giftcertificate']):
        $gotourl='/setup-giftcertificate';
    break;
    #-----------------------------------
    case isset($signup_process['business']):
        $gotourl='/setup-business';
    break;
    #-----------------------------------
    default:
    # !empty($_REQUEST['individual']):
  #  $signup_process['account_plan']=$_REQUEST['account_plan'] ?? false;

    
    $gotourl='/checkout';
    break;
    }
header('location: /validate-failed');
exit;
}
}



#-------------------------------------------------------------------------------
# ENSURE A VALID REGISTRATION EXISTS IN SESSION
#-------------------------------------------------------------------------------
$userregistrationdata=$session->get('userregistrationdata', '');
$sentagainmessage='';
if (empty($userregistrationdata['email'])) {
    header('location: /signup');
    exit;
}



#-------------------------------------------------------------------------------
# HANDLE SENTING REPEAT VALIDATION CODE
#-------------------------------------------------------------------------------
if (!isset($userregistrationdata['validationemailsent']) || (isset($_GET['sendagain']) && (!isset($userregistrationdata['validationemailsent_count']) || (isset($userregistrationdata['validationemailsent_count']) && $userregistrationdata['validationemailsent_count']<5) ))) {
$message=array();

if (isset($_GET['sendagain'])) {
$sendcount=($userregistrationdata['validationemailsent_count']+1);
switch($sendcount){
    case 1: $colortag='text-success'; break;
    case 2: $colortag='text-bg-success'; break;
    case 3: $colortag='text-danger'; break;
    case 4: $colortag='text-bg-danger'; break;
}
$sentagainmessage='<p class="'>$colortag.'">We have sent it again at '.date('r').'.  Please check your spam/junk folders.</p>';
}
$email=$userregistrationdata['email'];
$fullname=$userregistrationdata['first_name'].' '.$userregistrationdata['last_name'];
// Send verification email
#   global $website;
$message['toemail']=$email;
$message['fullname']=$fullname;
# $link= $appclass->getshortcode( $website['fullurl'].'/validate-account?t='.sha1($email) );
#$message['validatelink']=$link['shorturl'];
$validatedata['rawdata']=$email;
$validatedata['user_id']=$userregistrationdata['user_id'];
$validatedata['sendcount']=$sendcount;

$validationcodes=$app->getvalidationcodes($validatedata);

$link=$website['fullurl'].'/validate-account?t='.$validationcodes['long'];
$message['validatelink']=$link;
$message['validationcode']=$local_validationcode=$validationcodes['mini'];
#print_r($message);
$result=$mail->sendVerificationEmail($message);
$userregistrationdata['validationemailsent']=date('r');
$userregistrationdata['validationemailsent_count']=($sendcount++);

$session->set('userregistrationdata', $userregistrationdata);
}
?>


<?PHP
if ($mode!='dev') $local_validationcode='';
#-------------------------------------------------------------------------------
# ASK USER FOR VALIDATION CODE
#-------------------------------------------------------------------------------
include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 
echo '
<!-- Start -->
<div class="container-xxl py-6">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg">
<i class="bi bi-exclamation-triangle display-1 text-primary"></i>
<h1 class="display-1">Validate Your Email</h1>
<h3 class="mb-4">We need to ensure that you will receive our messages</h3>
' . $sentagainmessage . '
<p class="fw-bold">' . $userregistrationdata['first_name'] . ', we have emailed you a six character validation code at: ' . $userregistrationdata['email'] . '
' . ($sendcount < 5 ? '<a class="text-success btn-sm button" href="validate-account?sendagain">Send Again</a>' : '') . '
</p>
<p class="text-bg-info py-2">TIP: Don\'t forget to check your spam/junk folder.</p>
</div>
</div>
<div class="row justify-content-center mt-4">
<div class="col-4">

<form method="post" action="/validate-account">
' . $display->inputcsrf_token() . '
<div class="form-group">
<label for="validationCode">Validation Code:</label>
<input type="text" class="form-control form-control-sm" id="validationCode" placeholder="Enter validation code"  autocomplete="nope" autocomplete="off" name="validationCode" value="'.$local_validationcode.'">
</div>
<button type="submit" class="btn btn-primary btn-block mt-3">Validate</button>

</form>

</div>
</div>
</div>
</div>
<!--  End -->
  ';
#print_r($userregistrationdata);

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php'); 
