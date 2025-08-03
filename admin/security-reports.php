<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin only access
if (!$account->isadmin()) {
    header('Location: /myaccount/');
    exit;
}

$pagetitle = "Security Reports";

// Handle actions
if ($app->formposted() && isset($_POST['action'])) {
    $report_id = $_POST['report_id'] ?? 0;
    $action = $_POST['action'];
    
    switch ($action) {
        case 'resolve':
            $sql = "UPDATE bg_user_attributes 
                    SET status = 'resolved', modify_dt = NOW() 
                    WHERE attribute_id = :report_id 
                    AND type = 'security_report'";
            $database->query($sql, [':report_id' => $report_id]);
            $_SESSION['admin_message'] = 'Report marked as resolved.';
            break;
            
        case 'dismiss':
            $sql = "UPDATE bg_user_attributes 
                    SET status = 'dismissed', modify_dt = NOW() 
                    WHERE attribute_id = :report_id 
                    AND type = 'security_report'";
            $database->query($sql, [':report_id' => $report_id]);
            $_SESSION['admin_message'] = 'Report dismissed.';
            break;
            
        case 'block_device':
            // Get the device ID from the report
            $sql = "SELECT user_id, string_value FROM bg_user_attributes 
                    WHERE attribute_id = :report_id 
                    AND type = 'security_report'";
            $stmt = $database->query($sql, [':report_id' => $report_id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($report) {
                // Mark the device as blocked
                $sql = "UPDATE bg_user_attributes 
                        SET status = 'blocked', modify_dt = NOW() 
                        WHERE user_id = :user_id 
                        AND type = 'bg_rememberme_set' 
                        AND name = :device_id";
                $database->query($sql, [
                    ':user_id' => $report['user_id'],
                    ':device_id' => $report['string_value']
                ]);
                
                // Also update the report
                $sql = "UPDATE bg_user_attributes 
                        SET status = 'actioned', modify_dt = NOW() 
                        WHERE attribute_id = :report_id";
                $database->query($sql, [':report_id' => $report_id]);
                
                $_SESSION['admin_message'] = 'Device has been blocked.';
            }
            break;
    }
    
    header('Location: /admin/security-reports.php');
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'pending';

// Build query based on filter
$where_clause = "WHERE ua.type = 'security_report'";
if ($filter !== 'all') {
    $where_clause .= " AND ua.status = :status";
}

// Get reports with user information
$sql = "SELECT 
        ua.attribute_id,
        ua.user_id,
        ua.description,
        ua.string_value as device_id,
        ua.status,
        ua.create_dt,
        ua.modify_dt,
        u.username,
        u.email,
        u.first_name,
        u.last_name
    FROM bg_user_attributes ua
    LEFT JOIN bg_users u ON ua.user_id = u.user_id
    $where_clause
    ORDER BY ua.create_dt DESC";

$params = [];
if ($filter !== 'all') {
    $params[':status'] = $filter;
}

$stmt = $database->query($sql, $params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count reports by status
$count_sql = "SELECT status, COUNT(*) as count 
              FROM bg_user_attributes 
              WHERE type = 'security_report' 
              GROUP BY status";
$count_stmt = $database->query($count_sql);
$status_counts = $count_stmt->fetchAll(PDO::FETCH_ASSOC);
$counts = [];
foreach ($status_counts as $row) {
    $counts[$row['status']] = $row['count'];
}
$counts['all'] = array_sum($counts);

// Additional styles
$additionalstyles = '
<style>
.security-reports-container {
    max-width: 1400px;
    margin: 0 auto;
}

.status-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0;
}

.status-tab {
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
}

.status-tab:hover {
    color: #495057;
    text-decoration: none;
}

.status-tab.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 20px;
    text-transform: uppercase;
}

.status-badge.pending {
    background: #ffc107;
    color: #000;
}

.status-badge.resolved {
    background: #28a745;
    color: #fff;
}

.status-badge.dismissed {
    background: #6c757d;
    color: #fff;
}

.status-badge.actioned {
    background: #17a2b8;
    color: #fff;
}

.report-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
}

.report-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.report-header {
    padding: 1.25rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: start;
}

.report-user {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.report-time {
    color: #6c757d;
    font-size: 0.875rem;
}

.report-body {
    padding: 1.25rem;
}

.device-info {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.info-row {
    display: flex;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #495057;
    width: 120px;
    flex-shrink: 0;
}

.info-value {
    color: #212529;
    word-break: break-all;
}

.report-actions {
    padding: 1rem 1.25rem;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 0.5rem;
}

.report-json {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1rem;
    font-family: monospace;
    font-size: 0.813rem;
    max-height: 300px;
    overflow-y: auto;
    white-space: pre-wrap;
}
</style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Admin Section -->
<div class="content-header-admin">
    <div class="container">
        <h1 class="mt-3">Security Reports</h1>
        <p class="lead mb-4">Review and manage device security reports from users</p>
    </div>
</div>

<div class="container mt-4">
    <div class="security-reports-container">
        
        <?php
        // Display success message if exists
        if (isset($_SESSION['admin_message'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    ' . htmlspecialchars($_SESSION['admin_message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
            unset($_SESSION['admin_message']);
        }
        ?>
        
        <!-- Status filter tabs -->
        <div class="status-tabs">
            <a href="?filter=pending" class="status-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                <i class="bi bi-exclamation-circle me-2"></i>Pending
                <span class="badge bg-warning text-dark ms-2"><?php echo $counts['pending'] ?? 0; ?></span>
            </a>
            <a href="?filter=resolved" class="status-tab <?php echo $filter === 'resolved' ? 'active' : ''; ?>">
                <i class="bi bi-check-circle me-2"></i>Resolved
                <span class="badge bg-success ms-2"><?php echo $counts['resolved'] ?? 0; ?></span>
            </a>
            <a href="?filter=actioned" class="status-tab <?php echo $filter === 'actioned' ? 'active' : ''; ?>">
                <i class="bi bi-shield-x me-2"></i>Actioned
                <span class="badge bg-info ms-2"><?php echo $counts['actioned'] ?? 0; ?></span>
            </a>
            <a href="?filter=dismissed" class="status-tab <?php echo $filter === 'dismissed' ? 'active' : ''; ?>">
                <i class="bi bi-x-circle me-2"></i>Dismissed
                <span class="badge bg-secondary ms-2"><?php echo $counts['dismissed'] ?? 0; ?></span>
            </a>
            <a href="?filter=all" class="status-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="bi bi-list me-2"></i>All Reports
                <span class="badge bg-primary ms-2"><?php echo $counts['all'] ?? 0; ?></span>
            </a>
        </div>
        
        <!-- Reports list -->
        <?php if (empty($reports)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="mt-3 text-muted">No <?php echo $filter !== 'all' ? $filter : ''; ?> security reports found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($reports as $report): 
                $report_data = json_decode($report['description'], true) ?? [];
                $device_data = $report_data['device_data'] ?? [];
            ?>
                <div class="report-card">
                    <div class="report-header">
                        <div>
                            <div class="report-user">
                                <i class="bi bi-person-circle me-2"></i>
                                <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($report['username']); ?>)</small>
                            </div>
                            <div class="report-time">
                                <i class="bi bi-clock me-1"></i>
                                Reported on <?php echo date('M j, Y \a\t g:i A', strtotime($report['create_dt'])); ?>
                            </div>
                        </div>
                        <span class="status-badge <?php echo $report['status']; ?>">
                            <?php echo $report['status']; ?>
                        </span>
                    </div>
                    
                    <div class="report-body">
                        <h6 class="mb-3">Reported Device Information</h6>
                        
                        <div class="device-info">
                            <div class="info-row">
                                <span class="info-label">Device ID:</span>
                                <span class="info-value"><?php echo htmlspecialchars($report['device_id']); ?></span>
                            </div>
                            <?php if (!empty($device_data['browser'])): ?>
                            <div class="info-row">
                                <span class="info-label">Browser:</span>
                                <span class="info-value"><?php echo htmlspecialchars($device_data['browser']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($device_data['os'])): ?>
                            <div class="info-row">
                                <span class="info-label">OS:</span>
                                <span class="info-value"><?php echo htmlspecialchars($device_data['os']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($device_data['deviceType'])): ?>
                            <div class="info-row">
                                <span class="info-label">Device Type:</span>
                                <span class="info-value"><?php echo htmlspecialchars($device_data['deviceType']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($device_data['location_string'])): ?>
                            <div class="info-row">
                                <span class="info-label">Location:</span>
                                <span class="info-value"><?php echo htmlspecialchars($device_data['location_string']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($report_data['reported_at'])): ?>
                            <div class="info-row">
                                <span class="info-label">Report Time:</span>
                                <span class="info-value"><?php echo htmlspecialchars($report_data['reported_at']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($report_data['user_ip'])): ?>
                            <div class="info-row">
                                <span class="info-label">Reporter IP:</span>
                                <span class="info-value"><?php echo htmlspecialchars($report_data['user_ip']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <details class="mt-3">
                            <summary class="text-muted" style="cursor: pointer;">
                                <i class="bi bi-code-slash me-2"></i>View Raw Data
                            </summary>
                            <div class="report-json mt-2">
                                <?php echo htmlspecialchars(json_encode($report_data, JSON_PRETTY_PRINT)); ?>
                            </div>
                        </details>
                    </div>
                    
                    <?php if ($report['status'] === 'pending'): ?>
                    <div class="report-actions">
                        <button type="button" class="btn btn-danger btn-sm" 
                                onclick="showBlockModal(<?php echo $report['attribute_id']; ?>, '<?php echo htmlspecialchars($report['device_id']); ?>', '<?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>')">
                            <i class="bi bi-shield-x me-1"></i>Block Device
                        </button>
                        
                        <form method="POST" style="display: inline;">
                            <?php echo $display->inputcsrf_token(); ?>
                            <input type="hidden" name="action" value="resolve">
                            <input type="hidden" name="report_id" value="<?php echo $report['attribute_id']; ?>">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-check-circle me-1"></i>Mark Resolved
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <?php echo $display->inputcsrf_token(); ?>
                            <input type="hidden" name="action" value="dismiss">
                            <input type="hidden" name="report_id" value="<?php echo $report['attribute_id']; ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <i class="bi bi-x-circle me-1"></i>Dismiss
                            </button>
                        </form>
                        
                        <a href="/admin/user-details.php?id=<?php echo $qik->encodeId($report['user_id']); ?>" 
                           class="btn btn-outline-primary btn-sm ms-auto">
                            <i class="bi bi-person me-1"></i>View User
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
    </div>
</div>

<!-- Block Device Modal -->
<div class="modal fade" id="blockDeviceModal" tabindex="-1" aria-labelledby="blockDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white" id="blockDeviceModalLabel">
                    <i class="bi bi-shield-x me-2"></i>Block Device
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Warning:</strong> This action will block the device from accessing the user's account.
                </div>
                
                <p>You are about to block the following device:</p>
                
                <div class="bg-light p-3 rounded mb-3">
                    <div class="mb-2">
                        <strong>User:</strong> <span id="blockDeviceUser"></span>
                    </div>
                    <div>
                        <strong>Device ID:</strong><br>
                        <small class="text-muted" id="blockDeviceId"></small>
                    </div>
                </div>
                
                <p>The user will no longer be able to access their account from this device without logging in again.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <?php echo $display->inputcsrf_token(); ?>
                    <input type="hidden" name="action" value="block_device">
                    <input type="hidden" name="report_id" id="blockReportId" value="">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-shield-x me-1"></i>Yes, Block Device
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showBlockModal(reportId, deviceId, userName) {
    document.getElementById('blockReportId').value = reportId;
    document.getElementById('blockDeviceId').textContent = deviceId;
    document.getElementById('blockDeviceUser').textContent = userName;
    
    const modal = new bootstrap.Modal(document.getElementById('blockDeviceModal'));
    modal.show();
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>