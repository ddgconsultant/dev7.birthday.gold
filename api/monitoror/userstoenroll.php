<?php
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$rowCount = $app->admin_getenrollments();


if ($rowCount<=3)    
 echo '<span aria-label="'.$rowCount.' user enrollments">'.$rowCount.' user enrollments</span>';
else
echo ''.$rowCount.' Enrollments';
$displayform=false;

