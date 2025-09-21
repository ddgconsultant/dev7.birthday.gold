<?php 
header('Location: /myaccount/mail-box'); exit;
$addClasses[] = 'Mail';
include ($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php'); 

$errormessage = '';
$continue = false;

// DISPLAY PAGE

// MAKE SURE WE HAVE A VALID MESSAGE
if (!empty($_GET['m'])) {
    $messageid = $qik->decodeID($_GET['m']);    
    if ($messageid) {
        $message = $mail->getMessage($messageid);
        $continue = !empty($message);
    }
} 

// NO VALID MESSAGE
if (!$continue) {
    header('location: /myaccount/mail_box');
    exit;
}

$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);
$item_company = [];

$message['create_dt_formatted'] = $display->formatdate($message['create_dt']);
if (!empty($message['company_id'])) {
    $item_company = $app->getcompany($message['company_id']);
    $item_company['company_logo'] = !empty($item_company['company_logo']) ? $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) : '/public/images/spacer.gif';
}

$additionalstyles.= '
<link rel="stylesheet" href="/public/css/login.css">
<style>
    .email-read-content {
        min-height: calc(100vh - 200px); /* Adjust the 200px based on your header/footer height */
        position: relative;
    }
    .email-read-content iframe {
        width: 100%;
        height: 100%;
        border: 0;
    }
</style>
<style>
    .footerxcontent {
        display: none;
    }
    .hidden {
        display: block !important;
    }
</style>
<link href="/public/mailassets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet" />
<link href="/public/mailassets/css/app.css" rel="stylesheet">
<link href="/public/mailassets/css/icons.css" rel="stylesheet">';

$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/nav-mailmenuside.inc');

/*
echo '<div class="container pt-5 px-4 mt-5 d-flex flex-column" style="min-height: 100vh;"><div class="row flex-grow-1">
    <div class="page-content d-flex flex-column w-100">';
*/
#include ($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-mailmenuside.php'); 
#include ($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-mailmenutop.php'); 


echo '<div class="container main-content mt-0 pt-0">';

echo '<div class="row  mt-0 pt-0 main-content">
            <h4>' . htmlspecialchars($message['subject']) . '</h4>
            <hr>
            <div class="d-flex align-items-center  main-content ">
                <img src="' . htmlspecialchars($item_company['company_logo']) . '" width="42" height="42" class="rounded-circle me-2" alt="" />
                <div class=" ">
                    <p class="mb-0 fw-bold">' . htmlspecialchars($item_company['company_display_name']) . '</p>
                </div>
                <p class="mb-0 chat-time ms-auto">' . htmlspecialchars($message['create_dt_formatted']) . '</p>
            </div>
            <div class="email-read-content flex-grow-1 ">                                
                <iframe srcdoc="' . htmlspecialchars($message['body']) . '" class=""></iframe>
            </div>
  
</div>';



echo '<div class="overlay email-toggle-btn-mobile"></div></div></div>
<div class="row my-5"></div>';

// JavaScript plugins
echo '<script src="/public/mailassets/js/jquery.min.js"></script>
<script src="/public/mailassets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script>
    new PerfectScrollbar(".email-navigation");
</script>
<script src="/public/mailassets/js/app.js"></script>';

echo '            </div>
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
