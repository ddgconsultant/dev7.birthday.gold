<?PHP
 // Start output buffering with gzip compression
 ob_start('ob_gzhandler');
if(empty($ignoretrueheader)) {
echo '<!DOCTYPE html>
<html data-bs-theme="light" lang="'.($pagelang??'en-US').'" dir="ltr" class="h-100">
<head>
';
}
?>

<!-- ======================================================== -->
<!-- START OF PAGESTART -->
<!-- ======================================================== -->
<?PHP
// Prevent caching
#header('Cache-Control: no-store, no-cache, must-revalidate');
#header('Pragma: no-cache');
#header('Expires: 0');
#header("Content-Security-Policy: frame-ancestors 'self'");

// Your PHP code for additional styles or other head elements
if (!isset($additionalstyles)) $additionalstyles = '';


$webservername = gethostname() ?: 'unk';
echo '
<title>'.$display->pagename($pagedata['pagetitle']??'').'</title>
<!-- Metadata -->   
<meta name="ahthn" content="' . strtolower($webservername) . '"> 
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta property="og:site_name" content="Birthday Gold">

';

if ($mode == 'production') {

function getHAProxyIP()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $haProxyIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
        // If there are multiple IPs in the header, take the first one
        $haProxyIP = explode(',', $haProxyIP)[0];
        $ip = trim(str_replace('.',  chr(rand(65, 90)), $haProxyIP));
        return $ip;
    }
    return '0';
}

function getWebserverIP()
{
    if (!empty($_SERVER['SERVER_ADDR'])) {
        $ip = trim(str_replace('.',  chr(rand(65, 90)), $_SERVER['SERVER_ADDR']));
        return $ip;
    }
    return '0';
}

$haProxyIP = getHAProxyIP();
$webserverIP = getWebserverIP();
echo '<meta name="bginfdata" content="' . $haProxyIP . '-' . $webserverIP . '">';



echo '<meta  name="banner" ddg_alt="v3pageheader" content="' . str_repeat("\n", 30) . '
Celebrate You!!

dP       oo            dP   dP             dP                                        dP       dP 
88                     88   88             88                                        88       88 
88d888b. dP 88d888b. d8888P 88d888b. .d888b88 .d8888b. dP    dP    .d8888b. .d8888b. 88 .d888b88 
88   `88 88 88   `88   88   88   `88 88   `88 88   `88 88    88    88   `88 88   `88 88 88   `88 
88.  .88 88 88         88   88    88 88.  .88 88.  .88 88.  .88 -- 88.  .88 88.  .88 88 88.  .88 
88Y8888  dP dP         dP   dP    dP `88888P8 `88888P8 `8888P88 88 `8888P88 `88888P  dP `88888P8 
                                                            .88         .88                      
                                                        d8888P      d8888P     

Copyright (c) ' . date('Y') . '
' . str_repeat("\n", 80) . '">';
} else {
echo '<meta  name="banner" content="


- Non Production environment - [ '.$site.' ]


">';
}


#-------------------------------------------------------------------------------
# HANDLE ROBOTS
#-------------------------------------------------------------------------------
if ($mode != 'production') {
echo '<meta name="robots" content="noindex">
';
}
echo '<meta name="creator" content="php_pageheader-' . date('r') . '">
';


$v=6;
switch($v){
    case 1: $vers='5.3.3'; $styvers='v3'; break;
    case 2: $vers='5.3.0'; $styvers=''; break;    
    case 3: $vers='5.0.0'; $styvers=''; break;
    case 4: $vers='5.0.0'; $styvers='v3'; break;
    case 5: $vers='5.3.3'; $styvers='v3'; break;
    case 6: $vers='5.3.0'; $styvers='v3'; break;
}


$metakeywords=$pagedata['metakeywords']??'Birthday Freebies, Birthday Rewards, Personalized Birthday Offers, Birthday Celebration Map, Exclusive Birthday Perks, Birthday Gold Registration, Birthday Coupons and Vouchers, VIP Birthday Experiences, Year-Round Birthday Deals, Birthday Celebration Itinerary, Unique Birthday Experiences';
$metadescriptions=$pagedata['metadescriptions']??'Unlock a world of birthday perks with birthday.gold! Register, personalize, and celebrate with exclusive offers from over ' . $website['numberofbiz'] . '+  brands. Discover freebies, VIP experiences, and unique celebrations tailored just for you.';

echo '

<meta name="author" content="Birthday.Gold">
<meta name="keywords" content="'.$metakeywords.'">
<meta name="description" content="'.$metadescriptions.'">
<meta property="og:title" content="Birthday Gold - Unlock Exclusive Birthday Perks">
<meta property="og:description" content="Celebrate your birthday with exclusive offers from over ' . $website['numberofbiz'] . '+ brands. Register, personalize, and enjoy unique birthday rewards and experiences tailored just for you.">
<meta property="og:image" content="https://birthday.gold/logo.png">
<meta property="og:url" content="https://birthday.gold">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_US">
<meta property="og:site_name" content="Birthday Gold">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@birthdaygold">
<meta name="twitter:creator" content="@birthdaygold">
<meta name="twitter:title" content="Birthday Gold - Unlock Exclusive Birthday Perks">
<meta name="twitter:description" content="Celebrate your birthday with exclusive offers from over ' . $website['numberofbiz'] . '+ brands. Register, personalize, and enjoy unique birthday rewards and experiences tailored just for you.">
<meta name="twitter:image" content="https://birthday.gold/logo.png">
<meta name="twitter:image:alt" content="Birthday Gold Logo">
<meta name="twitter:domain" content="birthday.gold">
';


echo '

<!-- Favicon -->
<link href="/public/images/favicons/favicon.ico" rel="icon">


<!-- Google Web Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">


<!-- Bootstrap & CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="/public/css/'.$styvers.'/bg_theme.css" rel="stylesheet">  

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
'."


<style>
.dropdown-menu {
    z-index: 1050 !important; /* Ensure the dropdown appears above other elements */
    position: absolute !important; /* Force positioning to prevent clipping */
}

.btn-group {
    position: relative; /* Make sure the dropdown is positioned relative to this */
}

.table-responsive {
    overflow: visible !important; /* Ensure the table's container does not clip the dropdown */
}

</style>
";
