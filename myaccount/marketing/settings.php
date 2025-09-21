<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Settings";

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// Get user settings
$user_settings = [];
$user_settings['marketing_email_notifications'] = $account->getUserAttribute($current_user_data['user_id'], 'marketing_email_notifications');
$user_settings['marketing_weekly_reports'] = $account->getUserAttribute($current_user_data['user_id'], 'marketing_weekly_reports');
$user_settings['marketing_default_sender'] = $account->getUserAttribute($current_user_data['user_id'], 'marketing_default_sender');
$user_settings['marketing_default_from_email'] = $account->getUserAttribute($current_user_data['user_id'], 'marketing_default_from_email');

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $update_data = [];
    
    if (isset($_POST['email_notifications'])) {
        $update_data['marketing_email_notifications'] = $_POST['email_notifications'] == '1' ? '1' : '0';
    }
    
    if (isset($_POST['weekly_reports'])) {
        $update_data['marketing_weekly_reports'] = $_POST['weekly_reports'] == '1' ? '1' : '0';
    }
    
    if (isset($_POST['default_sender'])) {
        $update_data['marketing_default_sender'] = trim($_POST['default_sender']);
    }
    
    if (isset($_POST['default_from_email'])) {
        $update_data['marketing_default_from_email'] = trim($_POST['default_from_email']);
    }
    
    if (!empty($update_data)) {
        $account->setUserAttribute($current_user_data['user_id'], $update_data);
        $app->notify('Settings updated successfully', 'success');
    }
    
    header('Location: /myaccount/marketing/settings.php');
    exit;
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
body {
    margin-bottom: 100px !important;
}
</style>
';

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-gear"></i> Marketing Settings</h1>
        <p class="lead">Configure your marketing preferences and defaults</p>
    </div>
</div>';

include('nav.inc.php');

echo '
<div class="container mt-4">';

echo '
  <div class="card">
      <div class="card-body">
          <form method="POST">
              <div class="row">
                  <div class="col-md-6">
                      <h5>Notification Preferences</h5>
                      <div class="form-check mb-3">
                          <input class="form-check-input" type="checkbox" name="email_notifications" id="emailNotifications" value="1"' . (($user_settings['marketing_email_notifications'] ?? '1') == '1' ? ' checked' : '') . '>
                          <label class="form-check-label" for="emailNotifications">
                              Email notifications for campaign updates
                          </label>
                      </div>
                      <div class="form-check mb-3">
                          <input class="form-check-input" type="checkbox" name="weekly_reports" id="weeklyReports" value="1"' . (($user_settings['marketing_weekly_reports'] ?? '1') == '1' ? ' checked' : '') . '>
                          <label class="form-check-label" for="weeklyReports">
                              Weekly performance reports
                          </label>
                      </div>
                  </div>
                  <div class="col-md-6">
                      <h5>Default Campaign Settings</h5>
                      <div class="mb-3">
                          <label for="defaultSender" class="form-label">Default Sender Name</label>
                          <input type="text" class="form-control" name="default_sender" id="defaultSender" value="' . htmlspecialchars($user_settings['marketing_default_sender'] ?? $current_user_data['display_name'] ?? '') . '">
                      </div>
                      <div class="mb-3">
                          <label for="defaultFromEmail" class="form-label">Default From Email</label>
                          <input type="email" class="form-control" name="default_from_email" id="defaultFromEmail" value="' . htmlspecialchars($user_settings['marketing_default_from_email'] ?? $current_user_data['email'] ?? '') . '">
                      </div>
                  </div>
              </div>
              
              <hr>
              
              <div class="row">
                  <div class="col-12">
                      <div class="d-flex gap-2">
                          <button type="submit" class="btn btn-primary">Save Settings</button>
                          <a href="/myaccount/marketing/" class="btn btn-outline-secondary">Cancel</a>
                      </div>
                  </div>
              </div>
          </form>
      </div>
  </div>
</div>
  </div></div></div>';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();