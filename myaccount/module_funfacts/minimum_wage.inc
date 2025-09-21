<?php 
# DISPLAY MINIMUM WAGE CARD
#-------------------------------------------------------------------------------
$event = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'minimum_wage', true);
if (empty($event)) {
// If no events were found, set a default message
$funfactrecord = '<div id="events"><p class="fw-bold">No historical data found.</p></div>';
} else {
$event=$event[0];
$eventarray = json_decode($event['event']);
$eventarray=$eventarray[0];

// Current federal minimum wage as of April 2025
$current_wage = 7.25;
// Calculate percentage change
$old_wage = floatval(preg_replace('/[^0-9.]/', '', $eventarray->title));
$percent_change = round((($current_wage - $old_wage) / $old_wage) * 100);

// Check if this is an approximate year
$year_display = isset($event['approximate_year']) ? 
                $event['year'] . ' <small class="text-muted">(nearest to your birth year)</small>' : 
                $event['year'];

$funfactrecord = '
<div class="card h-100">
  <div class="card-body">
  <div class="card-title">Minimum Wage in '.$year_display.':</div>
  <div class=" d-flex align-items-start">
    <div class="avatar avatar-2xl me-3">
      <img src="/public/images/icon/money.gif" alt="" style="width: 48px; height: 48px;">
    </div>
    <div>
    <p class="h4 mb-1"><strong>' . $eventarray->title . '</strong></p>
    <p>'.$eventarray->description.' A ' . $percent_change . '% change compared to today\'s federal minimum wage of $' . $current_wage . '. <a href="https://www.dol.gov/agencies/whd/minimum-wage/history" target="link" class=" py-0 my-0 "><i class="bi bi-box-arrow-up-right py-0 my-0 ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="View minimum wage history"></i></a></p>
    </div>
  </div>
  </div>
</div>
';
}