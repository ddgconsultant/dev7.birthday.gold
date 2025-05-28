<?PHP

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require_once $dir['vendor'] . '/autoload.php';


# ##--------------------------------------------------------------------------------------------------------------------------------------------------

class Mail
{

  protected $mail;

  public function __construct($mailConfig)
  {

    // Create a new PHPMailer instance


    $this->mail = new PHPMailer();

    // Set mail server configuration
    $this->mail->isSMTP();
    $this->mail->Host = $mailConfig['MAIL_HOST'];
    $this->mail->SMTPAuth = $mailConfig['MAIL_SMTPAUTH'];
    $this->mail->Username = $mailConfig['MAIL_USERNAME'];
    $this->mail->Password = $mailConfig['MAIL_PASSWORD'];

    if ($mailConfig['MAIL_ENCRYPTION'] != '') {
      $this->mail->SMTPSecure = $mailConfig['MAIL_ENCRYPTION'];
    }

    $this->mail->Port = $mailConfig['MAIL_PORT'];
  }

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function sendmail($details)
  {
    if (!isset($details['donottrack'])) $details['donottrack']=false;
    // Set up email parameters
    $details['to'] = $details['to'] ?? $details['toemail'];
    $to = is_array($details['to']) ? $details['to'] : [$details['to'], $details['to']];  // use email/name if provided if not then make the name the same as the email

    $details['from'] = $details['from'] ?? ['noreply@birthday.gold', 'birthday.gold-noreply'];
    $from = is_array($details['from']) ? $details['from'] : [$details['from'], $details['from']];

    // Now you can proceed to configure and send your email with the set values
    $attachment = $details['attachment'] ?? '';

    $subject = $details['subject'] ?? 'A Message from birthday.gold';
    $body = $details['body'] ?? 'We just wanted to say hi!';

    // Check if tracking is allowed
    if (isset($details['notificationid']) && empty($details['donottrack'])) {
      global $qik;
      $tracking_url = 'https://birthday.gold/mtrk?i=' . $qik->encodeId($details['notificationid']);
      $tracking_pixel = '<img src="' . $tracking_url . '" alt="" width="1" height="1" style="display:none;">';

      // Append the tracking pixel to the email body
      $body .= $tracking_pixel;
    }


    try {

      $this->mail->clearAddresses();
      $this->mail->clearAttachments();

      //Recipients
      $this->mail->setFrom($from[0], $from[1]);
      $this->mail->addAddress($to[0], $to[1]);

      // Attachments
      if ($attachment != '')  $this->mail->addAttachment($attachment);         // Add attachments

      // Set CharSet to UTF-8
      $this->mail->CharSet = 'UTF-8';

      //Content
      $this->mail->isHTML(true);
      $this->mail->Subject =  $subject; #'Here is the subject';
      $this->mail->Body    = $body; #'This is the HTML message body <b>in bold!</b>';

      $result = $this->mail->send();

      #echo 'Message has been sent';
    } catch (Exception $e) {
      $processingerror = 'Message could not be sent. Mailer Error: ' . $this->mail->ErrorInfo;
    }

    $results = array('mail_sent' => $result, 'details' => $details);
    if (!empty($processingerror)) $results['processingerror'] = $processingerror;
    session_tracking('sendsent', $results);
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function emailbutton($label = '', $url = '')
  {

    $output = '<table border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td align="center" style="border-radius: 3px;" bgcolor="goldenrod">
        <a href="' . $url . '" style="font-size: 18px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: #fff; text-decoration: none; color: #ffffff; text-decoration: none; padding: 15px 20px; border-radius: 3px; border: 1px solid goldenrod; display: inline-block;">
          ' . $label . '
        </a>
      </td>
    </tr>
  </table>';
    return $output;
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function sendVerificationEmail($input)
  {
    global $dir, $app, $qik;
    $output = $verificationcode_tag = '';
  
    if (!$qik->checkvariables($input, ['toemail', 'fullname', 'validatelink', 'validationcode'])){      
      session_tracking('sendVerificationEmail_error!!', 'missing fields');
      return false;
    }

    include($dir['blade'] . '/email/email-template_basic.inc');
 
    if (is_array($input['toemail'])) {
      // Handle array case
      foreach ($input['toemail'] as $email) {
          if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
              $message['toemail'] = $email;
              break;
          }
      }
  } else {
      // Handle non-array case
      $message['toemail'] = $input['toemail'];
  }
    

    $message['subject'] = 'birthday.gold Email Account Validation';
    if (isset($input['validationcode'])) {
      $verificationcode_tag = '<h3>Or use this VALIDATION CODE on the website: </h3><h2 style="font-family: Courier, monospace; white-space: pre-wrap;"><b>' . $input['validationcode'] . '</b></h2><br><br>';
    }
    $message['body'] = '
<!-- Greeting -->
<h1 style="margin-top: 0; color: navy; font-size: 19px; font-weight: bold; text-align: left;">
Hello, ' . $input['fullname'] . '
</h1>

<!-- Intro -->
<p style="margin-top: 0; color: navy; font-size: 16px; line-height: 1.5em;">
Sweet! You are signed up. Now validate your account by clicking the button.
' . $this->emailbutton('Validate Your Account', $input['validatelink']) . '
</p>
<br><br>
{{VERIFICATION_CODE}}
<p style="margin-top: 0; color: navy; font-size: 16px; line-height: 1.5em;">
If that link doesn\'t work for you, you can copy and paste this URL into your browser
</p>
<pre>' . $input['validatelink'] . '</pre>


<br><br><br>
<!-- Salutation -->
<p style="margin-top: 0; color: navy; font-size: 16px; line-height: 1.5em;">
Cheers!<br>birthday.gold
</p>
';
    $shortcode = $app->getshortcode($input['validatelink']);
    $search = array('{{VERIFICATION_CODE}}', $input['validatelink']);
    $replace = array($verificationcode_tag, $shortcode['shorturl']);

    $message['body'] = str_replace($search, $replace, $message['body']);

    $message['body'] = str_replace('{{MESSAGE_CONTENT}}', $message['body'], $output);
    $message['body'] = str_replace('{{TO_EMAIL}}', $message['toemail'], $message['body']);

    $this->sendmail($message);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function sendOnlineContactForm($input)
  {
    global $dir, $qik;
    $output = $verificationcode_tag = '';

    if (!$qik->checkvariables($input, ['toemail', 'from'])) {      
      session_tracking('sendOnlineContactForm_error!!', 'missing fields');
      return false;
    }

    include($dir['blade'] . '/email/email-template_basic.inc');


    $message['to'] = $input['to']?? $input['toemail']??'' ;    
    $message['toemail'] = $input['toemail']?? $input['to']??'' ;
 #   $message['toemail'] = '';

if (isset($input['toemail'])) {
    $message['toemail'] = is_array($input['toemail'])
        ? $this->findEmailInArray($input['toemail'])
        : $input['toemail'];
} elseif (isset($input['to'])) {
    $message['toemail'] = is_array($input['to'])
        ? $this->findEmailInArray($input['to'])
        : $input['to'];
}

    $message['from'] = $input['from'];
    $message['subject'] = 'birthday.gold ONLINE MESSAGE - ' . $input['from'][0];
    $message['body'] = '
<!-- Greeting -->
<h1 style="margin-top: 0; color: #2F3133; font-size: 19px; font-weight: bold; text-align: left;">
An online message: 
</h1>

<!-- Intro -->
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
' . $input['body'] . '
</p>
';

    $message['body'] = str_replace('{{MESSAGE_CONTENT}}', $message['body'], $output);
    $message['body'] = str_replace('{{TO_EMAIL}}', $message['toemail'], $message['body']);
    #$shortcode=$app->getshortcode($input['validatelink']);
    #$search=array('{{VERIFICATION_CODE}}', $input['validatelink']);
    #$replace=array($verificationcode_tag, $shortcode['shorturl']);

    #$message['body']=str_replace($search,$replace, $message['body']);

    $result = $this->sendmail($message);
    $results['status'] = $result;
    $results['output'] = $output;
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
// Function to find the first valid email address in an array
function findEmailInArray($array)
{
    foreach ($array as $item) {
        if (filter_var($item, FILTER_VALIDATE_EMAIL)) {
            return $item; // Return the first valid email address
        }
    }
    return ''; // Default to empty if no email address is found
}

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function sendEnrollmentQueueEmail($input)
  {
    global $dir, $app, $website;
    $output = $verificationcode_tag = '';
    include($dir['blade'] . '/email/email-template_basic.inc');
    $message['toemail'] = $input['toemail'];
    $message['subject'] = 'birthday.gold Enrollment Queue: ' . date('M d, Y');
    $message['body'] = '
<!-- Greeting -->
<h1 style="margin-top: 0; color: #2F3133; font-size: 19px; font-weight: bold; text-align: left;">
Hello, ' . $input['fullname'] . '
</h1>

<!-- Intro -->
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
YEA!!! </B> We are ready to enroll you in the '.$website['biznames'].' that you selected.
Your enrollment will start within the next hour and you will need to have access to your 
phone and email in case some of the '.$website['biznames'].' need you to click or confirm any details.
If you aren\'t in a place to receive messages/emails right now... you can click the 
' . $this->emailbutton('Delay My Enrollment', $input['validatelink']) . '
button, to delay the enrollment to the next business day.  If you click it, it will automatically reschedule you, 
and you\'ll receive a new email just like this. Of which you will be able to delay your enrollments up to seven times.
</p>
<p>If you do nothing, your enrollment will automatically start.</p>
<p>We are so exciting to help celebrate your birthday!</p>
<br><br>
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
If that link doesn\'t work for you, you can copy and paste this URL into your browser
</p>
<pre>' . $input['validatelink'] . '</pre>


<br><br><br>
<!-- Salutation -->
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
Regards,<br>birthday.gold
</p>
';

    $message['body'] = str_replace('{{MESSAGE_CONTENT}}', $message['body'], $output);


    $shortcode = $app->getshortcode($input['validatelink']);
    $search = array('{{VERIFICATION_CODE}}', $input['validatelink']);
    $replace = array($verificationcode_tag, $shortcode['shorturl']);

    $message['body'] = str_replace($search, $replace, $message['body']);
    $message['body'] = str_replace('{{TO_EMAIL}}', $message['toemail'], $message['body']);


    $result = $this->sendmail($message);
    $results['status'] = $result;
    $results['output'] = $output;
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function sendPasswordResetEmail($input)
  {
    global $dir, $app;
    $output = $verificationcode_tag = '';
    include($dir['blade'] . '/email/email-template_basic.inc');

    $message['toemail'] = $input['toemail'];
    $message['subject'] = 'birthday.gold Account Password Reset';
    $message['body'] = '
<!-- Greeting -->
<h1 style="margin-top: 0; color: #2F3133; font-size: 19px; font-weight: bold; text-align: left;">
Hello, ' . $input['fullname'] . '
</h1>

<!-- Intro -->
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
Here is your password reset link: </B>
' . $this->emailbutton('Reset Your Password', $input['validatelink']) . '
</p>
<br><br>
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
If that link doesn\'t work for you, you can copy and paste this URL into your browser
</p>
<pre>' . $input['validatelink'] . '</pre>


<br><br><br>
<!-- Salutation -->
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
Regards,<br>birthday.gold
</p>
';

    $message['body'] = str_replace('{{MESSAGE_CONTENT}}', $message['body'], $output);


    $shortcode = $app->getshortcode($input['validatelink']);
    $search = array('{{VERIFICATION_CODE}}', $input['validatelink']);
    $replace = array($verificationcode_tag, $shortcode['shorturl']);

    $message['body'] = str_replace($search, $replace, $message['body']);
    $message['body'] = str_replace('{{TO_EMAIL}}', $message['toemail'], $message['body']);


    $result = $this->sendmail($message);
    $results['status'] = $result;
    $results['output'] = $output;
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function sendVerfiedUseremail($input)
  {

    #breakpoint($input);
    global $dir;
    $output = '';
    include($dir['blade'] . '/email/email-template_basic.inc');
    $message['to'] = $input['to'];    
    $message['toemail'] = $input['toemail']?? $input['to'];
    if (isset($input['from'])) $message['from'] = $input['from'];
    switch ($input['messagetype']) {

      case 'approve':
        $message['subject'] = 'Your account has been verified! - birthday.gold';
        $messagetag = 'Congratulations! Your account has been verified, now you can make publications without problems, welcome on board! ';
        break;

      case 'reject':
        $message['subject'] = 'Your account could not be verified! - birthday.gold';
        $messagetag = "We're sorry, but your account could not be verified. If you think it is a mistake, contact us by replying to this email";
        $message['from'] = ['cs@birthday.gold', 'birthday.gold Customer Service'];
        break;
    }

    $message['body'] = '
<!-- Greeting -->
<h1 style="margin-top: 0; color: #2F3133; font-size: 19px; font-weight: bold; text-align: left;">
Hello, ' . $input['fullname'] . '
</h1>

<!-- Intro -->
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
' . $messagetag . '
</p>


<br><br><br>
<!-- Salutation -->
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
Regards,<br>birthday.gold
</p>
';
    $message['body'] = str_replace('{{MESSAGE_CONTENT}}', $message['body'], $output);    
    $message['body'] = str_replace('{{TO_EMAIL}}', $message['toemail'], $message['body']);
    $this->sendmail($message);
    return $output;
  }


  #$mailactions = new mailhandler;


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function sendtemplate($input, $message = [])
  {
    global $dir;
    $templatename = $input['templatename'] ?? '';
    $output = '';
    if (!empty($templatename)) {
      include($dir['blade'] . '/email/email-' . $templatename . '.inc');

      $message = array_merge($message, $input);

      session_tracking('sendtemplate', $message);

      $this->sendmail($message);
    }
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function mailcount($user_id, $type = 'unread', $limit = 0)
{
    global $sitesettings;
    $config = $sitesettings['database_admin'];
    $servers = [
        ['DB_HOST' => 'march01.bday.gold', 'DB_DATABASE' => 'mailserver', 'DB_USERNAME' => 'birthday_gold_admin', 'DB_PASSWORD' => $config['password'], 'DB_CHARSET' => 'utf8mb4'],
        ['DB_HOST' => 'march02.bday.gold', 'DB_DATABASE' => 'mailserver', 'DB_USERNAME' => 'birthday_gold_admin', 'DB_PASSWORD' => $config['password'], 'DB_CHARSET' => 'utf8mb4'],
        ['DB_HOST' => 'march02.bday.gold', 'DB_DATABASE' => 'xfer', 'DB_USERNAME' => 'birthday_gold_admin', 'DB_PASSWORD' => $config['password'], 'DB_CHARSET' => 'utf8mb4']
    ];

    $totalCount = 0;
    $message = [];
    $criteria = ($type === 'user') ? ' AND company_id != 0 AND company_id IS NOT NULL ' : '';

    // Loop through each server
    foreach ($servers as $serverConfig) {
        try {
            // Create PDO connection
            $dsn = "mysql:host={$serverConfig['DB_HOST']};dbname={$serverConfig['DB_DATABASE']};charset={$serverConfig['DB_CHARSET']}";
            $pdo = new PDO($dsn, $serverConfig['DB_USERNAME'], $serverConfig['DB_PASSWORD']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Prepare SQL query
            $sql = "SELECT COUNT(*) as cnt FROM messages WHERE user_id = :user_id" . $criteria;
            if ($limit > 0) {
                $sql .= " LIMIT :limit";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            if ($limit > 0) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($row)) {
                $totalCount += (int)$row['cnt'];
            }
        } catch (PDOException $e) {
            // Log error and continue with the next server
            error_log('Error connecting to database: ' . $e->getMessage());
            continue;
        }
    }

    // Build the message structure
    if ($totalCount > 0) {
        $message['total'] = $totalCount;
        $message['count'] = $totalCount;
        $message['unread'] = $totalCount;
        $message['read'] = $totalCount;
        $message['inbox'] = $totalCount;
        $message['trash'] = $totalCount;
        $message['displaycount'] = $totalCount;

        $badgeContent = $totalCount > 1000 ? '999+' : $totalCount;
        $message['badge'] = $totalCount === 0
            ? ''
            : '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' . $badgeContent . '<span class="visually-hidden">unread messages</span></span>';
    } else {
        $message = [
            'total' => 0,
            'count' => 0,
            'unread' => 0,
            'read' => 0,
            'inbox' => 0,
            'trash' => 0,
            'displaycount' => 0,
            'badge' => ''
        ];
    }

    return $message;
}





  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getMessageList($user_id, $type = 'user', $params = []) {
    $sort = $params['sort'] ?? 'create_dt';
    $order = strtoupper($params['order'] ?? 'DESC');
    $search = $params['search'] ?? '';
    $page = max(1, intval($params['page'] ?? 1));
    $per_page = intval($params['per_page'] ?? 100);

    // Whitelist allowed columns for sorting
    $allowedColumns = ['create_dt', 'company_id', 'subject'];
    if (!in_array($sort, $allowedColumns)) {
        $sort = 'create_dt';
    }
    if (!in_array($order, ['ASC', 'DESC'])) {
        $order = 'DESC';
    }

    global $sitesettings;
    $config = $sitesettings['database_admin'];
    $servers = [
        ['DB_HOST' => 'march01.bday.gold', 'DB_DATABASE' => 'mailserver', 'DB_USERNAME' => 'birthday_gold_admin', 'DB_PASSWORD' => $config['password'], 'DB_CHARSET' => 'utf8mb4'],
        ['DB_HOST' => 'march02.bday.gold', 'DB_DATABASE' => 'mailserver', 'DB_USERNAME' => 'birthday_gold_admin', 'DB_PASSWORD' => $config['password'], 'DB_CHARSET' => 'utf8mb4'],
        ['DB_HOST' => 'march02.bday.gold', 'DB_DATABASE' => 'xfer', 'DB_USERNAME' => 'birthday_gold_admin', 'DB_PASSWORD' => $config['password'], 'DB_CHARSET' => 'utf8mb4']
    ];

    $allMessages = [];
    $hostCounts = [];
    $criteria = !empty($search) ? ' AND (subject LIKE :search OR message_body LIKE :search) ' : '';

    foreach ($servers as $config) {
        try {
            $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_DATABASE']};charset={$config['DB_CHARSET']}";
            $pdo = new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("Connected to {$config['DB_HOST']} - {$config['DB_DATABASE']}");

            // Add LIMIT 2000 to each query to prevent overload
            $sql = "SELECT @@hostname AS host, message_id, company_id, subject, create_dt, processstatus 
                    FROM messages 
                    WHERE user_id = :user_id $criteria 
                    ORDER BY `$sort` $order 
                    LIMIT 2000";

            $stmt = $pdo->prepare($sql);
            
            // Build params array
            $queryParams = ['user_id' => $user_id];
            if (!empty($search)) {
                $queryParams['search'] = '%' . $search . '%';
            }
            
            error_log("Query: $sql");
            error_log("Params: " . json_encode($queryParams));
            
            $stmt->execute($queryParams);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($messages) . " messages in {$config['DB_HOST']}");

            $hostKey = $config['DB_HOST'] . '|' . $config['DB_DATABASE'];
            $hostCounts[$hostKey] = count($messages);
            $allMessages = array_merge($allMessages, $messages);
            
        } catch (PDOException $e) {
            error_log("Database error on {$config['DB_HOST']}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }

    usort($allMessages, function ($a, $b) use ($sort, $order) {
        return $order === 'ASC' ? strcmp($a[$sort], $b[$sort]) : strcmp($b[$sort], $a[$sort]);
    });

    // Limit the total messages to 5000 after sorting
    $allMessages = array_slice($allMessages, 0, 5000);

    $offset = ($page - 1) * $per_page;
    $paginatedMessages = array_slice($allMessages, $offset, $per_page);

    return [
        'messages' => $paginatedMessages,
        'counts' => ['total' => count($allMessages)],
        'hostCounts' => $hostCounts
    ];
}


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getmessage_old($id)
  {
  
    global $sitesettings;
    $config = $sitesettings['database_admin'];
    $servers = [
        ['DB_HOST' => 'march01.bday.gold', 'DB_DATABASE' => 'mailserver', 'DB_USERNAME' => 'birthday_gold_admin', 'DB_PASSWORD' => $config['password'], 'DB_CHARSET' => 'utf8mb4'],
        ['DB_HOST' => 'march02.bday.gold', 'DB_DATABASE' => 'mailserver', 'DB_USERNAME' => 'birthday_gold_admin', 'DB_PASSWORD' => $config['password'], 'DB_CHARSET' => 'utf8mb4'],
        ['DB_HOST' => 'march02.bday.gold', 'DB_DATABASE' => 'xfer', 'DB_USERNAME' => 'birthday_gold_admin', 'DB_PASSWORD' => $config['password'], 'DB_CHARSET' => 'utf8mb4']
    ];

    $allMessages = [];
    $hostCounts = [];
    $criteria = !empty($search) ? ' AND (subject LIKE :search OR message_body LIKE :search) ' : '';

    foreach ($servers as $config) {
        try {
            $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_DATABASE']};charset={$config['DB_CHARSET']}";
            $pdo = new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("Connected to {$config['DB_HOST']} - {$config['DB_DATABASE']}");


      $stmt = $pdo->prepare('SELECT * FROM messages WHERE message_id = :message_id');
      $stmt->bindParam(':message_id', $id, PDO::PARAM_INT);
      $stmt->execute();

      $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
      if ($message) {
        $stmt = $pdo->prepare('update messages set processstatus="read" WHERE message_id = :message_id');
        $stmt->bindParam(':message_id', $id, PDO::PARAM_INT);
        $stmt->execute();


        return $message;
      } else {
        continue;
      }
    } catch (PDOException $e) {
      echo 'Error: ' . $e->getMessage();
      return null;
    }
   } 
 
  }
  
  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getmessage($message_id, $mailserver = null) {
    global $sitesettings;
    $config = $sitesettings['database_admin'];

    // Map server names to full configurations
    $servers = [
        'march01' => ['DB_HOST' => 'march01.bday.gold', 'DB_DATABASE' => 'mailserver'],
        'march02' => ['DB_HOST' => 'march02.bday.gold', 'DB_DATABASE' => 'mailserver'],
        'xfer' => ['DB_HOST' => 'march02.bday.gold', 'DB_DATABASE' => 'xfer']
    ];

    // If mailserver is specified, only query that server
    if ($mailserver && isset($servers[$mailserver])) {
        $serverConfig = $servers[$mailserver];
        $serverConfig = array_merge($serverConfig, [
            'DB_USERNAME' => 'birthday_gold_admin',
            'DB_PASSWORD' => $config['password'],
            'DB_CHARSET' => 'utf8mb4'
        ]);
        
        try {
            $dsn = "mysql:host={$serverConfig['DB_HOST']};dbname={$serverConfig['DB_DATABASE']};charset={$serverConfig['DB_CHARSET']}";
            $pdo = new PDO($dsn, $serverConfig['DB_USERNAME'], $serverConfig['DB_PASSWORD']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare('SELECT * FROM messages WHERE message_id = :message_id');
            $stmt->execute(['message_id' => $message_id]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($message) {
                // Mark as read
                $stmt = $pdo->prepare('UPDATE messages SET processstatus = "read" WHERE message_id = :message_id');
                $stmt->execute(['message_id' => $message_id]);
                return $message;
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    return null;
}

}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
# ##--------------------------------------------------------------------------------------------------------------------------------------------------
# ##--------------------------------------------------------------------------------------------------------------------------------------------------
# ##--------------------------------------------------------------------------------------------------------------------------------------------------

class MailQueue
{

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function __construct($local_config)
  {
    // Use $config
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function send($to, $subject, $body, $tags = [])
  {

    // Add mail to database queue
    $this->queueMail($to, $subject, $body, $tags);

    // Actually send email 
    mail($to, $subject, $body);

    // Log tags
    $this->trackTags($tags);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  private function queueMail($to, $subject, $body, $tags)
  {

    // Insert mail into database
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  private function trackTags($tags)
  {

    // Log tags used
  }
}
  
  // Usage:
  /*
  $mail = new Mail();
  
  $mail->send(
    'recipient@example.com',
    'Hello',
    'Body text',
    ['signup', 'welcome']
  );
  */