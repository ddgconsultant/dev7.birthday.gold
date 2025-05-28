<?php



echo '
<!-- Error Message -->
<div class="error-message" id="errorMessage">Unable to complete the action</div>
';

// Start the right-panel and carousel container
echo '
<div class="right-panel p-0 m-0">
';



include($_SERVER['DOCUMENT_ROOT'] . '/social/components/element-carousel.inc');

echo $elementcontent['carousel'];


include($_SERVER['DOCUMENT_ROOT'] . '/social/components/overlay.inc');
?>


</div>
</div>

<?PHP 
echo $elementcontent['js-carousel'];

