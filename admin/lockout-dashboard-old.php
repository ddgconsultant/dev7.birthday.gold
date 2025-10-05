<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin only access
if (!$account->isadmin()) {
    header('Location: /myaccount/');
    exit;
}

$pagetitle = "Lockout Dashboard";
$page_title = "Lockout Dashboard - Birthday.Gold Admin";
$page_description = "Monitor and manage rate limit lockouts";

// Handle actions
if ($app->formposted() && isset($_POST['action'])) {
    $lockout_id = $_POST['lockout_id'] ?? 0;
    $action = $_POST['action'];

    switch ($action) {
        case 'unlock':
            $sql = "UPDATE bg_lockout
                    SET expire_dt = NOW(),
                        modify_dt = NOW()
                    WHERE id = :id";
            $database->query($sql, ['id' => $lockout_id]);
            $_SESSION['admin_message'] = 'IP unlocked successfully.';
            break;

        case 'reset_level':
            $sql = "UPDATE bg_lockout
                    SET lockout_level = 1,
                        expire_dt = NOW(),
                        modify_dt = NOW()
                    WHERE id = :id";
            $database->query($sql, ['id' => $lockout_id]);
            $_SESSION['admin_message'] = 'Lockout level reset to 1.';
            break;

        case 'whitelist':
            $sql = "UPDATE bg_lockout
                    SET status = 'never_block',
                        expire_dt = NOW(),
                        modify_dt = NOW()
                    WHERE id = :id";
            $database->query($sql, ['id' => $lockout_id]);
            $_SESSION['admin_message'] = 'IP added to whitelist.';
            break;
    }

    header('Location: /admin/lockout-dashboard.php');
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'active';

// Get statistics
$stats_sql = "SELECT
    COUNT(*) as total_lockouts,
    COUNT(CASE WHEN expire_dt > NOW() AND status = 'active' THEN 1 END) as active_lockouts,
    COUNT(CASE WHEN status = 'never_block' THEN 1 END) as whitelisted,
    SUM(total_violations) as total_violations_all_time,
    AVG(lockout_level) as avg_level,
    MAX(lockout_level) as max_level
FROM bg_lockout";
$stats = $database->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

// Get severity distribution
$severity_sql = "SELECT
    CASE
        WHEN lockout_level <= 5 THEN 'Minor'
        WHEN lockout_level <= 10 THEN 'Moderate'
        WHEN lockout_level <= 15 THEN 'Severe'
        ELSE 'Critical'
    END as severity,
    COUNT(*) as count,
    CASE
        WHEN lockout_level <= 5 THEN 'success'
        WHEN lockout_level <= 10 THEN 'warning'
        WHEN lockout_level <= 15 THEN 'danger'
        ELSE 'dark'
    END as badge_class
FROM bg_lockout
WHERE status = 'active' AND expire_dt > NOW()
GROUP BY severity, badge_class
ORDER BY
    CASE severity
        WHEN 'Minor' THEN 1
        WHEN 'Moderate' THEN 2
        WHEN 'Severe' THEN 3
        WHEN 'Critical' THEN 4
    END";
$severity_dist = $database->query($severity_sql)->fetchAll(PDO::FETCH_ASSOC);

// Build query based on filter
$where_clause = "WHERE 1=1";
$params = [];

if ($filter === 'active') {
    $where_clause .= " AND l.expire_dt > NOW() AND l.status = 'active'";
} elseif ($filter === 'expired') {
    $where_clause .= " AND l.expire_dt <= NOW() AND l.status = 'active'";
} elseif ($filter === 'whitelisted') {
    $where_clause .= " AND l.status = 'never_block'";
} elseif ($filter === 'critical') {
    $where_clause .= " AND l.lockout_level >= 16 AND l.status = 'active'";
}

// Get lockouts
$lockouts_sql = "SELECT
    l.id,
    l.ip,
    l.type,
    l.session_id,
    l.first_violation_dt,
    l.last_violation_dt,
    l.total_violations,
    l.lockout_level,
    l.expire_dt,
    l.status,
    l.create_dt,
    CASE
        WHEN l.lockout_level <= 5 THEN 'Minor'
        WHEN l.lockout_level <= 10 THEN 'Moderate'
        WHEN l.lockout_level <= 15 THEN 'Severe'
        ELSE 'Critical'
    END as severity,
    CASE
        WHEN l.lockout_level <= 5 THEN 'success'
        WHEN l.lockout_level <= 10 THEN 'warning'
        WHEN l.lockout_level <= 15 THEN 'danger'
        ELSE 'dark'
    END as badge_class,
    TIMESTAMPDIFF(MINUTE, NOW(), l.expire_dt) as minutes_remaining,
    (SELECT COUNT(*) FROM bg_lockout_history WHERE parent_id = l.id) as history_count
FROM bg_lockout l
$where_clause
ORDER BY l.lockout_level DESC, l.last_violation_dt DESC
LIMIT 100";

$lockouts = $database->query($lockouts_sql, $params)->fetchAll(PDO::FETCH_ASSOC);

// Helper function to format duration
function formatDuration($minutes) {
    if ($minutes <= 0) return 'Expired';
    if ($minutes < 60) return $minutes . ' min';
    if ($minutes < 1440) return round($minutes / 60, 1) . ' hrs';
    return round($minutes / 1440, 1) . ' days';
}

// Helper function to get level emoji
function getLevelEmoji($level) {
    if ($level <= 5) return '🟢';
    if ($level <= 10) return '🟡';
    if ($level <= 15) return '🟠';
    return '🔴';
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
echo '
<style>
.lockout-dashboard {
    padding: 2rem 0;
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
    border: 1px solid #e9ecef;
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    background: white;
    border: 1px solid #dee2e6;
    color: #495057;
    text-decoration: none;
    transition: all 0.2s;
}

.filter-tab:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.filter-tab.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.lockout-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.lockout-table table {
    margin: 0;
}

.lockout-table th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #495057;
}

.ip-cell {
    font-family: "Courier New", monospace;
    font-weight: 600;
}

.level-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.action-btn {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

.severity-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.severity-card {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #e9ecef;
    text-align: center;
}

.modal-body table {
    font-size: 0.875rem;
}

.history-timeline {
    max-height: 300px;
    overflow-y: auto;
}

.timeline-item {
    padding: 0.5rem;
    border-left: 3px solid #dee2e6;
    margin-left: 1rem;
    margin-bottom: 0.5rem;
}

.timeline-item:hover {
    background: #f8f9fa;
    border-left-color: #0d6efd;
}
</style>

<div class="container-fluid lockout-dashboard">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🔒 Lockout Dashboard</h1>
            <p class="text-muted mb-0">Monitor and manage rate limit lockouts</p>
        </div>
        <a href="/admin" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Admin
        </a>
    </div>
';

if (isset($_SESSION['admin_message'])) {
    echo '
    <div class="alert alert-success alert-dismissible fade show">
        ' . $_SESSION['admin_message'] . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['admin_message']);
}

echo '

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value text-primary">' . number_format($stats['total_lockouts']) . '</div>
            <div class="stat-label">Total Lockouts</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-danger">' . number_format($stats['active_lockouts']) . '</div>
            <div class="stat-label">Active Now</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-success">' . number_format($stats['whitelisted']) . '</div>
            <div class="stat-label">Whitelisted</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-warning">' . number_format($stats['total_violations_all_time']) . '</div>
            <div class="stat-label">Total Violations</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">' . round($stats['avg_level'], 1) . '</div>
            <div class="stat-label">Avg Level</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-dark">' . $stats['max_level'] . '</div>
            <div class="stat-label">Max Level</div>
        </div>
    </div>

    <!-- Severity Distribution -->
    <?php if (!empty($severity_dist)): ?>
    <div class="severity-cards">
        <?php foreach ($severity_dist as $sev): ?>
        <div class="severity-card">
            <div class="h3 mb-1 text-<?= $sev["badge_class"] ?>"><?= $sev["count"] ?></div>
            <div class="text-muted small"><?= $sev["severity"] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?filter=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">
            🔴 Active Lockouts
        </a>
        <a href="?filter=expired" class="filter-tab <?= $filter === 'expired' ? 'active' : '' ?>">
            ⚪ Expired
        </a>
        <a href="?filter=critical" class="filter-tab <?= $filter === 'critical' ? 'active' : '' ?>">
            🔥 Critical (Level 16+)
        </a>
        <a href="?filter=whitelisted" class="filter-tab <?= $filter === 'whitelisted' ? 'active' : '' ?>">
            ✅ Whitelisted
        </a>
        <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
            📋 All
        </a>
    </div>

    <!-- Lockouts Table -->
    <div class="lockout-table">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Level</th>
                    <th>Severity</th>
                    <th>Violations</th>
                    <th>First / Last</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lockouts)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        No lockouts found for this filter
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($lockouts as $lock): ?>
                <tr>
                    <td>
                        <span class="ip-cell"><?= htmlspecialchars($lock['ip']) ?></span>
                        <button class="btn btn-sm btn-link p-0 ms-2"
                                onclick="showHistory(<?= $lock['id'] ?>, '<?= htmlspecialchars($lock['ip']) ?>')">
                            <i class="bi bi-clock-history"></i> <?= $lock['history_count'] ?>
                        </button>
                    </td>
                    <td>
                        <span class="level-badge">
                            <?= getLevelEmoji($lock['lockout_level']) ?>
                            <strong><?= $lock['lockout_level'] ?></strong>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $lock['badge_class'] ?>">
                            <?= $lock['severity'] ?>
                        </span>
                    </td>
                    <td><?= number_format($lock['total_violations']) ?></td>
                    <td class="small">
                        <?= date('M j, g:ia', strtotime($lock['first_violation_dt'])) ?><br>
                        <?= date('M j, g:ia', strtotime($lock['last_violation_dt'])) ?>
                    </td>
                    <td>
                        <?php if ($lock['minutes_remaining'] > 0): ?>
                            <span class="text-danger"><?= formatDuration($lock['minutes_remaining']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">Expired</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($lock['status'] === 'never_block'): ?>
                            <span class="badge bg-success">Whitelisted</span>
                        <?php elseif ($lock['minutes_remaining'] > 0): ?>
                            <span class="badge bg-danger">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <?php if ($lock['status'] !== 'never_block' && $lock['minutes_remaining'] > 0): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="lockout_id" value="<?= $lock['id'] ?>">
                                <input type="hidden" name="action" value="unlock">
                                <button type="submit" class="action-btn btn btn-outline-success"
                                        onclick="return confirm('Unlock this IP?')">
                                    <i class="bi bi-unlock"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="lockout_id" value="<?= $lock['id'] ?>">
                                <input type="hidden" name="action" value="reset_level">
                                <button type="submit" class="action-btn btn btn-outline-warning"
                                        onclick="return confirm('Reset level to 1?')">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="lockout_id" value="<?= $lock['id'] ?>">
                                <input type="hidden" name="action" value="whitelist">
                                <button type="submit" class="action-btn btn btn-outline-info"
                                        onclick="return confirm('Add to whitelist (never block)?')">
                                    <i class="bi bi-shield-check"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lockout History - <span id="modal-ip"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="history-content">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
function showHistory(lockoutId, ip) {
    document.getElementById('modal-ip').textContent = ip;
    const modal = new bootstrap.Modal(document.getElementById('historyModal'));
    modal.show();

    // Fetch history via AJAX
    fetch(`/admin/ajax/get-lockout-history.php?id=${lockoutId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="history-timeline">';
                data.history.forEach(h => {
                    html += `
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>Level ${h.level}</strong> - ${h.lockout_minutes} min
                                </div>
                                <small class="text-muted">${h.create_dt}</small>
                            </div>
                            <div class="small text-muted">
                                Expires: ${h.expire_dt}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';

                html += `
                    <table class="table table-sm mt-3">
                        <tr><th>Total Violations:</th><td>${data.parent.total_violations}</td></tr>
                        <tr><th>Current Level:</th><td>${data.parent.lockout_level}</td></tr>
                        <tr><th>First Violation:</th><td>${data.parent.first_violation_dt}</td></tr>
                        <tr><th>Last Violation:</th><td>${data.parent.last_violation_dt}</td></tr>
                    </table>
                `;

                document.getElementById('history-content').innerHTML = html;
            } else {
                document.getElementById('history-content').innerHTML = '<div class="alert alert-danger">Failed to load history</div>';
            }
        })
        .catch(error => {
            document.getElementById('history-content').innerHTML = '<div class="alert alert-danger">Error loading history</div>';
        });
}
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
