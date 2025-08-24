<?php
/**
 * Allocation History Page
 * Shows complete history of user's enrollment allocations
 */

 $addClasses[] = 'allocationmanager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get user data
$current_user_data = $session->get('current_user_data');
$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$user_id = $current_user_data['user_id'];


// Get user's current allocation balance
$balance = $allocationmanager->getUserBalance($user_id);

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get allocations from bg_user_allocations
$alloc_sql = "SELECT * FROM bg_user_allocations 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC";
$user_allocations = $database->getrows($alloc_sql, ['user_id' => $user_id]);

// Get total count of enrollments
$count_sql = "SELECT COUNT(*) as total 
              FROM bg_user_companies 
              WHERE user_id = :user_id 
              AND status NOT IN ('failed', 'removed')";
$total_result = $database->getrow($count_sql, ['user_id' => $user_id]);
$total_records = $total_result['total'];
$total_pages = ceil($total_records / $per_page);

// Get individual enrollment history with pagination
$sql = "SELECT 
            uc.*,
            c.company_name,
            c.company_id,
            c.display_category as company_category,
            c.description as company_description,
            ca.description as company_logo,
            uc.create_dt as enrollment_date,
            uc.status as enrollment_status
        FROM bg_user_companies uc
        JOIN bg_companies c ON uc.company_id = c.company_id
        LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id 
            AND ca.category = 'company_logos' 
            AND ca.grouping = 'primary_logo'
        WHERE uc.user_id = :user_id
        AND uc.status NOT IN ('failed', 'removed')
        ORDER BY uc.create_dt DESC
        LIMIT :limit OFFSET :offset";

$stmt = $database->prepare($sql);
$stmt->bindValue('user_id', $user_id, PDO::PARAM_INT);
$stmt->bindValue('limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$enrollment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary stats from user data and enrollments
$stats_sql = "SELECT 
                COUNT(*) as total_used,
                COUNT(*) as use_count
              FROM bg_user_companies 
              WHERE user_id = :user_id
              AND status NOT IN ('failed', 'removed')";
$enrollment_stats = $database->getrow($stats_sql, ['user_id' => $user_id]);

// Get earned allocations from plan
$stats = [
    'total_earned' => $balance['total_earned'] ?? $balance['plan_allocations'] ?? 0,
    'total_used' => $enrollment_stats['total_used'] ?? 0,
    'earn_count' => 1, // From plan
    'use_count' => $enrollment_stats['use_count'] ?? 0
];

// Page setup
$pagetitle = 'Allocation History';
$additionalstyles .= '
<style>
/* Modern tab navigation matching login-history style */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    overflow: hidden;
    position: relative;
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

.stats-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    height: 100%;
}

.stats-number {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.stats-label {
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.history-table {
    background: white;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.allocation-positive {
    color: #28a745;
    font-weight: 600;
}

.allocation-negative {
    color: #dc3545;
    font-weight: 600;
}

.enrollment-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 500;
    background: #e8f5e9;
    color: #2e7d32;
}

.tab-badge {
    font-size: 0.75rem;
    vertical-align: middle;
    display: inline-block;
    min-width: 20px;
    padding: 2px 6px;
    margin-left: 8px;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    background-color: #0d6efd;
    border-radius: 10px;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

/* Mobile card view for tables */
@media (max-width: 768px) {
    /* Hide table headers on mobile */
    .history-table thead {
        display: none;
    }
    
    /* Convert table rows to cards with better mobile layout */
    .history-table tbody tr.enrollment-row {
        display: flex;
        margin-bottom: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 0.75rem;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        position: relative;
        align-items: center;
    }
    
    .history-table tbody td {
        display: block;
        padding: 0;
        border: none;
        position: relative;
    }
    
    /* ENROLLMENT TAB MOBILE LAYOUT */
    /* Company logo column - left side */
    .history-table tbody td.company-cell {
        flex: 0 0 auto;
        margin-right: 0.75rem;
        order: 1;
    }
    
    /* Mobile content cell - contains company name, status, allocation, date */
    .history-table tbody td.mobile-content-cell {
        flex: 1;
        order: 2;
        display: flex !important;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .history-table .company-name-mobile {
        font-size: 0.9375rem;
        line-height: 1.3;
        margin-bottom: 0.125rem;
    }
    
    .history-table .mobile-details {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.375rem;
        font-size: 0.75rem;
    }
    
    .history-table .mobile-details .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.35rem;
    }
    
    .history-table .mobile-details .allocation-negative {
        color: #dc3545;
        font-weight: 600;
    }
    
    /* ALLOCATIONS TAB MOBILE LAYOUT */
    .history-table tbody tr.allocation-row {
        display: flex;
        margin-bottom: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 0.75rem;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        align-items: flex-start;
    }
    
    .history-table tbody td.badge-cell {
        flex: 0 0 auto;
        margin-right: 0.75rem;
        order: 1;
        display: flex !important;
        align-items: center;
        padding-top: 0.125rem;
    }
    
    .history-table tbody td.badge-cell:before {
        content: none !important;
    }
    
    .history-table tbody td.description-cell {
        flex: 1;
        order: 2;
        display: block !important;
    }
    
    .history-table tbody td.description-cell:before {
        content: none !important;
    }
    
    /* Mobile allocation details styling */
    .mobile-alloc-details {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.375rem;
        font-size: 0.75rem;
    }
    
    .mobile-alloc-details .allocation-positive {
        color: #28a745;
        font-weight: 600;
    }
    
    .mobile-alloc-details .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.35rem;
    }
    
    /* RECENT ACTIVITY TAB */
    .history-table tbody tr.activity-row {
        display: flex;
        margin-bottom: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 0.75rem;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        align-items: flex-start;
    }
    
    .history-table tbody td.activity-type-cell {
        flex: 0 0 auto;
        margin-right: 0.75rem;
        order: 1;
        display: flex !important;
        align-items: center;
        padding-top: 0.125rem;
    }
    
    .history-table tbody td.activity-type-cell:before {
        content: none !important;
    }
    
    .history-table tbody td.activity-desc-cell {
        flex: 1;
        order: 2;
        display: block !important;
    }
    
    .history-table tbody td.activity-desc-cell:before {
        content: none !important;
    }
    
    .mobile-activity-details {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.75rem;
    }
    
    .mobile-activity-details .allocation-positive {
        color: #28a745;
        font-weight: 600;
    }
    
    .mobile-activity-details .allocation-negative {
        color: #dc3545;
        font-weight: 600;
    }
    
    /* General mobile styles */
    .history-table tbody td .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
    
    /* Ensure proper spacing */
    .history-table {
        margin-bottom: 0;
    }
    
    /* Fix for rows without specific classes */
    .history-table tbody tr:not(.enrollment-row):not(.allocation-row):not(.activity-row) {
        display: flex;
        margin-bottom: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 0.75rem;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .nav-tab-item {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }
    
    .nav-tab-item i {
        font-size: 1rem;
        margin-right: 0.25rem !important;
    }
    
    .tab-badge {
        font-size: 0.7rem;
        padding: 1px 4px;
    }
    
    /* Stack earn more button on mobile */
    .content-header-dark .btn-lg {
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
    
    /* Adjust stats cards on smallest screens */
    .stats-card {
        padding: 1rem;
    }
    
    .stats-number {
        font-size: 1.5rem;
    }
    
    .stats-label {
        font-size: 0.75rem;
    }
}
</style>
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12 text-center">
                <h1 class="mb-3"><i class="bi bi-coin me-3"></i>Allocation History</h1>
                <p class="lead mb-0">Track your enrollment allocation earnings and usage</p>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <!-- Earn More Button -->
    <div class="mb-4 text-end">
        <a href="/myaccount/earn-enrollments" class="btn btn-primary" style="border-radius: 25px;">
            <i class="bi bi-plus-circle me-2"></i>Earn More Allocations
        </a>
    </div>
    
    <!-- Modern Tab Navigation -->
    <nav class="nav-tabs-modern">
        <a href="#overview" class="nav-tab-item active" data-tab="overview">
            <i class="bi bi-speedometer2 me-2"></i>Overview
        </a>
        <a href="#allocations" class="nav-tab-item" data-tab="allocations">
            <i class="bi bi-plus-circle me-2"></i>Allocations
            <?php if (!empty($user_allocations)): ?>
            <span class="tab-badge"><?php echo count($user_allocations); ?></span>
            <?php endif; ?>
        </a>
        <a href="#enrollments" class="nav-tab-item" data-tab="enrollments">
            <i class="bi bi-check-circle me-2"></i>Enrollments
            <?php if ($total_records > 0): ?>
            <span class="tab-badge"><?php echo $total_records; ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <!-- Tab Content -->
    <div class="tab-content" id="allocationTabContent">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <!-- Summary Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <h3 class="stats-number text-primary"><?php echo $balance['available_allocations']; ?></h3>
                        <p class="stats-label mb-0">Current Balance</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <h3 class="stats-number text-success"><?php echo $stats['total_earned'] ?? 0; ?></h3>
                        <p class="stats-label mb-0">Total Earned</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <h3 class="stats-number text-danger"><?php echo $stats['total_used'] ?? 0; ?></h3>
                        <p class="stats-label mb-0">Total Used</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <h3 class="stats-number text-info"><?php echo $total_records; ?></h3>
                        <p class="stats-label mb-0">Total Transactions</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <h4 class="mb-3">Recent Activity</h4>
            <div class="history-table">
                <?php 
                // Combine allocations and enrollments for recent activity
                $recent_activity = [];
                
                // Add allocations to activity
                foreach ($user_allocations as $alloc) {
                    $recent_activity[] = [
                        'type' => 'allocation',
                        'date' => $alloc['created_at'],
                        'data' => $alloc
                    ];
                }
                
                // Add enrollments to activity
                foreach ($enrollment_history as $enrollment) {
                    $recent_activity[] = [
                        'type' => 'enrollment',
                        'date' => $enrollment['enrollment_date'],
                        'data' => $enrollment
                    ];
                }
                
                // Sort by date descending
                usort($recent_activity, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                
                // Get last 5 activities
                $recent_activities = array_slice($recent_activity, 0, 5);
                
                if (!empty($recent_activities)): 
                ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-center">Type</th>
                                <th>Description</th>
                                <th class="text-center">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activities as $activity): ?>
                            <tr class="activity-row">
                                <td data-label="Date" class="d-none d-md-table-cell"><?php echo date('M j, Y', strtotime($activity['date'])); ?></td>
                                <td data-label="Type" class="text-center activity-type-cell">
                                    <?php if ($activity['type'] == 'allocation'): ?>
                                        <span class="badge bg-<?php echo $activity['data']['status'] == 'pending' ? 'warning' : 'success'; ?>">
                                            <?php echo $activity['data']['status'] == 'pending' ? 'Pending' : 'Earned'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Used</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Description" class="activity-desc-cell">
                                    <!-- Desktop view -->
                                    <div class="d-none d-md-block">
                                        <?php if ($activity['type'] == 'allocation'): ?>
                                            <div>
                                                <strong><?php echo ucfirst($activity['data']['allocation_type']); ?> Allocation</strong>
                                                <?php if (!empty($activity['data']['allocation_comment'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($activity['data']['allocation_comment']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($activity['data']['company_logo'])): ?>
                                                <img src="<?php echo $display->companyimage($activity['data']['company_id'] . '/' . $activity['data']['company_logo']); ?>" 
                                                     class="rounded me-2" 
                                                     style="width: 24px; height: 24px; object-fit: cover;"
                                                     alt="">
                                                <?php endif; ?>
                                                <strong><?php echo htmlspecialchars($activity['data']['company_name']); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Mobile view -->
                                    <div class="d-md-none">
                                        <?php if ($activity['type'] == 'allocation'): ?>
                                            <div class="mb-1">
                                                <strong><?php echo ucfirst($activity['data']['allocation_type']); ?> Allocation</strong>
                                                <?php if (!empty($activity['data']['allocation_comment'])): ?>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($activity['data']['allocation_comment']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mobile-activity-details">
                                                <span class="allocation-positive me-2">+<?php echo $activity['data']['amount']; ?></span>
                                                <span class="text-muted small"><?php echo date('M j, Y', strtotime($activity['date'])); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex">
                                                <?php if (!empty($activity['data']['company_logo'])): ?>
                                                <img src="<?php echo $display->companyimage($activity['data']['company_id'] . '/' . $activity['data']['company_logo']); ?>" 
                                                     class="rounded me-2" 
                                                     style="width: 40px; height: 40px; object-fit: cover;"
                                                     alt="">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="mb-1">
                                                        <strong><?php echo htmlspecialchars($activity['data']['company_name']); ?></strong>
                                                    </div>
                                                    <div class="mobile-activity-details">
                                                        <span class="allocation-negative me-2">-1</span>
                                                        <span class="text-muted small"><?php echo date('M j, Y', strtotime($activity['date'])); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center <?php echo $activity['type'] == 'allocation' ? 'allocation-positive' : 'allocation-negative'; ?> d-none d-md-table-cell" data-label="Amount">
                                    <?php echo $activity['type'] == 'allocation' ? '+' . $activity['data']['amount'] : '-1'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted">No recent activity</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Allocations Tab -->
        <div class="tab-pane fade" id="allocations" role="tabpanel">
            <h3 class="mb-3">Allocation Transactions</h3>
            <?php if (!empty($user_allocations)): ?>
        <div class="history-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
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
                        <?php foreach ($user_allocations as $alloc): ?>
                        <tr class="allocation-row">
                            <td data-label="Date" class="d-none d-md-table-cell">
                                <?php echo date('M j, Y', strtotime($alloc['created_at'])); ?>
                            </td>
                            <td data-label="Type" class="badge-cell">
                                <span class="badge bg-<?php echo $alloc['allocation_type'] == 'plan' ? 'primary' : ($alloc['allocation_type'] == 'bonus' ? 'success' : 'info'); ?>">
                                    <?php echo ucfirst($alloc['allocation_type']); ?>
                                </span>
                            </td>
                            <td data-label="Amount" class="text-center d-none d-md-table-cell">
                                <span class="allocation-positive">+<?php echo $alloc['amount']; ?></span>
                            </td>
                            <td data-label="Description" class="description-cell">
                                <div class="d-none d-md-block">
                                    <?php echo htmlspecialchars($alloc['allocation_comment'] ?? ''); ?>
                                    <?php if ($alloc['reference_type']): ?>
                                        <br><small class="text-muted">Ref: <?php echo $alloc['reference_type']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <!-- Mobile layout content -->
                                <div class="d-md-none">
                                    <div class="mb-1">
                                        <strong><?php echo htmlspecialchars($alloc['allocation_comment'] ?? 'Allocation'); ?></strong>
                                    </div>
                                    <div class="mobile-alloc-details">
                                        <span class="allocation-positive me-2">+<?php echo $alloc['amount']; ?> allocations</span>
                                        <span class="badge bg-<?php echo $alloc['status'] == 'active' ? 'success' : 'secondary'; ?> me-2">
                                            <?php echo ucfirst($alloc['status']); ?>
                                        </span>
                                        <span class="text-muted small"><?php echo date('M j, Y', strtotime($alloc['created_at'])); ?></span>
                                    </div>
                                    <?php if ($alloc['amount_used'] > 0): ?>
                                    <div class="text-muted small mt-1">
                                        Available: <?php echo $alloc['amount'] - $alloc['amount_used']; ?> (Used: <?php echo $alloc['amount_used']; ?>)
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($alloc['reference_type']): ?>
                                    <div class="text-muted small mt-1">Ref: <?php echo $alloc['reference_type']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Status" class="d-none d-md-table-cell">
                                <span class="badge bg-<?php echo $alloc['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($alloc['status']); ?>
                                </span>
                            </td>
                            <td data-label="Available" class="text-center d-none d-md-table-cell">
                                <?php echo $alloc['amount'] - $alloc['amount_used']; ?>
                                <?php if ($alloc['amount_used'] > 0): ?>
                                    <br><small class="text-muted">Used: <?php echo $alloc['amount_used']; ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-coin"></i>
                <h3>No Allocations Yet</h3>
                <p>You haven't earned any allocations yet.</p>
                <a href="/myaccount/earn-enrollments" class="btn btn-primary mt-3">Start Earning</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Enrollments Tab -->
        <div class="tab-pane fade" id="enrollments" role="tabpanel">
            <h3 class="mb-3">Enrollment History</h3>
    <div class="history-table">
        <?php if (empty($enrollment_history)): ?>
        <div class="empty-state">
            <i class="bi bi-clock-history"></i>
            <h3>No Allocation History</h3>
            <p>You haven't earned or used any allocations yet.</p>
            <a href="/myaccount/earn-enrollments" class="btn btn-primary mt-3">Start Earning</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Company</th>
                        <th>Category</th>
                        <th class="text-end">Allocation</th>
                        <th class="text-end">Balance After</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $running_balance = $balance['available_allocations'] + $stats['total_used'];
                    foreach ($enrollment_history as $history): 
                    ?>
                    <tr class="enrollment-row">
                        <td data-label="Date" class="mobile-info-cell d-none d-md-table-cell">
                            <?php echo date('M j, Y', strtotime($history['enrollment_date'])); ?>
                        </td>
                        <td data-label="Status" class="d-none d-md-table-cell">
                            <?php 
                            $status_class = 'bg-success';
                            $status_text = 'Enrolled';
                            if ($history['enrollment_status'] == 'pending') {
                                $status_class = 'bg-warning';
                                $status_text = 'Pending';
                            } elseif ($history['enrollment_status'] == 'existing') {
                                $status_class = 'bg-info';
                                $status_text = 'Existing';
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td data-label="Company" class="company-cell">
                            <?php if (!empty($history['company_logo'])): ?>
                            <img src="<?php echo $display->companyimage($history['company_id'] . '/' . $history['company_logo']); ?>" 
                                 class="rounded company-logo d-md-none" 
                                 style="width: 48px; height: 48px; object-fit: cover;"
                                 alt="">
                            <?php else: ?>
                            <div class="company-logo-placeholder d-md-none" style="width: 48px; height: 48px; background: #f0f0f0; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-building" style="color: #999;"></i>
                            </div>
                            <?php endif; ?>
                            <div class="d-none d-md-flex align-items-center">
                                <?php if (!empty($history['company_logo'])): ?>
                                <img src="<?php echo $display->companyimage($history['company_id'] . '/' . $history['company_logo']); ?>" 
                                     class="rounded me-2" 
                                     style="width: 32px; height: 32px; object-fit: cover;"
                                     alt="">
                                <?php endif; ?>
                                <div>
                                    <a href="/brand-details?cid=<?php echo $history['company_id']; ?>" class="text-decoration-none">
                                        <strong><?php echo htmlspecialchars($history['company_name']); ?></strong>
                                    </a>
                                    <?php if (!empty($history['company_description'])): ?>
                                    <br><small class="text-muted d-none d-md-block"><?php echo htmlspecialchars(substr($history['company_description'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td data-label="Category" class="d-none d-md-table-cell">
                            <span class="badge bg-light text-dark">
                                <?php echo htmlspecialchars($history['company_category'] ?? 'Other'); ?>
                            </span>
                        </td>
                        <td class="text-end d-none d-md-table-cell" data-label="Allocation">
                            <span class="allocation-negative">-1</span>
                        </td>
                        <td class="text-end d-none d-md-table-cell" data-label="Balance After">
                            <?php 
                            $running_balance -= 1;
                            echo $running_balance; 
                            ?>
                        </td>
                        <!-- Mobile layout info cell -->
                        <td data-label="Date" class="mobile-content-cell d-md-none">
                            <div class="company-name-mobile">
                                <a href="/brand-details?cid=<?php echo $history['company_id']; ?>" class="text-decoration-none text-dark">
                                    <strong><?php echo htmlspecialchars($history['company_name']); ?></strong>
                                </a>
                            </div>
                            <div class="mobile-details">
                                <?php 
                                $status_class = 'bg-success';
                                $status_text = 'Enrolled';
                                if ($history['enrollment_status'] == 'pending') {
                                    $status_class = 'bg-warning';
                                    $status_text = 'Pending';
                                } elseif ($history['enrollment_status'] == 'existing') {
                                    $status_class = 'bg-info';
                                    $status_text = 'Existing';
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?> me-2"><?php echo $status_text; ?></span>
                                <span class="text-muted small">Allocation: <span class="allocation-negative">-1</span></span>
                                <span class="text-muted small ms-2"><?php echo date('M j, Y', strtotime($history['enrollment_date'])); ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Allocation history pagination" class="p-3 border-top">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                </li>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
        </div>
    </div>
</div>

<script>
// Handle tab switching
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.nav-tab-item');
    const tabContents = document.querySelectorAll('.tab-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.remove('show', 'active');
            });
            
            // Show selected tab content
            const targetId = this.getAttribute('data-tab');
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.add('show', 'active');
            }
        });
    });
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>