<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');




#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$email = $_REQUEST['e'] ?? '';



#-------------------------------------------------------------------------------
# HANDLE ACTIONS
#-------------------------------------------------------------------------------
if ($formdata = $app->formposted()) {
    // Validate email
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sql =    "INSERT INTO unsubscribe_emails (email) VALUES (:email)";
        $stmt = $database->query($sql, [':email' => $email]);

        $lastId = $database->lastInsertId();

    }
}


    #-------------------------------------------------------------------------------
    # DISPLAY PAGE
    #-------------------------------------------------------------------------------
    $headerattribute['additionalcss'] = '';

    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');
    echo '


    <!-- Unsubscribe Start -->
    <div class="container  main-content ">

        <div class="container py-5 text-center card">
            <div class="row justify-content-center">
                <div class="col">
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f622/512.webp" type="image/webp">
  <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f622/512.gif" alt="😢" width="64" height="64">
</picture>

';
if (!empty($lastId)) {
    echo '
    <h1 class="display-1">You\'ve Been Unsubscribed</h1>
    <p class="mb-4">We are sorry to see you go!</p>
    <p class="mt-6">Please know that our system sometimes sends messages that are already scheduled to be delivered.</p>
    <p class="">Trust that we will not send you newsletters, announcements, and promotional offers email after 24 hours from now.</p>
<p class="my-3 py-6">
<p class="text-secondary">Confirmation Identifier: '.$qik->encodeId($lastId).'</p>
</p>
';

} else {
echo '
                    <h1 class="display-1">Unsubscribe</h1>
                    <h1 class="mb-4">from Our Emails</h1>
                    <p class="mb-4">We are sorry to see you go! If you unsubscribe, you will stop receiving newsletters, announcements, and promotional offers emails from us.</p>
                    <p class="mb-4">If you have an active account on our site, we will still send you account management related message only.</p>
                    <div class="row justify-content-center">
                     <div class="col-6">
                        <form method="post" id="mainform" action="/unsubscribe">                        
                        ' . $display->inputcsrf_token() . '
                        <div class="mb-3">
                            <label for="emailInput" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="emailInput" name="email" placeholder="Enter your email" required value="' . $email . '">
                        </div>
                        <div class="">
                            <button type="submit" id="mainsubmit" class="btn btn-secondary">Confirm Unsubscribe</button>
                        </div>
                        </form>
                    </div>
                    </div>
';
}
echo '               
                </div>
            </div>
               
        </div>
          <a class="btn btn-primary my-5 py- px-5" href="/">Go Back To Home</a>
    </div>

';


echo $display->submitbuttoncolorjs('mainform');
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();