<?php
// app.php
class App
{
  public function __construct($local_config)
  {
    // Use $config
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function securePage($page)
  {
    // add security check for page
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  // Example usage
  // $integer = 12345;
  // $key = 98765;

  // $obfuscatedGuid = obfuscateIntegerToGuid($integer, $key);
  // echo "Obfuscated GUID: $obfuscatedGuid\n";

  // $unobfuscatedInteger = unobfuscateGuidToInteger($obfuscatedGuid, $key);
  // echo "Unobfuscated Integer: $unobfuscatedInteger\n";

  // Function to obfuscate (encrypt) an integer and format it like a GUID
  function id2og($integer)
  {
    // Using a static key for this example; normally, you would store/retrieve this from a secure place
    $key = 0x1A2B3C4D;

    // Obfuscate integer
    $obfuscated = $integer ^ $key;

    // Pack the obfuscated integer into binary data
    $binaryData = pack('P', $obfuscated);  // 'P' is for storing 64-bit unsigned integers

    // Convert binary data to hex string
    $hex = bin2hex($binaryData);

    // Format as GUID
    $guid = vsprintf('%08s-%04s-%04s-%04s-%12s', str_split($hex, 4));

    return $guid;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
function tagreplace($content) {
    global $website;
    $search = ['{{numberofbiz}}', '{{biznames}}', '{{bizname}}'];
    $replace = [$website['numberofbiz'], $website['biznames'], $website['bizname']];
    return str_replace($search, $replace, $content);
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function outputpage()
  {
    global $additionalstyles;

    // Search and replace content before sending it to the client
    $content = ob_get_clean();
    $search = [";\r\n", "  "];
    $replace = ["; ", " "];
    $content = str_replace('</head>', str_replace($search, $replace, $additionalstyles) . '</head>', $content);
    echo $content;

    // End output buffering only if an output buffer exists
    if (ob_get_level() > 0) {
      ob_end_flush();
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  // Function to unobfuscate (decrypt) a GUID-like string to get the original integer
  function og2id($guid)
  {
    // Convert GUID to binary data
    $hex = str_replace('-', '', $guid);
    $binaryData = hex2bin($hex);

    // Unpack binary data into integer
    $data = unpack('P', $binaryData);
    $obfuscated = $data[1];

    // Static key used in obfuscation
    $key = 0x1A2B3C4D;

    // Recover the original integer
    $integer = $obfuscated ^ $key;

    return $integer;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function admin_getuserstats($type = 'all')
  {
    $output = false;
    global $database;
    switch ($type) {
      case 'all':
        $output = [];
        $sql = 'select "total" name, count(*) value from bg_users where `status`="active" and (create_dt >= "2023-08-01")';
        $sql .= ' union select "month" name, count(*) value from bg_users where `status`="active" and month(create_dt) = month(now()) and (create_dt >= "2023-08-01")';
        $sql .= ' union select "today" name, count(*) value from bg_users where `status`="active" and date(create_dt) = date(now()) and (create_dt >= "2023-08-01")';
        $sql .= ' union select "pending" name, count(*) value from bg_users where `status`="pending" and (create_dt >= "2023-08-01")';
        $sql .= ' union select "planfree" name, count(*) value from bg_users where account_plan="free" and (create_dt >= "2023-08-01")';
        $sql .= ' union select "plangold" name, count(*) value from bg_users where account_plan="gold" and (create_dt >= "2023-08-01")';
        $sql .= ' union select "planlife" name, count(*) value from bg_users where account_plan="life" and (create_dt >= "2023-08-01")';
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $result) {
          $output[$result['name']] = $result['value'];
        }
        break;
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function admin_getrevenuestats($type = 'all')
  {
    $output = false;
    global $database;
    switch ($type) {
      case 'all':
        $output = [];
        $sql = 'select "total" name, sum(account_revenue) value from bg_users where `status`="active" and (create_dt >= "2023-08-01")';
        $sql .= ' union select "month" name, sum(account_revenue) value from bg_users where `status`="active" and month(create_dt) = month(now()) and (create_dt >= "2023-08-01")';
        $sql .= ' union select "today" name, sum(account_revenue) value from bg_users where `status`="active" and date(create_dt) = date(now()) and (create_dt >= "2023-08-01")';
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $result) {
          $output[$result['name']] = $result['value'];
        }
        break;
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function admin_getsystemstats($type = 'all')
  {
    $output = false;
    global $database;
    switch ($type) {
      case 'all':
        $output = [];
        $sql = 'select "sessions_total" name, count(distinct sessionid) value from bg_sessiontracking where  (create_dt >= "2023-08-01")';
        $sql .= ' union select "sessions_month" name, count(distinct sessionid) value from bg_sessiontracking where  month(create_dt) = month(now()) and (create_dt >= "2023-08-01")';
        $sql .= ' union select "sessions_day" name, count(distinct sessionid) value from bg_sessiontracking where  date(create_dt) = date(now()) and (create_dt >= "2023-08-01")';
        $sql .= ' union select "pagehits_total" name, count(*) value from bg_sessiontracking where (create_dt >= "2023-08-01")';
        $sql .= ' union select "pagehits_month" name, count(*) value from bg_sessiontracking where  month(create_dt) = month(now()) and (create_dt >= "2023-08-01")';
        $sql .= ' union select "pagehits_day" name, count(*) value from bg_sessiontracking where  date(create_dt) = date(now()) and (create_dt >= "2023-08-01")';
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $result) {
          $output[$result['name']] = $result['value'];
        }
        $output['days_live'] = floor((time() - strtotime('8/1/2023')) / (60 * 60 * 24));;
        break;
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function admin_getbusinessstats($type = 'all')
  {
    $output = false;
    global $database;
    switch ($type) {
      case 'all':
        $output = [];
        $sql = 'select "total" name, count(*) value from bg_companies where `company_status`="active"';
        $sql .= ' union select "month" name, count(*) value from bg_companies where `company_status`="active" and  month(create_dt) = month(now())';
        $sql .= ' union (select concat("status_", `status`) name, count(*) value from bg_companies group by concat("status_", `status`))';
        $sql .= ' union (select concat("displaycategory_", display_category) name, count(*) value from bg_companies group by concat("displaycategory_", display_category))';
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $result) {
          $output[$result['name']] = $result['value'];
        }
        break;
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public  function getCompanyRewards($companyid = '', $type = 'all')
  {
    global $database;
    $item_company = [];
    $query = "SELECT * FROM bg_company_rewards WHERE company_id= ? and `status`='active'";
    $stmt = $database->prepare($query);
    $stmt->execute([$companyid]);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowcnt = count($rewards);
    $totalvalue = 0;
    $itemcounter = 0;
    $menus = '';
    $counter['total'] = 0;
    $counter['record'] = 0;
    $counter['display'] = 0;
    $counter['rewards'] = 0;

    $get_rewardcategories = $this->get_rewardcategories();
    $rewardiconlist = $get_rewardcategories[1];


    ### $rewardiconlist retrieved from $app->getreward_categories
    ## reset the icons
    foreach ($rewardiconlist as $icon) {
      $rewardicon[$icon] = '';
    }

    if ($rewards) {
      foreach ($rewards as $reward) {
        $counter['rewards']++;
        $totalvalue = $totalvalue + $reward["cash_value"];
        switch ($reward["category"]) {

            ####-----------------------------------------------------
            ### ENROLLMENT REWARD
          case 'enrollment':
            $rewardicon[$reward["category"]] = '
      <span class="badge rounded-pill bg-primary p-2 pe-3">
      <i class="bi bi-pen me-1 fs-10"></i>
      <span>Enrollment</span></span>
  ';
            break;

            ####-----------------------------------------------------
            ### BIRTHDAY REWARD
          case 'birthday':
            $rewardicon[$reward["category"]] = '
      <span class="badge rounded-pill bg-warning p-2 pe-3">
      <i class="bi bi-cake-fill me-1 fs-10" ></i>
      <span>Birthday</span></span>
      ';
            break;

            ####-----------------------------------------------------
            ### ENROLLMENT ANNIVERSARY REWARD    
          case 'enrollment_anniversary':
            $rewardicon[$reward["category"]] = '    
      <span class="badge rounded-pill bg-success p-2 pe-3">
      <i class="bi bi-calendar-heart me-1 fs-10" ></i>
      <span>Annual Member</span></span>
      ';
            break;


            ####-----------------------------------------------------
            ### WEDDING ANNIVERSARY REWARD
          case 'wedding_anniversary':
            $rewardicon[$reward["category"]] = '    
      <span class="badge rounded-pill bg-info p-2 pe-3">
      <i class="bi bi-gem me-1 fs-10" ></i>
      <span>Wedding</span></span>
      ';
            break;
            ####-----------------------------------------------------
            ### OTHER CLASS REWARD
          case 'honor':
            $rewardicon[$reward["category"]] = '      
<span class="badge rounded-pill bg-secondary p-2 pe-3">
<i class="bi bi-award-fill me-1 fs-10"></i>
<span>Honor</span></span>
';
            break;
        }
      }
    }

    $output = [];
    $output['totalvalue'] =  $totalvalue;
    $output['count'] = $rowcnt;
    $output['icon'] = $rewardicon[$reward["category"]];
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function admin_getenrollments($type = 'all')
  {

    $output = false;
    global $database;

    // Fetch pending users from bg_user_companies
    $sql = "SELECT 
    distinct u.user_id
FROM 
    bg_user_companies uc
INNER JOIN 
    bg_users u ON uc.user_id = u.user_id
INNER JOIN
    bg_companies c ON c.company_id = uc.company_id
WHERE 
    ((c.`status` IN ('finalized') and c.signup_url!='APP ONLY')
    AND u.create_dt >= '2023-08-01'
    AND uc.create_dt >= '2023-08-01') 
    AND NOT (uc.`status` LIKE '%failed%' AND uc.`reason` = 'account_exists')
GROUP BY 
    uc.user_id
    HAVING 
    SUM(CASE WHEN uc.status = 'selected' THEN 1 ELSE 0 END) > 0
";
    $stmt = $database->prepare($sql);
    $stmt->execute();
    #$listofcompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rowCount = $stmt->rowCount();
    return $rowCount;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function plandetail_deep($type = 'current', $input = '')
  {
    $plans = [
      'free' => 'Free',
      'gold' => 'Gold',
      'life' => 'Lifetime'
    ];

    switch ($type) {
        # ##--------------------------------------------
      case 'current': # send the current user plan
        global $session;
        $currentuserdata = $session->get('current_user_data', '');
        $plan = $currentuserdata['account_plan'];
        break;
      case 'this':
        global $session, $database;
        $currentuserdata = $type === 'current' ? $session->get('current_user_data', '') : ['account_plan' => $input];
        $plan = $currentuserdata['account_plan'];

        // Check for additional units
        $sql = 'SELECT id, `name`, description FROM bg_user_attributes WHERE user_id = :user_id AND name LIKE "additional_%" and ((start_dt is null or start_dt > now()) and (end_dt is null or end_dt < now()))';
        $stmt = $database->prepare($sql);
        $stmt->execute(['user_id' => $currentuserdata['user_id']]);
        $additionalUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($additionalUnits)) {
          foreach ($additionalUnits as $additional) {
            $attribute = str_replace('additional_', '', $additional['name']);
            $currentuserdata['original_plan_allocation'][$attribute] = $currentuserdata[$attribute];
            $currentuserdata[$attribute] += $additional['description'];
          }
        }
        return $currentuserdata;

      case 'all':
        return $plans;

      case 'details':   // Send the plan details
        global $session, $database, $website;
        $currentuserdata =  $session->get('current_user_data', '');
        $plan = $currentuserdata['account_plan'];

        if ($input != '') { // Return details for a specific plan
          $list[] = $plans[$input];
        } else {
          $list = $plans;
        }
        $output = [];
        foreach ($list as $plan => $name) {
          $sql = 'SELECT id, name, value FROM bg_plans WHERE plan = :plan AND `status` = "active" and `version` ="' . $website['plan_version'] . '"';
          $stmt = $database->prepare($sql);
          $stmt->execute(['plan' => $plan]);
          $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

          foreach ($results as $result) {
            $output[$plan][$result['name']] = $result['value'];
          }

          // Check and apply additional units for each plan
          $sql = 'SELECT id, `name` attribute_name, description attribute_value FROM bg_user_attributes WHERE user_id = :user_id AND name LIKE "additional_%" and ((start_dt is null or start_dt <= now()) and (end_dt is null or end_dt >= now()))';

          $stmt = $database->prepare($sql);
          $stmt->execute(['user_id' => $currentuserdata['user_id']]); // Assuming $input is the user ID
          $additionalUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);

          if (!empty($additionalUnits)) {
            foreach ($additionalUnits as $additional) {
              $attribute = str_replace('additional_', '', $additional['attribute_name']);
              $output[$plan]['original_plan_allocation'][$attribute] = $output[$plan][$attribute];
              $output[$plan][$attribute] += $additional['attribute_value'];
            }
          }
        }
        return $output;

      default:
        ## GET A SPECIFIC PLAN DETAIL ($TYPE must match the bg_plan name )
        global $database, $website;
        if ($input != '') { # return all details for all plans
          $list[] = $plans[$input];
        } else {
          $list = $plans;
        }
        $output = [];

        foreach ($list as $plan => $name) {
          $sql = 'select  id, name, value from bg_plans where plan="' . $plan . '" and name="' . $type . '" and `status`="active"  and `version` ="' . $website['version'] . '"';
          $stmt = $database->prepare($sql);
          $stmt->execute();
          $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach ($results as $result) {
            $output[$plan] = $result['value'];
          }
        }
        return $output;
        # ##------------- END SWITCH -------------------------------
    }

    return $plans[$plan];
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function plandetail($type = 'current', $input = '', $lookupversion = '')
  {

    $plans = [
      'free' => 'Free',
      'gold' => 'Gold',
      'life' => 'Lifetime'
    ];


    switch ($type) {
        # ##--------------------------------------------
      case 'current': # send the current user plan
        global $session;
        $currentuserdata = $session->get('current_user_data', '');
        $plan = $currentuserdata['account_plan'];
        break;
        # ##--------------------------------------------
      case 'this': # send the current user plan
        $plan = $input;
        break;
        # ##--------------------------------------------
      case 'all': # send all the plans
        return $plans;
        # ##--------------------------------------------
      case 'details': # send the plan details
        global $database, $website;
        if ($lookupversion == '') {
          $lookupversion = $website['plan_version'];
        }

        if ($input != '') { # return all details for all plans
          $list[] = $plans[$input];
        } else {
          $list = $plans;
        }
        $output = [];
        foreach ($list as $plan => $name) {
          $sql = 'select id, name, value from bg_plans where plan="' . $plan . '" and `status`="active" and `version` ="' . $lookupversion . '"';
          $stmt = $database->prepare($sql);
          $stmt->execute();
          $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach ($results as $result) {
            $output[$plan][$result['name']] = $result['value'];
          }
        }
        return $output;
        # ##--------------------------------------------
      case 'details_id': # send the plan details
        global $database, $website;
        if ($lookupversion == '') {
          $lookupversion = $website['plan_version'];
        }
        $output = [];

        $sql = 'select name, value from bg_product_features where product_id=:id and `status`="active"';
        $stmt = $database->prepare($sql);
        $stmt->execute(['id' => $input]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Map the results into an associative array

#breakpoint($input);
        $features = [
          'max_business_select' => 0,
          'celebration_tour_option_tag' => '',
          'celebration_planning_days' => 0,
          'max_business_select_tag' => '',
          'plan' => '',
          'support_tag' => '',
          'support_link' => '',
          'displayname' => '',
          'upgradeable' => '0',
        ];

        foreach ($results as $row) {
          $features[$row['name']] = $row['value'];
        }

        return $features;
        # ##--------------------------------------------
      case 'detailsfull_id': # send the plan details
      case 'detailsall_id': # send the plan details
        global $database, $website;
        // Ensure the lookup version is set, fallback to the default plan version if not provided
        $lookupversion = $lookupversion ?: $website['plan_version'];

        // Validate input to ensure it's not empty
        if (empty($input)) {
          return ['error' => 'Invalid product ID'];
        }


        // Prepare the SQL query, filtering by product_id and active status
        $sql = 'SELECT * FROM bg_product_features WHERE product_id = :id AND `status` = "active"';
        $stmt = $database->prepare($sql);
        $stmt->execute(['id' => $input]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Initialize the features array with default values
        $features = [
          'max_business_select' => ['product_id'=> null, 'id' => null,  'title'=>null,'value' => 0],
          'celebration_tour_option_tag' => ['product_id'=> null, 'id' => null,  'title'=>null,'value' => ''],
          'celebration_planning_days' => ['product_id'=> null, 'id' => null,  'title'=>null,'value' => 0],
          'max_business_select_tag' => ['product_id'=> null, 'id' => null,  'title'=>null,'value' => ''],
          'plan' => ['product_id'=> null, 'id' => null,  'title'=>null,'value' => ''],
          'support_tag' => ['product_id'=> null, 'id' => null,  'title'=>null,'value' => ''],
          'support_link' => ['product_id'=> null, 'id' => null,  'title'=>null,'value' => ''],
          'displayname' => ['product_id'=> null, 'id' => null,  'title'=>null,'value' => ''],
          'upgradeable' => ['product_id'=> null, 'id' => null,  'title'=>null,'value' => '0'],
        ];

        // Iterate through results and populate the features array
        foreach ($results as $row) {
        #  if (isset($features[$row['name']])) {
            $features[$row['name']] = [
              'product_id'=>  $row['product_id'],
              'id' => $row['id'],
              'title' => $row['name'],
              'value' => $row['value']
            ];
        # }
        }
        return $features;

        # ##--------------------------------------------
      default:
        ## GET A SPECIFIC PLAN DETAIL ($TYPE must match the bg_plan name )
        global $database, $website;
        if ($input != '') { # return all details for all plans
          $list[] = $plans[$input];
        } else {
          $list = $plans;
        }
        $output = [];

        foreach ($list as $plan => $name) {
          $sql = 'select id, name, value from bg_plans where plan="' . $plan . '" and name="' . $type . '" and `status`="active" and `version` ="' . $website['plan_version'] . '"';
          $stmt = $database->prepare($sql);
          $stmt->execute();
          $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach ($results as $result) {
            $output[$plan] = $result['value'];
          }
        }
        return $output;
        # ##------------- END SWITCH -------------------------------
    }

    return $plans[$plan];
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function check_lockout($client_ip)
  {
    global $database, $nolockout;

    // Whitelist IP range (71.33.250.224-255)
    $whitelisted_ips = [
      '71.33.250.224',
      '71.33.250.225',
      '71.33.250.226',
      '71.33.250.227',
      '71.33.250.228',
      '71.33.250.229',
      '71.33.250.230',
      '71.33.250.231',
      '71.33.250.232',
      '71.33.250.233',
      '71.33.250.234',
      '71.33.250.235',
      '71.33.250.236',
      '71.33.250.237',
      '71.33.250.238',
      '71.33.250.239',
      '71.33.250.240',
      '71.33.250.241',
      '71.33.250.242',
      '71.33.250.243',
      '71.33.250.244',
      '71.33.250.245',
      '71.33.250.246',
      '71.33.250.247',
      '71.33.250.248',
      '71.33.250.249',
      '71.33.250.250',
      '71.33.250.251',
      '71.33.250.252',
      '71.33.250.253',
      '71.33.250.254',
      '71.33.250.255'
    ];

    // Check if the client IP is within the whitelisted range
    if (in_array($client_ip, $whitelisted_ips)) {
      return;  // Skip lockout check for whitelisted IPs
    }


    if (empty($nolockout)) {
      $sql = "SELECT * FROM bg_lockout WHERE ip = :ip AND (NOW() between start_dt and expire_dt) and status='active' and
 ip not in (select distinct ip from bg_lockout where  ip = :ip2 and `status`='never_block')  order by expire_dt desc limit 1";
      $stmt = $database->prepare($sql);
      $stmt->execute(['ip' => $client_ip, 'ip2' => $client_ip]);
      if ($stmt->rowCount() > 0) {
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $expire_dt = $results[0]['expire_dt'];
        if (!empty($results)) error_log('Rate limited IPs: ' . print_r($results, 1));

        die("You're temporarily blocked due to excessive requests. You may be unblocked after: " . $expire_dt . ".  This time gets extended automatically if continued requests are received.");
      }
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function time_based_greeting($client_timezone = null, $punctuation = ',')
  {
    // Determine timezone
    if (!empty($client_timezone) && in_array($client_timezone, DateTimeZone::listIdentifiers())) {
      $timezone = new DateTimeZone($client_timezone);
    } else {
      // Use server's default timezone if client timezone is unavailable
      $timezone = new DateTimeZone(date_default_timezone_get());
    }


    // SEE IF IT's THE USERS' BIRTHDAY
    global $session;
    $current_user_data = $session->get('current_user_data');

    if (!empty($current_user_data['birthday']) && date('Y-m-d', strtotime($current_user_data['birthday'])) == date('Y-m-d')) {
      return '<picture>
  <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.webp" type="image/webp">
  <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.gif" alt="🥳" width="48" height="48">
</picture>' . ' Happy Birthday ';
    }

    $datetime = new DateTime("now", $timezone);
    $hour = $datetime->format('H'); // 24-hour format

    $greeting = "Hello";
    if ($hour >= 5 && $hour < 12) {
      $greeting = "Good Morning" . $punctuation;
    } else if ($hour >= 12 && $hour < 18) {
      $greeting = "Good Afternoon" . $punctuation;
    } else {
      $greeting = "Good Evening" . $punctuation;
    }

    return  $greeting . ' ';
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function deviceid($device_id = '')
  {
    if (empty($device_id)) {
      $device_id = $_COOKIE['bgdeviceid'] ?? hash('sha256', $_SERVER['HTTP_USER_AGENT'] . time());
    }
    return $device_id;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getvalidationcodes($input = '')
{
    // Register new user
    $rawdata = $input['rawdata'] ?? null;

    // Ensure rawdata is not null
    if ($rawdata === null) return false;

    global $database, $qik;
    $output = array();

    // Default values
    $type = $input['type'] ?? 'email';
    $expireminutes = $input['expireminutes'] ?? 120;
    $sendcount = $input['sendcount'] ?? 1;
    $status = $input['status'] ?? 'pending';
    $userid = $input['user_id'] ?? $input['userid'] ?? null;
    $companyid = $input['companyid'] ?? null;
    $locationid = $input['locationid'] ?? null;
    $device_id = $input['device_id'] ?? null;

    $output['expiremessagetag'] = $qik->convertMinutes($expireminutes);
    $expiredt = (new DateTime())->add(new DateInterval('PT' . $expireminutes . 'M'))->format('Y-m-d H:i:s');
    $extendedrawdata = $rawdata . '|' . $expiredt . '|' . rand(1, 999);
    $code1 = md5($extendedrawdata);
    $minicode = substr($code1, 0, 1) . substr($code1, -5);
    $longcode = sha1($extendedrawdata);

    $extendedrawdata = $input['validation_rawdata'] ?? $extendedrawdata;
    $longcode = $input['validation_code'] ?? $longcode;

 $params = array(
  ':user_id' => $userid,
  ':company_id' => $companyid,
  ':location_id' => $locationid,
  ':device_id' => $device_id,
  ':sendcount' => $sendcount,
  ':validation_type' => $type,
  ':validation_rawdata' => $extendedrawdata,
  ':validation_minicode' => $minicode,
  ':validation_code' => $longcode,
  ':expire_dt' => $expiredt,
  ':status' => $status
);


    // If action is 'getlatest', fetch the latest unexpired code
    if (isset($input['action']) && $input['action'] == 'getlatest') {
        $queryParams = [
            ':user_id' => $userid,
            ':validation_type' => $type,
        #    ':current_time' => (new DateTime())->format('Y-m-d H:i:s')
        ];
  
        $sql = "SELECT validation_minicode, validation_code, validation_rawdata, validation_id 
                FROM bg_validations
                WHERE user_id = :user_id             
                  and validation_type = :validation_type
                  AND expire_dt >= now()
                  AND validation_dt IS NULL
                ORDER BY create_dt DESC
                LIMIT 1";
         $stmt = $database->prepare($sql);
        $stmt->execute($queryParams);
    
        if ($stmt->rowCount() > 0) {
          $existingCode = $stmt->fetch(PDO::FETCH_ASSOC);
        // If a valid unexpired code exists, return it
        if ($existingCode) {
            $output = [
                'mini' => $existingCode['validation_minicode'],
                'long' => $existingCode['validation_code'],
                'code' => $existingCode['validation_code'],
                'validation_id' => $existingCode['validation_id'],
                'rawdata' => $existingCode['validation_rawdata']
            ];
            $output = array_merge($output, $params);
            return $output;
         
        }
    }
  }
   
 // Insert new validation code if no valid unexpired code exists
    $sql = "INSERT INTO bg_validations (user_id, company_id, location_id, device_id, sendcount, validation_type, validation_rawdata, validation_minicode, validation_code, expire_dt, create_dt, modify_dt, `status`)
            VALUES (:user_id, :company_id, :location_id, :device_id, :sendcount, :validation_type, :validation_rawdata, :validation_minicode, :validation_code, :expire_dt, now(), now(), :status)";
    
    $database->query($sql, $params);
    $lastId = $database->lastInsertId();

    // Prepare output
    $output['mini'] = $minicode;
    $output['long'] = $longcode;
    $output['code'] = $longcode;
    $output['validation_id'] = $lastId;
    $output = array_merge($output, $params);

    return $output;
}




  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function subscribe($input = '', $type = 'email')
  {
    global $database;
    $params = array(
      ':email' => $input,
      ':source' => session_id(),
    );
    $sql = 'insert into bg_subscriptions (email, source) values (:email, :source)';
    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    session_tracking(('newsletter_subscription_activate'));
    return true;
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function getcompanyname($input = '', $type = 'company_name')
  {
    global $database;
    $params = array(
      ':company_id' => $input,
    );
    $sql = 'select ' . $type . ' as result from bg_companies where company_id=:company_id';
    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results[0]['result'];
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public  function checkvalidationcodes($input = '')
  {
    global $database;

    // Initialize variables with default values or from $input
    $type = $input['type'] ?? 'email';
    $continue = false;
    $output = array();
    $output['validated'] = false;
    $output['validatedaction'] = 'failed';

    $rawdata = $input['rawdata'] ?? null;
    $minicode = $input['mini'] ?? null;
    $longcode = $input['code'] ?? $input['long'] ?? null;
    $userid = $input['user_id'] ?? $input['userid'] ?? null;
    $deviceid = $input['deviceid'] ?? null;
    $companyid = $input['companyid'] ?? null;
    $locationid = $input['locationid'] ?? null;
    $status = $input['status'] ?? 'pending';
    $updatestatus = $input['updatestatus'] ?? 'validated';

    $validationcode = $longcode;
    $criteria = ' where validation_code=:code and validation_type=:validation_type and expire_dt>=now() and (`status`="' . $status . '" or `status`="validated" ) ';
    if (empty($longcode)) {
      $validationcode = $minicode;
      $criteria = ' where validation_minicode=:code and validation_type=:validation_type and expire_dt>=now() and (`status`="' . $status . '" or `status`="validated" )';
    }


    $params = array(
      ':validation_type' => $type,
      ':code' => $validationcode
    );


    // ADD DEVICE ID HANDLING -- rememberme feature
    if ($type == 'bgrememberme_autologin') {
      $criteria = str_replace('pending', 'cookie', $criteria);
      $criteria .= '   and validation_rawdata=:rawdata ';
      $params[':rawdata'] = $rawdata;
      if (!empty($deviceid)) {
        $criteria .= ' and device_id=:device_id  ';
        $params[':device_id'] = $deviceid;
        # $extratracking=true;
      }
      session_tracking('bg_rememberme_validate', ['criteria' => $criteria, 'params' => $params]);
    }


    $sql = 'select max(validation_id) as validation_id from bg_validations ' . $criteria;

    $maxIdStmt = $database->prepare($sql);
    $maxIdStmt->execute($params);
    $maxid = $maxIdStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($maxid) || $maxid[0]['validation_id'] === null) {
      return false;
    }

    $maxid = $maxid[0]['validation_id'];

    if (!empty($extratracking)) session_tracking('bg_rememberme_maxid', $maxid);
    #breakpoint($maxid);
    if (isset($maxid) && is_int($maxid)) {
      // $maxid is valid 
      # $maxId = $maxid['validation_id'];
      $sql = 'update bg_validations set validation_dt=now(), modify_dt=now(), `status`="' . $updatestatus . '"  ' . $criteria;
      $updateStmt = $database->prepare($sql);
      $updateStmt->execute($params);
      $rowsaffected = $updateStmt->rowCount();
      if (!empty($extratracking)) session_tracking('bg_rememberme_rowsaffected', $rowsaffected);
      $sql = 'SELECT * FROM bg_validations WHERE validation_id = :id';
      $stmt = $database->prepare($sql);
      $params = array(
        ':id' => $maxid
      );
      $stmt->execute($params);
      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
      #breakpoint($results);
      $output['validated'] = true;
      $output['validatedaction'] = 'success';
      $output['count'] = $rowsaffected;

      if (!empty($results)) {
        $output = array_merge($output, $results);
      }
      $continue = true;
    }

    if (!$continue) {
      $criteria = str_replace('expire_dt>=now() and `status`="' . $status . '"', '`status`="' . $updatestatus . '" ', $criteria) . ' order by validation_id desc limit 1';
      $sql = 'select * from bg_validations ' . $criteria;
      #breakpoint($sql);
      $maxIdStmt = $database->prepare($sql);
      $maxIdStmt->execute($params);
      $results = $maxIdStmt->fetchAll(PDO::FETCH_ASSOC);
      $output['validated'] = false;
      $output['validatedaction'] = $status;
      $output['count'] = 0;
      if (!empty($results)) {
        $output['validated'] = true;
        $output['validatedaction'] = 'alreadyvalidated';
        $output['count'] = 1;
        $output = array_merge($output, $results);
      }
    }
    # breakpoint($output);

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getformdate($input = [], $fieldnames = [])
  {
    $date = false;
    if (empty($input)) {
      $input = $_POST;
    }

    // Default field names for individual components
    if (empty($fieldnames)) {
      $fieldnames = ['date' => 'birthday', 'year' => 'year', 'month' => 'month', 'day' => 'day', 'datetime' => 'start_datetime'];
    }

    if (isset($input[$fieldnames['year']]) && isset($input[$fieldnames['month']]) && isset($input[$fieldnames['day']])) {
      // Use three separate fields for date
      $day = str_pad($input[$fieldnames['day']], 2, '0', STR_PAD_LEFT);
      $month = str_pad($input[$fieldnames['month']], 2, '0', STR_PAD_LEFT);
      $year = $input[$fieldnames['year']];
      $date = "$year-$month-$day";  // Assemble back into a single date string
    } elseif (isset($input[$fieldnames['date']])) {
      // Use single date input field
      $date = $input[$fieldnames['date']];
    } elseif (isset($input[$fieldnames['datetime']])) {
      // Handle datetime-local input
      $datetime = $input[$fieldnames['datetime']];
      // Convert datetime to desired format if needed. Here it's assumed the format is already YYYY-MM-DD HH:MM
      $date = str_replace('T', ' ', $datetime); // Replace the 'T' between date and time with a space
    }

    return $date;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getshortcode($input_url, $input_code = '', $input_pass = '')
  {
    // URL encode the parameters

    $urlParam = 'url=' . urlencode($input_url);
    $custParam = '&cust=' . urlencode($input_code);
    $passParam = '&pass=' . urlencode($input_pass);

    $querystring = $urlParam;
    if ($input_code != '') $querystring .= $custParam;
    if ($input_pass != '') $querystring .= $passParam;

    // Construct the full API URL
    $baseurl = 'https://bd.gold/';
    $apiUrl = $baseurl . "api.php?" . $querystring;

    // Fetch the data from the API
    $apiResponse = file_get_contents($apiUrl);


    // Strip out the non-JSON parts
    $jsonStartPos = strpos($apiResponse, '{');
    $jsonEndPos = strrpos($apiResponse, '}');

    if ($jsonStartPos === false || $jsonEndPos === false) {
      echo "No valid JSON found in the response";
      return;
    }

    $json = substr($apiResponse, $jsonStartPos, $jsonEndPos - $jsonStartPos + 1);

    // Now decode and use the JSON
    $data = json_decode($json, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
      echo 'JSON decoding error: ' . json_last_error_msg();
      return false;
    }
    #breakpoint($data);

    if (isset($data['shorturl'])) {
      $data['shortcode'] = str_replace($baseurl, '', $data['shorturl']);
    }
    ### returns $data['longurl'] (orginal URL), $data['shorturl'] (shortened url), $data['stats'],  $data['shortcode'] (just code)
    return $data;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function validateAPItoken($auth_token = '')
  {
    global $database; // Assuming $database is a globally available PDO instance
    if (empty($auth_token) && !empty($_REQUEST['$auth_token'])) $auth_token = $_REQUEST['$auth_token'];
    // Update the session record if the token is not expired, push the expire date another hour from now, and set the last_page column value to the current URL page
    $sql = "UPDATE bg_api_sessions SET modify_dt=now(), expire_dt = DATE_ADD(NOW(), INTERVAL 1 HOUR), last_page = :last_page WHERE session_id = :auth_token AND expire_dt > NOW()";
    $stmt = $database->prepare($sql);
    $stmt->execute([
      ':auth_token' => $auth_token,
      ':last_page' => $_SERVER['REQUEST_URI'] // Current URL page
    ]);

    // Return true if a record was found to update, otherwise return false
    return $stmt->rowCount() > 0;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public  function testfeature($input = '')
  {
    $allow = false;
    global $account, $mode;
    if ($account->isadmin()) $allow = 1;
    if ($account->isimpersonator()) $allow = 2;
    if ($mode == 'dev') $allow = 3;
    return  $allow;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function formposted($input = 'POST')
  {
    global $session;
    $output = false;
    switch ($input) {

      case 'force':
        $output = true;
        break;

      case 'token':
        $gettoken = $_POST['_token'] ?? '';
        if ($gettoken == $_SESSION['csrf_token'])     $output = true;
        break;


      case 'POST':
        if (
          isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'  && isset($_POST['_token']) && $_POST['_token'] == $session->get('csrf_token', '')
        )  $output = $_POST;
        if (isset($output['_token']))  unset($output['_token']);
        break;

      case 'GET':
        if (
          isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET'  && isset($_GET['_token']) && $_GET['_token'] == $session->get('csrf_token', '')
        )  $output = $_GET;
        if (isset($output['_token']))  unset($output['_token']);
        break;

      case 'REQUEST':
        if (
          isset($_REQUEST['_token']) && $_REQUEST['_token'] == $session->get('csrf_token', '')
        )  $output = $_REQUEST;
        if (isset($output['_token']))  unset($output['_token']);
        break;
    }
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function carouselimage($info)
  {
    global $session;
    $imagelist = $session->unset('carouselimages');
    $imagelist = $session->get('carouselimages', '');
    $imgDir = $_SERVER['DOCUMENT_ROOT'] . '/public/images/site_covers/';

    if ($imagelist == '') {
      // Get images   
      $images = scandir($imgDir);
      // Remove document root from paths
      // Remove . and ..
      # $images = array_diff($images, array('.', '..'));
      $images = array_filter($images, function ($file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return $ext == 'jpg';
      });
      #breakpoint($images);

      $imagelist = $session->set('carouselimages', $images);
    }
    // Pick random image
    return  $imagelist[array_rand($imagelist)];
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function get_rewardcategories($input = [], $type = 'basic')
  {
    $rewardiconlist = [
      'enrollment' => ['name' => 'Enrollment', 'active' => false],
      'birthday' => ['name' => 'Birthday', 'active' => true], // Example: set to true
      'enrollment_anniversary' => ['name' => 'Annual Member', 'active' => false],
      'wedding_anniversary' => ['name' => 'Wedding', 'active' => false],
      'honor' => ['name' => 'Honor', 'active' => true], // Example: set to true
    ];
    $iconlist = [];
    $data = [
      'Category' => []
    ];
    switch ($type) {
      case 'extended':
        foreach ($rewardiconlist as $icon => $details) {
          $iconlist[] = $icon;
          // Populate $data['Category'] with the 'label', 'filter', and 'state' for each reward
          $data['Category'][] = [
            'label' => $details['name'],
            'filter' => strtolower($details['name']),
            'state' => $details['active']
          ];
        }
        break;
      default:
        foreach ($rewardiconlist as $icon => $details) {
          $iconlist[] = $icon;
          // Populate $data['Category'] with the 'active' status of each reward
          $data['Category'][$details['name']] = $details['active'];
        }
        break;
    }
    return array($data, $iconlist);
    // Now $data['Category'] contains all the $rewardicon keys with their 'active' status

  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getcompany($id, $input = [])
  {

    #### setup to handle multiple configurations
    if (empty($input['locationlimit'])) $input['locationlimit'] = 1;
    if (empty($input['businessstatus'])) $input['businessstatus'] = 'finalized';
    if (empty($input['consideruserdata'])) $input['consideruserdata'] = false;

    $usercriteria = '';
    if ($input['consideruserdata']) {
      $usercriteria .= '';
    }


    global $database;
    $sql = 'SELECT c.*, a.description AS company_logo, l.location_id, l.address, l.city, l.state, l.zip_code, l.country, 
concat( l.address, ",", l.city,",",  l.state, ",", l.zip_code) as map_address ,
l.latitude, ",", l.longitude,       
       concat( ifnull(l.latitude, ""), ",", ifnull(l.longitude,"")) as map_lonlat
FROM bg_companies AS c
LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id AND a.category = "company_logos"  and a.`grouping` ="primary_logo"
LEFT JOIN bg_company_locations AS l ON c.company_id = l.company_id
WHERE c.status like "' . $input['businessstatus'] . '" and c.company_id =  :cid';
    $stmt = $database->prepare($sql);
    $stmt->execute([':cid' => $id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($results)) {
      $row = $results[0];
      $row['coordinates'] = array("lat" => $row['latitude'], "lon" => $row['longitude']);
      return $row;
    }
    return false;
  }




  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function getProduct($accountPlan, $accountType, $columns = '*', $limit = 1, $displayGrouping = 'primary', $displayGroupingStatus = 'active')
  {
    global $database, $website;

    // If $accountPlan is an integer and $accountType is 'PRODUCT_ID', search by product ID
    if (($accountPlan) && $accountType === 'PRODUCT_ID') {
      $sql = '
            SELECT ' . $columns . ' 
            FROM bg_products 
            WHERE id = :product_id AND `status` = "active" 
            LIMIT 1
        ';

      // Execute the query with product ID instead of account_plan and account_type
      $stmt = $database->prepare($sql);
      $stmt->execute([
        ':product_id' => $accountPlan
      ]);
    }
    // Otherwise, proceed with the existing logic based on account_plan and account_type
    elseif ($website['plan_version'] == 'v2') {
      // Prepare the SQL statement for the old version
      $sql = '
            SELECT ' . $columns . ' 
            FROM bg_products_OLDVERSION 
            WHERE account_plan = :account_plan AND account_type = :account_type AND `status` = "active" and `version` = "' . $website['plan_version'] . '"
            LIMIT 1
        ';

      // Execute the query
      $stmt = $database->prepare($sql);
      $stmt->execute([
        ':account_plan' => $accountPlan,
        ':account_type' => $accountType
      ]);
    } else {
      // Prepare the SQL statement for the current version
      $sql = '
            SELECT ' . $columns . ' 
            FROM bg_products 
            WHERE account_plan = :account_plan AND account_type = :account_type AND `status` = "active" 
            AND version = "' . $website['plan_version'] . '" 
            AND display_grouping = "' . $displayGrouping . '" 
            AND display_grouping_status = "' . $displayGroupingStatus . '"
        ';

      // Add the limit clause if applicable
      if ($limit >= 1) {
        $sql .= ' LIMIT ' . $limit;
      }

      // Execute the query
      $stmt = $database->prepare($sql);
      $stmt->execute([
        ':account_plan' => $accountPlan,
        ':account_type' => $accountType
      ]);
    }

    // Fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return the row data if found, otherwise return null
    return $result ? $result : null;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  // Generic function to get all or specific product features based on the product_id
  public function getProductFeatures($productid, $product_item = 'all', $status = 'active')
  {
    global $database;

    $sql = 'SELECT * FROM bg_product_features WHERE product_id = :product_id AND `status` = "' . $status . '"';

    // If a specific product item is requested, add it to the WHERE clause
    if ($product_item !== 'all') {
      $sql .= ' AND name = :product_item';
    }

    $stmt = $database->prepare($sql);
    $params = [':product_id' => $productid];

    // Add the product_item parameter if it's not 'all'
    if ($product_item !== 'all') {
      $params[':product_item'] = $product_item;
    }

    // Execute the SQL statement with the parameters array
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result ? $result : false;
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getcompanyimages($id, $limit = '0')
  {
    global $database;

    $sql = 'SELECT  a.description AS company_logo
FROM bg_company_attributes a
where a.company_id = :cid and a.category = "company_logos" ';
    if ($limit != '0') $sql .= ' limit ' . $limit;
    $stmt = $database->prepare($sql);

    $stmt->bindParam(':cid', $id);


    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($results)) return $results;
    return false;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getcompany_extended($id, $input = [])
  {
    global $database;

    $current_user_data = false;
    if (!empty($input['userdata'])) $current_user_data = $input['userdata'];

    // Setup to handle multiple configurations
    $input = array_merge([
      'locationlimit' => 1,
      'businessstatus' => 'finalized',
      'consideruserdata' => false,
    ], $input);

    $whereClauses = ["c.status = :businessstatus", "c.company_id = :cid"];

    if ($input['consideruserdata'] && $current_user_data) {
      $userDataClauses = [
        'l.zip_code = :user_zipcode',
        '(l.city = :user_city AND l.state = :user_state)',
        'l.state = :user_state',
      ];
      $whereClauses[] = '(' . join(' OR ', $userDataClauses) . ')';
    }

    if (!empty($input['opendt'])) {
      $date = new DateTime($input['opendt']);
      $whereClauses[] = 'bh.day_date = :day_date';
    }

    $sql = 'SELECT c.*, a.description AS company_logo, l.location_id, l.address, l.city, l.state, l.zip_code, l.country, 
          CONCAT(l.address, ",", l.city,",",  l.state, ",", l.zip_code) as map_address 
          FROM bg_companies AS c
          LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id AND a.category = "company_logos" AND a.grouping = "primary_logo"
          LEFT JOIN bg_company_locations AS l ON c.company_id = l.company_id';

    if (!empty($input['opendt'])) {
      $sql .= ' LEFT JOIN bg_business_hours AS bh ON l.location_id = bh.location_id';
    }

    $sql .= ' WHERE ' . join(' AND ', $whereClauses) . ' ORDER BY l.zip_code, l.state, l.country LIMIT :locationlimit';

    $stmt = $database->prepare($sql);

    // Binding the parameters
    $stmt->bindParam(':businessstatus', $input['businessstatus']);
    $stmt->bindParam(':cid', $id);
    $stmt->bindParam(':locationlimit', $input['locationlimit'], PDO::PARAM_INT);

    if ($input['consideruserdata'] && $current_user_data) {
      $stmt->bindParam(':user_zipcode', $current_user_data['zipcode']);
      $stmt->bindParam(':user_city', $current_user_data['city']);
      $stmt->bindParam(':user_state', $current_user_data['state']);
    }

    if (!empty($input['opendt'])) {
      $stmt->bindParam(':day_date', $date->format('Y-m-d'));
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($results)) return $results[0];
    return false;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getrating($id, $startrecord = 0, $numofrecords = 10)  // provide company_id
  {
    global $database;
    $sql = 'SELECT u.username, r.*
FROM bg_company_rewards_ratings AS r
 JOIN bg_users AS u ON r.user_id = u.user_id 
WHERE r.status = "active" and r.company_id = :cid order by r.create_dt desc limit ' . $numofrecords;
    $stmt = $database->prepare($sql);
    $stmt->execute([':cid' => $id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getcompany2($limit)
  {
    global $database;
    $sql = '  SELECT	c.*,   a.description as company_logo FROM	bg_companies AS c
LEFT JOIN	bg_company_attributes AS a	ON 
c.company_id = a.company_id  and a.category="company_logos" and a.`grouping` ="primary_logo"
where c.`status` ="finalized" and a.description is not null
limit 1';
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getBirthElement($birthdate, $element = 'stone')
  {
    // Assuming birthdate is in the format 'YYYY-MM-DD'
    $month = intval(date('m', strtotime($birthdate)));

    switch (strtolower($element)) {
      case 'stone':
        $elementarray = [
          1 => 'Garnet',
          2 => 'Amethyst',
          3 => 'Aquamarine',
          4 => 'Diamond',
          5 => 'Emerald',
          6 => 'Pearl',
          7 => 'Ruby',
          8 => 'Peridot',
          9 => 'Sapphire',
          10 => 'Opal',
          11 => 'Topaz',
          12 => 'Turquoise'
        ];
        $icon = 'diamond.gif';
        break;
      case 'color':
        $elementarray = [
          1 => 'Cool Blue',
          2 => 'Purple or Violet',
          3 => 'Light Blue or Aquamarine',
          4 => 'White or Clear',
          5 => 'Bright Green',
          6 => 'Light Purple or Pearl',
          7 => 'Red',
          8 => 'Light Green or Yellow',
          9 => 'Deep Blue',
          10 => 'Pink or Opal',
          11 => 'Yellow or Golden',
          12 => 'Turquoise or Blue'
        ];
        $icon = 'paint-palette.gif';
        break;
      case 'flower':
        $elementarray = [
          1 => ['Carnation', 'Snowdrop'],
          2 => ['Violet', 'Primrose'],
          3 => ['Daffodil', 'Jonquil'],
          4 => ['Daisy', 'Sweet Pea'],
          5 => ['Lily of the Valley', 'Hawthorn'],
          6 => ['Rose', 'Honeysuckle'],
          7 => ['Larkspur', 'Water Lily'],
          8 => ['Gladiolus', 'Poppy'],
          9 => ['Aster', 'Morning Glory'],
          10 => ['Marigold', 'Cosmos'],
          11 => ['Chrysanthemum'],
          12 => ['Narcissus', 'Holly']
        ];
        $icon = 'tulip.gif';
        break;
    }
    return array('message' => $elementarray[$month], 'icon' => $icon) ?? array('message' => 'Unknown Element', 'icon' => '');
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getAstroSign($birthdate, $type = 'signonly')
  {
    // Parse birthday 
    $birthdate = new DateTime($birthdate);
    $month = $birthdate->format('n'); // Numeric representation of a month, without leading zeros
    $day = $birthdate->format('j'); // Day of the month without leading zeros
    $year = $birthdate->format('Y'); // Full numeric representation of a year, 4 digits

    // Astrological signs by date range and element
    $signs = [
      'aquarius' => ['start' => [1, 20], 'end' => [2, 18], 'element' => 'Air'],
      'pisces' => ['start' => [2, 19], 'end' => [3, 20], 'element' => 'Water'],
      'aries' => ['start' => [3, 21], 'end' => [4, 19], 'element' => 'Fire'],
      'taurus' => ['start' => [4, 20], 'end' => [5, 20], 'element' => 'Earth'],
      'gemini' => ['start' => [5, 21], 'end' => [6, 20], 'element' => 'Air'],
      'cancer' => ['start' => [6, 21], 'end' => [7, 22], 'element' => 'Water'],
      'leo' => ['start' => [7, 23], 'end' => [8, 22], 'element' => 'Fire'],
      'virgo' => ['start' => [8, 23], 'end' => [9, 22], 'element' => 'Earth'],
      'libra' => ['start' => [9, 23], 'end' => [10, 22], 'element' => 'Air'],
      'scorpio' => ['start' => [10, 23], 'end' => [11, 21], 'element' => 'Water'],
      'sagittarius' => ['start' => [11, 22], 'end' => [12, 21], 'element' => 'Fire'],
      'capricorn' => ['start' => [12, 22], 'end' => [1, 19], 'element' => 'Earth']
    ];

    $chinese_zodiac = [
      'rat' => ['years' => [1924, 1936, 1948, 1960, 1972, 1984, 1996, 2008, 2020], 'element' => 'Water'],
      'ox' => ['years' => [1925, 1937, 1949, 1961, 1973, 1985, 1997, 2009, 2021], 'element' => 'Earth'],
      'tiger' => ['years' => [1926, 1938, 1950, 1962, 1974, 1986, 1998, 2010, 2022], 'element' => 'Wood'],
      'rabbit' => ['years' => [1927, 1939, 1951, 1963, 1975, 1987, 1999, 2011, 2023], 'element' => 'Wood'],
      'dragon' => ['years' => [1928, 1940, 1952, 1964, 1976, 1988, 2000, 2012, 2024], 'element' => 'Earth'],
      'snake' => ['years' => [1929, 1941, 1953, 1965, 1977, 1989, 2001, 2013, 2025], 'element' => 'Fire'],
      'horse' => ['years' => [1930, 1942, 1954, 1966, 1978, 1990, 2002, 2014, 2026], 'element' => 'Fire'],
      'goat' => ['years' => [1931, 1943, 1955, 1967, 1979, 1991, 2003, 2015, 2027], 'element' => 'Earth'],
      'monkey' => ['years' => [1932, 1944, 1956, 1968, 1980, 1992, 2004, 2016, 2028], 'element' => 'Metal'],
      'rooster' => ['years' => [1933, 1945, 1957, 1969, 1981, 1993, 2005, 2017, 2029], 'element' => 'Metal'],
      'dog' => ['years' => [1934, 1946, 1958, 1970, 1982, 1994, 2006, 2018, 2030], 'element' => 'Earth'],
      'pig' => ['years' => [1935, 1947, 1959, 1971, 1983, 1995, 2007, 2019, 2031], 'element' => 'Water']
    ];

    $finalname = false;
    $element = '';
    $dates = [];

    // Determine sign based on the $type parameter
    if ($type == 'chinesesign') {
      foreach ($chinese_zodiac as $name => $info) {
        if (in_array($year, $info['years'])) {
          $finalname = $name;
          $element = $info['element'];
          break;
        }
      }
    } else {
      // Loop through signs
      foreach ($signs as $name => $info) {
        if (
          ($month == $info['start'][0] && $day >= $info['start'][1]) ||
          ($month == $info['end'][0] && $day <= $info['end'][1])
        ) {
          $finalname = $name;
          $element = $info['element'];
          $dates = ['start' => $info['start'], 'end' => $info['end']];
          break; // Exit the loop once the sign is found
        }
      }
    }

    switch ($type) {
      case 'all':
        if ($finalname) {
          $element_meanings = [
            'Earth' => 'Practical, stable, grounded, reliable, helpful, focuses on tangible achievements',
            'Air' => 'Intellectual, communicative, sharp, thrives on communication and data collection skills, prone to fantasy and theory',
            'Fire' => 'Bold, creative, daring, passionate, radiates energy, can have short tempers and burn brightly',
            'Water' => 'Emotional, intuitive, in touch with subconscious, highly intuitive, can get overwhelmed by emotions'
          ];
          return array('sign' => $finalname, 'name' => ucwords($finalname),  'dates' => $dates, 'element' => $element, 'element_meaning' => $element_meanings[$element]);
        } else {
          return 'Invalid birthdate';
        }
        break;
      case 'chinesesign':
        $element_meanings = [
          'Wood' => 'Spring, imagination, creativity, optimism, and courage',
          'Fire' => 'Summer, passion, and bursts of energy',
          'Earth' => 'Seasonal transitions, kindness, tolerance, honesty, and leadership',
          'Metal' => 'Autumn, solidity, willpower, discipline, focus, loyalty, and determination',
          'Water' => 'Winter, responsiveness, persuasion, sensitivity, creativity, and sympathy'
        ];

        $output = ['sign' => $finalname, 'name' => ucwords($finalname), 'year' => $year, 'element' => $element, 'element_meaning' => $element_meanings[$element]];
        return $output;
        break;
      default:
        return $finalname ? $finalname : 'Invalid birthdate';
        break;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function getTopSongWrapper($birthdate)
  {
    // Check if the data exists in the database
    $result = $this->checkDatabase($birthdate);

    if (!empty($result)) {
      return $result;
    }

    // If not, call the existing function
    $data = $this->getTopSong($birthdate);

    // Store the data in the database
    $this->insertIntoDatabase($birthdate, $data);

    return $data;
  }

  function checkDatabase($birthdate)
  {
    // Query the database to see if the birthdate's data exists
    // Return the data if it exists, null otherwise
  }

  function insertIntoDatabase($birthdate, $data)
  {
    // Insert the data into the database
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function getTopSongAPI($findDate, $genres, $randomGenre)
  {

    $buildURL = "https://www.billboard.com/charts/" . $genres[$randomGenre] . "/" . $findDate . '/';

    $curl = curl_init($buildURL);

    // Set the cURL options
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Disable SSL certificate verification
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);


    $htmlOutput = curl_exec($curl);
    curl_close($curl);

    if (!empty($htmlOutput)) {
      libxml_use_internal_errors(true); // Suppress HTML parsing errors and warnings

      $dom = new DOMDocument();
      @$dom->loadHTML($htmlOutput); // Suppress errors with the '@' symbol

      libxml_clear_errors(); // Clear the error buffer

      $xpath = new DOMXPath($dom);


      // XPath query to find the parent container for the songs
      $queryContainer = "//div[contains(@class, 'o-chart-results-list-row-container')]";
      $containerNodes = $xpath->query($queryContainer);

      // Check if the container is found
      if ($containerNodes->length > 0) {
        $containerNode = $containerNodes->item(0);

        // Query to find the song title
        $queryTitle = ".//h3[@id='title-of-a-story']";
        $titleNodes = $xpath->query($queryTitle, $containerNode);

        // Query to find the artist name
        $queryArtist = ".//span[contains(@class, 'c-label')]";
        $artistNodes = $xpath->query($queryArtist, $containerNode);

        // Check if title and artist nodes are found
        if ($titleNodes->length > 0 && $artistNodes->length > 0) {
          $song = trim($titleNodes->item(0)->nodeValue);
          $artist = " by " . trim($artistNodes->item(1)->nodeValue);
          #     $message = "The song title is: " . $song . " and the artist is: " . $artist;
          #    $message = "The " . $randomGenre . " for " . $randomCategory . " was: " . $song . " " . $artist;
          $status = true;
        } else {
          $message = "Information Song or artist not found"; // Debugging message
        }
      } else {
        $message = "Informaiton Container not found"; // Debugging message
      }
    }
    return [
      'status' => $status,
      # 'randomCategory' => $randomCategory,
      'randomGenre' => $randomGenre,
      # 'message' => $message,
      'song' => $song ?? "Song not found",
      'artist' => $artist ?? "Artist not found",
      'findDate' => $findDate,
      'findYear' => date('Y', strtotime($findDate)),
      'buildURL' => $buildURL,
      // 'containerNode' => $containerNode
    ];
  }



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function validateCaptcha()
{
    global $session;

    // Check if the 'recaptcha' value exists and matches the stored session value
    if (!empty($_REQUEST['recaptcha']) && $_REQUEST['recaptcha'] === $session->get('recaptcha')) {
        return true;
    }
    return false;
}


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function generateCaptcha($size = 'large', $numberofoptions = 4)
{
    global $session;

    // Start the session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        return false;
    }

    // Get the session ID
    $sessionId = session_id();

    switch ($size) {
        case 'small':
            $sizetag = ' h5 w-25';
            $spacingtag = 'mx-1';
            break;
        case 'medium':
            $sizetag = ' h3 w-50';
            $spacingtag = 'mx-2';
            break;
        default:
            $sizetag = ' h1 w-75';
            $spacingtag = 'mx-3';
            break;
    }

    // Define the options and their corresponding icons
    $options = [
        'house' => 'bi-house-fill',
        'plane' => 'bi-airplane-fill',
        'apple' => 'bi-apple',
        'heart' => 'bi-heart-fill',
        'camera' => 'bi-camera-fill',
        'clock' => 'bi-clock-fill',
        'key' => 'bi-key-fill',
        'tree' => 'bi-tree-fill',
        'star' => 'bi-star-fill',
        'bus' => 'bi-bus-front-fill',
        'car' => 'bi-car-front-fill',
        'bicycle' => 'bi-bicycle',
        'bell' => 'bi-bell-fill',
        'envelope' => 'bi-envelope-fill',
        'phone' => 'bi-telephone-fill',
        'umbrella' => 'bi-umbrella-fill',
        'cup' => 'bi-cup-fill',
        'sun' => 'bi-sun-fill',
        'moon' => 'bi-moon-fill',
        'gift' => 'bi-gift-fill',
        'flag' => 'bi-flag-fill',
        'rocket' => 'bi-rocket-fill',
        'scissors' => 'bi-scissors',
        'trophy' => 'bi-trophy-fill',
    ];

    // Randomly pick four options
    $selectedOptions = array_rand($options, $numberofoptions);

    // Randomly pick one option to be the correct one from the selected options
    $correctOption = $selectedOptions[array_rand($selectedOptions)];

    // Store the correct option's session ID in the session
    $session->set('recaptcha', $sessionId);

    // Initialize output
    $output = '<div class="mb-3">';

    // Add label and icons container with flex properties
    $output .= '<div class="d-flex align-items-start">';
    $output .= '<div class="flex-shrink-0"><label class="form-label mb-0">Select the ' . strtoupper($correctOption) . ':</label></div>';
    $output .= '<div class="d-flex flex-wrap flex-grow-1 ms-3">';

    // Generate the HTML for each selected option
    foreach ($selectedOptions as $option) {
        $icon = $options[$option];
        $value = ($option === $correctOption) ? $sessionId : bin2hex(random_bytes(8));
        $output .= '
            <div class="mb-2 mx-2" style="max-width: 80px;">
                <input type="radio" class="btn-check" name="recaptcha" id="option-' . $option . '" value="' . $value . '">
                <label class="btn btn-outline-secondary w-100" for="option-' . $option . '" style="max-width: 80px;">
                    <i class="bi ' . $icon . ' ' . $sizetag . ' pt-2"></i>
                </label>
            </div>';
    }

    // Close the containers
    $output .= '</div></div></div>';

    return $output;
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function getTopSong($birthdate)
  {
    global $database;

    $categories = [
      'the week you were born' => 0,
      'when you turned 5' => '+5 years',
      'when you turned 18' => '+18 years',
      'when you turned 21' => '+21 years',
      'when you turned 30' => '+30 years',
      'when you turned 40' => '+40 years',
      'when you turned 50' => '+50 years',
      'when you turned 60' => '+60 years',
      'when you turned 70' => '+70 years',
    ];
    $genres = [
      'Top Rock Song' => 'hot-100',
      //  'Top Country Song' => 'country-songs',
      //  'Top R&B Song' => 'r-and-b-songs'
    ];
    $status = false;

    // Calculate the person's age
    $currentDate = new DateTime();
    $birthDate = new DateTime($birthdate);
    $age = $currentDate->diff($birthDate)->y; // Calculate age in years

    // Filter categories based on age
    $filteredCategories = [];
    foreach ($categories as $key => $value) {
      $ageForCategory = ($value === 0) ? 0 : intval(substr($value, 1, 2)); // Extract age value from the string
      if ($ageForCategory <= $age) {
        $filteredCategories[$key] = $value;
      }
    }

    $randomCategory = array_rand($filteredCategories);
    $randomGenre = array_rand($genres);

    $findDate = date('Y-m-d', strtotime($birthdate . ' ' . $filteredCategories[$randomCategory]));

    // Check if the data exists in the database
    list($event_year, $event_month, $event_day) = explode('-', $findDate);

    $sql = "SELECT * FROM bg_historic_eventdata WHERE type='hottest_song' AND event_year=? AND event_month=? AND event_day=? limit 1";
    $stmt = $database->prepare($sql);
    $stmt->execute([$event_year, $event_month, $event_day]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($results[0]['event_description'])) {
      $data = json_decode($results[0]['event_description'], true);
      $data['message'] = "The " . $randomGenre . " for " . $randomCategory . " was: " . $data['song'] . " " . $data['artist'];
      return $data;
    }

    // If not, call the existing function
    $data = $this->getTopSongAPI($findDate, $genres, $randomGenre);

    // Store the data in the database
    $event_description = json_encode($data);
    $sql = "INSERT INTO bg_historic_eventdata (type, event_year, event_month, event_day, source, event_description, create_dt, status) VALUES ('hottest_song', ?, ?, ?, 'billboard', ?, NOW(), 'active')";
    $stmt = $database->prepare($sql);
    $stmt->execute([$event_year, $event_month, $event_day, $event_description]);
    $data['message'] =  "The " . $randomGenre . " for " . $randomCategory . " was: " . $data['song'] . " " . $data['artist'];
    return $data;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getZodiacInfo($sign)
  {
    $signs = [
      'aquarius' => ['symbol' => '♒', 'name' => 'Aquarius (The water bearer)'],
      'pisces' => ['symbol' => '♓', 'name' => 'Pisces (The fish)'],
      'aries' => ['symbol' => '♈', 'name' => 'Aries (The Ram)'],
      'taurus' => ['symbol' => '♉', 'name' => 'Taurus (The bull)'],
      'gemini' => ['symbol' => '♊', 'name' => 'Gemini (The Twins)'],
      'cancer' => ['symbol' => '♋', 'name' => 'Cancer (The Crab)'],
      'leo' => ['symbol' => '♌', 'name' => 'Leo (The Lion)'],
      'virgo' => ['symbol' => '♍', 'name' => 'Virgo (The Virgin)'],
      'libra' => ['symbol' => '♎', 'name' => 'Libra (The Scales)'],
      'scorpio' => ['symbol' => '♏', 'name' => 'Scorpio (The Scorpion)'],
      'sagittarius' => ['symbol' => '♐', 'name' => 'Sagittarius (Centaur The Archer)'],
      'capricorn' => ['symbol' => '♑', 'name' => 'Capricorn (Goat-horned, The sea goat)']
    ];

    if (array_key_exists($sign, $signs)) {
      return $signs[$sign];
    } else {
      return 'Invalid astrological sign';
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function calculateAge($birthDate)
  {

        // If the input date is empty, return false
        if (empty($birthDate)) {
          return false;
      }

      // Try to create a DateTime object to validate the date
    //  $date = DateTime::createFromFormat('Y-m-d', $birthDate);
  //    if (!$date || $date->format('Y-m-d') !== $birthDate) {
   //       return false; // Return false if the date is invalid or doesn't match the format
   //   }

    $output = array();
    $today = new DateTime('now');
    $birthdate = new DateTime($birthDate);
    $interval = $today->diff($birthdate);

    $output['days'] = $interval->format('%a');
    $output['years'] = $interval->format('%y');
    $output['months'] = $interval->format('%m');
    $output['minutes'] = $interval->format('%i');
    $output['seconds'] = $interval->format('%s');

    // Date components of the provided birthDate
    $output['dtpart_year'] = $birthdate->format('Y');
    $output['dtpart_month'] = $birthdate->format('m');
    $output['dtpart_day'] = $birthdate->format('d');
    $output['dtpart_hour'] = $birthdate->format('H');
    $output['dtpart_minute'] = $birthdate->format('i');
    $output['dtpart_second'] = $birthdate->format('s');

    // If years is zero, set the tag to months; otherwise, set to years
    if ($interval->y == 0) {
      $output['agetag'] = $interval->m . ' months';
    } else {
      $output['agetag'] = $interval->y . ' years';
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getAvailabilityTag($availability_from_date, $expiration_date)
  {
    $availability_date = new DateTime($availability_from_date);
    $now = new DateTime();
    $availability_tag = '';
    $expiration_tag = '';
    $flag = '';

    // Check if the expiration date is set and in the past
    if (!empty($expiration_date)) {
      $expiration_date_obj = new DateTime($expiration_date);

      if ($expiration_date_obj < $now) {
        // Mark as expired in both availability and expiration tags
        $availability_tag = '<span class="badge bg-danger position-absolute top-0 end-0 m-2">Expired</span>';
        $expiration_tag = '<span class="badge bg-danger">Expired</span>';
        $flag = 'expired';
        $redeembuttontext = '<i class="bi bi-gift me-2"></i> Reward Details';
      } else {
        // If the expiration date is set but not in the past, display it
        $expiration_tag = $expiration_date_obj->format('M j, Y');
      }
    } else {
      // If no expiration date is set, show "Never"
      $expiration_tag = '<span class="badge bg-light" style="color:#999">Never</span>';
      $flag = 'available';
      $redeembuttontext = '<i class="bi bi-gift me-2"></i> Redeem Now';
    }

    // Determine availability tag if not expired
    if ($availability_tag === '') {
      if ($availability_date > $now) {
        $availability_tag = '<span class="badge bg-warning position-absolute top-0 end-0 m-2">Available ' . $availability_date->format('M j, Y') . '</span>';
        $flag = 'pending';
      } else {
        $availability_tag = '<span class="badge bg-success position-absolute top-0 end-0 m-2">Available NOW</span>';
        $flag = 'available';
      }
    }

    // Return an array with both tags
    return [
      'availability' => $availability_tag,
      'expiration' => $expiration_tag,
      'flag' => $flag,
      'redeembuttontext' => $redeembuttontext??'',
    ];
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function getTimeTilBirthday($birthdate)
  {
    // Parse birthdate  
    $birthdate = new DateTime($birthdate);
    $month = $birthdate->format('m');
    $day = $birthdate->format('d');

    // Current date
    $today = new DateTime();

    // Set birthday this year
    $birthdayThisYear = new DateTime();
    $birthdayThisYear->setDate($today->format('Y'), $month, $day);

    // Check if birthday has passed
    if ($birthdayThisYear < $today) {
      // Set next year
      $birthdayThisYear->modify('+1 year');
    } else if ($birthdayThisYear == $today) {
      return [
        'datenumber' => $birthdayThisYear,
        'days' => 0,
        'hours' => 0,
        'seconds' => 0,
        'months' => 0,
        'weeks' => 0,
        'date' => $birthdayThisYear->format('Y-m-d'), // Format the date as "day, month day, year"
        'formatted_date' => $birthdayThisYear->format('D, M. j, Y'), // Format the date as "day, month day, year"
        'dayofweek' => $birthdayThisYear->format('l'),
      ];
    }

    // Calculate the difference
    $diff = $birthdayThisYear->diff($today);

    // Get total days
    $days = $diff->days;

    // Calculate months and weeks
    $months = $diff->m + ($diff->y * 12); // Total months difference, including years
    $weeks = floor($days / 7); // Total weeks based on the number of days

    // Convert days to hours and seconds
    $hours = $days * 24;
    $seconds = $hours * 3600;

    // Return the result with additional months and weeks
    return [
      'datenumber' => $birthdayThisYear,
      'days' => $days,
      'hours' => $hours,
      'seconds' => $seconds,
      'months' => $months,
      'weeks' => $weeks,
      'date' => $birthdayThisYear->format('Y-m-d'), // Format the date as "day, month day, year"
      'formatted_date' => $birthdayThisYear->format('D, M. j, Y'), // Format the date as "day, month day, year"
      'dayofweek' => $birthdayThisYear->format('l'),
    ];
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  // Function to get the elements since a specific date
  function getTimeSinceDate($pastDate)
  {
    // Parse the past date
    $pastDate = new DateTime($pastDate);

    // Current date
    $today = new DateTime();

    // Check if the past date is in the future
    if ($pastDate > $today) {
      return [
        'error' => 'The provided date is in the future.'
      ];
    }

    // Calculate the difference
    $diff = $today->diff($pastDate);

    // Get total days since
    $days = $diff->days;

    // Calculate months and weeks
    $years = $diff->y; // Total years difference
    $months = $diff->m + ($years * 12); // Total months difference, including years
    $weeks = floor($days / 7); // Total weeks based on the number of days

    // Convert days to hours and seconds
    $hours = $days * 24;
    $seconds = $hours * 3600;

    // Return the result with years, months, and weeks
    return [
      'datenumber' => $pastDate,
      'years' => $years,                // Total years since the past date
      'months' => $months,              // Total months including years
      'weeks' => $weeks,                // Total weeks since the past date
      'days' => $days,                  // Total days since the past date
      'hours' => $hours,                // Total hours since the past date
      'seconds' => $seconds,            // Total seconds since the past date
      'date' => $pastDate->format('Y-m-d'), // Format the date as "year-month-day"
      'formatted_date' => $pastDate->format('D, M. j, Y'), // Format the date as "day, month day, year"
      'dayofweek' => $pastDate->format('l'), // Day of the week
    ];
    /*
// Example usage:
$pastDate = '2024-08-01';
$result = getTimeSinceDate($pastDate);
*/
  }






  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function mapsearchlink($company = [], $current_user_data = [], $type = 'google')
  {

    $search_address = $current_user_data['profile_mailing_address'] . ', ' .
      $current_user_data['profile_city'] . ', ' .
      $current_user_data['profile_state'] . ' ' .
      $current_user_data['profile_zip_code'];


    switch ($type) {
      case 'google':
        $link = '<a href="https://www.google.com/maps/search/' . urlencode($company['company_name']) . '+near+' . urlencode($search_address) . '" target="_blank" class="btn btn-info btn-lg"><i class="bi bi-geo-alt-fill"></i> Map</a>';
        break;

      case 'googlefindlocation':
        $link = ' <a href="https://www.google.com/maps/search/' . urlencode($company['company_name']) . '+near+' . urlencode($search_address) . '" target="_blank" class="ms-5 btn btn-info btn-lg px-5 py-3" data-bs-toggle="tooltip" data-bs-html="true" data-bs-original-title="Searching near:<br>' . htmlspecialchars($search_address) . '">
  <i class="bi bi-geo-alt-fill me-2"></i> Find Locations Near Your Address</a>';
        break;
    }

    return $link;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getpromocode($input = '')
  {
    global $database;
    $params = array(
      ':lookupvalue' => strtoupper($input)
    );
    $sql = 'select * from bg_promocodes where upper(code)=:lookupvalue and `status`="active" limit 1';
    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    #breakpoint($results);
    $data = array();

    if (!$results) {
      $status = false;
      $resultcode = 'invalid';
      $resultmessage = 'Hmm... Invalid promo code.';
    } else {
      $status = true;
      $resultcode = 'valid';
      $resultmessage =  $results[0]['successmessage'];
      $data =  $results[0];

      if ($results[0]['end_dt'] < date('Y-m-d H:i:s')) {
        $status = false;
        $resultcode = 'expired';
        $resultmessage = 'Darn! Promo is over.';
      }

      if ($results[0]['start_dt'] >= date('Y-m-d H:i:s')) {
        $status = false;
        $resultcode = 'not_started';
        $resultmessage = 'Oops! Promo has not started.';
      }

      if ($results[0]['tracking_count'] >= $results[0]['limit_count']) {
        $status = false;
        $resultcode = 'limit_reached';
        $resultmessage = 'Ugh! Promo code usage limit reached.';
      }
    }
    return  array('status' => $status, 'resultcode' => $resultcode, 'resultmessage' => $resultmessage, 'data' => $data);
  }





  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getreferrer($input = '')
  {
    global $database;
    $params = array(
      ':lookupvalue' => strtoupper($input)
    );
    $sql = 'select * from bg_promocodes where upper(code)=:lookupvalue and `status`="xxxx" limit 1';
    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    #breakpoint($results);
    $data = array();

    if (!$results) {
      $status = false;
      $resultcode = 'invalid';
      $resultmessage = 'Hmm... Invalid referrer code.';
    } else {
      $status = true;
      $resultcode = 'valid';
      $resultmessage =  $results[0]['successmessage'];
      $data =  $results[0];

      if ($results[0]['end_dt'] < date('Y-m-d H:i:s')) {
        $status = false;
        $resultcode = 'expired';
        $resultmessage = 'Darn! referrer is over.';
      }

      if ($results[0]['start_dt'] >= date('Y-m-d H:i:s')) {
        $status = false;
        $resultcode = 'not_started';
        $resultmessage = 'Oops! referrer has not started.';
      }

      if ($results[0]['tracking_count'] >= $results[0]['limit_count']) {
        $status = false;
        $resultcode = 'limit_reached';
        $resultmessage = 'Ugh! referrer code usage limit reached.';
      }
    }
    return  array('status' => $status, 'resultcode' => $resultcode, 'resultmessage' => $resultmessage, 'data' => $data);
  }




  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getFeaturedCompanies($limit = '5', $displaycategory = '', $type = 'featured', $exclusionlist = null, $options = [])
  {
    $limitsql = ' limit ' . $limit;
    if ($limit == 'active')   $limitsql = '';
    if (strpos($limit, ',') !== false) {
      list($limitsplit, $page) = explode(',', $limit);
      // Calculate the offset based on the page number and limit
      $offset = ($page - 1) * $limitsplit;
      // Add LIMIT and OFFSET to the SQL query
      $limitsql = ' LIMIT ' . $limitsplit . ' OFFSET ' . $offset;
    }


    global $database, $current_user_data;
    $categorycriteria = '';
    $exclusioncriteria = '';
    $agecriteria = '';
    $regioncriteria = '';
    if (!empty($options['age'])) {
      $agecriteria = ' and ' . $options['age'] . ' between minage and maxage ';
    }
    if (!empty($options['region']) && !empty($current_user_data['statecode'])) {
      $regioncriteria = ' and region_type like "%' . strtoupper($current_user_data['statecode']) . ',%"';
    }
    $preblock=$postblock='';
    $ordertag = 'order by rand()';
    if (  $displaycategory=='!!alphabetical!!') {
$preblock='SELECT * 
FROM (';

$postblock=') AS result_set
ORDER BY company_name';

    } 
    if ($type == 'selection') $ordertag = 'order by c.company_name';
    if (!empty($exclusionlist)) {

      $exclusioncriteria = ' and  c.company_id not in (';
      if (is_array($exclusionlist) && !empty($exclusionlist)) $exclusioncriteria .= implode(',', $exclusionlist);
      else $exclusioncriteria .= $exclusionlist;
      $exclusioncriteria .= ')';
      #breakpoint($exclusioncriteria);
    }
    if ($displaycategory != '' && $displaycategory!='!!alphabetical!!') $categorycriteria = ' and c.display_category="' . $displaycategory . '"';

    // build QUERY
    $sql = 'SELECT distinct c.*, a.description as company_logo FROM bg_companies AS c
LEFT JOIN bg_company_attributes AS a ON 
c.company_id = a.company_id  and a.category="company_logos" and a.`grouping` ="primary_logo"
where c.`status` ="finalized" and a.description is not null 
' . $categorycriteria . ' ' . $exclusioncriteria . ' ' . $agecriteria . ' ' . $regioncriteria . ' ' . $ordertag . ' ' . $limitsql;



$sql =$preblock. ' SELECT c.*, MAX(a.description) as company_logo 
        FROM bg_companies AS c
        LEFT JOIN bg_company_attributes AS a 
            ON c.company_id = a.company_id  
            AND a.category = "company_logos" 
            AND a.grouping = "primary_logo"
        WHERE c.status = "finalized" 
            AND a.description IS NOT NULL 
            ' . $categorycriteria . ' ' . $exclusioncriteria . ' ' . $agecriteria . ' ' . $regioncriteria . '
        GROUP BY c.company_id
        ' . $ordertag . ' ' . $limitsql.' '. $postblock;

    # breakpoint($limit);
    session_tracking('selectcompanies', $sql);
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getCustomizedCompanies($limit, $profile_data, $include_suppressed = false, $displaycategory = '', $type = 'featured', $exclusionlist = null)
  {
    global $database;
    $categorycriteria = '';
    $exclusioncriteria = '';
    $agecriteria = '';
    $dietarycriteria = '';

    // Exclusion list criteria
    if (!empty($exclusionlist)) {
      $exclusioncriteria = ' and c.company_id not in (';
      if (is_array($exclusionlist) && !empty($exclusionlist))
        $exclusioncriteria .= implode(',', $exclusionlist);
      else
        $exclusioncriteria .= $exclusionlist;
      $exclusioncriteria .= ')';
    }

    // Category criteria
    if ($displaycategory != '')
      $categorycriteria = ' and display_category="' . $displaycategory . '"';

    // Age criteria
    if (isset($profile_data['age'])) {
      $user_age = $profile_data['age'];
      $agecriteria = " AND c.minage <= {$user_age} AND c.maxage >= {$user_age}";
    }

    // Dietary preferences and allergies criteria
    $dietary_subqueries = [];
    foreach ($profile_data as $key => $value) {
      if (strpos($key, "profile_allergy_") !== false || strpos($key, "profile_diet_") !== false) {
        $attribute_name = str_replace("profile_", "", $key);
        $attribute_name = str_replace("allergy_", "", $attribute_name);
        $attribute_name .= "_option"; // Converts to format like gluten_free_option

        // Create subquery
        $dietary_subqueries[] = "(SELECT company_id FROM bg_company_attributes WHERE name='{$attribute_name}' AND description='no')";
      }
    }
    if (!empty($dietary_subqueries)) {
      if ($include_suppressed)
        $dietarycriteria = " AND c.company_id IN (" . implode(' UNION ', $dietary_subqueries) . ")";
      else
        $dietarycriteria = " AND c.company_id NOT IN (" . implode(' UNION ', $dietary_subqueries) . ")";
    }

    $ordertag = 'order by rand()';
    if ($type == 'selection')
      $ordertag = 'order by c.company_name';

    // Main SQL
    $sql = 'SELECT c.*, a.description as company_logo 
            FROM bg_companies AS c
            LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id AND a.category="company_logos" AND a.`grouping`="primary_logo"
            WHERE c.`status`="finalized" AND a.description IS NOT NULL'
      . $categorycriteria
      . $exclusioncriteria
      . $agecriteria
      . $dietarycriteria
      . ' ' . $ordertag . ' limit ' . $limit;

    $stmt = $database->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getSelectionCompanies($limit, $displaycategory = 'All', $exclusionlist = null, $options = [])
  {
    $limitsql = ' limit ' . $limit;
    if (strpos($limit, ',')) {
      list($limitsplit, $page) = explode(',', $limit);
      // Calculate the offset based on the page number and limit
      $offset = ($page - 1) * $limitsplit;
      // Add LIMIT and OFFSET to the SQL query
      $limitsql = ' LIMIT ' . $limitsplit . ' OFFSET ' . $offset;
    }

    if (empty($exclusionlist)) {
      global $database, $session;
      $current_user_data = $session->get('current_user_data');
      $userid = $current_user_data['user_id'];
      $exclusionlist = [];
      $categorycriteria = '';
      switch ($displaycategory) {
        case 'All':
        case '':
          $categorycriteria = '';
          break;
        default:
          $categorycriteria = ' and display_category="' . $displaycategory . '" ';
          break;
      }
      $categorycriteria = '';
      $sql = 'select company_id from bg_user_companies where user_id= ' . $userid . ' ' . $categorycriteria . ' and `status` not in ("failed") ' . $limitsql;
      $stmt = $database->prepare($sql);
      $stmt->execute();
      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($results as $result) {
        $exclusionlist[] = $result['company_id'];
      }
    }

    return $this->getFeaturedCompanies($limit, $displaycategory, 'selection', $exclusionlist, $options);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getcompanydetails($id, $type = 'shortdetails')
  {
      global $database;
      $sql = 'SELECT c.*, a.description as company_logo 
      FROM bg_companies AS c
      LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id 
          AND a.category = "company_logos" 
          AND a.`grouping` = "primary_logo"
      WHERE c.company_id = :id';
  
      $stmt = $database->prepare($sql);
      $stmt->execute([':id' => $id]);
      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      return !empty($results) ? $results[0] : null;
  }

  

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getHistoricalEvents($birthdate, $type = 'events')
  {
    $date = new DateTime($birthdate);
    $year = $date->format('Y');
    $month = $date->format('m');
    $day = $date->format('d');

    // Try to get historical events from the database
    $events = $this->getHistoricalEventsFromDatabase($birthdate, $type);

    // If no events were found in the database, try to get them from the API
    if (empty($events)) {
      $events = $this->getHistoricalEventsFromAPI($birthdate, $type);
    }

    // Check if events is an array before trying to count it
    if (!is_array($events)) {
      // You might want to return some kind of default value here
      // For now, let's just return an empty array
      return [];
    }

    $output = [];
    $output['data'] = $events;

    $birthdayEvents = [];
    $monthEvents = [];
    $yearEvents = [];

    foreach ($events as $event) {
      if ($event['year'] == $year) {
        if ($event['month'] == $month) {
          if ($event['day'] == $day) {
            $birthdayEvents[] = $event;
          } else {
            $monthEvents[] = $event;
          }
        } else {
          $yearEvents[] = $event;
        }
      }
    }
    $totalEvents = count($output['data']);
    switch ($totalEvents) {

      case 0:
        $tag = 'no historical events occured.  But, YOU were born, and that\'s very important.';
        break;
      case 1:
        $tag = 'one event happened:';
        break;
      default:
        $tag = 'these events happened:';
        break;
    }


    if (!empty($birthdayEvents)) {
      $output['comment'] = "On your birthday, ";
    } elseif (!empty($monthEvents)) {
      $output['comment'] = "In the month you were born, ";
    } elseif (!empty($yearEvents)) {
      $output['comment'] = "In the year you were born, ";
    } else {
      $output['comment'] = '';
    }
    $output['comment'] .= $tag;
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getHistoricalEventsFromDatabase($birthdate, $type = 'events', $nearest_year = false)
  {
    global $database;
    $date = new DateTime($birthdate);
    $year = $date->format('Y');
    $month = $date->format('m');
    $day = $date->format('d');
    $orderby = 'ORDER BY day ASC';
    
    switch ($type) {
      case 'slang_words':
        $orderby = 'ORDER BY RAND()';
        break;
      default:
        $orderby = 'ORDER BY day ASC';
        break;
    }
    
    // Initial query - exact match on year (original behavior)
    $sql = "
  SELECT *
  FROM (
    SELECT event_year year, event_month month, event_day day, event_description event, source
    FROM bg_historic_eventdata 
    WHERE event_year = :year
      AND ((event_month = :month AND event_day = :day) 
           OR (event_month = :month2 ) 
           OR (event_month IS NULL AND event_day IS NULL)) 
      AND status = 'active' AND type=:type
    ORDER BY RAND()
  ) AS derived_table
  " . $orderby . "
  LIMIT 3  
  ";
    $params = ['type' => $type, 'year' => $year, 'month' => $month, 'month2' => $month, 'day' => $day];
    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
    // If no results and nearest_year is true, find the closest year with data
    if (empty($results) && $nearest_year) {
      // First try to find the nearest year with available data
      $sql_nearest = "
        SELECT event_year, ABS(event_year - :year) as year_diff
        FROM bg_historic_eventdata
        WHERE status = 'active' AND type = :type
        GROUP BY event_year
        ORDER BY year_diff ASC
        LIMIT 1
      ";
      
      $params_nearest = ['type' => $type, 'year' => $year];
      $stmt_nearest = $database->prepare($sql_nearest);
      $stmt_nearest->execute($params_nearest);
      $nearest_result = $stmt_nearest->fetch(PDO::FETCH_ASSOC);
      
      if ($nearest_result) {
        // We found a nearest year, now get the data for that year
        $nearest_year_value = $nearest_result['event_year'];
        
        $sql = "
        SELECT *
        FROM (
          SELECT event_year year, event_month month, event_day day, event_description event, source
          FROM bg_historic_eventdata 
          WHERE event_year = :year
            AND ((event_month = :month AND event_day = :day) 
                 OR (event_month = :month2 ) 
                 OR (event_month IS NULL AND event_day IS NULL)) 
            AND status = 'active' AND type=:type
          ORDER BY RAND()
        ) AS derived_table
        " . $orderby . "
        LIMIT 3  
        ";
        
        $params = ['type' => $type, 'year' => $nearest_year_value, 'month' => $month, 'month2' => $month, 'day' => $day];
        $stmt = $database->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add a flag to indicate this is an approximate year
        if (!empty($results)) {
          foreach ($results as &$result) {
            $result['approximate_year'] = true;
            $result['original_year'] = $year;
          }
        }
      }
    }
  
    // Check if results are still empty
    if (empty($results)) {
      return false;
    }
  
    // Special handling for generations (original code unchanged)
    if ($type == 'generations') {
      $generationDescriptions = [
        "Greatest Generation" => [
          "Born" => "Before 1928",
          "Context" => "Grew up during the Great Depression and many served in World War II.",
          "Traits" => "Characterized by strong values of duty, personal responsibility, and perseverance.",
          "Influence" => "Shaped by the hardships of the Depression and the war, they played a crucial role in achieving victory in World War II and rebuilding the world afterwards."
        ],
        "Silent Generation" => [
          "Born" => "1928-1945",
          "Context" => "Grew up during the Great Depression and World War II.",
          "Traits" => "Often characterized as traditional and disciplined.",
          "Influence" => "They experienced significant societal change, including the post-war economic boom."
        ],
        "Baby Boomers" => [
          "Born" => "1946-1964",
          "Context" => "Born during a period of post-World War II economic prosperity.",
          "Traits" => "Known for their strong work ethic, resourcefulness, and goal-centricity.",
          "Influence" => "Played a significant role in cultural and political change, including the civil rights movement."
        ],
        "Generation X" => [
          "Born" => "1965-1980",
          "Context" => "Grew up during a time of shifting societal values and the rise of dual-income families and single parents.",
          "Traits" => "Often seen as independent, resourceful, and self-sufficient.",
          "Influence" => "Witnessed the rise of personal computing and the internet."
        ],
        "Millennials" => [
          "Born" => "1981-1996",
          "Context" => "Came of age during the internet explosion, economic recession, and significant global events.",
          "Traits" => "Known for their tech-savviness, values-driven outlook, and adaptability.",
          "Influence" => "They have reshaped consumer culture and communication methods (e.g., social media)."
        ],
        "Generation Z" => [
          "Born" => "1997-2010",
          "Context" => "Grew up in a fully digital world, with rapid technological advancements and social media.",
          "Traits" => "Digital natives, socially conscious, and more racially and ethnically diverse.",
          "Influence" => "Very comfortable with technology; they value individual expression and avoid labels."
        ],
        "Generation Alpha" => [
          "Born" => "2010-2025",
          "Context" => "Born into a world of advanced technology, AI, and significant global challenges like climate change.",
          "Traits" => "Expected to be the most technologically immersed, highly educated, and globally connected.",
          "Influence" => "Likely to experience even more rapid technological changes and global interconnectedness."
        ]
      ];
      
      if (array_key_exists($results[0]['event'], $generationDescriptions)) {
        // Replace the event_description with the corresponding array from generationDescriptions
        $originalDescription = $results[0]['event'];
  
        // Replace the event_description with the corresponding array from generationDescriptions
        $results[0]['event'] = $generationDescriptions[$originalDescription];
  
        // Add the original description as an element to the array
        $results[0]['event']['Original'] = $originalDescription;
      }
    }
    
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getHistoricalEventsFromAPI($birthdate, $type = 'events')
  {
    global $database;
    $date = new DateTime($birthdate);
    $year = $date->format('Y');
    $month = $date->format('m');
    $day = $date->format('d');
    // Define the base API endpoint URL
    $base_url = "https://api.api-ninjas.com/v1/historicalevents";

    // The request headers
    $headers = [
      'X-Api-Key: vRGaydoX7MvRjVV1MU7Qdw==UK9hEqJCfRHOGKEW',
    ];

    // Initialize the events array
    $events = [];

    // Attempt to get the events with decreasing specificity (day, then month, then year)
    for ($i = 3; $i > 0; $i--) {
      // Form the URL based on the current level of specificity
      if ($i === 3) {
        $url = "$base_url?year=$year&month=$month&day=$day";
      } elseif ($i === 2) {
        $url = "$base_url?year=$year&month=$month";
      } else {
        $url = "$base_url?year=$year";
      }

      // Log the URL being used
      #echo ("Attempting to retrieve historical events with URL: $url");

      // Initialize cURL
      $curl = curl_init($url);

      // Set the cURL options
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

      // Disable SSL certificate verification
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

      // Execute the cURL request
      $response = curl_exec($curl);

      // Check for cURL errors
      if (curl_errno($curl)) {
        #echo ("cURL error: " . curl_error($curl));
      }

      // Get the HTTP status code of the response
      $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      if ($httpCode != 200) {
        #echo ("API request unsuccessful, HTTP status code: " . $httpCode);
      }

      // Close cURL
      curl_close($curl);

      // Decode the response
      $events = json_decode($response, true);

      // Check if events array is empty and log the response
      if (empty($events)) {
        #echo ("No events found with URL: $url");
      } else {
        // Log the count of the events found
        #echo ("Events found: " . count($events));
      }

      // If events were found, break the loop
      if (!empty($events)) {
        break;
      }
    }

    // If no events were found after all attempts, return false
    if (empty($events)) {
      return false;
    }

    // Insert all the events into the database
    foreach ($events as $event) {
      $sql = "INSERT INTO bg_historic_eventdata (type, event_year, event_month, event_day, source, event_description, create_dt, modify_dt, status) VALUES (:type, :year, :month, :day, 'api.api-ninjas.com', :description, NOW(), now(), 'active')";
      $stmt = $database->prepare($sql);
      $stmt->execute([
        'type' => $type,
        'year' => $event['year'],
        'month' => $event['month'],
        'day' => $event['day'],
        'description' => $event['event'],
      ]);
    }

    // Limit the events to 3
    $events = array_slice($events, 0, 3);
    #breakpoint($events);
    return $events;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getSampleScheduleCompanies($limit)
  {
    global $database;
    $sql = 'SELECT c.*, a.description AS company_logo, l.location_id, l.address, l.city, l.state, l.zip_code, l.country, 
concat( l.address, ",", l.city,",",  l.state, ",", l.zip_code) as map_address 
FROM bg_companies AS c
LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id AND a.category = "company_logos"  and a.`grouping` ="primary_logo"
LEFT JOIN bg_company_locations AS l ON c.company_id = l.company_id
WHERE c.status = "finalized" AND 
l.address IS NOT NULL
and a.description is not null
and l.state="TX"
ORDER BY rand() limit ' . $limit;
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }




  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------

/**
 * Get or set a statistic value from the bg_stats table
 */
public function statvalue($key, $value = null, $dataType = null, $formatOutput = null, $description = null, $formatted = false) {
  /**
   * @param string $key The key to get or update
   * @param mixed $value Optional. If provided, updates the stat value
   * @param string $dataType Optional. The data type of the value ('string', 'integer', 'float', 'boolean')
   * @param string $formatOutput Optional. Format code for displaying the value (e.g. "%d users", "$%d")
   * @param string $description Optional. Description of what this statistic represents
   * @return mixed The current value of the statistic or formatted string if $formatted is true
   */
  global $database;
  
  // Sanitize the key
  $key = trim($key);

  
  if ($value !== null) {
      
  // Get current user ID from session
  $userId = 0;
  if (isset($_SESSION['current_user_data']) && isset($_SESSION['current_user_data']['user_id'])) {
      $userId = $_SESSION['current_user_data']['user_id'];
  }
      // UPDATE mode - we're setting a value
      
      // Determine data type if not explicitly provided
      if ($dataType === null) {
          if (is_int($value)) {
              $dataType = 'integer';
          } elseif (is_float($value)) {
              $dataType = 'float';
          } elseif (is_bool($value)) {
              $dataType = 'boolean';
              $value = $value ? 'true' : 'false';
          } else {
              $dataType = 'string';
          }
      }
      
      // Convert value to string for storage
      $valueStr = (string)$value;
      
      // Check if the key already exists
      $sql = "SELECT stat_id FROM bg_stats WHERE stat_key = :key";
      $stmt = $database->prepare($sql);
      $stmt->execute(['key' => $key]);
      $exists = $stmt->fetchColumn();
      
      if ($exists) {
          // Build update SQL dynamically based on what parameters are provided
          $updateFields = [
              "stat_value = :value", 
              "data_type = :data_type",
              "modifydt = NOW()",
              "modifyby = :modifyby"
          ];
          $params = [
              'key' => $key,
              'value' => $valueStr,
              'data_type' => $dataType,
              'modifyby' => $userId
          ];
          
          // Add format_output if provided
          if ($formatOutput !== null) {
              $updateFields[] = "format_output = :format_output";
              $params['format_output'] = $formatOutput;
          }
          
          // Add description if provided
          if ($description !== null) {
              $updateFields[] = "description = :description";
              $params['description'] = $description;
          }
          
          // Create the update query
          $sql = "UPDATE bg_stats SET " . implode(", ", $updateFields) . " WHERE stat_key = :key";
          
          $stmt = $database->prepare($sql);
          $stmt->execute($params);
      } else {
          // Build insert SQL dynamically based on what parameters are provided
          $insertFields = ["stat_key", "stat_value", "data_type", "createdt", "createby"];
          $insertValues = [":key", ":value", ":data_type", "NOW()", ":createby"];
          $params = [
              'key' => $key,
              'value' => $valueStr,
              'data_type' => $dataType,
              'createby' => $userId
          ];
          
          // Add format_output if provided
          if ($formatOutput !== null) {
              $insertFields[] = "format_output";
              $insertValues[] = ":format_output";
              $params['format_output'] = $formatOutput;
          }
          
          // Add description if provided
          if ($description !== null) {
              $insertFields[] = "description";
              $insertValues[] = ":description";
              $params['description'] = $description;
          }
          
          // Create the insert query
          $sql = "INSERT INTO bg_stats (" . implode(", ", $insertFields) . ") VALUES (" . implode(", ", $insertValues) . ")";
          
          $stmt = $database->prepare($sql);
          $stmt->execute($params);
      }
      
      // If formatted output is requested and format is provided
      if ($formatted && $formatOutput) {
          // Convert value to appropriate type before formatting
          switch($dataType) {
              case 'integer':
                  $typedValue = (int)$valueStr;
                  break;
              case 'float':
                  $typedValue = (float)$valueStr;
                  break;
              case 'boolean':
                  $typedValue = $valueStr === 'true' || $valueStr === '1';
                  break;
              default:
                  $typedValue = $valueStr;
          }
          
          // Apply the format
          return sprintf($formatOutput, $typedValue);
      }
      
      // Return the value in its proper type
      switch($dataType) {
          case 'integer':
              return (int)$valueStr;
          case 'float':
              return (float)$valueStr;
          case 'boolean':
              return $valueStr === 'true' || $valueStr === '1';
          default:
              return $valueStr;
      }
  } else {
      // GET mode - we're retrieving a value
      $sql = "SELECT stat_value, data_type, format_output FROM bg_stats WHERE stat_key = :key";
      $stmt = $database->prepare($sql);
      $stmt->execute(['key' => $key]);
      
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if (!$result) {
          return null; // Key doesn't exist
      }
      
      // Convert to appropriate type
      $typedValue = $result['stat_value'];
      switch($result['data_type']) {
          case 'integer':
              $typedValue = (int)$result['stat_value'];
              break;
          case 'float':
              $typedValue = (float)$result['stat_value'];
              break;
          case 'boolean':
              $typedValue = $result['stat_value'] === 'true' || $result['stat_value'] === '1';
              break;
      }
      
      // If formatted output is requested and format is available
      if ($formatted && !empty($result['format_output'])) {
          return sprintf($result['format_output'], $typedValue);
      }
      
      return $typedValue;
  }
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function calculateNextOccurrence($birthday, $days = 60)
  {
    $output = array();
    $birthdayDT = new DateTime($birthday);
    #   breakpoint($birthdayDT);
    if (!$birthdayDT) {
      $output['result'] = false;
      $output['date'] = $birthday;
      #"Invalid date: $birthday");
      return $output;
    }
    // Set to this year's birthday
    $birthdayThisYear = $birthdayDT->setDate(date('Y'), $birthdayDT->format('m'), $birthdayDT->format('d'));

    // If birthday has passed, set to next year
    if ($birthdayThisYear < new DateTime()) {
      $birthdayThisYear->modify('+1 year');
    }

    // Subtract 60 days 
    $checkoutDT = (clone $birthdayThisYear)->modify('-' . $days . ' days');

    $output['long_date'] = $checkoutDT->format('l, F j, Y');
    $output['date'] = $checkoutDT->format('Y-m-d');
    $output['result'] = true;

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function googlemap($local_companies)
  {
    $result = array();
    $googleAPIkey = 'AIzaSyAsEkpDB6xZl7pcmVoUvXxIXdhnV5Ee1S8';
    foreach ($local_companies as $item_company) {
      $result[]['address'] = $item_company['map_address'];
    }
    if (!empty($result)) {
      $origin = urlencode($result[0]['address']);
      // Build API URL
      $origin = urlencode($result[0]['address']);
      $destination = urlencode(end($result)['address']);

      $waypoints = '';
      for ($i = 1; $i < count($result) - 1; $i++) {
        $waypoints .= urlencode($result[$i]['address']) . '|';
      }
      $waypoints = rtrim($waypoints, '|');

      $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$origin}&destination={$destination}&waypoints={$waypoints}&key={$googleAPIkey}";

      // Call API
      $data = file_get_contents($url);
      $result = json_decode($data, true);

      // Parse data to get route coordinates 
      $polyline = $result['routes'][0]['overview_polyline']['points'];


      return $polyline;
    } else {
      echo '<h1>no companies</h1>';
      return false;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function googlemap_decodePolyline($encoded)
  {
    $len = strlen($encoded);
    $index = 0;
    $coords = array();
    $lat = 0;
    $lng = 0;

    while ($index < $len) {
      $result = 1;
      $shift = 0;
      do {
        $b = ord($encoded[$index++]) - 63 - 1;
        $result += $b << $shift;
        $shift += 5;
      } while ($b >= 0x1f);
      $lat += (($result & 1) ? ~($result >> 1) : ($result >> 1));

      $result = 1;
      $shift = 0;
      do {
        $b = ord($encoded[$index++]) - 63 - 1;
        $result += $b << $shift;
        $shift += 5;
      } while ($b >= 0x1f);
      $lng += (($result & 1) ? ~($result >> 1) : ($result >> 1));

      $coords[] = array('lat' => $lat * 1e-5, 'lng' => $lng * 1e-5);
    }

    return $coords;
  }


  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
function bg_businesshours() {
  global $database;
   $timezone = 'America/Denver';

/**
 * Business Hours Function
 * 
 * Determines if the current time is within business hours and checks for holidays.
 * Returns an array of settings related to business hours status.
 * 
 * @param object $database Database connection object
 * @param string $timezone Optional timezone (default: 'America/Denver')
 * @return array Array of business hours settings and status
 */
    // Set the timezone
    date_default_timezone_set($timezone);
    
    // Define working days and hours
    $workingDays = [1, 2, 3, 4, 5]; // Monday to Friday
    $startHour = 9;
    $endHour = 17;
    
    // Get current date and time information
    $currentDate = date('Y-m-d');
    $dayOfWeek = date('N'); // 1 (for Monday) through 7 (for Sunday)
    $hourOfDay = date('G'); // 0 through 23
    
    // Day abbreviations mapping
    $days = ['1' => 'M', '2' => 'T', '3' => 'W', '4' => 'T', '5' => 'F', '6' => 'S', '7' => 'S'];
    
    // Create the working days string
    $workingDaysString = '';
    if ($workingDays) {
        $firstDay = isset($days[$workingDays[0]]) ? $days[$workingDays[0]] : '';
        $lastDay = isset($days[end($workingDays)]) ? $days[end($workingDays)] : '';
        $workingDaysString = $firstDay . '-' . $lastDay;
    }
    
    // Get timezone abbreviation
    $dateTime = new DateTime();
    $dateTime->setTimeZone(new DateTimeZone($timezone));
    $timezoneAbbr = $dateTime->format('T');
    
    // Create the working hours string
    $workingHoursString = $workingDaysString . ' ' . $startHour . 'AM - ' . 
                         ($endHour > 12 ? ($endHour - 12) . 'PM' : $endHour . 'AM') . ' ' . 
                         $timezoneAbbr;
    
    // Check if current time is within business hours
    $isBusinessHours = in_array($dayOfWeek, $workingDays) && ($hourOfDay >= $startHour && $hourOfDay < $endHour);
    
    // Query to get holidays
    $holidayQuery = "
        SELECT 
            `name` AS Holiday,
            `category`,
            `content` AS HolidayDate
        FROM 
            `bg_content`
        WHERE 
            `label` = 'officeclosed' AND
            `type` = 'calendar' AND
            `grouping` IN (YEAR(CURRENT_DATE()), YEAR(CURRENT_DATE()) + 1) AND
            `status` = 'active'
    ";
    
    $holidays = $database->query($holidayQuery);
    
    // Check if today is a holiday
    $isHoliday = false;
    $holidayName = '';
    
    if ($holidays) {
        while ($row = $holidays->fetch()) {
            if ($currentDate == date('Y-m-d', strtotime($row['HolidayDate']))) {
                $isHoliday = true;
                $holidayName = $row['Holiday'];
                break;
            }
        }
    }
    
    // Determine CSS classes and status message based on business hours status
    if ($isBusinessHours && !$isHoliday) {
        $disabledClass = '';
        $afterhourtag = '';
        $status = 'open';
    } else {
        $disabledClass = 'text-muted disabled-content';
        $afterhourtag = '<i class="bi bi-clock-history me-3 text-danger"> Unavailable After Hours</i>';
        $status = $isHoliday ? 'holiday' : 'closed';
    }
    
    // Build alert message for holidays
    $alertMessage = '';
    if ($isHoliday) {
        $alertMessage = '
            <div class="alert alert-warning fw-bold" role="alert">
                <i class="bi bi-calendar2-check me-2"></i>
                Our offices are closed while observing: ' . htmlspecialchars($holidayName) . '
            </div>
        ';
    }
    
    // Return all settings as an array
    return [
        'status' => $status,
        'isBusinessHours' => $isBusinessHours,
        'isHoliday' => $isHoliday,
        'holidayName' => $holidayName,
        'workingDays' => $workingDays,
        'workingHours' => [
            'start' => $startHour,
            'end' => $endHour
        ],
        'currentTime' => [
            'date' => $currentDate,
            'dayOfWeek' => $dayOfWeek,
            'hour' => $hourOfDay
        ],
        'display' => [
            'workingHoursString' => $workingHoursString,
            'disabledClass' => $disabledClass,
            'afterhourtag' => $afterhourtag,
            'alertMessage' => $alertMessage,
            'serviceHeading' => '<h5 class="fs-6 mb-2 mt-5">birthday.gold Customer Service</h5>'
        ],
        'timezone' => $timezone
    ];
}


  //*************************************************************************************************************************
  //*************************************************************************************************************************
  //*************************************************************************************************************************
  function testipcheck($ip = '')
  {
    global $database, $session;
    if ($ip == '')   $ip = $session->get('cip');

    if ($ip == '')   return false;

    list($ip1, $ip2, $ip3, $ip4) = explode('.', $ip);
    $sql = 'select 1 as response from ds_testip where (ip="' . $ip . '" and status_code="A") or (ip="' . $ip1 . '.' . $ip2 . '" or ip="' . $ip1 . '.' . $ip2 . '.' . $ip3 . '") and status_code="T" limit 1';
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $testip = $results[0]['response'];
    if ($testip == '1') return true;
    else return false;
  }



  // Helper function to decode polyline
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function googlemap_decodePolyline2($polyline)
  {
    $lat = $lng = 0;
    $coords = [];

    $data = base64_decode($polyline);

    for ($i = 0; $i < strlen($data); $i++) {
      $shift = $i % 2 == 0 ? 1 : -1;
      $lat = $lat + ((ord($data[$i]) - 63 - $shift) >> 6) * 1e-6;
      $lng = $lng + ((ord($data[$i]) - 63 - $shift) & 0x3f) * 1e-6;

      $coords[] = ['lat' => $lat, 'lng' => $lng];
    }

    return $coords;
  }
}
