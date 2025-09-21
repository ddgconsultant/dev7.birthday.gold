<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

$headerattribute['additionalcss']='';

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 

?>
<!-- Navbar End -->

<!-- timcard locked Start -->
<div class="container-xxl py-6 min-vh-50">
    
<div class="container text-center">
<div class="row justify-content-center">
<div class="col">
<picture>
  <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a8/512.webp" type="image/webp">
  <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a8/512.gif" alt="🚨" width="64" height="64">
</picture>
<h1 class="display-1">Time Card Locked</h1>
<h1 class="mb-4">Oops! Your time card is locked.</h1>
<p class="mb-4"><?PHP echo $session->get('clockin_lock_reason');  $session->unset('clockin_lock_reason');     ?></p>
<p class="mb-4">You will need your supervisor/manager to unlock your timecard.</p>
<a class="btn btn-primary py-3 px-5" href="<?PHP echo $session->get('clockin_referer_page'); ?>">Go Back</a>
</div>
</div>
</div>
</div>
<!-- 403 End -->


<?PHP

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php'); 