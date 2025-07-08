<?PHP
$addClasses[] = 'agentparser';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------

$p_displaylength = 30;

$workingUser = $current_user_data['user_id'];

if (isset($_REQUEST['uid'])) {
  $workingUser = $_REQUEST['uid'];

  // The rest of your code that depends on having a $workingUser and $workingUserData
  #  breakpoint($workinguserdata);
}

$workinguserdata = $account->getuserdata($workingUser, 'user_id');
$displaysection = $_REQUEST['view'] ?? '';



#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
  if (isset($_POST['formtype']) && ($_POST['formtype'] == 'changedisplaylength')) {
    $p_displaylength = $_POST['displaylength'];
  }
}



#-------------------------------------------------------------------------------
# HANDLE THE DELETE DEVICE
#-------------------------------------------------------------------------------
if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'delete') && ($app->formposted('GET') || $app->formposted())) {      
  // Retrieve the list of devices to delete
  $deviceList = explode(',', $_REQUEST['devicelist']);
  #breakpoint($deviceList);
  
  // Get current device ID from cookies to check if we need to clear cookies
  $current_device_id = $_COOKIE['bg_device_id'] ?? $_COOKIE['bgdeviceid'] ?? null;
  $should_clear_cookies = false;
  
  foreach ($deviceList as $device_id) {
      if (!empty($device_id)) {
          // Check if this is the current device
          if ($current_device_id === $device_id) {
              $should_clear_cookies = true;
          }
          
          // Mark the device as deleted in bg_user_attributes
          $sql = "UPDATE bg_user_attributes SET `status`='deleted', modify_dt=NOW() 
                  WHERE user_id=:user_id AND type='bg_rememberme_set' AND name=:device_id AND `status`='A'";
          $stmt = $database->prepare($sql);
          $stmt->execute([':user_id' => $current_user_data['user_id'], ':device_id' => $device_id]);

          // Mark the device as deleted in bg_validations
          $sql = "UPDATE bg_validations SET `status`='deleted', modify_dt=NOW() 
                  WHERE user_id=:user_id AND device_id=:device_id AND validation_type='bgrememberme_autologin'  and `status`='cookie'";
          $stmt = $database->prepare($sql);
          $stmt->execute([':user_id' => $current_user_data['user_id'], ':device_id' => $device_id]);
      }
  }
  
  // If the current device was deleted, clear the rememberme cookies
  if ($should_clear_cookies) {
      $account->clearRememberMeCookies();
  }

  $goto = '/myaccount/loginhistory';
  if (!empty($displaysection)) {
      $goto .= '?view=' . $displaysection;
  }
  header('location: ' . $goto);
  exit;
}



#-------------------------------------------------------------------------------
# HANDLE THE DELETE HISTORY
#-------------------------------------------------------------------------------
if ($app->formposted('GET')) {
  if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'deletehistory') {

    $sql = "update bg_logintracking set `status`='X', modify_dt=now() WHERE user_id = " . $workinguserdata['user_id'] . " and `status`='A'  and `site`='" . $site . "' ";

    $stmt = $database->prepare($sql);
    $stmt->execute();
  }
  $goto = '/myaccount/loginhistory';
  if (!empty($displaysection)) $goto .= '?view=' . $displaysection;
  header('location: ' . $goto);
  exit;
}



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');
$additionalstyles .= '
<style>
/* Modern minimal design for login history */
.login-container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Tab navigation with active bottom border */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    overflow: hidden;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background: none;
}

.nav-tab-item.active::after {
    content: "";
    position: absolute;
    bottom: -3px;
    left: 0;
    right: 0;
    height: 3px;
    background: #0d6efd;
    z-index: 1;
}

/* Settings tab aligned to the right */
.nav-tab-item.settings-tab {
    margin-left: auto;
}

/* Compact device cards for mobile */
.device-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
    position: relative;
}

.device-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-1px);
}

.device-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.device-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 1.5rem;
    color: #495057;
    flex-shrink: 0;
}

.device-icon.current {
    background: #d1ecf1;
    color: #0c5460;
}

.device-info {
    margin-left: 1rem;
    flex: 1;
}

.device-name {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.25rem;
    color: #212529;
}

.device-meta {
    font-size: 0.875rem;
    color: #6c757d;
}

.device-details {
    margin-bottom: 1rem;
}

.detail-row {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    color: #495057;
    padding: 0.25rem 0;
}

.detail-label {
    font-weight: 500;
    margin-right: 0.5rem;
    color: #6c757d;
    min-width: 60px;
}

.device-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.current-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: #0dcaf0;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .nav-tab-item {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }
    
    .device-card {
        padding: 0.875rem;
    }
    
    .device-icon {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
    
    .detail-row {
        font-size: 0.813rem;
    }
    
    .detail-label {
        min-width: 50px;
    }
}

/* Remove Bootstrap default styles that might interfere */
.nav-tabs {
    border-bottom: none;
}

.nav-link {
    border: none;
}

/* Ensure active border shows on all screen sizes */
@media (max-width: 991px) {
    .nav-tab-item.active {
        border-bottom-color: #0d6efd;
    }
    
    .nav-tab-item.active::after {
        content: "";
        position: absolute;
        bottom: -3px;
        left: 0;
        right: 0;
        height: 3px;
        background: #0d6efd;
        z-index: 1;
    }
}
</style>
';


echo '<div class="col-12 col-lg-9">
<div class="login-container">
';

// Header
echo '<div class="mb-4">
<h2 class="fw-bold mb-1">Login Activity</h2>
<p class="text-muted mb-0">Monitor access to your account and manage trusted devices</p>
</div>
';

// Tab navigation
$device_result = $account->user_activedevices($workinguserdata['user_id']);
echo '<nav class="nav-tabs-modern">';

$loginHistoryActive = ($displaysection === '') ? 'active' : '';
$devicesActive = ($displaysection === 'devices') ? 'active' : '';
$settingsActive = ($displaysection === 'settings') ? 'active' : '';

echo '<a href="/myaccount/loginhistory" class="nav-tab-item ' . $loginHistoryActive . '">
        <i class="bi bi-clock-history me-2"></i>Login History
      </a>';

if (!empty($device_result)) {
    echo '<a href="/myaccount/loginhistory?view=devices" class="nav-tab-item ' . $devicesActive . '">
            <i class="bi bi-shield-check me-2"></i>Trusted Devices
          </a>';
}

echo '<a href="/myaccount/loginhistory?view=settings" class="nav-tab-item settings-tab ' . $settingsActive . '">
        <i class="bi bi-gear"></i>
      </a>';

echo '</nav>';



switch ($displaysection) {
// --------------------------
  case 'devices':
    include('module_login/manage_devices_modern.inc');
    break;

// --------------------------
  case 'settings':
    echo '<div class="settings-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-gear me-2"></i>Login Settings
                </h5>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Security Preferences</h6>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="emailAlerts" checked>
                        <label class="form-check-label" for="emailAlerts">
                            Email me when a new device logs in
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="locationTracking" checked>
                        <label class="form-check-label" for="locationTracking">
                            Track login locations
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="deviceRemember">
                        <label class="form-check-label" for="deviceRemember">
                            Remember devices for 30 days
                        </label>
                    </div>
                </div>
            </div>
          </div>';
    break;

// --------------------------
  default:
    include('module_login/manage_history_timeline.inc');
    break;
}



echo '</div>'; // Close login-container
echo '</div>'; // Close col-12 col-lg-9

echo '  </div>
</div>
</div>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
