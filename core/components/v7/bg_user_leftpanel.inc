<!-- ========================================================================================================================================================== -->
<!-- START OF USERLEFTPANEL -->
<!-- ========================================================================================================================================================== -->


<?PHP


include_once($dir['core_components'] . '/user_getaccountdetails.inc');


if (empty($useraccountdetails)) {
    header('Location: /login.php');
    exit;
}
$lefpanelcontent['body_class'] = $lefpanelcontent['body_class'] ?? 'container';
$lefpanelcontent['panel_class'] = $lefpanelcontent['panel_class'] ?? 'col-md-3 col-lg-3';
if (!isset($lefpanelcontent['prepanel'])) $lefpanelcontent['prepanel'] = '';
if (!isset($lefpanelcontent['postpanel'])) $lefpanelcontent['postpanel'] = '';

$additionalstyles .= "
<style>
/* USERLEFTPANEL styles ======================================== */
.main-content {
flex: 1;
overflow-y: auto;
padding: 20px;
}

.random-text {
margin-top: 60px;
}

.border-right {
border-right: 1px solid lightgrey;
}

.title-wrapper {
display: flex;
align-items: center;
margin-bottom: 5px; /* Adjust to your needs */
}
.title-wrapper h4 {
margin: 0;
white-space: nowrap;
padding-right: 10px; /* Adjust space between text and line */
}
.title-wrapper .title-line {
flex-grow: 1;
height: 1px;
background-color: #000; /* Adjust color of the line */
}
.list-unstyled.list-userdetail li {
padding: 5px 0;
font-size: .9rem; /* Adjust font size */
}
.list-unstyled.list-userdetail li i {
margin-left: 10px; /* Add right margin to icons */
margin-right: 10px; /* Add right margin to icons */
}
.placeholder {
background: none !important; /* Remove the background */
display: inline-block; /* Ensure it displays correctly inline */
margin-left:5px;
cursor: text !important;
}

a:hover .placeholder {
cursor: pointer !important; /* Change the cursor to pointer on hover */
}
</style>
";


//==================================================================================================================
echo '
<!-- Main Content -->
<div class="main-content">
<div class="' . $lefpanelcontent['body_class'] . ' random-text">
<div class="row">

<div class="' . $lefpanelcontent['panel_class'] . ' d-none d-md-block border-right">
' . $lefpanelcontent['prepanel'] . '
<ul class="list-unstyled list-userdetail">
<li>
<div class="title-wrapper">
<a href="/myaccount/" class="text-decoration-none"><h4>Personal</h4></a>
<div class="title-line"></div>
</div>
</li>
<li>
    <i class="bi bi-calendar-day"></i> 
    Next Birthdate: 
    <span class="placeholder" data-bs-toggle="tooltip" data-bs-placement="top" title="'. $till['formatted_date'].'">
        '.$qik->plural2($till['days'], 'day').' away
    </span>
</li>

<li><i class="bi bi-calendar-day"></i> Days Alive: <span class="placeholder">' . number_format($alive['days']) . '</span></li>
<li><a href="'.$astrosign_horoscopelink.'" target="link"><i class="bi bi-star"></i>Astro: <span class="placeholder">' . $user_astrosigndetails['name'] . '</span></a></li>
';
// feature not enabled yet
/*
echo '<li><i class="bi bi-info-circle"></i> Fun Facts: <span class="placeholder">Interesting Fact</span></li>
<li><i class="bi bi-people"></i> Friends: <span class="placeholder">###</span></li>';
*/


//==================================================================================================================
echo '
</ul>
<ul class="list-unstyled list-userdetail">
<li>
<div class="title-wrapper">
<a href="/myaccount/enrollment-history" class="text-decoration-none"><h4>Enrollments</h4></a>
<div class="title-line"></div>
</div>
</li>
<li><i class="bi bi-hourglass-split"></i> Available: <span class="placeholder">' . $businessoutput['counts']['remaining'] . '</span></li>
<li><i class="bi bi-hourglass-split"></i> Pending: <span class="placeholder">' . $businessoutput['counts']['pending'] . '</span></li>
<li><i class="bi bi-check-circle"></i> Successful: <span class="placeholder">' . $businessoutput['counts']['success'] . '</span></li>
<li><i class="bi bi-x-circle"></i> Failed: <span class="placeholder">' . $businessoutput['counts']['failed'] . '</span></li>
<li><i class="bi bi-calendar-check"></i> Scheduled: <span class="placeholder">' . $businessoutput['counts']['selected'] . '</span></li>
<li><i class="bi bi-calendar-check"></i> # of Rewards: <span class="placeholder">' . (count($user_reward_results ?? [])) . '</span></li>
</ul>
';


//==================================================================================================================
echo '
<ul class="list-unstyled list-userdetail">
<li>
<div class="title-wrapper">
<a href="/myaccount/profile" class="text-decoration-none"><h4>Profile</h4></a>
<div class="title-line"></div>
</div>
</li>
<li><a class="align-items-start mb-2" href="/myaccount/plan-details"><i class="bi bi-layout-text-sidebar-reverse"></i> Plan: <span class="placeholder">' . $userplanname . '</span></a></li>
<li><i class="bi bi-check2-circle"></i> Completion: <span class="placeholder">
<!-- Visible only on small and larger screens -->
<span class="badge rounded-pill bg-' . $profilecompletion['required_percentage_color'] . ' me-2 d-none d-sm-inline">' . $profilecompletion['required_percentage'] . '% Completed</span>

<!-- Visible only on extra small screens -->
<span class="badge rounded-pill bg-' . $profilecompletion['required_percentage_color'] . ' me-2 d-inline d-sm-none">' . $profilecompletion['required_percentage'] . '% Done</span>

<a class="text-600" href="/myaccount/profile">
<span data-bs-toggle="tooltip" data-bs-placement="top" title="Edit your profile"><span class="fas fa-pencil-alt"></span></span>
</a>
</span></li>
';
if ($enabledHonorFlags != 'none' ) {
    echo '<li><i class="bi bi-award"></i> Honor Classes: <span class="placeholder">' . ucwords($enabledHonorFlags) . '</span></li>';
}
echo '
<li><i class="bi bi-cake"></i> Birthdate: <span class="placeholder">' . $current_user_data['birthdate'] . '</span></li>
';

// feature not enabled yet
/* echo '   <li><i class="bi bi-heart"></i> Anniversary: <span class="placeholder">MM/DD/YYYY</span></li>
';
*/
echo '
<li><i class="bi bi-calendar"></i> Joined: <span class="placeholder">' . $accountanniversary['dtpart_year'] . '</span></li>
<li><i class="bi bi-geo-alt"></i> Location: <span class="placeholder">' . $location . '</span></li>
';

// feature not enabled yet
/* echo '
<li><i class="bi bi-slash-circle"></i> Restrictions: <span class="placeholder">###</span></li>
';
*/


//==================================================================================================================
echo '
</ul>
<ul class="list-unstyled list-userdetail  d-none d-xxl-block">
<li>
<div class="title-wrapper">
<span class="text-decoration-none"><h4>Statistics</h4></span>
<div class="title-line"></div>
</div>
</li>
<li><a class="align-items-start mb-2" href="/myaccount/loginhistory"><i class="bi bi-clock-history"></i> Last Login: <span class="placeholder">' . $lastloginmessage['message'] . '</span></a></li>
<li><i class="bi bi-bar-chart-line"></i> Login Count: <span class="placeholder">' . $logincount . '</span></li>
';

// feature not enabled yet
/*      <li><i class="bi bi-clock"></i> Average Session: <span class="placeholder">### mins</span></li>
*/
// feature not enabled yet
/*  <li><i class="bi bi-graph-up-arrow"></i> Activity Score: <span class="placeholder">###</span></li>
<li><i class="bi bi-award"></i> Achievements: <span class="placeholder">###</span></li>
*/
echo '
</ul>
';



echo $lefpanelcontent['postpanel'];
echo '
</div>

<!-- ========================================================================================================================================================== -->
<!-- END OF USERLEFTPANEL -->
<!-- ========================================================================================================================================================== -->
';
