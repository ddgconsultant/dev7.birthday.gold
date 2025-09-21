<?php 

$addClasses[]='Mail';
$addClasses[]='Convert';
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');



#-------------------------------------------------------------------------------
# PROTECT ACCIDENTIAL USAGE
#-------------------------------------------------------------------------------
$allowcontinue=false;
if ($account->isadmin()) { $allowcontinue=true;}
if ($account->isimpersonator()) { $allowcontinue=true;}
if ($app->formposted()) { $allowcontinue=true;}
if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'welcome') !== false)) { $allowcontinue=true;}

if(!$allowcontinue) {
    header('Location: /myaccount');
    exit;
}



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$paymenttag='';
$birthdayprioritytag='';
$titletag='Gift Certificate';
$displaytype='generated'; 
$giftcode=$session->get('generateGiftCertificateCode');
$done=false;
$recipient='';
$message='';


/*
#-------------------------------------------------------------------------------
# PROCESS DOWNLOAD
#-------------------------------------------------------------------------------
if ($app->formposted() && !empty($giftcode)){
$filepath=$_SERVER['DOCUMENT_ROOT'].'/downloads/gc/';
$filename=str_replace('','', $giftcode);
$finalfile=$filepath.$filename;

$recipient=(!empty($_REQUEST['recipient']))?$_REQUEST['recipient']:'';
$message=(!empty($_REQUEST['specialmessage']))?$_REQUEST['specialmessage']:'';
include($dir['core'].'/'.$website['ui_version'].'/content-giftcertificate.php');

$response = [];
switch ($_POST['outputFormat']) {

case 'pdf':
// Convert to PDF
$pdfOutput = $finalfile.'.pdf';

$pdfResult = $convert->toPDF('https://www.google.com?q='.$recipient, $pdfOutput);

if ($pdfResult === "PDF successfully generated.") {
  // Set headers for file download
  header('Content-Description: File Transfer');
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename='.$filename.'.pdf');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($pdfOutput));

  // Send the file to the browser
  readfile($pdfOutput);



      $response['status'] = "success";

      echo json_encode($response);
      exit;
  // Output JavaScript for redirect
  // echo '<script type="text/javascript">
  //         window.location = "/myaccount/done-giftcertificate.php";
  //       </script>';
} else {
  #echo "Failed to generate PDF.";
  $response['status'] = "error";
  echo json_encode($response);
  exit;
}
  $done=true;


break;
case 'jpg': 


  $done=true;
break;

}
$done=true;
if ($done) { 

$displaytype='downloaded_success_lockout'; #   echo "Failed to update gift code and status for user.";

  // Successfully updated the user record
 # $displaytype='success_lockout'; # echo "Successfully updated gift code and status for user.";
  $account->logout();


}

}


*/

#-------------------------------------------------------------------------------
# HANDLE ACTIONS
#-------------------------------------------------------------------------------
$log=true;
// Determine the action based on the query string
$action = $_GET['action'] ?? '';
if ($log) error_log("File Ready: " . $session->get('file_ready'));
if ($log) error_log("File Path: " . $session->get('file_path'));
if ($log) error_log("File Error: " . $session->get('file_error'));

switch ($action) {

  case 'reset':
   
$updatefields=  [
  'feature_giftcode'=> '',
  'status'=> 'active',
  ];
  $userid= $current_user_data['user_id'];
  $result=   $account->updateSettings($userid, $updatefields);
  $account->logout();
  header('location: /login');
    break;



  case 'poll':
    if ($log) error_log("action-poll");
    $response = ['ready' => false, 'error' => ''];
    if ($session->get('file_ready')) {
        $session->set('file_path', $session->get('file_ready'));
        $response['ready'] = true;
        $session->unset('file_ready');
    } elseif ($session->get('file_error')) {
        $response['error'] = $session->get('file_error');
        $session->unset('file_error');
    }
    echo json_encode($response);
    break;

case 'download':
  if ($log) error_log("action-download");

  if ($session->get('file_ready')) {
    $session->set('file_path', $session->get('file_ready'));
    $response['ready'] = true;
    $session->unset('file_ready');
}



$file_path = $session->get('file_path');
if ($file_path) {
$session->unset('file_path');
// Add any additional security checks here
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;
}
break;

case 'error':
  if ($log) error_log("action-error");
echo "An error occurred.";
break;

default:
// Your existing code to handle form submission and generate the gift certificate
// ...
if ($log) error_log("action-default");
if ($app->formposted() && !empty($giftcode)) {
  if ($log) error_log("action-formhandler");
// Your existing code for PDF generation
$filepath=$_SERVER['DOCUMENT_ROOT'].'/downloads/gc/';
$filename=str_replace('','', $giftcode);
$finalfile=$filepath.$filename;

$recipient=(!empty($_REQUEST['recipient']))?$_REQUEST['recipient']:'';
$message=(!empty($_REQUEST['specialmessage']))?$_REQUEST['specialmessage']:'';
include($dir['core'].'/'.$website['ui_version'].'/content-giftcertificate.php');

$response = [];

$gencert=[];
$gencert['generate_gc_to']=$recipient;
$gencert['generate_gc_code']=$giftcode;
$gencert['generate_gc_message']=$message;
$gencert['generate_gc_format']=$_POST['outputFormat'];
$session->set('generate_gc_to', $recipient);
$session->set('generate_gc_code', $giftcode);
$session->set('generate_gc_message', $message);
$session->set('generate_gc_format', $_POST['outputFormat']);
$query_params = http_build_query($gencert);
$encoded_params = base64_encode($query_params);

$url = 'https://dev.birthday.gold/generategc?' . $query_params;
$url = 'https://dev.birthday.gold/generategc?generate_gc_code='.$giftcode.'&'; # . $query_params;
$url = 'https://dev.birthday.gold/generategc?' . 'data=' . $encoded_params;


switch ($_POST['outputFormat']) {
##--------------------------------
case 'pdf':
  if ($log) error_log("generate-pdf");
// Convert to PDF
$pdfOutput = $finalfile.'.pdf';
$outfilename=$filename.'.'.$_POST['outputFormat'];
$pdfResult = $convert->toPDF($url, $pdfOutput);
if ($pdfResult === "PDF successfully generated.") {
  if ($log) error_log("generate-pdfsuccess");
$session->set('file_ready', $pdfOutput);
$done=true;
} else {
  ### respond with error/failure.
  if ($log) error_log("generate-pdferror");
  $session->set('file_error', 'Failed to generate PDF.');
}
break;
##--------------------------------
case 'jpg': 
  $pdfOutput = $finalfile.'.jpg';
  $outfilename=$filename.'.'.$_POST['outputFormat'];
  $pdfResult = $convert->toJPG($url, $pdfOutput);
  if ($pdfResult === "JPG successfully generated.") {
  $session->set('file_ready', $pdfOutput);
  }

$done=true;
break;
}
echo $url;
if ($done) {

$input['to']=$current_user_data['email'];
#$input['toemail']=$current_user_data['email'];
$input['attachment'] =$pdfOutput;
$input['templatename']='giftcertificate';
$mail->sendtemplate($input);
   
  

  $session->set('download_gc_file', $outfilename);
  header('location: /myaccount/done-giftcertificate');
  exit;
}
}


break;


}


if ($log) error_log("end");



#-------------------------------------------------------------------------------
# CREATE CODE
#-------------------------------------------------------------------------------
if (empty($giftcode)) {
  $giftcode = $account->generateGiftCertificateCode();

if (empty($giftcode)) {
    // Couldn't generate gift code
   $displaytype='failed_generate'; # echo "<h2>An error occurred while generating the gift certificate code. Please try again.</h2>";
    // You can include a button or link here to allow the user to retry
   # echo '<a href="myaccount/giftcertificate" class="btn button">Try Again</a>';
} else {
  $session->set('generateGiftCertificateCode',  $giftcode);

$updatefields=  [
  'feature_giftcode'=> $giftcode,
  'status'=> 'giftlock',
  ];
  $userid= $current_user_data['user_id'];
  $result=   $account->updateSettings($userid, $updatefields);
 
}
}


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$additionaljs= '';
$headerattribute['additionalcss']='<link rel="stylesheet" href="/public/css/myaccount.css">
<style>
.feature {
width: 100px;  /* Set width */
height: 100px;  /* Set height */
display: flex;
align-items: center;
justify-content: center;
}

.feature i {
font-size: 48px;  /* Increase icon size */
}
</style>
';

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 


switch ($displaytype) {

##---------------------------------------------------------
case 'generated':
echo '
<!-- Gift Certificate Start -->
<div class="container-xxl py-5 flex-grow-1">
  <div class="container text-center">
    <div class="row justify-content-center">
      <div class="col-12">
        <picture>
          <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f49d/512.webp" type="image/webp">
          <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f49d/512.gif" alt="💝" width="64" height="64">
        </picture>
        <h1>Gift Certificate</h1>
        <h4 class="mb-4">' . $current_user_data['first_name'] . ', we have issued your gift certificate.</h4>
      </div>
    </div>
    <h6>Click the Personalize button to receive the printable gift certificate that you can give to your special someone.</h6>
   <p class="text-danger fw-bold pb-0 mb-0">Make sure to either download the certificate or jot down the code for future use.</p>
   <p class="mb-3">If you need to access the code later and haven\'t saved it, you\'ll need to reach out to us at support@birthday.gold.</p>
    <section class="pt-2">                
      <div class="container px-lg-5">
        <!-- Single Account Card -->
        <div class="row gx-lg-5 justify-content-center">
          <div class="col-lg-8 mb-5 account-type-card" data-target="#individual">
            <div class="card bg-light border-0 h-100">
              <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                <div class="feature bg-dark bg-gradient text-white rounded-3 mb-2"><i class="bi bi-gift"></i></div>
                <h2 class="fs-4 fw-bold">Your Gift Certificate Code:</h2>
                <h1 class="text-bg-dark" id="giftCodeElement">' . $giftcode . '</h1>
                <form method="post" action="/myaccount/setup-giftcertificate">
                 ' . $display->inputcsrf_token() . '
                <div class="mb-3">
                  <label for="recipient" class="form-label d-none">Recipient\'s Name</label>
                  <input type="text" class="form-control" id="recipient" name="recipient" value="'.$recipient.'" placeholder="Enter recipient\'s name">
                </div>
                <div class="mb-3">
                  <label for="specialmessage" class="form-label d-none">Special Message</label>
                  <textarea class="form-control" id="specialmessage" name="specialmessage" rows="5" placeholder="Enter your special message" maxlength="500">'.$message.'</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label pe-2">Select Download Format:</label>
<input type="radio" class="btn-check" name="outputFormat" id="pdf" value="pdf" checked autocomplete="off" >
<label class="btn px-4" for="pdf">PDF</label>

<input type="radio" class="btn-check" name="outputFormat" id="jpg" value="jpg" autocomplete="off">
<label class="btn px-4" for="jpg">JPG</label>      
                </div>
                <button type="submit" class="btn btn-primary">Personalize</button>
              </form>
              </div>
            </div>
          </div>
        </div>
      </div>';

      if ($account->isadmin() || $account->isimpersonator()) echo '
      <a type="button" href="/myaccount/setup-giftcertificate?action=reset" class="btn btn-primary">Reset</a>
';

      echo '
    </section>
  </div>
</div>
<!-- Gift Certificate End -->
';
$additionaljs='
<script>
function copyText() {
  const giftCodeElement = document.getElementById("giftCodeElement");
  const giftCode = giftCodeElement.textContent || giftCodeElement.innerText;

  navigator.clipboard.writeText(giftCode).then(() => {
    alert("Copied to clipboard");
  }).catch(err => {
    //alert("Failed to copy text");
  });
}

</script>';

break;

##---------------------------------------------------------
case 'failed_generate':
  echo '
<!-- Gift Certificate Start -->
<div class="container-xxl py-5 flex-grow-1">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-12">
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/203c_fe0f/512.webp" type="image/webp">
<img src="https://fonts.gstatic.com/s/e/notoemoji/latest/203c_fe0f/512.gif" alt="‼" width="64" height="64">
</picture>
<h1 class="display-1">Gift Certificate</h1>
<h3 class="mb-4">' . $current_user_data['first_name'] . ', there was an issue creating your gift certificate.</h3>
</div>
</div>
<h6>Let\'s try again.</h6>
<div class="m-3">
<a  href="/myaccount/setup-giftcertificate" class="btn btn-primary">Generate Again.</a>
</div>
</div>
</div>
<!-- Gift Certificate End -->
';
  break;



##---------------------------------------------------------
case 'downloaded_success_lockout':
  echo '
<!-- Gift Certificate Start -->
<div class="container-xxl py-5 flex-grow-1">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-12">
<picture>
  <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f3c1/512.webp" type="image/webp">
  <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f3c1/512.gif" alt="🏁" width="64" height="64">
</picture>
<h1 class="display-1">Gift Certificate</h1>
<h3 class="mb-4">' . $current_user_data['first_name'] . ', we\'ve personalized your gift certificate and<br>
started the download to your device.</h3>
</div>
</div>
<div class="m-3">
<h5>This ends your session with us.  You can close this window</h5>
</div>
</div>
</div>
<!-- Gift Certificate End -->
';


break;


}


$footerattribute['postfooter'] = '
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="/public/js/myaccount.js" language="javascript"></script>
';

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php'); 