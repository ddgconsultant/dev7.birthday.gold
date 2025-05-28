<?PHP

$till=$app->getTimeTilBirthday($current_user_data['birthdate']);

# DISPLAY HEARTBEAT CARD
#-------------------------------------------------------------------------------
$heartbeats = floor(($alive['days'] + (time() - strtotime('today')) / 86400) * 100000 * 1.1574);
$funfactrecord= '

 <div class="card h-100">
   <div class="card-body">
   <div class="card-title">Heartbeats</div>
   <div class=" d-flex align-items-start">
     <div class="avatar avatar-2xl me-3">
       <img src="/public/images/icon/heartbeat.gif" alt="" style="width: 48px; height: 48px;">
     </div>
     <div>
     <p class="h4 mb-1"><strong id="heartbeatcount">'.number_format($heartbeats).'</strong></p>
     <p>...since being born</p>
     </div>
   </div>
   </div>
 </div>

';

$funfactrecord.= '
<script>


var initial = '.$till['seconds'].';
var count = initial * 100;
var counter; //10 will run it every 100th of a second

function timer() {
if (count <= 0) {
clearInterval(counter);
return;
}
count--;
displayCount(count);
}


// JavaScript code to update the heartbeat count every second
// Assuming the heartbeat count is displayed in the element with id "heartbeatcount"
function updateHeartbeatCount() {
var heartbeatElement = document.getElementById("heartbeatcount");
var currentHeartbeats = parseInt(heartbeatElement.innerText.replace(/,/g, \'\')); // Remove commas and parse as an integer
var newHeartbeats = currentHeartbeats + 1.1574;
heartbeatElement.innerText = Math.floor(newHeartbeats).toLocaleString(); // Add commas for better readability
// Log message to the console
//console.log("Heartbeat count updated to " + heartbeatElement.innerText);

}


// Update the heartbeat count every second (1000 milliseconds)
setInterval(updateHeartbeatCount, 1000);

function displayCount(count) {
var timerElement = document.getElementById("timer");
if (timerElement) {
  var res = count / 100;
  res = res.toFixed(2);
  var parts = res.toString().split(".");
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  timerElement.innerHTML = parts.join(".");
}
}


$(document).ready(function() {
counter = setInterval(timer, 10);
});

displayCount(initial);
</script>
';