<?PHP
$addClasses[]='Mail';
$addClasses[]='ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$name = '';
$email = '';
$subject = '';
$message = '';
$errormessage = '';
$continue = false;
$showDisabledForm = false;


#-------------------------------------------------------------------------------
# HANDLE THE CONTACT FORM SUBMISSION
#-------------------------------------------------------------------------------
$error = false;
if (($formdata = $app->formposted())) {
  $continue = false;
  $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Your contact information is invalid.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
 // Retain the submitted values for subject and message
 $submitted_subject = htmlspecialchars($_REQUEST['subject'] ?? '', ENT_QUOTES, 'UTF-8');
 $submitted_message = htmlspecialchars($_REQUEST['message'] ?? '', ENT_QUOTES, 'UTF-8');

  
  // Only validate captcha if not confirming a flagged message
  if (!$skipCaptcha && !$app->validateCaptcha()) {
    $continue = false;
    $error = true;
    $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">The Recaptcha Challenge is incorrect.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';

   
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
    $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">You must provide all the required fields.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    goto displaypage;
  }

  // Here you could add more checks, such as checking if the email is valid
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Invalid email address.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    $continue = false;
    $error = true;
    goto displaypage;
  }

  // Check if user confirmed the message after spam detection
  $confirmedMessage = isset($_POST['confirmed_message']) && $_POST['confirmed_message'] == '1';
  
  // Skip captcha validation if confirming a flagged message
  $skipCaptcha = $confirmedMessage;
  
  // If confirming, retrieve the original data from session
  if ($confirmedMessage && isset($_SESSION['flagged_contact_data'])) {
    // Verify the data isn't too old (5 minutes max)
    if (time() - $_SESSION['flagged_contact_data']['timestamp'] > 300) {
      $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Your confirmation has expired. Please submit the form again.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
      unset($_SESSION['flagged_contact_data']);
      goto displaypage;
    }
    
    // Use the original data from session
    $name = $_SESSION['flagged_contact_data']['name'];
    $email = $_SESSION['flagged_contact_data']['email'];
    $subject = $_SESSION['flagged_contact_data']['subject'];
    $message = $_SESSION['flagged_contact_data']['message'];
    
    // Clear the session data
    unset($_SESSION['flagged_contact_data']);
  }
  
  // AI Spam Detection - Only check if not already confirmed
  if (!$confirmedMessage) {
    try {
      // Set up AI engine for spam detection
      $ai->setEngine('anthropic_goldie', 'text');
      
      // Create spam detection prompt
      $spamCheckPrompt = "You are a spam detection assistant for a birthday rewards website contact form. Analyze the following message and determine if it is:
1. Legitimate customer inquiry about birthday rewards, account issues, or service questions
2. Spam, marketing message, or business solicitation

Message Details:
Name: $name
Email: $email
Subject: $subject
Message: $message

Respond with ONLY 'SPAM' if this appears to be spam/marketing/solicitation, or 'LEGITIMATE' if it appears to be a genuine customer inquiry. If uncertain, err on the side of LEGITIMATE.";

      // Process through AI
      $response = $ai->process($spamCheckPrompt, [
          'temperature' => 0.3,
          'max_tokens' => 10
      ]);
      
      $normalizedResponse = $ai->getNormalizedResponse($response);
      $aiDecision = trim(strtoupper($normalizedResponse['content']));
      
      // Record AI result in session tracking
      $trackingData = [
          'ai_decision' => $aiDecision,
          'ai_response' => $normalizedResponse,
          'name' => $name,
          'email' => $email,
          'subject' => $subject,
          'message_preview' => substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
          'timestamp' => date('Y-m-d H:i:s')
      ];
      session_tracking('contact-ai-spam-check', $trackingData);
      
      // If flagged as spam, show confirmation dialog
      if (strpos($aiDecision, 'SPAM') !== false) {
        // Store the original values in session to prevent tampering
        $_SESSION['flagged_contact_data'] = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'timestamp' => time()
        ];
        
        $errormessage = '<div class="alert alert-warning p-4 mb-4" role="alert">
          <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Message Flagged</h4>
          <p>Your message has been flagged as potentially inappropriate use of our contact system. This form is for customer service inquiries about Birthday Gold services only.</p>';
          
        // Show AI details in dev mode
        if ($mode == 'dev') {
          $errormessage .= '
          <div class="mt-3 p-3 bg-light border rounded">
            <h5 class="text-danger">AI Analysis Details (Dev Mode)</h5>
            <p class="mb-1"><strong>Decision:</strong> <span class="badge bg-danger">' . htmlspecialchars($aiDecision) . '</span></p>
            <p class="mb-1"><strong>Full Response:</strong> ' . htmlspecialchars($normalizedResponse['content']) . '</p>';
            
          if (isset($normalizedResponse['usage'])) {
            $errormessage .= '
            <p class="mb-1"><strong>Tokens Used:</strong> ' . $normalizedResponse['usage']['total_tokens'] . ' 
            (Prompt: ' . $normalizedResponse['usage']['prompt_tokens'] . ', Completion: ' . $normalizedResponse['usage']['completion_tokens'] . ')</p>';
          }
          
          $errormessage .= '
            <p class="mb-0"><strong>Engine:</strong> anthropic_goldie</p>
          </div>';
        }
        
        $errormessage .= '
          <hr>
          <div class="mb-3 p-3 bg-light border rounded">
            <h6 class="text-muted mb-2">Message to be sent:</h6>
            <p class="mb-1"><strong>From:</strong> ' . htmlspecialchars($name) . ' (' . htmlspecialchars($email) . ')</p>
            <p class="mb-1"><strong>Subject:</strong> ' . htmlspecialchars($subject ?: '(No subject)') . '</p>
            <p class="mb-0"><strong>Message:</strong> ' . nl2br(htmlspecialchars(substr($message, 0, 200))) . (strlen($message) > 200 ? '...' : '') . '</p>
          </div>
          <p class="mb-3">If you believe this is a legitimate customer service inquiry, please confirm to proceed:</p>
          <form method="POST" action="/contact" class="d-inline">
            ' . $display->inputcsrf_token() . '
            <input type="hidden" name="confirmed_message" value="1">
            <button type="submit" class="btn btn-success me-2">Confirm & Send</button>
            <a href="/contact" class="btn btn-secondary">Cancel</a>
          </form>
        </div>';
        
        // Set flag to show disabled form
        $showDisabledForm = true;
        goto displaypage;
      } else {
        // Track legitimate message
        $trackingData['status'] = 'legitimate';
        session_tracking('contact-ai-legitimate', $trackingData);
      }
    } catch (Exception $e) {
      // If AI fails, log error but continue with normal flow
      error_log('Contact form AI spam check failed: ' . $e->getMessage());
      
      // Track AI failure
      session_tracking('contact-ai-error', [
          'error' => $e->getMessage(),
          'email' => $email,
          'timestamp' => date('Y-m-d H:i:s')
      ]);
    }
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
$errormessage = '<div class="alert alert-success alert-dismissible fade show p-3 mb-4" role="alert"><i class="bi bi-check-circle-fill me-2"></i><strong>Success!</strong> Your message was sent to our customer service team. We\'ll respond within 24-48 hours.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';

// Track successful submission
$successTrackingData = [
    'status' => 'sent',
    'confirmed_after_spam' => $confirmedMessage ? 'yes' : 'no',
    'ai_checked' => !$confirmedMessage ? 'yes' : 'bypassed',
    'email' => $email,
    'subject' => $subject,
    'timestamp' => date('Y-m-d H:i:s')
];
session_tracking('contact-form-sent', $successTrackingData);

// Clear form values after successful submission
$name = '';
$email = '';
$subject = '';
$message = '';
$submitted_subject = '';
$submitted_message = '';

$system->postToRocketChat('* An Online Contact Form Message was sent: *'."\n".$messageinput['notification'], '#BG-MemberSupportTeam');
#$system->postToRocketChat('An Online Contact Message was sent: '.$messageinput['body'], '@Richard');
}

if (strpos($errormessage, '<button')===false && $errormessage!='' && strpos($errormessage, 'alert-warning')===false) {
$errormessage=str_replace('</div>', '<button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.style.display=\'none\'"></button></div>', $errormessage );
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
        min-height: 150px;
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
    <?PHP
     if (!empty($errormessage))
    echo '<div class="m-3">'.$errormessage.'</div>';
 ?>
                <div class="col-12">
                    <form method="post" action="/contact">
                  <?PHP echo $display->inputcsrf_token(); ?>
                        <div class="row gx-3 gy-2">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control border-0 bg-light" name="name" id="contact_name" placeholder="Your Name" value="<?php echo htmlspecialchars($name); ?>"<?php echo $showDisabledForm ? ' disabled' : ''; ?>>
                                    <label for="contact_name">Your Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control border-0 bg-light" name="email" id="contact_email" placeholder="Your Email*" value="<?php echo htmlspecialchars($email); ?>"<?php echo $showDisabledForm ? ' disabled' : ''; ?>>
                                    <label for="contact_email">Your Email</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                <input type="text" class="form-control border-0 bg-light" name="subject" id="contact_subject" placeholder="Subject"
            value="<?php echo htmlspecialchars($subject ?: (isset($submitted_subject) ? $submitted_subject : '')); ?>"<?php echo $showDisabledForm ? ' disabled' : ''; ?>>
        <label for="contact_subject">Subject</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                <textarea class="form-control border-0 bg-light" placeholder="Leave a message here*" name="message" id="contact_message" style="height: 150px"<?php echo $showDisabledForm ? ' disabled' : ''; ?>><?php echo htmlspecialchars($message ?: (isset($submitted_message) ? $submitted_message : '')); ?></textarea>
                                <label for="contact_message">Message</label>
                                </div>
                            </div>

<?PHP
if (!$showDisabledForm) {
    echo $app->generateCaptcha('medium');
}

echo '
                           <div class="col-12 d-flex justify-content-end">';
if (!$showDisabledForm) {
    echo '<button class="btn btn-success py-3 px-5" type="submit">Send Message</button>';
} else {
    echo '<span class="text-muted">Please use the confirmation dialog above to send this message.</span>';
}
echo '
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

// Add JavaScript for auto-fade and auto-expanding textarea
$footerattribute['postfooter'] = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Auto-fade success messages after 14 seconds
    const successAlert = document.querySelector(".alert-success");
    if (successAlert) {
        setTimeout(function() {
            // Fade out animation
            successAlert.style.transition = "opacity 1s ease-out";
            successAlert.style.opacity = "0";
            
            // Remove from DOM after fade completes
            setTimeout(function() {
                successAlert.remove();
            }, 1000);
        }, 14000);
    }
    
    // Auto-expanding textarea
    const messageTextarea = document.getElementById("contact_message");
    if (messageTextarea) {
        // Override the CSS !important rule
        messageTextarea.style.setProperty("height", "150px", "important");
        messageTextarea.style.minHeight = "150px";
        messageTextarea.style.maxHeight = "400px";
        messageTextarea.style.overflow = "hidden";
        
        // Auto-expand function
        function autoExpand() {
            // Store current scroll position
            const scrollPos = window.scrollY;
            
            // Reset height to auto to get accurate scrollHeight
            this.style.setProperty("height", "auto", "important");
            
            // Calculate new height
            const scrollHeight = this.scrollHeight;
            const minHeight = 150;
            const maxHeight = 400;
            
            // Set height within bounds using !important to override CSS
            if (scrollHeight <= minHeight) {
                this.style.setProperty("height", minHeight + "px", "important");
                this.style.overflow = "hidden";
            } else if (scrollHeight >= maxHeight) {
                this.style.setProperty("height", maxHeight + "px", "important");
                this.style.overflow = "auto";
            } else {
                this.style.setProperty("height", scrollHeight + "px", "important");
                this.style.overflow = "hidden";
            }
            
            // Restore scroll position to prevent jumping
            window.scrollTo(0, scrollPos);
        }
        
        // Attach event listeners
        messageTextarea.addEventListener("input", autoExpand);
        messageTextarea.addEventListener("paste", function() {
            setTimeout(autoExpand.bind(this), 0);
        });
        
        // Initial adjustment if there is existing content
        if (messageTextarea.value) {
            autoExpand.call(messageTextarea);
        }
    }
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();