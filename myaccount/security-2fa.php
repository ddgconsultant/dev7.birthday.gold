<?php
$addClasses[] = 'twofactorauth';
 // See what we have before
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check again:
#breakpoint($_SESSION, false);
#exit;





#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$current_user_id = $current_user_data['user_id'];
$error_message = '';
$success_message = '';
$show_setup_form = false;
$show_change_form = false;
$show_qr = false;
$qr_code = '';

#$secret = $account->generateTOTPSecret();
#breakpoint($secret);

// Get current 2FA configuration if exists
$sql = 'SELECT string_value as auth_method, create_dt 
       FROM bg_user_attributes 
       WHERE user_id = :user_id 
       AND type = \'2fa_method\' 
       AND status = \'active\'';
$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $current_user_id]);
$current_2fa = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user contact info for verification methods
$sql = 'SELECT email, phone_number FROM bg_users WHERE user_id = :user_id';
$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $current_user_id]);
$user_contact = $stmt->fetch(PDO::FETCH_ASSOC);

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
   $action = $_POST['action'] ?? '';
   $transferpage = [];

   try {
       switch ($action) {
           case 'setup':
           case 'change':
               $method = $_POST['method'] ?? '';
               if (!in_array($method, ['Secure', 'Highly Secure'])) {
                   throw new Exception('Invalid authentication method selected.');
               }

               if ($method === 'Secure') {
                   $email = trim($_POST['email'] ?? '');
                   $phone = trim($_POST['phone'] ?? '');
                   
                   if (empty($email) && empty($phone)) {
                       throw new Exception('Please provide either an email or phone number for verification codes.');
                   }

                   // Update user contact info
                   $sql = 'UPDATE bg_users SET 
                           email = :email,
                           phone_number = :phone,
                           modify_dt = NOW()
                           WHERE user_id = :user_id';
                   $stmt = $database->prepare($sql);
                   $stmt->execute([
                       'email' => $email,
                       'phone' => $phone,
                       'user_id' => $current_user_id
                   ]);
               } 
               else if ($method === 'Highly Secure') {
                   // Generate TOTP secret and verify setup


                   if (isset($_POST['verify_code'])) {
                       $verify_code = trim($_POST['verify_code']);
                       $secret = $_SESSION['temp_2fa_secret'] ?? '';
                       
                       if (empty($secret)) {
                           throw new Exception('Security setup expired. Please try again.');
                       }
                       
                       if (!$twofactorauth->verifyCode($secret, $verify_code, 2)) {   ////---- HERE
                           throw new Exception('Invalid verification code. Please try again.');
                       }
                             // Disable existing method
               $sql = "UPDATE bg_user_attributes 
               SET `status` = 'inactive', modify_dt = NOW() 
               WHERE user_id = :user_id AND (`type` = '2fa_secret')";  
       $stmt = $database->prepare($sql);
       $stmt->execute(['user_id' => $current_user_id]);

                       // Store secret permanently if verified
                       $sql = "INSERT INTO bg_user_attributes 
                               (user_id, `type`, name, string_value, `status`, create_dt, modify_dt) 
                               VALUES 
                               (:user_id, '2fa_secret', 'totp_secret', :secret, 'active', NOW(), NOW())";
                       $stmt = $database->prepare($sql);
                       $stmt->execute([
                           'user_id' => $current_user_id,
                           'secret' => $secret
                       ]);
                       
                       unset($_SESSION['temp_2fa_secret']);
                   } else {
                       // Generate new secret for QR code display
                       $secret = $twofactorauth->createSecret();   ////---- HERE
                       $_SESSION['temp_2fa_secret'] = $secret;
                       
                       // Build otpauth URL for QR code
                       $issuer = urlencode('Birthday.Gold');
                     #  $account = urlencode($user_contact['email']);
                       $otpauth_url = "otpauth://totp/{$issuer}:{".urlencode($user_contact['email'])."}?secret={$secret}&issuer={$issuer}";
                       
                       // Generate QR code
                       $show_qr = true;
                       continue; // Show QR form instead of completing setup
                   }
               }

               // Disable existing method
               $sql = "UPDATE bg_user_attributes 
                       SET `status` = 'inactive', modify_dt = NOW() 
                  WHERE user_id = :user_id AND (`type` = '2fa_method')";  
               $stmt = $database->prepare($sql);
               $stmt->execute(['user_id' => $current_user_id]);

               // Set up new method
               $sql = "INSERT INTO bg_user_attributes 
                       (user_id, `type`, name, string_value, `status`, create_dt, modify_dt) 
                       VALUES 
                       (:user_id, '2fa_method', 'auth_type', :method, 'active', NOW(), NOW())";
                  #     breakpoint($sql);
               $stmt = $database->prepare($sql);
               $stmt->execute([
                   'user_id' => $current_user_id,
                   'method' => $method
               ]);

               $success_message = $current_2fa ? 
                   'Your two-factor authentication method has been updated.' : 
                   'Two-factor authentication has been set up successfully.';

               session_tracking('2FA ' . ($current_2fa ? 'updated' : 'configured') . ' successfully');
               $pagemessage = '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
                             $success_message . '</div>';
               break;

           case 'disable':
               $sql = "UPDATE bg_user_attributes 
                       SET `status` = 'inactive', modify_dt = NOW() 
                       WHERE user_id = :user_id AND  (`type` = '2fa_method' or `type` = '2fa_secret')"; 
               $stmt = $database->prepare($sql);
               $stmt->execute(['user_id' => $current_user_id]);

               session_tracking('2FA disabled successfully');
               $pagemessage = '<div class="alert alert-success alert-dismissible fade show" role="alert">' .
                             'Two-factor authentication has been disabled.' . '</div>';
               break;

           case 'showchange':
               $show_change_form = true;
               break;

           case 'cancel':
               $transferpage['url'] = '/myaccount/security-2fa';
               $transferpage['message'] = '';
               $system->endpostpage($transferpage);
               exit;
       }

       if (isset($pagemessage)) {
           $transferpage['url'] = '/myaccount/security-2fa';
           $transferpage['message'] = $pagemessage;
           $system->endpostpage($transferpage);
           exit;
       }

   } catch (Exception $e) {
       error_log("2FA update failed: " . $e->getMessage());
       session_tracking('2FA update failed: ' . $e->getMessage());
       $pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
                      'An error occurred while updating two-factor authentication settings.' . '</div>';
       $transferpage['url'] = '/myaccount/security-2fa';
       $transferpage['message'] = $pagemessage;
       $system->endpostpage($transferpage);
       exit;
   }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';

$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);
$success_message=$transferpagedata['message'];

// Additional styles
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">
<style>
/* 2FA Setup Page Styles */
.setup-card {
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 0;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    overflow: hidden;
}

.setup-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.setup-card-header {
    padding: 1.5rem;
    background: #e9ecef;
    border-bottom: 1px solid #dee2e6;
}

.setup-card-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #212529;
}

.setup-card-body {
    padding: 1.5rem;
}

.method-card {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.method-card:hover {
    border-color: #0d6efd;
    background: #f8f9fa;
}

.method-card.selected {
    border-color: #0d6efd;
    background: #e7f1ff;
}

.method-card input[type="radio"] {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
}

.method-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.method-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.method-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0;
}

.qr-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 12px;
    margin: 2rem 0;
    text-align: center;
}

.qr-code-container {
    background: white;
    display: inline-block;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

#verify_code {
    letter-spacing: 0.5em;
    font-family: monospace;
    font-size: 1.5em;
    text-align: center;
    max-width: 200px;
    margin: 0 auto;
}

.status-card {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.status-card.active {
    background: #d4edda;
    border-color: #c3e6cb;
}

.security-tip {
    background: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1.5rem;
}

.security-tip-icon {
    color: #856404;
    font-size: 1.25rem;
}

/* Benefits list */
.benefit-list {
    list-style: none;
    padding: 0;
}

.benefit-list li {
    padding: 0.75rem 0;
    display: flex;
    align-items: flex-start;
}

/* Steps */
.setup-steps {
    counter-reset: step-counter;
    list-style: none;
    padding: 0;
}

.setup-steps li {
    counter-increment: step-counter;
    margin-bottom: 1.5rem;
    padding-left: 3rem;
    position: relative;
}

.setup-steps li:before {
    content: counter(step-counter);
    position: absolute;
    left: 0;
    top: 0;
    background: #0d6efd;
    color: white;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.setup-steps li.completed:before {
    background: #198754;
    content: "\F26A";
    font-family: "Bootstrap Icons";
}

@media (max-width: 768px) {
    .method-card {
        text-align: center;
    }
    
    .method-card input[type="radio"] {
        position: static;
        margin-top: 1rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-shield-lock me-3"></i>Two-Factor Authentication</h1>
            <p class="lead mb-0">Add an extra layer of security to protect your account</p>
        </div>
    </div>
</div>

<div class="container my-5 pt-5">
    <?php echo $success_message; ?>
    
    <!-- 2FA Benefits Section -->
    <div class="setup-card">
        <div class="setup-card-header">
            <h3><i class="bi bi-shield-check me-2 text-success"></i>Why Enable Two-Factor Authentication?</h3>
        </div>
        <div class="setup-card-body">
            <p class="mb-3">Two-factor authentication (2FA) significantly improves your account security by requiring two forms of identification:</p>
            <ul class="benefit-list">
                <li><i class="bi bi-shield-check text-success me-3"></i>Protection against password theft - Even if someone gets your password, they cannot access your account</li>
                <li><i class="bi bi-bell-fill text-success me-3"></i>Real-time alerts - Get notified instantly when someone tries to log in</li>
                <li><i class="bi bi-heart-fill text-success me-3"></i>Peace of mind - Know that your birthday rewards and personal information are secure</li>
                <li><i class="bi bi-people-fill text-success me-3"></i>Industry standard security - Join millions who use 2FA to protect their accounts</li>
            </ul>
        </div>
    </div>

<?php

if (!$current_2fa || $show_change_form) {
    // Show setup/change form
    echo '
    <!-- Setup Method Selection -->
    <div class="setup-card">
        <div class="setup-card-header">
            <h3><i class="bi bi-gear me-2 text-primary"></i>' . ($current_2fa ? 'Change Authentication Method' : 'Choose Your Authentication Method') . '</h3>
        </div>
        <div class="setup-card-body">
            <form method="POST" action="">
                ' . $display->inputcsrf_token() . '
                <input type="hidden" name="action" value="' . ($current_2fa ? 'change' : 'setup') . '">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="method-card">
                            <input type="radio" name="method" value="Secure" id="method_secure">
                            <div class="method-icon text-info">
                                <i class="bi bi-envelope-check-fill"></i>
                            </div>
                            <h4 class="method-title">Email/SMS Verification</h4>
                            <p class="method-description">
                                Receive a verification code via email or text message each time you log in. 
                                Simple and convenient for most users.
                            </p>
                        </label>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="method-card">
                            <input type="radio" name="method" value="Highly Secure" id="method_app">
                            <div class="method-icon text-success">
                                <i class="bi bi-smartphone"></i>
                            </div>
                            <h4 class="method-title">Authenticator App</h4>
                            <p class="method-description">
                                Use an app like Google Authenticator or Authy to generate codes. 
                                Most secure option, works offline.
                            </p>
                        </label>
                    </div>
                </div>';

    if ($show_qr) {
        echo '
                <div class="qr-section">
                    <h4 class="mb-3"><i class="bi bi-qr-code me-2"></i>Scan QR Code with Your Authenticator App</h4>
                    
                    <ol class="setup-steps text-start mb-4">
                        <li>Download an authenticator app like Google Authenticator, Microsoft Authenticator, or Authy on your phone</li>
                        <li>Open the app and tap the + button to add a new account</li>
                        <li>Scan the QR code below with your phone camera</li>
                        <li class="completed">Enter the 6-digit code shown in your app to verify setup</li>
                    </ol>
                    
                    <div class="qr-code-container mb-4">
                        <img id="qrCodeImage" src="'.$website['fullurl'].'/qr?i=' . $otpauth_url.'" alt="QR Code" style="width: 200px; height: 200px;">
                    </div>
                    
                    <div class="alert alert-info mb-4" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Cannot scan?</strong> Enter this code manually in your app: 
                        <code class="ms-2">' . chunk_split($_SESSION['temp_2fa_secret'], 4, ' ') . '</code>
                    </div>
                    
                    <div class="form-group">
                        <label for="verify_code" class="form-label fw-bold">Verification Code:</label>
                        <input type="text" class="form-control" id="verify_code" name="verify_code" 
                               placeholder="000000" required maxlength="6" pattern="[0-9]{6}" autocomplete="off">
                        <small class="text-muted">Enter the 6-digit code from your authenticator app</small>
                    </div>
                    <input type="hidden" name="method" value="Highly Secure">
                </div>';
    } else {
        echo '
                <div id="contactFields" style="display:none">
                    <div class="alert alert-info mb-3" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        We will send verification codes to your email or phone number when you log in.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope me-2"></i>Email for Verification Codes:
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="' . htmlspecialchars($user_contact['email']) . '"
                                       placeholder="your@email.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="phone" class="form-label">
                                    <i class="bi bi-phone me-2"></i>Phone for SMS Codes (Optional):
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="' . htmlspecialchars($user_contact['phone_number']) . '"
                                       placeholder="+1 (555) 123-4567">
                            </div>
                        </div>
                    </div>
                </div>';
    }

    echo '
                <div class="security-tip">
                    <i class="bi bi-lightbulb security-tip-icon me-2"></i>
                    <strong>Security Tip:</strong> ' . 
                    ($show_qr ? 'Keep your authenticator app backed up or save the secret code in a secure location.' 
                              : 'Make sure your email and phone are always up to date to receive verification codes.') . '
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-shield-check me-2"></i>
                        ' . ($current_2fa ? 'Update 2FA Method' : 'Enable Two-Factor Authentication') . '
                    </button>
                    <a href="/myaccount/security-settings" class="btn btn-secondary btn-lg ms-2">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>';

} else {
    // Show current configuration
    $method_icon = $current_2fa['auth_method'] === 'Highly Secure' ? 'smartphone' : 'envelope-check-fill';
    $method_color = $current_2fa['auth_method'] === 'Highly Secure' ? 'success' : 'info';
    
    echo '
    <!-- Current 2FA Status -->
    <div class="status-card active">
        <div class="d-flex align-items-center mb-3">
            <i class="bi bi-' . $method_icon . ' text-' . $method_color . ' me-3" style="font-size: 2.5rem;"></i>
            <div>
                <h4 class="mb-0">Two-Factor Authentication is Active</h4>
                <p class="mb-0 text-muted">Your account is protected with additional security</p>
            </div>
        </div>
    </div>
    
    <div class="setup-card">
        <div class="setup-card-header">
            <h3><i class="bi bi-shield-check me-2 text-success"></i>Current Security Configuration</h3>
        </div>
        <div class="setup-card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Authentication Method</h5>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-' . $method_icon . ' text-' . $method_color . ' me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h6 class="mb-0">' . htmlspecialchars($current_2fa['auth_method']) . '</h6>
                            <small class="text-muted">Enabled on ' . date('F j, Y', strtotime($current_2fa['create_dt'])) . '</small>
                        </div>
                    </div>';

    if ($current_2fa['auth_method'] === 'Secure') {
        echo '
                    <div class="alert alert-light" role="alert">
                        <h6 class="alert-heading"><i class="bi bi-send me-2"></i>Verification Methods</h6>';
        
        if (!empty($user_contact['email'])) {
            echo '<p class="mb-1"><i class="bi bi-envelope me-2"></i>Email: <strong>' . htmlspecialchars($user_contact['email']) . '</strong></p>';
        }
        if (!empty($user_contact['phone_number'])) {
            echo '<p class="mb-0"><i class="bi bi-phone me-2"></i>Phone: <strong>' . htmlspecialchars($user_contact['phone_number']) . '</strong></p>';
        }
        
        echo '
                    </div>';
    } else {
        echo '
                    <div class="alert alert-light" role="alert">
                        <h6 class="alert-heading"><i class="bi bi-smartphone me-2"></i>Authenticator App</h6>
                        <p class="mb-0">You are using an authenticator app to generate verification codes.</p>
                    </div>';
    }

    echo '
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">How It Works</h5>
                    <ol class="small text-muted">';
    
    if ($current_2fa['auth_method'] === 'Secure') {
        echo '
                        <li>When you log in, we will send a code to your email or phone</li>
                        <li>Enter the code to complete your login</li>
                        <li>Codes expire after 10 minutes for security</li>';
    } else {
        echo '
                        <li>Open your authenticator app when logging in</li>
                        <li>Enter the 6-digit code shown in the app</li>
                        <li>Codes refresh every 30 seconds</li>';
    }
    
    echo '
                    </ol>
                    
                    <div class="security-tip mt-3">
                        <i class="bi bi-shield-check security-tip-icon me-2"></i>
                        Your account is ' . ($current_2fa['auth_method'] === 'Highly Secure' ? 'highly' : 'well') . ' protected
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <form method="POST" action="" class="text-center">
                ' . $display->inputcsrf_token() . '
                <button type="submit" name="action" value="showchange" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat me-2"></i>Change Authentication Method
                </button>
                <button type="submit" name="action" value="disable" class="btn btn-outline-danger ms-2"
                        onclick="return confirm(\'Are you sure you want to disable two-factor authentication? This will make your account less secure.\')">
                    <i class="bi bi-x-circle me-2"></i>Disable 2FA
                </button>
            </form>
        </div>
    </div>';
}

echo '</div>'; // Close container

if (!$current_2fa || $show_change_form) {
    echo '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Handle method card selection
        const methodCards = document.querySelectorAll(".method-card");
        const contactFields = document.getElementById("contactFields");
        
        methodCards.forEach(card => {
            card.addEventListener("click", function() {
                // Remove selected class from all cards
                methodCards.forEach(c => c.classList.remove("selected"));
                
                // Add selected class to clicked card
                this.classList.add("selected");
                
                // Check the radio button
                const radio = this.querySelector("input[type=radio]");
                radio.checked = true;
                
                // Show/hide contact fields
                if (contactFields) {
                    contactFields.style.display = radio.value === "Secure" ? "block" : "none";
                }
            });
        });
        
        // Auto-format verification code input
        if (document.getElementById("verify_code")) {
            document.getElementById("verify_code").addEventListener("input", function(e) {
                this.value = this.value.replace(/[^0-9]/g, "").substring(0, 6);
            });
            
            // Auto-focus on verification code field
            document.getElementById("verify_code").focus();
        }
    });
    </script>';
}

echo '
<!-- Back to Security Settings link -->
<div class="text-center my-5">
    <a href="/myaccount/security-settings" class="text-muted">
        <i class="bi bi-arrow-left me-2"></i>Back to Security Settings
    </a>
</div>';

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();