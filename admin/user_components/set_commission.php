<?PHP

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$skip=false;
if (!$account->isadmin()) {$skip=true;}



#breakpoint($_REQUEST);

if ($app->formposted()) {
if (!isset($_REQUEST['uid'])) {$skip=true;}
  }
  

if (!$skip) {

    $p_uid = $_REQUEST['uid'] ?? null; // Default to null if 'uid' is not set

    if ($p_uid !== null) {
        $workinguserdata = $account->getuserdata($p_uid, 'user_id');

        // Extracting form values
        $p_role = $_REQUEST['role'] ?? 'N'; // Default to 'N' if 'role' is not set
        $hourly_rate = $_REQUEST['hourly_rate'] ?? '0';
        $gift_certificate_life = $_REQUEST['gift_certificate_life'] ?? '0';
        $parental_life = $_REQUEST['parental_life'] ?? '0';
        $user_gold = $_REQUEST['user_gold'] ?? '0';
        $user_life = $_REQUEST['user_life'] ?? '0';
        $max_hours_day = $_REQUEST['max_hours_day'] ?? '0';
        $max_hours_week = $_REQUEST['max_hours_week'] ?? '0';

        #$start_datetime = $_REQUEST['start_datetime'] ?? null;

        $start_datetime=$app->getformdate();


        if ($start_datetime) {
            // Convert datetime-local input value to a format suitable for your database
            $start_datetime = date('Y-m-d H:i:s', strtotime($start_datetime));
        } else {
            $start_datetime = 'NULL'; // Use a default value or handle as required
        }


$columns=' `user_id`, `type`, `name`, `description`, `status`, `create_dt`, `modify_dt`, `rank`, `value`, `grouping`, `category`, `start_dt`, `end_dt`';

$sqlStatements .= "
UPDATE `bg_user_attributes` set `status`='previous', `end_dt`=now(),  modify_dt=now() where `status`='A' and user_id=".$workinguserdata['user_id']." and  name in('commissioned_consultant', 'hourly_pay_rate') or type in ('referral_payout','timeclock');
";

if (!empty($_REQUEST['type']) && $_REQUEST['type'] =='consultant' ) {
$sqlStatements .= "
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'staff', 'commissioned_consultant', '".$p_role."', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'staff', 'hourly_pay_rate', '$hourly_rate', 'A', now(), now(), NULL, NULL, NULL, 'staff', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'giftcertificate|life', '$gift_certificate_life', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'parental|life', '$parental_life', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'user|gold', '$user_gold', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'user|life', '$user_life', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'growth_to_hours_minimumhours', '4', 'A',  now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'growth_to_hours_ratio', '1', 'A',  now(), now(),, NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'max_hours_day', '$max_hours_day', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'max_hours_week', '$max_hours_week', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
";

} else {
$sqlStatements .= "
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'staff', 'commissioned_staff', '".$p_role."', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'staff', 'hourly_pay_rate', '$hourly_rate', 'A', now(), now(), NULL, NULL, NULL, 'staff', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'giftcertificate|life', '$gift_certificate_life', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'parental|life', '$parental_life', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'user|gold', '$user_gold', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'user|life', '$user_life', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
";
if (!empty($_REQUEST['role']) && $_REQUEST['role'] =='senior' ) {
$sqlStatements .= "
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'corporate|life', '$parental_life', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'referral_payout', 'business|life', '$user_gold', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
";
}

$sqlStatements .= "
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'growth_to_hours_minimumhours', '4', 'A',  now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'growth_to_hours_ratio', '1', 'A',  now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'max_hours_day', '$max_hours_day', 'A', now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'max_hours_week', '$max_hours_week', 'A',  now(), now(), NULL, NULL, NULL, 'bdgold_employee', '".$start_datetime."', NULL);
";
}


foreach (explode(';', $sqlStatements) as $sql) {
    if (trim($sql)) { // Check if $sql is not just whitespace
        $stmt = $database->prepare($sql);
        $stmt->execute();
    }
}
}
}


$referrer = $_SERVER['HTTP_REFERER'] ?? '/myaccount/';
header('Location: ' . $referrer);