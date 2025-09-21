<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


    <div class="container main-content py-12">
        <div class="container text-center">
            <div class="row justify-content-center">
                <div class="col-lg-12 mt-5">
                    <img src="/public/images/logo/bg_icon.png">
                    <h1 class="display-1">Coming Soon</h1>
                    <h1 class="mb-4">Our Community Forum Feature</h1>
                    <p class="mb-4">This big dessert isn't quite ready to come out of the oven.  Check back soon.</p>
                    <a class="btn btn-primary py-3 px-5" href="/">Go Back To Home</a>
                </div>
            </div>
        </div>
    </div>
        

    
    <?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
