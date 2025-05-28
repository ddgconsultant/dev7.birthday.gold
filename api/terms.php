<?php
  include('api_coordinator.php');


// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
if (
    //$path=='/privacy' && 
$method === 'GET') {
    $content = $api->getContent('terms');
    $payload = [
        'content' => $content,
    ];
   
    $api->responseHandler($payload);
} else {
    $api->response400();
}