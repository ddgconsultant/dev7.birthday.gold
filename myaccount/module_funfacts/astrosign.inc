<?php 
# DISPLAY ASTROSIGN CARD
if ($component_user_data['birthdate']) $astrobdate=$component_user_data['birthdate']; else  $astrobdate=$current_user_data['birthdate'];
#-------------------------------------------------------------------------------
$astrosigndetails=$app->getastrosign($astrobdate, 'all');
$astrosign_horoscopelink='https://www.astrology.com/horoscope/daily/'.$astrosigndetails['sign'].'.html';
$funfactrecord= '

 <div class="card h-100">
   <div class="card-body">
   <div class="card-title">Your Sign</div>
   <div class=" d-flex align-items-start">
     <div class="avatar avatar-2xl me-3">
       <img src="/public/images/icon/'.$astrosigndetails['sign'].'.gif" alt="" style="width: 48px; height: 48px;">
     </div>
     <div>
     <p class="h4 mb-1">
     
     <strong>'.ucfirst($astrosigndetails['sign']).'</strong></p>
     <p><span data-bs-toggle="tooltip" data-bs-placement="top"  title="'.$astrosigndetails['element_meaning'].'">'.$astrosigndetails['element'].'</span> 
      <a href="'.$astrosign_horoscopelink.'" target="link">
      <i class="bi bi-box-arrow-up-right py-0 my-0 ms-1"   data-bs-toggle="tooltip" data-bs-placement="top" title="Get your horoscope - Astrology.com"></i></a></p>
     </div>
   </div>
   </div>
 </div>

';
