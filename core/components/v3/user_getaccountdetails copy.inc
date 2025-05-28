<?PHP

if (empty($current_user_data) || empty($current_user_data['birthdate'])) {
    header('Location: /login.php');
    exit; 
}

$useraccountdetails=true;



//====================================================================================================
/// GET USER DETAILS
$bdarray = explode('-', $current_user_data['birthdate']);

$avatar=$current_user_data['avatar']??'/public/images/defaultavatar.png';

$alive = $app->calculateage($current_user_data['birthdate']);

$accountanniversary= $app->calculateage($current_user_data['create_dt']);

$tillanniversary=$app->getTimeTilBirthday($current_user_data['create_dt']);


$till=$app->getTimeTilBirthday($current_user_data['birthdate']);

$born_birthdate = new DateTime($current_user_data['birthdate']);;

$logincount=$account->getLoginCount($current_user_data['user_id']);
$userplanname=$bg_users_accountplans[$current_user_data['account_plan']];


$enabledHonorFlags = $account->getEnabledFlags();

if ($session->get('featuremailcount_cachedtime') < time() - 60) {
    $session->set('featuremailcount_cachedtime', time());
    require_once($dir['classes'] . '/' . 'class.mail.php');  
    $mail= new mail( $sitesettings['mail']);
    
    $featuremailcount=$mail->mailcount($current_user_data['user_id']);
 $session->set('featuremailcount',  $featuremailcount);

#$messages = $mail->getmessagelist($current_user_data['user_id'], 'user');
} else {
  $featuremailcount=$session->get('featuremailcount', 0);
}

include_once($_SERVER['DOCUMENT_ROOT'] .  '/myaccount/module_funfacts/astrosign.inc');
$user_astrosigndetails=$astrosigndetails;
   
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

$user_reward_results = $account->getbusinesslist_rewards($current_user_data, 'list', '"success", "success-btn"', 0, true);

$lastlogindetails = $account->getLastLogin($current_user_data['user_id']);

//====================================================================================================
/// COVER BANNER
#$coverbanner = '//files.birthday.gold/public/images/site_covers/cbanner_' . $bdarray[1] . '.jpg';  ### MOVING TO LOCAL SERVER
#$coverbanner = '/public/images/site_covers/cbanner_' . $bdarray[1] . '.jpg';
$coverbuttontag = 'Upload';
#$usercover = $account->getUserAttribute($current_user_data['user_id'], 'account_cover');
$coverbanner=$session->get('account_cover' ,'');
if (empty($coverbanner)) {
$usercover = $database->query("select description from bg_user_attributes where `type`='profile_image' and `name`='account_cover' and `status`='active'  and `category`='primary' and user_id=:user_id", ['user_id' => $current_user_data['user_id']])->fetch();
if (!empty($usercover['description'])) {
    $coverbanner =  $usercover['description'];
    $coverbuttontag = 'Change';
    $coverbanner=$session->set('account_cover' ,$coverbanner );
}
}


//====================================================================================================
/// GET PROFILE COMPLETION
$profilecompletion = $account->profilecompletionratio($current_user_data);


//====================================================================================================
/// GET ENROLLMENT COUNTS
$showlimit=0;
$businessoutput = $account->getbusinesslist($current_user_data, 'any', $showlimit);
/*if ($businessoutput['counts']['success'] == 0) {
  $businessoutput = $account->getbusinesslist($current_user_data, '"selected"', $showlimit);
  $enrollmentstyle = 'selected';
}
*/

//====================================================================================================
/// GET USER LOCATION
$location = '';
if ($current_user_data['state']) {
    $location = $current_user_data['state'] . ', ';
}
$location .= $current_user_data['country'];



//====================================================================================================
/// GET LAST LOGIN
   $lastlogindetails = $account->getLastLogin($current_user_data['user_id']);
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