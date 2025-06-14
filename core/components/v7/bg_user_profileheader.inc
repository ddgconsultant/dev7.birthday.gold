<!-- ========================================================================================================================================================== -->
<!-- START OF USERPROFILEHEADER -->
<!-- ========================================================================================================================================================== -->

<?PHP
include_once($dir['core_components'] . '/user_getaccountdetails.inc');
#$current_user_data=$session->get('current_user_data');

if (empty($useraccountdetails) && empty($current_user_data['user_id'])) {
    header('Location: /login.php');
    exit; 
}

$birthdaybanner=$session->get('display_birthday_banner', false);
if ($birthdaybanner) {
$coverbanner = '/public/images/site_covers/cbanner_happybirthday.jpg';
}
$additionalstyles.="
<style>
/* USERPROFILEHEADER styles ======================================== */
    .profile-header {
        position: relative;
        background: url('".$coverbanner."') no-repeat center center;
        background-size: cover;
        height: 200px;
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

</style>
".'
<link rel="stylesheet" href="/public/css/' . $website['ui_version'] . '/bg_userprofile.css">
';


$stafficon=$adminicon=$tmp_button='';
if ($account->isstaff()) {
    $exclusionlist='staff'; 
    $tmp_button='<button type="button" class="btn btn-sm btn-primary">Staff</button>'; 
    $stafficon='<span data-bs-toggle="tooltip" data-bs-placement="right" title="Staff"><small class="bi bi-person-badge text-black" data-fa-transform="shrink-4 down-2"></small></span>';
}
if ($account->isadmin()) {
    $exclusionlist='admin';
    $tmp_button='<a href="/admin/" class="btn btn-sm btn-primary">Admin</a>'; 
 $adminicon= '<span data-bs-toggle="tooltip" data-bs-placement="right" title="Admin"><small class="bi bi-shield-fill text-danger" data-fa-transform="shrink-4 down-2"></small></span>' ;
}

$bioline='';



$avatarbuttontag = 'Upload';
if (!empty($current_user_data['avatar'])) {
   $avatarbuttontag = 'Change';
}

$avatarfeature=2;
if ($account->isdeveloper()) $avatarfeature=2;
switch ($avatarfeature) {
    case 1:
$avatarcontent='   <div class="col-2 col-md-3 col-lg-3 profile-left text-center text-md-start">
                    <img src="' . $avatar . '" alt="Profile Picture" class="profile-pic  img-fluid rounded-circle">
                </div>';

                $avatarcontent = '
                <div class="col-2 col-md-3 col-lg-3 profile-left text-center text-md-start">
                    <div class="profile-pic">
                        <img src="' . $avatar . '" alt="Profile Picture" class="profile-avatarimage">
                    </div>
                </div>';
                
          /*      $avatarcontent = '
<div class="col-2 col-md-3 col-lg-3 profile-left text-center text-md-start">
    <div class="profile-pic" style="background-image: url(' . $avatar . ');"></div>
</div>';
*/

break;
case 2:
    $additionalstyles.= '
<style>
#profileavatarupload {
display: contents; /* Keeps the form from disrupting the layout */
}

.profile-pic {
position: relative;
border-radius: 50%; /* Make it a circle */
background-size: cover; /* Maintain aspect ratio and cover the container */
background-position: center; /* Center the image */
background-repeat: no-repeat; /* Avoid repeating the image */
background-color: #f0f0f0; /* Fallback background color */
height: auto !important; /* Maintain auto height for scaling */
aspect-ratio: 1/1; /* Ensures a 1:1 ratio to maintain a square */

object-fit: cover;
object-position: center;
}

.profile-avatarimage {
width: 50% !important;
height: 100%;
}

.overlay-icon {
position: absolute;
bottom: -55px; /* Stick to the bottom of the avatar */
left: 50%; /* Start at 50% from the left */
transform: translateX(-38%); /* Adjust to center the icon */

width: 30%; /* Width equals the avatars width */
height: 30%; /* Fixed height for the overlay */
display: flex;
justify-content: center;
align-items: center;
background-color: rgba(0, 0, 0, 0.6); /* Dark semi-transparent background */
color: white;
opacity: 0;  /* Hidden by default */
border-radius:  50%;  /* Rounded bottom corners to match the avatar */
transition: opacity 0.3s ease-in-out; /* Smooth fade-in */
cursor: pointer;
z-index: 2;  /* Overlay on top of the avatar */
}

.profile-left:hover .overlay-icon {
opacity: 1; /* Shows overlay when hovered */
}

</style>
    ';


    $avatarcontent = '
    <form id="profileavatarupload" method="POST" enctype="multipart/form-data">
        <div class="profile-left text-center text-md-start position-relative">
            <img src="' . $avatar . '" alt="Profile Picture" class="profile-pic ">
            
        ' . $display->inputcsrf_token() . '

   <input class="d-none"  id="profile-image" name="profile-image" type="file"  />
              <label class="mb-0 overlay-icon" for="profile-image">
                  <span class="bg-holder overlay overlay-0"></span>
                  <span class="z-1 text-white dark__text-white text-center fs-10">
                       <i class="bi bi-camera-fill  fs-7"></i>
                      <span class="d-block">' . $avatarbuttontag . '</span>
                  </span>
              </label>

        </div>
    </form>';
    
break;
    }

echo '
<!-- Profile Header -->
<div class="profile-header container-fluid">
    <div class="container">
        <div class="col-12">
            <div class="row align-items-end">
'.$avatarcontent.'
                <div class="col-10 col-md-9 col-lg-9 profile-right text-dark text-shadow fw-bold pb-4">
                    <h2 class="my-0 py-0 ">' . $app->time_based_greeting() . $current_user_data['first_name'] . '</h2>
                    <p>
                    ' . ($account->isverified() ? '<span data-bs-toggle="tooltip" data-bs-placement="right" title="Verified"><small class="bi bi-check-circle-fill text-info" data-fa-transform="shrink-4 down-2"></small></span>' : '') . '
                    '. $stafficon.'
                    ' .$adminicon. '
' . ($account->iscconsultant() ? '<span data-bs-toggle="tooltip" data-bs-placement="right" title="Commissioned Consultant"><small class="bi bi-badge-sd-fill text-success" data-fa-transform="shrink-4 down-2"></small></span>' : '') . '
';


// Initialize the parts of the string
$username = $current_user_data['username'] ?? '';
$city = $current_user_data['city'] ?? '';
$state = $current_user_data['state'] ?? '';
$years_old = $qik->plural2(($alive['years']), 'year') . ' old';

// Build the display string
$tmp_output = '@' . $username;

if (!empty($city) || !empty($state)) {
    $location = '';
    if (!empty($city)) {
        $location .= $city;
    }
    if (!empty($city) && !empty($state)) {
        $location .= ', ';
    }
    if (!empty($state)) {
        $location .= $state;
    }
    $tmp_output .= ' • <span class="d-none d-md-inline">' . $location . '</span>';
}

$tmp_output .= ' • ' . $years_old . '</p>
            <div class="profile-buttons d-none d-md-flex w-100 align-items-center">
                <div>
                    ';

// Echo the final output
echo $tmp_output;



// Define the paths where the button should not be displayed
$excluded_paths = ['home'=>[
    '/myaccount/',
    '/myaccount/index',
    '/myaccount/index.php',
],
'settings'=>[
    '/myaccount/account',
    '/myaccount/account.php',
],
'staff'=>[
    '/staff/',
    '/staff/index.php',
],
'admin'=>[
    '/admin/',
    '/admin/index.php',
],

];

// Get the current request URI
$current_path = $_SERVER['REQUEST_URI'];

// Check if the current path is not in the excluded paths
if (!in_array($current_path, $excluded_paths['home'])) {
    echo '<a href="/myaccount/" class="btn btn-sm btn-primary">Account Home</a>';
}
if (!in_array($current_path, $excluded_paths['settings'])) {
    echo '<a href="/myaccount/account"  class="btn btn-sm btn-primary">Settings</a>';
}
if ($tmp_button!='') {
    if (!in_array($current_path, $excluded_paths[$exclusionlist])) {
 echo '' . $tmp_button . '';
}
}        
echo '
                </div>

            </div>
        </div>
    </div>
</div>
</div>
</div>
';



$userprofilemainaction['collect_tag_large'] = 'Pick from ' . $website['numberofbiz'] . '+ '.$website['biznames'];
$userprofilemainaction['redeem_tag_large'] = 'View your 5 rewards';
$userprofilemainaction['celebrate_tag_large'] = '34 days away';

$userprofilemainaction['collect_tag_small'] = '' . $website['numberofbiz'] . '+ '.$website['biznames'];
$userprofilemainaction['redeem_tag_small'] = '5 rewards';
$userprofilemainaction['celebrate_tag_small'] = 'in 34 days';



echo '
<!-- ========================================================================================================================================================== -->
<!-- END OF USERPROFILEHEADER -->
<!-- ========================================================================================================================================================== -->
';
