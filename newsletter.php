<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$errormessage = '';
$username = '';
$continue = false;
#-------------------------------------------------------------------------------
# PROCESS LOGIN ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
  $username = (isset($_POST['email']) ? $_POST['email'] : '');
  if (!empty($username)) {
    $continue = true;
    $app->subscribe($username);
  }
}



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
if ($continue) {
  $content = '
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f44d/512.webp" type="image/webp">
<img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f44d/512.gif" alt="👍" width="128" height="128">
</picture>
<h1 class="display-1 mt-3">Thanks for Subscribing</h1>
<p class="mb-4">We\'ll use your email ' . $username . ' to send you our newsletter and other interesting features of birthday.gold</p>
';
} else {
  $content = '
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f626/512.webp" type="image/webp">
<img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f626/512.gif" alt="😦" width="128" height="128">
</picture>
<h1 class="display-1 mt-3">Hmmm... </h1><h1>We didn\'t receive a valid email</h1>
<p class="mb-4">Please provide an email address to send you our newsletter and other interesting features of birthday.gold</p>
';
}



echo '
<!-- Newsletter Start -->
<div class="container-xxl py-6  flex-grow-1" data-wow-delay="0.1s">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg-12">
' . $content . ' <a class="btn btn-primary py-3 px-5" href="/">Go Back To Home</a>
</div>
</div>
</div>
</div>
<!-- Newsletter End -->
';


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
