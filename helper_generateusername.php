<?php
$addClasses[] = 'createaccount';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$output = -1;
if ($app->formposted()||1==1) {
    if (isset($_REQUEST['first_name'])) {
        switch ($_REQUEST['type']??'') {
            case 'f.email':
                $first_name = $_REQUEST['first_name'];
                $last_name = $_REQUEST['last_name'];
                $birthday = $_REQUEST['birthday'];
              #  if (strpos($username, '@mybdaygold.com') !== true) $username .= '@mybdaygold.com';
                $output= $createaccount->generate_username($first_name, $last_name, $birthday, $type='real').'@mybdaygold.com';
                
                break;

        
        }
    } else {
        $output = -2;
    }
}

echo $output;
