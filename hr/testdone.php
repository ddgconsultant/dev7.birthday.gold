<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

$headerattribute['additionalcss']='';

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 

?>
<!-- Navbar End -->

<!-- 403 Start -->
<div class="container-xxl py-6 min-vh-50">
    
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg-6">
<picture>
  <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a7/512.webp" type="image/webp">
  <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a7/512.gif" alt="🚧" width="64" height="64">
</picture>
<h1 class="display-1">Done</h1>
<h1 class="mb-4">YEA! Done form submited.</h1>
<a class="btn btn-primary py-3 px-5" href="/">Go Back To Home</a>
</div>
</div>
</div>
</div>
<!-- 403 End -->


<?PHP

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php'); 