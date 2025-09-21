<?PHP
#$nosessiontracking=true;
$addClasses[] = 'Chat';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$sitecheckerkey = [];
$sitecheckerkey['id'] = 'Site-Checker'; // Custom header name
$sitecheckerkey['key'] = $sitesettings['app']['SITE_TRACKER_KEY'];
ini_set('max_execution_time', 0);
$DEBUG = false;
ob_start();
ob_implicit_flush(true);
ob_end_flush();

#-------------------------------------------------------------------------------
// Function to get the HTML content of a URL
function getHtmlContent($url)
{
  if (empty($url)) {
    logError('noneprovided', 'URL is empty');
    return false;
  }

  global $sitecheckerkey, $DEBUG;

  if ($DEBUG) {
    echo '<hr>' . date('r') . ' -- PROCESSING: ' . $url . '<br>';
    flush();
  }
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [$sitecheckerkey['id'] . ': ' . $sitecheckerkey['key']]);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL verification
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout
  curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Response timeout
  $content = curl_exec($ch);

  if (curl_errno($ch)) {
    $errorMessage = 'cURL error: ' . curl_error($ch);
    if ($DEBUG) {
        echo $errorMessage . '<br>';
    }
    logError($url, $errorMessage);
  } else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($DEBUG) {
        echo 'HTTP code: ' . $httpCode . '<br>';
    }

    if ($content === false) {
        $errorMessage = 'cURL content error: ' . curl_error($ch);
        if ($DEBUG) {
            echo $errorMessage . '<br>';
        }
        logError($url, $errorMessage);
    } else if (empty($content)) {
        $errorMessage = 'cURL returned empty content';
        if ($DEBUG) {
            echo $errorMessage . '<br>';
        }
        logError($url, $errorMessage);
    } else {
        if ($DEBUG) {
            echo 'cURL returned non-empty content<br>';
        }
    }
  }

  curl_close($ch);
  return $content;
}

#-------------------------------------------------------------------------------
// Function to log errors to a file
function logError($url, $message='')
{
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] SITECHECKER - Error fetching URL $url: $message\n");
}


// Function to check for different types of PHP errors in HTML content
function checkForErrors($content)
{
    $errorPatterns = [
        'Parse error' => 'Parse error:',   // Detects syntax errors
        'Notice error' => 'Notice:',       // Detects notice errors
        'Warning error' => 'Warning:',     // Detects warning errors
        'Fatal error' => 'Fatal error:',   // Detects fatal errors
        'Uncaught Exception' => 'Uncaught', // Detects uncaught exceptions
    ];

    foreach ($errorPatterns as $errorType => $pattern) {
        if (strpos($content, $pattern) !== false) {
            return true; // Error found
        }
    }

    return false; // No errors found
}


#-------------------------------------------------------------------------------
// Function to crawl the website and check for errors
function crawlWebsite($baseUrl, $files, $exclude)
{
  $errorsFound = [];
  $checkedFiles = [];
  $excludedFiles = [];
  $totalFiles = count($files);
  $startTime = microtime(true);

  foreach ($files as $file) {
    $search = [$_SERVER['DOCUMENT_ROOT'], 'var/www/BIRTHDAY_SERVER/www.birthday.gold/'];
    $relativePath = str_replace($search, '', $file);

    // Skip excluded paths
    if (in_array($relativePath, $exclude)) {
      $excludedFiles[] = $file;
      continue;
    }

    $checkedFiles[] = $file;
    $url = $baseUrl . $relativePath;
    $content = getHtmlContent($url);
    if (checkForErrors($content)) {
      $errorsFound[] = [
        'file' => $url,
        'content' => $content
      ];
    }
  }

  $endTime = microtime(true);
  $processingTime = $endTime - $startTime;

  return [
    'total_number_of_files' => $totalFiles,
    'total_files_checked' => count($checkedFiles),
    'total_files_excluded' => count($excludedFiles),
    'total_files_with_errors' => count($errorsFound),
    'total_files_no_errors' => count($checkedFiles) - count($errorsFound),
    'starttime' => date('Y-m-d H:i:s', (int) $startTime),
    'endtime' => date('Y-m-d H:i:s', (int) $endTime),
    'processing_time' => $processingTime,
    'list_of_files' => $files,
    'list_of_checked_files' => $checkedFiles,
    'list_of_excluded_files' => $excludedFiles,
    'list_of_files_with_errors' => $errorsFound
  ];
}

#-------------------------------------------------------------------------------
// Base URL of your website
$baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];


// Directory to crawl
#$directory = '/var/www/BIRTHDAY_SERVER/www.birthday.gold';
$directory = $_SERVER['DOCUMENT_ROOT'];
// Get all PHP files in the directory
# $files = glob("$directory/**/*.php", GLOB_BRACE);  // subdirectories
$files = glob("$directory/*.php");


// List of paths to exclude
$exclude = [
  '/_pahpinfo.php',
  '/applyplan_handler.php',
  '/special_applyplan_handler.php',
  '/special_validate-account.php',
  '/charge.php',
  '/generategc.php',
  '/checkout-finalize.php',
  '/checkout-parental_handler.php',
  '/checkout-parental.php',
  '/applyplan.php',
  '/career_apply.php',
  '/checkout.php',
  '/error.php',
  '/invitedby.php',
  '/register-giftcertificate.php',
  '/setup-parental.php',
  '/special_applyplan.php',
  '/webhook-paymentgateway-paypal.php',
  '/webhook-paymentgateway-stripe.php',
  '/testscroll.php',
  '/testmail.php',
  '/testscript.php',
  '/testupload.php'
];

#-------------------------------------------------------------------------------
// Testing files list (for testing purposes)
$testingFiles = [
  '/var/www/BIRTHDAY_SERVER/www.birthday.gold/forgot.php',
  // Add more test files as needed
];
//$files = $testingFiles;

#-------------------------------------------------------------------------------
// Crawl the website and check for errors
if ($DEBUG) echo 'STARTING: ' . date('r');
$result = crawlWebsite($baseUrl, $files, $exclude);

#-------------------------------------------------------------------------------
// Debug statements to check if we have reached this point
if ($DEBUG) echo 'Errors Found: ' . print_r($result['list_of_files_with_errors'], true) . '<br>';

if (empty($result['list_of_files_with_errors'])) {

  $currentDay = date('N'); // Get current day of the week (1 for Monday, 7 for Sunday)
  $currentHour = date('G'); // Get current hour in 24-hour format

  if (($currentHour >= 8 && $currentHour < 17) && $DEBUG) {
    $finalstatus = 'No errors found';
    if ($DEBUG) echo 'Sending no error message with full report<br>';
    $chat->sendRocketChatMessage("✅ No errors found on the website. (" . $_SERVER['HTTP_HOST'] . ") -- FULL REPORT:\n```" . print_r([
      'total_number_of_files' => $result['total_number_of_files'],
      'total_files_checked' => $result['total_files_checked'],
      'total_files_excluded' => $result['total_files_excluded'],
      'processing_time' => $result['processing_time'],
      'list_of_files' => $result['list_of_files'],
      'list_of_checked_files' => $result['list_of_checked_files']
    ], true) . "```");
  } else {
    $finalstatus = 'No errors found';
    if (($currentDay == 1 && $currentHour >= 8 && $currentHour < 12)) {
      $chat->sendRocketChatMessage("✅ No errors found on the website. (" . $_SERVER['HTTP_HOST'] . ") -- \n```" . print_r([
        'total_files_checked' => $result['total_files_checked'],
        'processing_time' => $result['processing_time'],
      ], true) . "```");
    }
  }
} else {
  $finalstatus = 'Errors exist';
  $errorMessage = "📛 Errors found on the following pages: " . implode(' ', array_column($result['list_of_files_with_errors'], 'file'));
  if ($DEBUG) echo 'Sending error message: ' . $errorMessage . '<br>';
  
  $logDirectory = '/var/www/BIRTHDAY_SERVER/www.birthday.gold/_logs_/';
  if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0755, true);
  }
  $logFile = $logDirectory . 'sitechecker_errors_' . date('YmdHis') . '.log';

  $logContent = "";
  foreach ($result['list_of_files_with_errors'] as $error) {
    $logContent .= $error['file'] . "\n" . $error['content'] . "\n\n";
  }

  file_put_contents($logFile, $logContent);
  
  session_tracking('sitetracking_errors', $result['list_of_files_with_errors']);
  $chat->sendRocketChatMessage($errorMessage);
}

echo $finalstatus;
// More debugging
if ($DEBUG) echo '<br>Message sent<br>';
if ($DEBUG) echo 'COMPLETED: ' . date('r');
