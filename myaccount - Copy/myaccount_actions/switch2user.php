<?php 
// Include the main site controller, which initializes the application and session.
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$response = false;

// Check if the form was posted using the GET method.
if ($app->formposted('GET')) {

    // Validate current user and 'aid' parameter.
    if (!empty($current_user_data['user_id']) && !empty($_REQUEST['aid'])) {

        // Handle revert impersonation request.
        if (isset($_REQUEST['revertimpersonation']) && $_REQUEST['revertimpersonation'] == '1') {
            session_tracking('attempting to stop impersonation', $impersonatordata);

            $impersonator_data = $session->get('impersonator');
            if (!empty($impersonator_data)) {
                $account->logout();
                $response = $account->login($impersonator_data['user_id'], $sitesettings['app']['APP_AUTOLOGIN'], 'adminswitch');

                // Clear impersonation-related session data.
                $session->unset('impersonator');
                $session->unset('impersonate_login_source');
                $session->unset('impersonate_login_data');

                if ($response) {
                    session_tracking('undo impersonation successful', $impersonator_data);
                    $transferpage['url'] = $impersonator_data['startpage'] ?? '/myaccount/account';
                    $transferpage['message'] = '<div class="alert alert-success">Reverted to original account.</div>';
                    $system->endpostpage($transferpage);
                    exit;
                }
            }
        }

        // Handle impersonation switch.
        if (!empty($_REQUEST['id']) && $current_user_data['user_id'] == $qik->decodeId($_REQUEST['aid'] ?? '')) {
            $session->set('impersonate_login_source', $current_user_data['user_id']);
            $impersonatordata['impersonator'] = [
                '_type' => 'impersonator',
                'user_id' => $current_user_data['user_id'],
                'username' => $current_user_data['username'],
                'first_name' => $current_user_data['first_name'],
                'startpage' => $_SERVER['HTTP_REFERER'] ?? '/myaccount/account',
            ];
            $session->set('impersonate_login_data', json_encode($impersonatordata));

            session_tracking('switching impersonator data', $impersonatordata);

            $switchto_user_id = $qik->decodeId($_REQUEST['id'] ?? '');
            session_tracking('switching', $switchto_user_id);

            // Logout the current user and login as the new user.
            $account->logout();
            $response = $account->login($switchto_user_id, $sitesettings['app']['APP_IMPERSONATEPASS'], 'adminswitch');

            if ($response) {
				$tmpsettings['status']='*';
                $switchdata = $account->getuserdata($switchto_user_id, 'user_id', $tmpsettings);
                $session->set('current_user_data', $switchdata);
                $session->set('impersonator', $impersonatordata['impersonator']);

                $errormessage = '<div class="alert alert-success">Switched Accounts successfully.</div>';
                if ($switchdata['status'] == 'validated') {
                    $transferpage['url'] = '/checkout?u=' . $qik->encodeId($switchto_user_id);
                } else {
                    $transferpage['url'] = '/myaccount/';
                }
                session_tracking('switched to user: ' . $switchdata['user_id'],  'transfering to ' . $transferpage['url']);
                $transferpage['message'] = $errormessage;
                $system->endpostpage($transferpage);
            } else {
                $errormessage = '<div class="alert alert-danger">Hmmm... failed to switch accounts.</div>';
                $transferpage['url'] = '/login';
                $transferpage['message'] = $errormessage;
                session_tracking('problem switching', $errormessage);
                $system->endpostpage($transferpage);
            }
        }
    }
}

// Default error handling if switching fails.
$errormessage = '<div class="alert alert-danger">Hmmm... unable to switch accounts.</div>';
$transferpage['url'] = '/myaccount/account';
$transferpage['message'] = $errormessage;
session_tracking('problem switching', $errormessage);
$system->endpostpage($transferpage);
