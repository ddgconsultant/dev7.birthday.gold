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
                $sql = "INSERT INTO bg_form_field_mappings (company_id, user_field_name, website_field_name, fieldformattype, fieldformat, status, `version`, version_dt, version_status) 
                        VALUES (:company_id, :user_field_name, :website_field_name, :fieldformattype, :fieldformat, :status, :version, now(), 'active')";
                $database->query($sql, [
                    'company_id' => $company_id, 
                    'user_field_name' => $userFieldName, 
                    'website_field_name' => $websiteFieldName, 
                    'fieldformattype' => $fieldformattype, 
                    'fieldformat' => $fieldformat, 
                    'status' => $status,
                    'version' => $versionnumber
                ]);
            }
        } else {
            // Else, update the existing mapping
            $sql = "UPDATE bg_form_field_mappings 
                    SET user_field_name = :user_field_name, 
                        website_field_name = :website_field_name, 
                        fieldformattype = :fieldformattype, 
                        fieldformat = :fieldformat, 
                        status = :status 
                    WHERE mapping_id = :mapping_id";
            $database->query($sql, [
                'user_field_name' => $userFieldName, 
                'website_field_name' => $websiteFieldName, 
                'fieldformattype' => $fieldformattype, 
                'fieldformat' => $fieldformat, 
                'status' => $status, 
                'mapping_id' => $mappingID
            ]);
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

$sql = "SELECT * FROM bg_form_field_mappings WHERE company_id = " . $company_id . " " . $criteria . " ORDER BY user_field_name";
$stmt = $database->prepare($sql);
$stmt->execute();
$mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch company details
$sql = "SELECT company_name FROM bg_companies WHERE company_id = :company_id";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$company_name = $company['company_name'] ?? 'Unknown Company';

// Format type definitions
$formatTypes = [
    '' => 'Select format...',
    'date' => 'Date formatting',
    'email' => 'Email formatting',
    'phone' => 'Phone formatting',
    'punchh' => 'Punchh formatting'
];

// Additional styles for modern UI
$additionalstyles .= '
<style>
body {
    background-color: #f9fafb;
}
.main-header {
    margin-bottom: 1.5rem;
}
.version-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    background-color: #dbeafe;
    color: #1e40af;
    border-radius: 9999px;
    margin-left: 0.5rem;
}
.company-name {
    color: #6b7280;
}
.card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    margin-bottom: 1.5rem;
    overflow: hidden;
}
.card-header {
    padding: 1rem 1.5rem;
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
}
.card-body {
    padding: 1.5rem;
}
.btn-group {
    display: flex;
    margin-bottom: 1.5rem;
}
.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-outline {
    background-color: white;
    border-color: #d1d5db;
    color: #374151;
}
.btn-outline:hover {
    background-color: #f9fafb;
}
.btn-primary {
    background-color: #2563eb;
    color: white;
}
.btn-primary:hover {
    background-color: #1d4ed8;
}
.btn-success {
    background-color: #10b981;
    color: white;
}
.btn-success:hover {
    background-color: #059669;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
.template-btn {
    background-color: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}
.template-btn:hover {
    background-color: #e5e7eb;
}
.form-table {
    width: 100%;
    border-collapse: collapse;
}
.form-table th {
    text-align: left;
    padding: 1rem;
    text-transform: uppercase;
    color: #6b7280;
    font-weight: 600;
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}
.form-table td {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}
.form-table tr {
    height: 56px; /* Set a fixed height for rows to reduce spacing */
}
.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
}
.form-control:focus {
    outline: none;
    border-color: #93c5fd;
    box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.25);
}
.form-select {
    width: 100%;
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-image: url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3e%3cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M6 8l4 4 4-4\'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    appearance: none;
}
.form-select:focus {
    outline: none;
    border-color: #93c5fd;
    box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.25);
}
.table-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: #f9fafb;
    border-top: 1px solid #e5e7eb;
}
.remove-btn {
    color: #ef4444;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
}
.remove-btn:hover {
    color: #dc2626;
}
.info-icon {
    color: #6b7280;
    margin-left: 0.5rem;
}
.field-control-group {
    display: flex;
    align-items: center;
}
.status-toggle-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.status-toggle-btn:hover {
    opacity: 0.8;
}
.text-success {
    color: #10b981;
}
.text-secondary {
    color: #9ca3af;
}
.text-center {
    text-align: center;
}
</style>
';
?>

<div class="container">
    <div class="main-header">
        <h1>Form Field Mappings</h1>
        <div>
            <span class="company-name"><?php echo htmlspecialchars($company_name); ?></span>
            <span class="version-badge">Version <?php echo $versionnumber; ?> • Active</span>
        </div>
    </div>
    
    <div class="btn-group">
        <button type="button" class="btn btn-outline" onclick="location.href='<?php echo $_SERVER['PHP_SELF']; ?>?cid=<?php echo $company_id; ?>&section=formfieldedit&view=history'">
            <i class="bi bi-clock-history me-2"></i> Version History
        </button>
        <button type="button" class="btn btn-primary" id="addFieldBtn">
            <i class="bi bi-plus-lg me-2"></i> Add Field
        </button>
    </div>
    
    <div class="card">
        <div class="card-header">Quick Format Templates</div>
        <div class="card-body">
            <div class="templates-container">
                <button type="button" class="template-btn" data-template="birthday||date||Y-m-d">Date of Birth</button>
                <button type="button" class="template-btn" data-template="email||lowercase">Email</button>
                <button type="button" class="template-btn" data-template="phone||(###) ###-####">Phone</button>
                <button type="button" class="template-btn" data-template="name||capitalize">Name</button>
                <button type="button" class="template-btn" data-template="username||lowercase">Username</button>
                <button type="button" class="template-btn" data-template="password||hash">Password</button>
            </div>
        </div>
    </div>
    
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="addtestcase" value="0">
        <input type="hidden" name="version" value="<?php echo $versionnumber; ?>">
        <input type="hidden" name="cid" value="<?php echo $_REQUEST['cid']; ?>">
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
                                   title="<?php echo $mapping['status'] === 'active' ? 'Active' : 'Not Used'; ?>">
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
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?cid=<?php echo $_REQUEST['cid']; ?>&section=formfieldedit&addtestcase=1" class="btn btn-outline btn-sm">
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
                    <option value="">Select format...</option>
                    <option value="date">Date formatting</option>
                    <option value="email">Email formatting</option>
                    <option value="phone">Phone formatting</option>
                    <option value="punchh">Punchh formatting</option>
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
    
    // Template button functionality
    document.querySelectorAll('.template-btn').forEach(button => {
        button.addEventListener('click', function() {
            const template = this.getAttribute('data-template');
            navigator.clipboard.writeText(template)
                .then(() => {
                    // Visual feedback
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    this.style.backgroundColor = '#d1fae5';
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.style.backgroundColor = '';
                    }, 1500);
                })
                .catch(err => {
                    console.error('Could not copy text: ', err);
                    alert('Copy to clipboard failed. Template: ' + template);
                });
        });
    });
    
    // Format type change handler
    document.querySelectorAll('.form-select').forEach(select => {
        select.addEventListener('change', function() {
            const formatType = this.value;
            const formatInput = this.closest('tr').querySelector('input[name$="[fieldFormat]"]');
            const controlGroup = this.closest('tr').querySelector('.field-control-group');
            
            // Remove existing info icon if any
            const existingIcon = controlGroup.querySelector('.info-icon');
            if (existingIcon) {
                existingIcon.remove();
            }
            
            // Add info icon for format types
            if (formatType) {
                const infoIcon = document.createElement('i');
                infoIcon.className = 'bi bi-info-circle info-icon';
                controlGroup.appendChild(infoIcon);
            }
        });
    });
});
</script>