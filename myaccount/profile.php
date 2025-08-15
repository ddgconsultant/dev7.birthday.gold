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
    
    // Mark eligibility for refresh if profile changed
    if ($userdata_beforehash != $userdata_afterhash) {
        require_once($installpath . 'core/classes/class.enrollment.php');
        $enrollment = new Enrollment();
        $enrollment->markMemberEligibilityStale($current_user_data['user_id']);
        session_tracking('ELIGIBILITY', 'Marked user eligibility stale after profile update');
    }

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

// Include floating labels CSS BEFORE headers
$additionalstyles .= '
<link rel="stylesheet" href="/public/css/floating-labels.css?v=' . date('YmdHis') . '">
<style>
/* Profile page specific styles */
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Positioning context for floating panel */
@media (min-width: 992px) {
    .col-lg-4 {
        position: relative; /* Positioning context for absolute positioning */
    }
}

/* Ensure proper row alignment for sticky */
.row {
    align-items: flex-start !important;
    display: flex;
    flex-wrap: wrap;
}

#infoAccordion {
    position: -webkit-sticky;
    position: sticky;
    top: 100px;
    z-index: 100;
}

/* Profile section headers */
.profile-section-header {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    padding-bottom: 0.5rem;
    font-size: 1.25rem;
    color: #212529;
}

/* Make section headers more prominent on mobile */
@media (max-width: 576px) {
    .profile-section-header {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--bs-primary);
        border-bottom: 3px solid var(--bs-primary);
        padding-bottom: 0.75rem;
        margin-bottom: 1.5rem;
    }
}

/* Enhanced card styles */
.card {
    border: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

/* Better checkbox/switch groups */
.preference-grid {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
}

@media (min-width: 768px) {
    .preference-grid.grid-3 {
        grid-template-columns: repeat(3, 1fr);
    }
    .preference-grid.grid-4 {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Modern switch styles - enhance Bootstrap defaults */
.form-switch {
    padding-left: 2.5em; /* Bootstrap default */
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    margin-right: 0.5rem; /* Add more space after switch */
}

.form-switch .form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

/* Add spacing between checkbox/switch items */
.preference-grid .form-check {
    margin-bottom: 1rem;
}

/* Ensure proper label spacing */
.form-check-label {
    margin-left: 0.25rem;
}

/* Give switches more room on small screens */
@media (max-width: 767px) {
    .preference-grid.grid-3 {
        grid-template-columns: 1fr;
    }
}

/* Locked email field styling */
.locked-email {
    position: relative;
}

.locked-email .lock-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
}

/* Adjust padding for locked email input */
.locked-email .floating-input {
    padding-right: 3rem;
}

/* Style dropdowns to match floating inputs */
select.form-control,
.floating-select {
    border: none;
    border-bottom: 2px solid #e9ecef;
    border-radius: 0;
    padding: 1rem 0 0.5rem 0;
    background: transparent;
    transition: all 0.3s ease;
    width: 100%;
    min-height: 44px;
    font-size: 1rem;
    line-height: 1.5;
    color: #495057;
}

/* Style placeholder option */
select.floating-select option[disabled] {
    color: #6c757d;
    font-style: italic;
}

/* When select has no value (showing placeholder) */
select.floating-select:has(option:disabled:checked) {
    color: #6c757d;
}

select.form-control:focus,
.floating-select:focus {
    outline: none;
    border-bottom-color: var(--bs-primary);
    box-shadow: none;
    background: transparent;
}

/* Desktop specific dropdown styling to match floating inputs */
@media (min-width: 992px) {
    select.form-control,
    .floating-select {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 2rem 1rem 0.375rem 1rem;
        background: white !important;
        transition: all 0.2s ease;
        height: auto;
        min-height: calc(3.5rem + 2px);
    }
    
    select.form-control:focus,
    .floating-select:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }
}

/* Form Labels (for non-floating fields) */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

/* Select field labels - always visible */
.select-label {
    position: absolute;
    left: 0;
    top: 0;
    font-size: 0.85rem;
    color: #6c757d;
    background: transparent;
    padding: 0;
    pointer-events: none;
    transform: translateY(-0.25rem);
    transition: all 0.3s ease;
}

/* Style select label on focus */
.floating-select:focus ~ .select-label {
    color: var(--bs-primary);
}

/* Style the chevron icon in select labels */
.select-label i {
    font-size: 0.75rem;
    margin-left: 0.25rem;
    opacity: 0.6;
}

/* Desktop adjustments for select labels to match floating labels */
@media (min-width: 992px) {
    .select-label {
        left: 0.75rem; /* Moved slightly left to better align */
        top: 1.125rem;
        background: transparent;
        transform: translateY(-1.1rem) scale(0.85);
        transform-origin: left top;
        font-size: 1rem;
        color: var(--bs-primary);
    }
    
    .floating-select:focus ~ .select-label {
        color: var(--bs-primary);
    }
    
    .select-label i {
        font-size: 0.65rem;
    }
}

/* Better spacing for form sections */
.form-section {
    margin-bottom: 3rem;
}

/* Save button spacing */
.save-button-container {
    margin-top: 4rem;
    margin-bottom: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
}

/* JavaScript-powered Floating Panel */
@media (min-width: 992px) {
    .help-panel-wrapper {
        position: relative;
        width: 100%;
        transition: none;
    }
    
    .help-panel-wrapper.is-floating {
        position: fixed;
        z-index: 1000;
    }
    
    .help-panel {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: box-shadow 0.3s ease, border-color 0.3s ease, opacity 0.3s ease;
        opacity: 0;
        visibility: hidden;
    }
    
    .help-panel.show {
        opacity: 1;
        visibility: visible;
    }
    
    .help-panel.active {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: var(--bs-primary);
    }
    
    /* Placeholder to maintain layout when floating */
    .help-panel-placeholder {
        display: none;
        visibility: hidden;
    }
    
    .help-panel-placeholder.active {
        display: block;
        visibility: visible;
    }
}

.help-panel .help-header {
    display: flex;
    align-items: center;
    color: #495057;
}

.help-content {
    min-height: 60px;
    max-height: 400px;
    overflow-y: auto;
    transition: all 0.3s ease;
}

/* Animate help content changes */
.help-content.updating {
    opacity: 0.3;
}

/* Style help content based on type */
.help-content h4 {
    font-size: 1rem;
    color: #212529;
    margin-bottom: 0.5rem;
}

.help-content ul {
    margin-bottom: 0;
    padding-left: 1.25rem;
}

.help-content ul li {
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

/* Add a subtle highlight when help is active */
.help-panel.active {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* Mobile Help Button and Panel */
@media (max-width: 991px) {
    .help-panel-wrapper {
        display: none !important;
    }
    
    /* Floating help button */
    .floating-help-button {
        position: fixed;
        bottom: calc(80px + env(safe-area-inset-bottom, 0px)); /* Above bottom nav + safe area */
        right: 20px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease;
    }
    
    .floating-help-button:hover {
        transform: scale(1.1);
    }
    
    .floating-help-button i {
        font-size: 1.5rem;
    }
    
    /* Help overlay */
    .mobile-help-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1050;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .mobile-help-overlay.show {
        display: block;
        opacity: 1;
    }
    
    /* Help panel */
    .mobile-help-panel {
        position: absolute;
        bottom: 60px; /* Above bottom nav */
        left: 10px;
        right: 10px;
        background: white;
        border-radius: 1rem;
        max-height: 50vh; /* Reduced height */
        display: flex;
        flex-direction: column;
        transform: translateY(calc(100% + 60px));
        transition: transform 0.3s ease;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
    }
    
    .mobile-help-overlay.show .mobile-help-panel {
        transform: translateY(0);
    }
    
    .mobile-help-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #dee2e6;
        flex-shrink: 0;
    }
    
    .mobile-help-content {
        padding: 1.25rem;
        overflow-y: auto;
        flex: 1;
        padding-bottom: calc(2rem + env(safe-area-inset-bottom, 20px));
    }
    
    .mobile-help-content h4 {
        font-size: 1rem;
        color: #212529;
        margin-bottom: 0.5rem;
    }
    
    .mobile-help-content ul {
        margin-bottom: 0;
        padding-left: 1.25rem;
    }
    
    /* Pulse animation for button when content updates */
    .floating-help-button.pulse {
        animation: pulse 0.5s ease;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); }
    }
}

/* Improve mobile responsiveness */
@media (max-width: 576px) {
    .preference-grid {
        grid-template-columns: 1fr;
    }
    
    .row.gx-3 > * {
        padding-right: 0.5rem;
        padding-left: 0.5rem;
    }
    
    .save-button-container {
        margin-top: 3rem;
        margin-bottom: 2rem;
    }
}

/* Debug styles - remove after testing */
.help-panel-wrapper {
    /* border: 3px solid red; */ /* Uncomment to visualize floating element */
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

### nav-myaccount.php  DISPLAYS THE WIZARD WHEN $wizardmode = true
#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-person-circle me-3"></i>My Profile</h1>
            <p class="lead mb-0">Manage your enrollment profile information</p>
        </div>
    </div>
</div>

<?php



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
<div class="container profile-container my-5">
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
</div>

<!-- Desktop Help Panel -->
<div class="help-panel-wrapper d-none d-lg-block">
<div class="help-panel card shadow-sm mb-3">
<div class="card-body">
<div class="help-header mb-3">
<i class="bi bi-lightbulb text-warning fs-4"></i>
<h6 class="mb-0 ms-2">Quick Help</h6>
</div>
<div id="guidancecard" class="help-content">
<p class="text-muted mb-0">Click on any field to see helpful tips</p>
</div>
</div>
</div>
</div>

<!-- end left section -->
</div>
';

// PROFILE ENROLLMENT FIELDS
echo '
<div class="col-lg-8 d-flex flex-column">
';

// Mobile help panel - floating button with dismissible panel
echo '
<div class="d-lg-none">
<!-- Floating Help Button -->
<button class="btn btn-primary floating-help-button" id="mobileHelpToggle" aria-label="Quick Help">
<i class="bi bi-question-circle-fill"></i>
</button>

<!-- Mobile Help Panel Overlay -->
<div class="mobile-help-overlay" id="mobileHelpOverlay">
<div class="mobile-help-panel">
<div class="mobile-help-header">
<h6 class="mb-0">Quick Help</h6>
<button class="btn-close" id="mobileHelpClose" aria-label="Close"></button>
</div>
<div class="mobile-help-content" id="guidancecard-mobile">
<p class="text-muted">Tap any field to see helpful tips here</p>
</div>
</div>
</div>
</div>
';

echo '<div class="mb-4">
<h2 class="fw-bold mb-1">Your Enrollment Profile</h2>
<p class="text-muted">Complete your profile to maximize birthday rewards</p>
</div>
';


echo '
<!-- Account details card-->
<div class="card mb-4">
<div class="card-header d-flex justify-content-between align-items-center">
<span class="fw-semibold">Enrollment Details</span>
<button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#instructionsModal">
<i class="bi bi-info-circle me-1"></i>Instructions
</button>
</div>

<div class="card-body">
<form id="profileupdateForm" method="post" action="/myaccount/profile">
<fieldset ' . $locktag . '>
' . $display->inputcsrf_token() . '
<input name="profileupdate" type="hidden" value="1">

<!-- Personal Details Section -->
<div class="form-section">
<h4 class="profile-section-header">Personal Details</h4>

<div class="row gx-3">
<!-- Title dropdown with floating-style label -->
<div class="col-md-2 mb-3">
<div class="floating-label-group">
<select name="inputprofile_title" class="form-control floating-select has-value" aria-label="Title">
<option value="" disabled' . (empty($current_user_data['profile_title']) ? ' selected' : '') . '>Select...</option>
' . $display->list_title($current_user_data['profile_title']) . '
</select>
<label class="select-label">Title <i class="bi bi-caret-down-fill"></i></label>
</div>
</div>

<!-- First Name with floating label -->
<div class="col-md-4 mb-3">
<div class="floating-label-group">
<input type="text" 
       class="form-control floating-input" 
       name="inputprofile_first_name" 
       id="inputprofile_first_name" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_first_name']) . '">
<label for="inputprofile_first_name" class="floating-label">First Name</label>
</div>
</div>

<!-- Middle Name with floating label -->
<div class="col-md-2 mb-3">
<div class="floating-label-group">
<input type="text" 
       class="form-control floating-input" 
       name="inputprofile_middle_name" 
       id="inputprofile_middle_name" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_middle_name'] ?? '') . '">
<label for="inputprofile_middle_name" class="floating-label">Middle</label>
</div>
</div>

<!-- Last Name with floating label -->
<div class="col-md-4 mb-3">
<div class="floating-label-group">
<input type="text" 
       class="form-control floating-input" 
       name="inputprofile_last_name" 
       id="inputprofile_last_name" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_last_name']) . '">
<label for="inputprofile_last_name" class="floating-label">Last Name</label>
</div>
</div>
</div>

<div class="row gx-3">
<!-- Gender dropdown with floating-style label -->
<div class="col-md-4">
<div class="floating-label-group">
<select name="inputprofile_gender" class="form-control floating-select has-value" aria-label="Gender">
<option value="" disabled' . (empty($current_user_data['profile_gender']) ? ' selected' : '') . '>Select...</option>
' . $display->list_gender($current_user_data['profile_gender']) . '
</select>
<label class="select-label">Gender <i class="bi bi-caret-down-fill"></i></label>
</div>
</div>
</div>
</div>

';



$passgenerator = '';
if ($current_user_data['profile_password'] == '') {
  $passgenerator = '<button id="generatePassword" type="button" class="btn btn-sm btn-outline-success position-absolute" style="right: 10px; top: 8px;">Generate</button>';
}

echo '
<!-- Enrollment Credentials Section -->
<div class="form-section">
<h4 class="profile-section-header">Enrollment Credential Details</h4>

<div class="row gx-3">
<!-- Username with floating label -->
<div class="col-md-6 mb-3">
<div class="floating-label-group">
<input type="text" 
       class="form-control floating-input" 
       name="inputprofile_Username" 
       id="inputprofile_Username" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_username']) . '">
<label for="inputprofile_Username" class="floating-label">Username</label>
</div>
</div>

<!-- Password with floating label and toggle -->
<div class="col-md-6 mb-3">
<div class="floating-label-group password-floating-wrapper">
<input type="password" 
       class="form-control floating-input" 
       name="inputprofile_password" 
       id="input_password" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_password'] ?? '') . '" 
       autocomplete="new-password">
<label for="input_password" class="floating-label">Password</label>
<button class="password-toggle" id="togglePassword" type="button">
<i class="field-icon toggle-password bi bi-eye-fill"></i>
</button>
' . $passgenerator . '
</div>
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
<div class="floating-label-group locked-email">
<input class="form-control floating-input" 
       name="profile_email" 
       id="inputprofile_email" 
       type="email" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['feature_email']) . '" 
       disabled readonly>
<label for="inputprofile_email" class="floating-label">Email Address</label>
<span class="lock-icon text-success"><i class="bi bi-lock-fill"></i></span>
<input name="inputprofile_email" id="hiddenprofile_email" type="hidden" value="' . htmlspecialchars($current_user_data['feature_email']) . '">
</div>
';
} else {
  $emailfieldcontent = '
<div class="floating-label-group">
<input type="email" 
       class="form-control floating-input" 
       name="inputprofile_email" 
       id="inputprofile_email" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_email']) . '">
<label for="inputprofile_email" class="floating-label">Email Address</label>
</div>
';
}

echo '
<div class="row gx-3">
<!-- Email with floating label -->
<div class="col-md-6 mb-3">
' . $emailfieldcontent . '
</div>

<!-- Phone Number with floating label -->
<div class="col-md-3 mb-3">
<div class="floating-label-group">
<input type="tel" 
       class="form-control floating-input" 
       name="inputprofile_phone_number" 
       id="inputprofile_phone_number" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_phone_number']) . '">
<label for="inputprofile_phone_number" class="floating-label">Mobile Number</label>
</div>
</div>

<!-- Phone Type dropdown with floating-style label -->
<div class="col-md-3 mb-3">
<div class="floating-label-group">
<select name="inputprofile_phone_type" class="form-control floating-select has-value" aria-label="Phone Type">
<option value="" disabled' . (empty($current_user_data['profile_phone_type']) ? ' selected' : '') . '>Select...</option>
' . $display->list_phonetype($current_user_data['profile_phone_type']) . '
</select>
<label class="select-label">Phone Type <i class="bi bi-caret-down-fill"></i></label>
</div>
</div>
</div>
</div>



<!-- Mailing Address Section -->
<div class="form-section">
<h4 class="profile-section-header">Enrollment Mailing Address</h4>
<p class="text-muted small mb-3">Some businesses may mail you things on your birthday.</p>

<div class="row gx-3">
<!-- Mailing Address with floating label -->
<div class="col-md-12 mb-3">
<div class="floating-label-group">
<input type="text" 
       class="form-control floating-input" 
       name="inputprofile_mailing_address" 
       id="inputprofile_mailing_address" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_mailing_address']) . '">
<label for="inputprofile_mailing_address" class="floating-label">Mailing Address</label>
</div>
</div>
</div>

<div class="row gx-3">
<!-- City with floating label -->
<div class="col-md-4 mb-3">
<div class="floating-label-group">
<input type="text" 
       class="form-control floating-input" 
       name="inputprofile_City" 
       id="inputprofile_City" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_city']) . '">
<label for="inputprofile_City" class="floating-label">City</label>
</div>
</div>

<!-- State dropdown with floating-style label -->
<div class="col-md-4 mb-3">
<div class="floating-label-group">
<select name="inputprofile_State" id="inputprofile_State" class="form-control floating-select has-value" aria-label="State">
<option value="" disabled' . (empty($profilemailingstate) ? ' selected' : '') . '>Select...</option>
' . $display->list_state($profilemailingstate) . '
</select>
<label class="select-label">State <i class="bi bi-caret-down-fill"></i></label>
</div>
</div>

<!-- Zip Code with floating label -->
<div class="col-md-4 mb-3">
<div class="floating-label-group">
<input type="text" 
       class="form-control floating-input" 
       name="inputprofile_zip_code" 
       id="inputprofile_zip_code" 
       placeholder=" " 
       value="' . htmlspecialchars($current_user_data['profile_zip_code']) . '">
<label for="inputprofile_zip_code" class="floating-label">Zip Code</label>
</div>
</div>
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
    'grid' => 'grid-3',
    'tag' => ''
  ],
  'agree' => [
    'title' => 'Preferences',
    'style' => 'switch',
    'grid' => 'grid-3',
    'tag' => 'agree_'
  ],
  'allergy' => [
    'title' => 'Allergens',
    'style' => 'checkbox',
    'grid' => 'grid-4',
    'tag' => 'allergy_'
  ],
  'diet' => [
    'title' => 'Diet Preferences',
    'style' => 'checkbox',
    'grid' => 'grid-3',
    'tag' => 'diet_'
  ]
);

foreach ($sections as $section => $sectionData) {

  if ($current_user_data['account_type'] == 'minor' &&  $section == 'honor') {
    echo '<div class="form-section d-none">';
  } else {
    echo '<div class="form-section">';
  }

  echo '<h4 class="profile-section-header">' . $sectionData['title'] . '</h4>';
  echo '<div class="preference-grid ' . $sectionData['grid'] . '">';
  
  $nametag = $sectionData['tag'];
  $optionlist = $account->getuseroptions($section);
  foreach ($optionlist as $option) {
    $isChecked = (isset($current_user_data['profile_' . $nametag . '' . $option]) && $current_user_data['profile_' . $nametag . '' . $option] == 'true') ? 'checked' : '';
    $icon = '';
    if ($option == 'kosher') $icon = ' <i class="fas fa-exclamation-triangle" ' . $display->tooltip("Kosher Rewards are very limited") . '></i>';

    echo '
<div class="form-check form-' . $sectionData['style'] . '">
<input class="form-check-input" 
       type="checkbox" 
       role="' . $sectionData['style'] . '" 
       value="true" 
       name="inputprofile_' . $nametag . '' . $option . '" 
       id="inputprofile_' . $nametag . '' . $option . '" 
       ' . $isChecked . '>
<label class="form-check-label" for="inputprofile_' . $nametag . '' . $option . '">
' . $labels[$section][$option] . $icon . '
</label>
</div>
';
  }
  echo '</div></div>';
}


echo '</div>
<div class="save-button-container text-center">
<!-- Save changes button-->
<button class="btn btn-success btn-lg px-5" ' . $locktag . ' type="submit">
<i class="bi bi-check-circle me-2"></i>Save Changes
</button>
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
<div class="modal-header bg-primary text-white">
<h5 class="modal-title">Instructions for Your Profile</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
<p>We are excited to use your profile to maximize the birthday fun!</p> 
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
  echo '<p>We want you to have it exactly the way you want.  Maybe you can share the birthday treat/benefit with a friend who does not have the same dietary restrictions.  We just want you to have as many options as possible.</p> ';
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
<!-- Google Maps API for Address Autocomplete -->
<script>
// Load Google Maps API with Places library
(function() {
    var script = document.createElement("script");
    script.src = "https://maps.googleapis.com/maps/api/js?key=' . ($sitesettings['GOOGLEAPI']['mainkey'] ?? '') . '&libraries=places&callback=initAddressAutocomplete";
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
})();
</script>

<!-- Address Autocomplete Script -->
<script src="/public/js/address-autocomplete.js?' . date('YmdHis') . '"></script>

<!-- Expert Floating Panel JavaScript Solution -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Only run on desktop
    if (window.innerWidth < 992) return;
    
    // Get the desktop panel wrapper specifically
    const helpPanelWrapper = document.querySelector(".col-lg-4 .help-panel-wrapper");
    if (!helpPanelWrapper) {
        console.log("Help panel wrapper not found");
        return;
    }
    
    // Create placeholder element to maintain layout
    const placeholder = document.createElement("div");
    placeholder.className = "help-panel-placeholder";
    helpPanelWrapper.parentNode.insertBefore(placeholder, helpPanelWrapper.nextSibling);
    
    // Get important elements
    const header = document.querySelector("header") || document.querySelector(".navbar");
    const leftCol = document.querySelector(".col-lg-4");
    const rightCol = document.querySelector(".col-lg-8");
    
    // Calculate header height
    const headerHeight = header ? header.offsetHeight : 80;
    const topOffset = headerHeight + 20; // 20px gap below header
    
    let ticking = false;
    let panelHeight = 0;
    let isFloating = false;
    let panelShown = false;
    let hasScrolled = false;
    
    function updateFloatingPanel() {
        // Only update if panel is shown
        const helpPanel = helpPanelWrapper.querySelector(".help-panel");
        if (!helpPanel || !helpPanel.classList.contains("show")) {
            ticking = false;
            return;
        }
        
        // On first show, set initial state
        if (!panelShown) {
            panelShown = true;
            panelHeight = helpPanelWrapper.offsetHeight;
            placeholder.style.height = panelHeight + "px";
            // Do not process positioning on first show - let it stay in natural position
            ticking = false;
            return;
        }
        
        // Do not float until user has actually scrolled
        if (!hasScrolled) {
            ticking = false;
            return;
        }
        
        const scrollY = window.pageYOffset;
        const leftColRect = leftCol.getBoundingClientRect();
        const rightColRect = rightCol.getBoundingClientRect();
        
        // Get original position from placeholder
        const originalTop = placeholder.offsetTop;
        
        // Calculate when to start floating
        const startFloat = originalTop - topOffset;
        
        // Calculate when to stop floating (bottom of right column)
        const rightColBottom = rightColRect.bottom + scrollY;
        const stopFloat = rightColBottom - panelHeight - topOffset;
        
        // Only update if state changes to prevent jumping
        if (scrollY >= startFloat && scrollY <= stopFloat && !isFloating) {
            // Start floating
            isFloating = true;
            helpPanelWrapper.classList.add("is-floating");
            helpPanelWrapper.style.position = "fixed";
            helpPanelWrapper.style.top = topOffset + "px";
            helpPanelWrapper.style.left = leftColRect.left + "px";
            helpPanelWrapper.style.width = leftColRect.width + "px";
            placeholder.classList.add("active");
        } else if (scrollY < startFloat && isFloating) {
            // Stop floating - return to normal
            isFloating = false;
            helpPanelWrapper.classList.remove("is-floating");
            helpPanelWrapper.style.position = "";
            helpPanelWrapper.style.top = "";
            helpPanelWrapper.style.left = "";
            helpPanelWrapper.style.width = "";
            placeholder.classList.remove("active");
        } else if (scrollY > stopFloat && isFloating) {
            // Update position when scrolled past bottom
            const newTop = topOffset - (scrollY - stopFloat);
            helpPanelWrapper.style.top = newTop + "px";
        }
        
        // Update dimensions if floating
        if (isFloating) {
            helpPanelWrapper.style.left = leftColRect.left + "px";
            helpPanelWrapper.style.width = leftColRect.width + "px";
        }
        
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateFloatingPanel);
            ticking = true;
        }
    }
    
    // Event listeners
    window.addEventListener("scroll", () => {
        hasScrolled = true;
        requestTick();
    });
    window.addEventListener("resize", () => {
        requestTick();
    });
    
    // Watch for panel visibility changes
    const observer = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            if (mutation.type === "attributes" && mutation.attributeName === "class") {
                const helpPanel = helpPanelWrapper.querySelector(".help-panel");
                if (helpPanel && helpPanel.classList.contains("show") && !panelShown) {
                    // Panel just became visible - just measure it, do not position
                    setTimeout(() => {
                        panelHeight = helpPanelWrapper.offsetHeight;
                        placeholder.style.height = panelHeight + "px";
                        panelShown = true;
                    }, 350); // Wait for fade-in transition
                }
            }
        });
    });
    
    observer.observe(helpPanelWrapper.querySelector(".help-panel"), {
        attributes: true,
        attributeFilter: ["class"]
    });
});
</script>
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
