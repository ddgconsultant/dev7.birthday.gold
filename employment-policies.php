<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------


// Read the HTML file
$htmlFile = $_SERVER['DOCUMENT_ROOT'] . '/components/employment-and-hiring-policies.html';
$htmlContent = file_get_contents($htmlFile);

// Use regex to strip out the h1 tag with the specific content
$cleanedContent = preg_replace("/Documize Community Export/", 'Birthday.Gold', $htmlContent);



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
$additionalstyles .= '

<link rel="stylesheet" href="/public/css/employment.css">
<style>
</style>
';

// PAGE CONTENT STARTS HERE
// Chunked reading of the HTML file
$htmlFile = $_SERVER['DOCUMENT_ROOT'] . '/components/employment-and-hiring-policies.html';
$chunkSize = 8192; // Read in 8KB chunks
echo '<div class="container main-content">';

echo $cleanedContent;



echo '</div>';
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
