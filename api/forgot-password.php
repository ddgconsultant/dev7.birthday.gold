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
   if (isset($input['email'])) {
    $email = $input['email'];

    $sendcount=0;
    $response = $account->getuserdata($email, 'email');
    if (!empty($response['user_id'])) {
      $fullname=$response['first_name'];
      $message['toemail'] = $email;
      $message['fullname'] = $fullname;
      # $link= $appclass->getshortcode( $website['fullurl'].'/validate-account?t='.sha1($email) );
      #$message['validatelink']=$link['shorturl'];
      $validatedata['rawdata'] = $email;
      $validatedata['user_id'] = $response['user_id'];
      $validatedata['sendcount'] = $sendcount;
      $validatedata['type'] = 'forgotpassword';
      $validationcodes = $app->getvalidationcodes($validatedata);
      $link = $website['fullurl'] . '/changepassword?t=' . $validationcodes['long'];
      $message['validatelink'] = $link;
      $message['validationcode'] = $local_validationcode = $validationcodes['mini'];
      #print_r($message);
  
  
      $mail->sendPasswordResetEmail($message);
#breakpoint($message);
           // Successful login
           $payload = [
            'success' => true,
            'message' => 'Password Reset Email has been sent.',
           ];
           $api->responseHandler($payload);
       } else {
           // Unauthorized
           $api->responseError(401, ['message' => 'Invalid email ']);
       }
   } else {
       // Bad input parameter
       $api->responseError(400, ['message' => 'email']);
   }
} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}
