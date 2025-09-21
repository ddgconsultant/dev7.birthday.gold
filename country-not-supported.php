<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 




#-------------------------------------------------------------------------------
# PROCESS LOGIN ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted() && isset($_POST['override'])){
$session->set('country_not_supported_override', 'Y');

$pagemessage='<div class="alert alert-warning alert-dismissible fade show" role="alert">Proceeding in your unsupported country.</div>';
$transferpage['message']=$pagemessage;
$transferpage['url']=$_SERVER['REFERRER_URL'] ?? '/';
$system->endpostpage($transferpage);
exit;
}


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$countrytag=$session->get('countrynotsupportedtag', '');
$tagline=str_replace('{{countrytag}}', $countrytag, 'Birthday.Gold does not currently offer services in your country {{countrytag}}');
?>
<!-- Navbar End -->

<!-- Start -->
<div class="container main-content py-6">
<div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <i class="bi bi-exclamation-triangle display-1 text-danger"></i>
            <h1 class="display-1">Country Not Supported</h1>
            <div class="col-lg-8 mx-auto">
                <h2 class="mb-4"><?= $tagline; ?>.</h2>
            </div>
            <p class="mb-4">You can subscribe to our newsletter for updates and service expansion information.</p>

            <form method="post" id="mainform" action="/newsletter">
                <?PHP echo $display->inputcsrf_token(); ?>
                <div class="col-md-4 mx-auto">
                <input type="hidden" name="countrynotsupported" value="yes">
                    <input type="email" class="form-control p-2 border border-dark" name="email" id="email" value="" placeholder="Your Email Address">
                </div>
                <button class="btn btn-primary py-3 px-5 mt-3" id="mainsubmit" type="submit">Subscribe</button>
            </form>

            <div class="row justify-content-center mt-5">
                <form method="post" action="/country-not-supported">
                    <?PHP echo $display->inputcsrf_token(); ?>
                    <input type="hidden" name="override" value="yes">
                    <button class="btn btn-secondary fa-lg mt-5 btn-sm" type="submit">Continue Anyways</button>
                    <div><small class="mt-0">Continuing does not guarantee any service or support.</small></div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
<!-- End -->

        

<?PHP

echo $display->submitbuttoncolorjs('mainform');
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
