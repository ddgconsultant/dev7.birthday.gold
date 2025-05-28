<?php

class Account
{

  private $db; // Database connection 
  private $session; // Session handler



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function __construct($database, $session)
  {
    $this->db = $database;
    $this->session = $session;
  }

  

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function login($input, $password, $logintype = 'username', $allowrememberme = false)
  {
    // Validate username and password
    // Query database to get user record
    global $qik, $sitesettings;
    $rawinput = $input;
    $input = trim(strtolower($input));


    switch ($logintype) {
        //--------------------------------------
      case 'both':
      case 'both_api':
        $sql = 'SELECT * FROM bg_users WHERE (trim(lower(username)) = :input or trim(lower(email)) = :input2) and `status`="active" limit 1';
        $params = ['input' => $input, 'input2' => $input];
        break;
        //--------------------------------------
      case 'any':
        $sql = 'SELECT * FROM bg_users WHERE (trim(lower(username)) = :input or trim(lower(email)) = :input2 or trim(lower(feature_email)) = :input3)  and `status`="active" limit 1';
        $params = ['input' => $input, 'input2' => $input, 'input3' => $input];
        break;
        //--------------------------------------
      case 'giftcode':
        $sql = 'SELECT * FROM bg_users WHERE feature_giftcode = :input and `status`="giftlock" limit 1';
        $params = ['input' => $input];
        break;
        //--------------------------------------
      case (strpos($logintype, 'rememberme') !== false && $allowrememberme):  

        $rememberme = true;
        $input =    $rawinput;
        global $app;
        $decoded_userid = $qik->decodeId($input);
        $deviceid = $app->deviceid();
        $checkdata = [
          'rawdata' => $input,
          'user_id' => $decoded_userid,
          'device_id' => $deviceid,
          'type' => 'bgrememberme_autologin',
          'long' => $password,
          'invalidate_previouscodes' => null, // Assuming this is not relevant here, set accordingly if needed
          'status' => 'cookie',
          'updatestatus' => 'cookie',
        ];
        session_tracking('bg_rememberme_attempt', $checkdata);

        $response = $app->checkvalidationcodes($checkdata);
        if (!empty($response['validated']) && !empty($input)) {
          // things are true and active -- log the person in.
          $input = $decoded_userid;
          $sql = 'SELECT * FROM bg_users WHERE user_id = :input and `status`="active" limit 1';
          $params = ['input' => $input];
          session_tracking('bg_rememberme_loginsuccess', $response);
        } else {
          // failed -- invalidate the cookies
          setcookie('bgralid', '', time() - 3600, "/"); // Invalidate the 'bgralid' cookie
          setcookie('bgraltoken', '', time() - 3600, "/"); // Invalidate the 'bgraltoken' cookie
          return false;
        }
        break;
      //--------------------------------------
      case 'adminswitch':
        $sql = 'SELECT * FROM bg_users WHERE user_id = :input and `status` in ("validated", "active") limit 1';
        $params = ['input' => $input];
        break;
        //--------------------------------------
      default:
        $sql = 'SELECT * FROM bg_users WHERE ' . $logintype . ' = :input and `status`="active" limit 1';
        $params = ['input' => $input];
        break;
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    // ---------------------------------
    // Record check
    if (empty($user)) {  ## no records
      session_tracking('LOGIN-failed');
      return false;
    }


    // WHITELIST BOT CHECK
    // if "bot" or "spider" found in HTTP_USER_AGENT
    // check for bg_user_attributes for type="login_bot_whitelist" where description=HTTP_USER_AGENT
    // if no record - fail login
    // bots are rejected by default
    global $system;

    $botData = $system->isBot();

    if (!empty($botData)) {
      # echo "Bot detected: " . $botData['name'] . "\n";
      #  echo "Bot ID: " . $botData['id'] . "\n";
      // Additional actions or logging can be performed here
      if ($this->db->count('bg_user_attributes',  "`type`='login_bot_whitelist' and description =:botid and user_id=:user_id and `status`='A'", [':botid' => $botData['id'], ':userid' => $user['user_id']]) == 0) {
        session_tracking('LOGIN-BOTfailed');
        return false;
      }
    } else {
      # echo "No bot detected.\n";
    }


    // ---------------------------------
    // Handle Impersonation
    if ($qik->impersonatepassword($password)) {  ## someone is impersonating
      $impersonator_user_data = $this->session->get('current_user_data');
      $this->session->set('is_impersonator', true);
      // Login successful, store user ID in session
      $this->session->unset('current_user_data');
      $this->session->set('current_user_id', $user['user_id']);
      $this->session->set('current_user_data', $user);
      session_tracking('LOGIN-is_impersonator',  $user);
      return true;
    }

    // ---------------------------------
    // Handle  Gift Code
    if (!empty($user['feature_giftcode'])) {
      if ($logintype !== 'giftcode') {  # deal with regular login 

        # Handle unredeemed gift code
        if (empty($user['redeem_dt'])) {

          # handle API / APP
          if ($logintype == 'both_api') {   ## DENY - USER is not allow to log in via API/App
            http_response_code(404);
            session_tracking('LOGIN-API_failed',  $user);
            exit;
          }

          # handle website
          global $system;
          session_tracking('LOGIN-redeem_failed',  $user);
          $transferpagedata['message'] = '<div class="alert alert-danger">This account is associated with an unredeemed Gift Certificate.</div>';
          $transferpagedata['url'] = '/redeem';
          $system->endpostpage($transferpagedata);
          exit;
        }
      }

      # Handle Gift Code / redeem
      if ($logintype === 'giftcode') {  # things are the way they are supposed to be 
        ## we need to expand this so that they can't keep redeeming the same and resetting the account
        $updatefields = ['redeem_dt' => 'now()', 'status' => 'activegift'];
        $this->updateSettings($user['user_id'], $updatefields);
        // Login successful, store user ID in session
        $this->session->set('current_user_id', $user['user_id']);
        $this->session->set('current_user_data', $user);
        session_tracking('LOGIN-redeem_cert',  $user);
        return $user['user_id'];
      }
    }


    ## WE STILL HAVE REGULAR USER LOGIN   // Final Result
    if (password_verify($password, $user['password'])  || ($password == $sitesettings['app']['APP_AUTOLOGIN']) || !empty($rememberme)) {

      // handle admin
      if ($this->isadmin()) {
        $sql = 'update bg_sessions set expire_dt=now() where user_id = ' . $user['user_id'] . ' and expire_dt is null';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $sql = 'insert bg_sessions (user_id, session_id, type, create_dt) values (' . $user['user_id'] . ', "' . session_id() . '", "admin", now())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        session_tracking('LOGIN-success_admin',  $user);
      } else {
        session_tracking('LOGIN-success_user',  $user);
      }

      // Login successful, store user ID in session
      $this->session->set('current_user_id', $user['user_id']);
      $this->session->set('current_user_data', $user);

      // track the login
      global $client_ip;
      #$this->logintracking($user['user_id'], session_id() . ' || from: ' . $client_ip . ' || using: ' . $_SERVER['HTTP_USER_AGENT']);
      $logintrackingdata['session_id'] = session_id();
      $logintrackingdata['client_ip'] = $client_ip;
      $logintrackingdata['agent'] = $_SERVER['HTTP_USER_AGENT'];
      $logintrackingdata['location'] = $this->session->get('client_locationdata');
      $logintrackingdata['browser'] = $qik->getbrowser('quick', $_SERVER['HTTP_USER_AGENT']);
      $logintrackingdata['device_id'] = $deviceid;


      $this->logintracking($user['user_id'], $logintrackingdata);

      return true;
    }
    session_tracking('LOGIN-failed', $input . '|' . $password . '|' . $logintype . '||' . $user['status']);

    ## PROVIDE FAILURE REASONS -- set it in the session - so that a more specific message can be displayed to the user:
    if (strpos($user['status'], 'pending') === true) $this->session->set('login_failure_message', '<div class="alert alert-danger">You still need to validate your account.  Please look for an email from birthday.gold.</div>');

    if (strpos($user['status'], 'validated') === true) {
      $this->session->set('login_failure_message', '<div class="alert alert-danger">Account Validated -- forwarding to checkout.</div>');
      header('Location: /checkout?u=' . $qik->encodeId($user['user_id']));
      exit;
    }

    // Invalid login
    return false;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function logintracking($user_id, $description = '')
  {
    global $site;
    // Begin transaction to ensure data integrity
    $this->db->beginTransaction();

    try {
      // Step 1: Update the current state to last and last to history
      // Update the 'last' entry to 'history'
      $sqlUpdateLastToHistory = "UPDATE bg_logintracking SET type = 'history', modify_dt=now() WHERE user_id = :user_id AND type = 'last'";
      $stmt = $this->db->prepare($sqlUpdateLastToHistory);
      $stmt->execute([':user_id' => $user_id]);

      // Update the 'current' entry to 'last'
      $sqlUpdateCurrentToLast = "UPDATE bg_logintracking SET type = 'last', modify_dt=now() WHERE user_id = :user_id AND type = 'current'";
      $stmt = $this->db->prepare($sqlUpdateCurrentToLast);
      $stmt->execute([':user_id' => $user_id]);

      // Step 2: Insert the new 'current' entry
      $sqlInsert = "INSERT INTO bg_logintracking (user_id, site, type, description, create_dt, modify_dt) VALUES (:user_id, :site, 'current', :description, NOW(), now())";
      $stmt = $this->db->prepare($sqlInsert);
      $formattedDescription = is_array($description) ? json_encode($description, JSON_PRETTY_PRINT) : $description;
      $stmt->execute([
        ':user_id' => $user_id,
        ':site' => $site,
        ':description' => $formattedDescription
      ]);

      // Commit the transaction
      $this->db->commit();
    } catch (Exception $e) {
      // Rollback the transaction in case of error
      $this->db->rollback();
      // Handle the error, maybe log it or show a message to the user
      error_log($e->getMessage());
      return false;
    }

    return true;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getLastLogin($user_id = '')
  {
    if ($user_id == '') return false;
    try {
      // Prepare the SQL query to select the 'last' login record for the given user_id
      $sql = "SELECT * FROM bg_logintracking WHERE user_id = :user_id AND  `type` IN ('last', 'current')   ORDER BY FIELD(`type`, 'last', 'current'), modify_dt DESC  LIMIT 1";
      $stmt = $this->db->prepare($sql);
      $stmt->execute([':user_id' => $user_id]);

      // Fetch the result
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      // Check if a result was found
      if ($result) {
        return $result;
      } else {
        // No last login found
        return false;
      }
    } catch (Exception $e) {
      // Log the error or handle it as needed
      error_log($e->getMessage());
      return false;
    }
  }


  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getuserdata($input, $type = 'username',  $settings = []) 
  {
      global $bg_systemdata_states;
      

      $columns = $settings['columns'] ?? '*';  //default all columns
      $status = $settings['status'] ?? 'active';// default any status
      $savedatatosession = $settings['savetosession'] ?? false;// default any status

      // Build status clause
      if ($status == '*') {
          $statustag = '';
      } else {
          if (is_array($status)) {
              $statusArray = array_map(function($value) {
                  return '"' . $value . '"';
              }, $status);
              $statusString = implode(',', $statusArray);
              $statustag = 'and `status` in (' . $statusString . ')';
          } else {
              $statustag = 'and `status`="' . $status . '"';
          }
      }
  
      // Get base user data
      if (!empty($input)) {
          $sql = 'SELECT ' . $columns . ', trim(concat(ifnull(first_name,""), " ", ifnull(last_name,""))) as full_name, 
                  YEAR(create_dt) as create_dt_year 
                  FROM bg_users 
                  WHERE lower(' . $type . ') = :input ' . $statustag . ' limit 1';
          $stmt = $this->db->prepare($sql);
          $stmt->execute(['input' => strtolower($input ?? '')]);
      } else {
          $sql = 'SELECT ' . $columns . ', trim(concat(ifnull(first_name,""), " ", ifnull(last_name,""))) as full_name, 
                  YEAR(create_dt) as create_dt_year 
                  FROM bg_users 
                  WHERE 1=1 ' . $statustag . ' limit 1';
          $stmt = $this->db->prepare($sql);
          $stmt->execute();
      }
  
      if ($result = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
          $user = $result[0];
   /*       
          // Get profile attributes
          $sql = "SELECT name, string_value 
                  FROM bg_user_attributes 
                  WHERE user_id = :user_id 
                  AND type = 'profile' 
                  AND status = 'active'";
          
          $stmt = $this->db->prepare($sql);
          $stmt->execute(['user_id' => $user['user_id']]);
          
          // Merge attributes into user array
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $user[$row['name']] = $row['string_value'];
          }
  */

          // Fetch all attributes, including 'avatar', for the user
$sql = "
SELECT 
    name, string_value, description, value
FROM 
    bg_user_attributes 
WHERE 
    user_id = :user_id 
     AND (type = 'profile' or (  name= 'avatar'    AND category = 'primary'    AND type = 'profile_image' ))
    AND status = 'active'";

$stmt = $this->db->prepare($sql);
$stmt->execute(['user_id' => $user['user_id']]);

// Initialize avatar as default if not found
global $website;

// Merge attributes into user array
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
if ($row['name'] === 'avatar') {
    // Add avatar to its own element
    $user['avatar'] = $row['description'] ?: $website['defaultavatar'];
} else {
    // Merge other attributes
    $user[$row['name']] = $row['string_value'];
}
}

#$tmpresults=$this->getuseravatar($user);
#breakpoint($tmpresults);
#array_merge($tmpresults, $user);

if ($savedatatosession) {
          // Handle session data        
          $currentuser = $this->session->get('current_user_data', '');
          if (!empty($currentuser['user_id']) && $user['user_id'] == $currentuser['user_id']) {
              $this->session->set('current_user_id', $user['user_id']);
  
              // Add non-bg_user data elements
              $user['statecode'] = $bg_systemdata_states[$user['state'] ?? ''] ?? '';
  
              $this->session->unset('current_user_data');
              $this->session->set('current_user_data', $user);
          }
                  }

          return $user;
      }
      
      return false;
  }





  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function profilecompletionratio($userProfile = null)
  {
    if (empty($userProfile)) {
      $userProfile = $this->session->get('current_user_data');
    }
    $profileColumns = array(
      'profile_username',
      'profile_email',
      'profile_password',
      'profile_title',
      'profile_first_name',
      'profile_middle_name',
      'profile_last_name',
      'profile_mailing_address',
      'profile_city',
      'profile_state',
      'profile_zip_code',
      'profile_country',
      'profile_phone_number',
      'profile_phone_type',
      'profile_gender',
      'profile_agree_terms',
      'profile_agree_email',
      'profile_agree_text',
      'profile_allergy_gluten',
      'profile_allergy_sugar',
      'profile_allergy_nuts',
      'profile_allergy_dairy',
      'profile_diet_vegan',
      'profile_diet_kosher',
      'profile_diet_pescatarian',
      'profile_diet_keto',
      'profile_diet_paleo',
      'profile_diet_vegetarian',
      'profile_military',
      'profile_educator',
      'profile_firstresponder'
    );

    $optionalColumns = array(
      'profile_title',
      'profile_middle_name',
      'profile_agree_email',
      'profile_agree_text',
      'profile_allergy_gluten',
      'profile_gender',
      'profile_allergy_sugar',
      'profile_allergy_nuts',
      'profile_allergy_dairy',
      'profile_diet_vegan',
      'profile_diet_kosher',
      'profile_diet_pescatarian',
      'profile_diet_keto',
      'profile_diet_paleo',
      'profile_diet_vegetarian',
      'profile_military',
      'profile_educator',
      'profile_firstresponder',
    );

    $requiredColumns = array_diff($profileColumns, $optionalColumns);

    $requiredTotal = count($requiredColumns);
    $optionalTotal = count($optionalColumns);

    $requiredFilledIn = 0;
    $optionalFilledIn = 0;
    $requiredNotCompleted = [];
    $optionalNotCompleted = [];
    $requiredNotCompleted_strings = [];
    $optionalNotCompleted_strings = [];

    $search = array('profile_',  '_');
    $replace = array('', ' ');

    foreach ($userProfile as $column => $value) {
      if (in_array($column, $requiredColumns)) {
        if (!empty($value)) {
          $requiredFilledIn++;
        } else {
          $requiredNotCompleted[] = $column;
          $requiredNotCompleted_strings[] = ucwords(str_replace($search, $replace, $column));
        }
      } elseif (in_array($column, $optionalColumns)) {
        if (!empty($value)) {
          $optionalFilledIn++;
        } else {
          $optionalNotCompleted[] = $column;
          $optionalNotCompleted_strings[] = ucwords(str_replace($search, $replace, $column));
        }
      }
    }

    $requiredPercentage = $requiredFilledIn / $requiredTotal;
    $optionalPercentage = $optionalFilledIn / $optionalTotal;

    $requiredPercentage = round(($requiredPercentage * 100), 0);
    $optionalPercentage = round(($optionalPercentage * 100), 0);

    $requiredPercentage = min(100, max(0, $requiredPercentage));
    $optionalPercentage = min(100, max(0, $optionalPercentage));


    $output = array(
      'required_total' => $requiredTotal,
      'required_filledin' => $requiredFilledIn,
      'required_percentage' => $requiredPercentage,
      'required_fields_notcompleted' => $requiredNotCompleted,
      'required_fields_notcompleted_strings' => $requiredNotCompleted_strings,
      'optional_total' => $optionalTotal,
      'optional_filledin' => $optionalFilledIn,
      'optional_percentage' => $optionalPercentage,
      'optional_fields_notcompleted' => $optionalNotCompleted,
      'optional_fields_notcompleted_strings' => $optionalNotCompleted_strings
    );

    $tagpercentage = ['required_percentage', 'optional_percentage'];
    foreach ($tagpercentage as $pcrname) { # => $pcrvalue) {
      $pcrvalue = $output[$pcrname];
      # $pcrtag = $pcrvalue;
      $class = '';

      $pcrtag = $pcrvalue . '%';
      if ($pcrvalue < 30) {
        $class = 'danger'; // Red color for less than 30% completion
      } elseif ($pcrvalue >= 30 && $pcrvalue < 70) {
        $class = 'warning'; // Yellow color for 30% - 70% completion
      } else {
        $class = 'success'; // Green color for more than 70% completion
      }
      $output[$pcrname . '_color'] = $class;
      $output[$pcrname . '_tag'] = '<span class="fw-bold text-' . $class . '">' . $pcrtag . ' Completed</span>';
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getEnrollments($userid = '', $listtype = 'active')
  {
    $finaloutput = [];
    $output = '';
    global $qik;
    $statusCounters = [
      'failed' => 0,
      'pending' => 0,
      'selected' => 0,
      'toenroll' => 0,
      'active' => 0,
      'success' => 0,
      'existing' => 0,
      'default' => 0,
      'removed' => 0,
      'total' => 0
    ];

    if (!empty($userid)) {
      global $current_user_data;
      if (!empty($current_user_data['user_id'])) $userid = $current_user_data['user_id'];
    }

    switch ($listtype) {

      case 'active':
        $statuscriteria = "and status='success'";
      case 'all':
        $statuscriteria = "";
        $sql = "SELECT uc.*, c.company_name , c.appgoogle, c.appapple FROM bg_user_companies uc, bg_companies c 
WHERE uc.company_id=c.company_id and user_id = " . $userid . " " . $statuscriteria . " order by uc.modify_dt desc";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $apptype = $current_user_data['profile_phone_type'];
          #$apptype='iphone';
          #
          $showcompany = true;
          $removetag = '<p><a class="text-danger remove-link" href="#" data-id="' . $row['user_company_id'] . '">Remove</a></p>';

          global $display;
          $applink = $display->applink($apptype, $row);
          $appicon = $applink['applink'];
          $qrcode = $applink['qrlink'];

          $statusCounters['total']++;
          switch ($row['status']) {
            case 'failed':
              $status_sign = '<i class="bi bi-x-octagon-fill text-danger"></i>';
              $statusmessagetag = '';
              if (!empty($row['reason']))  $statusmessagetag = '<br>' . $row['reason'];
              $statusmessage = '<p class="text-danger p-0 m-0">We were unable to enroll you.' . $statusmessagetag . '</p>';
              $statusCounters['failed']++;
              break;
            case 'pending':
              $status_sign = '<i class="bi bi-clock-history text-dark"></i>';
              $statusmessage = '<p class="text-dark p-0 m-0">We are in the process of enrolling you.</p>';
              $statusCounters['pending']++;
              $statusCounters['toenroll']++;
              break;
            case 'selected':
              $status_sign = '<i class="bi bi-clock-history text-dark"></i>';
              $statusmessage = '<p class="text-dark p-0 m-0">You selected this business.  The system has not picked it up yet to enroll you yet.</p>';
              $statusCounters['selected']++;
              $statusCounters['toenroll']++;
              break;
            case 'success':
              $status_sign = '<i class="bi bi-patch-check-fill text-success"></i>';
              $statusmessage = '<p class="text-success p-0 m-0">You were successfully enrolled.</p>';
              $statusCounters['success']++;
              $statusCounters['active']++;
              $removetag = '';
              break;
            case 'existing':
              $status_sign = '<i class="bi bi-check-circle-fill"></i>';
              $statusmessage = '<p class="text-success p-0 m-0">You had an account before birthday.gold.</p>';
              $statusCounters['existing']++;
              $statusCounters['active']++;
              $removetag = '';
              break;

            case 'removed':
              $status_sign = '';
              $statusmessage = '';
              $statusCounters['removed']++;
              $removetag = '';
              $showcompany = false;
              break;

            default:
              $status_sign = '<i class="bi bi-question-diamond-fill text-warning"></i>';
              $statusmessage = '<p class="text-warning p-0 m-0"></p>';
              $statusCounters['default']++;
              break;
          }

          // Now you can use $statusCounters to get the count for each status.
          if ($showcompany) {
            $timetag = $qik->timeago($row['modify_dt']);
            $output .= '
<tr>
<td scope="row"  class="align-middle">' . str_replace('class="', 'class="h1 ', $status_sign) . '' . $removetag . '</td>
';
            #  <td>   <img src="'. $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']).'" class="card-img-top img-responsive" alt="" /></td>
            $output .= '<td class="text-left align-middle">
<h3 class="mb-0 pb-0 pe-6">' . $row['company_name'] . '</h3>
' . $statusmessage . '  
<p class="p-0 m-0">' . $row['reason'] . '</p>
<p class="p-0 m-0">' . $timetag['message'] . '</p>
</td>
<td class="align-middle">' . $appicon . '</td>
</tr>
';
          }
        }
        $finaloutput['html'] = $output;
        $finaloutput['counters'] = $statusCounters;
        $finaloutput['count'] = $statusCounters['total'];
        break;
    }

    return $finaloutput;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getBirthdates($birthdate = '', $plandetails = [])
  {
    global $mode;
    if (empty($birthdate)) {
      global $current_user_data;
      if (!empty($current_user_data['birthdate'])) $birthdate = $current_user_data['birthdate'];
    }
    $output = [];

    $plandetails['celebration_tour_days_after'] = $plandetails['celebration_tour_days_after'] ?? 30;
    $plandetails['celebration_tour_days_before'] = $plandetails['celebration_tour_days_before'] ?? 30;

    if ($mode == 'dev') {
      $plandetails['celebration_tour_days_before'] = 300;
      $plandetails['celebration_tour_days_after'] = 300;
    }

    $recencyafterdays = new DateInterval('P' . $plandetails['celebration_tour_days_after'] . 'D');
    $recencybeforedays = new DateInterval('P' . $plandetails['celebration_tour_days_before'] . 'D');

    // Assigning the birthdate to the 'born' key
    $output['born'] = $birthdate;

    // Getting the current date
    $currentDate = new DateTime();
    $output['today'] = $currentDate;
    $output['today_formatted'] = $currentDate->format('Y-m-d');
    // Getting the current year
    $currentYear = $currentDate->format('Y');

    // Get the month and day from the birthdate
    $birthDateObj = new DateTime($birthdate);
    $birthMonthDay = $birthDateObj->format('m-d');


    // Extract the year from the birthdate.
    $birthYear = date('Y', strtotime($birthdate));

    // Calculate the decade by rounding down the birth year to the nearest decade.
    $decade = floor($birthYear / 10) * 10;
    $output['decade'] = $decade;
    $output['decade_1_1'] = $decade . '-01-01';

    // Assigning the birthdate but for the current year to the 'thisyear' key
    $output['thisyear'] = "$currentYear-$birthMonthDay";

    // Finding if the birthday has passed this year and assigning the appropriate date to the 'next' key
    if ($output['thisyear'] < $currentDate->format('Y-m-d')) {
      $nextBirthday = new DateTime(($currentYear + 1) . "-$birthMonthDay");
      $output['next'] = $nextBirthday->format('Y-m-d');
    } else {
      $output['next'] = $output['thisyear'];
    }

    // Finding if the birthday will occur within the next 30 days and assigning the result to the 'recent' key
    $dateIn30Days = clone $currentDate;
    $dateIn30Days->add($recencyafterdays);
    $nextBirthday = new DateTime($output['next']);

    if ($nextBirthday >= $currentDate && $nextBirthday <= $dateIn30Days) {
      $output['recent'] = $output['next'];
    } else {
      $output['recent'] = $output['thisyear'];
    }

    $startDate = clone $currentDate;
    $startDate->sub($recencybeforedays);

    $endDate = clone $currentDate;
    $endDate->add($recencyafterdays);
    $output['planstart_shortformatted'] = $startDate->format('m/d');
    $output['planend_shortformatted'] = $endDate->format('m/d');
    $output['planstart_formatted'] = $startDate->format('Y-m-d');
    $output['planend_formatted'] = $endDate->format('Y-m-d');

    $output_recentDate = new DateTime($output['recent']);
    $output['recent_longformatted'] = $output_recentDate->format('l, F d, Y');

    $birthday_in_plan = $output_recentDate >= $startDate && $output_recentDate <= $endDate;
    $output['birthday_in_plan'] = $birthday_in_plan;

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getenrollmentlistcounts($userid = '', $status = '')
  {

    if (empty($userid)) {
      $currentuser = $this->session->get('current_user_data', '');
      $userid = $currentuser['user_id'];
    }
    $statuscriteria = '';
    if (!empty($status)) {
      $statuscriteria = ' and `status`="' . $status . '"';
    }
    $sql = "SELECT uc.status, count(*) as count 
FROM bg_user_companies uc, bg_companies c 
WHERE uc.company_id=c.company_id and user_id = " . $userid . ' ' .  $statuscriteria . ' group by uc.status';

    $statusCounters = [
      'failed' => 0,
      'pending' => 0,
      'selected' => 0,
      'toenroll' => 0,
      'active' => 0,
      'success' => 0,
      'existing' => 0,
      'default' => 0,
      'removed' => 0,
      'count' => 0,
      'total' => 0
    ];

    // Prepare the statement
    $stmt =  $this->db->prepare($sql);
    $stmt->execute();
    $data = array();
    $output = array();
    $data['data'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure $data['data'] is an array
if (!is_array($data['data'])) {
  $data['data'] = [];
}

$output = array_merge($statusCounters, $data['data']);
$output['count'] = $stmt->rowCount();

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getaccountmessages($userid = '', $status = '')
  {
    global $display;

    if (empty($userid)) {
      $currentuser = $this->session->get('current_user_data', '');
      $userid = $currentuser['user_id'] ?? null;
    }

    $query = " SELECT * FROM bg_user_attributes 
      WHERE user_id = :userid 
        AND name = 'account_message' 
        AND `status` = 'unread' 
        AND NOW() BETWEEN start_dt AND end_dt
        order by `rank`
      LIMIT 1  ";
    $params = [
      ':userid' => $userid
    ];

    $stmt = $this->db->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    // Check if $result is false before trying to access its keys
    if ($result !== false) {
      $output = $display->formaterrormessage('<div class="alert alert-' . $result['grouping'] . '">' . $result['description'] . '</div>', 'attribute:' . $result['attribute_id']);
      return $output;
    }

    // Handle the case where no result is found
    return false;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getlogincount($userid = '', $status = '')
  {
    if (empty($userid)) {
      $currentuser = $this->session->get('current_user_data', '');
      $userid = $currentuser['user_id'] ?? null;
    }

    $query = "SELECT count(1) cnt FROM bg_logintracking  WHERE user_id = :userid  ";
    $params = [
      ':userid' => $userid
    ];

    $stmt = $this->db->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $output = 0;
    if ($result !== false) {
      $output =  $result['cnt'];
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function getEnabledFlags($userId = '')
  {
    // Check if userId is passed; if not, try to get it from the session
    $runsql = true;
    if (empty($userId)) {
      $currentuser = $this->session->get('current_user_data', []);
      $userId = $currentuser['user_id'] ?? null;
      if (isset($result['profile_military']) && isset($result['profile_educator']) && isset($result['profile_firstresponder'])) {
        $runsql = false;
        $result = $currentuser;
      }
    }
    // Initialize an array to hold enabled flags
    $enabledFlags = [];
    $flagsString = 'none';

    // Ensure we have a valid user ID
    if (empty($userId)) {
      return $flagsString; // Return 'none' if no user ID is available
    }


    if ($runsql) {
      // Query to get the flag fields for the specified user ID
      $query = "SELECT IFNULL(profile_military, '') AS profile_military, IFNULL(profile_educator, '') AS profile_educator, IFNULL(profile_firstresponder, '') AS profile_firstresponder FROM bg_users WHERE user_id = :userid";

      // Prepare and execute the query
      $stmt = $this->db->prepare($query);
      $params = [':userid' => $userId];
      $stmt->execute($params);

      // Fetch the result
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
    }


    if ($result) {
      // Check each flag and add to the array if it is enabled
      if ($result['profile_military'] === 'true') {
        $enabledFlags[] = 'Military';
      }
      if ($result['profile_educator'] === 'true') {
        $enabledFlags[] = 'Educator';
      }
      if ($result['profile_firstresponder'] === 'true') {
        $enabledFlags[] = 'First Responder';
      }

      // Convert the array to a comma-separated string
      $flagsString = !empty($enabledFlags) ? implode(', ', $enabledFlags) : 'none';
    }

    return $flagsString;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getenrollmentlist($userid = '', $status = '')
  {
    if (empty($userid)) {
      $currentuser = $this->session->get('current_user_data', '');
      $userid = $currentuser['user_id'];
    }
    $statuscriteria = '';
    if (!empty($status)) {
      $statuscriteria = ' and uc.`status` in (' . $status . ')';
    }
    $sql = "SELECT uc.*, c.company_name , c.appgoogle, c.appapple 
FROM bg_user_companies uc, bg_companies c 
WHERE uc.company_id=c.company_id 
AND user_id = " . $userid . ' ' .  $statuscriteria . ' 
ORDER BY uc.modify_dt desc';

    // Prepare the statement
    $stmt =  $this->db->prepare($sql);
    $stmt->execute();
    $data = array();
    $data['count'] = $stmt->rowCount();
    $data['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data['data'] as $key => $row) {
      if (isset($row['registration_detail'])) {
        unset($data['data'][$key]['registration_detail']);
      }
    }
    return $data;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getgoldlist($userid = '', $status = '')
  {
    if (empty($userid)) {
      $currentuser = $this->session->get('current_user_data', '');
      $userid = $currentuser['user_id'];
    }
    $statuscriteria = '';
    if (!empty($status)) {
      $statuscriteria = ' and uc.`status` in (' . $status . ')';
    }
    $sql = "WITH AddressRanked AS (
SELECT 
company_id, 
address, 
city, 
state, 
zip_code,
ROW_NUMBER() OVER (PARTITION BY company_id ORDER BY zip_code ASC, city ASC, state ASC) AS rn
FROM 
bg_company_locations
WHERE 
status = 'active'
)
SELECT 
uc.*, 
c.company_name, 
c.appgoogle, 
c.appapple, 
loc.address, 
loc.city, 
loc.state, 
loc.zip_code,
a.description AS company_logo
FROM 
bg_user_companies uc
JOIN 
bg_companies c ON uc.company_id = c.company_id
LEFT JOIN AddressRanked loc ON uc.company_id = loc.company_id AND loc.rn = 1
LEFT JOIN bg_company_attributes AS a ON uc.company_id = a.company_id AND a.category = 'company_logos'  and a.`grouping` ='primary_logo'

WHERE 
uc.user_id =  " . $userid . "
$statuscriteria
ORDER BY 
uc.modify_dt DESC";

    // Prepare the statement
    $stmt =  $this->db->prepare($sql);
    $stmt->execute();
    $data = array();
    $data['count'] = $stmt->rowCount();
    $data['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data['data'] as $key => $row) {
      if (isset($row['registration_detail'])) {
        unset($data['data'][$key]['registration_detail']);
      }
    }
    $data['sql'] = $sql;
    return $data;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function logout($userid = '')
  {
    // Destroy entire session
    if (empty($userid)) {
      $record = $this->session->get('current_user_data', false);
      $userid = $record['user_id'];
    }
    if (!empty($userid)) {
      $sql = 'update bg_sessions set expire_dt=now() where user_id = ' . $userid . ' and expire_dt is null';
      $stmt = $this->db->prepare($sql);
      $stmt->execute();
    }
    #  unset( $current_user_data['user_id']);
    unset($current_user_data);
    $this->session->destroy();
    return true;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function exists($input, $type = 'username')
  {
    // Check if username exists in database
    $input = strtolower($input);
    $sql = "SELECT COUNT(user_id) AS num FROM bg_users WHERE lower('.$type.') = :input";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['input' => $input]);

    $row = $stmt->fetch();
    return $row['num'] > 0;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function setEnrollmentStatus($uid = '', $status = 'pending', $companies = null)
  {
    // Start of the SQL statement
    $sql = "UPDATE bg_user_companies SET status = :status WHERE user_id = :uid";

    // Build status conditions based on the input status
    if ($status === 'pending') {
      $sql .= " AND status NOT IN ('queued', 'success')";
    } elseif ($status === 'queued') {
      $sql .= " AND status IN ('pending', 'failed')";
    }

    // If $companies is an array, we add a condition to the query to only update rows where the company_id is in the array
    if (is_array($companies)) {
      $sql .= " AND company_id IN (" . implode(',', $companies) . ")";
    }
    // If $companies is a single value, we add a condition to the query to only update the row where the company_id matches the value
    else if ($companies !== null) {
      $sql .= " AND company_id = :company_id";
    }
    $stmt = $this->db->prepare($sql);

    // Create the parameters array
    $params = ['uid' => $uid, 'status' => $status];
    // Add company_id to the parameters array if $companies is a single value
    if ($companies !== null && !is_array($companies)) {
      $params['company_id'] = $companies;
    }

    $stmt->execute($params);
    return $stmt->rowCount() > 0;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getTransactionData($input, $type='user_id')
  {
    // Prepare the SQL query to fetch transaction data based on user_id
    $sql = "SELECT * FROM bg_transactions WHERE `".$type."` = :input and transaction_status='pending' ORDER BY create_dt DESC limit 1";
    session_tracking('getTransactionData = '.$input .','. $type.': ' , $sql);

    // Prepare and execute the query
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':input' => $input]);

    // Fetch all transactions for the user
    $transaction_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    session_tracking('getTransactionData result size: ' , count($transaction_data));

    // Return the fetched data
    return $transaction_data ?: null;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function generateGiftCertificateCode()
  {
    $letters = 'ABCDEFGHKMNPQRSTUVWXYZ';
    $numbers = '23456789';
    $foundunique = false;
    $attempts = 0; // To limit the number of iterations

    while (!$foundunique && $attempts < 100) { // Limit to 100 attempts
      $letterPart = substr(str_shuffle($letters), 0, 4) . '-' .
        substr(str_shuffle($letters . $numbers), 0, 4) . '-' .
        substr(str_shuffle($letters . $numbers), 0, 4);
      $numberPart = substr(str_shuffle($letters . $numbers), 0, 4);
      $final = $letterPart . '-' . $numberPart;

      $sql = 'SELECT count(*) as cnt FROM bg_users WHERE feature_giftcode = :input';
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['input' => $final]);

      $row = $stmt->fetch();
      if ($row['cnt'] == 0) {
        $foundunique = true;
      }

      $attempts++;
    }

    if ($foundunique) {
      return $final;
    } else {
      // Handle the case where a unique code could not be generated
      return false;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function isactive()
  {
    // Check if user ID stored in session
    $output = $this->session->get('current_user_data', false);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function isverified()
  {
    // Check if user ID stored in session
    $user = $this->session->get('current_user_data', false);
    if (empty($user['account_verified'])) {
      $value = $this->getUserAttribute($user['user_id'], 'verified');
    }

    if (empty($value))     return false;
    $user['account_verified'] = $value;

    $this->session->set('current_user_data', $user);
    return true;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function isadmin($input = '')
  {
      // Case 1: $input is an empty string, so grab the current user data
      if ($input == '') {
          $user = $this->session->get('current_user_data', false);
          if (empty($user['account_admin'])) {
              return false;
          }
          return ($user['account_admin'] != 'N') ? true : false;
      }
  
      // Case 2: $input is an array, check if 'account_admin' is set directly
      if (is_array($input)) {
          if (isset($input['account_admin']) && $input['account_admin'] != 'N') {
              return true;
          }
  
          // If 'user_id' is set in the array, use it for the next check
          if (isset($input['user_id'])) {
              $user_id = (int) $input['user_id'];
          } else {
              return false; // If no user_id is provided in the array, return false
          }
      } else {
          // Case 3: $input is a string/int, treat it as a user_id
          $user_id = (int) $input;
      }
  
      // If we reach here, it means we have a $user_id to check
      $output = false;
      $sql = "SELECT count(1) as cnt FROM bg_users WHERE user_id = :user_id AND account_admin != 'N' AND status = 'A'";
      $stmt = $this->db->prepare($sql);
      $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
  
      if ($row['cnt'] >= 1) {
          $output = true;
      }
  
      return $output;
  }
  
  


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public  function isdeveloper($input = '20')
  {
    global $current_user_data;

    #if (1==2) return true; else return false;
    if ($current_user_data['user_id'] == $input) return true;
    else return false;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getstaff($level = '*', $check_all_statuses = false)
{
    // Get the SQL query from the isstaff function
    $sql = $this->isstaff($level, '', $check_all_statuses, 'stafflist_subquerysql');
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $results =  $stmt->fetch();
    
    // Return both the SQL query and the results
    return [
        'sql' => $sql,
        'results' => $results ?? false
    ];
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function isstaff($level = '*', $input = '', $check_all_statuses = false, $returntype='status')
{

    // Determine the level condition
    switch ($level) {
        case '*':
            $level = '';
            break;
        default:
            $level = " AND staff_ua.description='" . $level . "' ";
    }

    // Modify status condition based on the flag
    $status_condition = $check_all_statuses ? '' : " AND staff_ua.`status` in ('A', 'active')";

if ($returntype=='stafflist_sql' || $returntype=='stafflist_subquerysql' ){
  $sql = "SELECT  ";
$sql.=($returntype=='stafflist_sql' ? "staff_ua.user_id, staff_ua.`status` ":"1");
  $sql.="
  FROM bg_user_attributes staff_ua
  WHERE staff_ua.`type`='staff' " . $level . $status_condition;

return $sql;
}

$user = [];
// Check if user ID is stored in session
if ($input == '') {
    $user = $this->session->get('current_user_data');
} elseif (!is_array($input)) {
    $user['user_id'] = $input;
}

if (empty($user['user_id'])) return false;

    // Construct the query
    $sql = "SELECT DISTINCT staff_ua.`status` 
            FROM bg_user_attributes  staff_ua
            WHERE staff_ua.user_id=:user_id 
            AND staff_ua.`type`='staff' " . $level . $status_condition;

    // Prepare and execute the query
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':user_id', $user['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // If statuses are found
    if (!empty($statuses)) {
        // If check_all_statuses is true, return an array of statuses
        if ($check_all_statuses) {
            return $statuses; // Returns an array of statuses, e.g., ['A', 'terminated']
        }

        // Otherwise, return true since there is at least one active staff record
        return true;
    }

    // Return false if no staff records are found
    return false;
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function isExistingPhoneNumber($phone, $exclude_user_id = null) {
  $phone = preg_replace('/[^0-9]/', '', $phone);
  
  if(empty($phone)) {
      return false;
  }

  // Get current user if no exclude_user_id provided
  if($exclude_user_id === null) {
      $current_user = $this->session->get('current_user_data');
      $exclude_user_id = $current_user['user_id'] ?? null;
  }

  $params = [':phone' => $phone];
  $exclude_sql = '';
  
  if($exclude_user_id) {
      $params[':exclude_id'] = $exclude_user_id;
      $exclude_sql = 'AND u.user_id != :exclude_id';
  }

  $sql = "SELECT COUNT(1) as cnt
          FROM bg_users u
          LEFT JOIN bg_user_attributes ua ON u.user_id = ua.user_id
          WHERE (u.phone = :phone 
                OR ua.value = :phone AND ua.type = 'phone')
          AND u.status = 'A'
          $exclude_sql";

  $stmt = $this->db->prepare($sql);
  $stmt->execute($params);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  
  return ($result['cnt'] > 0);
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function isbrandowner($level = '*', $p_user = '')
  {
    $user = [];
    // Check if user ID stored in session
    if ($p_user == '')
      $user = $p_user = $this->session->get('current_user_data');

    if (!is_array($p_user)) $user['user_id'] = $p_user;

    if (empty($user['user_id'])) return false;

    switch ($level) {
      case '*':
        $level = '';
        break;
      default:
        $level = " and description='" . $level . "' ";
    }

    $output = false;
    $sql = "SELECT count(1) as cnt FROM bg_user_attributes WHERE user_id=" . $user['user_id'] . " and `type`='brandowner' " . $level . " and `status`='A'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch();
    if ($row['cnt'] >= 1) {
      $output = true;
    }
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function iscconsultant($user = '', $level = '*')
  {
    // Check if user ID stored in session
    $user = $this->session->get('current_user_data', $user);

    switch ($level) {
      case '*':
        $level = '';
        break;
      default:
        $level = " and description='" . $level . "' ";
    }

    $output = false;
    $sql = "SELECT count(1) as cnt FROM bg_user_attributes WHERE user_id=" . $user['user_id'] . " and `name` in ('commissioned_consultant', 'commissioned_staff') " . $level . " and `status`='A'";
     $stmt = $this->db->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch();
    if ($row['cnt'] >= 1) {
      $output = true;
    }
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function get_user_attribute($user_id, $attribute_name)
  {
    $sql = "SELECT * FROM bg_user_attributes WHERE user_id = :user_id AND name = :attribute_name AND status = 'A'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'attribute_name' => $attribute_name]);
    $attribute = $stmt->fetch(PDO::FETCH_ASSOC);

    return $attribute !== false ? $attribute : [];
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function isimpersonator()
  {
    // Check if user ID stored in session
    $output = $this->session->get('is_impersonator', false);
    return $output;
  }



  ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function profileLocked($userId = '', $action = 'status', $input = [])
  {
    $expireInMinutes = 60; // Lock expiry time
    if (empty($input['admin_id'])) $input['admin_id'] = 0;
    if (empty($userId)) {
      global $current_user_data;
      if (empty($current_user_data)) {
        header('location: /login');
        exit;
      }
      $userId = $current_user_data['user_id'];
    }

    switch ($action) {
      case 'status':
        $sql = "SELECT * FROM bg_user_enrollment_sessions 
                      WHERE user_id = :user_id AND lock_expired_dt > NOW() AND status = 'A'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;

      case 'lock':
        $sql = "SELECT id FROM bg_user_enrollment_sessions 
                      WHERE user_id = :user_id AND enrollment_data_id = :dataid AND status = 'A' limit 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
          'user_id' => $userId,
          'dataid' => $input['enrollment_data_id']
        ]);
        $resultId = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($resultId)) {
          $sql = "INSERT INTO bg_user_enrollment_sessions 
                          (enrollment_data_id, user_id, status, lock_dt, lock_expired_dt, create_dt, create_by, modify_dt, modify_by)
                          VALUES (:data_id, :user_id, 'A', NOW(), DATE_ADD(NOW(), INTERVAL :expire_minutes MINUTE), NOW(), :admin_id1, NOW(), :admin_id2)";
          $stmt = $this->db->prepare($sql);
          $params= [
            ':data_id' => $input['enrollment_data_id'],
            ':user_id' => $userId,
            ':admin_id1' => $input['admin_id'],
            ':admin_id2' => $input['admin_id'],
            ':expire_minutes' => $expireInMinutes
          ];
      #    breakpoint($sql, false); breakpoint($params);
          $stmt->execute($params);
        } else {
          $sql = "UPDATE bg_user_enrollment_sessions 
                          SET lock_expired_dt = DATE_ADD(NOW(), INTERVAL :expire_minutes MINUTE), modify_dt=NOW(), modify_by = :admin_id
                          WHERE id = :id";
          $stmt = $this->db->prepare($sql);
          $stmt->execute([
            'id' => $resultId['id'],
            'expire_minutes' => $expireInMinutes,
            'admin_id' => $input['admin_id']
          ]);
        }
        break;

      case 'unlock':
        $sql = "UPDATE bg_user_enrollment_sessions 
                      SET `status` = 'I', modify_dt=NOW(), modify_by = :admin_id
                      WHERE user_id = :user_id AND status = 'A'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
          'user_id' => $userId,
          'admin_id' => $input['admin_id']
        ]);
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function forgotPassword($email)
  {
    global $app, $mail;
    // Lookup user by email
    $sql = "SELECT user_id, username FROM bg_users WHERE email = :email";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['email' => $email]);

    if ($user = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
      // User found, generate and email reset link
      $resetToken = $app->generateResetToken($user['id']);
      #  $mail->sendPasswordResetEmail($user['username']);
      # $mail->sendPasswordResetEmail($user['username'], $resetToken);
    } else {
      // User not found
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function deleteAccount($userId)
  {
    // Delete user record
    $sql = "update bg_users set`status`='terminated' WHERE user_id = :userId";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['userId' => $userId]);

    $rowCount = $stmt->rowCount();
    return $rowCount === 1; // Returns true if rowCount is 1, false otherwise
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function updateSettings($user_id, $settings)
  {
    if (empty($user_id)) return false;
    // Build query parts 
    $setParts = [];
    $params = [':user_id' => $user_id];
    foreach ($settings as $name => $value) {
      // If the value is an SQL expression, use it directly
      if (is_array($value) && isset($value['type']) && $value['type'] === 'sql_expression') {
        $setParts[] = "$name = {$value['expression']}";
      } else {
        if ($value == 'now()')
          $setParts[] = "$name = now()";
        else {
          $setParts[] = "$name = :$name";
          $params[':' . $name] = $value;
        }
      }
    }

    $setSql = implode(', ', $setParts);
    $sql = "UPDATE bg_users SET $setSql, modify_dt = NOW() WHERE user_id = :user_id";
    session_tracking('updatesettings',  $sql);
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return true;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function updateSettingsx($user_id, $settings)
  {
      if (empty($user_id)) return false;
  
      // Build query parts 
      $setParts = [];
      $params = [':user_id' => $user_id];
      foreach ($settings as $name => $value) {
          // Handle SQL expressions safely
          if (is_array($value) && isset($value['type']) && $value['type'] === 'sql_expression') {
              if (!preg_match('/^[\w\s().]+$/', $value['expression'])) {
                  throw new InvalidArgumentException("Invalid SQL expression for $name");
              }
              $setParts[] = "$name = {$value['expression']}";
          } elseif ($value === 'now()') {
              // Directly use SQL `now()` function
              $setParts[] = "$name = NOW()";
          } else {
              // Use placeholders for values
              $setParts[] = "$name = :$name";
              $params[':' . $name] = $value;
          }
      }
  
      $setSql = implode(', ', $setParts);
      $sql = "UPDATE bg_users SET $setSql, modify_dt = NOW() WHERE user_id = :user_id";
      session_tracking('updatesettings', $sql);
      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);
  
      return true;
  }

  

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function updateUserProfileData($user_id, $settings) 
  {
      if (empty($user_id)) return false;
  
      $metrics = [
          'success' => false,
          'total_fields' => count($settings),
          'fields_processed' => 0,
          'fields_skipped' => 0,
          'records_found' => 0,
          'records_updated' => 0,
          'records_inserted' => 0,
          'unchanged_records' => 0,
          'execution_time' => 0,
          'error_message' => null,
          'field_details' => []
      ];
  
      $startTime = microtime(true);
      $anyChanges = false; // Track if any changes were made
      
      $this->db->beginTransaction();
  
      try {
          $now = date('Y-m-d H:i:s');
  
          foreach ($settings as $name => $value) {
              $fieldMetric = [
                  'field_name' => $name,
                  'action' => null,
                  'status' => 'processed',
                  'had_changes' => false
              ];
  
              // Skip SQL expressions for attribute table
              if (is_array($value) && isset($value['type']) && $value['type'] === 'sql_expression') {
                  $metrics['fields_skipped']++;
                  $fieldMetric['status'] = 'skipped';
                  $fieldMetric['reason'] = 'sql_expression';
                  $metrics['field_details'][] = $fieldMetric;
                  continue;
              }
  
              // Handle 'now()' special case
              if ($value === 'now()') {
                  $value = $now;
              }
  
              // Check if attribute already exists and get current value
              $sql = "SELECT attribute_id, string_value 
                     FROM bg_user_attributes 
                     WHERE user_id = :user_id 
                     AND type = 'profile' 
                     AND name = :name 
                     AND status = 'active'";
  
              $stmt = $this->db->prepare($sql);
              $stmt->execute([
                  ':user_id' => $user_id,
                  ':name' => $name
              ]);
  
              if ($existing = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  $metrics['records_found']++;
                  
                  // Compare current and new values
                  if ((string)$existing['string_value'] !== (string)$value) {
                      // Update only if values are different
                      $sql = "UPDATE bg_user_attributes 
                             SET string_value = :value,
                                 modify_dt = :modify_dt
                             WHERE attribute_id = :attribute_id";
  
                      $stmt = $this->db->prepare($sql);
                      $stmt->execute([
                          ':value' => $value,
                          ':modify_dt' => $now,
                          ':attribute_id' => $existing['attribute_id']
                      ]);
  
                      $metrics['records_updated']++;
                      $fieldMetric['action'] = 'updated';
                      $fieldMetric['had_changes'] = true;
                      $fieldMetric['old_value'] = $existing['string_value'];
                      $fieldMetric['new_value'] = $value;
                      $anyChanges = true;
                  } else {
                      $metrics['unchanged_records']++;
                      $fieldMetric['action'] = 'skipped';
                      $fieldMetric['reason'] = 'no changes';
                  }
                  
                  $fieldMetric['attribute_id'] = $existing['attribute_id'];
  
              } else {
                  // Insert new attribute
                  $sql = "INSERT INTO bg_user_attributes 
                         (user_id, type, name, string_value, status, create_dt, modify_dt) 
                         VALUES 
                         (:user_id, 'profile', :name, :value, 'active', :create_dt, :modify_dt)";
  
                  $stmt = $this->db->prepare($sql);
                  $stmt->execute([
                      ':user_id' => $user_id,
                      ':name' => $name,
                      ':value' => $value,
                      ':create_dt' => $now,
                      ':modify_dt' => $now
                  ]);
  
                  $metrics['records_inserted']++;
                  $fieldMetric['action'] = 'inserted';
                  $fieldMetric['had_changes'] = true;
                  $fieldMetric['new_value'] = $value;
                  $fieldMetric['attribute_id'] = $this->db->lastInsertId();
                  $anyChanges = true;
              }
  
              $metrics['fields_processed']++;
              $metrics['field_details'][] = $fieldMetric;
          }
  
          // Update main user record's modify_dt only if there were changes
          if ($anyChanges) {
              $sql = "UPDATE bg_users 
                      SET modify_dt = NOW() 
                      WHERE user_id = :user_id";
              $stmt = $this->db->prepare($sql);
              $stmt->execute([':user_id' => $user_id]);
          }
  
          $this->db->commit();
          
          $metrics['success'] = true;
          $metrics['had_changes'] = $anyChanges;
          $metrics['execution_time'] = round(microtime(true) - $startTime, 4);
          
          return [
              'success' => true,
              'metrics' => $metrics
          ];
  
      } catch (Exception $e) {
          $this->db->rollBack();
          
          $metrics['success'] = false;
          $metrics['error_message'] = $e->getMessage();
          $metrics['execution_time'] = round(microtime(true) - $startTime, 4);
          
          return [
              'success' => false,
              'metrics' => $metrics
          ];
      }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public  function user_activedevices($user_id)
  {

    $sql = "SELECT distinct a.attribute_id, a.type, a.name, a.description, a.create_dt FROM bg_user_attributes a, bg_validations v WHERE a.user_id = :user_id AND a.type = 'bg_rememberme_set' AND 
   a.status = 'A' and a.name=v.device_id and v.validation_type='bgrememberme_autologin' and v.status='cookie'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $result;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function setUserAttribute($user_id, $input)
{
    // Build query parts
    $columnNames = [];
    $valuePlaceholders = [];
    $updateStatements = [];
    $params = [':user_id' => $user_id];

    foreach ($input as $name => $value) {
        // Escape column names with backticks to handle reserved words
        $escapedName = '`' . str_replace('`', '``', $name) . '`';
        $columnNames[] = $escapedName;
        $valuePlaceholders[] = ":$name";
        $updateStatements[] = "$escapedName = VALUES($escapedName)";
        $params[":$name"] = $value;
    }

    // Join column names, value placeholders, and update statements to create parts of the SQL query
    $columnNamesSql = implode(', ', $columnNames);
    $valuePlaceholdersSql = implode(', ', $valuePlaceholders);
    $updateStatementsSql = implode(', ', $updateStatements);

    // Construct query
    $sql = "
    INSERT INTO bg_user_attributes (user_id, $columnNamesSql, create_dt, modify_dt) 
    VALUES (:user_id, $valuePlaceholdersSql, NOW(), NOW())
    ON DUPLICATE KEY UPDATE $updateStatementsSql, modify_dt = NOW()
    ";

    // Prepare and execute
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return true;
}


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function account_getstats($type = 'all', $user_id = '')
  {
    $output = false;
    global $database;
    if ($user_id == '') {
      global $session;
      $current_user_data = $session->get('current_user_data');
      $user_id = $current_user_data['user_id'];
    }
    switch ($type) {
      case 'all':
        $output = [];
        $sql = 'select concat("business_", `status`) name, count(*) value from bg_user_companies where user_id= ' . $user_id . ' group by concat("business_", `status`)';
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $result) {
          $output[$result['name']] = $result['value'];
        }
        $elementsToCheck = array('business_selected', 'business_pending', 'business_failed', 'business_success', 'business_testing',  'business_removed');
        $output = array_merge(array_fill_keys($elementsToCheck, 0), $output);
        break;
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function getbusinesslist_rewards($current_user_data = [], $querytype = 'card', $criteriastatus = 'any', $limit = 0, $sendresults = true)
  {
    #var_dump($criteriastatus);
    global $database;

    if (empty($current_user_data)) {
      global $session;
      $current_user_data = $session->get('current_user_data');
      if (empty($current_user_data)) {
        header('location: /login');
        exit;
      }
    }
    $user_id = $current_user_data['user_id'];
    $criteria = '';
    $output = '';

    $statusCounters = [
      'failed' => 0,
      'pending' => 0,
      'selected' => 0,
      'toenroll' => 0,
      'active' => 0,
      'success' => 0,
      'existing' => 0,
      'default' => 0,
      'removed' => 0,
      'total' => 0,
      'remaining' => 0,
      'overage' => 0,
      'plan_total' => 0,
    ];

    $outputallarray = [];
    $finalarray = [];
    $outputlimitedarray = [];
    $limitcount = 0;

    $enablelimit = false;
    if ($limit != 0)  $enablelimit = true;

    # $status ='any';
    # if ($criteriastatus != 'any' || $limit != 0)  $enablelimit = true;

    // Adjust the criteria based on the status

    $statuscriteriasql = "";
    $listcriteriasql = "";



    if ($criteriastatus == 'any') {
      $statuscriteriasql = " and c.`status` = 'finalized' ";
    } else {
      $statuscriteriasql = " and uc.`status` in (  " . $criteriastatus . ") and c.`status` = 'finalized' ";
    }


    switch ($querytype) {
      case 'card':
        $listcriteria = " HAVING (expiration_date IS NULL OR expiration_date >= CURDATE()) ";
        break;
      case 'list':
        $listcriteria = "";
        break;
      case 'detail':
        $statuscriteriasql = " and r.reward_id=" . $limit . " " . $statuscriteriasql;
        $enablelimit = true;
        $limit = 1;
        break;
    }




    $sql = "WITH RankedCompanies AS
(SELECT uc.user_company_id, c.spinner_description, uc.user_id, uc.company_id, uc.reason, uc.status, uc.`status` AS enrollment_status, uc.registration_dt, uc.create_dt, uc.modify_dt, c.company_name, 
c.appgoogle, c.appapple, ca.description AS company_logo, MAX(IFNULL(ad.id, '')) AS amid, ROW_NUMBER() OVER (PARTITION BY uc.company_id
ORDER BY uc.modify_dt DESC) AS rn
FROM bg_user_companies AS uc
LEFT JOIN am_datastore ad ON uc.user_id = ad.user_id
AND uc.company_id = ad.company_id
JOIN bg_companies AS c ON uc.company_id = c.company_id
LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id
AND ca.category = 'company_logos'
AND ca.grouping = 'primary_logo'
WHERE uc.user_id = :user_id0
GROUP BY uc.user_company_id, uc.user_id, uc.company_id, uc.modify_dt, c.company_name, c.appgoogle, c.appapple, ca.description)
SELECT uc.user_company_id, r.reward_id, r.reward_name, c.signup_url, c.info_url, r.reward_description_short, r.reward_value, r.cash_value, r.redeem_instructions, r.requirements, r.category, r.minage, r.maxage, r.mindaysstart, 
r.expiredays, uc.registration_dt, rc.company_name, rc.company_logo, rc.appgoogle, rc.appapple, uc.company_id, -- Calculate availability based on category
CASE
WHEN r.category = 'birthday' THEN CASE
WHEN DATE_FORMAT(u.birthdate, CONCAT(YEAR(CURDATE()), '-%m-%d')) < CURDATE() THEN DATE_FORMAT(u.birthdate, CONCAT(YEAR(CURDATE()) + 1, '-%m-%d'))
ELSE DATE_FORMAT(u.birthdate, CONCAT(YEAR(CURDATE()), '-%m-%d'))
END
WHEN r.category = 'enrollment' THEN DATE_ADD(uc.registration_dt, INTERVAL r.mindaysstart DAY)
ELSE NULL
END AS availability_from_date, -- Calculate expiration based on category
CASE
WHEN r.expiredays IS NOT NULL THEN CASE
WHEN r.category = 'birthday' THEN DATE_ADD(CASE
WHEN DATE_FORMAT(u.birthdate, CONCAT(YEAR(CURDATE()), '-%m-%d')) < CURDATE() THEN DATE_FORMAT(u.birthdate, CONCAT(YEAR(CURDATE()) + 1, '-%m-%d'))
ELSE DATE_FORMAT(u.birthdate, CONCAT(YEAR(CURDATE()), '-%m-%d'))
END, INTERVAL r.expiredays DAY)
WHEN r.category = 'enrollment' THEN DATE_ADD(DATE_ADD(uc.registration_dt, INTERVAL r.mindaysstart DAY), INTERVAL r.expiredays DAY)
ELSE NULL
END
ELSE NULL
END AS expiration_date
FROM bg_user_companies uc
LEFT JOIN bg_users u ON uc.user_id = u.user_id
LEFT JOIN bg_company_rewards r ON uc.company_id = r.company_id
AND r.status = 'active'
AND r.category IN ('enrollment', 'birthday')
LEFT JOIN bg_companies c ON uc.company_id = c.company_id

LEFT JOIN RankedCompanies rc ON uc.company_id = rc.company_id
AND rc.rn = 1
WHERE uc.user_id = :user_id1
" . $statuscriteriasql . "  -- !!  Filter by status
" . $listcriteriasql . " -- !! Filter by list criteria
ORDER BY availability_from_date ASC, expiration_date ASC
";

    if ($enablelimit) $sql .= ' LIMIT ' . $limit;
    $stmt = $database->prepare($sql);
    $stmt->execute([':user_id0' => $current_user_data['user_id'], ':user_id1' => $current_user_data['user_id']]);

    if ($sendresults) {
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getbusinesslist($current_user_data = [], $criteriastatus = 'any', $limit = 0, $sendresults = false)
  {
    # var_dump($criteriastatus);
    global $database, $display, $qik, $app;

    if (empty($current_user_data)) {
      global $session;
      $current_user_data = $session->get('current_user_data');
      if (empty($current_user_data)) {
        header('location: /login');
        exit;
      }
    }
    $criteria = '';
    $output = '';

    $statusCounters = [
      'failed' => 0,
      'pending' => 0,
      'selected' => 0,
      'toenroll' => 0,
      'active' => 0,
      'success' => 0,
      'existing' => 0,
      'default' => 0,
      'removed' => 0,
      'total' => 0,
      'remaining' => 0,
      'overage' => 0,
      'plan_total' => 0,
    ];

    $outputallarray = [];
    $finalarray = [];
    $outputlimitedarray = [];
    $limitcount = 0;

    $enablelimit = false;
    if ($criteriastatus != 'any' || $limit != 0)  $enablelimit = true;

    # $status ='any';
    if ($criteriastatus != 'any' || $limit != 0)  $enablelimit = true;
    // Adjust the criteria based on the status
    if ($criteriastatus == 'any') {
      $criteria = " and c.`status` = 'finalized' ";
    } else {
      $criteria = " and uc.`status` in (' . $criteriastatus . ') and c.`status` = 'finalized' ";
    }
    #$criteria='';


    $sql = "WITH RankedCompanies AS (
  SELECT uc.user_company_id, uc.user_id, uc.company_id company_id,  uc.reason, uc.status,   uc.`status` as enrollment_status,  uc.registration_dt, uc.create_dt, uc.modify_dt, 
  c.company_name, c.appgoogle, c.appapple, ca.description AS company_logo, MAX(IFNULL(ad.id, '')) as amid, ROW_NUMBER() 
  OVER (PARTITION BY uc.company_id ORDER BY uc.modify_dt DESC) as rn
  FROM bg_user_companies AS uc
  LEFT JOIN am_datastore ad ON uc.user_id = ad.user_id AND uc.company_id = ad.company_id
  JOIN bg_companies AS c ON uc.company_id = c.company_id
  LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id AND ca.category = 'company_logos' AND ca.grouping = 'primary_logo'
  WHERE uc.user_id = ? " . $criteria . " 
  AND uc.create_dt >= '2023-08-01'
  GROUP BY uc.user_company_id, uc.user_id, uc.company_id, uc.modify_dt, c.company_name, c.appgoogle, c.appapple, ca.description
)
SELECT user_company_id, user_id, company_id, reason, status, enrollment_status, create_dt , modify_dt, registration_dt, company_name, appgoogle, appapple, company_logo, amid
FROM RankedCompanies
WHERE rn = 1
ORDER BY status, company_name, modify_dt DESC
";
    if ($enablelimit) $sql .= ' LIMIT ' . $limit;
    $stmt = $database->prepare($sql);
    $stmt->execute([$current_user_data['user_id']]);

    if ($sendresults) {
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $apptype = $current_user_data['profile_phone_type'];
      $output = '';
      $showcompany = true;
      $removetag = '<p><a class="text-danger remove-link" href="#" data-id="' . $row['user_company_id'] . '">Remove</a></p>';

      $applink = $display->applink($apptype, $row);
      $appicon = $applink['applink'];
      $qrcode = $applink['qrlink'];

      $statusCounters['total']++;
      switch ($row['enrollment_status']) {
        case 'failed':
        case 'failed-btn':
          $status_sign = '<i class="bi bi-x-octagon-fill text-danger"></i>';
          $statusmessagetag = '';
          if (!empty($row['reason']))  $statusmessagetag = '<br>' . $row['reason'];
          $statusmessage = '<p class="text-danger p-0 m-0">We were unable to enroll you.' . $statusmessagetag . '</p>';
          $statusCounters['failed']++;
          break;
        case 'pending':
          $status_sign = '<i class="bi bi-clock-history text-dark"></i>';
          $statusmessage = '<p class="text-dark p-0 m-0">We are in the process of enrolling you.</p>';
          $statusCounters['pending']++;
          $statusCounters['toenroll']++;
          break;
        case 'selected':
          $status_sign = '<i class="bi bi-clock-history text-dark"></i>';
          $statusmessage = '<p class="text-dark p-0 m-0">You selected this business. The system has not picked it up yet to enroll you yet.</p>';
          $statusCounters['selected']++;
          $statusCounters['toenroll']++;
          break;
        case 'success':
        case 'success-btn':
          $status_sign = '<i class="bi bi-patch-check-fill text-success"></i>';
          $statusmessage = '<p class="text-success p-0 m-0">You were successfully enrolled.</p>';
          $statusCounters['success']++;
          $statusCounters['active']++;
          $removetag = '';
          break;
        case 'existing':
          $status_sign = '<i class="bi bi-check-circle-fill"></i>';
          $statusmessage = '<p class="text-success p-0 m-0">You had an account before birthday.gold.</p>';
          $statusCounters['existing']++;
          $statusCounters['active']++;
          $removetag = '';
          break;
        case 'removed':
          $status_sign = '';
          $statusmessage = '';
          $statusCounters['removed']++;
          $removetag = '';
          $showcompany = false;
          break;
        default:
          $status_sign = '<i class="bi bi-question-diamond-fill text-warning"></i>';
          $statusmessage = '<p class="text-warning p-0 m-0"></p>';
          $statusCounters['default']++;
          break;
      }

      // Now you can use $statusCounters to get the count for each status.
      if ($showcompany) {
        $timetag['message'] = '';
        if (!empty($row['modify_dt'])) {
          $usedate = $row['modify_dt'];
          $timetag = $qik->timeago($usedate);
        } elseif (!empty($row['create_dt'])) {
          $usedate = $row['create_dt'];
          $timetag = $qik->timeago($usedate);
        }
        $output .= '
                  <tr>
                    <td scope="row" class="align-middle">' . str_replace('class="', 'class="h1 ', $status_sign) . '' . $removetag . '</td>
                  ';
        $output .= '<td class="text-left align-middle">
                  <h3 class="mb-0 pb-0 pe-6">' . $row['company_name'] . '</h3>
                  ' . $statusmessage . '  
                  <p class="p-0 m-0">' . $row['reason'] . '</p>
                  <p class="p-0 m-0">' . $timetag['message'] . '</p>
                    </td>
                    <td class="align-middle">' . $appicon . '</td>
                  </tr>
              ';
      }
      $currentRowArray = [
        'data' => $row,
        'apptype' => $apptype,
        'showcompany' => $showcompany,
        'removetag' => $removetag,
        'applink' => $applink,
        'appicon' => $appicon,
        'qrcode' => $qrcode,
        'status_sign' => $status_sign,
        'statusmessage' => $statusmessage,
        'timetag' => $timetag ?? '',
        'outputhtml' => $output
      ];

      $outputallarray[] = $currentRowArray;

      if ($enablelimit && (!empty($row['enrollment_status']) &&
        strpos($criteriastatus, $row['enrollment_status']) !== false) && $limitcount < $limit) {
        $outputlimitedarray[] = $currentRowArray;
        $limitcount++;
      }
    }

    if ($enablelimit) {
      $statusCounters['limited'] = $limit;
      $statusCounters['limited_total'] = $limitcount;
    }

    // Calculate remaining selections
    $accountstats = [
      'business_pending' => $statusCounters['pending'],
      'business_selected' => $statusCounters['selected'],
      'business_success' => $statusCounters['success']
    ];
    $selectsused = ($accountstats['business_pending'] + $accountstats['business_selected'] + $accountstats['business_success']);


/*
    // Attempt to get the v3 plan details
$userplan = $current_user_data['account_plan']; // Assuming 'user_plan' is part of $current_user_data
#$plandetails = $app->plandetail('details');
// Check if the user plan exists in v3
if (!isset($plandetails[$userplan])) {
  // If the user plan doesn't exist in v3, fall back to v2
  $plandetails = $app->plandetail('details', '', 'v2');
}
  */
  $plandatafeatures=$app->plandetail('details_id', $current_user_data['account_product_id']);
      $selectsleft = ($plandatafeatures['max_business_select'] - $selectsused);
    $statusCounters['remaining'] = max(0, $selectsleft); // Ensure remaining is not negative
    $statusCounters['overage'] =  $selectsleft; // Ensure remaining is not negative
    $statusCounters['plan_total'] = $plandatafeatures['max_business_select'];


    

    $finalarray['data'] = $outputallarray;
    $finalarray['counts'] = $statusCounters;
    if ($enablelimit) $finalarray['data_limited'] = $outputlimitedarray;
    return $finalarray;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getUserAttribute($user_id, $p_attribute, $options = '', $columns = '*')
  {

    if (strpos($p_attribute, '::') !== false) {

      // Use Type and Name
      list($attribute_type, $attribute_name) = explode('::', $p_attribute);
      $sql = "SELECT $columns FROM bg_user_attributes WHERE user_id = :user_id AND `type` = :type AND `name` = :name  and `status` in ('A', 'active') limit 1";
      $stmt = $this->db->prepare($sql);
      $stmt->execute([':user_id' => $user_id, ':type' => $attribute_type, ':name' => $attribute_name]);
    } else {
      $attribute_name = $p_attribute;
      // Use Name
      $sql = "SELECT $columns FROM bg_user_attributes WHERE user_id = :user_id AND `name` = :name  order by `rank` desc limit 1";
      $stmt = $this->db->prepare($sql);
      $stmt->execute([':user_id' => $user_id, ':name' => $attribute_name]);
    }

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
      if ($columns != '*' && strpos($columns, ',') === false)  return $result[$columns];
      else
        return $result;
    } else {
      $out = false;
      if (strpos($options, 'defaultvalue=') !== false) $out = str_replace('defaultvalue=', '', $options);
      return $out;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function generateReferralCode($current_user_data) {
    $first_name = $current_user_data['first_name'];
    $last_name = $current_user_data['last_name'];
    $birthday = $current_user_data['birthdate']; // Assuming format YYYY-MM-DD

    // Generate the base of the referral code
    $codeBase = strtoupper(substr($first_name, 0, 1)) . strtoupper(substr($last_name, 0, 5));
    $birthdayDigits = preg_replace('/[^0-9]/', '', $birthday); // Extract only digits from birthday
    
    $code = $codeBase . substr($birthdayDigits, -2); // Use last 4 digits of the birthday

    // Check if the generated code already exists in the bg_user_attributes table
    $count = 1;
    $finalCode = $code;
    do {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM bg_user_attributes WHERE referral_code = :code');
        $stmt->execute([':code' => $finalCode]);
        $exists = $stmt->fetchColumn();
        if ($exists > 0) {
            $finalCode = $code . $count;
            $count++;
        }
    } while ($exists > 0);

    // Store the unique referral code for the current user
    $stmt = $this->db->prepare('UPDATE bg_user_attributes SET referral_code = :code WHERE user_id = :user_id');
    $stmt->execute([':code' => $finalCode, ':user_id' => $current_user_data['user_id']]);

    return $finalCode;
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function manageReferralCode($current_user_data = [], $task = 'create', $code = '') {
    if (empty($current_user_data)) {
        global $session;
        $current_user_data = $session->get('current_user_data');
        if (empty($current_user_data)) {
            header('location: /login');
            exit;
        }
    }

    $first_name = $current_user_data['first_name'];
    $last_name = $current_user_data['last_name'];
    $birthday = $current_user_data['birthdate']; // Assuming format YYYY-MM-DD
    $user_id = $current_user_data['user_id'];

    if ($task === 'update' && empty($code)) {
        return false;
    }

    // Check if there's already a generated code when the task is 'create' or 'get'
    if (in_array($task, ['create', 'get'])) {
        $stmt = $this->db->prepare('
            SELECT description 
            FROM bg_user_attributes 
            WHERE user_id = :user_id AND type = :type AND name = :name
        ');
        $stmt->execute([
            ':user_id' => $user_id,
            ':type' => 'referralcode',
            ':name' => 'generated_code'
        ]);
        $existingCode = $stmt->fetchColumn();

        if ($existingCode) {
            return [
                'code' => $existingCode,
                'task' => $task,
                'count' => 0,
                'message' => 'Existing referral code retrieved.'
            ];
        }

        // If task is 'get' and no code is found, return an appropriate message
        if ($task === 'get') {
            return [
                'code' => null,
                'task' => $task,
                'count' => 0,
                'message' => 'No referral code found.'
            ];
        }
    }

    if (empty($code)) {
        // Generate the base of the referral code
        $codeBase = strtoupper(substr($first_name, 0, 1)) . strtoupper(substr($last_name, 0, 5));
        $birthdayDigits = preg_replace('/[^0-9]/', '', $birthday); // Extract only digits from birthday
        $code = $codeBase . substr($birthdayDigits, -2); // Use last 2 digits of the birthday
    }

    // Check if the code exists and is unique
    $count = 1;
    $finalCode = $code;
    do {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM bg_user_attributes WHERE type = :type AND description = :description');
        $stmt->execute([':type' => 'referralcode', ':description' => $finalCode]);
        $exists = $stmt->fetchColumn();
        if ($exists > 0) {
            $finalCode = $code . $count;
            $count++;
        }
    } while ($exists > 0);

    // Handle 'create' or 'update' task
    if ($task === 'create') {
        // Insert new referral code
        $stmt = $this->db->prepare('
            INSERT INTO bg_user_attributes (user_id, type, name, description, status, create_dt, modify_dt)
            VALUES (:user_id, :type, :name, :description, :status, NOW(), NOW())
        ');
        $stmt->execute([
            ':user_id' => $user_id,
            ':type' => 'referralcode',
            ':name' => 'generated_code',
            ':description' => $finalCode,
            ':status' => 'active'
        ]);
        $message = 'Referral code created successfully.';
    } elseif ($task === 'update') {
        // Update existing referral code
        $stmt = $this->db->prepare('
            UPDATE bg_user_attributes
            SET description = :description, modify_dt = NOW()
            WHERE user_id = :user_id AND type = :type AND name in ("generated_code", "custom_code")
        ');
        $stmt->execute([
            ':description' => $finalCode,
            ':user_id' => $user_id,
            ':type' => 'referralcode',
        ]);
        $message = 'Referral code updated successfully.';
    }

    return [
        'code' => $finalCode,
        'task' => $task,
        'count' => $count,
        'message' => $message
    ];
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function getuseroptions($list = '', $type = 'array')
  {
    $optionlist['honor'] = array('military', 'educator', 'firstresponder');
    $optionlist['agree'] = array('terms', 'text', 'email');
    $optionlist['allergy'] = array('gluten', 'sugar', 'nuts', 'dairy');
    $optionlist['diet'] = array('vegan', 'kosher', 'pescatarian', 'keto', 'paleo', 'vegetarian');
    $output = array();

    if ($list == 'all') return $optionlist;
    switch ($type) {
      case 'array':
        $output = $optionlist[$list];
        break;
      case 'settofalse':
        if (isset($optionlist[$list])) {
          foreach ($optionlist[$list] as $item) {
            $output['inputprofile_' . $item] = '';
          }
        }
        break;
    }
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getuserid($input = '', $type = 'email')
  {
    $params = [
      ':lookupvalue' => $input
    ];
    $sql = 'SELECT user_id FROM bg_users WHERE ' . $type . ' = :lookupvalue';
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
      return $result['user_id'];
    } else {
      return false;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function checkEnrollmentSchedule($user, $incurrentTime='' ) {
      global $bg_enrollment_schedule_timeBlockMap;
          $timeBlockMap=$bg_enrollment_schedule_timeBlockMap;

            // Initialize variables to avoid undefined variable errors
    $schedule_record_count = 0;
    $hoursUntilEnrollment = null;
    $allowenrollment=true;
if ($incurrentTime == '') {
  $currentDay= $currentDTDay = strtolower(date('l')); // Get current day of the week, e.g., "monday"
  $currentDTTime = date('H:i'); // Get current time, e.g., "14:30"
  $currentTime = new DateTime("$currentDTDay $currentDTTime");  
        } else {
            $currentTime = new DateTime($incurrentTime);
        }

        $currentBlock = null;

        // Grab current schedule
        foreach ($timeBlockMap as $block => $times) {
            $startDateTime = new DateTime($times['start']);
            $endDateTime = new DateTime($times['end']);
    
            // If the block crosses midnight, adjust the end time
            if ($endDateTime < $startDateTime) {
                $endDateTime->modify('+1 day');
            }
    
            if ($currentTime >= $startDateTime && $currentTime < $endDateTime) {
                $currentBlock = $block;
                break;
            }
        }

        // Check if there is a matching schedule for the current time
        $validEnrollmentTime = false;
        $scheduleFlag = '';
        $delayMessage = '';
        $delayColor = '';

        if ($currentBlock !== null) {
            $sql = "SELECT * FROM bg_user_schedules 
                    WHERE user_id = :user_id 
                    AND status = 'active' 
                    AND day = :current_day 
                    AND time_block = :time_block";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $user['user_id'],
                ':current_day' => $currentDay,
                ':time_block' => $currentBlock,
            ]);

            if ($stmt->rowCount() > 0) {
                $validEnrollmentTime = true;
                $scheduleFlag = "Enrollment is valid for today ($currentDay) and time block ($currentBlock).";
            }
        }

        if (!$validEnrollmentTime) {
            // Find the next available schedule
            $sql = "SELECT * FROM bg_user_schedules 
                    WHERE user_id = :user_id 
                    AND status = 'active' 
                    AND (
                        (day = :current_day1 AND time_block > :time_block) 
                        OR day > :current_day2
                    )
                    ORDER BY FIELD(day, :current_day3, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), time_block ASC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $user['user_id'],
                ':current_day1' => $currentDay,
                ':current_day2' => $currentDay,
                ':current_day3' => $currentDay,
                ':time_block' => $currentBlock,
            ]);
        
         
   
$schedule_record_count=$stmt->rowCount();
            if ($schedule_record_count > 0) {
              $allowenrollment=true;
                $nextBlock = $stmt->fetch(PDO::FETCH_ASSOC);
                $nextDay = $nextBlock['day'];
                $nextBlockNumber = $nextBlock['time_block'];
            
                if (isset($timeBlockMap[$nextBlockNumber])) {
                    $nextStartTime = $timeBlockMap[$nextBlockNumber]['start'];
                    $nextEnrollmentDateTime = new DateTime("$nextDay $nextStartTime");
                    $interval = $currentTime->diff($nextEnrollmentDateTime);
                    $hoursUntilEnrollment = $interval->h + ($interval->days * 24);

                
                    $scheduleFlag = 'Next valid enrollment: ' . ucfirst($nextDay) . ' (' . $timeBlockMap[$nextBlockNumber]['start'] . ' - ' . $timeBlockMap[$nextBlockNumber]['end'] . ')';
                    $delayMessage = 'Delayed ' . $hoursUntilEnrollment . ' hrs.';
                    $delayColor = 'bg-info';
                    $allowenrollment=false;
                } else {
                  $hoursUntilEnrollment = null;
                    $scheduleFlag = "Invalid time block detected: $nextBlockNumber. Unable to calculate the next enrollment time.";
                    $delayMessage = 'Invalid Schedule.';
                    $delayColor = 'bg-danger';
                    $allowenrollment=true;
                }
            } else {
              $hoursUntilEnrollment = null;
                $delayMessage = 'No Schedule.';
                $delayColor = 'bg-warning';
                $scheduleFlag = "No upcoming valid enrollment blocks found.";
                $allowenrollment=true;
            }
        }

        return [
            'valid_enrollment_time' => $validEnrollmentTime,
            'schedule_flag' => $scheduleFlag,
            'delay_message' => $delayMessage,
            'allow_enrollment' => $allowenrollment ? true : false,
            'delay_color' => $delayColor,
            'schedule_record_count'=> $schedule_record_count,
            'hours_until_enrollment' => $hoursUntilEnrollment,
            'user' => $user
        ];
    }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public  function generatetourcalendar($startdate_in, $length = 14, $input = [])
  {
    global $display;
    $output = '';
    $toutlist = [];
    $plan = $input['plan'] ?? $input['current_user_data']['account_plan'];
    $userbirthdate = $input['birthdate'] ?? $input['current_user_data']['birthdate'];
    $plandetails = $input['plandetails'];
    if (isset($input['plandetails_override'])) {
      $plandetails = array_merge($plandetails, $input['plandetails_override']);
    }
    $user_id = $input['user_id'] ?? $input['current_user_data']['user_id'];
    if (empty($input['loopstop'])) $input['loopstop'] = 'dates';
    if (empty($input['displaytype'])) $input['displaytype'] = 'web';
    if (empty($input['navigation'])) $input['navigation'] = 'on';
    if (empty($input['formaction'])) $input['formaction'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


    if ($startdate_in instanceof DateTime) {
      $display_start_date = clone $startdate_in;
    } else {
      $display_start_date = new DateTime($startdate_in);
    }

    $birthdate = new DateTime($userbirthdate);
    $currentYear = (new DateTime())->format('Y');
    $birthdate->setDate($currentYear, $birthdate->format('m'), $birthdate->format('d'));

    $icalendar_start_date = clone $birthdate;
    $icalendar_start_date->modify('-' . ($plandetails['celebration_tour_days_before'] + 1) . ' days');

    $icalendar_end_date = clone $birthdate;
    $icalendar_end_date->modify('+' . $plandetails['celebration_tour_days_after'] . ' days');


    $icalendar_start_date_str = $icalendar_start_date->format('Y-m-d');
    $icalendar_end_date_str = $icalendar_end_date->format('Y-m-d');

    $tourlistdates = $apitourlistdates = [];
    $tournumber = 0;
    $stmt =  $this->db->prepare("SELECT * FROM bg_user_tours WHERE user_id = :user_id AND calendar_dt BETWEEN :start_date AND :end_date order by calendar_dt");
    $stmt->execute([':user_id' => $user_id, ':start_date' => $icalendar_start_date_str, ':end_date' => $icalendar_end_date_str]);
    $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tours as $tour) {
      $tourlistdates[] = $tour['calendar_dt'];
      $apitourlistdates[$tour['calendar_dt']] = $tour['status'];
    }

    if ($input['displaytype'] == 'apilist') return $apitourlistdates;

    /*
    foreach ($tours as $tour) {
     # $tourlistdates[] = [$tour['calendar_dt']=>$tour['status']];
      $tourlistdates[$tour['calendar_dt']] = $tour['status'];
     #$tourlistdates[] = $tour['calendar_dt'];
     # $tourlistdates[$tournumber]=[$tour['calendar_dt'] => $tour['status']];
      $tournumber++;
    }
    */


    $tourlistdates = array_unique($tourlistdates);
    if ($display_start_date < $icalendar_start_date)  $display_start_date = clone $icalendar_start_date;
    $display_end_date = clone $icalendar_end_date;

    switch ($plan) {

      case 'life':

        $checkstart_date = clone $display_start_date;
        $checkstart_date->modify('-' . $length . ' days');

        if ($checkstart_date < $icalendar_start_date) {
          $checkstart_date = clone $icalendar_start_date;
        }

        if ($icalendar_start_date >= $display_start_date) {
          $calendarbutton['previous'] = '<a href="" class="btn btn-sm button-secondary disabled border-0 "><i class="h1 bi bi-arrow-left-square-fill"></i></a>';
        } else {
          $calendarbutton['previous'] = '<a href="?previous=' . $checkstart_date->format('Y-m-d') . '" class="btn btn-sm button-secondary"><i class="h1 bi bi-arrow-left-square-fill"></i></a>';
        }

        $checkend_date = clone $display_start_date;
        $checkend_date->modify('+' . $length . ' days');

        if ($icalendar_end_date <= $checkend_date) {
          $calendarbutton['next'] = '<a href="" class="btn btn-sm button-secondary disabled border-0 "><i class="h1 bi bi-arrow-right-square-fill"></i></a>';
        } else {
          $calendarbutton['next'] = '<a href="?next=' . $checkend_date->format('Y-m-d') . '" class="btn btn-sm button-secondary"><i class="h1 bi bi-arrow-right-square-fill"></i></a>';
        }

        break;

      default:
        $calendarbutton['previous'] = '';
        $calendarbutton['next'] = '';
        break;
    }
    if ($input['navigation'] == 'off') {
      $calendarbutton['previous'] = '';
      $calendarbutton['next'] = '';
    }

    if ($input['loopstop'] == 'dates') {
      $output .= '
<div class="col-1 d-flex align-items-center justify-content-center">' . $calendarbutton['previous'] . '</div>
<div class="col-10 mx-0 px-0">
<!-- Dynamic Calendar -->
<div class="calendar text-center  mx-0 px-0">
<!-- Generate calendar radio buttons based on the number of days to display -->
';
      #global $qik;
      #$csd=$qik->encodeId($display_start_date->format('Ymd'));
      $csd = $display_start_date->format('Y-m-d');
      
    }

    $displaycounter = $tourcount = 0;

    
    while ($display_start_date < $display_end_date) :
      $showdate = false;
      $displaycounter++;
      if ($displaycounter > $length && $input['loopstop'] == 'dates') break;
      $display_calendar_day = explode('|', $display_start_date->format('M|d|D'));
      $btn_class = ($display_start_date->format('Y-m-d') == $birthdate->format('Y-m-d')) ? 'btn-success bg-success' : 'btn-primary';
      if (in_array($display_start_date->format('Y-m-d'), $tourlistdates)) {
        $btn_class = 'btn-outline-secondary booked';
        $tourcount++;
        if ($tourcount > $length && $input['loopstop'] == 'tours') break;
        $showdate = true;
      }

      if ($input['loopstop'] == 'dates' || $showdate) {
        $output .= '   <div class="form-check form-check-inline m-0 p-0">';
        if ($input['loopstop'] == 'dates') {
          $linkhref = $input['linkhref'] . $display_start_date->format('Y-m-d') . '&csd=' . $csd;
          /*
$output.='
<input class="form-check-input d-none" type="radio" name="calendar_date" 
id="date'. $display_start_date->format('Y-m-d').'" value="'. $display_start_date->format('Y-m-d').'">
<label class="btn calendarbtn '.$btn_class.' form-check-label" for="date'.$display_start_date->format('Y-m-d').'">
'."".$display_calendar_day[0]." ".$display_calendar_day[1]."<br>".$display_calendar_day[2]."".'
</label>';
*/
          $output .= '
<a href="' . $linkhref . '" class="btn calendarbtn ' . $btn_class . '">
' . "" . $display_calendar_day[0] . " " . $display_calendar_day[1] . "<br>" . $display_calendar_day[2] . "" . '
</a>
';
        }
        if ($input['loopstop'] == 'tours') {
          $linkhref = $input['linkhref'] . $display_start_date->format('Y-m-d');


          /*
          <!-- Bootstrap Card for Calendar -->
<div class="card text-center rounded-3" style="width: 10rem;">
    <div class="card-header text-white bg-danger"><span class="fs-6">Feb</span></div>
    <div class="card-body"><h5 class="card-title fs-6">21</h5></div>
</div>
*/

          $output .= '
          <a href="' . $linkhref . '" class="calendarbtn booked  text-decoration-none mb-2">
            <div class="calendar mx-1"><span class="calendar-month">' . $display_calendar_day[0] . ' ' . $display_calendar_day[1] . '</span><span class="calendar-day fs-10">' . $display_calendar_day[2] . '</div>
            </a>
            ';
          /*
                     
          $output .= '
<a href="' . $linkhref . '" class="btn calendarbtn btn-outline-secondary booked">
' . "" . $display_calendar_day[0] . " " . $display_calendar_day[1] . "<br>" . $display_calendar_day[2] . "" . '
</a>
';
*/
          $toutlist[] = $display_start_date->format('Y-m-d');
        }
        $output .= '
</div>
';
      }
      $display_start_date->modify('+1 day');
    endwhile;

    $output .= '
</div>
';

    if ($input['loopstop'] == 'dates') {
      $output .= '
</div>
<div class="col-1 d-flex align-items-center justify-content-center">' . $calendarbutton['next'] . '</div>
</div></div></div>
';
    }
    if ($input['displaytype'] == 'apilist') return $toutlist;
    else
      return $output;
  }






}
