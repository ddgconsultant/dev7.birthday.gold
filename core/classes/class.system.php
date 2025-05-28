<?php
// system.php



# ##==================================================================================================================================================
# ##==================================================================================================================================================
# ##==================================================================================================================================================
class CustomExceptionHandler
{
# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function report(Throwable $e)
{
    error_log($e);  // Or however you'd like to log the exception.
    #  echo print_r($e, 1);
}
# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function render(Throwable  $e)
{
    $displaymode = 'page';
    // You might have more complex logic here depending on the type of exception.
    if (is_integer($e->getCode()))       http_response_code($e->getCode());
    #  echo $e->getMessage();
    $variables = print_r($e, 1);
    $dir = str_replace("/", "\\", $_SERVER['DOCUMENT_ROOT']);
    $search = array($dir);
    $replace = array('[WEBSERVER_DIRECTORY]');
    $variables = str_replace($search, $replace, $variables);
    $output = "  <h5>" . $e->getCode() . "</h5>     <p><b>{$e->getMessage()}</b></p>
<span style='color: black;'><pre>" . $variables . "</pre><BR>
<pre><small>" . date('r') . "</small></pre>
</span>
";

    switch ($displaymode) {
        case 'page':
            $compressed = gzcompress($output, 9);
            $compressed = $output;
            // URL encode the compressed data for safe transmission
            #$encoded = urlencode(base64_encode($compressed));
            #$encoded=base64_encode($output);
            $encoded = urlencode($output);
            if (!headers_sent()) {
                ob_clean();
                flush();
                echo "<script>
var encoded = '" . $encoded . "';  // replace this with your encoded data
window.location.href = '/error?e=' + encoded;
</script>";
                #header('location: /error?e='.$encoded);
                exit;
            } else {
                // Headers are already sent, handle error directly without redirect
                echo "Error: " . $encoded;
            }

            #ob_clean(); flush();
            #header('location: /error?e='.$encoded); 
            break;
        case 'html':
            echo    "<html><head>
<title>Error</title>
<style>.error-message{border:1px solid red;background-color:#ffcccc;color:red;padding:10px;margin:10px;border-radius:5px;}</style>
</head>
<body>
<div class='error-message'>
<h2>Oops... An Error Occurred:</h2>
" . $output . "
</div>
</body>
</html>
";
            break;
    }
}
}


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
set_exception_handler(function ($e) {
$handler = new CustomExceptionHandler();
$handler->report($e);
$handler->render($e);
});




# ##==================================================================================================================================================
# ##==================================================================================================================================================
# ##==================================================================================================================================================
class Request
{
private $params;
private $headers;
private $cookies;

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function __construct(array $params = [], array $headers = [], array $cookies = [])
{
    $this->params = $params;
    $this->headers = $headers;
    $this->cookies = $cookies;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getParam($key)
{
    // Check if the parameter exists in the params array
    if (array_key_exists($key, $this->params)) {
        return $this->params[$key];
    }

    // Check if the parameter exists in the _REQUEST string
    if (isset($_REQUEST[$key])) {
        return $_REQUEST[$key];
    }

    // If the parameter doesn't exist, return null
    return null;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getHeader($key)
{
    return $this->headers[$key] ?? null;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getCookie($key)
{
    return $this->cookies[$key] ?? null;
}


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function merge(array $data)
{
    $this->params = array_merge($this->params, $data);
}
}



# ##==================================================================================================================================================
# ##==================================================================================================================================================
# ##==================================================================================================================================================
class System
{

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function varset($variable, $default = '')
{

    if (!isset($variable)) $variable = $default;
    return $variable;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function avarset($arrayname, $variablename, $default = '')
{
    if (!isset($arrayname[$variablename])) $variable = $default;
    else $variable = $arrayname[$variablename];
    return $variable;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public  function is_production()
{
    global $mode; // Access the global $mode variable
    return $mode == 'production'; // Return true if $mode is 'prod', indicating production
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function initvar($variablename, $default = '')
{
    $temparray = [];
    if (!is_array($variablename)) $variablename = explode(',', $variablename);
    foreach ($variablename as $variable) {
        $temparray[$variable] = $default;
    }
    return $temparray;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getcountryviaip($ip = '', $reset = '')
{
    if ($ip == '') return false;
    global $session;
    $client_locationdata = $session->get('client_locationdata', 'notset');

    if ($client_locationdata == 'notset' || $reset != '') {
        $ip_data = new stdClass();
        $ip_data->status = 'unset';

        // Initialize cURL session
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://ip-api.com/json/{$ip}?fields=49934335",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache"
            ),
        ));

        // Execute cURL session
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            // Log cURL errors
            error_log("cURL Error #:" . $err);
        } else {
            $ip_data = json_decode($response);
    
            if ($ip_data && $ip_data->status == 'success') {
                $client_locationdata = array_map('trim', get_object_vars($ip_data));
            }                
        }
    }

    $session->set('client_locationdata', $client_locationdata);
    return $client_locationdata;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getcountryviaip_old($ip = '', $reset = '')
{
    if ($ip == '') return false;
    global $session;
    $client_locationdata = $session->get('client_locationdata', 'notset');

    if ($client_locationdata == 'notset' || $reset != '') {
        $ip_data = new stdClass();
        $ip_data->status = 'unset';

        $file_contents = @file_get_contents("http://ip-api.com/json/{$ip}?fields=49934335");

        if ($file_contents === false) {
            // Log the error or handle the failure case
            error_log('Failed to fetch IP data.');
        } else {
            $ip_data = json_decode($file_contents);

            if ($ip_data && $ip_data->status == 'success') {
                $client_locationdata = get_object_vars($ip_data);
            }
        }
    }

    $session->set('client_locationdata', $client_locationdata);
    return $client_locationdata;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function mapToUSATimezones($originalTimezone)
{
    // Define the mapping
    $mapping = [
        'America/New_York' => ['America/New_York', 'America/Toronto', 'America/Detroit'], // 'Eastern'
        'America/Chicago' => ['America/Chicago', 'America/Mexico_City'], //'Central'
        'America/Denver' => ['America/Denver', 'America/Phoenix'], // 'Mountain'
        'America/Los_Angeles' => ['America/Los_Angeles', 'America/Anchorage'] //'Pacific' 
    ];

    // Loop through the mapping to find the corresponding USA timezone
    foreach ($mapping as $usaTimezone => $originalTimezones) {
        if (in_array($originalTimezone, $originalTimezones)) {
            return $usaTimezone;
        }
    }

    // If not found, return the original timezone or some default value
    return $originalTimezone;

    // // Test the function
    // echo mapToUSATimezones("America/Toronto");  // Output: Eastern
    // echo mapToUSATimezones("America/Los_Angeles");  // Output: Pacific
    // echo mapToUSATimezones("America/Chicago");  // Output: Central

}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getipaddress($reset = '')
{
    global $session;
    $ip = $session->get('client_ip', 'notset');
    $done = false;
    $source = 'notset';
    $step = 0;

    if ($ip == 'notset' || $reset != '') {
        // Try to get IP from X-Real-IP header
        $step++;
        $ip = (isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '');
        if ($ip != '') {
            $done = true;
            $source = 'HTTP_X_REAL_IP';
        }

        // Fallback to X-Forwarded-For if X-Real-IP not available
        if (!$done) {
            $step++;
            $ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '');
            if ($ip != '') {
                // Consider using a more robust method to parse X-Forwarded-For
                // in case it contains multiple IP addresses.
                $ip_list = explode(',', $ip);
                $ip = trim($ip_list[0]); // Taking the first IP from the list
                $done = true;
                $source = 'HTTP_X_FORWARDED_FOR';
            }
        }

        if (!$done) {
            $step++;
            $ip = (isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '');
            if ($ip != '') {
                $done = true;
                $source = 'HTTP_CLIENT_IP';
            }
        }

        // Fallback to REMOTE_ADDR if none of the headers are set
        if (!$done) {
            $step++;
            $ip = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
            if ($ip != '') {
                $done = true;
                $source = 'REMOTE_ADDR';
            }
        }

        if (!$done ||  ($ip != '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)) {
            $step++;
            $ip = @file_get_contents('https://api.ipify.org');
                // Check if the API call was successful
if ($ip === false) {
    // Record the error in session_tracking
    session_tracking("Error - system->getipaddress()", "Unable to fetch IP address from api.ipify.org");
} else {
    $ip = trim($ip); // Remove any extra whitespace
    if ($ip != '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
        $done = true;
        $source = 'ipify';
    } else {
        // Record if the IP is not valid
        session_tracking("Error - system->getipaddress()", "Invalid IP address received from api.ipify.org: $ip");
    }
}
        }
    }

    // Set session variables as before
    $session->set('client_ip', $ip);
    $session->set('client_REMOTE_ADDR', $_SERVER['REMOTE_ADDR']);
    $session->set('client_ipsource', $source);

    return $ip;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getprivateipaddress()
{
    // Check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->isValidIpAddress($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    // Check for IPs passing through proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Check if multiple IPs are set and take the first one
        $ip = (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['HTTP_X_FORWARDED_FOR'];
        if ($this->isValidIpAddress($ip)) {
            return $ip;
        }
    }

    // Return remote IP (most reliable)
    if (!empty($_SERVER['REMOTE_ADDR']) && $this->isValidIpAddress($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }

    // If all else fails, return unknown
    return 'UNKNOWN';
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------

public function checkAvailabilityAndUpdateStatus($url, $port) {
    // Check if the URL is reachable
    $is_available = $this->isUrlReachable($url, $port);

    // Update the database with the availability status
    $this->updateAvailabilityStatus($url, $port, $is_available);

    // Return the availability status (for displaying on admin page)
    return $is_available;
}


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
private function isUrlReachable($url, $port, $timeout = 5, $maxRetries = 1) {
    if (!is_numeric($port) || $port < 1 || $port > 65535) {
        return false; // Invalid port
    }

    $hostname = parse_url($url, PHP_URL_HOST) ? parse_url($url, PHP_URL_HOST) : $url;
    $attempt = 0;

    // Set up error handling to prevent any error output
    set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext = []) {
        // Handle the error here (e.g., log to a file, ignore, etc.)
        return true; // Always return true to indicate that the standard PHP error handler should not run
    });

    while ($attempt < $maxRetries) {
        $fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
        if ($fp) {
            fclose($fp);
            restore_error_handler();  // Restore the previous error handler
            return true; // URL is reachable
        } else {
            $attempt++;
            sleep(1); // Wait for a second before retrying
        }
    }

    restore_error_handler();  // Restore the previous error handler
    return false; // URL is not reachable after retries
}




# ##--------------------------------------------------------------------------------------------------------------------------------------------------

private function updateAvailabilityStatus($url, $port, $is_available) {
    global $database;

    // Update the database with the availability status
    $status = $is_available ? 'green' : 'red';
    $column = $is_available ? 'last_success_dt' : 'last_failure_dt';
    
    // Prepare the SQL query with placeholders
    $sql = "UPDATE bg_system_availability SET `system_status` = :status, `$column` = NOW(), modify_dt=now() WHERE `url` = :url AND `port` = :port";
    
    // Prepare the statement with the database connection
    $stmt = $database->prepare($sql);
    
    // Execute the prepared statement with the specified parameters
    $stmt->execute([
        ':status' => $status,
        ':url' => $url,
        ':port' => $port
    ]);
}




# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function isValidIpAddress($ip)
{
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return true;
    }
    return false;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function isBot($userAgent = '') {
    // Use server's HTTP_USER_AGENT if no argument is provided and it's set
    if (empty($userAgent) && isset($_SERVER['HTTP_USER_AGENT'])) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
    }

    // Return false immediately if no user agent is provided or found
    if (empty($userAgent)) {
        return false;
    }

    // Access global database variable
    global $database;

    // Fetch active bots from the database
    $stmt = $database->query("SELECT id, name FROM bg_ref_bots WHERE `status`='A'");
        
    // Make the user agent string lowercase for easier matching
    $userAgent = strtolower($userAgent);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $bot) {
        // Ensure $bot is an array and $bot['name'] is set and is a string
        if (is_array($bot) && isset($bot['name']) && is_string($bot['name'])) {
            // Check if current user agent contains any bot name
            if (stripos($userAgent, strtolower($bot['name'])) !== false) {
                    // Ensure $bot['id'] is set and is an integer or can be used as one
                if (isset($bot['id']) && (is_int($bot['id']) || ctype_digit($bot['id']))) {
                    // Update visit count and modify date for the detected bot            
                    $database->query("UPDATE bg_ref_bots SET visit_count = visit_count + 1, modify_dt = NOW() WHERE id = :id", [':id' => $bot['id']]);
                    // Return the bot data
                    return $bot;
                }
            }
        }
    }
    

    // Return null if no bot is detected
    return null;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function curlRequest($url, $headers, $data = [], $method = 'POST', $options = []) {
    global $sitesettings, $mode;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    // Configure SSL/TLS based on environment
    if ($mode === 'dev') {
        // Disable SSL verification for development
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    } else {
        // Enable SSL verification and set CA path
        if (!empty($sitesettings['ssl_cert']['pem_path'])) {
            $caPath = $sitesettings['ssl_cert']['pem_path'];
            if (!file_exists($caPath)) {
                die("CA file not found: $caPath");
            }
            curl_setopt($ch, CURLOPT_CAINFO, $caPath);
        } else {
            die("CA path not configured in \$sitesettings['ssl_cert']['pem_path']");
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }

    // Force TLS 1.2 for compatibility
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

    // Debugging CURL version and SSL backend
   # echo 'CURL Version: ' . curl_version()['version'] . PHP_EOL;
   # echo 'SSL Backend: ' . curl_version()['ssl_version'] . PHP_EOL;

    // Handle POST data
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute CURL request
    $responseRaw = curl_exec($ch);
    $error = curl_error($ch);

    // Close CURL session
    curl_close($ch);

    // Parse response
    $response = [];
    if ($responseRaw === false) {
        $response['error'] = $error;
    } else {
        $response['raw'] = $responseRaw;
        $response['decoded'] = json_decode($responseRaw, true);
    }

    return $response;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function currenturl($type = 'full')
{
    $url['protocol'] = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url['domainName'] = $_SERVER['HTTP_HOST'];
    $url['uri'] = $_SERVER['REQUEST_URI'];
    $search = array('/', '?');
    $url['uri_stripped'] = str_replace($search, '', $_SERVER['REQUEST_URI']);
    $url['querystring'] = $_SERVER['QUERY_STRING'];
    $url['page'] = $_SERVER['PHP_SELF'];
    if (isset($_SERVER['HTTP_REFERER']))  $url['referer'] = $_SERVER['HTTP_REFERER'];
    else  $url['referer'] = 'unknown';
    $url['full'] = $url['protocol'] . $url['domainName'] . $url['querystring'];
    return $url;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function impersonatepassword($input)
{
    $result = false;
    if ($input == '!!BigMoneyToday8Figures!')  $result = true;

    return $result;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function validatetoken($input = '', $type = 'csrf', $redirect = true)
{
    $result = false;
    global $session;
    switch ($type) {
        case 'csrf':
            if (isset($input['_token']) && $input['_token']  && $input['_token'] == $session->get('csrf_token'))
                $result = true;
            break;
    }

    if (!$result && $redirect) {
        $pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
Your session expired.  Login required.
<button type="button" class="close" data-dismiss="alert" aria-label="Close">
<span aria-hidden="true">&times;</span>
</button>
</div>';
        $transferpage['url'] = '/login';
        $transferpage['loginredirect'] = $_SERVER['REQUEST_URI'];
        $transferpage['message'] = $pagemessage;
        $this->endpostpage($transferpage);
    }

    return $result;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function sanitize_datetimex($datetime, $options = '', $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $datetime);
    // The date is not valid if:
    // - The date couldn't be parsed into a DateTime object
    // - The date after being formatted doesn't match the original date
    //   (this can happen if the input is something like '2021-02-30')
    if ($d !== false && $d->format($format) === $datetime) {
        return $datetime;
    } elseif (strpos($options, 'blankok')) {
        return '';
    } else {
        throw new Exception("Invalid date: $datetime - options= $options");
    }
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function sanitize_datetime($datetime, $options = '')
{
    if ($datetime === null) {
        return null;
    }

    $in_format = $this->getvalue($options, 'informat') ?? 'm/d/Y';
    $out_format = $this->getvalue($options, 'outformat') ?? 'Y-m-d H:i:s';

    $d = DateTime::createFromFormat($in_format, $datetime);

    // Check if the date was parsed successfully and matches the original date
    if ($d !== false && $d->format($in_format) === $datetime) {
        // Reformat the date to the desired output format
        $formattedDate = $d->format($out_format);
        return $formattedDate;
    } elseif (strpos($options, 'blankok') !== false) {
        return '';
    } else {
        throw new Exception("Invalid date: $datetime - options = $options");
    }
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function sanitize_date($datetime, $options = '')
{
    if (!strpos($options, 'outformat')) $options .= ',outformat=Y-m-d';
    return $this->sanitize_datetime($datetime, $options);
}


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function sanitize_time($datetime, $options = '')
{
    if (!strpos($options, 'outformat')) $options .= ',outformat=H:i:s';
    return $this->sanitize_datetime($datetime, $options);
}


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getvalue($string, $key)
{
    $options = explode(',', $string);
    foreach ($options as $option) {
        $pair = explode('=', $option);
        if (count($pair) === 2 && $pair[0] === $key) {
            return $pair[1];
        }
    }
    return null; // Key not found or invalid string format
}


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getage($birthDate)
{
    $currentDate = new DateTime();
    $birthDateObj = new DateTime($birthDate);
    $age = $currentDate->diff($birthDateObj)->y;

    // Assign the tag based on the age
    $tag = ($age < 18) ? "Minor" : "Adult";

    return array('age' => $age, 'tag' => $tag);
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function endpostpage($input = '', $id = '')
{
    global $session, $display;
    $pagedata = array();
    $messagetype = 'primary';
    $message = $input;
    $id = '';
    $loginredirect = '';
    $url = $_SERVER['REQUEST_URI'];

    if (is_array($input)) {
        ## Full set was sent
        if (isset($input['message'])) $message = $input['message'];
        if (isset($input['url'])) $url = $input['url'];
        if (isset($input['id'])) $id = $input['id'];
        if (isset($input['messagetype'])) $messagetype = $input['messagetype'];
        if (isset($input['loginredirect'])) $loginredirect = $input['loginredirect'];
    }

    if (is_string($message) && !strpos($message, 'alert')) {
        $message = '<div class="alert alert-' . $messagetype . ' " role="alert">' . $message . '</div>';
    }
    

    $gotourl = $url;
    $pagehash_new = hash('sha256', trim($url, '/'));
    $pagehash_old = str_replace('/', '', $url);

    // Store both new and old hash formats in the session for backward compatibility
    $pagedata['url'] = $session->set('postpagemessage-' . $pagehash_new, $message);
    $pagedata['url_old'] = $session->set('postpagemessage-' . $pagehash_old, $message);

    $pagedata['id'] = $session->set('postpageid-' . $pagehash_new, $id);
    $pagedata['id_old'] = $session->set('postpageid-' . $pagehash_old, $id);

    if ($loginredirect != '') {
        $pagedata['loginredirect'] = $session->set('postloginredirect-' . $pagehash_new, $loginredirect);
        $pagedata['loginredirect_old'] = $session->set('postloginredirect-' . $pagehash_old, $loginredirect);
    }

    if (!isset($input['selfredirecting'])) {
        header('Location: ' . $gotourl);
        exit();
    }
    return;
}


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function clearpage()
{
    $found = 0;
    global $session;
    foreach ($_SESSION as $key => $value) {
        if (substr($key, 0, 8) == 'postpage') {
            $session->unset($key);
            $found++;
        }
    }
    return $found;
}




# ##--------------------------------------------------------------------------------------------------------------------------------------------------
// Convert HTML to Rocket.Chat Markdown
function convertHtmlToMarkdown($html) {
    $markdown = $html;

    // Convert basic HTML tags to markdown equivalents
    $markdown = preg_replace('/<strong>(.*?)<\/strong>/', '*$1*', $markdown); // Bold
    $markdown = preg_replace('/<em>(.*?)<\/em>/', '_$1_', $markdown);        // Italics
    $markdown = preg_replace('/<br\s*\/?>/', "\n", $markdown);              // Line breaks
    $markdown = preg_replace('/<a href="(.*?)">(.*?)<\/a>/', '[$2]($1)', $markdown); // Links

    // Strip any remaining HTML tags
    $markdown = strip_tags($markdown);

    return $markdown;
}


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function postToRocketChat($message_in, $destination_user_channel, $sender = 'Goldie', $avatar_url = '', $include_attachments = false, $DEBUG = false) {
    $chatsettings['ROCKETCHAT_WEBHOOK'] = "https://chat.birthdaygold.cloud/hooks/66f8e50d5af30cb428cfa142/cnjGjtQeLdGNDdiGHWtTKD6SijJTyJ3xabPgcmeNiugCJApe"; // BG::$system->postToRocketChat
 
    if ($avatar_url=='') {
    switch ($sender) {
    case 'Goldie';
    $avatar_url = 'https://cdn.birthday.gold/public/images/bgchat_goldie_icon.png';  // goldie
    break;
    case 'System';
    case 'BGAlerter';
    $avatar_url = 'https://cdn.birthday.gold/public/images/bgchat_system_icon.png';  // system
    break;
    }
    }

    if (is_array($message_in)) {
        $message = $message_in['message'];
if (isset($message_in['nopreview'])) {
        
    $message = preg_replace('/(http[s]?:\/\/[^\s]+)/', '<$1>', $message);
    $nopreview= true;
}
    } else {
        $message = $message_in;
    }

    // Basic payload structure with markdown-enabled text
    $data = [
        "channel" => $destination_user_channel, // Post to the specified channel or user
        "username" => $sender,                  // Name of the sender
        "text" => $message,                     // Main message text
        "mrkdwn" => true,                       // Enable markdown in the message text
    ];
if (isset( $nopreview )) { $data["nopreview"]=true; }


    // Conditionally add the icon URL if provided
    if (!empty($avatar_url)) {
        $data['icon_url'] = $avatar_url; // URL of the sender's avatar
    }

    // Conditionally add attachments if the flag is set
    if ($include_attachments) {
        $data['attachments'] = [
            [
                "title" => "Rocket.Chat",
                "title_link" => "https://rocket.chat",
                "text" => "Rocket.Chat, the best open source chat",
                "image_url" => "https://chat.birthdaygold.cloud/images/integration-attachment-example.png",
                "color" => "#764FA5"
            ]
        ];
    }

    
    // Make the POST request to the Rocket.Chat webhook
    $ch = curl_init($chatsettings['ROCKETCHAT_WEBHOOK']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Optionally disable host verification as well


    // Execute the request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for cURL errors
    if (curl_errno($ch)) {
        if ($DEBUG) echo 'cURL error: ' . curl_error($ch) . '<br>';
    } else {
        // Display HTTP code and response if debugging is enabled
        if ($DEBUG) {
            echo 'HTTP code: ' . $http_code . '<br>';
            echo 'Response: ' . htmlspecialchars($response) . '<br>';
        }

        // Additional logging based on the HTTP code
        if ($http_code !== 200) {
            if ($DEBUG) echo 'Failed to send message. HTTP code: ' . $http_code . '<br>';
        } else {
            if ($DEBUG) echo 'Message sent successfully.<br>';
        }
    }

    // Close the cURL session
    curl_close($ch);
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function startpostpage($input = null)
{
    global $session;
    // Default values for the input array
    $defaultInput = array(
        'message' => '',
        'pageid' => '',
        'url' => '',
    );
    if (isset($input['message']) && $input['message'] == '') unset($input['message']);
    // Merge the defaultInput array with the provided $input array
    $input = is_array($input) ? array_merge($defaultInput, $input) : $defaultInput;

    // Generate a consistent pagehash, but fall back to the old method if needed
    $pageurl = trim($_SERVER['REQUEST_URI'], '/');
    $pagehash_new = hash('sha256', $pageurl);
    $pagehash_old = str_replace('/', '', $_SERVER['REQUEST_URI']);

    // Try retrieving with the new hash first, then fall back to the old method if needed
    $pagemessage = $session->get('postpagemessage-' . $pagehash_new, $input['message']);
    if ($pagemessage == $input['message']) {
        $pagemessage = $session->get('postpagemessage-' . $pagehash_old, $input['message']);
    }

    $pageid = $session->get('postpageid-' . $pagehash_new, $input['pageid']);
    if ($pageid == $input['pageid']) {
        $pageid = $session->get('postpageid-' . $pagehash_old, $input['pageid']);
    }

    $pageloginredirect = $session->get('postloginredirect-' . $pagehash_new,  $input['url']);
    if ($pageloginredirect == $input['url']) {
        $pageloginredirect = $session->get('postloginredirect-' . $pagehash_old,  $input['url']);
    }

    $output = array(
        'message' => $pagemessage,
        'id' => $pageid,
        'url' => $pageurl,
        'loginredirect' => $pageloginredirect,
    );

    // Unset both new and old session values to maintain consistency
    $session->unset('postpagemessage-' . $pagehash_new);
    $session->unset('postpageid-' . $pagehash_new);
    $session->unset('postloginredirect-' . $pagehash_new);
    $session->unset('postpagemessage-' . $pagehash_old);
    $session->unset('postpageid-' . $pagehash_old);
    $session->unset('postloginredirect-' . $pagehash_old);
    return $output;
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function redirectUser($session_tracking_event)
{
    global $session;
    ob_clean();
    flush();
    $transferpagedata['url'] = '/login';
    $transferpagedata['selfredirecting'] = 1;
    session_tracking($session_tracking_event);
    $session->unset('current_user_data');
    if (!headers_sent()) {
        $transferpagedata['message'] = '<div class="alert alert-warning">Oops.  You have to login first</div>';
        $this->endpostpage($transferpagedata);
        header('Location: /login');
    } else {
        global $session;
        $transferpagedata['message'] = '<div class="alert alert-warning">Oops.  You have to login first. [code:678]</div>';
        $this->endpostpage($transferpagedata);
        echo "<script>window.location.href='/login';</script>";
    }
    exit;
}



}
