<?php
$addClasses[] = 'AccessManager';
$addClasses[] = 'api';
$apibypass=true;
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


// Example usage
$id = $_REQUEST['id']; // Retrieve the ID from the query parameter

$id=$qik->decodeId($id);
$api_key = $_REQUEST['apikey']; // Retrieve the ID from the query parameter

$auth_response = $api->authenticate_api_key($api_key);
if ($auth_response['success']) {   
$decryptedValue = $accessmanager->getDecryptedValue($id);
}

if (!empty($decryptedValue)) {
 $user_id=$auth_response['success']??'-1';
 $accessmanager->logAccess($user_id, $id, 'api_get');
    #echo "Decrypted Value: " . $decryptedValue;
    echo $decryptedValue;
} else {
  echo 'ERROR';
    if ($account->isadmin()) echo ": No record found for ID: " . $id;
}


