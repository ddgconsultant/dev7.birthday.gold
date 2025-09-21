<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$lockout_until = $session->get('login_lockout_until', 0, true);
$current_time = time();

// If lockout has expired, send back to login
if ($lockout_until <= $current_time) {
    $session->set('login_lockout_until', 0);
    $transferpagedata['message'] = 'Try logging in again.';
    $transferpagedata['url'] = '/login';
    $transferpagedata = $system->endpostpage($transferpagedata);
}

$minutes_remaining = ceil(($lockout_until - $current_time) / 60);

// In account-lockout.php, before the HTML section
$remaining_seconds = max(0, $lockout_until - $current_time);
$minutes_remaining = ceil($remaining_seconds / 60);


include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$transferpagedata['message'] = '';
$transferpagedata = $system->startpostpage($transferpagedata);
?>

<section class="h-100 gradient-form main-content">
    <div class="container py-5">
        <div class="row d-flex justify-content-center align-items-center">
            <div class="col-xl-10">
                <div class="card rounded-3 text-black">
                    <div class="row g-0">
                        <div class="col-lg-12">
                            <div class="card-body p-md-5 mx-md-4">
                                <div class="text-center mb-5">
                                <picture>
  <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a8/512.webp" type="image/webp">
  <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a8/512.gif" alt="🚨" width="64" height="64">
</picture>
                                    <h2>Account Temporarily Locked</h2>
                                </div>
                                
                                <div class="alert alert-warning">
    <h4 class="alert-heading">Security Notice</h4>
    <p>For your security, account access has been temporarily restricted due to multiple failed login attempts.</p>
    <hr>
    <p class="mb-0" id="countdown">Please try again in <?php echo $minutes_remaining; ?> minutes</p>
</div>

<div class="text-center mt-4">
    <div id="actionButtons">
       
        <a href="/forgot" class="btn btn-outline-primary me-2">Reset Password</a>
        <a href="/help" class="btn btn-outline-secondary">Contact Support</a>
    </div>
</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?PHP

// Add this JS before the closing body tag
echo '
<script>
const lockoutEndTime = ' . $lockout_until . ' * 1000; // Convert to milliseconds
const loginButtonHtml = \'<a href="/login" class="btn btn-primary me-2">Login</a>\';

function updateCountdown() {
    const now = new Date().getTime();
    const timeLeft = lockoutEndTime - now;
    
    if (timeLeft <= 0) {
        // Time has expired
        document.getElementById("countdown").innerHTML = "You may now try logging in again.";
        
        // Add login button if it doesn\'t exist
        const actionDiv = document.getElementById("actionButtons");
        if (!document.getElementById("loginButton")) {
            actionDiv.innerHTML = loginButtonHtml + actionDiv.innerHTML;
        }
        return;
    }
    
    // Calculate time units
    const minutes = Math.floor(timeLeft / (1000 * 60));
    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
    
    // Update countdown display
    document.getElementById("countdown").innerHTML = 
        `Please try again in ${minutes}:${seconds < 10 ? "0" : ""}${seconds} minutes`;
    
    // Continue countdown
    setTimeout(updateCountdown, 1000);
}

// Start countdown when page loads
updateCountdown();
</script>';
?>
<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>