<?PHP

$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$skip = false;
if (!$account->isadmin()) {
    $skip = true;
}


#breakpoint($_REQUEST);
if ($app->formposted()) {

    if (empty($_REQUEST['uid'])) {
        $skip = true;
    }
}


if (!$skip) {

    $p_uid = $_REQUEST['uid'] ?? null; // Default to null if 'uid' is not set

    # breakpoint($_REQUEST);
    if ($p_uid !== null) {
        $workinguserdata = $account->getuserdata($p_uid, 'user_id');
        
        $p_status = $_REQUEST['accountstatus'] ?? $workinguserdata['status']; // Default to 'N' if 'role' is not set      
        $p_type = $_REQUEST['accounttype'] ?? $workinguserdata['account_type']; // Default to 'N' if 'role' is not set
        $p_plan = $_REQUEST['accountplan'] ?? $workinguserdata['account_plan']; // Default to 'N' if 'role' is not set

        $send_mail = $_REQUEST['send_mail'] ?? ''; // Default to 'N' if 'role' is not set
        $previoustype = $_REQUEST['previoustype'] ?? ''; // added to assist in reducing duplicate changes
        $previousplan = $_REQUEST['previousplan'] ?? ''; // added to assist in reducing duplicate changes

        // Use a prepared statement with bound parameters to avoid SQL injection
      $params = [
        ':status' => $p_status,
        ':account_type' => $p_type,
        ':account_plan' => $p_plan,
        ':user_id' => $p_uid
    ];
    
    $sql = "UPDATE bg_users SET `status` = :status, account_type = :account_type, account_plan = :account_plan, modify_dt = NOW() WHERE user_id = :user_id LIMIT 1";
    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    

        // $bg_users_accountstatus from site-arrays.inc
        // $bg_users_accounttypes from site-arrays.inc
        // $bg_users_accountplans from site-arrays.inc


        if (!empty($send_mail)) {
            $input['templatename'] = 'admin_changetypeplan';    // view in /core/'.$website['ui_version'].'/email/email-$input['templatename'].inc
            $input['source'] = 'set_accounttypeplan.php';  // typically page that can generate the email
            $input['type'] = 'all';
            $input['name'] = $workinguserdata['first_name'];
            $input['to'] = $workinguserdata['email'];
            // message body related variables  -----------------------
            $input['previousstatustag'] = $bg_users_accounttypes[$workinguserdata['status']];
            $input['previoustypetag'] = $bg_users_accounttypes[$workinguserdata['account_type']];
            $input['previousplantag'] = $bg_users_accountplans[$workinguserdata['account_plan']];
            $input['newstatustag'] = $bg_users_accountstatus[$p_status];
            $input['newtypetag'] = $bg_users_accounttypes[$p_type];
            $input['newplantag'] = $bg_users_accountplans[$p_plan];
            $mail->sendtemplate($input);
        }
    }
}


$referrer = $_SERVER['HTTP_REFERER'] ?? '/myaccount/';
header('Location: ' . $referrer);
