<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr" class="h-100">
<head>
<!-- ===============================================-->
<!--   pageheaderinc-->
<!-- ===============================================-->
<?PHP 
$webservername = gethostname() ?: 'unk';
echo '<meta name="ahthn" content="'.strtolower($webservername).'">
';
if ($mode=='production') {

  function getHAProxyIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $haProxyIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
        // If there are multiple IPs in the header, take the first one
        $haProxyIP = explode(',', $haProxyIP)[0];
        $ip=trim(str_replace('.',  chr(rand(65, 90)), $haProxyIP));
        return $ip;
    }
    return '0';
}

function getWebserverIP() {
    if (!empty($_SERVER['SERVER_ADDR'])) {
      $ip=trim(str_replace('.',  chr(rand(65, 90)),$_SERVER['SERVER_ADDR']));
        return $ip;
    }
    return '0';
}

$haProxyIP = getHAProxyIP();
$webserverIP = getWebserverIP();
echo '<meta name="bginfdata" content="'.$haProxyIP.'-'.$webserverIP.'">';



    echo '<meta  name="banner" ddg_alt="v2pageheader" content="'.str_repeat("\n",30).'
    Celebrate You!!
    
    dP       oo            dP   dP             dP                                        dP       dP 
    88                     88   88             88                                        88       88 
    88d888b. dP 88d888b. d8888P 88d888b. .d888b88 .d8888b. dP    dP    .d8888b. .d8888b. 88 .d888b88 
    88   `88 88 88   `88   88   88   `88 88   `88 88   `88 88    88    88   `88 88   `88 88 88   `88 
    88.  .88 88 88         88   88    88 88.  .88 88.  .88 88.  .88 -- 88.  .88 88.  .88 88 88.  .88 
    88Y8888  dP dP         dP   dP    dP `88888P8 `88888P8 `8888P88 88 `8888P88 `88888P  dP `88888P8 
                                                                .88         .88                      
                                                            d8888P      d8888P     
    
    Copyright (c) '.date('Y').'
    '.str_repeat("\n",80).'">';
    } else {
      echo '<meta  name="banner" content="
      
      
      - Non Production environment - 
      
      
      ">';
    
    }


echo '
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

<meta property="og:title" content="Birthday Gold - Unlock Exclusive Birthday Perks" />
<meta property="og:description" content="Celebrate your birthday with exclusive offers from over '.$website['numberofbiz'].'+ '.$website['biznames'].'. Register, personalize, and enjoy unique birthday rewards and experiences tailored just for you." />
<meta property="og:image" content="https://birthday.gold/logo.jpg" />
<meta property="og:url" content="https://birthday.gold" />
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Birthday Gold" />

<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<script data-host="//a.bd.gold" data-dnt="false" src="//a.bd.gold/js/script.js" id="ZwSg9rf6GA" async defer></script>
';

#-------------------------------------------------------------------------------
# HANDLE ROBOTS
#-------------------------------------------------------------------------------

if ($mode!='production') {
echo '<meta name="robots" content="noindex">
';
}
echo '<meta name="creator" content="php_pageheader-'.date('r').'">
';

?>
<meta name="author" content="Birthday.Gold">
<meta name="description" content="Unlock a world of birthday perks with birthday.gold! Register, personalize, and celebrate with exclusive offers from over 250 brands. Discover freebies, VIP experiences, and unique celebrations tailored just for you.">
<meta name="keywords" content="Birthday Freebies, Birthday Rewards, Personalized Birthday Offers, Birthday Celebration Map, Exclusive Birthday Perks, Birthday Gold Registration, Birthday Coupons and Vouchers, VIP Birthday Experiences, Year-Round Birthday Deals, Birthday Celebration Itinerary, Unique Birthday Experiences">

<meta property="og:title" content="Birthday.Gold">
<meta property="og:description" content="Get your birthday freebies">
<meta property="og:image" content="https://cdn.birthday.gold/birthday.gold_logo.png">
<meta property="og:url" content="https://birthday.gold/">


