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

// Add required styles
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
    font-size: .75rem;
    line-height: .9;
}
.light-grey-bg {
    background-color: #f2f2f2;
}

.small-font {
    font-size: 9px;
}
</style>';
?>

<div class="container">
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
                <div class="form-group row no-gutters small-row">
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
            <input type="button" class="btn btn-primary btn-sm" onclick="addMapping()" value="Add Mapping">
            <input type="submit" class="btn btn-success btn-sm float-end" value="Save">
        </div>
    </form>
</div>

<script>
function addMapping() {
    var mappingsDiv = document.getElementById('mappings');
    var newRow = document.createElement('div');
    newRow.className = 'form-group row no-gutters small-row';
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