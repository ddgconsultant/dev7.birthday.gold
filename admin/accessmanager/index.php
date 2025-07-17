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

/* Access Manager Modern Design */
/* Remove extra spacing that causes white space after navbar */
body {
    padding: 0 !important;
    margin: 0 !important;
}

.navbar {
    margin-bottom: 0 !important;
}

/* Ensure proper container spacing */
.container {
    padding-left: 15px;
    padding-right: 15px;
}

/* Stats box styling to match admin header */
.am-stats {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 0.5rem;
    padding: 1.5rem;
    backdrop-filter: blur(10px);
}

/* Search Box - matching admin page */
.am-search {
    max-width: 600px;
    margin: -2rem auto 2rem;
    position: relative;
    z-index: 1000;
}

.am-search .search-input {
    width: 100%;
    padding: 1rem 3rem 1rem 1.5rem;
    font-size: 1.125rem;
    border: 1px solid #dee2e6;
    border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    background: white;
}

.am-search .search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.am-search .search-icon {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

/* Category Pills */
.category-filter {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 0.5rem;
    overflow: hidden;
}

.category-scroll {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

.category-scroll::-webkit-scrollbar {
    height: 6px;
}

.category-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.category-scroll::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 3px;
}

.category-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 2rem;
    color: #495057;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s ease;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
}

.category-pill:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    color: #212529;
    text-decoration: none;
}

.category-pill.active {
    background: #0066cc;
    border-color: #0066cc;
    color: white;
}

.category-pill.active:hover {
    background: #0052a3;
    border-color: #0052a3;
    color: white;
}

.category-pill i {
    font-size: 1rem;
}

/* Reduce list item spacing */
.list-group-item {
    border: none;
    border-bottom: 1px solid #f8f9fa;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.15s ease;
    background: #fff;
}

.am-stats .stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #FFD700;
    display: block;
    margin-bottom: 0.25rem;
}

.am-stats .stat-label {
    font-size: 0.875rem;
    color: rgba(255,255,255,0.9);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 500;
}

.card {
    border: 1px solid #e9ecef;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
}

.card-header {
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.25rem;
}

/* Dark headers for both panels */
.card-header-dark {
    background: #495057;
    color: white !important;
    border-bottom: none;
}

.card-header-dark h5,
.card-header-dark h2 {
    margin: 0;
    color: white !important;
}

#datapanel .card-header h2 {
    font-size: 1.75rem;
    font-weight: 600;
}

/* Remove full height from cards - allow natural expansion */
.am-card-container {
    height: auto;
    min-height: 400px;
    /* Removed max-height to prevent internal scrollbars */
}

.am-list-container {
    /* Remove fixed height to allow content to expand the page */
    height: auto;
    min-height: 400px;
    background: #fff;
}

/* Removed scrollbar styling since we no longer have internal scrolling */

.list-group-item {
    border: none;
    border-bottom: 1px solid #f8f9fa;
    padding: 1rem 1.25rem;
    cursor: pointer;
    transition: all 0.15s ease;
    background: #fff;
}

.list-group-item:hover {
    background-color: #f8f9fa;
    transform: translateX(4px);
}

.list-group-item.active {
    background-color: #e7f3ff;
    color: #0066cc;
    border-left: 3px solid #0066cc;
    padding-left: calc(1.25rem - 3px);
}

.strength-bar {
    width: 4px;
    height: 32px;
    display: inline-block;
    margin-right: 1rem;
    border-radius: 2px;
}

/* Style for count in pill */
.pill-count {
    font-weight: normal;
    opacity: 0.8;
}

/* More spacing for header on all screens */
.content-header-admin {
    padding: 4rem 2rem !important;
}

/* Modal action buttons styling */
#credentialModal .action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

#credentialModal .modal-header {
    display: flex;
    align-items: center;
}

#credentialModal .modal-title {
    flex: 1;
}

.empty-state {
    text-align: center;
    padding: 5rem 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    color: #e9ecef;
}

.empty-state h5 {
    font-weight: 600;
    color: #495057;
}

/* Removed old search wrapper styles since search is now in header */

/* Modern button styles */
.btn-primary {
    background: #0066cc;
    border-color: #0066cc;
    font-weight: 500;
    padding: 0.5rem 1.25rem;
    border-radius: 0.375rem;
}

.btn-primary:hover {
    background: #0052a3;
    border-color: #0052a3;
}

.btn-outline-secondary {
    border-color: #dee2e6;
    color: #495057;
    font-weight: 500;
}

.btn-outline-secondary:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #212529;
}

.dropdown-menu {
    border: 1px solid #e9ecef;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
    border-radius: 0.375rem;
    padding: 0.5rem;
}

.dropdown-item {
    border-radius: 0.25rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
}

.dropdown-item:hover {
    background: #f8f9fa;
}

/* Item type icon styling */
.item-icon {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.item-icon i {
    font-size: 1.25rem;
    color: #6c757d;
}

.item-details {
    flex: 1;
    min-width: 0;
}

.item-name {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.125rem;
}

.item-host {
    font-size: 0.875rem;
    color: #6c757d;
}

.item-type-icon {
    color: #adb5bd;
    font-size: 1rem;
}

/* Loading spinner animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.spinner-border {
    width: 3rem;
    height: 3rem;
    border-width: 0.25rem;
    animation: spin 1s linear infinite;
}

/* Mobile adjustments */
@media (max-width: 991px) {
    /* Adjust header for mobile */
    .content-header-admin {
        padding: 3rem 1rem !important;
    }
    
    .content-header-admin h1 {
        font-size: 1.75rem;
    }
    
    .content-header-admin .lead {
        font-size: 1rem;
    }
    
    .am-search {
        margin: -2rem auto 1.5rem;
        padding: 0 15px;
    }
    
    .am-search .search-input {
        font-size: 1rem;
        padding: 0.875rem 2.5rem 0.875rem 1.25rem;
    }
    
    /* Hide desktop filter pills on mobile */
    .category-filter {
        display: none !important;
    }
    
    /* Show mobile filter dropdown */
    .mobile-filter-row {
        display: flex !important;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Hide right panel on mobile - will show in modal */
    #datapanel,
    .col-lg-8 {
        display: none;
    }
    
    /* Make left panel full width */
    .col-lg-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    /* Adjust card heights for mobile */
    .am-card-container {
        /* Remove max-height for mobile as well */
        min-height: 300px;
    }
}

/* Desktop only styles */
@media (min-width: 992px) {
    .mobile-filter-row {
        display: none !important;
    }
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
<!-- Admin Header Section -->
<div class="content-header-admin">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-shield-lock-fill me-3"></i>Access Manager</h1>
            <p class="lead mb-0">Secure credential and sensitive data management system</p>
        </div>
    </div>
</div>

<div class="container">
    <!-- Search Bar in Header -->
    <div class="am-search">
        <div class="position-relative">
            <input 
                type="text" 
                class="search-input" 
                placeholder="Search items..."
                id="searchBar"
                autocomplete="off"
            >
            <i class="bi bi-search search-icon"></i>
        </div>
    </div>
</div>

<div class="container mt-4">
    <!-- Mobile Filter Row -->
    <div class="mobile-filter-row" style="display: none;">
        <div class="dropdown flex-grow-1">
            <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="mobileFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-funnel me-1"></i> All Categories <span class="pill-count">('.$listcount.')</span>
            </button>
            <ul class="dropdown-menu w-100" aria-labelledby="mobileFilterDropdown">
                <li><button class="dropdown-item mobile-category-filter" type="button" data-value="all"><i class="bi bi-grid me-2"></i>All</button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item mobile-category-filter" type="button" data-value="Social Media"><i class="bi bi-chat-dots me-2"></i>Social Media</button></li>
                <li><button class="dropdown-item mobile-category-filter" type="button" data-value="Mail Server"><i class="bi bi-envelope me-2"></i>Mail Server</button></li>
                <li><button class="dropdown-item mobile-category-filter" type="button" data-value="vendor"><i class="bi bi-shop me-2"></i>Vendor</button></li>
                <li><button class="dropdown-item mobile-category-filter" type="button" data-value="licenses"><i class="bi bi-file-earmark-text me-2"></i>Licenses</button></li>
                <li><button class="dropdown-item mobile-category-filter" type="button" data-value="Personal"><i class="bi bi-person me-2"></i>Personal</button></li>
            </ul>
        </div>
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
                <i class="bi bi-plus-circle me-1"></i> <span class="d-none d-sm-inline">Create New</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButtonCreate">
                <li><button class="dropdown-item type-filter-dropdown" type="button" data-type="username_password"><i class="bi bi-person-lock me-2"></i>User/Password</button></li>
                <li><button class="dropdown-item type-filter-dropdown" type="button" data-type="sshkey"><i class="bi bi-key me-2"></i>SSH Key</button></li>
                <li><button class="dropdown-item type-filter-dropdown" type="button" data-type="file"><i class="bi bi-file-earmark me-2"></i>File</button></li>
                <li><button class="dropdown-item type-filter-dropdown" type="button" data-type="keyvalue"><i class="bi bi-code-square me-2"></i>Key/Value</button></li>
                <li><button class="dropdown-item type-filter-dropdown" type="button" data-type="special"><i class="bi bi-star me-2"></i>Special</button></li>
            </ul>
        </div>
        </form>
    </div>
    
    <!-- Desktop Filter Row -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Category Pills -->
        <div class="category-filter flex-grow-1 me-3">
            <div class="d-flex align-items-center">
                <div class="category-scroll">
                    <a href="#" class="category-pill active" data-value="all">
                        <i class="bi bi-grid"></i> All <span class="pill-count">('.$listcount.')</span>
                    </a>
                    <a href="#" class="category-pill" data-value="Social Media">
                        <i class="bi bi-chat-dots"></i> Social Media
                    </a>
                    <a href="#" class="category-pill" data-value="Mail Server">
                        <i class="bi bi-envelope"></i> Mail Server
                    </a>
                    <a href="#" class="category-pill" data-value="vendor">
                        <i class="bi bi-shop"></i> Vendor
                    </a>
                    <a href="#" class="category-pill" data-value="licenses">
                        <i class="bi bi-file-earmark-text"></i> Licenses
                    </a>
                    <a href="#" class="category-pill" data-value="Personal">
                        <i class="bi bi-person"></i> Personal
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="d-flex gap-2">
';


////////// RE-ENCRYPT DATA BUTTON
if (($account->isadmin()  && $account->isdeveloper(0))) {
    include($_SERVER['DOCUMENT_ROOT'] . $dir['ampath'].'/components/re-encyrpt_data.inc');
}



// Desktop Create New button
echo '
            <form action="' . $_SERVER['PHP_SELF'] . '" id="createnewform2" name="createnewform2"  method="post" class="d-none d-lg-block">
            '.$display->inputcsrf_token().'
            <input type="hidden" name="act" value="createnew">
            <input type="hidden" name="type" id="typeInput2"> 
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButtonCreate2" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-plus-circle me-1"></i> Create New
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButtonCreate2">
                    <li><button class="dropdown-item type-filter-dropdown-desktop" type="button" data-type="username_password"><i class="bi bi-person-lock me-2"></i>User/Password</button></li>
                    <li><button class="dropdown-item type-filter-dropdown-desktop" type="button" data-type="sshkey"><i class="bi bi-key me-2"></i>SSH Key</button></li>
                    <li><button class="dropdown-item type-filter-dropdown-desktop" type="button" data-type="file"><i class="bi bi-file-earmark me-2"></i>File</button></li>
                    <li><button class="dropdown-item type-filter-dropdown-desktop" type="button" data-type="keyvalue"><i class="bi bi-code-square me-2"></i>Key/Value</button></li>
                    <li><button class="dropdown-item type-filter-dropdown-desktop" type="button" data-type="special"><i class="bi bi-star me-2"></i>Special</button></li>
                </ul>
            </div>
            </form>
            ';

echo '
        </div>
    </div>
</div>
';


// DISPLAY THE DATA
echo '
<div class="container">
    <div class="row g-4">
        <!-- Left Column (1/3) -->
        <div class="col-lg-4">
            <div class="card am-card-container">
            <div class="card-header card-header-dark">
                <h5 class="mb-0"><i class="bi bi-key-fill me-2"></i>Credentials</h5>
            </div>
            <div class="card-body p-0">
                <div class="am-list-container">
                    <div class="list-group list-group-flush">
';


#    breakpoint($rows);

foreach ($rows as $row) {

    $strengthresult= $accessmanager->getStrength($row['password_strength']);

/// GENERATE THE DATA ACCESS LINK
echo  ' 
<a href="javascript:void(0);" class="list-group-item list-group-item-action" 
    data-category="'.htmlspecialchars($row['category']).'" 
    data-full-context="'. trim(htmlspecialchars($row['category'].' '.$row['host'].' '.$row['name'].' '.$row['description']).'"  
    onclick="selectItem(this, '.addslashes($row['id'])) . ')">
    <div class="d-flex align-items-center">
        <span class="strength-bar bg-'.$strengthresult['color'].'"></span>
        <div class="item-icon">
            <i class="'.$row['type_icon'].'"></i> 
        </div>
        <div class="item-details">
            <div class="item-name">' . htmlspecialchars($row['name']) . '</div>
            ' . ($row['host'] ? '<div class="item-host">' . htmlspecialchars($row['host']) . '</div>' : '') . '
        </div>
        <div class="item-type-icon">
            <i class="'.$row['datatype_icon'].'"></i> 
        </div>
    </div>
</a>';
}


echo '
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column (2/3) -->
    <div class="col-lg-8">
        <div class="card am-card-container" id="datapanel">
            <div class="card-header card-header-dark">
                <h2 class="mb-0">Select an item to view details</h2>
            </div>
';
if ($outputcontent=='')  {
echo '
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="empty-state">
                    <i class="bi bi-shield-lock"></i>
                    <h5 class="mt-3">No Item Selected</h5>
                    <p class="text-muted">Choose a credential from the list to view and manage its details</p>
                </div>
            </div>
';
} else {
echo '            <div class="card-body">'.
$outputcontent.'
            </div>';
}



echo '
            </div>
        </div>
    </div>
</div>
</div>
';

// Modal for mobile credential details
echo '
<div class="modal fade" id="credentialModal" tabindex="-1" aria-labelledby="credentialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header card-header-dark">
                <h2 class="modal-title" id="credentialModalLabel">Credential Details</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalCredentialContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

include($_SERVER['DOCUMENT_ROOT'] . $dir['ampath'].'/components/js-scripts.inc');


$forcefalseenablechat=true;

$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();