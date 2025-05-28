<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

if ($current_user_data['username']=='ddgconsultant') {
    include ($dir['configs'] . '/payout/payout.php');
} else{
    header('Location: /');
    exit();
}