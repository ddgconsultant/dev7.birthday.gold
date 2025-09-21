<?php

$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$redirectlink = '/myaccount/changepassword';
$errormessage = '';
#-------------------------------------------------------------------------------
# CHECK FOR VALID VALIDATION 
#-------------------------------------------------------------------------------
if (isset($_GET['t'])) {
    if (isset($_GET['t'])) $checkdata['long'] = $_GET['t'];
    if (isset($_POST['validationCode'])) $checkdata['mini'] = $_POST['validationCode'];
    $checkdata['type'] = 'forgotpassword';
    $response = $app->checkvalidationcodes($checkdata);
    #breakpoint($response);
    if ($response['validated']) {
        $session->set('passwordreset', $response);
        header('location: /myaccount/changepassword');
        exit;
    } else {

        $errormessage = '<div class="alert alert-danger">That link didn\'t work.  Please try again.</div>';
        $transferpage['url'] = '/forgot';
        $transferpage['message'] = $errormessage;
        $system->endpostpage($transferpage);
        exit;
    }
}

#-------------------------------------------------------------------------------
# HANDLE FORM POSTING
#-------------------------------------------------------------------------------
if ($formdata = $app->formposted()) {
    $checkdata = array();

    $passwordresetdata = $session->get('passwordreset', '');
    if (empty($passwordresetdata)) {

        ## THIS MEANS THAT THE USER IS USING THE REAL PASSWORD RESET FORM AND THE PROVIDED PASSWORD MUST MATCH


        $current_user_data = $session->get('current_user_data', '');
        if (empty($current_user_data['user_id'])) {
            $errormessage = '<div class="alert alert-danger">You need to log in to change your password.</div>';
            $transferpage['url'] = '/login';
            $transferpage['message'] = $errormessage;
            $system->endpostpage($transferpage);
            exit;
        }
        #  $userid= $userregistrationdata['user_id'];
        # $current_user_data=$account->getuserdata($userid, 'user_id');
        ## got a user, now check to see if old password matches (can also be )
        if (!password_verify($formdata['inputcurrentPassword'], $current_user_data['password'])) {
            # breakpoint($formdata['inputcurrentPassword'].'/'. $current_user_data['password']);
            ## we do not have matching old and existing passwords... 
            $errormessage = '<div class="alert alert-danger">Your old password does not match.<br>You use this <a href="/forgot">link</a> to reset your password.</div>';
            $transferpage['url'] = '/myaccount/changepassword';
            $transferpage['message'] = $errormessage;
            $system->endpostpage($transferpage);
            exit;
        }
    } else {

        if ($passwordresetdata['validated'] !== true) {
            ## new password doesn't match confirm password
            $session->unset('passwordreset');
            unset($current_user_data);
            $errormessage = '<div class="alert alert-danger">Hmmm... something weird happened.</div>';
            $transferpage['url'] = '/myaccount/changepassword';
            $transferpage['message'] = $errormessage;
            $system->endpostpage($transferpage);
            exit;
        }
        $redirectlink = '/login';
        $userid = $passwordresetdata[0]['user_id'];
    }

    if ($formdata['inputnewPassword'] != $formdata['inputconfirmPassword']) {
        ## new password doesn't match confirm password
        $errormessage = '<div class="alert alert-danger">Your New password and Confirm password does not match.</div>';
        $transferpage['url'] = '/myaccount/changepassword';
        $transferpage['message'] = $errormessage;
        $system->endpostpage($transferpage);
        exit;
    }

    ## we made it this far successfully... update the user password
    $session->unset('passwordreset');
    $userid = $current_user_data['user_id'];
    $password = $formdata['inputnewPassword'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $output = print_r($userid, 1) . '|' . print_r($hashed_password, 1) . '|' . print_r($password, 1) . '|' . print_r($passwordresetdata, 1) . '|' . print_r($userregistrationdata, 1) . '|' . print_r($current_user_data, 1);
    $response = $account->updateSettings($current_user_data['user_id'], ['password' => $hashed_password]);
    $current_user_data = $account->getuserdata($userid, 'user_id');
    $output .= "\n-----------------------------------\nPOSTUPDATE\n" . print_r($response, 1) . '|' . print_r($current_user_data, 1);
    
    // Track password change with strength analysis
    $password_strength = $account->trackPasswordChange($userid, $password, 'change');
    
    $outputarrayelement['output'] = $output;
    $outputarrayelement['response'] = $response;
    $outputarrayelement['current_user_data'] = $current_user_data;
    $outputarrayelement['hashed_password'] = $hashed_password;
    session_tracking('CHANGEPASSWORD_SUCCESS', $outputarrayelement);
    $session->set('current_user_data', $current_user_data);
    $errormessage = '<div class="alert alert-success">Your password was successfully changed.</div>';
    $transferpage['url'] = $redirectlink;
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
    exit;
}


$transferpage = $system->startpostpage();

#-------------------------------------------------------------------------------
# ASK USER FOR NEW PASSWORDS
#-------------------------------------------------------------------------------
$passwordresetdata = $session->get('passwordreset', '');
$userfullname = '';
if (!empty($passwordresetdata)) {
    $tempuserdata = $account->getuserdata($passwordresetdata[0]['user_id'], 'user_id');
    $userfullname = '<div class="mb-3 text-center"><h3>' . $tempuserdata['first_name'] . ", welcome back.  Let's reset your password.</h3></div>";
}

// Page setup
$pagetitle = "Change Password - My Account";
$additionalstyles .= '
<style>
.password-form-container {
    max-width: 500px;
    margin: 0 auto;
}

.password-strength-meter {
    margin-top: 0.5rem;
}

.strength-bar {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-weak { background: #dc3545; width: 25%; }
.strength-fair { background: #fd7e14; width: 50%; }
.strength-good { background: #198754; width: 75%; }
.strength-strong { background: #0d6efd; width: 100%; }

.password-requirements {
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.requirement {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.requirement.met {
    color: #198754;
}

.requirement.unmet {
    color: #6c757d;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12">
                <h1 class="mb-3">Change Password</h1>
                <p class="lead mb-0">
                    Update your password to keep your Birthday.Gold account secure.
                </p>
                <?php if (!empty($userfullname)): ?>
                    <div class="mt-3 text-white-50">
                        <?php echo $userfullname; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <?php echo $display->formaterrormessage($transferpage['message']); ?>
            
            <div class="password-form-container">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-shield-check me-2"></i>Change Password
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="changepassword" method="post" id="passwordChangeForm">                           
                            <?php echo $display->inputcsrf_token(); ?>
                            <input name="returnto" type="hidden" value="/myaccount/changepassword">                   
                            
                            <?php

if (empty($passwordresetdata['validated'])) {
                                echo '
                                <div class="mb-4">
                                    <label class="form-label fw-semibold" for="inputcurrentPassword">
                                        <i class="bi bi-lock me-2"></i>Current Password
                                    </label>
                                    <div class="input-group">
                                        <input class="form-control form-control-lg" 
                                               name="inputcurrentPassword" 
                                               id="inputcurrentPassword" 
                                               type="password" 
                                               placeholder="Enter your current password"
                                               required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(\'inputcurrentPassword\', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>';
                            }
                            ?>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="inputnewPassword">
                                    <i class="bi bi-key me-2"></i>New Password
                                </label>
                                <div class="input-group">
                                    <input class="form-control form-control-lg" 
                                           name="inputnewPassword" 
                                           id="newPassword" 
                                           type="password" 
                                           placeholder="Enter your new password"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                
                                <!-- Password Strength Meter -->
                                <div class="password-strength-meter">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Password Strength:</small>
                                        <small class="strength-text text-muted">Enter password</small>
                                    </div>
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                </div>
                                
                                <!-- Password Requirements -->
                                <div class="password-requirements">
                                    <?php 
                                    $password_requirements = $account->getPasswordRequirements();
                                    foreach ($password_requirements['requirements'] as $req): 
                                    ?>
                                    <div class="requirement unmet" id="req-<?php echo $req['id']; ?>">
                                        <i class="bi bi-circle"></i>
                                        <span><?php echo htmlspecialchars($req['description']); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="inputconfirmPassword">
                                    <i class="bi bi-check-circle me-2"></i>Confirm New Password
                                </label>
                                <div class="input-group">
                                    <input class="form-control form-control-lg" 
                                           name="inputconfirmPassword" 
                                           id="inputconfirmPassword" 
                                           type="password" 
                                           placeholder="Confirm your new password"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('inputconfirmPassword', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-match-indicator mt-2" id="passwordMatch" style="display: none;"></div>
                            </div>
                            
                            <div class="d-grid">
                                <button class="btn btn-secondary btn-lg" type="submit" id="submitBtn" disabled>
                                    <i class="bi bi-shield-check me-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/public/js/passwordhelper.js"></script>
<script>
// Toggle password visibility
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
        button.setAttribute('aria-label', 'Hide password');
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
        button.setAttribute('aria-label', 'Show password');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('inputconfirmPassword');
    const submitBtn = document.getElementById('submitBtn');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.querySelector('.strength-text');
    const passwordMatch = document.getElementById('passwordMatch');

    // Password requirements from server-side (matches Birthday Gold standards)
    const requirements = <?php echo json_encode($password_requirements['requirements']); ?>;
    const minScoreForSubmit = <?php echo $password_requirements['minimum_score_for_submit']; ?>;

    // Password strength checking using Birthday Gold algorithm
    function checkPasswordStrength(password) {
        let score = 0;
        const length = password.length;
        
        // Length scoring (0-40 points) - matches calculatePasswordStrength
        if (length >= 8) score += 10;
        if (length >= 10) score += 10; 
        if (length >= 12) score += 10;
        if (length >= 16) score += 10;
        
        // Character variety (0-60 points) - matches calculatePasswordStrength
        if (/[a-z]/.test(password)) score += 10; // lowercase
        if (/[A-Z]/.test(password)) score += 10; // uppercase
        if (/[0-9]/.test(password)) score += 10; // numbers
        if (/[^a-zA-Z0-9]/.test(password)) score += 15; // special chars
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) score += 5; // common special chars
        
        // Complexity bonus
        let charTypes = 0;
        if (/[a-z]/.test(password)) charTypes++;
        if (/[A-Z]/.test(password)) charTypes++;
        if (/[0-9]/.test(password)) charTypes++;
        if (/[^a-zA-Z0-9]/.test(password)) charTypes++;
        
        if (charTypes >= 3 && length >= 10) score += 10; // complexity bonus
        
        // Penalize common patterns
        if (/(.)\1{2,}/.test(password)) score -= 10; // repeated chars
        if (/123|abc|password|qwerty/i.test(password)) score -= 15; // common sequences
        
        // Ensure score stays in bounds
        score = Math.max(0, Math.min(100, score));
        
        // Update requirement indicators
        requirements.forEach(req => {
            const reqElement = document.getElementById('req-' + req.id);
            if (reqElement) {
                const pattern = new RegExp(req.pattern);
                const met = req.id === 'length' ? length >= 8 : pattern.test(password);
                
                if (met) {
                    reqElement.classList.add('met');
                    reqElement.classList.remove('unmet');
                    reqElement.querySelector('i').className = 'bi bi-check-circle-fill';
                } else {
                    reqElement.classList.add('unmet');
                    reqElement.classList.remove('met');
                    reqElement.querySelector('i').className = 'bi bi-circle';
                }
            }
        });
        
        return score;
    }
    
    // Update password strength display
    function updateStrengthDisplay(score) {
        strengthFill.className = 'strength-fill';
        
        if (score === 0) {
            strengthText.textContent = 'Enter password';
            strengthText.className = 'strength-text text-muted';
        } else if (score < 50) {
            strengthFill.classList.add('strength-weak');
            strengthText.textContent = 'Weak';
            strengthText.className = 'strength-text text-danger';
        } else if (score < 75) {
            strengthFill.classList.add('strength-fair');
            strengthText.textContent = 'Fair';
            strengthText.className = 'strength-text text-warning';
        } else if (score < 100) {
            strengthFill.classList.add('strength-good');
            strengthText.textContent = 'Good';
            strengthText.className = 'strength-text text-success';
        } else {
            strengthFill.classList.add('strength-strong');
            strengthText.textContent = 'Strong';
            strengthText.className = 'strength-text text-primary';
        }
    }
    
    // Check password match
    function checkPasswordMatch() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword.length === 0) {
            passwordMatch.style.display = 'none';
            return false;
        }
        
        passwordMatch.style.display = 'block';
        
        if (newPassword === confirmPassword) {
            passwordMatch.innerHTML = '<small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Passwords match</small>';
            return true;
        } else {
            passwordMatch.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Passwords do not match</small>';
            return false;
        }
    }
    
    // Update submit button state
    function updateSubmitButton() {
        const passwordScore = checkPasswordStrength(newPasswordInput.value);
        const passwordsMatch = checkPasswordMatch();
        const currentPasswordFilled = document.getElementById('inputcurrentPassword') ? 
            document.getElementById('inputcurrentPassword').value.length > 0 : true;
        
        // Enable submit if password is good enough, passwords match, and current password is filled
        const canSubmit = passwordScore >= minScoreForSubmit && passwordsMatch && currentPasswordFilled;
        
        submitBtn.disabled = !canSubmit;
        
        // Change button color based on readiness
        if (canSubmit) {
            submitBtn.className = 'btn btn-success btn-lg';  // Green when ready
        } else {
            submitBtn.className = 'btn btn-secondary btn-lg'; // Gray when not ready
        }
    }
    
    // Event listeners
    newPasswordInput.addEventListener('input', function() {
        const score = checkPasswordStrength(this.value);
        updateStrengthDisplay(score);
        updateSubmitButton();
    });
    
    confirmPasswordInput.addEventListener('input', updateSubmitButton);
    
    // Current password listener (if field exists)
    const currentPasswordInput = document.getElementById('inputcurrentPassword');
    if (currentPasswordInput) {
        currentPasswordInput.addEventListener('input', updateSubmitButton);
    }
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
