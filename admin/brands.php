<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page setup
$pagetitle = "Brand Management";
$header_flush = true;

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Assuming you have an instantiated $database object

// Fetch all records from the bg_companies table
$apponlycounter = 0;
$uploadtag = '';
$filter = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['cid']) && $_REQUEST['upload'] == 'y') {
    $company_id = $_REQUEST['cid'];

    if (isset($_FILES['imageUpload'])) {
        $errors = [];
        $file_name = $_FILES['imageUpload']['name'];
        $file_size = $_FILES['imageUpload']['size'];
        $file_tmp = $_FILES['imageUpload']['tmp_name'];
        $file_type = $_FILES['imageUpload']['type'];
        $explodedFileName = explode('.', $_FILES['imageUpload']['name']);
        $file_ext = strtolower(end($explodedFileName));

        $extensions = ["jpeg", "jpg", "png", "webp"];

        if (in_array($file_ext, $extensions) === false) {
            $errors[] = "Extension not allowed, please choose a JPEG, PNG, or WEBP file.";
        }

        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = 'File size must be less than 5 MB';
        }

        if (empty($errors)) {
            $index = 0;
            $sizeTag = 'custom';
            $sourceGrouping = 0;

            // Prepare the path to save the file
            $destinationFileName = 'logo_' . $company_id . '_cat-' . $sourceGrouping . '_set-' . $index . '_' . $sizeTag . '.' . $file_ext;

            if (move_uploaded_file($file_tmp, $dir['logos'] . '/' . $destinationFileName)) {
                // Update the company record with the new logo path
                $stmt = $database->prepare("UPDATE bg_companies SET logo = ? WHERE company_id = ?");
                $stmt->execute([$destinationFileName, $company_id]);
            }
        }
    }
}

// Fetch all companies
#$companies = $database->query("SELECT * FROM bg_companies")->fetchAll();



$additionalstyles .= '
<style>
.no-wrap {
white-space: nowrap;
}

.statusForm {
display: flex; /* Enables flexbox */
align-items: center; /* Vertically center aligns children */
justify-content: space-between; /* Optional: Spreads out children across the horizontal axis */
width: 100%; /* Optional: Makes the form take the full width of the container */
}

.statusForm select {
width: 150px; /* Adjust as needed */
font-size: 12px; /* Adjust as needed */
}

.statusForm i {
margin-left: 3px; /* Creates space between the select box and the icon */
}

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
background-color: #f2f2f2; /* This is a light grey color */
}

/* Custom Styles */
.custom-container {
font-size: 14px; /* Adjust the font size as needed */
}

.custom-container .btn {
padding: 0.25rem 0.5rem; /* Adjust the button padding as needed */
font-size: 12px; /* Adjust the button font size as needed */
}
.img-fluid{
width:40px;
}

/* Lazy loading styles */
.lazy {
    opacity: 0;
    transition: opacity 0.3s;
}

.lazy.loaded {
    opacity: 1;
}

/* Fix container alignment */
.main-content {
    padding-left: 0;
    padding-right: 0;
    padding-top: 0 !important;
}

/* Content header styling to match accessmanager */
.content-header-admin {
    padding: 4rem 2rem !important;
}

/* Search Box - matching accessmanager */
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

.pill-count {
    opacity: 0.7;
    font-weight: normal;
}

/* Remove extra spacing from table */
.table-responsive {
    margin-top: 0 !important;
}

#logoGallery {
    margin-top: 0 !important;
}
</style>
';

#-------------------------------------------------------------------------------
# ORIGINAL BEGINNING
#-------------------------------------------------------------------------------
$criteria = '';
if ((isset($_REQUEST['filter']) && $_REQUEST['filter'] != 'all') || $filter) {
    $criteria = ' where c.`status`="' . $_REQUEST['filter'] . '"';
    $filter = true;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_REQUEST['cid'])) {
    $criteria = ' where c.`company_id`="' . $_REQUEST['cid'] . '"';
}
$stmt = $database->query('SELECT c.* , MAX(a.description) AS company_logo
FROM bg_companies AS c
LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id AND a.category = "company_logos"  and a.`grouping` ="primary_logo" 
' . $criteria . ' 
GROUP BY c.company_id
order by company_name');
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
$companycount= count($companies);
$stmt = $database->query("SELECT `status`, count(*) as 'cnt' FROM bg_companies group by `status`");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);


echo '
<!-- Hero Section -->
<div class="content-header-admin">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-building me-3"></i>Brand Management</h1>
            <p class="lead mb-0">'.ucfirst($website['biznames']).' database with ' . $companycount. ' total brands</p>
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
                placeholder="Search brands..."
                id="searchBar"
                autocomplete="off"
            >
            <i class="bi bi-search search-icon"></i>
        </div>
    </div>
</div>

<div class="container mt-4">';

// Add filter pills
echo '
    <!-- Desktop Filter Row -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Category Pills -->
        <div class="category-filter flex-grow-1 me-3">
            <div class="d-flex align-items-center">
                <div class="category-scroll pb-2">
                    <a href="?filter=all" class="category-pill ' . (!isset($_REQUEST['filter']) || $_REQUEST['filter'] == 'all' ? 'active' : '') . '" data-value="all">
                        <i class="bi bi-grid"></i> All <span class="pill-count">(' . $companycount . ')</span>
                    </a>';

foreach ($stats as $stat) {
    $isActive = (isset($_REQUEST['filter']) && $_REQUEST['filter'] == $stat['status']);
    
    // Icon mapping for different statuses
    $iconMap = [
        'finalized' => 'bi-check-circle-fill',
        'active' => 'bi-circle-fill',
        'inactive' => 'bi-x-circle',
        'duplicate' => 'bi-files',
        'pending' => 'bi-clock',
        'new' => 'bi-star',
        'notworking' => 'bi-exclamation-triangle',
        'toocomplex' => 'bi-puzzle',
        'otprequired' => 'bi-shield-lock',
        'ng_toocomplex' => 'bi-puzzle-fill',
        'finalized_otp_bgm' => 'bi-shield-check'
    ];
    
    $icon = $iconMap[$stat['status']] ?? 'bi-circle';
    
    echo '
                    <a href="?filter=' . $stat['status'] . '" class="category-pill ' . ($isActive ? 'active' : '') . '" data-value="' . $stat['status'] . '">
                        <i class="bi ' . $icon . '"></i> ' . ucfirst(str_replace('_', ' ', $stat['status'])) . ' <span class="pill-count">(' . $stat['cnt'] . ')</span>
                    </a>';
}

echo '
                </div>
            </div>
        </div>
    </div>
</div>

<div class="">
    <div class="container">';

echo '
        <div class="row" id="logoGallery">
            <div class="col-12">
                <div class="table-responsive">
<table class="table table-striped table-bordered">
<thead>
<tr>
<th>ID</th>
<th>Logo</th>
<th>Name</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
';
foreach ($companies as $company) {

    $tag_strike_start = $tag_strike_end = '';
    if ($company['status'] == 'inactive' || $company['status'] == 'duplicate') {
        $tag_strike_start = '<del>';
        $tag_strike_end = '</del>';
    }
    switch ($company['status']) {
        case 'inactive':
            $statuscolor = 'text-secondary';
            $btn_color = 'btn-outline-secondary';
            break;
        case 'duplicate':
            $statuscolor = 'text-secondary';
            $btn_color = 'btn-outline-secondary';
            break;
        case 'notworking':
            $statuscolor = 'text-secondary';
            $btn_color = 'btn-outline-secondary';
            break;
        case 'active':
            $statuscolor = 'text-primary';
            $btn_color = 'btn-primary';
            break;
        case 'finalized':
            $statuscolor = 'text-success';
            $btn_color = 'btn-success fw-bold';
            break;
        default:
            $statuscolor = 'text-secondary';
            $btn_color = 'btn-outline-secondary';
            break;
    }


    echo '
<tr>
';

    echo '<td>' . $tag_strike_start . $company['company_id'] . $tag_strike_end . '</td>';
    echo '  <td><img class="img-fluid lazy" data-src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="' . htmlspecialchars($company['company_name']) . ' logo"></td>';



    $companysearchlink = '<a href="https://www.google.com/search?tbm=isch&q=' . urlencode($company['company_name'] . ' logo') . '" target="_logo" class="card-title">' . $company['company_name'] . '</a>';
    echo '<td>' . $tag_strike_start . $companysearchlink . $tag_strike_end . '</td>';

    $apponlyicon = '';
    if ($company['signup_url'] == $website['apponlytag']) {
        $apponlyicon = '<i class="ms-3 text-danger bi bi-phone-fill"></i>';
        $apponlycounter++;
    }


    echo '<td class="' . $statuscolor . '">' . $tag_strike_start . '
<form class="statusForm d-flex align-items-center" data-company-id="' . $company['company_id'] . '">
<select class="form-control" name="status" style="width: 150px; font-size: 12px;">';
    $statuses = ['finalized', 'active', 'inactive', 'duplicate', 'pending', 'new', 'notworking', 'toocomplex', 'otprequired', 'ng_toocomplex', 'finalized_otp_bgm'];
    foreach ($statuses as $status) {
        $selected = ($company['status'] === $status) ? 'selected' : '';
        echo '<option value="' . $status . '" ' . $selected . '>' . ucfirst(str_replace('_', ' ', $status)) . '</option>';
    }
    echo '</select>' . $tag_strike_end . $apponlyicon . '</form></td>';

    // Generate encoded company ID for business-editor
    $encodedCid = $qik->encodeId($company['company_id']);
    
    echo '
<td>
<a href="/admin/company-editor-main?cid=' . $company['company_id'] . '" class="btn btn-sm '. $btn_color.'">View Brand</a>
<a href="/admin/business-editor?cid=' . $encodedCid . '&section=formfieldedit" class="btn btn-sm btn-outline-primary ms-1">Form Fields</a>
</td>
</tr>
';
}

echo  '
                </tbody>
            </table>
        </div>
    </div>
</div>
    </div>
</div>';
?>
<script>
    // Lazy loading for images
    document.addEventListener("DOMContentLoaded", function() {
        let lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
        
        if ("IntersectionObserver" in window) {
            let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        let lazyImage = entry.target;
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImage.classList.remove("lazy");
                        lazyImage.classList.add("loaded");
                        lazyImageObserver.unobserve(lazyImage);
                    }
                });
            });
            
            lazyImages.forEach(function(lazyImage) {
                lazyImageObserver.observe(lazyImage);
            });
        } else {
            // Fallback for browsers that don't support IntersectionObserver
            lazyImages.forEach(function(lazyImage) {
                lazyImage.src = lazyImage.dataset.src;
                lazyImage.classList.remove("lazy");
                lazyImage.classList.add("loaded");
            });
        }
    });
    
    $(document).ready(function() {
        // Enhanced search functionality
        $('#searchBar').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            var visibleCount = 0;
            
            $('table tbody tr').each(function() {
                var text = $(this).text().toLowerCase();
                var matches = text.indexOf(value) > -1;
                $(this).toggle(matches);
                if (matches) visibleCount++;
            });
            
            // Update search placeholder with count
            if (value) {
                $('#searchBar').attr('placeholder', 'Found ' + visibleCount + ' brands...');
            } else {
                $('#searchBar').attr('placeholder', 'Search brands...');
            }
        });
        
        // Category pill click handlers
        $('.category-pill').on('click', function(e) {
            if (!$(this).hasClass('active')) {
                // Let the href navigate naturally
                return true;
            }
            e.preventDefault();
        });
    });

    function updateCompanyStatus(companyId, status) {
        $.ajax({
            url: 'editcompany.php',
            type: 'POST',
            data: {
                company_id: companyId,
                status: status,
                isAjax: 'true'
            },
            success: function(response) {
                console.log('Response:', response);
            },
            error: function(xhr, status, error) {
                console.error('Update failed:', error);
            }
        });
    }

    $('.statusForm select').on('change', function() {
        var companyId = $(this).closest('.statusForm').data('company-id');
        var status = $(this).val();
        updateCompanyStatus(companyId, status);
    });
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
