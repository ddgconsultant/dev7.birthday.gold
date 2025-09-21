<?php 

include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

$headerattribute['additionalcss']='';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

?>
<!-- Navbar End -->

<div class="container py-6 main-content">


<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg-12">
<picture>
  <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1fae1/512.webp" type="image/webp">
  <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1fae1/512.gif" alt="🫡" width="64" height="64">
</picture>
<h1 class="display-1">Received</h1>
<h1 class="mb-4">Your request has been submitted.</h1>
<p class="mb-4">We will process your request.  
  A human will actually process your request by hand, so it will take a little bit of time.  
  We may need to reach out to you for confirmation. Please respond to any emails from our customer service team.</p>
<a class="btn btn-primary py-3 px-5 my-5" href="/myaccount/">Go To Account</a>
</div>
</div>
</div>
</div>



<?PHP
#include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php'); 
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();

