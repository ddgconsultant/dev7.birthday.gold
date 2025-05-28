<?php

$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');




$errormessage = '';
#-------------------------------------------------------------------------------
# PROCESS POST ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
  $email = (isset($_POST['email']) ? $_POST['email'] : '');
  $sendcount = 1;
  $response = $account->getuserdata($email, 'email');
  if (!empty($response['user_id'])) {
    $fullname = $response['first_name'];
    $message['toemail'] = $email;
    $message['fullname'] = $fullname;
    # $link= $appclass->getshortcode( $website['fullurl'].'/validate-account?t='.sha1($email) );
    #$message['validatelink']=$link['shorturl'];
    $validatedata['rawdata'] = $email;
    $validatedata['user_id'] = $response['user_id'];
    $validatedata['sendcount'] = $sendcount;
    $validatedata['type'] = 'forgotpassword';
    $validationcodes = $app->getvalidationcodes($validatedata);

    $link = $website['fullurl'] . '/resetpassword?t=' . $validationcodes['long'];
    $message['validatelink'] = $link;
    $message['validationcode'] = $local_validationcode = $validationcodes['mini'];
    #print_r($message);


    $mail->sendPasswordResetEmail($message);
    # header('location: /login');






    #-------------------------------------------------------------------------------
    # DISPLAY PAGE
    #-------------------------------------------------------------------------------   
    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');
    echo '

<div class="container py-6 main-content">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg-8">
<i class="bi bi-envelope-check display-4 text-primary""></i>
<h1 class="display-4">Password Reset Email Sent</h1>
<p class="">We sent an email to:  ' . $email . '</p>
<p>Click the link in the email to reset your password.</p>
<p class="mb-4 fw-bold">TIP: Don\'t forget to check your spam/junk folders.</p>
<a class="btn btn-primary py-3 px-5" href="/login">Go to Login</a>
</div>
</div>
</div>
</div>

';


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
exit;
  } else {
    $errormessage = '<div class="bg-danger p-2 mb-4 text-white">Hmmm: Unable to locate an account with that information</div>';
  }
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<section class="h-100 gradient-form main-content">
  <div class="container py-5 h-100">

    <div class="row d-flex justify-content-center align-items-center h-100">

      <div class="col-xl-10">
        <?= $errormessage; ?>
        <div class="card rounded-3 text-black">
          <div class="row g-0">
            <div class="col-lg-6">
              <div class="card-body p-md-5 mx-md-4">

                <div class="text-center">
                  <h1 class="mt-1 mb-5 pb-1">Forgot Password</h1>
                </div>

                <form method="post" id="mainform" action="/forgot">
                  <?PHP echo $display->inputcsrf_token(); ?>
                  <p class="fw-bolder ">Provide your account email. We will send you a link to change your password.</p>

                  <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control">
                  </div>

                  <div class="text-center pt-1 mb-5 pb-1">
                    <button class="btn btn-primary fa-lg mb-3" id="mainsubmit" type="submit">Submit</button>
               
              </div>
              </form>
              <div class="d-flex align-items-center justify-content-center pb-4">
                <a  class="btn btn-outline-primary" href="/login">Back to Login</a>
              </div>

            </div>
          </div>

          <div class="col-lg-6 d-flex align-items-center flex-column bg-secondary">

            <div class="flex-grow-1 d-flex align-items-center">
              <div class="text-white px-3 py-4 p-md-5 mx-md-4 text-center">
                <h4 class="mb-4 text-white">Don't worry, we can help you get back into your account.</h4>
                <p class="mb-0 text-black">If you don't remember what email you used, please use our contact form: <a href="/contact" class="text-white">HERE</a></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>  </div>
</section>


<?PHP
echo $display->submitbuttoncolorjs();
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
