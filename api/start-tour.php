<?php
$pagemode = 'core';
  include('api_coordinator.php');


// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
if (
    //$path=='/privacy' && 
$method === 'POST') {
  #  $content = $api->getContent('terms');
    $payload = [
        'success' => true,
    ];
   session_tracking('start tour', $_REQUEST);
    $api->responseHandler($payload);
} else {
    $api->response400();
}