<?php
if (!isset($componentmode)) $componentmode = 'default';
if ($componentmode != 'include') {
    // Include the site-controller.php file
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php';
}

// Get company id
if (empty($company_id)) {
    $company_id = $_REQUEST['cid'] ?? $_REQUEST['bid'] ?? 0;
    if (!is_numeric($company_id)) {
        $company_id = $qik->decodeId($company_id);
    }
}

if (isset($_REQUEST['bname'])) $companyname = $_REQUEST['bname']; else $companyname = '';
if (isset($_REQUEST['version'])) $versionnumber = $_REQUEST['version']; else $versionnumber = 1;

// Preset user field names
$userFieldNames = [
    'username',
    'email',
    'password',
    'first_name',
    'middle_name',
    'last_name',
    'mailing_address',
    'city',
    'state',
    'zip_code',
    'country',
    'birthdaystatus',
    'account_type',
    'type',
    'termsagree'
];

###==============================================================================================================
###==============================================================================================================
if ($componentmode == 'default' && $_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['newversion'])) {
    $versionnumber = $_REQUEST['newversion'];
    
    // Use correct table name bg_form_field_mappings, but adjust version handling
    $bid = $company_id;
    $sqllist = "update `bg_form_field_mappings` set `version_status`='inactive', `modify_dt`=now() where company_id=" . $bid . " and `version`!='" . $versionnumber . "' and `version_status`='active';";
    
    // Add default field mappings for the new version
    $sqllist .= "INSERT INTO `bg_form_field_mappings` (`company_id`, `version`, `version_status`, `user_field_name`, `website_field_name`, `fieldformattype`, `fieldformat`, `create_dt`, `modify_dt`, `status`) VALUES 
    (" . $bid . ", " . $versionnumber . ", 'active', 'profile_phone_type', 'profile_phone_type', NULL, NULL, now(), now(), 'active'),
    (" . $bid . ", " . $versionnumber . ", 'active', 'profile_gender', 'profile_gender', NULL, NULL, now(), now(), 'active'),
    (" . $bid . ", " . $versionnumber . ", 'active', 'profile_agree_terms', 'profile_agree_terms', NULL, NULL, now(), now(), 'active'),
    (" . $bid . ", " . $versionnumber . ", 'active', 'profile_agree_email', 'profile_agree_email', NULL, NULL, now(), now(), 'active'),
    (" . $bid . ", " . $versionnumber . ", 'active', 'profile_agree_text', 'profile_agree_text', NULL, NULL, now(), now(), 'active')";
    
    foreach (explode(';', $sqllist) as $sql) {
        if (!empty(trim($sql))) $database->query($sql);
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?cid=' . $_REQUEST['cid'] . '&section=formfieldedit');
    exit;
}


###==============================================================================================================
###==============================================================================================================
if ($componentmode == 'default' && $_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['addtestcase'] == '0') {
    // Fetch the post data
    $mappings = $_POST['mappings'];
 
    // Loop over each mapping
    foreach ($mappings as $mappingID => $mappingData) {
        $userFieldName = trim($mappingData['userFieldName']);
        $websiteFieldName = trim($mappingData['websiteFieldName']);
        $expanded = false;
        $fieldformattype = '';
        $fieldformat = '';
        
        if (strpos($websiteFieldName, '||') !== false) {
            list($websiteFieldName, $fieldformattype, $fieldformat) = explode('||', $websiteFieldName);
            $expanded = true;
        } 

        // Check if the website field name is blank and set status accordingly
        $status = ($websiteFieldName == '') ? 'notused' : 'active';

        if ($expanded) {
            // If mappingID is not a number, insert new mapping
            if (!is_numeric($mappingID)) {
                $sql = "INSERT INTO bg_form_field_mappings (company_id, user_field_name, website_field_name, status, fieldformattype, fieldformat, `version`, version_dt, version_status) VALUES (:company_id, :user_field_name, :website_field_name, :status, :fieldformattype, :fieldformat, $versionnumber, now(), 'active')";
                $database->query($sql, ['fieldformattype' => $fieldformattype, 'fieldformat' => $fieldformat, 'company_id' => $company_id, 'user_field_name' => $userFieldName, 'website_field_name' => $websiteFieldName, 'status' => $status]);
            } else {
                // Else, update the existing mapping
                $sql = "UPDATE bg_form_field_mappings SET fieldformattype = :fieldformattype, fieldformat = :fieldformat, user_field_name = :user_field_name, website_field_name = :website_field_name, status = :status WHERE mapping_id = :mapping_id";
                $database->query($sql, ['fieldformattype' => $fieldformattype, 'fieldformat' => $fieldformat, 'user_field_name' => $userFieldName, 'website_field_name' => $websiteFieldName, 'status' => $status, 'mapping_id' => $mappingID]);
            }
        } else {
            if ($userFieldName != '') {
                // If mappingID is not a number, insert new mapping
                if (!is_numeric($mappingID)) {
                    $sql = "INSERT INTO bg_form_field_mappings (company_id, user_field_name, website_field_name, status, `version`, version_dt, version_status) VALUES (:company_id, :user_field_name, :website_field_name, :status, $versionnumber, now(), 'active')";
                    $database->query($sql, ['company_id' => $company_id, 'user_field_name' => $userFieldName, 'website_field_name' => $websiteFieldName, 'status' => $status]);
                } else {
                    // Else, update the existing mapping
                    $sql = "UPDATE bg_form_field_mappings SET user_field_name = :user_field_name, website_field_name = :website_field_name, status = :status WHERE mapping_id = :mapping_id";
                    $database->query($sql, ['user_field_name' => $userFieldName, 'website_field_name' => $websiteFieldName, 'status' => $status, 'mapping_id' => $mappingID]);
                }
            }
        }
    }

    // Redirect back to form field edit page
    header('Location: ' . $_SERVER['PHP_SELF'] . '?cid=' . $_REQUEST['cid'] . '&section=formfieldedit');
    exit;
}

###==============================================================================================================
###==============================================================================================================
if ($componentmode == 'default' && $_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['addtestcase'])) {
    // Fetch POST data
    $companyID = $_REQUEST["bid"] ?? $_REQUEST["cid"] ?? 0;
    $userID = 20; // User ID is static in this case

    // Check if the data already exists
    $checkSql = "SELECT * FROM bg_user_companies WHERE user_id = :user_id AND company_id = :company_id";
    $stmt = $database->query($checkSql, ['user_id' => $userID, 'company_id' => $companyID]);

    $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If the data doesn't exist, insert it into the database
    if (count($existingData) == 0) {
        // Insert data into database
        $sql = "INSERT INTO bg_user_companies (user_id, company_id, create_dt, status) VALUES (:user_id, :company_id, NOW(), 'testing')";
        $database->query($sql, ['user_id' => $userID, 'company_id' => $companyID]);
    }
    
    // Redirect back to form field edit page
    header('Location: ' . $_SERVER['PHP_SELF'] . '?cid=' . $_REQUEST['cid'] . '&section=formfieldedit');
    exit;
}

###==============================================================================================================
###==============================================================================================================
if ($componentmode == 'default' && $_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['buildout'])) {
    $companyID = $company_id;
    
    // Create SQL for inserting default field mappings
    $sqllist = "INSERT INTO `bg_form_field_mappings` (`company_id`, `version`, `version_status`, `user_field_name`, `website_field_name`, `fieldformattype`, `fieldformat`, `create_dt`, `modify_dt`, `status`) VALUES 
    (" . $companyID . ", 1,  'active', 'profile_username', 'profile_username', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_email', 'profile_email', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_password', 'profile_password', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_title', 'profile_title', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_first_name', 'profile_first_name', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_middle_name', 'profile_middle_name', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_last_name', 'profile_last_name', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_mailing_address', 'profile_mailing_address', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_city', 'profile_city', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_state', 'profile_state', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_zip_code', 'profile_zip_code', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_country', 'profile_country', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_phone_number', 'profile_phone_number', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'birthdate', 'birthdate', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_phone_type', 'profile_phone_type', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_gender', 'profile_gender', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_agree_terms', 'profile_agree_terms', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_agree_email', 'profile_agree_email', NULL, NULL, now(), now(), 'active'),
    (" . $companyID . ", 1,  'active', 'profile_agree_text', 'profile_agree_text', NULL, NULL, now(), now(), 'active')";
    
    foreach (explode(';', $sqllist) as $sql) {
        if (!empty(trim($sql))) $database->query($sql);
    }
    
    // Redirect back to form field edit page
    header('Location: ' . $_SERVER['PHP_SELF'] . '?cid=' . $_REQUEST['cid'] . '&section=formfieldedit');
    exit;
}

###==============================================================================================================
###==============================================================================================================
// Fetch existing mappings
$sql = "SELECT max(version) version FROM bg_form_field_mappings WHERE company_id = :company_id and version_status='active' group by company_id limit 1";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$version = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($version[0]['version'])) {
    $versionnumber = $version[0]['version'];
    $criteria = " and version=" . $versionnumber;
} else {
    $criteria = '';
}

$sql = "SELECT * FROM bg_form_field_mappings WHERE company_id = " . $company_id . " " . $criteria;
$stmt = $database->prepare($sql);
$stmt->execute();
$mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Additional styles
$additionalstyles .= '
<style>
    .no-gutters {
        margin-top: 0;
        margin-bottom: 2px;
    }

    .no-gutters > .col,
    .no-gutters > [class*="col-"] {
        padding-top: 0;
        padding-bottom: 0;
    }
    
    .small-row .form-control, 
    .small-row .col-form-label {
        padding: .1rem .2rem;
    }
    .light-grey-bg {
        background-color: #f2f2f2; /* This is a light grey color */
    }

    ::-webkit-input-placeholder { /* Chrome/Opera/Safari */
        color: #bbbbbb !important;
        opacity: 1 !important;
    }
    ::-moz-placeholder { /* Firefox 19+ */
        color: #bbbbbb !important;
        opacity: 1 !important;
    }
    :-ms-input-placeholder { /* IE 10+ */
        color: #bbbbbb !important;
        opacity: 1 !important;
    }
    :-moz-placeholder { /* Firefox 18- */
        color: #bbbbbb !important;
        opacity: 1 !important;
    }
    input, textarea, select {
        border-color: #bbbbbb !important;
    }
    input:focus, textarea:focus, select:focus {
        border-color: #bbbbbb !important;
    }
    .small-font {
    }

    .copy-contentdate:hover {
        background-color: yellow;
        cursor: pointer;
    }
    .copy-content:hover {
        background-color: yellow;
        cursor: pointer;
    }
</style>
';
?>

<script>
    function addMapping() {
        var mappings = document.getElementById('mappings');
        var mapping = document.createElement('div');
        mapping.className = 'form-group row no-gutters small-row';
        mapping.innerHTML = '<div class="col-5"><input type="text" class="form-control" name="mappings[new][userFieldName]" placeholder="User Field Name"></div>' +
                          '<div class="col-7"><input type="text" class="form-control" name="mappings[new][websiteFieldName]" placeholder="Website Field Name"></div>';
        mappings.appendChild(mapping);
    }
</script>

<body class="light-grey-bg">
<?php 
echo '<h4>&nbsp;' . $companyname . ' <small>(' . $company_id . ' : ' . $versionnumber . ')</small></h4><hr>';
?>
<div class="container">
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="addtestcase" value="0">
        <input type="hidden" name="version" value="<?php echo $versionnumber; ?>">
        <input type="hidden" name="cid" value="<?php echo $_REQUEST['cid']; ?>">
        <input type="hidden" name="section" value="formfieldedit">
        <div id="mappings">
        <?php
$index = 0;
foreach ($mappings as $mapping) {
    $showvalue = $mapping['website_field_name'];
    if ($mapping['status'] == 'notused') {
        $showvalue = '';
    }
    
    if ($mapping['fieldformattype'] != '') {
        $showvalue .= '||' . $mapping['fieldformattype'] . '||' . $mapping['fieldformat'];
    }

    $row_class = ($index % 2 == 0) ? 'bg-light' : 'bg-white';

    echo '<div class="form-group row no-gutters small-row ' . $row_class . '">
            <div class="col-4">
                <input type="text" class="form-control p-1 py-2 ' . $row_class . '" name="mappings[' . $mapping['mapping_id'] . '][userFieldName]" value="' . $mapping['user_field_name'] . '" placeholder="User Field Name">
            </div> 
            <div class="col-8">
                <input type="text" class="form-control p-1 py-2 ' . $row_class . '" name="mappings[' . $mapping['mapping_id'] . '][websiteFieldName]" value="' . $showvalue . '" placeholder="' . $mapping['website_field_name'] . '">
            </div>  
        </div>';
    
    $index++;
}
?>



        </div>
        <input type="button" class="btn btn-primary btn-sm" onclick="addMapping()" value="Add Mapping">
        <?php
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?cid=' . $_REQUEST['cid'] . '&section=formfieldedit&addtestcase=1" type="button" class="btn btn-primary btn-sm">TEST</a>';
        ?>
        <input type="submit" class="btn btn-success btn-sm float-right" value="Save">
    </form>
</div>

<div class="container mt-2">
    <div class="row">
        <div class="col">
            <a class="btn btn-secondary btn-sm" href="<?= $_SERVER['PHP_SELF'] . '?cid=' . $_REQUEST['cid'] . '&section=formfieldedit&buildout=' . $company_id; ?>" role="button">BuildOut</a>
            <a class="btn btn-secondary btn-sm" href="<?= $_SERVER['PHP_SELF'] . '?cid=' . $_REQUEST['cid'] . '&section=formfieldedit&newversion=' . ($versionnumber+1); ?>" role="button">New Version</a>

            <a class="btn btn-secondary btn-sm" data-toggle="collapse" href="#dateformatContent" role="button" aria-expanded="false" aria-controls="dateformatContent">Dates</a>
            <a class="btn btn-secondary btn-sm" data-toggle="collapse" href="#fieldlistContent" role="button" aria-expanded="false" aria-controls="fieldlistContent">Fields</a>
            <a class="btn btn-secondary btn-sm" data-toggle="collapse" href="#punchh" role="button" aria-expanded="false" aria-controls="punchh">PunchH</a>
        </div>
    </div>
    <div class="row small-font">
        <div class="col">
            <div class="collapse" id="dateformatContent">
                <ul>
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
            <div class="collapse" id="fieldlistContent">
                <ul>
                    <li>formname - the name/id of a form</li>
                </ul>
            </div>

            <div class="collapse" id="punchh">
                <ul>
                    <li>formname - <span class="copy-content">user-form||punchh||</span></li>
                    <li>email - <span class="copy-content">user_email</span></li>
                    <li>password - <span class="copy-content">user_password</span></li>
                    <li>first_name - <span class="copy-content">user_first_name</span></li>
                    <li>phone_number - <span class="copy-content">user_phone</span></li>
                    <li>last_name - <span class="copy-content">user_last_name</span></li>
                    <li>password - <span class="copy-content">user_password_confirmation</span></li>
                    <li>birthday - <span class="copy-content">user_birthday_3i||date||j</span></li>
                    <li>birthday - <span class="copy-content">user_birthday_2i||date||n</span></li>
                    <li>FIXEDVALUE:326236 - <span class="copy-content">user_fav_location_id</span></li>
                    <li>birthday - <span class="copy-content">user_birthday_1i||date||Y</span></li>
                    <li>termsagree - <span class="copy-content">user_terms_and_conditions</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.copy-content').click(function() {
            var content = $(this).text(); // Get the text content of the clicked span
            copyToClipboard(content);
        });

        $('.copy-contentdate').click(function() {
            var text = $(this).text();
            var content = '||date||' + text.substring(text.indexOf(':') + 1, text.indexOf(':') + 2);
            copyToClipboard(content);
        });

        function copyToClipboard(text) {
            var tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(text).select();
            document.execCommand('copy');
            tempInput.remove();
        }
    });
</script>