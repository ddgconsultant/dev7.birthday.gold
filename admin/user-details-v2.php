<?php
$addClasses[] = 'Referral';
$addClasses[] = 'allocationmanager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES AND SETUP
#-------------------------------------------------------------------------------
$p_displaylength = 30;
$badgeColors = ['success', 'warning', 'primary', 'danger', 'info', 'secondary', 'dark'];
$colorIndex = 0;
$ipColorMap = [];

#-------------------------------------------------------------------------------
# GET USER DATA
#-------------------------------------------------------------------------------
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

#-------------------------------------------------------------------------------
# GET ADDITIONAL USER DATA
#-------------------------------------------------------------------------------
// Get user's current allocation balance
$allocation_balance = $allocationmanager->getUserBalance($workingUser);

// Get recent enrollments
$recent_enrollments_sql = "SELECT uc.*, c.company_name, c.company_id
                          FROM bg_user_companies uc
                          JOIN bg_companies c ON uc.company_id = c.company_id
                          WHERE uc.user_id = :user_id
                          ORDER BY uc.create_dt DESC
                          LIMIT 5";
$recent_enrollments = $database->getrows($recent_enrollments_sql, ['user_id' => $workingUser]);

// Get recent logins
$recent_logins_sql = "SELECT * FROM bg_user_login_history 
                     WHERE user_id = :user_id 
                     ORDER BY login_time DESC 
                     LIMIT 5";
$recent_logins = $database->getrows($recent_logins_sql, ['user_id' => $workingUser]);

// Get user attributes summary
$attributes_sql = "SELECT type, COUNT(*) as count 
                  FROM bg_user_attributes 
                  WHERE user_id = :user_id AND status = 'active' 
                  GROUP BY type";
$user_attributes = $database->getrows($attributes_sql, ['user_id' => $workingUser]);

// Get notification preferences
$notification_prefs = $database->getrow(
    "SELECT * FROM bg_user_notification_preferences WHERE user_id = :user_id",
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

#-------------------------------------------------------------------------------
# SETUP REFERER DATA
#-------------------------------------------------------------------------------
$referer = $referral->getreferer($workinguserdata['user_id']);
$refererbuttontitle = 'Add Referer';
$refereraction = 'add';

if (!empty($referer)) {
    $refererbuttontitle = 'Change Referer';
    $refereraction = 'change';
}

#-------------------------------------------------------------------------------
# PAGE STYLES
#-------------------------------------------------------------------------------
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

/* Tab Navigation */
.nav-tabs-custom {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
}

.nav-tabs-custom .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 1rem 1.5rem;
    font-weight: 500;
    border-radius: 0;
}

.nav-tabs-custom .nav-link:hover {
    color: #495057;
    border-color: transparent;
}

.nav-tabs-custom .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
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
    padding-left: 2rem;
}

.timeline::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item::before {
    content: "";
    position: absolute;
    left: -2rem;
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
    
    .nav-tabs-custom .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .user-avatar-large {
        width: 80px;
        height: 80px;
    }
}
</style>';

// Add Bootstrap Icons
$additionalheaders = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">';

#-------------------------------------------------------------------------------
# START PAGE OUTPUT
#-------------------------------------------------------------------------------
$transferpagedata = $system->startpostpage();

$header_flush = true;
$pagetitle = "User Details - " . $workinguserdata['first_name'] . " " . $workinguserdata['last_name'];
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Admin Header Section -->
<div class="content-header-admin">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-2">User Details</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                        <li class="breadcrumb-item"><a href="/admin/user-list-v2">Users</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($workinguserdata['first_name'] . ' ' . $workinguserdata['last_name']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="/admin/user-list-v2" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Users
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container my-4">
    <!-- User Header Card -->
    <div class="user-header-card">
        <div class="row align-items-center">
            <div class="col-auto">
                <img src="<?php echo htmlspecialchars($workinguserdata['avatar']); ?>" 
                     alt="User Avatar" 
                     class="user-avatar-large"
                     data-bs-toggle="modal" 
                     data-bs-target="#avatarModal">
            </div>
            <div class="col">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h2 class="mb-1">
                            <?php echo htmlspecialchars($workinguserdata['first_name'] . ' ' . $workinguserdata['last_name']); ?>
                            <?php if ($workinguserdata['account_admin'] == 'Y'): ?>
                                <span class="badge bg-danger ms-2">Admin</span>
                            <?php endif; ?>
                            <?php if ($account->isstaff('*', $workinguserdata['user_id'])): ?>
                                <span class="badge bg-success ms-2">Staff</span>
                            <?php endif; ?>
                        </h2>
                        <p class="text-muted mb-2">
                            <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($workinguserdata['email']); ?>
                            <span class="mx-2">·</span>
                            <i class="bi bi-person me-2"></i>@<?php echo htmlspecialchars($workinguserdata['username']); ?>
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <span class="status-indicator <?php echo $workinguserdata['status']; ?>">
                                <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                <?php echo ucfirst($workinguserdata['status']); ?>
                            </span>
                            <small class="text-muted">
                                <i class="bi bi-calendar-plus me-1"></i>
                                Joined <?php echo date('M j, Y', strtotime($workinguserdata['create_dt'])); ?>
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-hash me-1"></i>
                                ID: <?php echo $workinguserdata['user_id']; ?>
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
                                    <i class="bi bi-people-fill me-2"></i><?php echo $refererbuttontitle; ?></a></li>
                                <?php if ($account->isadmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#employeeModal">
                                    <i class="bi bi-person-badge me-2"></i>Set Staff</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adminModal">
                                    <i class="bi bi-shield-lock me-2"></i>Set Admin Role</a></li>
                                <?php if ($workinguserdata['status'] != 'pending' && $workinguserdata['status'] != 'deleted'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="/myaccount/myaccount_actions/switch2user?id=<?php echo $qik->encodeId($workinguserdata['user_id']); ?>&aid=<?php echo $qik->encodeId($current_user_data['user_id']); ?>&_token=<?php echo $display->inputcsrf_token('tokenonly'); ?>" class="dropdown-item">
                                    <i class="bi bi-person-arrows me-2"></i>Impersonate User</a></li>
                                <?php endif; ?>
                                <?php endif; ?>
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
                    <div class="stat-value text-primary"><?php echo $businessoutput['counts']['success']; ?></div>
                    <div class="stat-label">Enrollments</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value text-success"><?php echo $allocation_balance['available_allocations']; ?></div>
                    <div class="stat-label">Available Allocations</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value text-info"><?php echo $profilecompletion['required_percentage']; ?>%</div>
                    <div class="stat-label">Profile Complete</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value text-warning"><?php echo count($recent_logins); ?></div>
                    <div class="stat-label">Recent Logins</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#overview">
                <i class="bi bi-speedometer2 me-2"></i>Overview
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#account">
                <i class="bi bi-person-vcard me-2"></i>Account
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#enrollments">
                <i class="bi bi-gift me-2"></i>Enrollments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#activity">
                <i class="bi bi-activity me-2"></i>Activity
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#security">
                <i class="bi bi-shield-lock me-2"></i>Security
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#attributes">
                <i class="bi bi-tags me-2"></i>Attributes
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview">
            <div class="row">
                <!-- Personal Information -->
                <div class="col-lg-6">
                    <div class="info-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="card-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-person"></i>
                                </div>
                                <h5 class="mb-0 ms-3">Personal Information</h5>
                            </div>
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">Birthday</td>
                                    <td class="text-end">
                                        <?php echo htmlspecialchars($workinguserdata['birthdate']); ?>
                                        <small class="text-muted d-block"><?php echo $alive['years']; ?> years old</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Zodiac Sign</td>
                                    <td class="text-end"><?php echo htmlspecialchars($user_astrosigndetails['name']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Location</td>
                                    <td class="text-end"><?php echo htmlspecialchars($location); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone</td>
                                    <td class="text-end"><?php echo htmlspecialchars($workinguserdata['phone_number'] ?? 'Not set'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Gender</td>
                                    <td class="text-end"><?php echo htmlspecialchars($workinguserdata['gender'] ?? 'Not specified'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Account Summary -->
                <div class="col-lg-6">
                    <div class="info-card">
                        <div class="card-body">
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
                                        <strong><?php echo htmlspecialchars($user_planddetails['displayname'] ?? 'Free'); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Account Type</td>
                                    <td class="text-end"><?php echo ucfirst($workinguserdata['account_type']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Allocations</td>
                                    <td class="text-end">
                                        <span class="badge bg-success"><?php echo $allocation_balance['available_allocations']; ?> Available</span>
                                        <?php if ($allocation_balance['pending_allocations'] > 0): ?>
                                        <span class="badge bg-warning"><?php echo $allocation_balance['pending_allocations']; ?> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Feature Email</td>
                                    <td class="text-end"><?php echo htmlspecialchars($workinguserdata['feature_email'] ?? 'Not set'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Referrer</td>
                                    <td class="text-end">
                                        <?php if (!empty($referer)): ?>
                                            <a href="/admin/user-details?u=<?php echo $qik->encodeId($referer['referrer_id']); ?>">
                                                <?php echo htmlspecialchars($referer['referrer_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Timeline -->
                <div class="col-12 mt-4">
                    <div class="info-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <div class="card-icon bg-info bg-opacity-10 text-info">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <h5 class="mb-0 ms-3">Recent Activity</h5>
                            </div>
                            <div class="timeline">
                                <?php
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
                                        'date' => $login['login_time'],
                                        'title' => 'Logged in from ' . $login['ip_address'],
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
                                
                                foreach ($activities as $activity):
                                ?>
                                <div class="timeline-item <?php echo $activity['status']; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <i class="<?php echo $activity['icon']; ?> me-2"></i>
                                            <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M j, g:i A', strtotime($activity['date'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
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
                        <div class="card-body">
                            <h5 class="mb-3">Account Details</h5>
                            <table class="table">
                                <tr>
                                    <td width="200"><strong>User ID</strong></td>
                                    <td><?php echo $workinguserdata['user_id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Username</strong></td>
                                    <td><?php echo htmlspecialchars($workinguserdata['username']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email</strong></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($workinguserdata['email']); ?>">
                                            <?php echo htmlspecialchars($workinguserdata['email']); ?>
                                        </a>
                                        <?php if ($workinguserdata['email_verified'] == 'Y'): ?>
                                            <span class="badge bg-success ms-2">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning ms-2">Unverified</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Account Status</strong></td>
                                    <td>
                                        <span class="status-indicator <?php echo $workinguserdata['status']; ?>">
                                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                            <?php echo ucfirst($workinguserdata['status']); ?>
                                        </span>
                                        <?php if ($workinguserdata['status'] == 'pending'): ?>
                                            <?php
                                            $validatedata = [
                                                'rawdata' => $workinguserdata['email'],
                                                'user_id' => $workinguserdata['user_id'],
                                                'sendcount' => 0,
                                                'action' => 'getlatest'
                                            ];
                                            $validationcodes = $app->getvalidationcodes($validatedata);
                                            if (!empty($validationcodes['mini'])):
                                            ?>
                                            <span class="ms-2 small text-muted">
                                                Code: <code class="user-select-all"><?php echo htmlspecialchars($validationcodes['mini']); ?></code>
                                            </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Account Type</strong></td>
                                    <td><?php echo ucfirst($workinguserdata['account_type']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Plan</strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($user_planddetails['displayname'] ?? 'Free'); ?>
                                        <small class="text-muted">(ID: <?php echo $workinguserdata['account_product_id']; ?>)</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Created</strong></td>
                                    <td><?php echo date('F j, Y g:i A', strtotime($workinguserdata['create_dt'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Last Modified</strong></td>
                                    <td><?php echo date('F j, Y g:i A', strtotime($workinguserdata['modify_dt'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="info-card">
                        <div class="card-body">
                            <h5 class="mb-3">Roles & Permissions</h5>
                            <div class="mb-3">
                                <?php if ($workinguserdata['account_admin'] == 'Y'): ?>
                                    <span class="badge bg-danger d-inline-block mb-2">
                                        <i class="bi bi-shield-lock me-1"></i>Administrator
                                    </span>
                                <?php endif; ?>
                                <?php if ($account->isstaff('*', $workinguserdata['user_id'])): ?>
                                    <span class="badge bg-success d-inline-block mb-2">
                                        <i class="bi bi-person-badge me-1"></i>Staff Member
                                    </span>
                                <?php endif; ?>
                                <?php if ($account->isbrandowner('*', $workinguserdata['user_id'])): ?>
                                    <span class="badge bg-primary d-inline-block mb-2">
                                        <i class="bi bi-building me-1"></i>Brand Owner
                                    </span>
                                <?php endif; ?>
                                <?php if ($account->iscconsultant($workinguserdata['user_id'])): ?>
                                    <span class="badge bg-info d-inline-block mb-2">
                                        <i class="bi bi-briefcase me-1"></i>Consultant
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <h6 class="mt-4 mb-3">Quick Actions</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#accounttypeplanModal">
                                    <i class="bi bi-gear me-2"></i>Change Plan
                                </button>
                                <?php if ($account->isadmin()): ?>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#adminModal">
                                    <i class="bi bi-shield me-2"></i>Admin Settings
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrollments Tab -->
        <div class="tab-pane fade" id="enrollments">
            <div class="info-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Enrollment History</h5>
                        <div>
                            <span class="badge bg-primary"><?php echo $businessoutput['counts']['remaining']; ?> Available</span>
                            <span class="badge bg-warning"><?php echo $businessoutput['counts']['pending']; ?> Pending</span>
                            <span class="badge bg-success"><?php echo $businessoutput['counts']['success']; ?> Successful</span>
                            <span class="badge bg-danger"><?php echo $businessoutput['counts']['failed']; ?> Failed</span>
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
                            <tbody>
                                <?php
                                $enrollment_sql = "SELECT uc.*, c.company_name 
                                                  FROM bg_user_companies uc
                                                  JOIN bg_companies c ON uc.company_id = c.company_id
                                                  WHERE uc.user_id = :user_id
                                                  ORDER BY uc.create_dt DESC
                                                  LIMIT 50";
                                $enrollments = $database->getrows($enrollment_sql, ['user_id' => $workingUser]);
                                
                                foreach ($enrollments as $enrollment):
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($enrollment['company_name']); ?></strong>
                                        <small class="text-muted d-block">ID: <?php echo $enrollment['company_id']; ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = 'secondary';
                                        if ($enrollment['status'] == 'success') $status_class = 'success';
                                        elseif ($enrollment['status'] == 'pending') $status_class = 'warning';
                                        elseif ($enrollment['status'] == 'failed') $status_class = 'danger';
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($enrollment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($enrollment['create_dt'])); ?></td>
                                    <td>
                                        <a href="/admin/company-details?id=<?php echo $enrollment['company_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Tab -->
        <div class="tab-pane fade" id="activity">
            <div class="info-card">
                <div class="card-body">
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
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM bg_sessiontracking 
                                        WHERE user_id = ? " . 
                                        ($mode != 'dev' ? "AND site = 'www'" : "AND type = 'user'") . 
                                        " ORDER BY create_dt DESC LIMIT 100";
                                
                                $stmt = $database->prepare($sql);
                                $stmt->execute([$workinguserdata['user_id']]);
                                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($logs as $log):
                                    if (!isset($ipColorMap[$log['ip']])) {
                                        $ipColorMap[$log['ip']] = $badgeColors[$colorIndex % count($badgeColors)];
                                        $colorIndex++;
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php echo $ipColorMap[$log['ip']]; ?>">
                                            <?php echo htmlspecialchars($log['ip']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($log['page']); ?></code></td>
                                    <td><?php echo date('M j, g:i A', strtotime($log['create_dt'])); ?></td>
                                    <td>
                                        <a href="/admin/sessiondetails?id=<?php echo $log['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-search"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
                        <div class="card-body">
                            <h5 class="mb-3">Security Settings</h5>
                            <table class="table">
                                <tr>
                                    <td>Two-Factor Authentication</td>
                                    <td class="text-end">
                                        <?php
                                        $has_2fa = $database->query("SELECT COUNT(*) FROM bg_user_attributes WHERE user_id = {$workingUser} AND type = 'security' AND name = '2fa' AND status = 'active'")->fetchColumn() > 0;
                                        ?>
                                        <span class="security-badge <?php echo $has_2fa ? 'enabled' : 'disabled'; ?>">
                                            <i class="bi bi-<?php echo $has_2fa ? 'check' : 'x'; ?>-circle"></i>
                                            <?php echo $has_2fa ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Security Questions</td>
                                    <td class="text-end">
                                        <?php
                                        $has_sq = $database->query("SELECT COUNT(*) FROM bg_user_attributes WHERE user_id = {$workingUser} AND type = 'security' AND name = 'security_questions' AND status = 'active'")->fetchColumn() > 0;
                                        ?>
                                        <span class="security-badge <?php echo $has_sq ? 'enabled' : 'disabled'; ?>">
                                            <i class="bi bi-<?php echo $has_sq ? 'check' : 'x'; ?>-circle"></i>
                                            <?php echo $has_sq ? 'Set' : 'Not Set'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Email Verified</td>
                                    <td class="text-end">
                                        <span class="security-badge <?php echo $workinguserdata['email_verified'] == 'Y' ? 'enabled' : 'disabled'; ?>">
                                            <i class="bi bi-<?php echo $workinguserdata['email_verified'] == 'Y' ? 'check' : 'x'; ?>-circle"></i>
                                            <?php echo $workinguserdata['email_verified'] == 'Y' ? 'Verified' : 'Unverified'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Password Last Changed</td>
                                    <td class="text-end">
                                        <?php
                                        $pwd_change = $database->getrow("SELECT modify_dt FROM bg_user_attributes WHERE user_id = :user_id AND type = 'security' AND name = 'password_changed' ORDER BY modify_dt DESC LIMIT 1", ['user_id' => $workingUser]);
                                        echo $pwd_change ? date('M j, Y', strtotime($pwd_change['modify_dt'])) : 'Never';
                                        ?>
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
                                    <tbody>
                                        <?php foreach ($recent_logins as $login): ?>
                                        <tr>
                                            <td><?php echo date('M j, g:i A', strtotime($login['login_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                                            <td><?php echo htmlspecialchars($login['user_agent'] ?? 'Unknown'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
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
                <div class="card-body">
                    <h5 class="mb-4">User Attributes</h5>
                    <div class="row mb-4">
                        <?php foreach ($user_attributes as $attr): ?>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h3 class="mb-1"><?php echo $attr['count']; ?></h3>
                                <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $attr['type'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
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
                            <tbody>
                                <?php
                                $recent_attrs = $database->getrows(
                                    "SELECT * FROM bg_user_attributes 
                                     WHERE user_id = :user_id 
                                     ORDER BY create_dt DESC 
                                     LIMIT 20",
                                    ['user_id' => $workingUser]
                                );
                                foreach ($recent_attrs as $attr):
                                ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($attr['type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($attr['name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($attr['value'] ?? $attr['description'], 0, 50)); ?>...</td>
                                    <td><?php echo date('M j', strtotime($attr['create_dt'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Modals -->
<?php include('user_components/user-details_modals.inc'); ?>

<!-- Avatar Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="<?php echo htmlspecialchars($workinguserdata['avatar']); ?>" 
                     alt="User Avatar" 
                     class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>