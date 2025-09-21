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

        $start_datetime = $_REQUEST['start_datetime'] ?? null;
        if ($start_datetime) {
            // Convert datetime-local input value to a format suitable for your database
            $start_datetime = date('Y-m-d H:i:s', strtotime($start_datetime));
        } else {
            $start_datetime = 'NULL'; // Use a default value or handle as required
        }


$columns=' `user_id`, `type`, `name`, `description`, `status`, `create_dt`, `modify_dt`, `rank`, `value`, `grouping`, `category`, `start_dt`, `end_dt`';

$sqlStatements = "
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  NULL, 'hourly_pay_rate', '$hourly_rate', 'A', now(), now(), NULL, NULL, NULL, 'staff', NULL, NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'max_hours_day', '$max_hours_day', 'A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT `bg_user_attributes` (".$columns.") VALUES (".$workinguserdata['user_id'].",  'timeclock', 'max_hours_week', '$max_hours_week', 'A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
";



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