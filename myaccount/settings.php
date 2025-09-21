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

/* Security Cards from security-settings */
.security-card {
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 0;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    overflow: hidden;
}

.security-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.security-card-header {
    padding: 1.5rem;
    background: #e9ecef;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: nowrap;
    gap: 1rem;
}

.security-card-icon {
    font-size: 2rem;
    margin-right: 1rem;
    color: #495057;
}

.security-card-title {
    display: flex;
    align-items: center;
    margin: 0;
    flex-shrink: 1;
    min-width: 0;
}

.security-card-title h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #212529;
    white-space: nowrap;
}

.security-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    white-space: nowrap;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.security-card-body {
    padding: 1.5rem;
}

/* Pill-shaped buttons */
.btn {
    border-radius: 25px !important;
    padding: 0.5rem 1.5rem !important;
    font-weight: 500;
}

.btn-sm {
    border-radius: 20px !important;
    padding: 0.25rem 1rem !important;
}

.btn-lg {
    border-radius: 30px !important;
    padding: 0.75rem 2rem !important;
}

/* Input group buttons need special handling */
.input-group .btn {
    border-radius: 0 25px 25px 0 !important;
}

/* Floating Label Styles */
.floating-label-group {
    position: relative;
    margin-bottom: 1.5rem;
}

.floating-input {
    background: transparent;
    border: none;
    border-bottom: 2px solid #e9ecef;
    border-radius: 0;
    padding: 1.625rem 0 0.625rem 0;
    font-size: 1rem;
    line-height: 1.5;
    transition: all 0.3s ease;
    width: 100%;
    height: 3.75rem;
}

.floating-input:focus {
    outline: none;
    border-bottom-color: var(--bs-primary);
    box-shadow: none;
}

.floating-input.is-invalid {
    border-bottom-color: #dc3545;
}

.floating-label {
    position: absolute;
    left: 0;
    top: 1rem;
    color: #6c757d;
    font-size: 1rem;
    transition: all 0.3s ease;
    pointer-events: none;
    transform-origin: left top;
}

/* Float label when input is focused or has content */
.floating-input:focus + .floating-label,
.floating-input:not(:placeholder-shown) + .floating-label {
    transform: translateY(-1.25rem) scale(0.85);
    color: var(--bs-primary);
}

.floating-input:focus.is-invalid + .floating-label,
.floating-input:not(:placeholder-shown).is-invalid + .floating-label {
    color: #dc3545;
}

/* Desktop-specific adjustments */
@media (min-width: 992px) {
    .floating-input {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1.625rem 1rem 0.625rem 1rem;
        background: white !important;
        transition: all 0.2s ease;
        height: calc(3.75rem + 2px);
    }
    
    .floating-input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }
    
    .floating-label {
        left: 1rem;
        top: 1.125rem;
    }
    
    .floating-input:focus + .floating-label,
    .floating-input:not(:placeholder-shown) + .floating-label {
        transform: translateY(-1.1rem) scale(0.85);
    }
}

/* Mobile optimizations for floating labels */
@media (max-width: 576px) {
    .floating-input {
        font-size: 16px; /* Prevent zoom on iOS */
    }
}

/* Select/dropdown styling with floating labels */
.floating-label-group select.floating-select {
    padding-top: 1.875rem;
    padding-bottom: 0.625rem;
    height: 3.75rem;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3e%3cpolyline points=\'6 9 12 15 18 9\'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1em;
    padding-right: 2.5rem;
}

/* Make floating label work with selects */
.floating-label-group select.floating-select ~ .floating-label {
    transform: translateY(-1.2rem) translateX(-0.25rem) scale(0.85);
    color: var(--bs-primary);
    background: none;
    padding: 0 0.25rem;
    left: 0.75rem;
}

/* Mobile: Move dropdown labels to the left */
@media (max-width: 991px) {
    .floating-label-group select.floating-select ~ .floating-label {
        left: 0;
        transform: translateY(-1.2rem) translateX(0) scale(0.85);
    }
}

/* Desktop styles for floating select */
@media (min-width: 992px) {
    .floating-label-group select.floating-select {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: white;
        height: calc(3.75rem + 2px);
    }
    
    .floating-label-group select.floating-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
}

/* Input group with floating labels */
.floating-label-group .input-group {
    position: relative;
}

.floating-label-group .input-group .floating-input {
    padding-top: 1.625rem;
    padding-bottom: 0.625rem;
    height: 3.75rem;
}

.floating-label-group .input-group .btn {
    height: 3.75rem;
    border-left: 0;
    padding: 0 1rem;
}

/* Position label for input groups */
.floating-label-group .input-group ~ .floating-label {
    transform: translateY(-1.25rem) scale(0.85);
    z-index: 10;
    color: var(--bs-primary);
}

/* Desktop specific input group styling */
@media (min-width: 992px) {
    .floating-label-group .input-group .floating-input {
        border-radius: 0.375rem 0 0 0.375rem;
    }
    
    .floating-label-group .input-group .btn {
        border-radius: 0 0.375rem 0.375rem 0;
        border: 1px solid #dee2e6;
        border-left: 0;
    }
    
    .floating-label-group .input-group .floating-input:focus + .btn {
        border-color: #86b7fe;
    }
}

/* Mobile styles for input group with floating labels */
@media (max-width: 991px) {
    .floating-label-group .input-group .btn {
        border: none;
        border-bottom: 2px solid #e9ecef;
        border-radius: 0;
        background: transparent;
        color: var(--bs-primary);
    }
}



/* Fix label positioning for input groups */
.input-group ~ .floating-label {
    z-index: 10;
    pointer-events: none;
}

/* Account switch buttons */
.accountswitch {
    border-radius: 25px !important;
}

/* Remove background from wrapper cards */
.card.bg-transparent {
    background-color: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

/* Make certain cards transparent */
.wrapper-card {
    background-color: transparent !important;
    border: none !important;
}

/* Remove background from main container */
.main-content {
    background-color: transparent !important;
}

/* Remove any default Bootstrap container backgrounds */
.container {
    background-color: transparent !important;
}

/* Ensure body has no conflicting background */
body {
    background-color: #f8f9fa !important; /* Light gray background */
}

/* Remove background from any outer wrapper divs */
#bodyContentWrapper {
    background-color: transparent !important;
}
</style>
';
#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header2.inc');
$bodycontentclass='';

// Add v7 theme CSS
$additionalstyles .= '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-gear me-3"></i>Account Settings</h1>
            <p class="lead mb-0">Manage your account preferences and privacy settings</p>
        </div>
    </div>
</div>

<?php
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
<div class="security-card">
        <div class="security-card-header">
            <div class="security-card-title">
                <i class="bi bi-people-fill security-card-icon"></i>
                <h3>Minor Accounts</h3>
            </div>
            <div class="security-status">
                <span class="badge rounded-pill bg-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $minorcount . ' Child Accounts">' . $minorcount . ' Active</span>
            </div>
        </div>
        <div class="security-card-body px-0">
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


echo '

<!-- Plan Feature card-->
<div class="security-card">
    <div class="security-card-header">
        <div class="security-card-title">
            <i class="bi bi-award-fill security-card-icon"></i>
            <h3>Plan Features</h3>
        </div>
    </div>
    <div class="security-card-body px-0">
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
<div class="security-card">
<div class="security-card-header">
    <div class="security-card-title">
        <i class="bi bi-person-badge-fill security-card-icon"></i>
        <h3>Account Details</h3>
    </div>
</div>
<div class="security-card-body">
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Note:</strong> The Account Details are used to manage your account which the details are allowed to be different from your Enrollment Profile details.
</div>
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
<div class="floating-label-group">
<select name="inputtitle" id="inputtitle" class="form-control floating-input floating-select">
' . $display->list_title($current_user_data['title']) . '
</select>
<label for="inputtitle" class="floating-label">Title</label>
</div>
</div>


<!-- Form Group (first name)-->
<div class="col-md-4">
<div class="floating-label-group">
<input class="form-control floating-input" name="inputfirst_name" id="inputfirst_name" type="text" placeholder=" " value="' . $current_user_data['first_name'] . '">
<label for="inputfirst_name" class="floating-label">First name</label>
</div>
</div>
<!-- Form Group (middle name)-->
<div class="col-md-2">
<div class="floating-label-group">
<input class="form-control floating-input" name="inputmiddle_name" id="inputmiddle_name" type="text" placeholder=" " value="' . $current_user_data['middle_name'] . '">
<label for="inputmiddle_name" class="floating-label">Middle name</label>
</div>
</div>
<!-- Form Group (last name)-->
<div class="col-md-4">
<div class="floating-label-group">
<input class="form-control floating-input" name="inputlast_name" id="inputlast_name" type="text" placeholder=" " value="' . $current_user_data['last_name'] . '">
<label for="inputlast_name" class="floating-label">Last name</label>
</div>
</div>
</div>

<div class="row gx-3 mb-3">
<!-- Form Group (gender)-->
<div class="col-md-4">
<div class="floating-label-group">
<select name="inputgender" id="inputgender" class="form-control floating-input floating-select">
' . $display->list_gender($current_user_data['gender']) . '
</select>
<label for="inputgender" class="floating-label">Gender</label>
</div>
</div>
</div>

';



$passgenerator = '';

echo '
<hr class="mt-4 mb-4">
<!-- Contact Details -------------------------------------------------------------------------------------------- -->
<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">User Account Details:</h4></div>
<!-- Form Group (username)-->
<div class="col-md-6">
<div class="floating-label-group">
<div class="input-group">
<input type="text" class="form-control floating-input" name="inputUsername" id="inputUsername" placeholder=" " value="' . $current_user_data['username'] . '">
<button class="btn btn-outline-secondary" type="button" id="checkButton">Check</button>
</div>
<label for="inputUsername" class="floating-label">Username</label>
<small class="text-muted" id="availability"></small>
</div>
</div>


<!-- Form Group (email)-->
<div class="col-md-6">
<div class="floating-label-group">
<input class="form-control floating-input" name="inputemail" id="inputemail" type="email" placeholder=" " value="' . $current_user_data['email'] . '">
<label for="inputemail" class="floating-label">Email Address</label>
</div>
</div>



<!-- Form Group (phone_number)-->
<div class="col-md-6">
<div class="floating-label-group">
<input class="form-control floating-input" name="inputphone_number" id="inputphone_number" type="tel" placeholder=" " value="' . $current_user_data['phone_number'] . '">
<label for="inputphone_number" class="floating-label">Mobile Phone Number</label>
</div>
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
<div class="floating-label-group">
<input class="form-control floating-input" name="inputmailing_address" id="inputmailing_address" type="text" placeholder=" " value="' . $current_user_data['mailing_address'] . '">
<label for="inputmailing_address" class="floating-label">Mailing Address</label>
</div>
</div>

</div>
<!-- Form Row        -->
<div class="row gx-3 mb-3">

<!-- Form Group (location)-->
<div class="col-md-4">
<div class="floating-label-group">
<input class="form-control floating-input" name="inputCity" id="inputCity" type="text" placeholder=" " value="' . $current_user_data['city'] . '">
<label for="inputCity" class="floating-label">City</label>
</div>
</div>



<!-- Form Group (state)-->
<div class="col-md-4">
<div class="floating-label-group">
<select name="inputState" id="inputState" class="form-control floating-input floating-select" aria-label=".form-select example">
' . $display->list_state($state) . '
</select>
<label for="inputState" class="floating-label">State</label>
</div>
</div>
<!-- Form Group (location)-->
<div class="col-md-4">
<div class="floating-label-group">
<input class="form-control floating-input" name="inputzip_code" id="inputzip_code" type="text" placeholder=" " value="' . $current_user_data['zip_code'] . '">
<label for="inputzip_code" class="floating-label">Zipcode</label>
</div>
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
<div class="security-card-body">
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
<div class="floating-label-group">
<input class="form-control floating-input" name="inputemail" id="inputemail" type="email" placeholder=" " value="' . $current_user_data['email'] . '">
<label for="inputemail" class="floating-label">Email Address</label>
</div>
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
<div class="security-card-body">
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
<div class="security-card-body">
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
            <div class="security-card">
                <div class="security-card-header">
                    <div class="security-card-title">
                        <i class="bi bi-shield-lock-fill security-card-icon"></i>
                        <h3>Two-Factor Authentication</h3>
                    </div>
                    <div class="security-status">
                        <span class="status-badge status-inactive">Not Enabled</span>
                    </div>
                </div>
                <div class="security-card-body">
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
    <div class="security-card">
            <div class="security-card-header">
                <div class="security-card-title">
                    <i class="bi bi-key-fill security-card-icon"></i>
                    <h3>Change Account Password</h3>
                </div>
            </div>
            <div class="security-card-body">
                <form action="/myaccount/changepassword" method="post">                           
' . $display->inputcsrf_token() . '
<input name="returnto" type="hidden" value="/myaccount/account">
        <!-- Form Group (current password)-->
        <div class="floating-label-group">
            <input class="form-control floating-input" name="inputcurrentPassword" id="inputcurrentPassword" type="password" placeholder=" ">
            <label for="inputcurrentPassword" class="floating-label">Current Password</label>
        </div>
        <!-- Form Group (new password)-->
        <div class="floating-label-group">
            <input class="form-control floating-input" name="inputnewPassword" id="newPassword" type="password" placeholder=" ">
            <label for="inputnewPassword" class="floating-label">New Password</label>
        </div>
        <!-- Form Group (confirm password)-->
        <div class="floating-label-group">
            <input class="form-control floating-input" name="inputconfirmPassword" id="inputconfirmPassword" type="password" placeholder=" ">
            <label for="inputconfirmPassword" class="floating-label">Confirm Password</label>
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
    <div class="security-card">
        <div class="security-card-header">
            <div class="security-card-title">
                <i class="bi bi-gear security-card-icon"></i>
                <h3>More Account Settings</h3>
            </div>
        </div>
        <div class="security-card-body">
            <ul class="list-unstyled">
                <li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/account"><div><i class="bi bi-pencil-square me-2"></i>Account Settings</div> <i class="bi bi-chevron-right"></i></a></li>
                <li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/notifications#settings"><div><i class="bi bi-bell me-2"></i>Manage Notifications</div> <i class="bi bi-chevron-right"></i></a></li>
                <li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/security-settings"><div><i class="bi bi-shield-lock me-2"></i>Security Settings</div> <i class="bi bi-chevron-right"></i></a></li>
                <li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/parental-mode"><div><i class="bi bi-person me-2"></i>Parental Mode</div> <i class="bi bi-chevron-right"></i></a></li>
                <li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/invite"><div><i class="bi bi-hand-thumbs-up me-2"></i>Invite Friends</div> <i class="bi bi-chevron-right"></i></a></li>
                <li class="mb-3">
                    <a class="d-flex justify-content-between align-items-center" href="/myaccount/profile-images">
                        <div>
                            <i class="bi bi-images me-2"></i>Profile Images 
                            <i class="bi bi-info-circle ms-1 text-muted" data-bs-toggle="tooltip" data-bs-placement="top" title="Change your profile avatar and cover image"></i>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>';

if (!empty($current_user_data['feature_email'])) {
    echo '<li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/mail-box#settings"><div><i class="bi bi-envelope me-2"></i>BGInbox Settings</div> <i class="bi bi-chevron-right"></i></a></li>';
}
             
if (!$account->isverified() && $account->isadmin()) {
    echo '<li class="mb-3"><a class="d-flex justify-content-between align-items-center" href="/myaccount/manage-verification"><div><i class="bi bi-patch-check me-2"></i>Become Verified</div> <i class="bi bi-chevron-right"></i></a></li>';
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
            <div class="security-card">
                <div class="security-card-header">
                    <div class="security-card-title">
                        <i class="bi bi-badge-sd-fill text-success security-card-icon"></i>
                        <h3>Sales Representatives</h3>
                    </div>
                </div>
                <div class="security-card-body">
                    <p>You are a listed as one of our Sales Representatives.</p>
                    <a class="btn btn-success btn-sm me-5 mb-2" href="/myaccount/cckiosk">Kiosk SignUp</a>
                    ';

    echo '
                    <a class="btn btn-success btn-sm mb-2" href="/staff/ccdashboard">View Your Dashboard</a>
                </div>
            </div>
            </div>
            ';
}






### ----------------------------------------------------------------------------------------------------------------
echo '<!-- Delete account card-->
<div class="col-12 mb-4">
<div class="security-card">
    <div class="security-card-header">
        <div class="security-card-title">
            <i class="bi bi-database-lock security-card-icon"></i>
            <h3>Data Rights</h3>
        </div>
    </div>
    <div class="security-card-body">
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
<div class="security-card">
    <div class="security-card-header">
        <div class="security-card-title">
            <i class="bi bi-trash3-fill text-danger security-card-icon"></i>
            <h3>Delete Account</h3>
        </div>
    </div>
    <div class="security-card-body">
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
                <div class="floating-label-group">
                    <input type="text" name="deleteConfirm" id="deleteConfirm" class="form-control floating-input" placeholder=" ">
                    <label for="deleteConfirm" class="floating-label">Type "delete" to confirm</label>
                </div>
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

echo '
            </div> <!-- end right column -->
        </div> <!-- end row -->
    </div> <!-- end container -->
';

$footerattribute['postfooter'] .= '

</script>
<script src="/public/js/myaccount-profile.js?' . date('Ymdis') . '" language="javascript"></script>

'.$display->availabilitycheckjs().'


' . $content . '  <script src="/public/js/passwordhelper.js" language="javascript"></script>

';


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
