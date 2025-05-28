<?php
$dir['base'] = $BASEDIR = __DIR__ . "/../.." ?? $_SERVER['DOCUMENT_ROOT'];
require_once($BASEDIR . '/core/site-controller.php');


if (!empty( $userId)) {

$local_profilemode='v2';
} else {
// Get user ID from request
$userId = intval($_GET['userId']  );
$local_profilemode='modal';
}
// Get user details
$userDetails = $account->getuserdata($userId, 'user_id');

if (!$userDetails) {
    echo '<div class="alert alert-danger">User not found</div>';
    exit;
}

// Function to create a clickable data field
function createDataField($label, $value, $isHtml = false, $template = null) {
    if (empty($value)) return '';

    $displayValue = $isHtml ? $value : htmlspecialchars($value);

    // Default template if none is provided
    if (is_null($template)) {
        $template = '<div class="row mb-1">
                        <div class="col-4 text-end fw-bold text-muted small">{{label}}:</div>
                        <div class="col-8">
                            <span class="data-field small fw-bold text-success" 
                                  onclick="copyToClipboard(\'{{value}}\')" 
                                  style="cursor: pointer" title="Click to copy">
                                {{value}}
                                <i class="bi bi-copy ms-1 opacity-50 d-none"></i>
                            </span>
                        </div>
                    </div>';
    }

    // Replace tokens in the template
    $output = str_replace(
        ['{{label}}', '{{value}}'], 
        [htmlspecialchars($label), $displayValue], 
        $template
    );

    return $output;
}




// Define card content variables
$accountInfoCard = '<div class="card mb-2">
    <div class="card-header py-1 bg-light">
        <h6 class="mb-0 small fw-bold">Account Information</h6>
    </div>
    <div class="card-body py-2">' .
    createDataField('User ID', $userDetails['user_id']) .
    createDataField('Username', $userDetails['profile_username']) .
    createDataField('Email', $userDetails['profile_email']) .
    createDataField('Password', $userDetails['profile_password']) .
    createDataField('Account Type', $userDetails['account_type']) .
    createDataField('Status', $userDetails['status']) .
    '</div></div>';

    $accountInfoCard2 = '<div class="card mb-2">
    <div class="card-header py-1 bg-light">
        <h6 class="mb-0 small fw-bold">Account Information</h6>
    </div>
    <div class="card-body py-2">' .
    createDataField('User ID', $userDetails['user_id']) .
    createDataField('Account Type', $userDetails['account_type']) .
    createDataField('Status', $userDetails['status']) .
    '</div></div>';

$personalInfoCard = '<div class="card mb-2">
    <div class="card-header py-1 bg-light">
        <h6 class="mb-0 small fw-bold">Personal Information</h6>
    </div>
    <div class="card-body py-2">' .
    createDataField('Title', $userDetails['profile_title']) .
    createDataField('First Name', $userDetails['profile_first_name']) .
    createDataField('Middle Name', $userDetails['profile_middle_name']) .
    createDataField('Last Name', $userDetails['profile_last_name']) .
    createDataField('Gender', $userDetails['profile_gender']) .
    createDataField('Birthdate', $userDetails['birthdate']) .
    '</div></div>';

$contactInfoCard = '<div class="card mb-2">
    <div class="card-header py-1 bg-light">
        <h6 class="mb-0 small fw-bold">Contact Information</h6>
    </div>
    <div class="card-body py-2">' .
    createDataField('Phone', $userDetails['profile_phone_number']) .
    createDataField('Phone Type', $userDetails['profile_phone_type']) .
    createDataField('Address', $userDetails['profile_mailing_address']) .
    createDataField('City', $userDetails['profile_city']) .
    createDataField('State', $userDetails['profile_state']) .
    createDataField('Zip', $userDetails['profile_zip_code']) .
    createDataField('Country', $userDetails['profile_country']) .
    '</div></div>';

$agreementsCard = '<div class="card mb-2">
    <div class="card-header py-1 bg-light">
        <h6 class="mb-0 small fw-bold">Agreements & Preferences</h6>
    </div>
    <div class="card-body py-2">' .
    createDataField('Terms Agreement', $userDetails['profile_agree_terms']) .
    createDataField('Email Agreement', $userDetails['profile_agree_email']) .
    createDataField('Text Agreement', $userDetails['profile_agree_text']) .
    '</div></div>';

    $template = '<div class="row mb-1">
                <div class="col-9 text-end fw-bold text-muted small">{{label}}:</div>
                <div class="col-3">{{value}}</div>
            </div>';


    $agreementsCardcolumns = '<div class="card mb-2">
    <div class="card-header py-1 bg-light">
        <h6 class="mb-0 small fw-bold">Agreements & Preferences</h6>
    </div>
    <div class="card-body py-2">
        <div class="row">
            <div class="col-4">
                ' . createDataField('Terms Agreement', '<i class="bi ' . ($userDetails['profile_agree_terms'] ? 'bi-check-circle text-success' : 'bi-x-circle text-danger') . '"></i>', true, $template) . '
            </div>
            <div class="col-4">
                ' . createDataField('Email Agreement', '<i class="bi ' . ($userDetails['profile_agree_email'] ? 'bi-check-circle text-success' : 'bi-x-circle text-danger') . '"></i>', true, $template) . '
            </div>
            <div class="col-4">
                ' . createDataField('Text Agreement', '<i class="bi ' . ($userDetails['profile_agree_text'] ? 'bi-check-circle text-success' : 'bi-x-circle text-danger') . '"></i>', true, $template) . '
            </div>
        </div>
    </div>
</div>';
    

$allergiesCard = '<div class="card mb-2">
    <div class="card-header py-1 bg-light">
        <h6 class="mb-0 small fw-bold">Allergies</h6>
    </div>
    <div class="card-body py-2">' .
    createDataField('Gluten', $userDetails['profile_allergy_gluten']) .
    createDataField('Sugar', $userDetails['profile_allergy_sugar']) .
    createDataField('Nuts', $userDetails['profile_allergy_nuts']) .
    createDataField('Dairy', $userDetails['profile_allergy_dairy']) .
    '</div></div>';

$dietaryPrefsCard = '<div class="card mb-2">
    <div class="card-header py-1 bg-light">
        <h6 class="mb-0 small fw-bold">Dietary Preferences</h6>
    </div>
    <div class="card-body py-2">' .
    createDataField('Vegan', $userDetails['profile_diet_vegan']) .
    createDataField('Kosher', $userDetails['profile_diet_kosher']) .
    createDataField('Pescatarian', $userDetails['profile_diet_pescatarian']) .
    createDataField('Keto', $userDetails['profile_diet_keto']) .
    createDataField('Paleo', $userDetails['profile_diet_paleo']) .
    createDataField('Vegetarian', $userDetails['profile_diet_vegetarian']) .
    '</div></div>';

$specialStatusCard = '<div class="card">
    <div class="card-header py-1 bg-light">
        <h6 class="mb-0 small fw-bold">Special Status</h6>
    </div>
    <div class="card-body py-2">' .
    createDataField('Military', $userDetails['profile_military']) .
    createDataField('Educator', $userDetails['profile_educator']) .
    createDataField('First Responder', $userDetails['profile_firstresponder']) .
    '</div></div>';

// Start container
echo '<div class="container-fluid p-2">';
echo '<div class="row">';

// Layout switch
switch($local_list_profiledetailslayout) {
    case '2':
        // Left Column - Enrollment Info
        echo '<div class="col-6">';
        echo  '<div class="card mb-2">
        <div class="card-header py-1 bg-light">
            <h6 class="mb-0 small fw-bold">Enrollment Profile Information</h6>
        </div>
        <div class="card-body py-2">' .
        createDataField('Title', $userDetails['profile_title']) .
        createDataField('First Name', $userDetails['profile_first_name']) .
        createDataField('Middle Name', $userDetails['profile_middle_name']) .
        createDataField('Last Name', $userDetails['profile_last_name']) .
        createDataField('Gender', $userDetails['profile_gender']) .
        createDataField('Birthdate', $userDetails['birthdate']) .
        '<hr>'.
        createDataField('Username', $userDetails['profile_username']) .
        createDataField('Email', $userDetails['profile_email']) .
        createDataField('Password', $userDetails['profile_password']) .
        '<hr>'.
        createDataField('Phone', $userDetails['profile_phone_number']) .
        createDataField('Phone Type', $userDetails['profile_phone_type']) .
        createDataField('Address', $userDetails['profile_mailing_address']) .
        createDataField('City', $userDetails['profile_city']) .
        createDataField('State', $userDetails['profile_state']) .
        createDataField('Zip', $userDetails['profile_zip_code']) .
        createDataField('Country', $userDetails['profile_country']) .

        '</div></div>';
    
        echo '</div>';
        
        // Right Column - Account, Agreements, Allergies, Diet, Special Status
        echo '<div class="col-6">';
        echo $accountInfoCard2;
        echo $agreementsCardcolumns;
        echo $allergiesCard;
        echo $dietaryPrefsCard;
        echo $specialStatusCard;
        echo '</div>';
        break;
        
    default:
        // Left Column - Account, Personal, Contact Info
        echo '<div class="col-6">';
        echo $accountInfoCard;
        echo $personalInfoCard;
        echo $contactInfoCard;
        echo '</div>';
        
        // Right Column - Agreements, Allergies, Diet, Special Status
        echo '<div class="col-6">';
        echo $agreementsCard;
        echo $allergiesCard;
        echo $dietaryPrefsCard;
        echo $specialStatusCard;
        echo '</div>';


        if ($local_profilemode=='modal') {
            echo '
            <!-- Status Update Form -->
            <div class="card mb-2 mt-5">
               <div class="card-header py-1 bg-danger">
                   <h6 class="mb-0 small fw-bold text-white">Force Update Status for user: '.$userId.'</h6>
               </div>
               <div class="card-body p-4">
                   <form action="https://dev.birthday.gold/admin/bgreb_v3/bgr_actions.php">
                    '.$display->input_csrftoken().'
                       <!-- Hidden fields -->
                       <input type="hidden" name="aid" value="20">
                       <input type="hidden" name="uid" value="'.$userId.'">
                       <input type="hidden" name="message" value="done">
                       <input type="hidden" name="version" value="3.9999.99.999990">
                       
                       <div class="mb-2">
                           <label for="company_select" class="form-label small">Select Company</label>
                           <select class="form-select form-select-sm" id="company_select" name="company_select"  required>
                               <option value="">Choose Company...</option>';
                              
                               // Fetch and display enrollments
                               $sql = "SELECT ue.user_company_id, ue.company_id, c.company_display_name as company_name 
                                       FROM bg_user_enrollments ue 
                                       JOIN bg_companies c ON ue.company_id = c.company_id 
                                       WHERE ue.user_id = ? and ue.status='selected'
                                       ORDER BY c.company_display_name ASC";
                               $stmt = $database->prepare($sql);
                               $stmt->execute([$userId]);
                               
                               while($enrollmentlist = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                   echo '<option value="'.$enrollmentlist['user_company_id'].'|'.$enrollmentlist['company_id'].'">
                                           '.htmlspecialchars($enrollmentlist['company_name']).' 
                                           (CID: '.$enrollmentlist['company_id'].')
                                       </option>';
                               }
                                       
            echo '          </select>
                       </div>
                       
                       <div class="mb-2">
                           <label for="act" class="form-label small">Status</label>
                           <select class="form-select form-select-sm" id="act" name="act" required>
                               <option value="">Select Status</option>
                               <option value="success-btn">Success</option>
                               <option disabled>─────────────</option>
                               <option value="failed-exists">Failed: Account Already Exists</option>
                               <option value="failed-form">Failed: Form Failure</option>
                               <option value="failed-password">Failed: Password Failure</option>
                               <option value="failed-missing">Failed: Missing Data Element</option>
                           </select>
                       </div>
                       
                       <button type="submit" class="btn btn-primary mt-5 btn-sm">Update Status</button>
                   </form>
               </div>
            </div>
            
            ';
            
                            }
        break;
}



echo '</div>'; // End Row
echo '</div>'; // End Container