<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# HANDLE THE INSERT ATTEMPT
#-------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' &&  (isset($_GET['action']) && $_GET['action'] === 'addnew')) {

$company_id = $_GET['cid'];
$insert_query = "INSERT INTO bg_company_rewards (company_id, `category`, mindaysstart, `reward_name`,  `status`, create_dt) 
VALUES (?, 'new', 0, 'new', 'new', now())";
$stmt = $database->prepare($insert_query);

$stmt->execute([$company_id]);

$reward_id = $database->lastInsertId();
#echo "<b>Added reward line for company ID: $company_id </b>";
header('location: manual_rewards#go' . $reward_id);
exit;
}

#-------------------------------------------------------------------------------
# HANDLE THE DUPLICATE ATTEMPT
#-------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' &&  (isset($_GET['action']) && $_GET['action'] === 'duplicate')) {

    $rewardid = $_GET['rid'];
    $insert_query = "INSERT INTO bg_company_rewards (company_id, location_id, category, reward_type, reward_name, 
    reward_description_long, reward_description_short, reward_description_spinner, 
    reward_value, cash_value, redeem_instructions, requirements, 
    minage, maxage, mindaysstart, create_dt, modify_dt, `status`)
SELECT company_id, location_id, category, reward_type, reward_name, 
reward_description_long, reward_description_short, reward_description_spinner, 
reward_value, cash_value, redeem_instructions, requirements, 
minage, maxage, mindaysstart, create_dt, modify_dt, `status`
FROM bg_company_rewards
WHERE reward_id = ?;
";
    $stmt = $database->prepare($insert_query);
    
    $stmt->execute([$rewardid]);
    
    $reward_id = $database->lastInsertId();
    #echo "<b>Added reward line for company ID: $company_id </b>";
    header('location: manual_rewards#go' . $reward_id);
    exit;
    }

#-------------------------------------------------------------------------------
# HANDLE THE UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$company_id = $_POST['company_id'];
$reward_id = $_POST['reward_id'];

// Initialize empty arrays to hold your SET clauses and WHERE clauses and parameters
$setClauses = [];
$params = [];


// Add modify_dt = now() to SET clauses
$setClauses[] = "`status` = 'active'";
// Add modify_dt = now() to SET clauses
$setClauses[] = "`modify_dt` = now()";

// Loop through each POST variable
foreach ($_POST as $key => $value) {
// Skip the company_id as it will be used in the WHERE clause
if (strpos(' company_id reward_id format custom_policy custom_url ', $key) === false) {

if (empty($value) && strpos(' maxage minage mindaystart expiredays cash_value reward_value', $key) !== false) continue;
// Add each key = ? to the SET clause
$setClauses[] = "`$key` = ?";
// Add each value to the parameters array
if (!empty($_POST['format']) && $key=='reward_name') $value=ucwords($value);
$params[] = $value;
}
}

// Join the SET clauses into a single string separated by commas
$setClauseStr = implode(', ', $setClauses);

// Add company_id to the WHERE clause and to the parameters array
$whereClause = "WHERE `company_id` = ? and `reward_id` = ?";
$params[] = $company_id;
$params[] = $reward_id;
// Construct the full UPDATE query
$query = "UPDATE `bg_company_rewards` SET $setClauseStr $whereClause";

// Prepare the query
$stmt = $database->prepare($query);

// Execute the query with the parameters
$stmt->execute($params);

header('location: manual_rewards#go' . $reward_id);
exit;
}

if (empty($company_id) ) {

$company_id= $qik->decodeId($_REQUEST['cid']);
if (empty($company_id)) {
  header('location: /admin/brands');
}

}

#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
echo '
<!DOCTYPE html>
<html>

<head>
<title>Rewards Editor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript">
function openWindow(url) {
window.open(url, "myNamedWindow");
}
</script>
</head>

<body>
';

$textAreaArray = array(
'reward_description_long'=>'charCount_rdl,1000',     
'reward_description_short' => 'charCount_rds,1000',                     
'reward_description_spinner'=>'charCount,50',   
'redeem_instructions' => 'charCount_ri,1000',       
'requirements' => 'charCount_r,1000',
);

// Fetch companies from bg_companies
#$query = "SELECT company_id, company_name, company_url, info_url, signup_url FROM bg_companies WHERE `status`='finalized'";


$criteria=' where c.`status`="finalized" and c.company_id ='.$company_id.' ';
$query =  'SELECT c.* , MAX(a.description) AS company_logo
FROM bg_companies AS c
LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id AND a.category = "company_logos"  and a.`grouping` ="primary_logo" 
'.$criteria.' 
GROUP BY c.company_id
order by company_name';


$stmt = $database->prepare($query);
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT type, name FROM bg_ref_reward_categories ";
$stmt = $database->prepare($query);
$stmt->execute();
$categories  = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<h1>Reward Editor</h1>
<!-- # of companies: ' . count($companies) . ' -->
<div class="container">
<div class="accordion" id="companyAccordion">';

$columns=array('reward_description_long', 'reward_description_short','reward_description_spinner','redeem_instructions','requirements');

foreach ($companies as $company) {
$company_id = $company['company_id'];
$rewardx['collectedtag'] = '';
// Check if bg_company_attributes are found for the company_id (Add your logic here)
// If not found, set headerColor to 'bg-danger'

$query = "SELECT * FROM bg_company_rewards WHERE company_id= ? ";
$stmt = $database->prepare($query);
$stmt->execute([$company_id]);
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
$rowcnt = count($rewards);

if ($rewards) {
foreach ($rewards as $reward) {
#   echo '<h1>rewardid: '.$reward['reward_id'].'</h1>';

$urlx['count'] = 0;
$urlx['birthday'] = $urlx['enrollment'] = '';
$linkColor = 'text-black';
$headerColor = 'bg-danger'; // Initialize header color

## INTIALIZE THE COLUMNS
foreach ($columns as $column) {
if (empty($reward[$column])) $reward[$column]='';
}
if (!empty($reward['reward_description_short'])) {
if ($reward['reward_name'] != '') {
$urlx['birthday'] = $reward['reward_description_short'];
$urlx['count']++;
}
if ($reward['reward_value'] != '') {
$urlx['enrollment'] = $reward['reward_description_short'];
$urlx['count']++;
}
if ($reward['requirements'] != '') {
$urlx['enrollment'] = $reward['reward_description_short'];
$urlx['count']++;
}
if ($reward['redeem_instructions'] != '') {
$urlx['enrollment'] = $reward['reward_description_short'];
$urlx['count']++;
}

$rewardx['collectedtag'] = ' - <small> collected: ' . $reward['create_dt'] . '</small>';
}


if ($urlx['count'] >= 2) {
$headerColor = 'bg-warning';
}

if ($urlx['count'] >= 3) {
$headerColor = 'bg-success';
$linkColor = 'text-white';
}


echo '<div class="accordion-item" id="go' . $reward['reward_id'] . '">';
$logotag='&nbsp;<img class="img-fluid" src="'.$display->companyimage($company['company_id'].'/'.$company['company_logo']).'" class="mx-3 px-3" width=32 alt="">&nbsp;';
echo '
<h2 class="accordion-header  fw-bold " id="heading' . $reward['reward_id'] . '">
<button class="accordion-button ' . $headerColor . '  bg-opacity-50 ' . $linkColor . ' fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $reward['reward_id'] . '" aria-expanded="true" aria-controls="collapse' . $reward['reward_id']. '">
[' . $urlx['count'] . '/' . $rowcnt . '] | '.$logotag.' Company ID: ' . $company_id . ' - ' . $company['company_name'] . ' ' . $rewardx['collectedtag'] . '
</button>
</h2>
<div id="collapse' . $reward['reward_id'] . '" class="accordion-collapse collapse" aria-labelledby="heading' . $reward['reward_id'] . '" data-bs-parent="#companyAccordion">
<div class="accordion-body">';
echo '<h3>REWARD ID: '.$reward['reward_id'].'</h3>';
$urls = ['COMANY'=>$company['company_url'], 'INFO'=>$company['info_url'], 'SIGNUP'=>$company['signup_url']];
foreach ($urls as $name=>$url) {
if (!$url) continue;
echo '<span class="fw-bold" style="width:80px">'.$name.":</span> <a href='javascript:void(0);' onclick='openWindow(\"$url\");'>$url</a><br>";
}

echo '<div class="container form-control bg-light mt-3">
<form action="/admin_actions/manual_rewards_single" method="post" class="">
<input class="form-control" type="hidden" name="company_id" value="' . $company_id . '">
<input class="form-control" type="hidden" name="reward_id" value="' . $reward['reward_id'] . '">

<div class="row">

<!-- Reward Name -->
<div class="col-md-5 mb-3">
  <label class="form-label" style="color: navy; font-weight: bold;" for="reward_name">Reward Name:</label>
  <div class="d-flex align-items-center">
    <input class="form-control me-2" type="text" name="reward_name" id="reward_name" value="' . $reward["reward_name"] . '">
    <input class="form-check-input" type="checkbox" name="format" id="format" value="1">
    <label class="form-check-label ms-1" for="format">Format</label>
  </div>
</div>


';

echo '
<!-- Category -->
<div class="col-md-4 mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="category">Category:</label>
<select class="form-control" name="category" id="category">';


foreach ($categories as $category) {
$type = $category['type'];
$name = $category['name'];

$selected = ($reward["category"] === $type) ? 'selected' : '';
echo "<option value='$type' $selected>$name</option>";
}

echo '
</select>
</div>
';


echo '
<!-- Reward Type -->
<div class="col-md-3 mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="reward_type">Reward Type:</label>
<select class="form-control" name="reward_type" id="reward_type">';

$rewardTypes = [
'physical' => 'Physical',
'cash' => 'Cash',
'points' => 'Points',
];

foreach ($rewardTypes as $value => $label) {
$selected = ($reward["reward_type"] === $value) ? 'selected' : '';
echo "<option value='$value' $selected>$label</option>";
}

echo '
</select>
</div>
';


echo '
</div>

<!-- Reward Description Long -->
<div class="mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="reward_description_long">Reward Description Long:</label>
<textarea class="form-control" name="reward_description_long" id="'.$reward["reward_id"].'reward_description_long">' . $reward["reward_description_long"] . '</textarea>
<small id="'.$reward["reward_id"].'charCount_rdl" class="form-text text-muted">'.strlen($reward["reward_description_long"]).'/1000</small>
</div>

<!-- Reward Description Short -->
<div class="mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="reward_description_short">Reward Description Short:</label>
<textarea class="form-control" name="reward_description_short" id="'.$reward["reward_id"].'reward_description_short">' . $reward["reward_description_short"] . '</textarea>
<small id="'.$reward["reward_id"].'charCount_rds" class="form-text text-muted">'.strlen($reward["reward_description_short"]).'/1000</small>
</div>

<!-- Reward Description Spinner -->
<div class="mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="reward_description_spinner">Reward Description Spinner:</label>
<textarea class="form-control" name="reward_description_spinner" id="'.$reward["reward_id"].'reward_description_spinner">' . $reward["reward_description_spinner"] . '</textarea>
<small id="'.$reward["reward_id"].'charCount" class="form-text text-muted">'.strlen($reward["reward_description_spinner"]).'/50</small>
</div>


<!-- Redeem Instructions -->
<div class="mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="redeem_instructions">Redeem Instructions:</label>
<textarea class="form-control" name="redeem_instructions" id="'.$reward["reward_id"].'redeem_instructions">' . $reward["redeem_instructions"] . '</textarea>
<small id="'.$reward["reward_id"].'charCount_ri" class="form-text text-muted">'.strlen($reward["redeem_instructions"]).'/1000</small>
</div>

<!-- Requirements -->
<div class="mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="requirements">Requirements:</label>
<textarea class="form-control" name="requirements" id="'.$reward["reward_id"].'requirements">' . $reward["requirements"] . '</textarea>
<small id="'.$reward["reward_id"].'charCount_r" class="form-text text-muted">'.strlen($reward["requirements"]).'/1000</small>
</div>



<div class="row">
<!-- Reward Value -->
<div class="col-md-2 mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="reward_value">Reward Value:</label>
<input class="form-control" type="number" name="reward_value" id="reward_value" step="0.01" value="' . $reward["reward_value"] . '">
</div>
<!-- Cash Value -->
<div class="col-md-2 mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="cash_value">Cash Value:</label>
<input class="form-control" type="number" name="cash_value" id="cash_value" step="0.01" value="' . $reward["cash_value"] . '">
</div>
<!-- Minimum Age -->
<div class="col-md-2 mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="minage">Minimum Age:</label>
<input class="form-control" type="number" name="minage" id="minage" value="' . $reward["minage"] . '">
</div>

<!-- Maximum Age -->
<div class="col-md-2 mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="maxage">Maximum Age:</label>
<input class="form-control" type="number" name="maxage" id="maxage" value="' . $reward["maxage"] . '">
</div>

<!-- Minimum Days to Start -->
<div class="col-md-2 mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="mindaysstart">Minimum Days to Start:</label>
<input class="form-control" type="number" name="mindaysstart" id="mindaysstart" value="' . $reward["mindaysstart"] . '">
</div>

<!-- Expires Days after issue -->
<div class="col-md-2 mb-3">
<label class="form-label" style="color: navy; font-weight: bold;" for="expiredays">Expires in Days:</label>
<input class="form-control" type="number" name="expiredays" id="expiredays" value="' . $reward["expiredays"] . '">
</div>



</div>


';

echo '<div class="row mb-3 align-items-center">
<div class="">

<button type="submit" class="btn btn-success me-5 px-5"><h3>Submit</h3></button>

<a href="manual_rewards?action=duplicate&rid=' . $reward["reward_id"] . '" class="btn btn-sm btn-secondary me-5 ">Duplicate Record</a>
<a href="manual_rewards?action=addnew&cid=' . $company_id . '" class="btn btn-sm btn-secondary me-5 ">Add New Reward</a>


</form>
</div>

</div>

</div>
</div>
</div>';

echo '
<!-- ----------------------------------------------------------------------- -->
<script>
document.addEventListener("DOMContentLoaded", function() {';
foreach ($textAreaArray as $field=>$code)      {
list($caption, $size)=explode(',',$code);
echo '  
const textarea = document.getElementById("'.$reward["reward_id"].''.$field.'");        
const counter = document.getElementById("'.$reward["reward_id"].''.$caption.'");
const initialLength = textarea.value.length;
counter.innerText = `${initialLength}/'.$size.'`;
if (initialLength > '.$size.') { counter.style.color = "red";  } else {   counter.style.color = "grey";   }

textarea.addEventListener("keyup", function() {
const length = textarea.value.length;    
counter.innerText = `${length}/'.$size.'`;              
if (initialLength > '.$size.') { counter.style.color = "red";  } else {   counter.style.color = "grey";   }          
});     

';
}
echo '  
});
</script>
';  
echo '    </div>'  ;
}
}
}
?>

</div>
</div>


</body>

</html>