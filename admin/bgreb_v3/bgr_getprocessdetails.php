<?php
$addClasses=['enrollment'];
$dir['base']=$BASEDIR=__DIR__."/../.." ?? $_SERVER['DOCUMENT_ROOT'];
require_once ($BASEDIR.'/core/site-controller.php');


// Add this before the grabdetails() call in bgr_getprocessdetails.php
if (!isset($enrollment) || $enrollment === null) {
    session_tracking('enrollment_object_status', 'Enrollment object is null or not set');
    throw new Exception('Enrollment class not properly loaded');
}
// Add after the site-controller.php require_once
#session_tracking('loaded_classes', get_declared_classes());
session_tracking('enrollment_class_exists', class_exists('Enrollment'));

$userId=0;
$authcode='';
$aid='';  // admin id
$adminDetails=[];
#-------------------------------------------------------------------------------
$return='local';
if (empty($userId)) {
// Get the user ID from the query string
$userId = isset($_GET['uid']) ? $_GET['uid'] : 0;
$return='js';
$companyId=0;
}

if ($userId==0) {
    return false;
}


#-------------------------------------------------------------------------------
if (empty($aid)) {
    // Get the user ID from the query string
    $aid = isset($_GET['aid']) ? $_GET['aid'] : 0;
    $return='js';
    $companyId=0;    

	/*
    if ($aid==0 && (isset($_REQUEST['type']) && $_REQUEST['type']!='bgrab')) {
        return false;
    }
    */

if ($aid!=0) {
    $tmpsettings=[];
	$tmpsettings['columns']='user_id, first_name, last_name, username';
    $adminDetails=$account->getuserdata( $aid, 'user_id', $tmpsettings);
   # breakpoint($aid);
}
}

if (!empty($_REQUEST['bid'])) {
$companyId=$_REQUEST['bid']?? $_REQUEST['cid']??0;
session_tracking('companyId provided', $companyId);
}
  #  $stmt = $database->query("SELECT * FROM bg_users WHERE user_id = :userId", ['userId' => $aid]);
   # $adminDetails = $stmt->fetch(PDO::FETCH_ASSOC);
   # breakpoint($adminDetails);  #$output.=print_r($adminDetails,1);
      
#-------------------------------------------------------------------------------
if (empty($authcode)) {
    // Get the user ID from the query string
    $authcode = isset($_GET['acode']) ? intval($_GET['acode']) : 0;
}



#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
 list($output, $adminDetails, $userDetails, $registrationList)=$enrollment->grabdetails($database, $adminDetails, $userId, $companyId, $return) ;


#breakpoint( $registrationList);


if (isset($_REQUEST['type']) && $_REQUEST['type']=='bgrab') {
    $DEBUG=false;

    session_tracking('running bgrab section', $companyId);



#if (!empty( $_REQUEST['cid']))  $companyId =   $_REQUEST['cid'];

 $stmt = $database->query("SELECT * FROM bg_companies WHERE  company_id = :companyid limit 1", [':companyid'=>$companyId ]);
 $userCompanyDetails = $stmt->fetch(PDO::FETCH_ASSOC);
$amnotes=$registrationList;
$senddata['datatype']='enrollment_session';
$senddata['category']='__SYSTEMRESTRICTED__';
$senddata['grouping']='__SYSTEMRESTRICTED__';

if (!empty($bgr_action)){
    $companyDetails = $enrollment->findCompanyById($registrationList, $companyId);
    $amnotes= $companyDetails;
$senddata['datatype']='enrollment';
$senddata['category']='Personal';
$senddata['grouping']='enrollment';
}

 // Usage example
 $url = 'https://birthday.gold/admin/accessmanager/am_enrollmentaction.php';
 $postData = [
     'registration_detail' => $amnotes, // Example data, adjust according to actual needs
     'profile_username' => $userDetails['profile_username']??'',
     'profile_password' => $userDetails['profile_password']??'',
     'profile_email' => $userDetails['profile_email']??'',
     'profile_phone_number' => $userDetails['profile_phone_number']??'',
     'admin_id' => $aid, // Define $adminId as needed
     'user_id' =>  $userId,
     'company_id' => $companyId,
     'CompanyName' => urlencode($userCompanyDetails['company_display_name']??''),
     'CompanyURL' =>   urlencode($userCompanyDetails['signup_url']??''),
     'datatype' =>  $senddata['datatype'],
     'category' =>  $senddata['category'],
     'grouping' => $senddata['grouping'],
 ];

 $response = $enrollment->postToUrl($url, $postData);


if ($DEBUG) {

 $responseData = $response; // Decode the JSON response
 session_tracking("responseData", print_r($response,1));
 $start = strpos($response, "{");
    $json = false;
    if ($start !== false) {
        $json = substr($response, $start); // Get the substring starting from '{'
    }
    
    // Decode the JSON to an associative array
    if ($json) {
        $responseData = json_decode($json, true);
    } else {
        $responseData = null;
        // Log an error or handle cases where JSON is missing or response is malformed
        session_tracking("Error", "JSON data not found in response.");
    }
    
    // Check if decoding was successful and use the data
    if ($responseData) {
        session_tracking("responseData", print_r($responseData, true)); // Log the decoded array
    
        if ($responseData['success'] && isset($responseData['recordId'])) {
            $recordId = $responseData['recordId'];
            session_tracking("Success - Record ID:", $recordId);
            
            $lockinput=['enrollment_data_id'=>$recordId, 'admin_id'=>$aid];
            session_tracking('trying profileLocked', $lockinput, '__NOREQUESTDATA__');
$account->profileLocked($userId, 'lock', $lockinput);

        } else {
            session_tracking("Error", "Failed to retrieve 'recordId' or 'success' field.");
        }
    } else {
        // Handle JSON decode error
        session_tracking("Error", "Failed to decode JSON. JSON Error: " . json_last_error_msg());
    }
} else {
/// PRODUCTION MODE
    // Decode the JSON response directly
$responseData = json_decode($response, true);  // Assumes $response is already just the JSON body

// Log and check the response
session_tracking("responseData", print_r($responseData, true));
if ($responseData) {
    if (!empty($responseData['success']) && isset($responseData['recordId'])) {
        $recordId = $responseData['recordId'];
        session_tracking("Success - Record ID:", $recordId);
        
        $lockinput=['enrollment_data_id'=>$recordId, 'admin_id'=>$aid];
        session_tracking('trying profileLocked', $lockinput, '__NOREQUESTDATA__');
       
$account->profileLocked($userId, 'lock', $lockinput);  //line 164
    } else {
        session_tracking("Error", "Failed to retrieve 'recordId' or 'success' field.");
    }
} else {
    session_tracking("Error", "Failed to decode JSON. JSON Error: " . json_last_error_msg());
}

}


}

 // allow special tags to be handled
$search=array('{{email}}');
$replace=array($userDetails['email']??'{{email}}');
$output=str_replace($search, $replace,$output);

 #session_tracking('bgr_getprocessdetails', $output);
if (empty($noecho)) {
 if ($return=='js') echo $output;
 else $output= json_encode(json_decode($output), JSON_PRETTY_PRINT);
}


