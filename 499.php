<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<?PHP
if (!empty($enableadminpageeditor)) {   $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###
?>
<!-- 499 Start -->
<div class="container main-content">

  <div class="container text-center">
    <div class="row justify-content-center">
      <div class="col-12">
        <picture>
          <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f4a9/512.webp" type="image/webp">
          <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f4a9/512.gif" alt="💩" width="64" height="64">
        </picture>
        <h1 class="display-1">499</h1>
        <h1 class="mb-4">Oh Sh!t</h1>
        <p class="h3 mb-4">We have detected that you are trying to be malicious.</p>
        <p>Additional attempts will result in a permanent ban and if necessary, we may persuit legal action.</p>
        <p class="mb-4">Your IP ADDRESS: <? echo $client_ip; ?> has been recorded.</p>
        <a class="btn btn-primary py-3 px-5" href="/">Go Back To Home</a>
      </div>
    </div>
  </div>
</div>
<!-- 499 End -->
<?PHP
### ADMIN PAGE EDITOR: END-body-1 ###
?>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
