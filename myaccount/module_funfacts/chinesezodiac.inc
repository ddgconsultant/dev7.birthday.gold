<?php 
# DISPLAY ASTROSIGN CARD
#-------------------------------------------------------------------------------

$astrosigndetails= $app->getastrosign($current_user_data['birthdate'], 'chinesesign');
$funfactrecord= '

 <div class="card h-100">
   <div class="card-body">
   <div class="card-title">Your Chinese Zodiac Sign</div>
   <div class=" d-flex align-items-start">
     <div class="avatar avatar-2xl me-3">
       <img src="/public/images/icon/'.$astrosigndetails['sign'].'.gif" alt="" style="width: 48px; height: 48px;">
     </div>
     <div>
     <p class="h4 mb-1">'.$astrosigndetails['year'].' year of the <strong>'.ucfirst($astrosigndetails['sign']).'</strong></p>
   <p><span    data-bs-toggle="tooltip" data-bs-placement="top"  title="'.$astrosigndetails['element_meaning'].'">'.$astrosigndetails['element'].'</span> 
      <a href="https://www.astrology.com/horoscope/daily-chinese/'.$astrosigndetails['sign'].'.html" target="link" class=" py-0 my-0 "><i class="bi bi-box-arrow-up-right ms-1 py-0 my-0 "   data-bs-toggle="tooltip" data-bs-placement="top" title="Get your daily Chinese horoscope - Astrology.com"></i></a> 
     </p> 
     </div>
   </div>
   </div>
 </div>

';
