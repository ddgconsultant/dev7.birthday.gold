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

if (isset($_REQUEST['cname'])) $companyname = $_REQUEST['cname']; else $companyname = '';
if (isset($_REQUEST['version'])) $versionnumber = $_REQUEST['version']; else $versionnumber = 1;

// Preset user field names
$userFieldNames = [
    'profile_username',
    'profile_email',
    'profile_password',
    'profile_first_name',
    'profile_middle_name',
    'profile_last_name',
    'profile_mailing_address',
    'profile_city',
    'profile_state',
    'profile_zip_code',
    'profile_country',
    'profile_phone_number',
    'birthdate',
    'profile_phone_type',
    'profile_gender',
    'profile_agree_terms',
    'profile_agree_email',
    'profile_agree_text'
];

###==============================================================================================================
###==============================================================================================================
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['newversion']) && isset($_REQUEST['section']) && $_REQUEST['section'] == 'formfieldedit') {
    $versionnumber = $_REQUEST['newversion'];
    
    // Update existing mappings to inactive
    $sql = "UPDATE `bg_form_field_mappings` SET 
        `version_status`='inactive', 
        `modify_dt`=now() 
    WHERE company_id = ? 
    AND `version` != ? 
    AND `version_status`='active'";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([$company_id, $versionnumber]);
    
    // Insert default mappings for the new version
    $sql = "INSERT INTO `bg_form_field_mappings` 
            (`company_id`, `version`, `version_status`, `user_field_name`, 
            `website_field_name`, `fieldformattype`, `fieldformat`, 
            `create_dt`, `modify_dt`, `status`) 
    VALUES (?, ?, 'active', ?, ?, NULL, NULL, now(), now(), 'active')";
    
    $stmt = $database->prepare($sql);
    
    // Add default fields for the new version
    $defaultFields = [
        'profile_phone_type',
        'profile_gender',
        'profile_agree_terms',
        'profile_agree_email',
        'profile_agree_text'
    ];
    
    foreach ($defaultFields as $fieldName) {
        $stmt->execute([$company_id, $versionnumber, $fieldName, $fieldName]);
    }
    
    // Set success message
    $_SESSION['form_field_message'] = "Version {$versionnumber} created successfully with default fields.";
    $_SESSION['form_field_message_type'] = 'success';
    
    // Redirect back to form field edit page
    header("Location: {$_SERVER['PHP_SELF']}?cid={$_REQUEST['cid']}&section=formfieldedit");
    exit;
}


###==============================================================================================================
###==============================================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['addtestcase']) && $_REQUEST['addtestcase'] == '0' && isset($_REQUEST['section']) && $_REQUEST['section'] == 'formfieldedit') {
    // Fetch the post data
    $mappings = $_POST['mappings'];
 
    // Loop over each mapping
    foreach ($mappings as $mappingID => $mappingData) {
        $userFieldName = trim($mappingData['userFieldName']);
        $websiteFieldName = trim($mappingData['websiteFieldName']);
        $fieldformattype = isset($mappingData['fieldFormatType']) ? trim($mappingData['fieldFormatType']) : '';
        $fieldformat = isset($mappingData['fieldFormat']) ? trim($mappingData['fieldFormat']) : '';
        
        // If using the old format with ||
        if (strpos($websiteFieldName, '||') !== false) {
            list($websiteFieldName, $fieldformattype, $fieldformat) = explode('||', $websiteFieldName);
        }

        // Use the status from the form, or default based on website field name
        $status = isset($mappingData['status']) ? $mappingData['status'] : (($websiteFieldName == '') ? 'notused' : 'active');

        // If mappingID is not a number, insert new mapping
        if (!is_numeric($mappingID)) {
            if (!empty($userFieldName)) {
                $sql = "INSERT INTO bg_form_field_mappings 
                        (company_id, user_field_name, website_field_name, fieldformattype, fieldformat, status, `version`, version_dt, version_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, now(), 'active')";
                $stmt = $database->prepare($sql);
                $stmt->execute([
                    $company_id, 
                    $userFieldName, 
                    $websiteFieldName, 
                    $fieldformattype, 
                    $fieldformat, 
                    $status,
                    $versionnumber
                ]);
            }
        } else {
            // Else, update the existing mapping
            $sql = "UPDATE bg_form_field_mappings 
                    SET user_field_name = ?, 
                        website_field_name = ?, 
                        fieldformattype = ?, 
                        fieldformat = ?, 
                        status = ? 
                    WHERE mapping_id = ?";
            $stmt = $database->prepare($sql);
            $stmt->execute([
                $userFieldName, 
                $websiteFieldName, 
                $fieldformattype, 
                $fieldformat, 
                $status, 
                $mappingID
            ]);
        }
    }

    // Set success message
    $_SESSION['form_field_message'] = 'Form field mappings saved successfully.';
    $_SESSION['form_field_message_type'] = 'success';

    // Redirect back to form field edit page
    header("Location: {$_SERVER['PHP_SELF']}?cid={$_REQUEST['cid']}&section=formfieldedit");
    exit;
}

###==============================================================================================================
###==============================================================================================================
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['addtestcase']) && isset($_REQUEST['section']) && $_REQUEST['section'] == 'formfieldedit') {
    // Fetch data
    $companyID = $company_id;
    $userID = 20; // User ID is static in this case

    // Check if the data already exists
    $sql = "SELECT * FROM bg_user_companies WHERE user_id = ? AND company_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->execute([$userID, $companyID]);
    $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If the data doesn't exist, insert it into the database
    if (count($existingData) == 0) {
        // Insert data into database
        $sql = "INSERT INTO bg_user_companies (user_id, company_id, create_dt, status) VALUES (?, ?, NOW(), 'testing')";
        $stmt = $database->prepare($sql);
        $stmt->execute([$userID, $companyID]);
        
        $_SESSION['form_field_message'] = 'Test user added successfully. You can now test the enrollment process.';
        $_SESSION['form_field_message_type'] = 'success';
    } else {
        $_SESSION['form_field_message'] = 'Test user already exists for this company.';
        $_SESSION['form_field_message_type'] = 'info';
    }
    
    // Redirect back to form field edit page
    header("Location: {$_SERVER['PHP_SELF']}?cid={$_REQUEST['cid']}&section=formfieldedit");
    exit;
}

###==============================================================================================================
###==============================================================================================================
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['buildout']) && isset($_REQUEST['section']) && $_REQUEST['section'] == 'formfieldedit') {
    $companyID = $company_id;
    
    // Prepare a single query for all default fields
    $sql = "INSERT INTO `bg_form_field_mappings` 
            (`company_id`, `version`, `version_status`, `user_field_name`, 
            `website_field_name`, `fieldformattype`, `fieldformat`, 
            `create_dt`, `modify_dt`, `status`) 
    VALUES (?, 1, 'active', ?, ?, NULL, NULL, now(), now(), 'active')";
    
    $stmt = $database->prepare($sql);
    
    foreach ($userFieldNames as $fieldName) {
        $stmt->execute([$companyID, $fieldName, $fieldName]);
    }
    
    // Set success message
    $_SESSION['form_field_message'] = 'All default form fields have been added successfully.';
    $_SESSION['form_field_message_type'] = 'success';
    
    // Redirect back to form field edit page
    header("Location: {$_SERVER['PHP_SELF']}?cid={$_REQUEST['cid']}&section=formfieldedit");
    exit;
}

###==============================================================================================================
###==============================================================================================================
// Check if we're viewing version history
if (isset($_GET['view']) && $_GET['view'] == 'history' && isset($_GET['section']) && $_GET['section'] == 'formfieldedit') {
    // Get company name if not already fetched
    if (!isset($company_name)) {
        $company = $app->getcompanydetails($company_id);
        $company_name = $company['company_name'] ?? 'Unknown Company';
    }
    
    // Fetch version history
    $sql = "SELECT version, 
            MAX(version_status) as version_status, 
            MAX(version_dt) as version_dt,
            COUNT(*) as field_count
            FROM bg_form_field_mappings
            WHERE company_id = ?
            GROUP BY version
            ORDER BY version DESC";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([$company_id]);
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Display version history
    ?>
    <div class="main-header mb-4">
        <h1>Version History</h1>
        <p class="text-muted">Form field mapping versions for <?php echo htmlspecialchars($company_name ?? 'Company'); ?></p>
    </div>
    
    <div class="mb-3">
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?cid=<?php echo $company_id; ?>&section=formfieldedit" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Field Mappings
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Field Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($versions as $version): ?>
                        <tr>
                            <td>
                                <strong>Version <?php echo $version['version']; ?></strong>
                                <?php if ($version['version_status'] == 'active'): ?>
                                    <span class="badge bg-success ms-2">Current</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $version['version_status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($version['version_status']); ?>
                                </span>
                            </td>
                            <td><?php echo $version['version_dt'] ? date('M d, Y g:i A', strtotime($version['version_dt'])) : '-'; ?></td>
                            <td><?php echo $version['field_count']; ?> fields</td>
                            <td>
                                <?php if ($version['version_status'] != 'active'): ?>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?cid=<?php echo $company_id; ?>&section=formfieldedit&activate_version=<?php echo $version['version']; ?>" 
                                   class="btn btn-sm btn-primary"
                                   onclick="return confirm('Activate Version <?php echo $version['version']; ?>?');">
                                    Activate
                                </a>
                                <?php endif; ?>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?cid=<?php echo $company_id; ?>&section=formfieldedit&preview_version=<?php echo $version['version']; ?>" 
                                   class="btn btn-sm btn-outline-secondary">
                                    Preview
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php
    return; // Stop execution here for version history view
}

// Handle version activation
if (isset($_GET['activate_version']) && isset($_GET['section']) && $_GET['section'] == 'formfieldedit') {
    $activate_version = intval($_GET['activate_version']);
    
    // Deactivate all versions
    $sql = "UPDATE bg_form_field_mappings 
            SET version_status = 'inactive' 
            WHERE company_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->execute([$company_id]);
    
    // Activate selected version
    $sql = "UPDATE bg_form_field_mappings 
            SET version_status = 'active' 
            WHERE company_id = ? AND version = ?";
    $stmt = $database->prepare($sql);
    $stmt->execute([$company_id, $activate_version]);
    
    // Set success message
    $_SESSION['form_field_message'] = "Version {$activate_version} has been activated successfully.";
    $_SESSION['form_field_message_type'] = 'success';
    
    // Redirect back to form field edit
    header("Location: {$_SERVER['PHP_SELF']}?cid={$company_id}&section=formfieldedit");
    exit;
}

###==============================================================================================================
###==============================================================================================================
// Fetch existing mappings
$sql = "SELECT max(version) version FROM bg_form_field_mappings 
        WHERE company_id = ? and version_status='active' 
        GROUP BY company_id LIMIT 1";
$stmt = $database->prepare($sql);
$stmt->execute([$company_id]);
$version = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($version[0]['version'])) {
    $versionnumber = $version[0]['version'];
    $criteria = " AND version = " . $versionnumber;
} else {
    $criteria = '';
}

$sql = "SELECT * FROM bg_form_field_mappings 
        WHERE company_id = ? $criteria 
        ORDER BY user_field_name";
$stmt = $database->prepare($sql);
$stmt->execute([$company_id]);
$mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch company details
$sql = "SELECT company_name, signup_url FROM bg_companies WHERE company_id = ?";
$stmt = $database->prepare($sql);
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$company_name = $company['company_name'] ?? 'Unknown Company';
$signup_url = $company['signup_url'] ?? '';

// Check if company is APP ONLY
$isAppOnly = ($company['signup_url'] ?? '') === $website['apponlytag'];

// Format type definitions - must match bgr_getprocessdetails.php
$formatTypes = [
    '' => 'None',
    'date' => 'Date',
    'date-calculate' => 'Date Calculate',
    'date-numberformat' => 'Date Number',
    'lowerdate' => 'Lowercase Date',
    'state' => 'State',
    'title' => 'Title',
    'name' => 'Name',
    'gender' => 'Gender',
    'tf->yn' => 'True/False → Yes/No',
    'tf->10' => 'True/False → 1/0',
    'tf->fixed' => 'True/False → Fixed',
    'tf->fixedpipe' => 'True/False → Fixed (pipe)',
    'country' => 'Country',
    'phone' => 'Phone'
];

// Additional styles for modern UI
$additionalstyles .= '
<style > body { background-color: #f9fafb; }
.main-header { margin-bottom: 1.5rem; }
.version-badge { display: inline-block; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 600; background-color: #dbeafe; color: #1e40af; border-radius: 9999px; margin-left: 0.5rem; }
.company-name { color: #6b7280; }
.card { background-color: white; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); margin-bottom: 1.5rem; overflow: hidden; }
.card-header { padding: 1rem 1.5rem; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
.card-body { padding: 1.5rem; }
.btn-group { display: flex; margin-bottom: 1.5rem; }
.btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; border: 1px solid transparent; cursor: pointer; transition: all 0.2s; }
.btn-outline { background-color: white; border-color: #d1d5db; color: #374151; }
.btn-outline:hover { background-color: #f9fafb; }
.btn-primary { background-color: #2563eb; color: white; }
.btn-primary:hover { background-color: #1d4ed8; }
.btn-success { background-color: #10b981; color: white; }
.btn-success:hover { background-color: #059669; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.template-btn { background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 0.25rem 0.75rem; font-size: 0.75rem; margin-right: 0.5rem; margin-bottom: 0.5rem; }
.template-btn:hover { background-color: #e5e7eb; }
.form-table { width: 100%; border-collapse: collapse; }
.form-table th { text-align: left; padding: 1rem; text-transform: uppercase; color: #6b7280; font-weight: 600; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; }
.form-table td { padding: 0.5rem vertical-align:middle; }
.form-table tr {     /*  height: 56px; Set a fixed height for rows to reduce spacing */ }
.form-control { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; }
.form-control:focus { outline: none; border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.25); }
.form-select { width: 100%; padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; background-image: url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3e%3cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M6 8l4 4 4-4\'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; appearance: none; }
.form-select:focus { outline: none; border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.25); }
.table-actions { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background-color: #f9fafb; border-top: 1px solid #e5e7eb; }
.remove-btn { color: #ef4444; background: none; border: none; cursor: pointer; padding: 0.25rem; }
.remove-btn:hover { color: #dc2626; }
.info-icon { color: #6b7280; margin-left: 0.5rem; }
.field-control-group { display: flex; align-items: center; }
.status-toggle-btn { background: none; border: none; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; }
.status-toggle-btn:hover { opacity: 0.8; }
.text-success { color: #10b981; }
.text-secondary { color: #9ca3af; }
.text-center { text-align: center; }

/* APP ONLY disabled state */
.field-mappings-section.app-only {
    opacity: 0.7;
}
.field-mappings-section.app-only .form-control:disabled {
    background-color: #f8d7da;
    border-color: #dc3545;
    cursor: not-allowed;
}
.field-mappings-section.app-only .btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Collapsible header styling */
.card-header[data-bs-toggle="collapse"] {
    transition: background-color 0.2s;
}

.card-header[data-bs-toggle="collapse"]:hover {
    background-color: #e9ecef;
}

.card-header[data-bs-toggle="collapse"] i {
    transition: transform 0.2s;
}
</style >
';
?>

<div class="container field-mappings-section <?php echo $isAppOnly ? 'app-only' : ''; ?>">
    <?php
    // Display alert messages from session
    if (isset($_SESSION['form_field_message'])) {
        $message = $_SESSION['form_field_message'];
        $alertType = $_SESSION['form_field_message_type'] ?? 'info';
        unset($_SESSION['form_field_message']);
        unset($_SESSION['form_field_message_type']);
    ?>
    <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert" id="formFieldAlert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <script>
        // Auto-fade the alert after 5 seconds
        setTimeout(function() {
            var alert = document.getElementById('formFieldAlert');
            if (alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    </script>
    <?php } ?>
    
    <div class="main-header">
        <h1>
            Form Field Mappings
            <?php if ($isAppOnly): ?>
                <i class="bi bi-phone-x text-danger ms-2" title="APP ONLY - No form field mapping needed"></i>
            <?php endif; ?>
        </h1>
        <div>
            <span class="company-name"><?php echo htmlspecialchars($company_name); ?></span>
            <span class="version-badge">Version <?php echo $versionnumber; ?> • Active</span>
        </div>
        <?php if ($isAppOnly): ?>
        <p class="text-danger fw-bold mt-2 mb-0">This is an APP ONLY company - Form field mapping is not applicable</p>
        <?php endif; ?>
    </div>
    
    <div class="btn-group">
        <button type="button" class="btn btn-outline" onclick="location.href='<?php echo $_SERVER['PHP_SELF']; ?>?cid=<?php echo $company_id; ?>&section=formfieldedit&view=history'">
            <i class="bi bi-clock-history me-2"></i> Version History
        </button>
        <button type="button" class="btn btn-outline" onclick="location.href='<?php echo $_SERVER['PHP_SELF']; ?>?cid=<?php echo $company_id; ?>&newversion=<?php echo ($versionnumber + 1); ?>&section=formfieldedit'" <?php echo $isAppOnly ? 'disabled' : ''; ?>>
            <i class="bi bi-plus-circle me-2"></i> New Version
        </button>
        <button type="button" class="btn btn-outline" onclick="location.href='<?php echo $_SERVER['PHP_SELF']; ?>?buildout=<?php echo $company_id; ?>&cid=<?php echo $company_id; ?>&section=formfieldedit'" <?php echo $isAppOnly ? 'disabled' : ''; ?>>
            <i class="bi bi-grid-3x3-gap me-2"></i> BuildOut
        </button>
        <?php if (!empty($signup_url) && $signup_url !== $website['apponlytag']): ?>
        <button type="button" class="btn btn-success" onclick="window.open('<?php echo htmlspecialchars($signup_url); ?>', '_blank')" title="Open the signup URL for this company">
            <i class="bi bi-eye me-2"></i> Preview Signup
        </button>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" id="addFieldBtn" <?php echo $isAppOnly ? 'disabled' : ''; ?>>
            <i class="bi bi-plus-lg me-2"></i> Add Field
        </button>
    </div>
    
    <?php if ($isAppOnly): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>APP ONLY Company:</strong> Form field mapping is disabled for app-only companies as they don't use web forms for enrollment.
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#formatHelpCollapse" aria-expanded="false" aria-controls="formatHelpCollapse">
            <span><i class="bi bi-question-circle me-2"></i>Format Examples & Help</span>
            <i class="bi bi-chevron-down" id="formatHelpChevron"></i>
        </div>
        <div class="collapse" id="formatHelpCollapse">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Date Formats</h6>
                        <code>Y-m-d</code> → 2025-07-27<br>
                        <code>m/d/Y</code> → 07/27/2025<br>
                        <code>F j, Y</code> → July 27, 2025<br>
                        <code>n/j/Y</code> → 7/27/2025<br>
                        
                        <h6 class="mt-3">Phone Formats</h6>
                        <code>(###) ###-####</code> → (555) 123-4567<br>
                        <code>###-###-####</code> → 555-123-4567<br>
                        <code>012</code> → First 3 digits only<br>
                        <code>345</code> → Middle 3 digits only<br>
                        <code>6789</code> → Last 4 digits only<br>
                        
                        <h6 class="mt-3">State Format</h6>
                        <code>code</code> → Converts "California" to "CA"
                    </div>
                    <div class="col-md-6">
                        <h6>Gender Formats</h6>
                        <code>uppercode</code> → M or F<br>
                        <code>lowercode</code> → m or f<br>
                        <code>upper</code> → MALE or FEMALE<br>
                        <code>ucwords</code> → Male or Female<br>
                        <code>MF->12</code> → 1 (male) or 2 (female)<br>
                        
                        <h6 class="mt-3">True/False → Yes/No Formats</h6>
                        <code>uinitial</code> → Y or N<br>
                        <code>ucwords</code> → Yes or No<br>
                        <code>upper</code> → YES or NO<br>
                        <code>lower</code> → yes or no<br>
                        
                        <h6 class="mt-3">Country Formats</h6>
                        <code>code</code> → US<br>
                        <code>codelong</code> → USA<br>
                        <code>fullname_lower</code> → united states
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" <?php echo $isAppOnly ? 'onsubmit="return false;"' : ''; ?>>
        <input type="hidden" name="addtestcase" value="0">
        <input type="hidden" name="version" value="<?php echo $versionnumber; ?>">
        <input type="hidden" name="cid" value="<?php echo $company_id; ?>">
        <input type="hidden" name="section" value="formfieldedit">
        
        <div class="card">
            <table class="form-table" id="mappingsTable">
                <thead>
                    <tr>
                        <th width="35">STATUS</th>
                        <th>PROFILE FIELD</th>
                        <th>WEBSITE FIELD</th>
                        <th>FORMAT TYPE</th>
                        <th>FORMAT</th>
                        <th width="50"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mappings as $mapping): ?>
                    <tr data-mapping-id="<?php echo $mapping['mapping_id']; ?>" data-status="<?php echo $mapping['status']; ?>">
                        <td class="text-center">
                            <button type="button" 
                                   class="status-toggle-btn" 
                                   data-status="<?php echo $mapping['status']; ?>"
                                   title="<?php echo $mapping['status'] === 'active' ? 'Active' : 'Not Used'; ?>"
                                   <?php echo $isAppOnly ? 'disabled' : ''; ?>>
                                <i class="bi <?php echo $mapping['status'] === 'active' ? 'bi-check-circle-fill text-success' : 'bi-dash-circle-fill text-secondary'; ?>"></i>
                                <input type="hidden" 
                                      name="mappings[<?php echo $mapping['mapping_id']; ?>][status]" 
                                      value="<?php echo $mapping['status']; ?>">
                            </button>
                        </td>
                        <td>
                            <input type="text" 
                                   class="form-control" 
                                   name="mappings[<?php echo $mapping['mapping_id']; ?>][userFieldName]" 
                                   value="<?php echo htmlspecialchars($mapping['user_field_name']); ?>" 
                                   placeholder="User field name..." 
                                   list="userFieldsList">
                        </td>
                        <td>
                            <input type="text" 
                                   class="form-control" 
                                   name="mappings[<?php echo $mapping['mapping_id']; ?>][websiteFieldName]" 
                                   value="<?php echo htmlspecialchars($mapping['website_field_name']); ?>" 
                                   placeholder="Website field name...">
                        </td>
                        <td>
                            <select class="form-select" 
                                    name="mappings[<?php echo $mapping['mapping_id']; ?>][fieldFormatType]">
                                <?php foreach ($formatTypes as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($mapping['fieldformattype'] === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <div class="field-control-group">
                                <input type="text" 
                                       class="form-control" 
                                       name="mappings[<?php echo $mapping['mapping_id']; ?>][fieldFormat]" 
                                       value="<?php echo htmlspecialchars($mapping['fieldformat'] ?? ''); ?>" 
                                       placeholder="Format...">
                                <?php if (!empty($mapping['fieldformattype'])): ?>
                                <i class="bi bi-info-circle info-icon"></i>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="remove-btn remove-row" title="Remove">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="table-actions">
                <div>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?cid=<?php echo $company_id; ?>&section=formfieldedit&addtestcase=1" class="btn btn-outline btn-sm">
                        <i class="bi bi-play-fill me-1"></i> TEST
                    </a>
                </div>
                <div>
                    <button type="reset" class="btn btn-outline">Reset</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- User Fields Datalist for autocomplete -->
<datalist id="userFieldsList">
    <?php foreach ($userFieldNames as $field): ?>
    <option value="<?php echo htmlspecialchars($field); ?>">
    <?php endforeach; ?>
</datalist>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle format help collapse chevron
    const formatHelpCollapse = document.getElementById('formatHelpCollapse');
    const formatHelpChevron = document.getElementById('formatHelpChevron');
    
    if (formatHelpCollapse && formatHelpChevron) {
        formatHelpCollapse.addEventListener('show.bs.collapse', function() {
            formatHelpChevron.classList.remove('bi-chevron-down');
            formatHelpChevron.classList.add('bi-chevron-up');
        });
        
        formatHelpCollapse.addEventListener('hide.bs.collapse', function() {
            formatHelpChevron.classList.remove('bi-chevron-up');
            formatHelpChevron.classList.add('bi-chevron-down');
        });
    }
    // Add field functionality
    document.getElementById('addFieldBtn').addEventListener('click', function() {
        const tableBody = document.querySelector('#mappingsTable tbody');
        const newId = 'new_' + Date.now();
        
        const newRow = document.createElement('tr');
        newRow.dataset.mappingId = newId;
        newRow.dataset.status = 'active';
        newRow.innerHTML = `
            <td class="text-center">
                <button type="button" 
                       class="status-toggle-btn" 
                       data-status="active"
                       title="Active">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <input type="hidden" 
                          name="mappings[${newId}][status]" 
                          value="active">
                </button>
            </td>
            <td>
                <input type="text" 
                       class="form-control" 
                       name="mappings[${newId}][userFieldName]" 
                       placeholder="User field name..." 
                       list="userFieldsList">
            </td>
            <td>
                <input type="text" 
                       class="form-control" 
                       name="mappings[${newId}][websiteFieldName]" 
                       placeholder="Website field name...">
            </td>
            <td>
                <select class="form-select" 
                        name="mappings[${newId}][fieldFormatType]">
                    <?php foreach ($formatTypes as $value => $label): ?>
                    <option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <div class="field-control-group">
                    <input type="text" 
                           class="form-control" 
                           name="mappings[${newId}][fieldFormat]" 
                           placeholder="Format...">
                </div>
            </td>
            <td>
                <button type="button" class="remove-btn remove-row" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        
        tableBody.appendChild(newRow);
        
        // Initialize status toggle for the new row
        initStatusToggle(newRow.querySelector('.status-toggle-btn'));
    });
    
    // Remove row functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            const row = e.target.closest('tr');
            row.remove();
        }
    });
    
    // Initialize status toggle buttons
    function initStatusToggle(button) {
        button.addEventListener('click', function() {
            const currentStatus = this.getAttribute('data-status');
            const newStatus = currentStatus === 'active' ? 'notused' : 'active';
            
            // Update button attributes and icon
            this.setAttribute('data-status', newStatus);
            this.setAttribute('title', newStatus === 'active' ? 'Active' : 'Not Used');
            
            const icon = this.querySelector('i');
            if (newStatus === 'active') {
                icon.className = 'bi bi-check-circle-fill text-success';
            } else {
                icon.className = 'bi bi-dash-circle-fill text-secondary';
            }
            
            // Update hidden input value
            const hiddenInput = this.querySelector('input[type="hidden"]');
            hiddenInput.value = newStatus;
            
            // Update row data attribute
            this.closest('tr').dataset.status = newStatus;
        });
    }
    
    // Initialize all status toggle buttons
    document.querySelectorAll('.status-toggle-btn').forEach(button => {
        initStatusToggle(button);
    });
    
    // Make format code examples clickable to copy
    document.querySelectorAll('code').forEach(code => {
        code.style.cursor = 'pointer';
        code.title = 'Click to copy';
        code.addEventListener('click', function() {
            const text = this.textContent;
            navigator.clipboard.writeText(text)
                .then(() => {
                    // Visual feedback
                    const originalBg = this.style.backgroundColor;
                    this.style.backgroundColor = '#d1fae5';
                    this.style.transition = 'background-color 0.2s';
                    
                    setTimeout(() => {
                        this.style.backgroundColor = originalBg;
                    }, 500);
                })
                .catch(err => {
                    console.error('Could not copy text: ', err);
                    alert('Copy to clipboard failed. Format: ' + text);
                });
        });
    });
    
    // Format type change handler with dynamic help
    const formatHelp = {
        'date': 'Examples: Y-m-d, m/d/Y, F j Y, n/j/Y',
        'date-calculate': 'Example: {m}+81098',
        'date-numberformat': 'Formats date as number',
        'lowerdate': 'Lowercase date output',
        'phone': 'Examples: (###) ###-####, ###-###-####, 012, 345, 6789',
        'state': 'Use: code (for 2-letter abbreviation)',
        'title': 'Use: noperiod (removes periods)',
        'name': 'Use: {first_name} {middle_name} {last_name}',
        'gender': 'Options: uppercode, lowercode, upper, ucwords, MF->12',
        'tf->yn': 'Options: uinitial, ucwords, upper, lower, NNo',
        'tf->10': 'Converts true/false to 1/0',
        'tf->fixed': 'Format: truevalue/falsevalue',
        'tf->fixedpipe': 'Format: truevalue|falsevalue',
        'country': 'Options: code, codelong, fullname_lower'
    };
    
    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name*="[fieldFormatType]"]')) {
            const formatType = e.target.value;
            const formatInput = e.target.closest('tr').querySelector('input[name$="[fieldFormat]"]');
            const controlGroup = e.target.closest('tr').querySelector('.field-control-group');
            
            // Remove existing info icon if any
            const existingIcon = controlGroup.querySelector('.info-icon');
            if (existingIcon) {
                existingIcon.remove();
            }
            
            // Add info icon with tooltip for format types
            if (formatType && formatHelp[formatType]) {
                const infoIcon = document.createElement('i');
                infoIcon.className = 'bi bi-info-circle info-icon';
                infoIcon.title = formatHelp[formatType];
                infoIcon.style.cursor = 'help';
                controlGroup.appendChild(infoIcon);
                
                // Set placeholder based on format type
                formatInput.placeholder = formatHelp[formatType];
            } else {
                formatInput.placeholder = 'Format...';
            }
        }
    });
});
</script>