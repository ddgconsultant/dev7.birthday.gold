<?php
$addClasses[] = 'createaccount';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$output = -1;
if ($app->formposted()) {
    if (isset($_REQUEST['username'])) {
        // Set current user ID for exclusion from availability check
        $original_user_id = $session->get('current_user_id', '');
        if (isset($_REQUEST['current_child_id']) && !empty($_REQUEST['current_child_id'])) {
            $session->set('current_user_id', $_REQUEST['current_child_id']);
        }
        
        switch ($_REQUEST['type']??'') {
            case 'f.email':
                $username = $_REQUEST['username'];
                if (strpos($username, '@mybdaygold.com') !== true) $username .= '@mybdaygold.com';
                $output = $createaccount->isavailable($username, 'feature_email');
                break;

            case 'f.username':
                $username = $_REQUEST['username'];
                session_tracking('Username availability check', json_encode([
                    'username' => $username,
                    'current_child_id' => $_REQUEST['current_child_id'] ?? 'none',
                    'session_user_id' => $session->get('current_user_id', 'none')
                ]));
                $result = $createaccount->isavailable($username);
                session_tracking('Username availability result', 'Username: ' . $username . ', Result: ' . var_export($result, true));
                $output = $result;
                break;

            default:
                $username = $_REQUEST['username'];
                $output = $createaccount->isavailable($username);
                break;
        }
        
        // Restore original session after check
        if (isset($_REQUEST['current_child_id'])) {
            if (!empty($original_user_id)) {
                $session->set('current_user_id', $original_user_id);
            } else {
                $session->remove('current_user_id');
            }
        }
    }
}

echo $output;
