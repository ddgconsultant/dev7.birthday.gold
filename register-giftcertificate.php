<?php 
$addClasses[] = 'createaccount';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$first_name = '';
$last_name = '';
$email = '';
$password = '';
$birthday = '';
$errormessage = '';
$continue = false;
$promoinputtag=$planinputtag='';

#-------------------------------------------------------------------------------
# HANDLE PLAN REDIRECT
#-------------------------------------------------------------------------------
if (isset($_GET['plan'])) {
  $planinputtag='<input type="hidden" name="plan" value="'.$_GET['plan'].'">';
  if (isset($_GET['promocode']) && $_GET['plan']=='gold') {
  $promoinputtag='<input type="hidden" name="promocode" value="'.$_GET['promocode'].'">';
}
}
 
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
# HANDLE THE REGISTRATION ATTEMPT
#-------------------------------------------------------------------------------
$error = false;
if ($formdata = $app->formposted()) {
  $continue = false;
  $errormessage = '<div class="alert alert-danger">Your registration information is invalid.</div>';
  // Get form data
  $foundformfieldcount = 0;
  
  $formdata['birthday']=$app->getformdate();

  $requiredfields = ['first_name', 'last_name', 'email', 'password', 'birthday', 'terms'];
  foreach ($requiredfields as $field) {
    if (isset($formdata[$field])) {
      $$field = trim($formdata[$field]);
      $foundformfieldcount++;
    }
  }

  if(!isset($formdata['terms'])) {
    $errormessage = '<div class="alert alert-danger">You must agree to the terms and conditions to sign up.</div>';
    $continue = false;
    $error = true;
    goto displaypage;
  }
  

  if ($foundformfieldcount != count($requiredfields) || count(array_filter($formdata)) != count($requiredfields)) {
    $continue = false;
    $error = true;
    $errormessage = '<div class="alert alert-danger">You must provide all the required fields. ('.count(array_filter($formdata)).'/'.count($requiredfields).')</div>';
    goto displaypage;
  }



  $birthday = (str_replace(' ', '', $birthday));
  #$birthday_date = DateTime::createFromFormat('m/d/Y', $birthday);
  $birthday_date = DateTime::createFromFormat('Y-m-d', $birthday);
  if (!$birthday_date) {
    $errormessage = '<div class="alert alert-danger">Invalid date for birthday - ' . $birthday . ' / ' .  $birthday_date . '</div>';
    $continue = false;
    $error = true;
    goto displaypage;
  }

  $today = new DateTime(); // Creates a DateTime object for the current date
  if ($birthday_date > $today) {
    $errormessage = '<div class="alert alert-danger">Birthdate cannot be greater than today\'s date.</div>';
    $continue = false;
    $error = true;
    goto displaypage;
  }

  // Check if the birthday is more than 150 years in the past
  $too_old = clone $today;
  $too_old->modify('-150 years');
  if ($birthday_date < $too_old) {
    $errormessage = '<div class="alert alert-danger">Birthdate cannot be more than 150 years in the past.</div>';
    $continue = false;
    $error = true;
    goto displaypage;
  }


  $birthday_formatted = $birthday_date->format('Y-m-d');
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  $email=strtolower($email);
  $first_name=ucfirst($first_name);
  $response = $createaccount->isavailable($email);
  if (!$response) {

#let's check for already registered/validated users and accept/bypass

	$tmpsettings['status']=['validated', 'active_gift', 'pending_gift'];	
$tempinfo=$account->getuserdata($email, 'email',  $tmpsettings);


if (!empty($tempinfo['status'])) {

  $params = array(
    ':first_name' => $tempinfo['first_name'],
    ':last_name' =>  $tempinfo['last_name'] ,
    ':username' => $tempinfo['username'],
    ':email' => $tempinfo['email'] ,
    ':birthday' => $birthday_formatted,
    'alreadyregistered_validated'=>true,

    ':city' => $tempinfo['city'] ,
    ':state' => $tempinfo['state'] ,
    ':zip_code' => $tempinfo['zip_code'] ,
  );
  $session->set('userregistrationdata', $params);
  // Last insert ID is a number, redirect
  header('Location: /validate-account');
  exit();
} else {
    $errormessage = '<div class="alert alert-danger">Email address cannot be used.  It\'s seems to already be in our system.</div>';
    $continue = false;
    $error = true;
    goto displaypage;
  }
  }

   #   $session->get('generateGiftCertificateCode',  $username);
   $userid= $lastId=  $session->get('generateGiftCertificateCode_user_id');
   if (empty($userid)) {
   $errormessage = '<div class="alert alert-danger">Invalid Account Details.  System Error Occured.</div>';
   $continue = false;
   $error = true;
   goto displaypage;
   }

  ### GENERATE USERNAME
  $username = $createaccount->generate_username($first_name, $last_name, $birthday);


  ## REGISTER USER
  $params2 = array(
    ':username' => $username,
    ':email' => $email,
    ':user_id' => $userid,
  );


  ## CLEAR ANY PENDING USERS
   $database->query('delete FROM bg_users where ((email=:email or username=:username) and user_id != :user_id) and `status`="pending"', $params2);


  $exists = $database->count('bg_users', '(email=:email or username=:username) and user_id != :user_id', $params2);
  if ($exists == 0) {
    $client_locationdata=$session->get('client_locationdata');
$city=(!empty($client_locationdata['city'])?$client_locationdata['city']:'');
$state=(!empty($client_locationdata['regionName'])?$client_locationdata['regionName']:'');
$zip_code=(!empty($client_locationdata['zip'])?$client_locationdata['zip']:'');

/*
    $params = array(
      ':first_name' => $first_name,
      ':last_name' => $last_name,
      ':username' => $username,
      ':email' => $email,
      ':hashed_password' => $hashed_password,
      ':birthday' => $birthday_formatted,
## account location
      ':city' => $city,
      ':state' => $state,
      ':zip_code' => $zip_code,
 ## profile location
      ':city2' => $city,
      ':state2' => $state,
      ':zip_code2' => $zip_code,
    );

*/
    
    $params = array(
      'first_name' => $first_name,
      'last_name' => $last_name,
      'username' => $username,
      'email' => $email,
      'password' => $hashed_password,
      'birthdate' => $birthday_formatted,
## account location
      'city' => $city,
      'state' => $state,
      'zip_code' => $zip_code,
      
      'status'=> 'pending_gift',
      'modify_dt' => 'now()',
    );

    $updatefields_profile=array(
 ## profile location
      'profile_city' => $city,
      'profile_state' => $state,
      'profile_zip_code' => $zip_code,
    );
    # breakpoint($params);
    ## CHANGE THE USER RECORD
    $newParams = array();
    foreach ($params as $key => $value) {
      $newKey = ':' . $key;
      $newParams[$newKey] = $value;
    }



 # $userid= $current_user_data['user_id'];
  $result=   $account->updateSettings($userid, $params);
  $result =$account->updateUserProfileData($userid, $updatefields_profile);

//     $sql = "INSERT INTO bg_users (first_name, last_name, username, email, password, birthdate, `status`, city, state, zip_code,  profile_city, profile_state, profile_zip_code,  create_dt, modify_dt)
// VALUES (:first_name, :last_name, :username, :email, :hashed_password, :birthday, 'pending', :city, :state, :zip_code,  :city2, :state2, :zip_code2,  now(), now())";
//     $stmt = $database->query($sql, $params);
//     $lastId = $database->lastInsertId();

    /// ADD MORE DETAILS TO userregistrationdata
    $newParams['user_id'] = $lastId;
    $newParams['plan'] ='life';
   # if (isset($_POST['plan'])) { $params['plan'] =$_POST['plan'];
  #  if (isset($_POST['promocode']) && $_POST['plan']=='gold') { $params['promocode'] =$_POST['promocode'];   }
 #   }

    $session->set('userregistrationdata', $newParams);
    // Last insert ID is a number, redirect
    header('Location: /validate-account');
    exit();
  } else {
    $errormessage = '<div class="alert alert-danger">Registration Conflict.  Please try again.</div>';
    $continue = false;
    $error = true;
    goto displaypage;
  }
}





#-------------------------------------------------------------------------------
# DISPLAY THE REGISTRATION PAGE
#-------------------------------------------------------------------------------
displaypage:

$transferpagedata['message']=$errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

$headerattribute['additionalcss'] = '<link rel="stylesheet" href="/public/css/login.css">';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
?>


<section class="h-100 gradient-form " style="background-color: #eee;">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">

      <div class="col-xl-10">
        <?= $display->formaterrormessage($transferpagedata['message']); ?>
        <div class="card rounded-3 text-black">
          <div class="row g-0">

            <div class="col-lg-6 d-flex align-items-center flex-column bg-dark-subtle ">
              <div class="flex-grow-1 d-flex align-items-center">
                <div class="text-white px-3 py-4 p-md-5 mx-md-4 text-center">
                  <h4 class="mb-4 text-white">We are excited about your gift!</h4>
                  <p class="mb-0 fw-bold text-dark d-none d-md-block">Plan your next birthday bash with freebies! Simply fill out the form to begin.  We walk you through the three step process. <br> Let's do this!</p>
                  <p class="mb-6 fw-bold text-dark d-md-none d-sm-block">Simply fill out the form to begin and we'll walk you through the rest!</p>

        </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card-body p-md-5 mx-md-4">

                <div class="text-center">
                  <h1 class="mt-1 mb-5 pb-1">Setup Account</h1>
                </div>

                <form method="post" action="/register-giftcertificate">
                  <?PHP echo $display->inputcsrf_token();
                  echo $promoinputtag.$planinputtag;
                  echo '
<!-- 2 column grid layout with text inputs for the first and last names -->
<div class="row">
<div class="col-md-6 mb-3">
<div class="form-outline">
<input type="text" name="first_name" id="first_name" value="' . $first_name . '" class="form-control" />
<label class="form-label" for="first_name">First name</label>
</div>
</div>
<div class="col-md-6 mb-3">
<div class="form-outline">
<input type="text"  name="last_name"  id="last_name"  value="' . $last_name . '" class="form-control" />
<label class="form-label" for="last_name">Last name</label>
</div>
</div>
</div>

<!-- Email input -->
<div class="form-outline mb-3">
<input type="email"  name="email" id="email" class="form-control" />
<label class="form-label" for="email">Email address</label>
</div>

<!-- Password input -->
<div class="form-outline mb-3">
<input type="password"  name="password"  id="password" class="form-control" />
<label class="form-label" for="password">Password</label>
</div>
';
/*
<!-- DOB input -->
<div class="form-outline mb-3">
<!--  pattern="^(0[1-9]|1[0-2])/(0[1-9]|[12][0-9]|3[01])/[0-9]{4}$"/ data-date="" data-date-format="MM/DD/YYYY" data-autoclose="true" pattern="^(0[1-9]|1[0-2])/(0[1-9]|[12][0-9]|3[01])/[0-9]{4}$"-->
<input type="date" name="birthday" id="birthday" class="form-control" value="' . $birthday . '" >
<label class="form-label" for="birthday">Birthday <small class="ps-2 text-light-emphasis fst-italic">can\'t be changed, so make sure it\'s right</small></label> 
</div>
*/
$options['value']=$birthday;
echo '
'.$display->input_datefield([], $options).'
  

<!-- Terms and conditions checkbox -->
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" value="1" id="terms" name="terms" required>
    <label class="form-label" for="terms">I agree to the <a href="/terms?register">Terms and Conditions</a><br> and other <a href="/legal?register">legal stuff</a></label>
</div>

<div class="text-center pt-1  pb-1">
<!-- Submit button -->
<button type="submit" class="btn btn-primary btn-block py-2 px-5">
Sign up
</button>


</div>

</form>
</div>

</div>

</div>
</div>
</div>
</div>
</div>
</section>
';

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
$footerattribute['postfooter'] = '';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');