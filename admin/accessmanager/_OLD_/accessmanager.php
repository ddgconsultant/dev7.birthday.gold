<?PHP
$addClasses[] = 'AccessManager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$skip=false;
if (!$account->isadmin()) {$skip=true;}

$outputcontent='';
$csrf_token=$display->inputcsrf_token('tokenonly');



#-------------------------------------------------------------------------------
# HANDLE THE DATA ELEMENT FORM SUBMIT
#-------------------------------------------------------------------------------
if ($app->formposted()) {

if (isset($_POST['formtype']) && ($_POST['formtype'] == 'changedisplaylength')) {
$p_displaylength = $_POST['displaylength'];
}



///////////////////////////////////////////////////////////////////////////
// VIEW/retrieve A DATA ELEMENT
if ((isset($_POST['act']) && ($_POST['act'] == 'getdata')) && (isset($_POST['id']) )  ) {
$datastore_action='retrieve';
$accessmanager->logAccess($current_user_data['user_id'], $_POST['id'], 'retrieve');
include($_SERVER['DOCUMENT_ROOT'] . '/admin/accessmanager_dataaction.php');
echo $outputcontent;
exit;
}



///////////////////////////////////////////////////////////////////////////
// DECYRPT/SHOW DATA
if ((isset($_POST['act']) && ($_POST['act'] == 'showx')) && (isset($_POST['id']) )  ) {
    $datastore_action='show';
    $accessmanager->logAccess($current_user_data['user_id'], $_POST['id'], 'show');
    include($_SERVER['DOCUMENT_ROOT'] . '/admin/accessmanager_dataaction.php');
    echo $outputcontent;
    exit;
}



///////////////////////////////////////////////////////////////////////////
// clipboardcopy or show password
if ((isset($_POST['act']) && ($_POST['act'] == 'clipboardcopy'  ||  $_POST['act'] == 'showpassword' ||  $_POST['act'] == 'hidepassword' )) && (isset($_POST['id']) )  ) {
    $accessmanager->logAccess($current_user_data['user_id'], $_POST['id'], $_POST['act']);
    exit;
}



///////////////////////////////////////////////////////////////////////////
//  reEncyptAll
if ((isset($_POST['act']) && ($_POST['act'] == 'reEncyptAll' && (isset($_POST['newpath']) && $_POST['newpath']!='') && $account->isadmin()   )) ) {
    $accessmanager->reEncryptAll($_POST['newpath']);
    header('location: '.$_SERVER['PHP_SELF']);
    exit;
}



///////////////////////////////////////////////////////////////////////////
// CREATE A NEW DATA ELEMENT
if ((isset($_POST['act']) && ($_POST['act'] == 'createnew'))   ) {  /// create new record button action
$datastore_action='create';
$datastore_datatype=$_POST['type'];
$accessmanager->logAccess($current_user_data['user_id'], 0, 'create');
include($_SERVER['DOCUMENT_ROOT'] . '/admin/accessmanager_dataaction.php');
#exit;
}



///////////////////////////////////////////////////////////////////////////
// ADD THE NEW DATA ELEMENT - form was submitted with the "addnew" action
if (isset($_POST['act']) && $_POST['act'] == 'addnew') {
    // Extract and sanitize form values
 #   $name = $_POST['name'] ?? '';
  ##  $host = $_POST['host'] ?? '';
  #  $encryptedValue = $_POST['password'] ?? '';
  #  $dataType = $_POST['data_type'] ?? '';
  #  $strength=$accessmanager->checkPassword($encryptedValue);
    // Add other fields as necessary
  #  $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
 
#$kipath=$accessmanager->generateKipath();


$input = [
    'user_id' => $current_user_data['user_id'],
    'company_id' => 0,
    'type' => $_POST['type'] ?? $_POST['data_type'] ?? '',
    'data_type' => $_POST['type'] ?? $_POST['data_type'] ?? '',
    'name' => htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'),
    'host' => $_POST['host'] ?? '',
    'username' => $_POST['username'] ?? '',
    'password' => $_POST['password'] ?? '',
    'notes' => $_POST['notes'] ?? '',
    'category' => $_REQUEST['category'] ?? '',
    'grouping' => $_REQUEST['grouping'] ?? '',
    'datatype' => $_REQUEST['datatype'] ?? 'username_password',
  'creator_id' => $_REQUEST['admin_id'] ?? 0,
];

$result =$accessmanager->create_record($input);

        if ($result) {
            // Get the last inserted ID
           # $last_inserted_id = $database->lastInsertId();  
           $last_inserted_id=   $result;
        $accessmanager->logAccess($current_user_data['user_id'], $last_inserted_id, 'addnew');
        $outputcontent = '<h1 class="text-success">Record added successfully.</h1>';
    
    } else {
        $outputcontent = '<h1 class="text-danger">Error adding record.</h1>';
    }
}



///////////////////////////////////////////////////////////////////////////
// UPDATE A DATA ELEMENT
// Check if the form was submitted with the "edit" action
if (isset($_POST['act']) && $_POST['act'] == 'editexisting') {
    $accessmanager->logAccess($current_user_data['user_id'], $_POST['id'], 'edit');
  
    // Extract and sanitize form values
    $id = $_POST['id'] ?? ''; // Default to an empty string if not set
    $name = $_POST['name'] ?? '';
    $host = $_POST['host'] ?? '';
    $encryptedName = $_POST['username'] ?? '';
    $encryptedValue = $_POST['password'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $dataType = $_POST['data_type'] ?? '';
    // Add other fields as necessary
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $strength=$accessmanager->checkPassword($encryptedValue) ;
    $kipath=$accessmanager->generateKipath();

    // Prepare the SQL statement for updating
    $sql = "UPDATE am_datastore SET modify_dt=now(), type=:type, kipath=:kipath, name=:name, host=:host, encrypted_name=:encryptedName, encrypted_value=:encryptedValue, password_strength=:password_strength, notes=:notes, data_type=:dataType WHERE id=:id";

    $stmt = $database->prepare($sql);

    // Bind values to parameters
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':type', $dataType);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':host', $host);
    $stmt->bindValue(':kipath',$kipath);
    $stmt->bindValue(':password_strength',json_encode($strength));
    $stmt->bindValue(':encryptedName', $accessmanager->encrypt_wki($encryptedName, $kipath));
    $stmt->bindValue(':encryptedValue', $accessmanager->encrypt_wki($encryptedValue, $kipath));
    $stmt->bindValue(':notes', $accessmanager->encrypt_wki($notes, $kipath));
    $stmt->bindValue(':dataType', $dataType);
    // Bind other fields as necessary

    // Execute the statement and check for success
    if ($stmt->execute()) {
        $outputcontent = '<h1 class="text-success">Record updated successfully.</h1>';  
    } else {
        $outputcontent = '<h1 class="text-danger">Error updating record.</h1>';
    }
}

///   $outputcontent  will be used later in the page.
}



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

    

<!-- ===============================================-->
<!--    Main Content-->
<!-- ===============================================-->

<?PHP
$additionalstyles.= '
<style type="text/css">
/* better progress bar styles for the bootstrap demo */
.pass-strength-visible input.form-control,
input.form-control:focus {
border-bottom-right-radius: 0;
border-bottom-left-radius: 0;
}

.pass-strength-visible .pass-graybar,
.pass-strength-visible .pass-colorbar,
.form-control:focus + .pass-wrapper .pass-graybar,
.form-control:focus + .pass-wrapper .pass-colorbar {
border-bottom-right-radius: 4px;
border-bottom-left-radius: 4px;
}
</style>
';


/// DISPLAY LIST OF ACCOUNTS
$sql = 'SELECT d.id,
IFNULL(d.type, "") AS `type`,
IFNULL(d.name, "") AS `name`,
IFNULL(d.description, "") AS `description`,
IFNULL(d.category, "") AS category,
IFNULL(d.grouping, "") AS `grouping`,
IFNULL(d.host, "") AS `host`,
d.password_strength,
IFNULL(d.host_link_type, "") AS host_link_type,
IFNULL(d.file_path, "") AS file_path,
IFNULL(t1.icon, "bi bi-box") AS type_icon, 
IFNULL(t2.icon, "bi bi-key") AS datatype_icon, 
d.create_dt, d.modify_dt FROM am_datastore d 
LEFT JOIN am_types t1 ON (d.type = t1.type and t1.category="category")
LEFT JOIN am_types t2 ON (d.data_type = t2.type and t2.category="data_type")
where company_id=0 or (user_id='.$current_user_data['user_id'].')
';

$stmt = $database->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


$listcount=count($rows);



/// DISPLAY HEADER
echo ' 
<div class="container main-content">
<div class="d-flex justify-content-between align-items-center">
<h2 class="mb-3">Access Manager</h2>


';

if (($account->isadmin()  && $account->isdeveloper(0))) {
    echo '<a href="#" class="btn button btn-danger" data-bs-toggle="modal" data-bs-target="#reEncryptModal">ReEncrypt All</a>';
  
    $stmt = $database->prepare("SELECT value FROM am_config WHERE name = 'path'");
    $stmt->execute();
    $path = $stmt->fetchColumn();


    // Modal HTML
    echo '
    <div class="modal fade" id="reEncryptModal" tabindex="-1" aria-labelledby="reEncryptModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="reEncryptModalLabel">Re-Encrypt Data</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form action="' . $_SERVER['PHP_SELF'] . '" id="recryptform" name="recryptform" method="POST">          
'.$display->inputcsrf_token().'
<input type="hidden" name="act" value="reEncyptAll">
            <div class="modal-body">
              <div class="mb-3">
                <label for="newPathInput" class="form-label">New Path</label>
                <input type="text" class="form-control" id="newPathInput" name="newpath" value="'.$path.'" placeholder="XXX_X/ZZZ_Z">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    ';

}

echo '
<form action="' . $_SERVER['PHP_SELF'] . '" id="createnewform" name="createnewform"  method="post">
'.$display->inputcsrf_token().'
<input type="hidden" name="act" value="createnew">
<input type="hidden" name="type" id="typeInput"> 
<div class="dropdown">
<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButtonCreate" data-bs-toggle="dropdown" aria-expanded="false">
Create New
</button>
<ul class="dropdown-menu" aria-labelledby="dropdownMenuButtonCreate">
<li><button class="dropdown-item type-filter-dropdown" type="button" data-type="username_password">New User/Password</button></li>
<li><button class="dropdown-item type-filter-dropdown" type="button" data-type="sshkey">New SSH Key</button></li>
<li><button class="dropdown-item type-filter-dropdown" type="button" data-type="file">New File</button></li>
<li><button class="dropdown-item type-filter-dropdown" type="button" data-type="keyvalue">Key/Value</button></li>
<li><button class="dropdown-item type-filter-dropdown" type="button" data-type="special">New Special</button></li>

</ul>
</div>
</form>
';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.type-filter-dropdown').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default anchor click behavior
            var type = this.getAttribute('data-type'); // Get the data-type attribute value
            document.getElementById('typeInput').value = type; // Set the hidden input value
            document.getElementById('createnewform').submit(); // Submit the specific form
        });
    });
});

</script>

<?PHP
echo '

</div>


<div class="container mx-0 px-0">
<div class="row">
<!-- Left Column (1/3) -->
<div class="col-md-4">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<div class="dropdown">
<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
Categories
</button>
<ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
    <li><button class="dropdown-item category-filter-dropdown" type="button" data-value="all">All</button></li>
    <li><button class="dropdown-item category-filter-dropdown" type="button" data-value="Social Media">Social Media</button></li>
    <li><button class="dropdown-item category-filter-dropdown" type="button" data-value="Mail Server">Mail Server</button></li>
    <li><button class="dropdown-item category-filter-dropdown" type="button" data-value="vendor">Vendor</button></li>
    <li><button class="dropdown-item category-filter-dropdown" type="button" data-value="licenses">Licenses</button></li>
    <li><button class="dropdown-item category-filter-dropdown" type="button" data-value="Personal">Personal</button></li>
</ul>

</div>
<span class="badge bg-secondary badge-pill px-2">'.$listcount.'</span>
</div>

<div class="input-group mb-4">
    <span class="input-group-text">
        <i class="bi bi-search"></i> <!-- Bootstrap search icon -->
    </span>
    <input type="text" id="searchBar" class="form-control" placeholder="Search...">
    <span class="input-group-text clear-icon" style="cursor: pointer; display: none;">
        <i class="bi bi-x-circle-fill"></i> <!-- Clear icon -->
    </span>
    <span class="input-group-text">
        <i class="bi bi-sort-alpha-down"></i> <!-- Bootstrap sort icon -->
    </span>
</div>




<div class="list-group list-group-flush">
';


#    breakpoint($rows);

$strengthColors = ['danger', 'warning', 'success'];

foreach ($rows as $row) {
  #  $strengthValue = $row['password_strength'];
  #  $strengthColor = $strengthValue <= 33 ? 'danger' : ($strengthValue <= 66 ? 'warning' : 'success');
    
    #$strengthresult=$accessmanager->checkPassword($row['encrypted_value']); 

   # $strengthresult= ['word' => 'Strong', 'num' => 1, 'scale' => 70, 'color' => 'success'];
   # $strengthValue=$strengthresult['scale']; 
   # $row['name'].='--'.$strengthValue; 
   # $strengthColor=$strengthresult['color']; 

    $strengthresult= $accessmanager->getStrength($row['password_strength']);



/// GENERATE THE DATA ACCESS LINK
echo  ' 
<a href="#" class="list-group-item list-group-item-action d-flex align-items-center" 
data-category="'.htmlspecialchars($row['category']).'" 
data-full-context="'. trim(htmlspecialchars($row['category'].' '.$row['host'].' '.$row['name'].' '.$row['description']).'"  onclick="populateRightColumn('.addslashes($row['id'])) . ')">
';
echo '
<div class="ps-0 mx-0 pe-2">
<span class="bg-'.$strengthresult['color'].' mx-0 px-0">&nbsp;</span> <!-- Password Strength Indicator -->
</div>
<div class="pe-2">
<i class="'.$row['type_icon'].'"></i> 
</div>
' . ($row['name']) . '
<div class="ms-auto ps-2 text-end"> <!-- Modified this line for flush right -->
<i class="'.$row['datatype_icon'].'"></i> 
</div>
</a>';


}


echo '
</div>




</div>
</div>
<!-- Right Column (2/3) -->
<div class="col-md-8" id="datapanel">
<div class="card">
';
if ($outputcontent=='')  {
echo '
<div class="card-header d-flex justify-content-between align-items-center">

<h4>Select an Item</h4>

</div>

<div class="card-body" id="rightColumnContent">
<!-- Placeholder Content Section -->
<h5 class="card-title"> Please select an item to view its details.</h5>   

</div>
';
} else {
echo '<div class="p-3">'.
$outputcontent.'
</div>';
}



echo '
</div>
</div>


</div>
</div>
</div>
</div>

';

?>

<?PHP
echo '
</div>

</div>   
';

?>


<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js"></script>  
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css">  


<script>
function populateRightColumn(itemId) {
const contentArea = document.getElementById('datapanel');

// Create FormData object and append parameters
const formData = new FormData();
formData.append('id', itemId);

// Assuming you have a way to get the CSRF token in your JavaScript
// For example, you might have it in a meta tag or somewhere accessible
const csrfToken = '<?php echo addslashes($csrf_token); ?>';
formData.append('_token', csrfToken);
formData.append('act', 'getdata');
// AJAX request to fetch HTML content
const xhr = new XMLHttpRequest();
xhr.open('POST', 'accessmanager.php', true); // Changed to POST
xhr.onreadystatechange = function() {
if (xhr.readyState == 4 && xhr.status == 200) {
// Update the content area with the response HTML
contentArea.innerHTML = xhr.responseText;
}
};
xhr.send(formData); // Send FormData object
}


function copyToClipboardAndLog(rowData) {
// Copy the password to the clipboard
const passwordElement = document.getElementById('password');
passwordElement.type = 'text'; // Temporarily change type to text to copy
passwordElement.select();
document.execCommand('copy');
passwordElement.type = 'password'; // Change back to password type

// Log the copy access
recordAccess(rowData.id, 'clipboardcopy');
}

function recordAccess(itemId, action = 'show') {
    const csrfToken = '<?php echo addslashes($csrf_token); ?>'; // Make sure the CSRF token is available in the scope

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/accessmanager.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            console.log(action, 'Access recorded');
        }
    };

    const data = 'id=' + encodeURIComponent(itemId) + '&act=' + encodeURIComponent(action) + '&_token=' + encodeURIComponent(csrfToken);
    xhr.send(data);
}


function togglePasswordVisibility(itemId) {  // Accept itemId as a parameter
    let passwordInput = document.getElementById('password');
    let toggleIcon = document.getElementById('toggleIcon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
        recordAccess(itemId, 'showpassword');  // Use the passed itemId
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
        recordAccess(itemId, 'hidepassword');  // Use the passed itemId
    }
}
</script>


<script>
// Initialize Bootstrap tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
return new bootstrap.Tooltip(tooltipTriggerEl)
})
</script>

<script>
$(document).ready(function(){
 //   console.log('LOG ENTRY: starting script for searchBar');
  //  console.log($('.list-group-item').length);  // Log to confirm list items are selected

    $('#searchBar').on('keyup', function() {
        var value = $(this).val().toLowerCase();
     //   console.log('Search term:', value);  // Debug: log the search term

        $('.list-group-item').each(function() {  // Iterate over each list item
            var itemText = $(this).data('full-context').toLowerCase();
       //     console.log('Item text:', itemText);  // Debug: log the item text being compared

            if (itemText.includes(value)) {
                $(this).css('display', '');  // Remove the inline display style if present
         //       console.log('show:', this);  // Debug: log the item being shown
            } else {
                $(this).attr('style', 'display: none !important;');  // Force hide the element
           //     console.log('hide:', this);  // Debug: log the item being hidden
            }
        });
    });
});

$(document).ready(function(){
    // Category filter
    $('.category-filter-dropdown').click(function(e) {
        e.preventDefault();  // Prevent the default anchor behavior

        var selectedCategoryValue = $(this).data('value');  // Get the category value from the clicked item
        $('.list-group-item').each(function() {  // Iterate over each list item
            var itemCategoryValue = $(this).data('category'); // Assuming list items have data-category attributes with corresponding values
            if (selectedCategoryValue === 'all') {
                $(this).show();  // If 'All' is selected, show all items
            } else {
                // Show or hide the item based on its data-category attribute value
                if (itemCategoryValue === selectedCategoryValue) {
                    $(this).css('display', '');  // Remove the inline display style if present
                   } else {
                    $(this).attr('style', 'display: none !important;');  // Force hide the element
                }
            }
        });
    });

    // Existing searchBar functionality here
    // ...
});


$(document).ready(function(){
    // Detect input on the search bar
    $('#searchBar').on('input', function() {
        // If there's text in the input, show the clear icon, otherwise hide it
        if ($(this).val().length > 0) {
            $('.clear-icon').show();
        } else {
            $('.clear-icon').hide();
        }
    });

    // Clear the search bar when the clear icon is clicked
    $('.clear-icon').click(function() {
        $('#searchBar').val('').focus();  // Clear the input and focus it
        $(this).hide();  // Hide the clear icon
        $('#searchBar').trigger('keyup');  // Trigger the keyup event to update the list based on the cleared input
    });
});

</script>


<?PHP
$forcefalseenablechat=true;

$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();