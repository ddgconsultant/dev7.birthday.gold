<?PHP
# DISPLAY HOURS SLEPT CARD
#-------------------------------------------------------------------------------
$days_alive = $alive['days'] + (time() - strtotime('today')) / 86400;
// Average person sleeps about 8 hours per day
$hours_slept = floor($days_alive * 8);

$funfactrecord = '
<div class="card h-100">
  <div class="card-body">
  <div class="card-title">Hours Slept</div>
  <div class=" d-flex align-items-start">
    <div class="avatar avatar-2xl me-3">
      <img src="/public/images/icon/sleep.gif" alt="" style="width: 48px; height: 48px;">
    </div>
    <div>
    <p class="h4 mb-1"><strong id="sleepcount">'.number_format($hours_slept).'</strong> hours</p>
    <p>...spent sleeping in your lifetime</p>
    </div>
  </div>
  </div>
</div>
';

$funfactrecord .= '
<script>
// JavaScript code to update the sleep count every hour
function updateSleepCount() {
  var sleepElement = document.getElementById("sleepcount");
  var currentHours = parseInt(sleepElement.innerText.replace(/,/g, \'\'));
  var newHours = currentHours + 0.33; // About 8 hours per day = 0.33 per hour
  sleepElement.innerText = Math.floor(newHours).toLocaleString();
}

// Update the sleep count every hour
setInterval(updateSleepCount, 3600000);
</script>
';