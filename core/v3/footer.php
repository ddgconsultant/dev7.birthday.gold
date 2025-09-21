<!-- ===============================================-->
<!--    START of BLADE FOOTER-->
<!-- ===============================================-->
    <?PHP
$footeroutput='';
$copyrighttimertag='"';
#print_r($website['fulluri']);



include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer2.inc');
return;


if (!strpos($website['fulluri']['uri'], 'myaccount-admin')) {
$copyrighttimertag='wow fadeIn" data-wow-delay="0.1s"';
#$session->set('CONTENT_footermessage', '');
$CONTENT_footermessage=$session->get('CONTENT_footermessage', '');
if (empty($CONTENT_footermessage)) {
    $CONTENT_footermessage=  $database->fetchOne('select concat(id, "/", `rank`) id, "db" source, content from bg_content where category="footer" and type="message" and `rank`=MONTH(now()) and `status`="active"');
if (empty($row)) {$row['id']="0";$row['source']="default";$row['content']='Help us share the birthday magic - follow, tag, and spread the word about <span class="birthdaygold">@birthday.gold</span>! We want to make birthdays fun, easy and full of free treats for everyone. Follow us for deals and steals.
Tag us to get some birthday love! Tell your friends so they can get in on the action too. Let\'s celebrate YOU together!';}
    $session->set('CONTENT_footermessage', $CONTENT_footermessage);    
} else {
    $CONTENT_footermessage['source']='ses';
}
$footeroutput .= '
<style>
.custom-link {
    color:#fff;
}
</style>
<!-- Footer Start -->
<div class="container-fluid bg-dark text-light footer my-4 mb-0 py-4 no-print" >
<div class="container footer">
    <div class="row g-5 footer">
        <div class="col-lg-6 col-md-6">
            <h4 class="text-white mb-2">Connect</h4>
            <p class="d-none d-md-block text-white" title="'.$CONTENT_footermessage['id'].'|'.$CONTENT_footermessage['source'].'">'.$CONTENT_footermessage['content'].'</p>

            <div class="d-flex pt-2">
                <a class="btn btn-square btn-outline-light me-1" href="https://twitter.com/birthday_gold" target="_socialmedia"><i class="bi bi-twitter-x"></i></a>
                <a class="btn btn-square btn-outline-light me-1" href="https://www.facebook.com/birthdaygold/" target="_socialmedia"><i class="bi bi-facebook"></i></a>
                <a class="btn btn-square btn-outline-light me-1" href="https://www.instagram.com/birthday_gold/" target="_socialmedia"><i class="bi bi-instagram"></i></a>
                <a class="btn btn-square btn-outline-light me-1" href="https://www.linkedin.com/company/birthdaygold" target="_socialmedia"><i class="bi bi-linkedin"></i></a>
                <a class="btn btn-square btn-outline-light me-1" href="https://www.tiktok.com/@birthday.gold" target="_socialmedia"><i class="bi bi-tiktok"></i></a>
                <a class="btn btn-square btn-outline-light me-1" href="https://www.youtube.com/@birthdaygold" target="_socialmedia"><i class="bi bi-youtube"></i></a>
                <a class="btn btn-square btn-outline-light me-0" href="https://www.pinterest.com/birthdaygold/" target="_socialmedia"><i class="bi bi-pinterest"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
        <h4 class="text-light mb-2">Quick Links</h4>
        <a class="btn btn-link custom-link" href="/about"><i class="bi bi-chevron-right"></i> About Us</a>
        <a class="btn btn-link custom-link" href="/careers"><i class="bi bi-chevron-right"></i> Careers</a>
        <a class="btn btn-link custom-link" href="/contact"><i class="bi bi-chevron-right"></i> Contact Us</a>
        <a class="btn btn-link custom-link" href="/faq"><i class="bi bi-chevron-right"></i> FAQs</a>
        <a class="btn btn-link custom-link" href="/terms"><i class="bi bi-chevron-right"></i> Terms & Conditions</a>
        <a class="btn btn-link custom-link" href="/privacy"><i class="bi bi-chevron-right"></i> Privacy Policy</a>
        <a class="btn btn-link custom-link" href="/legal"><i class="bi bi-chevron-right"></i> Legal</a> 
    </div>
    
';

$footeroutput .= ' 
        <div class="col-lg-3 col-md-6">
        ';


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
                <p class="mb-2"><i class="bi bi-envelope-fill me-3"></i><span id="emaillink"></span></p>
            ';
        } else {
        $footeroutput .= ' 
            <h4 class="text-white mb-2">Mail Us</h4>
            <div class="d-flex">
                <p class="mb-2"><i class="bi bi-envelope-fill me-3"></i><span id="emaillink"></span></p>
';
}
$footeroutput .= '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        mail2("info", "ibtrdhyag?lod", -2, "", "Click to Email us");
    });
</script>
';

$footeroutput .= '                   
            </div>
        </div>
    </div>
</div>
</div>
<!-- Footer End -->
';
}

$copyrightbar = '&copy; '.date('Y').' <a href="/">birthday.gold</a>, All Right Reserved.
';

$enablechat=true;
$timer->stop();
if ($mode == 'dev' || strpos($website['fulluri']['uri'], 'myaccount-admin') || ($mode=='production' && $app->testipcheck())) {
$enablechat=false;
$copyrightbar .= '<a href="/client-stats" target="_blank"><small> - ' . $timer->getElapsedTime() . '</small></a>';
}

if (empty($nocopyrightbar)) {
    $footeroutput .= '<!-- Copyright Start -->
    <div class="container-fluid copyright text-light py-4 '.$copyrighttimertag.'>
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                '.$copyrightbar.'
                ';
}

$footeroutput .= '
        </div>
    </div>
</div>
</div>
<!-- Copyright End -->
';


if (empty($local_suppressfooterupicon)) $local_suppressfooterupicon=true;
if (!$enablechat && !$display->isappledevice() && !$local_suppressfooterupicon)   {
$footeroutput .=  '
<!-- Back to Top --> 
<a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top no-print"><i class="bi bi-arrow-up"></i></a>
';
}


$footeroutput .=   '
<!-- JavaScript Libraries -->

<script src="/public/lib/wow/wow.min.js" language="javascript"></script>
<script src="/public/lib/easing/easing.min.js" language="javascript"></script>
<script src="/public/lib/waypoints/waypoints.min.js" language="javascript"></script>
<script src="/public/lib/owlcarousel/owl.carousel.min.js" language="javascript"></script>
<script src="/public/js/email.js" language="javascript"></script>
<script src="/public/js/site-main.js" language="javascript"></script>

';

if (isset($footerattribute['postfooter'])) {
$footeroutput .=  $footerattribute['postfooter'];
unset( $footerattribute['postfooter']);
}

if (empty($enablechat)) $forcefalseenablechat=true;
include($_SERVER['DOCUMENT_ROOT'].'/core/blade/footer_chatsystem.inc');


?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
$('#showlinks, #showqrcodes').click(function(e){
    e.preventDefault();
});

$('#showlinks').click(function(){
    console.log('showlinks clicked');
    $('.qrlink').addClass('d-none');
    $('.applink').removeClass('d-none');
});

$('#showqrcodes').click(function(){
    console.log('showqrcodes clicked');
    $('.applink').addClass('d-none');
    $('.qrlink').removeClass('d-none');
});


});
</script>

<?PHP

if (isset($footerattribute['rawfooter'])) {
    $footeroutput='    
<script src="/public/lib/wow/wow.min.js" language="javascript"></script>
<script src="/public/lib/easing/easing.min.js" language="javascript"></script>
<script src="/public/lib/waypoints/waypoints.min.js" language="javascript"></script>
<script src="/public/lib/owlcarousel/owl.carousel.min.js" language="javascript"></script>
<script src="/public/js/email.js" language="javascript"></script>
<script src="/public/js/site-main.js" language="javascript"></script>';
if (isset($footerattribute['postfooter'])) {
    $footeroutput .=  $footerattribute['postfooter'];    
   unset($footerattribute['postfooter']);
    }
}

$footeroutput .=  '
</div>
</body>
</html>';
echo  $footeroutput;