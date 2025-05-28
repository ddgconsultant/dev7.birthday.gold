<?PHP
header('location: /admin/bgreb_v3/enrollment-list'); exit;
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass = '';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<?PHP
#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php');
include($dir['core_components'] . '/bg_user_profileheader.inc');

// Fetch pending users from bg_user_companies
$query = "
SELECT 
u.user_id, 
u.first_name, 
u.last_name, 
u.email, 
DATE_FORMAT(u.birthdate, '%Y') as formatted_birthyear, 
DATE_FORMAT(u.birthdate, '%M %e') as formatted_birthdate, 
enrollmentstart_dt,
CASE
WHEN enrollmentstart_dt IS NOT NULL AND enrollmentstart_dt > NOW() THEN TIMESTAMPDIFF(HOUR,NOW(),enrollmentstart_dt)
WHEN enrollmentstart_dt IS NULL THEN NULL
ELSE 0
END AS hours_until_enrollment,
COUNT(uc.user_id) AS company_count,
SUM(CASE WHEN uc.status = 'selected' THEN 1 ELSE 0 END) AS selected_count,
SUM(CASE WHEN uc.status like 'success%' THEN 1 ELSE 0 END) AS success_count,
group_concat(c.company_id) AS company_list
FROM 
bg_user_companies uc
INNER JOIN 
bg_users u ON uc.user_id = u.user_id
INNER JOIN
bg_companies c ON c.company_id = uc.company_id
WHERE 
((c.`status` IN ('finalized') and c.signup_url!='".$website['apponlytag']."')
AND u.create_dt >= '2023-08-01'
AND uc.create_dt >= '2023-08-01') 
AND NOT (uc.`status` LIKE '%failed%' AND uc.`reason` = 'account_exists')
GROUP BY 
uc.user_id
HAVING 
SUM(CASE WHEN uc.status = 'selected' THEN 1 ELSE 0 END) > 0
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

/*
$query = "WITH RankedCompanies AS (
  SELECT 
    uc.user_company_id, 
    uc.user_id, 
    uc.company_id, 
    uc.reason, 
    uc.status, 
    uc.`status` AS enrollment_status, 
    uc.registration_dt, 
    uc.create_dt, 
    uc.modify_dt, 
    c.company_name, 
    c.appgoogle, 
    c.appapple, 
    ca.description AS company_logo, 
    MAX(IFNULL(ad.id, '')) AS amid, 
    ROW_NUMBER() OVER (PARTITION BY uc.user_id, uc.company_id ORDER BY uc.modify_dt DESC) AS rn
  FROM 
    bg_user_companies AS uc
  LEFT JOIN 
    am_datastore ad ON uc.user_id = ad.user_id AND uc.company_id = ad.company_id
  JOIN 
    bg_companies AS c ON uc.company_id = c.company_id
  LEFT JOIN 
    bg_company_attributes ca ON c.company_id = ca.company_id AND ca.category = 'company_logos' AND ca.grouping = 'primary_logo'
  WHERE 1=1
   -- uc.create_dt >= '2023-08-01'
    AND (c.status IN ('finalized') AND c.signup_url != '".$website['apponlytag']."')
    AND NOT (uc.status LIKE '%failed%' AND uc.reason = 'account_exists')
  GROUP BY 
    uc.user_company_id, uc.user_id, uc.company_id, uc.modify_dt, c.company_name, c.appgoogle, c.appapple, ca.description
),
FilteredCompanies AS (
  SELECT 
    user_company_id, 
    user_id, 
    company_id, 
    reason, 
    status, 
    enrollment_status, 
    create_dt, 
    modify_dt, 
    registration_dt, 
    company_name, 
    appgoogle, 
    appapple, 
    company_logo, 
    amid
  FROM 
    RankedCompanies
  WHERE 
    rn = 1
)
SELECT 
  u.user_id, 
  u.first_name, 
  u.last_name, 
  u.email, 
  DATE_FORMAT(u.birthdate, '%Y') AS formatted_birthyear, 
  DATE_FORMAT(u.birthdate, '%M %e') AS formatted_birthdate, 
  u.enrollmentstart_dt,
  CASE
    WHEN u.enrollmentstart_dt IS NOT NULL AND u.enrollmentstart_dt > NOW() THEN TIMESTAMPDIFF(HOUR, NOW(), u.enrollmentstart_dt)
    WHEN u.enrollmentstart_dt IS NULL THEN NULL
    ELSE 0
  END AS hours_until_enrollment,
  COUNT(fc.company_id) AS company_count,
  SUM(CASE WHEN fc.status = 'selected' THEN 1 ELSE 0 END) AS selected_count,
  SUM(CASE WHEN fc.status LIKE 'success%' THEN 1 ELSE 0 END) AS success_count,
  GROUP_CONCAT(fc.company_id) AS company_list
FROM 
  bg_users u
INNER JOIN 
  FilteredCompanies fc ON fc.user_id = u.user_id
-- WHERE   u.create_dt >= '2023-08-01'
WHERE u.user_id=20
GROUP BY 
  u.user_id
HAVING 
  SUM(CASE WHEN fc.status = 'selected' THEN 1 ELSE 0 END) > 0
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
*/

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
  AND NOT (uc.`status` LIKE '%failed%' AND uc.`reason` = 'account_exists')
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

    foreach ($users as $user) {
        if ($user['selected_count']>0) {
        $usercount++;
        $enrollmentcount['total'] = ($enrollmentcount['total'] + $user['company_count']);
        $enrollmentcount['pending'] = ($enrollmentcount['pending'] + $user['selected_count']);
        $enrollmentcount['success'] = ($enrollmentcount['success'] + $user['success_count']);

        $userlistoutput .= "<div class='row my-2'>";
        $userlistoutput .= "<div class='col-md-5'><span class=' fw-bold'>" . $user['first_name'] . " " . $user['last_name'] . '</span><br><small>' . $user['email'] . '<br>uid=' . $user['user_id'] . "</small></div>";
        $userlistoutput .= "<div class='col-md-2 text-center '>" . $user['formatted_birthdate'] . '<br>' . $user['formatted_birthyear'] . "</div>";
        $userlistoutput .= "<div class='col-md-3'>TOTAL: " . $user['company_count'] . "<BR>Pending: " . $user['selected_count'] . "<br>Success: " .  $user['success_count'] . "</div>";

        if ($user['hours_until_enrollment'] == 0) {
            $userlistoutput .= "<div class='col-md-2'><a target='_userregistration' href='" . $linkbase . '' . $user['user_id'] . "' class='btn btn-success'>Start Enrolling</a></div>";
        } else {
            $userlistoutput .= "<div class='col-md-2 bg-warning'>Delayed " . $user['hours_until_enrollment'] . " hrs.</div>";
        }

        $userlistoutput .= "<div class='text-muted'>" . $user['company_list'] . "</div>";


        $userlistoutput .= "</div><hr>";
    }
}
} else {
    echo "<p>No pending users found.</p>";
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



include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
