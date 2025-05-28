<?PHP

function br2nl($string)
{
    return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}

# ##==================================================================================================================================================
# ##==================================================================================================================================================
# ##==================================================================================================================================================
class Qik
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
    public function convert_StateNameToCode($stateName)
    {
        global $bg_systemdata_states;
        $states = $bg_systemdata_states;

        $stateName = ucwords(strtolower($stateName)); // Format the input to match keys in the array
        if (array_key_exists($stateName, $states)) {
            return $states[$stateName];
        }

        return false; // Return false if the state name is not found
    }



    # ##-------------------------------------------------------------------------------------------------------------------------------------------------- 
    function convertamount($amount, $sign = '$', $freeword = 'Free', $decimals = 2)
    {
        // Ensure the amount is treated as a float
        $amount = (float)$amount;

        // If the amount is zero and freeword is provided, return freeword
        if ($amount == 0 && !empty($freeword)) {
            return $freeword;
        }

        // Conversion logic
        if ($amount < 100 && fmod($amount, 1) == 0.00) {
            // If the amount is less than 100, treat as dollars and return formatted as is
            $convertedAmount = $amount;
        } else {
            // If the amount is 100 or more, divide by 100 to convert to dollars
            $convertedAmount = $amount / 100;
        }

        // If the converted amount is 0 and freeword is provided, return freeword
        if ($convertedAmount == 0 && !empty($freeword)) {
            return $freeword;
        }


        // Return the formatted amount with the currency sign
        return $sign . number_format($convertedAmount, $decimals);
    }





    //*************************************************************************************************************************
    //*************************************************************************************************************************
    //*************************************************************************************************************************
    function generateRandomWord($length = 6)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $randomWord = '';
        for ($i = 0; $i < $length; $i++) {
            $randomWord .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomWord;
    }



    //*************************************************************************************************************************
    //*************************************************************************************************************************
    //*************************************************************************************************************************
    public function getvar($haystack = '', $needle = '', $enddelimiter = '&')
    {

        $name = '';
        $value = '';
        $haystack = '&' . $haystack . '&';
        if (!strpos($haystack, '=')) {
            session_tracking('DEBUG:' . $_SESSION['currentpage'] . '-getvar', 'No real value provided: haystack:' . $haystack . '/needle:' . $needle);
            return $haystack;
        }
        if (!strpos($needle, '&'))
            $startsearch = strpos($haystack, '&' . $needle) + 1;
        else
            $startsearch = strpos($haystack, $needle);

        if ($startsearch) {
            $endsearch = strpos($haystack, $enddelimiter, $startsearch);
            if (strpos(substr($haystack, $startsearch, ($endsearch - $startsearch)), '=') !== false)
                list($name, $value) = explode('=', substr($haystack, $startsearch, ($endsearch - $startsearch)), 2);
        }
        return $value;
    }



    //*************************************************************************************************************************
    //*************************************************************************************************************************
    //*************************************************************************************************************************
    function setsesvar($arg, $default = '', $forcesetflag = '')
    {
        if ($default == 'unset') {
            unset($_SESSION[$arg]);
            return null;
        }
        global $session;
        ///////////////////////////////////////////////////////////////////////////////
        if ($default == '_clearall') {
            $count = 0;
            foreach ($_SESSION as $key => $value) {
                if (strpos($key, $arg) !== false) {
                    unset($_SESSION[$key]);
                    $count++;
                }
            }
            return $count;
        }

        ///////////////////////////////////////////////////////////////////////////////
        if (!isset($_SESSION[$arg]) || $forcesetflag != '') {
            $argval = $default;
        } else $argval = $_SESSION[$arg];

        if (!isset($_SESSION[$arg]))
            $session->set($arg, $argval);
        if (!isset($_SESSION[$arg]))
            $session->set($arg, $argval);
        return $argval;
    }



    //*************************************************************************************************************************
    //*************************************************************************************************************************
    //*************************************************************************************************************************
    public function encodelink($data = '', $auth = 'Y')
    {
        return $this->securelink('encode', $data, 'N', $auth);
    }
    public function decodelink($data = '', $auth = 'Y')
    {
        return $this->securelink('decode', $data, 'N', $auth);
    }

    public function securelink($action = '', $data = '', $echo = 'Y', $verifysid = 'Y', $settingstr = '')
    {
        global $session;
        // THIS FUNCTION IS EXTREMELY CRITICAL --- DO NOT CHANGE WITHOUT FULL CHANGE MANAGEMENT APPROVAL!!!!
        // IT HANDLES THE SECURE LINK STRINGS IN ALL HTML LINKS AND EMAILS.  IT ENCODES AND DECODES CRYPTIC INFORMATION
        // TO ALLOW THE SYSTEM TO TRANSPORT SENSITIVE DATA (IE. USERID'S, RECORDID'S, ETC) OVER URL'S
        if ($action == '' || $data == '') return false;
        $settingstr = '&' . $settingstr . '&';
        $usesid = $_SESSION['sid'];
        if (strpos($settingstr, 'sid') !== false) $usesid = $this->getvar($settingstr, 'sid');
        #$chksum=$chksum_digit=$rawchecksum=$d_data=$data='';
        global $dbaccesslib;
        $seedvalue = 97;
        if ($action == 'encodeshort') {
            $offset = rand(0, 9);
            $linkvalue = $offset;
            for ($i = 0; $i < strlen($data); $i++) {
                $value = $offset + substr($data, $i, 1) + $seedvalue;
                $linkvalue .= chr($value);
            }
            return $linkvalue;
        }
        # ##--------------------------------------------------------
        if ($action == 'decodeshort') {
            $linkvalue = '';
            $offset = substr($data, 0, 1) + $seedvalue;
            $tmpdata = substr($data, 1);
            for ($i = 0; $i < strlen($tmpdata); $i++) {
                $value = substr($tmpdata, $i, 1);
                $linkvalue .= (ord($value) - $offset);
            }
            return $linkvalue;
        }

        # ##--------------------------------------------------------
        if ($action == 'encode') {
            $randstr = substr(md5(uniqid(rand(), 1)), 0, rand(10, 29));
            $searchvalue = rand(0, 9);
            $replacevalue = rand(0, 9);
            $seedvalue = rand(10, 89);
            $sidoffset = rand(0, 8);
            $tmpdata = '';
            $chksum = 0;
            for ($i = 0; $i <= strlen($data); $i++) {
                $extrachr = rand(0, 9);
                $tmpdata .= sprintf('%s%s', $seedvalue + substr($data, $i, 1), $extrachr);
            }
            if ($verifysid == 'Y')
                $checksid = rand(0, 4);
            else
                $checksid = rand(5, 9);

            $chksum = 0;
            $chksum_data = sprintf('%s%s%s', $replacevalue, $seedvalue, $tmpdata);
            $chksum_digit = rand(1, 9);
            for ($i = 0; $i <= strlen($chksum_data); $i++) {
                $chksum = $chksum + substr($chksum_data, $i, 1);
            }
            $chksum = ($chksum * $chksum_digit) + (strlen($chksum_data) + $searchvalue + strlen($randstr));

            $linkvalue = sprintf('%s%s_%s%s%s_%s%s%s_%s%s', $searchvalue, $randstr, $replacevalue, $seedvalue, $tmpdata, $sidoffset, substr($usesid, $sidoffset, 5), $checksid, $chksum, $chksum_digit);
            #if ($echo=="Y") echo $linkvalue;
            return $linkvalue;
        }

        # ##--------------------------------------------------------
        if (substr_count($data, "_") != 3) return false;
        if ($action == 'decode' || $action == 'break') {
            list($prefix, $realdata, $suffix, $rawchecksum) = explode('_', $data);
            if ($action == 'break') echo "<BR>$prefix<BR>$realdata<BR>$suffix<BR>$rawchecksum";
            $d_searchvalue = substr($prefix, 0, 1);
            $d_randstr = substr($prefix, 1);
            $sidoffset = substr($suffix, 0, 1);
            $d_checksid = substr($suffix, -1);

            // see if we need to check the data was provided in the same session
            if ($action == 'break') echo '<BR>' . $d_checksid;
            if (abs($d_checksid) < 5 && (substr($usesid, $sidoffset, 5) != substr($suffix, 1, -1))) {
                #if ($echo=='Y')  echo 'false';
                return false;
            }

            $d_replacevalue = substr($realdata, 0, 1);
            $d_seedvalue = substr($realdata, 1, 2);

            $d_data = substr($realdata, 3);
            $linkdata = "";
            for ($i = 0; $i < strlen($d_data) - 3; $i += 3) {
                $linkdata .= sprintf('%s', abs($d_seedvalue - substr($d_data, $i, 2)));
            }

            // see if everything adds back up for the checksum
            $chksum = 0;
            $chksum_digit = substr($rawchecksum, -1);
            for ($i = 0; $i <= strlen($realdata); $i++) {
                $chksum = $chksum + substr($realdata, $i, 1);
            }
            $chksum = ($chksum * $chksum_digit) + (strlen($realdata) + $d_searchvalue + strlen($d_randstr));

            if ($chksum == substr($rawchecksum, 0, -1)) { // life is good
                #echo "checksum success:$chksum - $rawchecksum";
                #if ($echo=="Y")  echo $linkdata;
                return $linkdata;
            }
        }

        # ##--------------------------------------------------------
        $getcount = $this->setsesvar('checksumfailure');

        if ($getcount == '') $failurecount = 0;
        else $failurecount = intval($getcount);
        if ($failurecount > 2) {
            global $dbaccesslib;
            $suspect_uid = $this->setsesvar('uid');
            if ($suspect_uid == '') $suspect_uid = 'null';

            $suspect_memberid = $this->setsesvar('memberid');
            if ($suspect_memberid == '') $suspect_memberid = 'null';

            $suspect_username = $this->setsesvar('username');
            if ($suspect_username == '') $suspect_username = 'null';

            global $client_ip;


            // Lock this user out for a period of time... they could be trying to hack
            $stmt = 'insert ds_lockout (suspect_userid, suspect_memberid, suspect_username, suspect_cip, reason, expirelockdt) values (';
            $stmt .= '' . $suspect_uid . ', ';
            $stmt .= '' . $suspect_memberid . ', ';
            $stmt .= '"' . $suspect_username . '", ';
            $stmt .= '"' . $client_ip . '", ';
            $stmt .= '"possible hack - $quikcodelib->securelink() failure", ';
            $stmt .= 'DATE_ADD(now(),INTERVAL 15 MINUTE)) ';
            list($result, $rowcount) = $dbaccesslib->execute($stmt);
        }
        $failurecount++;
        $this->setsesvar('checksumfailure', $failurecount, 'set');
        if (!isset($chksum)) $chksum = '';
        if (!isset($chksum_digit)) $chksum_digit = '';
        if (!isset($rawchecksum)) $rawchecksum = '';
        if (!isset($d_data)) $d_data = '';
        if (!isset($data)) $data = '';
        session_tracking('checksum failure:' . $failurecount, "$chksum/$chksum_digit/$rawchecksum/$d_data|$data");
        #echo "checksum failure:$chksum / $chksum_digit - $rawchecksum: $d_data";
        return false;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function isPrivateIP($ip)
    {
        // Define the private IP address ranges
        $privateRanges = array(
            '10.0.0.0|10.255.255.255', // 10.0.0.0 - 10.255.255.255
            '172.16.0.0|172.31.255.255', // 172.16.0.0 - 172.31.255.255
            '192.168.0.0|192.168.255.255', // 192.168.0.0 - 192.168.255.255
        );

        // Convert IP address to long integer
        $ipLong = ip2long($ip);

        if ($ipLong !== false) {
            // Check if the IP address falls within any of the private ranges
            foreach ($privateRanges as $range) {
                list($start, $end) = explode('|', $range);
                if ($ipLong >= ip2long($start) && $ipLong <= ip2long($end)) {
                    return false; // IP is in a private network
                }
            }
        }

        return true; // IP is not in a private network
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function getcountryviaip($ip = '')
    {
        #$ip=($ip=='' ? $_SERVER['REMOTE_ADDR'], $ip);
        $ip_data = new stdClass();
        $ip_data->status = 'unset';

        if (!$this->isPrivateIP($ip)) return false;
        $ip_data = @json_decode(file_get_contents("//ip-api.com/json/{$ip}"));

        if ($ip_data && $ip_data->status == 'success') {
            #    echo 'Country: ' . $ip_data->country . ', Country Code: ' . $ip_data->countryCode;
        }
        $array = get_object_vars($ip_data);

        #print_r($array); exit;
        return $array;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function getipaddress()
    {
        $ip = 'unknown';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function formatShortNumber($num, $decimals = 0, $roundUp = false)
    {
        $suffix = '';
        if ($num >= 1000000000) {
            $num = $num / 1000000000;
            $suffix = 'B';
        } elseif ($num >= 1000000) {
            $num = $num / 1000000;
            $suffix = 'M';
        } elseif ($num >= 1000) {
            $num = $num / 1000;
            $suffix = 'K';
        }

        if ($roundUp) {
            $num = round($num, $decimals, PHP_ROUND_HALF_UP);
        } else {
            $num = round($num, $decimals, PHP_ROUND_HALF_DOWN);
        }

        return number_format($num, $decimals) . $suffix;
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
    public function isValidIpAddress($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        return false;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function currentUrl($type = 'full')
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
        $url['full'] = $url['protocol'] . $url['domainName'] . $url['page'] . (!empty($url['querystring']) ? '?' . $url['querystring'] : '');
        return $url;
    }



    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Masks an IP address unless user has impersonation rights
 * 
 * @param string $ip The IP address to mask
 * @return string The masked or unmasked IP address
 */
function maskIP($ip, $settings=[]) {
    // If viewing user is different from record user (impersonation)
    // and has proper permissions, show full IP
    if (isset($settings['admin'])) {
        return $ip;
    }
    
    // Handle IPv4
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', '$1.$2.$3.xxx', $ip);
    }
    
    // Handle IPv6
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        $lastPart = array_pop($parts);
        return implode(':', $parts) . ':xxxx';
    }
    
    // If not valid IP, return as is
    return $ip;
}



    # ##-------------------------------------------------------------------------------------------------------------------------------------------------- 
    public function getbrowser($type, $user_agent)
    {
        if (empty($user_agent)) {
            return false;
        }
        switch ($type) {
            case 'quick':
                if (strpos($user_agent, 'MSIE') !== FALSE)
                    $output = 'Internet explorer';
                elseif (strpos($user_agent, 'Trident') !== FALSE) //For Supporting IE 11
                    $output = 'Internet explorer';
                elseif (strpos($user_agent, 'Firefox') !== FALSE)
                    $output = 'Mozilla Firefox';
                elseif (strpos($user_agent, 'Chrome') !== FALSE)
                    $output = 'Google Chrome';
                elseif (strpos($user_agent, 'Opera Mini') !== FALSE)
                    $output =  "Opera Mini";
                elseif (strpos($user_agent, 'Opera') !== FALSE)
                    $output =  "Opera";
                elseif (strpos($user_agent, 'Safari') !== FALSE)
                    $output =  "Safari";
                else
                    $output =  'Other';
                break;

            case 'full':
                $bname = 'Unknown';
                $platform = 'Unknown';
                $version = "";
                $ub = '';
                //First get the platform?
                if (preg_match('/linux/i', $user_agent)) {
                    $platform = 'linux';
                } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
                    $platform = 'mac';
                } elseif (preg_match('/windows|win32/i', $user_agent)) {
                    $platform = 'windows';
                }

                // Next get the name of the useragent yes seperately and for good reason
                if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
                    $bname = 'Internet Explorer';
                    $ub = "MSIE";
                } elseif (preg_match('/Firefox/i', $user_agent)) {
                    $bname = 'Mozilla Firefox';
                    $ub = "Firefox";
                } elseif (preg_match('/Chrome/i', $user_agent)) {
                    $bname = 'Google Chrome';
                    $ub = "Chrome";
                } elseif (preg_match('/Safari/i', $user_agent)) {
                    $bname = 'Apple Safari';
                    $ub = "Safari";
                } elseif (preg_match('/Opera/i', $user_agent)) {
                    $bname = 'Opera';
                    $ub = "Opera";
                } elseif (preg_match('/Netscape/i', $user_agent)) {
                    $bname = 'Netscape';
                    $ub = "Netscape";
                }

                // finally get the correct version number
                $known = array('Version', $ub, 'other');
                $pattern = '#(?<browser>' . join('|', $known) .
                    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
                if (!preg_match_all($pattern, $user_agent, $matches)) {
                    // we have no matching number just continue
                }

                // see how many we have
                $i = count($matches['browser']);
                if ($i != 1) {
                    //we will have two since we are not using 'other' argument yet
                    //see if version is before or after the name
                    if (strripos($user_agent, "Version") < strripos($user_agent, $ub)) {
                        $version = $matches['version'][0];
                    } else {
                        $version = $matches['version'][1];
                    }
                } else {
                    $version = $matches['version'][0];
                }

                // check if we have a number
                if ($version == null || $version == "") {
                    $version = "?";
                }

                $output = array(
                    'name'      => $bname,
                    'version'   => $version,
                    'platform'  => $platform,
                    'browser_name_pattern'    => $pattern
                );
                break;


            case 'detail':
                // Check if browscap is set
                if (ini_get("browscap")) {
                    $output = get_browser($user_agent, true);
                } else {
                    // Fallback if browscap is not set
                    $output = $this->generateBrowserDetails($user_agent);
                    $output['error'] = "browscap ini directive not set. Using manual detection.";
                }
                break;
        }
        return $output;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function generateBrowserDetails($user_agent)
    {
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version = "";
        $ub = '';

        // Determine platform
        if (preg_match('/linux/i', $user_agent)) {
            $platform = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
            $platform = 'Mac';
        } elseif (preg_match('/windows|win32/i', $user_agent)) {
            $platform = 'Windows';
        }

        // Determine browser
        if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i', $user_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif (preg_match('/Opera/i', $user_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        }

        // Determine version
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (preg_match_all($pattern, $user_agent, $matches)) {
            $i = count($matches['browser']);
            if ($i != 1) {
                if (strripos($user_agent, "Version") < strripos($user_agent, $ub)) {
                    $version = $matches['version'][0];
                } else {
                    $version = $matches['version'][1];
                }
            } else {
                $version = $matches['version'][0];
            }
        } else {
            $version = "?";
        }

        return array(
            'name' => $bname,
            'version' => $version,
            'platform' => $platform
        );
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function browserdetail($type = 'full')
    {
        global $session;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? false;

        $browser = [
            'agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Not provided',
            'request' => $_SERVER['REQUEST_METHOD'] ?? 'Not provided',
            'time' => $_SERVER['REQUEST_TIME'] ?? 'Not provided',
            'accept' => $_SERVER['HTTP_ACCEPT'] ?? 'Not provided',
            'charset' => $_SERVER['HTTP_ACCEPT_CHARSET'] ?? 'Not provided',
            'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Not provided',
            'browser_quick' => $this->getbrowser('quick', $user_agent),
            'browser_full' => $this->getbrowser('full', $user_agent),
        ];

        if ($session->get('browser_detail', 'notset') === 'notset') {
            $browser['browser_detail'] = $this->getbrowser('detail', $user_agent);
            $session->set('browser_detail', $browser['browser_detail']);
        } else {
            $browser['browser_detail'] = $session->get('browser_detail');
        }

        return $browser;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function convertMinutes($expireminutes)
    {
        #$expireminutes = 120;
        #echo convertMinutes($expireminutes);
        // If minutes are less than 60, show them as minutes
        if ($expireminutes < 60) {
            return "{$expireminutes} minutes";
        }
        // If minutes are exactly 60, show as 1 hour
        elseif ($expireminutes == 60) {
            return "1 hour";
        }
        // If minutes are less than 1440 (24 hours * 60 minutes), show them as hours
        elseif ($expireminutes < 1440) {
            $hours = floor($expireminutes / 60);
            $remaining_minutes = $expireminutes % 60;

            if ($remaining_minutes > 0) {
                return "{$hours} hours, {$remaining_minutes} minutes";
            } else {
                return ($hours > 1) ? "{$hours} hours" : "{$hours} hour";
            }
        }
        // If minutes are exactly 1440, show as 1 day
        elseif ($expireminutes == 1440) {
            return "1 day";
        }
        // If minutes are 1440 or more, show them as days
        else {
            $days = floor($expireminutes / 1440);
            $remaining_hours = floor(($expireminutes % 1440) / 60);
            $remaining_minutes = ($expireminutes % 1440) % 60;

            $result = ($days > 1) ? "{$days} days" : "{$days} day";
            if ($remaining_hours > 0) {
                $result .= ($remaining_hours > 1) ? ", {$remaining_hours} hours" : ", {$remaining_hours} hour";
            }
            if ($remaining_minutes > 0) {
                $result .= ", {$remaining_minutes} minutes";
            }

            return $result;
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------

    function generateRandomDate($start = 'now', $end = '2 years ago')
    {
        // Create DateTime objects for start and end
        $startDate = new DateTime($start);
        $endDate = new DateTime($start);

        // Modify the end date based on the subtraction input (like '2 years ago')
        $endDate->modify($end);

        // Ensure the start date is after the end date, if needed (swap them)
        if ($startDate < $endDate) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        // Generate a random timestamp between the start and end range
        $randomTimestamp = rand($endDate->getTimestamp(), $startDate->getTimestamp());

        // Create a DateTime object from the random timestamp
        $randomDate = (new DateTime())->setTimestamp($randomTimestamp);

        return $randomDate->format('Y-m-d H:i:s');
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function logmessage($message, $preformatted = false)
    {
        global $debug, $logBuffer;
 
        //actions
        switch ($message) {
            case '!CLEAR!':
                $logBuffer = [];
                break;

            case '!GET!':
                return $logBuffer;
                break;

            case '!FINALIZE!':
                $retunBuffer = $logBuffer;
                $logBuffer = [];
                return $retunBuffer;
                break;

            default:
                // Store the message in the log buffer
                if (!$preformatted) {
                    $message='<pre style="color:navy">' . $message . "</pre>";
                 }
                  $logBuffer[] = $message;
              
                if ($debug) { 
                        echo $message;
                       // Flush the output buffer and send it to the browser immediately
                       ob_flush();
                       flush();
                       }
                break;
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Function to check and update the session data
    function storeDataIfChanged($key, $data)
    {
        // Keep the original data intact for storage
        $originalData = $filteredData = $data;

        // Define keys to remove based on the type of data
        $keysToRemoveMap = [
            'sessiondata' => ['featuremailcount_cachedtime', 'lastvisit_dt', 'pagecount_minute', 'pagecount_second', 'requestdata_hashholder', 'serverdata_hashholder', 'sessiondata_hashholder', 'trackingdata_hashholder'],
            'serverdata' => ['REQUEST_TIME', 'REQUEST_TIME_FLOAT']
        ];

        // Check if the current key has removal criteria
        if (isset($keysToRemoveMap[$key])) {
            // Remove keys that change often from the filtered data for hashing (only if they exist)
            foreach ($keysToRemoveMap[$key] as $removeKey) {
                if (isset($filteredData[$removeKey])) {
                    unset($filteredData[$removeKey]);
                }
            }
        }

        // Generate the hash for the filtered data
        $currentHash = md5(serialize($filteredData));
        $previousHash = isset($_SESSION[$key . '_hashholder']) ? $_SESSION[$key . '_hashholder'] : null;

        // If the hash has changed, update the session with the new hash and return the original data for storage
        if ($currentHash !== $previousHash) {
            $_SESSION[$key . '_hashholder'] = $currentHash; // Store new hash
            return $originalData; // Return the original data with all keys for storage
        }

        // If the hash hasn't changed, return null to avoid storing duplicate data
        return null;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function timeago999($date, $showdate = 700, $dateformat = 'Y-m-d H:i:s')
    {
        $timezone = new DateTimeZone('America/Denver');
        $date_time = new DateTime($date, $timezone);
        $now = new DateTime('now', $timezone);
        $diff = $now->diff($date_time);

        $differenceArray = array(
            "years" => $diff->y,
            "months" => $diff->m,
            "days" => $diff->d,
            "hours" => $diff->h,
            "minutes" => $diff->i,
            "seconds" => $diff->s
        );

        $message = "just now";
        $messagetag = '';
        $messageunit = '';
        $shorttag = '';

        // Determine the appropriate time unit
        if ($differenceArray['years'] > 0) {
            $messageunit = $differenceArray['years'];
            $messagetag = 'year';
        } elseif ($differenceArray['months'] > 0) {
            $messageunit = $differenceArray['months'];
            $messagetag = "month";
        } elseif ($differenceArray['days'] > 0) {
            if ($differenceArray['days'] > $showdate) {
                // If the difference exceeds the showdate threshold, return the formatted date
                $differenceArray['originaldate'] = $date;
                $differenceArray['now'] = $now;
                $differenceArray["messageunit"] = $messageunit;
                $differenceArray["message"] = '<span title="' . $date . '">' . $date_time->format($dateformat) . '</span>';
                $differenceArray['shortmessage'] = '<span title="' . $date . '">' . $date_time->format($dateformat) . '</span>';
                $differenceArray['shortmessagevalue'] = $date_time->format($dateformat);
                $differenceArray['code'] = 'exceeds';
                return $differenceArray;
            } else {
                $messageunit = $differenceArray['days'];
                $messagetag = "day";
            }
        } elseif ($differenceArray['hours'] > 0) {
            $messageunit = $differenceArray['hours'];
            $messagetag = "hour";
        } elseif ($differenceArray['minutes'] > 0) {
            $messageunit = $differenceArray['minutes'];
            $messagetag = "minute";
        } elseif ($differenceArray['seconds'] > 0) {
            $messageunit = $differenceArray['seconds'];
            $messagetag = "second";
        }

        // Create the short tag (e.g., 'y' for year, 'd' for day, etc.)
        $shorttag = substr($messagetag, 0, 1);

        // Build the full message and short message
        if (!empty($messagetag)) {
            $message = $messageunit . " " . $this->plural($messagetag, $messageunit) . " ago";
            $shortmessage = $messageunit . $shorttag;
        }

        // Populate the array with original date, full message, and short message
        $differenceArray['originaldate'] = $date;
        $differenceArray['now'] = $now;
        $differenceArray["messageunit"] = $messageunit;
        $differenceArray["message"] = '<span title="' . $date . '">' . $message . '</span>';
        $differenceArray['shortmessage'] = '<span title="' . $date . '">' . $shortmessage . '</span>';
        $differenceArray['shortmessagevalue'] = $shortmessage;
        $differenceArray['code'] = 'normal';
        return $differenceArray;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function timeago99($date, $showdate = 700, $dateformat = 'Y-m-d H:i:s', $sameyeardateformat = 'm-d H:i:s')
    {
        $timezone = new DateTimeZone('America/Denver');
        $date_time = new DateTime($date, $timezone);
        $now = new DateTime('now', $timezone);
        $diff = $now->diff($date_time);

        // Calculate the total difference in days (this includes the overall difference)
        $totalDays = ($diff->y * 365) + ($diff->m * 30) + $diff->d;

        $differenceArray = array(
            "years" => $diff->y,
            "months" => $diff->m,
            "days" => $diff->d,
            "hours" => $diff->h,
            "minutes" => $diff->i,
            "seconds" => $diff->s,
            "totalDays" => $totalDays
        );

        // If the total days difference exceeds the showdate, return the formatted date
        if ($totalDays > $showdate) {
            $differenceArray['originaldate'] = $date;
            $differenceArray['now'] = $now;
            $differenceArray["messageunit"] = '';
            $differenceArray["message"] = '<span title="' . $date . '">' . $date_time->format($dateformat) . '</span>';
            $differenceArray['shortmessage'] = '<span title="' . $date . '">' . $date_time->format($dateformat) . '</span>';
            $differenceArray['shortmessagevalue'] = $date_time->format($dateformat);
            $differenceArray['code'] = 'exceeds';

            return $differenceArray;
        }

        // Otherwise, proceed with "time ago" calculation
        $message = "just now";
        $messagetag = '';
        $messageunit = '';
        $shorttag = '';

        if ($differenceArray['years'] > 0) {
            $messageunit = $differenceArray['years'];
            $messagetag = 'year';
        } elseif ($differenceArray['months'] > 0) {
            $messageunit = $differenceArray['months'];
            $messagetag = "month";
        } elseif ($differenceArray['days'] > 0) {
            $messageunit = $differenceArray['days'];
            $messagetag = "day";
        } elseif ($differenceArray['hours'] > 0) {
            $messageunit = $differenceArray['hours'];
            $messagetag = "hour";
        } elseif ($differenceArray['minutes'] > 0) {
            $messageunit = $differenceArray['minutes'];
            $messagetag = "minute";
        } elseif ($differenceArray['seconds'] > 0) {
            $messageunit = $differenceArray['seconds'];
            $messagetag = "second";
        }

        // Create the short tag (e.g., 'y' for year, 'd' for day, etc.)
        $shorttag = substr($messagetag, 0, 1);

        // Build the full message and short message
        if (!empty($messagetag)) {
            $message = $messageunit . " " . $messageunit . ' ' . $messagetag . " ago";
            $shortmessage = $messageunit . $shorttag;
        }

        // Populate the array with original date, full message, and short message
        $differenceArray['originaldate'] = $date;
        $differenceArray['now'] = $now;
        $differenceArray["messageunit"] = $messageunit;
        $differenceArray["message"] = '<span title="' . $date . '">' . $message . '</span>';
        $differenceArray['shortmessage'] = '<span title="' . $date . '">' . $shortmessage . '</span>';
        $differenceArray['shortmessagevalue'] = $shortmessage;
        $differenceArray['code'] = 'normal';

        return $differenceArray;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function timeago($date, $showdate = 700, $dateformat = 'Y-m-d H:i:s', $sameyeardateformat = 'm-d H:i:s')
    {
        $timezone = new DateTimeZone('America/Denver');
        $date_time = new DateTime($date, $timezone);
        $now = new DateTime('now', $timezone);
        $diff = $now->diff($date_time);

        // Calculate the total difference in days (this includes the overall difference)
        $totalDays = ($diff->y * 365) + ($diff->m * 30) + $diff->d;

        // Check if the given date is in the same year
        $isSameYear = ($date_time->format('Y') == $now->format('Y'));

        // Format date based on whether it's in the same year
        $formattedDate = $isSameYear ? $date_time->format($sameyeardateformat) : $date_time->format($dateformat);

        $differenceArray = array(
            "years" => $diff->y,
            "months" => $diff->m,
            "days" => $diff->d,
            "hours" => $diff->h,
            "minutes" => $diff->i,
            "seconds" => $diff->s,
            "totalDays" => $totalDays
        );

        // If the total days difference exceeds the showdate, return the formatted date
        if ($totalDays > $showdate) {
            $differenceArray['originaldate'] = $date;
            $differenceArray['now'] = $now;
            $differenceArray["messageunit"] = '';
            $differenceArray["message"] = '<span title="' . $date . '">' . $formattedDate . '</span>';
            $differenceArray['shortmessage'] = '<span title="' . $date . '">' . $formattedDate . '</span>';
            $differenceArray['shortmessagevalue'] = $formattedDate;
            $differenceArray['code'] = 'exceeds';

            return $differenceArray;
        }

        // Otherwise, proceed with "time ago" calculation
        $message = "just now";
        $messagetag = '';
        $messageunit = '';
        $shorttag = '';

        if ($differenceArray['years'] > 0) {
            $messageunit = $differenceArray['years'];
            $messagetag = 'year';
        } elseif ($differenceArray['months'] > 0) {
            $messageunit = $differenceArray['months'];
            $messagetag = "month";
        } elseif ($differenceArray['days'] > 0) {
            $messageunit = $differenceArray['days'];
            $messagetag = "day";
        } elseif ($differenceArray['hours'] > 0) {
            $messageunit = $differenceArray['hours'];
            $messagetag = "hour";
        } elseif ($differenceArray['minutes'] > 0) {
            $messageunit = $differenceArray['minutes'];
            $messagetag = "minute";
        } elseif ($differenceArray['seconds'] > 0) {
            $messageunit = $differenceArray['seconds'];
            $messagetag = "second";
        }

        // Create the short tag (e.g., 'y' for year, 'd' for day, etc.)
        $shorttag = substr($messagetag, 0, 1);

        // Build the full message and short message
        if (!empty($messagetag)) {
            $message = $this->plural2($messageunit, $messagetag) . " ago";
            $shortmessage = $messageunit . $shorttag;
        }

    // Define a default value for $shortmessage if it's not already set
$shortmessage = isset($shortmessage) ? $shortmessage : ''; // Use an empty string or any default value you want

// Populate the array with original date, full message, and short message
$differenceArray['originaldate'] = $date;
$differenceArray['now'] = $now;
$differenceArray['messageunit'] = $messageunit;
$differenceArray['message'] = '<span title="' . $date . '">' . $message . '</span>';
$differenceArray['shortmessage'] = '<span title="' . $date . '">' . $shortmessage . '</span>';
$differenceArray['shortmessagevalue'] = $shortmessage;
$differenceArray['code'] = 'normal';


        return $differenceArray;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function encodeIdxx($integer)
    {
        $key = 'dR5dpTVHQQpgtCx457c9aoBBRnYjCHPQruB7bKhlrmMkXKji6hn0SZkV1814qU98';
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($integer, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
        $output = urlencode($ciphertext);
        return $output;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function decodeIdxx($ciphertext)
    {
        $key = 'dR5dpTVHQQpgtCx457c9aoBBRnYjCHPQruB7bKhlrmMkXKji6hn0SZkV1814qU98';
        $ciphertext = urldecode($ciphertext);
        $ciphertext = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($ciphertext, 0, $ivlen);
        $hmac = substr($ciphertext, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($ciphertext, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        if (hash_equals($hmac, $calcmac)) {
            return $original_plaintext;
        } else {
            return false;
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function encodeId($number)
    {
        $DEBUG = false;
        if (!is_numeric($number)) {
            if ($DEBUG) {


                session_tracking('encode - failed', 'Not a number');
            }
            return false;
        }

        global $bg_systemdata_qikstaticcodemaps;
        // Convert number to string
        $numberStr = strval($number);

        // Calculate the length of the number string
        $length = strlen($numberStr);

        // Convert length to a character from A to V
        $lengthChar = chr(64 + min($length, 20)); // A=1, B=2, ..., V=22

        // Generate random padding
        $paddingLength = 20 - $length;
        $padding = '';
        for ($i = 0; $i < $paddingLength; $i++) {
            $padding .= mt_rand(0, 9); // Random number between 0 and 9
        }


        // Choose a random mapId
        $mapId = array_rand($bg_systemdata_qikstaticcodemaps);

        // Get the codemap corresponding to the mapId
        $codemap = $bg_systemdata_qikstaticcodemaps[$mapId];

        // Map the digits of the number to the codemap
        $mappedNumber = strtr($numberStr, '0123456789', $codemap);
        $mappedpadding = strtr($padding, '0123456789', $codemap);

        // Concatenate all parts
        $encoded = $lengthChar . $mapId . $mappedNumber . $mappedpadding;

        // Now, insert hyphens to format as 'xxxxxx-xxxxx-xxxxx-xxxxxx'
        $formattedEncoded = substr($encoded, 0, 6) . '-'    // First 6 characters
            . substr($encoded, 6, 5) . '-'    // Next 5 characters
            . substr($encoded, 11, 5) . '-'   // Next 5 characters
            . substr($encoded, 16);           // Last 6 characters


        if ($DEBUG) {
            // Debugging outputs
            $debug_message = '';
            $debug_message .= "Number: $number\n";
            $debug_message .=  "Number Str: $numberStr\n";
            $debug_message .=  "Length: $length\n";
            $debug_message .=  "Length Char: $lengthChar\n";
            $debug_message .=  "Padding: $padding\n";
            $debug_message .=  "Map ID: $mapId\n";
            $debug_message .=  "Codemap: $codemap\n";
            $debug_message .=  "Mapped Number: $mappedNumber\n";
            $debug_message .=  "Mapped Padding: $mappedpadding\n";
            session_tracking('encode', $debug_message);
        }

        return trim($formattedEncoded);
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function decodeIdx($formattedencoded)
    {
        global $bg_systemdata_qikstaticcodemaps;

        // Extract length character and mapId character
        $encoded = str_replace('-', '', $formattedencoded);
        $lengthChar = substr($encoded, 0, 1);
        $mapIdChar = substr($encoded, 1, 1);

        // Convert length character to number
        $length = ord($lengthChar) - 64; // A=1, B=2, ..., V=22

        // Extract mapped number (including padding)
        $mappedNumber = substr($encoded, 2, $length);

        // Get the codemap corresponding to the mapId
        $codemap = $bg_systemdata_qikstaticcodemaps[$mapIdChar];

        // Replace alpha characters with numbers based on codemap
        $decodedNumber = strtr($mappedNumber, $codemap, '0123456789');

        return $decodedNumber;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function decodeId($formattedencoded)
    {
        global $bg_systemdata_qikstaticcodemaps;

        // Input validation
        if (empty($formattedencoded)) {
            return false;
        }

        // Ensure the input is a string
        $formattedencoded = (string)$formattedencoded;

        // Extract length character and mapId character
        $encoded = str_replace('-',
                '',
                $formattedencoded
            );

        // Verify minimum length (at least 2 characters for length and mapId)
        if (strlen($encoded) < 2) {
            return false;
        }

        $lengthChar = substr($encoded, 0, 1);
        $mapIdChar = substr($encoded, 1, 1);

        // Convert length character to number
        $length = ord($lengthChar) - 64; // A=1, B=2, ..., V=22

        // Validate length is within reasonable bounds
        if ($length < 1 || $length > 22
        ) {
            return false;
        }

        // Verify there are enough characters for the specified length
        if (strlen($encoded) < $length + 2) {
            return false;
        }

        // Extract mapped number (including padding)
        $mappedNumber = substr($encoded, 2, $length);

        // Check if the mapIdChar exists in the codemap array
        if (!isset($bg_systemdata_qikstaticcodemaps[$mapIdChar])) {
            return false;
        }

        // Get the codemap corresponding to the mapId
        $codemap = $bg_systemdata_qikstaticcodemaps[$mapIdChar];

        // Ensure codemap is not null or an empty array
        if (is_null($codemap) || empty($codemap)) {
            return false;
        }

        // Replace alpha characters with numbers based on codemap
        $decodedNumber = strtr($mappedNumber, $codemap, '0123456789');

        // Verify the result is numeric
        if (!is_numeric($decodedNumber)) {
            return false;
        }

        return $decodedNumber;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function impersonatepassword($input)
    {
        $result = false;
        global $sitesettings, $mode;
        if ($input == $sitesettings['app']['APP_IMPERSONATEPASS'])  $result = 1;
        if ($mode == 'dev' && $input == $sitesettings['app']['APP_DEVIMPERSONATEPASS'])  $result = 2;
        return $result;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function giftcodepassword($input)
    {
        $result = false;
        global $sitesettings;
        if ($input == $sitesettings['app']['APP_GIFTCODEPASS'])  $result = true;
        return $result;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function checkvariables($input, $required_fields) {
        // Check if input is an array
        if (!is_array($input)) {
            return [
                'valid' => false,
                'error' => 'Input must be an array',
                'missing_fields' => []
            ];
        }
    
        // Track missing fields
        $missing_fields = [];
    
        // Validate required fields exist and are not empty
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || 
                (empty($input[$field]) && !is_numeric($input[$field]))) {
                $missing_fields[] = $field;
            }
        }
    
        if (!empty($missing_fields)) {
            session_tracking('missing_fields!!', $missing_fields);
            return false;
        }
    
        return true;
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
        global $session;
        $pagedata = array();
        $messagetype = 'primary';
        $message = $input;
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


        if (!strpos($message, 'alert')) {
            $message =
                '<div class="alert alert-' . $messagetype . ' alert-dismissible fade show" role="alert">
' . $message . '
<button type="button" class="close" data-dismiss="alert" aria-label="Close">
<span aria-hidden="true">&times;</span>
</button>
</div>';
        }


        $pagedata['url'] = $url;
        $pagedata['message'] = $session->set('pagemessage-' . hash('sha256', $url), $message);
        $pagedata['id'] = $session->set('pageid-' . hash('sha256', $url), $id);
        if ($loginredirect != '') $pagedata['loginredirect'] = $session->set('loginredirect-' . hash('sha256', $url), $loginredirect);
        #breakpoint($pagedata);
        header('Location: ' . $url);
        exit();
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function startpostpage($input = '')
    {
        global $system;
        /*
global $session;
$output=array();
$pageurl = hash('sha256', $_SERVER['REQUEST_URI']);
$pagemessage = $session->get('pagemessage-' . $pageurl, '');
$pageid = $session->get('pageid-' .  $pageurl, '!--notset--!');
$pageloginredirect = $session->get('loginredirect-' . $pageurl, '');
#if ($pageurl!='') {
if ($pageid== '!--notset--!') $pageid='';
$output=array();
$output['message']=$pagemessage;
$output['id']=$pageid;
$output['url']=$pageurl;
$output['loginredirect']=$pageloginredirect; 
return $output;
*/
        return $system->startpostpage($input);

        #}
        #return $output;
        /*
if ($pageid != '!--notset--!')
return array('message'=>$pagemessage, 'id'=>$pageid, 'url'=);
else
return  $pagemessage;
*/
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function plural($tag, $count = 0)
    {
        if ($count == '') $count = 0;
        if ($count != 1)
            $tag .= 's';
        return $tag;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function plural2($count = 0, $tag = '', $options = '')
    {
        if ($count == '') {
            $count = 0;
        }
    
        if ($count != 1) {
            // Check if the word ends in 's', 'sh', 'ch', 'x', or 'z'
            if (in_array(substr($tag, -1), ['x', 'z']) || in_array(substr($tag, -2), ['sh', 'ch'])) {
                $tag .= 'es';
            }
            // Special case for words ending in 's'
            elseif (substr($tag, -1) == 's') {
                if (substr($tag, -2) != 'es') {
                    $tag .= 'es';
                }
            }
            // Check if the last character is 'y' and not preceded by a vowel
            elseif (substr($tag, -1) == 'y' && !in_array(substr($tag, -2, 1), ['a', 'e', 'i', 'o', 'u'])) {
                $tag = substr($tag, 0, -1) . 'ies';
            } else {
                $tag .= 's';
            }
        }
    
        $separator = ' ';
        if (strpos('|' . $options . '|', '_nbsp')) {
            $separator = '&nbsp;';
        }
    
        // Check if count should be hidden
        if (strpos('|' . $options . '|', '_hide_count')) {
            return $tag;
        }
        
        return $count . $separator . $tag;
    }


    # ##--------------------------------------------------------------------------------------------------------------------------------------------------

    function generateLoremIpsum($limit, $type = 'words')
    {
        $loremIpsum = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

        if ($type == 'characters') {
            return substr($loremIpsum, 0, $limit);
        } else if ($type == 'words') {
            $words = explode(' ', $loremIpsum);
            $words = array_slice($words, 0, $limit);
            shuffle($words);
            $words[0] = ucfirst($words[0]);
            return implode(' ', $words) . '.';
        } else {
            return "Invalid type specified. Please choose 'words' or 'characters'.";
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function number2word($int)
    {
        $words = [
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten'
        ];

        return $words[$int] ?? $int;
    }
}
