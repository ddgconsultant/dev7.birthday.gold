<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

// CORS headers
if (isset($_SERVER['HTTP_ORIGIN'])) {
  $origin = $_SERVER['HTTP_ORIGIN'];
  if (preg_match('/^https:\/\/[a-zA-Z0-9.-]*\.birthday\.gold$/', $origin)) {
      // Ensure header is set only once
      header_remove('Access-Control-Allow-Origin'); // Remove any pre-existing header
      header('Access-Control-Allow-Origin: ' . $origin);
      header('Vary: Origin'); // Recommended to prevent caching issues with CORS
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
      header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
      header('Access-Control-Allow-Credentials: true');
  }
}



#-------------------------------------------------------------------------------
# MAINTENANCE MODE
#-------------------------------------------------------------------------------
$site_maintenancemode = false;
$website['statuspage'] = 'https://uptime.birthdaygold.cloud/status/all';

if (!empty($site_maintenancemode)) {
  echo '
<!doctype html><html lang="en"><head>
<title>Birthday.Gold Site Maintenance</title>
<link href="//fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
<style>
  html, body { padding: 0; margin: 0; width: 100%; height: 100%; }
  * {box-sizing: border-box;}
  body { text-align: center; padding: 0; background: #f3bd00; color: #fff; font-family: Open Sans;}
  h1 { font-size: 50px; text-align: center;}
  body { font-family: Open Sans; font-weight: 175; font-size: 20px; color: #fff; text-align: center; display: flex; justify-content: center; align-items: center; flex-direction: column;}
  article { display: block; width: 700px; padding: 50px; margin: 0 auto; }
  a { color: #fff; font-weight: bold;}
  a:hover { text-decoration: none; }
  svg { width: 75px; margin-top: 1em; }
  footer { position: absolute; bottom: 0; width: 100%; padding: 20px 0; background: #f3bd00; }
</style>
</head><body>
<article>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 202.24 202.24"><defs><style>.cls-1{fill:#fff;}</style></defs>
    <title>Maintenance</title><g id="Layer_2" data-name="Layer 2"><g id="Capa_1" data-name="Capa 1"><path class="cls-1" d="M101.12,0A101.12,101.12,0,1,0,202.24,101.12,101.12,101.12,0,0,0,101.12,0ZM159,148.76H43.28a11.57,11.57,0,0,1-10-17.34L91.09,31.16a11.57,11.57,0,0,1,20.06,0L169,131.43a11.57,11.57,0,0,1-10,17.34Z"/><path class="cls-1" d="M101.12,36.93h0L43.27,137.21H159L101.13,36.94Zm0,88.7a7.71,7.71,0,1,1,7.71-7.71A7.71,7.71,0,0,1,101.12,125.63Zm7.71-50.13a7.56,7.56,0,0,1-.11,1.3l-3.8,22.49a3.86,3.86,0,0,1-7.61,0l-3.8-22.49a8,8,0,0,1-.11-1.3,7.71,7.71,0,1,1,15.43,0Z"/></g></g></svg>
    <h1>We&rsquo;ll be back soon!</h1>
    <div>
        <p>Sorry for the inconvenience. We&rsquo;re performing some maintenance at the moment. 
        <span style="display:block">If you would like, you can always follow us on <a href="//www.x.com/birthday_gold">X (formally Twitter)</a> for updates.</span>
        We&rsquo;ll be back up shortly!</p>
        <p>&mdash; The Birthday.Gold Team</p>
    </div>
</article>

<footer>
<a href="' . $website['statuspage'] . '">Status Page</a>
</footer>
</body></html>';

  exit;
}




#-------------------------------------------------------------------------------
# SET UP SITE ENVIRONMENT VARIABLES - do not change the formatting of this section
#-------------------------------------------------------------------------------
$site = 'dev7';
$mode = 'dev';
$errormode='showerrors';
$codemode = 'web';
$devversion = 'dev7';
$cookiebannerversion=2;
$cookieinfolink='https://consumer.ftc.gov/articles/how-websites-and-apps-collect-and-use-your-information';

#-------------------------------------------------------------------------------
# ERROR HANDLER
#-------------------------------------------------------------------------------
if ($errormode === 'showerrors') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);    
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else { // 'prod' mode
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

// Handle uncaught exceptions and log them
set_exception_handler(function ($e) {
    error_log('Uncaught Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    if ($GLOBALS['errormode'] === 'showerrors') {
        echo "Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "<br>";
    }
});

// Convert warnings and notices to log entries but allow execution to continue
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");

    if ($GLOBALS['errormode'] === 'showerrors') {
        echo "PHP Error [$errno]: $errstr in $errfile on line $errline <br>";
    }
    return true; // Prevents PHP from handling the error as a fatal error
});

// Ensure fatal errors are logged but don't break Apache
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");

        if ($GLOBALS['errormode'] === 'showerrors') {
            echo "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']} <br>";
        }
    }
});




#-------------------------------------------------------------------------------
# REMOVE WWW
#-------------------------------------------------------------------------------
if (php_sapi_name() != 'cli') {
  if (isset($_SERVER['HTTP_HOST'])) {
    if ($_SERVER['HTTP_HOST'] === 'www.birthday.gold') {
      $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
      # session_tracking('www_redirect', [   'nonWwwUrl' => 'https://birthday.gold',  'requestUri' => $requestUri       ]);
      header('HTTP/1.1 301 Moved Permanently');
      header('Location: https://birthday.gold' . $requestUri);
      exit;
    }
  }
}


#-------------------------------------------------------------------------------
# SET UP OUTPUT BUFFERING HANDLING
#-------------------------------------------------------------------------------
ob_start();

if (!isset($nolockout)) $nolockout = true;



#-------------------------------------------------------------------------------
# EARLY GLOBAL FUNCTIONS
#-------------------------------------------------------------------------------
function breakpoint($msg = 'END', $break = true)
{
  global $mode;
  if ($mode == 'production') return;
  if ($msg === false) echo '<h1 color="red">!!-valueprovided=false-!!</h1><br><pre>' . date('r') . '</pre>';
  else
    echo '<h2 color="red"><pre>' . print_r($msg, 1) . '</pre></h2><br><pre>' . date('r') . '</pre>';
  if ($break) exit;
}
function showerrors()
{
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}



#-------------------------------------------------------------------------------
# SET UP THE UI -- we can have multiple designs of our site with this setting
#-------------------------------------------------------------------------------
$website['ui_version'] = 'v3';
$website['plan_version'] = 'v3';
$additionalstyles = '';
$logBuffer = [];



#-------------------------------------------------------------------------------
# SET UP $DIR VARIABLES
#-------------------------------------------------------------------------------
// Check if $dir['base'] is already set and non-empty, otherwise determine its value
if (empty($dir['base'])) {
  if (php_sapi_name() != 'cli') {
      $dir['base'] = $_SERVER['DOCUMENT_ROOT'];
  } else {
      $dir['base'] = __DIR__ . '/..';
  }
} else {
  $dir['base'] = rtrim($dir['base'], '/');
 # echo "DIR BASE: " . $dir['base']; exit;
}

if (empty($BASEDIR)) $BASEDIR = $dir['base'];
$dir['core'] = $dir['base'] . '/core';
$dir['blade'] = $dir['core'] . '/' . $website['ui_version'];
$dir['core_components'] = $dir['core'] . '/components/' . $website['ui_version'];
$dir['classes'] = $dir['core'] . '/classes';
$dir['vendor'] = $dir['base'] . '/vendor';

$dir['useravatar'] = '/public/uploads/avatar/';
$dir['useravatar_full'] =  $dir['base'] . $dir['useravatar'];
$dir['usercover'] = '/public/uploads/cover/';
$dir['usercover_full'] =  $dir['base'] . $dir['usercover'];

$dir['coavatar'] = '/public/uploads/company_avatar/';
$dir['coavatar_full'] =  $dir['base'] . $dir['coavatar'];
$dir['cocover'] = '/public/uploads/company_cover/';
$dir['cocover_full'] =  $dir['base'] . $dir['cocover'];
$dir['updatemedia'] = '/public/uploads/updates/';
$dir['updatemedia_full'] =  $dir['base'] . $dir['updatemedia'];

$dir['configs'] = $dir['base'] . '/../ENV_CONFIGS';
$dir['companyserverbase'] = $dir['base'] . '/../';
$dir['ampath'] = $ampath = '/admin/accessmanager';
$dir['currentsscriptpage'] = $dir['base'] . htmlspecialchars($_SERVER['PHP_SELF']);

if (!ini_get('date.timezone'))  date_default_timezone_set('GMT');

$headerattribute = array();
$footerattribute = array();


/*
// Resolve and verify configuration directory
$dir['configs'] = realpath($dir['base'] . '/../ENV_CONFIGS');
if (!$dir['configs']) {
    die('Error: Configuration directory not found: ' . $dir['base'] . '/../ENV_CONFIGS');
}

// Check configuration file existence
$configFile = $dir['configs'] . '/config-database.inc';
if (!file_exists($configFile)) {
    die('Error: Configuration file not found: ' . $configFile);
}

// Include configuration file
include($configFile);

// Verify $db initialization
if (empty($db) || !is_array($db)) {
    die('Error: $db variable is not initialized correctly in the configuration file.');
}
    breakpoint ($dir);
*/





#-------------------------------------------------------------------------------
# SET UP WEBSITE ADDITIONAL VARIABLES
#-------------------------------------------------------------------------------
#$dir['bge'] = '/admin/bgrab';
$dir['bge'] = '/admin/bgreb_v3';
$dir['bge_dir'] = $dir['base'] . $dir['bge'];
$dir['bge_web'] = 'https://' . $site . '.birthday.gold'.$dir['bge'];
$dir['bge_raw'] = 'W:/BIRTHDAY_SERVER/'.$devversion.'.birthday.gold'.$dir['bge'];
$dir['bge_webA'] = 'https://' . $site . '.birthday.gold'.$dir['bge'];
$website['bge_extensionversion'] ='v4';

$website['domain'] = '.birthday.gold';  // Notice the leading dot (.)
$website['url'] = $site . $website['domain'];
$website['fullurl'] = 'https://' . $website['url'];
$website['formalurl'] = ($site == 'www') ? 'https://birthday.gold' : $website['fullurl'];
$website['mode'] = 'light';
$website['thememode'] = 'light';
$website['currentpage'] = '';
$website['pagecontrol'] = '';
$website['fulluri'] = '';
$website['useravatar_url'] = '';
$dir['useravatar'];
$website['usercover_url'] = '';

$dir['usercover'];
$website['updatemedia_url'] = $website['fullurl'] . $dir['updatemedia'];
$website['cdnurl'] = 'files.birthday.gold';

$website['numberofbiz'] = 275;
$website['biznames']='businesses';
$website['bizname']='business';
$website['defaultavatar'] = '/public/avatars/problemavatar.png';

$website['bootstrap_css'] = '<link href="//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">';
$website['bootstrap_js'] = '<script src="//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>';
$website['bootstrap_css'] = '<link href="/public/css/bootstrap.min.css" rel="stylesheet">';
$website['bootstrap_js'] = '<script src="/public/js/bootstrap.bundle.min.js"></script>';
$website['apponlytag'] = 'APP ONLY';
include($dir['core'] . '/site-arrays.inc');
$enablechat = true;


#-------------------------------------------------------------------------------
# GRAB CODE VERSION
#-------------------------------------------------------------------------------
include($dir['core'] . '/' . $website['ui_version'] . '/footerversion.inc');



#-------------------------------------------------------------------------------
# ALLOW CROSS SUBDOMAIN SESSIONS
#-------------------------------------------------------------------------------
ini_set('session.cookie_domain', $website['domain']);



#-------------------------------------------------------------------------------
# READ IN ENVIRONMENT FILE
#-------------------------------------------------------------------------------
$configfile = $dir['configs'] . '/config-main-' . $mode . '6.inc';
if (!file_exists($configfile)) {
  die("CRITICAL: Configuration file does not exist: $configfile");
}

$config = file_get_contents($configfile);
$sitesettings = parse_ini_string($config, true);
$STRIPECONFIG = $sitesettings['paymentgateway-stripe-live'];



#-------------------------------------------------------------------------------
# SET UP ALL THE CLASSES
#-------------------------------------------------------------------------------
// Base array of classes
$classes = ['Timer', 'System', 'Qik', 'Session', 'Database', 'App', 'Account', 'Admin', 'Display'];

// Check if any classes set externally
if (isset($addClasses)) {
 # $classes = array_merge($classes, $addClasses);  
  array_push($classes, ...$addClasses);
}


// Loop through classes
foreach ($classes as $class) {
  $class = strtolower($class);
  // Build filename
  $filename = 'class.' . $class . '.php';

  // Check file exists
  if (file_exists($dir['classes'] . '/' . $filename)) {

    // Require file
    require_once($dir['classes'] . '/' . $filename);
    if (isset($sitesettings[$class]))
      $classConfig = $sitesettings[$class];
    else
      $classConfig = array();

    // Instantiate class and Allow special instantiation of a class if needed
    $className = $class;
    switch ($class) {
        // -----------------------------------------
      case 'accessmanager':
        $am_default_kidirpath = 'AFL_H/QAB_A';
        $$className = new $className($database, $sitesettings['keymanager'],  $am_default_kidirpath);
        break;
        // -----------------------------------------      
      case 'account':
        case 'createaccount':
        $$className = new $className($database, $session);
        break;
        // -----------------------------------------
        case 'admin':
       if ($account->isadmin())   $$className = new $className($database, $session);
          break;
        // -----------------------------------------
      case 'referral':
        $$className = new $className($database, $session);
        break;
        // -----------------------------------------
      case 'timeclock':
        $$className = new $className($database, $session);
        break;
        // -----------------------------------------
      case 'timer':
        $$className = new $className($classConfig);
        $timer->start();
        break;
        // -----------------------------------------
      case 'fileuploader':
        $b2Credentials = $sitesettings['storage'];
        $$className = new $className($b2Credentials);
        break;
        // -----------------------------------------
      case 'fileuploader_ui':
        // Default parameters
        $defaultParams = array(
          'limit' => null,
          'maxSize' => null,
          'extensions' => null,
          'uploadDir' => $_SERVER['DOCUMENT_ROOT'] . '/public/uploads/',
          'title' => 'name'
        );
        $params1 = $classparams1['fileuploader_ui'] ?? 'files';  // Default to 'files' if not provided
        $params2 = $classparams2['fileuploader_ui'] ?? [];  // Default to an empty array if not provided            
        $customParams = array_merge($defaultParams, $params2); // Merge custom parameters with default          
        $$className = new $className($params1, $customParams); // Instantiate fileuploader_ui with the first param ($params1) and merged custom parameters
        break;
        // -----------------------------------------
      case 'leantime':
        $params1 = $classparams1['leantime'] ?? 'https://leantime.birthdaygold.cloud//api/v1';
        $params2 = $classparams2['leantime'] ?? [];  // need an API key         
        $$className = new $className($params1, $params2);
        break;
        // -----------------------------------------
      case 'cdn':
        $cdnAccountId = $sitesettings['cdn_backblaze']['account_id'];
        $cdnApplicationKey = $sitesettings['cdn_backblaze']['application_key'];
        $$className = new $className($cdnAccountId, $cdnApplicationKey);
        break;
        // -----------------------------------------
        case 'telegramsmsservice':
          $telegramToken = $sitesettings['telegram']['telegramToken'];
          $telegramAPI = $sitesettings['telegram']['telegramAPI'];
          $telegramsmsservice = new TelegramSMSService($telegramToken, $telegramAPI, $database);
          break;
          // -----------------------------------------
      case 'chat':
        $$className = new $className($sitesettings['chat']);
        break;
     // -----------------------------------------  
     case 'ai':
      $config_ai = file_get_contents($dir['configs'] . '/config-ai.inc');
      $sitesettings_ai = parse_ini_string($config_ai, true);
      $ai = new AI($system, $sitesettings_ai);
    #  $$className = AI::getInstance($sitesettings_ai['ai']);
      break;

        // -----------------------------------------
      default:
        $$className = new $className($classConfig);
        break;
    }
  } else {
    #throw new Exception("File $filename not found");
    echo "File " . $dir['classes'] . '/' . $filename . " not found.";
  }
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
# ## SITE WIDE VARIABLES 
############################
$errormessage='';
$website['fulluri'] = $qik->currenturl();
$entrypoint['first'] = $session->get('entrypoint_first', 'notset');
if ($entrypoint['first'] == 'notset') {
  $session->set('entrypoint_first', $website['fulluri']);
  $session->set('enable_adminpageeditor', false);
}

if ($account->isadmin() && $mode=='dev' &&  $session->get('enable_adminpageeditor', false) ) $enableadminpageeditor=true;
else $enableadminpageeditor=false;

## INITIALIZE CLIENT DETAILS
#-------------------------------------
$client_ip = $system->getipaddress();
if (empty($nolockout)) $app->check_lockout($client_ip);  ## CHECK LOCKOUT
$client_browser = $qik->browserdetail();
if ($codemode == 'api') $client_locationdata = '';
else $client_locationdata = $system->getcountryviaip($client_ip);
#-------------------------------------



if (isset($_COOKIE['birthdaygold_theme'])) {
  $website['mode'] = $_COOKIE['birthdaygold_theme'];
}
switch ($website['mode']) {
  case 'dark':
    $thememodecssfile = 'bootstrap-dark.min.css';
    break;
  default:
    $thememodecssfile = 'bootstrap.min.css';
    break;
}


$csrf_token = $session->get('csrf_token', bin2hex(random_bytes(32)), 'set');
if ($client_ip == '52.22.66.203') $apibypass = true; // validator.org

// ----
$activeuser = $account->isactive();
$uri = $website['fulluri']['uri'];
if (empty($apibypass)) {
  if (empty($activeuser)) {
    // Check if the URI contains the '/myaccount/' directory
    if (strpos($uri, '/myaccount/') !== false) {
      session_tracking('myaccount_access_failure');
      $system->redirectUser('myaccount_access_failure');
    }

    // For admin access, check if the URI contains the '/myaccount/' directory and has '-admin' in it
    if (strpos($uri, '/myaccount/') !== false && strpos($uri, '-admin') !== false && !$account->isadmin()) {
      session_tracking('myaccount_admin_access_failure');
      $system->redirectUser('myaccount_admin_access_failure');
    }

    // For admin access, check if the URI contains the '//' directory 
    if (strpos($uri, '/admin/')  !== false && !$account->isadmin()) {

      if (isset($securityoverride_referrer) && $_SERVER['HTTP_REFERER'] == $securityoverride_referrer) {
        session_tracking('securityoverride_referrer', $_SERVER['HTTP_REFERER']);
      } else {
        session_tracking('admin_access_failure');
        $system->redirectUser('admin_access_failure');
      }
    }

    // For staff access, check if the URI contains the '/staff/' directory 
    if (strpos($uri, '/staff/')  !== false && !$account->isstaff()) {
      session_tracking('staff_access_failure');
      $system->redirectUser('staff_access_failure');
    }
  } else {
    $current_user_data = $session->get('current_user_data');
    if (empty($current_user_data)) {
      $current_user_data = $account->getuserdata($activeuser['user_id'], 'user_id');
    }
  }
}



#-------------------------------------------------------------------------------
# SESSION TRACKING FUNCTION
#-------------------------------------------------------------------------------
function session_tracking($name = '', $trackingdata = array(), $p_pagename = '')
{
  global $database, $qik, $current_user_data, $client_ip, $session, $site, $footerappversion;
  // --------------------------------------------------------------------------------------------------
  // Use $_SERVER['SCRIPT_NAME'] as default if $pagename is not provided
  if ($p_pagename == '' || $p_pagename == '__NOREQUESTDATA__') {
    $pagename = $_SERVER['SCRIPT_NAME'];
  }
  ksort($_SERVER);
  ksort($_SESSION);
  $sorted_SERVER = $_SERVER;
  $sorted_SESSION = $_SESSION;
  // Use session data if available, encode it if it's an array and not empty
  $sessiondata = isset($_SESSION) && !empty($_SESSION) ? (is_array($_SESSION) ? json_encode($sorted_SESSION, JSON_PRETTY_PRINT) : $_SESSION) : null;


  // --------------------------------------------------------------------------------------------------
  // Check if tracking data is an array and not empty, encode it; otherwise store it as is
  if (is_array($trackingdata) && !empty($trackingdata)) {
    $trackingdata = json_encode($trackingdata, JSON_PRETTY_PRINT);
  } elseif (empty($trackingdata)) {
    $trackingdata = null;
  } // If not an array and not empty, $trackingdata stays as it is


  // --------------------------------------------------------------------------------------------------
  // Get user ID and username from current user data if available
  $userid = is_array($current_user_data) && isset($current_user_data['user_id']) ? $current_user_data['user_id'] : null;
  $username = is_array($current_user_data) && isset($current_user_data['username']) ? $current_user_data['username'] : null;


  // --------------------------------------------------------------------------------------------------
  // set the TYPE 
  $type = 'user';
  $impersonator = $session->get('impersonator', '');
  if (!empty($impersonator))  $type = json_encode($impersonator, JSON_PRETTY_PRINT);


  // --------------------------------------------------------------------------------------------------
  // Structure request data with method and data, encode as JSON
  $requestdata = !empty($_REQUEST) ? [
    'method' => $_SERVER['REQUEST_METHOD'],
    'data' => $_REQUEST
  ] : null;

  if ($p_pagename == '__NOREQUESTDATA__') $requestdata = null;
  $requestdata = $requestdata ? json_encode($requestdata, JSON_PRETTY_PRINT) : null;


  // --------------------------------------------------------------------------------------------------
  // Encode server data as JSON
  $serverdata = json_encode($sorted_SERVER, JSON_PRETTY_PRINT);


  // Truncate the name if it exceeds 255 characters
  if (strlen($name) >= 255) {
    $original_name = $name;
    $name = substr($name, 0, 250) . '...>';

    // Update tracking_data by concatenating the original name and tracking data
    $trackingdata = $original_name . ' | ' . $trackingdata;
  }



  // --------------------------------------------------------------------------------------------------
  // CHECK FOR ERROR PAGES
  $logerrorpage = false;
  if (preg_match('/(404\.php|500\.php|401\.php|403\.php)$/', $pagename)) {
    // Extract information from $_SESSION and $_SERVER
    $capturesession_id = session_id() ?? null;
    $city = $_SESSION['client_locationdata']['city'] ?? null;
    $state = $_SESSION['client_locationdata']['regionName'] ?? null;
    $country_code = $_SESSION['client_locationdata']['countryCode'] ?? null;
    $lon = $_SESSION['client_locationdata']['lon'] ?? null;
    $lat = $_SESSION['client_locationdata']['lat'] ?? null;
    $hit = $_SERVER['REQUEST_URI'] ?? null;
    if (!empty($hit)) $hit = substr($hit, 0, 5000);
    $requestdata = $_SERVER['REQUEST_URI'] ?? null;
    $logerrorpage = true;
  }



  // --------------------------------------------------------------------------------------------------
  // Prepare SQL query
  $sql = "INSERT INTO bg_sessiontracking (`name`, `page`, `sessionid`, `ip`, `user_id`, `username`, `type`, `tracking_data`, `session_data`, `request_data`, `server_data`, `site`, `server`, `version`, `create_dt`) 
   VALUES (:name, :pagename, :sessionid, :ip, :userid, :username, :type, :trackingdata, :sessiondata, :requestdata, :serverdata, :site, :server, :version, NOW())";
  $stmt = $database->prepare($sql);



  // --------------------------------------------------------------------------------------------------
  // Execute the SQL query
  $stmt = $stmt->execute([
    'name' => $name,
    'pagename' => $pagename,
    'sessionid' => session_id(),
    'ip' => $client_ip,
    'userid' => $userid,
    'username' => $username,
    'type' => $type,

    'sessiondata' => $qik->storeDataIfChanged('sessiondata', $sessiondata),

    'trackingdata' => $qik->storeDataIfChanged('trackingdata', $trackingdata),
    'requestdata' => $qik->storeDataIfChanged('requestdata', $requestdata),
    'serverdata' => $qik->storeDataIfChanged('serverdata', $serverdata),

    'site' => $site,
    'server' => $_SERVER['SERVER_ADDR'],
    'version' => $footerappversion
  ]);
  $capturesession_id = $database->lastInsertId();


  // --------------------------------------------------------------------------------------------------
  // HANDLE ERROR PAGES
  if ($logerrorpage) {
    // Prepare and execute the SQL statement
    $stmt = $database->prepare("INSERT INTO `bg_errors` (`cip`, `type`, `hit`, `city`, `state`, `country_code`, `lon`, `lat`, `data_string`, `create_dt`, `session_id`) 
                                VALUES (:cip, :type, :hit, :city, :state, :country_code, :lon, :lat, :data_string, now(), :session_id)");
    $stmt->execute([
      'cip' => $client_ip,
      'type' => $pagename,
      'hit' => $hit,
      'city' => $city,
      'state' => $state,
      'country_code' => $country_code,
      'lon' => $lon,
      'lat' => $lat,
      'data_string' => $sessiondata,
      'session_id' => $capturesession_id
    ]);
  }

  

  // --------------------------------------------------------------------------------------------------
  // -- do rate limiting
  $current_time = time();
  if (empty($nolockout)) {
    $pagecount_second = $session->get('pagecount_second', 0, true);
    $pagecount_minute = $session->get('pagecount_minute', 0, true);
    $lastvisit_dt = $session->get('lastvisit_dt', $current_time, true);

    if ($current_time - $lastvisit_dt >= 1) {
      $pagecount_second = 0;
    }
    if ($current_time - $lastvisit_dt >= 60) {
      $pagecount_minute = 0;
    }

    $pagecount_second++;
    $pagecount_minute++;

    if ($pagecount_second > 40 || $pagecount_minute > 150) {
      $lockout_count =  $database->count('bg_lockout', "ip=:ip and `status`!='never_block'", ['ip' => $client_ip]);

      // --------------------------------------------------------------------------------------------------
      // Calculate block duration with exponential growth and limit it to a maximum of 99999 minutes
      $block_duration = min(pow(2, $lockout_count), 99999); // 2^lockout_count but no more than 99999 minutes

      $sql = "INSERT INTO bg_lockout (ip, expire_dt, session_id, start_dt, create_dt, modify_dt, `status`, `type`) 
      VALUES (:ip, DATE_ADD(NOW(), INTERVAL :duration MINUTE), :sessionid, NOW(), NOW(), NOW(), 'active', 'rate_limit')";
      $database->query($sql, ['ip' => $client_ip, 'duration' => $block_duration, 'sessionid' => session_id()]);
      session_tracking('BLOCKED: excessive requests');
      die("You're temporarily blocked due to excessive requests. [$client_ip]");
    }

    $session->set('pagecount_second', $pagecount_second);
    $session->set('pagecount_minute', $pagecount_minute);
    $session->set('lastvisit_dt', $current_time);
  }
}



// ===========================================================================
// ===========================================================================
// do some session tracking -- skip if: known server / nosessiontracking flag enabled
$ignored_ips = ['3.93.16.208', '54.146.11.169']; // List of IPs to ignore
if (isset($_SERVER['HTTP_SITE_CHECKER'])) {
  if ($_SERVER['HTTP_SITE_CHECKER'] == $sitesettings['app']['SITE_TRACKER_KEY'])  $nosessiontracking = true;
}
if (!in_array($client_ip, $ignored_ips) && empty($nosessiontracking)) {
  session_tracking('session_tracking');
}
