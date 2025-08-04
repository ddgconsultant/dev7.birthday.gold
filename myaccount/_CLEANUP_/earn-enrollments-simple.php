<?php
/**
 * Earn More Enrollments Page - Simplified Version
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login');
    exit;
}

// Get user data
$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

// Page setup
$pagetitle = 'Earn More Enrollments';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content">
    <h1>Earn More Enrollments</h1>
    <p>This is a test page to check if the encoding error persists.</p>
    
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Your current user ID is: <?php echo $user_id; ?>
    </div>
    
    <a href="/myaccount" class="btn btn-primary">Back to My Account</a>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>