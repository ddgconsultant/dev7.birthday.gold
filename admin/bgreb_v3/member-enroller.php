<?php
//Member Enrollment Process Page
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# UTILITY FUNCTIONS
#-------------------------------------------------------------------------------

/**
 * Safely output text with HTML escaping
 */
function safe_echo($value, $default = '') {
    if (isset($value) && $value !== null && $value !== '') {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
    return $default;
}

/**
 * Safely get an array element with a default value
 */
function safe_array_get($array, $key, $default = '') {
    if (is_array($array) && isset($array[$key])) {
        return $array[$key];
    }
    return $default;
}

/**
 * Safely apply a function to an array element
 */
function safe_array_process($array, $key, $function, $default = '') {
    if (is_array($array) && isset($array[$key]) && $array[$key] !== null) {
        return $function($array[$key]);
    }
    return $default;
}

#-------------------------------------------------------------------------------
# PREP VARIABLES & VALIDATION
#-------------------------------------------------------------------------------

$errormessage = '';
$show_error_page = false;
$redirect_url = '';

// Validate essential URL parameters
$userId = isset($_REQUEST['uid']) ? $qik->decodeId($_REQUEST['uid']) : null;
$aid = isset($_REQUEST['aid']) ? $qik->decodeId($_REQUEST['aid']) : null;
$bid = isset($_REQUEST['bid']) ? $qik->decodeId($_REQUEST['bid']) : null;

// Early validation of critical parameters
if (empty($userId) || empty($aid)) {
    $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error:</strong> Missing or invalid required parameters for enrollment.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    $show_error_page = true;
    $redirect_url = '/admin/bgreb_v3/enrollment-listv2';
}

if (!$show_error_page) {
    // Fetch user data with validation
    $working_user_data = $account->getuserdata($userId, 'user_id');
    $admin_user_data = $account->getuserdata($aid, 'user_id');

    // Validate that we got valid user data back
    if (empty($working_user_data) || empty($admin_user_data)) {
        error_log("Unable to retrieve user data: working_user_id=$userId, admin_user_id=$aid");
        
        $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> The specified user accounts could not be found or accessed.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        $show_error_page = true;
        $redirect_url = '/admin/bgreb_v3/enrollment-listv2';
    }
}

if (!$show_error_page) {
    // Get member details and pending enrollments
    $sql = "SELECT DISTINCT u.user_id, u.birthdate, u.profile_username, u.profile_email, u.profile_first_name, u.profile_last_name, u.profile_mailing_address,
    u.profile_city, u.profile_state, u.profile_zip_code, u.profile_phone_number, uc.company_id, c.company_name, c.company_display_name, c.`status` as company_status, 
    uc.`status` as enrollment_status, uc.reason, uc.modify_dt, c.signup_url, c.description, 
    GROUP_CONCAT(CONCAT(CASE WHEN ca.name IS NOT NULL THEN CONCAT('**', ca.name, '**: ') ELSE '' END, COALESCE(ca.description, ''), CASE WHEN ca.grouping IS NOT NULL THEN CONCAT(' [', ca.grouping, ']') ELSE '' END) ORDER BY COALESCE(ca.rank, '999999') ASC, ca.create_dt ASC SEPARATOR '\n') as enrollment_hints
    FROM bg_users AS u
    INNER JOIN bg_user_companies AS uc ON u.user_id = uc.user_id 
    INNER JOIN bg_companies AS c ON uc.company_id = c.company_id AND c.`status`='finalized'
    LEFT JOIN bg_company_attributes AS ca ON c.company_id = ca.company_id AND ca.type = 'enroller-hint' AND ca.`status` = 'active'
    WHERE u.user_id = :user_id AND u.status = 'active'
    GROUP BY u.user_id, u.birthdate, u.profile_username, u.profile_email, u.profile_first_name, u.profile_last_name, u.profile_mailing_address, u.profile_city, u.profile_state, u.profile_zip_code, u.profile_phone_number, uc.company_id, uc.status, uc.reason, uc.modify_dt, c.company_name, c.company_display_name, c.status, c.signup_url, c.description
    ORDER BY c.company_name ASC;";

    $stmt = $database->prepare($sql);
    $stmt->execute([
        'user_id' => $userId
    ]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($enrollments)) {
        $errormessage = '<div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>Notice:</strong> No pending enrollments found for this member.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        $show_error_page = true;
        $redirect_url = '/admin/bgreb_v3/enrollment-list';
    }
}

// Validate business ID if provided
if (!$show_error_page && !empty($bid)) {
    $business = $app->getcompanydetails($bid);
    if (!$business || !is_array($business)) {
        // Invalid business ID, but not a fatal error - we'll just show the enrollment list
        $errormessage = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Warning:</strong> The specified business could not be found.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        $bid = null; // Reset to show enrollment list instead
    }
}

// Display error page if needed and exit
if ($show_error_page) {
    $bodycontentclass = '';
    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');
    echo '<section class="pt-4 main-content"><div class="container mt-4">' . $errormessage;
    
    if (!empty($redirect_url)) {
        echo '<p class="text-center mt-4">Redirecting to enrollment list in 3 seconds...</p>';
        echo '<script>
            setTimeout(function() {
                window.location.href = "' . $redirect_url . '";
            }, 3000);
        </script>';
    }
    
    echo '</div></section>';
    include($dir['core_components'] . '/bg_footer.inc');
    exit;
}

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    session_tracking('form submitted', $_REQUEST);
    include($_SERVER['DOCUMENT_ROOT'] . '/admin/bgreb_v3/components/memberenroller-formhandling.inc');

    $action = $_REQUEST['action'] ?? null;
    $post_bid = isset($_REQUEST['bid']) ? $qik->decodeId($_REQUEST['bid']) : null;
    
    // Process form actions only with valid parameters
    if (empty($post_bid) || empty($action)) {
        $errormessage = '<div class="alert alert-danger alert-dismissible fade show" id="autoCloseAlert" role="alert">
            <strong>Error:</strong> Missing or invalid parameters for the requested action.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    } else {
        $business = $app->getcompanydetails($post_bid);
        
        if (!$business || !is_array($business)) {
            $errormessage = '<div class="alert alert-danger alert-dismissible fade show" id="autoCloseAlert" role="alert">
                <strong>Error:</strong> The specified business could not be found.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        } else {
            switch ($action) {
                case 'add_hint':
                    session_tracking('submitting add-hint', $post_bid . '/' . $aid);
                    
                    if (empty($_POST['hint_description'])) {
                        $errormessage = '<div class="alert alert-danger alert-dismissible fade show" id="autoCloseAlert" role="alert">
                            Hint description is required.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                    } else {
                        $result = handle_add_hint($post_bid, $_POST);
                        if (empty($result)) {
                            $errormessage = '<div class="alert alert-danger alert-dismissible fade show" id="autoCloseAlert" role="alert">
                                There was a problem saving the hint for: ' . safe_echo($business['company_name'], 'Unknown Company') . '.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                        } else {
                            $errormessage = '<div class="alert alert-success alert-dismissible fade show" id="autoCloseAlert" role="alert">
                                Hint was successfully saved for: ' . safe_echo($business['company_name'], 'Unknown Company') . '.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                        }
                    }
                    break;

                case 'enrollment_success':
                    $post_data['registration_data'] = json_encode($enrollments);
                    session_tracking('submitting enrollment_success', $post_bid . '/' . $userId . '/' . $aid);
                    $result = handle_enrollment_success($post_bid, $working_user_data, $admin_user_data, $post_data);
                    
                    if (empty($result)) {
                        $errormessage = '<div class="alert alert-danger alert-dismissible fade show" id="autoCloseAlert" role="alert">
                            There was a problem saving "success" status for: ' . safe_echo($business['company_name'], 'Unknown Company') . '.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                    } else {
                        $errormessage = '<div class="alert alert-success alert-dismissible fade show" id="autoCloseAlert" role="alert">
                            Enrollment "success" status recorded for: ' . safe_echo($business['company_name'], 'Unknown Company') . '.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                    }
                    break;

                case 'enrollment_failure':
                    $reason = $_POST['failureReason'] ?? null;
                    
                    if (empty($reason)) {
                        $errormessage = '<div class="alert alert-danger alert-dismissible fade show" id="autoCloseAlert" role="alert">
                            A failure reason must be provided.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                    } else {
                        // Handle custom "other" reason
                        if ($reason === 'other' && !empty($_POST['customFailureReason'])) {
                            $reason = htmlspecialchars($_POST['customFailureReason']);
                        }
                        
                        $post_data['registration_data'] = json_encode($enrollments);
                        session_tracking('submitting enrollment_failure', $post_bid . '/' . $userId . '/' . $aid . '/' . $reason);
                        $result = handle_enrollment_failure($post_bid, $working_user_data, $admin_user_data, $reason, $post_data);
                        
                        if (empty($result)) {
                            $errormessage = '<div class="alert alert-danger alert-dismissible fade show" id="autoCloseAlert" role="alert">
                                There was a problem saving "failure" status for: ' . safe_echo($business['company_name'], 'Unknown Company') . '.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                        } else {
                            $errormessage = '<div class="alert alert-success alert-dismissible fade show" id="autoCloseAlert" role="alert">
                                Enrollment "failure" status recorded for: ' . safe_echo($business['company_name'], 'Unknown Company') . '.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                        }
                    }
                    break;

                default:
                    $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error:</strong> Unknown or invalid action requested.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
                    break;
            }
        }
    }

    $url = $_SERVER['PHP_SELF'] . '?uid=' . $qik->encodeId($userId) . '&aid=' . $qik->encodeId($aid);
    if (!empty($post_bid)) {
        $url .= '&bid=' . $qik->encodeId($post_bid);
    }
    
    $transferpagedata['message'] = $errormessage;
    $transferpagedata['url'] = $url;
    $transferpagedata = $system->endpostpage($transferpagedata);
}

#-------------------------------------------------------------------------------
# GET PAGE DATA
#-------------------------------------------------------------------------------

// If we got this far, we have valid user data and enrollments
$userProfile = $enrollments[0]; // Member details are the same in all rows

// If a business ID is provided, get full company details
if (!empty($bid)) {
    $business = $app->getcompanydetails($bid);
    
    // Get user-company relationship for this business
    $sql = 'SELECT user_company_id FROM bg_user_companies WHERE user_id = :userId AND company_id = :bid';
    $stmt = $database->prepare($sql);
    $stmt->execute([
        'userId' => $userId,
        'bid' => $bid
    ]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    $eid = safe_array_get($enrollment, 'user_company_id', '0');
    
    // Get enrollment status for this business
    $bidenrollmentstatus = null;
    $bidenrollmentreason = null;
    $matching = array_filter($enrollments, function ($enrollment) use ($bid) {
        return $enrollment['company_id'] == $bid;
    });
    
    if (!empty($matching)) {
        $match = reset($matching);
        $bidenrollmentstatus = safe_array_get($match, 'enrollment_status');
        $bidenrollmentreason = safe_array_get($match, 'reason');
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

// Add container header with member and agent names
echo '<section class="pt-4 main-content">
<div class="container-fluid mt-4">
<div class="row">
';

#-------------------------------------------------------------------------------
$emailbox_button = '';
if (!empty($working_user_data['feature_email'])) {
    $emailbox_button = '<a href="/myaccount/mail-box?uid=' . $qik->encodeId($working_user_data['user_id']) . '" target="_blank" class="btn btn-primary me-2"><i class="bi bi-envelope"></i> Email</a>';
}

$first_name = safe_array_get($working_user_data, 'first_name', '');
$last_name = safe_array_get($working_user_data, 'last_name', '');

echo '
<div class="col">
<div class="mb-3">
<div class="d-flex justify-content-between">
<div>
<h2 class="mb-2">
<i class="bi bi-person-circle"></i> 
Enrolling Member: ' . safe_echo($first_name . ' ' . $last_name) . '
</h2>
' . $emailbox_button . '
<a href="/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($working_user_data['user_id']) . '&aid=' . $qik->encodeId($admin_user_data['user_id']) . '&_token=' . $display->inputcsrf_token('tokenonly') . '"  target="_blank" class="btn btn-secondary" type="button"><i class="bi bi-person-fill"></i> Impersonate</a>
   
</div>
';

// Display any alerts
echo '
<div class="col-md-4 text-end">
' . $display->formaterrormessage($transferpagedata['message']) . '
</div>';

$admin_first_name = safe_array_get($admin_user_data, 'first_name', '');
$admin_last_name = safe_array_get($admin_user_data, 'last_name', '');

echo '
<div class="text-end pe-3 d-flex flex-column" style="gap: 1rem;">
  <a href="/admin/redirect-enrollments" class="btn btn-sm btn-light">
    <i class="bi bi-arrow-left"></i> Back to Enrollment List
  </a>

  <p class="text-muted mb-0 small">
    <i class="bi bi-person-workspace mx-2"></i> 
    Enrollment Agent: ' . safe_echo($admin_first_name . ' ' . $admin_last_name) . '
  </p>
</div>';
echo '
</div>
</div> 
';

echo '  </div> 
<div class="row">';

#-------------------------------------------------------------------------------
echo '
<!-- Left Panel - Enrollment List -->
<div class="col-md-3">
';

echo '
<div class="accordion accordion enrollment-accordion" id="enrollmentsAccordion">
<!-- Main Header -->
<div class="accordion-item">
<h2 class="accordion-header">
<button class="accordion-button bg-primary text-white fw-bold py-2" type="button" disabled>
<h5 class="card-title mb-0"> Enrollments (' . count($enrollments) . ')</h5>
</button>
</h2>
</div>';

// Define status colors/labels at the top
$statusLabels = [
    'selected' => ['label' => 'Pending', 'class' => 'bg-warning'],
    'pending' => ['label' => 'Pending', 'class' => 'bg-warning'],
    'queued' => ['label' => 'Queued', 'class' => 'bg-primary'],
    'failed' => ['label' => 'Failed', 'class' => 'bg-danger'],
    'failed-form' => ['label' => 'Failed-form', 'class' => 'bg-danger'],
    'removed' => ['label' => 'Removed', 'class' => 'bg-secondary'],
    'success-btn' => ['label' => 'Success-btn', 'class' => 'bg-success'],
    'default' => ['label' => 'Unknown', 'class' => 'bg-secondary']
];

// Filter enrollments by status
global $website;
$pendingEnrollments = array_filter($enrollments, function ($e) use ($website) {
    return isset($e['enrollment_status']) && $e['enrollment_status'] === 'selected' && 
           isset($e['signup_url']) && $e['signup_url'] !== $website['apponlytag'];
});

$otherEnrollments = array_filter($enrollments, function ($e) {
    return isset($e['enrollment_status']) && $e['enrollment_status'] !== 'selected';
});

function linkmaker($enrollment, $userId, $admin_user_data, $type = 'x') {
    global $qik;
    switch ($type) {
        case 'js':
            return "  <a href='" . htmlspecialchars($enrollment['signup_url'] ?? '#') . "' 
onclick='openEnrollmentWindow(`" . htmlspecialchars($enrollment['signup_url'] ?? '#') . "`, `" . $userId . "`, `" . $admin_user_data['user_id'] . "`); return false;'  class='list-group-item list-group-item-action business-item'>";
            break;
        case 'x':
            return '   <a href="member-enroller?bid=' . $qik->encodeId($enrollment['company_id']) .  '&uid=' . $qik->encodeId($userId) . '&aid=' . $qik->encodeId($admin_user_data['user_id']) . '"' . "
onclick='openEnrollmentWindow(`" . htmlspecialchars($enrollment['signup_url'] ?? '#') . "`, `" .  $userId . "`, `" . $admin_user_data['user_id'] . "`, `" . $enrollment['company_id'] . "`);  '  class='list-group-item list-group-item-action business-item'>";
        default:
            return '  <a href="member-enroller?bid=' . $qik->encodeId($enrollment['company_id']) .  '&uid=' . $qik->encodeId($userId) . '&aid=' . $qik->encodeId($admin_user_data['user_id']) . '" 
class="list-group-item list-group-item-action business-item">
';
            break;
    }
}

// Define enrollment sections configuration
$sections = [
    'pending' => [
        'id' => 'pendingEnrollments',
        'title' => 'Pending Enrollments',
        'data' => $pendingEnrollments,
        'expanded' => true,
        'button_class' => 'accordion-button enrollementlist',
    ],
    'other' => [
        'id' => 'otherEnrollments',
        'title' => 'Other Enrollments',
        'data' => $otherEnrollments,
        'expanded' => false,
        'button_class' => 'accordion-button collapsed enrollementlist',
    ]
];

// Define status configurations
$statusConfigs = [
    'pending' => ['class' => 'bg-warning', 'label' => 'Pending'],
    'queued' => ['class' => 'bg-primary', 'label' => 'Queued'],
    'failed' => ['class' => 'bg-danger', 'label' => 'Failed'],
    'default' => ['class' => 'bg-secondary', 'label' => null] // Label will be generated from status
];

// Generate accordion sections
foreach ($sections as $sectionKey => $section) {
    if (!empty($section['data'])) {
        echo '
<div class="accordion-item">
<h2 class="accordion-header">
<button class="' . $section['button_class'] . '" type="button" 
data-bs-toggle="collapse"     data-bs-target="#' . $section['id'] . '" 
aria-expanded="' . ($section['expanded'] ? 'true' : 'false') . '"    aria-controls="' . $section['id'] . '">
' . $section['title'] . ' (' . count($section['data']) . ')
</button>
</h2>
<div id="' . $section['id'] . '" 
class="accordion-collapse collapse ' . ($section['expanded'] ? 'show' : '') . '" 
data-bs-parent="#enrollmentsAccordion">
<div class="accordion-body p-0">
<div class="list-group list-group-flush">';

        foreach ($section['data'] as $enrollment) {
            $status = safe_array_get($enrollment, 'enrollment_status', 'default');
            $statusConfig = $statusConfigs[$status] ?? $statusConfigs['default'];
            $label = $statusConfig['label'] ?? ucfirst($status);
            $company_name = safe_array_get($enrollment, 'company_name', 'Unknown Company');
            $modify_dt = safe_array_get($enrollment, 'modify_dt', '');

            echo linkmaker($enrollment, $userId, $admin_user_data) . '
<div class="d-flex justify-content-between align-items-center">
<div>
<h6 class="mb-1">' . safe_echo($company_name) . '</h6>
<small class="text-muted">' . $modify_dt . '</small>
</div>
<span class="badge ' . $statusConfig['class'] . '">' . $label . '</span>
</div>
</a>';
        }

        echo '
</div>
</div>
</div>
</div>';
    }
}

#-------------------------------------------------------------------------------
echo '
</div>
</div>
<!-- Right Panel - Waiting State & Business Details and Enrollment -->
<div class="col-md-9">';

// CONTENT DISPLAY LOGIC - Either show business details or waiting state
if (empty($bid)) {
    // No business selected - show waiting state
    $emailTypes = [
        'starting' => [
            'id' => 'startingEnrollmentModal',
            'buttonText' => 'Send Starting Enrollment Email',
            'buttonClass' => 'btn-primary',
            'action' => 'sendmessage_starting',
            'description' => 'This email notifies the member that we are beginning to process their enrollment documents and will be in touch with updates.'
        ],
        'queuing' => [
            'id' => 'queuingEnrollmentModal',
            'buttonText' => 'Send Queuing Enrollment Email',
            'buttonClass' => 'btn-info',
            'action' => 'sendmessage_queuing',
            'description' => 'This email informs the member that their enrollment is in our processing queue and provides an estimated timeline for completion.'
        ],
        'completed' => [
            'id' => 'completedEnrollmentModal',
            'buttonText' => 'Send Enrollments Completed',
            'buttonClass' => 'btn-success',
            'action' => 'sendmessage_completed',
            'description' => 'This email confirms to the member that their enrollment has been successfully processed and provides next steps.'
        ]
    ];

    echo '
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Ready to Process Enrollments</h5>
        </div>
        <div class="card-body text-center py-5">
            <i class="bi bi-arrow-left-circle mb-3" style="font-size: 2rem;"></i>
            <h3 class="mb-3">Select a Business</h3>
            <p class="text-muted mb-0">Please select a business from the left panel to begin processing enrollments</p>
            
            <div class="mt-5">';

    // Generate buttons
    foreach ($emailTypes as $type => $config) {
        echo '
                <button type="button" class="btn ' . $config['buttonClass'] . ' mx-2" data-bs-toggle="modal" data-bs-target="#' . $config['id'] . '">
                    ' . $config['buttonText'] . '
                </button>';
    }

    echo '
            </div>
        </div>
    </div>';

    // Generate modals
    foreach ($emailTypes as $type => $config) {
        echo '
    <!-- ' . ucfirst($type) . ' Enrollment Modal -->
    <div class="modal fade mt-5" id="' . $config['id'] . '" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="' . $_SERVER["PHP_SELF"] . '">
                    ' . $display->input_csrftoken() . '
                    <input type="hidden" name="action" value="' . $config['action'] . '">
                    <input type="hidden" name="bid" value="' . $qik->encodeId($bid) . '">
                    <input type="hidden" name="uid" value="' . $qik->encodeId($userId) . '">
                    <input type="hidden" name="aid" value="' . $qik->encodeId($aid) . '">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Email Send</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Send email to <span id="memberName">' . safe_echo($first_name . ' ' . $last_name) . '</span> at: <span id="memberEmail">'. safe_echo(safe_array_get($working_user_data, 'email', '')) .'</span></p>
                        <hr>
                        <p class="text-muted small">' . $config['description'] . '</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">SEND</button>
                    </div>
                </form>
            </div>
        </div>
    </div>';
    }

    // Show the member profile details
    echo '<div class="card">
    <div class="card-header bg-primary text-white">
    <h5 class="card-title" id="profileModalLabel">Member Enrollment Profile Details</h5>
    </div>
    <div class="card-body" id="profileDetails">
    ';

    $local_list_profiledetailslayout = '2';
    include($_SERVER['DOCUMENT_ROOT'] . '/admin/bgreb_v3/enrollment-list_profiledetails.php');

    echo '
    </div>
    </div>';
} else {
    // Business selected - show business details
    // Ensure we have valid business data
    if (!is_array($business)) {
        echo '<div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Error:</strong> Could not load business details for ID: ' . $bid . '
        </div>';
    } else {
        // Business Details Card 
        echo '
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title d-inline align-middle me-4">Business Details</h5>

                <span class="fs-9 me-2 badge bg-secondary align-middle">Enrollment ID: ' . safe_echo($eid) . '</span>
                <span class="fs-9 badge bg-' . ($bidenrollmentstatus === 'success' ? 'success' : 'danger') . ' align-middle">' 
                . (is_null($bidenrollmentstatus) ? '' : ucfirst($bidenrollmentstatus)) 
                . (!empty($bidenrollmentreason) ? ': ' . safe_echo($bidenrollmentreason) : '') 
                . '</span>
            </div>
            <div class="card-body">
                <!-- Logo and Basic Info -->
                <div class="row align-items-center mb-3">
                    <div class="col-auto">
                        <img src="' . $display->companyimage(safe_array_get($business, 'company_id', '0') . '/' . safe_array_get($business, 'company_logo', '')) . '" 
                            alt="' . safe_echo(safe_array_get($business, 'company_display_name', 'Company')) . ' Logo" 
                            class="img-fluid" style="max-width: 150px; max-height: 100px;">
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h1 class="mb-0 fw-bold">' . safe_echo(safe_array_get($business, 'company_display_name', 'Company')) . '</h1>
                        </div>
                        <div>
                            <span class="badge bg-dark">Business ID: ' . safe_array_get($business, 'company_id', '0') . '</span>
                            <span class="badge bg-' . (safe_array_get($business, 'company_status') === 'active' ? 'success' : 'warning') . '">
                                ' . ucfirst(safe_array_get($business, 'company_status', 'unknown')) . '
                            </span>
                        </div>
                    </div>
                </div>';

        // Action Buttons
        echo '
        <div class="col-auto">
            <div class="enrollment-actions">
                <div class="row">
                    <div class="col-md-9">
                        <form id="enrollmentForm" method="post" action="' . $_SERVER['PHP_SELF'] . '" class="m-0 p-0">
                            ' . $display->input_csrftoken() . '
                            <input type="hidden" name="bid" value="' . $qik->encodeId(safe_array_get($business, 'company_id', '0')) . '">
                            <input type="hidden" name="uid" value="' . $qik->encodeId($userId) . '">
                            <input type="hidden" name="aid" value="' . $qik->encodeId($aid) . '">
                            <input type="hidden" name="action" value="enrollment_failure">     

                            <div class="d-grid gap-2">
                                <div class="btn-group w-100" role="group" aria-label="Failure reason options">
                                    <input type="radio" class="btn-check" name="failureReason" id="reason1" value="Account Already Exists" autocomplete="off" checked>
                                    <label class="btn btn-outline-danger btn-sm py-2" for="reason1">Account Exists</label>

                                    <input type="radio" class="btn-check" name="failureReason" id="reason2" value="Password Validation Failed" autocomplete="off">
                                    <label class="btn btn-outline-danger btn-sm py-2" for="reason2">Password Failed</label>

                                    <input type="radio" class="btn-check" name="failureReason" id="reason3" value="Missing Required Data" autocomplete="off">
                                    <label class="btn btn-outline-danger btn-sm py-2" for="reason3">Missing Data</label>

                                    <input type="radio" class="btn-check" name="failureReason" id="reason4" value="Form Failure" autocomplete="off">
                                    <label class="btn btn-outline-danger btn-sm py-2" for="reason4">Form Failure</label>

                                    <input type="radio" class="btn-check" name="failureReason" id="reasonOther" value="other" autocomplete="off">
                                    <label class="btn btn-outline-danger btn-sm py-2" for="reasonOther">Other...</label>
                                </div>

                                <div class="collapse" id="otherReasonInput">
                                    <input type="text" class="form-control" id="customFailureReason" placeholder="Enter reason...">
                                </div>

                                <button id="successBtn" class="btn btn-danger py-0 btn-lg w-100">
                                    <i class="bi bi-x-circle"></i> Enrollment Failed
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-3">
                        <form id="enrollmentForm" method="post" action="' . $_SERVER['PHP_SELF'] . '" class="m-0 p-0">
                            ' . $display->input_csrftoken() . '
                            <input type="hidden" name="bid" value="' . $qik->encodeId(safe_array_get($business, 'company_id', '0')) . '">
                            <input type="hidden" name="uid" value="' . $qik->encodeId($userId) . '">
                            <input type="hidden" name="aid" value="' . $qik->encodeId($aid) . '">
                            <input type="hidden" name="action" value="enrollment_success">                  

                            <button id="successBtn" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-check-circle"></i> Enrollment Success
                            </button>
                        </form>
                    </div>
                </div>
            </div>';

        // Business Information and Links Section
        echo '
        <div class="row g-3">
            <!-- Business Information -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body p-3">
                        <h6 class="border-bottom pb-2 mb-2">Business Information</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="text-muted small">Category</label>
                                <div class="small">' . safe_echo(safe_array_get($business, 'display_category', 'Uncategorized')) . '</div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted small">Region Type</label>
                                <div class="small">' . safe_array_process($business, 'region_type', 'ucfirst', 'Unknown') . '</div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted small">Email Domain</label>
                                <div class="small">' . safe_echo(safe_array_get($business, 'email_domain', '')) . '</div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted small">Status</label>
                                <div class="small">' . safe_echo(safe_array_get($business, 'company_status', '')) . '</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Links Section -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-body p-3">
                        <h6 class="border-bottom pb-2 mb-2">Links</h6>
                        <div class="row">
                            <!-- Web Links -->
                            <div class="col-9 border-end">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <div class="row mb-1">
                                            <div class="col-2 text-muted small">Website:</div>
                                            <div class="col-10">
                                                <a href="' . safe_echo(safe_array_get($business, 'company_url', '#')) . '" target="enrollerwindow" class="small text-break">
                                                    <i class="bi bi-globe"></i> ' . safe_echo(safe_array_get($business, 'company_url', 'N/A')) . '
                                                </a>
                                            </div>
                                        </div>
                                        <div class="row mb-1">
                                            <div class="col-2 text-muted small">Signup:</div>
                                            <div class="col-10">
                                                <a href="' . safe_echo(safe_array_get($business, 'signup_url', '#')) . '" target="enrollerwindow" class="small text-break">
                                                    <i class="bi bi-box-arrow-in-right"></i> ' . safe_echo(safe_array_get($business, 'signup_url', 'N/A')) . '
                                                </a>
                                            </div>
                                        </div>';

        // Only show info URL if it exists
        if (!empty($business['info_url'])) {
            echo '
                                        <div class="row mb-1">
                                            <div class="col-2 text-muted small">Info:</div>
                                            <div class="col-10">
                                                <a href="' . safe_echo($business['info_url']) . '" target="_blank" class="small text-break">
                                                    <i class="bi bi-info-circle"></i> ' . safe_echo($business['info_url']) . '
                                                </a>
                                            </div>
                                        </div>';
        }

        echo '
                                    </div>
                                </div>
                            </div>

                            <!-- Apps and Social -->
                            <div class="col-3">
                                <div class="row g-2">
                                    <!-- Mobile Apps -->
                                    <div class="col-5">
                                        <div class="text-muted small mb-2">App<br>Store:</div>
                                        <div class="">';

        // Conditionally show app links
        if (!empty($business['appgoogle'])) {
            echo '<a href="' . safe_echo($business['appgoogle']) . '" target="enrollerwindow" class="btn btn-sm btn-outline-success" title="' . safe_echo($business['appgoogle']) . '"><i class="bi bi-google-play"></i></a>';
        }
        if (!empty($business['appapple'])) {
            echo '<a href="' . safe_echo($business['appapple']) . '" target="enrollerwindow" class="btn btn-sm btn-outline-dark" title="' . safe_echo($business['appapple']) . '"><i class="bi bi-apple"></i></a>';
        }

        echo '
                                        </div>
                                    </div>

                                    <!-- Social Media -->
                                    <div class="col-7">
                                        <div class="text-muted small mb-2">Social<br>Media:</div>
                                        <div class="">';

        // Conditionally show social media links
        if (!empty($business['facebook'])) {
            echo '<a href="' . safe_echo($business['facebook']) . '" target="enrollerwindow" class="btn btn-sm btn-outline-primary" title="' . safe_echo($business['facebook']) . '"><i class="bi bi-facebook"></i></a>';
        }
        if (!empty($business['twitter'])) {
            echo '<a href="' . safe_echo($business['twitter']) . '" target="enrollerwindow" class="btn btn-sm btn-outline-info" title="' . safe_echo($business['twitter']) . '"><i class="bi bi-twitter-x"></i></a>';
        }
        if (!empty($business['instagram'])) {
            echo '<a href="' . safe_echo($business['instagram']) . '" target="enrollerwindow" class="btn btn-sm btn-outline-danger" title="' . safe_echo($business['instagram']) . '"><i class="bi bi-instagram"></i></a>';
        }
        if (!empty($business['tiktok'])) {
            echo '<a href="' . safe_echo($business['tiktok']) . '" target="enrollerwindow" class="btn btn-sm btn-outline-dark" title="' . safe_echo($business['tiktok']) . '"><i class="bi bi-tiktok"></i></a>';
        }

        echo '
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        // Show reason if it exists
        if (!empty($business['reason'])) {
            echo '
            <div class="alert alert-danger mt-3">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Note:</strong> ' . safe_echo($business['reason']) . '
            </div>';
        }

        echo '</div>
        </div>
        </div>';

        // Profile Details Section
        $profilecompletion = $account->profilecompletionratio($working_user_data);
        echo '
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">  
                <h5 class="card-title d-inline align-middle me-4" id="profileModalLabel">Member Enrollment Profile Details</h5>';
                
        $badgeClass = $profilecompletion['required_percentage'] >= 80 ? 'bg-success' : 
                    ($profilecompletion['required_percentage'] >= 50 ? 'bg-warning' : 'bg-danger');

        echo '
                <span class="badge '. $badgeClass .' align-middle">
                    '. $profilecompletion['required_percentage'] .'% Complete
                </span>
            </div>
            <div class="card-body" id="profileDetails">';

        $local_list_profiledetailslayout = '2';
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/bgreb_v3/enrollment-list_profiledetails.php');

        echo '
            </div>
        </div>';

        // Hints Management Section
        $query = "SELECT * FROM `bg_company_attributes` 
            WHERE `company_id` = :company_id AND `type` = 'enroller-hint' 
            ORDER BY `rank`";

        $stmt = $database->prepare($query);
        $stmt->execute([':company_id' => $bid]);
        $hints = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get the count of hints
        $hintCount = count($hints);

        echo '
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Enroller Help</h5>
                <div>
                    <span class="badge bg-white text-primary me-2">' . $hintCount . ' Hints</span>
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addHintModal">
                        <i class="bi bi-plus-circle"></i> Add Hint
                    </button>
                </div>
            </div>
            <div class="card-body">';

        foreach ($hints as $hint) {
            $priority = '';
            $colorClass = '';
            $rankNum = intval($hint['rank']);

            if ($rankNum < 20) {
                $priority = 'Critical';
                $colorClass = 'danger';
            } elseif ($rankNum >= 20 && $rankNum < 50) {
                $priority = 'Important';
                $colorClass = 'warning';
            } else {
                $priority = 'Notice';
                $colorClass = 'success';
            }

            echo '
            <div class="border-start border-4 border-' . $colorClass . ' ps-2 mb-2 py-1 bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-' . $colorClass . ' mb-0">' . safe_echo(safe_array_get($hint, 'name', '')) . '</div>
                        <div class="small text-muted">' . safe_echo(safe_array_get($hint, 'description', '')) . '</div>
                    </div>
                    <div class="ms-2 d-flex align-items-center gap-2">
                        <span class="badge bg-' . $colorClass . ' rounded-pill small">' . $priority . '</span>
                        ' . (safe_array_get($hint, 'status') ? '<span class="badge bg-secondary rounded-pill small">' . safe_echo($hint['status']) . '</span>' : '') . '
                    </div>
                </div>
            </div>';
        }
        echo '
            </div>
        </div>';

        // Add Hint Modal
        echo '
        <!-- Add Hint Modal -->
        <div class="modal fade mt-5" id="addHintModal" tabindex="-1" aria-labelledby="addHintModalLabel" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
        <form id="addHintForm" method="post" action="' . $_SERVER['PHP_SELF'] . '">
        <div class="modal-header">
        <h5 class="modal-title" id="addHintModalLabel">Add Enrollment Hint</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        ' . $display->input_csrftoken() . '
        <input type="hidden" name="action" value="add_hint">
        <input type="hidden" name="bid" value="' . $qik->encodeId(safe_array_get($business, 'company_id', '0')) . '">
        <input type="hidden" name="uid" value="' . $qik->encodeId($userId) . '">
        <input type="hidden" name="aid" value="' . $qik->encodeId($aid) . '">

        <div class="mb-3">
        <label class="form-label">Name (Optional)</label>
        <input type="text" class="form-control" name="hint_name" placeholder="e.g., Important Note">
        </div>
        <div class="mb-3">
        <label class="form-label">Description <span class="text-danger">*</span></label>
        <textarea class="form-control" name="hint_description" rows="4" required 
        placeholder="Enter the enrollment hint here..."></textarea>
        </div>
        <div class="mb-3">
        <label class="form-label">Group (Optional)</label>
        <input type="text" class="form-control" name="hint_grouping" 
        placeholder="e.g., Required Fields">
        </div>
        <div class="mb-3">
        <label class="form-label d-flex justify-content-between">
        <span>Hint Priority</span>
        </label>
        <input type="range" class="form-range" name="hint_rank" id="hintPriority" 
        min="1" max="100" value="50">
        <div class="d-flex justify-content-between small text-muted">
        <span>Higher Priority</span>
        <span>Lower Priority</span>
        </div>
        </div>

        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Add Hint</button>
        </div>
        </form>
        </div>
        </div>
        </div>
        ';

        // Include memberenroller scripts
        include($_SERVER['DOCUMENT_ROOT'] . '/admin/bgreb_v3/components/memberenroller-scripts.inc');
    }
}

echo '
</div>
</section>
';

include($_SERVER['DOCUMENT_ROOT'] . '/admin/bgreb_v3/components/memberenroller-styles.inc');
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();