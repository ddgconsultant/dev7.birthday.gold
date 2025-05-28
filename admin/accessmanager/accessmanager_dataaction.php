<?PHP

### EXIT IF NOT VALID CALL
if (empty($datastore_action)){return;}
$outputcontent='';
// Initialize an array with default values
$row = [
'name' => '',
'user_id' => ($current_user_data['user_id']??''),
'listofusers_groups' => ([0=>'Birthday.Gold']??[0=>'Birthday.Gold']),
'host' => '',
'encrypted_name' => '', // Assuming this is the username
'encrypted_value' => '', // Assuming this is the password
'notes' => '',
// Add other fields as necessary
];
$modaltitle='Create Record';
$modelaction='addnew';


///////////////////////////////////////////////////////////////////////////
if ($datastore_action=='retrieve_enrollment') {

$sql = 'SELECT d.id,
IFNULL(d.data_type, "") AS `data_type`,
IFNULL(d.kipath, "'.$am_default_kidirpath.'") AS `kipath`,
IFNULL(d.encrypted_name, "") AS encrypted_name_raw,
IFNULL(d.encrypted_value, "") AS `encrypted_value_raw`, 
IFNULL(d.password_strength, "") as password_strength,
IFNULL(d.host_link_type, "") AS host_link_type,
IFNULL(d.host, "") AS `host`,
IFNULL(d.notes, "") AS notes
FROM am_datastore d 
LEFT JOIN am_types t1 ON (d.type = t1.type and t1.category="category")
LEFT JOIN am_types t2 ON (d.data_type = t2.type and t2.category="data_type")
WHERE d.id="'.$amid.'" LIMIT 1';

$stmt = $database->prepare($sql);

// Bind parameters and execute the statement
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);
$row=$result;
$row['encrypted_name']=$accessmanager->decrypt_wki($row['encrypted_name_raw'], $row['kipath']);
$row['encrypted_value']=$accessmanager->decrypt_wki($row['encrypted_value_raw'], $row['kipath']);
$row['notes']=$accessmanager->decrypt_wki($row['notes'], $row['kipath']);


$datastore_datatype=$row['data_type'];
$tmp=explode(' / ', ($row['encrypted_name']));
$outputcontent = '
<style>
.icon-square {
width: 24px; /* Adjust based on your icon size */
height: 24px; /* Adjust based on your icon size */
display: flex;
align-items: center;
justify-content: center;
background-color: #f0f0f0; /* Secondary background color */
border-radius: 4px; /* Optional: for rounded corners */
margin-right: 5px; /* Space between icon and text */
}
</style>
';
$outputcontent.= $datastore_style??'';
$outputcontent.= '
<div class="text-left credential-details">
<div class="d-inline-block icon-square px-1 credential-details-item"><i class="bi bi-person-lock credential-details-icon""></i></div> ' . $tmp[0] . '<br>
<div class="d-inline-block icon-square px-1 credential-details-item"><i class="bi bi-envelope credential-details-icon"></i></div> ' . $tmp[1] . '<br>
<div class="d-inline-block icon-square px-1 credential-details-item"><i class="bi bi-telephone credential-details-icon"></i></div> ' . $tmp[2] . '<br>
<div class="d-inline-block icon-square px-1 credential-details-item"><i class="bi bi-key credential-details-icon"></i></div> ' . htmlspecialchars($row['encrypted_value']) . '
</div>';


return;

}


#breakpoint($datastore_action, 0);

///////////////////////////////////////////////////////////////////////////
if ($datastore_action=='retrieve') {   
   include($_SERVER['DOCUMENT_ROOT'] .  $dir['ampath'].'/components/action-retrieve.inc');
}



///////////////////////////////////////////////////////////////////////////
/// DYNAMIC EDIT DIALOG
$datastore_datatype=$row['data_type']??'username_password';
$outputcontent.= '
<h2 class="p-4">'.$datastore_datatype.'</h2>
<form action="/admin/accessmanager/index.php" method="post">

<input type="hidden" id="act" name="act" value="'.$modelaction.'">
<input type="hidden" id="data_type" name="data_type" value="'.$datastore_datatype.'">

<div class="modal-body">
<!-- Name Field -->
<div class="mb-3">
<label for="edit-name" class="form-label">Name</label>
<input type="text" class="form-control" id="edit-name" name="name" value="'.$row['name'].'">
</div>

';


switch ($datastore_datatype) {
/// ----------------------------------------------------------------------------------------------------------
case 'username_password':
   include($_SERVER['DOCUMENT_ROOT'] .  $dir['ampath'].'/components/type-username_password.inc');
break;
/// ----------------------------------------------------------------------------------------------------------
case 'sshkey':
// Add fields specific to SSH Key
include($_SERVER['DOCUMENT_ROOT'] .  $dir['ampath'].'/components/type-keyvalue.inc');
break;
/// ----------------------------------------------------------------------------------------------------------
case 'file':
   include($_SERVER['DOCUMENT_ROOT'] .  $dir['ampath'].'/components/type-file.inc');
break;
/// ----------------------------------------------------------------------------------------------------------
case 'keyvalue':
   include($_SERVER['DOCUMENT_ROOT'] .  $dir['ampath'].'/components/type-keyvalue.inc');
break;
/// ----------------------------------------------------------------------------------------------------------
case 'special':
// Add fields specific to Special
$outputcontent .= '
<!-- Special Fields -->
<!-- Value Field -->
<div class="mb-3">
<label for="edit-password" class="form-label">Value</label>
<input type="password" class="form-control" id="edit-password" name="password" placeholder="Enter Value" autocomplete="new-password">
</div>
<input type="hidden" id="hiddenFieldId" name="passwordstrength" value="100">
';
break;
/// ----------------------------------------------------------------------------------------------------------
default:
// Optionally handle the default case
$outputcontent .= '
<!-- Default Fields -->
<!-- Add any default form fields here -->
<input type="hidden" id="hiddenFieldId" name="passwordstrength" value="100">
';
break;
}


$outputcontent .= '

';
?>

<script src="/public/js/password.js"></script>
<script src="/public/css/password.css"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {
// Default behavior
$('#edit-password').password();
});
</script>




<?PHP

/*
$outputcontent .= '

<div class="container">  
<strong>Users/Groups</strong>  
<select name="users_groups" id="multiple-checkboxes" multiple="multiple">  
<option value="'.$row['user_id'].'" checked>Myself</option>  
';
foreach ($row['listofusers_groups'] as $companylist_id => $companylist_name) {
$outputcontent .= '<option value="'.$companylist_id.'">'.$companylist_name.'</option>  
';
}
$outputcontent .= ' 
</select>  
</div>  

<script type="text/javascript">  
$(document).ready(function() {  
$("#multiple-checkboxes").multiselect();  
});  
</script>  
';
*/

$outputcontent.= '
<!-- Users/Groups Field -->
<div class="mb-3">
<label for="edit-users-groups" class="form-label">Users/Groups</label>
<input type="text" class="form-control" id="edit-users-groups" name="users_groups" values="">
</div>
';


$outputcontent.= '
<!-- Notes Field -->
<div class="mb-3">
<label for="edit-notes" class="form-label">Notes</label>
<textarea class="form-control" id="edit-notes" name="notes" rows="10">'.htmlspecialchars($row['notes']).'</textarea>
</div>

<!-- Files or Photos Field -->
<div class="mb-3">
<label for="edit-files-photos" class="form-label">Files or Photos</label>
<input type="file" class="form-control" id="edit-files-photos" name="files_photos">
</div>


<!-- Include CSRF Token -->
<input type="hidden" name="_token" value="' . htmlspecialchars($csrf_token) . '">
</div>
<div class="modal-footer">
';

if ($datastore_action=='retrieve') {
$outputcontent.= '
<input type="hidden" name="id" value="'.$row['id'].'">

<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
<button type="submit" class="btn btn-primary">Save changes</button>
';
} else {
$outputcontent.= '
<button type="submit" class="btn btn-success">Submit</button>
';  
}

$outputcontent.= '
</div>
</form>
';



if ($datastore_action=='retrieve') {
$outputcontent.= '
</div>
</div>
</div>
';
}