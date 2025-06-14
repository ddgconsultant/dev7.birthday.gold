<?php 

// Generate a random card
$choices = ['Color'];
#$choices = ['inventions'];
$key = array_rand($choices);
$randomcard = $choices[$key];



# DISPLAY BIRTH ELEMENT CARD
#-------------------------------------------------------------------------------
$element=$randomcard;
$birthelement=$app->getBirthElement($current_user_data['birthdate'], $element);
$funfactrecord= '

 <div class="card h-100">
   <div class="card-body">
   <div class="card-title">Your Birth '.$element.'</div>
   <div class=" d-flex align-items-start">
     <div class="avatar avatar-2xl me-3">
       <img src="/public/images/icon/'.$birthelement['icon'].'" alt="" style="width: 48px; height: 48px;">
     </div>
     <p class="h4 mb-1"><strong>'.$birthelement['message'].'</strong></p>
   </div>
   </div>
 </div>

';