<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
// always use single PHP BLOCK, ECHO block statements. 
// Do not use Short Echo Tags, Short Tags, Multiple PHP Tags or Nowdoc/Heredoc syntax
// access to /myaccount and /admin pages are controlled by the site-controller.php file.



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
</style>
';


echo '    
<div class="container main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Page Title</h2>
  <a href="/" class="btn btn-sm btn-outline-secondary">Home</a>
</div>
';


  echo '
  <div class="card">
      <div class="card-body">';
        // CONTENT GOES HERE

  echo '
  </div></div></div>
  </div></div></div>';


$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
