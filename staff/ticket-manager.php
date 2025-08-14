<?php
// Ticket Management Dashboard
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Staff access is handled by site-controller.php
$pagetitle = "Ticket Management";

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $ticket_id = intval($_POST['ticket_id']);
        $new_status = $_POST['status'];
        $user_id = $current_user_data['user_id'];
        
        try {
            // Update ticket status
            $sql = "UPDATE bg_tickets SET status = :status, modified_dt = NOW() WHERE ticket_id = :ticket_id";
            $database->query($sql, ['status' => $new_status, 'ticket_id' => $ticket_id]);
            
            // Add status change comment
            $sql = "INSERT INTO bg_ticket_comments (ticket_id, user_id, comment_type, comment, created_dt) 
                    VALUES (:ticket_id, :user_id, 'status_change', :comment, NOW())";
            $comment = "Status changed to: " . $new_status;
            $database->query($sql, [
                'ticket_id' => $ticket_id,
                'user_id' => $user_id,
                'comment' => $comment
            ]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['ajax'] === 'assign_ticket' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $ticket_id = intval($_POST['ticket_id']);
        $assign_to = intval($_POST['assign_to']);
        $user_id = $current_user_data['user_id'];
        
        try {
            // Update ticket assignment
            $sql = "UPDATE bg_tickets SET assigned_to = :assigned_to, assigned_dt = NOW() WHERE ticket_id = :ticket_id";
            $database->query($sql, ['assigned_to' => $assign_to, 'ticket_id' => $ticket_id]);
            
            // Get assigned user name
            $sql = "SELECT profile_first_name, profile_last_name FROM bg_users WHERE user_id = :user_id";
            $assigned_user = $database->query($sql, ['user_id' => $assign_to])->fetch(PDO::FETCH_ASSOC);
            $assigned_name = $assigned_user['profile_first_name'] . ' ' . $assigned_user['profile_last_name'];
            
            // Add assignment comment
            $sql = "INSERT INTO bg_ticket_comments (ticket_id, user_id, comment_type, comment, created_dt) 
                    VALUES (:ticket_id, :user_id, 'assignment', :comment, NOW())";
            $comment = "Ticket assigned to: " . $assigned_name;
            $database->query($sql, [
                'ticket_id' => $ticket_id,
                'user_id' => $user_id,
                'comment' => $comment
            ]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['ajax'] === 'add_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $ticket_id = intval($_POST['ticket_id']);
        $comment = $_POST['comment'];
        $is_internal = isset($_POST['is_internal']) ? 1 : 0;
        $user_id = $current_user_data['user_id'];
        
        try {
            $sql = "INSERT INTO bg_ticket_comments (ticket_id, user_id, comment_type, comment, is_internal, created_dt) 
                    VALUES (:ticket_id, :user_id, 'comment', :comment, :is_internal, NOW())";
            $database->query($sql, [
                'ticket_id' => $ticket_id,
                'user_id' => $user_id,
                'comment' => $comment,
                'is_internal' => $is_internal
            ]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'active'; // active = open, in_progress, pending
$filter_priority = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($filter_type !== 'all') {
    $where_conditions[] = "t.ticket_type = :ticket_type";
    $params['ticket_type'] = $filter_type;
}

if ($filter_status === 'active') {
    $where_conditions[] = "t.status IN ('open', 'in_progress', 'pending')";
} elseif ($filter_status !== 'all') {
    $where_conditions[] = "t.status = :status";
    $params['status'] = $filter_status;
}

if ($filter_priority !== 'all') {
    $where_conditions[] = "t.priority = :priority";
    $params['priority'] = $filter_priority;
}

if (!empty($search)) {
    $where_conditions[] = "(t.ticket_number LIKE :search OR t.subject LIKE :search OR t.description LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get tickets
$sql = "SELECT 
    t.*,
    u.profile_first_name as creator_first,
    u.profile_last_name as creator_last,
    u.profile_username as creator_username,
    a.profile_first_name as assigned_first,
    a.profile_last_name as assigned_last,
    a.profile_username as assigned_username,
    (SELECT COUNT(*) FROM bg_ticket_comments WHERE ticket_id = t.ticket_id) as comment_count,
    TIMESTAMPDIFF(HOUR, t.created_dt, NOW()) as hours_open
FROM bg_tickets t
LEFT JOIN bg_users u ON t.user_id = u.user_id
LEFT JOIN bg_users a ON t.assigned_to = a.user_id
$where_clause
ORDER BY 
    FIELD(t.status, 'open', 'in_progress', 'pending', 'resolved', 'closed'),
    FIELD(t.priority, 'critical', 'high', 'normal', 'low'),
    t.created_dt DESC
LIMIT 100";

$tickets = $database->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical,
    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority
FROM bg_tickets
WHERE status IN ('open', 'in_progress', 'pending')";
$stats = $database->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

// Get staff members for assignment dropdown
$staff_sql = "SELECT user_id, profile_first_name, profile_last_name, profile_username 
              FROM bg_users 
              WHERE user_role IN ('staff', 'admin', 'manager') 
              AND status = 'active' 
              ORDER BY profile_first_name";
$staff_members = $database->query($staff_sql)->fetchAll(PDO::FETCH_ASSOC);

$additionalstyles = '
<style>
/* Hide skip link */
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

body { 
    margin-bottom: 100px !important; 
}

/* Ticket table styles */
.ticket-row {
    cursor: pointer;
    transition: background-color 0.2s;
}
.ticket-row:hover {
    background-color: #f8f9fa;
}

/* Priority badges */
.priority-critical { 
    background: #dc3545; 
    color: white;
    font-weight: bold;
}
.priority-high { 
    background: #fd7e14; 
    color: white;
}
.priority-normal { 
    background: #6c757d; 
    color: white;
}
.priority-low { 
    background: #adb5bd; 
    color: white;
}

/* Status badges */
.status-open { background: #28a745; color: white; }
.status-in_progress { background: #ffc107; color: black; }
.status-pending { background: #17a2b8; color: white; }
.status-resolved { background: #6c757d; color: white; }
.status-closed { background: #343a40; color: white; }

/* Stats cards */
.stat-card {
    border-left: 4px solid;
    transition: transform 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
}
.stat-card.open { border-color: #28a745; }
.stat-card.progress { border-color: #ffc107; }
.stat-card.critical { border-color: #dc3545; }
.stat-card.pending { border-color: #17a2b8; }

/* Ticket details modal */
.comment-internal {
    background-color: #fff3cd;
    border-left: 3px solid #ffc107;
}

.ticket-timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-item::before {
    content: "";
    position: absolute;
    left: -21px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    border: 2px solid white;
    box-shadow: 0 0 0 1px #dee2e6;
}
.timeline-item::after {
    content: "";
    position: absolute;
    left: -15px;
    top: 17px;
    bottom: -20px;
    width: 1px;
    background: #dee2e6;
}
.timeline-item:last-child::after {
    display: none;
}
</style>
';

// Include page components
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-ticket-alt"></i> Ticket Management</h1>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" onclick="window.location.href='/staff/it-support'">
                <i class="fas fa-plus"></i> New Ticket
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card open">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Open Tickets</h6>
                    <h2 class="mb-0"><?= $stats['open_count'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card progress">
                <div class="card-body">
                    <h6 class="text-muted mb-2">In Progress</h6>
                    <h2 class="mb-0"><?= $stats['in_progress'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card critical">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Critical/High Priority</h6>
                    <h2 class="mb-0"><?= ($stats['critical'] ?? 0) + ($stats['high_priority'] ?? 0) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card pending">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Pending</h6>
                    <h2 class="mb-0"><?= $stats['pending'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control" onchange="this.form.submit()">
                        <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>All Types</option>
                        <option value="it_support" <?= $filter_type === 'it_support' ? 'selected' : '' ?>>IT Support</option>
                        <option value="hardware_request" <?= $filter_type === 'hardware_request' ? 'selected' : '' ?>>Hardware</option>
                        <option value="member_support" <?= $filter_type === 'member_support' ? 'selected' : '' ?>>Member Support</option>
                        <option value="legal_review" <?= $filter_type === 'legal_review' ? 'selected' : '' ?>>Legal Review</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="open" <?= $filter_status === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="in_progress" <?= $filter_status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="resolved" <?= $filter_status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="closed" <?= $filter_status === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-control" onchange="this.form.submit()">
                        <option value="all" <?= $filter_priority === 'all' ? 'selected' : '' ?>>All Priorities</option>
                        <option value="critical" <?= $filter_priority === 'critical' ? 'selected' : '' ?>>Critical</option>
                        <option value="high" <?= $filter_priority === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="normal" <?= $filter_priority === 'normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="low" <?= $filter_priority === 'low' ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Ticket #, subject, or description" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary form-control">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Tickets (<?= count($tickets) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Creator</th>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Age</th>
                            <th>Comments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No tickets found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr class="ticket-row" onclick="viewTicket(<?= $ticket['ticket_id'] ?>)">
                                    <td>
                                        <strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong>
                                    </td>
                                    <td>
                                        <?= str_replace('_', ' ', ucfirst($ticket['ticket_type'])) ?>
                                        <?php if ($ticket['ticket_category']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($ticket['ticket_category']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(substr($ticket['subject'], 0, 50)) ?>
                                        <?= strlen($ticket['subject']) > 50 ? '...' : '' ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($ticket['creator_first'] . ' ' . $ticket['creator_last']) ?>
                                    </td>
                                    <td>
                                        <?php if ($ticket['assigned_to']): ?>
                                            <?= htmlspecialchars($ticket['assigned_first'] . ' ' . $ticket['assigned_last']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge priority-<?= $ticket['priority'] ?>">
                                            <?= ucfirst($ticket['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-<?= $ticket['status'] ?>">
                                            <?= str_replace('_', ' ', ucfirst($ticket['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($ticket['hours_open'] < 24) {
                                            echo $ticket['hours_open'] . ' hrs';
                                        } else {
                                            echo round($ticket['hours_open'] / 24) . ' days';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $ticket['comment_count'] ?></span>
                                    </td>
                                    <td onclick="event.stopPropagation()">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewTicket(<?= $ticket['ticket_id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="quickUpdateStatus(<?= $ticket['ticket_id'] ?>, 'in_progress')">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" onclick="quickUpdateStatus(<?= $ticket['ticket_id'] ?>, 'resolved')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Ticket Details Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ticketDetails">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function viewTicket(ticketId) {
    // Load ticket details via AJAX
    fetch('/staff/ticket-details?id=' + ticketId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('ticketDetails').innerHTML = html;
        new bootstrap.Modal(document.getElementById('ticketModal')).show();
    })
    .catch(error => {
        console.error('Error loading ticket:', error);
        document.getElementById('ticketDetails').innerHTML = '<div class="alert alert-danger">Error loading ticket details</div>';
        new bootstrap.Modal(document.getElementById('ticketModal')).show();
    });
}

function quickUpdateStatus(ticketId, newStatus) {
    if (confirm('Update ticket status to ' + newStatus.replace('_', ' ') + '?')) {
        fetch('/staff/ticket-manager?ajax=update_status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ticket_id=' + ticketId + '&status=' + newStatus
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating ticket');
            }
        });
    }
}

function assignTicket(ticketId) {
    const assignTo = prompt('Enter user ID to assign to:');
    if (assignTo) {
        fetch('/staff/ticket-manager?ajax=assign_ticket', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ticket_id=' + ticketId + '&assign_to=' + assignTo
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error assigning ticket');
            }
        });
    }
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>