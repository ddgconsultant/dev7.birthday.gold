<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "User Management - Birthday Gold";
$page_description = "Manage Birthday Gold platform users";

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$p_displaylength = 180;
$searchTerm = '';

#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    if (isset($_POST['formtype']) && ($_POST['formtype'] == 'changedisplaylength')) {
        $p_displaylength = $_POST['displaylength'];
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
switch ($p_displaylength) {
    case 'all':
        $userlimitsql = '';
        break;
    default:
        $userlimitsql = " and u.create_dt >= CURDATE() - INTERVAL $p_displaylength DAY";
        break;
}

// Modern User List CSS
$additionalstyles .= '
<style>
/* Modern User Management Styles */
.main-content {
    min-height: calc(100vh - 200px);
    padding-top: 2rem;
    padding-bottom: 2rem;
}

/* Ensure content header is flush with navbar */
.content-header-admin {
    margin-top: 0 !important;
}

/* Main Card Styling */
.main-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.card-header.bg-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
    border: none;
    padding: 1.25rem 1.5rem;
}

.card-header h4 {
    font-size: 1.25rem;
    font-weight: 600;
}

/* Tab Styling */
.nav-tabs {
    border-bottom: none;
    gap: 0.5rem;
    margin-bottom: 0 !important;
}

.nav-tabs .nav-link {
    border: none;
    border-radius: 8px 8px 0 0;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 2rem;
    margin-bottom: -1px;
    transition: all 0.2s ease;
    background: #f8f9fa;
}

.nav-tabs .nav-link:hover {
    color: #0d6efd;
    background: #e9ecef;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    background: white;
    border: 1px solid #e9ecef;
    border-bottom: 1px solid white;
}

/* Tab Content */
.tab-content {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 0 8px 8px 8px;
    padding: 1.5rem;
}

/* Search and Filter Section */
.filter-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

/* Table Styling */
.user-table {
    font-size: 0.9rem;
}

.user-table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

/* Action Buttons */
.btn-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* User List Items */
.bglist-group-item {
    background: white;
    border: 1px solid #e9ecef !important;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
}

.bglist-group-item:hover {
    border-color: #0d6efd !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
}

/* Fix alignment issues */
.bglist-group-item .col-auto {
    display: flex;
    align-items: center;
}

.bglist-group-item .avatar {
    margin-right: 0.5rem;
}

/* User info section */
.bglist-group-item h6 {
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.bglist-group-item p {
    margin-bottom: 0.125rem;
}

/* User Details Button - Override Bootstrap */
.bglist-group-item .btn-primary {
    background: #0d6efd;
    color: white;
    border: none;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.bglist-group-item .btn-primary:hover {
    background: #0a58ca;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.bglist-group-item .btn-group {
    display: flex;
    align-items: center;
}

/* Override dropdown toggle */
.bglist-group-item .dropdown-toggle {
    padding: 0.375rem 0.5rem;
}

/* User Status Badges */
.badge {
    font-size: 0.75rem !important;
    padding: 0.25rem 0.5rem !important;
    font-weight: 500 !important;
}

.text-bg-danger {
    background: #dc3545 !important;
    color: white !important;
}

.text-bg-warning {
    background: #ffc107 !important;
    color: #000 !important;
}

.text-bg-primary {
    background: #0d6efd !important;
    color: white !important;
}

.text-bg-secondary {
    background: #6c757d !important;
    color: white !important;
}

.text-bg-info {
    background: #0dcaf0 !important;
    color: #000 !important;
}

.text-bg-success {
    background: #198754 !important;
    color: white !important;
}

/* Info Section */
.bg-info-subtle {
    background-color: #cff4fc !important;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

/* User Info Text */
.user-name {
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.user-email {
    font-size: 0.875rem;
    color: #6c757d;
}

.user-location {
    font-size: 0.75rem;
    color: #6c757d;
}

.user-dates {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: right;
}

/* Back Button Styling */
.btn-light {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-light:hover {
    background: white;
    transform: translateY(-1px);
}

/* Remove extra padding/margins */
.main-content.container-fluid {
    max-width: 100%;
    padding: 0;
}

.card.mb-3 {
    margin-bottom: 0 !important;
    border: none;
}

/* Text styling */
.fs-11 {
    font-size: 0.6875rem !important;
}

.text-muted {
    color: #6c757d !important;
}

/* Column adjustments for better spacing */
.bglist-group-item .row {
    align-items: center;
}

/* Responsive */
@media (max-width: 767px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .page-header p {
        font-size: 1rem;
    }
    
    .nav-tabs .nav-link {
        padding: 0.5rem 1rem;
    }
    
    .card-header h4 {
        font-size: 1.1rem;
    }
    
    .bglist-group-item {
        font-size: 0.875rem;
    }
    
    .bglist-group-item .col-4,
    .bglist-group-item .col-2,
    .bglist-group-item .col-1 {
        padding: 0.25rem;
    }
}
</style>
';

$bodycontentclass = '';
$header_flush = true; // Ensure header content is flush with admin header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


?>

<!-- Admin Header Section -->
<div class="content-header-admin">
    <div class="container">
        <h1 class="mb-3"><i class="bi bi-people me-3"></i>User Management</h1>
        <p class="lead mb-4">View and manage all platform users</p>
    </div>
</div>

<div class="container main-content">
    <div class="row">
        <div class="col-12">
            
            <!-- Main Content Card -->
            <div class="card main-card">
                <div class="card-header bg-primary text-white">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="mb-0">
                                <i class="bi bi-people"></i> User Database
                            </h4>
                        </div>
                        <div class="col-auto">
                            <a href="/admin/" class="btn btn-light btn-sm">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
            <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-5" id="all-users-tab" data-bs-toggle="tab" data-bs-target="#all-users" type="button" role="tab" aria-controls="all-users" aria-selected="true">Real Users</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-5" id="test-users-tab" data-bs-toggle="tab" data-bs-target="#test-users" type="button" role="tab" aria-controls="test-users" aria-selected="false">Test Users</button>
                </li>
            </ul>


            <div class="tab-content" id="userTabsContent">
                <div class="tab-pane fade show active" id="all-users" role="tabpanel" aria-labelledby="all-users-tab">
                    <?php include('user_components/user-list_allusers.inc'); ?>
                </div>


                <div class="tab-pane fade" id="test-users" role="tabpanel" aria-labelledby="test-users-tab">
                    <?php include('user_components/user-list_testusers.inc'); ?>
                </div>


            </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('displayLengthSelect').addEventListener('change', function() {
        document.getElementById('displayLengthForm').submit();
    });
</script>

<?php
echo "
<script>
$(document).ready(function() {
    $('#searchBar').on('input', function() {
        if ($(this).val().length > 0) {
            $('.clear-icon').show();
        } else {
            $('.clear-icon').hide();
        }
    });

    $('.clear-icon').click(function() {
        $('#searchBar').val('').focus();
        $(this).hide();
        $('#searchBar').trigger('keyup');
    });

    $('#searchBar').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.bglist-group-item').each(function() {
            var itemText = $(this).data('full-context').toLowerCase();
            if (itemText.includes(value)) {
                $(this).css('display', '');
            } else {
                $(this).attr('style', 'display: none !important;');
            }
        });
    });
});

var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
  return new bootstrap.Dropdown(dropdownToggleEl, {
    boundary: 'viewport' // Ensures the dropdown isn't clipped by any parent containers
  })
})
</script>
";

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
