<?php
// === PERFORMANCE MONITORING START ===
$perf_timers = [];
$perf_start = microtime(true);
$perf_timers['start'] = 0;

$addClasses[] = 'Referral';
$addClasses[] = 'allocationmanager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$perf_timers['site_controller'] = round((microtime(true) - $perf_start) * 1000, 2);

// PREP VARIABLES AND SETUP
$p_displaylength = 30;
$badgeColors = ['success', 'warning', 'primary', 'danger', 'info', 'secondary', 'dark'];
$colorIndex = 0;
$ipColorMap = [];

// GET USER DATA
$perf_checkpoint = microtime(true);
if (isset($_REQUEST['u'])) {
    // Handle both encoded and plain numeric user IDs
    $userIdParam = $_REQUEST['u'];

    // Check if it's a plain numeric ID
    if (is_numeric($userIdParam)) {
        $workingUser = intval($userIdParam);
    } else {
        // Try to decode it
        $workingUser = $qik->decodeId($userIdParam);

        // If decoding fails, it might be base64 encoded (from JavaScript)
        if (!$workingUser || $workingUser === false) {
            // Try base64 decode as fallback
            $decoded = base64_decode($userIdParam);
            if (is_numeric($decoded)) {
                $workingUser = intval($decoded);
            } else {
                // Invalid ID format
                $workingUser = 0;
            }
        }
    }

    if (!$workingUser || $workingUser <= 0) {
        header('location: /500');
        exit;
    }

    $tmpsettings['status']='*';
    $workinguserdata = $account->getuserdata($workingUser, 'user_id', $tmpsettings);
    $perf_timers['getuserdata'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

    $perf_checkpoint = microtime(true);
    $getaccountdetailsuser = $workinguserdata;
    include_once($dir['core_components'] . '/user_getaccountdetails.inc');
    $perf_timers['user_getaccountdetails'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);
} else {
    header('location: /500');
    exit;
}

// GET ADDITIONAL USER DATA
// Get current allocation balance for user
$perf_checkpoint = microtime(true);
$allocation_balance = $allocationmanager->getUserBalance($workingUser);
$perf_timers['getUserBalance'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

// OPTIMIZATION: Don't load heavy data upfront - load via AJAX on tab click
// Only load minimal data needed for Overview tab
$recent_enrollments = []; // Will load via AJAX
$recent_logins = []; // Will load via AJAX
$user_attributes = []; // Will load via AJAX

// Get MINIMAL recent enrollments just for overview (top 5)
$perf_checkpoint = microtime(true);
$recent_enrollments_sql = "SELECT uc.*, c.company_name, c.company_id
                          FROM bg_user_companies uc
                          JOIN bg_companies c ON uc.company_id = c.company_id
                          WHERE uc.user_id = :user_id
                          ORDER BY uc.create_dt DESC
                          LIMIT 5";
$recent_enrollments = $database->getrows($recent_enrollments_sql, ['user_id' => $workingUser]);
$perf_timers['recent_enrollments'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

// Get MINIMAL recent logins just for overview (top 5)
// OPTIMIZED: Use specific name values + only select needed columns
$perf_checkpoint = microtime(true);
$recent_logins_sql = "SELECT id, create_dt, ip, name, page FROM bg_sessiontracking
                     WHERE user_id = :user_id
                     AND name IN ('LOGIN-success_user', 'LOGIN-success_admin', 'bg_rememberme_loginsuccess', 'login_success')
                     ORDER BY create_dt DESC
                     LIMIT 5";
$recent_logins = $database->getrows($recent_logins_sql, ['user_id' => $workingUser]);
$perf_timers['recent_logins'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

// Get notification preferences from user attributes
$perf_checkpoint = microtime(true);
$notification_prefs = $database->getrow(
    "SELECT * FROM bg_user_attributes
     WHERE user_id = :user_id
     AND type = 'notification_preferences'
     AND status = 'active'
     LIMIT 1",
    ['user_id' => $workingUser]
);
$perf_timers['notification_prefs'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

// Get social connections
$perf_checkpoint = microtime(true);
$social_connections = $database->getrows(
    "SELECT * FROM bg_user_attributes
     WHERE user_id = :user_id
     AND type = 'social_connection'
     AND status = 'active'",
    ['user_id' => $workingUser]
);
$perf_timers['social_connections'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

// SETUP REFERER DATA
$perf_checkpoint = microtime(true);
$referer = $referral->getreferer($workinguserdata['user_id']);
$perf_timers['getreferer'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);
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

/* Allocation Amount Styles */
.allocation-positive {
    color: #28a745;
    font-weight: 600;
}

.allocation-negative {
    color: #dc3545;
    font-weight: 600;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
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
$perf_checkpoint = microtime(true);
$transferpagedata = $system->startpostpage();
$perf_timers['startpostpage'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

$header_flush = true;
$pagetitle = "User Details - " . $workinguserdata['first_name'] . " " . $workinguserdata['last_name'];

$perf_checkpoint = microtime(true);
include($dir['core_components'] . '/bg_pagestart.inc');
$perf_timers['bg_pagestart'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

$perf_checkpoint = microtime(true);
include($dir['core_components'] . '/bg_header.inc');
$perf_timers['bg_header'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

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
        <a href="#allocations" class="nav-tab-item" data-bs-toggle="tab">
            <i class="bi bi-coin me-2"></i>Allocations
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
        <a href="#notifications" class="nav-tab-item" data-bs-toggle="tab">
            <i class="bi bi-bell me-2"></i>Notifications
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

if (!empty($referer) && isset($referer['referrer_id']) && isset($referer['referrer_name'])) {
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
            <!-- Enrollment Statistics Summary -->
            <div class="row mb-4" id="enrollment-stats-summary">
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading enrollment statistics...</p>
                </div>
            </div>

            <!-- Enrollment History Table -->
            <div class="info-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Enrollment History</h5>
                        <div id="enrollment-badge-summary">
                            <span class="badge bg-secondary" id="total-enrollments-badge">0 Total</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table data-table" id="enrollments-table">
                            <thead>
                                <tr>
                                    <th width="80">EID</th>
                                    <th>Company</th>
                                    <th>Status</th>
                                    <th>Enrolled Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading enrollment data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Allocations Tab -->
        <div class="tab-pane fade" id="allocations">
            <!-- Allocation Summary Stats -->
            <div class="row g-3 mb-4" id="allocation-stats">
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading allocation data...</p>
                </div>
            </div>

            <!-- Allocation History Table -->
            <div class="info-card">
                <div class="card-body p-3">
                    <h5 class="mb-4">Allocation Transactions</h5>
                    <div class="table-responsive">
                        <table class="table data-table" id="allocations-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-center">Amount</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th class="text-center">Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading allocation data...</p>
                                    </td>
                                </tr>
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
                        <table class="table data-table" id="activity-table">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>Action</th>
                                    <th>Page</th>
                                    <th>Date/Time</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading activity data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-outline-primary" id="load-more-activity" style="display: none;">
                            Load More
                        </button>
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

$stmt_2fa = $database->prepare("SELECT COUNT(*) as count FROM bg_user_attributes WHERE user_id = :user_id AND type = 'security' AND name = '2fa' AND status = 'active'");
$stmt_2fa->execute(['user_id' => $workingUser]);
$has_2fa = $stmt_2fa->fetchColumn() > 0;
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

$stmt_sq = $database->prepare("SELECT COUNT(*) as count FROM bg_user_attributes WHERE user_id = :user_id AND type = 'security' AND name = 'security_questions' AND status = 'active'");
$stmt_sq->execute(['user_id' => $workingUser]);
$has_sq = $stmt_sq->fetchColumn() > 0;
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
                        <div class="card-body p-3">
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
                    <div class="row mb-4" id="attributes-summary">
                        <div class="col-12 text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading attributes...</p>
                        </div>
                    </div>

                    <h6 class="mb-3">Recent Attributes</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="attributes-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Value</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div class="tab-pane fade" id="notifications">
            <div class="info-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">User Notifications</h5>
                        <div id="notifications-summary">
                            <span class="badge bg-info" id="notif-total">0</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table data-table" id="notifications-table">
                            <thead>
                                <tr>
                                    <th width="100">Type</th>
                                    <th>Title</th>
                                    <th width="120">Status</th>
                                    <th width="120">Sent To</th>
                                    <th width="150">Date</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading notifications...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-outline-primary" id="load-more-notifications" style="display: none;">
                            Load More
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Details Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="notificationModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="notificationModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include Modals -->
';

include('user_components/user-details_modals.inc');

// Calculate total time before rendering performance report
$perf_timers['TOTAL_BACKEND'] = round((microtime(true) - $perf_start) * 1000, 2);

// Build performance report
$perf_report_html = '<div class="alert alert-info mt-4" id="perf-report">
    <div class="d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="document.getElementById(\'perf-details\').classList.toggle(\'d-none\')">
        <h5 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Performance Report</h5>
        <span class="badge bg-primary">' . $perf_timers['TOTAL_BACKEND'] . ' ms</span>
    </div>
    <div id="perf-details" class="mt-3 d-none">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Operation</th>
                    <th class="text-end">Time (ms)</th>
                    <th class="text-end">% of Total</th>
                </tr>
            </thead>
            <tbody>';

// Sort timers by time (descending)
arsort($perf_timers);

foreach ($perf_timers as $label => $time) {
    if ($label === 'start' || $label === 'TOTAL_BACKEND') continue;
    $percentage = round(($time / $perf_timers['TOTAL_BACKEND']) * 100, 1);
    $bar_color = 'bg-success';
    if ($percentage > 20) $bar_color = 'bg-warning';
    if ($percentage > 40) $bar_color = 'bg-danger';

    $perf_report_html .= '<tr>
        <td>' . htmlspecialchars($label) . '</td>
        <td class="text-end"><strong>' . $time . '</strong></td>
        <td class="text-end">
            <div class="d-flex align-items-center justify-content-end">
                <span class="me-2">' . $percentage . '%</span>
                <div class="progress" style="width: 100px; height: 8px;">
                    <div class="progress-bar ' . $bar_color . '" style="width: ' . $percentage . '%"></div>
                </div>
            </div>
        </td>
    </tr>';
}

$perf_report_html .= '
            </tbody>
        </table>
        <div class="mt-2 small text-muted">
            <strong>Note:</strong> Times shown are server-side execution only. Click to collapse.
        </div>
    </div>
</div>';

echo $perf_report_html;

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
';
?>
<script>
// Global variables for notification functions
const userId = <?php echo $workingUser; ?>;

// View notification details (global scope for onclick)
function viewNotification(notificationId) {
    const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    const modalBody = document.getElementById('notificationModalBody');
    const modalFooter = document.getElementById('notificationModalFooter');

    // Show loading state
    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';

    // Show modal
    modal.show();

    // Fetch notification details
    fetch(`/admin/ajax/user-notifications.php?user_id=${userId}&offset=0&limit=1000`)
        .then(response => response.json())
        .then(data => {
            const notif = data.notifications.find(n => n.notification_id == notificationId);
            if (notif) {
                // Status badge color
                let statusClass = 'secondary';
                if (notif.status === 'sent') statusClass = 'success';
                else if (notif.status === 'pending') statusClass = 'warning';
                else if (notif.status === 'failed') statusClass = 'danger';
                else if (notif.status === 'read') statusClass = 'info';

                // Sent to badge color (use sent_to_display from backend)
                const sentToDisplay = notif.sent_to_display || notif.sent_to;
                let sentToClass = 'secondary';
                if (sentToDisplay === 'email') sentToClass = 'primary';
                else if (sentToDisplay === 'sms') sentToClass = 'info';
                else if (sentToDisplay === 'display') sentToClass = 'success';
                else if (sentToDisplay === 'both') sentToClass = 'warning';

                // Parse options
                let optionsHTML = '<em class="text-muted">None</em>';
                if (notif.options) {
                    try {
                        const options = JSON.parse(notif.options);
                        optionsHTML = '<pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">' + JSON.stringify(options, null, 2) + '</pre>';
                    } catch (e) {
                        optionsHTML = '<pre class="bg-light p-2 rounded">' + notif.options + '</pre>';
                    }
                }

                // Format dates
                const createDate = new Date(notif.create_dt);
                const sentDate = notif.sent_dt ? new Date(notif.sent_dt) : null;

                // Build action buttons HTML (moved to top)
                let actionsHTML = '<div class="mb-4 pb-3 border-bottom"><h6 class="mb-3">Actions</h6><div class="d-flex gap-2 flex-wrap">';

                // Mark as Read/Unread buttons (only for display notifications)
                if (sentToDisplay === 'display') {
                    if (notif.status !== 'read') {
                        actionsHTML += `<button type="button" class="btn btn-info" onclick="markNotificationAs(${notif.notification_id}, 'read')">
                            <i class="bi bi-check2"></i> Mark as Read
                        </button>`;
                    }
                    if (notif.status === 'read') {
                        actionsHTML += `<button type="button" class="btn btn-warning" onclick="markNotificationAs(${notif.notification_id}, 'unread')">
                            <i class="bi bi-x"></i> Mark as Unread
                        </button>`;
                    }
                }

                // Resend button (for email notifications)
                if (notif.can_resend) {
                    actionsHTML += `<button type="button" class="btn btn-success" onclick="resendNotificationFromModal(${notif.notification_id})">
                        <i class="bi bi-arrow-clockwise"></i> Resend Email
                    </button>`;
                }

                // Status change dropdown (for all notifications)
                actionsHTML += `
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Change Status
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); markNotificationAs(${notif.notification_id}, 'sent')">Mark as Sent</a></li>
                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); markNotificationAs(${notif.notification_id}, 'pending')">Mark as Pending</a></li>
                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); markNotificationAs(${notif.notification_id}, 'failed')">Mark as Failed</a></li>
                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); markNotificationAs(${notif.notification_id}, 'read')">Mark as Read</a></li>
                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); markNotificationAs(${notif.notification_id}, 'unread')">Mark as Unread</a></li>
                        </ul>
                    </div>
                `;

                actionsHTML += '</div></div>';

                // Build collapsible details section
                const detailsHTML = `
                    <div class="accordion" id="notificationDetailsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#basicDetails">
                                    <i class="bi bi-info-circle me-2"></i> Basic Details
                                </button>
                            </h2>
                            <div id="basicDetails" class="accordion-collapse collapse" data-bs-parent="#notificationDetailsAccordion">
                                <div class="accordion-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td width="120"><strong>ID:</strong></td>
                                                <td>${notif.notification_id}</td>
                                                <td width="120"><strong>Type:</strong></td>
                                                <td><span class="badge bg-secondary">${notif.type || 'N/A'}</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Status:</strong></td>
                                                <td><span class="badge bg-${statusClass}">${notif.status || 'N/A'}</span></td>
                                                <td><strong>Sent To:</strong></td>
                                                <td>${notif.recipient_email || sentToDisplay || 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Created:</strong></td>
                                                <td><small>${createDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true})}</small></td>
                                                <td><strong>Sent:</strong></td>
                                                <td><small>${sentDate ? sentDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true}) : '<em class="text-muted">Not sent</em>'}</small></td>
                                            </tr>
                                            ${notif.priority || notif.category ? `
                                            <tr>
                                                ${notif.priority ? `<td><strong>Priority:</strong></td><td>${notif.priority}</td>` : '<td></td><td></td>'}
                                                ${notif.category ? `<td><strong>Category:</strong></td><td>${notif.category}</td>` : '<td></td><td></td>'}
                                            </tr>` : ''}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#messageContent">
                                    <i class="bi bi-envelope me-2"></i> Message Content
                                </button>
                            </h2>
                            <div id="messageContent" class="accordion-collapse collapse show" data-bs-parent="#notificationDetailsAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <strong>Title:</strong>
                                        <p class="mb-0">${notif.title || '<em class="text-muted">No title</em>'}</p>
                                    </div>
                                    <div>
                                        <strong>Message:</strong>
                                        <div class="card mt-2">
                                            <div class="card-body">
                                                ${notif.message || '<em class="text-muted">No message</em>'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#advancedOptions">
                                    <i class="bi bi-code-square me-2"></i> Advanced Options
                                </button>
                            </h2>
                            <div id="advancedOptions" class="accordion-collapse collapse" data-bs-parent="#notificationDetailsAccordion">
                                <div class="accordion-body">
                                    ${optionsHTML}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Build modal body (actions at top, then collapsible details)
                modalBody.innerHTML = actionsHTML + detailsHTML;

                // Simple footer with just close button (actions moved to top)
                modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            } else {
                modalBody.innerHTML = '<div class="alert alert-danger">Notification not found</div>';
            }
        })
        .catch(error => {
            console.error('Error loading notification:', error);
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading notification details</div>';
        });
}

// Mark notification as read/unread (global scope)
function markNotificationAs(notificationId, status) {
    const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
    console.log('Marking notification', notificationId, 'as', status, 'with token:', csrfToken ? 'present' : 'missing');

    fetch('/admin/ajax/notification-actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark_notification&notification_id=${notificationId}&status=${status}&_token=${csrfToken}`
    })
    .then(response => {
        console.log('Response status:', response.status, response.statusText);
        return response.text(); // Get text first to see what we're getting
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed response:', data);
            if (data && data.success === true) {
                // Show success message in modal
                showNotificationAlertInModal(`Notification marked as ${status}!`, 'success');

                // Reload the notification details in the modal to show updated status
                setTimeout(() => {
                    viewNotification(notificationId);
                }, 1500);

                // Reload notifications table in background
                loadNotificationsData(0);
            } else {
                console.error('Backend returned error:', data);
                showNotificationAlertInModal('Error: ' + (data.error || 'Unknown error'), 'danger');
            }
        } catch (e) {
            console.error('JSON parse error:', e, 'Raw text:', text);
            showNotificationAlertInModal('Invalid response from server', 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error, error.message);
        showNotificationAlertInModal('Failed to mark notification. Please try again.', 'danger');
    });
}

// Show dismissible alert inside the modal
function showNotificationAlertInModal(message, type = 'success') {
    const modalBody = document.getElementById('notificationModalBody');

    // Remove any existing alerts in modal
    const existingAlert = modalBody.querySelector('.alert.notification-action-alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show notification-action-alert mb-3`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Add to top of modal body
    modalBody.insertBefore(alertDiv, modalBody.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = bootstrap.Alert.getInstance(alertDiv);
        if (alert) {
            alert.close();
        } else {
            alertDiv.remove();
        }
    }, 5000);
}

// Show dismissible alert for notification actions (outside modal)
function showNotificationAlert(message, type = 'success') {
    // Remove any existing alerts
    const existingAlert = document.getElementById('notification-action-alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.id = 'notification-action-alert';
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Add to page
    document.body.appendChild(alertDiv);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = bootstrap.Alert.getInstance(alertDiv);
        if (alert) {
            alert.close();
        } else {
            alertDiv.remove();
        }
    }, 5000);
}

// Resend notification from modal (global scope)
function resendNotificationFromModal(notificationId) {
    if (!confirm('Are you sure you want to resend this notification?')) {
        return;
    }

    resendNotification(notificationId);

    // Close modal after a delay
    setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('notificationModal'));
        if (modal) modal.hide();
    }, 500);
}

// Resend notification (global scope)
function resendNotification(notificationId) {
    // Get CSRF token from the page
    const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

    // Get button if available
    const btn = event?.target?.closest('button');
    let originalHTML = '';

    if (btn) {
        originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    }

    fetch('/admin/ajax/resend-notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}&_token=${csrfToken}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotificationAlert('Notification resent successfully!', 'success');
            // Reload notifications to show updated status
            loadNotificationsData(0);
        } else {
            showNotificationAlert('Error: ' + (data.error || 'Unknown error'), 'danger');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }
    })
    .catch(error => {
        console.error('Error resending notification:', error);
        showNotificationAlert('Failed to resend notification. Please try again.', 'danger');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });
}

// Load notifications data (global scope)
function loadNotificationsData(offset = 0) {
    const tbody = document.querySelector("#notifications-table tbody");
    const loadMoreBtn = document.getElementById("load-more-notifications");
    const totalBadge = document.getElementById("notif-total");

    if (offset === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary"></div><p class="mt-2">Loading notifications...</p></td></tr>';
    }

    fetch(`/admin/ajax/user-notifications.php?user_id=${userId}&offset=${offset}&limit=50`)
        .then(response => response.json())
        .then(data => {
            if (offset === 0) tbody.innerHTML = '';

            // Update summary badge
            if (data.notifications) {
                totalBadge.textContent = `${data.notifications.length} notifications`;
            }

            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(notif => {
                    // Status badge color
                    let statusClass = 'secondary';
                    if (notif.status === 'sent') statusClass = 'success';
                    else if (notif.status === 'pending') statusClass = 'warning';
                    else if (notif.status === 'failed') statusClass = 'danger';
                    else if (notif.status === 'read') statusClass = 'info';

                    // Sent to badge color (use sent_to_display from backend)
                    const sentToDisplay = notif.sent_to_display || notif.sent_to;
                    let sentToClass = 'secondary';
                    if (sentToDisplay === 'email') sentToClass = 'primary';
                    else if (sentToDisplay === 'sms') sentToClass = 'info';
                    else if (sentToDisplay === 'display') sentToClass = 'success';
                    else if (sentToDisplay === 'both') sentToClass = 'warning';

                    const notifDate = new Date(notif.create_dt);
                    const formattedDate = notifDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true});

                    const row = document.createElement('tr');

                    // Truncate title and message for display
                    const displayTitle = notif.title ? (notif.title.length > 40 ? notif.title.substring(0, 40) + '...' : notif.title) : 'No title';

                    row.innerHTML = `
                        <td><span class="badge bg-secondary">${notif.type || 'N/A'}</span></td>
                        <td>
                            <strong>${displayTitle}</strong>
                            ${notif.message ? '<br><small class="text-muted">' + (notif.message.length > 60 ? notif.message.substring(0, 60) + '...' : notif.message) + '</small>' : ''}
                        </td>
                        <td><span class="badge bg-${statusClass}">${notif.status || 'N/A'}</span></td>
                        <td><span class="badge bg-${sentToClass}">${sentToDisplay || 'N/A'}</span></td>
                        <td><small>${formattedDate}</small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewNotification(${notif.notification_id})"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${notif.can_resend ? `<button class="btn btn-sm btn-outline-success" onclick="resendNotification(${notif.notification_id})"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Resend Notification">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>` : ''}
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Show/hide load more button
                if (data.notifications.length >= 50) {
                    loadMoreBtn.style.display = 'block';
                    loadMoreBtn.onclick = () => loadNotificationsData(offset + 50);
                } else {
                    loadMoreBtn.style.display = 'none';
                }

                // Initialize Bootstrap tooltips for the new buttons
                const tooltipTriggerList = [].slice.call(tbody.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            } else {
                if (offset === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center">No notifications found</td></tr>';
                }
                loadMoreBtn.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading notifications</td></tr>';
        });
}

// Tab switching functionality with lazy loading
document.addEventListener("DOMContentLoaded", function() {
    const tabItems = document.querySelectorAll(".nav-tab-item");
    const tabPanes = document.querySelectorAll(".tab-pane");
    const badgeColors = <?php echo json_encode($badgeColors); ?>;
    const ipColorMap = {};
    let colorIndex = 0;

    // Track which tabs have been loaded
    const loadedTabs = {
        overview: true, // Already loaded
        account: true,  // Static content
        enrollments: false,
        allocations: false,
        activity: false,
        security: true, // Static content mostly
        attributes: false,
        notifications: false
    };

    // Load activity data
    function loadActivityData(offset = 0) {
        const tbody = document.querySelector("#activity-table tbody");
        const loadMoreBtn = document.getElementById("load-more-activity");

        if (offset === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary"></div><p class="mt-2">Loading activity data...</p></td></tr>';
        }

        fetch(`/admin/ajax/user-activity-logs.php?user_id=${userId}&offset=${offset}&limit=50`)
            .then(response => response.json())
            .then(data => {
                if (offset === 0) tbody.innerHTML = '';

                if (data.logs && data.logs.length > 0) {
                    data.logs.forEach(log => {
                        // Assign color to IP
                        if (!ipColorMap[log.ip]) {
                            ipColorMap[log.ip] = badgeColors[colorIndex % badgeColors.length];
                            colorIndex++;
                        }

                        const row = document.createElement('tr');
                        const logDate = new Date(log.create_dt);
                        const formattedDate = logDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true});

                        row.innerHTML = `
                            <td><span class="badge bg-${ipColorMap[log.ip]}">${log.ip}</span></td>
                            <td>${log.name}</td>
                            <td><code>${log.page}</code></td>
                            <td>${formattedDate}</td>
                            <td>
                                <a href="/admin/sessiondetails?id=${log.id}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-search"></i>
                                </a>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Show/hide load more button
                    if (data.logs.length >= 50) {
                        loadMoreBtn.style.display = 'block';
                        loadMoreBtn.onclick = () => loadActivityData(offset + 50);
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                } else {
                    if (offset === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No activity found</td></tr>';
                    }
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading activity:', error);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading activity data</td></tr>';
            });
    }

    // Load enrollments data with statistics
    function loadEnrollmentsData() {
        const tbody = document.querySelector("#enrollments-table tbody");
        const statsSummary = document.getElementById("enrollment-stats-summary");
        const badgeSummary = document.getElementById("enrollment-badge-summary");
        const totalBadge = document.getElementById("total-enrollments-badge");

        tbody.innerHTML = '<tr><td colspan="4" class="text-center"><div class="spinner-border text-primary"></div><p class="mt-2">Loading enrollments...</p></td></tr>';

        fetch(`/admin/ajax/user-enrollments.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                // Update statistics summary
                statsSummary.innerHTML = '';
                if (data.stats && Object.keys(data.stats).length > 0) {
                    // Create stat cards for each status
                    const statusLabels = {
                        'success': { label: 'Successful', color: 'text-success' },
                        'pending': { label: 'Pending', color: 'text-warning' },
                        'failed': { label: 'Failed', color: 'text-danger' },
                        'selected': { label: 'Selected', color: 'text-info' },
                        'removed': { label: 'Removed', color: 'text-secondary' },
                        'active': { label: 'Active', color: 'text-primary' },
                        'testing': { label: 'Testing', color: 'text-info' },
                        'user_owned': { label: 'User Owned', color: 'text-primary' },
                        'existing': { label: 'Existing', color: 'text-secondary' }
                    };

                    Object.entries(data.stats).forEach(([status, count]) => {
                        const statusInfo = statusLabels[status] || { label: status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()), color: 'text-secondary' };
                        const col = document.createElement('div');
                        col.className = 'col-6 col-md-3 col-lg-2';
                        col.innerHTML = `
                            <div class="info-card">
                                <div class="card-body p-3 text-center">
                                    <div class="stat-value ${statusInfo.color}">${count}</div>
                                    <div class="stat-label">${statusInfo.label}</div>
                                </div>
                            </div>
                        `;
                        statsSummary.appendChild(col);
                    });
                } else {
                    statsSummary.innerHTML = '<div class="col-12 text-center text-muted">No enrollment statistics available</div>';
                }

                // Update badge summary
                if (data.total_enrollments) {
                    totalBadge.textContent = `${data.total_enrollments} Total`;

                    // Add badges for key statuses
                    badgeSummary.innerHTML = `
                        <span class="badge bg-secondary">${data.total_enrollments} Total</span>
                        ${data.stats.pending ? `<span class="badge bg-warning">${data.stats.pending} Pending</span>` : ''}
                        ${data.stats.success ? `<span class="badge bg-success">${data.stats.success} Success</span>` : ''}
                        ${data.stats.failed ? `<span class="badge bg-danger">${data.stats.failed} Failed</span>` : ''}
                    `;
                }

                // Update table
                tbody.innerHTML = '';
                if (data.enrollments && data.enrollments.length > 0) {
                    data.enrollments.forEach(enrollment => {
                        let statusClass = 'secondary';
                        if (enrollment.status === 'success') statusClass = 'success';
                        else if (enrollment.status === 'pending') statusClass = 'warning';
                        else if (enrollment.status === 'failed') statusClass = 'danger';
                        else if (enrollment.status === 'active') statusClass = 'primary';
                        else if (enrollment.status === 'selected') statusClass = 'info';

                        const enrollDate = new Date(enrollment.create_dt);
                        const formattedDate = enrollDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <small class="text-muted">EID:</small><br>
                                <code>${enrollment.user_company_id}</code>
                            </td>
                            <td>
                                <strong>${enrollment.company_name}</strong>
                                <small class="text-muted d-block">CID: ${enrollment.company_id}</small>
                            </td>
                            <td>
                                <span class="badge bg-${statusClass}">${enrollment.status.charAt(0).toUpperCase() + enrollment.status.slice(1)}</span>
                            </td>
                            <td>${formattedDate}</td>
                            <td>
                                <a href="/admin/company-editor-main?cid=${enrollment.company_id}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No enrollments found</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading enrollments:', error);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading enrollment data</td></tr>';
                statsSummary.innerHTML = '<div class="col-12 text-center text-danger">Error loading statistics</div>';
            });
    }

    // Load allocations data
    function loadAllocationsData() {
        const statsContainer = document.getElementById("allocation-stats");
        const tbody = document.querySelector("#allocations-table tbody");

        fetch(`/admin/ajax/user-allocations.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                // Update stats summary
                statsContainer.innerHTML = '';
                if (data.balance) {
                    const hasPending = (data.balance.pending_allocations || 0) > 0;
                    const colClass = hasPending ? 'col-6 col-md-4 col-lg' : 'col-6 col-lg-3';

                    // Available balance
                    const availableCol = document.createElement('div');
                    availableCol.className = colClass;
                    availableCol.innerHTML = `
                        <div class="info-card">
                            <div class="card-body p-3 text-center">
                                <div class="stat-value text-primary">${data.balance.available_allocations}</div>
                                <div class="stat-label">Available</div>
                            </div>
                        </div>
                    `;
                    statsContainer.appendChild(availableCol);

                    // Total earned
                    const earnedCol = document.createElement('div');
                    earnedCol.className = colClass;
                    earnedCol.innerHTML = `
                        <div class="info-card">
                            <div class="card-body p-3 text-center">
                                <div class="stat-value text-success">${data.balance.total_earned || 0}</div>
                                <div class="stat-label">Total Earned</div>
                            </div>
                        </div>
                    `;
                    statsContainer.appendChild(earnedCol);

                    // Total used
                    const usedCol = document.createElement('div');
                    usedCol.className = colClass;
                    usedCol.innerHTML = `
                        <div class="info-card">
                            <div class="card-body p-3 text-center">
                                <div class="stat-value text-danger">${data.balance.total_used || 0}</div>
                                <div class="stat-label">Total Used</div>
                            </div>
                        </div>
                    `;
                    statsContainer.appendChild(usedCol);

                    // Transaction count
                    const transactionCol = document.createElement('div');
                    transactionCol.className = colClass;
                    transactionCol.innerHTML = `
                        <div class="info-card">
                            <div class="card-body p-3 text-center">
                                <div class="stat-value text-info">${data.allocations ? data.allocations.length : 0}</div>
                                <div class="stat-label">Transactions</div>
                            </div>
                        </div>
                    `;
                    statsContainer.appendChild(transactionCol);

                    // Pending if exists
                    if (hasPending) {
                        const pendingCol = document.createElement('div');
                        pendingCol.className = colClass;
                        pendingCol.innerHTML = `
                            <div class="info-card">
                                <div class="card-body p-3 text-center">
                                    <div class="stat-value text-warning">${data.balance.pending_allocations}</div>
                                    <div class="stat-label">Pending</div>
                                </div>
                            </div>
                        `;
                        statsContainer.appendChild(pendingCol);
                    }
                }

                // Update table
                tbody.innerHTML = '';
                if (data.allocations && data.allocations.length > 0) {
                    data.allocations.forEach(alloc => {
                        const allocDate = new Date(alloc.created_at);
                        const formattedDate = allocDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});

                        let typeClass = 'bg-primary';
                        if (alloc.allocation_type == 'bonus') typeClass = 'bg-success';
                        else if (alloc.allocation_type == 'referral') typeClass = 'bg-info';

                        let statusClass = 'bg-success';
                        if (alloc.status == 'pending') statusClass = 'bg-warning';
                        else if (alloc.status == 'expired') statusClass = 'bg-secondary';

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${formattedDate}</td>
                            <td><span class="badge ${typeClass}">${alloc.allocation_type ? alloc.allocation_type.charAt(0).toUpperCase() + alloc.allocation_type.slice(1) : 'N/A'}</span></td>
                            <td class="text-center"><span class="allocation-positive">+${alloc.amount}</span></td>
                            <td>
                                ${alloc.allocation_comment || 'N/A'}
                                ${alloc.reference_type ? '<br><small class="text-muted">Ref: ' + alloc.reference_type + '</small>' : ''}
                            </td>
                            <td><span class="badge ${statusClass}">${alloc.status ? alloc.status.charAt(0).toUpperCase() + alloc.status.slice(1) : 'N/A'}</span></td>
                            <td class="text-center">
                                ${alloc.amount - alloc.amount_used}
                                ${alloc.amount_used > 0 ? '<br><small class="text-muted">Used: ' + alloc.amount_used + '</small>' : ''}
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center">No allocation history found</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading allocations:', error);
                statsContainer.innerHTML = '<div class="col-12 text-center text-danger">Error loading allocation data</div>';
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading allocation data</td></tr>';
            });
    }

    // Load attributes data
    function loadAttributesData() {
        const summary = document.getElementById("attributes-summary");
        const tbody = document.querySelector("#attributes-table tbody");

        fetch(`/admin/ajax/user-attributes.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                // Update summary
                summary.innerHTML = '';
                if (data.summary && data.summary.length > 0) {
                    data.summary.forEach(attr => {
                        const col = document.createElement('div');
                        col.className = 'col-md-2 mb-3';
                        col.innerHTML = `
                            <div class="text-center">
                                <h3 class="mb-1">${attr.count}</h3>
                                <small class="text-muted">${attr.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</small>
                            </div>
                        `;
                        summary.appendChild(col);
                    });
                } else {
                    summary.innerHTML = '<div class="col-12 text-center text-muted">No attribute summary available</div>';
                }

                // Update table
                tbody.innerHTML = '';
                if (data.recent && data.recent.length > 0) {
                    data.recent.forEach(attr => {
                        const attrDate = new Date(attr.create_dt);
                        const formattedDate = attrDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
                        const value = attr.value || attr.description || '';
                        const displayValue = value.length > 50 ? value.substring(0, 50) + '...' : value;

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td><span class="badge bg-secondary">${attr.type}</span></td>
                            <td>${attr.name}</td>
                            <td>${displayValue}</td>
                            <td>${formattedDate}</td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">No recent attributes found</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading attributes:', error);
                summary.innerHTML = '<div class="col-12 text-center text-danger">Error loading attributes</div>';
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading attributes data</td></tr>';
            });
    }

    // Tab switching with lazy loading
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

                // Lazy load data if not already loaded
                if (!loadedTabs[targetId]) {
                    loadedTabs[targetId] = true;

                    if (targetId === 'activity') {
                        loadActivityData();
                    } else if (targetId === 'enrollments') {
                        loadEnrollmentsData();
                    } else if (targetId === 'allocations') {
                        loadAllocationsData();
                    } else if (targetId === 'attributes') {
                        loadAttributesData();
                    } else if (targetId === 'notifications') {
                        loadNotificationsData();
                    }
                }
            }
        });
    });

    // Frontend performance monitoring
    window.addEventListener('load', function() {
        const perfData = performance.timing;
        const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
        const domReady = perfData.domContentLoadedEventEnd - perfData.navigationStart;
        const backendTime = <?php echo $perf_timers['TOTAL_BACKEND']; ?>;

        // Add frontend timing to performance report
        const perfReport = document.getElementById('perf-details');
        if (perfReport) {
            const frontendHTML = `
                <hr>
                <h6 class="mt-3">Frontend Performance</h6>
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        <tr>
                            <td>Backend Processing</td>
                            <td class="text-end"><strong>${backendTime} ms</strong></td>
                        </tr>
                        <tr>
                            <td>DOM Ready</td>
                            <td class="text-end"><strong>${domReady} ms</strong></td>
                        </tr>
                        <tr>
                            <td>Full Page Load</td>
                            <td class="text-end"><strong>${pageLoadTime} ms</strong></td>
                        </tr>
                        <tr>
                            <td>Network + Frontend</td>
                            <td class="text-end"><strong>${pageLoadTime - backendTime} ms</strong></td>
                        </tr>
                    </tbody>
                </table>
            `;
            perfReport.insertAdjacentHTML('beforeend', frontendHTML);
        }

        // Log to console for easy debugging
        console.log('=== USER DETAILS PAGE PERFORMANCE ===');
        console.log('Backend:', backendTime + 'ms');
        console.log('DOM Ready:', domReady + 'ms');
        console.log('Full Load:', pageLoadTime + 'ms');
        console.log('Network + Frontend:', (pageLoadTime - backendTime) + 'ms');
    });
});
</script>
<?php
$display_footertype = 'min';

$perf_checkpoint = microtime(true);
include($dir['core_components'] . '/bg_footer.inc');
$perf_timers['bg_footer'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

$perf_checkpoint = microtime(true);
$app->outputpage();
$perf_timers['outputpage'] = round((microtime(true) - $perf_checkpoint) * 1000, 2);

// Log performance summary to error log
$perf_total = round((microtime(true) - $perf_start) * 1000, 2);
$slow_operations = [];
foreach ($perf_timers as $label => $time) {
    if ($label === 'start' || $label === 'TOTAL_BACKEND') continue;
    if ($time > 50) { // Only log operations taking more than 50ms
        $slow_operations[] = "$label={$time}ms";
    }
}
$perf_summary = "USER-DETAILS-V2 [User $workingUser]: Total={$perf_total}ms";
if (!empty($slow_operations)) {
    $perf_summary .= " | SLOW: " . implode(', ', $slow_operations);
}
error_log($perf_summary);