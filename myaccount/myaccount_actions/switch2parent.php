<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$response = false;

if ($app->formposted('GET')) {

    if (!empty($current_user_data['user_id']) && !empty($_GET['pid'])) {
        if (!empty($_GET['id'])  && $current_user_data['user_id'] == $_GET['id']) {
$parent_id=$_GET['pid'];
            $minor_id = $_GET['id'];
          #  breakpoint($parent_id);
     
            #$minor_id=$qik->decodeId($minor_id); echo $minor_id;exit;
          #  $minordata = $account->getuserdata($minor_id, 'user_id');
         # $current_user_data['user_id']
            if ($current_user_data['feature_parent_id'] == $parent_id) {
                $account->logout();
                session_start();
                $response = $account->login($parent_id, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
            }
            #echo $response. ' '. $minor_id .'/ '. $sitesettings['app']['APP_AUTOLOGIN'];
            #$userdata=$session->get('current_user_data');
            #print_r($userdata);
            #exit;

            if ($response) {
                #echo 'hello'; exit;
                $switchdata = $account->getuserdata($parent_id, 'user_id');
                $session->set('current_user_data', $switchdata);
                $session->set('parental_login_source', $parent_id);
                # breakpoint($account->isactive());
                $errormessage = '<div class="alert alert-success">Switched Accounts successfully.</div>';
                $transferpage['url'] = '/myaccount';
                $transferpage['message'] = $errormessage;
                $system->endpostpage($transferpage);
            } else {

                # breakpoint($_GET);
                $errormessage = '<div class="alert alert-danger">Hmmm... failed to switch accounts.</div>';
                $transferpage['url'] = '/login';
                $transferpage['message'] = $errormessage;
                $system->endpostpage($transferpage);
            }
        }
    }
}


$errormessage = '<div class="alert alert-danger">Hmmm... unable to switch accounts.</div>';
$transferpage['url'] = '/myaccount/account';
$transferpage['message'] = $errormessage;
$system->endpostpage($transferpage);
