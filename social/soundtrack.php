<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');





$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();