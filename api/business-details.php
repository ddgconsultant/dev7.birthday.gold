<?PHP
$pagemode = 'core';
  include('api_coordinator.php');

// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];



#-------------------------------------------------------------------------------
# LOCAL FUNCTIONS
#-------------------------------------------------------------------------------
function generateRandomBusinessHours() {
    $openHour = rand(6, 10); // Opening time between 6 AM and 10 AM
    $closeHour = rand(18, 23); // Closing time between 5 PM and 10 PM

    // Format them to 2-digit
    $formattedOpenHour = str_pad($openHour, 2, "0", STR_PAD_LEFT);
    $formattedCloseHour = str_pad($closeHour, 2, "0", STR_PAD_LEFT);

    return "$formattedOpenHour:00 - $formattedCloseHour:00";
}


function randomCoordinatesAroundZip($zipCode, $database, $radiusInMiles) {
    $earth_radius = 3958.8;  // Earth radius in miles

    // Fetch the latitude and longitude for the given ZIP code
    $sql = "SELECT latitude, longitude FROM ds_ref_zipcodes WHERE zipcode = :zipcode LIMIT 1";
    $stmt = $database->prepare($sql);
    $stmt->execute([':zipcode' => $zipCode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return null;  // ZIP code not found
    }

    $zipCenterLat = $result['latitude'];
    $zipCenterLon = $result['longitude'];

    // Convert latitude and longitude from degrees to radians
    $lat = deg2rad($zipCenterLat);
    $lon = deg2rad($zipCenterLon);

    // Generate random distance and angle
    $randDist = sqrt(mt_rand() / mt_getrandmax()) * $radiusInMiles;
    $randAngle = mt_rand() / mt_getrandmax() * 2 * M_PI;

    // Calculate new coordinates
    $newLat = rad2deg($lat + ($randDist / $earth_radius));
    $newLon = rad2deg($lon + ($randDist / $earth_radius) / cos($lat));
// Round to 6 decimal places
$newLat = round($newLat, 6);
$newLon = round($newLon, 6);
    return [
        'lat' => $newLat,
        'lon' => $newLon
    ];
}





#-------------------------------------------------------------------------------
# HANDLE THE REQUEST
#-------------------------------------------------------------------------------
if ( $method === 'GET') {
   // Get the JSON payload from the request body
   #$inputJSON = file_get_contents('php://input');
   $input = $_GET;

   // Validate the input
   if (isset($input['company_id']) && isset($input['user_zipcode']) ) {
       $id = $input['company_id'];
       $zipCode=$input['user_zipcode'];
       // Authenticate the user
       $result = $app->getcompany($id);
  #  breakpoint($resultraw);
    }

    $result['company_logo']= $display->companyimage($result['company_id'].'/'.$result['company_logo']);
     
           if (!empty($result)) {
        // Define the keys you want to keep
        $keys_to_keep = [
            "company_name", 
            "address", 
            "city", 
            "state", 
            "zip", 
            "map_address",
            "lat" => "latitude",
            "lon" => "longitude", 
            "coordinates" ,
            "lonlat_source" => function() { return 'db'; },
            "phone_number", 
            "business_hours", 
            "logo_url" => 'company_logo', 
            "overall_rating" => function() { return rand(0, 5); }, 
            "num_ratings" => 'usage_count'
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
        $filtered_result['business_hours']=generateRandomBusinessHours();
        $filtered_result['logo_url']='https:'.$filtered_result['logo_url']  ;
     
          
#$zipCode = '94103';  // Replace with the ZIP code you're interested in
$radiusInMiles = 15;  // Radius around the ZIP code within which to generate coordinates
if (empty($filtered_result['coordinates'])) {
$randomCoordinates = randomCoordinatesAroundZip($zipCode, $database, $radiusInMiles);

if ($randomCoordinates) {
    $filtered_result['coordinates'] =$randomCoordinates ;
    $filtered_result['lat'] = $randomCoordinates['lat'];
    $filtered_result['lon'] = $randomCoordinates['lon'];
   # print_r($filtered_result);
} else {
    $filtered_result['coordinates'] = 'location-not-found';
    $filtered_result['lat'] = 'location-not-found';
    $filtered_result['lon'] = 'location-not-found';
}
$filtered_result['lonlat_source']='random';
$filtered_result['xxx']='random';
}

      #  $filtered_result['logo_url']='https:'.$display->companyimage($filtered_result['logo_url'])  ;




      $rewardrow=$database->fetch('select * from bg_company_rewards where company_id='.$input['company_id'].' and category="birthday" and `status`="active"');
      if (!empty($rewardrow)) {
          $filtered_result['reward_id']=$rewardrow['reward_id'];
          $filtered_result['reward_description_short']=$rewardrow['reward_description_short'];
          $filtered_result['reward_description_long']=$rewardrow['reward_description_long'];
          $filtered_result['reward_redeem_instructions']=$rewardrow['redeem_instructions'];
      } else {
      $filtered_result['reward_id']=rand(10001,99999);
      $filtered_result['reward_description_short']=$qik->generateLoremIpsum(10, 'words');
      $filtered_result['reward_description_long']=$qik->generateLoremIpsum(20, 'words');
      $filtered_result['reward_redeem_instructions']=$qik->generateLoremIpsum(30, 'words');
      }

           $payload = [
            'success' => true,
               'data' => $filtered_result,
      #         'rawdata' => $result,
           ];
           $api->responseHandler($payload);
        

      
   } else {
       // Bad input parameter
       $api->responseError(400, ['message' => 'company_id is required']);
   }
} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}


