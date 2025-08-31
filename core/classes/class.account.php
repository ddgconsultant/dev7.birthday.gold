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
      case 'phone':
        // For phone login, we need to look in bg_user_attributes table
        // The input should be just digits at this point
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawinput);
        
        // First find user_id from bg_user_attributes
        $sql = "SELECT u.* FROM bg_users u
                INNER JOIN bg_user_attributes ua ON u.user_id = ua.user_id
                WHERE ua.name = 'profile_phone_number' 
                AND ua.string_value = :phone 
                AND ua.type = 'profile' 
                AND ua.status = 'active'
                AND u.status = 'active'
                LIMIT 1";
        $params = ['phone' => $cleanPhone];
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
          $this->clearRememberMeCookies();
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
      $logintrackingdata['device_id'] = isset($deviceid) ? $deviceid : null;


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

  /**
   * Get user data by phone number from bg_user_attributes
   * @param string $phone The phone number to search for
   * @return array|false User data array or false if not found
   */
  public function getUserByPhone($phone) {
      // Clean phone number - remove all non-numeric characters
      $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
      
      // First find user_id from bg_user_attributes
      $sql = "SELECT user_id FROM bg_user_attributes 
              WHERE name = 'profile_phone_number' 
              AND string_value = :phone 
              AND type = 'profile' 
              AND status = 'active' 
              LIMIT 1";
      
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['phone' => $cleanPhone]);
      
      if ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
          // Found user, now get full user data
          return $this->getuserdata($result['user_id'], 'user_id');
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
  public function setLoginMethodPreference($method = 'email') {
    // Set a cookie to remember the user's preferred login method
    // Valid methods: 'email' or 'phone'
    if (!in_array($method, ['email', 'phone'])) {
      $method = 'email'; // Default to email if invalid
    }
    
    setcookie('bdgold_login_method', $method, [
      'expires' => time() + (86400 * 365), // 1 year
      'path' => '/',
      'domain' => '.birthday.gold',
      'secure' => true,
      'httponly' => true,
      'samesite' => 'Lax'
    ]);
    
    return true;
  }
  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getLoginMethodPreference() {
    // Get the user's preferred login method from cookie
    // Returns 'email' or 'phone', defaults to 'email'
    $method = $_COOKIE['bdgold_login_method'] ?? 'email';
    
    // Validate the method
    if (!in_array($method, ['email', 'phone'])) {
      $method = 'email';
    }
    
    return $method;
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
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM bg_user_attributes WHERE type = "referralcode" AND name = "code" AND description = :code');
        $stmt->execute([':code' => $finalCode]);
        $exists = $stmt->fetchColumn();
        if ($exists > 0) {
            $finalCode = $code . $count;
            $count++;
        }
    } while ($exists > 0);

    // Store the unique referral code for the current user - use manageReferralCode method instead
    return $this->manageReferralCode($current_user_data, 'update', $finalCode)['code'];
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




  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Clear all rememberme related cookies
   * Used when logging out or when deleting a device from login history
   */
  public function clearRememberMeCookies()
  {
    // Set cookies to expire in the past to delete them
    $expire_time = time() - 3600; // 1 hour ago
    $cookie_path = '/';
    $cookie_domain = ''; // Let PHP determine the domain
    $secure = true; // HTTPS only
    $httponly = true; // HTTP only, no JavaScript access
    
    // Clear all possible rememberme cookie names
    // Note: Different parts of the codebase may use different cookie names
    setcookie('bgralid', '', $expire_time, $cookie_path, $cookie_domain, $secure, $httponly);
    setcookie('bgraltoken', '', $expire_time, $cookie_path, $cookie_domain, $secure, $httponly);
    setcookie('bgdeviceid', '', $expire_time, $cookie_path, $cookie_domain, $secure, $httponly);
    setcookie('bg_device_id', '', $expire_time, $cookie_path, $cookie_domain, $secure, $httponly);
  }

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Get available upgrade options for a user/plan
   * 
   * @param array $options Optional parameters to override defaults:
   *   - user_id: User ID to check (defaults to current user)
   *   - product_id: Product ID to check (defaults to user's current product)
   *   - account_type: Account type to check (defaults to user's type)
   *   - account_plan: Account plan to check (defaults to user's plan)
   *   - debug: Enable debug mode to return additional info
   * 
   * @return array Contains:
   *   - available_plans: Array of available upgrade plans
   *   - is_upgradeable: Boolean if plan can be upgraded
   *   - upgrade_message: Custom message for non-upgradeable plans
   *   - is_grandfathered: Boolean if current plan is grandfathered
   *   - is_free_plan: Boolean if current plan is free
   *   - is_top_tier: Boolean if at highest tier
   *   - debug_info: Debug information (if debug=true)
   */
  public function getUpgradeOptions($options = []) {
    global $website, $current_user_data;
    
    // Default values from current user
    $user_id = $options['user_id'] ?? ($current_user_data['user_id'] ?? 0);
    $current_product_id = $options['product_id'] ?? ($current_user_data['account_product_id'] ?? null);
    $current_type = $options['account_type'] ?? ($current_user_data['account_type'] ?? 'user');
    $current_plan = $options['account_plan'] ?? ($current_user_data['account_plan'] ?? 'free');
    $debug_mode = $options['debug'] ?? false;
    
    // Initialize return data
    $result = [
      'available_plans' => [],
      'is_upgradeable' => true,
      'upgrade_message' => '',
      'is_grandfathered' => false,
      'is_free_plan' => false,
      'is_top_tier' => false,
      'current_plan_display' => '',
      'debug_info' => []
    ];
    
    // Get current plan display name
    $current_plan_display = ucfirst(str_replace(['user_', 'parental_', 'minor_', 'business_', 'family_'], '', $current_plan));
    if ($current_product_id) {
      $sql = "SELECT account_name FROM bg_products WHERE id = :id AND status = 'active'";
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['id' => $current_product_id]);
      $plan_result = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($plan_result && !empty($plan_result['account_name'])) {
        $current_plan_display = $plan_result['account_name'];
      }
    }
    $result['current_plan_display'] = $current_plan_display;
    
    // Check if plan is free
    $result['is_free_plan'] = (strpos($current_plan, 'free') !== false || $current_plan == 'free');
    
    // Check if at top tier (including lifetime plans)
    $plan_lower = strtolower($current_plan);
    if (strpos($plan_lower, 'platinum') !== false || 
        strpos($plan_lower, 'gold') !== false ||
        strpos($plan_lower, 'life') !== false ||
        strpos($plan_lower, 'lifetime') !== false) {
      $result['is_top_tier'] = true;
    }
    
    // Use system's plan version setting
    $version_to_use = $website['plan_version'] ?? 'v7';
    
    // Check if current plan has upgradeable restrictions
    $allowed_upgrades = [];
    if ($current_product_id) {
      // Check if plan is explicitly non-upgradeable
      $sql = "SELECT value FROM bg_product_features 
              WHERE product_id = :product_id 
              AND name = 'upgradeable' 
              AND status = 'active' 
              LIMIT 1";
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['product_id' => $current_product_id]);
      $feature_result = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if ($feature_result && $feature_result['value']) {
        $value = trim(strtolower($feature_result['value']));
        // Check for explicit non-upgradeable values
        if ($value === 'false' || $value === 'no' || $value === '0' || $value === 'none') {
          $result['is_upgradeable'] = false;
        } elseif (strpos($value, '[') === 0) {
          $allowed_upgrades = json_decode($feature_result['value'], true) ?? [];
        } else {
          $allowed_upgrades = array_map('trim', explode(',', $feature_result['value']));
        }
      }
      
      // Check for upgrade message
      $sql = "SELECT value FROM bg_product_features 
              WHERE product_id = :product_id 
              AND name = 'upgrade_message' 
              AND status = 'active' 
              LIMIT 1";
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['product_id' => $current_product_id]);
      $msg_result = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($msg_result && $msg_result['value']) {
        $result['upgrade_message'] = $msg_result['value'];
      }
      
      // Check if grandfathered
      $sql = "SELECT version FROM bg_products WHERE id = :product_id AND status = 'active' LIMIT 1";
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['product_id' => $current_product_id]);
      $version_result = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($version_result && $version_result['version'] && !in_array($version_result['version'], ['v7', 'v3'])) {
        $result['is_grandfathered'] = true;
      }
    }
    
    // If not upgradeable, return early
    if (!$result['is_upgradeable']) {
      if ($debug_mode) {
        $result['debug_info'] = [
          'reason' => 'Plan explicitly marked as non-upgradeable',
          'current_product_id' => $current_product_id
        ];
      }
      return $result;
    }
    
    // Determine target account types for upgrades
    // Hierarchy: user < parental/family < business
    if ($current_type == 'minor') {
      $target_account_types = ['user', 'parental', 'family', 'business'];
    } elseif ($current_type == 'user') {
      $target_account_types = ['user', 'parental', 'family', 'business'];
    } elseif ($current_type == 'parental') {
      $target_account_types = ['parental', 'family', 'business'];
    } elseif ($current_type == 'family') {
      $target_account_types = ['family', 'parental', 'business'];
    } elseif ($current_type == 'business') {
      $target_account_types = ['business'];
    } else {
      $target_account_types = ['user', 'parental', 'family', 'business'];
    }
    
    // Build query for available plans
    $type_placeholders = array_map(function($i) { return ':type' . $i; }, range(0, count($target_account_types) - 1));
    $type_in_clause = implode(', ', $type_placeholders);
    
    $sql = "SELECT DISTINCT p.id, p.account_name, p.account_plan, p.account_type, p.version,
            p.price, p.billing_cycle,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'billing_period' AND status = 'active' LIMIT 1) as billing_period,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'description' AND status = 'active' LIMIT 1) as description,
            (SELECT value FROM bg_product_features WHERE product_id = p.id AND name = 'enrollments_per_period' AND status = 'active' LIMIT 1) as enrollments
            FROM bg_products p
            WHERE p.status = 'active' 
            AND p.account_type IN (" . $type_in_clause . ")
            AND p.version = :version
            AND p.id != :current_product_id
            ORDER BY 
            CASE p.account_plan 
                WHEN 'parental_free' THEN 1
                WHEN 'user_free' THEN 1
                WHEN 'family_free' THEN 1
                WHEN 'parental_plus' THEN 2
                WHEN 'user_plus' THEN 2  
                WHEN 'parental_gold' THEN 3
                WHEN 'user_gold' THEN 3
                WHEN 'family_gold' THEN 3
                WHEN 'parental_platinum' THEN 4
                WHEN 'user_platinum' THEN 4
                WHEN 'business_bronze' THEN 1
                WHEN 'business_silver' THEN 2
                WHEN 'business_gold' THEN 3
                WHEN 'business_platinum' THEN 4
                ELSE 5
            END";
    
    $params = [
      'version' => $version_to_use,
      'current_product_id' => $current_product_id ?? 0
    ];
    
    // Add type parameters
    foreach ($target_account_types as $i => $type) {
      $params['type' . $i] = $type;
    }
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    $all_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter available plans based on upgrade logic
    $available_plans = [];
    
    // Determine current tier
    $current_tier = 0;
    if (strpos($current_plan, 'free') !== false || $current_plan == 'free') $current_tier = 1;
    elseif (strpos($current_plan, 'plus') !== false) $current_tier = 2;
    elseif (strpos($current_plan, 'gold') !== false) $current_tier = 3;
    elseif (strpos($current_plan, 'platinum') !== false) $current_tier = 4;
    
    foreach ($all_plans as $plan) {
      // Determine plan tier - check both account_plan and account_name for tier keywords
      $plan_tier = 0;
      $plan_check = strtolower($plan['account_plan'] . ' ' . ($plan['account_name'] ?? ''));
      
      if (strpos($plan_check, 'trial') !== false) $plan_tier = 0.5;
      elseif (strpos($plan_check, 'free') !== false) $plan_tier = 1;
      elseif (strpos($plan_check, 'plus') !== false || strpos($plan_check, 'bronze') !== false) $plan_tier = 2;
      elseif (strpos($plan_check, 'gold') !== false || strpos($plan_check, 'silver') !== false) $plan_tier = 3;
      elseif (strpos($plan_check, 'platinum') !== false) $plan_tier = 4;
      
      // Business plans default to tier 3 if not set
      if ($plan_tier == 0 && $plan['account_type'] == 'business') {
        $plan_tier = 3;
      }
      
      // Only show plans that are upgrades (higher tier)
      // If current tier is 0 (unknown/legacy), don't show plans unless explicitly allowed
      if ($current_tier > 0 && $plan_tier > $current_tier) {
        // Format price from cents to dollars
        $plan['price_formatted'] = number_format(($plan['price'] ?? 0) / 100, 2, '.', '');
        
        // Determine billing period display
        $plan['period_display'] = $plan['billing_period'] ?? '';
        if (empty($plan['period_display'])) {
          $plan['period_display'] = ($plan['billing_cycle'] == 'one_time') ? 'lifetime' : 'month';
        }
        
        $available_plans[] = $plan;
      }
    }
    
    // Apply upgradeable restrictions if any
    // Exception: Free plans should show all upgrades unless explicitly set to 'false'/'no'
    if (!empty($allowed_upgrades) && !$result['is_free_plan']) {
      // Apply restrictions for non-free plans
      $available_plans = array_filter($available_plans, function($plan) use ($allowed_upgrades) {
        return in_array($plan['id'], $allowed_upgrades) || in_array($plan['account_plan'], $allowed_upgrades);
      });
    } elseif (!empty($allowed_upgrades) && $result['is_free_plan']) {
      // For free plans, only apply restrictions if they seem reasonable (more than 1 option)
      if (count($allowed_upgrades) > 1) {
        $available_plans = array_filter($available_plans, function($plan) use ($allowed_upgrades) {
          return in_array($plan['id'], $allowed_upgrades) || in_array($plan['account_plan'], $allowed_upgrades);
        });
      }
      // If free plan has only 1 upgrade option, ignore it and show all valid upgrades
    }
    
    $result['available_plans'] = array_values($available_plans); // Re-index array
    
    // Add debug info if requested
    if ($debug_mode) {
      $result['debug_info'] = [
        'current_plan' => $current_plan,
        'current_type' => $current_type,
        'current_product_id' => $current_product_id,
        'current_tier' => $current_tier,
        'target_types' => $target_account_types,
        'version_used' => $version_to_use,
        'total_plans_found' => count($all_plans),
        'allowed_upgrades' => $allowed_upgrades,
        'sql_params' => $params
      ];
    }
    
    return $result;
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Require selective 2FA for sensitive operations on trusted devices
   * Call this at the top of protected pages to enforce periodic 2FA verification
   * 
   * @param string $redirect_after_success URL to redirect to after successful 2FA
   * @param int $valid_hours How many hours 2FA verification is valid (default: 168 = 7 days)
   */
  public function requireSelectiveTwoFactor($redirect_after_success = null, $valid_hours = 168) {
    global $session, $database, $twofactorauth;
    
    // Only check if user is logged in
    if (!$this->isactive()) {
      return;
    }
    
    $user_id = $this->session->get('current_user_id');
    
    // Check if user has 2FA enabled
    $sql = 'SELECT string_value as auth_method FROM bg_user_attributes 
            WHERE user_id = :user_id 
            AND type = "2fa_method" 
            AND status = "active"';
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $user_2fa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no 2FA enabled, allow access
    if (!$user_2fa || empty($user_2fa['auth_method'])) {
      return;
    }
    
    // Check if this is a trusted device (remember me login)
    $is_trusted_device = !empty($_COOKIE['bgralid']) && !empty($_COOKIE['bgraltoken']) && !empty($_COOKIE['bgdeviceid']);
    
    // If not a trusted device, don't interfere (regular 2FA will handle it)
    if (!$is_trusted_device) {
      return;
    }
    
    // Check when 2FA was last verified on this device
    $device_id = $_COOKIE['bgdeviceid'] ?? '';
    $sql = 'SELECT modify_dt FROM bg_user_attributes 
            WHERE user_id = :user_id 
            AND type = "selective_2fa_verified" 
            AND name = :device_id 
            AND status = "active"';
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'device_id' => $device_id]);
    $last_verification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If verified within valid hours, allow access
    if ($last_verification) {
      $hours_since = (time() - strtotime($last_verification['modify_dt'])) / 3600;
      if ($hours_since < $valid_hours) {
        return; // Still valid, allow access
      }
    }
    
    // Need 2FA verification - set up session and redirect
    $current_url = $redirect_after_success ?? $_SERVER['REQUEST_URI'];
    
    // Get user contact info for 2FA
    $sql = 'SELECT email, phone_number FROM bg_users WHERE user_id = :user_id';
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $user_contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // For TOTP method, get the secret
    $totp_secret = '';
    if ($user_2fa['auth_method'] === 'Highly Secure') {
      $sql = 'SELECT string_value FROM bg_user_attributes 
             WHERE user_id = :user_id 
             AND type = "2fa_secret" 
             AND status = "active"';
      $stmt = $database->prepare($sql);
      $stmt->execute(['user_id' => $user_id]);
      $secret_result = $stmt->fetch(PDO::FETCH_ASSOC);
      $totp_secret = $secret_result['string_value'] ?? '';
    }
    
    // Store selective 2FA session data
    $pending_2fa_data = [
      'user_id' => $user_id,
      'method' => $user_2fa['auth_method'],
      'email' => $user_contact['email'] ?? '',
      'phone' => $user_contact['phone_number'] ?? '',
      'secret' => $totp_secret,
      'redirect_url' => $current_url,
      'timestamp' => time(),
      'code_sent' => false,
      'selective_2fa' => true, // Flag to indicate this is selective 2FA
      'device_id' => $device_id
    ];
    
    $session->set('pending_2fa', $pending_2fa_data);
    
    // Redirect to 2FA verification
    header('Location: /verify-2fa.php');
    exit;
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Calculate password strength score and category
   * @param string $password The plain text password
   * @return array Contains strength score (0-100), category (weak/fair/good/strong), and color
   */
  public function calculatePasswordStrength($password) {
    $score = 0;
    $length = strlen($password);
    
    // Length scoring (0-40 points)
    if ($length >= 8) $score += 10;
    if ($length >= 10) $score += 10; 
    if ($length >= 12) $score += 10;
    if ($length >= 16) $score += 10;
    
    // Character variety (0-60 points)
    if (preg_match('/[a-z]/', $password)) $score += 10; // lowercase
    if (preg_match('/[A-Z]/', $password)) $score += 10; // uppercase
    if (preg_match('/[0-9]/', $password)) $score += 10; // numbers
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 15; // special chars
    if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $score += 5; // common special chars
    if (preg_match('/[^\x20-\x7E]/', $password)) $score += 10; // unicode/extended chars
    
    // Complexity bonus
    $char_types = 0;
    if (preg_match('/[a-z]/', $password)) $char_types++;
    if (preg_match('/[A-Z]/', $password)) $char_types++;
    if (preg_match('/[0-9]/', $password)) $char_types++;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $char_types++;
    
    if ($char_types >= 3 && $length >= 10) $score += 10; // complexity bonus
    
    // Penalize common patterns
    if (preg_match('/(.)\1{2,}/', $password)) $score -= 10; // repeated chars
    if (preg_match('/123|abc|password|qwerty/i', $password)) $score -= 15; // common sequences
    
    // Ensure score stays in bounds
    $score = max(0, min(100, $score));
    
    // Determine category and color
    if ($score >= 80) {
        $category = 'Strong';
        $color = 'success';
    } elseif ($score >= 60) {
        $category = 'Good';
        $color = 'info';
    } elseif ($score >= 40) {
        $category = 'Fair';
        $color = 'warning';
    } else {
        $category = 'Weak';
        $color = 'danger';
    }
    
    return [
        'score' => $score,
        'category' => $category,
        'color' => $color,
        'percentage' => $score,
        'length' => $length
    ];
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Get password requirements for frontend validation
   * Returns standardized password requirements used across the site
   * @return array Password requirements with descriptions and validation rules
   */
  public function getPasswordRequirements() {
    return [
      'minimum_length' => 8,
      'requirements' => [
        [
          'id' => 'length',
          'description' => 'At least 8 characters',
          'pattern' => '.{8,}',
          'points' => 10
        ],
        [
          'id' => 'lowercase',
          'description' => 'One lowercase letter (a-z)',
          'pattern' => '[a-z]',
          'points' => 10
        ],
        [
          'id' => 'uppercase', 
          'description' => 'One uppercase letter (A-Z)',
          'pattern' => '[A-Z]',
          'points' => 10
        ],
        [
          'id' => 'number',
          'description' => 'One number (0-9)',
          'pattern' => '[0-9]',
          'points' => 10
        ],
        [
          'id' => 'special',
          'description' => 'One special character (!@#$%^&*)',
          'pattern' => '[^a-zA-Z0-9]',
          'points' => 15
        ]
      ],
      'strength_thresholds' => [
        'weak' => ['min' => 0, 'max' => 39, 'color' => 'danger'],
        'fair' => ['min' => 40, 'max' => 59, 'color' => 'warning'], 
        'good' => ['min' => 60, 'max' => 79, 'color' => 'info'],
        'strong' => ['min' => 80, 'max' => 100, 'color' => 'success']
      ],
      'minimum_score_for_submit' => 60 // Good or better required
    ];
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Track password change with strength analysis
   * @param int $user_id User ID
   * @param string $password Plain text password (for strength analysis)
   * @param string $context Context: 'creation', 'change', 'reset'
   */
  public function trackPasswordChange($user_id, $password, $context = 'change') {
    global $database;
    
    // Calculate password strength
    $password_strength = $this->calculatePasswordStrength($password);
    
    // Prepare tracking data
    $password_data = json_encode([
        'message' => 'Password ' . $context . ' successful',
        'context' => $context,
        'strength_score' => $password_strength['score'],
        'strength_category' => $password_strength['category'],
        'strength_color' => $password_strength['color'],
        'length' => $password_strength['length']
    ]);
    
    // Store tracking record
    $sql = "INSERT INTO bg_user_attributes (user_id, type, name, description, status, create_dt, modify_dt) 
            VALUES (:user_id, 'security', 'password_changed', :password_data, 'active', NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            description = VALUES(description), modify_dt = NOW(), status = 'active'";
    
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'password_data' => $password_data]);
    
    // Add security notification using mail class
    global $mail;
    if (isset($mail) && is_object($mail)) {
      $notification_title = $context === 'creation' ? '🎉 Welcome to Birthday.Gold!' : '🔐 Password Updated';
      $notification_message = $context === 'creation' 
        ? "Your account was created with a {$password_strength['category']} password. You're all set to start collecting birthday rewards!"
        : "Your password was successfully updated with {$password_strength['category']} strength. Your account security has been improved.";
      
      $mail->addNotification(
        $user_id, 
        'security_password', 
        $notification_title, 
        $notification_message, 
        [
          'alert_class' => 'success',
          'priority' => 'normal',
          'category' => 'security',
          'end_dt' => '30d'
        ]
      );
    }
    
    return $password_strength;
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Get recent account activity for security monitoring
   * @param int $user_id User ID (optional, defaults to current user)
   * @param int $limit Number of activities to return (default: 10)
   * @param int $days_back How many days back to look (default: 30)
   * @return array Recent activity events with timestamps, types, and details
   */
  public function getAccountActivity($user_id = null, $limit = 10, $days_back = 30) {
    global $database;
    
    // Use current user if not specified
    if ($user_id === null) {
      $user_id = $this->session->get('current_user_id');
    }
    
    if (!$user_id) {
      return [];
    }
    
    $activities = [];
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_back} days"));
    
    // Get login activities from bg_sessiontracking
    $sql = "SELECT create_dt, type, name, ip, tracking_data, server_data, request_data
            FROM bg_sessiontracking 
            WHERE user_id = :user_id 
            AND create_dt >= :cutoff_date
            AND type IN ('LOGIN-success_user', '2fa_verification_required', 'bg_rememberme_attempt', 'bg_rememberme_loginsuccess')
            ORDER BY create_dt DESC 
            LIMIT :limit";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([
      'user_id' => $user_id, 
      'cutoff_date' => $cutoff_date,
      'limit' => $limit
    ]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $activity = $this->parseActivityRow($row);
      if ($activity) {
        $activities[] = $activity;
      }
    }
    
    // Get security events from bg_user_attributes
    $sql = "SELECT modify_dt as create_dt, name, description
            FROM bg_user_attributes 
            WHERE user_id = :user_id 
            AND type = 'security'
            AND modify_dt >= :cutoff_date
            ORDER BY modify_dt DESC 
            LIMIT :limit";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([
      'user_id' => $user_id,
      'cutoff_date' => $cutoff_date, 
      'limit' => $limit
    ]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $activity = $this->parseSecurityEvent($row);
      if ($activity) {
        $activities[] = $activity;
      }
    }
    
    // Sort all activities by timestamp (most recent first)
    usort($activities, function($a, $b) {
      return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    return array_slice($activities, 0, $limit);
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Parse session tracking row into standardized activity format
   * @param array $row Raw database row from bg_sessiontracking
   * @return array|null Formatted activity data or null if not displayable
   */
  private function parseActivityRow($row) {
    $tracking_data = json_decode($row['tracking_data'] ?? '{}', true);
    $server_data = json_decode($row['server_data'] ?? '{}', true);
    $request_data = json_decode($row['request_data'] ?? '{}', true);
    
    $user_agent = $server_data['HTTP_USER_AGENT'] ?? $request_data['user_agent'] ?? '';
    $browser = $this->parseBrowserFromUserAgent($user_agent);
    $ip = $row['ip'] ?? 'Unknown';
    
    switch ($row['type']) {
      case 'LOGIN-success_user':
        return [
          'type' => 'login_success',
          'icon' => 'check-circle-fill',
          'color' => 'success',
          'title' => 'Successful login',
          'details' => "from {$browser['browser']} on {$browser['os']}",
          'timestamp' => $row['create_dt'],
          'ip' => $ip,
          'location' => $tracking_data['location'] ?? null
        ];
        
      case 'bg_rememberme_loginsuccess':
        return [
          'type' => 'trusted_login',
          'icon' => 'shield-check',
          'color' => 'info', 
          'title' => 'Trusted device login',
          'details' => "from {$browser['browser']} on {$browser['os']}",
          'timestamp' => $row['create_dt'],
          'ip' => $ip
        ];
        
      case '2fa_verification_required':
        return [
          'type' => '2fa_required',
          'icon' => 'phone-fill',
          'color' => 'warning',
          'title' => '2FA verification required',
          'details' => "from {$browser['browser']} on {$browser['os']}",
          'timestamp' => $row['create_dt'],
          'ip' => $ip
        ];
        
      default:
        return null;
    }
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Parse security event into standardized activity format
   * @param array $row Raw database row from bg_user_attributes (security type)
   * @return array|null Formatted activity data or null if not displayable
   */
  private function parseSecurityEvent($row) {
    $data = json_decode($row['description'] ?? '{}', true);
    
    switch ($row['name']) {
      case 'password_changed':
        $context = $data['context'] ?? 'change';
        $strength = $data['strength_category'] ?? 'Unknown';
        
        if ($context === 'creation') {
          $title = 'Account created';
          $details = "with {$strength} password";
        } else {
          $title = 'Password updated';
          $details = "new {$strength} password";
        }
        
        return [
          'type' => 'password_change',
          'icon' => 'key-fill',
          'color' => 'info',
          'title' => $title,
          'details' => $details,
          'timestamp' => $row['create_dt']
        ];
        
      case 'selective_2fa_verified':
        return [
          'type' => '2fa_verified',
          'icon' => 'shield-lock',
          'color' => 'success',
          'title' => 'Selective 2FA verified',
          'details' => 'for sensitive page access',
          'timestamp' => $row['create_dt']
        ];
        
      default:
        return null;
    }
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Parse browser and OS from user agent string
   * @param string $user_agent User agent string
   * @return array Browser and OS information
   */
  private function parseBrowserFromUserAgent($user_agent) {
    $browser = 'Unknown Browser';
    $os = 'Unknown OS';
    
    // Common browsers
    if (strpos($user_agent, 'Chrome') !== false && strpos($user_agent, 'Edg') === false) {
      $browser = 'Chrome';
    } elseif (strpos($user_agent, 'Firefox') !== false) {
      $browser = 'Firefox';
    } elseif (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Chrome') === false) {
      $browser = 'Safari';
    } elseif (strpos($user_agent, 'Edg') !== false) {
      $browser = 'Edge';
    } elseif (strpos($user_agent, 'Opera') !== false || strpos($user_agent, 'OPR') !== false) {
      $browser = 'Opera';
    }
    
    // Operating systems
    if (strpos($user_agent, 'Windows') !== false) {
      $os = 'Windows';
    } elseif (strpos($user_agent, 'Mac OS X') !== false || strpos($user_agent, 'macOS') !== false) {
      $os = 'macOS';
    } elseif (strpos($user_agent, 'Linux') !== false) {
      $os = 'Linux';
    } elseif (strpos($user_agent, 'iPhone') !== false || strpos($user_agent, 'iPad') !== false) {
      $os = 'iOS';
    } elseif (strpos($user_agent, 'Android') !== false) {
      $os = 'Android';
    }
    
    return ['browser' => $browser, 'os' => $os];
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Format timestamp into human-readable "time ago" format
   * @param string $timestamp Database timestamp
   * @return string Formatted time ago string
   */
  public function formatTimeAgo($timestamp) {
    $time_ago = time() - strtotime($timestamp);
    
    if ($time_ago < 60) {
      return 'Just now';
    } elseif ($time_ago < 3600) {
      $minutes = floor($time_ago / 60);
      return $minutes . ' minute' . ($minutes == 1 ? '' : 's') . ' ago';
    } elseif ($time_ago < 86400) {
      $hours = floor($time_ago / 3600);
      return $hours . ' hour' . ($hours == 1 ? '' : 's') . ' ago';
    } elseif ($time_ago < 604800) {
      $days = floor($time_ago / 86400);
      return $days . ' day' . ($days == 1 ? '' : 's') . ' ago';
    } else {
      return date('M j, Y', strtotime($timestamp));
    }
  }

  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Get account activity summary for security assessment
   * @param int $user_id User ID (optional, defaults to current user)  
   * @param int $days_back How many days back to analyze (default: 7)
   * @return array Security summary with suspicious activity indicators
   */
  public function getSecuritySummary($user_id = null, $days_back = 7) {
    global $database;
    
    // Use current user if not specified
    if ($user_id === null) {
      $user_id = $this->session->get('current_user_id');
    }
    
    if (!$user_id) {
      return ['status' => 'unknown', 'message' => 'No user data'];
    }
    
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_back} days"));
    
    // Count login attempts and successes
    $sql = "SELECT type, COUNT(*) as count
            FROM bg_sessiontracking 
            WHERE user_id = :user_id 
            AND create_dt >= :cutoff_date
            AND type IN ('LOGIN-success_user', 'LOGIN-failed', '2fa_verification_required')
            GROUP BY type";
    
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'cutoff_date' => $cutoff_date]);
    
    $login_stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $login_stats[$row['type']] = $row['count'];
    }
    
    $successful_logins = $login_stats['LOGIN-success_user'] ?? 0;
    $failed_logins = $login_stats['LOGIN-failed'] ?? 0;
    $two_fa_required = $login_stats['2fa_verification_required'] ?? 0;
    
    // Assess security status
    if ($failed_logins > 10 || ($failed_logins > 3 && $successful_logins == 0)) {
      $status = 'suspicious';
      $message = 'Suspicious login activity detected';
      $status_class = 'status-inactive';
    } elseif ($failed_logins > 3) {
      $status = 'warning';
      $message = 'Some failed login attempts';  
      $status_class = 'status-warning';
    } else {
      $status = 'secure';
      $message = 'No suspicious activity';
      $status_class = 'status-active';
    }
    
    return [
      'status' => $status,
      'message' => $message,
      'status_class' => $status_class,
      'stats' => [
        'successful_logins' => $successful_logins,
        'failed_logins' => $failed_logins,
        'two_fa_events' => $two_fa_required
      ],
      'days_analyzed' => $days_back
    ];
  }


}
