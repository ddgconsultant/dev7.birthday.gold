<?PHP
# DISPLAY BREATHS CARD
#-------------------------------------------------------------------------------
$days_alive = $alive['days'] + (time() - strtotime('today')) / 86400;
// Average 20,000 breaths per day (about 14 breaths per minute)
$breaths = floor($days_alive * 20000);

$funfactrecord = '
<div class="card h-100">
  <div class="card-body">
  <div class="card-title">Breaths Taken</div>
  <div class=" d-flex align-items-start">
    <div class="avatar avatar-2xl me-3">
      <img src="/public/images/icon/breath.gif" alt="" style="width: 48px; height: 48px;">
    </div>
    <div>
    <p class="h4 mb-1"><strong id="breathcount">'.number_format($breaths).'</strong></p>
    <p>...approximately since birth</p>
    </div>
  </div>
  </div>
</div>
';

$funfactrecord .= '
<script>
// JavaScript code to update the breath count regularly
function updateBreathCount() {
  var breathElement = document.getElementById("breathcount");
  var currentBreaths = parseInt(breathElement.innerText.replace(/,/g, \'\'));
  var newBreaths = currentBreaths + 14; // About 14 breaths per minute
  breathElement.innerText = Math.floor(newBreaths).toLocaleString();
}

// Update the breath count every minute
setInterval(updateBreathCount, 60000);
</script>
';