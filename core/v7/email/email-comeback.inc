<?PHP
 
$message['subject']= 'Please Finish Your Registration - birthday.gold';
$messagetag=
$message['from']=['cs@birthday.gold', 'birthday.gold Customer Service'];

$message['body']='
<!-- Greeting -->
<h1 style="margin-top: 0; color: #2F3133; font-size: 24px; font-weight: bold; text-align: left;">
Hello '.$input['first_name'].',
</h1>

<!-- Message -->
<p style="margin-top: 0; color: #74787E; font-size: 20px; line-height: 1.5em;">
Please come back!<br>
Your registration needs to be completed.<br>
By clicking on the link below it will validate your account and get you back on track to enjoying all the benefits of your birthday.gold membership.
</h1>
<br>
{{VALIDATION_BUTTON}}
<br>
<br>
<br><br>
<!-- Salutation -->
<p style="margin-top: 0; color: #74787E; font-size: 20px; line-height: 1.5em;">
Regards,<br>
birthday.gold
</p>
<br><br>
<hr>
<center>
<p>
This email is intended solely for the recipient and may contain personal information.<br>
If you have received this email in error, please notify us immediately and delete it from your system at: support@birthday.gold<br>
You are receiving this email because you signed up for our service at birthday.gold.<br>
&copy; '.date('Y').', birthday.gold. All rights reserved.
</p>
</center>';
$message['body']=str_replace('{{VALIDATION_BUTTON}}', $input['validation_button'], $message['body']);
