<?php

$addClasses[] = 'Convert';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$displaytype = 'downloaded_success_lockout'; #   echo "Failed to update gift code and status for user.";

// Successfully updated the user record
# $displaytype='success_lockout'; # echo "Successfully updated gift code and status for user.";

$filename = $session->get('download_gc_file');
$headerattribute['additionalcss'] = '<link rel="stylesheet" href="/public/css/myaccount.css"> ';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');


echo '
<!-- Gift Certificate Start -->
<div class="container-xxl py-5 flex-grow-1">
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-12">
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f3c1/512.webp" type="image/webp">
<img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f3c1/512.gif" alt="🏁" width="64" height="64">
</picture>
<h1 class="display-1 my-lg-3">Gift Certificate</h1>
<h3 class="mb-4">We\'ve personalized your gift certificate and emailed you a copy.<br>
You can also download it now.</h3>
</div>
</div>
<div class="m-3">
';


echo '<div id="firstdownload" class="d-block">';
echo ' <a target="_download" href="/downloader?t=gc&f=' . $filename . '" class="btn button btn-primary" onclick="handleDownload()">Download it</a>';
echo '</div>';
echo '<div id="additionaldownload" class="d-none">';
echo ' <h5>This ends your session with us.  You can close this window</h5>
<a target="_download" href="/downloader?t=gc&f=' . $filename . '" class="btn button btn-primary mt-lg-5">Download again</a>';
echo '</div>';
echo '<div id="spinner" class="d-none">';
echo ' <span>Loading...</span>'; // Replace this with your actual spinner
echo '</div>';



echo '
</div>
</div>
</div>
<!-- Gift Certificate End -->
';
$footerattribute['postfooter'] = '
<script>
function handleDownload() {
// Hide the first download button
document.getElementById("firstdownload").classList.add("d-none");

// Show the spinner
const spinner = document.getElementById("spinner");
spinner.classList.remove("d-none");

// Wait for 2 seconds
setTimeout(() => {
  // Hide spinner
  spinner.classList.add("d-none");
  
  // Show additional download button
  document.getElementById("additionaldownload").classList.remove("d-none");
}, 2000);
}
</script>
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
$account->logout();
