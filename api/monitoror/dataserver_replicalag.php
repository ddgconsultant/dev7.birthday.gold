<?php
$nosessiontracking=true;
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$queryB = "show replica status"; 
$stmt = $database->prepare($queryB);
$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$rowCount=$row['Seconds_Behind_Source'];

echo ''.$rowCount.' seconds behind';
