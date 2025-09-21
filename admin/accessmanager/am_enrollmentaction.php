<?php

$addClasses[] = 'AccessManager';
$apibypass=true;
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$testmode=false;

if (empty($testmode)) {
// Check the referer
$allowedReferers = ['bgr_actions', 'bgr_getprocessdetails'];
$actualReferer = $_SERVER['HTTP_REFERER'] ?? '';
$refererAllowed = false;

foreach ($allowedReferers as $allowedReferer) {
    if (strpos($actualReferer, $allowedReferer) !== false) {
        $refererAllowed = true;
        break;
    }
}

if (!$refererAllowed) {
    // If the referer does not match any of the allowed referers, stop execution and show an error
    http_response_code(403); // Forbidden
    die('Access denied. This action can only be performed from authorized systems.'. $actualReferer);
}



/*
$allowedIPs = ['71.33.250.239'];  // Array of allowed IP addresses
$currentIP = $_SERVER['REMOTE_ADDR'];  // Get the IP address of the request

if (!in_array($currentIP, $allowedIPs)) {
    http_response_code(403);  // Set HTTP status code to 403 Forbidden
    exit('Access denied');  // Terminate the script execution
}
*/
}
#session_tracking('am_enrollmentaction', $_REQUEST);
      
// Assuming $database is your PDO connection
try {
    // Check if data is posted
    if (($_SERVER['REQUEST_METHOD'] === 'POST') || $testmode) {


// Extract posted data and store in $input array
if (empty($testmode)) {
    $details=$_REQUEST['registration_detail'] ?? null;

$input = [
    'notes' => json_encode($details),
    'username' =>  join(' / ', [$_REQUEST['profile_username'] ?? null, $_REQUEST['profile_email'] ?? null, $_REQUEST['profile_phone_number'] ?? null] ),
    'password' => $_REQUEST['profile_password'] ?? null,
    'creator_id' => $_REQUEST['admin_id'] ?? null,
    'user_id' => $_REQUEST['user_id'] ?? null,
    'company_id' => $_REQUEST['company_id'] ?? null,
    'name' => $_REQUEST['CompanyName'] ?? null,
    'host' => $_REQUEST['CompanyURL'] ?? null,
    'category' => $_REQUEST['category'] ?? 'Personal',
    'grouping' => $_REQUEST['grouping'] ?? 'enrollment',
    'datatype' => $_REQUEST['datatype'] ?? 'enrollment',
];

} else {

$input = [
    'registration_detail' => [
        [
            'user_company_id' => '1242',
            'company_name' => "Applebee's",
            'user_id' => '86',
            'company_id' => '1969',
            'status' => 'selected',
            'company_status' => 'finalized',
            'signup_domain' => 'https://www.applebees.com',
            'signup_url' => 'https://www.applebees.com/en/sign-up',
            'bgrab_domain' => 'applebees',
            'FIELDMAPPING' => [
                '01||fxb_398e748c-1b18-46cc-9d77-0b54d02fdffd_Fields_e0aed596-e7ee-4bd6-ae8d-2950a656e176__Value' => 'value-not-provided',
                '02||fxb_398e748c-1b18-46cc-9d77-0b54d02fdffd_Fields_e0aed596-e7ee-4bd6-ae8d-2950a656e176__ConfirmEmail' => 'value-not-provided',
                '03||fxb_398e748c-1b18-46cc-9d77-0b54d02fdffd_Fields_e0305ed1-cc33-4610-90a4-34eba6763549__Value' => 'value-not-provided',
                '04||fxb_398e748c-1b18-46cc-9d77-0b54d02fdffd_Fields_873f7218-9d34-47ca-8e5a-67f3c4a806f8__Value' => 'value-not-provided',
                '05||fxb_398e748c-1b18-46cc-9d77-0b54d02fdffd_Fields_18f7b137-0126-4789-872c-4dacbeff721f__Value' => '',
                '06||fxb_398e748c-1b18-46cc-9d77-0b54d02fdffd_Fields_8a33cbfc-04e3-4c53-891d-eae947d6a019__Value' => 'value-not-provided',
                '07||fxb_398e748c-1b18-46cc-9d77-0b54d02fdffd_Fields_4ea65617-4404-44ff-99b5-65a8957cf25a__Value' => 'value-not-provided',
                '08||fxb_398e748c-1b18-46cc-9d77-0b54d02fdffd_Fields_b81432f7-8ae4-42a9-8f69-9a15738852db__Value' => '1969-12-12',
                '09||fxb_398e748c-1b18-46cc-9d77-0b54d02fdffd_Fields_c5e96e16-c6d3-4e25-9075-ad0796d75e37__Value' => '7516'
            ]
        ],
        [
            'user_company_id' => '1244',
            'company_name' => "Acapulco",
            'user_id' => '86',
            'company_id' => '5849',
            'status' => 'selected',
            'company_status' => 'finalized',
            'signup_domain' => 'https://xrg.myguestaccount.com',
            'signup_url' => 'https://xrg.myguestaccount.com/en-us/guest/eclub/enroll?card-template=kCi4xf6VYTw',
            'bgrab_domain' => 'xrg.myguestaccount.com',
            'FIELDMAPPING' => [
                '01||profile_username' => 'value-not-provided',
                '02||email' => 'value-not-provided',
                '03||profile_password' => 'value-not-provided',
            ]
        ],
    ],
    'admin_id' => '20',
    'user_id' => '86',
    'company_id' => '0',
    'CompanyName' => '',
    'CompanyURL' => '',
    'datatype' => 'enrollment_session',
    'category' => '__SYSTEMRESTRICTED__',
    'grouping' => '__SYSTEMRESTRICTED__'
];

$input['registration_detail']=json_encode($input['registration_detail']);

}

#session_tracking("HERE AM");

// Pass $input to the create_record function of the access manager
$result = $accessmanager->create_record($input);

session_tracking("DONE AMCALL", $result, '__NOREQUESTDATA__');

// Optionally, handle the result
if (!empty($result)) {
    // Set response code to 200 OK
    http_response_code(200);
    
    // Assuming you want to return JSON (common in APIs)
    header('Content-Type: application/json');
    
    // Create an associative array to hold your response data
    $response = [
        'success' => true,
        'message' => 'Record created successfully.',
        'recordId' => $result
    ];
    
    // Encode the array to JSON and output
    $finalout= json_encode($response);
    echo  $finalout;
    session_tracking("SUCCESS AMACTION",  $finalout, '__NOREQUESTDATA__');
} else {
    // If creation failed, send a 500 Internal Server Error
    http_response_code(500);
    header('Content-Type: application/json');
    $finalout= json_encode([
        'success' => false,
        'message' => 'Failed to create the record.'
    ]);

echo  $finalout;
session_tracking("FAILED AMACTION",  $finalout, '__NOREQUESTDATA__');
}


}     
        

} catch (Exception $e) {
    // Handle exception
  ##  echo 'Error: ' . $e->getMessage();
}

