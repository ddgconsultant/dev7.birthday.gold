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
  $transferpagedata['url'] = '/login_v2';
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
      $transferpagedata['url'] = '/login_v2';
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

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

$additionalstyles = '
<style>
/* Floating Label Styles */
.floating-label-group {
    position: relative;
    margin-bottom: 1.5rem;
}

.floating-input {
    background: transparent !important;
    border: none;
    border-bottom: 2px solid #e9ecef;
    border-radius: 0;
    padding: 1.5rem 0.75rem 0.5rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    transition: all 0.3s ease;
    width: 100%;
    min-height: 44px; /* Touch target */
    caret-color: #212529;
}

.floating-input:focus {
    outline: none;
    border: 1px solid #e9ecef;
    border-bottom-color: var(--bs-primary);
    border-radius: 8px 8px 0 0;
    box-shadow: none;
    background: transparent !important;
}

.floating-input.is-invalid {
    border-bottom-color: #dc3545;
}

/* Remove autofill styling - most aggressive approach */
.floating-input:-webkit-autofill,
.floating-input:-webkit-autofill:hover, 
.floating-input:-webkit-autofill:focus,
.floating-input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
    -webkit-text-fill-color: #212529 !important;
    background-color: transparent !important;
    background-image: none !important;
    transition: background-color 600000s ease-in-out 0s !important;
    -webkit-transition: background-color 600000s ease-in-out 0s !important;
    transition-delay: 600000s !important;
}

/* Additional autofill removal */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
    -webkit-text-fill-color: #212529 !important;
    background: transparent !important;
    transition: background-color 600000s ease-in-out 0s !important;
    -webkit-transition: background-color 600000s ease-in-out 0s !important;
    transition-delay: 600000s !important;
}

/* Force transparency for all autofill states */
input[data-autocompleted],
input:-internal-autofill-selected {
    background-color: transparent !important;
    background-image: none !important;
}

/* Desktop: Traditional form field look */
@media (min-width: 992px) {
    .floating-input {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 2rem 1rem 0.375rem 1rem;
        background: white !important;
        transition: all 0.2s ease;
    }
    
    .floating-input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }
    
    /* Desktop: Placeholder at normal position, form value much lower */
    .floating-input::placeholder {
        transform: translateY(2.5rem);
        opacity: 0.6;
        transition: all 0.3s ease;
        line-height: 1;
    }
    
    .floating-input:focus::placeholder {
        opacity: 0;
    }
}

.floating-label {
    position: absolute;
    left: 0.75rem;
    top: 1.5rem;
    color: #6c757d;
    font-size: 1rem;
    transition: all 0.3s ease;
    pointer-events: none;
    transform-origin: left top;
}

/* Float label when input is focused or has content */
.floating-input:focus + .floating-label,
.floating-input:not(:placeholder-shown) + .floating-label {
    transform: translateY(-1.25rem) scale(0.85);
    color: var(--bs-primary);
}

.floating-input:focus.is-invalid + .floating-label,
.floating-input:not(:placeholder-shown).is-invalid + .floating-label {
    color: #dc3545;
}

/* Desktop: Adjust label positioning for traditional form fields */
@media (min-width: 992px) {
    .floating-label {
        left: 1rem;
        top: 1.125rem;
    }
    
    .floating-input:focus + .floating-label,
    .floating-input:not(:placeholder-shown) + .floating-label {
        transform: translateY(-1.1rem) scale(0.85);
    }
}

/* Password Input Wrapper for floating labels */
.password-floating-wrapper {
    position: relative;
}

.password-floating-wrapper .floating-input {
    padding-right: 3rem;
}

.password-toggle {
    position: absolute;
    right: 0.75rem;
    top: 1.25rem;
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0.25rem;
    font-size: 1.125rem;
    transition: color 0.3s ease;
    z-index: 2;
}

.password-toggle:hover {
    color: #495057;
}

/* Desktop: Adjust password toggle positioning */
@media (min-width: 992px) {
    .password-toggle {
        right: 1rem;
        top: 1rem;
    }
}

/* Checkbox Styling */
.checkbox-group {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    min-height: 44px; /* Touch target */
}

.custom-checkbox {
    width: 18px;
    height: 18px;
    margin: 0;
    margin-top: 0.125rem; /* Align with text baseline */
    flex-shrink: 0;
}

.checkbox-label {
    flex: 1;
    margin: 0;
    cursor: pointer;
    line-height: 1.5;
    font-size: 0.875rem;
    color: #495057;
}

/* Form Labels (for non-floating fields) */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

/* Form Groups */
.form-group {
    margin-bottom: 1.5rem;
}

/* Error States */
.invalid-feedback {
    display: none;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

.floating-input.is-invalid ~ .invalid-feedback,
.custom-checkbox.is-invalid ~ .invalid-feedback,
.invalid-feedback.d-block {
    display: block;
}

/* Focus and Hover States */
.floating-input:hover:not(:focus) {
    border-bottom-color: #adb5bd;
}

.custom-checkbox:hover {
    cursor: pointer;
}

/* Accessibility */
.floating-input:focus-visible,
.custom-checkbox:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
}

/* Animation for better UX */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.invalid-feedback {
    animation: fadeInUp 0.3s ease;
}

/* Mobile Optimizations */
@media (max-width: 576px) {
    .floating-input {
        font-size: 16px; /* Prevent zoom on iOS */
    }
}

/* Main Content Container */
.main-content {
    max-width: 480px;
    margin: 2rem auto;
    padding: 0 1rem;
}

/* Card Container */
.login-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

/* Header Section */
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

/* Form Options Row */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
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
    min-height: 44px;
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

/* Divider */
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

/* CAPTCHA Styles */
.captcha-wrapper {
    margin-bottom: 1.5rem;
}

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
    .main-content {
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
    .main-content {
        max-width: 1200px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .login-wrapper {
        width: 100%;
        max-width: 1200px;
        display: grid;
        grid-template-columns: 1fr 500px;
        gap: 4rem;
        align-items: center;
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
        background: #f8f9fa;
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
                <span>Floating Labels Demo</span>
            </div>
            <h1>Sign In</h1>
            <p>Experience floating labels in action</p>
        </div>
        
        <!-- Form Section -->
        <div class="login-body">
            <?php if (!empty($transferpagedata['message'])): ?>
                <div class="alert-container">
                    <?php echo $transferpagedata['message']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/login_v2" id="loginForm">
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
                
                <!-- Email with Floating Label -->
                <div class="floating-label-group">
                    <input 
                        type="text" 
                        name="email" 
                        id="email" 
                        class="floating-input" 
                        placeholder=" "
                        autocomplete="username"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autofocus
                    >
                    <label for="email" class="floating-label">Email or Username</label>
                </div>
                
                <!-- Password with Floating Label -->
                <div class="floating-label-group">
                    <div class="password-floating-wrapper">
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="floating-input" 
                            placeholder=" "
                            autocomplete="current-password"
                            required
                        >
                        <label for="password" class="floating-label">Password</label>
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
                    <div class="checkbox-group">
                        <input 
                            class="custom-checkbox" 
                            type="checkbox" 
                            id="rememberme" 
                            name="rememberme"
                            <?php if (isset($_COOKIE['bgdeviceid'])) echo 'checked'; ?>
                        >
                        <label class="checkbox-label" for="rememberme">
                            Remember me
                        </label>
                    </div>
                    <a href="/forgot_v2" class="forgot-link">Forgot Password?</a>
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
    
    // Form submission handling
    if (loginForm) {
        loginForm.addEventListener("submit", function(e) {
            // Add loading state and disable button to prevent double submit
            loginBtn.classList.add("loading");
            loginBtn.disabled = true;
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