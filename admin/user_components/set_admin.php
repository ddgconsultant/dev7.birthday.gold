<?PHP

$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$skip = false;
if (!$account->isadmin()) {
    $skip = true;
}



if ($app->formposted()) {

    if (!isset($_REQUEST['uid'])) {
        $skip = true;
    }
}


if (!$skip) {

    $p_uid = $_REQUEST['uid'] ?? null; // Default to null if 'uid' is not set

    # breakpoint($_REQUEST);
    if ($p_uid !== null) {
        $workinguserdata = $account->getuserdata($p_uid, 'user_id');

        $p_role = $_REQUEST['role'] ?? 'N'; // Default to 'N' if 'role' is not set
        $send_mail = $_REQUEST['send_mail'] ?? ''; // Default to 'N' if 'role' is not set
        $previousrole = $_REQUEST['previousrole'] ?? ''; // added to assist in reducing duplicate changes

        // Use a prepared statement with bound parameters to avoid SQL injection
        $params = [
            ':account_admin' => $p_role,
            ':user_id' => $workinguserdata['user_id'],
            ':previous_account_admin' => $previousrole
        ];
        
        $sql = "UPDATE bg_users SET account_admin = :account_admin, modify_dt = NOW() WHERE user_id = :user_id AND account_admin = :previous_account_admin LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->execute($params);
        
        
        if (!empty($send_mail)) {
            $input['templatename'] = 'admin_account_admin';    // view in /core/'.$website['ui_version'].'/email/email-$input['templatename'].inc
            $input['source'] = 'set_admin.php';  // typically page that can generate the email
            $input['type'] = 'all';
            $input['name'] = $workinguserdata['first_name'];
            $input['to'] = $workinguserdata['email'];
            // message body related variables  -----------------------
            $input['previousrole'] = $previousrole;
            $input['newrole'] = $p_role;
            $mail->sendtemplate($input);
        }
    }
}


$referrer = $_SERVER['HTTP_REFERER'] ?? '/myaccount/';
header('Location: ' . $referrer);
