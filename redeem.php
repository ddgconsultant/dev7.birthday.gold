<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



$errormessage = '';
#-------------------------------------------------------------------------------
# PROCESS LOGIN ATTEMPT
#-------------------------------------------------------------------------------

if ($app->formposted()) {
  $username = ($_POST['code'] ?? '');
  #$password = (isset($_POST['password']) ? $_POST['password'] : '');
  $password = $sitesettings['app']['APP_GIFTCODEPASS'];
  if (!empty($username) && !empty($password)) {
    $response = $account->login($username, $password, 'giftcode');

    if ($response) {
      $session->set('generateGiftCertificateCode',  $username);
      $session->set('generateGiftCertificateCode_user_id',  $response);

      $errormessage = '<div class="alert alert-success">Gift Certificate successfully redeemed.</div>';
      $transferpagedata['message'] = $errormessage;
      $transferpagedata['url'] = '/register-giftcertificate';
      $transferpagedata = $system->endpostpage($transferpagedata);
      exit;
    } else {
      $errormessage = '<div class="alert alert-danger">Sorry: Unable to redeem that code.</div>';
      $transferpagedata['message'] = $errormessage;
      $transferpagedata['url'] = '/redeem';
      $transferpagedata = $system->endpostpage($transferpagedata);
      exit;
    }
  }
}






#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

?>



<section class="h-100 gradient-form main-content">

  <div class="container-xl my-lg-5">
    <div class="row d-flex justify-content-center align-items-center h-100">

      <div class="col-xl-10">
        <?= $display->formaterrormessage($transferpagedata['message']); ?>
        <div class="card rounded-3 text-black">
          <div class="row g-0">
            <div class="col-lg-6">
              <div class="card-body p-md-5 mx-md-4">

                <div class="text-center">
                  <h1 class="mt-1 pb-1">Redeem</h1>
                  <h2 class="mt-1 mb-5 pb-1">Gift Certificate</h2>
                </div>

                <form method="post"  id="mainform" action="/redeem">
                  <?PHP echo $display->inputcsrf_token(); ?>
                  <!--    <p class="font-weight-bold">Login to your account</p> -->

                  <div class="form-outline mb-4">
                    <input type="text" name="code" id="code" class="form-control" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="nope" autocomplete="off" />
                    <label class="form-label" for="code">Enter Your Gift Certificate Code</label>
                  </div>

                  <div class="text-center pt-1 mb-5 pb-1">
                    <div>
                      <button class="btn btn-primary fa-lg mb-3 py-2 px-5"  id="mainsubmit"  type="submit">Redeem</button>
                    </div>
                </form>
              </div>


              <div class="d-flex align-items-center justify-content-center pb-2">
                <a class="text-black" href="/login">I have an account <span class="btn btn-sm btn-outline-primary" href="/login">Login</span></a>
              </div>
              <div class="d-flex align-items-center justify-content-center pb-2">
                <a class="text-black" href="/signup">Don't have an account? <span class="btn btn-sm btn-outline-primary" href="/signup">Sign Up</span></a>
              </div>
            </div>
          </div>

          <div class="col-lg-6 d-flex align-items-center flex-column bg-success">
            <div class="flex-grow-1 d-flex align-items-center">
              <div class="text-white px-3 py-4 p-md-5 mx-md-4 text-center">
                <h4 class="mb-4 text-white">Someone Loves You!</h4>
                <p class="mb-0 fw-bold text-dark">At <span class="birthdaygold-white">birthday.gold</span>, we do too! Let's get you started with your own account and hurry and get you as many freebies we can to celebrate your special day.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>



<?php
$footerattribute['postfooter'] = "
<script>
document.addEventListener('DOMContentLoaded', function() {
// Assuming your input has an id of 'code'
const inputElement = document.getElementById('code');

if (inputElement) {
inputElement.addEventListener('input', function() {
formatInput(this);
});
}
});

function formatInput(input) {
let value = input.value.replace(/[^A-Za-z0-9-]/g, '').toUpperCase(); // Allow dashes and alphanumeric characters
let formatted = '';
let j = 0; // Counter for the actual characters (excluding dashes)

for (let i = 0; i < value.length; i++) {
// Automatically insert a dash after every 4th character (but not at the start)
if (j === 4 || j === 8 || j === 12) {
formatted += '-';
j = 0;
}

// If the user manually enters a dash, skip it and reset the counter
if (value[i] === '-') {
j = 0;
continue;
}

formatted += value[i];
j++;
}

input.value = formatted;
}

</script>
";


echo $display->submitbuttoncolorjs();
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
