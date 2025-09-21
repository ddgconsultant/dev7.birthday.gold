<?php 
# DISPLAY HOME PRICE CARD
#-------------------------------------------------------------------------------
$event = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'home_price', true);
if (empty($event)) {
// If no events were found, set a default message
$funfactrecord = '<div id="events"><p class="fw-bold">No historical data found.</p></div>';
} else {
$event=$event[0];
$eventarray = json_decode($event['event']);
$eventarray=$eventarray[0];

// Current home price as of 2025
$current_price = 467500;
// Extract numbers for calculation
$birth_year_price = preg_replace('/[^0-9.]/', '', $eventarray->title);
$percent_change = round((($current_price - $birth_year_price) / $birth_year_price) * 100);

// Format numbers for display
$birth_price_display = '$' . number_format($birth_year_price);
$current_price_display = '$' . number_format($current_price);

// Check if this is an approximate year
$year_display = isset($event['approximate_year']) ? 
                $event['year'] . ' <small class="text-muted">(nearest to your birth year)</small>' : 
                $event['year'];

$funfactrecord = '
<div class="card h-100">
  <div class="card-body">
  <div class="card-title">Average US Home Price in '.$year_display.':</div>
  <div class=" d-flex align-items-start">
    <div class="avatar avatar-2xl me-3">
      <img src="/public/images/icon/house.gif" alt="" style="width: 48px; height: 48px;">
    </div>
    <div>
    <p class="h4 mb-1"><strong>' . $birth_price_display . '</strong></p>
    <p>'.$eventarray->description.' That\'s a ' . $percent_change . '% increase to today\'s average of ' . $current_price_display . '. <a href="https://www.census.gov/construction/nrs/historical_data/index.html" target="link" class=" py-0 my-0 "><i class="bi bi-box-arrow-up-right py-0 my-0 ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="View historical home price data"></i></a></p>
    </div>
  </div>
  </div>
</div>
';
}
