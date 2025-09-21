<?php
$pagemode = 'core';
include('api_coordinator.php');

$method='POST';
#$method='GET';
$response = array("success" => false, "message" => "");

if ($_SERVER['REQUEST_METHOD'] == $method) {

    $apiKey = (isset($_REQUEST['api_key']) ? $_REQUEST['api_key'] : '');
    if ($apiKey == '') {
        $apiKey = (isset($_REQUEST['apikey']) ? $_REQUEST['apikey'] : '');
    }
    $ownerflag = (isset($_REQUEST['get_owner']) ? $_REQUEST['get_owner'] : '');

    // Check if the API key is active
    $sql = "SELECT api_key, ifnull(user_id, '') user_id FROM bg_api_keys WHERE api_key = :api_key AND status = 'active' ORDER BY create_dt DESC LIMIT 1";
    $stmt = $database->prepare($sql);
    $stmt->execute([':api_key' => $apiKey]);
    $row = $stmt->fetch();

    if ($row) {
        // Generate a unique 32-character authentication token
        $auth_token = bin2hex(random_bytes(16));
        
        // Store the authentication token in the `bg_api_sessions` table with an expiration date 1 hour in the future
        $sql = "INSERT INTO bg_api_sessions (api_key, session_id, create_dt, modify_dt, expire_dt) VALUES (:api_key, :session_id, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))";
        $stmt = $database->prepare($sql);
        $params = [':api_key' => $apiKey, ':session_id' => $auth_token];
        $stmt->execute($params);

        $response["success"] = true;
        $response["auth_token"] = $auth_token;
        if (!empty($ownerflag)) $response["owner_id"] = $row['user_id'];
        
    } else {
        $response["message"] = "Invalid or inactive API key.";
    }
}

http_response_code(200);
#header('Content-Type: application/json');
echo json_encode($response);