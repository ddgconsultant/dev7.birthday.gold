<?php
$pagemode = 'core';
  include('api_coordinator.php');


// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
  $filtered_result = [];
  $filtered_result['status'] = 'tracked';
  #$filtered_result['reward_id'] = rand(10001, 99999);

  // Assuming input::type is sanitized and validated
  $tour_company_status = $_REQUEST['type']; // or however you get input::type
  $tour_dt = $_REQUEST['tour_dt']; // assuming tour_dt is passed in request and is required
  $user_id = $_REQUEST['user_id']; // or however you get input::type
  $company_id = $_REQUEST['company_id']; // assuming tour_dt is passed in request and is required
 
  if (!$tour_dt && !$user_id && !$company_id && !$tour_company_status) {
      $api->response400();
      exit;
  }
  $params=[
    ':tour_company_status'=>$tour_company_status, 
    ':tour_dt'=>$tour_dt, 
   ':user_id'=>$user_id, 
   ':company_id'=>$company_id, 
   ];
  try {
      $stmt = $database->prepare("UPDATE bg_user_tours SET tour_company_status = :tour_company_status, last_visit_dt=now() WHERE calendar_dt = :tour_dt and user_id=:user_id and company_id=:company_id");  
      $stmt->execute($params);
  } catch (PDOException $e) {
      echo "Update failed: " . $e->getMessage();
  }
  

  $stmt = $database->prepare("select * from bg_company_rewards  WHERE company_id=:company_id and category='birthday' limit 1");  
  $stmt->execute([':company_id'=>$company_id, ]);


  $rewards= $stmt->fetch(PDO::FETCH_ASSOC);

  $payload = [
      'success' => true,
      'reward_id'=> $rewards['reward_id'],
      'reward_details' => $rewards,
  ];
 # session_tracking('start tour', $_REQUEST);
  $api->responseHandler($payload);
} else {
  $api->response400();
}
