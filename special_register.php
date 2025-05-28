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
$errormessage= '<div class="bg-info p-3 mb-4 text-white"><B>It is important that you do not hit the back button for this special early adopter signup.</B><br>If you do, please start over by clicking your link in your email.</div>';
#-------------------------------------------------------------------------------
# HANDLE THE REGISTRATION LINK
#-------------------------------------------------------------------------------
$error = false;
if (isset($_GET['code'])  && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' ) {
    $formdata=$_GET['code'];

    $params=['code'=>$formdata];
    $sql = 'select email from coming_soon_emails where regcode=:code and `status`="active"';
    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$results) {
        // Handle no result case
        #echo 'No active records found for this code';
        header('location: /register');
        exit();
    }

    $email =$results[0]['email'];
   # breakpoint('hello world');
} 


#-------------------------------------------------------------------------------
# HANDLE FORIEGN COUNTRIES
#-------------------------------------------------------------------------------
$approvedCountries = ['US'];
$countryCode = $session->get('countrynotsupported', '');
$getcountryviaip_data = $session->get('client_locationdata', '');
if ($countryCode == '') {

  if ($getcountryviaip_data == '') {
    $client_locationdata = $system->getcountryviaip($client_ip, 'reset');
    $countryCode = $client_locationdata['countryCode'];
    # $session->set('client_locationdata', $response);
  } else {
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
  $requiredfields = ['first_name', 'last_name', 'email', 'password', 'birthday', 'terms'];
  foreach ($requiredfields as $field) {
    if (isset($formdata[$field])) {
      $$field = trim($formdata[$field]);
      $foundformfieldcount++;
    }
  }

  if(!isset($formdata['terms'])) {
    $errormessage = '<div class="alert alert-danger">You must agree to the terms and conditions to register.</div>';
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
  $birthday_date = DateTime::createFromFormat('m/d/Y', $birthday);
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

  $response = $createaccount->isavailable($email);
  if (!$response) {
    $errormessage = '<div class="alert alert-danger">Email address cannot be used.</div>';
    $continue = false;
    $error = true;
    goto displaypage;
  }


  ### GENERATE USERNAME
  $username = $createaccount->generate_username($first_name, $last_name, $birthday);


  ## REGISTER USER
  $params2 = array(
    ':username' => $username,
    ':email' => $email
  );


  ## CLEAR ANY PENDING USERS
  $database->query('delete FROM bg_users where (email=:email or username=:username) and `status`="pending"', $params2);


  $exists = $database->count('bg_users', 'email=:email or username=:username', $params2);
  if ($exists == 0) {
    $params = array(
      ':first_name' => $first_name,
      ':last_name' => $last_name,
      ':username' => $username,
      ':email' => $email,

      ':profile_first_name' => $first_name,
      ':profile_last_name' => $last_name,
      ':profile_username' => $username,
      ':profile_email' => $email,

      ':hashed_password' => $hashed_password,
      ':birthday' => $birthday_formatted
    );
    # breakpoint($params);
    ## ADD THE USER
    $sql = "INSERT INTO bg_users (first_name, last_name, username, email, profile_first_name, profile_last_name, profile_username, profile_email, password, birthdate, `status`, create_dt, modify_dt)
VALUES (:first_name, :last_name, :username, :email, :profile_first_name, :profile_last_name, :profile_username, :profile_email, :hashed_password, :birthday, 'pending', now(), now())";
    $stmt = $database->query($sql, $params);
    $lastId = $database->lastInsertId();

    $params['user_id'] = $lastId;
    $session->set('userregistrationdata', $params);
    // Last insert ID is a number, redirect

////  BYPASS ACTUAL EMAIL VALIDATION PROCESS
////  BYPASS ACTUAL EMAIL VALIDATION PROCESS
////  BYPASS ACTUAL EMAIL VALIDATION PROCESS
$userregistrationdata= $session->get('userregistrationdata');
$sendcount=1;
////  BYPASS ACTUAL EMAIL VALIDATION PROCESS
    $validatedata['rawdata']=$email;
    $validatedata['user_id']=$userregistrationdata['user_id'];
    $validatedata['sendcount']=$sendcount;
    
    $validationcodes=$app->getvalidationcodes($validatedata);
    
    $link=$website['fullurl'].'/special_validate-account?t='.$validationcodes['long'];


    header('Location: '.$link);
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

            <div class="col-lg-6 d-flex align-items-center flex-column gradient-custom-2">
              <div class="flex-grow-1 d-flex align-items-center">
                <div class="text-white px-3 p-md-5 mx-md-4 text-center">
                  <h4 class="mb-4 text-white">Thank you for helping launch our service!</h4>
                  <h6 class="mb-0">Your link is uniquely tied to your email and not shareable.  You can change your email after you complete this information.</h6>
                  <hr>
                  <h4 class="mt-5 mb-4 text-white">Our Gift To You!</h4>
                  
                  <h6 class="mt-4 mb-0">You will receive our Gold plan for free for the first year.  Simply continue.</h6>
                  <h6 class="mt-4 mb-0">Or you can start with our Lifetime plan for $30 by changing the plan in the Dropdown.  This is an exclusive signup offer and you will not be able to get this discount later through an upgrade.</h6>
                    
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card-body p-md-5 mx-md-4">

                <div class="text-center">
                  <h1 class="mt-1 mb-5 pb-1">Welcome Early Adopter!</h1>
                </div>

                <form method="post" action="/special_register">
                  <?PHP echo $display->inputcsrf_token();
                  echo '
<!-- 2 column grid layout with text inputs for the first and last names -->
<div class="row">
<div class="col-md-6 mb-4">
<div class="form-outline">
<input type="text" name="first_name" id="first_name" value="' . $first_name . '" class="form-control" />
<label class="form-label" for="first_name">First name</label>
</div>
</div>
<div class="col-md-6 mb-4">
<div class="form-outline">
<input type="text"  name="last_name"  id="last_name"  value="' . $last_name . '" class="form-control" />
<label class="form-label" for="last_name">Last name</label>
</div>
</div>
</div>

<!-- Email input -->
<div class="form-outline mb-4">
<input type="email"  name="email" value="'.$email.'" id="email" readonly  class="form-control" />
<label class="form-label" for="email">Email address</label>
</div>

<!-- Password input -->
<div class="form-outline mb-4">
<input type="password"  name="password"  id="password" class="form-control" />
<label class="form-label" for="password">Password</label>
</div>

<!-- DOB input -->
<div class="form-outline mb-4">
<!--  pattern="^(0[1-9]|1[0-2])/(0[1-9]|[12][0-9]|3[01])/[0-9]{4}$"/ 
data-date="" data-date-format="MM/DD/YYYY" data-autoclose="true" pattern="^(0[1-9]|1[0-2])/(0[1-9]|[12][0-9]|3[01])/[0-9]{4}$"-->
<input type="text" name="birthday" id="birthday" class="form-control" value="' . $birthday . '" >
<label class="form-label" for="birthday">Birthday (mm/dd/yyyy)<p><small>This cannot be changed, so make sure it\'s accurate</small></p></label> 
</div>




<div class="mb-5">
<label class="small mb-1" for="inputprofile_title">Plan</label>
<select name="inputprofile_title" class="form-control custom-select select-form-background">
    <option  selected="selected"  selected="selected"  value="gold">Gold - 1st Year Free</option>
    <option value="life">Exclusive Lifetime - $30</option>
      
</select>
</div>



<!-- Terms and conditions checkbox -->
<div class="form-check mb-4">
<input class="form-check-input" type="checkbox" value="1" id="terms" name="terms" required>
<label class="form-label" for="terms">I agree to the <a href="/terms?register">Terms and Conditions</a></label>
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
return v.length == 2 && i < 2 ? v + ' / ' : v;
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
}).join(' / ');
};
};
this.value = output;
});
});
</script>
";
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');