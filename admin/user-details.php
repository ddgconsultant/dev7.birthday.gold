<?php
$addClasses[] = 'Referral';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES AND SETUP
#-------------------------------------------------------------------------------
$p_displaylength = 30;
$badgeColors = ['success', 'warning', 'primary', 'danger', 'info', 'secondary', 'dark'];
$colorIndex = 0;
$ipColorMap = [];

// Setup logs action
$logsaction = 'logs';
if (isset($_REQUEST['act'])) {
    switch($_REQUEST['act']) {
        case 'logs':
            $logsaction = 'morelogs';
            break;
        case 'morelogs':
            $logsaction = 'alllogs';
            break;
        case 'alllogs':
            $logsaction = 'logs';
            break;
    }
}


#-------------------------------------------------------------------------------
# GET USER DATA
#-------------------------------------------------------------------------------
if (isset($_REQUEST['u'])) {
    $workingUser = $qik->decodeId($_REQUEST['u']);
		$tmpsettings['status']='*';
    $workinguserdata = $account->getuserdata($workingUser, 'user_id', $tmpsettings);
    $getaccountdetailsuser = $workinguserdata;
  #  breakpoint($workinguserdata);
    include_once($dir['core_components'] . '/user_getaccountdetails.inc');
} else {
    header('location: /500');
    exit;
}


#-------------------------------------------------------------------------------
# HANDLE FORM ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    if (isset($_POST['formtype']) && ($_POST['formtype'] == 'changedisplaylength')) {
        $p_displaylength = intval($_POST['displaylength']);
    }
}
### MODAL FORM posts to set_accounttypeplan.php
### MODAL FORM posts to set_admin.php
### MODAL FORM posts to set_employee.php
### MODAL FORM posts to set_commission.php
### MODAL FORM posts to set_staff.php
### MODAL FROM posts to set_referrer.php



#-------------------------------------------------------------------------------
# SETUP REFERER DATA
#-------------------------------------------------------------------------------
$referer = $referral->getreferer($workinguserdata['user_id']);
$refererbuttontitle = 'Add Referer';
$refereraction = 'add';

if (!empty($referer)) {
    $refererbuttontitle = 'Change Referer';
    $refereraction = 'change';
}


#-------------------------------------------------------------------------------
# PAGE STYLES
#-------------------------------------------------------------------------------
$additionalstyles .= '
<style>
.cursor-pointer { cursor: pointer; }
#avatarModal .modal-backdrop.show { opacity: 0.85; }
.row-hover:hover { background-color: #f8f9fa; }
.profile-stat { font-size: 0.875rem; }
.profile-header { padding: 1.5rem; }
.badge-outline { 
    background-color: transparent;
    border: 1px solid currentColor;
}
</style>';


#-------------------------------------------------------------------------------
# START PAGE OUTPUT
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# START PAGE OUTPUT
#-------------------------------------------------------------------------------
$transferpagedata = [];
$transferpagedata['message'] = '';

// Check for any force_success_message or force_error_message in the session
if ($session->get('force_success_message', '') !== '') {
    $transferpagedata['message'] = $session->get('force_success_message');
    $session->unset('force_success_message');
} elseif ($session->get('force_error_message', '') !== '') {
    $transferpagedata['message'] = $session->get('force_error_message');
    $session->unset('force_error_message');
}

$transferpagedata = $system->startpostpage($transferpagedata);

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_admin_leftpanel.inc');

echo '
<section class="container main-content mt-0">
    <!-- Display any session messages -->
    ' . (isset($transferpagedata['message']) && !empty($transferpagedata['message']) ? $transferpagedata['message'] : '') . '
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 fw-bold">User Details: '.htmlspecialchars($workinguserdata['first_name']).' '.htmlspecialchars($workinguserdata['last_name']).'</h2>
        <a href="/admin/user-list" class="btn btn-sm btn-outline-secondary">
            Back to User List
        </a>
    </div>

<!-- User Profile Card -->
    <div class="card mb-4">
        <div class="card-body profile-header">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar" style="max-width: 150px; max-height: 150px;">
                        <img class="rounded-circle cursor-pointer w-100 h-100" 
                             src="'.$workinguserdata['avatar'].'" 
                             alt="User Avatar"
                             style="object-fit: cover; width: 150px; height: 150px;"
                             data-bs-toggle="modal" 
                             data-bs-target="#avatarModal">
                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-1">'.htmlspecialchars($workinguserdata['first_name']).' '.htmlspecialchars($workinguserdata['last_name']).'</h4>
                    <p class="mb-1 text-muted">Created: '.htmlspecialchars($workinguserdata['create_dt']).'</p>
                    <div class="d-flex mt-2">';

// User Role Badges
if ($account->isstaff('*', $workinguserdata['user_id'])) {
    echo '<span class="badge bg-success me-2">Staff</span>';
}
if ($account->isadmin($workinguserdata)) {
    echo '<span class="badge bg-danger me-2">Admin</span>';
}
if ($account->isbrandowner('*', $workinguserdata['user_id'])) {
    echo '<span class="badge bg-primary me-2">Brand Owner</span>';
}

echo '</div>
                </div>
                <div class="col-auto">
                    <div class="dropdown">
                        <button class="btn btn-lg btn-outline-primary dropdown-toggle" type="button" id="userActions" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userActions">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#accounttypeplanModal">
                                <i class="bi bi-person-gear me-2"></i>Set Account/Plan</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#refererModal">
                                <i class="bi bi-people-fill me-2"></i>Set Referer</a></li>';

if ($account->isadmin()) {
    echo '<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#employeeModal">
    <i class="bi bi-person-badge me-2"></i>Set Staff</a></li>';
    echo '<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adminModal">
            <i class="bi bi-shield-lock me-2"></i>Set Admin Role</a></li>';

    if ($workinguserdata['status'] != 'pending' && $workinguserdata['status'] != 'deleted') {  // impersonate 
        echo  '<li><a href="/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($workinguserdata['user_id']) . '&aid=' . $qik->encodeId($current_user_data['user_id']) . '&_token=' . $display->inputcsrf_token('tokenonly') . '" class="dropdown-item" type="button">
                <i class="bi bi-person-circle me-2"></i>Impersonate</a></li>';
    }
}

/*
echo '              <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash-alt me-2"></i>Delete User</a></li>
                            ';
                            */
     echo '                            
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>';

// Include the cards and modals
include('user_components/user-details_cards.inc');

// Activity Logs Section
echo '
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Recent Activity</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>IP Address</th>
                            <th>Action</th>
                            <th>Page</th>
                            <th>Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>';

// Activity Logs Query
$sql = "SELECT * FROM bg_sessiontracking 
        WHERE user_id = ? " . 
        ($mode != 'dev' ? "AND site = 'www'" : "AND type = 'user'") . 
        " ORDER BY create_dt DESC LIMIT 100";

try {
    $stmt = $database->prepare($sql);
    $stmt->execute([$workinguserdata['user_id']]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($logs as $log) {
        // Assign color to IP if not already assigned
        if (!isset($ipColorMap[$log['ip']])) {
            $ipColorMap[$log['ip']] = $badgeColors[$colorIndex % count($badgeColors)];
            $colorIndex++;
        }
        
        echo '<tr class="cursor-pointer" onclick="window.location=\'/admin/sessiondetails?id='.htmlspecialchars($log['id']).'\'">
                <td><span class="badge bg-'.htmlspecialchars($ipColorMap[$log['ip']]).'">'
                    .htmlspecialchars($log['ip']).'</span></td>
                <td>'.htmlspecialchars($log['name']).'</td>
                <td><code>'.htmlspecialchars($log['site']).' '.htmlspecialchars($log['page']).'</code></td>
                <td>'.htmlspecialchars($log['create_dt']).'</td>
              </tr>';
    }
} catch (PDOException $e) {
    // Log the error but don't display it to users
    error_log("Database error in user-details.php: " . $e->getMessage());
    echo '<tr><td colspan="4" class="text-center">Unable to load activity logs at this time.</td></tr>';
}

echo '</tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="'.$_SERVER['PHP_SELF'].'?u='.htmlspecialchars($qik->encodeId($workinguserdata['user_id'])).'&act='.htmlspecialchars($logsaction).'" 
               class="btn btn-falcon-default btn-sm">
                View More Logs
                <i class="fas fa-chevron-right ms-1"></i>
            </a>
        </div>
    </div>
</section>';

// Include all modals
include('user_components/user-details_modals.inc');


echo '</div></div></div>';
// Footer includes
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>