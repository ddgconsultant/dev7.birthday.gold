<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<?PHP
if (!empty($enableadminpageeditor)) {   $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###
?>
<!-- 403 Start -->
<div class="container main-content">

    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <picture>
                    <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/274c/512.webp" type="image/webp">
                    <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/274c/512.gif" alt="❌" width="64" height="64">
                </picture>
                <h1 class="display-1">403</h1>
                <h1 class="mb-4">Attempting To Access</h1>
                <p class="mb-2">Thank you for visiting our site, but it seems like you are attempt to access something that isn't available.</p>
                <p class="mb-4">Your IP ADDRESS: <? echo $client_ip; ?> has been recorded.</p>
                <a class="btn btn-primary py-3 px-5" href="/">Go Back To Home</a>
            </div>
        </div>
    </div>
</div>
<!-- 403 End -->
<?PHP
### ADMIN PAGE EDITOR: END-body-1 ###
?>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
