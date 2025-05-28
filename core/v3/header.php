<?PHP

include($BASEDIR.'/core/'.$website['ui_version'].'/header2.inc');
return;


///////------------------------------ does not process anything below ---------------------------///

$headercontent='<!DOCTYPE html>
<html lang="en">

<head>
<title>birthday.gold'.$display->pagename().'</title>
<!-- Meta tags -->
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
';

$webservername = gethostname() ?: 'unk';
echo '<meta name="ahthn" content="'.strtolower($webservername).'">
';


#-------------------------------------------------------------------------------
# HANDLE ROBOTS
#-------------------------------------------------------------------------------
if ($mode!='production') {
$headercontent.='<meta name="robots" content="noindex">';
}


$headercontent.='
<meta name="description" content="Unlock a world of birthday perks with birthday.gold! Register, personalize, and celebrate with exclusive offers from over ' . $website['numberofbiz'] . '+ brands. Discover freebies, VIP experiences, and unique celebrations tailored just for you.">
<meta name="keywords" content="Birthday Freebies, Birthday Rewards, Personalized Birthday Offers, Birthday Celebration Map, Exclusive Birthday Perks, Birthday Gold Registration, Birthday Coupons and Vouchers, VIP Birthday Experiences, Year-Round Birthday Deals, Birthday Celebration Itinerary, Unique Birthday Experiences">


<!-- Favicon -->
<link href="/public/images/favicons/favicon.ico" rel="icon">

<!-- Google Web Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"> 



<!-- Stylesheets -->
<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  -->

<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet"> -->
<!-- <script src="https://kit.fontawesome.com/ca31372e83.js" crossorigin="anonymous"></script> -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<!-- <link href="/public/lib/animate/animate.min.css" rel="stylesheet"> -->
<link href="/public/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
 <link href="/public/css/bootstrap5.0.0.min.css" rel="stylesheet"> 
 <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
-->
<link href="/public/css/style.css?43" rel="stylesheet">

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js" language="javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.10.2/umd/popper.min.js" language="javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" language="javascript"></script>
';  
#-------------------------------------------------------------------------------
# HANDLE ADDITIONAL CSS/HEADER INFO
#-------------------------------------------------------------------------------
if (isset(  $headerattribute['additionalcss'])) {
$headercontent.= $headerattribute['additionalcss'];
}

$headercontent.='
</head>

<body class="d-flex flex-column min-vh-100">
';

if (isset($headerattribute['rawheader'])) {echo $headercontent; return;}
$headercontent.='
<!-- Spinner page loading Start -->
<div id="spinner" class="show bg-white position-fixed translate-middle w-100 flex-column min-vh-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
<div class="spinner-grow text-primary" role="status"></div>
</div>
<!-- Spinner End -->
';


#-------------------------------------------------------------------------------
# HEADER MENU
#-------------------------------------------------------------------------------
$headercontent.='

<!-- Navbar Start -->
<nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0">
<a href="/" class="navbar-brand d-flex align-items-center border-end px-4 px-lg-5">
<h2 class="m-0"><img src="//cdn.birthday.gold/public/images/logo/birthday.gold_logo.png"></h2>
</a>
<button type="button" class="navbar-toggler mx-auto" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarCollapse">
<div class="navbar-nav ms-auto p-4 p-lg-0">
<a href="/" class="nav-item nav-link active">Home</a>
<a href="/how" class="nav-item nav-link">How It Works</a>
<a href="/plans" class="nav-item nav-link">Plans</a>

';

#   
if (!empty($current_user_data)) {
#  breakpoint($current_user_data);

$headerlink='<a href="/myaccount" class="btn btn-primary py-3 px-4 pe-5 d-none d-lg-block nav-link-loginbtn">';
$headericon='bi bi-house-door-fill';
if (!empty($current_user_data['status'])) {
if ($current_user_data['status']!='active' || $current_user_data['status']=='giftcertificate') {
$headerlink='<a href="#" class="btn btn-primary py-3 px-4 pe-5 d-none d-lg-block nav-link-loginbtn disabled">';
$headericon='bi bi-wrench-adjustable-circle';
}
}

$headercontent.=' 
<a href="/logout" class="nav-item nav-link nav-link-login">Logout</a>
'.$headerlink.'
<div class="row align-items-center no-gutters">
<div class="col-auto p-0 m-0 ms-4">
<i class="'.$headericon.'"></i>
</div>
<div class="col">
Welcome<br>'.$current_user_data['first_name'].'
</div>
</div>
</a>



<a href="/myaccount" class="nav-item nav-link d-lg-none">My Account</a>
';
}                            
else {                       
$headercontent.='  
<a href="/login" class="nav-item nav-link nav-link-login">Login</a>
<a href="/signup" class="btn btn-primary py-4 px-lg-5 d-none d-lg-block nav-link-loginbtn">Sign Up<i class="bi bi-arrow-right-circle-fill pt-2 pb-0 ms-3"></i></a>
<a href="/signup" class="nav-item nav-link d-md-none d-sm-block">Sign Up</a>
';

}
$headercontent.='

</div>
</div>
</nav>
<!-- Navbar End -->
';
echo     $headercontent;