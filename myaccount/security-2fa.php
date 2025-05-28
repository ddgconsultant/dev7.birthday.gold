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
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');


$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);
$success_message=$transferpagedata['message'];

$additionalstyles .= '
<style>
.qr-section {
   background: #f8f9fa;
   padding: 2rem;
   border-radius: 0.5rem;
   margin: 2rem 0;
}
#verify_code {
   letter-spacing: 0.5em;
   font-family: monospace;
   font-size: 1.2em;
   text-align: center;
}
</style>
';

echo '
<div class="container main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Two-Factor Authentication</h2>
       <a href="/myaccount/security-settings" class="btn btn-sm btn-primary">Security Settings</a>
 </div>

           '.$success_message.'
           <div class="card">
               <div class="card-body">';

if (!$current_2fa || $show_change_form) {
   // Show setup/change form
   echo '
       <form method="POST" action="">
           ' . $display->inputcsrf_token() . '
           <input type="hidden" name="action" value="' . ($current_2fa ? 'change' : 'setup') . '">
           
           <div class="form-group mb-3">
               <label for="method">Choose Security Method:</label>
               <select class="form-control" id="method" name="method">
                   <option value="">-- Select Method --</option>
                   <option value="Secure">Secure (Email/SMS Code)</option>
                   <option value="Highly Secure">Highly Secure (Authenticator App)</option>
               </select>
           </div>';

   if ($show_qr) {
       echo '
           <div id="qrSection" class="qr-section">
               <h4>Scan QR Code</h4>
               <p class="text-muted">Use your authenticator app (like Google Authenticator, Authy, or Microsoft Authenticator) to scan this QR code.</p>
               <div class="mb-3">
                  <img class="m-5" id="qrCodeImage" src="'.$website['fullurl'].'/qr?i=' . $otpauth_url.'" alt="QR Code" style="width: 200px; height: 200px;">
               </div>
               <div class="mb-3">
                   <p class="text-muted">Manual entry code: <code>' . chunk_split($_SESSION['temp_2fa_secret'], 4, ' ') . '</code></p>
               </div>
               <div class="form-group">
                   <label for="verify_code">Enter the 6-digit code from your authenticator app to verify setup:</label>
                   <input type="text" class="form-control w-50 mx-auto" id="verify_code" name="verify_code" 
                          placeholder="Enter 6-digit code" required maxlength="6" pattern="[0-9]{6}">
               </div>

            <input type="hidden" name="method" value="Highly Secure">
           </div>';
   } else {
       echo '
           <div id="contactFields" style="display:none">
               <div class="form-group mb-3">
                   <label for="email">Email for Verification Codes:</label>
                   <input type="email" class="form-control" id="email" name="email" 
                          value="' . htmlspecialchars($user_contact['email']) . '">
               </div>
               <div class="form-group mb-3">
                   <label for="phone">Phone for Verification Codes:</label>
                   <input type="tel" class="form-control" id="phone" name="phone" 
                          value="' . htmlspecialchars($user_contact['phone_number']) . '">
               </div>
           </div>';
   }

   echo '
           <div class="form-group mt-3">
               <button type="submit" class="btn btn-primary">
                   ' . ($current_2fa ? 'Update 2FA Method' : 'Enable 2FA') . '
               </button>
               <button type="submit" name="action" value="cancel" class="btn btn-secondary">
                   Cancel
               </button>
           </div>
       </form>';

} else {
   // Show current configuration
   echo '
       <div class="current-config">
           <h4>Current Two-Factor Authentication Method</h4>
           <p>Method: <strong>' . htmlspecialchars($current_2fa['auth_method']) . '</strong></p>
           <p>Configured on: ' . date('F j, Y', strtotime($current_2fa['create_dt'])) . '</p>';

   if ($current_2fa['auth_method'] === 'Secure') {
       echo '<p>Verification codes will be sent to: ';
       $methods = [];
       if (!empty($user_contact['email'])) $methods[] = 'Email (' . htmlspecialchars($user_contact['email']) . ')';
       if (!empty($user_contact['phone_number'])) $methods[] = 'Phone (' . htmlspecialchars($user_contact['phone_number']) . ')';
       echo implode(' or ', $methods) . '</p>';
   }

   echo '  <form method="POST" action="" class="mt-4">
               ' . $display->inputcsrf_token() . '
               <button type="submit" name="action" value="showchange" class="btn btn-primary">
                   Change Security Method
               </button>
               <button type="submit" name="action" value="disable" class="btn btn-danger ms-2"
                       onclick="return confirm(\'Are you sure you want to disable two-factor authentication?\')">
                   Disable 2FA
               </button>
           </form>
       </div>';
}

echo '      </div>     </div>
           </div>
       </div>
   </div>
</div>';

if (!$current_2fa || $show_change_form) {
   echo '
   <script>
   document.getElementById("method").addEventListener("change", function() {
       var contactFields = document.getElementById("contactFields");
       if (contactFields) {
           contactFields.style.display = this.value === "Secure" ? "block" : "none";
       }
   });

   if (document.getElementById("verify_code")) {
       document.getElementById("verify_code").addEventListener("input", function(e) {
           this.value = this.value.replace(/[^0-9]/g, "").substring(0, 6);
       });
   }
   </script>';
}

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();