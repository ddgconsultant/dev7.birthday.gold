<!-- ======================================================== -->
<!-- START OF FOOTER -->
<!-- ======================================================== -->
<?PHP
$forcenostickycontent='';
$additionalstyles .= "
<style>
/* FOOTER styles ======================================== */
/* Custom CSS for the footer */
.footer {
    padding: 20px 0 !important;
    font-size: 0.6rem !important; /* Smaller font size */
    font-weight: 200 !important; /* Lighter font weight */
    color: #6c757d !important; /* Lighter color */
}

.footer-links a {
    color: white !important;
     font-size: 0.95rem !important; /* Smaller font size */
     
    font-weight: 200 !important; /* Lighter font weight */
    text-decoration: none;
}

.footer-links a:hover {
    color: var(--secondary) !important;
    text-decoration: underline;
}

.footer-links li {
    margin-bottom: 0px !important; /* Adjust the bottom margin as needed */
    padding-bottom: 0 !important; /* Ensure no padding */
}

.footer-links li::before {
content: '▸ ';
    color: var(--secondary) !important;
}

/* Custom CSS for icon-item and btn-outline-light */
.icon-item {
    display: inline-block;
    padding: 0.5rem;
    border-radius: 0.25rem;
    text-align: center;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-outline-light {
    border: 1px solid var(--bs-secondary);
    color: var(--bs-secondary);
    background-color: transparent;
    text-decoration: none;
}

.btn-outline-light:hover, .btn-outline-light:focus {
    background-color: var(--bs-secondary);
    color: #fff;
    text-decoration: none;
}
    /* Custom CSS for emaillink */
#emaillink a {
    color: var(--bs-secondary);
    text-decoration: none;
      font-size: 0.875rem !important; 
}

#emaillink a:hover, #emaillink a:focus {
    color: var(--bs-secondary);
    text-decoration: underline;
}

/* Custom CSS for the Submit button */
.btn-custom-secondary {
    background-color: var(--bs-secondary);
    border-color: var(--bs-secondary);
    color: #fff;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-custom-secondary:hover, .btn-custom-secondary:focus {
    background-color: #b38f00; /* Much darker shade of #FFC700 */
    border-color: #b38f00; /* Much darker shade of #FFC700 */
    color: #fff;
}


</style>
";




if (!empty($nostickyfooter)) { 
$additionalstyles .="<style>
/*  NO STICKY FOOTER */
    body {
        display: block !important;
        flex-direction: unset !important;
        min-height: unset !important;
    }
    .main-content {
        flex: unset !important;
    }
</style>
";
$nostickyfooter_tag='nostickyfooter';
} else {
    $nostickyfooter_tag='';
}   

$footeroutput = '';
$footerclasstag = '';

/* if (!empty($stickyfooter)) {
    $footerclasstag = ' class="bg-dark text-white py-4 mt-5 pb-5"';
} else {

$footerclasstag = 'class="bg-dark text-white py-4 pb-5 footer"';
} */
$footerclasstag = 'class="bg-dark text-white py-4 pb-5 footer"';

/// FOOTER TYPE ========================================================================================================

switch ($display_footertype??'default') {
       //------------------------------------
case 'none':
    /// NO FOOTER
        echo '
        <div class="row m-0 p-0"></div>
        <!-- No Footer   -->
        <footer class="bg-dark text-white">         
    </footer>
        ';
        break;

            //------------------------------------
case 'min':
    /// COPYRIGHT FOOTER
        echo '
        <div class="row my-4"></div>
        <!-- Min Footer   -->
       <footer class="bg-dark text-white">
    <div class="container-fluid">
        <div class="container">
            <div class="row pt-2 pb-2">
              <h5 class="text-white fs-3">
    birthday.gold
    <span style="font-size: 0.85rem; color: #aaa;">
    <span class="px-5"></span>
    <a href="/" class="text-secondary text-decoration-none">HOME</a>
    <span class="px-5">|</span>
    <a href="/help" class="text-secondary text-decoration-none">HELP</a>
    <span class="px-5"></span>
    <span >Copyright &copy;'.date('Y').'</span>
    </span>
</h5>

            </div>
        </div>
    </div>
</footer>

        ';
        break;

        //------------------------------------
        //  DEFAULT FOOTER
    default:
echo $forcenostickycontent.'

<div class="row my-4"></div>
<!-- Sticky Footer   -->
<footer ' . $footerclasstag . ' data-style-tag="'.$nostickyfooter_tag.'"> 
        <div class="container-fluid">
    <div class="container">
<div class="row pt-3 pb-3">
';


echo '
<div class="col-md-5 col-lg-4 d-none d-md-block" id="footer-column-1">
    <h4 class="text-uppercase text-white opacity-85 mb-3">Connect</h4>
';
  // display footer monthly message from db or default
$CONTENT_footermessage = $session->get('CONTENT_footermessage', '');
if (empty($CONTENT_footermessage)) {
    $CONTENT_footermessage = $database->fetchOne('select concat(id, "/", `rank`) id, "db" source, content from bg_content where category="footer" and type="message" and `rank`=MONTH(now()) and `status`="active"');
    if (!$CONTENT_footermessage) {
        $CONTENT_footermessage = [
            'id' => "0",
            'source' => "default",
            'content' => 'Help us share the birthday magic - follow, tag, and spread the word about <span class="birthdaygold">@birthday.gold</span>! We want to make birthdays fun, easy and full of free treats for everyone. Follow us for deals and steals.
Tag us to get some birthday love! Tell your friends so they can get in on the action too. Let\'s celebrate YOU together!'
        ];
    }

    $session->set('CONTENT_footermessage', $CONTENT_footermessage);
} else {
    $CONTENT_footermessage['source'] = 'ses';
}
// Determine $fstag based on the length of the content
$contentLength = strlen($CONTENT_footermessage['content']);

if ($contentLength <= 50) {
    $fstag = 'fs-4';
} elseif ($contentLength <= 100) {
    $fstag = 'fs-6';
} elseif ($contentLength <= 200) {
    $fstag = 'fs-8';
} elseif ($contentLength <= 275) {
    $fstag = 'fs-9';
} else {
    $fstag = 'fs-10';
}

echo '<p class="d-none d-md-block text-white '.$fstag.' pe-4 fw-normal" title="['.$contentLength.'] ' . $CONTENT_footermessage['id'] . '|' . $CONTENT_footermessage['source'] . '">' . $CONTENT_footermessage['content'] . '</p>
';



// social media links
echo '
    <div class="icon-group mt-4">
        ' . $display->socialapplink('twitter', 'https://twitter.com/birthday_gold') . '
        ' . $display->socialapplink('facebook', 'https://www.facebook.com/birthdaygold/') . '
        ' . $display->socialapplink('instagram', 'https://www.instagram.com/birthday_gold/') . '
        ' . $display->socialapplink('linkedin', 'https://www.linkedin.com/company/birthdaygold') . '
        ' . $display->socialapplink('tiktok', 'https://www.tiktok.com/@birthday.gold') . '
        ' . $display->socialapplink('youtube', 'https://www.youtube.com/@birthdaygold') . '
        ' . $display->socialapplink('pinterest', 'https://www.pinterest.com/birthdaygold/') . '
    </div>
</div>
';




// site links
echo '<div class="col-md-7 col-lg-5 col-sm-12" id="footer-column-2">
  <div class="row">
    <div class="col-6">
      <h4 class="text-uppercase text-white opacity-85 mb-3 d-none d-md-block">Company</h4>
      <h4 class="text-uppercase text-white opacity-85 mb-3 d-md-none">More Links</h4>
      <ul class="list-unstyled footer-links">
        <li class="mb-1"><a class="link-600" href="/about">About</a></li>
        <li class="mb-1"><a class="link-600" href="/contact">Contact Us</a></li>
        <li class="mb-1"><a class="link-600" href="/careers">Careers</a></li>
        <li class="mb-1"><a class="link-600" href="/discover">Discover</a></li>
        <li class="mb-1"><a class="link-600" href="/legalhub/">Legal</a></li>
        <li class="mb-1"><a class="link-600" href="/legalhub/terms">Terms & Conditions</a></li>
        <li class="mb-1"><a class="link-600" href="/legalhub/privacy">Privacy Policy</a></li>
      </ul>
    </div>
    <div class="col-6">
      <h4 class="text-uppercase text-white opacity-85 mb-3 d-none d-md-block">Account</h4>
       <h4 class="text-uppercase text-white opacity-85 mb-3 d-md-none">&nbsp;</h4>
      <ul class="list-unstyled footer-links">
        <li class="mb-1"><a class="link-600" href="/plans">Plans / Pricing</a></li>
        <li class="mb-1"><a class="link-600" href="/how-it-works">How It Works</a></li>
        <li class="mb-1"><a class="link-600" href="/register?giftcertificate">Gift Certificates</a></li>
        <li class="mb-1"><a class="link-600" href="/faq">FAQs</a></li>
        <li class="mb-1"><a class="link-600" href="/help">Help</a></li>
        <li class="mb-1"><a class="link-600" href="/system-status" target="sswindow">System Status</a></li>
      </ul>
    </div>
  </div>
</div>

';



// newsletter and mail us section
echo '
<div class="col-lg-3 col-sm-12 d-none d-lg-block" id="footer-column-3">
';

    if (!strpos($website['fulluri']['uri'], 'myaccount') &&  !strpos($website['fulluri']['uri'], 'mail')) {
        echo ' 
            <h4 class="text-light mb-2">Newsletter<span class="text-dark"> / <a class="text-dark" href="/blog/">Blog</a></span></h4>
            <form action="/newsletter" method="post" class="mb-5">
                ' . $display->inputcsrf_token() . '
                <div class="input-group">
                    <input type="email" class="form-control p-2 border-0" name="email" id="newsletter_email" placeholder="Your Email Address">
                    <button class="btn btn-sm btn-custom-secondary text-black" type="submit">Submit</button>
                </div>                          
            </form>
            <h6 class="text-white mt-4 mb-2">Mail Us</h6>
            <div class="d-flex">
                <p class="mb-2"><i class="bi bi-envelope-fill me-3 text-white"></i><span id="emaillink"></span></p>
            ';
    } else {
        echo  ' 
            <h4 class="text-white mb-2">Mail Us</h4>
            <div class="d-flex">
                <p class="mb-2"><i class="bi bi-envelope-fill me-3 text-white"></i><span id="emaillink"></span></p>
';
    }


    echo '
</div>
</div>
</div>
</div>
</div>
</footer>
';


echo '
<!-- ===============================================-->
<!--    footer attribute scripts -->
<!-- ===============================================-->
';

$footeroutput .= '
<script src="/public/js/email.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        mail2("info", "ibtrdhyag?lod", -2, "", "Click to Email us");
    });
</script>
';
$emailnewslettercontentdisplayed = true;
echo  $footeroutput;
unset($footeroutput);
break;
}




######################################### include($_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/footerjs.inc');



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
<!-- <script src="/public/assets/vendors/popper/popper.min.js"></script> -->
 <script src="/public/assets/vendors/anchorjs/anchor.min.js"></script>
 <script src="/public/assets/vendors/is/is.min.js"></script> 
<?PHP
if (!empty($enableflatpickr)) 
echo '<script src="/public/assets/js/flatpickr.js"></script>
';


if (!empty($fascript) || (isset($nofa) && $nofa===false)) {
echo ' <script src="/public/assets/vendors/fontawesome/all.min.js"></script>
';
}

if ($mode!='dev') {
$enablechat=true;
} else $enablechat=false;
if ($enablechat===false) $forcefalseenablechat=true;
include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer_chatsystem.inc');
echo $footeroutput;


?>



<!-- ===============================================-->
<!--    JavaScripts-->
<!-- ===============================================-->
<script src="/public/assets/vendors/lodash/lodash.min.js"></script>
<!-- we no longer use polyfill -->
<script src="/public/assets/vendors/list.js/list.min.js"></script>
<script src="/public/assets/vendors/simplebar/simplebar.min.js"></script>
<script src="/public/assets/js/theme.js"></script>

<?PHP

// handle $session->set('footerjs_dismiss_alert', 'true');
if ($session->get('footerjs_dismiss_alert') === 'true' || isset($dismissible_user_notification) ) {
    echo '
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".alert .btn-close").forEach(function(button) {
        button.addEventListener("click", function() {
            // Get the encrypted message ID from the data attribute
            const messageId = button.getAttribute("data-message-id");
            
            if (messageId) { // Only send a request if messageId is present
                // Send an AJAX request to the PHP script
                fetch("/siteactions/dismissmessage?midtag=" + encodeURIComponent(messageId), {
                    method: "GET",
                    headers: {
                        "Content-Type": "application/json"
                    }
                })
                .then(response => response.text())
                .then(data => {
                    console.log("Message status updated:", data);
                })
                .catch(error => console.error("Error:", error));
            }
        });
    });
});
</script>';
}

if (empty($override_bstooltips)) {
echo "
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize all modals
    var modalTriggerList = [].slice.call(document.querySelectorAll('.modal'));
    var modalList = modalTriggerList.map(function (modalTriggerEl) {
        return new bootstrap.Modal(modalTriggerEl);
    });

    // Initialize all accordions (if needed)
    var accordionTriggerList = [].slice.call(document.querySelectorAll('.accordion'));
    var accordionList = accordionTriggerList.map(function (accordionTriggerEl) {
        return new bootstrap.Collapse(accordionTriggerEl, {
            toggle: false
        });
    });

    // Initialize any other Bootstrap components here
});
</script>
";
}

$session->unset('footerjs_dismiss_alert');





$vers='5.3.3';
echo '
<script src="/public/assets/vendors/typed.js/typed.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@'.$vers.'/dist/js/bootstrap.bundle.min.js"></script>
'. "
<script>
// Toggle body scroll lock when menu opens
const menu = document.querySelector('.bg_mega-menu');
const menuButton = document.querySelector('#menu-button');

menuButton.addEventListener('click', function() {
  menu.classList.toggle('menu-open');
  document.body.classList.toggle('scroll-lock');
});
</script>
";

if (!empty($footerattribute['bottomfooter']))  {
    echo ' 
    <!-- ===============================================-->
    <!--  footerattribute[bottomfooter]  -->
    <!-- ===============================================-->
    ';
   echo  $footerattribute['bottomfooter'];
   unset($footerattribute['bottomfooter']);
  }


switch ($mode){
case 'dev':
    echo '<script data-host="http://a.bd.gold" data-dnt="false" data-mode="dev" src="https://a.bd.gold/js/script.js" id="ZwSg9rf6GA" async defer></script>';
    break;
default:
    echo '<script data-host="http://a.bd.gold" data-dnt="false" src="https://a.bd.gold/js/script.js" id="ZwSg9rf6GA" async defer></script>';
    break;

}


// Cookie consent banner logic
// Check if cookie preferences have been set
$cookiePreferencesSet = isset($_COOKIE['bdgold_lastSetDate']);


// Include cookie banner if preferences aren't set
if (!$cookiePreferencesSet) {

        // Set the cookie banner version if not already defined elsewhere
        if (!isset($cookiebannerversion)) {
            // Random selection between version 1 and 2
            $cookiebannerversion = rand(1, 2);
        }

    // Include the appropriate cookie banner based on version
    $bannerFile = 'bg_cookiebanner' . $cookiebannerversion . '.inc';
    include($_SERVER['DOCUMENT_ROOT'] . '/core/components/' . $website['ui_version'] . '/' . $bannerFile);
}


echo '
</body>
</html>';