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


function randomDataGenerator($zipCode, $database) {
$earth_radius = 3958.8;  // Earth radius in miles

// Fetch ZIP code data
$sql = "SELECT * FROM ds_ref_zipcodes WHERE zipcode = :zipcode LIMIT 1";
$stmt = $database->prepare($sql);
$stmt->execute([':zipcode' => $zipCode]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$filtered_result = [];  // Initialize filtered_result array

if ($result) {
// Address components
$streetNames = ["Elm St", "Maple Ave", "Oak Rd", "Pine Ln", "Cedar Dr", "Birch Pl", "Willow Way", "Spruce Ct", "Redwood Blvd", "Fir Tr"];
$address = rand(1, 9999) . " " . $streetNames[array_rand($streetNames)];
$city = $result['city'];
$state = $result['state'];
$zip = $result['zipcode'];
$areaCode = $result['areacode'];
$phoneNumber = "($areaCode) " . rand(200, 999) . "-" . rand(1000, 9999);

// Coordinate calculation
$zipCenterLat = $result['latitude'];
$zipCenterLon = $result['longitude'];
$lat = deg2rad($zipCenterLat);
$lon = deg2rad($zipCenterLon);
$randDist = sqrt(mt_rand() / mt_getrandmax()) * 5;  // 5-mile radius by default
$randAngle = mt_rand() / mt_getrandmax() * 2 * M_PI;
$newLat = rad2deg($lat + ($randDist / $earth_radius));
$newLon = rad2deg($lon + ($randDist / $earth_radius) / cos($lat));
// Round to 6 decimal places
$newLat = round($newLat, 6);
$newLon = round($newLon, 6);


// Prepare randomData
$randomData = [
'address' => $address,
'city' => $city,
'state' => $state,
'zip' => $zip,
'phone_number' => $phoneNumber,
'lat' => $newLat,
'lon' => $newLon,
'coordinates' => "$newLat,$newLon"
];


} else {
$randomData = [
'coordinates' => 'location-not-found',
'lat' => 'location-not-found',
'lon' => 'location-not-found'
];
// Update filtered_result for not-found case
#  $filtered_result = $randomData;
}

return $randomData;
}







#-------------------------------------------------------------------------------
# PROCESSING
#-------------------------------------------------------------------------------
if ( $method === 'GET') {
// Get the JSON payload from the request body
# $inputJSON = file_get_contents('php://input');
# $input = json_decode($inputJSON, TRUE);
$input = $_GET;
// Validate the input
if (isset($input['user_id'])) {
#  breakpoint($input);
$user_id = $input['user_id'];
$tourdate = $input['tour_dt'] ?? '';

// Authenticate the user
#  $result=$account->login($username, $password, 'both_api');
$current_user_data=$account->getuserdata($user_id, 'user_id');
if (empty($current_user_data)) {
        $api->responseError(401, ['message' => 'Invalid user_id']);
        exit;
}
#$accountstats=$account->account_getstats();
$plandetails=$plandetails_all=$app->plandetail('details');

$userplan=$current_user_data['account_plan'];

#$user_id = $current_user_data['user_id'];
$plandetailsx=$plandetails_all[$userplan];
$zipCode=$current_user_data['zip_code'];
$userbirthdate=$current_user_data['birthdate'];
#$userbirthdate='1923-10-12';
$birthdates=$account->getBirthdates($userbirthdate, $plandetailsx);



$birthdate = new DateTime($userbirthdate);
$currentYear = (new DateTime())->format('Y');
$birthdate->setDate($currentYear, $birthdate->format('m'), $birthdate->format('d'));
$input['plandetails']=$plandetailsx;
$input['current_user_data']=$current_user_data;
$input['birthdate']=$userbirthdate;
$input['plan']=$userplan;
#$input['loopstop']='dates';
$input['loopstop']='tours';
$input['displaytype']='apilist';
$input['linkhref']='/myaccount/tour?date=';
$input['navigation']='off';
$tourdatedetails= $account->generatetourcalendar($birthdates['today'], $length=5, $input);
$bodymessage='Please select a date to see available rewards';

if ($tourdate =='') {

    $newTourDates = [];
if (!empty($tourdatedetails)) {

// Loop through the existing array and populate the new array
foreach ($tourdatedetails as $date => $status) {
    $newTourDates[] = [
        'date' => $date,
        'status' => $status
    ];
}


}

$till=$app->getTimeTilBirthday($current_user_data['birthdate']);
$tourcount=count($newTourDates);
$payload = [
'success' => true,
'mode'=>'testing',
'message_tourcount' => $till['days'].$qik->plural(' day', $till['days']).' until your birthday: '.$till['formatted_date'].'.<br>You have '.$tourcount.$qik->plural(' tour', $tourcount).'.',
'message_body' => $bodymessage,
'numberoftours' => count($newTourDates),
'tour_dates' => $newTourDates,
];
$api->responseHandler($payload);
exit;
} else {



#-------------------------------------------------------------------------------
## TOUR DATE PROVIDED -- return companies
#-------------------------------------------------------------------------------
$checkEnrollmentQuery = "SELECT * FROM bg_user_tours WHERE calendar_dt = :date AND user_id= ".$current_user_data['user_id']."";
$stmt = $database->prepare($checkEnrollmentQuery);
$stmt->execute([':date'=> $tourdate]);
$tour= $stmt->fetchAll(PDO::FETCH_ASSOC);
$i=0;
$tour_details=[];
$listofcompanies=[];
if (!empty($tour)) {
foreach ($tour as $tour_item) {  
if ($i==0) {
$tour_details['tour_id']=$tour_item['tour_id'];
$tour_details['tour_date']=$tour_item['calendar_dt'];
$tour_details['user_id']=$tour_item['user_id'];
$tour_details['tour_start_dt']=$tour_item['tour_start_dt'];
$tour_details['tour_end_dt']=$tour_item['tour_end_dt'];
$tour_details['tour_status']=$tour_item['tour_status'];
$tour_details['company_count']=count($tour);
}


#if ($tour_item['tour_company_status']!='pending')  breakpoint ($tour_item);
$options['businessstatus']='%';
$company_data = $app->getcompany($tour_item['company_id'], $options);   

// Merging $tour_item and $company_data without overwriting
$company_data['company_logo']='https:'.$display->companyimage($company_data['company_id'].'/'.$company_data['company_logo'])  ;


if (!empty($tour_item)) {
// Define the keys you want to keep
$keys_to_keep = [
"company_id", 
"company_name", 
"address", 
"city", 
"state", 
"zip",
"coordinates" => "map_lonlat",
"phone_number", 
"business_hours", 
"logo_url" => 'company_logo', 
"overall_rating" => function() { return rand(0, 5); }, 
"ratings_count" => 'usage_count'
];

// Create a new array with only the keys you want to keep
$filtered_result = [];
foreach ($keys_to_keep as $key => $value) {
    if (is_callable($value)) {
        $filtered_result[$key] = $value();
    } else {
        if (is_int($key)) {
            $filtered_result[$value] = $company_data[$value] ?? null;
        } else {
            $filtered_result[$key] = $company_data[$value] ?? null;
        }
    }

$filtered_result['company_last_visit_dt']=$tour_item['last_visit_dt'];
$filtered_result['company_status']=$tour_item['tour_company_status'];
$filtered_result['business_hours']=generateRandomBusinessHours();


if (empty($filtered_result['coordinates'])) {
$randomData = randomDataGenerator($zipCode, $database);

if ($randomData) {
    $randomData["lonlat_source"]='random';
#print_r($randomData);
$filtered_result=array_merge($filtered_result, $randomData);

}
} else {
    $filtered_result['lonlat_source']='db';
}


$filtered_result['reward_id']=rand(10001,99999);
$filtered_result['reward_description_short']=$qik->generateLoremIpsum(10, 'words');
$filtered_result['reward_description_long']=$qik->generateLoremIpsum(20, 'words');
$filtered_result['reward_redeem_instructions']=$qik->generateLoremIpsum(30, 'words');


    $rewardrow=$database->fetch('select * from bg_company_rewards where company_id='.$tour_item['company_id'].' and category="birthday" and `status`="active"');
if (!empty($rewardrow)) {
    $filtered_result['reward_id']=$rewardrow['reward_id'];
    $filtered_result['reward_description_short']=$rewardrow['reward_description_short'];
    $filtered_result['reward_description_long']=$rewardrow['reward_description_long'];
    $filtered_result['reward_redeem_instructions']=$rewardrow['redeem_instructions'];
} 

}


}  
$listofcompanies[] =  $filtered_result;
$i++;
}

}
switch ($i) {
case 0:  $bodymessage= "There are no rewards available for your selected date."; break;
case 1:  $bodymessage= "You have a reward from this business."; break;
default: $bodymessage= "We're excited to celebrate your special day with some exciting rewards."; break;
}


$payload = [
    'success' => true,
    'message_tourcount' => $qik->plural2($i, 'tour'),
    'message_body' => $bodymessage,
    'tour_date' => $tourdate,
    'tour_detail' => $tour_details,
    'company_list' => ['count' => $i, 'data' => $listofcompanies],
];
$api->responseHandler($payload);
exit;
}

} else {
$api->responseError(400, ['message' => 'user_id required and tour_dt is optional']);
}
} else {
$api->response400(['message' => 'Method not allowed']);
}
