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
<!-- Hero Section -->
<div class="content-header-admin">
    <div class="container">
        <h1 class="mt-3"><i class="bi bi-lock-fill"></i> Lockout Dashboard</h1>
        <p class="lead mb-4">Monitor and manage rate limit lockouts across the platform</p>
    </div>
</div>

<style>
.lockout-dashboard { padding: 2rem 0; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.stat-card { background: white; border-radius: 12px; padding: 1.5rem; border: 1px solid #e9ecef; text-align: center; }
.stat-value { font-size: 2rem; font-weight: 700; color: #212529; margin-bottom: 0.5rem; }
.stat-label { font-size: 0.875rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
.filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
.filter-tab { padding: 0.5rem 1rem; border-radius: 8px; background: white; border: 1px solid #dee2e6; color: #495057; text-decoration: none; transition: all 0.2s; }
.filter-tab:hover { border-color: #0d6efd; color: #0d6efd; }
.filter-tab.active { background: #0d6efd; border-color: #0d6efd; color: white; }
.lockout-table { background: white; border-radius: 12px; overflow: hidden; border: 1px solid #e9ecef; }
.lockout-table table { margin: 0; }
.lockout-table th { background: #f8f9fa; font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px; color: #495057; }
.ip-cell { font-family: "Courier New", monospace; font-weight: 600; color: #0d6efd; cursor: pointer; }
.ip-cell:hover { text-decoration: underline; color: #0a58ca; }
.level-badge { display: inline-flex; align-items: center; gap: 0.25rem; }
.action-btn { padding: 0.25rem 0.75rem; font-size: 0.875rem; border-radius: 6px; }
.severity-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.severity-card { background: white; border-radius: 8px; padding: 1rem; border: 1px solid #e9ecef; text-align: center; }
.history-timeline { max-height: 300px; overflow-y: auto; }
.timeline-item { padding: 0.5rem; border-left: 3px solid #dee2e6; margin-left: 1rem; margin-bottom: 0.5rem; }
.timeline-item:hover { background: #f8f9fa; border-left-color: #0d6efd; }
</style>

<div class="container-fluid px-5 lockout-dashboard">
    <div class="d-flex justify-content-end mb-4">
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
';

if (!empty($severity_dist)) {
    echo '<div class="severity-cards">';
    foreach ($severity_dist as $sev) {
        echo '
        <div class="severity-card">
            <div class="h3 mb-1 text-' . $sev['badge_class'] . '">' . $sev['count'] . '</div>
            <div class="text-muted small">' . $sev['severity'] . '</div>
        </div>';
    }
    echo '</div>';
}

echo '
    <div class="filter-tabs">
        <a href="?filter=active" class="filter-tab ' . ($filter === 'active' ? 'active' : '') . '">🔴 Active Lockouts</a>
        <a href="?filter=expired" class="filter-tab ' . ($filter === 'expired' ? 'active' : '') . '">⚪ Expired</a>
        <a href="?filter=critical" class="filter-tab ' . ($filter === 'critical' ? 'active' : '') . '">🔥 Critical (Level 16+)</a>
        <a href="?filter=whitelisted" class="filter-tab ' . ($filter === 'whitelisted' ? 'active' : '') . '">✅ Whitelisted</a>
        <a href="?filter=all" class="filter-tab ' . ($filter === 'all' ? 'active' : '') . '">📋 All</a>
    </div>

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
';

if (empty($lockouts)) {
    echo '<tr><td colspan="8" class="text-center py-4 text-muted">No lockouts found for this filter</td></tr>';
} else {
    foreach ($lockouts as $lock) {
        $statusBadge = '';
        if ($lock['status'] === 'never_block') {
            $statusBadge = '<span class="badge bg-success">Whitelisted</span>';
        } elseif ($lock['minutes_remaining'] > 0) {
            $statusBadge = '<span class="badge bg-danger">Active</span>';
        } else {
            $statusBadge = '<span class="badge bg-secondary">Inactive</span>';
        }

        echo '
                <tr>
                    <td>
                        <a href="#" class="ip-cell text-decoration-none"
                           onclick="analyzeIP(\'' . htmlspecialchars($lock['ip']) . '\'); return false;"
                           data-bs-toggle="tooltip" data-bs-placement="top"
                           title="Click to analyze this IP address">
                            ' . htmlspecialchars($lock['ip']) . '
                        </a>
                    </td>
                    <td>
                        <span class="level-badge">
                            ' . getLevelEmoji($lock['lockout_level']) . '
                            <strong>' . $lock['lockout_level'] . '</strong>
                        </span>
                    </td>
                    <td><span class="badge bg-' . $lock['badge_class'] . '">' . $lock['severity'] . '</span></td>
                    <td>
                        <button class="btn btn-sm btn-link p-0"
                                onclick="showHistory(' . $lock['id'] . ', \'' . htmlspecialchars($lock['ip']) . '\')">
                            ' . number_format($lock['total_violations']) . '
                            <i class="bi bi-clock-history ms-1"></i>
                        </button>
                    </td>
                    <td class="small">
                        ' . date('M j, g:ia', strtotime($lock['first_violation_dt'])) . '<br>
                        ' . date('M j, g:ia', strtotime($lock['last_violation_dt'])) . '
                    </td>
                    <td>' . ($lock['minutes_remaining'] > 0 ? '<span class="text-danger">' . $qik->formatDuration($lock['minutes_remaining']) . '</span>' : '<span class="text-muted">Expired</span>') . '</td>
                    <td>' . $statusBadge . '</td>
                    <td>';

        if ($lock['status'] !== 'never_block' && $lock['minutes_remaining'] > 0) {
            echo '
                        <div class="btn-group btn-group-sm">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="lockout_id" value="' . $lock['id'] . '">
                                <input type="hidden" name="action" value="unlock">
                                <button type="submit" class="action-btn btn btn-outline-success"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Unlock this IP immediately"
                                        onclick="return confirm(\'Unlock this IP?\')">
                                    <i class="bi bi-unlock"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="lockout_id" value="' . $lock['id'] . '">
                                <input type="hidden" name="action" value="reset_level">
                                <button type="submit" class="action-btn btn btn-outline-warning"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Reset lockout level to 1"
                                        onclick="return confirm(\'Reset level to 1?\')">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="lockout_id" value="' . $lock['id'] . '">
                                <input type="hidden" name="action" value="whitelist">
                                <button type="submit" class="action-btn btn btn-outline-info"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Add to whitelist (never block again)"
                                        onclick="return confirm(\'Add to whitelist (never block)?\')">
                                    <i class="bi bi-shield-check"></i>
                                </button>
                            </form>
                        </div>';
        }

        echo '
                    </td>
                </tr>';
    }
}

echo '
            </tbody>
        </table>
    </div>
</div>

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

<div class="modal fade" id="ipAnalysisModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-globe"></i> IP Address Analysis - <span id="analysis-modal-ip"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ip-analysis-content">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Analyzing IP address...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Bootstrap tooltips
document.addEventListener(\'DOMContentLoaded\', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function showHistory(lockoutId, ip) {
    document.getElementById("modal-ip").textContent = ip;
    const modal = new bootstrap.Modal(document.getElementById("historyModal"));
    modal.show();

    fetch("/admin/ajax/get-lockout-history.php?id=" + lockoutId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = \'<div class="history-timeline">\';
                data.history.forEach(h => {
                    html += `
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <div><strong>Level ${h.level}</strong> - ${h.lockout_minutes} min</div>
                                <small class="text-muted">${h.create_dt}</small>
                            </div>
                            <div class="small text-muted">Expires: ${h.expire_dt}</div>
                        </div>
                    `;
                });
                html += \'</div>\';
                html += `
                    <table class="table table-sm mt-3">
                        <tr><th>Total Violations:</th><td>${data.parent.total_violations}</td></tr>
                        <tr><th>Current Level:</th><td>${data.parent.lockout_level}</td></tr>
                        <tr><th>First Violation:</th><td>${data.parent.first_violation_dt}</td></tr>
                        <tr><th>Last Violation:</th><td>${data.parent.last_violation_dt}</td></tr>
                    </table>
                `;
                document.getElementById("history-content").innerHTML = html;
            } else {
                document.getElementById("history-content").innerHTML = \'<div class="alert alert-danger">Failed to load history</div>\';
            }
        })
        .catch(error => {
            document.getElementById("history-content").innerHTML = \'<div class="alert alert-danger">Error loading history</div>\';
        });
}

function analyzeIP(ip) {
    document.getElementById("analysis-modal-ip").textContent = ip;
    const modal = new bootstrap.Modal(document.getElementById("ipAnalysisModal"));
    modal.show();

    // Reset content with loading spinner
    document.getElementById("ip-analysis-content").innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Analyzing IP address...</p>
        </div>
    `;

    fetch("/admin/ajax/analyze-ip.php?ip=" + encodeURIComponent(ip))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = \'\';

                // Current Lockout Status
                if (data.current_lockout) {
                    const current = data.current_lockout;
                    const isActive = current.minutes_remaining > 0;
                    html += `
                        <div class="alert ${isActive ? \'alert-danger\' : \'alert-secondary\'} mb-4">
                            <h5 class="alert-heading">
                                <i class="bi bi-lock-fill"></i> Current Lockout Status
                            </h5>
                            <div class="row">
                                <div class="col-md-3"><strong>Level:</strong> ${current.lockout_level}</div>
                                <div class="col-md-3"><strong>Violations:</strong> ${current.total_violations}</div>
                                <div class="col-md-3"><strong>Status:</strong> ${isActive ? \'🔴 Active\' : \'⚪ Inactive\'}</div>
                                <div class="col-md-3"><strong>Expires:</strong> ${isActive ? current.minutes_remaining + \' min\' : \'Expired\'}</div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6"><strong>First Violation:</strong> ${current.first_violation_dt}</div>
                                <div class="col-md-6"><strong>Last Violation:</strong> ${current.last_violation_dt}</div>
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="alert alert-success mb-4">
                            <i class="bi bi-check-circle"></i> No active lockout for this IP address
                        </div>
                    `;
                }

                // Basic IP Info
                if (data.basic_info && Object.keys(data.basic_info).length > 0) {
                    const info = data.basic_info;
                    html += `
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Geographic & Network Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Country:</strong> ${info.country || \'N/A\'} (${info.countryCode || \'N/A\'})</p>
                                        <p class="mb-2"><strong>Region:</strong> ${info.regionName || \'N/A\'}</p>
                                        <p class="mb-2"><strong>City:</strong> ${info.city || \'N/A\'}</p>
                                        <p class="mb-2"><strong>Timezone:</strong> ${info.timezone || \'N/A\'}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>ISP:</strong> ${info.isp || \'N/A\'}</p>
                                        <p class="mb-2"><strong>Organization:</strong> ${info.org || \'N/A\'}</p>
                                        <p class="mb-2"><strong>AS:</strong> ${info.as || \'N/A\'}</p>
                                        ${info.proxy ? \'<p class="mb-2 text-warning"><strong>⚠️ Proxy Detected</strong></p>\' : \'\'}
                                        ${info.hosting ? \'<p class="mb-2 text-info"><strong>🖥️ Hosting/Datacenter IP</strong></p>\' : \'\'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                // AI Analysis
                html += `
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-robot"></i> AI-Powered Threat Intelligence</h6>
                        </div>
                        <div class="card-body">
                            <div style="white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">
                                ${data.ai_analysis ? data.ai_analysis.replace(/\\n/g, \'<br>\') : \'Analysis unavailable\'}
                            </div>
                        </div>
                    </div>
                `;

                // Lockout History
                if (data.history && data.history.length > 0) {
                    html += `
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Lockout History (Last 10 Events)</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Level</th>
                                                <th>Duration</th>
                                                <th>Type</th>
                                                <th>Start Date</th>
                                                <th>Expire Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    data.history.forEach(h => {
                        html += `
                            <tr>
                                <td><span class="badge bg-secondary">Level ${h.level}</span></td>
                                <td>${h.lockout_minutes} min</td>
                                <td><code>${h.type}</code></td>
                                <td class="small">${h.start_dt}</td>
                                <td class="small">${h.expire_dt}</td>
                                <td><span class="badge bg-${h.status === \'active\' ? \'danger\' : \'secondary\'}">${h.status}</span></td>
                            </tr>
                        `;
                    });

                    html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No lockout history found for this IP address
                        </div>
                    `;
                }

                document.getElementById("ip-analysis-content").innerHTML = html;
            } else {
                document.getElementById("ip-analysis-content").innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> Failed to analyze IP: ${data.error || \'Unknown error\'}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById("ip-analysis-content").innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Error loading IP analysis: ${error.message}
                </div>
            `;
        });
}
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
