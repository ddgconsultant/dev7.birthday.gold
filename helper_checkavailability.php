<?php
$addClasses[] = 'createaccount';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$output = -1;
if ($app->formposted()) {
    if (isset($_REQUEST['username'])) {
        switch ($_REQUEST['type']??'') {
            case 'f.email':
                $username = $_REQUEST['username'];
                if (strpos($username, '@mybdaygold.com') !== true) $username .= '@mybdaygold.com';
                $output = $createaccount->isavailable($username, 'feature_email');

                break;

            default:
                $username = $_REQUEST['username'];
                $output = $createaccount->isavailable($username);
                break;
        }
    }
}

echo $output;
