<?PHP
$dir['base'] = $BASEDIR = __DIR__ . "/../.." ?? $_SERVER['DOCUMENT_ROOT'];
require_once($BASEDIR . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = ''; // This removes the my-4 margin from the row after nav
$header_flush = true; // Ensure header content is flush with admin header

// Admin header styles
$additionalstyles = '
<style>
/* Custom styles that cannot be replaced with Bootstrap utilities */
.content-header-admin {
    margin-top: 0 !important;
}

.navbar + .row {
    height: 0 !important;
}

.navbar + .row + .content-header-admin {
    margin-top: 0 !important;
}

/* Modern Tab Navigation */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    gap: 0;
    overflow: visible;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.3s ease-out;
    background: none;
    border-radius: 0;
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.nav-tab-item::after {
    content: "";
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 3px;
    background-color: #6c757d;
    transition: width 0.3s ease-out;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
}

.nav-tab-item:hover::after {
    width: 100%;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom: 3px solid #0d6efd;
    background: none;
    position: relative;
    z-index: 1;
}

.nav-tab-item.active::after {
    display: none;
}

/* Badge styling */
.badge-count {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    border-radius: 0.375rem;
    margin-left: 0.25rem;
    background-color: #6c757d;
    color: #fff;
}
</style>
';

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
// Generate statistics cards
function generateStatsCards($stats)
{
    return '
    <div class="mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-3 py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-uppercase text-muted small fw-semibold">Pending Users</span>
                </div>
                <div class="display-6 fw-bold">' . $stats['count'] . '</div>
                <div class="text-muted small">Awaiting enrollment</div>
            </div>
        </div>
    </div>
    
    <div class="mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-3 py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase text-muted small fw-semibold">Statistics</span>
                    <span class="badge bg-secondary">' . $stats['total'] . ' Total</span>
                </div>
                
                <div class="vstack gap-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Pending</span>
                        <span class="fw-semibold">' . $stats['pending'] . '</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Success</span>
                        <span class="fw-semibold text-success">' . $stats['success'] . '</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Failed</span>
                        <span class="fw-semibold text-danger">' . $stats['failure'] . '</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Removed</span>
                        <span class="fw-semibold text-warning">' . $stats['removed'] . '</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="small text-muted">App Only</span>
                        <span class="fw-semibold text-info">' . $stats['app_only'] . '</span>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function getStatusClass($status)
{
    if (str_contains($status, 'success')) return 'text-success';
    if ($status === 'APP ONLY') return 'text-success'; // Green for app-only
    if (in_array($status, ['selected', 'pending'])) return 'text-primary fw-bold';
    if (str_contains($status, 'failed')) return 'text-danger';
    return 'text-black';
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
// Function to generate user list
function generateUserList($listofusers, $current_user_data)
{
    global  $account, $app, $qik, $display, $dir;
    $userlistoutput = '';
    $stats = [
        'total' => 0,
        'pending' => 0,
        'success' => 0,
        'count' => 0,
        'failure' => 0,
        'removed' => 0,
        'app_only' => 0,
    ];

    foreach ($listofusers as $user) {
        if ($user['selected_count'] > 0) {
            $stats['count']++;
            $stats['total'] += $user['company_count'];
            $stats['pending'] += $user['selected_count'];
            $stats['success'] += $user['success_count'];
            $stats['failure'] += $user['failure_count'];
            $stats['removed'] += $user['removed_count'];
            $stats['app_only'] += $user['selected_app_only_count'];

            $scheduleresult = $account->checkEnrollmentSchedule($user);
            $linkbase = '/admin/bgreb_v3/member-enroller?sid=' . session_id() . '&aid=' . $qik->encodeId($current_user_data['user_id']) . '&uid=';

            // Extract schedule variables
            $scheduleflag = $scheduleresult['schedule_flag'];
            $delaymessage = $scheduleresult['delay_message'];
            $delaycolor = $scheduleresult['delay_color'];
            $validenrollmenttime = $scheduleresult['valid_enrollment_time'];

            // Always use success styling for "No Schedule" cases
// Set button and container classes based on enrollment validity
$buttonClass = $scheduleresult['allow_enrollment'] ? 'btn-success' : 'btn-secondary';
$buttonContainerClass = $scheduleresult['allow_enrollment'] ? 
    'border border-2 border-success rounded p-2' : 
    'border border-2 border-secondary rounded p-2';

            // User-level stats
            $user_pending = $user['selected_count'];
            $user_success = $user['success_count'];
            $user_failure = $user['failure_count'];
            $user_removed = $user['removed_count'];
            $user_app_only = $user['selected_app_only_count'];

            $userlistoutput .= '<article class="row my-2 align-items-start">';
            $userlistoutput .= '<div class="col-md-6"><span class="fw-bold">
            ' . $user['first_name'] . ' ' . $user['last_name'] . '</span><br>
            <small>' . $user['email'] . '<br>uid=' . $user['user_id'] . '</small><br>
            <small> ' . $user['formatted_birthdate'].', ' . $user['formatted_birthyear'] . '</small>
            </div>';
           # $userlistoutput .= '<div class="col-md-2 text-center">' . $user['formatted_birthdate'] . '<br>' . $user['formatted_birthyear'] . '</div>';
           $userlistoutput .= '<div class="col-md-3">
           <span class="text-black fw-bold">TOTAL: ' . $user['company_count'] . '</span>
           <div class="d-md-block d-none  fs-10">
               <span class="text-primary">Pending: ' . $user_pending . '</span><br>
               <span class="text-success">Success: ' . $user_success . '</span><br>
               <span class="text-danger">Failure: ' . $user_failure . '</span><br>
               <span class="text-danger">Removed: ' . $user_removed . '</span><br>
               <span class="text-success">App Only: ' . $user_app_only . '</span>
           </div>
           <div class="d-block d-md-none fs-10">
               <small>
                   <span class="text-primary">Pending: ' . $user_pending . '</span>, 
                   <span class="text-success">Success: ' . $user_success . '</span>, 
                   <span class="text-danger">Failure: ' . $user_failure . '</span>, 
                   <span class="text-danger">Removed: ' . $user_removed . '</span>, 
                   <span class="text-success">App Only: ' . $user_app_only . '</span>
               </small>
           </div>
       </div>';

            $enrollingbutton='<div class="my-1"><a target="userregistration" href="' . $linkbase . $qik->encodeId($user['user_id']) . '" class="btn btn-sm ' . $buttonClass . '">Start Enrolling</a></div>';
            $impersonatebutton = '<div class="my-1"><a class="btn btn-sm ' . $buttonClass . '" href="/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($user['user_id']) . '&aid=' . $qik->encodeId($current_user_data['user_id']) . '&_token=' . $display->inputcsrf_token('tokenonly') . '">Impersonate</a></div>';
            $userdetailbutton = '<div class="my-1"><button class="btn btn-sm ' . $buttonClass . '" onclick="loadProfileDetails(' . $user['user_id'] . ')" data-bs-toggle="modal" data-bs-target="#profileModal">Profile Details</button></div>';
            $buttons = '
            <div class="' . $buttonContainerClass . ' ">
                ' . $enrollingbutton . '
                ' . $impersonatebutton . '
                ' . $userdetailbutton . '
            </div>';

            $userlistoutput .= '<div class="col-md-3  text-center">';
            if (!$validenrollmenttime && $scheduleflag != 'No Schedule.') {
                $userlistoutput .= '<div class="text-muted small mb-2">' . $delaymessage . '</div>';
            }
            $userlistoutput .= $buttons . '</div>';

            // Company list handling
            $company_list = explode(',', $user['company_list']);
            $company_span_output = '';
            $span_elements = [];
            $show_tooltips = count($company_list) < 35;

            foreach ($company_list as $company_info) {
                $parts = explode('|', $company_info);
                
                // Skip if we do not have both parts
                if (count($parts) < 2) {
                    continue;
                }
                
                list($company_id, $status) = $parts;
                $status_class = getStatusClass($status);
            
                if ($show_tooltips) {
                    $company_name = $app->getcompanyname($company_id);
                    $span_elements[] = sprintf(
                        '<span class="company-tooltip %s" data-bs-toggle="tooltip" title="%s - %s">%s</span>',
                        $status_class,
                        $company_name,
                        $status,
                        $company_id
                    );
                } else {
                    $span_elements[] = sprintf(
                        '<span class="%s">%s</span>',
                        $status_class,
                        $company_id
                    );
                }
            }

            $company_span_output = implode(', ', $span_elements);
            $userlistoutput .= '<div class="text-muted fs-11">' . $company_span_output . '</div>';
            $userlistoutput .= '</article><hr>';
        }
    }
    return ['output' => $userlistoutput, 'stats' => $stats];

}

// Fetch pending users from bg_user_companies
$baseQuery = "
SELECT 
u.user_id, 
u.first_name, 
u.last_name, 
u.email,
u.type,
DATE_FORMAT(u.birthdate, '%Y') AS formatted_birthyear, 
DATE_FORMAT(u.birthdate, '%M %e') AS formatted_birthdate, 
enrollmentstart_dt,
CASE
WHEN enrollmentstart_dt IS NOT NULL AND enrollmentstart_dt > NOW() THEN TIMESTAMPDIFF(HOUR, NOW(), enrollmentstart_dt)
WHEN enrollmentstart_dt IS NULL THEN NULL
ELSE 0
END AS hours_until_enrollment,
COUNT(uc.user_id) AS company_count,
SUM(CASE WHEN uc.status IN ('selected', 'pending') AND c.signup_url != '" . $website['apponlytag'] . "' THEN 1 ELSE 0 END) AS selected_count,
SUM(CASE WHEN uc.status LIKE 'success%' AND c.signup_url != '" . $website['apponlytag'] . "' THEN 1 ELSE 0 END) AS success_count,
SUM(CASE WHEN uc.status = 'selected' AND c.signup_url = '" . $website['apponlytag'] . "' THEN 1 ELSE 0 END) AS selected_app_only_count,
SUM(CASE WHEN uc.status = 'failed' THEN 1 ELSE 0 END) AS failure_count,
SUM(CASE WHEN uc.status = 'removed' THEN 1 ELSE 0 END) AS removed_count,
SUM(CASE WHEN c.signup_url = 'APP ONLY' THEN 1 ELSE 0 END) AS app_only_count,

GROUP_CONCAT(
    CONCAT(
        c.company_id, '|',
        CASE
            WHEN COALESCE(c.signup_url, '') = 'APP ONLY' THEN 'APP ONLY'
            ELSE uc.status
        END
    )
) AS company_list
FROM 
bg_user_companies uc
INNER JOIN 
bg_users u ON uc.user_id = u.user_id and u.status='active'
INNER JOIN
bg_companies c ON c.company_id = uc.company_id
WHERE 
c.`status` IN ('finalized') 
AND u.create_dt >= '2023-08-01'
AND uc.create_dt >= '2023-08-01'
AND NOT (uc.`status` LIKE '%failed%' AND lower(uc.`reason`) LIKE '%account%exists%')
AND u.type = ?
GROUP BY 
u.user_id
HAVING 
SUM(CASE WHEN uc.status IN ('selected', 'pending') THEN 1 ELSE 0 END) > 0
ORDER BY 
CASE
WHEN MONTH(u.birthdate) > MONTH(CURDATE()) OR 
(MONTH(u.birthdate) = MONTH(CURDATE()) AND DAY(u.birthdate) >= DAY(CURDATE())) 
THEN 0
ELSE 1
END,
MONTH(u.birthdate),
DAY(u.birthdate)";

// Get real users
$realResult = $database->prepare($baseQuery);
$realResult->execute(['real']);
$realUsers = $realResult->fetchAll();
#breakpoint($realUsers);
// Get test users
$testResult = $database->prepare($baseQuery);
$testResult->execute(['test']);
$testUsers = $testResult->fetchAll();




#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

?>

<!-- Hero Section -->
<div class="content-header-admin no-rounded-corners">
    <div class="container">
        <h1 class="mt-3">Pending Enrollments V2</h1>
        <p class="lead mb-4">Manage and process user birthday reward enrollments</p>
    </div>
</div>

<?php
// Generate the tabbed interface
echo '    <section class="container mt-3 min-vh-0">';

// Process data and get accurate counts
$realData = generateUserList($realUsers, $current_user_data);
$testData = generateUserList($testUsers, $current_user_data);

// Use the actual count of users with pending enrollments
$realCount = $realData['stats']['count'];
$testCount = $testData['stats']['count'];

echo '
<div class="container">
<div class="d-flex justify-content-between align-items-center mb-2">
<nav class="nav-tabs-modern flex-grow-1">
<button class="nav-tab-item active" id="real-tab" data-bs-toggle="tab" data-bs-target="#real" type="button">
Real Users
<span class="badge-count">'.$realCount.'</span>
</button>
<button class="nav-tab-item" id="test-tab" data-bs-toggle="tab" data-bs-target="#test" type="button">
Test Users
<span class="badge-count">'.$testCount.'</span>
</button>
</nav>
<div class="d-flex gap-2">
<a href="/admin/" class="btn btn-sm btn-outline-secondary">Back To Admin</a>
<a href="https://birthday.gold/admin/bgreb_v3/instructions" class="btn btn-sm btn-outline-secondary">Instructions</a>
'.$display->enrollerextensiondownload().'
</div>
</div>

<div class="tab-content" id="userTabsContent">
';

echo '
<div class="tab-pane fade show active" id="real" role="tabpanel">
<div class="row mt-3 align-items-start">
<div class="col-lg-3 d-none d-lg-block">' . generateStatsCards($realData['stats']) . '</div>
<div class="col-lg-9 col-12">
<div class="card border-start-lg border-start-success">
<div class="card-header border-bottom-0">
<div class="text-muted fw-bold">Real Users Pending Enrollment</div>
</div>
<div class="card-body">' . $realData['output'] . '</div>
</div>
</div>
</div>
</div>
';

echo '
<div class="tab-pane fade" id="test" role="tabpanel">
<div class="row mt-3 align-items-start">
<div class="col-lg-3 d-none d-lg-block">' . generateStatsCards($testData['stats']) . '</div>
<div class="col-lg-9 col-12">
<div class="card border-start-lg border-start-success">
<div class="card-header border-bottom-0">
<div class="text-muted fw-bold">Test Users Pending Enrollment</div>
</div>
<div class="card-body">' . $testData['output'] . '</div>
</div>
</div>
</div>
</div>
';

echo '
</div>
</div>
</section>';

echo '<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
<div class="modal-dialog modal-xl">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title" id="profileModalLabel">User Enrollment Profile Details</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body" id="profileModalBody">
<div class="text-center">
<div class="spinner-border" role="status">
<span class="visually-hidden">Loading...</span>
</div>
</div>
</div>
</div>
</div>
</div>';

// Add JavaScript for handling the modal and tabs
echo "<script>
// Handle modern tab switching
document.querySelectorAll('.nav-tab-item').forEach(button => {
    button.addEventListener('click', function() {
        // Remove active class from all tabs
        document.querySelectorAll('.nav-tab-item').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Add active class to clicked tab
        this.classList.add('active');
        
        // Hide all tab panes
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });
        
        // Show the target tab pane
        const target = this.getAttribute('data-bs-target');
        const targetPane = document.querySelector(target);
        if (targetPane) {
            targetPane.classList.add('show', 'active');
        }
    });
});
function loadProfileDetails(userId) {
const modalBody = document.getElementById('profileModalBody');
modalBody.innerHTML = '<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"visually-hidden\">Loading...</span></div></div>';

fetch('/admin/bgreb_v3/enrollment-list_profiledetails.php?userId=' + userId)
.then(response => response.text())
.then(data => {
modalBody.innerHTML = data;
})
.catch(error => {
modalBody.innerHTML = '<div class=\"alert alert-danger\">Error loading profile details</div>';
});
}

function copyToClipboard(text) {
navigator.clipboard.writeText(text).then(function() {
// Create a temporary tooltip
const tooltip = document.createElement('div');
tooltip.className = 'position-fixed bg-dark text-white px-2 py-1 rounded';
tooltip.style.zIndex = '9999';
tooltip.textContent = 'Copied!';
document.body.appendChild(tooltip);

// Position near cursor
document.addEventListener('mousemove', function handler(e) {
tooltip.style.left = (e.clientX + 10) + 'px';
tooltip.style.top = (e.clientY + 10) + 'px';
document.removeEventListener('mousemove', handler);
});

// Remove after delay
setTimeout(() => tooltip.remove(), 1000);
});
}
</script>";

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
