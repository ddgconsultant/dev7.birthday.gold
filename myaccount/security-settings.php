<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


include($dir['core_components'] . '/bg_user_profileheader.inc');

include($dir['core_components'] . '/bg_user_leftpanel.inc');
?>

<div class="container main-content mt-0 py-0">
    <h2 class="mb-4">Security Settings</h2>

    <div class="card mb-3">
        <div class="card-header">
            <h5>Password Settings</h5>
        </div>
        <div class="card-body">
            <p>It's recommended to update your password regularly to enhance account security.</p>
            <a href="/myaccount/changepassword" class="btn btn-primary">Change Password</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5>Two-Factor Authentication (2FA)</h5>
        </div>
        <div class="card-body">
            <p>Enable 2FA to add an extra layer of security to your account.</p>
            <a href="/myaccount/security-2fa" class="btn btn-success">Enable 2FA</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5>Account Activity</h5>
        </div>
        <div class="card-body">
            <p>View recent account activity.</p>
            <a href="/myaccount/loginhistory" class="btn btn-secondary">View Activity</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5>Trusted Devices</h5>
        </div>
        <div class="card-body">
            <p>Manage devices that you've marked as trusted for easier login experiences.</p>
            <a href="/myaccount/loginhistory?view=devices" class="btn btn-info">Manage Trusted Devices</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5>Security Questions</h5>
        </div>
        <div class="card-body">
            <p>Set up or update your security questions to secure account recovery options.</p>
            <a href="/myaccount/security-questions" class="btn btn-warning">Update Security Questions</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5>Account Deletion</h5>
        </div>
        <div class="card-body">
            <p>If you no longer want to use this service, you can delete your account. Note that this action is irreversible.</p>
            <a href="#" class="btn btn-danger">Delete Account</a>
        </div>
    </div>
</div>
</div>
</div></div>
<?php

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();