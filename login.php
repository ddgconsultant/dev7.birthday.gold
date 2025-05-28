<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$autologin_days_length = 45;
$errormessage = '';


#-------------------------------------------------------------------------------
# PREP LOGIN ATTEMPT VARIABLES
#-------------------------------------------------------------------------------
$doautologin = false;
$username = !empty($_POST['email']) ? $_POST['email'] : 
            (!empty($_POST['email1']) ? $_POST['email1'] : 
            (!empty($_POST['email2']) ? $_POST['email2'] : ''));

$password = !empty($_POST['password']) ? $_POST['password'] : 
            (!empty($_POST['password1']) ? $_POST['password1'] : 
            (!empty($_POST['password2']) ? $_POST['password2'] : ''));

$logintype = 'any';

$login_attempts = $session->get('login_attempts', 0, true);
$show_captcha = $login_attempts >= 3;

$lockout_until = $session->get('login_lockout_until', 0, true);
$current_time = time();





#-------------------------------------------------------------------------------
// Modify the lockout check at the start
if ($lockout_until > $current_time) {
  $minutes_remaining = ceil(($lockout_until - $current_time) / 60);
  $errormessage = str_replace('{MINUTES}', $minutes_remaining, $bg_login_messages[5]);
  $errormessage = '<div class="alert alert-danger">' . $errormessage . '</div>';
  
  session_tracking('login-lockout-attempt', [
      'email' => $username,
      'ip' => $_SERVER['REMOTE_ADDR'],
      'minutes_remaining' => $minutes_remaining
  ]);
  
  $transferpagedata['message'] = $errormessage;
  $transferpagedata['url'] = '/account-lockout';
  $transferpagedata = $system->endpostpage($transferpagedata);
  exit();
}



#-------------------------------------------------------------------------------
# PROCESS REMEMBER ME ATTEMPT  (caution -- they could be deleted/fake tokens)
#-------------------------------------------------------------------------------
$device_id = $_COOKIE["bgdeviceid"] ?? '';
$bgralid = $_COOKIE["bgralid"] ?? '';
$bgraltoken = $_COOKIE["bgraltoken"] ?? '';
if (!empty($device_id) && !empty($bgralid)  && !empty($bgraltoken)) {
  $logintype = 'rememberme||' . $device_id;
  $username = $bgralid;
  $password = $bgraltoken;
  $doautologin = true;
}



#-------------------------------------------------------------------------------
# PROCESS FORM POSTED LOGIN ATTEMPT
#-------------------------------------------------------------------------------
if ((!empty($username) && !empty($password)) || (strpos($logintype, 'rememberme') !== false)) {




// Before login attempt processing
if ($show_captcha && !$app->validateCaptcha()) {
  $session->set('login_attempts', $login_attempts + 1);
  $errormessage = '<div class="alert alert-danger">Please complete the CAPTCHA correctly.</div>';
  session_tracking('login-captcha-fail', $_REQUEST);
  // Force return here to prevent login attempt
  $transferpagedata['message'] = $errormessage;
  $transferpagedata['url'] = '/login';
  $transferpagedata = $system->endpostpage($transferpagedata);
  exit();
}


  // Try logging in
  $response = $account->login($username, $password, $logintype, true);


  // see if we were actually successful
  if ($app->formposted() || $doautologin) {
    if ($response) {  // TRUE LOGIN - SUCCESSFUL


      
      $session->set('login_attempts', 0);

      // Handle Remember Me functionality -- set new cookies
      if (isset($_POST['rememberme'])) {
        $current_user_data = $session->get('current_user_data');
        $userId =   $current_user_data['user_id'];
        $encodedId = $qik->encodeId($userId);
        $deviceid = $app->deviceid();


        $validatedata = [
          'rawdata' => $current_user_data['email'],
          'user_id' => $userId,
          'validation_rawdata' => $encodedId,
          'device_id' => $deviceid,
          'type' => 'bgrememberme_autologin',
          'invalidate_previouscodes' => true,
          'status' => 'cookie',
          'updatestatus' => 'cookie',
          'expireminutes' => $autologin_days_length * 24 * 60 // $autologin_days_length in minutes
        ];

        $validationcodes = $app->getvalidationcodes($validatedata);
        if (!empty($validationcodes)) {
          $validationToken = $validationcodes['long'] ?? '';

          $expiredt = (time() + ($autologin_days_length * 24 * 60 * 60));
          setcookie('bgralid', $encodedId, $expiredt, "/"); // $autologin_days_length in seconds
          setcookie('bgraltoken', $validationToken, $expiredt, "/"); // $autologin_days_length  in seconds
          setcookie('bgdeviceid', $deviceid, time() + (365 * 24 * 60 * 60), "/"); // $autologin_days_length  in seconds


          // Store user attributes
          $description = [
            'agent' => $_SERVER['HTTP_USER_AGENT'] ?? $userAgent ?? '',
            'remember_me_device' => $deviceid,
            'bgralid' => $encodedId,
            'expire_ts' =>      $expiredt,
            'expire_dt' =>      date('Y-m-d H:i:s', $expiredt),
            'validation_data' => $validatedata,
            'validation_codes' => $validationcodes,
            'client_ip' => $client_ip,
          ];

          $input = [
            'type' => 'bg_rememberme_set',
            'name' => $deviceid,
            'status' => 'A',
            'description' => json_encode($description),
            'end_dt' => date('Y-m-d H:i:s', $expiredt),
          ];

          $account->setUserAttribute($userId, $input);

          session_tracking('bg_rememberme_set', array_merge($validatedata, $validationcodes));
        }
      }


      // Handle GOTO Redirect
      $referer = $_REQUEST['goto'] ?? $_SERVER['HTTP_REFERER'] ?? '';

      foreach ($bg_secured_paths as $path) {
        if (strpos($referer, $path) !== false) {
          session_tracking('allowing_login_redirect', $referer);
          header('Location: ' . $referer);
          exit();
        }
      }


      // Successful login redirect      
      header('Location: /myaccount');
      exit();
    } else {


// After failed login attempt
if (!$response) {
  $login_attempts = $session->get('login_attempts', 0, true) + 1;
  $session->set('login_attempts', $login_attempts);
  
  // Implement lockout after max attempts
  if ($login_attempts >= $bg_account_security['max_attempts']) {
      $lockout_until = time() + ($bg_account_security['lockout_minutes'] * 60);
      $session->set('login_lockout_until', $lockout_until);
      $session->set('login_attempts', 0); // Reset counter
      
      session_tracking('account_locked', [
          'email' => $username,
          'ip' => $_SERVER['REMOTE_ADDR'],
          'lockout_until' => date('Y-m-d H:i:s', $lockout_until)
      ]);
      
      $minutes_remaining = $bg_account_security['lockout_minutes'];
      $errormessage = str_replace('{MINUTES}', $minutes_remaining, $bg_login_messages[5]);
      $errormessage = '<div class="alert alert-danger">' . $errormessage . '</div>';

          // Redirect to lockout page
   
          $transferpagedata['message'] = $errormessage;
          $transferpagedata['url'] = '/account-lockout';
          $transferpagedata = $system->endpostpage($transferpagedata);
    exit();


  } else {
      $errormessage = '<div class="alert alert-danger">' . $bg_login_messages[min($login_attempts - 1, 4)] . '</div>';
  }
}

       $transferpagedata['message'] = $errormessage;
      $transferpagedata['url'] = '/login';
      $transferpagedata = $system->endpostpage($transferpagedata);
    }
  } elseif ($app->validateAPItoken()) {
    // If this is an API call, return a JSON response
    $apiResponse = ["success" => $response, "message" => $response ? "Logged in successfully." : "Nope: Unable to log you in with that information."];
    header('Content-Type: application/json');
    echo json_encode($apiResponse);
    exit;
  }
}


#include($_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

$additionalstyles.="
<style>
  @media (max-width: 767.98px) {
    #card-email::placeholder {
      content: 'Username or Email';
    }

    #input_password::placeholder {
      content: 'Password';
    }
    .form-label {
      display: none;
    }
  }
</style>";

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
?>


<section class="h-100 gradient-form main-content py-0 my-0">

  <div class="container-xl my-lg-5 my-sm-0">
    <div class="row d-flex justify-content-center align-items-center h-100 py-0 my-0">

      <div class="col-xl-10 py-0 my-0">
        <?PHP echo $transferpagedata['message']; ?>
        <div class="card rounded-3 text-black py-0 my-0">
          <div class="row g-0 h-100">
            <div class="col-md-5 text-center bg-card-gradient  bg-info">
              <div class="position-relative p-4 py-sm-0 pt-md-5 pb-md-7" data-bs-theme="light">

                <div class="z-1 position-relative">
                  <h2 class="my-sm-0">Login</h2>
                  <a class="link-light mb-4 font-sans-serif fs-5 d-inline-block fw-bolder d-none d-sm-block" href="/index">birthday.gold</a>
                  <p class="opacity-75 text-white mb-0 fw-bold text-dark d-none d-sm-block">
                    We believe every birthday and special milestone deserves to be commemorated and cherished. That's why we make it easy to enjoy receiving all the freebie promos from companies wanting to celebrate you.
                  </p>

                </div>
              </div>
              <div class="mt-lg-3 mb-4 mt-md-4 mb-md-5" data-bs-theme="light">
                <p class="text-white">Don't have an account?<br><a class="text-decoration-underline link-light" href="/signup">Sign Up!</a></p>
                <p class="mb-0 mt-4 mt-md-5 fs-10 fw-semi-bold text-white opacity-75">
                  Read our <a class="text-decoration-underline text-white" href="/terms">terms</a> and <a class="text-decoration-underline text-white" href="/legal">conditions </a></p>
              </div>
            </div>
            <div class="col-md-7 d-flex flex-center">
              <div class="p-4 p-md-5 flex-grow-1">
                <div class="row flex-between-center">
                  <div class="col-auto">
                    <h3>Account Login</h3>
                  </div>
                </div>
   
                
                <form method="post" id="mainform" action="/login">
  <?PHP
  echo $display->inputcsrf_token();

  $referer = $_REQUEST['goto'] ?? $_SERVER['HTTP_REFERER'] ?? '';
  if (!empty($referer)) {
    foreach ($bg_secured_paths as $path) {
      if (strpos($referer, $path) !== false) {
        echo '<input type="hidden" name="goto" value="' . $referer . '">';
        break;
      }
    }
  } 
  ?>

  <div class="mb-3">
    <label class="form-label d-none d-md-block" for="card-email">Username or Email</label>
    <input type="text" name="email1" id="card-email" class="form-control d-md-none" placeholder="Username or Email">
    <input type="text" name="email2" id="card-email-md" class="form-control d-none d-md-block" >
  </div>
  <div class="mb-3">
    <div class="d-flex justify-content-between">
      <label class="form-label d-none d-md-block" for="input_password">Password</label>
    </div>
    <div class="input-group">
    <input type="password" id="input_password" name="password1"  class="form-control d-md-none" placeholder="Password">
    <input type="password" id="input_password2" name="password2"  class="form-control d-none d-md-block">
    <button class="btn btn-outline-secondary custom-button"  id="togglePassword" type="button"><i class="field-icon toggle-password bi bi-eye-fill"></i></button>
  </div>
  </div>


<?php if ($show_captcha): ?>
    <div class="mb-3">
        <?php echo $app->generateCaptcha(); ?>
    </div>
<?php endif; ?>


  <div class="row">
    <div class="col-auto">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="card-checkbox" name="rememberme" <?php if (isset($_COOKIE['bgdeviceid'])) echo 'checked="checked"'; ?>>
            <label class="form-check-label mb-0" for="card-checkbox">Remember me</label>
        </div>
    </div>
    <div class="col text-end">
        <a class="fs-10" href="/forgot">Forgot Password?</a>
    </div>
</div>

  <div class="mb-3">
    <button class="btn btn-primary btn-lg mt-3" type="submit" id="mainsubmit" name="submit">Log in</button>
  </div>
</form>


              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ===============================================-->
  <!--    End of Main Content-->
  <!-- ===============================================-->
</section>


<?PHP
echo '
<script>

let passwordInputs = [document.getElementById("input_password"), document.getElementById("input_password2")];
let togglePasswordButton = document.getElementById("togglePassword");
let togglePasswordIcon = togglePasswordButton.querySelector(".toggle-password");

// Function to toggle password visibility
function togglePasswordVisibility() {
    passwordInputs.forEach(input => {
        if (input.type === "password") {
            input.type = "text";
            togglePasswordIcon.classList.remove("bi-eye-fill");
            togglePasswordIcon.classList.add("bi-eye-slash-fill");
        } else {
            input.type = "password";
            togglePasswordIcon.classList.remove("bi-eye-slash-fill");
            togglePasswordIcon.classList.add("bi-eye-fill");
        }
    });
}

// Attach the toggle function to the button click event
togglePasswordButton.addEventListener("click", togglePasswordVisibility);

</script>';


echo $display->submitbuttoncolorjs('mainform');
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
