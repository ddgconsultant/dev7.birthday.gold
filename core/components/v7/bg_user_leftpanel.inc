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

.user-panel-card {
background: white;
border-radius: 12px;
box-shadow: 0 2px 8px rgba(0,0,0,0.1);
overflow: hidden;
}

.card-header-stats {
background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 50%, #16213e 100%);
color: white;
padding: 1rem 1.5rem;
font-size: 1.2rem;
font-weight: 600;
display: flex;
align-items: center;
gap: 0.5rem;
}

.card-header-stats i {
font-size: 1.4rem;
}

.panel-section {
padding: 1.5rem;
border-bottom: 1px solid #e9ecef;
}

.panel-section:last-child {
border-bottom: none;
}

.section-header {
font-size: 1.1rem;
font-weight: 600;
color: #333;
margin-bottom: 1rem;
padding-bottom: 0.75rem;
border-bottom: 2px solid #f0f0f0;
display: block;
text-decoration: none;
transition: color 0.2s ease;
}

.section-header:hover {
color: #0056b3;
text-decoration: none;
}

.section-header i {
margin-right: 0.5rem;
}

.list-unstyled.list-userdetail {
margin-bottom: 0;
}

.list-unstyled.list-userdetail li {
padding: 0.5rem 0;
font-size: 0.9rem;
color: #495057;
display: flex;
align-items: center;
justify-content: space-between;
}

.list-unstyled.list-userdetail li i {
width: 20px;
margin-right: 0.75rem;
color: #6c757d;
text-align: center;
flex-shrink: 0;
}

.list-unstyled.list-userdetail li a {
color: #495057;
text-decoration: none;
display: flex;
align-items: center;
justify-content: space-between;
width: 100%;
transition: color 0.2s ease;
}

.list-unstyled.list-userdetail li a:hover {
color: #0056b3;
}

/* Label styling */
.list-label {
display: flex;
align-items: center;
flex-grow: 1;
}

.placeholder {
font-weight: 500;
color: #212529;
margin-left: auto;
background: none !important;
background-color: transparent !important;
display: inline !important;
padding: 0 !important;
border: none !important;
width: auto !important;
height: auto !important;
}

.badge {
font-size: 0.75rem;
}

/* Remove old title wrapper styles */
.title-wrapper {
display: none;
}

/* Force placeholder spans to display inline with no background */
.list-userdetail .placeholder,
.user-panel-card .placeholder {
background: none !important;
background-color: transparent !important;
width: auto !important;
height: auto !important;
}

/* Ensure completion badge and pencil stay on same line */
.placeholder .badge {
margin-right: 0.25rem;
}

.placeholder a {
display: inline !important;
}

/* Ensure ms-auto works for pencil icon */
.list-userdetail li .ms-auto {
margin-left: auto !important;
}
</style>
";


//==================================================================================================================
echo '
<!-- Main Content -->
<div class="main-content">
<div class="' . $lefpanelcontent['body_class'] . ' random-text">
<div class="row">

<div class="' . $lefpanelcontent['panel_class'] . ' d-none d-md-block">
' . $lefpanelcontent['prepanel'] . '
<div class="user-panel-card">
    <!-- Card Header -->
    <div class="card-header-stats">
        <i class="bi bi-graph-up"></i>
        Quick Stats
    </div>
    
    <!-- Personal Section -->
    <div class="panel-section">
        <a href="/myaccount/" class="section-header">
            <i class="bi bi-person-circle"></i>Personal
        </a>
        <ul class="list-unstyled list-userdetail">
            <li>
                <span class="list-label">
                    <i class="bi bi-calendar-day"></i> 
                    Next Birthdate
                </span>
                <span class="placeholder" data-bs-toggle="tooltip" data-bs-placement="top" title="'. $till['formatted_date'].'">
                    '.$qik->plural2($till['days'], 'day').' away
                </span>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-calendar-check"></i> 
                    Days Alive
                </span>
                <span class="placeholder">' . number_format($alive['days']) . '</span>
            </li>
            <li>
                <a href="'.$astrosign_horoscopelink.'" target="link">
                    <span class="list-label">
                        <i class="bi bi-star"></i>
                        Astro Sign
                    </span>
                    <span class="placeholder">' . $user_astrosigndetails['name'] . '</span>
                </a>
            </li>
        </ul>
    </div>';
// feature not enabled yet
/*
echo '<li><i class="bi bi-info-circle"></i> Fun Facts: <span class="placeholder">Interesting Fact</span></li>
<li><i class="bi bi-people"></i> Friends: <span class="placeholder">###</span></li>';
*/


//==================================================================================================================
echo '
    
    <!-- Enrollments Section -->
    <div class="panel-section">
        <a href="/myaccount/enrollment-history" class="section-header">
            <i class="bi bi-card-checklist"></i>Enrollments
        </a>
        <ul class="list-unstyled list-userdetail">
            <li>
                <span class="list-label">
                    <i class="bi bi-hourglass-split"></i> 
                    Available
                </span>
                <span class="placeholder">' . $businessoutput['counts']['remaining'] . '</span>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-hourglass"></i> 
                    Pending
                </span>
                <span class="placeholder">' . $businessoutput['counts']['pending'] . '</span>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-check-circle"></i> 
                    Successful
                </span>
                <span class="placeholder">' . $businessoutput['counts']['success'] . '</span>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-x-circle"></i> 
                    Failed
                </span>
                <span class="placeholder">' . $businessoutput['counts']['failed'] . '</span>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-calendar-check"></i> 
                    Scheduled
                </span>
                <span class="placeholder">' . $businessoutput['counts']['selected'] . '</span>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-gift"></i> 
                    # of Rewards
                </span>
                <span class="placeholder">' . (count($user_reward_results ?? [])) . '</span>
            </li>
        </ul>
    </div>';


//==================================================================================================================
echo '
    
    <!-- Profile Section -->
    <div class="panel-section">
        <a href="/myaccount/profile" class="section-header">
            <i class="bi bi-person-lines-fill"></i>Profile
        </a>
        <ul class="list-unstyled list-userdetail">
            <li>
                <a href="/myaccount/plan-details">
                    <span class="list-label">
                        <i class="bi bi-layout-text-sidebar-reverse"></i> 
                        Plan
                    </span>
                    <span class="placeholder">' . $userplanname . '</span>
                </a>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-check2-circle"></i> 
                    Completion
                </span>
                <span class="placeholder" style="display: flex !important; align-items: center; gap: 0.5rem;">
                    <span class="badge rounded-pill bg-' . $profilecompletion['required_percentage_color'] . '">' . $profilecompletion['required_percentage'] . '%</span>
                    <a class="text-600 text-end" href="/myaccount/profile" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit your profile" style="margin-left: auto;"><i class="bi bi-pencil"></i></a>
                </span>
            </li>';
            
if ($enabledHonorFlags != 'none' ) {
    echo '
            <li>
                <span class="list-label">
                    <i class="bi bi-award"></i> 
                    Honor Classes
                </span>
                <span class="placeholder">' . ucwords($enabledHonorFlags) . '</span>
            </li>';
}

echo '
            <li>
                <span class="list-label">
                    <i class="bi bi-cake"></i> 
                    Birthdate
                </span>
                <span class="placeholder">' . $current_user_data['birthdate'] . '</span>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-calendar"></i> 
                    Joined
                </span>
                <span class="placeholder">' . $accountanniversary['dtpart_year'] . '</span>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-geo-alt"></i> 
                    Location
                </span>
                <span class="placeholder">' . $location . '</span>
            </li>
        </ul>
    </div>';

// feature not enabled yet
/* echo '
<li><i class="bi bi-slash-circle"></i> Restrictions: <span class="placeholder">###</span></li>
';
*/


//==================================================================================================================
echo '
    
    <!-- Statistics Section (Larger screens only) -->
    <div class="panel-section d-none d-xxl-block">
        <a href="/myaccount/loginhistory" class="section-header">
            <i class="bi bi-bar-chart-line"></i>Statistics
        </a>
        <ul class="list-unstyled list-userdetail">
            <li>
                <a href="/myaccount/loginhistory">
                    <span class="list-label">
                        <i class="bi bi-clock-history"></i> 
                        Last Login
                    </span>
                    <span class="placeholder">' . $lastloginmessage['message'] . '</span>
                </a>
            </li>
            <li>
                <span class="list-label">
                    <i class="bi bi-bar-chart-line"></i> 
                    Login Count
                </span>
                <span class="placeholder">' . $logincount . '</span>
            </li>
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
    </div>
</div>'; // Close the card



echo $lefpanelcontent['postpanel'];
echo '
</div>

<!-- ========================================================================================================================================================== -->
<!-- END OF USERLEFTPANEL -->
<!-- ========================================================================================================================================================== -->
';
