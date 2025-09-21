<?PHP
$dir['base']=$BASEDIR=__DIR__."/../.." ?? $_SERVER['DOCUMENT_ROOT'];
require_once ($BASEDIR.'/core/site-controller.php');

$bodycontentclass = '';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<?PHP
#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php');
include($dir['core_components'] . '/bg_user_profileheader.inc');

// Fetch pending users from bg_user_companies

$query="
SELECT 
  u.user_id, 
  u.first_name, 
  u.last_name, 
  u.email, 
  DATE_FORMAT(u.birthdate, '%Y') AS formatted_birthyear, 
  DATE_FORMAT(u.birthdate, '%M %e') AS formatted_birthdate, 
  enrollmentstart_dt,
  CASE
    WHEN enrollmentstart_dt IS NOT NULL AND enrollmentstart_dt > NOW() THEN TIMESTAMPDIFF(HOUR, NOW(), enrollmentstart_dt)
    WHEN enrollmentstart_dt IS NULL THEN NULL
    ELSE 0
  END AS hours_until_enrollment,
  COUNT(uc.user_id) AS company_count,
  SUM(CASE WHEN uc.status IN ('selected', 'pending') AND c.signup_url != '".$website['apponlytag']."' THEN 1 ELSE 0 END) AS selected_count,
  SUM(CASE WHEN uc.status LIKE 'success%' AND c.signup_url != '".$website['apponlytag']."' THEN 1 ELSE 0 END) AS success_count,
  SUM(CASE WHEN uc.status = 'selected' AND c.signup_url = '".$website['apponlytag']."' THEN 1 ELSE 0 END) AS selected_app_only_count,
  GROUP_CONCAT(c.company_id) AS company_list
FROM 
  bg_user_companies uc
INNER JOIN 
  bg_users u ON uc.user_id = u.user_id
INNER JOIN
  bg_companies c ON c.company_id = uc.company_id
WHERE 
  c.`status` IN ('finalized') 
  AND u.create_dt >= '2023-08-01'
  AND uc.create_dt >= '2023-08-01'
  AND NOT (uc.`status` LIKE '%failed%' AND lower(uc.`reason`) = '%account%exists%')
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
  DAY(u.birthdate);
";

## REMOVED:
/*
--  (((uc.status NOT LIKE 'success%' )   
--  OR (uc.status LIKE 'failed%' AND u.modify_dt > uc.modify_dt))
--  AND 
*/


$result = $database->query($query);
$users = $result->fetchAll();

if ($users) {
    $userlistoutput = '';
    $usercount = $enrollmentcount['total'] = $enrollmentcount['pending'] = $enrollmentcount['success'] = 0;

    #$linkbase='https://bgrab.birthday.gold/startregistration?sid='.session_id().'&aid='.$current_user_data['user_id'].'&uid=';
    $linkbase = $dir['bge'] . '/startregistration?sid=' . session_id() . '&aid=' . $current_user_data['user_id'] . '&uid=';
    $linkbase = 'https://dev.birthday.gold/admin/bgreb_v3/startregistration?sid=' . session_id() . '&aid=' . $current_user_data['user_id'] . '&uid=';

    foreach ($users as $user) {
        $currentBlock = null;
      
        if ($user['selected_count'] > 0) {
            $usercount++;
            $enrollmentcount['total'] = ($enrollmentcount['total'] + $user['company_count']);
            $enrollmentcount['pending'] = ($enrollmentcount['pending'] + $user['selected_count']);
            $enrollmentcount['success'] = ($enrollmentcount['success'] + $user['success_count']);

  

// Initialize or use existing $account class instance
$scheduleresult = $account->checkEnrollmentSchedule($user);

// Extract variables from the returned array
$scheduleflag = $scheduleresult['schedule_flag'];
$delaymessage = $scheduleresult['delay_message'];
$delaycolor = $scheduleresult['delay_color'];
$schedule_record_count = $scheduleresult['schedule_record_count'];
$hours_until_enrollment = $scheduleresult['hours_until_enrollment'];
$validenrollmenttime= $scheduleresult['valid_enrollment_time'];


            $userlistoutput .= '<article class="row my-2">';
            $userlistoutput .= '<div class="col-md-5"><span class="fw-bold">' . $user['first_name'] . ' ' . $user['last_name'] . '</span><br><small>' . $user['email'] . '<br>uid=' . $user['user_id'] . '</small></div>';
            $userlistoutput .= '<div class="col-md-2 text-center">' . $user['formatted_birthdate'] . '<br>' . $user['formatted_birthyear'] . '</div>';
            $userlistoutput .= '<div class="col-md-3">
            <span class="text-black fw-bold">TOTAL: ' . $user['company_count'] . '</span><br>
            <span class="text-primary">Pending: ' . $user['selected_count'] . '</span><br>
            <span class="text-success">Success: ' . $user['success_count'] . '</span></div>';

            $impersonatebutton=    '<div class="my-1"><a class="btn btn-sm btn-primary"  href="/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($user['user_id']) . '&aid=' . $qik->encodeId($current_user_data['user_id']) . '&_token=' . $display->inputcsrf_token('tokenonly') . '"  >            Impersonate</a></div>';
            if ( $validenrollmenttime) {
                $userlistoutput .= '<div class="col-md-2 text-center"><a target="userregistration" href="' . $linkbase . $user['user_id'] . '" class="btn btn-success">Start Enrolling</a>'.$impersonatebutton.'</div>';
            } else {
                $userlistoutput .= '<div class="col-md-2 '.$delaycolor.' text-center fw-bold" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $scheduleflag . '">'.$delaymessage.'
                <a target="userregistration" href="' . $linkbase . $user['user_id'] . '" class="btn btn-sm btn-primary">Start Enrolling</a>
                ' . $impersonatebutton . '</div>';
            }

            $company_list = explode(',', $user['company_list']);
            $company_span_output = '';
            
            if (count($company_list) < 25) {
                $span_elements = [];
                foreach ($company_list as $company_id) {
                    $company_name = $app->getcompanyname($company_id);
                    $span_elements[] = '<span class="company-tooltip" data-bs-toggle="tooltip" title="' . $company_name . '">' . $company_id . '</span>';
                }
                $company_span_output = implode(', ', $span_elements);
            } else {
                $company_span_output = $user['company_list']; // Just output the raw list if there are 15 or more
            }
            
            $userlistoutput .= '<div class="text-muted fs-11">' . $company_span_output . '</div>';
            
            

            $userlistoutput .= '</article><hr>';
        }
    }
} else {
    echo '<p>No pending users found.</p>';
}



echo '<section class="mt-5 main-content">
<div class="container">
    <div class="row">
        <div class="col-3">
            <div class="mb-4">
                <div class="card h-100 border-start-lg border-start-secondary">
                    <div class="card-header border-bottom-0">
                        <div class="text-muted fw-bold">Users Pending</div>
                    </div>
                    <div class="card-body">
                        <div class="h5">' . $usercount . '</div>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <div class="card h-100 border-start-lg border-start-success">
                    <div class="card-header border-bottom-0">
                        <div class="text-muted fw-bold">Enrollment Totals: ' . $enrollmentcount['total'] . '</div>
                    </div>
                    <div class="card-body h5">
                        <div>Pending: ' . $enrollmentcount['pending'] . '</div>
                        <div>Success: ' . $enrollmentcount['success'] . '</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-9">
            <div class="mb-4">
                <div class="card h-100 border-start-lg border-start-success">
                    <div class="card-header border-bottom-0">
                        <div class="text-muted fw-bold">List of Enrollments To Process</div>
                    </div>
                    <div class="card-body">
                        ' . $userlistoutput . '
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>';



$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
