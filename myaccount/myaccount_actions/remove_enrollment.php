<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



if ($app->formposted()) {
    $ucid=$qik->decodeId($_REQUEST['ucid'] ??'');

    if (!empty($ucid)) {

        $sql = "UPDATE bg_user_companies SET `status`='removed', modify_dt=now() WHERE user_company_id = :ucid limit 1";
        $stmt = $database->prepare($sql);
        $result = $stmt->execute([':ucid' => $ucid]);

        
        if ($result) {
            $errormessage = '<div class="alert alert-success">Enrollment removed successfully.</div>';  
            $transferpage['url'] = '/myaccount/enrollment-history';
            $transferpage['message'] = $errormessage;
            $system->endpostpage($transferpage);
            exit;
        } else {

            $errormessage = '<div class="alert alert-danger">Hmmm... failed to remove enrollment.</div>';
            $transferpage['url'] = '/myaccount/enrollment-history';
            $transferpage['message'] = $errormessage;
            $system->endpostpage($transferpage);
            exit;
        }

    }

}
