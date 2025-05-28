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
$strengthColors = ['danger', 'warning', 'success'];


$currentHost = $_SERVER['HTTP_HOST'];
// Determine the subdomain tag
if (preg_match('/^(www|dev|dev6|[^.]+)\.birthday\.gold$/', $currentHost, $matches)) {
    $subdomaintag = ($matches[1] === 'www') ? '' : $matches[1];
} else {
    // Fallback for no valid subdomain (e.g., "birthday.gold")
    $subdomaintag = '';
}
// Add "." only if subdomaintag is not empty
$subdomainPrefix = ($subdomaintag !== '') ? $subdomaintag . '.' : '';
// Set the amscriptendpoint based on the actual host domain
$amscriptendpoint = "'https://" . $subdomainPrefix . "birthday.gold" . $dir['ampath'] . "/'";


#-------------------------------------------------------------------------------
# HANDLE THE DATA ELEMENT FORM SUBMIT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
  
if (isset($_POST['formtype']) && ($_POST['formtype'] == 'changedisplaylength')) {
$p_displaylength = $_POST['displaylength'];
}

if (isset($_POST['act']) || isset($_REQUEST['act'])) {
    $action = $_POST['act'] ?? $_REQUEST['act']; // Handle both POST and REQUEST
    $id = $_POST['id'] ?? $_REQUEST['id'] ?? null;

switch ($action) {
    // VIEW/RETRIEVE A DATA ELEMENT ///////////////////////////////////////////////////////////////////////////
    case 'getdata':
        if (isset($id)) {
            $datastore_action = 'retrieve';
            $accessmanager->logAccess($current_user_data['user_id'], $id, 'retrieve');
            include($_SERVER['DOCUMENT_ROOT'] .  $dir['ampath'].'/accessmanager_dataaction.php');
            echo $outputcontent;
            exit;
        }
        break;
    // DECRYPT/SHOW DATA ///////////////////////////////////////////////////////////////////////////
    case 'showx':
        if (isset($id)) {
            $datastore_action = 'show';
            $accessmanager->logAccess($current_user_data['user_id'], $id, 'show');
            include($_SERVER['DOCUMENT_ROOT'] .  $dir['ampath'].'/accessmanager_dataaction.php');
            echo $outputcontent;
            exit;
        }
        break;    
    // CLIPBOARD COPY OR SHOW/HIDE PASSWORD ///////////////////////////////////////////////////////////////////////////
    case 'clipboardcopy':
    case 'showpassword':
    case 'hidepassword':
        if (isset($id)) {
            $accessmanager->logAccess($current_user_data['user_id'], $id, $action);
            exit;
        }
        break; 
    // RE-ENCRYPT ALL DATA ///////////////////////////////////////////////////////////////////////////
    case 'reEncyptAll':
        if (isset($_POST['newpath']) && $_POST['newpath'] != '' && $account->isadmin()) {
            $accessmanager->reEncryptAll($_POST['newpath']);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        break;    
    // CREATE A NEW DATA ELEMENT ///////////////////////////////////////////////////////////////////////////
    case 'createnew':
        $datastore_action = 'create';
        $datastore_datatype = $_POST['type'];
        $accessmanager->logAccess($current_user_data['user_id'], 0, 'create');
        include($_SERVER['DOCUMENT_ROOT'] .  $dir['ampath'].'/accessmanager_dataaction.php');
        break;   
    // ADD THE NEW DATA ELEMENT ///////////////////////////////////////////////////////////////////////////
    case 'addnew':
        include($_SERVER['DOCUMENT_ROOT'] . $dir['ampath'].'/components/db_create_value.inc');
        break;    
    // UPDATE AN EXISTING DATA ELEMENT ///////////////////////////////////////////////////////////////////////////
    case 'editexisting':
        if (isset($id)) {
            $accessmanager->logAccess($current_user_data['user_id'], $id, 'edit');
            include($_SERVER['DOCUMENT_ROOT'] . $dir['ampath'].'/components/db_update_value.inc');
        }
        break;
    // DEFAULT CASE IF NO MATCH ///////////////////////////////////////////////////////////////////////////
    default:
        // Handle any unexpected actions or do nothing
        break;
}
}

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
<h2 class="mb-3">Access Manager v2</h2>
';


////////// RE-ENCRYPT DATA BUTTON
if (($account->isadmin()  && $account->isdeveloper(0))) {
    include($_SERVER['DOCUMENT_ROOT'] . $dir['ampath'].'/components/re-encyrpt_data.inc');
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

echo '
</div>
';


// DISPLAY THE DATA
echo '

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

foreach ($rows as $row) {

    $strengthresult= $accessmanager->getStrength($row['password_strength']);

/// GENERATE THE DATA ACCESS LINK
echo  ' 
<a href="javascript:void(0);" class="list-group-item list-group-item-action d-flex align-items-center" 
data-category="'.htmlspecialchars($row['category']).'" 
data-full-context="'. trim(htmlspecialchars($row['category'].' '.$row['host'].' '.$row['name'].' '.$row['description']).'"  
onclick="populateRightColumn('.addslashes($row['id'])) . ')">
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
';




/// DISPLAY THE DATA ELEMENT FORM
echo '

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



echo str_repeat('</div>', 8) . ' 
';


include($_SERVER['DOCUMENT_ROOT'] . $dir['ampath'].'/components/js-scripts.inc');


$forcefalseenablechat=true;

$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();