<?PHP




// Set up email parameters

/// TYPICALLY CALLED BY: set_staff.php
switch ($input['type']){

default:
// Default case for updating account to staff level
$message['subject'] = 'Welcome Aboard! 🎉';
$message['from'] = ['hr@birthday.gold', 'Birthday.Gold Human Resources'];
$message['body'] = '
<!-- Greeting -->
<h1 style="margin-top: 0; color: #2F3133; font-size: 24px; font-weight: bold; text-align: left;">
Welcome Aboard '.$input['name'].'!
</h1>
<!-- Message -->
<p style="margin-top: 0; color: #74787E; font-size: 20px; line-height: 1.5em;">
Congratulations, '.$input['name'].'! Your account has now been converted to a staff account. Welcome to the Birthday.Gold team as <strong>'.$input['newrole'].'</strong>.  We are thrilled to have you with us and excited to see the contributions you\'ll bring to our team.

Over the next few days, you will receive additional emails from the Human Resources team to kick-start your onboarding journey.

If you have any immediate questions or need assistance, don\'t hesitate to reach out.

Welcome to the team!

Best Wishes,

The Birthday.Gold HR Team
</p>
';

break;

}

$message['body']=nl2br($message['body']);