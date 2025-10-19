<?php
/**
 * Error Fix Dashboard
 * Overview of all error fixes in the system
 */

include '../core/site-controller.php';

// Admin access check
if (!$account->isadmin()) {
    header('Location: /');
    exit;
}

// Page setup
$page_title = "Error Fix Dashboard - Birthday.Gold Admin";
$page_description = "Manage automated error fixes";

// Handle filters
$status_filter = $_GET['status'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'recent';

// Build query
$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "fix_status = :status";
    $params['status'] = $status_filter;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Sorting
$order_by = match ($sort_by) {
    'oldest' => 'ORDER BY first_seen ASC',
    'confidence_high' => 'ORDER BY ai_confidence DESC',
    'confidence_low' => 'ORDER BY ai_confidence ASC',
    'occurrences' => 'ORDER BY occurrence_count DESC',
    default => 'ORDER BY last_seen DESC'
};

// Pagination
$page = intval($_GET['page'] ?? 1);
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM bg_auto_error_fixes {$where_sql}";
$stmt = $database->query($count_sql, $params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $per_page);

// Get fixes
$sql = "SELECT * FROM bg_auto_error_fixes
        {$where_sql}
        {$order_by}
        LIMIT {$per_page} OFFSET {$offset}";

$stmt = $database->query($sql, $params);
$fixes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "SELECT
    COUNT(*) as total_fixes,
    SUM(CASE WHEN fix_status = 'pending_review' THEN 1 ELSE 0 END) as pending_review,
    SUM(CASE WHEN fix_status = 'approved_pending_apply' THEN 1 ELSE 0 END) as approved_pending,
    SUM(CASE WHEN fix_status = 'applied' THEN 1 ELSE 0 END) as applied,
    SUM(CASE WHEN fix_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN fix_status = 'failed_to_apply' THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN fix_status = 'needs_manual_review' THEN 1 ELSE 0 END) as manual_review,
    SUM(occurrence_count) as total_occurrences,
    AVG(ai_confidence) as avg_confidence
FROM bg_auto_error_fixes";

$stats = $database->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

// Get last check timestamp
$last_check_sql = "SELECT config_value FROM bg_config
                   WHERE config_type = 'auto_error_fixer'
                   AND config_key = 'last_run_timestamp'";
$last_check = $database->query($last_check_sql)->fetchColumn();

// CSS
$additionalstyles .= '
<style>
.dashboard-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
}

.filters-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.fixes-table {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.fix-row {
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 0;
    transition: background 0.2s;
}

.fix-row:hover {
    background: #f8f9fa;
}

.fix-row:last-child {
    border-bottom: none;
}

.fix-file {
    font-family: "Consolas", "Monaco", "Courier New", monospace;
    font-size: 0.875rem;
    color: #0d6efd;
}

.fix-message {
    color: #495057;
    font-size: 0.875rem;
    margin: 0.25rem 0;
}

.fix-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.5rem;
}

.pagination {
    margin-top: 2rem;
    justify-content: center;
}
</style>';

// Include header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Page header
echo '
<div class="content-header-admin bg-primary text-white py-4">
    <div class="container-fluid">
        <h1 class="h2 mb-0">
            <i class="bi bi-tools"></i> Auto Error Fixer Dashboard
        </h1>
        <p class="mb-0">Monitor and manage automated error fixes</p>
    </div>
</div>

<div class="dashboard-container">';

// Statistics
echo '
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number text-primary">' . number_format($stats['total_fixes'] ?? 0) . '</div>
            <div class="stat-label">Total Errors</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-warning">' . number_format($stats['pending_review'] ?? 0) . '</div>
            <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-secondary">' . number_format($stats['manual_review'] ?? 0) . '</div>
            <div class="stat-label">Manual Review</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-info">' . number_format($stats['approved_pending'] ?? 0) . '</div>
            <div class="stat-label">Approved (Pending)</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-success">' . number_format($stats['applied'] ?? 0) . '</div>
            <div class="stat-label">Applied</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-danger">' . number_format($stats['rejected'] ?? 0) . '</div>
            <div class="stat-label">Rejected</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-danger">' . number_format($stats['failed'] ?? 0) . '</div>
            <div class="stat-label">Failed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['avg_confidence'] ?? 0, 1) . '%</div>
            <div class="stat-label">Avg AI Confidence</div>
        </div>
    </div>';

// Actions card
echo '
    <div class="filters-card mb-3">
        <h5 class="mb-3"><i class="bi bi-gear"></i> Actions</h5>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <a href="/admin_actions/scheduler--auto-error-fixer.php" target="_blank" class="btn btn-primary">
                <i class="bi bi-play-circle"></i> Trigger Error Fixer Job
            </a>
            <button type="button" class="btn btn-warning" onclick="showResetModal()">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Last Check Time
            </button>
            <span class="text-muted ms-3">
                <i class="bi bi-clock-history"></i> Last Check: ' . ($last_check ? date('M j, Y g:i A', strtotime($last_check)) : 'Never') . '
            </span>
        </div>
        <div id="action-status" class="mt-3" style="display: none;"></div>
    </div>';

// Filters
echo '
    <div class="filters-card">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                    <option value="all"' . ($status_filter === 'all' ? ' selected' : '') . '>All Statuses</option>
                    <option value="pending_review"' . ($status_filter === 'pending_review' ? ' selected' : '') . '>Pending Review (Auto-fixable)</option>
                    <option value="needs_manual_review"' . ($status_filter === 'needs_manual_review' ? ' selected' : '') . '>Needs Manual Review</option>
                    <option value="approved_pending_apply"' . ($status_filter === 'approved_pending_apply' ? ' selected' : '') . '>Approved (Pending Apply)</option>
                    <option value="applied"' . ($status_filter === 'applied' ? ' selected' : '') . '>Applied</option>
                    <option value="rejected"' . ($status_filter === 'rejected' ? ' selected' : '') . '>Rejected</option>
                    <option value="failed_to_apply"' . ($status_filter === 'failed_to_apply' ? ' selected' : '') . '>Failed to Apply</option>
                    <option value="auto_ignored"' . ($status_filter === 'auto_ignored' ? ' selected' : '') . '>Ignored</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="sort" class="form-label">Sort By</label>
                <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                    <option value="recent"' . ($sort_by === 'recent' ? ' selected' : '') . '>Most Recent</option>
                    <option value="oldest"' . ($sort_by === 'oldest' ? ' selected' : '') . '>Oldest First</option>
                    <option value="confidence_high"' . ($sort_by === 'confidence_high' ? ' selected' : '') . '>Confidence (High to Low)</option>
                    <option value="confidence_low"' . ($sort_by === 'confidence_low' ? ' selected' : '') . '>Confidence (Low to High)</option>
                    <option value="occurrences"' . ($sort_by === 'occurrences' ? ' selected' : '') . '>Most Occurrences</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <a href="/admin/error-fix-dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset Filters
                </a>
            </div>
        </form>
    </div>';

// Results summary
echo '
    <div class="mb-3 text-muted">
        Showing ' . (empty($fixes) ? 0 : $offset + 1) . '-' . min($offset + $per_page, $total) . ' of ' . number_format($total) . ' fixes
    </div>';

// Fixes table
echo '
    <div class="fixes-table">';

if (empty($fixes)) {
    echo '
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
            <p class="mt-3">No error fixes found matching your filters</p>
        </div>';
} else {
    foreach ($fixes as $fix) {
        // Status badge
        $status_badge = match ($fix['fix_status']) {
            'pending_review' => '<span class="badge bg-warning text-dark">Pending Review</span>',
            'needs_manual_review' => '<span class="badge bg-secondary">Manual Review</span>',
            'approved_pending_apply' => '<span class="badge bg-info">Approved</span>',
            'applied' => '<span class="badge bg-success">Applied</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'failed_to_apply' => '<span class="badge bg-danger">Failed</span>',
            'auto_ignored' => '<span class="badge bg-secondary">Ignored</span>',
            default => '<span class="badge bg-light text-dark">' . htmlspecialchars($fix['fix_status']) . '</span>'
        };

        // Confidence badge
        $conf_class = $fix['ai_confidence'] >= 90 ? 'success' : ($fix['ai_confidence'] >= 75 ? 'info' : 'warning');
        $confidence_badge = '<span class="badge bg-' . $conf_class . '">' . $fix['ai_confidence'] . '%</span>';

        echo '
        <div class="fix-row">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <strong>Fix #' . $fix['fix_id'] . '</strong>
                        ' . $status_badge . '
                        ' . $confidence_badge . '
                        <span class="badge bg-light text-dark">' . htmlspecialchars($fix['ai_fix_type']) . '</span>
                    </div>
                    <div class="fix-file">
                        <i class="bi bi-file-code"></i> ' . htmlspecialchars($fix['error_file']) . ':' . $fix['error_line'] . '
                    </div>
                    <div class="fix-message">
                        <strong>' . htmlspecialchars($fix['error_type']) . ':</strong> ' . htmlspecialchars(substr($fix['error_message'], 0, 150)) . (strlen($fix['error_message']) > 150 ? '...' : '') . '
                    </div>
                    <div class="fix-meta">
                        <span><i class="bi bi-clock"></i> Last seen: ' . date('M j, Y g:i A', strtotime($fix['last_seen'])) . '</span>
                        <span><i class="bi bi-exclamation-circle"></i> Occurred: ' . $fix['occurrence_count'] . ' times</span>';

        if (!empty($fix['reviewed_dt'])) {
            echo '<span><i class="bi bi-person-check"></i> Reviewed: ' . date('M j, Y', strtotime($fix['reviewed_dt'])) . '</span>';
        }

        if (!empty($fix['applied_dt'])) {
            echo '<span><i class="bi bi-check-circle"></i> Applied: ' . date('M j, Y', strtotime($fix['applied_dt'])) . '</span>';
        }

        echo '
                    </div>
                </div>
                <div class="flex-shrink-0 ms-3">
                    <a href="/admin/error-fix-review.php?token=' . urlencode($fix['review_token']) . '" class="btn btn-sm btn-primary">
                        <i class="bi bi-eye"></i> View
                    </a>
                </div>
            </div>
        </div>';
    }
}

echo '
    </div>';

// Pagination
if ($total_pages > 1) {
    echo '<nav aria-label="Page navigation"><ul class="pagination">';

    // Previous
    if ($page > 1) {
        $prev_url = '?page=' . ($page - 1) . '&status=' . urlencode($status_filter) . '&sort=' . urlencode($sort_by);
        echo '<li class="page-item"><a class="page-link" href="' . $prev_url . '">Previous</a></li>';
    }

    // Page numbers (show 5 pages)
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);

    if ($start_page > 1) {
        echo '<li class="page-item"><a class="page-link" href="?page=1&status=' . urlencode($status_filter) . '&sort=' . urlencode($sort_by) . '">1</a></li>';
        if ($start_page > 2) {
            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = $i === $page ? ' active' : '';
        $page_url = '?page=' . $i . '&status=' . urlencode($status_filter) . '&sort=' . urlencode($sort_by);
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $page_url . '">' . $i . '</a></li>';
    }

    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&status=' . urlencode($status_filter) . '&sort=' . urlencode($sort_by) . '">' . $total_pages . '</a></li>';
    }

    // Next
    if ($page < $total_pages) {
        $next_url = '?page=' . ($page + 1) . '&status=' . urlencode($status_filter) . '&sort=' . urlencode($sort_by);
        echo '<li class="page-item"><a class="page-link" href="' . $next_url . '">Next</a></li>';
    }

    echo '</ul></nav>';
}

echo '</div>';

// Reset timestamp modal
$default_reset_time = date('Y-m-d') . 'T01:00';
echo '
<!-- Reset Timestamp Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Last Check Time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This will reset the last error check timestamp, causing the system to reprocess errors from the specified date.</p>
                <div class="mb-3">
                    <label for="resetDate" class="form-label">Reset to date/time:</label>
                    <input type="datetime-local" class="form-control" id="resetDate" value="' . $default_reset_time . '">
                </div>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> This may cause duplicate error entries if the same errors still exist in the logs.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="executeReset()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Timestamp
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let resetModal;

document.addEventListener(\'DOMContentLoaded\', function() {
    resetModal = new bootstrap.Modal(document.getElementById(\'resetModal\'));
});

function showResetModal() {
    resetModal.show();
}

function triggerJob() {
    const statusDiv = document.getElementById(\'action-status\');
    const btn = event.target.closest(\'button\');

    btn.disabled = true;
    btn.innerHTML = \'<span class="spinner-border spinner-border-sm"></span> Running...\';

    statusDiv.style.display = \'block\';
    statusDiv.className = \'mt-3 alert alert-info\';
    statusDiv.innerHTML = \'<i class="bi bi-hourglass-split"></i> Triggering error fixer job...\';

    fetch(\'/admin_actions/scheduler--auto-error-fixer.php\')
        .then(response => response.text())
        .then(output => {
            statusDiv.className = \'mt-3 alert alert-success\';
            statusDiv.innerHTML = \'<strong><i class="bi bi-check-circle"></i> Job triggered successfully!</strong><br>\' +
                \'<details class="mt-2"><summary>View output</summary><pre class="mt-2" style="max-height: 300px; overflow-y: auto; font-size: 0.75rem;">\' +
                escapeHtml(output) + \'</pre></details>\';

            // Reload page after 2 seconds to show updated stats
            setTimeout(() => window.location.reload(), 2000);
        })
        .catch(error => {
            statusDiv.className = \'mt-3 alert alert-danger\';
            statusDiv.innerHTML = \'<i class="bi bi-x-circle"></i> Error triggering job: \' + escapeHtml(error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = \'<i class="bi bi-play-circle"></i> Trigger Error Fixer Job\';
        });
}

function executeReset() {
    const dateInput = document.getElementById(\'resetDate\');
    const resetDate = dateInput.value.replace(\'T\', \' \') + \':00\';
    const statusDiv = document.getElementById(\'action-status\');

    resetModal.hide();

    statusDiv.style.display = \'block\';
    statusDiv.className = \'mt-3 alert alert-info\';
    statusDiv.innerHTML = \'<i class="bi bi-hourglass-split"></i> Resetting timestamp...\';

    const url = \'/admin_actions/scheduler--auto-error-fixer.php?reset=1&reset_date=\' + encodeURIComponent(resetDate);

    fetch(url)
        .then(response => response.text())
        .then(output => {
            statusDiv.className = \'mt-3 alert alert-success\';
            statusDiv.innerHTML = \'<strong><i class="bi bi-check-circle"></i> Timestamp reset successfully!</strong><br>\' +
                \'<small>Last check time reset to: \' + escapeHtml(resetDate) + \'</small><br>\' +
                \'<details class="mt-2"><summary>View output</summary><pre class="mt-2" style="max-height: 300px; overflow-y: auto; font-size: 0.75rem;">\' +
                escapeHtml(output) + \'</pre></details>\';
        })
        .catch(error => {
            statusDiv.className = \'mt-3 alert alert-danger\';
            statusDiv.innerHTML = \'<i class="bi bi-x-circle"></i> Error resetting timestamp: \' + escapeHtml(error.message);
        });
}

function escapeHtml(text) {
    const div = document.createElement(\'div\');
    div.textContent = text;
    return div.innerHTML;
}
</script>';

// Include footer
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
