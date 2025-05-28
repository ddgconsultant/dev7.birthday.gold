<?PHP echo <<<'eod'
#!/usr/bin/php
<?php

require_once '/var/vmail/vendor/autoload.php';
$parser = new PhpMimeMailParser\Parser();


try {
    #### YOU MUST MAKE SURE THIS EXISTS -- this will change if we move the API directory
  #  include($_SERVER['DOCUMENT_ROOT'] . '/ENV_CONFIGS/config-database.inc');
$homeDir = $_SERVER['HOME'];
$homeDir = '/var/vmail';
include($homeDir . '/ENV_CONFIGS/config-database.inc');
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
    # echo $homeDir;
    #    print_r($db); exit;
    $pdo = new PDO($db['host'], $db['user'], $db['password'], $options);
} catch (PDOException $e) {
    die(date('r') . ': ' . "-Database connection failed: " . $e->getMessage());
}



if(isset($argv[1])){
$recipient = $argv[1];
} else {
throw new Exception('Recipient not set');
}


switch ($recipient) {

case 'refresh_users':

break;

case 'refresh_companies':


break;

default:


$parser->setStream(fopen("php://stdin", "r"));

$sender=$rawHeaderFrom = $parser->getHeader('from');
// return "test" <test@example.com>

if (preg_match('/@([\w\.\-]+)/', $sender, $matches)) {
    $domain = $matches[1];
} else {
    $domain = null;
}

$subject = $parser->getHeader('subject');

$body = $parser->getMessageBody('htmlEmbedded');
if (empty($body)) {
$body = $parser->getMessageBody('html');
}
if (empty($body)) {
$body = $parser->getMessageBody('text');
}

$size=strlen(($body));

////  GRAB BIRTHDAY GOLD DETAILS:
// GET USERID from the database
$stmt = $pdo->prepare("select ifnull(user_id, 0) as id from bg_mail_users where feature_email=:recipient limit 1");
$params=[':recipient' =>$recipient];
$stmt->execute($params);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $user_id = $row['id'];
    } else    $user_id =0;

// GET COMPANYID from the database
$stmt = $pdo->prepare("select ifnull(company_id, 0) from bg_mail_companies where email_domain=:domain limit 1");
$params=[':domain' =>$domain];
$stmt->execute($params);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $company_id = $row['id'];
    } else    $company_id =0;



// Inserting into the database
$stmt = $pdo->prepare("INSERT INTO messages (user_id, recipient, sender, company_id, subject, body, size) 
VALUES (:user_id, :recipient, :sender, :company_id,  :subject,  :body,  :size )");
$params=[
':user_id' =>$user_id,
':recipient' =>$recipient,
':sender' =>$sender,
':company_id' =>$company_id,
':subject' =>$subject,
':body' =>$body,
':size' =>$size,
];

$stmt->execute($params);
}
eod;