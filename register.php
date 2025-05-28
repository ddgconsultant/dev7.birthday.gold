<?php
$addClasses[] = 'fileuploader';
$addClasses[] = 'createaccount';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREDEFINED VARIABLES
#-------------------------------------------------------------------------------
$requiredfields = ['first_name', 'last_name', 'accountemail', 'password', 'birthday', 'terms'];
//initiate $requiredfields;
foreach ($requiredfields as $field) {$$field = '';}
$email='';
$promo_code = $referrer = '';
$understand = '';
$understandflag = false;
$errormessage = '';
$continue = false;
$promoinputtag = $planinputtag = '';
$signupmode = '';
$buttonsize = '';
$signupexit = '';
$displayline = true;
$terms = false;
$error = false;
$session->unset('userregistrationdata');
$session->unset('ALERT_existing_record');
$session->unset('ALERT_existing_record-user');
$session->unset('ALERT_existing_record-pass');

#-------------------------------------------------------------------------------
# SIGN UP MODE
#-------------------------------------------------------------------------------
$signupmode = $session->get('signupmode', isset($_GET['signupmode']) ? $_GET['signupmode'] : '');
if ($signupmode != '') {
$headerattribute['rawheader'] = true;
$buttonsize = 'btn-lg';
$signupexit = '<a href="/logout"><i class="bi bi-x-square text-info m-1"></i></a>';
$footerattribute['rawfooter'] = true;
$displayline = false;
$session->set('signupmode', $signupmode);
$terms = 1;
if ($session->get('referral_userid', '') == '') $session->set('referral_userid', $current_user_data['user_id']);
}



#-------------------------------------------------------------------------------
# HANDLE PLAN REDIRECT
#-------------------------------------------------------------------------------
if (isset($_GET['plan'])) {
$planinputtag = '<input type="hidden" name="plan" value="' . $_GET['plan'] . '">';
if (isset($_GET['promocode']) && $_GET['plan'] == 'gold') {
$promoinputtag = '<input type="hidden" name="promocode" value="' . $_GET['promocode'] . '">';
}
}



#-------------------------------------------------------------------------------
# HANDLE NO MASTER REGISTER DATA
#-------------------------------------------------------------------------------
$signup_process = $session->get('signup_process_data');
if (empty($signup_process)) {
$pagemessage = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Select a sign up option.</div>';
$transferpage['message'] = $pagemessage;
$transferpage['url'] = '/signup';
$system->endpostpage($transferpage);
exit;
}



#-------------------------------------------------------------------------------
# HANDLE THE REGISTRATION ATTEMPT
#-------------------------------------------------------------------------------
if ($formdata = $app->formposted()) {
  session_tracking("Processing form data: " , $_REQUEST);
$continue = false;
$errormessage = '<div class="alert alert-danger">Your account information does not seem valid.</div>';
// Get form data
$foundformfieldcount = 0;

$formdata['birthday'] = $app->getformdate();
$signup_process = $session->get('signup_process_data', []);


foreach ($requiredfields as $field) {
if (isset($formdata[$field])) {
$$field = trim($formdata[$field]);
$foundformfieldcount++;
}
}
$email = $formdata['accountemail'];

#------------------------------------
// Must agree to terms
if (!isset($formdata['terms'])) {
$errormessage = '<div class="alert alert-danger">You must agree to the terms and conditions to sign up.</div>';
$continue = false;
$error = true;
goto displaypage;
}

#------------------------------------
// Must provide all required fields
if ($foundformfieldcount < count($requiredfields) || count(array_filter($formdata)) < count($requiredfields)) {
$continue = false;
$error = true;
$errormessage = '<div class="alert alert-danger">You must provide all the required fields. (' . count(array_filter($formdata)) . '/' . count($requiredfields) . ')</div>';
goto displaypage;
}

#------------------------------------
// Must provide a valid birthday
$birthday = (str_replace(' ', '', $birthday));
#$birthday_date = DateTime::createFromFormat('m/d/Y', $birthday);
$birthday_date = DateTime::createFromFormat('Y-m-d', $birthday);
if (!$birthday_date) {
$errormessage = '<div class="alert alert-danger">Invalid date for birthday - ' . $birthday . ' / ' .  $birthday_date . '</div>';
$continue = false;
$error = true;
goto displaypage;
}

#------------------------------------
// Creates a DateTime object for the current date
$today = new DateTime();
if ($birthday_date > $today) {
$errormessage = '<div class="alert alert-danger">Sorry, you must be born - your birthday cannot be greater than today.</div>';
$continue = false;
$error = true;
goto displaypage;
}

#------------------------------------
// Check if the birthday is more than 150 years in the past
$too_old = clone $today;
$too_old->modify('-150 years');
if ($birthday_date < $too_old) {
$errormessage = '<div class="alert alert-danger">Dang! You are old - you can\'t be older than 150 years old.</div>';
$continue = false;
$error = true;
goto displaypage;
}

#------------------------------------
// Check if the birthday is more than 21 years 
$old_enough = clone $today;
$old_enough->modify('-21 years');
if ($birthday_date > $old_enough && $signup_process['account_type'] == 'parental') {
$errormessage = '<div class="alert alert-danger">Sorry! You must be at least 21 years old for our Parental Accounts.</div>';
$continue = false;
$error = true;
goto displaypage;
}

#------------------------------------
// Check if the birthday is less than 13 years 
$young_person = clone $today;
$young_person->modify('-13 years');
if ($birthday_date > $young_person) {
$errormessage = '<div class="alert alert-danger">Sorry! You must be at least 13 years old to access our services.  Please refer to our <a href="/legalhub/terms" class="text-dark fw-bold">Terms & Conditions</a></div>';
$continue = false;
$error = true;
goto displaypage;
}

#------------------------------------
// Check if the birthday is less than 18 years 
$understand = $formdata['understand'] ?? '';
$young_person = clone $today;
$young_person->modify('-18 years');
if ($birthday_date >= $young_person && ($signup_process['account_type'] != 'parental' || $signup_process['account_plan'] != 'free') && $understand == '') {
$errormessage = '<div class="alert alert-warning">Note: Being under 18 years old may restrict you from a number of businesses. Click I understand to proceed.</div>';
$continue = false;
$error = true;
$understandflag = true;
goto displaypage;
}

#------------------------------------
// format values
$birthday_formatted = $birthday_date->format('Y-m-d');
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$first_name = ucfirst($first_name);
$last_name = ucfirst($last_name);

#------------------------------------
// Check if the email is already in use
$email = trim(strtolower($email));
$response = $createaccount->isemailaccountavailable($email);
#breakpoint($response);

#-------------------------------------------------------------------------------
# HANDLE A PREVIOUS SIGN UP
# let's check for already registered/validated users and accept/bypass
#-------------------------------------------------------------------------------

if ($response !== true) {  // We found a record
    $tempinfo = $response; // We already have the full record
    
    // Block these statuses immediately
    if (empty($tempinfo['status']) || 
        $tempinfo['status'] == 'duplicate' || 
        $tempinfo['status'] == 'active' || 
        $tempinfo['status'] == 'deleted') {
        $errormessage = '<div class="alert alert-danger">Email address cannot be used. It\'s seems to already be in our system.</div>';
        $continue = false;
        $error = true;
        goto displaypage;
    }
    
    // Handle in-progress signup for other statuses
    if (!empty($tempinfo['status'])) {
        session_tracking('found existing record', $tempinfo);
        $tempinfo = array_merge($tempinfo, [
            'ALERT_existing_record' => 'found',
            'ALERT_existing_record-user' => $email,
            'ALERT_existing_record-pass' => $password]);

        $session->set('userregistrationdata', $tempinfo);
        $pagemessage = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Picking up where you left off.</div>';
        $transferpage['message'] = $pagemessage;
        $transferpage['url'] = '/validate-account';

        if ($signup_process['account_verification'] == 'notrequired') {
            $transferpage['url'] = '/checkout?u=' . $qik->encodeId($tempinfo['user_id']);
        }
        if ($tempinfo['status'] == 'validated') {
            $transferpage['url'] = '/checkout?u=' . $qik->encodeId($tempinfo['user_id']);
        }
        $system->endpostpage($transferpage);
        exit;
    }
}


if (!$error) {
#------------------------------------
### GENERATE USERNAME
$username = $createaccount->generate_username($first_name, $last_name, $birthday);


#------------------------------------
## REGISTER USER
$client_locationdata = $session->get('client_locationdata');
$city = trim((!empty($client_locationdata['city']) ? $client_locationdata['city'] : ''));
$state = trim(!empty($client_locationdata['regionName']) ? $client_locationdata['regionName'] : '');
$zip_code = trim(!empty($client_locationdata['zip']) ? $client_locationdata['zip'] : '');
$type = 'real';
$accountcosts = ['free' => 0, 'gold' => 1000, 'life' => 4000];
$signup_process['account_plan'] = $signup_process['account_plan'] ?? 'free';
if ($signup_process['account_plan'] == 'free') $signup_process['account_type'] = 'user';
if ($signup_process['account_type'] == 'individual') $signup_process['account_type'] = 'user';
$account_cost = $signup_process['account_cost'] ?? $accountcosts[$signup_process['account_plan']];





#-------------------------------------------------------------------------------
// HANDLE MAGIC TEST ACCOUNTS
$testAccountSuffixes = [
'tester@ddg.mx',
'@bdtest.xyz',
// Add more suffixes as needed
];

$isMagicTestAccount = false;
$additional_columns= $additional_values='';
$additional_params= [];
foreach ($testAccountSuffixes as $suffix) {
if (substr($email, -strlen($suffix)) === $suffix) {
$isMagicTestAccount = true;
$type = 'test';
session_tracking('Magic Test Account FOUND', $email);

// Step 1: Fetch the HTML content of the page
$url = 'https://www.randomlists.com/random-addresses?qty=1';
$html = file_get_contents($url);

// Step 2: Extract the address details using regex
preg_match('/<span class="address">(.+?)<\/span>/', $html, $matches);

if (!empty($matches)) {
$address = $matches[1];
// Split the address into city, state, and zip code
preg_match('/(.+), ([A-Z]{2}) (\d{5})/', $address, $address_parts);
if (!empty($address_parts)) {
$city = $address_parts[1];
$state = $address_parts[2];
$zip_code = $address_parts[3];
}

// Fetch the HTML content of the phone number page
$phone_url = 'https://www.randomlists.com/phone-numbers?dup=false&qty=1';
$phone_html = file_get_contents($phone_url);

// Extract the phone number using regex
preg_match('/<li>(.+?)<\/li>/', $phone_html, $phone_matches);

if (!empty($phone_matches)) {
$phone_number = preg_replace('/[^0-9]/', '', $phone_matches[1]);
// phone number

$profile_phone_type = rand(0, 1) ? 'iphone' : 'android';
$additional_params = [
'phone_number' => $phone_number,
'profile_phone_type' => $profile_phone_type
];
$additional_columns .= ', phone_number, profile_phone_type';
$additional_values .= ', :phone_number, :profile_phone_type';

} 
}
$username= $createaccount->generate_username($first_name, $last_name, $birthday, 'test');
break;

}
}


#-------------------------------------------------------------------------------
// grab Avatar
$avatar_file = $display->generateAvatarUrl($fileuploader);
if (is_array($avatar_file)) $avatar_file = '/public/avatars/problemavatar.png';

$account_plan= $signup_process['account_plan'] ?? 'free';
$account_type= $signup_process['account_type'] ?? 'notset';
$account_product_id=$signup_process['plandata']['id'] ?? null;
$productdata=$app->getProduct( $account_product_id, 'PRODUCT_ID' );
if (empty($productdata)) {
$errormessage = '<div class="alert alert-danger">Invalid account plan or type.</div>';
$continue = false;
$error = true;
goto displaypage;
}
$account_cost=$productdata['price'] ?? 0;
$promodata=$app->getpromocode();

// set the params
$input = array(
'first_name' => $first_name,
'last_name' => $last_name,
'username' => $username,
'email' => $email,
'profile_first_name' => $first_name,
'profile_last_name' => $last_name,
'profile_username' => $username,
'profile_email' => $email,
'hashed_password' => $hashed_password,
'birthday' => $birthday_formatted,
## account location
'city' => $city,
'state' => $state,
'zip_code' => $zip_code,
## profile location
'city2' => $city,
'state2' => $state,
'zip_code2' => $zip_code,
'type' => $type,
'product_id' =>$productdata['id'] ?? null,
'account_plan' => $account_plan,
'account_type' =>  $account_type,
'account_cost' =>  $account_cost,
'account_validation' =>  $signup_process['account_verification'] ?? 'required',
'avatar_file' => $avatar_file,
//':coverbanner'=>$coverbanner
);

$input= array_merge($input, $additional_params);
session_tracking('create user params-register', $input);

## ADD THE USER
if (isset($_POST['plan'])) {
    $input['plan'] = $_POST['plan'];
    if (isset($_POST['promocode']) && $_POST['plan'] == 'gold') {
    $input['promocode'] = $_POST['promocode'];
    }
    }


    $input['birthday_month']=$birthday_date->format('m');

    $input['user_id']=$lastId =$createaccount->create_user($input);

/*
$sql = "INSERT INTO bg_users (first_name, last_name, username, email, password, birthdate, `status`, city, state, zip_code, 
account_product_id, account_plan, account_type, account_cost, account_verification, `type`,
profile_first_name, profile_last_name, profile_username, profile_email,
profile_city, profile_state, profile_zip_code, avatar, create_dt, modify_dt ".$additional_columns.")
VALUES (:first_name, :last_name, :username, :email, :hashed_password, :birthday, 'pending', :city, :state, :zip_code, 
:product_id, :account_plan, :account_type, :account_cost, :account_validation, :type,
:profile_first_name, :profile_last_name, :profile_username, :profile_email,
:city2, :state2, :zip_code2, :avatar, now(), now() ".$additional_values.")";
$stmt = $database->query($sql, $params);
$lastId = $database->lastInsertId();
*/

/// ADD MORE DETAILS TO userregistrationdata
#$params['user_id'] = $lastId;



/*

// ADD create time, cover banner, avatar to bg_user_attributes
$coverbanner = '/public/images/site_covers/cbanner_' . $birthday_date->format('m') . '.jpg';
$sql = "
INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, category, create_dt, modify_dt)
VALUES 
(:user_id, 'created', 'timeline', '', 'active', 100, null, NOW(), NOW()),
(:user_id2, 'profile_image', 'avatar', :avatar, 'active', 100, 'primary', NOW(), NOW()),
(:user_id3, 'profile_image', 'account_cover', :banner, 'active', 100, 'primary', NOW(), NOW())
";
$stmt = $database->query($sql, [':user_id' => $params['user_id'], ':user_id2' => $params['user_id'], ':user_id3' => $params['user_id'], ':avatar' => $avatar_file, ':banner' => $coverbanner]);
*/



#-------------------------------------------------------------------------------
// ADD THE USER TO THE TRANSACTIONS TABLE
$params_transaction = array(
':user_id' =>  $input['user_id'],
':product_id' => $productdata['id'] ?? null,
':promo_id' => $promodata['promo_id'] ?? null,

':amount' => $account_cost,
':currency' => 'USD',
':payment_status' => 'pending',
':payment_message' => $payment_message,
':transaction_status' => 'pending',
);

session_tracking('create transaction params', $params_transaction);
$sql = "INSERT INTO bg_transactions
(user_id,  product_id  , promo_id, amount, currency, payment_status, payment_message, transaction_status,  create_dt, modify_dt) 
VALUES 
(:user_id,  :product_id , :promo_id, :amount, :currency, :payment_status, :payment_message, :transaction_status,  NOW(), NOW())";

$stmt = $database->query($sql, $params_transaction);

// Get the last inserted ID if needed
$invoice_id = $database->lastInsertId();



#-------------------------------------------------------------------------------
// MANAGE REFERRAL/CONSULTANT - record the payout
#-------------------------------------------------------------------------------
// Initialize referral_user_id to null
$referral_user_id = null;
$params=$input;
// Determine the referral_user_id based on priority: referral_userid, current_user_data, request['referredby']
if (!empty($session->get('referral_userid'))) {
$referralsetby = 'session-referral_userid';
$referral_user_id = $session->get('referral_userid');
} elseif (!empty($current_user_data['userid'])) {
$referralsetby = 'current_user_data-userid';
$referral_user_id = $current_user_data['userid'];
} elseif (!empty($_REQUEST['referredby'])) {
$referralsetby = 'request-referredby';
$referral_user_id = $_REQUEST['referredby'];
}

// If a referral_user_id is determined, proceed with inserting the record
if ($referral_user_id !== null) {
session_tracking('referral_user_id set by: ' . $referralsetby . ' = ' . $referral_user_id);
$params['referral_user_id'] = $referral_user_id;
$params['referral_payout'] = '0';
$accounttypetag = $signup_process['account_type'] . '|' . $signup_process['account_plan'];
$referral_payout = $database->fetchOne('SELECT IFNULL(description, 0) AS payout FROM bg_user_attributes WHERE user_id = :referral_user_id AND type = "referral_payout" AND `name` = :accounttypetag AND `status` = "A"', [':referral_user_id' => $referral_user_id, ':accounttypetag' => $accounttypetag]);

$sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt)
VALUES (:referral_userid, 'referred', :user_id, :plan, 'pending', :payout, NOW(), NOW())";
$stmt = $database->query($sql, [':referral_userid' => $referral_user_id, ':user_id' => $params['user_id'], ':plan' => $accounttypetag, ':payout' => $referral_payout['payout']]);
}



#-------------------------------------------------------------------------------
# REGISTRATION SUCCESSFUL -- move on to checkout or account validation
#-------------------------------------------------------------------------------
##
## remove current user data
$account->logout();

## remove colon from keys
$new_keys = array_map(function ($key) {
return ltrim($key, ':');
}, array_keys($params));
$final_params = array_combine($new_keys, array_values($params));
$final_params = array_merge($signup_process, $final_params);
$input = [
'name' => 'userregistrationdata',
'description' => json_encode($final_params)
];
$account->setUserAttribute($params['user_id'], $input);
$session->set('userregistrationdata', $final_params);
$checkoutlink='?t='.$qik->encodeId($invoice_id);

switch ($signup_process['account_verification']) {
#--------------------------------------------
case 'notrequired':
// for accounts = gift certificates
$transferpage['message'] = '';
$transferpage['url'] = '/checkout'.$checkoutlink;
$system->endpostpage($transferpage);
exit;
#--------------------------------------------
case 'testernotrequired':
// for test accounts
$transferpage['message'] = '';
$transferpage['url'] = '/checkout'.$checkoutlink;
$system->endpostpage($transferpage);
exit;
#--------------------------------------------

default:
// Last insert ID is a number, redirect
$transferpage['message'] = '';
$transferpage['url'] = '/validate-account';
if ($signup_process['account_verification'] == 'notrequired') {
$transferpage['url'] = '/checkout'.$checkoutlink;
}
$system->endpostpage($transferpage);
exit;
}
}

} // end form posted


#-------------------------------------------------------------------------------
# DISPLAY THE REGISTRATION PAGE
#-------------------------------------------------------------------------------
displaypage:

$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);


#-------------------------------------------------------------------------------
# ROUTE TO REGISTER
#-------------------------------------------------------------------------------

$signup_process = $session->get('signup_process_data', []);
$signup_process = array_merge($signup_process, $_GET);


$additionalstyles.= '
<link rel="stylesheet" href="/public/css/login.css">
<style>
.feature {
margin: auto;
width: 100px;  /* Set width */
height: 100px;  /* Set height */
display: flex;
align-items: center;
justify-content: center;
border: 1px solid white;
background-color: rgba(255, 255, 255, 0.2);  /* White background with 20% transparency */
}

.feature i {
font-size: 56px;  /* Increase icon size */
}

@media (max-width: 576px) { /* Bootstrap sm breakpoint */
.h6-sm {
font-size: 1.1rem !important; /* Size you want for sm */
}
}


.ajax-message-closer {
    margin-top: -14px; /* Adjust this value to move the message closer */
    padding-top: 0;
    font-style: italic; /* Makes the text italic */
}


</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


## HANDLE APPLE DATE UI ISSUES
##---------------------------------------------
$formpadding = 'my-3 mb-sm-0';
$titlepadding = 'mt-1 mb-5 pb-1';
$containerpadding = 'py-1';
$headerpadding = 'p-md-5';
$formtype = 'form-floating mb-3';
#$formtype='form-control';
if ($display->isappledevice()) {
$formpadding = 'my-3 mb-sm-0';
$titlepadding = 'mt-0 mb-3 pb-1';
$containerpadding = 'py-3';
$headerpadding = 'p-md-2';
}


$signup_process['account_type'] = '';
$signup_process['account_plan'] = '';  // default to error if never set in session
$signup_process = $session->get('signup_process_data', $signup_process['account_plan']);
$panelcolor = 'bg-primary';
$buttonlabel = 'Next';
$titletag = 'Sign Up';


$options['value'] = $birthday;
$options['divclass'] = '' . $formtype . '';
$options['divformtypeclass'] = 'form-floating';
$options['labelclass'] = '';
$options['nochangetag'] = '<span class="ms-2 mt-0 pt-0 "><small class="mt-0 pt-0 text-light-emphasis fst-italic"><span class="d-none d-md-inline">Birthday</span><span class="d-md-none d-sm-inline">DOB</span> can\'t be changed, make sure it\'s right.</small></span>';
$options['birthday_label'] = 'Birthday';

$sidebarmessage = '<h4 class="mb-4 text-white">Get this party started!</h4>
<p class="mb-0 fw-bold text-white d-none d-md-block">Plan your next birthday bash with freebies!<br>
Complete the form to create your account.</p>';
$bottombutton = '<a href="/login" class="text-black text-decoration-none">I already have an account</a> <a href="/login" class="btn btn-sm btn-outline-primary">Login</a>';

switch ($signup_process['account_type']) {
#-------------------------------
case 'user':
switch ($signup_process['account_plan']) {
#-------------------------------
case 'free':
$caption = '<p class="fw-bold text-white h4 h6-sm">You are signing up for an<br>Individual Free plan.
' . ($displayline ? '<br>Provide your information, validate, and enjoy.' : '') . '</p>';
$panelicon = '<i class="bi bi-person"></i>';
break;

#-------------------------------
case 'gold':
$caption = '<p class="fw-bold text-white h4 h6-sm">You are signing up for an<br>Individual Gold plan.
' . ($displayline ? '<br>Provide your information and we\'ll walk you through the rest.' : '') . '</p>';
$panelicon = '<i class="bi bi-person"></i>';
break;

#-------------------------------
case 'life':
$caption = '<p class="fw-bold text-white h4 h6-sm">You are signing up for an<br>Individual Lifetime plan.
' . ($displayline ? '<br>Provide your information and we\'ll walk you through the rest.' : '') . '</p>';
$panelicon = '<i class="bi bi-person"></i>';
break;
}
break;

#-------------------------------
case 'parental':
$titletag = 'Sign Up Parent';
$caption = '<p class="fw-bold text-white h4 h6-sm">You are signing up for a<br>Parental Lifetime plan.
' . ($displayline ? '<br>Provide your information and we will walk through the next steps to add minors.' : '') . '</p>';
$panelcolor = 'bg-info';
$panelicon = '<i class="bi bi-people"></i>';
break;

#-------------------------------
case 'giftcertificate':
$titletag = 'Purchaser Info';
$caption = '<p class="fw-bold text-white h4 h6-sm">You are purchasing a<br>Gift Certificate.
' . ($displayline ? '<br>Provide your information, checkout and then personalize.' : '') . '</p>';
$panelcolor = 'bg-success';
$panelicon = '<i class="bi bi-gift"></i>';
$buttonlabel = 'Checkout';
$options['nochangetag'] = '<span class="ms-2 mt-0 pt-0 "><small class="mt-0 pt-0 text-light-emphasis fst-italic"><span class="d-none d-md-inline">Birthday</span><span class="d-md-none d-sm-inline">DOB</span> can\'t be changed.  Used to locate your certificate if it\'s ever lost.</small></span>';

$sidebarmessage = '<h4 class="mb-4 text-white">One of the best gifts ever!</h4>
<p class="mb-0 fw-bold text-white d-none d-md-block">Give someone you care about all the freebies!<br>
You are purchasing a gift certificate that you give to someone to redeem for a prepaid Gold account.<br>
Let\'s do this!</p>';
$bottombutton = '<a href="/redeem" class="text-black">I have a gift certificate <span class="btn btn-sm btn-outline-primary">Redeem</span</a>';
break;

#-------------------------------
case 'business':
$caption = '<p class="fw-bold text-white h4 h6-sm">You are signing up for a<br>Business Account.
<br>Provide your information and we\'ll walk you through the rest.</p>';
$panelcolor = 'bg-secondary';
$panelicon = '<i class="bi bi-briefcase"></i>';
break;
#-------------------------------
default:
// $caption='<p class="fw-bold text-dark">
// <br>Provide your information and we\'ll walk you through the rest.</p>';

$pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Select a plan.</div>';
#$session->set('force_error_message', $pagemessage);

$transferpage['message'] = $pagemessage;
$transferpage['url'] = '/signup?reset';
$system->endpostpage($transferpage);
break;
}



#$panelicon=str_replace('class="', 'class="h1 ', $panelicon);
$signup_process = $session->set('signup_process_data', $signup_process);
$caption .= '<a href="/signup?reset" class="btn btn-sm button btn-dark">Change Account/Plan</a>';
echo '<div class="main-content">
<section class="h-100">
<div class="container ' . $containerpadding . ' h-100">
<div class="row d-flex justify-content-center align-items-center h-100 mx-0 px-0">

<div class="col-12 col-lg-9 mx-0 px-0">
' . $display->formaterrormessage($transferpagedata['message']) . '
<div class="card rounded-3 text-black mx-0 px-0">
<div class="row g-0">

<div class="col-lg-5 d-flex align-items-center flex-column ' . $panelcolor . '">
<div class="flex-grow-1 d-flex align-items-center">
<div class="text-white px-3 py-2 mx-md-4 text-center">
<div class="d-none  d-lg-inline"><div class="feature text-white rounded-3 mb-4 mt-n5">' . $panelicon . '</div></div>

' . ($displayline ? $sidebarmessage . '<p><hr class="border-white border-2"></p> ' : '') . '
' . $caption . '
</div>
</div>
</div>
';
echo '
<div class="col-lg-7">
<div class="card-body ' . $headerpadding . ' mx-md-4">

<div class="text-center">
<h1 class="' . $titlepadding . '">' . $titletag . '</h1>
</div>

<form method="post"  id="mainform" action="/register">
' . $display->inputcsrf_token();

echo $promoinputtag . $planinputtag;

echo '
<!-- 2 column grid layout with text inputs for the first and last names -->
<div class="row ' . $formpadding . '">
<div class="col-md-6">
<div class="' . $formtype . ' ">
<input type="text"  class="form-control" name="first_name" placeholder="" id="first_name" value="' . $first_name . '" >
<label  for="first_name">First name</label>
</div>
</div>

<div class="col-md-6">
<div class="' . $formtype . ' ">
<input type="text" class="form-control" name="last_name" placeholder=""  id="last_name"  value="' . $last_name . '"  >
<label  for="last_name">Last name</label>
</div>
</div>
</div>

<!-- Email input -->

<div class="' . $formtype . ' ">
<input type="email" class="form-control" id="email_' . rand(100, 999) . '"   value="' . $email . '" name="accountemail" placeholder="youremail@example.com" autocomplete="off">
<label for="uniqueEmailInput">Email address</label>
</div>
';
$idnumber = rand(100, 999);
echo '
<!-- Password input -->
<div class="' . $formtype . ' input-group">
<input type="password" class="form-control" id="password_' . $idnumber . '"    value="' . $password . '" name="password" placeholder="Password" autocomplete="new-password">
<label for="uniquePasswordInput">Password</label> 
<span class="input-group-text" id="basic-addon2">
<i toggle="#password_' . $idnumber . '" class="field-icon toggle-password bi bi-eye-fill"></i>
</span>
</div>

';

echo '<!-- DOB input -->
' . $display->input_datefield([], $options) . '
';



// ADDED FIELDS -- Promocode and Referer
echo '
<!-- PromoCode/Referrer Toggle -->
<div class="my-3">
  <a href="#promoFields" class="d-inline-flex align-items-center" data-bs-toggle="collapse" aria-expanded="false" aria-controls="promoFields" id="promoToggle">
    <i class="bi bi-plus-square"></i>
    <span class="ms-2">Promo code / Referrer</span>
  </a>
</div>

<!-- Hidden PromoCode/Referrer Fields -->
<div class="row collapse" id="promoFields">
';



////////////////////////////////////////////////////////////////////
echo '
<!-- Promo Code Field Group -->
<div class="col-md-6 pb-0 mb-0">
    <div class="form-floating pb-0 mb-0">
        <div class="' . $formtype . ' input-group">
            <input type="text" class="form-control" name="promo_code" id="promoCode" placeholder=" " value="' . $promo_code . '">
            <button class="btn btn-outline-secondary" type="button" id="checkPromo" data-bs-toggle="tooltip" data-bs-placement="top" title="Check to see if valid">
                <i class="bi bi-check-lg"></i>
            </button>
            <label for="promoCode">Promo Code</label>
        </div>
    </div>
    <div id="promoMessage" class="form-text  ajax-message-closer"></div>
</div>
';




////////////////////////////////////////////////////////////////////
echo '
<!-- Referrer Field Group -->
<div class="col-md-6 pb-0 mb-0">
    <div class="form-floating pb-0 mb-0">
        <div class="' . $formtype . ' input-group">
            <input type="text" class="form-control" name="referrer" id="referrer" placeholder=" " value="' . $referrer . '">
            <button class="btn btn-outline-secondary" type="button" id="checkReferrer" data-bs-toggle="tooltip" data-bs-placement="top" title="Check to see if valid">
                <i class="bi bi-check-lg"></i>
            </button>
            <label for="referrer">Referrer</label>
        </div>
    </div>
    <div id="referrerMessage" class="form-text  ajax-message-closer"></div>
</div>
';


// Correct PHP generation for the CSRF token:
  $csrfToken = $display->inputcsrf_token('tokenonly'); // This should return the token string only

?>
<script>
$(document).ready(function() {
    // Generic function to handle input validation
    function validateInput(url, inputData, messageElement) {
        $.post(url, inputData, function(response) {
            if (response && response.resultmessage) {
                $(messageElement).html(response.resultmessage)
                                 .toggleClass('text-success', response.status)
                                 .toggleClass('text-danger', !response.status);
            } else {
                $(messageElement).html('An error occurred.')
                                 .removeClass('text-success')
                                 .addClass('text-danger');
            }
        }, 'json')
        .fail(function() {
            $(messageElement).html('Failed to communicate with the server.')
                             .removeClass('text-success')
                             .addClass('text-danger');
        });
    }

    // Promo code check handler
    $('#checkPromo').click(function() {
        var promoCode = $('#promoCode').val();
        var csrfToken = '<?php echo $csrfToken; ?>';
        validateInput('/helper_checkinput', {promocode: promoCode, _token: csrfToken}, '#promoMessage');
    });

    // Referrer check handler
    $('#checkReferrer').click(function() {
        var referrer = $('#referrer').val();
        var csrfToken = '<?php echo $csrfToken; ?>';
        validateInput('/helper_checkinput', {referrer: referrer, _token: csrfToken}, '#referrerMessage');
    });
});


</script>





<?PHP


echo '</div>';

echo '
<!-- Terms and conditions checkbox -->
<div class="form-check ' . $formpadding . '">
<input class="form-check-input" type="checkbox" value="1" id="terms" name="terms" required ' . ($terms == "1" ? "checked" : "") . '>
<label class="form-label" for="terms">
<span class="d-none d-md-inline fw-bold">I agree to the <a href="/legalhub/terms?register" class="text-underline">Terms & Conditions</a> and other <a href="/legalhub/?register" class="text-underline">legal stuff</a></span>
<span class="d-md-none d-sm-inline fw-bold">I agree to the <a href="/legalhub/terms?register" class="text-underline">Terms & Conditions</a><br> and other <a href="/legalhub/?register" class="text-underline">legal stuff</a></span>
</label>
</div>
';

if ($understandflag) {
echo '  <div class="form-check ' . $formpadding . '">
<input class="form-check-input" type="checkbox" value="1" id="understand" name="understand" required ' . ($understand == "1" ? "checked" : "") . '>
<label class="form-label" for="understand">
I understand that my age limits the number of businesses available to me.
</label>
</div>';
}

echo '  
<div class="text-center pt-1 pb-1 py-lg-3">
<!-- Submit button -->
<button type="submit" id="mainsubmit" class="btn ' . $buttonsize . ' btn-primary btn-block py-2 px-5">' . $buttonlabel . '</button>

</div>

</form>
' . ($displayline ? '
<div class="text-center mt-4 pt-lg-2">
' . $bottombutton . '
</div>
' : '') . '
</div>

</div>

</div>
</div>
</div>
</div>
</div>
</section>
</div>
';

echo $signupexit;
$footerattribute['postfooter'] = "
<script>
$(document).ready(function() {
var date = document.getElementById('birthday');

function checkValue(str, max) {
if (str.charAt(0) !== '0' || str == '00') {
var num = parseInt(str);
if (isNaN(num) || num <= 0 || num > max) num = 1;
str = num > parseInt(max.toString().charAt(0)) && num.toString().length == 1 ? '0' + num : num.toString();
};
return str;
};

date.addEventListener('input', function(e) {
this.type = 'text';
var input = this.value;
if (/\D\/$/.test(input)) input = input.substr(0, input.length - 3);
var values = input.split('/').map(function(v) {
return v.replace(/\D/g, '')
});
if (values[0]) values[0] = checkValue(values[0], 12);
if (values[1]) values[1] = checkValue(values[1], 31);
var output = values.map(function(v, i) {
return v.length == 2 && i < 2 ? v + '/' : v;
});
this.value = output.join('').substr(0, 14);
});

date.addEventListener('blur', function(e) {
this.type = 'text';
var input = this.value;
var values = input.split('/').map(function(v, i) {
return v.replace(/\D/g, '')
});
var output = '';

if (values.length == 3) {
var year = values[2].length !== 4 ? parseInt(values[2]) + 2000 : parseInt(values[2]);
var month = parseInt(values[0]) - 1;
var day = parseInt(values[1]);
var d = new Date(year, month, day);
if (!isNaN(d)) {
document.getElementById('result').innerText = d.toString();
var dates = [d.getMonth() + 1, d.getDate(), d.getFullYear()];
output = dates.map(function(v) {
v = v.toString();
return v.length == 1 ? '0' + v : v;
}).join('/');
};
};
this.value = output;
});
});
</script>
";

echo  $display->togglepaswordjs();

$enableflatpickr = true;
echo $display->submitbuttoncolorjs();
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
