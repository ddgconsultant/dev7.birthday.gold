<?php
if (!isset($componentmode)) $componentmode = 'default';
if ($componentmode != 'include') {
    // Include the site-controller.php file
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php';
}

// Get company id
if (empty($company_id)) {
    $company_id = $_REQUEST['cid'] ?? 0;
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

// Handle creating a new version
if ($componentmode == 'default' && $_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['newversion'])) {
    $versionnumber = $_REQUEST['newversion'];
    $cid = $company_id;
    
    // Update existing mappings to inactive
    $sql = "UPDATE `bg_form_field_mappings` SET 
        `version_status`='inactive', 
        `modify_dt`=now() 
    WHERE company_id = ? 
    AND `version` != ? 
    AND `version_status`='active'";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([$cid, $versionnumber]);
    
    // Insert default mappings for the new version based on common fields
    $sql = "INSERT INTO `bg_form_field_mappings` 
            (`company_id`, `version`, `version_status`, `user_field_name`, 
            `website_field_name`, `fieldformattype`, `fieldformat`, 
            `create_dt`, `modify_dt`, `status`) 
    VALUES (?, ?, 'active', ?, ?, NULL, NULL, now(), now(), 'active')";
    
    $stmt = $database->prepare($sql);
    
    foreach ($userFieldNames as $fieldName) {
        $stmt->execute([$cid, $versionnumber, $fieldName, $fieldName]);
    }
    
    // Redirect back to the page
    header("Location: {$_SERVER['PHP_SELF']}?cid={$_REQUEST['cid']}&section=formfieldedit");
    exit;
}

// Handle form submission
if ($componentmode == 'default' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['addtestcase']) && $_REQUEST['addtestcase'] == '0') {
    // Fetch the post data
    $mappings = $_POST['mappings'];
 
    // Loop over each mapping
    foreach ($mappings as $mappingID => $mappingData) {
        $userFieldName = trim($mappingData['userFieldName']);
        $websiteFieldName = trim($mappingData['websiteFieldName']);
        $fieldformattype = '';
        $fieldformat = '';
        
        // Check if the old format with || is used
        if (strpos($websiteFieldName, '||') !== false) {
            list($websiteFieldName, $fieldformattype, $fieldformat) = explode('||', $websiteFieldName);
        } elseif (isset($mappingData['fieldFormatType'])) {
            $fieldformattype = trim($mappingData['fieldFormatType']);
            $fieldformat = isset($mappingData['fieldFormat']) ? trim($mappingData['fieldFormat']) : '';
        }

        // Check if the website field name is blank and set status accordingly
        $status = ($websiteFieldName == '') ? 'notused' : 'active';
        if (isset($mappingData['status'])) {
            $status = $mappingData['status'];
        }

        if ($userFieldName != '') {
            // If mappingID is not a number, insert new mapping
            if (!is_numeric($mappingID)) {
                $sql = "INSERT INTO bg_form_field_mappings 
                        (company_id, user_field_name, website_field_name, status, 
                        fieldformattype, fieldformat, `version`, version_dt, version_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, now(), 'active')";
                $stmt = $database->prepare($sql);
                $stmt->execute([
                    $company_id, 
                    $userFieldName, 
                    $websiteFieldName, 
                    $status, 
                    $fieldformattype, 
                    $fieldformat, 
                    $versionnumber
                ]);
            } else {
                // Update existing mapping
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
    }

    // Redirect to prevent form resubmission
    header("Location: {$_SERVER['PHP_SELF']}?cid={$_REQUEST['cid']}&section=formfieldedit");
    exit;
}  

// Handle adding test case
if ($componentmode == 'default' && $_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['addtestcase'])) {
    // Fetch data
    $companyID = $company_id;
    $userID = 20; // User ID is static in this case

    // Check if the data already exists
    $checkSql = "SELECT * FROM bg_user_companies WHERE user_id = ? AND company_id = ?";
    $stmt = $database->prepare($checkSql);
    $stmt->execute([$userID, $companyID]);
    $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If the data doesn't exist, insert it into the database
    if (count($existingData) == 0) {
        // Insert data into database
        $sql = "INSERT INTO bg_user_companies (user_id, company_id, create_dt, status) VALUES (?, ?, NOW(), 'testing')";
        $stmt = $database->prepare($sql);
        $stmt->execute([$userID, $companyID]);
    }
    
    // Redirect back
    header("Location: {$_SERVER['PHP_SELF']}?cid={$_REQUEST['cid']}&section=formfieldedit");
    exit;
}

// Handle building out default mappings
if ($componentmode == 'default' && $_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST['buildout'])) {
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
    
    // Redirect back
    header("Location: {$_SERVER['PHP_SELF']}?cid={$_REQUEST['cid']}&section=formfieldedit");
    exit;
}

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

// Get company name
$sql = "SELECT company_name FROM bg_companies WHERE company_id = ?";
$stmt = $database->prepare($sql);
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$companyname = $company['company_name'] ?? 'Unknown Company';

// Add styles to enhance UI
$additionalstyles .= '
<style>
    .mapping-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    .version-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background-color: #e9ecef;
        border-radius: 0.375rem;
        margin-left: 0.5rem;
        font-size: 0.875rem;
    }
    .mapping-container {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }
    .mapping-header {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
    }
    .mapping-body {
        padding: 1rem;
    }
    .mapping-footer {
        padding: 1rem;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
    }
    .template-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .template-tag {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        cursor: pointer;
    }
    .template-tag:hover {
        background-color: #e9ecef;
    }
    .form-table {
        width: 100%;
        border-collapse: collapse;
    }
    .form-table th {
        background-color: #f8f9fa;
        padding: 0.75rem;
        text-align: left;
        font-weight: 600;
        border-bottom: 1px solid #dee2e6;
    }
    .form-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #dee2e6;
    }
    .status-indicator {
        display: inline-block;
        width: 0.75rem;
        height: 0.75rem;
        border-radius: 50%;
    }
    .status-active {
        background-color: #28a745;
    }
    .status-notused {
        background-color: #dc3545;
    }
    .action-btn {
        background: none;
        border: none;
        padding: 0.25rem;
        cursor: pointer;
    }
    .action-btn svg {
        width: 1rem;
        height: 1rem;
    }
    .action-btn:hover {
        opacity: 0.7;
    }
    .btn-group {
        display: flex;
        gap: 0.5rem;
    }
</style>';

// Output the form
echo '
<div class="mapping-header">
    <div>
        <h2>Form Field Mappings</h2>
        <span>' . htmlspecialchars($companyname) . ' <span class="version-badge">Version ' . $versionnumber . '</span></span>
    </div>
    <div class="btn-group">
        <a href="' . $_SERVER['PHP_SELF'] . '?cid=' . $company_id . '&newversion=' . ($versionnumber + 1) . '&section=formfieldedit" class="btn btn-secondary btn-sm">New Version</a>
        <a href="' . $_SERVER['PHP_SELF'] . '?buildout=' . $company_id . '&cid=' . $company_id . '&section=formfieldedit" class="btn btn-secondary btn-sm">BuildOut</a>
    </div>
</div>

<div class="mapping-container">
    <div class="mapping-header">
        <h5>Format Templates</h5>
    </div>
    <div class="mapping-body">
        <div class="template-container">
            <span class="template-tag copy-content">username||lowercase</span>
            <span class="template-tag copy-content">email||lowercase</span>
            <span class="template-tag copy-content">password||hash</span>
            <span class="template-tag copy-content">birthdate||date||Y-m-d</span>
            <span class="template-tag copy-content">phone||(###) ###-####</span>
            <span class="template-tag copy-content">formname||punchh||</span>
        </div>

        <h6>Date Format Codes:</h6>
        <div class="template-container">
            <span class="template-tag copy-contentdate">d - Day with leading zeros (01-31)</span>
            <span class="template-tag copy-contentdate">j - Day without leading zeros (1-31)</span>
            <span class="template-tag copy-contentdate">m - Month with leading zeros (01-12)</span>
            <span class="template-tag copy-contentdate">n - Month without leading zeros (1-12)</span>
            <span class="template-tag copy-contentdate">Y - Full year (e.g., 2023)</span>
            <span class="template-tag copy-contentdate">y - Two-digit year (e.g., 23)</span>
        </div>
    </div>
</div>

<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="addtestcase" value="0">
<input type="hidden" name="version" value="' . $versionnumber . '">
<input type="hidden" name="cid" value="' . $company_id . '">
<input type="hidden" name="section" value="formfieldedit">

<div class="mapping-container">
    <div class="mapping-body">
        <table class="form-table" id="mappings-table">
            <thead>
                <tr>
                    <th width="5%">Status</th>
                    <th width="30%">User Field</th>
                    <th width="30%">Website Field</th>
                    <th width="15%">Format Type</th>
                    <th width="15%">Format</th>
                    <th width="5%">Actions</th>
                </tr>
            </thead>
            <tbody>';

foreach ($mappings as $mapping) {
    $showvalue = $mapping['website_field_name'];
    if ($mapping['status'] == 'notused') $showvalue = '';
    
    if ($mapping['fieldformattype'] != '') {
        $showformat = $mapping['fieldformattype'];
        $showformatvalue = $mapping['fieldformat'];
    } else {
        $showformat = '';
        $showformatvalue = '';
    }
    
    echo '
                <tr>
                    <td>
                        <span class="status-indicator ' . ($mapping['status'] == 'active' ? 'status-active' : 'status-notused') . '" title="' . ($mapping['status'] == 'active' ? 'Active' : 'Not Used') . '"></span>
                        <input type="hidden" name="mappings[' . $mapping['mapping_id'] . '][status]" value="' . $mapping['status'] . '" class="status-input">
                    </td>
                    <td>
                        <input type="text" class="form-control" name="mappings[' . $mapping['mapping_id'] . '][userFieldName]" value="' . htmlspecialchars($mapping['user_field_name']) . '" placeholder="User Field Name" list="userFieldsList">
                    </td>
                    <td>
                        <input type="text" class="form-control" name="mappings[' . $mapping['mapping_id'] . '][websiteFieldName]" value="' . htmlspecialchars($showvalue) . '" placeholder="Website Field Name">
                    </td>
                    <td>
                        <select class="form-control format-type-select" name="mappings[' . $mapping['mapping_id'] . '][fieldFormatType]">
                            <option value="">Select format...</option>
                            <option value="date" ' . ($showformat == 'date' ? 'selected' : '') . '>Date formatting</option>
                            <option value="email" ' . ($showformat == 'email' ? 'selected' : '') . '>Email formatting</option>
                            <option value="phone" ' . ($showformat == 'phone' ? 'selected' : '') . '>Phone formatting</option>
                            <option value="punchh" ' . ($showformat == 'punchh' ? 'selected' : '') . '>Punchh formatting</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="mappings[' . $mapping['mapping_id'] . '][fieldFormat]" value="' . htmlspecialchars($showformatvalue) . '" placeholder="Format value">
                    </td>
                    <td>
                        <button type="button" class="action-btn toggle-status" title="Toggle active status">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                            </svg>
                        </button>
                    </td>
                </tr>';
}

echo '
            </tbody>
        </table>
    </div>

    <div class="mapping-footer">
        <div>
            <button type="button" class="btn btn-primary" id="add-mapping-btn">Add Mapping</button>
            <a href="' . $_SERVER['PHP_SELF'] . '?cid=' . $company_id . '&section=formfieldedit&addtestcase=1" class="btn btn-secondary">Test</a>
        </div>
        <div>
            <button type="submit" class="btn btn-success">Save Mappings</button>
        </div>
    </div>
</div>
</form>

<datalist id="userFieldsList">';
foreach ($userFieldNames as $fieldName) {
    echo '<option value="' . htmlspecialchars($fieldName) . '">';
}
echo '</datalist>';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle copy to clipboard for template tags
    document.querySelectorAll('.copy-content').forEach(function(element) {
        element.addEventListener('click', function() {
            var text = this.textContent;
            copyToClipboard(text);
            showCopyFeedback(this);
        });
    });

    // Handle copy to clipboard for date format codes
    document.querySelectorAll('.copy-contentdate').forEach(function(element) {
        element.addEventListener('click', function() {
            var text = this.textContent.split(' - ')[0];
            copyToClipboard('||date||' + text);
            showCopyFeedback(this);
        });
    });

    // Toggle status indicators
    document.querySelectorAll('.toggle-status').forEach(function(element) {
        element.addEventListener('click', function() {
            var row = this.closest('tr');
            var statusIndicator = row.querySelector('.status-indicator');
            var statusInput = row.querySelector('.status-input');
            
            if (statusIndicator.classList.contains('status-active')) {
                statusIndicator.classList.remove('status-active');
                statusIndicator.classList.add('status-notused');
                statusIndicator.title = 'Not Used';
                statusInput.value = 'notused';
            } else {
                statusIndicator.classList.remove('status-notused');
                statusIndicator.classList.add('status-active');
                statusIndicator.title = 'Active';
                statusInput.value = 'active';
            }
        });
    });

    // Add new mapping row
    document.getElementById('add-mapping-btn').addEventListener('click', function() {
        var table = document.getElementById('mappings-table').querySelector('tbody');
        var newRow = document.createElement('tr');
        var newId = 'new_' + Date.now();
        
        newRow.innerHTML = `
            <td>
                <span class="status-indicator status-active" title="Active"></span>
                <input type="hidden" name="mappings[${newId}][status]" value="active" class="status-input">
            </td>
            <td>
                <input type="text" class="form-control" name="mappings[${newId}][userFieldName]" placeholder="User Field Name" list="userFieldsList">
            </td>
            <td>
                <input type="text" class="form-control" name="mappings[${newId}][websiteFieldName]" placeholder="Website Field Name">
            </td>
            <td>
                <select class="form-control format-type-select" name="mappings[${newId}][fieldFormatType]">
                    <option value="">Select format...</option>
                    <option value="date">Date formatting</option>
                    <option value="email">Email formatting</option>
                    <option value="phone">Phone formatting</option>
                    <option value="punchh">Punchh formatting</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control" name="mappings[${newId}][fieldFormat]" placeholder="Format value">
            </td>
            <td>
                <button type="button" class="action-btn toggle-status" title="Toggle active status">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                    </svg>
                </button>
            </td>
        `;
        
        table.appendChild(newRow);
        
        // Add event listener to the new toggle status button
        newRow.querySelector('.toggle-status').addEventListener('click', function() {
            var statusIndicator = this.closest('tr').querySelector('.status-indicator');
            var statusInput = this.closest('tr').querySelector('.status-input');
            
            if (statusIndicator.classList.contains('status-active')) {
                statusIndicator.classList.remove('status-active');
                statusIndicator.classList.add('status-notused');
                statusIndicator.title = 'Not Used';
                statusInput.value = 'notused';
            } else {
                statusIndicator.classList.remove('status-notused');
                statusIndicator.classList.add('status-active');
                statusIndicator.title = 'Active';
                statusInput.value = 'active';
            }
        });
    });

    // Helper function to copy text to clipboard
    function copyToClipboard(text) {
        var tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
    }

    // Helper function to show copy feedback
    function showCopyFeedback(element) {
        var originalText = element.textContent;
        var originalBg = element.style.backgroundColor;
        
        element.textContent = 'Copied!';
        element.style.backgroundColor = '#d1e7dd';
        
        setTimeout(function() {
            element.textContent = originalText;
            element.style.backgroundColor = originalBg;
        }, 1000);
    }
});
</script>