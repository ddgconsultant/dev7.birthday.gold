
  <?PHP
  if (!empty($footerattribute['postfooter']))  {
    echo ' 
    <!-- ===============================================-->
    <!--    JavaScript from footerjs - footerattribute[postfooter]  -->
    <!-- ===============================================-->
    ';
   echo  $footerattribute['postfooter'];
   unset($footerattribute['postfooter']);
  }
   ?>


  <!-- ===============================================-->
  <!--    JavaScripts - footerjs -->
  <!-- ===============================================-->
  <script src="/public/assets/vendors/popper/popper.min.js"></script>
  <script src="/public/assets/vendors/anchorjs/anchor.min.js"></script>
  <script src="/public/assets/vendors/is/is.min.js"></script> 
<?PHP
if (!empty($enableflatpickr)) 
echo '<script src="/public/assets/js/flatpickr.js"></script>
';


if (empty($nofa)) {
  echo ' <script src="/public/assets/vendors/fontawesome/all.min.js"></script>
  ';
}




if ($enablechat===false) $forcefalseenablechat=true;
include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer_chatsystem.inc');
echo $footeroutput;


?>

 
  <script src="/public/assets/vendors/lodash/lodash.min.js"></script>
<!-- we no longer use polyfill -->
  <script src="/public/assets/vendors/list.js/list.min.js"></script>
  <script src="/public/assets/js/theme.js"></script>
  <script src="//code.jquery.com/jquery-3.6.0.min.js"></script>
