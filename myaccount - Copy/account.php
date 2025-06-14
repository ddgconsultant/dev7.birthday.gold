<?php
$addClasses[] = 'createaccount';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');





$jstag_openinstructions = '';
$profilesettings['savetosession']= true;
$current_user_data=$account->getuserdata($current_user_data['user_id'], 'user_id', $profilesettings );

#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT   -- this is an AJAX POST
#-------------------------------------------------------------------------------
if ($app->formposted()) {

    #-----------------------------
    if (isset($_POST['minorform'])) {
        #breakpoint($_REQUEST);
        $minor_user_id = $row['user_id'] = $_POST['minor_user_id'] ?? '';

        if ($minor_user_id != '') {

            $minoroptions = ['Login', 'Profile', 'Account'];
            foreach ($minoroptions as $minoroption) {

                $setting = $_POST[$minoroption . 'flexSwitch' . $row['user_id']] ?? '0';

                $input = ['name' => 'minor_allow_' . strtolower($minoroption), 'description' => $setting];
                $response = $account->setUserAttribute($row['user_id'], $input);
            }
            $errormessage = '<div class="alert alert-success">Updated settings.</div>';
            $transferpagedata['message'] = $errormessage;
            $transferpagedata['url'] = '/myaccount/account';
            $transferpagedata = $system->endpostpage($transferpagedata);
        }
        http_response_code(200);
        exit;
    }


    #-----------------------------
    if (isset($_REQUEST['profileupdate'])) {
        #breakpoint($_REQUEST);
        $updatefields = [];
        // Step 2: Set checked options to "true".
        foreach ($_POST as $formelement => $formvalue) {
            if (strpos($formelement, 'input') !== false) {
                $columnname = strtolower(str_replace('input', '', $formelement));
                $updatefields[$columnname] = trim($formvalue);
            }
        }

        if (!empty($updatefields)) {
            $updatefields['email'] = strtolower($updatefields['email']);

            # breakpoint($updatefields);
            $userdata_before = $current_user_data;
            unset($userdata_before['modify_dt']);
            $userdata_beforehash = hash('sha256', serialize($userdata_before));
            $account->updateSettings($current_user_data['user_id'], $updatefields);
            #breakpoint($updatefields);

            $profilesettings['savetosession']= true;
            $current_user_data=$account->getuserdata($current_user_data['user_id'], 'user_id', $profilesettings );
            $userdata_after = $current_user_data;
            unset($userdata_after['modify_dt']);
            $userdata_afterhash = hash('sha256', serialize($userdata_after));
        }
        if (
            isset($updatefields['username']) && $updatefields['username'] != $userdata_before['username'] ||
            isset($updatefields['email']) && $updatefields['email'] != $userdata_before['email']
        ) {
            header('location: /logout?_relogin');
            exit;
        }
        $supressionitem = $extremesupression = false;
        $messages = array();
    }
}






#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

# <link rel="stylesheet" href="/public/css/myaccount.css">
$additionalstyles.= '
<style>
.accountswitch {
width: 140px;
}
</style>
';
#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header2.inc');
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');



include($dir['core_components'] . '/bg_user_profileheader.inc');

echo '    

<div class="container mt-5 pt-5 main-content">
        <div class="row">
            <div class="col-lg-8">
                
        ';




/*
#-------------------------------------------------------------------------------
# PARENTAL MINOR ACCOUNTS
#-------------------------------------------------------------------------------
if ($current_user_data['account_type'] == 'parental') {

    $sql = 'select * from bg_users where feature_parent_id=' . $current_user_data['user_id'] . ' and `status`="active" order by status limit 6';

    $stmt = $database->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $listofminors = [];
    $minorcount = count($results);
    $minorcountdisplayed = 0;

    $parentaljs = $display->tooltip('-js-');

    echo '
<!-- Parental  card-->
<div class="card card-header-actions mb-4">
        <div class="card-header">
            Minor Accounts <span class="badge rounded-pill bg-secondary"  data-bs-toggle="tooltip" data-bs-placement="top" title="' . $minorcount . ' Child Accounts">' . $minorcount . '</span>
        </div>
        <div class="card-body px-0">
        ';


    ###### LOOP THROUGH MINOR RECORDS
    foreach ($results as $row) {
        $tmpclasstag1 = 'ms-auto';
        $signinbutton = '';
        $young_person = $app->calculateage($row['birthdate']);
        if ($row['status'] == 'active') {
            #  $cid=$qik->encodeId($row['user_id']);
            $cid = $row['user_id'];
            $tmpclasstag1 = '';
            $signinbutton = ' <a class="btn btn-sm button btn-primary accountswitch fw-bold" href="/myaccount/switch2minor?id=' . $cid . '&pid=' . $current_user_data['user_id'] . '&_token=' . $display->inputcsrf_token('tokenonly') . '">Switch Account</a>
  ';
        }
        $settingsbutton = '<button class="btn p-0 m-0" type="button" data-bs-toggle="collapse" data-bs-target="#minorcontroller' . $row['user_id'] . '" aria-expanded="false" aria-controls="minorcontroller' . $row['user_id'] . '">
<i class="bi bi-gear"></i>
</button>
';

        $avatar = '/public/images/defaultavatar.png';
        $avatarbuttontag = 'Upload';
        if (!empty($row['avatar'])) {
            $avatar = '/' . $row['avatar'];
        }

        echo '
<!-- Parent container for each minor account -->
<div class="d-flex align-items-start px-3 mb-3">
  <!-- Profile Image (Common for both SM and MD/LG) -->
  <img class="img-account-profile rounded-circle mb-2 mb-md-0 me-3" style="width:48px;height:48px" src="' . $avatar . '" alt="">
  

    <!-- Account Details -->
    <div class="container m-0 p-0">
        <div class="d-flex">
          <div class="fw-bold">' . $row['first_name'] . ' ' . $row['last_name'] . ' (' . $young_person['years'] . ')</div>
          <div class="ms-auto d-block d-md-none py-0 my-0">' . $settingsbutton . '</div>
        </div>
        <div class="text-xs text-muted pe-3">' . $row['birthdate'] . '</div>
        <div class="text-xs text-muted pe-3">' . $row['username'] . '</div>

        <!-- Buttons to show on SM -->
        <div class="d-block d-md-none">' . $signinbutton . '</div>
        </div>

';


        echo '
    <!-- Switch Account Button -->
    <div class="ms-auto align-self-center d-none d-md-block">
  ' . $signinbutton . '   </div>
';


        ### manage MINOR ACCOUNT Settings
        echo '
    <!-- Settings Button -->
    <div class="align-self-center ms-2 ps-3 ' . $tmpclasstag1 . '  d-none d-md-block">
' . $settingsbutton . '    </div>

';


        echo '
</div>
<!-- Collapsible Settings -->
<div id="minorcontroller' . $row['user_id'] . '" class="collapse">
<div class="d-flex flex-column flex-md-row justify-content-between"  style="margin-left:82px;">
<span class="fw-bold me-auto pe-2">Allow: </span>
  <form id="minorform' . $row['user_id'] . '" action="/myaccount/account" method="POST" class="d-flex flex-column flex-md-row justify-content-between w-100">
      <!-- Hidden Inputs -->
      <input type="hidden" name="minorform" value="1">
      <input type="hidden" name="minor_user_id" value="' . $row['user_id'] . '">
      ' . $display->inputcsrf_token() . '
      
      <!-- Switches -->
';
        $minoroptions = ['Login', 'Profile', 'Account'];
        foreach ($minoroptions as $minoroption) {
            $disabledtooltip =    $disabled = '';
            // Skip creating the Login switch if the child is less than 13 years old

            if ($young_person['years'] < 13 && $minoroption == 'Login') {
                $disabled = 'disabled ' .
                    $disabledtooltip = $display->tooltip('Too Young - feature disabled');
            }

            $response = $account->getUserAttribute($row['user_id'], 'minor_allow_' . strtolower($minoroption));
            $result = $response['description'] ?? '0';
            if ($result == '1') $minorchecked = 'checked';
            else $minorchecked = '';
            #   $minorchecked='checked';
            echo '
    <div class="form-check form-switch me-3" ' . $disabledtooltip . '>
    <input class="form-check-input" type="checkbox" value="1" ' . $disabled . ' role="switch" id="' . $minoroption . 'flexSwitch' . $row['user_id'] . '" name="' . $minoroption . 'flexSwitch' . $row['user_id'] . '" ' . $minorchecked . '>
    <label class="form-check-label" for="' . $minoroption . 'flexSwitch' . $row['user_id'] . '">' . $minoroption . ' Edits</label>
    </div>
';
        }
        #echo '<button type="submit" class="btn btn-sm btn-success p-0 m-0 px-1 mx-1">Save</button>';
        echo '
</form>
</div>
</div>
<!-- end minorcontroller -->
';

        $minorcountdisplayed++;
        if ($minorcount != $minorcountdisplayed) {
            echo '
<hr class="py-0 my-2">
';
        }
    }


    echo ' 
</div>
<!-- end of minor accounts -->';

    echo '    </div>';
}

*/



### ENSURE THERE IS NO EMAIL / USERNAME COLLISION
$username = (isset($_POST['username']) ? $_POST['username'] : false);
if ($username) $output = $createaccount->isavailable($username);

$accountstats = $account->account_getstats();

#$plandetails = $app->plandetail('details');
$plandatafeatures=$app->plandetail('details_id', $current_user_data['account_product_id']);
$userplan = $current_user_data['account_plan'];

$selectsused = ($accountstats['business_pending'] + $accountstats['business_selected'] + $accountstats['business_success']);
#$selectsleft = ($plandetails[$userplan]['max_business_select'] - $selectsused);
$selectsleft = ($plandatafeatures['max_business_select'] - $selectsused);

$plandata = $app->plandetail('details');
$userplan = $current_user_data['account_plan'];






$daysouttag = $plandatafeatures['celebration_tour_option_tag'] ;
$daysout = $plandatafeatures['celebration_planning_days'];
/* 
switch ($userplan) {
    case 'free':
        $daysouttag = $plandatafeatures['celebration_tour_option_tag'] . ' - Click Here to upgade.';
        $daysout = $plandatafeatures['celebration_planning_days'];
        break;
    case 'gold':
        $daysouttag = $plandatafeatures['celebration_tour_option_tag'];
        $daysout = $plandetails[$userplan]['celebration_planning_days'];
        break;
    case 'life':
        $daysouttag = $plandetails[$userplan]['celebration_tour_option_tag'];
        $daysout = $plandetails[$userplan]['celebration_planning_days'];
        break;
    default:
        $daysouttag = 'This feature is not available on the FREE plan - Click Here to upgade.';
        $daysout = 0;
        break;
}
 */
$tag1 = $plandatafeatures['max_business_select_tag'];
/*
switch ($plandatafeatures['max_business_select_tag']) {
    case 0:
        $tag1 = ' The free plan does not allow you to enroll in any businesses.';
        break;


    default:
        $tag1 = ' Every year you renew you get ' . $plandatafeatures['max_business_select'] . ' more.';
        break;
}
        */
# breakpoint($current_user_data);
$nextDate = $app->calculateNextOccurrence($current_user_data['birthdate'], $daysout);
#breakpoint($nextDate);
# $output['result']
$outdays = $app->getTimeTilBirthday($nextDate['date']);

echo '<div class="mb-3">
<h2 class="text-primary">Your Account Settings</h2>
</div>
';

echo '

<!-- Plan Feature card-->
<div class="card card-header-actions mb-4">
    <div class="card-header">
        Plan Features
    </div>
    <div class="card-body px-0">
        <!-- Payment method 1-->
        <div class="d-flex align-items-center justify-content-between px-4">
            <div class="d-flex align-items-center">
                    <h1><i class="bi bi-bag-heart"></i></h1>
                <div class="ms-4">
                ';

                echo  '<div class="small">You can select up to  ' . $plandatafeatures['max_business_select'] . ' '.$website['biznames'].' in your plan.
                ' . $tag1 . '</div>
                <div class="text-xs text-muted">You are using ' . $selectsused . ' and have ' . ($selectsleft < 0 ? 0 : $selectsleft) . ' left.</div>
                ';
                /*
switch ($plandatafeatures['plan'] ) {
    case 'free':
        echo $tag1;
        break;
    default:
        echo  '<div class="small">You can select up to  ' . $plandatafeatures['max_business_select'] . '  '.$website['biznames'].' in your plan.
                    ' . $tag1 . '</div>
                    <div class="text-xs text-muted">You are using ' . $selectsused . ' and have ' . ($selectsleft < 0 ? 0 : $selectsleft) . ' left.</div>
                    ';
        break;
}
*/

echo '
                </div>
            </div>               
        </div>
        <hr>
        <!-- Item method 2-->
        <div class="d-flex align-items-center justify-content-between px-4">
            <div class="d-flex align-items-center">
            <h1><i class="bi bi-calendar3"></i></h1>
                <div class="ms-4">
                <div class="small">Celebration Tour: ' . $plandatafeatures['celebration_tour_option_tag'] . '</div>';

                echo '                     
                <div class="text-xs text-muted">You can start your planning in ' . $outdays['days'] . ' ' . $qik->plural('day',  $outdays['days']) . '</div>
                ';
                /*
switch ($plandatafeatures['plan'] ) {
    case 'free':
        echo
        '';
        break;
    default:
        echo '                     
                <div class="text-xs text-muted">You can start your planning in ' . $outdays['days'] . ' ' . $qik->plural('day',  $outdays['days']) . '</div>
                ';
        break;
}
        */
echo '
                </div>
            </div>
        </div>
        <hr>
        <!-- Item method 3-->
        <div class="d-flex align-items-center justify-content-between px-4">
            <div class="d-flex align-items-center">
            <h1><i class="bi bi-alarm"></i></h1>
                <div class="ms-4">
                <div class="small">Reminder of upcoming benefits</div>
                <div class="text-xs text-muted">Don\'t miss out on any freebies!</div>
                </div>
            </div>
        </div>
        <hr>
        <!-- Item method 3-->
        <div class="d-flex align-items-center justify-content-between px-4">
            <div class="d-flex align-items-center">
            <h1><i class="bi bi-wechat"></i></h1>
                <div class="ms-4">
                <div class="small">Support through ' . $plandatafeatures['support_tag'] . '</div>
                <div class="text-xs text-muted"><a target="_blank" href="' . $plandatafeatures['support_link'] . '">Click here to get support now.</a></div>
                </div>
            </div>
        </div>

    </div>

    </div>
    
    ';
    // https://chat.birthdaygold.cloud/channel/BG-CustomerService






#-------------------------------------------------------------------------------
# DISPLAY PROFILE SECTION
#-------------------------------------------------------------------------------

$till = $app->getTimeTilBirthday($current_user_data['birthdate']);
$astrosign = $app->getastrosign($current_user_data['birthdate']);
$astroicon = $app->getZodiacInfo($astrosign);
$state = $current_user_data['state'];
if (empty($state) && !empty($client_locationdata['regionName'])) {
    $state = $client_locationdata['regionName'];
}
$avatar = '/public/images/defaultavatar.png';
if (!empty($current_user_data['avatar'])) $avatar = '/' . $current_user_data['avatar'];








#-------------------------------------------------------------------------------
# Account Personal Details
#------------------------------------------------------------------------------- 
echo '
<!-- Account details card-->
<div class="card mb-4">
<div class="card-header d-flex justify-content-between">
<span>birthday.gold Account Details</span>
<!-- Button trigger modal
<button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#instructionsModal">
Instructions
</button>  -->
</div>
<div class="card-body">
<form id="profileupdateForm" method="post">
' . $display->inputcsrf_token() . '
<input name="profileupdate" type="hidden" value="1">


<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">Account Personal Details:</h4></div>


</div>






<!-- Form Row-->
<div class="row gx-3 mb-3">
<!-- Form Group (title)-->
<div class="col-md-2">
<label class="small mb-1" for="inputtitle">Title</label>
<select name="inputtitle" class="form-control custom-select select-form-background">
' . $display->list_title($current_user_data['title']) . '
</select>
</div>


<!-- Form Group (first name)-->
<div class="col-md-4">
<label class="small mb-1" for="inputfirst_name">First name</label>
<input class="form-control" name="inputfirst_name"  id="inputfirst_name" type="text" placeholder="Enter your first name" value="' . $current_user_data['first_name'] . '">
</div>
<!-- Form Group (middle name)-->
<div class="col-md-2">
<label class="small mb-1" for="inputmiddle_name">Middle name</label>
<input class="form-control" name="inputmiddle_name"  id="inputmiddle_name" type="text" placeholder="" value="' . $current_user_data['middle_name'] . '">
</div>
<!-- Form Group (last name)-->
<div class="col-md-4">
<label class="small mb-1" for="inputlast_name">Last name</label>
<input class="form-control" name="inputlast_name" id="inputlast_name" type="text" placeholder="Last name" value="' . $current_user_data['last_name'] . '">
</div>
</div>

<div class="row gx-3 mb-3">
<!-- Form Group (gender)-->
<div class="col-md-4">
<label class="small mb-1" for="inputgender">Gender</label>                      
<select name="inputgender" class="form-control custom-select select-form-background">
' . $display->list_gender($current_user_data['gender']) . '
</select>
</div>
</div>

';



$passgenerator = '';

echo '
<hr class="mt-4 mb-4">
<!-- Contact Details -------------------------------------------------------------------------------------------- -->
<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">User Account Details:</h4></div>
<small class="mb-2">Used to log into birthday.gold</small>
<!-- Form Group (username)-->
<div class="col-md-6">
<label class="small mb-1" for="inputUsername">Username  <span id="availability"></span></label>
<div class="input-group mb-3">
<input type="text" class="form-control" name="inputUsername" id="inputUsername" placeholder="Enter your username" value="' . $current_user_data['username'] . '">
<button class="btn btn-outline-secondary ms-0 p-1" type="button" id="checkButton">Check</button>
</div>
</div>


<!-- Form Group (email)-->
<div class="col-md-6">
<label class="small mb-1" for="inputemail">Email Address</label>
<input class="form-control" name="inputemail" id="inputemail" type="email" placeholder="Enter your email address" value="' . $current_user_data['email'] . '">
</div>



<!-- Form Group (phone_number)-->
<div class="col-md-6">
<label class="small mb-1" for="inputphone_number">Mobile Phone Number</label>
<input class="form-control" name="inputphone_number" id="inputphone_number" type="tel" placeholder="Enter your mobile number" value="' . $current_user_data['phone_number'] . '">
</div>
</div>







<hr class="mt-4 mb-4">
<!-- Mailing Address -------------------------------------------------------------------------------------------- -->
<!-- Form Row-->
<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">Mailing Address:</h4></div>
<small class="mb-2">Used for birthday.gold gifts.</small>
<!-- Form Group (organization name)-->
<div class="col-md-12">
<input class="form-control" name="inputmailing_address"  id="inputmailing_address" type="text" placeholder="Mailing Address" value="' . $current_user_data['mailing_address'] . '">
</div>

</div>
<!-- Form Row        -->
<div class="row gx-3 mb-3">

<!-- Form Group (location)-->
<div class="col-md-4">
<input class="form-control" name="inputCity"  id="inputCity" type="text" placeholder="City" value="' . $current_user_data['city'] . '">
</div>



<!-- Form Group (state)-->
<div class="col-md-4">
<select name="inputState" class="form-select form-select mb-3" aria-label=".form-select example">
' . $display->list_state($state) . '
</select>


</div>
<!-- Form Group (location)-->
<div class="col-md-4">
<input class="form-control"  name="inputzip_code"  id="inputzip_code" type="text" placeholder="Zipcode" value="' . $current_user_data['zip_code'] . '">
</div>
</div>

';



echo '</div>
<div class="m-5 text-center">
<!-- Save changes button-->
<button class="btn btn-success px-5" type="submit">Save Changes</button>
</div>
</form>
</div>
';




#-------------------------------------------------------------------------------
# DISPLAY SOCIAL NETWORK SECTION
#-------------------------------------------------------------------------------

echo '
<!-- SOCIAL NETWORK ACCOUNTS -------------------------------------------------------------------------------------------- -->
<div class="card mb-4 d-none">
<div class="card-header d-flex justify-content-between">
<span>Your Social Media Networks</span>
<!-- Button trigger modal
<button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#instructionsModal">
Instructions
</button>  -->
</div>
<div class="card-body">
<form id="profileupdateForm" method="post">
' . $display->inputcsrf_token() . '
<input name="profileupdate" type="hidden" value="1">


<div class="row gx-3 mb-3">
<small class="mb-2">Used to allow you to auto post your photos/videos</small>

<!-- Form Group (username)-->
<div class="col-md-6">
<label class="small mb-1" for="inputUsername">Username  <span id="availability"></span></label>
<input type="text" class="form-control" name="inputUsername" id="inputUsername" placeholder="Enter your username" value="' . $current_user_data['username'] . '">
</div>



<!-- Form Group (email)-->
<div class="col-md-6">
<label class="small mb-1" for="inputemail">Email Address</label>
<input class="form-control" name="inputemail" id="inputemail" type="email" placeholder="Enter your email address" value="' . $current_user_data['email'] . '">
</div>


</div>
';



echo '</div>
<div class="m-5 text-center">
<!-- Save changes button-->
<button class="btn btn-success px-5" type="submit">Save Changes</button>
</div>
</form>
</div>

';





#-------------------------------------------------------------------------------
# DISPLAY RIGHT COLUMN SECTION
#-------------------------------------------------------------------------------
echo '

    </div>  <!-- end left column -->

';


echo '            <div class="col-lg-4"> <!-- start right column -->';
#breakpoint($plandatafeatures);
if (isset($plandatafeatures['plan_description'] )) {
echo '    <div class="row">

<div class="col-12 mb-4">

<!-- Billing card 1-->
<div class="card h-100 border-start-lg border-start-primary">
<div class="card-body">
    <div class="small text-muted">Your Plan</div>
    <div><span class="h3 me-4">' . $plandatafeatures['displayname'] . '</span> <span class"fs-10">' . $plandatafeatures['plan_description'] . ' (' . $current_user_data['account_product_id'] . ')</span> </div>';
if ($plandatafeatures['upgradeable'] == 'Y') {
    echo '
    <a class="text-arrow-icon small" href="/myaccount/upgrade"> ' . $plandatafeatures['upgradeable_tag'] . '   </a>
    ';
}
echo '
</div>
</div>
</div>

';

}

if (isset($plandatafeatures['renewable'])) {
if ($current_user_data['account_plan'] == 'gold') {
    echo '
<div class="col-12 mb-4">
<!-- Billing card 2-->
<div class="card h-100 border-start-lg border-start-secondary">
<div class="card-body">
';


    if ($plandatafeatures['renewable'] == 'Y') {
        $next_year_date = date("F j, Y", strtotime('+1 year', strtotime($current_user_data['create_dt'])));
        echo '
<div class="small text-muted">Your account will auto-renew</div>
<div class="row">
<div class="col-lg-7">
    
<div class="h3">' . $next_year_date . '
</div>

<!--  <a class="text-arrow-icon small text-secondary" href="/myaccount/billinghistory">  View billing history </a> -->
<!--  <a class="text-arrow-icon small text-danger" href="/cancelplan">  Cancel Plan </a> -->
</div>
    

<div class="col-lg-5">
<div class="d-flex align-items-center">
<!-- <i class="bi bi-credit-card h3"></i> -->
<div class="ms-4">
<div class="small">xxxx</div>
<div class="text-xs text-muted">##/####</div>
</div>
</div>
</div>
</div>
';
    } elseif ($current_user_data['account_plan'] == 'life') {
        echo 'Yea! you have the best plan.';
    }


    echo '
</div>
</div>
</div>
';
}
}


/*
    echo '

        <!-- Form Group (username)-->
        <div >
<label class="small mb-1" for="inputUsername">Username  <span id="availability"></span></label>
<div class="input-group mb-3">
<input type="text" class="form-control" name="inputUsername" id="inputUsername" placeholder="Enter your username" value="'.$current_user_data['username'].'">
<button class="btn btn-outline-secondary ms-0 p-1" type="button" id="checkButton">Check</button>
</div>


        

            <!-- Two factor authentication card-->
            <div class="card mb-4">
                <div class="card-header">Two-Factor Authentication</div>
                <div class="card-body">
                    <p>Add more security to your account by enabling two-factor authentication. We will send you a text message to verify your login attempts on unrecognized devices and browsers.</p>
                    <form>
                    
                        <div class="mt-3">
                            <label class="small mb-1" for="twoFactorSMS">SMS Number</label>
                            <div class="input-group mb-3">
    <input type="text" class="form-control" name="dyn_twoFactorSMS" id="twoFactorSMS" placeholder="Enter your mobile number" value="">
    <button class="btn btn-outline-secondary ms-0 p-1" type="button" id="checkButton">Enroll</button>
</div>

                        </div>
                    </form>
                </div>
            </div>

            */



### ----------------------------------------------------------------------------------------------------------------
echo '
    <div class="col-12 mb-4">
    <!-- Change password card-->
    <div class="card mb-4">
            <div class="card-header">Change Account Password</div>
            <div class="card-body">
                <form action="/myaccount/changepassword" method="post">                           
' . $display->inputcsrf_token() . '
<input name="returnto" type="hidden" value="/myaccount/account">
        <!-- Form Group (current password)-->
        <div class="mb-3">
            <label class="small mb-1" for="inputcurrentPassword">Current Password</label>
            <input class="form-control" name="inputcurrentPassword" id="inputcurrentPassword" type="password" placeholder="Enter current password">
        </div>
        <!-- Form Group (new password)-->
        <div class="mb-3">
            <label class="small mb-1" for="inputnewPassword">New Password</label>
            <input class="form-control"  name="inputnewPassword" id="newPassword" type="password" placeholder="Enter new password">
        </div>
        <!-- Form Group (confirm password)-->
        <div class="mb-3">
            <label class="small mb-1" for="inputconfirmPassword">Confirm Password</label>
            <input class="form-control"  name="inputconfirmPassword" id="inputconfirmPassword" type="password" placeholder="Confirm new password">
        </div>
        <button class="btn btn-primary" type="submit">Save</button>
    </form>
</div>
</div>
</div>
';




### ----------------------------------------------------------------------------------------------------------------
echo '<!-- Settings card-->
<div class="col-12 mb-4">
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-gear" title="Account Settings"></i> More Account Settings</div>
        <div class="card-body">
            <ul class="list-unstyled">
             <!-- Commented out actions for future use -->
                <!-- <li><a class="d-flex justify-content-between align-items-center" href="/myaccount/settings?action=action1">Action <i class="bi bi-chevron-right"></i></a></li>  -->
                <!-- <li><a class="d-flex justify-content-between align-items-center" href="/myaccount/settings?action=action2">Another action <i class="bi bi-chevron-right"></i></a></li>  -->
                <li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/manage-notifications">Notifications <i class="bi bi-chevron-right"></i></a></li>
<li class="mb-3">
    <a class="d-flex justify-content-between align-items-center" href="/myaccount/profile-images">
        <span>
            Profile Images 
            <i class="bi bi-info-circle ms-1 text-muted" data-bs-toggle="tooltip" data-bs-placement="top" title="Change your profile avatar and cover image"></i>
        </span>
        <i class="bi bi-chevron-right"></i>
    </a>
</li>
            <li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/security-settings">Security Settings <i class="bi bi-chevron-right"></i></a></li>

                <li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/manage-mail">BGInbox Settings <i class="bi bi-chevron-right"></i></a></li>';
             
if (!$account->isverified() && $account->isadmin()) {
    echo '<li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/manage-verification">Become Verified <i class="bi bi-chevron-right"></i></a></li>';
}

            echo '
            </ul>
        </div>
    </div>
</div>';




### ----------------------------------------------------------------------------------------------------------------
if ($account->iscconsultant()) {
    echo '<!-- Consultant account card-->
            <div class="col-12 mb-4">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-badge-sd-fill text-success h5" title="Sales Representatives"></i> Sales Representatives</div>
                <div class="card-body">
                    <p>You are a listed as one of our Sales Representatives.</p>
                    <a class="btn btn-success btn-sm me-5" href="/myaccount/cckiosk">Kiosk SignUp</a>
                    ';

    echo '
                    <a class="btn btn-success btn-sm" href="/staff/ccdashboard">View Your Dashboard</a>
                </div>
            </div>
            </div>
            ';
}






### ----------------------------------------------------------------------------------------------------------------
echo '<!-- Delete account card-->
<div class="col-12 mb-4">
<div class="card mb-4">
    <div class="card-header">Data Rights</div>
    <div class="card-body">
        <p>You have a right of access to any personal information we hold about you. 
        You can ask us for a copy of your personal information; confirmation whether your personal information is being used by us; 
        details about how and why it is being used; and details of what safeguards are in place.</p>
        <a class="btn btn-secondary btn-sm" href="/legalhub/datarights?manage">View My Rights</a>
    </div>
</div>
</div>
';


#-------------------------------------------------------------------------------
# DELETE ACCOUNT - CONFIRMATION MODAL DIALOG
#------------------------------------------------------------------------------- 
echo '<!-- Delete account card-->
<div class="col-12 mb-4">
<div class="card mb-4">
    <div class="card-header">Delete Account</div>
    <div class="card-body">
        <p>Deleting your birthday.gold account is a permanent action and cannot be undone. 
        If you are sure you want to delete your account, click the link below.</p>
        <button class="btn btn-danger-subtle text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
        I understand, delete my account
        </button>
    </div>
</div>
</div>
';

echo '<!-- Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Are You Sure?</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="/myaccount/deleteaccount" method="POST">
        ' . $display->inputcsrf_token() . '
            <div class="modal-body">
                <p>Type "delete" in the field below to confirm.</p>
                <input type="text" name="deleteConfirm" id="deleteConfirm" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, I changed my mind</button>
                <button type="submit" class="btn btn-danger" id="confirmDelete">Yes, delete my account</button>
            </div>
        </form>
    </div>
</div>
</div>
';

$content = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmDeleteButton = document.getElementById('confirmDelete');
    const deleteConfirmField = document.getElementById('deleteConfirm');

    confirmDeleteButton.addEventListener('click', function(event) {
        if (deleteConfirmField.value !== 'delete') {
            event.preventDefault();
            alert('You must type \"delete\" to proceed.');
        }
    });
});
</script>
";


echo '
</div>


</div>
</div>


</div>
</div>
';

$footerattribute['postfooter'] = '

<script>
$(document).ready(function() {
$("#uploadBtn").click(function() {
$("#imageUpload").click();
});

$("#imageUpload").change(function() {
$("#uploadForm").submit();
});

' . $jstag_openinstructions . '
});

';

if ($current_user_data['account_type'] == 'parental') {

    $footerattribute['postfooter'] .= '' .
        $display->tooltip('-js-noscriptwrapper-') . '


////  MINOR CONTROLLER CHECKBOXES
document.addEventListener("DOMContentLoaded", (event) => {
    const forms = document.querySelectorAll(\'form[id^="minorform"]\');

    forms.forEach((form) => {
        form.addEventListener("change", (event) => {
            if (event.target.type === "checkbox") {
                event.preventDefault();

                const formData = new FormData(form);
                fetch(form.action, {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Success:", data);
                })
                .catch((error) => {
                    console.error("Error:", error);
                });
            }
        });
    });
});

';
}


$footerattribute['postfooter'] .= '

</script>
<script src="/public/js/myaccount-profile.js?' . date('Ymdis') . '" language="javascript"></script>

'.$display->availabilitycheckjs().'


' . $content . '  <script src="/public/js/passwordhelper.js" language="javascript"></script>

';


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
