<?php
# Staff HR Details Page
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Staff-only access is already handled by site-controller.php

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$pagetitle = 'HR Details';
$staff_user = $current_user_data;
$staff_role = $staff_user['user_role'] ?? 'staff';

// Get detailed HR information (mock data for now - would come from HR tables)
$hr_data = [
    'employee_id' => str_pad($staff_user['user_id'], 6, '0', STR_PAD_LEFT),
    'department' => $staff_user['profile_department'] ?? 'Customer Service',
    'position' => $staff_user['profile_position'] ?? 'Staff Member',
    'employment_type' => 'Full-Time',
    'hire_date' => $staff_user['create_dt'] ?? date('Y-m-d'),
    'manager' => 'John Smith',
    'location' => 'Remote',
    'salary_grade' => 'Level 3',
    'vacation_days_total' => 15,
    'vacation_days_used' => 5,
    'sick_days_total' => 10,
    'sick_days_used' => 2,
    'personal_days' => 3,
    'next_review' => date('Y-m-d', strtotime('+6 months')),
    'last_review' => date('Y-m-d', strtotime('-6 months')),
    'emergency_contact' => $staff_user['profile_emergency_contact'] ?? 'Not provided',
    'emergency_phone' => $staff_user['profile_emergency_phone'] ?? 'Not provided'
];

// Calculate time with company
$hire_date = new DateTime($hr_data['hire_date']);
$now = new DateTime();
$tenure = $hire_date->diff($now);

// Get recent PTO requests (mock data)
$pto_requests = [
    ['date' => '2025-08-20', 'type' => 'Vacation', 'days' => 2, 'status' => 'Approved'],
    ['date' => '2025-07-15', 'type' => 'Sick', 'days' => 1, 'status' => 'Approved'],
    ['date' => '2025-06-10', 'type' => 'Personal', 'days' => 1, 'status' => 'Approved']
];

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_emergency') {
        // Update emergency contact info
        $emergency_contact = $_POST['emergency_contact'] ?? '';
        $emergency_phone = $_POST['emergency_phone'] ?? '';
        
        // Would update in database here
        $system->addmessage('success', 'Emergency contact information updated');
        header('Location: /staff/hr-details');
        exit;
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
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
    .hr-card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }
    .hr-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
    }
    .info-label {
        font-weight: 600;
        color: #6c757d;
    }
    .info-value {
        color: #212529;
    }
    .pto-badge {
        font-size: 1.5rem;
        font-weight: bold;
    }
    .pto-card {
        text-align: center;
        padding: 1rem;
        border-radius: 0.5rem;
        background: white;
        border: 1px solid #dee2e6;
    }
    .tenure-display {
        font-size: 1.2rem;
        color: #28a745;
        font-weight: 600;
    }
    .review-timeline {
        position: relative;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }
    </style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Output the CSS for screen reader elements
echo $additionalstyles;

echo '
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/staff/">Staff Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">HR Details</li>
                </ol>
            </nav>
            <h1 class="h2">HR Details</h1>
            <p class="text-muted">Employee Information for ' . htmlspecialchars(($staff_user['profile_first_name'] ?? '') . ' ' . ($staff_user['profile_last_name'] ?? '')) . '</p>
        </div>
    </div>';

// Employment Information
echo '
    <div class="row">
        <div class="col-lg-8">
            <div class="card hr-card">
                <div class="card-header">
                    <i class="bi bi-person-badge me-2"></i> Employment Information
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Employee ID</div>
                            <div class="info-value fs-5">#' . $hr_data['employee_id'] . '</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Department</div>
                            <div class="info-value fs-5">' . htmlspecialchars($hr_data['department']) . '</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Position</div>
                            <div class="info-value fs-5">' . htmlspecialchars($hr_data['position']) . '</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Employment Type</div>
                            <div class="info-value fs-5">' . $hr_data['employment_type'] . '</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Hire Date</div>
                            <div class="info-value fs-5">' . date('F d, Y', strtotime($hr_data['hire_date'])) . '</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Time with Company</div>
                            <div class="tenure-display">' . $tenure->format('%y years, %m months') . '</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Direct Manager</div>
                            <div class="info-value fs-5">' . htmlspecialchars($hr_data['manager']) . '</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Work Location</div>
                            <div class="info-value fs-5">' . htmlspecialchars($hr_data['location']) . '</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PTO Balance -->
            <div class="card hr-card">
                <div class="card-header">
                    <i class="bi bi-calendar-check me-2"></i> PTO Balance
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="pto-card">
                                <div class="text-muted">Vacation Days</div>
                                <div class="pto-badge text-primary">' . ($hr_data['vacation_days_total'] - $hr_data['vacation_days_used']) . '</div>
                                <small class="text-muted">of ' . $hr_data['vacation_days_total'] . ' remaining</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="pto-card">
                                <div class="text-muted">Sick Days</div>
                                <div class="pto-badge text-warning">' . ($hr_data['sick_days_total'] - $hr_data['sick_days_used']) . '</div>
                                <small class="text-muted">of ' . $hr_data['sick_days_total'] . ' remaining</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="pto-card">
                                <div class="text-muted">Personal Days</div>
                                <div class="pto-badge text-success">' . $hr_data['personal_days'] . '</div>
                                <small class="text-muted">available</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Recent PTO Requests</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>';

foreach ($pto_requests as $request) {
    $badge_class = $request['status'] == 'Approved' ? 'bg-success' : 'bg-warning';
    echo '
                                <tr>
                                    <td>' . date('M d, Y', strtotime($request['date'])) . '</td>
                                    <td>' . $request['type'] . '</td>
                                    <td>' . $request['days'] . '</td>
                                    <td><span class="badge ' . $badge_class . '">' . $request['status'] . '</span></td>
                                </tr>';
}

echo '
                            </tbody>
                        </table>
                    </div>
                    <a href="/staff/request-pto" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Request Time Off
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Performance Reviews -->
            <div class="card hr-card">
                <div class="card-header">
                    <i class="bi bi-graph-up me-2"></i> Performance Reviews
                </div>
                <div class="card-body">
                    <div class="review-timeline">
                        <div class="mb-3">
                            <div class="info-label">Last Review</div>
                            <div class="info-value">' . date('F d, Y', strtotime($hr_data['last_review'])) . '</div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Next Review</div>
                            <div class="info-value fw-bold">' . date('F d, Y', strtotime($hr_data['next_review'])) . '</div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">6 months until next review</small>
                    </div>
                    <a href="/staff/performance-history" class="btn btn-outline-primary btn-sm mt-3 w-100">
                        View Performance History
                    </a>
                </div>
            </div>
            
            <!-- Emergency Contact -->
            <div class="card hr-card">
                <div class="card-header">
                    <i class="bi bi-exclamation-triangle me-2"></i> Emergency Contact
                </div>
                <div class="card-body">
                    <form method="POST">
                        ' . $display->input_csrftoken() . '
                        <input type="hidden" name="action" value="update_emergency">
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Name</label>
                            <input type="text" class="form-control" name="emergency_contact" 
                                   value="' . htmlspecialchars($hr_data['emergency_contact']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="emergency_phone" 
                                   value="' . htmlspecialchars($hr_data['emergency_phone']) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Update Emergency Contact
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card hr-card">
                <div class="card-header">
                    <i class="bi bi-lightning me-2"></i> Quick Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/staff/download-w2" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-file-earmark-pdf"></i> Download W-2
                        </a>
                        <a href="/staff/pay-stubs" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-receipt"></i> View Pay Stubs
                        </a>
                        <a href="/staff/benefits" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-heart"></i> Benefits Information
                        </a>
                        <a href="/staff/training" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-book"></i> Training Resources
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>