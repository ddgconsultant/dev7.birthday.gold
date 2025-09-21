<?PHP
$footeroutput='';
?>
<!-- ============================================-->
<!-- <section> begin ============================-->

               
<?PHP 
$CONTENT_footermessage=$session->get('CONTENT_footermessage', '');
if (empty($CONTENT_footermessage)) {
    $CONTENT_footermessage=  $database->fetchOne('select concat(id, "/", `rank`) id, "db" source, content from bg_content where category="footer" and type="message" and `rank`=MONTH(now()) and `status`="active"');
if (empty($row)) {
  $row['id']="0";
  $row['source']="default";
  $row['content']='Help us share the birthday magic - follow, tag, and spread the word about <span class="birthdaygold">@birthday.gold</span>! We want to make birthdays fun, easy and full of free treats for everyone. Follow us for deals and steals.
Tag us to get some birthday love! Tell your friends so they can get in on the action too. Let\'s celebrate YOU together!';}
    $session->set('CONTENT_footermessage', $CONTENT_footermessage);    
} else {
    $CONTENT_footermessage['source']='ses';
}


$footerclasstag='';
if (!empty($stickyfooter))
$footerclasstag=' class="footer mt-auto py-3"';
#$footerclasstag=' class="footer sticky-footer mt-auto py-3"';

echo '
</div>
</main>
<footer '.$footerclasstag.'>
<style>
html, body {
    height: 100%;
    margin: 0;
}

body {
    display: flex;
    flex-direction: column;
}

main {
    flex: 1;
}
</style>
';
?>
      <section class="bg-dark pt-8 pb-4" data-bs-theme="light">

        <div class="container">
          <div class="position-absolute btn-back-to-top bg-dark"><a class="text-600" href="#banner" data-bs-offset-top="0">
            <span class="fas fa-chevron-up" data-fa-transform="rotate-45"></span></a></div>
          <div class="row">
            <div class="col-lg-4">
              <h5 class="text-uppercase text-white opacity-85 mb-3">Connect</h5>
 
<?PHP echo '   <p class="text-600 d-none d-md-block text-white" title="'.$CONTENT_footermessage['id'].'|'.$CONTENT_footermessage['source'].'">'.$CONTENT_footermessage['content'].'</p>
';
?>
             
              <div class="icon-group mt-4">
                
                <a class="icon-item btn-outline-light me-1" href="https://twitter.com/birthday_gold" target="_socialmedia"><i class="bi bi-twitter-x"></i></a>
                <a class="icon-item btn-outline-light me-1" href="https://www.facebook.com/birthdaygold/" target="_socialmedia"><i class="bi bi-facebook"></i></a>
                <a class="icon-item btn-outline-light me-1" href="https://www.instagram.com/birthday_gold/" target="_socialmedia"><i class="bi bi-instagram"></i></a>
                <a class="icon-item btn-outline-light me-1" href="https://www.linkedin.com/company/birthdaygold" target="_socialmedia"><i class="bi bi-linkedin"></i></a>
                <a class="icon-item btn-outline-light me-1" href="https://www.tiktok.com/@birthday.gold" target="_socialmedia"><i class="bi bi-tiktok"></i></a>
                <a class="icon-item btn-outline-light me-1" href="https://www.youtube.com/@birthdaygold" target="_socialmedia"><i class="bi bi-youtube"></i></a>
                <a class="icon-item btn-outline-light me-0" href="https://www.pinterest.com/birthdaygold/" target="_socialmedia"><i class="bi bi-pinterest"></i></a>

            </div>
            </div>
            <div class="col ps-lg-6 ps-xl-8">
              <div class="row mt-5 mt-lg-0">
                <div class="col-6 col-md-3">
                  <h5 class="text-uppercase text-white opacity-85 mb-3">Company</h5>
                  <ul class="list-unstyled">
                    <li class="mb-1"><a class="link-600" href="/about">About</a></li>
                    <li class="mb-1"><a class="link-600" href="/contact">Contact Us</a></li>
                    <li class="mb-1"><a class="link-600" href="/careers">Careers</a></li>
                    <li class="mb-1"><a class="link-600" href="/discover">Discover</a></li>
                    <li class="mb-1"><a class="link-600" href="/legal">Legal</a></li>
                    <li class="mb-1"><a class="link-600" href="/terms">Terms & Conditions</a></li>
                    <li class="mb-1"><a class="link-600" href="/privacy">Privacy Policy</a></li>
                  </ul>
                </div>
                <div class="col-6 col-md-3">
                  <h5 class="text-uppercase text-white opacity-85 mb-3">Account</h5>
                  <ul class="list-unstyled">
                    <li class="mb-1"><a class="link-600" href="/plans">Plans / Pricing</a></li>
                    <li class="mb-1"><a class="link-600" href="/how">How It Works</a></li>
                    <li class="mb-1"><a class="link-600" href="/register?giftcertificate">Gift Certificates</a></li>
                    <li class="mb-1"><a class="link-600" href="/faq">FAQs</a></li>
                    <li class="mb-1"><a class="link-600" href="/help">Help</a></li>
                    <li class="mb-1"><a class="link-600" href="/company/systems/">System Status</a></li>
                  </ul>
                </div>
                <div class="col mt-5 mt-md-0">
                
                
<?PHP
    if (!strpos($website['fulluri']['uri'], 'myaccount') &&  !strpos($website['fulluri']['uri'], 'mail') ) {
            $footeroutput .= ' 
            <h4 class="text-light mb-2">Newsletter</h4>
            <form action="/newsletter" method="post" class="mb-5">
                '. $display->inputcsrf_token().'
                <div class="input-group">
                    <input type="text" class="form-control p-2 border-0" name="email" id="email" value="" placeholder="Your Email Address">
                    <button class="btn btn-sm btn-primary text-black" type="submit">Submit</button>
                </div>                          
            </form>
            <h6 class="text-white mt-4 mb-2">Mail Us</h6>
            <div class="d-flex">
                <p class="mb-2"><i class="bi bi-envelope-fill me-3 text-white"></i><span id="emaillink"></span></p>
            ';
        } else {
        $footeroutput .= ' 
            <h4 class="text-white mb-2">Mail Us</h4>
            <div class="d-flex">
                <p class="mb-2"><i class="bi bi-envelope-fill me-3 text-white"></i><span id="emaillink"></span></p>
';
}

$footeroutput .= '

<script src="/public/js/email.js" language="javascript"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        mail2("info", "ibtrdhyag?lod", -2, "", "Click to Email us");
    });
</script>
';
$emailnewslettercontentdisplayed=true;
echo    $footeroutput ;
unset($footeroutput);
?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- end of .container-->

      </section>
  </footer>
<!-- <section> close ============================-->
<!-- ============================================-->




      

<!-- ============================================-->
<!-- <section> begin ============================-->
      <section class="py-0 bg-dark" data-bs-theme="light">
        <div>
          <hr class="my-0 text-600 opacity-25" />

<?PHP
#include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.inc');
?>

        </div>
        <!-- end of .container-->

      </section>
<!-- <section> close ============================-->
<!-- ============================================-->


     
    </main>
<!-- ===============================================-->
<!--    End of Main Content-->
<!-- ===============================================-->


  
<?PHP
include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/sitecustomizer.inc');
?>




<?PHP
if (!empty($footerattribute['postfooter'] )) {
echo '
<!-- ===============================================-->
<!--    $footerattribute script -->
<!-- ===============================================-->
';
  echo $footerattribute['postfooter'] ;  
  unset($footerattribute['postfooter']);
}


include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footerjs.inc');


?>

<!-- ===============================================-->
<!--    JavaScripts-->
<!-- ===============================================-->

  
  <script src="/public/assets/vendors/typed.js/typed.js"></script>

    
</body>
</html>