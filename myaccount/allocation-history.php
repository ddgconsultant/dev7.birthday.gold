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

/* Responsive adjustments */
@media (max-width: 576px) {
    .nav-tab-item {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }
    
    .nav-tab-item i {
        font-size: 1rem;
    }
    
    .tab-badge {
        font-size: 0.7rem;
        padding: 1px 4px;
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
            <div class="col-md-8 text-center text-md-start">
                <h1 class="mb-3"><i class="bi bi-coin me-3"></i>Allocation History</h1>
                <p class="lead mb-0">Track your enrollment allocation earnings and usage</p>
            </div>
            <div class="col-md-4 text-center text-md-end mt-3 mt-md-0">
                <a href="/myaccount/earn-enrollments" class="btn btn-primary btn-lg" style="border-radius: 25px;">
                    <i class="bi bi-plus-circle me-2"></i>Earn More Allocations
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
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
                                <th>Description</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activities as $activity): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($activity['date'])); ?></td>
                                <td>
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
                                </td>
                                <td>
                                    <?php if ($activity['type'] == 'allocation'): ?>
                                        <span class="badge bg-<?php echo $activity['data']['status'] == 'pending' ? 'warning' : 'success'; ?>">
                                            <?php echo $activity['data']['status'] == 'pending' ? 'Pending' : 'Earned'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Used</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end <?php echo $activity['type'] == 'allocation' ? 'allocation-positive' : 'allocation-negative'; ?>">
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
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_allocations as $alloc): ?>
                        <tr>
                            <td>
                                <?php echo date('M j, Y', strtotime($alloc['created_at'])); ?><br>
                                <small class="text-muted"><?php echo date('g:i A', strtotime($alloc['created_at'])); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $alloc['allocation_type'] == 'plan' ? 'primary' : ($alloc['allocation_type'] == 'bonus' ? 'success' : 'info'); ?>">
                                    <?php echo ucfirst($alloc['allocation_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="allocation-positive">+<?php echo $alloc['amount']; ?></span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($alloc['allocation_comment'] ?? ''); ?>
                                <?php if ($alloc['reference_type']): ?>
                                    <br><small class="text-muted">Ref: <?php echo $alloc['reference_type']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $alloc['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($alloc['status']); ?>
                                </span>
                            </td>
                            <td>
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
                        <th>Date & Time</th>
                        <th>Company</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th class="text-end">Allocation</th>
                        <th class="text-end">Balance After</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $running_balance = $balance['available_allocations'] + $stats['total_used'];
                    foreach ($enrollment_history as $history): 
                    ?>
                    <tr>
                        <td>
                            <?php echo date('M j, Y', strtotime($history['enrollment_date'])); ?><br>
                            <small class="text-muted"><?php echo date('g:i A', strtotime($history['enrollment_date'])); ?></small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
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
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($history['company_description'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <?php echo htmlspecialchars($history['company_category'] ?? 'Other'); ?>
                            </span>
                        </td>
                        <td>
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
                        <td class="text-end">
                            <span class="allocation-negative">-1</span>
                        </td>
                        <td class="text-end">
                            <?php 
                            $running_balance -= 1;
                            echo $running_balance; 
                            ?>
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