<?php 
# DISPLAY INTERNET USERS CARD
#-------------------------------------------------------------------------------
$event = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'internet_users', true);
if (empty($event)) {
// If no events were found, set a default message
$funfactrecord = '<div id="events"><p class="fw-bold">No historical data found.</p></div>';
} else {
$event=$event[0];
$eventarray = json_decode($event['event']);
$eventarray=$eventarray[0];

// Current internet users as of 2025
$current_users = "5.3 billion";
// Extract numbers for calculation
$birth_year_users = preg_replace('/[^0-9.]/', '', $eventarray->title);
// Handle millions vs billions conversion
if (strpos($eventarray->title, 'million') !== false) {
  $birth_year_users = $birth_year_users / 1000; // Convert millions to billions
} else {
  // Already in billions, no conversion needed
}
$current_users_num = 5.3;

// For births before internet era
if (intval($event['year']) < 1990) {
  $growth_text = "When you were born, the internet didn't exist for public use! Today, over 5.3 billion people use the internet.";
} else {
  // Calculate growth
  if ($birth_year_users > 0) {
    $growth_factor = round($current_users_num / $birth_year_users);
    $growth_text = "That's " . $growth_factor . "x fewer users than today's " . $current_users . " internet users.";
  } else {
    $growth_text = "Today, over " . $current_users . " people use the internet worldwide.";
  }
}

// Check if this is an approximate year
$year_display = isset($event['approximate_year']) ? 
                $event['year'] . ' <small class="text-muted">(nearest to your birth year)</small>' : 
                $event['year'];

$funfactrecord = '
<div class="card h-100">
  <div class="card-body">
  <div class="card-title">Internet Users in '.$year_display.':</div>
  <div class=" d-flex align-items-start">
    <div class="avatar avatar-2xl me-3">
      <img src="/public/images/icon/internet.gif" alt="" style="width: 48px; height: 48px;">
    </div>
    <div>
    <p class="h4 mb-1"><strong>' . $eventarray->title . '</strong></p>
    <p>'.$eventarray->description.' ' . $growth_text . ' <a href="https://www.internetworldstats.com/emarketing.htm" target="link" class=" py-0 my-0 "><i class="bi bi-box-arrow-up-right py-0 my-0 ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="View internet usage statistics"></i></a></p>
    </div>
  </div>
  </div>
</div>
';
}
