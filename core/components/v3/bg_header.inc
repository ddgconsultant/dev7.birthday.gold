<!-- ======================================================== -->
<!-- START OF HEADER -->
<!-- ======================================================== -->
<?PHP
if (!isset($bodyclass)) $bodyclass='';
$additionalstyles .= "
".'
<link rel="stylesheet" href="/public/css/' . $website['ui_version'] . '/bg_header.css?'.date('H').'"> 
<link rel="stylesheet" href="/public/css/' . $website['ui_version'] . '/bg_megamenu.css?'.date('H').'">
<link href="/public/assets/vendors/simplebar/simplebar.min.css" rel="stylesheet">
';

echo '
<style>
@media (max-width: 768px) {
    /* Overrides for mobile devices */
    .simplebar-content {
        overflow: auto !important;
    }
}
</style>
</head>

<body '.$bodyclass.'>
';

// Get the current URL path
$current_url = $_SERVER['REQUEST_URI'];

// Check if the current URL contains "/admin"
$adminenabledpage = $adminenabledpage ?? false;

if ($adminenabledpage || strpos($current_url, '/admin') !== false) {
$navclass = 'bg-danger-subtle';

} else {
$navclass = '';
}

$impersonator_enabled=false;

if ($account->isimpersonator()) {
$returntouser=   $session->get('impersonator', '');
        if (!empty($returntouser['user_id'])) {
#breakpoint($_SESSION);
$impersonator_enabled=true;
    $navclass = 'admin-impersonation-bg';
}
}
echo '
<nav class="navbar navbar-light '.$navclass.'">
    ';
    
    ?>
    <div class="container-fluid d-flex align-items-center ">
        <a class="navbar-brand header-logo" href="/">
<img class="d-block d-md-none" src="//cdn.birthday.gold/public/images/logo/bg_icon.png" alt="Company Icon" id="companyIcon" data-bs-toggle="tooltip" data-bs-placement="right" title="birthday.gold Home">
<img class="d-none d-md-block" src="//cdn.birthday.gold/public/images/logo/birthday.gold_logo.png" alt="Company Logo" id="companyLogo">
        </a>
        <div class="btn-group dropdown ms-auto" id="megaMenuGroup">
            <button class="btn btn-secondary btn-lg  ps-4 pe-5 fs-4 fw-bold" type="button" id="megaMenuButton">
                I want to...
            </button>
            <button type="button" class="btn btn-lg btn-secondary dropdown-toggle dropdown-toggle-split" id="megaMenuToggle">
            </button>
            <div class="dropdown-menu dropdown-menu-end bg_mega-menu" data-simplebar id="dropdownmegamenucontent">
<?PHP


echo '
                <div class="row p-3 pt-0">
                <span><a href="/" class="text-decoration-none fw-bold fs-9"><i class="bi bi-house-fill text-secondary me-1 fs-5"></i> birthday.gold Home</a></span>
    ';


    if (strpos($_SERVER['REQUEST_URI'], '/social/') !== false ) {
        echo '
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3 menu-icons icon-social">
                <h6 class="dropdown-header long-text">Social Community</h6>
                <h6 class="dropdown-header short-text">Community</h6>
                <a class="dropdown-item fw-bold" href="/social/create"><i class="bi bi-pencil-square"></i> Create Post</a>
                <a class="dropdown-item long-text" href="/social/search"><i class="bi bi-search"></i> Search</a>
                <a class="dropdown-item short-text" href="/social/activity"><i class="bi bi-activity"></i> Activity</a>
                <a class="dropdown-item long-text mini-screen" href="/social/settings"><i class="bi bi-gear"></i> Settings</a>
                <a class="dropdown-item" href="/social/trending"><i class="bi bi-graph-up"></i> Trending Posts</a>
                <a class="dropdown-item" href="/social/report"><i class="bi bi-flag"></i> Report</a>
                <a class="dropdown-item" href="/social/block"><i class="bi bi-slash-circle"></i> Block</a>
            </div>
        ';
    }


    echo '  <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3 menu-icons icon-create">
                        <h6 class="dropdown-header long-text">Plans & Features</h6>
                        <h6 class="dropdown-header short-text">Plans/Features</h6>
                        ';

if (!$account->isactive()) echo '     
                        <a class="dropdown-item fw-bold" href="/register"><i class="bi bi-person-plus"></i> Sign Up</a>     ';
                
echo '
                        <a class="dropdown-item long-text" href="/how-it-works"><i class="bi bi-question-circle"></i> How It Works</a>
                        <a class="dropdown-item short-text" href="/how-it-works"><i class="bi bi-question-circle"></i> How To</a>
                        <a class="dropdown-item long-text mini-screen" href="/eligibility"><i class="bi bi-check-circle"></i> Eligibility</a>
                        <a class="dropdown-item" href="/gift-certificate"><i class="bi bi-gift"></i> Gift Certificates</a>
                        <a class="dropdown-item long-text" href="/plans"><i class="bi bi-card-list"></i> Plans & Pricing</a>
                        <a class="dropdown-item short-text" href="/plans"><i class="bi bi-card-list"></i> Plans/Pricing</a>
';
if ($account->isadmin()) echo '     <a class="dropdown-item long-text" href="/social/"><i class="bi bi-chat-heart"></i> Social Community</a>
<a class="dropdown-item short-text" href="/social/"><i class="bi bi-chat-heart"></i> Community</a>
';

echo '</div>
                    ';





                    echo '
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3  menu-icons icon-signin">
                        <h6 class="dropdown-header">Sign In/Out</h6>
                    ';

if (!$account->isactive()) echo '     <a class="dropdown-item fw-bold" href="/login"><i class="bi bi-box-arrow-in-right"></i> Login</a>';
else
                echo '       <a class="dropdown-item mini-screen fw-bold" href="/logout"><i class="bi bi-box-arrow-right"></i> Logout</a>';

                    echo '
                        <a class="dropdown-item long-text" href="/redeem-giftcertificate"><i class="bi bi-card-checklist"></i> Redeem Gift Certificate</a>
                        <a class="dropdown-item short-text" href="/redeem-giftcertificate"><i class="bi bi-card-checklist"></i> Redeem Gift</a>
                        ';

                        echo '
                        <a class="dropdown-item mini-screen" href="/forgot"><i class="bi bi-key"></i> Forgot Password</a>
                    </div>

<div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3  menu-icons icon-account">
                        <h6 class="dropdown-header long-text">Account Management</h6>
                        <h6 class="dropdown-header short-text">Account</h6>
                        <a class="dropdown-item" href="/myaccount/"><i class="bi bi-person"></i> Account Home</a>
                        <a class="dropdown-item" href="/myaccount/account"><i class="bi bi-gear"></i> Settings</a>
                        <a class="dropdown-item long-text" href="/myaccount/plan-details"><i class="bi bi-card-list"></i> Plan Details</a>
                        <a class="dropdown-item" href="/myaccount/parental-mode"><i class="bi bi-person-badge"></i> Parental Mode</a>
                        <!-- <a class="dropdown-item" href="/myaccount/friends-list"><i class="bi bi-people"></i> Friends</a> -->
                        <a class="dropdown-item long-text" href="/unsubscribe"><i class="bi bi-x-circle"></i> Unsubscribe</a>
                    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3 menu-icons icon-help">
                        <h6 class="dropdown-header long-text">Help</h6>
                        <h6 class="dropdown-header short-text">Help</h6>
                        <a class="dropdown-item" href="/about"><i class="bi bi-info-circle"></i> About Us</a>
                        <a class="dropdown-item" href="/chat"><i class="bi bi-chat-dots"></i> Chat with Us</a>
                        <a class="dropdown-item long-text" href="/faq"><i class="bi bi-question-circle"></i> Frequently Asked Questions</a>
                        <a class="dropdown-item short-text" href="/faq"><i class="bi bi-question-circle"></i> FAQs</a>
                        <a class="dropdown-item long-text" href="/careers"><i class="bi bi-briefcase"></i> Careers</a>
                        <a class="dropdown-item" href="/contact"><i class="bi bi-envelope"></i> Contact Us</a>
                        <a class="dropdown-item long-text mini-screen" href="/social-media"><i class="bi bi-twitter"></i> Social Media</a>
                    </div>



                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3 menu-icons icon-enrollments">
                        <h6 class="dropdown-header">Pick Enrollments</h6>
                      '. (!$account->isactive() ? '<a class="dropdown-item" href="/discover"><i class="bi bi-search"></i> Search '.ucfirst($website['biznames']).'</a>':'').'
                        <a class="dropdown-item" href="/myaccount/select"><i class="bi bi-ui-checks"></i> Select '.ucfirst($website['biznames']).'</a>
                            <a class="dropdown-item long-text" href="/myaccount/profile"><i class="bi bi-person"></i> Enrollment Profile</a>
                        <a class="dropdown-item short-text" href="/myaccount/profile"><i class="bi bi-person"></i> Profile</a>
                    <a class="dropdown-item long-text" href="/myaccount/enrollment-schedule"><i class="bi bi-calendar-check"></i> Enrollment Schedule</a>
                        <a class="dropdown-item short-text" href="/myaccount/enrollment-schedule"><i class="bi bi-calendar-check"></i> Schedule</a>
                        <a class="dropdown-item long-text" href="/myaccount/enrollment-history"><i class="bi bi-clock-history"></i> Enrollment History</a>
                        <a class="dropdown-item short-text" href="/myaccount/enrollment-history"><i class="bi bi-clock-history"></i> History</a>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3 menu-icons icon-rewards">
                        <h6 class="dropdown-header">Redeem Rewards</h6>
                        <a class="dropdown-item" href="/myaccount/redeem"><i class="bi bi-gift"></i> Current Offers</a>
                        <a class="dropdown-item long-text" href="/myaccount/redeem-list"><i class="bi bi-list-check"></i> Upcoming Rewards</a>
                        <a class="dropdown-item short-text" href="/myaccount/redeem-list"><i class="bi bi-list-check"></i> Upcoming</a>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3  menu-icons icon-celebrate">
                        <h6 class="dropdown-header">Celebrate</h6>
                        <a class="dropdown-item long-text" href="/myaccount/tour-build"><i class="bi bi-calendar2-event"></i> Organize Rewards</a>
                        <a class="dropdown-item short-text" href="/myaccount/tour-build"><i class="bi bi-calendar2-event"></i> Organize</a>
                        <a class="dropdown-item long-text" href="/myaccount/rewards-calendar"><i class="bi bi-calendar3"></i> Rewards Calendar</a>
                        <a class="dropdown-item short-text" href="/myaccount/rewards-calendar"><i class="bi bi-calendar3"></i> Calendar</a>
                        <a class="dropdown-item" href="/myaccount/fun-facts"><i class="bi bi-info-circle"></i> Fun Facts</a>
                    <!--        <a class="dropdown-item long-text mini-screen" href="/myaccount/friends-connect"><i class="bi bi-people"></i> Connect with Friends</a>
                        <a class="dropdown-item short-text" href="/myaccount/friends-connect"><i class="bi bi-people"></i> Connect</a> -->
                    </div>
                    
';

if ( $account->isstaff() && 1==2) {

echo '                       <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3 menu-icons icon-staff mini-screen">
                        <h6 class="dropdown-header">Staff</h6>
                        <a class="dropdown-item" href="/admin/accessmanager/"><i class="bi bi-shield-lock"></i> Access Manager</a>
                        <a class="dropdown-item" href="/staff/hr"><i class="bi bi-people-fill"></i> HR</a>
                        <a class="dropdown-item" href="/staff/legal"><i class="bi bi-journal-text"></i> Legal</a>
                        <a class="dropdown-item" href="/staff/cs"><i class="bi bi-headset"></i> Customer Service</a>
                        <a class="dropdown-item" href="/staff/sales"><i class="bi bi-currency-dollar"></i> Sales</a>
                        <a class="dropdown-item" href="/chat"><i class="bi bi-chat"></i> RocketChat</a>
                        <a class="dropdown-item" href="/redirect_corporateholidays"><i class="bi bi-calendar"></i>Corporate Holidays</a>
                    </div>
    ';
}
if ($account->isactive() && $account->isadmin()) {
echo '                   <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3 menu-icons icon-admin mini-screen">
                        <h6 class="dropdown-header">Admin</h6>
                        <a class="dropdown-item" href="/admin/user-list"><i class="bi bi-list-ul"></i> User List</a>
                        <a class="dropdown-item long-text" href="/admin/brands"><i class="bi bi-briefcase"></i> '.ucfirst($website['bizname']).' Management</a>
                        <a class="dropdown-item short-text" href="/admin/brands"><i class="bi bi-briefcase"></i> '.ucfirst($website['biznames']).'</a>
                        <a class="dropdown-item long-text" href="/admin/redirect-metabase"><i class="bi bi-bar-chart"></i> Charts and Graphs</a>
                        <a class="dropdown-item long-text" href="/admin/systemavailability"><i class="bi bi-cpu"></i> System Availability</a>
                        <a class="dropdown-item short-text" href="/admin/systemavailability"><i class="bi bi-cpu"></i> Systems</a>
                        <a class="dropdown-item short-text" href="/chat"><i class="bi bi-chat"></i> RocketChat</a>
                    </div>
';
}


echo '             
                </div>
';
 $headeravatarmenuinboxlink='';
if ($account->isactive()) {
include_once($dir['core_components'] . '/user_getaccountdetails.inc');
echo '<div class="row d-none d-md-flex">
';
$headerbottomlinksize='col-md-4';
#breakpoint($featuremailcount);

if (!empty($current_user_data['feature_email'])){
    $headerbottomlinksize='col-md-3';
echo '
    <a href="/myaccount/mail" class="'. $headerbottomlinksize.' dropdown-bottom-button bg-secondary-subtle text-decoration-none d-flex align-items-center">
        <i class="bi bi-envelope me-2"></i>
        <div>
            <div class="dropdown-button-title">Your Mail</div>
            <div class="title">'.number_format($featuremailcount['unread']).' Messages</div>
        </div>
    </a>
    ';

  $headeravatarmenuinboxlink='<li><a class="dropdown-item fw-bold btn btn-sm  me-1" href="/myaccount/mail"><i class="bi bi-envelope me-2"></i>BGInbox</a></li>';

}

echo '
    <a href="/myaccount/redeem" class="'. $headerbottomlinksize.' dropdown-bottom-button bg-secondary-subtle text-decoration-none d-flex align-items-center">
        <i class="bi bi-trophy me-2"></i>
        <div>
            <div class="dropdown-button-title">Your Rewards</div>
            <div class="title">'.$qik->plural2(count($user_reward_results), 'Reward').'</div>
        </div>
    </a>
    <a href="/myaccount/profile" class="'. $headerbottomlinksize.' dropdown-bottom-button bg-secondary-subtle text-decoration-none d-flex align-items-center">
        <i class="bi bi-book me-2"></i>
        <div>
            <div class="dropdown-button-title">Your Profile</div>
            <div class="title">'.$profilecompletion['required_percentage'].'% completed</div>
        </div>
    </a>
    <div class="col-md-3 dropdown-bottom-button bg-secondary-subtle d-flex align-items-center">
        <i class="bi bi-cake me-2"></i>
        <div>
            <div class="dropdown-button-title">'.$qik->plural2($till['days'], 'Day').'</div>
            <div class="title">until your birthday</div>
        </div>
    </div>
</div>
';


}
echo '             
            </div>
        </div>';


        if ($account->isactive()) {
            // USER LOGGED IN
            $tmp_button = '';
            if ($account->isstaff()) {
                $tmp_button = '<li><a class="dropdown-item fw-bold btn btn-sm  me-1" href="/staff"><i class="bi bi-person-badge me-2 text-warning"></i>Staff</a></li>';
            }
            if ($account->isadmin()) {
                $tmp_button = '<li><a class="dropdown-item fw-bold btn btn-sm  me-1" href="/admin"><i class="bi bi-shield-lock me-2 text-danger"></i>Admin</a></li>';
            }
            $notification_icon = '
<div class="d-flex align-items-center ms-auto">
<div class="me-1">
<i class="bi bi-bell"></i>
</div>
';

echo '
<div class="ms-3">
<div class="dropdown avatar-menu">
<div class="avatar-placeholder rounded-circle" id="bg_avatarMenuMain" data-bs-toggle="dropdown" aria-expanded="false" role="button" 
style="background-image: url(' . ($current_user_data['avatar']??'') . ')"></div>
<ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu-190" aria-labelledby="bg_avatarMenuMain">
<li><a class="dropdown-item fw-bold btn btn-sm  me-1" href="/myaccount/"><i class="bi bi-house-door me-2"></i>Account Home</a></li>
<li><a class="dropdown-item fw-bold btn btn-sm  me-1" href="/myaccount/account"><i class="bi bi-gear me-2"></i>Settings</a></li>
'. $headeravatarmenuinboxlink.'
' . $tmp_button . '
<li>
<hr class="dropdown-divider">
</li>
<li><a class="dropdown-item btn btn-sm  me-1" href="/">Home</a></li>
<li><a class="dropdown-item btn btn-sm  me-1" href="/help">Help</a></li>
<li><a class="dropdown-item btn btn-sm  me-1" href="/logout">Logout</a></li>
';


if ($impersonator_enabled){
echo '<li><a class="dropdown-item btn btn-sm me-1 fw-bold text-danger" href="/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($returntouser['user_id']) . '&aid=' . $qik->encodeId($returntouser['user_id']) . '&revertimpersonation=1&_token=' . $display->inputcsrf_token('tokenonly') . '">';
echo '<i class="bi bi-slash-circle-fill text-danger"></i> Stop Impersonating';
#  echo $returntouserid;
echo '</a></li>';
    
}

echo '
</ul>
</div>
</div>';
        } else {
            #echo '<a href="/register" class="btn btn-sm btn-primary-outline ms-1 px-1">Sign Up</a>';
            echo '<a href="/login" class="btn btn-sm btn-primary px-1 ms-3">Login</a>';
        }

        if (!isset($bodycontentclass)) $bodycontentclass='my-4';
echo '
</div>
</nav>
<div class="row '.$bodycontentclass.'"></div>
';

        echo '   <script src="/public/js/' . $website['ui_version'] . '/bg_megamenu.js"></script>';
        ?>

<!-- ======================================================== -->
<!-- END OF HEADER -->
<!-- ======================================================== -->