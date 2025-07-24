<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Get company id if not already set
if (empty($company_id)) {
    $company_id = $_GET['cid'] ?? null;
}

// Fetch current mappings
$sql = "SELECT max(version) as version 
        FROM bg_form_field_mappings 
        WHERE company_id = :company_id 
        AND version_status = 'active' 
        GROUP BY company_id 
        LIMIT 1";

$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$version = $stmt->fetch(PDO::FETCH_ASSOC);

// Set version number and criteria
if (!empty($version['version'])) {
    $versionnumber = $version['version'];
    $criteria = " AND version = " . $versionnumber;
} else {
    $versionnumber = 1;
    $criteria = "";
}

// Fetch mappings for current version
$sql = "SELECT * 
        FROM bg_form_field_mappings 
        WHERE company_id = :company_id" . $criteria;
        
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add field mapping specific styles
?>

<style>
.field-mapping-row {
    margin-bottom: 0.5rem;
}
.field-mapping-row .form-control {
    font-size: 0.875rem;
}
</style>

<div class="field-mappings-section">
    <form method="POST" action="/admin_actions/save_field_mappings.php">
        <input type="hidden" name="addtestcase" value="0">
        <input type="hidden" name="version" value="<?php echo $versionnumber; ?>">
        <input type="hidden" name="cid" value="<?php echo $company_id; ?>">
        
        <div id="mappings">
            <?php foreach($mappings as $mapping): 
                $showvalue = $mapping['website_field_name'];
                if ($mapping['status'] == 'notused') {
                    $showvalue = '';
                }
                
                if ($mapping['fieldformattype'] != '') {
                    $showvalue .= '||' . $mapping['fieldformattype'] . '||' . $mapping['fieldformat']; 
                }
            ?>
                <div class="row field-mapping-row g-2">
                    <div class="col-5">
                        <input type="text" 
                               class="form-control" 
                               name="mappings[<?php echo $mapping['mapping_id']; ?>][userFieldName]" 
                               value="<?php echo htmlspecialchars($mapping['user_field_name']); ?>" 
                               placeholder="User Field Name">
                    </div> 
                    <div class="col-7">
                        <input type="text" 
                               class="form-control" 
                               name="mappings[<?php echo $mapping['mapping_id']; ?>][websiteFieldName]" 
                               value="<?php echo htmlspecialchars($showvalue); ?>" 
                               placeholder="<?php echo htmlspecialchars($mapping['website_field_name']); ?>">
                    </div>  
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3">
            <button type="button" class="btn btn-primary btn-sm" onclick="addMapping()">
                <i class="bi bi-plus-circle me-2"></i>Add Mapping
            </button>
            <button type="submit" class="btn btn-success btn-sm float-end">
                <i class="bi bi-save me-2"></i>Save Mappings
            </button>
        </div>
    </form>
</div>

<script>
function addMapping() {
    var mappingsDiv = document.getElementById('mappings');
    var newRow = document.createElement('div');
    newRow.className = 'row field-mapping-row g-2';
    newRow.innerHTML = `
        <div class="col-5">
            <input type="text" class="form-control" name="mappings[new][userFieldName]" placeholder="User Field Name">
        </div>
        <div class="col-7">
            <input type="text" class="form-control" name="mappings[new][websiteFieldName]" placeholder="Website Field Name">
        </div>
    `;
    mappingsDiv.appendChild(newRow);
}
</script>