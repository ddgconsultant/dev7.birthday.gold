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
  foreach ($deviceList as $device_id) {
      if (!empty($device_id)) {
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
.row-hover:hover {
background-color: #f8f9fa; /* gray color */
}          
</style>
';


echo '
<div class="container main-content mt-0 pt-0">
<div class="row">
';
echo '<div class="mb-3">
<h2 class="text-primary">Your Login History</h2>
</div>
';

$device_result = $account->user_activedevices($workinguserdata['user_id']);
if (!empty($device_result)) {
  echo '<div class="mb-3">';

  $loginHistoryClass = ($displaysection === '') ? 'btn btn-primary me-2' : 'btn btn-secondary me-2';
  $savedDevicesClass = ($displaysection === 'devices') ? 'btn btn-primary' : 'btn btn-secondary';

  echo '<a href="/myaccount/loginhistory" class="' . $loginHistoryClass . '">LOGIN HISTORY</a>';
  echo '<a href="/myaccount/loginhistory?view=devices" class="' . $savedDevicesClass . '">TRUSTED DEVICES</a>';
  echo '</div>';
} else {
  $displaysection = '';
}



switch ($displaysection) {
// --------------------------
  case 'devices':
  include('module_login/manage_devices.inc');
    break;

// --------------------------
  default:
  include('module_login/manage_history.inc');
    break;
}



echo '  </div>
</div>
</div>
';

echo '  </div>
</div>
</div>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
