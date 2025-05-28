<?PHP
global $website;
if (!isset($buttonlink)) {
    $buttonlink=$mail->emailbutton('Visit Birthday.Gold', 'https://birthday.gold/');
}   
$search=[
    '{{MESSAGE_CONTENT}}',
    '{{BUTTON_CONTENT}}',
];
$replace=[
    $message,
    $buttonlink,
];
$output=trim('<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style rel="stylesheet" media="all">@media  only screen and (max-width: 500px) {.button {width: 100% !important;}}</style>
</head>
<body style="margin: 0; padding: 0; width: 100%; background-color: #F2F4F6;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td style="width: 100%; margin: 0; padding: 0; background-color: #F2F4F6;" align="center">
<table width="100%" cellpadding="0" cellspacing="0">
<!-- Logo -->
<tr><td style="padding: 25px 0; text-align: center;">
    <a style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 20px; font-weight: bold; color: #2F3133; text-decoration: none; text-shadow: 0 1px 0 white;" 
        href="'.$website_full_url.'" target="_blank">
    <img src="https://cdn.birthday.gold/public/images/logo/bg_icon.png" alt="" style="display:block; margin-bottom: 10px; margin-left: auto; margin-right: auto; width: 15%;"><br>
    birthday.gold
    </a>
</td></tr>

<!-- Email Body -->
<tr>
<td style="width: 100%; margin: 0; padding: 0; border-top: 1px solid #EDEFF2; border-bottom: 1px solid #EDEFF2; background-color: #FFF;" width="100%">
<table style="width: auto; max-width: 570px; margin: 0 auto; padding: 0;" align="center" width="570" cellpadding="0" cellspacing="0">
    <tr>
        <td style="font-family: Arial, &#039;Helvetica Neue&#039;, Helvetica, sans-serif; padding: 35px;">
<!-- Message Content start -->            
{{MESSAGE_CONTENT}}
<!-- Message Content end -->            
            </td>
    </tr>
</table>
<!-- Button Content start -->            
<center>
{{BUTTON_CONTENT}}
</center>
<br>
<!-- Message Content end --> 
</td>
</tr>

<!-- Footer -->
<tr><td>
    <table style="width: auto; max-width: 570px; margin: 0 auto; padding: 0; text-align: center;" align="center" width="570" cellpadding="0" cellspacing="0">
        <tr><td style="font-family: Arial, &#039;Helvetica Neue&#039;, Helvetica, sans-serif; color: #AEAEAE; padding: 35px; text-align: center;">
            <p style="margin-top: 0; color: #74787E; font-size: 12px; line-height: 1.5em;">
                <footer style="text-align: center;">
                <p>&copy; '.date('Y').' - birthday.gold. All rights reserved.</p>
                <p>You received this email because you have an active account on our website. You can update your notification preferences.</p>
                    <a href="https://birthday.gold/myaccount/preferences" style="color: #666666;">Manage Preferences</a>
                <p>We are located in:  Parker, Colorado 80134 - USA</p>
                <p>Need help? Contact us at <a href="mailto:support@birthday.gold" style="color: #666666;">support@birthday.gold</a></p>
                </footer>
            </p>
            </td></tr>
    </table>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
');
