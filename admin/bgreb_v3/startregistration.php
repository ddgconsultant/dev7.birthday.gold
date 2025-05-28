<?php
// startregistration.php
$dir['base'] = $BASEDIR = __DIR__ . "/../.." ?? $_SERVER['DOCUMENT_ROOT'];
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# check for validation of authorized agent
#-------------------------------------------------------------------------------
// Ensure all required query string variables are provided
if (isset($_GET['sid'], $_GET['aid'], $_GET['uid'])) {
$sid = $_GET['sid'];
$aid = $_GET['aid'];
$uid = $_GET['uid'];


// Validate the provided 'sid' by checking if it exists in bg_sessions and matches the corresponding 'aid'
$stmt = $database->prepare("SELECT user_id FROM bg_sessions WHERE session_id = :sid AND user_id = :aid");
$stmt->bindParam(':sid', $sid, PDO::PARAM_STR);
$stmt->bindParam(':aid', $aid, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result && $result['user_id'] === (int)$aid) {
// Provided 'sid' is valid, start the session with the given 'sid' as the session name
if (session_status() === PHP_SESSION_ACTIVE) {
session_abort();
}
@session_name($sid);
@session_start();
// Do any additional processing with the session data as needed
$continue = true;
// Redirect to the new subdomain with the original query string
#     $url = 'https://bgrab.birthday.gold/startregistration?' . $_SERVER['QUERY_STRING'];
#     header('Location: ' . $url);
#      exit();
}
}




#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


// Check if the HTTP referer is set
$referralsite = $session->get('referralsite', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);


if (!empty($referralsite)) {
$session->set('referralsite', $referralsite);
// Parse the referer URL to get parts
$refererParts = parse_url($referralsite);

// Check if the host part is available
if (!empty($refererParts['host'])) {
// Set linkprefix to the protocol and host of the referer
$linkprefix = (empty($refererParts['scheme']) ? 'http:' : $refererParts['scheme'] . ':') . '//' . $refererParts['host'];
}
}


#include($BASEDIR . '/core/'.$website['ui_version'].'/nav-myaccount.php');
include($dir['core_components'] . '/bg_user_profileheader.inc');



$userId = 20;
if (isset($_REQUEST['uid'])) $userId = $_REQUEST['uid'];

$sql = "
SELECT distinct 
u.user_id, u.birthdate,
u.profile_username, u.profile_email, u.profile_password, u.profile_title, u.profile_first_name, u.profile_middle_name, u.profile_last_name, 
u.profile_mailing_address, u.profile_city, u.profile_state, u.profile_zip_code, u.profile_country, u.profile_phone_number, u.profile_gender, 
u.profile_agree_terms, u.profile_agree_email, u.profile_agree_text, u.profile_allergy_gluten, u.profile_allergy_sugar, u.profile_allergy_nuts, u.profile_allergy_dairy, 
u.profile_diet_vegan, u.profile_diet_kosher, u.profile_diet_pescatarian, u.profile_diet_keto, u.profile_diet_paleo, u.profile_diet_vegetarian,
u.profile_military, u.profile_educator, u.profile_firstresponder,
uc.company_id, c.company_name, c.status as company_status,
uc.status as enrollment_status, uc.reason, uc.modify_dt, c.signup_url
FROM bg_users AS u
INNER JOIN bg_user_companies AS uc
ON (u.user_id = uc.user_id and  (   (uc.`status` in ('pending', 'selected', 'queued')) or
(uc.`status`='failed' and u.modify_dt>uc.modify_dt) )  )
INNER JOIN bg_companies AS c
ON (uc.company_id = c.company_id  and c.status='finalized'
and c.signup_url != '".$website['apponlytag']."' )
Where  u.user_id=" . $userId . " and u.`status` = 'active'
";

$stmt = $database->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rowCount = $stmt->rowCount();



echo '<section class=" main-content">';

echo '<div class="container">
<div class="row">

<div class="d-flex justify-content-between align-items-center mt-5">
<div class="h3">
Pending User Enrollments
<span class="badge rounded-pill bg-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $rowCount . ' Enrollments">' . $rowCount . '</span>
</div>
'.$display->enrollerextensiondownload().'
</div>

<p>
' . date('r') . '
</p>
<hr>
</div>
</div>
';


$output = $adminoutput = '';
$cardsperrow = 3;
$i = 0;
$addedUsers = [];



#-------------------------------------------------------------------------------
# GENERATE THE ADMIN USER CARD
#-------------------------------------------------------------------------------
$adminprofile = $account->getuserdata($aid, 'user_id');
$validatedata['rawdata'] = $adminprofile['email'];
$validatedata['user_id'] = $adminprofile['user_id'];
$validatedata['sendcount'] = 1;
$validatedata['type'] = 'bgrabadminauth';
$validationcodes = $app->getvalidationcodes($validatedata);
$showdetails = false;
$adminprofile['acode'] = $validationcodes['long'];

$adminoutput .= '
<div class="mb-5">
<div class="card">
<h5 class="card-header fw-bold">ADMIN: ' . $adminprofile['first_name'] . ' ' . $adminprofile['last_name'] . '</h5>
<div class="card-body">
<p class="card-text">Email: ' . $adminprofile['email'] . '</p>
<p class="card-text">Session ID: ' . session_name() . '</p>
<p class="card-text">Session Elements: ' . ($showdetails ? print_r($_SESSION, 1) : count($_SESSION)) . '</p>
<p class="card-text">Auth Code: ' . $validationcodes['long'] . '</p>
</div>
</div>
';



#-------------------------------------------------------------------------------
# Get companies 
#-------------------------------------------------------------------------------
$companylist_output = '<ol>'; // Start ordered list
foreach ($results as $userDetails) {
    if ($userDetails['signup_url'] !== $website['apponlytag']) {
        // Check if a reason exists
        $hasReason = !empty($userDetails['reason']);
        $listItemClass = $hasReason ? 'text-danger' : '';
        $statusSuffix = $hasReason ? ' - ' . $userDetails['reason'] : '';

        $companylist_output .= '  
        <li class="mb-3 ' . $listItemClass . '">
            <a href="' . $dir['bge'] . '/companysetup?cid=' . $userDetails['company_id'] . '" target="_companysetup">
            [ ' . $userDetails['company_id'] . ' ]</a> - <b>' . $userDetails['company_name'] . '</b> / ' . $userDetails['company_status'] . '<br>
            <small>
                <a href="' . $userDetails['signup_url'] . '" target="signupwindow">' . $userDetails['signup_url'] . '</a>
            </small><br>
            <small>' . $userDetails['modify_dt'] . ': ' . $userDetails['enrollment_status'] . $statusSuffix . '</small>
        </li>';
    }
}
$companylist_output .= '</ol>'; // Close ordered list



#-------------------------------------------------------------------------------
# Set the userid
#-------------------------------------------------------------------------------
foreach ($results as $userDetails) {
if (in_array($userDetails['user_id'], $addedUsers)) {
continue;
}
$addedUsers[] = $userDetails['user_id'];
$i++;



#-------------------------------------------------------------------------------
# Get the JSON response from the URL AND VALIDATE THAT THE USER PROFILE IS CORRECT
#-------------------------------------------------------------------------------
$jsonResponse = file_get_contents('' . $dir['bge_web'] . '/bgr_getprocessdetails.php?type=bgrab&uid=' . $userDetails['user_id']);

// Validate the JSON response
$data = json_decode($jsonResponse, true);
$alertmessage = '';
if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
// JSON is invalid, report the error message
# $alertmessage= '<div class="bg-danger text-white fw-bold p-3">USER PROFILE IS NOT SETUP PROPERLY - Invalid JSON response: ' . json_last_error_msg().'</div>';
} else {
// JSON is valid, continue with processing the data
// $data contains the decoded JSON as a PHP array
// Example: echo $data['USERDETAILS']['username'];
}



#-------------------------------------------------------------------------------
# GENERATE THE USER CARD
#-------------------------------------------------------------------------------
$reg_userprofile = $account->getuserdata($userDetails['user_id'], 'user_id');
$profilecompletion = $account->profilecompletionratio($reg_userprofile);
$profilecompletionreport = $profilecompletion;
unset($profilecompletionreport['required_fields_notcompleted']);
unset($profilecompletionreport['optional_fields_notcompleted']);



$stats_ouput = '     <div class="">
<ul>';
foreach ($profilecompletionreport as $pcrname => $pcrvalue) {
$pcrtag = $pcrvalue;
$class = '';
if (strpos($pcrname, 'percentage') !== false) {
if (empty($pcrvalue)) {
$pcrvalue = 0;
} elseif (!is_numeric($pcrvalue)) {
// Log this case for further investigation
error_log("Non-numeric pcrvalue encountered: $pcrvalue");
$pcrvalue = 0;
}
$pcrtag =  $pcrvalue  . '%';
if ($pcrvalue < 30) {
$class = 'danger'; // Red color for less than 30% completion
} elseif ($pcrvalue >= 30 && $pcrvalue < 70) {
$class = 'warning'; // Yellow color for 30% - 70% completion
} else {
$class = 'success'; // Green color for more than 70% completion
}
}


if (is_array($pcrtag)) {
$pcrtagList = "<ul>";
foreach ($pcrtag as $item) {
$pcrtagList .= "<li>" . htmlspecialchars($item) . "</li>";
}
$pcrtagList .= "</ul>";
$pcrtag = $pcrtagList;
}

$stats_ouput .= '<li class="text-' . $class . '"><b>' . $pcrname . '</b> - ' . $pcrtag . '</li>';
}



$stats_ouput .= '
</ul>
</div>
<div class="">
<p class="fw-bold">Missing values:</p>
<ul>';

foreach ($profilecompletion['required_fields_notcompleted'] as $missing_field) {
$stats_ouput .= '<li class="text-danger">' . $missing_field . '</li>';
}


//  BEGIN BUILDING CARD
$output .= '
<!- ------------------------------------ CARD START ---------------------------------------------- ->

<div class="card bg-light">
' . $alertmessage . '
<div class="card-header bg-gray-100">
    <div class="d-flex align-items-center gap-4">
        <div class="flex-shrink-0">
    <div class="rounded-circle bg-primary-subtle fw-bold" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
    <img src="' . $reg_userprofile['avatar'] . '" alt="User Avatar" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
</div>
        </div>
      <div>
    <h5 class="fw-bold">
        <i class="bi bi-person-badge me-2"></i>
        ' . 
        (isset($reg_userprofile['profile_first_name']) ? $reg_userprofile['profile_first_name'] : '') . ' ' . 
        (isset($reg_userprofile['profile_last_name']) ? $reg_userprofile['profile_last_name'] : '') . '
    </h5>
    <div class="text-muted">
        <i class="bi bi-envelope me-2"></i>
        ' . (isset($reg_userprofile['profile_email']) ? $reg_userprofile['profile_email'] : '') . '
    </div>
    <div class="text-muted">
        <i class="bi bi-geo-alt me-2"></i>
        ' . (isset($reg_userprofile['profile_mailing_address']) ? $reg_userprofile['profile_mailing_address'] : '') . ', 
        ' . (isset($reg_userprofile['profile_city']) ? $reg_userprofile['profile_city'] : '') . ', 
        ' . (isset($reg_userprofile['profile_state']) ? $reg_userprofile['profile_state'] : '') . ' 
        ' . (isset($reg_userprofile['profile_zip_code']) ? $reg_userprofile['profile_zip_code'] : '') . '
    </div>
    ' . (isset($reg_userprofile['profile_phone_number']) ? '<div class="text-muted"><i class="bi bi-telephone me-2"></i>' . $reg_userprofile['profile_phone_number'] . '</div>' : '') . '
</div>
    </div>
</div>

<div class="card-body mt-4">
    <!-- Accordion PROFILE starts here -->
    <div class="accordion" id="userPROFILEAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button collapsed bg-primary-subtle fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfile" aria-expanded="false" aria-controls="collapseProfile">
                    User Profile Data
                </button>
            </h2>
            <div id="collapseProfile" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#userPROFILEAccordion">
                <div class="accordion-body">
                    <pre>' . print_r($reg_userprofile, 1) . '</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Accordion STATS starts here -->
    <div class="accordion mt-3" id="userSTATSAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingStats">
                <button class="accordion-button collapsed bg-primary-subtle fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStats" aria-expanded="false" aria-controls="collapseStats">
                    User Profile Stats
                </button>
            </h2>
            <div id="collapseStats" class="accordion-collapse collapse" aria-labelledby="headingStats" data-bs-parent="#userSTATSAccordion">
                <div class="accordion-body">
                    ' . $stats_ouput . '
                </div>
            </div>
        </div>
    </div>

    <!-- Accordion COMPANIES starts here -->
    <div class="accordion mt-3" id="userCompaniesAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingCompanies">
                <button class="accordion-button collapsed bg-primary-subtle fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompanies" aria-expanded="false" aria-controls="collapseCompanies">
                    Companies Found in Registration <span class="badge bg-warning ms-2">' . $rowCount . '</span>
                </button>
            </h2>
            <div id="collapseCompanies" class="accordion-collapse collapse" aria-labelledby="headingCompanies" data-bs-parent="#userCompaniesAccordion">
                <div class="accordion-body">
                    ' . $companylist_output . '
                </div>
            </div>
        </div>
    </div>
</div>
</div>';



#-------------------------------------------------------------------------------
# ADMIN BUTTONS
#-------------------------------------------------------------------------------
$adminoutput .= '
<div class="mt-3">
<div class="card">
<h5 class="card-header fw-bold">Action Buttons</h5>
<div class="card-body">

<a class="btn btn-secondary" target="test" href="' . $dir['bge'] . '/bgr_getprocessdetails.php?type=bgrab&acode=' . $adminprofile['acode'] . '&aid=' . $adminprofile['user_id'] . '&uid=' . $userDetails['user_id'] . '">Validate</a>
<button class="btn btn-success" onclick="processUser(' . $userDetails['user_id'] . ',' . $aid . ')">LOAD USER <span class="badge text-bg-secondary">' . $rowCount . '</span></button>
<a class="btn btn-secondary" target="test" href="https://birthday.gold/enrollment.php?_xtoken=' . (sha1($userDetails['user_id'])) . '&uid=' . $userDetails['user_id'] . '">Queue</a>

<!-- end buttons -->

</div>
</div>
</div>
';

echo '

</div>
</div>
</div>
</div>
';


// Generate modal for this user's companies
$companyQuery = '
SELECT
uc.user_company_id, uc.user_id, uc.company_id, uc.create_dt, uc.modify_dt, uc.status, uc.registration_dt, uc.registration_id, uc.registered_by, uc.registration_engine, uc.registration_detail, 
c.parent_company_name, c.company_name, c.company_display_name, c.category, c.status, c.source, c.info_url, c.signup_url, c.description, 
uccnt.registration_count
FROM  bg_user_companies AS uc
INNER JOIN bg_companies AS c
ON uc.company_id = c.company_id and c.status="finalized"
LEFT JOIN (select user_id, count(*) as registration_count from 	bg_user_companies  where `status` in ("pending", "selected", "queued") group by user_id) uccnt
ON 	 uccnt.user_id = uc.user_id
where uc.user_id= ' . $userDetails['user_id'] . '
';
$showcompanies = false;

if ($showcompanies) {
$companyStmt = $database->query($companyQuery);
$companyStmt->fetch();
#$companyStmt->execute([':userId' => $user['user_id']]);

$output .= '
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">Companies for ' . $userDetails['first_name'] . '</h5>
<button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
<!-- Modal content with company list -->
';

foreach ($companyStmt as $company) {
$output .= '
<div class="form-check">

<input class="form-check-input" type="checkbox" 
value="' . $company['company_id'] . '"
id="company' . $company['company_id'] . '">

<label for="company' . $company['company_id'] . '">
' . $company['company_name'] . ' 
</label>
</div>
</div>';
}
}

echo '<div class="container mt-3 px-0">
<div class="row">';
echo '<div class="col-4">' . $adminoutput . '</div></div>';
echo '<div class="col-8">' . $output . '</div>';
}

// Add modal HTML
echo '
<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="actionModalLabel">Processing Action</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalContent">
          <!-- Content will be loaded here -->
          <div class="d-flex justify-content-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>';

echo '  </div>

</div>
</section>';
?>



<script>
function processUser(userId, aid) {
  console.log('processUser called with:', { userId, aid });
  // Convert parameters to numbers if they're strings
  userId = parseInt(userId, 10);
  aid = parseInt(aid, 10);
  
  const event = new CustomEvent('processUser', {
    detail: {
      userId: userId,
      aid: aid
    },
    bubbles: true // Add this to ensure event bubbling
  });
  
  console.log('Dispatching event:', event);
  document.dispatchEvent(event);
  console.log('Event dispatched');
}
</script>

<?PHP
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();