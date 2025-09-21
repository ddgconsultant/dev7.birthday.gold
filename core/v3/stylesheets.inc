<?PHP
if (empty($local_stylesheetmode)) $local_stylesheetmode = false;
$bootstrapcss1='';

echo '
<!-- ===============================================-->
<!--    Stylesheets: ' . $local_stylesheetmode . '-->
<!-- ===============================================-->
';

switch ($local_stylesheetmode) {
###--------------------------------------------------------------------------------------------------------------------
case 'min':
echo '
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
';

if (empty($errorpage)) {
echo '
<link href="/public/assets/vendors/simplebar/simplebar.min.css" rel="stylesheet">
';
}

echo '
<link href="/public/assets/css/theme.css" rel="stylesheet" id="style-default">
<link href="/public/assets/css/user.css" rel="stylesheet" id="user-style-default">
' . $bootstrapcss1 . '
';
break;


###--------------------------------------------------------------------------------------------------------------------
case 'customx':
echo '
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
<link href="/public/assets/vendors/simplebar/simplebar.min.css" rel="stylesheet">
<link href="/public/assets/css/theme.css" rel="stylesheet" id="style-default">
<link href="/public/assets/css/user.css" rel="stylesheet" id="user-style-default">
' . $bootstrapcss1 . '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
';
break;


###--------------------------------------------------------------------------------------------------------------------
case 'businesses':
echo '
<link href="/public/assets/vendors/swiper/swiper-bundle.min.css" rel="stylesheet">
<link href="/public/assets/vendors/glightbox/glightbox.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
<link href="/public/assets/vendors/simplebar/simplebar.min.css" rel="stylesheet">
<link href="/public/assets/css/theme.css" rel="stylesheet" id="style-default">=
<link href="/public/assets/css/user.css" rel="stylesheet" id="user-style-default">
' . $bootstrapcss1 . '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

';
break;


###--------------------------------------------------------------------------------------------------------------------
case 'v3':
echo '<link href="/public/assets/vendors/simplebar/simplebar.min.css" rel="stylesheet">
<link href="/public/assets/css/theme.css" rel="stylesheet" id="style-default">';
break;


###--------------------------------------------------------------------------------------------------------------------
default:
echo '
<link href="/public/assets/vendors/flatpickr/flatpickr.min.css" rel="stylesheet">
<link href="/public/assets/vendors/glightbox/glightbox.min.css" rel="stylesheet">
<link href="/public/assets/vendors/plyr/plyr.css" rel="stylesheet">
<link href="/public/assets/vendors/dropzone/dropzone.min.css" rel="stylesheet">
<link href="/public/assets/vendors/leaflet/leaflet.css" rel="stylesheet">
<link href="/public/assets/vendors/leaflet.markercluster/MarkerCluster.css" rel="stylesheet">
<link href="/public/assets/vendors/leaflet.markercluster/MarkerCluster.Default.css" rel="stylesheet">
<link href="/public/assets/vendors/fullcalendar/main.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
<link href="/public/assets/vendors/simplebar/simplebar.min.css" rel="stylesheet">
<link href="/public/assets/css/theme.css" rel="stylesheet" id="style-default">
<link href="/public/assets/css/user.css" rel="stylesheet" id="user-style-default">
' . $bootstrapcss1 . '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
<link href="/public/assets/vendors/swiper/swiper-bundle.min.css" rel="stylesheet">
';
break;
}

if (!empty($headerattribute['additionalcss'])) echo $headerattribute['additionalcss'];
