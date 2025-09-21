<?php 
# DISPLAY GAS CARD
#-------------------------------------------------------------------------------
$event = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'popular_babynames');
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
$congrats='';
if (strpos($current_user_data['first_name'], $eventarray->title)!==false) {
  $congrats='<p class="text-success fw-bold">Congrats on having one of the most popular names of the year!</p>';
}
$funfactrecord= '

 <div class="card h-100">
   <div class="card-body">
   <div class="card-title">Popular Baby Names of '.$event['year'].':</div>
   <div class=" d-flex align-items-start">
     <div class="avatar avatar-2xl me-3">
       <img src="/public/images/icon/certificate.png" alt="" style="width: 48px;">
     </div>
     <div>
     <p class="h4 mb-1"><strong>' .str_replace(' axnd ', ' / ',  $eventarray->title) . '</strong></p>
     <p class="">'.$eventarray->description.'</p>
     '.$congrats.'
     </div>
   </div>
   </div>
   </div>
   
';
}
