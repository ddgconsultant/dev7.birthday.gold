<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
if ($_SERVER['HTTP_HOST'] == 'www.birthday.gold' || $_SERVER['HTTP_HOST'] == 'birthday.gold') {
  header('Location: /500');
  exit;
}
$errormessage='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

// Get a unique identifier from the error message or create one if not available
$errorHash = md5($transferpagedata['message'] ?? uniqid());
$sessionKey = "error_" . $errorHash;

// Define possible error messages
$errorMessages = [
  "<b>Oops!</b> &nbsp;That didn't work.",
  "<b>Yikes!</b> &nbsp;Error detected.",
  "<b>Hmm...</b> &nbsp;Something's not right.",
  "<b>Well then!</b> &nbsp;We hit a snag.",
  "<b>Uh-oh!</b> &nbsp;Problem encountered.",
  "<b>Hang on!</b> &nbsp;Error found.",
  "<b>Whoops!</b> &nbsp;That failed.",
  "<b>Darn it!</b> &nbsp;Something broke.",
  "<b>Not quite!</b> &nbsp;Process failed.",
  "<b>Well shoot!</b> &nbsp;Error occurred."
];

// Check if we've already stored a message for this error hash
if (isset($_SESSION[$sessionKey])) {
  $randomMessage = $_SESSION[$sessionKey];
} else {
  // Select a new random message and store it
  $randomMessage = $errorMessages[array_rand($errorMessages)];
  $_SESSION[$sessionKey] = $randomMessage;
}

// Get current datetime
$currentDateTime = date('Y-m-d H:i:s');

echo '
<!-- ERROR Start -->
<div class="container main-content">
  
<div class="container text-center">
<div class="row justify-content-center">
<div class="col-lg-10">
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a7/512.webp" type="image/webp">
<img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f6a7/512.gif" alt="🚧" width="64" height="64">
</picture>
<h1 class="display-2 text-danger">' . $randomMessage . '</h1>

<p class="mt-2 mb-0">If you need help or have any questions, please use our <a href="/contact" target="_blank"><B>Contact form</B></a>.</p>
<p class="my-0">You may want to copy and paste this message.</p>
<p class="mt-0">Note: We get alerted when these errors arise and review the issues surrounding the circumstances to see what needs to be corrected.</p>

<!-- Action Buttons row with datetime on left and buttons on right -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="text-start text-muted">
    <small>' . $currentDateTime . '</small>
  </div>
  <div class="text-end">
    <button onclick="window.history.back();" class="btn btn-secondary me-2">Go Back</button>
    <a href="/contact" class="btn btn-primary me-2">Contact Us</a>
    <button onclick="copyErrorDetails();" class="btn btn-info">Copy Error</button>
  </div>
</div>

<hr>
</div>

<div class="card-body bg-white px-lg-5 py-lg-5">
';

$encoded = isset($_REQUEST['e']) ? $_REQUEST['e'] : ($transferpagedata['message'] ?? '');

$output = 'No error details can be displayed.';
if ($encoded != '' && $mode == 'dev') {
  #$output=gzuncompress(base64_decode(urldecode($encoded)));
  #$output=base64_decode(urldecode($encoded));
  $output = urldecode($encoded);
  #$output=$encoded;
}
error_log($output);
echo '<div class="text-start small">'.$output.'</div>';

echo '
</div>
</div>
<div class="card bg-light">
  <div class="card-body px-lg-5 py-lg-5">';

if (isset($_SERVER['HTTP_REFERER'])) {
  $referrer = htmlspecialchars($_SERVER['HTTP_REFERER']); // Sanitize the URL to prevent XSS attacks
  echo '<a href="' . $referrer . '" title="' . $referrer . '" class="btn btn-primary btn-lg btn-block">Go Back</a>';
} else {
  #    echo 'No referrer information available.';
}

echo '
  </div>
</div>

</div>
</div>
</div>
</div>
<!-- ERROR End -->

<!-- JavaScript for copying error details to clipboard -->
<script>
function copyErrorDetails() {
  // Get error message text
  const errorText = document.querySelector(".text-start.small").innerText;
  
  // Create a temporary textarea element to copy from
  const textarea = document.createElement("textarea");
  textarea.value = errorText;
  document.body.appendChild(textarea);
  
  // Select and copy the text
  textarea.select();
  document.execCommand("copy");
  
  // Remove the temporary element
  document.body.removeChild(textarea);
  
  // Show feedback
  // alert("Error details copied to clipboard");
}
</script>
';

// Check if HTTP_REFERER is set
$referer = $_SERVER['HTTP_REFERER'] ?? null;

if ($referer) {
  $parsedReferer = parse_url($referer);
  $domainAndPage = ($parsedReferer['host'] ?? '') . ($parsedReferer['path'] ?? '');

  // Extract subdomain
  $hostParts = explode('.', $parsedReferer['host'] ?? '');
  $subdomain = 'dev';
  if (!empty($hostParts[0])) {
    $subdomain = $hostParts[0];  // Assuming www.example.com format, this will give 'www'. Adjust accordingly if there are more parts to your domain.
  }
} else {
  $parsedReferer = null;
  $domainAndPage = '';
  $subdomain = 'dev';
}

// Detect which site mode is being used for pagerduty handling
switch ($mode) {
  case 'dev':
  case 'dev4':
    $severity = 'info';
    $summaryValue = "Non-Prod - BIRTHDAY GOLD web error";
    break;

  default:
    $severity = 'critical';
    $summaryValue = "PROD - BIRTHDAY GOLD web error";

    // API Endpoint
    $url = 'https://events.pagerduty.com/v2/enqueue';

    // Headers
    $headers = array(
      'Content-Type: application/json'
    );

    // Payload
    $data = array(
      "payload" => array(
        "summary"  => $summaryValue,
        "severity" => $severity,
        "source"   => "Site Error Handler",
        "timestamp" =>  date('Y-m-d\TH:i:sO'),
        "component" => $domainAndPage,
        "custom_details" => json_encode(substr($output, 0, 750))
      ),
      "routing_key"  => "07ea6420add84102d00b58061518e891",
      "event_action" => "trigger"
    );

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    // Execute cURL session and get the result
    $response = curl_exec($ch);

    // Error handling
    if ($response === false) {
      echo 'Curl error: ' . curl_error($ch);
    } else {
      echo 'Response: ' . $response;
    }

    // Close cURL session
    curl_close($ch);
    break;
}

$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>