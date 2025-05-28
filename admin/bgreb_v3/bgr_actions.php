<?php
/*header('Access-Control-Allow-Origin: *'); // Add this at the very top
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    exit(0);
}
*/
// Include the site-controller.php file

$addClasses=['enrollment'];
$dir['base'] = $BASEDIR = __DIR__ . "/../.." ?? $_SERVER['DOCUMENT_ROOT'];
require_once($BASEDIR . '/core/site-controller.php');

session_tracking('bgrab_action-' . $_REQUEST['act'], $_REQUEST);

// Get POST data
$failed = false;
$doreturn=false;

// First check if we have a company_select value

if ( isset($_REQUEST['company_select']) && !empty($_REQUEST['company_select'])) {
    // Split the combined value
  #  breakpoint($_GET);
    list($userCompanyId, $companyId) = explode('|', $_REQUEST['company_select']);
    $doreturn=$_SERVER['HTTP_REFERER'];
} else {
    // Use individual ucid/cid values if provided
    $userCompanyId = (isset($_REQUEST['ucid']) ? $_REQUEST['ucid'] : '');
    $companyId = (isset($_REQUEST['cid']) ? $_REQUEST['cid'] : '');
}

// Verify all required parameters
if (!isset($_REQUEST['aid']) || 
    !isset($_REQUEST['uid']) || 
    !isset($_REQUEST['act']) || 
    empty($userCompanyId) || 
    empty($companyId) || 
    !isset($_REQUEST['message'])) {
    $failed = true;
}

if ($failed) {
    http_response_code(400);
    echo 'Invalid parameters provided: ' . print_r($_REQUEST, 1);
    exit;
}

$adminId = (isset($_REQUEST['aid']) ? $_REQUEST['aid'] : '');
if ($adminId == 'undefined') $adminId = 1;

$userId = (isset($_REQUEST['uid']) ? $_REQUEST['uid'] : '');
$action = (isset($_REQUEST['act']) ? $_REQUEST['act'] : '');
$message = (isset($_REQUEST['message']) ? $_REQUEST['message'] : '');
$acode = (isset($_REQUEST['acode']) ? $_REQUEST['acode'] : '');  /// admin authorization code
$version = (isset($_REQUEST['version']) ? $_REQUEST['version'] : 'unknown');




// Validate the user
$stmt = $database->query("SELECT * FROM bg_users WHERE user_id = :userId limit 1", ['userId' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(400);
    echo 'Invalid user: ' . print_r($_REQUEST, 1);
    exit;
}
// else {
// $user =$user[0];
//}



// GET Administrator user
if ($acode != '' || $adminId != '') {
    $stmt = $database->query("SELECT * FROM bg_users WHERE user_id = :userId limit 1", ['userId' => $adminId]);
    $adminuser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$adminuser) {
        http_response_code(400);
        echo 'Invalid user: ' . print_r($_REQUEST, 1);
        exit;
    } else {
        $adminuser = $user;
    }
}
// Validate the user_company_id
$stmt = $database->query(
    "SELECT * FROM bg_user_enrollments WHERE user_company_id = :userCompanyId  and company_id = :CompanyId AND user_id = :userId",
    ['userCompanyId' => $userCompanyId, 'CompanyId' => $companyId, 'userId' => $userId]
);


$userCompany = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$userCompany) {
    http_response_code(400);
    echo 'Invalid user company ID: ' . print_r($_REQUEST, 1);
    exit;
}

// Validate the action
if (strpos($action, 'success') === false && strpos($action, 'failed') === false) {
    http_response_code(400);
    echo 'Invalid action: ' . print_r($_REQUEST, 1);
    exit;
}


$noecho = true;
$bgr_action = true;
$bgr_action_company_id = $companyId;

include('bgr_getprocessdetails.php');

$registrationDetail = json_decode($output, true);
$registrationDetail['SERVERINFO'] = $_SERVER;
#$registration_detail = json_encode($registrationDetail, JSON_PRETTY_PRINT);





# ##--------------------------------------------------------------------------------------------------------------------------------------------------
# ##--------------------------------------------------------------------------------------------------------------------------------------------------

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
# ##--------------------------------------------------------------------------------------------------------------------------------------------------
$foundRegistration = $enrollment->findRegistrationByCompanyId($registrationDetail, $bgr_action_company_id);
$registration_detail = json_encode($foundRegistration, JSON_PRETTY_PRINT);


// If action is success, set registration_dt, registration_id, and registered_by
if (strpos($action, 'success') !== false || strpos($action, 'failed') !== false) {
    if (strpos($action, '||') === false) {
        $action .= '||';
    }
    list($finalaction, $reason) = explode('||', $action);
    $sql=" UPDATE bg_user_companies 
    SET `status` = :status, 
    reason=:reason,
        modify_dt = now(), 
        registration_dt = NOW(), 
        registration_id = :registrationId, 
        registered_by = :registeredBy ,
        registration_engine = :engineversion,
        registration_detail = :registration_detail
    WHERE user_company_id = :userCompanyId";
    $params=  [
        'status' => $finalaction,
        'reason' => $reason,
        'registrationId' => $adminId,
        'registeredBy' => $adminuser['username'],
        'engineversion' => $version,
        'registration_detail' => $registration_detail,
        'userCompanyId' => $userCompanyId
    ];
    $stmt = $database->query($sql,    $params  );
    session_tracking('bgrab_action_update_sql', $sql, '__NOREQUESTDATA__');
    session_tracking('bgrab_action_update_params', $params, '__NOREQUESTDATA__');
    
    $stmt = $database->query($sql, $params);
    
    session_tracking('bgrab_action_update_result', [
        'success' => ($stmt !== false),
        'error' => $database->errorInfo($stmt),
        'stmt' => ($stmt !== false) ? 'success' : 'failed'
    ], '__NOREQUESTDATA__');


    ////  store the credential in the accessmanager
    if (strpos($action, 'success') !== false) {      

       # $companyId =  $_REQUEST['cid'];
        $stmt = $database->query("SELECT * FROM bg_companies WHERE  company_id = ? limit 1", [$bgr_action_company_id]);
        $userCompanyDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        // Usage example
        $url = 'https://dev.birthday.gold/admin/accessmanager/am_enrollmentaction.php';
        $postData = [
            'registration_detail' => $registration_detail, // Example data, adjust according to actual needs
            'profile_username' => $userDetails['profile_username'],
            'profile_password' => $userDetails['profile_password'],
            'profile_email' => $userDetails['profile_email'],
            'profile_phone_number' => $userDetails['profile_phone_number'],
            'admin_id' => $adminId, // Define $adminId as needed
            'user_id' =>  $userId,
            'company_id' => $bgr_action_company_id,
            'CompanyName' => urlencode($userCompanyDetails['company_display_name']),
            'CompanyURL' =>   urlencode($userCompanyDetails['signup_url'])

        ];

        session_tracking('posting_to_accessmanager', $postData);
        $response = $enrollment->postToUrl($url, $postData);          

    }
} else {
    $stmt = $database->query(
        "UPDATE bg_user_companies SET `status` = :status, modify_dt=now() WHERE user_company_id = :userCompanyId",
        ['status' => $action, 'userCompanyId' => $userCompanyId]
    );
}

if ( $doreturn) {
    header("Location: $doreturn");
    exit;
}
// Output success response
http_response_code(200);
#echo "Database updated successfully";
