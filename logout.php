<?PHP include_once ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

$transferpagedata=$qik->startpostpage();

// Clear rememberme cookies before destroying session
$account->clearRememberMeCookies();

// Destroy the session
$session->destroy();

if (isset($_REQUEST['_relogin'])) {
    session_start();
    $transferpage['url']='/login';
    $transferpage['message']=$transferpagedata['message'];
    $qik->endpostpage($transferpage);
}
else {
    header('location: /');
}