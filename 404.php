<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<?PHP
if (!empty($enableadminpageeditor)) {   $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###
?>
<!-- 404 Start -->
<div class="container main-content">

    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <picture>
                    <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a9/512.webp" type="image/webp">
                    <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a9/512.gif" alt="🚩" width="64" height="64">
                </picture>

                <h1 class="display-1">404</h1>
                <h1 class="mb-4">Page Not Found</h1>
                <p class="mb-4">We're sorry, the page you have looked for does not exist in our website! Maybe go to our home page or try to use a search?</p>
                <a class="btn btn-primary my-5 py- px-5" href="/">Go Back To Home</a>
            </div>
        </div>
    </div>
</div>
<!-- 404 End -->
<?PHP
### ADMIN PAGE EDITOR: END-body-1 ###
?>


<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
