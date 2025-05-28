<?php

$addClasses[] = 'AccessManager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$addcredjs = false;
$errormessage = '';
#-------------------------------------------------------------------------------
# HANDLE THE DATA ELEMENT FORM SUBMIT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    if ((isset($_POST['act']) && ($_POST['act'] == 'getdata')) && (isset($_POST['id']))) {
        list($amid, $userId, $companyId) = explode('/', $_POST['id'], 3); // Split the 'id' into user_id and company_id

        $datastore_action = 'retrieve_enrollment';

        $datastore_style = '
<style>
.credential-details-item{
margin-bottom: 15px !important; 
}   
</style>
';

        $accessmanager->logAccess($current_user_data['user_id'], $amid, 'retrieve_enrollment');
        include($_SERVER['DOCUMENT_ROOT'] . $dir['ampath'].'/accessmanager_dataaction.php');
        echo $outputcontent;
        exit;
    }


    if ((isset($_POST['act']) && ($_POST['act'] == 'updatesettings')) && ($qik->decodeId($_POST['uid']))) {
        $settings = [
            'email_notifications' => ['type' => 'boolean', 'default' => 'no'],
            'sms_notifications' => ['type' => 'boolean', 'default' => 'no'],
            'auto_delete_timeframe' => ['type' => 'string', 'default' => '1-year'],
            'weekly_reports' => ['type' => 'boolean', 'default' => 'no'],
            'auto_enroll' => ['type' => 'boolean', 'default' => 'yes'],
            'share_data_with_partners' => ['type' => 'boolean', 'default' => 'no']
        ];
    
        foreach($settings as $name => $config) {
            $value = match($config['type']) {
                'boolean' => isset($_POST[$name]) ? 'yes' : 'no',
                'string' => $_POST[$name] ?? $config['default'],
                default => $config['default']
            };
    
            $input = [
                'name' => $name,
                'description' => $value  // Store directly without JSON encoding
            ];
            $params['user_id'] = $qik->decodeId($_POST['uid']);
            $account->setUserAttribute($params['user_id'], $input);
        }
    }
 }






#-------------------------------------------------------------------------------
# HANDLE THE DATA ELEMENT FORM SUBMIT
#-------------------------------------------------------------------------------
if (!empty($_GET['getcontent'])) {
    $statuscontent = [
        'failed' => 'Failed enrollments indicate attempts that were not successful due to various errors or issues. These need to be reviewed and corrected for resubmission.',
        'pending' => 'Pending enrollments are those that have been submitted but are awaiting further processing or approval. These will be processed shortly.',
        'selected' => 'Selected enrollments have been chosen for further review or action. These are in the intermediate stage before moving to the next step.',
        'toenroll' => 'To enroll status indicates enrollments that are ready to be processed. These need immediate attention to proceed with the enrollment process.',
        'active' => 'Active enrollments are currently ongoing and in progress. These enrollments are being actively managed and tracked.',
        'success' => 'Successful enrollments have been completed without any issues. These enrollments have fully passed all stages of processing.',
        'existing' => 'Existing enrollments refer to those that are already in the system. No further action is required unless updates are needed.',
        'default' => 'Default status is used for enrollments that do not fit into other specific categories. These may need special attention or review.',
        'removed' => 'Removed enrollments have been deleted or withdrawn from the process. No further action is needed for these.',
        'total' => 'The total count of all enrollments in various statuses. This gives an overview of the entire enrollment process.'
    ];

    $requestedStatus = $_GET['getcontent'];
    if (array_key_exists($requestedStatus, $statuscontent)) {
        echo $statuscontent[$requestedStatus];
    } else {
        echo 'No information available for this status.';
    }
    exit;
}


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($dir['core_components'] . '/bg_user_profileheader.inc');


/*
$lefpanelcontent['prepanel']= '<div class="text-center">
<a class="small btn button btn-primary" href="/myaccount/select">Collect brands for enrollment</a>
</div>
<div class="text-center">
<a class="small btn button btn-primary" href="/myaccount/enrollment-schedule">Change Enrollment Schedule</a>
</div>';
*/


include($dir['core_components'] . '/bg_user_leftpanel.inc');


if (!empty($_REQUEST['id'])) {
    $output = '';
    $id = intval($_REQUEST['id']); // Make sure to sanitize the ID input

    // Get the record 
    $sql = "SELECT uc.*, c.company_name, c.appgoogle, c.appapple, ca.description AS company_logo 
FROM bg_user_companies uc
JOIN bg_companies c ON uc.company_id=c.company_id
LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id AND ca.category = 'company_logos' AND ca.grouping = 'primary_logo'
WHERE uc.user_company_id = ? AND uc.user_id = ? 
ORDER BY uc.modify_dt DESC";
    $stmt = $database->prepare($sql);
    $stmt->execute([$id, $current_user_data['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($app->formposted()) {
        if ($row) {
            $companyName = $row['company_name'];

            $sql = "UPDATE bg_user_companies SET modify_dt=NOW(), `status`='removed' WHERE user_company_id = :user_company_id AND user_id = :user_id";
            $stmt = $database->prepare($sql);
            $stmt->execute([':user_company_id' => $id, ':user_id' => $current_user_data['user_id']]);

            $transferpagedata['message'] = '<div class="alert alert-warning">' . $companyName . ' has been successfully removed from your gallery.</div>';
            $transferpagedata['url'] = '/myaccount/enrollment-history';
        } else {
            $output = "Company not found!";
        }

        $transferpagedata = $system->endpostpage($transferpagedata);
        exit;
    } else {
        if ($row) {
            $companyName = $row['company_name'];
            $output = "Are you sure you want to remove {$companyName} from your gallery?";
        } else {
            $output = "Company not found!";
        }

        echo $output;
        exit;
    }
}




$transferpagedata['message'] = $errormessage;

$transferpagedata = $system->startpostpage();


#breakpoint($transferpagedata);
$statusCounters = [
    'failed' => 0,
    'pending' => 0,
    'selected' => 0,
    'toenroll' => 0,
    'active' => 0,
    'success' => 0,
    'existing' => 0,
    'default' => 0,
    'removed' => 0,
    'total' => 0
];

$companies = [];

$results = $account->getbusinesslist($current_user_data,  'any', 0, true);
foreach ($results as $row) {
    $status = str_replace('-btn', '', $row['status']);
    $statusCounters[$status]++;
    $statusCounters['total']++;
    $companies[$status][] = $row;
}

$totalEnrollments = $statusCounters['total'];

$apptype = $current_user_data['profile_phone_type'] ?? '';
$displayType = isset($_GET['displayType']) ? $_GET['displayType'] : 'link';
$additionalstyles .= '
<style>
.nav-tabs .nav-link { padding: 10px 20px; width: auto; background-color: #f8f9fa; border: 1px solid #dee2e6; color: #6c757d;     /* Inactive tab text color */ }
.nav-tabs .nav-link.active { background-color: #e9ecef;     /* Subtle background color for active tab */ border-color: #dee2e6 #dee2e6 #fff; color: #007bff;     /* Primary color for active tab text */ }
.nav-tabs .nav-link.active .bi-info-circle-fill { color: #007bff;     /* Primary color for active tab icon */ }
.nav-tabs .nav-link.active .badge { background-color: #6c757d;     /* Darker gray background color for active badge */ color: #ffffff;     /* White color for active badge text */ }
.nav-tabs .nav-link .bi-info-circle-fill { color: #6c757d;     /* Gray color for inactive tab icon */ }
.nav-tabs .nav-link .badge { background-color: #dee2e6;     /* Light gray background color for inactive badge */ color: #6c757d;     /* Gray color for inactive badge text */ }
.form-control::placeholder { color: #b0b0b0;     /* Lighter gray placeholder color */ opacity: 1;     /* Override default opacity */ }
.row-hover:hover { background-color: #eee;     /* Light gray background color */ }
.muted-company { opacity: 0.8; color: #6c757d;     /* Muted text color */ }
.muted-company .company-name { color: inherit;     /* Inherits the muted color from the parent */ }
.muted-company img { filter: grayscale(100%); opacity: 0.8;     /* Ensure the image has the same opacity */ }
</style>
';


// main right section
echo '<div class="container main-content mt-0 pt-0" data-layout="container">';
echo '<div class="mb-3">

<h2 class="text-primary">Your Enrollments</h2>
' . $display->formaterrormessage($transferpagedata['message']) . '
</div>
';
echo '<div class="alert alert-info my-4 d-flex justify-content-between align-items-center" role="alert">';
echo '<span>You have used ' . $totalEnrollments . ' enrollments.';

if ($businessoutput['counts']['remaining'] > 0) {
    echo '<a class="btn btn-sm btn-primary py-1 ms-3" href="/myaccount/select">Pick more '.$website['biznames'].'</a>';
}

echo '</span>';



// Display PHONE type buttons
// -----------------------------------------------------
$icon = '';
if (strtolower($apptype) === 'android') {
    $icon = '<i class="bi bi-android2 text-success"></i>';
    $devicename='Adroid';
} elseif (strtolower($apptype) === 'iphone') {
    $icon = '<i class="bi bi-apple text-danger"></i>';
    $devicename='iPhone';
}

if ($icon != '') {
    $showapplinks = true;
    echo '<div>';
    echo $icon . ' ' . $devicename . ' | Display: ';
    echo '<a href="?displayType=link" class="btn btn-outline-secondary btn-sm me-2' . ($displayType === 'link' ? ' active' : '') . '">App Store</a>';
    echo '<a href="?displayType=qrcode" class="btn btn-outline-secondary btn-sm' . ($displayType === 'qrcode' ? ' active' : '') . '">QR Code</a>';
    echo '</div>';
} else {
    $showapplinks = false;
    echo '<div class="text-danger">
    <a href="/myaccount/profile" class="text-danger text-decoration-none">
      <span data-bs-toggle="tooltip" data-bs-placement="top" title="Complete your Enrollment Profile to display App Store links">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        No phone type selected
      </span>
    </a>
  </div>';

}
echo '</div>';



// Tab Navigation
// -----------------------------------------------------
$firstPanel = true;

$statusDetails = [
    'failed' => [
        'label' => 'Failed',
        'color' => 'danger',
        'icon' => 'bi-x-circle',
        'allowapplink' => false,
        'allowremoval' => true,
        'allowsearchbar' => true,
    ],
    'pending' => [
        'label' => 'Pending',
        'color' => 'warning',
        'icon' => 'bi-hourglass-split',
        'allowapplink' => true,
        'allowremoval' => true,
        'allowsearchbar' => true,
    ],
    'selected' => [
        'label' => 'Selected',
        'color' => 'primary',
        'icon' => 'bi-check-circle',
        'allowapplink' => true,
        'allowremoval' => true,
        'allowsearchbar' => true,
    ],
    'toenroll' => [
        'label' => 'To Enroll',
        'color' => 'info',
        'icon' => 'bi-clipboard-data',
        'allowapplink' => true,
        'allowremoval' => true,
        'allowsearchbar' => true,
    ],
    'active' => [
        'label' => 'Active',
        'color' => 'success',
        'icon' => 'bi-play-circle',
        'allowapplink' => true,
        'allowremoval' => true,
        'allowsearchbar' => true,
    ],
    'success' => [
        'label' => 'Enrolled',
        'color' => 'success',
        'icon' => 'bi-check-circle-fill',
        'allowapplink' => true,
        'allowremoval' => false,
        'allowsearchbar' => true,
    ],
    'existing' => [
        'label' => 'Existing',
        'color' => 'secondary',
        'icon' => 'bi-file-earmark-text',
        'allowapplink' => true,
        'allowremoval' => false,
        'allowsearchbar' => true,
    ],
    'default' => [
        'label' => 'Default',
        'color' => 'secondary',
        'icon' => 'bi-gear',
        'allowapplink' => true,
        'allowremoval' => true,
        'allowsearchbar' => true,
    ],
    'removed' => [
        'label' => 'Removed',
        'color' => 'dark',
        'icon' => 'bi-trash',
        'allowapplink' => false,
        'allowremoval' => false,
        'allowsearchbar' => false,
    ]
    // 'total' is excluded as it doesn't need a tab representation
];



echo '<ul class="nav nav-tabs mb-4" id="statusTabs" role="tablist">';
foreach ($statusCounters as $status => $count) {
    if ($status != 'total' && $count > 0) {
        $details = $statusDetails[$status];
        echo '<li class="nav-item" role="presentation">';
        echo '<button class="nav-link ' . ($firstPanel ? 'active' : '') . ' px-5 text-' . $details['color'] . ' id="tab-' . htmlspecialchars($status) . '" data-bs-toggle="tab" data-bs-target="#section-' . htmlspecialchars($status) . '" type="button" role="tab" aria-controls="section-' . htmlspecialchars($status) . '" aria-selected="' . ($firstPanel ? 'true' : 'false') . '">';
        echo '<i class="bi bi-info-circle-fill me-2" data-bs-toggle="modal" data-bs-target="#infoModal" data-status="' . htmlspecialchars($status) . '" style="cursor: pointer;"></i>';
        echo htmlspecialchars($details['label']) . ' <span class="badge ms-2 mt-0">' . $count . '</span>';
        echo '</button>';
        echo '</li>';
        $firstPanel = false;
    }
}


// settings tab
// -----------------------------------------------------
echo '
<li class="nav-item" role="presentation">
<button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false"><i class="bi bi-gear me-2"></i>Settings</button>
</li>
';
echo '</ul>';

echo '<div class="tab-content" id="statusTabsContent">';
$firstPanel = true;
foreach ($statusCounters as $status => $count) {

    if ($status != 'total' && $count > 0) {
        echo '
<!-- ================================================================================================================= -->
<div class="tab-pane fade ' . ($firstPanel ? 'show active' : '') . '" id="section-' . htmlspecialchars($status) . '" role="tabpanel" aria-labelledby="tab-' . htmlspecialchars($status) . '">';
        if ($count > 5) {
            $rowpadding = 'py-1';
            $companytextsize = 'h5';
            if ($statusDetails[$status]['allowsearchbar'] !== false) {
                echo '<div class="input-group mb-4">';
                echo '<span class="input-group-text"><i class="bi bi-search"></i></span>';
                echo '<input type="text" id="searchBar-' . htmlspecialchars($status) . '" class="form-control search-input" data-status="' . htmlspecialchars($status) . '" placeholder="Search ' . htmlspecialchars($statusDetails[$status]['label']) . ' enrollments">';
                echo '<span class="input-group-text clear-icon" style="cursor: pointer; display: none;" data-status="' . htmlspecialchars($status) . '"><i class="bi bi-x-circle-fill"></i></span>';
                echo '<span class="input-group-text"><i class="bi bi-sort-alpha-down"></i></span>';
                echo '</div>';
            }
        } else {
            $rowpadding = 'py-4';
            $companytextsize = 'h4';
        }
        echo '<ul class="list-group">';
        foreach ($companies[$status] as $company) {
             $statusline='';
            
            #if ($company['company_id'] == 1955) breakpoint($company);
            $companylineclass = '';
            $highlightrowclass = 'row-hover';
            // Status-related Information
            if ($company['status'] == 'failed') {
                $statusline = '<p class="text-danger mb-0"><i>' . htmlspecialchars($company['reason']) . '</i></p>';
            }

            // Add enrollment date if status is "success"
            if ($company['status'] == 'success' && isset($company['registration_dt'])) {
                $timeago = $qik->timeago($company['registration_dt']);
                $statusline = '<p class="text-muted mb-0 mt-0 fw-light small">Enrolled: ' . $timeago['message'] . '</p>';
            } else {
                // Add enrollment date if status is "pending"
                if ($company['status'] == 'pending' && isset($company['create_dt'])) {
                    $timeago = $qik->timeago($company['create_dt']);
                    $statusline = '<p class="text-muted mb-0 mt-0 fw-light small">Selected: ' . $timeago['message'] . '</p>';
                }
                // Add enrollment date if status is "removed"
                if ($company['status'] == 'removed' && isset($company['modify_dt'])) {
                    $timeago = $qik->timeago($company['modify_dt']);
                    $statusline = '<p class="text-muted mb-0 mt-0 fw-light small">Removed: ' . $timeago['message'] . '</p>';
                    $companylineclass = 'muted-company';
                    $highlightrowclass = '';
                }
                           // Add enrollment date if status is "selected"
                           if ($company['status'] == 'selected' && isset($company['create_dt'])) {
                            $timeago = $qik->timeago($company['create_dt']);
                            $statusline = '<p class="text-muted mb-0 mt-0 fw-light small">Selected: ' . $timeago['message'] . '</p>';
                        }
            }


            $description = isset($company['description']) ? $company['description'] : '';
            echo '<li class="list-group-item  ' . $highlightrowclass . '" data-full-context="' . trim(strtolower($company['company_name'])) . '" data-status="' . htmlspecialchars($status) . '">';

            echo '<div class="row ' . $rowpadding . '" >';


            // Company Logo and Name
            if ($account->isadmin()) $companyidtag = 'title="' . $company['company_id'] . '"';
            else $companyidtag = '';
            echo '<div class="col-12 col-md-9 d-flex align-items-center ' . $companylineclass . '">';
            echo '<img ' . $companyidtag . ' src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="' . htmlspecialchars($company['company_name']) . ' Logo" class="me-4" 
style="width: 60px; height: 60px;">';
            echo '<div>';
            echo '<div class="' . $companytextsize . ' text-wrap fw-bold mb-1 company-name">' . htmlspecialchars($company['company_name']) . '</div>';

            echo $statusline;

            echo '</div>'; // End of div wrapping company name and status information
            echo '</div>'; // End of col-12 col-md-9

            // Buttons Group (Remove and QR Code)
            echo '<div class="col-12 col-md-3 d-flex justify-content-end align-items-center flex-wrap">';
            


            // Display App Store link or QR Code
            if ($showapplinks) {
                $applink = $display->applink($apptype, $company);
                
                // Check if we should display the App Store link
                if ($displayType == 'link' && !empty($applink['url']) && $statusDetails[$status]['allowapplink'] !== false) {
                    echo $applink['applink'];
                } 
                // Check if we should display the QR Code
                elseif ($displayType == 'qrcode' && !empty($applink['qrlink_url']) && $statusDetails[$status]['allowapplink'] !== false) {
                    echo '<button type="button" class="btn btn-outline-primary btn-sm my-2" data-bs-toggle="modal" data-bs-target="#qrCodeModal" data-qr="' . htmlspecialchars($applink['qrlink_url']) . '">Show QR Code</button>';
                }
            }
            
            

            if ($statusDetails[$status]['allowremoval']) {
                echo '<span class=" my-2 p-0" data-bs-toggle="tooltip" title="Remove this brand from your selection list">
<button type="button" class="btn btn-outline-danger btn-sm ms-2" 
data-bs-toggle="modal" data-bs-target="#removeEnrollmentModal" 
data-company-name="' . htmlspecialchars($company['company_name']) . '" data-user-company-id="' . $qik->encodeId($company['user_company_id']) . '" >
<i class="bi bi-trash3"></i></button></span>';
            }
            /*
if ($status == 'success') {
echo '<span class=" my-2 p-0" data-bs-toggle="tooltip" title="Show credentials for this enrollment">
<button type="button" class="btn btn-secondary btn-sm ms-2" 
data-bs-toggle="modal" data-bs-target="#credentialModal" 
data-company-name="' . htmlspecialchars($company['company_name']) . '" data-user-company-id="' . htmlspecialchars($company['user_company_id']) . '">
<i class="bi bi-key"></i></button></span>';
}
*/
            if ($status == 'success') {
                #     breakpoint($company);
                if (!empty($company['amid'])) {
                    $amlink = ($company['amid'] ?? '') . '/' . $company['user_id'] . '/' . $company['company_id'];
                    #    list($amid, $userId, $companyId) 

                    echo '<span class="my-2 p-0" data-bs-toggle="tooltip" title="Show credentials for this enrollment">
<button type="button" class="btn btn-secondary btn-sm ms-2" 
data-bs-toggle="modal" data-bs-target="#credentialModal" 
data-company-name="' . htmlspecialchars($company['company_name']) . '" 
data-user-company-id="' . htmlspecialchars($company['user_company_id']) . '"
data-credlink-id="' . htmlspecialchars($amlink) . '">
<i class="bi bi-key"></i></button></span>';
                }
            }


            echo '</div>'; // End of col-12 col-md-3
            echo '</div>'; // End of row
            echo '</li>';
        }
        echo '</ul>';

        echo '</div>';
        $firstPanel = false;
    }
}



// MODAL FOR REMOVING ENROLLMENT
echo '<div class="modal fade" id="removeEnrollmentModal" tabindex="-1" aria-labelledby="removeEnrollmentModalLabel" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title" id="removeEnrollmentModalLabel">Remove Enrollment</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4 py-5">
<p id="modalMessage" class="fw-bold h4">Are you sure you want to remove "<span id="modalCompanyName"></span>" from your enrollment selection?</p>

<p class="fw-light small mt-5">You will be automatically credited a new enrollment so you can select a different brand.</p>

</div>
<div class="modal-footer">
<form method="post" action="/myaccount/myaccount_actions/remove_enrollment">
' . $display->inputcsrf_token() . '
<input type="hidden" name="ucid" id="modalUserCompanyId" value="">
<button type="submit" class="btn btn-danger px-5 me-2">Yes</button>
<button type="button" class="btn btn-secondary px-5" data-bs-dismiss="modal">No</button>
</form>
</div>
</div>
</div>
</div>';
// script for modal
echo "
<script>
var removeEnrollmentModal = document.getElementById('removeEnrollmentModal');
removeEnrollmentModal.addEventListener('show.bs.modal', function (event) {
var button = event.relatedTarget;
var companyName = button.getAttribute('data-company-name');
var userCompanyId = button.getAttribute('data-user-company-id');

var modalCompanyName = removeEnrollmentModal.querySelector('#modalCompanyName');
var modalUserCompanyId = removeEnrollmentModal.querySelector('#modalUserCompanyId');

modalCompanyName.textContent = companyName;
modalUserCompanyId.value = userCompanyId;
});
</script>
";



// settings content
// -----------------------------------------------------
// Get all settings values before the form
$settings = [
    'email_notifications' => ['type' => 'boolean', 'default' => 'no'],
    'sms_notifications' => ['type' => 'boolean', 'default' => 'no'],
    'auto_delete_timeframe' => ['type' => 'string', 'default' => '1-month'],
    'weekly_reports' => ['type' => 'boolean', 'default' => 'no'],
    'auto_enroll' => ['type' => 'boolean', 'default' => 'yes'],
    'share_data_with_partners' => ['type' => 'boolean', 'default' => 'no']
];

$bg_user_attributes = [];
foreach($settings as $name => $config) {
    $attr = $account->get_user_attribute($current_user_data['user_id'], $name);
    // If attribute exists, decode the description, otherwise use default
    $bg_user_attributes[$name] = !empty($attr) ? 
        $attr['description']: 
        $config['default'];
}

echo '
<div class="tab-pane fade show" id="settings" role="tabpanel" aria-labelledby="settings-tab">
    <div class="container mt-5">
        <form action="" method="POST">
        '.$display->input_csrftoken().'
        <input type="hidden" name="uid" id="uid" value="'.$qik->encodeId($current_user_data['user_id']).'">
        <input type="hidden" name="act" id="act" value="updatesettings">
            <h2 class="mb-4">Settings</h2>

            <!-- Notifications Section -->
            <div class="card mb-4">
                <div class="card-header">
                    Notifications
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" name="email_notifications" ' . (isset($bg_user_attributes['email_notifications']) && $bg_user_attributes['email_notifications'] ? 'checked' : '') . '>
                        <label class="form-check-label" for="emailNotifications">
                            Notify me via email
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="smsNotifications" name="sms_notifications" ' . (isset($bg_user_attributes['sms_notifications']) && $bg_user_attributes['sms_notifications'] ? 'checked' : '') . '>
                        <label class="form-check-label" for="smsNotifications">
                            Notify me via SMS
                        </label>
                    </div>
                </div>
            </div>

            <!-- Scheduling Section -->
            <div class="card mb-4">
                <div class="card-header">
                    Scheduling
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="/myaccount/enrollment-schedule" class="btn btn-primary">Change Enrollment Schedule</a>
                    </div>
                </div>
            </div>

            <!-- Auto-delete Section -->
            <div class="card mb-4">
                <div class="card-header">
                    Auto-delete
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="autoDeleteTimeframe" class="form-label">Delete old records after</label>
                        <select class="form-select" id="autoDeleteTimeframe" name="auto_delete_timeframe">
                            <option value="1-month" ' . (isset($bg_user_attributes['auto_delete_timeframe']) && $bg_user_attributes['auto_delete_timeframe'] == '1-month' ? 'selected' : '') . '>1 Month</option>
                            <option value="6-months" ' . (isset($bg_user_attributes['auto_delete_timeframe']) && $bg_user_attributes['auto_delete_timeframe'] == '6-months' ? 'selected' : '') . '>6 Months</option>
                            <option value="1-year" ' . (isset($bg_user_attributes['auto_delete_timeframe']) && $bg_user_attributes['auto_delete_timeframe'] == '1-year' ? 'selected' : '') . '>1 Year</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Other Settings Section -->
            <div class="card mb-4">
                <div class="card-header">
                    Other Settings
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="weeklyReports" name="weekly_reports" ' . (isset($bg_user_attributes['weekly_reports']) && $bg_user_attributes['weekly_reports'] ? 'checked' : '') . '>
                        <label class="form-check-label" for="weeklyReports">
                            Receive weekly reports
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="autoEnroll" name="auto_enroll" ' . (isset($bg_user_attributes['auto_enroll']) && $bg_user_attributes['auto_enroll'] ? 'checked' : '') . '>
                        <label class="form-check-label" for="autoEnroll">
                            Enable auto-enrollment for upcoming birthdays
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="shareDataWithPartners" name="share_data_with_partners" ' . (isset($bg_user_attributes['share_data_with_partners']) && $bg_user_attributes['share_data_with_partners'] ? 'checked' : '') . '>
                        <label class="form-check-label" for="shareDataWithPartners">
                            Share data with partners
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="text-center">
                <button type="submit" class="btn btn-primary" name="save_settings">Save Settings</button>
            </div>
        </form>
    </div>
</div>
';



// end of tab content
// -----------------------------------------------------
echo '</div></div>';


echo '<!-- Modal Structure -->
<div class="modal fade" id="credentialModal" tabindex="-1" aria-labelledby="credentialModalLabel" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title fw-bold" id="credentialModalLabel">Credentials</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-5">
<!-- Content will be loaded here via AJAX -->
<p>Loading...</p>
</div>
</div>
</div>
</div>


<!-- Modal Structure for Status Info -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title" id="infoModalLabel">Status Information</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4 py-5" id="infoModalContent">
<!-- Content will be loaded dynamically -->
</div>
</div>
</div>
</div>

';


$csrfToken = $display->inputcsrf_token('tokenonly');
?>
</div>
</div>
</div>
</div>
</main>

<!-- Modal Structure for QR Code -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrCodeModalLabel">QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <p class="p-4">Scan this QR code with your phone to download from the App Store.</p>
            <div class="modal-body text-center mb-5">
                <img id="qrCodeImage" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100' style='margin:auto;background:none;display:block'%3E%3Ccircle cx='50' cy='50' r='40' stroke='%23000000' stroke-width='10' fill='none' stroke-linecap='round'/%3E%3CanimateTransform attributeName='transform' type='rotate' repeatCount='indefinite' dur='1s' values='0 50 50;360 50 50'%3E%3C/animateTransform%3E%3C/svg%3E" alt="Loading QR Code" style="width: 200px; height: 200px;" loading="lazy">
            </div>
        </div>
    </div>
</div>


<script>
    // Modal for QR Code
    var qrCodeModal = document.getElementById('qrCodeModal');
    qrCodeModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var qrLink = button.getAttribute('data-qr');
        var modalImage = qrCodeModal.querySelector('#qrCodeImage');
        modalImage.src = qrLink;
    });

    </script>

<script>

    var infoModal = document.getElementById('infoModal');
    infoModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var status = button.getAttribute('data-status');
        var modalContent = infoModal.querySelector('#infoModalContent');
        // Make an AJAX request to get the content
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'enrollment-history?getcontent=' + status, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                modalContent.innerHTML = xhr.responseText;
            } else {
                modalContent.innerHTML = 'Unable to retrieve details.';
            }
        };
        xhr.send();
    });

    </script>

    <script>
    // credentials modal
    document.addEventListener('DOMContentLoaded', function() {
        // Get the CSRF token from the page
        var csrfToken = '<?php echo $csrfToken; ?>';

        // Event listener for button clicks that trigger the modal
        $('#credentialModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var userCompanyId = button.data('user-company-id'); // Extract the user_company_id
            var companyName = button.data('company-name'); // Extract the company name
            var credlinkid = button.data('credlink-id'); // Extract the user_company_id

            var modal = $(this);
            modal.find('.modal-title').text('Your Credentials for ' + companyName);

            // Perform AJAX to get the data
            $.ajax({
                type: 'POST',
                url: '', // Use an empty string to refer to the current page
                data: {
                    act: 'getdata',
                    id: credlinkid,
                    _token: csrfToken // Include the CSRF token in the data
                },
                success: function(response) {
                    // Load the response into the modal body
                    modal.find('.modal-body').html(response);
                },
                error: function() {
                    modal.find('.modal-body').html('<p>Error loading data.</p>');
                }
            });
        });
    });



    // Search functionality
    $(document).ready(function() {
        $('.search-input').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            var status = $(this).data('status');
            // console.log('Search value:', value);
            //  console.log('Status:', status);

            $('#section-' + status + ' .list-group-item').each(function() {
                var itemText = $(this).data('full-context').toLowerCase();
                //       console.log('Item text:', itemText);
                //       console.log('List item:', this);
                if (itemText.includes(value)) {
                    $(this).show();
                    $(this).css('display', ''); // Remove the inline display style if present
                    //            console.log('Show item:', this);
                } else {
                    $(this).hide();
                    $(this).attr('style', 'display: none !important;'); // Force hide the element
                    //           console.log('Hide item:', this);
                }
            });
        });

        $('.clear-icon').on('click', function() {
            var status = $(this).data('status');
            //   console.log('Clear search for status:', status);
            $('#searchBar-' + status).val('').trigger('keyup');
        });
    });
</script>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
