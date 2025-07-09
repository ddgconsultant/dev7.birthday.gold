<?PHP
$addClasses[]='Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$name = '';
$email = '';
$subject = '';
$message = '';
$errormessage = '';
$continue = false;


#-------------------------------------------------------------------------------
# HANDLE THE CONTACT FORM SUBMISSION
#-------------------------------------------------------------------------------
$error = false;
if (($formdata = $app->formposted())) {
  $continue = false;
  $errormessage = '<div class="bg-danger p-3 mb-4 text-white">Your contact information is invalid.</div>';
 // Retain the submitted values for subject and message
 $submitted_subject = htmlspecialchars($_REQUEST['subject'] ?? '', ENT_QUOTES, 'UTF-8');
 $submitted_message = htmlspecialchars($_REQUEST['message'] ?? '', ENT_QUOTES, 'UTF-8');

  
  if (!$app->validateCaptcha()) {
    $continue = false;
    $error = true;
    $errormessage = '<div class="bg-danger p-3 mb-4 text-white">The Recaptcha Challenge is incorrect.</div>';

   
    // Track the failed attempt
    $_REQUEST['bg-pageerror'] = $errormessage;
    session_tracking('contact-captcha-fail', $_REQUEST);
    

    // Redirect back to the page with the retained values
    goto displaypage;
}
  // Get form data
  $requiredfields = ['email', 'message'];
  foreach ($requiredfields as $field) {
    if (isset($formdata[$field])) {
      $$field = trim($formdata[$field]);
    }
  }

  if (empty($email) || empty($message)) {
    $continue = false;
    $error = true;
    $errormessage = '<div class="bg-danger p-3 mb-4 text-white">You must provide all the required fields.</div>';
    goto displaypage;
  }

  // Here you could add more checks, such as checking if the email is valid
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errormessage = '<div class="bg-danger p-3 mb-4 text-white">Invalid email address.</div>';
    $continue = false;
    $error = true;
    goto displaypage;
  }

  // Process the form (e.g., send an email, save to a database, etc.)
  // If everything goes well, redirect the user
  
#$message['validatelink']=$link['shorturl'];
$messageinput['from']=[$email, $name];
$messageinput['to']='Member Support birthday.gold';
$messageinput['toemail']='membersupport@birthday.gold';
if ($subject!='') $subject='<br>SUBJECT: '.$subject;
$messageinput['body']='DATE: '.date('r').'<br>ID: '.session_id().$subject.'<hr>'.$message;
$messageinput['notification']='DATE: '.date('r')."\n".'ID: '.session_id().$subject."\n".$message;
$result=$mail->sendOnlineContactForm($messageinput);
$errormessage = '<div class="bg-success p-3 mb-4 text-white"><i class="bi bi-check-circle-fill me-2"></i>Your message was sent to our customer service team.</div>';

$system->postToRocketChat('* An Online Contact Form Message was sent: *'."\n".$messageinput['notification'], '#BG-MemberSupportTeam');
#$system->postToRocketChat('An Online Contact Message was sent: '.$messageinput['body'], '@Richard');
}

if (strpos($errormessage, '<button')===false && $errormessage!='') {
$errormessage=str_replace('</div>', '<button type="button" class="close position-absolute top-0 end-0 m-2" aria-label="Close" onclick="this.parentElement.style.display=\'none\'">
<span aria-hidden="true">&times;</span></button></div>', $errormessage );
} 


#-------------------------------------------------------------------------------
# DISPLAY THE CONTACT FORM
#-------------------------------------------------------------------------------
displaypage:

// Add page-specific styles
$additionalstyles .= '
<style>
    /* Additional margin for content header */
    .content-header-dark {
        margin-bottom: 3rem;
    }
    
    
    body {
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    
    .page-wrapper {
        flex: 1 0 auto;
        padding: 0 0 2rem 0;
    }
    
    /* Footer specific styles */
    footer, .footer {
        flex-shrink: 0;
        margin-top: auto;
    }
    
    .display-6 {
        font-size: 1.5rem !important;
        margin-bottom: 1rem !important;
    }
    
    .py-6 {
        padding-top: 2rem !important;
        padding-bottom: 2rem !important;
    }
    
    .form-floating {
        margin-bottom: 0.75rem;
    }
    
    .form-control {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
    
    .form-floating > label {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
    }
    
    .form-floating > .form-control {
        height: calc(2.5rem + 2px);
    }
    
    .form-floating > textarea.form-control {
        height: 100px !important;
    }
    
    .btn {
        padding: 0.5rem 1.5rem !important;
        font-size: 0.9rem;
    }
    
    /* Remove horizontal padding from card-body */
    .card-body {
        padding-left: 0;
        padding-right: 0;
    }
    
    /* Remove container padding on contact page */
    .contact-content .card {
        margin: 0;
    }
    
    /* Ensure form fills the card */
    .contact-content form {
        padding: 1.5rem;
    }
    
    /* Remove default Bootstrap card spacing */
    .contact-content .card-header {
        border-radius: 0;
        border: none;
    }
    
    .contact-content .card-body {
        padding: 0;
    }
    
    /* Adjust row margins */
    .contact-content .row {
        margin: 0;
    }
    
    h6 {
        font-size: 0.875rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .g-5 {
        --bs-gutter-y: 1.5rem !important;
        --bs-gutter-x: 1.5rem !important;
    }
    
    .g-3 {
        --bs-gutter-y: 0.75rem !important;
        --bs-gutter-x: 0.75rem !important;
    }
    
    /* Social Media Links */
    .social-link {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 2px solid;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        text-decoration: none;
        color: white;
    }
    
    /* Social Media Brand Colors */
    .social-link[title="Twitter"] {
        background: #000000;
        border-color: #000000;
    }
    
    .social-link[title="Facebook"] {
        background: #1877f2;
        border-color: #1877f2;
    }
    
    .social-link[title="Instagram"] {
        background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        border-color: #dc2743;
    }
    
    .social-link[title="LinkedIn"] {
        background: #0077b5;
        border-color: #0077b5;
    }
    
    .social-link[title="TikTok"] {
        background: #000000;
        border-color: #000000;
    }
    
    .social-link[title="YouTube"] {
        background: #ff0000;
        border-color: #ff0000;
    }
    
    .social-link[title="Pinterest"] {
        background: #bd081c;
        border-color: #bd081c;
    }
    
    .social-link:hover {
        transform: translateY(-2px) scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        opacity: 0.9;
    }
    
    /* Social Media Card Spacing */
    .social-media-card {
        padding: 2rem 1rem;
    }
    
    @media (min-width: 768px) {
        .social-media-card {
            padding: 3rem 2rem;
        }
    }
        </style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

?>

<!-- Contact Hero Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>Contact Us</h1>
        <p class="lead">We're here to help with your birthday rewards</p>
    </div>
</div>

<!-- Contact Start -->
<div class="page-wrapper">
        <div class="container">
            <div class="contact-content">
            <div class="row g-0">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h4 mb-0">If you need to reach us, please use this form.</h2>
                    </div>
                    <div class="card-body">
    <?PHP echo $errormessage; ?>
                <div class="col-12">
                    <form method="post" action="/contact">
                  <?PHP echo $display->inputcsrf_token(); ?>
                        <div class="row gx-3 gy-2">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control border-0 bg-light" name="name" id="contact_name" placeholder="Your Name">
                                    <label for="contact_name">Your Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control border-0 bg-light" name="email" id="contact_email" placeholder="Your Email*">
                                    <label for="contact_email">Your Email</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                <input type="text" class="form-control border-0 bg-light" name="subject" id="contact_subject" placeholder="Subject"
            value="<?php echo isset($submitted_subject) ? $submitted_subject : ''; ?>">
        <label for="contact_subject">Subject</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                <textarea class="form-control border-0 bg-light" placeholder="Leave a message here*" name="message" id="contact_message" style="height: 150px"><?php echo isset($submitted_message) ? $submitted_message : ''; ?></textarea>
                                <label for="contact_message">Message</label>
                                </div>
                            </div>

<?PHP
echo $app->generateCaptcha('medium');

echo '
                           <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn-success py-3 px-5" type="submit">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
                ';
     
                

                date_default_timezone_set('America/Denver'); // Set the timezone to MST
                
                $currentHour = date('G'); // Get the current hour in 24-hour format
                
                // Define the business hours (MST)
                $businessHoursStart = 9; // 9:00 AM MST
                $businessHoursEnd = 17; // 5:00 PM MST
                
                // Check if the current hour is within business hours
                if ($currentHour >= $businessHoursStart && $currentHour < $businessHoursEnd) {
                    echo '
                        <hr class="my-3 mx-4">
                        <div class="pt-3 px-4">
                            <h6 class="text-primary text-uppercase mb-2">Call Us</h6>
                            <p class="mb-2">Need to talk to us on the phone.</p>
                            <p class="h5 mb-2"><a href="tel:877-234-6532">1-877-BDGOLD-2</a> <span class="text-muted small">(877-234-6532)</span></p>
                            <p class="small text-muted mb-0">Our business hours: 9:00 AM - 5:00 PM MST, Monday - Friday</p>
                        </div>
                    ';
                }
       
                

echo '       </div>
                    </div> <!-- End card-body -->
                </div>
                
                <!-- Social Links Card -->
                <div class="card mt-4">
                    <div class="card-body text-center social-media-card p-5">
                        <h3 class="h5 fw-semibold text-dark mb-4">Connect with us on social media</h3>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="https://twitter.com/birthday_gold" target="_blank" class="social-link" title="Twitter">
                                <i class="bi bi-twitter-x"></i>
                            </a>
                            <a href="https://www.facebook.com/birthdaygold/" target="_blank" class="social-link" title="Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="https://www.instagram.com/birthday_gold/" target="_blank" class="social-link" title="Instagram">
                                <i class="bi bi-instagram"></i>
                            </a>
                            <a href="https://www.linkedin.com/company/birthdaygold" target="_blank" class="social-link" title="LinkedIn">
                                <i class="bi bi-linkedin"></i>
                            </a>
                            <a href="https://www.tiktok.com/@birthday.gold" target="_blank" class="social-link" title="TikTok">
                                <i class="bi bi-tiktok"></i>
                            </a>
                            <a href="https://www.youtube.com/@birthdaygold" target="_blank" class="social-link" title="YouTube">
                                <i class="bi bi-youtube"></i>
                            </a>
                            <a href="https://www.pinterest.com/birthdaygold/" target="_blank" class="social-link" title="Pinterest">
                                <i class="bi bi-pinterest"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- End contact-content -->
    </div> <!-- End container -->
</div> <!-- End page-wrapper -->
<!-- Contact End -->


';



include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();