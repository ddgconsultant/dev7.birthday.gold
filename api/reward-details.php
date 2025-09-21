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
   if (isset($input['company_id'])) {
       $ctid = $input['tour_dt'];
       $id = $input['company_id'];



       
  $stmt = $database->prepare("select * from bg_company_rewards  WHERE company_id=:company_id and category='birthday' limit 1");  
  $stmt->execute([':company_id'=>$id ]);


  $rewards= $stmt->fetch(PDO::FETCH_ASSOC);
  if (!empty($rewards)) {
    
  $payload = [
      'success' => true,
      'reward_id'=> $rewards['reward_id'],
      'reward_details' => $rewards,
  ];

/* 
       // Authenticate the user
       $result = $app->getcompany($id);
     #  $result=$resultraw[0];
    }
       if (!empty($result)) {
           // Successful 


           if (!empty($result)) {
                */
        // Define the keys you want to keep
        $keys_to_keep = [
            "reward_description_long", 
            "reward_description_short", 
            "reward_description_spinner", 
            "requirements",
            "redeem_instructions",
        ]; 
        
        // Create a new array with only the keys you want to keep
        $filtered_result = [];
        foreach ($keys_to_keep as $key => $value) {
            if (is_callable($value)) {
                $filtered_result[$key] = $value();
            } else {
                if (is_int($key)) {
                    $filtered_result[$value] = $rewards[$value] ?? null;
                } else {
                    $filtered_result[$key] = $rewards[$value] ?? null;
                }
            }
        }
    
        
           $payload = [
            'success' => true,
               'data' => $filtered_result,
            #   'rawdata' => $result,
           ];
           $api->responseHandler($payload);
        

       } else {
           // Unauthorized
           $api->responseError(401, ['message' => 'Invalid user_id']);
       }
   } else {
       // Bad input parameter
       $api->responseError(400, ['message' => 'Bad input parameter']);
   }
} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}


