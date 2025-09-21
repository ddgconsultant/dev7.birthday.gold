<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<?PHP
if (!empty($enableadminpageeditor)) {   $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###
?>
<!-- 503 Start -->
<div class="container main-content">

  <div class="container text-center">
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <picture>
          <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/2699_fe0f/512.webp" type="image/webp">
          <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/2699_fe0f/512.gif" alt="⚙" width="64" height="64">
        </picture>
        <h1 class="display-1">503</h1>
        <h1 class="mb-4">Service Unavailable</h1>
        <p class="mb-4">Our service is temporarily unavailable. We are currently undergoing maintenance or facing a temporary issue. Please try again later.</p>
        <a class="btn btn-primary py-3 px-5" href="/">Go Back To Home</a>
      </div>
    </div>
  </div>
</div>
<!-- 503 End -->
<?PHP
### ADMIN PAGE EDITOR: END-body-1 ###
?>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
