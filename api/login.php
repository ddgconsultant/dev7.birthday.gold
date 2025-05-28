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
   if (isset($input['username']) && isset($input['password'])) {
       $username = $input['username'];
       $password = $input['password'];

       // Authenticate the user
       $result=$account->login($username, $password, 'both_api');

       if (!empty($result)) {
           // Successful login
           $current_user_data=$session->get('current_user_data');
           $payload = [
            'success' => true,            
            'message0' => "Please select date to see your tour details",
            'user_id' => $current_user_data['user_id'],
           ];
           $api->responseHandler($payload);
       } else {
           // Unauthorized
           $api->responseError(401, ['message' => 'Invalid username or password']);
       }
   } else {
       // Bad input parameter
       $api->responseError(400, ['message' => 'Username and password are required']);
   }
} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}
