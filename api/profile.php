<?PHP
$pagemode = 'core';
  include('api_coordinator.php');

// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];


if ( $method === 'GET') {
   // Get the JSON payload from the request body
   #$inputJSON = file_get_contents('php://input');
   $input = $_GET;

   // Validate the input
   if (isset($input['user_id'])) {
       $userid = $input['user_id'];

       // Authenticate the user
       $result = $account->getuserdata($userid, 'user_id');

    }
       if (!empty($result)) {
           // Successful 


           if (!empty($result)) {
            // Define the keys you want to keep
            $keys_to_keep = [
                "username", "email", "title", "first_name", "middle_name", 
                "last_name", "mailing_address", "city", "state", "zip_code", 
                "country", "phone_number", "birthdate", "create_dt", 
                "modify_dt", "status", "account_type", "account_plan", 
                "type", "gender", "avatar"
            ];
    
            // Create a new array with only the keys you want to keep
            $filtered_result = [];
            foreach ($keys_to_keep as $key) {
                $filtered_result[$key] = $result[$key] ?? null;
            }
    
            $filtered_result['avatar']='https:'.$filtered_result['avatar']  ;
           $payload = [
            'success' => true,
               'data' => $filtered_result,
           ];
           $api->responseHandler($payload);
       } else {
           // Unauthorized
           $api->responseError(401, ['message' => 'Invalid user_id']);
       }
   } else {
       // Bad input parameter
       $api->responseError(400, ['message' => 'User_id is required']);
   }
} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}


