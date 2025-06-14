<?PHP
$ignoretrueheader=true;
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr" class="h-100">
<head>
<!-- =================-->
<!--       HEADER3inc -->
<?PHP
if (!empty($errorpage)) {
return;
}
?>

<script src="/public/assets/js/config.js"></script>
<script src="/public/assets/vendors/simplebar/simplebar.min.js"></script>

<?PHP
#echo '<link href="/public/assets/css/theme_reduced.css" rel="stylesheet" id="style-default">';

$local_stylesheetmode='v3';
include($BASEDIR . '/core/' . $website['ui_version'] . '/stylesheets.inc');

if (!empty($headerattribute['additionalcss'])) {
echo $headerattribute['additionalcss'];
}
?>

<link href="/public/assets/vendors/swiper/swiper-bundle.min.css" rel="stylesheet">
<!--   END HEADER3inc -->
<!-- =================-->