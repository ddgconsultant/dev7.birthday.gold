<?php 
# DISPLAY WORLD POPULATION CARD
#-------------------------------------------------------------------------------
$event = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'world_population', true);
if (empty($event)) {
// If no events were found, set a default message
$funfactrecord = '<div id="events"><p class="fw-bold">No historical data found.</p></div>';
} else {
$event=$event[0];
$eventarray = json_decode($event['event']);
$eventarray=$eventarray[0];

// Current world population as of 2025
$current_population = "8.2 billion";
// Extract numbers for calculation
$birth_year_pop = preg_replace('/[^0-9.]/', '', $eventarray->title);
$current_pop = 8.2;
$growth_percentage = round((($current_pop - $birth_year_pop) / $birth_year_pop) * 100);

// Check if this is an approximate year
$year_display = isset($event['approximate_year']) ? 
                $event['year'] . ' <small class="text-muted">(nearest to your birth year)</small>' : 
                $event['year'];

$funfactrecord = '
<div class="card h-100">
  <div class="card-body">
  <div class="card-title">World Population in '.$year_display.':</div>
  <div class=" d-flex align-items-start">
    <div class="avatar avatar-2xl me-3">
      <img src="/public/images/icon/population.gif" alt="" style="width: 48px; height: 48px;">
    </div>
    <div>
    <p class="h4 mb-1"><strong>' . $eventarray->title . '</strong></p>
    <p>'.$eventarray->description.' That\'s a ' . $growth_percentage . '% increase to today\'s population of ' . $current_population . '. <a href="https://www.worldometers.info/world-population/world-population-by-year/" target="link" class=" py-0 my-0 "><i class="bi bi-box-arrow-up-right py-0 my-0 ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="View population history"></i></a></p>
    </div>
  </div>
  </div>
</div>
';
}
