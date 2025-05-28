<?php 
# DISPLAY GAS CARD
#-------------------------------------------------------------------------------
$event = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'toy_oftheyear');
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
$funfactrecord= '

 <div class="card h-100">
   <div class="card-body">
   <div class="card-title">The most popular toy in '.$event['year'].':</div>
   <div class=" d-flex align-items-start">
     <div class="avatar avatar-2xl me-3">
       <img src="/public/images/icon/rocking-horse.gif" alt="" style="width: 48px; height: 48px;">
     </div>
     <div>
     <p class="h4 mb-1"><strong>' . $eventarray->title . '</strong></p>
     <p>'.$eventarray->description.'</p>
     </div>
   </div>
   </div>
   </div>
   
';
}
