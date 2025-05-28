<?PHP

#include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header2.php'); 
#return;
// Assuming the index page is named 'index.php'

$indexpage=false;
if (strpos(basename($_SERVER['PHP_SELF']), 'index') !== false) {
$indexpage=true;
}

  echo '

  <style>
  /* Default style for the logo */
.navbar-brand img {
  height: 40px;
}

/* Media query for screens with a max-width of 600px */
@media (max-width: 500px) {
  .navbar-brand img {
      height: 30px; /* Adjust the height as needed for smaller screens */
  }
}
</style>
';
if ($indexpage) {

    echo '<nav class="navbar navbar-standard px-3 navbar-top navbar-expand fixed-top navbar-dark" data-navbar-darken-on-scroll="data-navbar-darken-on-scroll"><div class="container">';
    $local_iconcolor='color:#fff !important';
    $local_logoimg='logo_gold-white_1000-x-194.png';
  
} else {
    echo '<nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">';
    $local_iconcolor='color:#333 !important';
    $local_logoimg='birthday.gold_logo.png';
}

?>

            <a class="navbar-brand me-1 me-sm-3" href="/index.php">
              <div class="d-flex align-items-center"><img class="me-2" src="/public/assets/img/icons/spot-illustrations/<?= $local_logoimg; ?>" alt="">
              </div>
            </a>

 
            <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">

<?PHP
####################################################################################################################################################
####################################################################################################################################################
####################################################################################################################################################
### RIGHT SIDE ICONS / AVATAR 
$avatar='/public/images/defaultavatar.png';





####################################################################################################################################################
#####  -_ USER IS LOGGED IN
####################################################################################################################################################
if (!empty($current_user_data)) {
    if (!empty($current_user_data['avatar'])) { $avatar='/'.$current_user_data['avatar']; }



include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/notificationmenu.inc');

if ($account->isadmin()) {
include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/adminmenu.inc');
}

include($_SERVER['DOCUMENT_ROOT'].'/core/nav-usermenu.inc');

} else {

####################################################################################################################################################
####################################################################################################################################################
####################################################################################################################################################
### USER IS NOT LOGGED IN
echo '
<!-- User Not Logged In Links -->
<div class="navbar-nav ms-auto">
  <a class="nav-item nav-link btn btn-primary btn-sm" href="/signup">Sign Up</a>
  <a class="nav-item nav-link btn btn-primary btn-sm" href="/login">Login</a>
</div>

<!-- ===============================================-->
<!--    User Menu/Avatar-->
<!-- ===============================================-->
    <li class="nav-item dropdown"><a class="nav-link pe-0 ps-2" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <div class="avatar avatar-xl">
     <i class="h2 bi bi-person-circle"  style="'.$local_iconcolor.'"></i>

    </div>
  </a>
  <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end py-0" aria-labelledby="navbarDropdownUser">
    <div class="bg-white dark__bg-1000 rounded-2 py-2">
      <a class="dropdown-item fw-bold text-warning" href="/signup"><span class="fas fa-crown me-1"></span><span>Go Lifetime</span></a>

      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="/help">Help</a>

      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="/signup">Sign Up</a>
      <a class="dropdown-item" href="/login">Login</a>
    </div>
  </div>
</li>
';
}
          echo '
            </ul>
          </nav>
          ';
          if (!$indexpage) {
echo '
<div class="my-2">&nbsp;</div>
';
          }
          ?>
<!-- ===============================================-->
<!--   START REAL CONTENT:x-->
<!-- ===============================================-->