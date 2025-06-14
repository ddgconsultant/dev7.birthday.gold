<?PHP

if (empty($giftcode)) {
 ## GIFT CODE NEEDED - not found
    return;
}


$page='
{logo}

{gift-certificate.image}
<hr>

'. $giftcode .'

<hr>
This gift certificate can be redeemed online
for a pre-paid Lifetime Account.
https://birthday.gold/redeem

<hr>
';

if (!empty($recipient)) {
$page='For: '.$recipient;
}

if (!empty($message)) {
$page.=$message;
}
