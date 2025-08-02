<?php
/**
 * Admin Eligibility Dashboard
 * Monitor and manage user eligibility issues
 */
$addClasses[]='enrollment';
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Get statistics
$stats = $enrollment->getEligibilityStats();

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';

if ($action == 'process_batch') {
    // Manually trigger batch processing
    $limit = intval($_GET['limit'] ?? 100);
    $processed = 0;
    
    try {
        // Get stale records
        $stale_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $sql = "SELECT u.user_id, c.company_id, MIN(e.last_checked) as last_checked
                FROM bg_users u
                CROSS JOIN bg_companies c
                LEFT JOIN bg_user_eligibility e 
                    ON u.user_id = e.member_id AND c.company_id = c.company_id
                WHERE u.status = 'active' 
                AND c.status = 'finalized'
                AND (e.last_checked IS NULL OR e.last_checked < :stale_date)
                GROUP BY u.user_id, c.company_id
                ORDER BY MIN(CASE WHEN e.last_checked IS NULL THEN 0 ELSE 1 END), MIN(e.last_checked) ASC
                LIMIT :limit";
        
        $records = $database->getrows($sql, ['stale_date' => $stale_date, 'limit' => $limit]);
        
        foreach ($records as $record) {
            $enrollment->checkAndStoreEligibility($record['user_id'], $record['company_id']);
            $processed++;
        }
        
        $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Successfully processed ' . $processed . ' eligibility records.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        
        // Refresh stats
        $stats = $enrollment->getEligibilityStats();
        
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Error processing batch: ' . $e->getMessage() . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    }
}

// Get detailed issue breakdown
$issue_details_sql = "SELECT 
                        r.id, r.code, r.message, r.category,
                        COUNT(DISTINCT e.member_id) as affected_users,
                        COUNT(*) as total_issues,
                        MAX(e.last_checked) as last_seen
                     FROM bg_eligibility_reasons r
                     LEFT JOIN bg_user_eligibility e ON r.id = e.reason_id
                     GROUP BY r.id
                     HAVING total_issues > 0
                     ORDER BY total_issues DESC";

$issue_details = $database->getrows($issue_details_sql);

// Get recent eligibility changes
$recent_changes_sql = "SELECT 
                        e.member_id, e.company_id, e.reason_id, e.last_checked,
                        u.first_name, u.last_name, u.email,
                        c.company_name,
                        r.message as reason
                      FROM bg_user_eligibility e
                      JOIN bg_users u ON e.member_id = u.user_id
                      JOIN bg_companies c ON e.company_id = c.company_id
                      JOIN bg_eligibility_reasons r ON e.reason_id = r.id
                      ORDER BY e.last_checked DESC
                      LIMIT 50";

$recent_changes = $database->getrows($recent_changes_sql);

// Calculate percentages for category breakdown
$category_stats = [];
foreach ($issue_details as $issue) {
    $category = $issue['category'];
    if (!isset($category_stats[$category])) {
        $category_stats[$category] = 0;
    }
    $category_stats[$category] += $issue['total_issues'];
}

$pagetitle = 'Eligibility Dashboard';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container-fluid my-4">
    <h1>User Eligibility Dashboard</h1>
    
    <?php echo $display->formaterrormessage($message); ?>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Issues</h5>
                    <h2 class="text-primary"><?php echo number_format($stats['total_issues']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Affected Users</h5>
                    <h2 class="text-warning"><?php echo number_format($stats['affected_users']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Affected Companies</h5>
                    <h2 class="text-info"><?php echo number_format($stats['affected_companies']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Stale Records</h5>
                    <h2 class="<?php echo $stats['stale_records'] > 1000 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo number_format($stats['stale_records']); ?>
                    </h2>
                    <?php if ($stats['stale_records'] > 1000): ?>
                        <small class="text-muted">Consider batch processing</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Actions</h5>
        </div>
        <div class="card-body">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#processBatchModal" data-batch-size="100">
                Process 100 Records
            </button>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#processBatchModal" data-batch-size="1000">
                Process 1000 Records
            </button>
            <a href="/admin_actions/scheduler--process_eligibility.php?key=<?php echo htmlspecialchars($SCHEDULERCONFIG['key']); ?>" 
               class="btn btn-secondary" target="_blank">
                Run Scheduler Manually
            </a>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Category Breakdown -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Issues by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Issues -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Top 10 Issues</h5>
                </div>
                <div class="card-body">
                    <canvas id="topIssuesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Issue Details Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Issue Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Issue</th>
                            <th>Category</th>
                            <th>Affected Users</th>
                            <th>Total Issues</th>
                            <th>Percentage</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($issue_details as $issue): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($issue['message']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($issue['code']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($issue['category']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($issue['affected_users']); ?></td>
                            <td><?php echo number_format($issue['total_issues']); ?></td>
                            <td>
                                <?php 
                                $percentage = $stats['total_issues'] > 0 
                                    ? round(($issue['total_issues'] / $stats['total_issues']) * 100, 1) 
                                    : 0;
                                echo $percentage . '%';
                                ?>
                            </td>
                            <td><?php echo $display->formatdate($issue['last_seen'], 'M j, Y g:i A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Changes -->
    <div class="card">
        <div class="card-header">
            <h5>Recent Eligibility Issues (Last 50)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Company</th>
                            <th>Issue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_changes as $change): ?>
                        <tr>
                            <td><?php echo $display->formatdate($change['last_checked'], 'M j g:i A'); ?></td>
                            <td>
                                <a href="/admin/user-details.php?id=<?php echo $change['member_id']; ?>">
                                    <?php echo htmlspecialchars($change['first_name'] . ' ' . $change['last_name']); ?>
                                </a>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($change['email']); ?></small>
                            </td>
                            <td>
                                <a href="/admin/business-editor.php?action=edit&id=<?php echo $change['company_id']; ?>">
                                    <?php echo htmlspecialchars($change['company_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($change['reason']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Process Batch Modal -->
<div class="modal fade" id="processBatchModal" tabindex="-1" aria-labelledby="processBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processBatchModalLabel">Process Eligibility Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to process <strong><span id="batchSizeText"></span></strong> stale eligibility records?</p>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> This will check user eligibility for companies and update any stale records.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="processBatchConfirm" class="btn btn-primary">
                    <i class="bi bi-play-circle"></i> Process Records
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_keys($category_stats)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($category_stats)); ?>,
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Top Issues Chart
const topIssuesCtx = document.getElementById('topIssuesChart').getContext('2d');
new Chart(topIssuesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_slice(array_column($issue_details, 'message'), 0, 10)); ?>,
        datasets: [{
            label: 'Issues',
            data: <?php echo json_encode(array_slice(array_column($issue_details, 'total_issues'), 0, 10)); ?>,
            backgroundColor: '#36A2EB'
        }]
    },
    options: {
        responsive: true,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true
            }
        }
    }
});

// Auto-refresh every 30 seconds if on dashboard
setTimeout(function() {
    if (!document.hidden && !window.location.search.includes('action=')) {
        location.reload();
    }
}, 30000);

// Handle process batch modal
const processBatchModal = document.getElementById('processBatchModal');
processBatchModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const batchSize = button.getAttribute('data-batch-size');
    
    // Update modal content
    document.getElementById('batchSizeText').textContent = batchSize;
    document.getElementById('processBatchConfirm').href = '?action=process_batch&limit=' + batchSize;
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>