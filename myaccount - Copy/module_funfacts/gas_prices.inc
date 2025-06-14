<?php 
# DISPLAY GAS CARD
#-------------------------------------------------------------------------------
$event = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'gas_usyearlyaverage');
if (empty($event)) {
// If no events were found, set a default message
$eventcontent = '<div id="events"><p class="fw-bold">No historical data found.</p></div>';
} else {
$event=$event[0];
$eventcontent = '$'.$event['event']." / gal.";
$statecode=$qik->convert_StateNameToCode($current_user_data['state']);
if (!empty($statecode)) $gaslink='https://gasprices.aaa.com/?state=' . $statecode;
  else 
  $gaslink='https://gasprices.aaa.com/state-gas-price-averages/';
  $funfactrecord= '

 <div class="card h-100">
   <div class="card-body">
   <div class="card-title">In '.$event['year'].', gas prices were</div>
   <div class=" d-flex align-items-start">
     <div class="avatar avatar-2xl me-3">
       <img src="/public/images/icon/eco-fuel.gif" alt="" style="width: 48px; height: 48px;">
     </div>
     <div>
     <p class="h4 mb-1"><strong>' . $eventcontent . '</strong></p>
     <p>(national average) <a href="'.$gaslink.'" target="link"><i class="bi bi-box-arrow-up-right ms-1  py-0 my-0"  data-bs-toggle="tooltip" data-bs-placement="top" title="Check Out Gas Pricess - AAA.com"></i></a></p>
     </div>
   </div>
   </div>
   </div>

';
}
