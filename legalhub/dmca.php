<?PHP
$addClasses[]='Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


#-------------------------------------------------------------------------------
# HANDLE THE POST ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()){
    // Process the DMCA takedown request here.
    
    // You can access form data using $_POST, e.g., $_POST["name"], $_POST["email"], etc.
    
    // You may want to perform validation and verification of the request.
    
    // Example: Send an email notification with the DMCA request details.
    $recipient_email = "cs@birthday.gold"; // Change to your email address.
    $subject = "DMCA Takedown Request";
    $email=$_POST["email"] ?? '';
     $name=$_POST["name"]  ?? '';
    $message = "Name: " .$name . "\n";
    $message .= "Email: " . $email . "\n";
    $message .= "Infringing URL: " . $_POST["infringing_url"] . "\n";
    $message .= "Original Content URL: " . $_POST["original_url"] . "\n";
    $message .= "Description of Infringement:\n" . $_POST["description"] . "\n";
    
    // Attach evidence (if provided).
    if (isset($_FILES["evidence"]) && $_FILES["evidence"]["error"] == UPLOAD_ERR_OK) {
        $tmp_name = $_FILES["evidence"]["tmp_name"];
        $file_name = $_FILES["evidence"]["name"];
        $message .= "Evidence File: $file_name";
        $attachment = chunk_split(base64_encode(file_get_contents($tmp_name)));
        
        // Set up headers for email attachment.
        $boundary = md5(time());
        $headers = "From: " . $email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $headers .= "This is a multi-part message in MIME format.\r\n";
        
        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $message . "\r\n";
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: application/octet-stream; name=\"$file_name\"\r\n";
        $message .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= $attachment . "\r\n";
        $message .= "--$boundary--";
    }
    
    // Send the email.

    
#$message['validatelink']=$link['shorturl'];
$messageinput['from']=[$email, $name];
$messageinput['to']=['cs@birthday.gold', 'CS birthday.gold'];

if ($subject!='') $subject='<br>SUBJECT: '.$subject;
$messageinput['body']='DATE: '.date('r').'<br>ID: '.session_id().$subject.'<hr>'.$message;
$result=$mail->sendOnlineContactForm($messageinput);

    
    // Display a confirmation message.
    

echo '
<div class="container-xl px-4 mt-4 flex-grow-1">
<div class="container px-5 mx-5 ">
';
    echo "<h2>DMCA Takedown Request Submitted</h2>";
    echo "<p>Thank you for submitting your DMCA takedown request. We will review it and take appropriate action.</p>";
echo '</div>
</div>
';
  

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
    exit;
} 










#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
$additionalstyles.='
<style>
.description {
    height: 200px !important;
}
      @media (max-width: 576px) {
        .form-container {
            padding-left: 10px;
            padding-right: 10px;
        }
    }
</style>';


echo '
<div class="container-xl px-4 mt-4 flex-grow-1">

<div class="container form-container px-md-5 mx-md-5 ">
<h2 class="mt-5 text-center">DMCA Takedown Request Form</h2>
<form action="/dmca" method="post" enctype="multipart/form-data" class="mt-4">
    <!-- Include CSRF token if needed -->
'. $display->inputcsrf_token().'
    
<div class="form-floating mb-3">
<input type="text" name="name" class="form-control" id="name" value="" placeholder=" " required>
<label for="name">Your Name:</label>
</div>

<div class="form-floating mb-3">
   <input type="email" name="email" class="form-control" id="email" value="" placeholder=" " required>
   <label for="email">Your Email:</label>
</div>

<div class="form-floating mb-3">
   <input type="url" name="infringing_url" class="form-control" id="infringing_url" value="" placeholder=" " required>
   <label for="infringing_url">Infringing URL:</label>
</div>

<div class="form-floating mb-3">
   <input type="url" name="original_url" class="form-control" id="original_url" value="" placeholder=" " required>
   <label for="original_url">Original Content URL:</label>
</div>

<div class="form-floating mb-3">
   <textarea name="description" class="form-control description" id="description" rows="10" placeholder=" " required></textarea>
   <label for="description">Description of Infringement:</label>
</div>


<div class="mb-3">
    <label for="evidence" class="form-label">Attach Evidence (if any):</label>
    <input class="form-control" type="file" name="evidence" id="evidence">
</div>


<div class="mt-5 text-center">
    <button type="submit" class="btn btn-primary">Submit DMCA Request</button>
</div>
</form>
</div>
</div>
';


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();