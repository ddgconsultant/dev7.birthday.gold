<?php 
# DISPLAY ALIVE CARD
#-------------------------------------------------------------------------------
$funfactrecord= '

 <div class="card h-100">
   <div class="card-body">
   <div class="card-title">You have been alive for</div>
   <div class=" d-flex align-items-start">
     <div class="avatar avatar-2xl me-3">
       <img src="/public/images/icon/calendar.gif" alt="" style="width: 48px; height: 48px;">
     </div>
     <p class="h4 mb-1"><strong>'.number_format($alive['days']).' days</strong></p>
     <!-- <a class="text-arrow-icon small text-secondary py-0 my-0 " href="/myaccount/stats">
Interesting stats
<i class="bi bi-arrow-right-square py-0 my-0 "></i>
</a> -->
   </div>
   </div>
 </div>

';

