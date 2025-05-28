<?php echo <<<'eod'
#!/usr/bin/php
<?php
require_once '/var/vmail/vendor/autoload.php';
$parser = new PhpMimeMailParser\Parser();

$DEBUG = false;

### ===============================================================================================================
try {
    $homeDir = '/var/vmail';
    include($homeDir . '/ENV_CONFIGS/config-database.inc');
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($db['host'], $db['user'], $db['password'], $options);
} catch (PDOException $e) {
    die(date('r') . ': ' . "-Database connection failed: " . $e->getMessage());
}


### ===============================================================================================================
if (isset($argv[1])) {
    $recipient = $argv[1];
} else {
    throw new Exception('Recipient not set');
}


### ===============================================================================================================
switch ($recipient) {
        // =======================================================
    case 'refresh_users':
    case 'populate_users':
    case 'refresh_companies':
    case 'populate_companies':
        include('mail_datarefresher.inc');
        return;
        break;

        // =======================================================
    default:
        // --------------------------------------------
        // Set the email parser to read from stdin
        $parser->setStream(fopen("php://stdin", "r"));

        // --------------------------------------------
        // Get sender email
        $sender = $rawHeaderFrom = $parser->getHeader('from');
        if ($DEBUG) echo "rawHeaderFrom: $rawHeaderFrom\n";
        $sender = str_replace(['<', '>'], '', $sender);
        $parts = explode(" ", $sender);
        $sender_email = null;
        $sender_domain = null;
        foreach ($parts as $part) {
            if (strpos($part, '@') !== false) {
                $sender_email = $part;
                break;
            }
        }
        if ($sender_email !== null) {
            $atPos = strrpos($sender_email, '@');
            if ($atPos !== false) {
                $sender_domain = strtolower(substr($sender_email, $atPos + 1));
            }
        }
        if ($sender_domain == 'c.pxsmail.com' || $sender_domain == 'tsg.pxsmail.com' || $sender_domain == 'punchhmail.com') {
            $sender_email = $sender_domain = null;
        }

        // --------------------------------------------
        // Get subject and body
        $subject = $parser->getHeader('subject');
        $to_email = $parser->getHeader('to');
        $body = $parser->getMessageBody('htmlEmbedded');
        if (empty($body)) {
            $body = $parser->getMessageBody('html');
        }
        if (empty($body)) {
            $body = $parser->getMessageBody('text');
        }
        $size = strlen($body);

        // Debug: Print extracted sender, subject, and to_email
        if ($DEBUG) {
            echo "sender: $sender\n";
            echo "sender_email: $sender_email\n";
            echo "sender_domain: $sender_domain\n";
            echo "subject: $subject\n";
            echo "to_email: $to_email\n";
            echo "body size: $size\n";
        }

        // --------------------------------------------
        // Get user_id from the database
        $stmt = $pdo->prepare("SELECT IFNULL(user_id, 0) AS id FROM bg_mail_users WHERE feature_email = :recipient LIMIT 1");
        $params = [':recipient' => $recipient];
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $user_id = $row['id'];
        } else {
            $user_id = 0;
        }

        // --------------------------------------------
        // Get company_id from the database
        $company_id = 99;
        if (!empty($sender_domain)) {
            $stmt = $pdo->prepare("SELECT IFNULL(company_id, 99) AS company_id FROM bg_mail_companies WHERE email_domain = :sender_domain OR email_domain = :sender_email OR email_domain = :sender LIMIT 1");
            $params = [':sender_domain' => $sender_domain, ':sender_email' => $sender_email, ':sender' => strtolower($sender)];
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $company_id = $row['company_id'];
            }
        }

        // --------------------------------------------
        // Insert into the messages table
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, recipient, sender, company_id, subject, body, size) 
    VALUES (:user_id, :recipient, :sender, :company_id, :subject, :body, :size)");
        $params = [
            ':user_id' => $user_id,
            ':recipient' => $recipient,
            ':sender' => $sender,
            ':company_id' => $company_id,
            ':subject' => $subject,
            ':body' => $body,
            ':size' => $size,
        ];
        $stmt->execute($params);

        // --------------------------------------------
        // Begin inserting email into the new schema
        $hostname = 'localhost';
        $username = $db['user'];
        $password = $db['password'];
        $database = 'email_db';

        // --------------------------------------------
        // Connect to the new schema database
        try {
            $pdo_new = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
            $pdo_new->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }

        // Use the existing parser to get the email structure
        $headers = $parser->getHeaders();
        $raw_email = stream_get_contents(fopen("php://stdin", "r"));

        // Debug: Print headers
        if ($DEBUG) {
            print_r($headers);
        }


        // Ensure headers are defined before using them
        $params = [
            'message_id' => $headers['message-id'] ?? uniqid('msg_', true),
            'company_id' => $company_id ?? 99,
            'subject' => $headers['subject'] ?? '',
            'user_id' => $user_id ?? 0,
            'from_email' => $headers['from'] ?? '',
            'from_name' => '', // Extract name if needed
            'to_email' => $recipient ?? '',
            'cc_email' => $headers['cc'] ?? '',
            'bcc_email' => $headers['bcc'] ?? '',
            'reply_to' => $headers['reply-to'] ?? '',
            'date_sent' => isset($headers['date']) ? date('Y-m-d H:i:s', strtotime($headers['date'])) : date('Y-m-d H:i:s'),
            'body_plain' => '', // Plain text body
            'body_html' => $body ?? '', // HTML body
            'compressed_json' =>  '' // Compressed JSON
        ];
        // --------------------------------------------
        // Insert compressed JSON copy of the email
        $compressed_json = gzcompress(json_encode($raw_email));
        $params['compressed_json'] = $compressed_json;


        // Debug: Print  params array email information
        if ($DEBUG) {
            print_r($params);
        }

        $stmt = $pdo_new->prepare("
    INSERT INTO emails 
    (message_id, company_id, user_id, subject, from_email, from_name, to_email, cc_email, bcc_email, reply_to, date_sent, body_plain, body_html, compressed_json) 
    VALUES 
    (:message_id, :company_id, :user_id, :subject, :from_email, :from_name, :to_email, :cc_email, :bcc_email, :reply_to, :date_sent, :body_plain, :body_html, :compressed_json)
");
        $stmt->execute($params);

        // --------------------------------------------
        // Get the inserted email ID
        $email_id = $pdo_new->lastInsertId();

        // --------------------------------------------
        // Insert headers into the new schema database
        foreach ($headers as $name => $value) {
            $stmt = $pdo_new->prepare("INSERT INTO email_headers (email_id, header_name, header_value) VALUES (?, ?, ?)");
            $stmt->execute([$email_id, $name, $value]);
        }


        // --------------------------------------------
        // Extract necessary data from the email
        $sender_ip = $parser->getHeader('X-Originating-IP'); // or any other header that holds the sender IP
        $sender_host = $parser->getHeader('Received'); // this might need parsing to extract the host
        $sender_user_agent = $parser->getHeader('User-Agent'); // or any other header that holds the user agent
        $sender_geo_location = ''; // this can be filled by a geo-location service if needed
        // Put the extracted data into an array
        $params_sender = [
            ':email_id' => $email_id,
            ':sender_ip' => $sender_ip,
            ':sender_host' => $sender_host,
            ':sender_user_agent' => $sender_user_agent,
            ':sender_geo_location' => $sender_geo_location
        ];

        // Count actual non-empty values
        $non_empty_values_count = 0;
        foreach ($params_sender as $key => $val) {
            if (!empty($val)) $non_empty_values_count++;
        }
        if ($non_empty_values_count > 1) {
            try {
                $params_sender['sender_geo_location'] = $non_empty_values_count;
                // Prepare the SQL statement
                $stmt = $pdo_new->prepare("INSERT INTO sender_fingerprints (email_id, sender_ip, sender_host, sender_user_agent, sender_geo_location) 
    VALUES (:email_id, :sender_ip, :sender_host, :sender_user_agent, :sender_geo_location)");
                $stmt->execute($params_sender);
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }

        // --------------------------------------------
        // Handle parts of the email (body and attachments)
        $attachments = $parser->getAttachments();
        foreach ($attachments as $attachment) {
            if ($attachment->getContentDisposition() == 'attachment') {
                $compressed_content = gzcompress($attachment->getContent());
                $file_name = $attachment->getFilename();
                $file_type = $attachment->getContentType();
                $file_size = strlen($compressed_content);
                $stmt = $pdo_new->prepare("INSERT INTO email_attachments (email_id, file_name, file_type, file_size, file_content) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$email_id, $file_name, $file_type, $file_size, $compressed_content]);
            }
        }

        $pdo_new = null;
        break;
}
eod;
