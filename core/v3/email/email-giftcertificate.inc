<?PHP

$message['subject']= 'Your Gift Certificate - birthday.gold';
$messagetag="We've attached your gift certificate in this message for your records.";
$message['from']=['cs@birthday.gold', 'birthday.gold Customer Service'];

$message['body']='
<!-- Greeting -->
<h1 style="margin-top: 0; color: #2F3133; font-size: 19px; font-weight: bold; text-align: left;">
Hello, '.$input['fullname'].'
</h1>

<!-- Intro -->
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
'.$messagetag.'
</p>


<br><br><br>
<!-- Salutation -->
<p style="margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;">
Regards,<br>birthday.gold
</p>
';
#$message['body']=str_replace('{{MESSAGE_CONTENT}}', $message['body'], $output);
