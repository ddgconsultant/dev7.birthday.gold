<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 


#-------------------------------------------------------------------------------
# HANDLE FORIEGN COUNTRIES
#-------------------------------------------------------------------------------
$approvedCountries = ['US'];
$countryCode = $session->get('countrynotsupported', '');
$getcountryviaip_data = $session->get('client_locationdata', '');
if ($countryCode == '') {

  if ($getcountryviaip_data == '' || $getcountryviaip_data == 'notset') {
    $client_locationdata = $system->getcountryviaip($client_ip, 'reset');  // set in site-controller
    if (!empty($client_locationdata['countryCode']))
    $countryCode = $client_locationdata['countryCode'];
    # $session->set('client_locationdata', $response);
  } else {
    if (!empty($getcountryviaip_data['countryCode']))
    $countryCode = $getcountryviaip_data['countryCode'];
  }

  // Check if country is approved
  $override = $session->get('country_not_supported_override', false);
  if (!in_array($countryCode, $approvedCountries) && $countryCode != '' && !$override) {
    // Not approved, redirect
    $session->set('countrynotsupported', $countryCode);
    $session->set('countrynotsupportedtag', '[' . $countryCode . ']');
    header('Location: /country-not-supported');
    exit();
  }
}


#-------------------------------------------------------------------------------
# HANDLE INITIALIZE - go to router / ## using new signup routing process
#-------------------------------------------------------------------------------
$gotorouter=false;
if (isset($_REQUEST['reset'])) {$gotorouter=true; $session->unset('force_error_message'); } #choose new plan
if (empty($_REQUEST)) {$gotorouter=true;} ## no route provided -- allow user to select route    
if ($gotorouter) {
header ('location: /signup-route');
exit;
}



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$signup_process=$_REQUEST;
$plan=false;


#-------------------------------------------------------------------------------
# HANDLE FORM
#-------------------------------------------------------------------------------
if ($app->formposted() && empty($signup_process['account_plan'])) {
    $pagemessage='<div class="alert alert-danger alert-dismissible show" role="alert">Select a plan.</div>';
    $session->set('force_error_message', $pagemessage);
    $transferpage['url']='/signup-route';
    $transferpage['message']=$pagemessage;
    $system->endpostpage($transferpage);
      exit;
  }


#-------------------------------------------------------------------------------
# HANDLE LINKS
#-------------------------------------------------------------------------------
if (isset($_REQUEST['plan'])) {
  $planbynamedata=$app->getProduct($_REQUEST['plan'] , 'user');
if ($planbynamedata) {
  $signup_process['account_plan']=$qik->encodeId($planbynamedata['id']);
}
}


#-------------------------------------------------------------------------------
# FORWARD USER TO ROUTE TO REGISTER
#-------------------------------------------------------------------------------
$session->unset('signup_process_data');

$planid=($qik->decodeId($signup_process['account_plan'] ?? false));

if ($planid) {
  $signup_process['account_plan_id']=$planid;
$plandata=$app->getProduct($planid , 'PRODUCT_ID');


    # If plandata is found, process it
    if ($plandata) {
      # Populate signup_process based on the plandata
      $signup_process['plandata'] = $plandata;
      $signup_process['account_type'] = $plandata['account_type'];
      $signup_process['account_plan'] = $plandata['account_plan'];
      $signup_process['account_cost'] = $plandata['price'];
      $signup_process['account_verification'] = $plandata['account_verification'];  // Use this directly
      $gotourl = $plandata['redirect_url'] ?? '/register';
     
  } else {
      # Handle the case when no plandata is found
      $transferpage['url'] = '/signup-route';
      $transferpage['message'] = 'Plan not found';
      session_tracking('signup_process_data - default', array_merge($transferpage, $signup_process));
      $system->endpostpage($transferpage);
      exit;
  }
} else {
  # If no plan ID is found, redirect to the signup route
  $transferpage['url'] = '/signup-route';
  $transferpage['message'] = 'Invalid plan selected';
  session_tracking('signup_process_data - default', array_merge($transferpage, $signup_process));
  $system->endpostpage($transferpage);
  exit;
}

# Save the signup process data in the session and redirect
$session->set('signup_process_data', $signup_process);

header('Location: ' . $gotourl);
