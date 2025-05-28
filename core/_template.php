<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
// always use single PHP BLOCK, ECHO block statements. 
// Do not use Short Echo Tags, Short Tags, Multiple PHP Tags or Nowdoc/Heredoc syntax
// access to /myaccount and /admin pages are controlled by the site-controller.php file.


#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// initialize variables here


#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// handle any form posted process here
if ($app->formposted()) {

}


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

$additionalstyles .= '
<style>
</style>
';

#$sql = 'SELECT * FROM bg_user_attributes WHERE user_id=:user_id';
#$stmt = $database->prepare($sql);
#$stmt->execute(['user_id' => $current_user_data['user_id']]);
#$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '    
<div class="container main-content mt-0 pt-0">
  <div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Page Title</h2>
  <a href="/careers" class="btn btn-sm btn-outline-secondary">Back To previous section</a>
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
