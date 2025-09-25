<?php
$addClasses[] = 'Referral';
$addClasses[] = 'allocationmanager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// PREP VARIABLES AND SETUP
$p_displaylength = 30;
$badgeColors = ['success', 'warning', 'primary', 'danger', 'info', 'secondary', 'dark'];
$colorIndex = 0;
$ipColorMap = [];

// GET USER DATA
if (isset($_REQUEST['u'])) {
    $workingUser = $qik->decodeId($_REQUEST['u']);
    $tmpsettings['status']='*';
    $workinguserdata = $account->getuserdata($workingUser, 'user_id', $tmpsettings);
    $getaccountdetailsuser = $workinguserdata;
    include_once($dir['core_components'] . '/user_getaccountdetails.inc');
} else {
    header('location: /500');
    exit;
}

// GET ADDITIONAL USER DATA
// Get current allocation balance for user
$allocation_balance = $allocationmanager->getUserBalance($workingUser);

// Get recent enrollments
$recent_enrollments_sql = "SELECT uc.*, c.company_name, c.company_id
                          FROM bg_user_companies uc
                          JOIN bg_companies c ON uc.company_id = c.company_id
                          WHERE uc.user_id = :user_id
                          ORDER BY uc.create_dt DESC
                          LIMIT 5";
$recent_enrollments = $database->getrows($recent_enrollments_sql, ['user_id' => $workingUser]);

// Get recent logins from sessiontracking
$recent_logins_sql = "SELECT * FROM bg_sessiontracking 
                     WHERE user_id = :user_id 
                     AND name LIKE '%login%'
                     ORDER BY create_dt DESC 
                     LIMIT 5";
$recent_logins = $database->getrows($recent_logins_sql, ['user_id' => $workingUser]);

// Get user attributes summary
$attributes_sql = "SELECT type, COUNT(*) as count 
                  FROM bg_user_attributes 
                  WHERE user_id = :user_id AND status = 'active' 
                  GROUP BY type";
$user_attributes = $database->getrows($attributes_sql, ['user_id' => $workingUser]);

// Get notification preferences from user attributes
$notification_prefs = $database->getrow(
    "SELECT * FROM bg_user_attributes 
     WHERE user_id = :user_id 
     AND type = 'notification_preferences' 
     AND status = 'active' 
     LIMIT 1",
    ['user_id' => $workingUser]
);

// Get social connections
$social_connections = $database->getrows(
    "SELECT * FROM bg_user_attributes 
     WHERE user_id = :user_id 
     AND type = 'social_connection' 
     AND status = 'active'",
    ['user_id' => $workingUser]
);

// SETUP REFERER DATA
$referer = $referral->getreferer($workinguserdata['user_id']);
$refererbuttontitle = 'Add Referer';
$refereraction = 'add';

if (!empty($referer)) {
    $refererbuttontitle = 'Change Referer';
    $refereraction = 'change';
}

// PAGE STYLES
$additionalstyles .= '
<style>
/* Modern User Details Page Styles */
.content-header-admin {
    margin-top: 0 !important;
}

/* Quick Stats Bar */
.quick-stats {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-item {
    text-align: center;
    padding: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

/* Tab navigation with active bottom border - copied from loginhistory.php */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    gap: 0;
    overflow: hidden;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd !important;
    background: none;
}

/* Cards */
.info-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    margin-bottom: 1.5rem;
    transition: all 0.2s ease;
}

.info-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

/* Timeline */
.timeline {
    position: relative;
    padding: 2rem;
    margin: 1rem;
}

.timeline::before {
    content: "";
    position: absolute;
    left: 2rem;
    top: 2rem;
    bottom: 2rem;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
    padding-left: 2rem;
}

.timeline-item::before {
    content: "";
    position: absolute;
    left: 1.75rem;
    top: 0;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6c757d;
    border: 2px solid white;
    z-index: 1;
}

.timeline-item.success::before {
    background: #28a745;
}

.timeline-item.warning::before {
    background: #ffc107;
}

.timeline-item.danger::before {
    background: #dc3545;
}

/* User Header Card */
.user-header-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.user-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f8f9fa;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.user-avatar-large:hover {
    transform: scale(1.05);
}

/* Status Indicators */
.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-indicator.active {
    background: #d4edda;
    color: #155724;
}

.status-indicator.pending {
    background: #fff3cd;
    color: #856404;
}

.status-indicator.inactive {
    background: #f8d7da;
    color: #721c24;
}

/* Data Tables */
.data-table {
    font-size: 0.9rem;
}

.data-table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    padding: 0.75rem;
    vertical-align: middle;
}

/* Action Buttons */
.action-menu {
    position: relative;
}

.btn-action {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-action:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

/* Security Badge */
.security-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.security-badge.enabled {
    background: #d4edda;
    color: #155724;
}

.security-badge.disabled {
    background: #f8f9fa;
    color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
    .quick-stats {
        padding: 1rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .nav-tabs-custom {
        overflow-x: auto;
        flex-wrap: nowrap;
        -webkit-overflow-scrolling: touch;
    }
    
    .nav-tabs-custom .nav-item {
        margin-right: 1.5rem;
    }
    
    .nav-tabs-custom .nav-link {
        padding: 0.75rem 1.75rem;
        font-size: 0.9rem;
    }
    
    .user-avatar-large {
        width: 80px;
        height: 80px;
    }
}
</style>';

// START PAGE OUTPUT
$transferpagedata = $system->startpostpage();

$header_flush = true;
$pagetitle = "User Details - " . $workinguserdata['first_name'] . " " . $workinguserdata['last_name'];
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Build the page HTML
echo '
<!-- Admin Header Section -->
<div class="content-header-admin">
    <div class="container">
        <h1 class="mb-2">User Details</h1>
    </div>
</div>

<div class="container my-4">
    <!-- Back to Users Link -->
    <div class="text-end mb-3">
        <a href="/admin/user-list" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Users
        </a>
    </div>
    <!-- User Header Card -->
    <div class="user-header-card">
        <div class="row align-items-center">
            <div class="col-auto">
                <img src="' . htmlspecialchars($workinguserdata['avatar']) . '" 
                     alt="User Avatar" 
                     class="user-avatar-large"
                     data-bs-toggle="modal" 
                     data-bs-target="#avatarModal">
            </div>
            <div class="col">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h2 class="mb-1">
                            ' . htmlspecialchars($workinguserdata['first_name'] . ' ' . $workinguserdata['last_name']) . '
                            ' . ($workinguserdata['account_admin'] == 'Y' ? '<span class="badge bg-danger ms-2">Admin</span>' : '') . '
                            ' . ($account->isstaff('*', $workinguserdata['user_id']) ? '<span class="badge bg-success ms-2">Staff</span>' : '') . '
                        </h2>
                        <p class="text-muted mb-2">
                            <i class="bi bi-envelope me-2"></i>' . htmlspecialchars($workinguserdata['email']) . '
                            <span class="mx-2">·</span>
                            <i class="bi bi-person me-2"></i>@' . htmlspecialchars($workinguserdata['username']) . '
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <span class="status-indicator ' . $workinguserdata['status'] . '">
                                <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                ' . ucfirst($workinguserdata['status']) . '
                            </span>
                            <small class="text-muted">
                                <i class="bi bi-calendar-plus me-1"></i>
                                Joined ' . date('M j, Y', strtotime($workinguserdata['create_dt'])) . '
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-hash me-1"></i>
                                ID: ' . $workinguserdata['user_id'] . '
                            </small>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-gear me-2"></i>Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#accounttypeplanModal">
                                    <i class="bi bi-person-gear me-2"></i>Set Account/Plan</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#refererModal">
                                    <i class="bi bi-people-fill me-2"></i>' . $refererbuttontitle . '</a></li>';

if ($account->isadmin()) {
    echo '
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#employeeModal">
                                    <i class="bi bi-person-badge me-2"></i>Set Staff</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adminModal">
                                    <i class="bi bi-shield-lock me-2"></i>Set Admin Role</a></li>';
    
    if ($workinguserdata['status'] != 'pending' && $workinguserdata['status'] != 'deleted') {
        echo '
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($workinguserdata['user_id']) . '&aid=' . $qik->encodeId($current_user_data['user_id']) . '&_token=' . $display->inputcsrf_token('tokenonly') . '" class="dropdown-item">
                                    <i class="bi bi-person-arrows me-2"></i>Impersonate User</a></li>';
    }
}

echo '
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Bar -->
    <div class="quick-stats">
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value text-primary">' . $businessoutput['counts']['success'] . '</div>
                    <div class="stat-label">Enrollments</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value text-success">' . $allocation_balance['available_allocations'] . '</div>
                    <div class="stat-label">Available Allocations</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value text-info">' . $profilecompletion['required_percentage'] . '%</div>
                    <div class="stat-label">Profile Complete</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value text-warning">' . count($recent_logins) . '</div>
                    <div class="stat-label">Recent Logins</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation - using loginhistory.php structure -->
    <nav class="nav-tabs-modern">
        <a href="#overview" class="nav-tab-item active" data-bs-toggle="tab">
            <i class="bi bi-speedometer2 me-2"></i>Overview
        </a>
        <a href="#account" class="nav-tab-item" data-bs-toggle="tab">
            <i class="bi bi-person-vcard me-2"></i>Account
        </a>
        <a href="#enrollments" class="nav-tab-item" data-bs-toggle="tab">
            <i class="bi bi-gift me-2"></i>Enrollments
        </a>
        <a href="#activity" class="nav-tab-item" data-bs-toggle="tab">
            <i class="bi bi-activity me-2"></i>Activity
        </a>
        <a href="#security" class="nav-tab-item" data-bs-toggle="tab">
            <i class="bi bi-shield-lock me-2"></i>Security
        </a>
        <a href="#attributes" class="nav-tab-item" data-bs-toggle="tab">
            <i class="bi bi-tags me-2"></i>Attributes
        </a>
    </nav>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview">
            <div class="row">
                <!-- Personal Information -->
                <div class="col-lg-6">
                    <div class="info-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="card-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-person"></i>
                                </div>
                                <h5 class="mb-0 ms-3">Personal Information</h5>
                            </div>
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">Birthday</td>
                                    <td class="text-end">
                                        ' . htmlspecialchars($workinguserdata['birthdate']) . '
                                        <small class="text-muted d-block">' . $alive['years'] . ' years old</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Zodiac Sign</td>
                                    <td class="text-end">
                                        ' . htmlspecialchars($user_astrosigndetails['name']) . '
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Location</td>
                                    <td class="text-end">
                                        ' . htmlspecialchars($location) . '
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone</td>
                                    <td class="text-end">
                                        ' . htmlspecialchars($workinguserdata['phone_number'] ?? 'Not set') . '
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Gender</td>
                                    <td class="text-end">
                                        ' . htmlspecialchars($workinguserdata['gender'] ?? 'Not specified') . '
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Account Summary -->
                <div class="col-lg-6">
                    <div class="info-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="card-icon bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-credit-card"></i>
                                </div>
                                <h5 class="mb-0 ms-3">Account Summary</h5>
                            </div>
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">Current Plan</td>
                                    <td class="text-end">
                                        <strong>' . htmlspecialchars($user_planddetails['displayname'] ?? 'Free') . '</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Account Type</td>
                                    <td class="text-end">
                                        ' . ucfirst($workinguserdata['account_type']) . '
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Allocations</td>
                                    <td class="text-end">
                                        <span class="badge bg-success">' . $allocation_balance['available_allocations'] . ' Available</span>
                                        ' . ($allocation_balance['pending_allocations'] > 0 ? '<span class="badge bg-warning">' . $allocation_balance['pending_allocations'] . ' Pending</span>' : '') . '
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Feature Email</td>
                                    <td class="text-end">
                                        ' . htmlspecialchars($workinguserdata['feature_email'] ?? 'Not set') . '
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Referrer</td>
                                    <td class="text-end">';

if (!empty($referer)) {
    echo '<a href="/admin/user-details?u=' . $qik->encodeId($referer['referrer_id']) . '">' . htmlspecialchars($referer['referrer_name']) . '</a>';
} else {
    echo '<span class="text-muted">None</span>';
}

echo '
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Timeline -->
                <div class="col-12 mt-4">
                    <div class="info-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="card-icon bg-info bg-opacity-10 text-info">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <h5 class="mb-0 ms-3">Recent Activity</h5>
                            </div>
                            <div class="timeline">';

// Combine different activities
$activities = [];

// Add recent enrollments
foreach ($recent_enrollments as $enrollment) {
    $activities[] = [
        'type' => 'enrollment',
        'date' => $enrollment['create_dt'],
        'title' => 'Enrolled in ' . $enrollment['company_name'],
        'status' => $enrollment['status'],
        'icon' => 'bi-gift'
    ];
}

// Add recent logins
foreach ($recent_logins as $login) {
    $activities[] = [
        'type' => 'login',
        'date' => $login['create_dt'],
        'title' => 'Logged in from ' . $login['ip'],
        'status' => 'success',
        'icon' => 'bi-box-arrow-in-right'
    ];
}

// Sort by date
usort($activities, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Display top 10
$activities = array_slice($activities, 0, 10);

foreach ($activities as $activity) {
    echo '
                                <div class="timeline-item ' . $activity['status'] . '">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <i class="' . $activity['icon'] . ' me-2"></i>
                                            <strong>' . htmlspecialchars($activity['title']) . '</strong>
                                        </div>
                                        <small class="text-muted">
                                            ' . date('M j, g:i A', strtotime($activity['date'])) . '
                                        </small>
                                    </div>
                                </div>';
}

echo '
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Tab -->
        <div class="tab-pane fade" id="account">
            <div class="row">
                <div class="col-lg-8">
                    <div class="info-card">
                        <div class="card-body p-3">
                            <h5 class="mb-3">Account Details</h5>
                            <table class="table">
                                <tr>
                                    <td width="200"><strong>User ID</strong></td>
                                    <td>' . $workinguserdata['user_id'] . '</td>
                                </tr>
                                <tr>
                                    <td><strong>Username</strong></td>
                                    <td>' . htmlspecialchars($workinguserdata['username']) . '</td>
                                </tr>
                                <tr>
                                    <td><strong>Email</strong></td>
                                    <td>
                                        <a href="mailto:' . htmlspecialchars($workinguserdata['email']) . '">
                                            ' . htmlspecialchars($workinguserdata['email']) . '
                                        </a>';

if (isset($workinguserdata['email_verified']) && $workinguserdata['email_verified'] == 'Y') {
    echo ' <span class="badge bg-success ms-2">Verified</span>';
} elseif (isset($workinguserdata['email_verified'])) {
    echo ' <span class="badge bg-warning ms-2">Unverified</span>';
}

echo '
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Account Status</strong></td>
                                    <td>
                                        <span class="status-indicator ' . $workinguserdata['status'] . '">
                                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                            ' . ucfirst($workinguserdata['status']) . '
                                        </span>';

if ($workinguserdata['status'] == 'pending') {
    $validatedata = [
        'rawdata' => $workinguserdata['email'],
        'user_id' => $workinguserdata['user_id'],
        'sendcount' => 0,
        'action' => 'getlatest'
    ];
    $validationcodes = $app->getvalidationcodes($validatedata);
    if (!empty($validationcodes['mini'])) {
        echo '
                                        <span class="ms-2 small text-muted">
                                            Code: <code class="user-select-all">' . htmlspecialchars($validationcodes['mini']) . '</code>
                                        </span>';
    }
}

echo '
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Account Type</strong></td>
                                    <td>' . ucfirst($workinguserdata['account_type']) . '</td>
                                </tr>
                                <tr>
                                    <td><strong>Plan</strong></td>
                                    <td>
                                        ' . htmlspecialchars($user_planddetails['displayname'] ?? 'Free') . '
                                        <small class="text-muted">(ID: ' . $workinguserdata['account_product_id'] . ')</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Created</strong></td>
                                    <td>' . date('F j, Y g:i A', strtotime($workinguserdata['create_dt'])) . '</td>
                                </tr>
                                <tr>
                                    <td><strong>Last Modified</strong></td>
                                    <td>' . date('F j, Y g:i A', strtotime($workinguserdata['modify_dt'])) . '</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="info-card">
                        <div class="card-body p-3">
                            <h5 class="mb-3">Roles & Permissions</h5>
                            <div class="mb-3">';

if ($workinguserdata['account_admin'] == 'Y') {
    echo '
                                <span class="badge bg-danger d-inline-block mb-2">
                                    <i class="bi bi-shield-lock me-1"></i>Administrator
                                </span>';
}

if ($account->isstaff('*', $workinguserdata['user_id'])) {
    echo '
                                <span class="badge bg-success d-inline-block mb-2">
                                    <i class="bi bi-person-badge me-1"></i>Staff Member
                                </span>';
}

if ($account->isbrandowner('*', $workinguserdata['user_id'])) {
    echo '
                                <span class="badge bg-primary d-inline-block mb-2">
                                    <i class="bi bi-building me-1"></i>Brand Owner
                                </span>';
}

if ($account->iscconsultant($workinguserdata['user_id'])) {
    echo '
                                <span class="badge bg-info d-inline-block mb-2">
                                    <i class="bi bi-briefcase me-1"></i>Consultant
                                </span>';
}

echo '
                            </div>
                            
                            <h6 class="mt-4 mb-3">Quick Actions</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#accounttypeplanModal">
                                    <i class="bi bi-gear me-2"></i>Change Plan
                                </button>';

if ($account->isadmin()) {
    echo '
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#adminModal">
                                    <i class="bi bi-shield me-2"></i>Admin Settings
                                </button>';
}

echo '
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrollments Tab -->
        <div class="tab-pane fade" id="enrollments">
            <div class="info-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Enrollment History</h5>
                        <div>
                            <span class="badge bg-primary">' . $businessoutput['counts']['remaining'] . ' Available</span>
                            <span class="badge bg-warning">' . $businessoutput['counts']['pending'] . ' Pending</span>
                            <span class="badge bg-success">' . $businessoutput['counts']['success'] . ' Successful</span>
                            <span class="badge bg-danger">' . $businessoutput['counts']['failed'] . ' Failed</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Status</th>
                                    <th>Enrolled Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';

$enrollment_sql = "SELECT uc.*, c.company_name 
                  FROM bg_user_companies uc
                  JOIN bg_companies c ON uc.company_id = c.company_id
                  WHERE uc.user_id = :user_id
                  ORDER BY uc.create_dt DESC
                  LIMIT 50";
$enrollments = $database->getrows($enrollment_sql, ['user_id' => $workingUser]);

foreach ($enrollments as $enrollment) {
    $status_class = 'secondary';
    if ($enrollment['status'] == 'success') $status_class = 'success';
    elseif ($enrollment['status'] == 'pending') $status_class = 'warning';
    elseif ($enrollment['status'] == 'failed') $status_class = 'danger';
    
    echo '
                                <tr>
                                    <td>
                                        <strong>' . htmlspecialchars($enrollment['company_name']) . '</strong>
                                        <small class="text-muted d-block">ID: ' . $enrollment['company_id'] . '</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-' . $status_class . '">
                                            ' . ucfirst($enrollment['status']) . '
                                        </span>
                                    </td>
                                    <td>' . date('M j, Y', strtotime($enrollment['create_dt'])) . '</td>
                                    <td>
                                        <a href="/admin/company-details?id=' . $enrollment['company_id'] . '" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>';
}

echo '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Tab -->
        <div class="tab-pane fade" id="activity">
            <div class="info-card">
                <div class="card-body p-3">
                    <h5 class="mb-4">Session Activity</h5>
                    <div class="table-responsive">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>Action</th>
                                    <th>Page</th>
                                    <th>Date/Time</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>';

$sql = "SELECT * FROM bg_sessiontracking 
        WHERE user_id = ? " . 
        ($mode != 'dev' ? "AND site = 'www'" : "AND type = 'user'") . 
        " ORDER BY create_dt DESC LIMIT 100";

$stmt = $database->prepare($sql);
$stmt->execute([$workinguserdata['user_id']]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($logs as $log) {
    if (!isset($ipColorMap[$log['ip']])) {
        $ipColorMap[$log['ip']] = $badgeColors[$colorIndex % count($badgeColors)];
        $colorIndex++;
    }
    
    echo '
                                <tr>
                                    <td>
                                        <span class="badge bg-' . $ipColorMap[$log['ip']] . '">
                                            ' . htmlspecialchars($log['ip']) . '
                                        </span>
                                    </td>
                                    <td>' . htmlspecialchars($log['name']) . '</td>
                                    <td><code>' . htmlspecialchars($log['page']) . '</code></td>
                                    <td>' . date('M j, g:i A', strtotime($log['create_dt'])) . '</td>
                                    <td>
                                        <a href="/admin/sessiondetails?id=' . $log['id'] . '" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-search"></i>
                                        </a>
                                    </td>
                                </tr>';
}

echo '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div class="tab-pane fade" id="security">
            <div class="row">
                <div class="col-lg-6">
                    <div class="info-card">
                        <div class="card-body p-3">
                            <h5 class="mb-3">Security Settings</h5>
                            <table class="table">
                                <tr>
                                    <td>Two-Factor Authentication</td>
                                    <td class="text-end">';

$has_2fa = $database->query("SELECT COUNT(*) FROM bg_user_attributes WHERE user_id = {$workingUser} AND type = 'security' AND name = '2fa' AND status = 'active'")->fetchColumn() > 0;
$tfa_class = $has_2fa ? 'enabled' : 'disabled';
$tfa_icon = $has_2fa ? 'check' : 'x';
$tfa_text = $has_2fa ? 'Enabled' : 'Disabled';

echo '
                                        <span class="security-badge ' . $tfa_class . '">
                                            <i class="bi bi-' . $tfa_icon . '-circle"></i> 
                                            ' . $tfa_text . '
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Security Questions</td>
                                    <td class="text-end">';

$has_sq = $database->query("SELECT COUNT(*) FROM bg_user_attributes WHERE user_id = {$workingUser} AND type = 'security' AND name = 'security_questions' AND status = 'active'")->fetchColumn() > 0;
$sq_class = $has_sq ? 'enabled' : 'disabled';
$sq_icon = $has_sq ? 'check' : 'x';
$sq_text = $has_sq ? 'Set' : 'Not Set';

echo '
                                        <span class="security-badge ' . $sq_class . '">
                                            <i class="bi bi-' . $sq_icon . '-circle"></i> 
                                            ' . $sq_text . '
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Email Verified</td>
                                    <td class="text-end">';

$email_verified = $workinguserdata['email_verified'] ?? 'N';
$verified_class = ($email_verified == 'Y') ? 'enabled' : 'disabled';
$verified_icon = ($email_verified == 'Y') ? 'check' : 'x';
$verified_text = ($email_verified == 'Y') ? 'Verified' : 'Unverified';

echo '
                                        <span class="security-badge ' . $verified_class . '">
                                            <i class="bi bi-' . $verified_icon . '-circle"></i> 
                                            ' . $verified_text . '
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Password Last Changed</td>
                                    <td class="text-end">';

$pwd_change = $database->getrow("SELECT modify_dt FROM bg_user_attributes WHERE user_id = :user_id AND type = 'security' AND name = 'password_changed' ORDER BY modify_dt DESC LIMIT 1", ['user_id' => $workingUser]);
echo $pwd_change ? date('M j, Y', strtotime($pwd_change['modify_dt'])) : 'Never';

echo '
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="info-card">
                        <div class="card-body">
                            <h5 class="mb-3">Recent Login History</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>IP Address</th>
                                            <th>Device</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

foreach ($recent_logins as $login) {
    echo '
                                        <tr>
                                            <td>' . date('M j, g:i A', strtotime($login['create_dt'])) . '</td>
                                            <td>' . htmlspecialchars($login['ip']) . '</td>
                                            <td>' . htmlspecialchars(substr($login['page'] ?? 'Unknown', 0, 50)) . '</td>
                                        </tr>';
}

echo '
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attributes Tab -->
        <div class="tab-pane fade" id="attributes">
            <div class="info-card">
                <div class="card-body p-3">
                    <h5 class="mb-4">User Attributes</h5>
                    <div class="row mb-4">';

foreach ($user_attributes as $attr) {
    echo '
                        <div class="col-md-2 mb-3">
                            <div class="text-center">
                                <h3 class="mb-1">' . $attr['count'] . '</h3>
                                <small class="text-muted">' . ucfirst(str_replace('_', ' ', $attr['type'])) . '</small>
                            </div>
                        </div>';
}

echo '
                    </div>
                    
                    <h6 class="mb-3">Recent Attributes</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Value</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>';

$recent_attrs = $database->getrows(
    "SELECT * FROM bg_user_attributes 
     WHERE user_id = :user_id 
     ORDER BY create_dt DESC 
     LIMIT 20",
    ['user_id' => $workingUser]
);

foreach ($recent_attrs as $attr) {
    echo '
                                <tr>
                                    <td><span class="badge bg-secondary">' . htmlspecialchars($attr['type']) . '</span></td>
                                    <td>' . htmlspecialchars($attr['name']) . '</td>
                                    <td>' . htmlspecialchars(substr($attr['value'] ?? $attr['description'], 0, 50)) . '...</td>
                                    <td>' . date('M j', strtotime($attr['create_dt'])) . '</td>
                                </tr>';
}

echo '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Modals -->
';

include('user_components/user-details_modals.inc');

echo '
<!-- Avatar Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="' . htmlspecialchars($workinguserdata['avatar']) . '" 
                     alt="User Avatar" 
                     class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<script>
// Tab switching functionality - similar to loginhistory.php
document.addEventListener("DOMContentLoaded", function() {
    const tabItems = document.querySelectorAll(".nav-tab-item");
    const tabPanes = document.querySelectorAll(".tab-pane");
    
    tabItems.forEach(function(tab) {
        tab.addEventListener("click", function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            tabItems.forEach(function(item) {
                item.classList.remove("active");
            });
            
            // Remove show and active classes from all panes
            tabPanes.forEach(function(pane) {
                pane.classList.remove("show", "active");
            });
            
            // Add active class to clicked tab
            this.classList.add("active");
            
            // Show corresponding pane
            const targetId = this.getAttribute("href").substring(1);
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
            }
        });
    });
});
</script>
';

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();