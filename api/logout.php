<?PHP
$pagemode = 'core';
  include('api_coordinator.php');

// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];


if (
    $method === 'POST') {
   // Get the JSON payload from the request body
   $inputJSON = file_get_contents('php://input');
   $input = json_decode($inputJSON, TRUE);

   // Validate the input
   if (isset($input['user_id'])) {
       $userid = $input['user_id'];

       // Authenticate the user
       $result=$account->logout($userid);
      # $session->destroy();
       if (!empty($result)) {
           // Successful login
           $payload = [
            'success' => true,
           ];
           $api->responseHandler($payload);
       } else {
           // Unauthorized
           $api->responseError(401, ['message' => 'Invalid username ']);
       }
   } else {
       // Bad input parameter
       $api->responseError(400, ['message' => 'Userid']);
   }
} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}
