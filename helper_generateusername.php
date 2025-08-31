<?php
$addClasses[] = 'createaccount';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$output = '';
if ($app->formposted()||1==1) {
    if (isset($_REQUEST['first_name'])) {
        switch ($_REQUEST['type']??'') {
            case 'f.email':
                $first_name = $_REQUEST['first_name'];
                $last_name = $_REQUEST['last_name'];
                $birthday = $_REQUEST['birthday'];
                $output= $createaccount->generate_username($first_name, $last_name, $birthday, $type='real').'@mybdaygold.com';
                break;
                
            default:
                // For username generation (not email)
                $first_name = $_REQUEST['first_name'];
                $last_name = $_REQUEST['last_name'];
                $birthday = $_REQUEST['birthday'];
                $output = $createaccount->generate_username($first_name, $last_name, $birthday, $type='real');
                break;
        }
    } else if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'generate_username') {
        // Handle the new generate_username action
        $first_name = $_REQUEST['first_name'];
        $last_name = $_REQUEST['last_name'];
        $birthday = $_REQUEST['birthday'];
        $output = $createaccount->generate_username($first_name, $last_name, $birthday, $type='real');
    } else {
        $output = '';
    }
}

echo $output;
