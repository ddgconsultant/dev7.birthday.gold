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

// Get monthly breakdown for current year
$monthly_sql = "SELECT 
                    MONTH(create_dt) as month,
                    COUNT(*) as enrollments
                FROM bg_user_companies 
                WHERE user_id = :user_id 
                AND YEAR(create_dt) = YEAR(NOW())
                AND status NOT IN ('failed', 'removed')
                GROUP BY MONTH(create_dt)
                ORDER BY month";
$monthly_data = $database->getrows($monthly_sql, ['user_id' => $user_id]);

// Get yearly breakdown
$yearly_sql = "SELECT 
                    YEAR(create_dt) as year,
                    COUNT(*) as enrollments
                FROM bg_user_companies 
                WHERE user_id = :user_id 
                AND status NOT IN ('failed', 'removed')
                GROUP BY YEAR(create_dt)
                ORDER BY year ASC";
$yearly_data = $database->getrows($yearly_sql, ['user_id' => $user_id]);

// Get allocation earning patterns
$allocation_earning_sql = "SELECT 
                            YEAR(created_at) as year,
                            MONTH(created_at) as month,
                            allocation_type,
                            SUM(amount) as total_amount,
                            COUNT(*) as count
                        FROM bg_user_allocations 
                        WHERE user_id = :user_id
                        GROUP BY YEAR(created_at), MONTH(created_at), allocation_type
                        ORDER BY year DESC, month DESC";
$allocation_earning_data = $database->getrows($allocation_earning_sql, ['user_id' => $user_id]);

// Calculate average usage patterns
$avg_monthly = 0;
$avg_yearly = 0;
if (!empty($monthly_data) && count($monthly_data) > 0) {
    $avg_monthly = array_sum(array_column($monthly_data, 'enrollments')) / count($monthly_data);
}
if (!empty($yearly_data) && count($yearly_data) > 0) {
    $avg_yearly = array_sum(array_column($yearly_data, 'enrollments')) / count($yearly_data);
}

// Get current month/year enrollments
$current_month_sql = "SELECT COUNT(*) as current_month_enrollments
                      FROM bg_user_companies 
                      WHERE user_id = :user_id 
                      AND YEAR(create_dt) = YEAR(NOW())
                      AND MONTH(create_dt) = MONTH(NOW())
                      AND status NOT IN ('failed', 'removed')";
$current_month_result = $database->getrow($current_month_sql, ['user_id' => $user_id]);

$current_year_sql = "SELECT COUNT(*) as current_year_enrollments
                     FROM bg_user_companies 
                     WHERE user_id = :user_id 
                     AND YEAR(create_dt) = YEAR(NOW())
                     AND status NOT IN ('failed', 'removed')";
$current_year_result = $database->getrow($current_year_sql, ['user_id' => $user_id]);

// Get earned allocations from plan
$stats = [
    'total_earned' => $balance['total_earned'] ?? $balance['plan_allocations'] ?? 0,
    'total_used' => $enrollment_stats['total_used'] ?? 0,
    'earn_count' => 1, // From plan
    'use_count' => $enrollment_stats['use_count'] ?? 0,
    'current_month_enrollments' => $current_month_result['current_month_enrollments'] ?? 0,
    'current_year_enrollments' => $current_year_result['current_year_enrollments'] ?? 0,
    'avg_monthly_usage' => round($avg_monthly, 1),
    'avg_yearly_usage' => round($avg_yearly, 1)
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

/* Chart containers */
.chart-container {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    height: 300px;
    margin-bottom: 1.5rem;
    min-width: 0;
    overflow: hidden;
}

.chart-container h5 {
    margin-bottom: 1rem;
    color: #495057;
    font-weight: 600;
}

.chart-wrapper {
    position: relative;
    height: 220px;
    width: 100%;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-item {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0d6efd;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.chart-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 2rem;
    width: 100%;
    overflow: hidden;
}

.chart-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 1400px) {
    .chart-row {
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
}

@media (max-width: 992px) {
    .chart-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .chart-row-2 {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        height: 280px;
        padding: 1rem;
    }
    
    .chart-wrapper {
        height: 200px;
    }
}
</style>
';
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
            <span class="tab-badge"><?php echo count($user_allocations ?? []); ?></span>
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
            <!-- Primary Stats -->
            <?php $has_pending = ($balance['pending_allocations'] ?? 0) > 0; ?>
            <div class="row g-3 mb-4">
                <div class="<?php echo $has_pending ? 'col-6 col-md-4 col-lg' : 'col-6 col-lg-3'; ?>">
                    <div class="stats-card">
                        <h3 class="stats-number text-primary"><?php echo $balance['available_allocations']; ?></h3>
                        <p class="stats-label mb-0">Current Balance</p>
                    </div>
                </div>
                <div class="<?php echo $has_pending ? 'col-6 col-md-4 col-lg' : 'col-6 col-lg-3'; ?>">
                    <div class="stats-card">
                        <h3 class="stats-number text-success"><?php echo $stats['total_earned'] ?? 0; ?></h3>
                        <p class="stats-label mb-0">Total Earned</p>
                    </div>
                </div>
                <div class="<?php echo $has_pending ? 'col-6 col-md-4 col-lg' : 'col-6 col-lg-3'; ?>">
                    <div class="stats-card">
                        <h3 class="stats-number text-danger"><?php echo $stats['total_used'] ?? 0; ?></h3>
                        <p class="stats-label mb-0">Total Used</p>
                    </div>
                </div>
                <div class="<?php echo $has_pending ? 'col-6 col-md-4 col-lg' : 'col-6 col-lg-3'; ?>">
                    <div class="stats-card">
                        <h3 class="stats-number text-info"><?php echo $total_records; ?></h3>
                        <p class="stats-label mb-0">Total Transactions</p>
                    </div>
                </div>
                <?php if ($has_pending): ?>
                <div class="col-12 col-md-4 col-lg">
                    <div class="stats-card">
                        <h3 class="stats-number text-warning"><?php echo $balance['pending_allocations']; ?></h3>
                        <p class="stats-label mb-0">Pending Allocations</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <h4 class="mb-3">Recent Activity <small class="text-muted">(Last 10 items)</small></h4>
            <div class="history-table">
                <?php 
                // Combine allocations and enrollments for recent activity
                $recent_activity = [];
                $activity_keys = []; // Track unique activities to prevent duplicates
                
                // Add allocations to activity
                foreach ($user_allocations as $alloc) {
                    $unique_key = 'alloc_' . $alloc['allocation_id'] . '_' . $alloc['created_at'];
                    if (!in_array($unique_key, $activity_keys)) {
                        $recent_activity[] = [
                            'type' => 'allocation',
                            'date' => $alloc['created_at'],
                            'data' => $alloc,
                            'unique_key' => $unique_key
                        ];
                        $activity_keys[] = $unique_key;
                    }
                }
                
                // Add enrollments to activity
                foreach ($enrollment_history as $enrollment) {
                    $unique_key = 'enrollment_' . $enrollment['user_company_id'] . '_' . $enrollment['enrollment_date'];
                    if (!in_array($unique_key, $activity_keys)) {
                        $recent_activity[] = [
                            'type' => 'enrollment',
                            'date' => $enrollment['enrollment_date'],
                            'data' => $enrollment,
                            'unique_key' => $unique_key
                        ];
                        $activity_keys[] = $unique_key;
                    }
                }
                
                // Sort by date descending
                usort($recent_activity, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                
                // Get last 10 activities
                $recent_activities = array_slice($recent_activity, 0, 10);
                
                // Debug: Check for potential data issues (remove this comment block after testing)
                /*
                echo "<!-- DEBUG INFO -->";
                echo "<!-- Total User Allocations: " . count($user_allocations) . " -->";
                echo "<!-- Total Enrollment History: " . count($enrollment_history) . " -->";
                echo "<!-- Total Recent Activities: " . count($recent_activities) . " -->";
                echo "<!-- Activity Keys: " . implode(', ', array_slice($activity_keys, 0, 10)) . " -->";
                */
                
                if (!empty($recent_activities)): 
                ?>
                <div class="table-responsive p-3">
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

            <!-- Charts Row -->
            <div class="chart-row mt-5">
                <!-- Monthly Usage Chart -->
                <div class="chart-container">
                    <h5><i class="bi bi-bar-chart me-2"></i>Monthly Usage (<?php echo date('Y'); ?>)</h5>
                    <div class="chart-wrapper">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
                
                <!-- Yearly Trend Chart -->
                <div class="chart-container">
                    <h5><i class="bi bi-graph-up me-2"></i>Yearly Trend</h5>
                    <div class="chart-wrapper">
                        <canvas id="yearlyChart"></canvas>
                    </div>
                </div>

                <!-- Usage Distribution Chart -->
                <?php 
                // Debug yearly data
                // echo "<!-- DEBUG: Yearly data: " . json_encode($yearly_data) . " -->";
                ?>
                <?php if (!empty($yearly_data) && count($yearly_data) > 0): ?>
                <div class="chart-container">
                    <?php 
                    $years = array_column($yearly_data, 'year');
                    $year_range = count($years) > 1 ? min($years) . '-' . max($years) : $years[0];
                    ?>
                    <h5><i class="bi bi-pie-chart me-2"></i>Usage Distribution by Year (<?php echo $year_range; ?>)
                    <?php if (count($years) > 1 && (max($years) - min($years) + 1) > count($years)): ?>
                    <small class="text-muted">- <?php echo count($years); ?> years with data</small>
                    <?php endif; ?>
                    </h5>
                    <div class="chart-wrapper">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
                <?php else: ?>
                <!-- Placeholder for when no yearly data -->
                <div class="chart-container">
                    <h5><i class="bi bi-pie-chart me-2"></i>Usage Distribution</h5>
                    <div class="chart-wrapper d-flex align-items-center justify-content-center">
                        <div class="text-center text-muted">
                            <i class="bi bi-bar-chart-line" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2">No data available</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
        </div>

        <!-- Allocations Tab -->
        <div class="tab-pane fade" id="allocations" role="tabpanel">
            <h3 class="mb-3">Allocation Transactions</h3>
            <?php if (!empty($user_allocations)): ?>
        <div class="history-table">
            <div class="table-responsive p-3">
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
        <div class="table-responsive p-3">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Version: 2025-08-28-v2
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

    // Initialize charts
    initializeCharts();
});

function initializeCharts() {
    console.log('Initializing charts...');
    console.log('Chart.js available:', typeof Chart !== 'undefined');
    
    // Monthly data from PHP
    const monthlyData = <?php echo json_encode($monthly_data ?? []); ?>;
    const yearlyData = <?php echo json_encode($yearly_data ?? []); ?>;
    
    console.log('Monthly Data:', monthlyData);
    console.log('Yearly Data:', yearlyData);
    
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded!');
        return;
    }
    
    // Create monthly usage chart
    const monthlyChartElement = document.getElementById('monthlyChart');
    if (monthlyChartElement) {
        console.log('Creating monthly chart...');
        const monthlyLabels = [];
        const monthlyValues = [];
        
        // Initialize all months with 0
        for (let i = 1; i <= 12; i++) {
            const monthName = new Date(2000, i-1, 1).toLocaleString('default', { month: 'short' });
            monthlyLabels.push(monthName);
            monthlyValues.push(0);
        }
        
        // Fill in actual data
        if (monthlyData && Array.isArray(monthlyData)) {
            monthlyData.forEach(item => {
                if (item.month >= 1 && item.month <= 12) {
                    monthlyValues[item.month - 1] = parseInt(item.enrollments);
                }
            });
        }
        
        console.log('Monthly Labels:', monthlyLabels);
        console.log('Monthly Values:', monthlyValues);
        
        new Chart(monthlyChartElement, {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Enrollments',
                    data: monthlyValues,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
    // Create yearly trend chart
    const yearlyChartElement = document.getElementById('yearlyChart');
    if (yearlyChartElement && yearlyData && yearlyData.length > 0) {
        console.log('Creating yearly chart...');
        const yearlyLabels = yearlyData.map(item => item.year.toString());
        const yearlyValues = yearlyData.map(item => parseInt(item.enrollments));
        
        console.log('Yearly Labels:', yearlyLabels);
        console.log('Yearly Values:', yearlyValues);
        
        new Chart(yearlyChartElement, {
            type: 'line',
            data: {
                labels: yearlyLabels,
                datasets: [{
                    label: 'Annual Enrollments',
                    data: yearlyValues,
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
    // Create distribution pie chart
    const distributionChartElement = document.getElementById('distributionChart');
    console.log('Distribution chart element found:', !!distributionChartElement);
    console.log('Yearly data for distribution:', yearlyData);
    console.log('Yearly data length:', yearlyData ? yearlyData.length : 'null');
    
    if (distributionChartElement && yearlyData && yearlyData.length > 0) {
        console.log('Creating distribution chart...');
        const colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
        ];
        
        const chartLabels = yearlyData.map(item => item.year.toString());
        const chartData = yearlyData.map(item => parseInt(item.enrollments));
        
        console.log('Distribution Labels:', chartLabels);
        console.log('Distribution Data:', chartData);
        
        new Chart(distributionChartElement, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: colors.slice(0, yearlyData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        align: 'center',
                        labels: {
                            padding: 8,
                            font: {
                                size: 11
                            },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 12,
                            boxHeight: 12
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 40,
                        left: 40,
                        right: 40
                    }
                },
                cutout: '50%',
                elements: {
                    arc: {
                        borderWidth: 1
                    }
                }
            }
        });
    }
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>