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
$messageinput['to']='CS birthday.gold';
$messageinput['toemail']='cs@birthday.gold';
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

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

?>
<!-- Navbar End -->


    <!-- Contact Start -->
    <div class="container py-6 flex-grow-1">
        <div class="container">
            <div class="row g-5">
                
    <?PHP echo $errormessage; ?>
                <div class="col-lg" data-wow-delay="0.5s">
                    <h6 class="text-primary text-uppercase mb-2">Contact Us</h6>
                    <h1 class="display-6 mb-4">If you need to reach us, please use this form.</h1>
                    <form method="post" action="/contact">
                  <?PHP echo $display->inputcsrf_token(); ?>
                        <div class="row g-3">
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
echo $app->generateCaptcha();

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
                        <hr>
                        <div class="pt-6 mt-6">
                            <h6 class="text-primary text-uppercase mb-2 mt-6">Call Us</h6>
                            <h1 class="display-6 mb-4">Need to talk to us on the phone.</h1>
                            <h2 class="display-6 mb-4"><a href="tel:877-234-6532">1-877-BDGOLD-2</a> <a class="h5" href="tel:877-234-6532">(877-234-6532)</a></h2>
                            Our business hours:  9:00 AM - 5:00 PM MST, Monday - Friday
                        </div>
                    ';
                }
       
                

echo '       </div>

        </div>
    </div>
    <!-- Contact End -->


';



include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();