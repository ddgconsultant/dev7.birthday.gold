<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Initialize counters and flags
$apponlycounter = 0;
$uploadtag = '';
$filter = false;

// Handle logo upload
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

// Add styles
$additionalstyles .= ' 
<style>
.no-wrap {
    white-space: nowrap;
}

.statusForm {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.statusForm select {
    width: 150px;
    font-size: 12px;
}

.statusForm i {
    margin-left: 3px;
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
    background-color: #f2f2f2;
}

.custom-container {
    font-size: 14px;
}

.custom-container .btn {
    padding: 0.25rem 0.5rem;
    font-size: 12px;
}
.img-fluid{
    width:40px;
}
</style>';

// Set query criteria based on filters
$criteria = '';
if ((isset($_REQUEST['filter']) && $_REQUEST['filter'] != 'all') || $filter) {
    $criteria = ' where c.`status`="' . $_REQUEST['filter'] . '"';
    $filter = true;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_REQUEST['cid'])) {
    $criteria = ' where c.`company_id`="' . $_REQUEST['cid'] . '"';
}

// Fetch companies with logo
$stmt = $database->query('SELECT c.* , MAX(a.description) AS company_logo
FROM bg_companies AS c
LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id AND a.category = "company_logos"  and a.`grouping` ="primary_logo" 
' . $criteria . ' 
GROUP BY c.company_id
order by company_name');
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
$companycount = count($companies);

// Get status statistics
$stmt = $database->query("SELECT `status`, count(*) as 'cnt' FROM bg_companies group by `status`");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Begin HTML output
echo '
<div class="container main-content">
    <h1>' . ucfirst($website['biznames']) . ': ' . $companycount . '</h1>
    <div class="container mt-5">';

// Add search bar for larger result sets
if ($companycount > 5) {
    echo '
    <input type="text" id="searchBar" class="form-control mb-4" placeholder="Search ' . $website['biznames'] . '...">';
}

// Begin table
echo '
    <div class="row" id="logoGallery">
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
                <tbody>';

// Loop through companies
foreach ($companies as $company) {
    // Format based on status
    $tag_strike_start = $tag_strike_end = '';
    if ($company['status'] == 'inactive' || $company['status'] == 'duplicate') {
        $tag_strike_start = '<del>';
        $tag_strike_end = '</del>';
    }
    
    // Set status colors
    switch ($company['status']) {
        case 'inactive':
        case 'duplicate':
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

    // Start table row
    echo '<tr>';
    
    // ID cell
    echo '<td>' . $tag_strike_start . $company['company_id'] . $tag_strike_end . '</td>';
    
    // Logo cell
    echo '<td><img class="img-fluid" src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt=""></td>';
    
    // Name cell with Google search link
    $companysearchlink = '<a href="https://www.google.com/search?tbm=isch&q=' . urlencode($company['company_name'] . ' logo') . '" target="_logo" class="card-title">' . $company['company_name'] . '</a>';
    echo '<td>' . $tag_strike_start . $companysearchlink . $tag_strike_end . '</td>';
    
    // App-only indicator
    $apponlyicon = '';
    if ($company['signup_url'] == $website['apponlytag']) {
        $apponlyicon = '<i class="ms-3 text-danger bi bi-phone-fill"></i>';
        $apponlycounter++;
    }
    
    // Status dropdown cell
    echo '<td class="' . $statuscolor . '">' . $tag_strike_start . '
        <form class="statusForm d-flex align-items-center" data-company-id="' . $company['company_id'] . '">
            <select class="form-control" name="status" style="width: 150px; font-size: 12px;">';
    
    $statuses = ['finalized', 'active', 'inactive', 'duplicate', 'pending', 'new', 'notworking', 'toocomplex', 'otprequired', 'ng_toocomplex', 'finalized_otp_bgm'];
    foreach ($statuses as $status) {
        $selected = ($company['status'] === $status) ? 'selected' : '';
        echo '<option value="' . $status . '" ' . $selected . '>' . ucfirst(str_replace('_', ' ', $status)) . '</option>';
    }
    echo '</select>' . $tag_strike_end . $apponlyicon . '</form></td>';
    
    // Action button cell
    $encoded_cid = $qik->encodeId($company['company_id']);
    echo '
        <td>
            <a href="/admin/business-editor?cid=' . $encoded_cid . '" class="btn btn-sm ' . $btn_color . '">View Business</a>
        </td>
    </tr>';
}

// Close table and containers
echo '
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>';
?>

<script>
    $(document).ready(function() {
        // Search functionality
        $('#searchBar').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });

        // Status update function
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

        // Status change event handler
        $('.statusForm select').on('change', function() {
            var companyId = $(this).closest('.statusForm').data('company-id');
            var status = $(this).val();
            updateCompanyStatus(companyId, status);
        });
    });
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>