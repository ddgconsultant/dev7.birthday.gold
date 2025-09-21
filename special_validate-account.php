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

    if ($response['validated']) {
        $userregistrationdata=$session->get('userregistrationdata');
        $userregistrationdata=array_merge($response, $userregistrationdata);
        $session->set('userregistrationdata', $userregistrationdata);
        $userid= $userregistrationdata['user_id'];
        $account->updateSettings($userid, ['status'=>'validated']);
        
if($userregistrationdata['plan']=='gold') {
        header('location: /special_applyplan_handler?_token='.$display->inputcsrf_token('tokenonly') );
        exit;
} else 
        header('location: /special_applyplan?plan=life');
        exit;
    }
}



#-------------------------------------------------------------------------------
# ENSURE A VALID REGISTRATION EXISTS IN SESSION
#-------------------------------------------------------------------------------
$userregistrationdata=$session->get('userregistrationdata', '');
$sentagainmessage='';
if (!isset($userregistrationdata[':email'])) {
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
$email=$userregistrationdata[':email'];
$fullname=$userregistrationdata[':first_name'].' '.$userregistrationdata[':last_name'];
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
if ($site!='dev4') $local_validationcode='';
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
<p class="fw-bold">' . $userregistrationdata[':first_name'] . ', we have emailed you a six character validation code at: ' . $userregistrationdata[':email'] . '
' . ($sendcount < 5 ? '<a class="text-success btn-sm button" href="validate-account?sendagain">Send Again</a>' : '') . '
</p>
</div>
</div>
<div class="row justify-content-center mt-4">
<div class="col-4">

<form method="post" action="/validate-account">
' . $display->inputcsrf_token() . '
<div class="form-group">
<label for="validationCode">Validation Code:</label>
<input type="text" class="form-control form-control-sm" id="validationCode" placeholder="Enter validation code" name="validationCode" value="'.$local_validationcode.'">
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
