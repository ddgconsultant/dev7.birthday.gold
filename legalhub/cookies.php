<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$errormessage = '';


#-------------------------------------------------------------------------------
# HANDLE THE POST ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    // Handling form submission
    # if (isset($_POST['saveSettings'])) {
    // Cookie options for compliance with Safari
    $cookieOptions = array(
        'expires' => time() + (86400 * 365), // 1 year
        'path' => '/',
        'secure' => true,   // Only send over HTTPS
        'samesite' => 'None', // Explicitly set SameSite attribute to 'None'
        'httponly' => false,   // Not HTTP-only as we might want to access it via JavaScript
        'domain' => 'birthday.gold'
    );
    # print_r($cookieOptions);
    // Analytics Cookie
    if (isset($_POST['analyticsCookie']) && $_POST['analyticsCookie'] == 'on') {
        $result =     setcookie("bdgold_analyticsCookie", "enabled", $cookieOptions);
        #    echo $result ? '1Cookie set successfully' : 'Failed to set cookie';
    } else {
        $result =      setcookie("bdgold_analyticsCookie", "disabled", array_merge($cookieOptions, ['expires' => time() - 3600]));
        #    echo $result ? '1xCookie set successfully' : 'Failed to set cookie';
    }

    // Advertising Cookie
    if (isset($_POST['advertisingCookie']) && $_POST['advertisingCookie'] == 'on') {
        $result =     setcookie("bdgold_advertisingCookie", "enabled", $cookieOptions);

        #    echo $result ? '2Cookie set successfully' : 'Failed to set cookie';
    } else {
        $result =   setcookie("bdgold_advertisingCookie", "disabled", array_merge($cookieOptions, ['expires' => time() - 3600]));
        #    echo $result ? '3xCookie set successfully' : 'Failed to set cookie';
    }

    // Performance Cookie
    if (isset($_POST['performanceCookie']) && $_POST['performanceCookie'] == 'on') {
        $result =      setcookie("bdgold_performanceCookie", "enabled", $cookieOptions);

        #    echo $result ? '3Cookie set successfully' : 'Failed to set cookie';
    } else {
        $result =  setcookie("bdgold_performanceCookie", "disabled", array_merge($cookieOptions, ['expires' => time() - 3600]));
        #    echo $result ? '3xCookie set successfully' : 'Failed to set cookie';
    }

       // Set a cookie for the last set date
       setcookie("bdgold_lastSetDate", time(), $cookieOptions);

    #    }
    $errormessage = '<div class="alert alert-success">Your preferences have been saved.</div>';
    $transferpagedata['message'] = $errormessage;
    $transferpagedata['url'] = '/legalhub/cookies';
    $transferpagedata = $system->endpostpage($transferpagedata);

    # breakpoint(print_r($_POST,1). print_r($_COOKIE,1));
}




#-------------------------------------------------------------------------------
# DISPLAY THE  PAGE
#-------------------------------------------------------------------------------
$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

// Get the last set date from cookies if it exists
$lastSetDate = isset($_COOKIE['bdgold_lastSetDate']) ? date("F j, Y, g:i a", $_COOKIE['bdgold_lastSetDate']) : null;

?>
<!-- Navbar End -->

<!--  Start -->
<div class="container main-content">
    <div class="row">
        <div class="col">
            <h1>Manage Cookies</h1>
            <div class="mb-5">You can adjust your preferences of the cookies you'd like to enable or disable. Remember, disabling some cookies might affect your browsing or overall experience with our service.</div>

            <div class="card">
                <div class="card-header">
                    <h5>Manage Cookies</h5>
                    <?php if ($lastSetDate): ?>
                        <small class="text-muted">Your preferences for this device were last updated: <?php echo $lastSetDate; ?></small>
                    <?php endif; ?>
                </div>
                <div class="card-body p-5">
                    <form action="/legalhub/cookies" method="post" name="cookieForm" id="cookieForm">
                        <?PHP echo $display->inputcsrf_token(); ?>
                        <!-- Required/Operational Cookies -->
                        <div class="mb-5">
                        <h3 class="fw-bold">Required/Operational Cookies:</h3>
                            <p>
                                These cookies are essential for the seamless functionality and performance of the site. They help maintain user sessions, retain certain settings, and ensure essential features like shopping cart functionality or user authentication. Because these cookies are vital for birthday.gold's core functions, they cannot be opted out by users.
                            </p>
                            <div class="form-check form-switch">
                                <input type="checkbox" name="requiredCookie" class="form-check-input" id="requiredCookie" role="switch" checked disabled>
                                <label class="form-check-label" for="requiredCookie">Enable Required Cookies</label>
                            </div>
                        </div>

                        <!-- Analytics Cookies -->
                        <div class="mb-5">
                            <h3 class="fw-bold">Analytics Cookies:</h3>
                            <p>
                                Analytics cookies provide birthday.gold with insights into user behavior and interactions with their site. They can track metrics like page views, duration of visit, bounce rate, and more. By analyzing this data, we can identify what's working and what's not, allowing us to optimize the user experience. Importantly, these cookies don't personally identify individual users. Instead, we aggregate user data to provide a general overview of website performance.
                            </p>
                            <div class="form-check form-switch">
                                <input type="checkbox" name="analyticsCookie" class="form-check-input" id="analyticsCookie" role="switch" <?php echo (isset($_COOKIE['bdgold_analyticsCookie']) && $_COOKIE['bdgold_analyticsCookie'] == 'enabled') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="analyticsCookie">Enable Analytics Cookies</label>
                            </div>
                        </div>

                        <!-- Performance Cookies -->
                        <div class="mb-5">
                        <h3 class="fw-bold">Performance Cookies:</h3>
                            <p>
                                Performance cookies help our website load faster and offer a smoother user experience by remembering certain choices and settings made by visitors, such as language preference or font size. These cookies can also help birthday.gold to identify and fix technical issues, track website loading times, and monitor the performance of specific site features. Unlike required cookies, users might opt-out of these, but doing so may affect the website's performance and functionality.
                            </p>
                            <div class="form-check form-switch">
                                <input type="checkbox" name="performanceCookie" class="form-check-input" id="performanceCookie" role="switch" <?php echo (isset($_COOKIE['bdgold_performanceCookie']) && $_COOKIE['bdgold_performanceCookie'] == 'enabled') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="performanceCookie">Enable Performance Cookies</label>
                            </div>
                        </div>

                        <!-- Advertising Cookies -->
                        <div class="mb-5">
                        <h3 class="fw-bold">Advertising Cookies:</h3>
                            <p>
                                Advertising cookies are used to collect information about your browsing habits in order to make advertising more relevant to you and your interests. They are also used to limit the number of times you see an advertisement as well as help measure the effectiveness of an advertising campaign. These cookies are usually placed by advertising networks with the website operator’s permission. Remembering that you have visited a website and this information is shared with other organizations such as advertisers.
                            </p>
                            <div class="form-check form-switch">
                                <input type="checkbox" name="advertisingCookie" class="form-check-input" id="advertisingCookie" role="switch" <?php echo (isset($_COOKIE['bdgold_advertisingCookie']) && $_COOKIE['bdgold_advertisingCookie'] == 'enabled') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="advertisingCookie">Enable Advertising Cookies</label>
                            </div>
                        </div>

                        <!-- Save Settings and Enable All Cookies buttons -->
                        <div class="d-flex justify-content-start gap-2">
                            <input type="submit" name="saveSettings" value="Save Settings" class="btn btn-primary">
                            <button type="button" class="btn btn-success" onclick="enableAllCookies()">Enable All Cookies</button>
                        </div>
                    </form>
                </div>

                <div class="card-footer"><small class="caption  float-right">Learn more at: <a href="<?php echo $cookieinfolink; ?>" class="text-dark" target="_blank">www.allaboutcookies.org</a></small></div>
            </div>
        </div>
    </div>
</div>


<!-- End -->

<!-- JavaScript to handle the Enable All Cookies button -->
<script>
    function enableAllCookies() {
        document.getElementById('analyticsCookie').checked = true;
        document.getElementById('performanceCookie').checked = true;
        document.getElementById('advertisingCookie').checked = true;

        // Programmatically submit the form
        document.getElementById('cookieForm').submit();
    }
</script>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
