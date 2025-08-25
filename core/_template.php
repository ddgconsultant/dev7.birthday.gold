<?php
# if we have to add any classes for any class instaitation, uncomment and add them to this array
# they are loaded/configured in the site-controller.php file
# $addClasses[]='';



include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
// BIRTHDAY GOLD DEVELOPMENT/CODING STANDARDS
// always use single PHP BLOCK, ECHO block statements.  (do not Mixing HTML output directly with conditional PHP using php tags/Multiple PHP tags)
// Do not use Short Echo Tags, Short Tags, Multiple PHP Tags or Nowdoc/Heredoc syntax
// access to /myaccount and /admin pages are controlled by the site-controller.php file - do not put any access control in the files
// Bootstrap 5 utility-first approach! - do not use custom css unless absolutely necessary of which you add to $additionalstyles
// typically member data is in $current_user_data array
// do not use abbreviation in comments - use full words (no single quotes/apostrophes)

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
