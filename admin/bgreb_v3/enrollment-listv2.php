<?PHP
$dir['base'] = $BASEDIR = __DIR__ . "/../.." ?? $_SERVER['DOCUMENT_ROOT'];
require_once($BASEDIR . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
// Generate statistics cards
function generateStatsCards($stats)
{
    return '
    <div class="mb-4">
        <div class="card h-100 border-start-lg border-start-secondary">
            <div class="card-header border-bottom-0">
                <div class="text-muted fw-bold">Users Pending</div>
            </div>
            <div class="card-body">
                <div class="h5">' . $stats['count'] . '</div>
            </div>
        </div>
    </div>
    <div class="mb-4">
        <div class="card h-100 border-start-lg border-start-success">
            <div class="card-header border-bottom-0">
                <div class="text-muted fw-bold">Enrollment Totals: ' . $stats['total'] . '</div>
            </div>
            <div class="card-body h5">
                <div>Pending: ' . $stats['pending'] . '</div>
                <div>Success: ' . $stats['success'] . '</div>
                <div class="text-danger">Failure: ' . $stats['failure'] . '</div>
                <div class="text-danger">Removed: ' . $stats['removed'] . '</div>
                <div class="text-success">App Only: ' . $stats['app_only'] . '</div>
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

            $userlistoutput .= '<article class="row my-2">';
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
                
                // Skip if we don't have both parts
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
AND NOT (uc.`status` LIKE '%failed%' AND lower(uc.`reason`) = '%account%exists%')
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

include($dir['core_components'] . '/bg_user_profileheader.inc');

// Generate the tabbed interface
echo '    <section class="container mt-5 main-content">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2 class="mb-0">Pending Enrollments V2</h2>
<div class="text-end">
<a href="/admin/" class="btn btn-sm btn-outline-secondary mb-1">Back To Admin</a>
<br>
<div>
<a href="https://birthday.gold/admin/bgreb_v3/instructions" class="btn btn-sm btn-outline-secondary">Instructions</a>'.$display->enrollerextensiondownload().'
</div>
</div>
</div>
';

echo '
<div class="container">
<ul class="nav nav-tabs" id="userTabs" role="tablist">
<li class="nav-item" role="presentation">
<button class="nav-link active" id="real-tab" data-bs-toggle="tab" data-bs-target="#real" type="button">Real Users</button>
</li>
<li class="nav-item" role="presentation">
<button class="nav-link" id="test-tab" data-bs-toggle="tab" data-bs-target="#test" type="button">Test Users</button>
</li>
</ul>

<div class="tab-content" id="userTabsContent">
';

echo '
<div class="tab-pane fade show active" id="real" role="tabpanel">';
$realData = generateUserList($realUsers,  $current_user_data);
echo '<div class="row mt-3">
<div class="col-lg-3 d-none d-lg-block">' . generateStatsCards($realData['stats']) . '</div>
<div class="col-lg-9 col-12">
<div class="card h-100 border-start-lg border-start-success">
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
<div class="tab-pane fade" id="test" role="tabpanel">';
$testData = generateUserList($testUsers,  $current_user_data);
echo '<div class="row mt-3">
<div class="col-lg-3 d-none d-lg-block">' . generateStatsCards($testData['stats']) . '</div>
<div class="col-lg-9 col-12">
<div class="card h-100 border-start-lg border-start-success">
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

// Add JavaScript for handling the modal
echo "<script>
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
