<?PHP
$pagemode = 'core';
$addClasses[] = 'Mail';
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
   if (isset($input['user_id']) && isset($input['old_password']) && isset($input['new_password']) && isset($input['confirm_password'])) {
    $user_id = $input['user_id'];

    $current_user_data=$account->getuserdata($user_id, 'user_id');
            if (empty($current_user_data['user_id'])) {       
            // Bad input parameter
            $api->responseError(400, ['message' => 'invalid user_id']);
            }
            if (!password_verify($input['old_password'], $current_user_data['password'])  ) {
            // Bad input parameter
            $api->responseError(401, ['message' => 'You provided an invalid old_password']);
            }

            if (($input['new_password']!== $input['confirm_password'])  ) {
            // Bad input parameter
            $api->responseError(401, ['message' => 'Your New password and Confirm password does not match']);
            }

    
## we made it this far successfully... update the user password
$password=$input['new_password'];
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$output=print_r($user_id, 1).'|'.print_r($hashed_password, 1).'|'.print_r($password, 1).'|'.print_r($current_user_data, 1);
$response=$account->updateSettings($user_id, ['password'=>$hashed_password]);
$current_user_data=$account->getuserdata($user_id, 'user_id');
$output.="\n-----------------------------------\nPOSTUPDATE\n".print_r($response, 1).'|'.print_r($current_user_data, 1);
session_tracking('CHANGEPASSWORD', $output);


           // Successful login
           $payload = [
            'success' => true,
            'message' => 'Your password was successfully changed.',
           ];
           $api->responseHandler($payload);
       } else {
           // Unauthorized
           $api->responseError(400, ['message' => 'Invalid parameters']);
       }
} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}
