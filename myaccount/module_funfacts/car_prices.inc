<?php 
# DISPLAY GAS CARD
#-------------------------------------------------------------------------------
$event = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'car_price');
if (empty($event)) {
// If no events were found, set a default message
$funfactrecord=  '<div id="events"><p class="fw-bold">No historical data found.</p></div>';
} else {
$event=$event[0];
$eventarray = json_decode($event['event']);
$eventarray=$eventarray[0];
#$statecode=$qik->convert_StateNameToCode($current_user_data['state']);
#if (!empty($statecode)) $gaslink='https://gasprices.aaa.com/?state=' . $statecode;
# else 
 # $gaslink='https://gasprices.aaa.com/state-gas-price-averages/';
#breakpoint ($eventarray);
$currentprice=46000;

// Extracting the price from the title field (assuming it's formatted correctly)
$priceInTitle = preg_replace('/[^0-9]/', '', $eventarray->title); // Extracting numeric characters

// Calculating the difference
$difference = $currentprice - $priceInTitle;

// Creating the comment
$comment = "A difference of $difference from today's prices.";

$funfactrecord= '

 <div class="card h-100">
   <div class="card-body">
   <div class="card-title">Car prices in '.$event['year'].':</div>
   <div class=" d-flex align-items-start">
     <div class="avatar avatar-2xl me-3">
       <img src="/public/images/icon/new-car.gif" alt="" style="width: 48px; height: 48px;">
     </div>
     <div>
     <p class="h4 mb-1"><strong>' . $eventarray->title . '</strong></p>
     <p>'.$eventarray->description.' <a href="https://www.hemmings.com/classifieds/cars-for-sale/all-makes/'.$event['year'].'" target="link" class=" py-0 my-0 "><i class="bi bi-box-arrow-up-right  py-0 my-0 ms-1"  data-bs-toggle="tooltip" data-bs-placement="top" title="Check out cars of '.$event['year'].'"></i></a></p>
 
     </div>
   </div>
   </div>
   </div>
   
';
}
