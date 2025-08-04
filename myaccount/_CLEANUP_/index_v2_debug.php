<?PHP
$addClasses[] = 'Mail';
$addClasses[] = 'TimeClock';
$addClasses[] = 'fileuploader';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Basic debug output
echo "<!-- Debug: Site controller loaded -->\n";

$businessselectorurl = '/myaccount/businessselect';

$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$transferpagedata = [];

echo "<!-- Debug: User data loaded -->\n";

$uploadTmpDir = $_SERVER['DOCUMENT_ROOT'] . '/public/uploads/';

// Create directory if it doesn't exist
if (!file_exists($uploadTmpDir)) {
  mkdir($uploadTmpDir, 0777, true);
}

#-------------------------------------------------------------------------------
# HANDLE FIRST PROFILE VISIT
#-------------------------------------------------------------------------------
$response = $account->getUserAttribute($current_user_data['user_id'], 'first_profile_visit');
if (!$response && $current_user_data['account_type'] != 'minor') {

  switch ($current_user_data['account_type']) {
    case 'giftcertificate':
      header('location: /setup-giftcertificate');
      break;

    default:
      header('location: /myaccount/myaccount_actions/setup-individual');
      exit;
  }
}

echo "<!-- Debug: First visit check passed -->\n";

$till = $app->getTimeTilBirthday($current_user_data['birthdate']);
$session->unset('display_birthday_banner');
$display_birthday_link=false;

#-------------------------------------------------------------------------------
# HANDLE BIRTHDAY NOTIFICATION
#-------------------------------------------------------------------------------
if ($till['days']==0) {
  $response = $account->getUserAttribute($current_user_data['user_id'], 'myaccount_redirect_happybirthday_'.date('Y'));

  if (!$response) {
    $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt, start_dt, end_dt)
    VALUES (:user_id, 'page_redirect', 'myaccount_redirect_happybirthday_".date('Y')."', '/myaccount/happy-birthday-to-you', 'active', 100, NOW(), NOW(), '".date('Y')."-01-01', '".date('Y')."-12-31 23:59:59')";
    $stmt = $database->query($sql, [':user_id' => $current_user_data['user_id']]);   
  }
  $session->set('display_birthday_banner', true);
  $display_birthday_link=true;
}

echo "<!-- Debug: Birthday check passed -->\n";

#-------------------------------------------------------------------------------
# HANDLE ATTRIBUTE REDIRECT
#-------------------------------------------------------------------------------
$sql = "SELECT description FROM bg_user_attributes WHERE user_id = :user_id AND `name` like 'myaccount_redirect%' AND `status`='active' limit 1";
$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $current_user_data['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
  header('location: ' . $result['description']);
  exit;
}

echo "<!-- Debug: Redirect check passed -->\n";

// Basic page setup
$bodycontentclass = '';
$header_spacer='60px';
$page_title = "My Account - Birthday Gold";
$page_description = "Manage your Birthday Gold account and birthday rewards";

echo "<!-- Debug: About to include pagestart -->\n";

include($dir['core_components'] . '/bg_pagestart.inc');

echo "<!-- Debug: Pagestart included -->\n";
?>

<h1>Debug Version - If you see this, the basic page is working</h1>
<p>User: <?php echo htmlspecialchars($current_user_data['first_name'] . ' ' . $current_user_data['last_name']); ?></p>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>