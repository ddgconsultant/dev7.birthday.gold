<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 


if ($app->formposted('GET')) {

if (!empty($current_user_data['user_id']) && !empty($_GET['pid'])) {

    if(!empty($_GET['id'])  && $current_user_data['user_id']==$_GET['pid']) {

$minor_id=$_GET['id'];
#breakpoint($qik->decodeId($_GET['id']));
#$minor_id=$qik->decodeId($minor_id); echo $minor_id;exit;
$account->logout();
session_start();
$response=$account->login($minor_id, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');

#echo $response. ' '. $minor_id .'/ '. $sitesettings['app']['APP_AUTOLOGIN'];
#$userdata=$session->get('current_user_data');
#print_r($userdata);
#exit;

if ($response) {
    #echo 'hello'; exit;
    $minordata = $account->getuserdata($minor_id, 'user_id');
    $session->set('current_user_data', $minordata);
   # breakpoint($account->isactive());
    $errormessage = '<div class="alert alert-success">Switched Accounts successfully.</div>';
    $transferpage['url']='/myaccount';
    $transferpage['message']=$errormessage;
    $system->endpostpage($transferpage);

} else  {

   # breakpoint($_GET);
    $errormessage = '<div class="alert alert-danger">Hmmm... failed to switch accounts.</div>';
    $transferpage['url']='/login';
    $transferpage['message']=$errormessage;
    $system->endpostpage($transferpage);



}
    }


}

}


$errormessage = '<div class="alert alert-danger">Hmmm... unable to switch accounts.</div>';
$transferpage['url']='/myaccount/account';
$transferpage['message']=$errormessage;
$system->endpostpage($transferpage);