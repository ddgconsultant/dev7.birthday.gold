<?PHP
$addClasses[] = 'Mail';
$addClasses[] = 'TimeClock';
$addClasses[] = 'fileuploader';
#$addClasses[] = 'cdn';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#$businessselectorurl = '/myaccount/select';
#if ($current_user_data['username'] == 'ddgconsultant') 
$businessselectorurl = '/myaccount/businessselect';

$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$transferpagedata = [];

// Get account statistics for consistent dashboard display
$accountstats = $account->account_getstats();

// Feature flags for showing/hiding panels
$show_enrollment_summary = false;  // Set to true to show the Enrollment Summary panel
$show_profile_completion = false;  // Set to true to show the Profile Completion panel

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
      // Only send paid plans to createemail flow, free plans go to profile
      if (in_array($current_user_data['account_plan'], ['free', 'user_free'])) {
        header('location: /myaccount/profile');
      } else {
        header('location: /myaccount/myaccount_actions/setup-individual'); #/myaccount-welcome');
      }
      exit;
  }
}

#-------------------------------------------------------------------------------
# HANDLE FEATURE INITIALIZATION (for paid/upgraded accounts)
#-------------------------------------------------------------------------------
// FEATURE CHECKING CONTROL FLAGS
$FEATURE_CHECKING_ENABLED = false;    // Set to false to disable feature checking globally
$FEATURE_CHECKING_DEV_ONLY = false;   // Set to true to enable only for $mode='dev'

// Determine if we should run feature checking
$should_check_features = false;

if ($FEATURE_CHECKING_ENABLED) {
    if ($FEATURE_CHECKING_DEV_ONLY) {
        // Only run in dev mode
        $should_check_features = ($mode === 'dev');
    } else {
        // Feature checking is enabled globally (not restricted to dev)
        $should_check_features = true;
    }
}

// Check if user has product features that need initialization
// This runs after first profile visit and handles both new signups and upgrades
if ($should_check_features && $current_user_data['account_type'] != 'minor') {
    include($_SERVER['DOCUMENT_ROOT'].'/myaccount/setup-features.php');
    // If setup-features redirects to a feature component, execution stops there
    // Otherwise, continue with normal index.php flow
}



$till = $app->getTimeTilBirthday($current_user_data['birthdate']);
$display_birthday_link=false;

#-------------------------------------------------------------------------------
# HANDLE BIRTHDAY NOTIFICATION
#-------------------------------------------------------------------------------
if ($till['days']==0) {
  // It's their birthday!
  $response = $account->getUserAttribute($current_user_data['user_id'], 'myaccount_redirect_happybirthday_'.date('Y'));

  if (!$response) {
    $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt, start_dt, end_dt)
    VALUES (:user_id, 'page_redirect', 'myaccount_redirect_happybirthday_".date('Y')."', '/myaccount/happy-birthday-to-you', 'active', 100, NOW(), NOW(), '".date('Y')."-01-01', '".date('Y')."-12-31 23:59:59')";
    $stmt = $database->query($sql, [':user_id' => $current_user_data['user_id']]);
  }
  $session->set('display_birthday_banner', true);
  $display_birthday_link=true;
} else {
  // It's NOT their birthday - ensure the banner is cleared
  $session->unset('display_birthday_banner');
}




#-------------------------------------------------------------------------------
# HANDLE ATTRIBUTE REDIRECT
#-------------------------------------------------------------------------------
$sql = "SELECT description FROM bg_user_attributes WHERE user_id = :user_id AND `name` like 'myaccount_redirect%' AND `status`='active' limit 1";
$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $current_user_data['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
  header('location: ' . $result['description']); #/myaccount-welcome');
  exit;
}



#-------------------------------------------------------------------------------
# HANDLE THE A REFRESH REQUEST
#-------------------------------------------------------------------------------
if ($app->formposted('GET')) {
  if (isset($_GET['refresh'])) {
    $current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
    header('location: /myaccount/');
    exit;
  }
}


/*
#-------------------------------------------------------------------------------
# HANDLE THE AVATAR UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted('token')) {
  if (isset($_FILES['profile-image'])) {

    session_tracking('avatarimageupload', $_FILES);
    $errors = [];
    $file_name = $_FILES['profile-image']['name'];
    $file_size = $_FILES['profile-image']['size'];
    $file_tmp = $_FILES['profile-image']['tmp_name'];
    $file_type = $_FILES['profile-image']['type'];
    $explodedFileName = explode('.', $_FILES['profile-image']['name']);
    $file_ext = strtolower(end($explodedFileName));

    $extensions = array("jpeg", "jpg", "png");

    if (in_array($file_ext, $extensions) === false) {
      $errors[] = "Extension not allowed, please choose a JPEG or PNG file.";
    }

    if ($file_size > 5 * 1024 * 1024) {
      $errors[] = 'File size must be less than 5 MB';
    }

    if (empty($errors)) {
      // Create a unique hashed filename based on the user_id and a random string
      $randomString = bin2hex(random_bytes(10)); // generates a random string
      $hashedFileName = hash('sha256', $current_user_data['user_id'] . $randomString) . '.' . $file_ext;

      $uploads_dir = realpath(__DIR__ . '/../../cdn.birthday.gold/public/useravatars');

      $filePath = $uploads_dir . "/" . $hashedFileName;
      $search = ['../', 'W:\\BIRTHDAY_SERVER\\'];
      $dbsavelocation = str_replace($search, '//', $uploads_dir) . "/" . $hashedFileName;
      $updatefields = ['avatar' => $dbsavelocation];


      $result = $fileuploader->uploadFile($_FILES['profile-image'], 'public/useravatars/' . $hashedFileName, $current_user_data['user_id']);

      if ($result['success']) {
        $updatefields = ['avatar' => '//files.birthday.gold/' . $result['file_path']];
        #  echo 'File uploaded successfully: ' . $result['file_path'];
      } else {
        #   echo 'Error: ' . $result['message'];
      }

      $account->updateSettings($current_user_data['user_id'], $updatefields);
      $current_user_data = $account->getuserdata($current_user_data['username']);
    } else {
      // print_r($errors);
    }
  }
}
*/



#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
$pageoutput = '';

$transferpagedata = $system->startpostpage($transferpagedata);
$pageoutput .= '' . $display->formaterrormessage($transferpagedata['message']);



$birthdates = $account->getBirthdates($current_user_data['birthdate']);
#$astroicon=$app->getZodiacInfo($astrosign);
$planname = $app->plandetail();
#$daysalive=$app->calculateDaysAlive($current_user_data['birthdate']);
$alive = $app->calculateage($current_user_data['birthdate']);
$avatar = '/public/images/defaultavatar.png';
$avatarbuttontag = 'Upload';
if (!empty($current_user_data['avatar'])) {
  $avatar = $current_user_data['avatar'];
  $avatarbuttontag = 'Change';
}


if ($current_user_data['account_type'] == 'minor') $minorbg = 'bg-info-subtle';
else $minorbg = '';


$bodycontentclass = '';
$header_spacer='60px';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');


$additionalstyles .= "
<style >
.min-vh-25 { min-height: 15vh !important; }
.funfacthandler .card { border: none; }
.funfacthandler:nth-child(2) .card { border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
/* General styling for large screens */
.icon-small { font-size: 2rem; }
.h2-small { font-size: 1.5rem; }
/* Hide left panel on screens 1199px and below (below xl breakpoint) */
@media (max-width: 1199.98px) {
    .border-right.d-none.d-md-block {
        display: none !important;
    }
}
/* White background for action buttons */
.content-block {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 2rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.content-block:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}
@media (max-width:767.98px) {
/* Reduce icon size */
.icon-small { font-size: 2rem !important; margin-right: 0.5rem; vertical-align: baseline; }
/* Specific adjustment for list-check icon */
.bi-list-check.icon-small { 
    position: relative;
    top: 3px; /* Slight adjustment to align with other icons */
}
.negative-margin-top { margin-top: -40px !important;         /* Adjust the value as needed */ }
/* Reduce heading size and move it to the right of the icon */
.h2-small { 
    font-size: 1.3rem; 
    display: inline-block; 
    margin-left: 0.5rem; 
    vertical-align: baseline;
    margin-bottom: -0.25rem !important; /* Pull label down closer to caption */
}
/* Container for icon + label to ensure alignment */
.content-block > div:first-child {
    width: 250px !important; /* Fixed width for icon + CELEBRATE */
    margin-left: 10px !important; /* Same left margin for all */
    text-align: left !important;
}
/* Adjust the flex container and reduce padding/height */
.content-block { 
    text-align: left; 
    display: flex; 
    align-items: center; 
    padding: 1rem 1rem 0.5rem 1rem !important; /* More top padding, less bottom */
    min-height: auto !important;
}
/* Disable hover transform on mobile */
.content-block:hover {
    transform: none !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
}
/* Make the block height smaller */
.content-block p { 
    font-size: 0.85rem; 
    margin-top: 0.25rem; 
    margin-bottom: 0.25rem !important;
    line-height: 1.2;
}
.content-block span.more-link { font-size: 0.85rem; }
/* Reduce spacing between blocks on mobile */
.col-lg-4.col-md-12 {
    margin-bottom: 0.25rem;
}
}


.no-border a {
    border: none; /* Ensure no borders are present */
    padding: 8px 0; /* Fine-tune the padding */
}

</style >
";



echo '
<div class="content container main-content mt-0 py-0 negative-margin-top">
';

$create_dt = new DateTime($current_user_data['create_dt']);
$now = new DateTime();
$interval = $now->diff($create_dt);


echo '
<div class="row pt-lg-3 my-0">
<div class="col-12  my-0">
';


if ($interval->days <= 30) {
  echo '<h1 class="d-none d-lg-block mb-4 mt-0 pt-0">Your Account Home</h1>';
}
/*================================================================================================
==================================================================================================*/

// account alert messages


$message = $account->getaccountmessages();
if (!empty($message))  echo $message;

if($display_birthday_link) {
  echo '<div class="content-panel bg-success-subtle">
<a href="/myaccount/happy-birthday-to-you" class="fw-bold m-0 p-0 text-decorations-none h3" >
<i class="bi bi-cake-fill mx-2"></i> View your special birthday message again.
</a>
</div>
';
}

// big buttons  - collect, redeem, celebrate
$additionalstyles.='
<style>
.phrase-center {
  text-align: center;
  margin-top: 10px; /* Adjust spacing as needed */
  font-weight:200;
}
.more-link {
  margin-left: auto; /* Ensures it is aligned to the bottom-right */
  text-decoration: underline;
}
/* Mobile adjustments for phrase-center */
@media (max-width:767.98px) {
  .phrase-center {
    margin-top: -2px !important; /* Reduced gap from label */
    margin-bottom: 2px !important;
    font-size: 1.2rem !important; /* Increased from 0.8rem */
  }
}
</style>
';

echo '
<div class="container my-0 py-0">
<div class="row d-flex align-items-stretch  my-0">

<!-- Collect Block -->
<div class="col-lg-4 col-md-12 d-flex">
  <a href="/myaccount/enrollment-picker" class="content-block flex-fill text-center text-decoration-none d-flex flex-column justify-content-between">
    <div>
      <i class="bi bi-list-check icon-small"></i>
      <h2 class="h2-small">PICK</h2>
      <p class="mb-2 phrase-center">Choose from '.$website['numberofbiz'].'+ '.$website['biznames'].'.</p>
    </div>
    <div class="mt-auto d-flex justify-content-between align-items-center w-100">
      <span class="more-link">Select ></span>
    </div>
  </a>
</div>


<!-- Redeem Block -->
<div class="col-lg-4 col-md-12 d-flex">
  <a href="/myaccount/redeem" class="content-block flex-fill text-center text-decoration-none d-flex flex-column justify-content-between">
    <div>
      <i class="bi bi-gift icon-small"></i>
      <h2 class="h2-small">REDEEM</h2>
      <p class="mb-2 phrase-center">You have ' . (count($user_reward_results ?? [])) . ' rewards.</p>
    </div>
    <div class="mt-auto d-flex justify-content-between align-items-center w-100">
      <span class="more-link">View ></span>
    </div>
  </a>
</div>


<!-- Celebrate Block -->
<div class="col-lg-4 col-md-12 d-flex">
  <a href="/myaccount/celebrate" class="content-block flex-fill text-center text-decoration-none d-flex flex-column justify-content-between">
    <div>
      <i class="bi bi-cake icon-small"></i>
      <h2 class="h2-small">CELEBRATE</h2>
      <p class="mb-2 phrase-center">' . $qik->plural2($till['days'], 'day') . ' away.</p>
    </div>
    <div class="mt-auto d-flex justify-content-between align-items-center w-100">
      <span class="more-link">More ></span>
    </div>
  </a>
</div>


</div>
</div>
';




echo '
<div class="container mt-4">
<div class="row">
';

// -------------------------------------------------------

// Set up the content based on conditions
// First check if there are any failed enrollments due to missing data
$failed_enrollments_query = "SELECT COUNT(*) as failed_count 
                           FROM bg_user_companies 
                           WHERE user_id = :user_id 
                           AND `status` = 'failed' 
                           AND reason = 'Missing Data Element'";

$stmt = $database->prepare($failed_enrollments_query);
$stmt->execute(['user_id' => $current_user_data['user_id']]);
$failed_count = $stmt->fetch()['failed_count'];
#breakpoint($current_user_data);
// Prepare the content template
$content_profilecompletion = '
<!-- Profile Status Notice -->    
<div class="content-panel {{panel_class}} p-3 mb-3">
    <div class="d-flex align-items-start">
        <i class="bi bi-{{icon}} me-3 {{icon_class}}"></i>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="{{text_class}} mb-0 pt-1">{{heading}}</h5>
                <a href="/myaccount/profile" class="btn {{button_class}}">{{button_text}}</a>
            </div>
            <p class="mb-2">{{message}}</p>
            {{failed_message}}
        </div>
    </div>
</div>';

// Set up the content based on conditions
if ($profilecompletion['required_percentage'] == 100) {
  $replacements = [
      '{{panel_class}}' => 'bg-success-subtle border border-success',
      '{{icon}}' => 'check-circle-fill',
      '{{icon_class}}' => 'text-success fs-1',
      '{{text_class}}' => 'text-success',
      '{{heading}}' => 'Enrollment Profile Complete',
      '{{message}}' => 'Your enrollment profile looks great!',
      '{{failed_message}}' => '',
      '{{button_class}}' => 'btn-outline-success',
      '{{button_text}}' => 'Edit Profile',
      'profilestatus' => 'success'
  ];
} elseif ($failed_count > 0) {
  // Danger state - has failed enrollments
  $replacements = [
      '{{panel_class}}' => 'bg-danger-subtle border border-danger',
      '{{icon}}' => 'exclamation-triangle-fill',
      '{{icon_class}}' => 'text-danger fs-1',
      '{{text_class}}' => 'text-danger',
      '{{heading}}' => 'Action Required: Reward Enrollments Failed',
      '{{message}}' => sprintf(
          'Your enrollment profile is only %d%% complete. Reward enrollments cannot be processed until missing information is provided.',
          $profilecompletion['required_percentage']
      ),
      '{{failed_message}}' => sprintf(
          '<p class="mb-2"><strong>%d reward enrollment%s failed due to missing profile information.</strong></p>',
          $failed_count,
          $failed_count > 1 ? 's have' : ' has'
      ),
      '{{button_class}}' => 'btn-danger',
      '{{button_text}}' => 'Complete Missing Information',
      'profilestatus' => 'danger'
      
  ];
} else {
  // Warning state - incomplete profile but no failures
  $replacements = [
      '{{panel_class}}' => 'bg-warning-subtle border border-warning',
      '{{icon}}' => 'exclamation-triangle-fill',
      '{{icon_class}}' => 'text-warning fs-1',
      '{{text_class}}' => 'text-warning',
      '{{heading}}' => 'Enrollment Profile Incomplete',
      '{{message}}' => sprintf(
          'Your enrollment profile is %d%% complete. Please complete your profile to ensure smooth processing of reward enrollments.',
          $profilecompletion['required_percentage']
      ),
      '{{failed_message}}' => '',
      '{{button_class}}' => 'btn-warning',
      '{{button_text}}' => 'Complete Profile',
      'profilestatus' => 'warn'
  ];
}
    

// Replace all placeholders
foreach ($replacements as $key => $value) {
  $content_profilecompletion = str_replace($key, $value, $content_profilecompletion);
}

// Output the profile completion notice (hidden by feature flag)
if ($show_profile_completion && $replacements['profilestatus']!=='success') {
    echo $content_profilecompletion;
}


// -------------------------------------------------------
// Dashboard Panel with Enrollments and Allocations (Enrollments first for importance)
echo '
<!-- Dashboard Panel -->
<div class="content-panel mb-2">
    <h3 class="text-primary fw-bold mb-3">Quick Dashboard</h3>
    <div class="row g-3">
        <!-- Enrollments Card (moved to first position - most important) -->
        <div class="col-md-6">
            <div class="card h-100 border-success">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="card-title text-success mb-1">
                                <i class="bi bi-check-circle me-2"></i>Enrollments
                            </h5>
                            <p class="text-muted small mb-0">Enrolled Birthday Rewards</p>
                        </div>
                        <span class="badge rounded-pill bg-success fs-6">' . ($businessoutput['counts']['success'] ?? 0) . '</span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Pending</span>
                                <span class="fw-bold text-warning">' . ($businessoutput['counts']['pending'] ?? 0) . '</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Selected</span>
                                <span class="fw-bold text-primary">' . ($businessoutput['counts']['selected'] ?? 0) . '</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Already Have</span>
                                <span class="fw-bold text-secondary">' . ($businessoutput['counts']['user_owned'] ?? 0) . '</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Failed</span>
                                <span class="fw-bold text-danger">' . ($businessoutput['counts']['failed'] ?? 0) . '</span>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="text-center">
                            <small>
                                <a href="/myaccount/enrollment" class="text-muted">Dashboard</a>
                                <span class="text-muted mx-2">•</span>
                                <a href="/myaccount/profile" class="text-muted">Edit Profile</a>
                            </small>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <a href="/myaccount/enrollment-history" class="btn btn-sm btn-outline-success flex-grow-1">
                            View History
                        </a>
                        <a href="/myaccount/enrollment-picker" class="btn btn-sm btn-success flex-grow-1">
                            Pick Businesses
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Allocations Card (second position - supporting information) -->
        <div class="col-md-6">
            <div class="card h-100 border-primary">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="card-title text-primary mb-1">
                                <i class="bi bi-wallet2 me-2"></i>Allocations
                            </h5>
                            <p class="text-muted small mb-0">Enrollment Picks</p>
                        </div>
                        <span class="badge rounded-pill bg-primary fs-6">' . $businessoutput['counts']['remaining'] . '</span>
                    </div>
                    <div class="flex-grow-1">';

// Get more detailed allocation information
$plandatafeatures = $app->plandetail('details_id', $current_user_data['account_product_id']);
$plan_allocations = $businessoutput['counts']['plan_total'] ?? $plandatafeatures['max_business_select'] ?? 0;
$used_allocations = ($plan_allocations - $businessoutput['counts']['remaining']);

// Get user's plan name from Product class
$plan_name = $product->getProduct($current_user_data['account_product_id'], 'id')['account_name'];

// Try to get bonus allocations from AllocationManager if available
$bonus_allocations = 0;
$allocation_details = null;
if (class_exists('AllocationManager')) {
    include_once($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.allocationmanager.php');
    $allocationManager = new AllocationManager($database);
    $allocation_details = $allocationManager->getUserBalance($current_user_data['user_id']);
    $bonus_allocations = $allocation_details['bonus_allocations'] ?? 0;
}

// Calculate totals
$total_allocations = $plan_allocations + $bonus_allocations;
$remaining_allocations = $businessoutput['counts']['remaining'];

echo '
                        <!-- Allocation Breakdown -->
                        <div class="small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">
                                    Plan Allocations 
                                    <span class="badge bg-secondary ms-1" style="font-size: 0.7rem;">' . strtoupper($plan_name) . '</span>
                                </span>
                                <span class="fw-semibold">' . $plan_allocations . '</span>
                            </div>';

if ($bonus_allocations > 0) {
    echo '
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Bonus/Earned:</span>
                                <span class="fw-semibold text-success">+' . $bonus_allocations . '</span>
                            </div>';
}

echo '
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Used:</span>
                                <span class="fw-semibold text-danger">-' . $used_allocations . '</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted fw-bold">Available Now:</span>
                                <span class="fw-bold text-primary">' . $remaining_allocations . '</span>
                            </div>
                        </div>
                        
                        <div class="progress mt-2 mb-2" style="height: 8px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                style="width: ' . (($total_allocations > 0) ? round(($remaining_allocations / $total_allocations) * 100) : 0) . '%"
                                aria-valuenow="' . $remaining_allocations . '" 
                                aria-valuemin="0" 
                                aria-valuemax="' . $total_allocations . '">
                            </div>
                        </div>';

// Calculate the actual reset date
$reset_date = new DateTime();
$reset_date->add(new DateInterval('P' . $tillanniversary['days'] . 'D'));
$reset_date_formatted = $reset_date->format('F j, Y');

echo '
                        <p class="text-muted small mb-0">
                            <span data-bs-toggle="tooltip" data-bs-placement="top" 
                                  title="Resets on ' . $reset_date_formatted . '"
                                  style="border-bottom: 1px dotted #6c757d;">
                                Resets in ' . $qik->plural2($tillanniversary['days'], 'day') . '
                            </span>
                        </p>
                    </div>
                    <div class="mt-3">';

// Check if user has free plan and add upgrade link
$is_free_plan = ($current_user_data['account_plan'] == 'free' || $current_user_data['account_product_id'] == 1 || $plan_allocations <= 5);

if ($is_free_plan) {
    echo '
                        <a href="/plans" class="btn btn-sm btn-success w-100 mb-2">
                            <i class="bi bi-arrow-up-circle me-1"></i>Upgrade for More Allocations
                        </a>';
}

echo '
                        <div class="d-flex gap-2">
                            <a href="/myaccount/allocation-history" class="btn btn-sm btn-outline-primary flex-grow-1">
                                View History
                            </a>
                            <a href="/myaccount/earn-enrollments" class="btn btn-sm btn-primary flex-grow-1">
                                Earn More
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

// Fun Facts Block (moved below Dashboard)
echo '
<!-- Fun Facts Block -->
<div class="content-panel mb-4 pb-1">
<h3 class="text-info fw-bold">Fun Facts</h3>
<div class="row p-0 m-0">
' . $funfact_content . '
</div>
<div class="my-0 py-0 d-flex justify-content-end">
<a href="/myaccount/fun-facts" class="fw-bold">Discover more fun facts ></a>
</div>
</div>
';

echo '
<div class="row mx-0 px-0 g-0 g-md-4 mt-0  mb-1 pb-1">
<div class="col-md-8 ms-0 ps-0">
';

// Account Links Card - moved to separate row below
// (Account Links card removed from here - see below)

// Temporarily hide the Account Links from this section
if (true) {
echo '
    <div class="content-panel mb-3">
        <div class="d-flex justify-content-between align-items-center p-0 m-0">
            <h5 class="card-title mb-0">Account Links
            </h5>

        </div>

        <div class="list-group no-border">
            <a href="/myaccount/account" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                <div><i class="bi bi-pencil-square me-2"></i>Settings</div>
                <i class="bi bi-chevron-right"></i>
            </a>

            <a href="/myaccount/notifications" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                <div><i class="bi bi-bell me-2"></i>Notifications</div>
                <i class="bi bi-chevron-right"></i>
            </a>

            <a href="/myaccount/security-settings" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                <div><i class="bi bi-shield-lock me-2"></i>Security Settings</div>
                <i class="bi bi-chevron-right"></i>
            </a>

            <a href="/myaccount/parental-mode" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                <div><i class="bi bi-person me-2"></i>Parental Mode</div>
                <i class="bi bi-chevron-right"></i>
            </a>';

// Add marketing link for staff members
if ($account->isstaff()) {
    echo '
            <a href="/myaccount/marketing/" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                <div><i class="bi bi-megaphone me-2"></i>Marketing Platform</div>
                <i class="bi bi-chevron-right"></i>
            </a>';
}

echo '

            <a href="/myaccount/invite" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                <div><i class="bi bi-hand-thumbs-up me-2"></i>Invite Friends</div>
                <i class="bi bi-chevron-right"></i>
            </a>

            <a href="/myaccount/recommend-business" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                <div><i class="bi bi-building-add me-2"></i>Recommend Business</div>
                <i class="bi bi-chevron-right"></i>
            </a>';

// Show Claim Business link for business accounts
if ($current_user_data['account_type'] == 'business') {
    echo '
            <a href="/myaccount/claim-business" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                <div><i class="bi bi-building-check me-2"></i>Claim a Business</div>
                <i class="bi bi-chevron-right"></i>
            </a>';
}

echo '        </div>
    </div>';
} // End of Account Links section (moved below)

// -------------------------------------------------------
// Enrollment Summary Block (hidden by feature flag)
if ($show_enrollment_summary) {
    echo '
    <!-- Enrollments Summary Block -->
    <div class="content-panel">
    <div class="d-flex justify-content-between align-items-baseline">
    <h3 class="text-success fw-bold mb-0" style="border-bottom: none;">Enrollment Summary</h3>
    <a href="/myaccount/enrollment" class="btn btn-sm btn-outline-secondary ms-auto">Dashboard</a>
    </div>
    <hr class="mt-2 mb-3">
    <p class="summary mt-3">';
    if ($businessoutput['counts']['remaining'] == 0) {
      echo '
    <i class="bi bi-cart-x-fill text-danger me-2"></i>You ran out of enrollments. You\'ll receive ' . $businessoutput['counts']['plan_total'] . ' more in ' . $qik->plural2($tillanniversary['days'], 'day') . '.';
    } else {
      echo '<i class="bi bi-cart-plus-fill text-success me-2"></i>You have ' . $qik->plural2($businessoutput['counts']['remaining'], 'enrollment') . ' remaining. ';
    }

    echo '</p>
    <a href="/myaccount/enrollment-history" class="btn btn-sm btn-primary text-decoration-none">View enrollment history</a>
    </div>
    ';
}

// Profile Completion panel (shown when status is success, hidden by feature flag)
if ($show_profile_completion && $replacements['profilestatus']=='success') {
    echo $content_profilecompletion;
}
echo '
</div>';





$additionalstyles .= '
<style>
.force-no-decoration a,
.force-no-decoration a * {
text-decoration: none !important;
}
.col-auto.navbar-vertical-label {
font-weight: 700 !important;
}
</style>
';

echo '
</div>
</div>
</div>
';
/*
<!-- Account Links in separate row -->
<div class="row mx-0 px-0 g-0 g-md-4 pt-0 mt-0 mb-1 pb-1">
    <div class="col-12">
        <div class="content-panel">
            <div class="d-flex justify-content-between align-items-center p-0 m-0">
                <h5 class="card-title mb-0">Account Settings/Links</h5>
            </div>

            <div class="list-group no-border">
                <a href="/myaccount/account" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                    <div><i class="bi bi-pencil-square me-2"></i>Settings</div>
                    <i class="bi bi-chevron-right"></i>
                </a>

                <a href="/myaccount/notifications" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                    <div><i class="bi bi-bell me-2"></i>Notifications</div>
                    <i class="bi bi-chevron-right"></i>
                </a>

                <a href="/myaccount/security-settings" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                    <div><i class="bi bi-shield-lock me-2"></i>Security Settings</div>
                    <i class="bi bi-chevron-right"></i>
                </a>

                <a href="/myaccount/parental-mode" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                    <div><i class="bi bi-person me-2"></i>Parental Mode</div>
                    <i class="bi bi-chevron-right"></i>
                </a>';

// Add marketing link for staff members
if ($account->isstaff()) {
    echo '
                <a href="/myaccount/marketing/" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                    <div><i class="bi bi-megaphone me-2"></i>Marketing Platform</div>
                    <i class="bi bi-chevron-right"></i>
                </a>';
}

echo '
                <a href="/myaccount/invite" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                    <div><i class="bi bi-hand-thumbs-up me-2"></i>Invite Friends</div>
                    <i class="bi bi-chevron-right"></i>
                </a>

                <a href="/myaccount/recommend-business" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                    <div><i class="bi bi-building-add me-2"></i>Recommend Business</div>
                    <i class="bi bi-chevron-right"></i>
                </a>';

// Show Claim Business link for business accounts
if ($current_user_data['account_type'] == 'business') {
    echo '
                <a href="/myaccount/claim-business" class="list-group-item-action d-flex justify-content-between align-items-center py-1">
                    <div><i class="bi bi-building-check me-2"></i>Claim a Business</div>
                    <i class="bi bi-chevron-right"></i>
                </a>';
}
*/

// Only show Other Features / Links section for staff and admin users
if ($account->isstaff() || $account->isadmin()) {
  $linkmode = 'x';

  switch ($linkmode) {
    case 'old':
      echo '
  <div class="row mt-3 px-0 mx-0">
  <!-- Other Features / Link Block -->
  <div class="content-panel force-no-decoration">
  <h3 class="text-warning fw-bold">Other Features / Links</h3>
  ';
      $accountlinkspresentation = '';
      include($dir['core_components'] . '/user_accountlinks_old.inc');

      echo '
  <a href="/myaccount/account"  class="btn btn-sm btn-primary text-decoration-none">Go to account settings</a>
  </div>
  </div>
  </div>
  </div>
  ';
      break;

    case '1':
      $accountlinkspresentation = '';
      include($dir['core_components'] . '/user_accountlinks1.inc');
      if ($accountlinks_display !== false) {

        echo '
  <div class="row mt-3 px-0 mx-0">
  <!-- Other Features / Link Block -->
  <div class="content-panel force-no-decoration">
  <h3 class="text-warning fw-bold">Other Features / Links</h3>
  ' . $accountlinks_output . '
  <a href="/myaccount/account"  class="btn btn-sm btn-primary text-decoration-none">Go to account settings</a>
  </div>
  </div>
  ';
      }
      break;

    default:
      $accountlinkspresentation = '';
      include($dir['core_components'] . '/user_accountlinks.inc');
      if ($accountlinks_display !== false) {
        echo '
  <div class="row mt-3 px-0 mx-0">
  <!-- Other Features / Link Block -->
  <div class="content-panel force-no-decoration">
  <h3 class="text-warning fw-bold">Other Features / Links</h3>

  ';
        echo $accountlinks_output;
        echo '
  <a href="/myaccount/account"  class="btn btn-sm btn-primary text-decoration-none">Go to account settings</a>
  </div>
  </div>
  </div>
  </div>
  ';
      }
      break;
  }
} else {
  // For non-staff users, just close the divs
  echo '</div></div>';
}



$footerattribute['bottomfooter'] = '

<script>
$(document).ready(function() {
$("#uploadBtn").click(function() {
$("#profile-image").click();
});

$("#profile-image").change(function() {
$("#profileavatarupload").submit();
});
});

</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
