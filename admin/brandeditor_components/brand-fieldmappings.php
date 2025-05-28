<?php
if (!isset($componentmode) || $componentmode != 'include') {
// Include the site-controller.php file
include_once $_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php';
}
// always use single PHP BLOCK, ECHO block statements. 
// Do not use Short Echo Tags, Short Tags, Multiple PHP Tags or Nowdoc/Heredoc syntax
// access to /myaccount and /admin pages are controlled by the site-controller.php file.


#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// Get company id if not already set
if (empty($cid)) {
    $companyID = $_REQUEST['cid'] ?? 0;
} else {
    $companyID = $cid;
}

if(isset($_REQUEST['cname'])) {
    $companyname = $_REQUEST['cname']; 
} else {
    $companyname = '';
}

// Get current version
$sql = "SELECT max(version) as version FROM bg_form_field_mappings WHERE company_id = :company_id and version_status='active' group by company_id limit 1";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $companyID]);
$version = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($version[0]['version'])) {
    $versionnumber = $version[0]['version'];
    $criteria = " and version=" . $versionnumber;
} else {
    $versionnumber = 1;
    $criteria = '';
}

if(isset($_REQUEST['version'])) {
    $versionnumber = $_REQUEST['version'];
    $criteria = " and version=" . $versionnumber;
}

// Known user fields for autocomplete
$knownUserFields = [
    'profile_username',
    'profile_email',
    'profile_password',
    'profile_first_name',
    'profile_middle_name',
    'profile_last_name',
    'profile_phone',
    'birthdate',
    'profile_address',
    'profile_city',
    'profile_state', 
    'profile_zip_code',
    'profile_country',
    'profile_phone_number',
    'profile_phone_type',
    'profile_gender',
    'profile_agree_terms',
    'profile_agree_email',
    'profile_agree_text'
];

// Format definitions with their corresponding formats
$formatDefinitions = [
    'date' => [
        'description' => 'Date formatting',
        'formats' => [
            ['value' => 'j', 'label' => 'Day of month without leading zeros (1-31)'],
            ['value' => 'd', 'label' => 'Day of month with leading zeros (01-31)'],
            ['value' => 'D', 'label' => 'Day of week abbreviated name (Mon-Sun)'],
            ['value' => 'm', 'label' => 'Month with leading zeros (01-12)'],
            ['value' => 'n', 'label' => 'Month without leading zeros (1-12)'],
            ['value' => 'M', 'label' => 'Month abbreviated name (Jan-Dec)'],
            ['value' => 'F', 'label' => 'Full month name (January through December)'],
            ['value' => 'Y', 'label' => 'Full year (2024)'],
            ['value' => 'y', 'label' => 'Two digit year (24)']
        ]
    ],
    'phone' => [
        'description' => 'Phone number formatting',
        'formats' => [
            ['value' => '(###) ###-####', 'label' => 'US format with area code'],
            ['value' => '###-###-####', 'label' => 'US format without parentheses'],
            ['value' => '+#-###-###-####', 'label' => 'International format']
        ]
    ],
    'email' => [
        'description' => 'Email formatting',
        'formats' => [
            ['value' => 'lowercase', 'label' => 'Convert to lowercase'],
            ['value' => 'trim', 'label' => 'Remove whitespace'],
            ['value' => 'validate', 'label' => 'Validate email format']
        ]
    ],
    'punchh' => [
        'description' => 'Punchh format',
        'formats' => [
            ['value' => '', 'label' => 'Default Punchh format']
        ]
    ]
];

// Fetch existing mappings
$sql = "SELECT * FROM bg_form_field_mappings WHERE company_id = ".$companyID.$criteria;
$stmt = $database->prepare($sql);
$stmt->execute();
$mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch company details
$sql = "SELECT company_name FROM bg_companies WHERE company_id = :company_id";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $companyID]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
if (!empty($company['company_name'])) {
    $companyname = $company['company_name'];
}

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// Handle new version creation
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['newversion'])) {
    $versionnumber = $_REQUEST['newversion'];
    $cid = $companyID;
    
    // First update old versions to inactive
    $sql = "UPDATE `bg_form_field_mappings` SET `version_status`='inactive', `modify_dt`=now() 
            WHERE company_id=:cid AND `version`!=:version AND `version_status`='active'";
    $stmt = $database->prepare($sql);
    $stmt->execute(['cid' => $cid, 'version' => $versionnumber]);
    
    // Insert default fields for new version
    $defaultFields = [
        'profile_username', 'profile_email', 'profile_password', 'profile_title',
        'profile_first_name', 'profile_middle_name', 'profile_last_name',
        'profile_mailing_address', 'profile_city', 'profile_state',
        'profile_zip_code', 'profile_country', 'profile_phone_number',
        'birthdate', 'profile_phone_type', 'profile_gender',
        'profile_agree_terms', 'profile_agree_email', 'profile_agree_text'
    ];
    
    foreach ($defaultFields as $field) {
        $sql = "INSERT INTO `bg_form_field_mappings` 
                (`company_id`, `version`, `version_status`, `user_field_name`, `website_field_name`, 
                `fieldformattype`, `fieldformat`, `status`, `create_dt`, `modify_dt`, `version_dt`) 
                VALUES (:cid, :version, 'active', :field, :field, NULL, NULL, 'active', NOW(), NOW(), NOW())";
        $stmt = $database->prepare($sql);
        $stmt->execute(['cid' => $cid, 'version' => $versionnumber, 'field' => $field]);
    }
    
    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF'] . "?cid=" . $companyID . "&version=" . $versionnumber);
    exit;
}

// Handle buildout action
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['buildout'])) {
    $companyID = $cid = $_REQUEST['buildout'];
    
    // Initialize database with default fields
    $defaultFields = [
        'profile_username', 'profile_email', 'profile_password', 'profile_title',
        'profile_first_name', 'profile_middle_name', 'profile_last_name',
        'profile_mailing_address', 'profile_city', 'profile_state',
        'profile_zip_code', 'profile_country', 'profile_phone_number',
        'birthdate', 'profile_phone_type', 'profile_gender',
        'profile_agree_terms', 'profile_agree_email', 'profile_agree_text'
    ];
    
    foreach ($defaultFields as $field) {
        $sql = "INSERT INTO `bg_form_field_mappings` 
                (`company_id`, `version`, `version_status`, `user_field_name`, `website_field_name`, 
                `fieldformattype`, `fieldformat`, `status`, `create_dt`, `modify_dt`) 
                VALUES (:cid, 1, 'active', :field, :field, NULL, NULL, 'active', NOW(), NOW())";
        $stmt = $database->prepare($sql);
        $stmt->execute(['cid' => $cid, 'field' => $field]);
    }
    
    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF'] . "?cid=" . $companyID);
    exit;
}

// Handle test case creation
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['addtestcase'])) {
    $companyID = $_REQUEST["cid"];
    $userID = 20; // User ID is static in this case

    // Check if the data already exists
    $checkSql = "SELECT * FROM bg_user_companies WHERE user_id = :user_id AND company_id = :company_id";
    $stmt = $database->prepare($checkSql);
    $stmt->execute(['user_id' => $userID, 'company_id' => $companyID]);
    $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If the data doesn't exist, insert it into the database
    if (count($existingData) == 0) {
        // Insert data into database
        $sql = "INSERT INTO bg_user_companies (user_id, company_id, create_dt, status) VALUES (:user_id, :company_id, NOW(), 'testing')";
        $stmt = $database->prepare($sql);
        $stmt->execute(['user_id' => $userID, 'company_id' => $companyID]);
    }
    
    // Redirect back to the page
    header("Location: " . $_SERVER['PHP_SELF'] . "?cid=" . $companyID);
    exit;
}

// handle any form posted process here
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['addtestcase']) && $_REQUEST['addtestcase']=='0') {
    // Fetch the post data
    $mappings = $_POST['mappings'];
 
    // Loop over each mapping
    foreach($mappings as $mappingID => $mappingData) {
        $userFieldName = trim($mappingData['userFieldName']);
        $websiteFieldName = trim($mappingData['websiteFieldName']);
        $expanded = false;
        $fieldformattype = '';
        $fieldformat = '';
        
        if(strpos($websiteFieldName, '||') !== false) {
            list($websiteFieldName, $fieldformattype, $fieldformat) = explode('||', $websiteFieldName);
            $expanded = true;
        } 

        // Check if the website field name is blank and set status accordingly
        $status = ($websiteFieldName == '') ? 'notused' : 'active';

        if ($expanded) {
            // If mappingID is not a number, insert new mapping
            if(!is_numeric($mappingID)) {
                $sql = "INSERT INTO bg_form_field_mappings 
                        (company_id, user_field_name, website_field_name, status, fieldformattype, fieldformat, `version`, version_dt, version_status) 
                        VALUES (:company_id, :user_field_name, :website_field_name, :status, :fieldformattype, :fieldformat, :version, NOW(), 'active')";
                $stmt = $database->prepare($sql);
                $stmt->execute([
                    'company_id' => $companyID, 
                    'user_field_name' => $userFieldName, 
                    'website_field_name' => $websiteFieldName, 
                    'status' => $status,
                    'fieldformattype' => $fieldformattype, 
                    'fieldformat' => $fieldformat,
                    'version' => $versionnumber
                ]);
            } else {
                // Else, update the existing mapping
                $sql = "UPDATE bg_form_field_mappings 
                        SET fieldformattype = :fieldformattype, fieldformat = :fieldformat, 
                        user_field_name = :user_field_name, website_field_name = :website_field_name, 
                        status = :status, modify_dt = NOW() 
                        WHERE mapping_id = :mapping_id";
                $stmt = $database->prepare($sql);
                $stmt->execute([
                    'fieldformattype' => $fieldformattype, 
                    'fieldformat' => $fieldformat, 
                    'user_field_name' => $userFieldName, 
                    'website_field_name' => $websiteFieldName, 
                    'status' => $status, 
                    'mapping_id' => $mappingID
                ]);
            }
        } else {
            if ($userFieldName != '') {
                // If mappingID is not a number, insert new mapping
                if(!is_numeric($mappingID)) {
                    $sql = "INSERT INTO bg_form_field_mappings 
                            (company_id, user_field_name, website_field_name, status, `version`, version_dt, version_status) 
                            VALUES (:company_id, :user_field_name, :website_field_name, :status, :version, NOW(), 'active')";
                    $stmt = $database->prepare($sql);
                    $stmt->execute([
                        'company_id' => $companyID, 
                        'user_field_name' => $userFieldName, 
                        'website_field_name' => $websiteFieldName, 
                        'status' => $status,
                        'version' => $versionnumber
                    ]);
                } else {
                    // Else, update the existing mapping
                    $sql = "UPDATE bg_form_field_mappings 
                            SET user_field_name = :user_field_name, website_field_name = :website_field_name, 
                            status = :status, modify_dt = NOW() 
                            WHERE mapping_id = :mapping_id";
                    $stmt = $database->prepare($sql);
                    $stmt->execute([
                        'user_field_name' => $userFieldName, 
                        'website_field_name' => $websiteFieldName, 
                        'status' => $status, 
                        'mapping_id' => $mappingID
                    ]);
                }
            }
        }
    }

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF'] . "?cid=" . $companyID . "&version=" . $versionnumber);
    exit;
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
#include($dir['core_components'] . '/bg_pagestart.inc');
#include($dir['core_components'] . '/bg_header.inc');

$additionalstylesx= '
<style >
.field-mapping-table { border-collapse: collapse; width: 100%; }
.field-mapping-table th { text-align: left; font-size: 0.75rem; text-transform: uppercase; color: #6c757d; padding: 0.75rem 1rem; border-bottom: 1px solid #dee2e6; border-top: 1px solid #dee2e6; }
.field-mapping-table td { padding: 0.5rem 1rem; border-bottom: 1px solid #f0f0f0; }
.field-mapping-table tr:hover { background-color: #f8f9fa; }
.field-input { width: 100%; padding: 0.375rem 0.5rem; font-size: 0.875rem; border: 1px solid #ced4da; border-radius: 0.25rem; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
.field-input:focus { background-color: #e6f0ff; outline: none; border-color: #86b7fe; }
.dropdown-suggestions { position: absolute; background-color: white; border: 1px solid #ced4da; border-radius: 0.25rem; max-height: 200px; overflow-y: auto; width: 100%; z-index: 1000; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
.dropdown-item { padding: 0.5rem 1rem; cursor: pointer; }
.dropdown-item:hover,
.dropdown-item.active { background-color: #e6f0ff; }
.field-format-info { font-size: 0.75rem; color: #6c757d; }
.version-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 0.25rem; background-color: #f0f0f0; color: #6c757d; }
.version-badge.active { background-color: #cfe2ff; color: #0d6efd; }
.no-gutters { margin-top: 0; margin-bottom: 2px; }
.no-gutters > .col,
.no-gutters > [class*="col-"] { padding-top: 0; padding-bottom: 0; }
.small-row .form-control,
.small-row .col-form-label { padding: .1rem .2rem; font-size: .75rem; line-height: .9; }
.light-grey-bg { background-color: #f2f2f2; }
::-webkit-input-placeholder { color: #bbbbbb !important; opacity: 1 !important; }
::-moz-placeholder { color: #bbbbbb !important; opacity: 1 !important; }
:-ms-input-placeholder { color: #bbbbbb !important; opacity: 1 !important; }
:-moz-placeholder { color: #bbbbbb !important; opacity: 1 !important; }
input,
textarea,
select { border-color: #bbbbbb !important; }
input:focus,
textarea:focus,
select:focus { border-color: #bbbbbb !important; }
.small-font { font-size: 9px; }
.copy-contentdate:hover { background-color: yellow; cursor: pointer; }
.copy-content:hover { background-color: yellow; cursor: pointer; }
.template-btn { margin-right: 5px; margin-bottom: 5px; }
</style >
';

echo '    
<div class="container main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4>' . htmlspecialchars($companyname) . ' <small>(' . $companyID . ' : ' . $versionnumber . ')</small></h4>
        </div>
        <div class="d-flex gap-2">
            <a href="' . $_SERVER['PHP_SELF'] . '?buildout=' . $companyID . '" class="btn btn-secondary btn-sm">
                BuildOut
            </a>
            <a href="' . $_SERVER['PHP_SELF'] . '?newversion=' . ($versionnumber+1) . '&cid=' . $companyID . '&cname=' . urlencode($companyname) . '" class="btn btn-secondary btn-sm">
                New Version
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <div class="btn-group"><span class="me-2 fw-bold">Hints:</span> 
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" href="#dateformatContent" aria-expanded="false" aria-controls="dateformatContent">
                    Dates
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" href="#fieldlistContent" aria-expanded="false" aria-controls="fieldlistContent">
                    Fields
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" href="#punchh" aria-expanded="false" aria-controls="punchh">
                    PunchH
                </button>
            </div>
        </div>
    </div>
    
    <div class="row small-font mb-4">
        <div class="col">
            <div class="collapse" id="dateformatContent">
                <div class="card card-body">
                    <h6>Date Format Codes</h6>
                    <ul class="mb-0">
                        <li><span class="copy-contentdate">d - The day of the month (from 01 to 31)</span></li>
                        <li><span class="copy-contentdate">D - A textual format of a day (three letters)</span></li>
                        <li><span class="copy-contentdate">j - The day of the month without leading zeros (1 to 31)</span></li>
                        <li><span class="copy-contentdate">F - A full textual format of a month (January through December)</span></li>
                        <li><span class="copy-contentdate">m - A numeric format of a month (from 01 to 12)</span></li>
                        <li><span class="copy-contentdate">M - A short textual format of a month (three letters)</span></li>
                        <li><span class="copy-contentdate">n - A numeric format of a month, without leading zeros (1 to 12)</span></li>
                        <li><span class="copy-contentdate">Y - A four digit format of a year</span></li>
                        <li><span class="copy-contentdate">y - A two digit format of a year</span></li>
                    </ul>
                </div>
            </div>
            
            <div class="collapse" id="fieldlistContent">
                <div class="card card-body">
                    <h6>Special Fields</h6>
                    <ul class="mb-0">
                        <li>formname - the name/id of a form</li>
                    </ul>
                </div>
            </div>

            <div class="collapse" id="punchh">
                <div class="card card-body">
                    <h6>Punchh Fields</h6>
                    <ul class="mb-0">
                        <li>formname - <span class="copy-content">user-form||punchh||</span></li>
                        <li>email - <span class="copy-content">user_email</span></li>
                        <li>password - <span class="copy-content">user_password</span></li>
                        <li>first_name - <span class="copy-content">user_first_name</span></li>
                        <li>phone_number - <span class="copy-content">user_phone</span></li>
                        <li>last_name - <span class="copy-content">user_last_name</span></li>
                        <li>password - <span class="copy-content">user_password_confirmation</span></li>
                        <li>birthday - <span class="copy-content">user_birthday_3i||date||j</span></li>
                        <li>birthday - <span class="copy-content">user_birthday_2i||date||n</span></li>
                        <li>FIXEDVALUE:326236  - <span class="copy-content">user_fav_location_id</span></li>
                        <li>birthday - <span class="copy-content">user_birthday_1i||date||Y</span></li>
                        <li>termsagree - <span class="copy-content">user_terms_and_conditions</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="mappingForm" method="POST">
                <input type="hidden" name="addtestcase" value="0">
                <input type="hidden" name="version" value="' . $versionnumber . '">
                <input type="hidden" name="cid" value="' . $companyID . '">
                
                <div id="mappings">
                ';
                
                foreach($mappings as $mapping) {
                    $showvalue = $mapping['website_field_name'];
                    if ($mapping['status'] == 'notused') $showvalue = '';
                    
                    if ($mapping['fieldformattype'] != '') {
                        $showvalue .= '||' . $mapping['fieldformattype'] . '||' . $mapping['fieldformat']; 
                    }
                    
                    echo '<div class="form-group row no-gutters small-row mb-1">
                        <div class="col-5">
                            <input type="text" class="form-control py-2" name="mappings[' . $mapping['mapping_id'] . '][userFieldName]" value="' . htmlspecialchars($mapping['user_field_name']) . '" placeholder="User Field Name" list="userFieldsList">
                        </div> 
                        <div class="col-7">
                            <input type="text" class="form-control py-2" name="mappings[' . $mapping['mapping_id'] . '][websiteFieldName]" value="' . htmlspecialchars($showvalue) . '" placeholder="Website Field Name">
                        </div>  
                    </div>';
                }
                
                echo '
                </div>
                
                <div class="mt-3 d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addMapping()">Add Mapping</button>
                        <a href="' . $_SERVER['PHP_SELF'] . '?cid=' . $companyID . '&cname=' . urlencode($companyname) . '&addtestcase=1" class="btn btn-primary btn-sm ms-2">TEST</a>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- User Fields Datalist for autocomplete -->
<datalist id="userFieldsList">';
foreach ($knownUserFields as $field) {
    echo '<option value="' . htmlspecialchars($field) . '">';
}
echo '</datalist>
';


echo '
        </div>
    </div>
</div>';


echo '
<script>
    function addMapping() {
        var mappings = document.getElementById("mappings");
        var newRow = document.createElement("div");
        newRow.className = "form-group row no-gutters small-row";
        newRow.innerHTML = `
            <div class="col-5">
                <input type="text" class="form-control py-2 mb-1" name="mappings[new][userFieldName]" placeholder="User Field Name" list="userFieldsList">
            </div>
            <div class="col-7">
                <input type="text" class="form-control py-2 mb-1" name="mappings[new][websiteFieldName]" placeholder="Website Field Name">
            </div>
        `;
        mappings.appendChild(newRow);
    }
    
    $(document).ready(function() {
        $(".copy-content").click(function() {
            var content = $(this).text();
            copyToClipboard(content);
        });

        $(".copy-contentdate").click(function() {
            var text = $(this).text();
            var content = "||date||" + text.substring(0, 1);
            copyToClipboard(content);
        });

        function copyToClipboard(text) {
            var tempInput = $("<input>");
            $("body").append(tempInput);
            tempInput.val(text).select();
            document.execCommand("copy");
            tempInput.remove();
            
            // Optional: Show a tooltip or notification that text was copied
            alert("Copied: " + text);
        }
    });
</script>
';

/*
if (!isset($componentmode) || $componentmode != 'include') {
$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
}
*/