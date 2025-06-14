<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# RECORD VISIT
#-------------------------------------------------------------------------------
$company_id = $_REQUEST['cid'];
if (!empty($company_id)) {
    $input = [
        'name' => 'company_visit',
        'description' => $company_id
    ];
    $response = $account->setUserAttribute($current_user_data['user_id'], $input);
    $company_data = $app->getcompanydetails($company_id);
    header('location: ' . $company_data['signup_url']);
    exit;
}
echo 'no valid information was provided.';
