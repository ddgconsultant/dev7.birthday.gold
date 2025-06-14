<?PHP




// Set up email parameters

/// TYPICALLY CALLED BY: set_admin.php
switch ($input['type']){

default:
$message['subject']='Your Account Admin Level Update 🌟';
$message['from']=['hr@birthday.gold', 'birthday.gold Human Resources'];
$message['body']='
<!-- Greeting -->
<h1 style="margin-top: 0; color: #2F3133; font-size: 24px; font-weight: bold; text-align: left;">
Hello '.$input['name'].',
</h1>
<!-- Message -->
<p style="margin-top: 0; color: #74787E; font-size: 20px; line-height: 1.5em;">
Just a quick note to let you know your account level has been updated from ["'.$input['previousrole'].'"] to ["'.$input['newrole'].'"], effective immediately.

Any questions? We are here for you.


Cheers,

The Birthday.Gold HR Team
</p>
';
break;

}

$message['body']=nl2br($message['body']);