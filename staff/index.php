<?php
# Staff Portal - Main Dashboard
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Staff-only access is already handled by site-controller.php
// Only staff and admin users can access /staff/ pages

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$pagetitle = 'Staff Dashboard';
$staff_user = $current_user_data;

// Get staff member's role and permissions
$staff_role = $staff_user['user_role'] ?? 'staff';
$is_admin = $account->isadmin();
$is_manager = in_array($staff_role, ['manager', 'admin', 'supervisor']);

// Get staff statistics
$stats = [];

// Total users managed (if applicable)
if ($is_manager) {
    $sql = "SELECT COUNT(*) as total FROM bg_users WHERE status = 'active'";
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $sql = "SELECT COUNT(*) as total FROM bg_users WHERE DATE(create_dt) = CURDATE()";
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $stats['new_users_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Get staff member's recent activity
$sql = "SELECT * FROM bg_timeclock 
        WHERE user_id = :user_id 
        ORDER BY clock_in DESC 
        LIMIT 5";
$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $staff_user['user_id']]);
$recent_timeclock = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current clock status
$sql = "SELECT * FROM bg_timeclock 
        WHERE user_id = :user_id 
        AND clock_out IS NULL 
        ORDER BY clock_in DESC 
        LIMIT 1";
$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $staff_user['user_id']]);
$current_clock = $stmt->fetch(PDO::FETCH_ASSOC);
$is_clocked_in = !empty($current_clock);

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'clock_in':
            if (!$is_clocked_in) {
                $sql = "INSERT INTO bg_timeclock (user_id, clock_in, create_dt) 
                        VALUES (:user_id, NOW(), NOW())";
                $database->query($sql, ['user_id' => $staff_user['user_id']]);
                $system->addmessage('success', 'Successfully clocked in');
                header('Location: /staff/');
                exit;
            }
            break;
            
        case 'clock_out':
            if ($is_clocked_in) {
                $sql = "UPDATE bg_timeclock 
                        SET clock_out = NOW(), modify_dt = NOW() 
                        WHERE entry_id = :id";
                $database->query($sql, ['id' => $current_clock['entry_id']]);
                $system->addmessage('success', 'Successfully clocked out');
                header('Location: /staff/');
                exit;
            }
            break;
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
$additionalheaders = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">';
$additionalstyles = '
    <style>
    /* Hide skip to main content link unless focused */
    .sr-only, .sr-only-focusable:not(:focus) {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0,0,0,0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }
    .staff-card {
        border-left: 4px solid var(--bs-primary);
        transition: transform 0.2s;
    }
    .staff-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .clock-status {
        font-size: 1.2rem;
        font-weight: 600;
    }
    .clock-in { color: var(--bs-success); }
    .clock-out { color: var(--bs-danger); }
    .stat-card {
        text-align: center;
        padding: 1.5rem;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
    }
    .quick-link {
        padding: 1rem;
        text-decoration: none;
        color: inherit;
        background: white;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .quick-link:hover {
        background: white;
        border-color: var(--bs-primary);
        color: var(--bs-primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    .quick-link:hover .bi {
        color: var(--bs-primary);
    }
    </style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Output the CSS for screen reader elements
echo $additionalstyles;

echo '
<!-- Staff Header Section -->
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-speedometer2"></i> Staff Dashboard</h1>
        <p class="lead">Welcome back, ' . htmlspecialchars($staff_user['profile_first_name'] ?? $staff_user['profile_username'] ?? 'Staff Member') . '</p>
    </div>
</div>

<div class="container mt-4">';

// Clock In/Out Section
echo '
    <div class="row mb-4">
        <div class="col-lg-4">
            <div class="card staff-card">
                <div class="card-body">
                    <h5 class="card-title">Time Clock</h5>';
                    
if ($is_clocked_in) {
    $clock_time = new DateTime($current_clock['clock_in']);
    $now = new DateTime();
    $interval = $clock_time->diff($now);
    
    echo '
                    <p class="clock-status clock-in">Currently Clocked In</p>
                    <p class="text-muted">Since: ' . $clock_time->format('g:i A') . '</p>
                    <p class="text-muted">Duration: ' . $interval->format('%h hours %i minutes') . '</p>
                    <form method="POST">
                        ' . $display->input_csrftoken() . '
                        <input type="hidden" name="action" value="clock_out">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-stop-circle"></i> Clock Out
                        </button>
                    </form>';
} else {
    echo '
                    <p class="clock-status clock-out">Not Clocked In</p>
                    <form method="POST">
                        ' . $display->input_csrftoken() . '
                        <input type="hidden" name="action" value="clock_in">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-play-circle"></i> Clock In
                        </button>
                    </form>';
}

echo '
                </div>
            </div>
        </div>';

// Staff Profile Card
echo '
        <div class="col-lg-4">
            <div class="card staff-card">
                <div class="card-body">
                    <h5 class="card-title">My Profile</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Employee ID:</dt>
                        <dd class="col-sm-7">' . htmlspecialchars($staff_user['user_id']) . '</dd>
                        
                        <dt class="col-sm-5">Department:</dt>
                        <dd class="col-sm-7">' . htmlspecialchars($staff_user['user_department'] ?? 'General') . '</dd>
                        
                        <dt class="col-sm-5">Role:</dt>
                        <dd class="col-sm-7">' . htmlspecialchars(ucfirst($staff_role)) . '</dd>
                        
                        <dt class="col-sm-5">Email:</dt>
                        <dd class="col-sm-7">' . htmlspecialchars($staff_user['profile_email'] ?? 'Not provided') . '</dd>
                    </dl>
                    <a href="/myaccount/profile" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>';

// HR Information Card
echo '
        <div class="col-lg-4">
            <div class="card staff-card">
                <div class="card-body">
                    <h5 class="card-title">HR Information</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Start Date:</dt>
                        <dd class="col-sm-7">' . date('M d, Y', strtotime($staff_user['create_dt'] ?? 'now')) . '</dd>
                        
                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7"><span class="badge bg-success">Active</span></dd>
                        
                        <dt class="col-sm-5">Vacation Days:</dt>
                        <dd class="col-sm-7">' . ($staff_user['vacation_days'] ?? '10') . ' remaining</dd>
                        
                        <dt class="col-sm-5">Next Review:</dt>
                        <dd class="col-sm-7">' . date('M Y', strtotime('+6 months')) . '</dd>
                    </dl>
                    <a href="/staff/hr-details" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-file-earmark-text"></i> View Details
                    </a>
                </div>
            </div>
        </div>
    </div>';

// Statistics Section (for managers)
if ($is_manager) {
    echo '
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Management Overview</h4>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number">' . number_format($stats['total_users']) . '</div>
                <div>Total Users</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="stat-number">' . number_format($stats['new_users_today']) . '</div>
                <div>New Today</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="stat-number">';
                
    $sql = "SELECT COUNT(*) as total FROM bg_companies WHERE status = 'active'";
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $companies_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo number_format($companies_count);
    
    echo '</div>
                <div>Active Companies</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="stat-number">';
                
    $sql = "SELECT COUNT(*) as total FROM bg_user_enrollments WHERE status = 'success'";
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $enrollments_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo number_format($enrollments_count);
    
    echo '</div>
                <div>Enrollments</div>
            </div>
        </div>
    </div>';
}

// Quick Links Section
echo '
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Quick Links</h4>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/timecards" class="quick-link d-block">
                <i class="bi bi-clock-history fs-3 mb-2"></i>
                <h6>Timecards</h6>
                <small class="text-muted">View time history</small>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/training_sales" class="quick-link d-block">
                <i class="bi bi-mortarboard fs-3 mb-2"></i>
                <h6>Training</h6>
                <small class="text-muted">Sales training</small>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/ccdashboard" class="quick-link d-block">
                <i class="bi bi-headset fs-3 mb-2"></i>
                <h6>Call Center</h6>
                <small class="text-muted">Dashboard</small>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/admin/" class="quick-link d-block">
                <i class="bi bi-gear fs-3 mb-2"></i>
                <h6>Admin Panel</h6>
                <small class="text-muted">System settings</small>
            </a>
        </div>
    </div>
    
    <!-- Additional Staff Tools -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/newsletter-list.php" class="quick-link d-block">
                <i class="bi bi-envelope fs-3 mb-2"></i>
                <h6>Newsletter System</h6>
                <small class="text-muted">Email campaigns</small>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/marketing-campaigns.php" class="quick-link d-block">
                <i class="bi bi-megaphone fs-3 mb-2"></i>
                <h6>Marketing Manager</h6>
                <small class="text-muted">Campaign management</small>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/redirect_legalpolicyeditor" class="quick-link d-block">
                <i class="bi bi-file-text fs-3 mb-2"></i>
                <h6>Legal Policies</h6>
                <small class="text-muted">Review & update policies</small>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/it-support" class="quick-link d-block">
                <i class="bi bi-headset fs-3 mb-2"></i>
                <h6>IT Support</h6>
                <small class="text-muted">Help & documentation</small>
            </a>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/ticket-manager" class="quick-link d-block">
                <i class="bi bi-list-check fs-3 mb-2"></i>
                <h6>Ticket Manager</h6>
                <small class="text-muted">View all tickets</small>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/birthday-viewer" class="quick-link d-block">
                <i class="bi bi-cake fs-3 mb-2"></i>
                <h6>Birthday Viewer</h6>
                <small class="text-muted">Staff birthdays</small>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/staff/personality-test.php" class="quick-link d-block">
                <i class="bi bi-palette fs-3 mb-2" style="color: #667eea;"></i>
                <h6>Personality Test</h6>
                <small class="text-muted">Discover your work style</small>
            </a>
        </div>
    </div>';

// Recent Activity Section
if (!empty($recent_timeclock)) {
    echo '
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Time Clock Activity</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Clock In</th>
                                    <th>Clock Out</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    foreach ($recent_timeclock as $entry) {
        $clock_in = new DateTime($entry['clock_in']);
        $clock_out = $entry['clock_out'] ? new DateTime($entry['clock_out']) : null;
        $duration = $clock_out ? $clock_in->diff($clock_out)->format('%h hrs %i min') : 'In Progress';
        $status = $clock_out ? '<span class="badge bg-success">Complete</span>' : '<span class="badge bg-warning">Active</span>';
        
        echo '
                                <tr>
                                    <td>' . $clock_in->format('M d, Y') . '</td>
                                    <td>' . $clock_in->format('g:i A') . '</td>
                                    <td>' . ($clock_out ? $clock_out->format('g:i A') : '-') . '</td>
                                    <td>' . $duration . '</td>
                                    <td>' . $status . '</td>
                                </tr>';
    }
    
    echo '
                            </tbody>
                        </table>
                    </div>
                    <a href="/staff/timecards" class="btn btn-sm btn-outline-primary">View All Timecards</a>
                </div>
            </div>
        </div>
    </div>';
}

// Tools and Resources Section
echo '
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Staff Tools</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/staff/newsletter-list.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-envelope me-2"></i> Newsletter System
                        </a>
                        <a href="/staff/companylogos" class="list-group-item list-group-item-action">
                            <i class="bi bi-image me-2"></i> Company Logos Management
                        </a>
                        <a href="/staff/systemlinks" class="list-group-item list-group-item-action">
                            <i class="bi bi-link-45deg me-2"></i> System Links
                        </a>
                        <a href="/admin/bgreb_v3/enrollment-listv2" class="list-group-item list-group-item-action">
                            <i class="bi bi-people me-2"></i> Enrollment Management
                        </a>
                        <a href="/admin/business-submissions" class="list-group-item list-group-item-action">
                            <i class="bi bi-building me-2"></i> Business Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Resources</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="https://docs.birthdaygold.cloud" class="list-group-item list-group-item-action" target="_blank">
                            <i class="bi bi-book me-2"></i> Documentation
                            <i class="bi bi-box-arrow-up-right float-end"></i>
                        </a>
                        <a href="/staff/training_sales" class="list-group-item list-group-item-action">
                            <i class="bi bi-play-circle me-2"></i> Training Videos
                        </a>
                        <a href="https://chat.birthday.gold" class="list-group-item list-group-item-action" target="_blank">
                            <i class="bi bi-chat-dots me-2"></i> Team Chat
                            <i class="bi bi-box-arrow-up-right float-end"></i>
                        </a>
                        <a href="/staff/it-support" class="list-group-item list-group-item-action">
                            <i class="bi bi-question-circle me-2"></i> IT Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>';

echo '</div>'; // End container

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>