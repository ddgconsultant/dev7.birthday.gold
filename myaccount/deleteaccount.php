<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$testmode = false;

#-------------------------------------------------------------------------------
# HANDLE THE PROFILE DELETE ATTEMPT
#-------------------------------------------------------------------------------
$deletinput = 'delete';
if (!empty($_POST['deleteConfirm']))  $deletinput = str_replace('"', '', strtolower($_POST['deleteConfirm']));


// Check if the form was submitted and if 'deleteConfirm' was posted
if ($app->formposted() || $testmode) {
    // Check if 'deleteConfirm' value is 'delete'
    if ($deletinput === 'delete' || $testmode) {

        // Fetch the current user's ID (Replace this with your actual logic)
        $currentUserId = $current_user_data['user_id'];  // Assuming the user_id is stored in session

        $enrollmentdata = $account->getenrollmentlistcounts($currentUserId);
        if (!$testmode) {
            // Prepare and execute the update query
            $sql = "UPDATE bg_users SET status = :status , modify_dt=now() WHERE user_id = :id";
            $stmt = $database->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $currentUserId);

            $status = 'deleted';
            $stmt->execute();
        }

        #  $enrollmentdata = $account->getenrollmentlistcounts($currentUserId);
        #   breakpoint($enrollmentdata);
        if ($account->isimpersonator()) {
            $returntouser =   $session->get('impersonator', '');
            $url =     '/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($returntouser['user_id']) . '&aid=' . $qik->encodeId($returntouser['user_id']) . '&revertimpersonation=1&_token=' . $display->inputcsrf_token('tokenonly');
            #$account->logout();
            unset($current_user_data);
            $transferpage['message'] = 'Invalid request method to delete account.';
            $transferpage['url'] =  $url;
            $system->endpostpage($transferpage);
            exit;
        }
        unset($current_user_data);

        #   include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
        include($dir['core_components'] . '/bg_pagestart.inc');
        include($dir['core_components'] . '/bg_header.inc');

        echo '
<!-- Navbar End -->


<!-- 404 Start -->
<div class="container-xxl py-6">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg-8">
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f622/512.webp" type="image/webp">
<img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f622/512.gif" alt="😢" width="64" height="64">
</picture>
<h1 class="mb-4">Account Deleted</h1>
<h5 class="mb-4">We are sad to see you go.</h5>

<p class="mb-4">Your account has been deleted successfully.  </p>
';

        switch ($enrollmentdata['success']) {
            case 0:
                echo '    <!--   <p class="mb-4">During your time with us, we did not enroll you in any businesses.</p> -->
';
                break;

            default:
                echo '   <p class="mb-4">During your time with us we successfully enrolled you in ' . $enrollmentdata['success'] . ' businesses.
Please know birthday.gold that has no ability to manage those account and you responsible to individually manage the accounts with those businesses.</p>
';
                break;
        }
        echo '
<p class="mb-4">You have been logged out.</p>
</div>
</div>
</div>
</div>
';


        // Log the user out or redirect to a different page if needed

        #  include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
        echo '</div></div></div>';
        // Footer includes
        $display_footertype = 'min';
        include($dir['core_components'] . '/bg_footer.inc');
        $app->outputpage();
        $account->logout();
    } else {
        echo 'You must type "delete" to delete your account.';
    }
} else {
    # echo 'Invalid request method.';

    $account->logout();
    $transferpage['message'] = 'Invalid request method to delete account.';
    $transferpage['url'] = '/login';
    $system->endpostpage($transferpage);

    #header('location: /login');
    exit;
}
