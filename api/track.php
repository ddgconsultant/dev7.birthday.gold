<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 



#-------------------------------------------------------------------------------
# HANDLE THE REGISTRATION ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted('REQUEST')){
    $type=array();
    if(isset($_REQUEST['request']) && $_REQUEST['request']!='') $type[]='request_received';
    if(isset($_REQUEST['reponse']) && $_REQUEST['reponse']!='') $type[]='reponse_received';
    session_tracking('apitracking', $type);
    http_response_code(200);
} else {
    http_response_code(400);
}