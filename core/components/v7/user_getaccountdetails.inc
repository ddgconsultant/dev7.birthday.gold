<?PHP
// PERFORMANCE TRACKING (if enabled in parent)
if (isset($perf_timers)) {
    $ugad_start = microtime(true);
    $ugad_timers = [];
}

if (empty($getaccountdetailsuser)) {
    $component_user_data = $session->get('current_user_data') ?? [];
} else {
    $component_user_data = $getaccountdetailsuser;
}


if (empty($component_user_data) || empty($component_user_data['birthdate'])) {
  session_tracking('error-user_getaccoutndetails-failure', 'User data not found.  Redirecting to login page.');
    header('Location: /login.php');
    exit; 
}

$useraccountdetails=true;



//====================================================================================================
/// GET USER DETAILS
$bdarray = explode('-', $component_user_data['birthdate']);

$avatar=$component_user_data['avatar']??'/public/images/defaultavatar.png';

$alive = $app->calculateage($component_user_data['birthdate']);

$accountanniversary= $app->calculateage($component_user_data['create_dt']);

$tillanniversary=$app->getTimeTilBirthday($component_user_data['create_dt']);


$till=$app->getTimeTilBirthday($component_user_data['birthdate']);

$born_birthdate = new DateTime($component_user_data['birthdate']);;

if (isset($ugad_timers)) $ugad_checkpoint = microtime(true);
$logincount=$account->getLoginCount($component_user_data['user_id']);
if (isset($ugad_timers)) $ugad_timers['getLoginCount'] = round((microtime(true) - $ugad_checkpoint) * 1000, 2);

// Ensure bg_users_accountplans is available
global $bg_users_accountplans;
if (!isset($bg_users_accountplans)) {
    include_once($dir['core'] . '/site-arrays.inc');
}

// Initialize Product object if not already available
if (!isset($product) || !is_object($product)) {
    require_once($dir['classes'] . '/class.product.php');
    $product = new Product($database, $qik);
}

$userplanname = $product->getProduct($component_user_data['account_product_id'], 'id')['account_name'];
$user_planddetails=$app->plandetail('details_id', $component_user_data['account_product_id']);

if (isset($ugad_timers)) $ugad_checkpoint = microtime(true);
$enabledHonorFlags = $account->getEnabledFlags();
if (isset($ugad_timers)) $ugad_timers['getEnabledFlags'] = round((microtime(true) - $ugad_checkpoint) * 1000, 2);

// OPTIMIZATION: Only check featuremailcount if user actually has a feature email
if (isset($ugad_timers)) $ugad_checkpoint = microtime(true);
$has_feature_email = !empty($component_user_data['feature_email']);

if ($has_feature_email && $session->get('featuremailcount_cachedtime') < time() - 60) {
    $session->set('featuremailcount_cachedtime', time());
    require_once($dir['classes'] . '/' . 'class.mail.php');
    $mail= new mail( $sitesettings['mail']);

    $featuremailcount=$mail->mailcount($component_user_data['user_id']);
 $session->set('featuremailcount',  $featuremailcount);

#$messages = $mail->getmessagelist($component_user_data['user_id'], 'user');
} else {
  $featuremailcount=$session->get('featuremailcount', 0);
}
if (isset($ugad_timers)) $ugad_timers['featuremailcount'] = round((microtime(true) - $ugad_checkpoint) * 1000, 2);

if (isset($ugad_timers)) $ugad_checkpoint = microtime(true);
include_once($_SERVER['DOCUMENT_ROOT'] .  '/myaccount/module_funfacts/astrosign.inc');
$user_astrosigndetails=$astrosigndetails;
if (isset($ugad_timers)) $ugad_timers['astrosign_inc'] = round((microtime(true) - $ugad_checkpoint) * 1000, 2);
   
/*echo '
<div class="border-bottom border-dashed my-4 d-lg-none"></div>
</div>

<div class="col ps-2 ps-lg-3 mt-lg-n6">
<div class="d-flex">
<i class="bi bi-caret-right-square-fill me-1 text-secondary me-2"></i>
<h6 class="mb-0 fw-bold ">Your next birthday is ' . $qik->plural2($till['days'], 'day') . ' away<br>
... and will be on a ' . $till['dayofweek'] . '<br>
... and you will be ' . $qik->plural2(($alive['years'] + 1), 'year') . ' old.<br>           
You were born on: ' . $born_birthdate->format('D, M. j, Y') . '<br>
</h6>
</div>
';
*/

if (isset($ugad_timers)) $ugad_checkpoint = microtime(true);
$user_reward_results = $account->getbusinesslist_rewards($component_user_data, 'list', '"success", "success-btn"', 0, true);
if (isset($ugad_timers)) $ugad_timers['getbusinesslist_rewards'] = round((microtime(true) - $ugad_checkpoint) * 1000, 2);

if (isset($ugad_timers)) $ugad_checkpoint = microtime(true);
$lastlogindetails = $account->getLastLogin($component_user_data['user_id']);
if (isset($ugad_timers)) $ugad_timers['getLastLogin'] = round((microtime(true) - $ugad_checkpoint) * 1000, 2);

//====================================================================================================
/// COVER BANNER
#$coverbanner = '//files.birthday.gold/public/images/site_covers/cbanner_' . $bdarray[1] . '.jpg';  ### MOVING TO LOCAL SERVER
#$coverbanner = '/public/images/site_covers/cbanner_' . $bdarray[1] . '.jpg';
$coverbuttontag = 'Upload';
#$usercover = $account->getUserAttribute($component_user_data['user_id'], 'account_cover');
$coverbanner=$session->get('account_cover' ,'');
if (empty($coverbanner)) {
$usercover = $database->query("select description from bg_user_attributes where `type`='profile_image' and `name`='account_cover' and `status`='active'  and `category`='primary' and user_id=:user_id", ['user_id' => $component_user_data['user_id']])->fetch();
if (!empty($usercover['description'])) {
    $coverbanner =  $usercover['description'];
    $coverbuttontag = 'Change';
    $coverbanner=$session->set('account_cover' ,$coverbanner );
}
}


//====================================================================================================
/// GET PROFILE COMPLETION
if (isset($ugad_timers)) $ugad_checkpoint = microtime(true);
$profilecompletion = $account->profilecompletionratio($component_user_data);
if (isset($ugad_timers)) $ugad_timers['profilecompletionratio'] = round((microtime(true) - $ugad_checkpoint) * 1000, 2);


//====================================================================================================
/// GET ENROLLMENT COUNTS
if (isset($ugad_timers)) $ugad_checkpoint = microtime(true);

// OPTIMIZATION: For admin pages, use sendresults=true to skip HTML generation (just get data)
$is_admin_page = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
if ($is_admin_page) {
    // Use sendresults=true to skip HTML rendering, just get counts
    $businessoutput = $account->getbusinesslist($component_user_data, 'any', 0, true);
    // The function with sendresults=true returns raw data array, we need to process it for counts
    if (is_array($businessoutput) && !isset($businessoutput['counts'])) {
        // It returned raw rows, need to count them
        $counts = ['success' => 0, 'pending' => 0, 'failed' => 0, 'total' => count($businessoutput)];
        foreach ($businessoutput as $row) {
            if (isset($row['status'])) {
                if (isset($counts[$row['status']])) {
                    $counts[$row['status']]++;
                }
            }
        }
        // Calculate remaining
        $plandatafeatures = $app->plandetail('details_id', $component_user_data['account_product_id']);
        $selectsused = ($counts['pending'] + $counts['success']);
        $counts['remaining'] = max(0, ($plandatafeatures['max_business_select'] - $selectsused));

        $businessoutput = ['counts' => $counts, 'list' => $businessoutput];
    }
} else {
    $showlimit=0;
    $businessoutput = $account->getbusinesslist($component_user_data, 'any', $showlimit);
}
if (isset($ugad_timers)) $ugad_timers['getbusinesslist'] = round((microtime(true) - $ugad_checkpoint) * 1000, 2);
/*if ($businessoutput['counts']['success'] == 0) {
  $businessoutput = $account->getbusinesslist($component_user_data, '"selected"', $showlimit);
  $enrollmentstyle = 'selected';
}
*/

//====================================================================================================
/// GET USER LOCATION
$location = '';
if ($component_user_data['state']) {
    $location = $component_user_data['state'] . ', ';
}
$location .= $component_user_data['country'];



//====================================================================================================
/// GET LAST LOGIN
   $lastlogindetails = $account->getLastLogin($component_user_data['user_id']);
   if (!empty($lastlogindetails['create_dt'])) {
     $lastloginmessage = $qik->timeago($lastlogindetails['create_dt']);
   } else {
     $lastloginmessage = ['message' => 'Now'];
   }


//====================================================================================================
 /// GET FUN FACTS
   // Array of available card files
   // $bg_funfacts  --  located in site_array.inc
$funfact_content='';
   $bg_funfacts = array_diff($bg_funfacts, ['random_facts.inc']);
   // Re-index the array to ensure the keys are consecutive integers
   $bg_funfacts = array_values($bg_funfacts);


   $index = 0;
   $visibilityClasses = [
     0 => 'd-block',              // Always show the first card
     1 => 'd-none d-md-block',    // Show the second card on medium screens and up
     2 => 'd-none d-lg-block'     // Show the third card on large screens and up
   ];

   // Shuffle the array and pick the first 3 files
   shuffle($bg_funfacts);
   // Check if 'random_facts.inc' is in the selected files
   if (in_array('random_facts.inc', $bg_funfacts)) {
     $selectedFiles = ['random_facts.inc'];
   } else {
     $selectedFiles = array_slice($bg_funfacts, 0, 3);
   }

   // Loop through the selected files and include them
   foreach ($selectedFiles as $file) {
     $funfact_content.= '<div class="funfacthandler my-0 py-0 col-sm-6 col-lg-4 mb-3 ' . $visibilityClasses[$index] . '">';
     $index++;
     if ($index > 2)  $index = 2;
     include_once($_SERVER['DOCUMENT_ROOT'] .  '/myaccount/module_funfacts/' . $file);
     $funfact_content.=$funfactrecord;
     $funfact_content.= '</div>';
   }
   unset($getaccountdetailsuser);

// PERFORMANCE TRACKING OUTPUT
if (isset($ugad_timers)) {
    $ugad_total = round((microtime(true) - $ugad_start) * 1000, 2);
    $perf_timers['ugad_TOTAL'] = $ugad_total;

    // Add individual timers to parent with ugad_ prefix
    foreach ($ugad_timers as $label => $time) {
        $perf_timers['ugad_' . $label] = $time;
    }
}