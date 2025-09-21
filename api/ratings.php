<?PHP
$pagemode = 'core';
  include('api_coordinator.php');

// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];




#-------------------------------------------------------------------------------
# HANDLE RETURN RATING
#-------------------------------------------------------------------------------
if ( $method === 'GET') {
   // Get the JSON payload from the request body
   #$inputJSON = file_get_contents('php://input');
   $input = $_GET;
#breakpoint($input);
   // Validate the input
   if (isset($input['company_id'])) {
       $id = $input['company_id'];

       // Authenticate the user
       $result = $app->getcompany($id);
      # $result=$resultraw[0];
    }
       if (!empty($result)) {
           // Successful 


           if (!empty($result)) {
        // Define the keys you want to keep
        $keys_to_keep = [
            "company_name", 
            "overall_rating" => function() { return rand(0, 5); }, 
            "num_ratings" => 'usage_count',
        ];
        
        // Create a new array with only the keys you want to keep
        $filtered_result = [];
        foreach ($keys_to_keep as $key => $value) {
            if (is_callable($value)) {
                $filtered_result[$key] = $value();
            } else {
                if (is_int($key)) {
                    $filtered_result[$value] = $result[$value] ?? null;
                } else {
                    $filtered_result[$key] = $result[$value] ?? null;
                }
            }
        }
        $start=0;
        $ratings=$app->getrating($id, $start, 10);
        $ratingoutput = [];

    if (!empty($ratings)) {
       
    
        foreach ($ratings as $ratingrow) {
            $mediaArray = [];
            if (!empty($ratingrow['media'])) {
                $mediaJson = json_decode($ratingrow['media'], true);
                if ($mediaJson) {
                    foreach ($mediaJson as $mediaItem) {
                        if (!strpos($mediaItem, 'cdn')) $mediaItem='//cdn.birthday.gold/public/ratingsmedia/'.$mediaItem;
                        $mediaItem='https:'.$mediaItem;
                        $mediaArray[] = $mediaItem;
                    }
                }
            }
    
            $ratingoutput[] = [
                'rating' => $ratingrow['rating'],
                'username' => '@'.$ratingrow['username'],
                'create_dt' => $ratingrow['create_dt'],
                'message' => $ratingrow['message'],
                'medialist' => $mediaArray,
            ];
        }
    }
            
           $payload = [
            'success' => true,
               'data' => $filtered_result,
      #         'rawdata' => $result,
      'ratings'=>$ratingoutput,
           ];
           $api->responseHandler($payload);
        

       } else {
           // Unauthorized
           $api->responseError(401, ['message' => 'Invalid company_id']);
       }
   } else {
       // Bad input parameter
       $api->responseError(400, ['message' => 'Company_id is required']);
   }
}



#-------------------------------------------------------------------------------
# HANDLE POSTING RATING
#-------------------------------------------------------------------------------
elseif ($method === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);
 
    #$input = $_POST;
    #breakpoint($input);
    // Validate and sanitize the input
    if(isset($input['company_id'], $input['user_id'], $input['rating'], $input['comment'])) {
        $company_id = (int)$input['company_id'];
        $user_id = (int)$input['user_id'];
        $tour_id = $input['tour_id'] ?? 1;
        $message = htmlspecialchars($input['comment'], ENT_QUOTES, 'UTF-8');
        $rating = (int)$input['rating'];
        $location_id = $input['location_id'] ?? 1;
        $location_gift_id = $input['location_gift_id'] ?? 1;


        // Insert into the database using your existing $database class
        $result = $database->query("INSERT INTO `bg_company_rewards_ratings` (`user_id`, `tour_id`, `company_id`, `location_id`, `location_gift_id`, `rating`, `message`, create_dt, modify_dt ) VALUES (?, ?, ?, ?, ?, ?, ?, now(), now())", [$user_id, $tour_id, $company_id, $location_id, $location_gift_id, $rating, $message]);
        $last_id = $database->lastInsertId();
        if($result) {
            $api->responseHandler(['success' => true, 
            'message' => 'Rating successfully added',
            'rating_id' => $last_id,
            'rating_comment'=>$message],
         );
        } else {
            $api->responseError(500, ['message' => 'Internal server error']);
        }
    } else {
        $api->responseError(400, ['message' => 'Required fields are missing']);
    }

} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}

