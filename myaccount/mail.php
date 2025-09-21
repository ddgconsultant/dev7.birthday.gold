<?PHP

 include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 
 header('location: /myaccount/mail_box');
 exit;


 if ($app->testfeature()) {
    header('location: /myaccount/mail_box');
exit;
    }
    