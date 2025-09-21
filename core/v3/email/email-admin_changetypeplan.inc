<?PHP




// Set up email parameters

/// TYPICALLY CALLED BY: set_admin.php
switch ($input['type']){

default:
$message['subject']='Your Account Type/Plan Changes 🌟';
$message['from']=['cs@birthday.gold', 'birthday.gold'];
$message['body']='
<!-- Greeting -->
<h1 style="margin-top: 0; color: #2F3133; font-size: 24px; font-weight: bold; text-align: left;">
Hello '.$input['name'].',
</h1>
<!-- Message -->
<p style="margin-top: 0; color: #74787E; font-size: 20px; line-height: 1.5em;">
Just a quick note to let you know we have made changes to your account:

Your Account Status: <b>["'.$input['newstatustag'].'"]</b> previous ["'.$input['previousstatustag'].'"]
Your Account Type: <b>["'.$input['newtypetag'].'"]</b> previous ["'.$input['previoustypetag'].'"]
Your Account Plan: <b>["'.$input['newplantag'].'"]</b> previous ["'.$input['previousplantag'].'"]

These changes are effective immediately.

Any questions? We are here for you.


Cheers,

The Birthday.Gold Team
</p>
';
break;

}

$message['body']=nl2br($message['body']);