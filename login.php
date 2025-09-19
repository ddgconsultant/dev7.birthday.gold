<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$autologin_days_length = 45;
$errormessage = '';

// Get user's login method preference
$preferred_method = $account->getLoginMethodPreference();

#-------------------------------------------------------------------------------
# PREP LOGIN ATTEMPT VARIABLES
#-------------------------------------------------------------------------------
$doautologin = false;
$login_method = $_POST['login_type'] ?? $preferred_method;

if ($login_method === 'phone') {
    $username = !empty($_POST['phone']) ? preg_replace('/[^0-9]/', '', $_POST['phone']) : '';
    $logintype = 'phone';
} else {
    $username = !empty($_POST['email']) ? $_POST['email'] : 
                (!empty($_POST['email1']) ? $_POST['email1'] : 
                (!empty($_POST['email2']) ? $_POST['email2'] : ''));
    $logintype = 'any';
}

$password = !empty($_POST['password']) ? $_POST['password'] : 
            (!empty($_POST['password1']) ? $_POST['password1'] : 
            (!empty($_POST['password2']) ? $_POST['password2'] : ''));

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
      'minutes_remaining' => $minutes_remaining,
      'error_code' => 'LOGIN_LOCKOUT_ACTIVE',
      'message' => $bg_login_messages[5]
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
session_tracking('rememberme_autologin_checking', $device_id.'|'.$bgralid.'|'.(empty($bgraltoken) ? 'no_token' : 'token_present'));
session_tracking('rememberme_autologin_cookies', $_COOKIE);
if (!empty($device_id) && !empty($bgralid)  && !empty($bgraltoken)) {
  session_tracking('rememberme_autologin_attempt', [
    'device_id' => $device_id,
    'bgralid' => $bgralid,
    'bgraltoken_length' => strlen($bgraltoken),
    'cookies_present' => [
      'bgdeviceid' => !empty($_COOKIE["bgdeviceid"]),
      'bgralid' => !empty($_COOKIE["bgralid"]),
      'bgraltoken' => !empty($_COOKIE["bgraltoken"])
    ]
  ]);
  
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
  session_tracking('login-captcha-fail', array_merge($_REQUEST, [
      'error_code' => 'CAPTCHA_FAIL',
      'message' => 'Please complete the CAPTCHA correctly.'
  ]));
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
      
      // Set login method preference based on how they logged in
      if ($login_method === 'phone') {
        $account->setLoginMethodPreference('phone');
      } else {
        $account->setLoginMethodPreference('email');
      }
      
      $session->set('login_attempts', 0);
      
      // Log successful login
      session_tracking('login_success', [
          'email' => $username,
          'ip' => $_SERVER['REMOTE_ADDR'],
          'login_method' => $login_method,
          'login_type' => $logintype
      ]);






      if (isset($_POST['rememberme'])) {
        // Collect all variables needed for the rememberme function
        $variables = [
          'current_user_data' => $session->get('current_user_data'),
          'autologin_days_length' => $autologin_days_length,
          'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? $userAgent ?? '',
          'client_ip' => $client_ip,
          'logintype' => $logintype,
          'doautologin' => $doautologin,
        ];
        
        // Call the new rememberme function
        $account->rememberme($variables);
      
          } 





      // Check if 2FA is enabled for this user
      $current_user_data = $session->get('current_user_data');
      $user_id = $current_user_data['user_id'];
      
      // Get 2FA method if enabled
      $sql = 'SELECT string_value as auth_method FROM bg_user_attributes 
             WHERE user_id = :user_id 
             AND type = "2fa_method" 
             AND status = "active"';
      $stmt = $database->prepare($sql);
      $stmt->execute(['user_id' => $user_id]);
      $user_2fa = $stmt->fetch(PDO::FETCH_ASSOC);
      
      // Skip 2FA for trusted device (remember me) logins - selective 2FA will handle sensitive pages
      $is_trusted_device = $doautologin && strpos($logintype, 'rememberme') !== false;
      
      if ($user_2fa && !empty($user_2fa['auth_method']) && !$is_trusted_device) {
        // 2FA is enabled - logout user temporarily and redirect to 2FA verification
        session_tracking('2fa_verification_required-setupfailure', [
          'user_id' => $user_id,
          'method' => $user_2fa['auth_method']
        ]);
        $account->logout(); // Logout to prevent bypass
        
        // Get user contact info for 2FA
        $sql = 'SELECT email, phone_number FROM bg_users WHERE user_id = :user_id';
        $stmt = $database->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $user_contact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // For TOTP method, get the secret
        $totp_secret = '';
        if ($user_2fa['auth_method'] === 'Highly Secure') {
          $sql = 'SELECT string_value FROM bg_user_attributes 
                 WHERE user_id = :user_id 
                 AND type = "2fa_secret" 
                 AND status = "active"';
          $stmt = $database->prepare($sql);
          $stmt->execute(['user_id' => $user_id]);
          $secret_result = $stmt->fetch(PDO::FETCH_ASSOC);
          $totp_secret = $secret_result['string_value'] ?? '';
        }
        
        // Store 2FA verification data in session
        $redirect_url = $_REQUEST['goto'] ?? '/myaccount';
        foreach ($bg_secured_paths as $path) {
          if (strpos($_SERVER['HTTP_REFERER'] ?? '', $path) !== false) {
            $redirect_url = $_SERVER['HTTP_REFERER'];
            break;
          }
        }
        
        $pending_2fa_data = [
          'user_id' => $user_id,
          'method' => $user_2fa['auth_method'],
          'email' => $user_contact['email'] ?? '',
          'phone' => $user_contact['phone_number'] ?? '',
          'secret' => $totp_secret,
          'redirect_url' => $redirect_url,
          'timestamp' => time(),
          'code_sent' => false
        ];
        
        $session->set('pending_2fa', $pending_2fa_data);
        
        session_tracking('2fa_verification_required', [
          'user_id' => $user_id,
          'method' => $user_2fa['auth_method'],
          'redirect_url' => $redirect_url
        ]);
        
        header('Location: /verify-2fa');
        exit();
      }

      // Handle Remember Me functionality -- set new cookies
      session_tracking('rememberme_checkbox_detected', [
        'user_id' => $current_user_data['user_id'],
        'rememberme_posted' => isset($_POST['rememberme']) ? 'yes' : 'no',
        'post_data' => $_POST,
        'logintype' => $logintype,
        'doautologin' => $doautologin
      ]);
      
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

          session_tracking('bg_rememberme_set_success', array_merge($validatedata, $validationcodes, [
            'device_id' => $deviceid,
            'encoded_id' => $encodedId,
            'expire_dt' => date('Y-m-d H:i:s', $expiredt),
            'cookies_set' => [
              'bgralid' => $encodedId,
              'bgraltoken' => $validationcodes['validation_code'],
              'bgdeviceid' => $deviceid
            ]
          ]));
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
          'lockout_until' => date('Y-m-d H:i:s', $lockout_until),
          'error_code' => 'ACCOUNT_LOCKED_NEW',
          'message' => $bg_login_messages[5]
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
      $message_index = min($login_attempts - 1, 4);
      $errormessage = '<div class="alert alert-danger">' . $bg_login_messages[$message_index] . '</div>';
      
      // Log the error code in session tracking
      session_tracking('login_failed', [
          'email' => $username,
          'ip' => $_SERVER['REMOTE_ADDR'],
          'attempt_number' => $login_attempts,
          'error_code' => 'LOGIN_FAIL_' . $message_index,
          'message' => $bg_login_messages[$message_index]
      ]);
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

// Flush header to remove default spacing
$header_flush = true;

#include($_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

$additionalstyles = '
<style>
/* Modern Minimalist Login Styles - Clean & Modern */
* {
    box-sizing: border-box !important;
}

/* Floating Label Styles from createaccount */
.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label,
.form-floating > .form-select ~ label {
    transform: scale(0.85) translateY(-0.7rem) translateX(0.15rem);
}

/* Fix input group with floating labels */
.input-group > .form-floating {
    flex: 1 1 auto;
    width: 1%;
    min-width: 0;
}

.input-group > .form-floating > .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-group > .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
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

/* For floating label inputs specifically */
.form-floating input:-webkit-autofill,
.form-floating input:-webkit-autofill:hover,
.form-floating input:-webkit-autofill:focus,
.form-floating input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 30px white inset !important;
    -webkit-text-fill-color: inherit !important;
}

/* Card Container */
.login-container {
    width: 100%;
    max-width: 480px;
    margin: 1rem auto 2rem; /* Reduced top margin from 2rem to 1rem */
}

/* Mobile: Position below fixed header */
@media (max-width: 767px) {
    .login-container {
        margin-top: 50px; /* Reduced to minimum spacing below header */
        margin-bottom: 2rem;
    }
    
    /* Remove any default padding from main content wrapper */
    .main-content {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
    
    /* Mobile - underline style for floating labels */
    .form-floating .form-control,
    .form-control {
        border: none;
        border-bottom: 2px solid #dee2e6;
        border-radius: 0;
        background-color: transparent;
        padding-left: 0;
        padding-right: 0;
    }
    
    .form-floating .form-control:focus,
    .form-control:focus {
        border-bottom-color: #0d6efd;
        box-shadow: none;
        background-color: transparent;
    }
    
    /* Invalid state */
    .form-floating .form-control.is-invalid,
    .form-control.is-invalid {
        border-bottom-color: #dc3545;
    }
    
    /* Password toggle button matches input bottom border on mobile */
    .password-toggle {
        border: none !important;
        border-bottom: 2px solid #dee2e6 !important;
        border-radius: 0 !important;
        padding: 0.375rem 0.75rem !important;
        background-color: transparent !important;
    }
    
    /* Input groups need special handling */
    .input-group .form-control {
        border-bottom: 2px solid #dee2e6;
        border-right: none !important;
    }
    
    /* Match border color when focused */
    .input-group:focus-within .password-toggle {
        border-bottom-color: #0d6efd !important;
    }
    
    /* Floating labels adjustment for mobile */
    .form-floating > label {
        padding-left: 0;
    }
    
    /* Remove autofill background on mobile with transparent background */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px transparent inset !important;
        -webkit-text-fill-color: inherit !important;
    }
}

.login-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

/* Header Section - Minimal */
.login-header {
    text-align: center;
    padding: 2rem 1.5rem 1rem;
}

.login-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.login-header p {
    font-size: 1rem;
    color: #6c757d;
    margin: 0;
}

/* Tab Switch for Email/Phone */
.login-tabs {
    display: flex;
    background: #f1f3f5;
    border-radius: 8px;
    padding: 4px;
    margin-bottom: 1.5rem;
}

.login-tab {
    flex: 1;
    padding: 0.75rem 1rem;
    border: none;
    background: transparent;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.login-tab.active {
    background: white;
    color: var(--bs-primary);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.login-tab:hover:not(.active) {
    color: #495057;
}

.login-tab i {
    font-size: 1rem;
}

/* Login Badge */
.login-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #e8f5e8;
    color: var(--bs-primary);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.login-badge i {
    font-size: 1rem;
}

/* Form Section */
.login-body {
    padding: 0 1.5rem 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* Input Fields - Updated for floating labels */
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

/* Password Toggle Button - Clean style without blue background */
.password-toggle {
    background: white !important;
    color: #6c757d !important;
    border-color: #dee2e6 !important;
    transition: all 0.2s ease;
}

.password-toggle:hover {
    background: #f8f9fa !important;
    color: #495057 !important;
    border-color: #dee2e6 !important;
}

.password-toggle:focus {
    box-shadow: none;
    outline: none;
}

/* Match border color when input is focused */
.input-group:focus-within .password-toggle {
    border-color: #86b7fe !important;
}

/* Remember Me & Forgot Password Row */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.form-check {
    margin: 0;
}

.form-check-input {
    margin-top: 0.25rem;
    border: 2px solid #dee2e6;
    transition: all 0.2s ease;
}

.form-check-input:checked {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.form-check-label {
    font-size: 0.875rem;
    color: #495057;
    margin-left: 0.25rem;
}

.forgot-link {
    font-size: 0.875rem;
    color: var(--bs-primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}

.forgot-link:hover {
    color: #0b5ed7;
    text-decoration: underline;
}

/* Submit Button */
.btn-login {
    width: 100%;
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    background: var(--bs-primary);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.btn-login:hover:not(:disabled) {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

.btn-login:active {
    transform: translateY(0);
}

.btn-login:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.65;
}

/* Divider - Subtle */
.divider {
    margin: 2rem 0;
    text-align: center;
    position: relative;
}

.divider::before {
    content: "";
    position: absolute;
    left: 20%;
    right: 20%;
    top: 50%;
    height: 1px;
    background: #e9ecef;
}

.divider span {
    background: white;
    padding: 0 0.75rem;
    position: relative;
    color: #adb5bd;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Alternative Actions */
.alt-actions {
    text-align: center;
    font-size: 0.875rem;
    color: #6c757d;
}

.alt-actions a {
    color: var(--bs-primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}

.alt-actions a:hover {
    color: #0b5ed7;
    text-decoration: underline;
}

/* Loading State */
.btn-login.loading {
    pointer-events: none;
}

.btn-login.loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    margin: auto;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    border: 2px solid transparent;
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

.btn-login.loading span {
    opacity: 0;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* CAPTCHA Integration */
.captcha-wrapper {
    margin-bottom: 1.5rem;
}

/* Tablet & Desktop Styles */
@media (min-width: 768px) {
    .login-container {
        max-width: 480px;
        margin: 1.5rem auto 3rem; /* Reduced top margin from 3rem to 1.5rem */
    }
    
    .login-header {
        padding: 3rem 2rem 1.5rem;
    }
    
    .login-header h1 {
        font-size: 2rem;
    }
    
    .login-body {
        padding: 0 2rem 3rem;
    }
    
    /* Desktop - keep full borders for floating labels */
    .form-floating .form-control,
    .form-control {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        background-color: #fff;
        padding: 1rem 0.75rem;
    }
    
    .form-floating .form-control:focus,
    .form-control:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    /* Fix border on password toggle for desktop */
    .input-group .password-toggle {
        border: 2px solid #dee2e6 !important;
        border-left: none !important;
    }
    
    /* Keep border when input is focused */
    .input-group:focus-within .password-toggle {
        border-color: #86b7fe !important;
        border-left: none !important;
    }
}

/* Large Desktop - Enhanced Layout */
@media (min-width: 992px) {
    .login-wrapper {
        width: 100%;
        max-width: 1200px;
        display: grid;
        grid-template-columns: 1fr 500px;
        gap: 4rem;
        align-items: center;
        padding: 0 2rem;
    }
    
    /* Welcome content for desktop */
    .welcome-content {
        color: #212529;
    }
    
    .welcome-content h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }
    
    .welcome-content h2 span {
        color: var(--bs-primary);
    }
    
    .welcome-content p {
        font-size: 1.25rem;
        color: #6c757d;
        margin-bottom: 2rem;
        line-height: 1.6;
    }
    
    .feature-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .feature-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .feature-icon {
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        background: var(--bs-secondary);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-primary);
        font-size: 1.25rem;
    }
    
    .feature-text h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #212529;
        margin-bottom: 0.25rem;
    }
    
    .feature-text p {
        font-size: 0.875rem;
        color: #6c757d;
        margin: 0;
        line-height: 1.4;
    }
    
    .login-container {
        margin: 0;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
}

@media (min-width: 1200px) {
    .login-wrapper {
        gap: 6rem;
    }
    
    .welcome-content h2 {
        font-size: 3rem;
    }
}
</style>
';

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
?>

<div class="main-content">
    <!-- Desktop wrapper for side-by-side layout -->
    <div class="login-wrapper">
        <!-- Welcome content - Desktop only -->
        <div class="welcome-content d-none d-lg-block">
            <h2>Welcome back to <span>Birthday.Gold</span></h2>
            <p>Sign in to access your birthday rewards dashboard and never miss another celebration.</p>
            
            <div class="feature-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-gift"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Track Rewards</h3>
                        <p>Monitor all your birthday rewards in one place</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-bell"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Get Reminders</h3>
                        <p>Never miss a birthday reward deadline</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Secure Account</h3>
                        <p>Your data is protected with enterprise security</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Quick Access</h3>
                        <p>One-click access to all your rewards</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Login Card -->
        <div class="login-container mb-md-5">
       
            <div class="login-card">
                <!-- Header Section -->
                <div class="login-header">
                    <div class="login-badge">
                        <i class="bi bi-person-circle"></i>
                        <span>Account Login</span>
                    </div>
                    <h1>Sign In</h1>
                    <p>Enter your credentials to continue</p>
                </div>
                
                <!-- Form Section -->
                <div class="login-body">
                <?php if (!empty($transferpagedata['message'])): ?>
                        <div class="alert-container">
                            <?php echo $transferpagedata['message']; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="/login" id="loginForm">
                        <?php echo $display->inputcsrf_token(); ?>
                        
                        <?php
                        $referer = $_REQUEST['goto'] ?? $_SERVER['HTTP_REFERER'] ?? '';
                        if (!empty($referer)) {
                            foreach ($bg_secured_paths as $path) {
                                if (strpos($referer, $path) !== false) {
                                    echo '<input type="hidden" name="goto" value="' . htmlspecialchars($referer) . '">';
                                    break;
                                }
                            }
                        }
                        ?>
                        
                        <div class="login-tabs">
                            <button type="button" class="login-tab <?php echo $preferred_method === 'email' ? 'active' : ''; ?>" data-type="email">
                                <i class="bi bi-envelope"></i>
                                Email
                            </button>
                            <button type="button" class="login-tab <?php echo $preferred_method === 'phone' ? 'active' : ''; ?>" data-type="phone">
                                <i class="bi bi-phone"></i>
                                Phone
                            </button>
                        </div>
                        
                        <input type="hidden" name="login_type" id="login_type" value="<?php echo $preferred_method; ?>">
                        
                        <!-- Email Input -->
                        <div class="form-group" id="email-group" style="<?php echo $preferred_method === 'email' ? '' : 'display: none;'; ?>">
                            <div class="form-floating">
                                <input 
                                    type="text" 
                                    name="email" 
                                    id="email" 
                                    class="form-control" 
                                    placeholder="Email or Username" 
                                    autocomplete="username"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                    <?php echo $preferred_method === 'email' ? 'required' : ''; ?>
                                    <?php echo $preferred_method === 'email' ? 'autofocus' : ''; ?>
                                >
                                <label for="email">Email or Username</label>
                            </div>
                        </div>
                        
                        <!-- Phone Input -->
                        <div class="form-group" id="phone-group" style="<?php echo $preferred_method === 'phone' ? '' : 'display: none;'; ?>">
                            <div class="form-floating">
                                <input 
                                    type="tel" 
                                    name="phone" 
                                    id="phone" 
                                    class="form-control" 
                                    placeholder="Phone Number" 
                                    autocomplete="tel"
                                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                    <?php echo $preferred_method === 'phone' ? 'required' : ''; ?>
                                    <?php echo $preferred_method === 'phone' ? 'autofocus' : ''; ?>
                                >
                                <label for="phone">Phone Number</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-group">
                                <div class="form-floating flex-grow-1">
                                    <input 
                                        type="password" 
                                        name="password" 
                                        id="password" 
                                        class="form-control" 
                                        placeholder="Password" 
                                        autocomplete="current-password"
                                        required
                                    >
                                    <label for="password">Password</label>
                                </div>
                                <button type="button" class="btn btn-outline-secondary password-toggle" id="togglePassword">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($show_captcha): ?>
                            <div class="captcha-wrapper">
                                <?php echo $app->generateCaptcha('medium'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-options">
                            <div class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    id="rememberme" 
                                    name="rememberme"
                                    <?php if (isset($_COOKIE['bgdeviceid'])) echo 'checked'; ?>
                                >
                                <label class="form-check-label" for="rememberme">
                                    Remember me
                                </label>
                            </div>
                            <a href="/forgot" class="forgot-link">Forgot Password?</a>
                        </div>
                        
                        <button type="submit" class="btn-login" id="loginBtn">
                            <span>Sign In</span>
                        </button>
                    </form>
                    
                    <!-- Divider -->
                    <div class="divider">
                        <span>or</span>
                    </div>
                    
                    <!-- Alternative Actions -->
                    <div class="alt-actions">
                        New to Birthday.Gold? <a href="/signup">Create account</a>
                        <br>
                        Have a gift certificate? <a href="/redeem">Redeem it</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
$footerattribute['postfooter'] = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.getElementById("loginForm");
    const loginBtn = document.getElementById("loginBtn");
    const emailInput = document.getElementById("email");
    const phoneInput = document.getElementById("phone");
    const passwordInput = document.getElementById("password");
    const togglePasswordBtn = document.getElementById("togglePassword");
    const loginTabs = document.querySelectorAll(".login-tab");
    const loginTypeInput = document.getElementById("login_type");
    const emailGroup = document.getElementById("email-group");
    const phoneGroup = document.getElementById("phone-group");
    
    // Tab switching
    loginTabs.forEach(tab => {
        tab.addEventListener("click", function() {
            const type = this.dataset.type;
            
            // Update active tab
            loginTabs.forEach(t => t.classList.remove("active"));
            this.classList.add("active");
            
            // Update hidden input
            loginTypeInput.value = type;
            
            // Show/hide appropriate input
            if (type === "phone") {
                emailGroup.style.display = "none";
                phoneGroup.style.display = "block";
                emailInput.removeAttribute("required");
                phoneInput.setAttribute("required", "");
                phoneInput.focus();
            } else {
                emailGroup.style.display = "block";
                phoneGroup.style.display = "none";
                phoneInput.removeAttribute("required");
                emailInput.setAttribute("required", "");
                emailInput.focus();
            }
        });
    });
    
    // Password visibility toggle
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener("click", function() {
            const icon = this.querySelector("i");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("bi-eye-fill");
                icon.classList.add("bi-eye-slash-fill");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("bi-eye-slash-fill");
                icon.classList.add("bi-eye-fill");
            }
        });
    }
    
    // Phone formatting
    if (phoneInput) {
        phoneInput.addEventListener("input", function(e) {
            let value = e.target.value.replace(/\D/g, "");
            let formattedValue = "";
            
            if (value.length > 0) {
                if (value.length <= 3) {
                    formattedValue = `(${value}`;
                } else if (value.length <= 6) {
                    formattedValue = `(${value.slice(0, 3)}) ${value.slice(3)}`;
                } else {
                    formattedValue = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
                }
            }
            
            e.target.value = formattedValue;
        });
    }
    
    // Form submission handling - just prevent double submit
    if (loginForm) {
        loginForm.addEventListener("submit", function(e) {
            // Add loading state and disable button to prevent double submit
            loginBtn.classList.add("loading");
            loginBtn.disabled = true;
            
            // Let the form submit normally - no preventDefault!
        });
    }
    
    // Auto-focus email field
    if (emailInput && !emailInput.value) {
        emailInput.focus();
    }
});
</script>
';

#$display_footertype='mobilenonemin';
echo $display->submitbuttoncolorjs('loginForm');
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
