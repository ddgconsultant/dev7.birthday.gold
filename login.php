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

$additionalstyles = '
<style>
/* Modern Minimalist Login Styles - Clean & Modern */
* {
    box-sizing: border-box !important;
}

/* Main Content Container */


/* Card Container */
.login-container {
    width: 100%;
    max-width: 480px;
    margin: 2rem auto;
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

/* Input Fields */
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
    color: #adb5bd;
}

/* Password Input Group */
.password-input-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0.25rem;
    font-size: 1.125rem;
    transition: color 0.2s ease;
}

.password-toggle:hover {
    color: #495057;
}

.password-input {
    padding-right: 3rem;
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

/* Invalid input state */
.form-control.is-invalid {
    border-color: #dc3545;
}

.form-control.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

/* CAPTCHA Integration */
.captcha-wrapper {
    margin-bottom: 1.5rem;
}

/* CAPTCHA Styles */
.captcha-container {
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
}

.captcha-options {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 0.5rem !important;
    margin-top: 1rem !important;
}

.captcha-option {
    width: 100% !important;
    height: auto !important;
    aspect-ratio: 1 !important;
    padding: 0.5rem !important;
    cursor: pointer !important;
    border: 2px solid #dee2e6 !important;
    border-radius: 6px !important;
    transition: all 0.2s ease !important;
    background: white !important;
}

.captcha-option:hover {
    border-color: var(--bs-primary) !important;
    transform: translateY(-2px) !important;
}

.captcha-option.selected {
    border-color: var(--bs-primary) !important;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1) !important;
}

.captcha-option img,
.captcha-option i {
    width: 100% !important;
    height: 100% !important;
    object-fit: contain !important;
    font-size: 1.5rem !important;
}


/* Tablet & Desktop Styles */
@media (min-width: 768px) {

    
    .login-container {
        max-width: 480px;
        margin: 3rem auto;
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
            <h2>Welcome back to <span>Birthday Gold</span></h2>
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
        <div class="login-container">
       
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
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email or Username</label>
                            <input 
                                type="text" 
                                name="email" 
                                id="email" 
                                class="form-control" 
                                placeholder="name@example.com" 
                                autocomplete="username"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                                autofocus
                            >
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <div class="password-input-wrapper">
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password" 
                                    class="form-control password-input" 
                                    placeholder="Enter your password" 
                                    autocomplete="current-password"
                                    required
                                >
                                <button type="button" class="password-toggle" id="togglePassword">
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
                        New to Birthday Gold? <a href="/signup">Create account</a>
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
    const passwordInput = document.getElementById("password");
    const togglePasswordBtn = document.getElementById("togglePassword");
    
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

echo $display->submitbuttoncolorjs('loginForm');
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
