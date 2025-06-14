<?php
/**
 * Allocation History Page
 * Shows complete history of user's enrollment allocations
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/claudecode/enrollment_allocations/classes/class.allocationmanager.php');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login');
    exit;
}

// Get user data
$current_user_data = $session->get('current_user_data');
$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$user_id = $current_user_data['user_id'];

// Initialize AllocationManager
$allocationManager = new AllocationManager($database);

// Get user's current allocation balance
$balance = $allocationManager->getUserBalance($user_id);

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

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
$allocation_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$additionalstyles = '
<style>
.history-header {
    background: #f8f9fa;
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-bottom: 1px solid #dee2e6;
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

.filter-tabs {
    margin-bottom: 2rem;
}

.filter-tabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    padding: 0.5rem 1rem;
}

.filter-tabs .nav-link.active {
    color: #667eea;
    border-bottom-color: #667eea;
    background: none;
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
</style>
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="history-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">Allocation History</h1>
                <p class="text-muted mb-0">Track your enrollment allocation earnings and usage</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="/myaccount/earn-enrollments" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Earn More
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container main-content">
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
                <p class="stats-label mb-0">Transactions</p>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="history-table">
        <?php if (empty($allocation_history)): ?>
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
                    foreach ($allocation_history as $history): 
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

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>