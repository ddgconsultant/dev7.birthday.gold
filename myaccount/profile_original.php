<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$profilesettings['savetosession']= true;
$current_user_data=$account->getuserdata($current_user_data['user_id'], 'user_id', $profilesettings );
$jstag_openinstructions = '';
$suppressionitem = $extremesuppression = false;
$messages = array();
$showtip = '
<div class="alert alert-info alert-dismissible fade show p-3 mb-4" role="alert">
<div style="display: flex; justify-content: space-between; align-items: center;">
<div style="flex-grow: 1; display: flex; align-items: center;" class="me-5">
<i class="bi bi-exclamation-triangle-fill" style="margin-right: 10px;"></i>
Your profile information here is only used to enroll you to the businesses you select. It does not have to match your birthday.gold account information.
</div>
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
</div>';

#-------------------------------------------------------------------------------
# HANDLE FIRST PROFILE VISIT
#-------------------------------------------------------------------------------
$response = $account->getUserAttribute($current_user_data['user_id'], 'first_profile_visit');
if (!$response) {
  $input = [
    'name' => 'first_profile_visit',
    'description' => date('Y-m-d H:i:s')
  ];
  $response = $account->setUserAttribute($current_user_data['user_id'], $input);
  $jstag_openinstructions = "$('#instructionsModal').modal('show');";
}



#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted() && isset($_REQUEST['profileupdate']) && !$account->ProfileLocked()) {
  session_tracking('HANDLE THE PROFILE UPDATE ATTEMPT');

  $updatefields = [];
  $checkbox_categories = $account->getuseroptions('all');
  // Step 1: Initialize all checkbox options to "".
  foreach ($checkbox_categories as $category => $options) {
    foreach ($options as $option) {
      $nametag = ($category == 'honor' ? '' : $category . '_');
      $updatefields['profile_' . $nametag . '' . $option] = '';
    }
  }

  // Step 2: Set checked options to "true".
  foreach ($_POST as $formelement => $formvalue) {
    if (strpos($formelement, 'inputprofile_') !== false) {
      $columnname = strtolower(str_replace('input', '', $formelement));
      $updatefields[$columnname] = trim($formvalue);
    }
  }

  if (!empty($updatefields)) {
    # breakpoint($updatefields);
    $updatefields['profile_email'] = strtolower($updatefields['profile_email']);

    $userdata_before = $current_user_data;
    unset($userdata_before['modify_dt']);
    $userdata_beforehash = hash('sha256', serialize($userdata_before));
    $result =$account->updateUserProfileData($current_user_data['user_id'], $updatefields);


    $profilesettings['savetosession']= true;
$current_user_data=$account->getuserdata($current_user_data['user_id'], 'user_id', $profilesettings );

    $DEBUG=0;
if ($DEBUG) {
  if ($result['success']) {
    $metrics = $result['metrics'];
    echo "Update completed successfully:\n";
    echo "- Fields processed: {$metrics['fields_processed']}/{$metrics['total_fields']}\n";
    echo "- Records found: {$metrics['records_found']}\n";
    echo "- Records updated: {$metrics['records_updated']}\n";
    echo "- Records inserted: {$metrics['records_inserted']}\n";
    echo "- Unchanged records: {$metrics['unchanged_records']}\n";
    echo "- Had changes: " . ($metrics['had_changes'] ? 'Yes' : 'No') . "\n";
    echo "- Execution time: {$metrics['execution_time']} seconds\n";
    
    // Detailed field information
    foreach ($metrics['field_details'] as $field) {
        echo "- Field {$field['field_name']}: {$field['action']}\n";
        if (isset($field['old_value']) && isset($field['new_value'])) {
            echo "  Changed from '{$field['old_value']}' to '{$field['new_value']}'\n";
        }
    }
} else {
  echo "Update failed: {$result['metrics']['error_message']}\n";
}

  print_r($current_user_data);
exit;
}

    #breakpoint($updatefields);
    $current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
    $userdata_after = $current_user_data;
    unset($userdata_after['modify_dt']);
    $userdata_afterhash = hash('sha256', serialize($userdata_after));

  if (
    isset($updatefields['username']) && $updatefields['username'] != $userdata_before['username'] ||
    isset($updatefields['email']) && $updatefields['email'] != $userdata_before['email']
  ) {
    session_tracking('relogin user');
    header('location: /logout?_relogin');
    exit;
  }
  }

  if ($current_user_data['profile_phone_number'] == '') $messages[] = '<i class="fas fa-exclamation-triangle"></i> You should provide a mobile number.  We use it to send links to download apps to the businesses you selected';

  if ($userdata_beforehash != $userdata_afterhash) {
    if ($current_user_data['profile_agree_terms'] == '') $messages[] = 'By having your "Agree To Terms" off, you more than likely cannot be successfully enrolled for businesses you select.  We recommend having this on';
    if ($current_user_data['profile_allergy_gluten'] != '') {
      $messages[] = 'By having your "Gluten Allergy" enabled, our service will automatically suppress businesses that are identified as providing gluten products.';
      $suppressionitem = true;
    }
    if ($current_user_data['profile_allergy_dairy'] != '') {
      $messages[] = 'By having your "Dairy Allergy" enabled, our service will automatically suppress businesses that are identified as providing dairy products.';
      $suppressionitem = true;
    }
    if ($current_user_data['profile_allergy_sugar'] != '') {
      $messages[] = 'By having your "Sugar Allergy" enabled, our service will automatically suppress businesses that are identified as providing sugar products.';
      $suppressionitem = true;
    }
    if ($current_user_data['profile_allergy_nuts'] != '') {
      $messages[] = 'By having your "Nut Allergy" enabled, our service will automatically suppress businesses that are identified as providing nut products.';
      $suppressionitem = true;
    }

    if ($current_user_data['profile_diet_vegan'] != '') {
      $messages[] = 'By having your "Vegan Diet" enabled, our service will automatically suppress businesses that are identified as providing non-vegan products.';
      $suppressionitem = true;
    }
    if ($current_user_data['profile_diet_kosher'] != '') {
      $messages[] = 'By having your "Kosher Diet" enabled, our service will automatically suppress businesses that are identified as providing non-kosher products.';
      $extremesuppression = true;
    }
    if ($current_user_data['profile_diet_pescatarian'] != '') {
      $messages[] = 'By having your "Pescatarian Diet" enabled, our service will automatically suppress businesses that are identified as providing non-pescatarian products.';
      $suppressionitem = true;
    }
    if ($current_user_data['profile_diet_keto'] != '') {
      $messages[] = 'By having your "Keto Diet" enabled, our service will automatically suppress businesses that are identified as providing non-keto products.';
      $suppressionitem = true;
    }
    if ($current_user_data['profile_diet_paleo'] != '') {
      $messages[] = 'By having your "Paleo Diet" enabled, our service will automatically suppress businesses that are identified as providing non-paleo products.';
      $suppressionitem = true;
    }
    if ($current_user_data['profile_diet_vegetarian'] != '') {
      $messages[] = 'By having your "Vegetarian Diet" enabled, our service will automatically suppress businesses that are identified as providing non-vegetarian products.';
      $suppressionitem = true;
    }


    #$jstag_openinstructions="$('#profileupdate').modal('show');";

  }
}

#breakpoint($current_user_data);
#-------------------------------------------------------------------------------
# PREP PAGE DATA
#-------------------------------------------------------------------------------
#breakpoint($current_user_data,false);
include_once($dir['core_components'] . '/user_getaccountdetails.inc');
#breakpoint($current_user_data);
#$till = $app->getTimeTilBirthday($current_user_data['birthdate']);   // obtained from user_getaccountdetails
$astrosign = $app->getastrosign($current_user_data['birthdate']);
$astroicon = $app->getZodiacInfo($astrosign);
$profilemailingstate = $current_user_data['profile_state'];

if (empty($profilemailingstate) && !empty($client_locationdata['regionName'])) {
  $profilemailingstate = $client_locationdata['regionName'];
}

#$avatar = '/public/images/defaultavatar.png';    // obtained from user_getaccountdetails
#if (!empty($current_user_data['avatar'])) $avatar = '/' . $current_user_data['avatar'];  // obtained from user_getaccountdetails 

$completiontag = '';
#$profilecompletion = $account->profilecompletionratio($current_user_data);   // obtained from user_getaccountdetails
$completiontag = str_replace('class="', 'class="', $profilecompletion['required_percentage_tag']);







if (count($profilecompletion['required_fields_notcompleted']) > 0) {
  $profileline = 'collapse';
  $profileindicator = '';
  $profilestate = 'false';
  $guidanceline = 'show';
  $guidancendicator = '';
  $guidancestate = 'true';
  $profileinfo = 'Fields to be completed are:
<ul>';

  foreach ($profilecompletion['required_fields_notcompleted_strings'] as $missing_field) {
    $profileinfo .= '<li>' . $missing_field . '</li>';
  }
  $profileinfo .= '</ul>';
} else {

  $profileline = 'show';
  $profileindicator = '';
  $profilestate = 'true';
  $guidanceline = 'collapse';
  $guidancendicator = '';
  $guidancestate = 'false';
  $profileinfo = 'Your enrollment profile is perfect!';
  $showtip = '';
  $nextpage = '/myaccount/enrollment-schedule';
}

$wizardmode = false;
$wizard['section'] = 'enrollment';
$wizard['step'] = 1;
if (!empty($current_user_data['enrollment_mode']) && $current_user_data['enrollment_mode'] == 'wizard') {
  $wizardmode = true;
}

if (!empty($wizardmode) && !empty($nextpage) && !isset($_GET['review'])) {
  session_tracking('wizardmode redirect');
  header('location: ' . $nextpage);
  exit;
}


#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= "
<style>
#infoAccordion {
position: -webkit-sticky; /* For Safari */
position: sticky;
top: 100px; /* Adjust based on your header nav bar's height */
z-index: 1000; /* Ensure it's above other content */
}
</style>
";



### nav-myaccount.php  DISPLAYS THE WIZARD WHEN $wizardmode = true
#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php');

include($dir['core_components'] . '/bg_user_profileheader.inc');









$locktag = '';
$locked = $account->ProfileLocked();
if ($locked) {
  $locktag = 'disabled';

  $showtip = '
<div class="alert-warning bg-warning-subtle show p-3 mb-4" role="alert">
<div style="display: flex; justify-content: space-between; align-items: center;">
<div style="flex-grow: 1; display: flex; align-items: center;" class="me-5">
<i class="bi bi-exclamation-triangle-fill" style="margin-right: 10px;"></i>
<div><span class="fw-bold">Your profile is currently locked and changes cannot be made.</span>  Your enrollments are being processed and will automatically be unlocked after they are done.</div>
</div>
</div>
</div>
';
}

echo '
<div class="container mt-7">
' . $showtip . '
<section id="body" class="mt-1 pt-1">
<div class="row">
';


// PROFILE COMPLETION ACCORDIAN BOX
echo '
<div class="col-lg-4">
<div class="accordion text-left mb-3" id="infoAccordion">
<div class="accordion-item">
<h2 class="accordion-header" id="headingOne">
<button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
<span class="button-text">Your profile is: ' . $completiontag . '</span><span class="indicator">' . $profileindicator . '</span>
</button>
</h2>
<div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#infoAccordion">
<div class="accordion-body">
' . $profileinfo . '
</div>
</div>
</div>
';

// PROFILE FIELD INSTRUCTION ACCORDIAN BOX
echo '
<div class="accordion-item mb-3">
<h2 class="accordion-header" id="headingTwo">
<button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
<span class="button-text">Profile Completion Guidance</span><span class="indicator">' . $guidancendicator . '</span>
</button>
</h2>
<div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#infoAccordion">
<div class="accordion-body" id="guidancecard">
Helpful instructions will appear here.
</div>
</div>
</div>
</div>
<!-- end left section -->
</div>
';

// PROFILE ENROLLMENT FIELDS
echo '
<div class="col-lg-8">
';

echo '<div class="mb-3">
<h2 class="text-primary">Your Enrollment Profile</h2>
</div>
';


echo '
<!-- Account details card-->
<div class="card mb-4">
<div class="card-header d-flex justify-content-between">
<span>Enrollment Details</span>
<!-- Button trigger modal -->
<button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#instructionsModal">
Instructions
</button>
</div>

<div class="card-body">
<form id="profileupdateForm" method="post" action="/myaccount/profile">
<fieldset ' . $locktag . '>
' . $display->inputcsrf_token() . '
<input name="profileupdate" type="hidden" value="1">

<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">Personal Details:</h4></div>
</div>


<!-- Form Row-->
<div class="row gx-3 mb-3">
<!-- Form Group (title)-->
<div class="col-md-2">
<label class="small mb-1" for="inputprofile_title">Title</label>
<select name="inputprofile_title" class="form-select form-select mb-3" aria-label=".form-select example">
' . $display->list_title($current_user_data['profile_title']) . '
</select>
</div>

<!-- Form Group (first name)-->
<div class="col-md-4">
<label class="small mb-1" for="inputprofile_first_name">First Name</label>
<input class="form-control" name="inputprofile_first_name"  id="inputprofile_first_name" type="text" placeholder="Enter your first name" value="' . $current_user_data['profile_first_name'] . '">
</div>
<!-- Form Group (middle name)-->
<div class="col-md-2">
<label class="small mb-1" for="inputprofile_middle_name">Middle</label>
<input class="form-control" name="inputprofile_middle_name"  id="inputprofile_middle_name" type="text" placeholder="" value="' . $current_user_data['profile_middle_name'] . '">
</div>
<!-- Form Group (last name)-->
<div class="col-md-4">
<label class="small mb-1" for="inputprofile_last_name">Last Name</label>
<input class="form-control" name="inputprofile_last_name" id="inputprofile_last_name" type="text" placeholder="Last name" value="' . $current_user_data['profile_last_name'] . '">
</div>
</div>

<div class="row gx-3 mb-3">
<!-- Form Group (gender)-->
<div class="col-md-4">
<label class="small mb-1" for="inputprofile_gender">Gender</label>                      
<select name="inputprofile_gender" class="form-select form-select mb-3" aria-label=".form-select example">
' . $display->list_gender($current_user_data['profile_gender']) . '
</select>
</div>
</div>

';



$passgenerator = '';
if ($current_user_data['profile_password'] == '') {
  $passgenerator = '&nbsp;&nbsp;&nbsp;   <button id="generatePassword" type="button" class="btn btn-sm custom-button-sm btn-outline-success my-0 py-0 mb-1">Generate Password</button>';
}

echo '
<hr class="mt-4 mb-4">
<!-- Contact Details -------------------------------------------------------------------------------------------- -->
<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">Enrollment Credential Details:</h4></div>

<!-- Form Group (username)-->
<div class="col-md-6">
<label class="small mb-1" for="inputprofile_Username">Username</label>
<div class="input-group mb-3">
<input type="text" class="form-control" name="inputprofile_Username" id="inputprofile_Username" placeholder="Enter your username" value="' . $current_user_data['profile_username'] . '">
</div>
</div>
<!-- Form Group (password)  -->
<div class="col-md-6">
<label class="small mb-1" for="inputprofile_password">Password ' . $passgenerator . '</label>
<div class="input-group">
<input class="form-control" name="inputprofile_password" id="input_password" type="password" placeholder="Enter your Password" value="' . $current_user_data['profile_password'] . '"  autocomplete="new-password">
<button class="btn btn-outline-secondary custom-button" id="togglePassword" type="button"><i class="field-icon toggle-password bi bi-eye-fill"></i></button>
</div>
</div>
';



$emaillock = false; // Default to false

// Check if 'feature_email' is set and not empty
if (!empty($current_user_data['feature_email'])) {
  $emaillock = true;
}

// Further checks only if 'profile_email' and 'feature_email' are set
if (isset($current_user_data['profile_email'], $current_user_data['feature_email'])) {
  // Check if 'profile_email' is the same as 'feature_email' and 'feature_email' contains '@mybdaygold.com'
  if ($current_user_data['profile_email'] === $current_user_data['feature_email'] && strpos($current_user_data['feature_email'], '@mybdaygold.com') !== false) {
    $emaillock = true;
  }
}



if ($emaillock) {
  $emailfieldcontent = '
<div class="input-group py-0 my-0">
<input class="form-control" name="profile_email" id="inputprofile_email" type="email"  aria-label="email" aria-describedby="basic-addon2" value="' . $current_user_data['feature_email'] . '" disabled readonly>
<input name="inputprofile_email" id="hiddenprofile_email" type="hidden" value="' . $current_user_data['feature_email'] . '">
<span class="input-group-text text-success py-0 my-0" id="basic-addon2"><i class="bi bi-lock-fill px-2"></i></span>
</div>
';
} else {
  $emailfieldcontent = '<input class="form-control" name="inputprofile_email" id="inputprofile_email" type="email" placeholder="Enter your email address" value="' . $current_user_data['profile_email'] . '">
';
}
echo '
<!-- Form Group (email)-->
<div class="col-md-6">
<label class="small mb-1" for="inputprofile_email">Email Address</label>
' . $emailfieldcontent . '
</div>
';


echo '
<!-- Form Group (phone_number)-->
<div class="col-md-3">
<label class="small mb-1" for="inputprofile_phone_number">Mobile Number</label>
<input class="form-control" name="inputprofile_phone_number" id="inputprofile_phone_number" type="tel" placeholder="Enter your mobile number" value="' . $current_user_data['profile_phone_number'] . '">
</div>
<!-- Form Group (phone_type)-->
<div class="col-md-3">
<label class="small mb-1" for="profile_phone_type">Phone Type</label>
<select  name="inputprofile_phone_type" class="form-select form-select mb-3" aria-label=".form-select example">
' . $display->list_phonetype($current_user_data['profile_phone_type']) . '
</select>
</div>
</div>



<hr class="mt-4 mb-4">
<!-- Mailing Address -------------------------------------------------------------------------------------------- -->
<!-- Form Row-->
<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">Enrollment Mailing Address:</h4></div>
<small class="mb-2">Some businesses may mail you things on your birthday.</small>
<!-- Form Group (organization name)-->
<div class="col-md-12">
<input class="form-control" name="inputprofile_mailing_address"  id="inputprofile_mailing_address" type="text" placeholder="Mailing Address" value="' . $current_user_data['profile_mailing_address'] . '">
</div>

</div>
<!-- Form Row        -->
<div class="row gx-3 mb-3">

<!-- Form Group (location)-->
<div class="col-md-4">
<input class="form-control" name="inputprofile_City"  id="inputprofile_City" type="text" placeholder="City" value="' . $current_user_data['profile_city'] . '">
</div>


<!-- Form Group (organization name)-->
<div class="col-md-4">
<select name="inputprofile_State" class="form-select form-select mb-3" aria-label=".form-select example">
' . $display->list_state($profilemailingstate) . '
</select>

</div>
<!-- Form Group (location)-->
<div class="col-md-4">
<input class="form-control"  name="inputprofile_zip_code"  id="inputprofile_zip_code" type="text" placeholder="Zipcode" value="' . $current_user_data['profile_zip_code'] . '">
</div>
</div>
';

// Define all labels in one place
$labels = [
  'honor' => ['military' => 'Veteran/Active Military', 'educator' => 'Educator', 'firstresponder' => 'First Responder'],
  'agree' => ['terms' => 'Terms/Condition', 'text' => 'Receiving SMS/Texts', 'email' => 'Receive Emails'],
  'allergy' => ['gluten' => 'Gluten', 'dairy' => 'Dairy', 'sugar' => 'Sugar', 'nuts' => 'Nuts'],
  'diet' => ['vegetarian' => 'Vegetarian Diet', 'vegan' => 'Vegan Diet', 'pescatarian' => 'Pescatarian Diet', 'paleo' => 'Paleo Diet', 'keto' => 'Keto Diet', 'kosher' => 'Kosher Diet']
];

$sections = array(
  'honor' => [
    'title' => 'Honor Class',
    'style' => 'switch',
    'columns' => 'col-md-4',
    'tag' => ''
  ],
  'agree' => [
    'title' => 'Agree to',
    'style' => 'switch',
    'columns' => 'col-md-4',
    'tag' => 'agree_'
  ],
  'allergy' => [
    'title' => 'Allergens',
    'style' => 'checkbox',
    'columns' => 'col-md-3',
    'tag' => 'allergy_'
  ],
  'diet' => [
    'title' => 'Diet Preferences',
    'style' => 'checkbox',
    'columns' => 'col-md-4',
    'tag' => 'diet_'
  ]
);

foreach ($sections as $section => $sectionData) {

  echo '<!-- SECTION ' . strtoupper($section) . ' START -------------------------------------------------------------------------------------------- -->';


  if ($current_user_data['account_type'] == 'minor' &&  $section == 'honor') {
    echo '<div class="row gx-3 mb-3 d-none">';
  } else {
    echo '<hr class="mt-4 mb-4">';
    echo '<div class="row gx-3 mb-3">';
  }



  echo '<div><h4 class="fw-bold">' . $sectionData['title'] . ':</h4></div>';
  $nametag = $sectionData['tag'];
  $optionlist = $account->getuseroptions($section);
  foreach ($optionlist as $option) {
    $isChecked = (isset($current_user_data['profile_' . $nametag . '' . $option]) && $current_user_data['profile_' . $nametag . '' . $option] == 'true') ? 'checked' : '';
    $icon = '';
    if ($option == 'kosher') $icon = ' <i class="fas fa-exclamation-triangle"  ' . $display->tooltip("Kosher Rewards are very limited") . '></i>';

    echo '
<!-- Form Group ' . $option . ' -->
<div class="' . $sectionData['columns'] . '">
<div class="form-check form-' . $sectionData['style'] . '">
<input class="form-check-input" type="checkbox" role="' . $sectionData['style'] . '" value="true" name="inputprofile_' . $nametag . '' . $option . '" id="inputprofile_' . $nametag . '' . $option . '" ' . $isChecked . '>
<label class="form-check-label" for="inputprofile_' . $nametag . '' . $option . '">' . $labels[$section][$option] . $icon . '</label>
</div>
</div>
';
  }
  echo '</div>';
}


echo '</div>
<div class="m-5 text-center">
<!-- Save changes button-->
<button class="btn btn-success px-5" ' . $locktag . ' type="submit">Save Changes</button>
</div>
</fieldset>
</form>
</div>
</div>
</div>
</div>
';


// MODALS FOR LEARN MORE CONTENT
echo '
<!-- Instruction modal   -->
<div class="modal fade" id="instructionsModal" tabindex="-1" role="dialog" aria-labelledby="ModalCenterTitle" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header  bg-primary">
<h5 class="modal-title">Instructions for Your Profile</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<p>Your profile contains important information that birthday.gold uses to enroll you for birthday freebies, deals,
and loyalty programs with the businesses you select.</p> <p>Please fill out your profile accurately and completely to 
ensure you can take full advantage of the birthday fun!</p> 

<h5 class="mt-5">Key Details:</h5> 
<ul> 
<li>Your name should match your legal identification for loyalty program registrations.  Some business may ask for ID that should match your account.</li>
<li>Provide your full mailing address for any physical mailings.</li>
<li>Add your email and phone number so businesses can contact you.</li>
<li>Specify any food allergies or diet preferences to receive appropriate options.</li>
<li>Agree to the terms, texts, and emails to get all the deals.</li> 
</ul>
<p>Once we enroll you into the business you select, birthday.gold cannot directly change any registration details. 
Please ensure everything is correct!</p>
<p>We\'re excited to use your profile to maximize the birthday fun!</p> 
</div>
<div class="modal-footer">
<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>
';

echo '
<!-- Profile Update modal   -->
<div class="modal fade" id="profileupdate" tabindex="-1" role="dialog" aria-labelledby="ModalCenterTitle" aria-hidden="true">
<div class="modal-dialog  modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Changes to Your Profile</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<p>By updating your profile you allowed birthday.gold to customize the way it interacts with you.</p> 

<h5>Key Details:</h5> 
<ul> 
';
foreach ($messages as $item) {
  echo '<li>' . $item . '</li>
';
}

echo '
</ul>
';
if ($suppressionitem) {
  echo '<p>We want you to have it exactly the way you want.  Maybe you can share the birthday treat/benefit with a friend who doesn\'t have the same dietary restrictions.  We just want you to have as many options as possible.</p> ';
}

if ($extremesuppression) {
  echo '<p style="color:red;">
<i class="fas fa-exclamation-triangle"></i> <!-- This is a yield sign icon -->
We wanna let you know one of your settings will have a significant impact on the number of businesses available to you.
</p>';
}

echo '
</div>
<div class="modal-footer">
<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>
</section>
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
</script>
<script src="/public/js/myaccount-profile.js?' . date('Ymdis') . '" language="javascript"></script>
<script src="/public/js/myaccount.js" language="javascript"></script>

' . $display->tooltip('-js-') . '





<script>
// Get the parent div of the button which has the tooltip
const tooltipDiv = document.getElementById("tooltipDiv1");

// Initialize the tooltip
new bootstrap.Tooltip(tooltipDiv, {});
const tooltipInstance = bootstrap.Tooltip.getInstance(tooltipDiv);
const radios = document.querySelectorAll(\'input[type=radio][name="account_plan"]\');
const submitBtn = document.getElementById("usersubmitBtn");
console.log(submitBtn);
// Add event listener for each radio button
radios.forEach(function(radio) {
radio.addEventListener("change", function() {
if (this.checked) {
submitBtn.removeAttribute("disabled");
console.log("plan selected");
if (tooltipInstance) {
try {
tooltipInstance.dispose();
} catch (error) {
}
} else {
}
}
});
});
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
