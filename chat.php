<?PHP
#header('location: https://chat.birthdaygold.cloud');

#https://chat.birthdaygold.cloud/livechat?mode=popout
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content">
    <div class="row ">
        <div class="container">
            <h1 class="text-center">Customer Support Chat</h1>
<iframe src="//chat.birthdaygold.cloud/livechat?mode=popout" width="100%" style="height: 80vh" frameborder="0" scrolling="no"></iframe>

</div>
</div>
</div>


<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();