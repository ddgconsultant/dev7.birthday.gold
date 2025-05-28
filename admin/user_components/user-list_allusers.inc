<?php
// Query for counting the total number of users
$countSql = "SELECT COUNT(*) AS total_cnt FROM bg_users where  type='real'";

// Prepare and execute the count query
$countStmt = $database->prepare($countSql);
$countStmt->execute();

// Fetch the total count
$totalCountRow = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalCount = $totalCountRow['total_cnt'];

// Build the prepared statement
$sql = "SELECT u.user_id, u.first_name, u.last_name, u.username, u.email, u.birthdate, u.city, u.state, u.`status`, u.account_product_id, u.account_admin, 
IFNULL(u.account_plan,'-') account_plan, IFNULL(u.account_type,'-') account_type, u.create_dt, u.modify_dt, DATEDIFF(NOW(),u.create_dt) days_old, 
a.description avatar, u.type, u.account_admin, lt.last_login_dt
FROM bg_users u
LEFT JOIN bg_user_attributes a ON u.user_id=a.user_id AND a.name='avatar' AND a.category='primary' AND a.`status`='active'
LEFT JOIN (SELECT user_id, MAX(modify_dt) last_login_dt FROM bg_logintracking WHERE `status`='A' GROUP BY user_id) lt ON u.user_id=lt.user_id 
WHERE u.type='real' " . $userlimitsql . "
ORDER BY u.create_dt DESC";

// Prepare the statement
$stmt = $database->prepare($sql);
$stmt->execute();

$content_rows = '';
$i = 0;
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$displayedUsersCount = count($rows);

$newUsersToday = 0; // Counter for new users

foreach ($rows as $row) {
    $i = 1;
    $defaultavatar =  '/public/avatars/problemavatar.png';
    $avatar = $row['avatar'] ?? $defaultavatar; // Default avatar if none is set
    $avatar = str_replace('cdn.birthday.gold', $website['cdnurl'], $avatar);
    $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
    $username = htmlspecialchars($row['username'] ?? ''); // Use an empty string if null
    $email = htmlspecialchars($row['email'] ?? ''); // Use an empty string if null
    $accountPlan = htmlspecialchars($row['account_plan'] ?? '-'); // Use an empty string if null
    $accountType = htmlspecialchars($row['account_type'] ?? '-'); // Use an empty string if null
    $accountStatus = htmlspecialchars($row['status'] ?? '-'); // Use an empty string if null
    $daysOld = $row['days_old'];

    if ($daysOld == 0) {
        $newUsersToday++;
    }

    $createDt = date('Y-m-d H:i', strtotime($row['create_dt']));
    $modifyDt = date('Y-m-d H:i', strtotime($row['modify_dt']));
    $lastloginDt = isset($row['last_login_dt']) && !empty($row['last_login_dt']) 
    ? date('Y-m-d H:i', strtotime($row['last_login_dt'])) 
    : 'none';

    $city = htmlspecialchars($row['city'] ?? '');
    $state = htmlspecialchars($row['state'] ?? '');
    $location = trim($city . ($city && $state ? ', ' : '') . $state);
    $birthday = $row['birthdate'] ?? '';
    $age ='';
    if ($birthday!='') {
    $agedata = $app->calculateage($row['birthdate']);
    if ($agedata)  $age=$agedata['years'];
    }

$staffbadgetag=$stafftag='';
    if ($account->isstaff('*', $row['user_id'])) {
        $staffbadgetag='<small class="badge fw-semi-bold rounded-pill text-bg-danger">staff</small>';
        $stafftag="staff";
    }


    $verifiedbadgetag=$verifiedtag='';
    if ($account->isverified('*', $row['user_id'])) {
        $verifiedbadgetag='<span data-bs-toggle="tooltip" data-bs-placement="right" title="Verified"><small class="bi bi-check-circle-fill text-primary ms-2" data-fa-transform="shrink-4 down-2"></small></span>';
        $verifiedtag="verified";
    }


    $adminbadgetag=$admintag='';
    if ($account->isadmin($row)) {
        $adminbadgetag='<span data-bs-toggle="tooltip" data-bs-placement="right" title="Admin"><small class="bi bi-shield-fill text-danger ms-2" data-fa-transform="shrink-4 down-2"></small></span>';
        $admintag="admin";
    }

    $content_rows .= '
    <div class="row border border-200 bglist-group-item py-3" data-full-context="' . $fullName . ' ' . $username . ' ' . $email . ' '.$stafftag.' '.$admintag.'">
        <div class="col-auto">
            <div class="avatar avatar-2xl">
                <img class="rounded-circle" src="' . $avatar . '" alt="" style="width:50px">
            </div>
        </div>
        <div class="col-4">
            <h6 class="mb-0 fw-semi-bold">' . $fullName . ' (' . $username . ') '. $verifiedbadgetag.' '. $adminbadgetag.'</h6>
      <a href="mailto:' . $email . '?subject=Welcome%20to%20Birthday.Gold%20%E2%80%93%20Transparency%20and%20Exciting%20Rewards%20Await!&body=%0A%0A%0A%0AWarm%20regards,%0A%0AYour%20friends%20at%20Birthday.Gold%20%F0%9F%8E%82">
         <p class="text-muted fs-11 mb-0">' . $email .  '</p>
 </a>
              <p class="text-muted fs-11 mb-0">' . $location .  '</p>
               <p class="text-500 fs-11 mb-0">' . $birthday. '<span class="ms-3">['.$age.']</span></p>
        </div>
<div class="col-2 text-center">
            <small class="badge fw-semi-bold rounded-pill text-bg-' . (isset($bg_users_accounttypecolors[$accountType]) ? $bg_users_accounttypecolors[$accountType] : 'danger') . '">' . $accountType . '</small>
            <small class="badge fw-semi-bold rounded-pill text-bg-' . (isset($bg_users_accountplancolors[$accountPlan]) ? $bg_users_accountplancolors[$accountPlan] : 'danger') . '"  data-bs-toggle="tooltip"  title="'.$row['account_product_id'].'">' . $accountPlan . '</small>
            '.$staffbadgetag.'
        </div>
        <div class="col-1 text-center">
            <small class="badge fw-semi-bold rounded-pill text-bg-' . (isset($bg_users_accountstatuscolors[$accountStatus]) ? $bg_users_accountstatuscolors[$accountStatus] : 'danger') . '">' . $accountStatus . '</small>
        </div>
        <div class="col-2 text-end">
            <small class="badge fw-semi-bold rounded-pill text-bg-primary">' . $daysOld . '</small>
            <p class="fs-11 mb-0">C: ' . $createDt . '</p>
            <p class="fs-11 mb-0">M: ' . $modifyDt . '</p>
              <p class="fs-11 mb-0">L: ' . $lastloginDt . '</p>
        </div>
        <div class="col-2 text-end">
            <div class="btn-group">
                <a class="btn btn-primary btn-sm" type="button" href="/admin/user-details?u=' . $qik->encodeId($row['user_id']) . '">
                User Details
                </a>';
    
    if ($account->isadmin()) {
        $content_rows .= '
        <button type="button" id="dropdown-for-user-' . md5($row['user_id']) . '" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"></button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-for-user-' . md5($row['user_id']) . '">';
        
        if ($row['status'] != 'pending') {  // impersonate 
            $content_rows .= '<li><a href="/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($row['user_id']) . '&aid=' . $qik->encodeId($current_user_data['user_id']) . '&_token=' . $display->inputcsrf_token('tokenonly') . '" class="dropdown-item" type="button">Impersonate</a></li>';
        }
        
        if ($row['status'] == 'pending' || $row['status'] == 'validated') { // validate account
            $content_rows .= '<li><a href="/validate-account?id=' . $row['user_id'] . '&adminsendagainrequest=1&aid=' . $current_user_data['user_id'] . '&act=resend&_token=' . $display->inputcsrf_token('tokenonly') . '" class="dropdown-item" type="button">Send Validation</a></li>';
        }
        
        $content_rows .= '<li><a class="dropdown-item" type="button">Message</a></li>';
        $content_rows .= '</ul>';
    }
    $content_rows .= '
            </div>
        </div>
    </div>';
}

echo '
<section class="mt-0 pt-0 main-content container">
<div class="card mb-3">
    <div class="card-body bg-info-subtle">
        <div class="row align-items-center">
            <div class="col-sm">
         Showing ' . $i . '-' . $displayedUsersCount . ' of ' . $totalCount . ' Users — ' . $newUsersToday . ' new users today
         </div>
            <div class="col-sm-auto">
                <form id="displayLengthForm" action="/admin/user-list" method="post" class="d-flex align-items-center">
                    ' . $display->inputcsrf_token() . '
                    <input type="hidden" name="formtype" value="changedisplaylength">
                    <small class="me-2">Limit:</small>
                    <select id="displayLengthSelect" class="form-select form-select-sm" aria-label="Limit Days" name="displaylength">
                        ' . $display->list_displaylength($p_displaylength) . '
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>


    <!-- Search bar -->
    <div class="input-group">
        <span class="input-group-text">
            <i class="bi bi-search"></i> <!-- Bootstrap search icon -->
        </span>
        <input type="text" id="searchBar" class="form-control" placeholder="Search...">
        <span class="input-group-text clear-icon" style="cursor: pointer; display: none;">
            <i class="bi bi-x-circle-fill"></i> <!-- Clear icon -->
        </span>
        <span class="input-group-text">
            <i class="bi bi-sort-alpha-down"></i> <!-- Bootstrap sort icon -->
        </span>
    </div>

    <div class="container py-3">
        ' . $content_rows . '
    </div>
</section>';