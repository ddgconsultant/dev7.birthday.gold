<?php
// Ticket Details View
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Staff access is handled by site-controller.php
$ticket_id = intval($_GET['id'] ?? 0);

if (!$ticket_id) {
    // If called via AJAX from ticket-manager, return a message
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo '<div class="alert alert-danger">Invalid ticket ID</div>';
    } else {
        header('Location: /staff/ticket-manager');
    }
    exit;
}

// Get ticket details
$sql = "SELECT 
    t.*,
    u.profile_first_name as creator_first,
    u.profile_last_name as creator_last,
    u.profile_username as creator_username,
    u.profile_email as creator_email,
    a.profile_first_name as assigned_first,
    a.profile_last_name as assigned_last,
    a.profile_username as assigned_username,
    TIMESTAMPDIFF(HOUR, t.created_dt, NOW()) as hours_open
FROM bg_tickets t
LEFT JOIN bg_users u ON t.user_id = u.user_id
LEFT JOIN bg_users a ON t.assigned_to = a.user_id
WHERE t.ticket_id = :ticket_id";

$ticket = $database->query($sql, ['ticket_id' => $ticket_id])->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo '<div class="alert alert-danger">Ticket not found</div>';
    } else {
        header('Location: /staff/ticket-manager');
    }
    exit;
}

// Get ticket comments
$sql = "SELECT 
    c.*,
    u.profile_first_name,
    u.profile_last_name,
    u.profile_username
FROM bg_ticket_comments c
LEFT JOIN bg_users u ON c.user_id = u.user_id
WHERE c.ticket_id = :ticket_id
ORDER BY c.created_dt ASC";

$comments = $database->query($sql, ['ticket_id' => $ticket_id])->fetchAll(PDO::FETCH_ASSOC);

// Get staff members for assignment dropdown
$sql = "SELECT user_id, profile_first_name, profile_last_name, profile_username 
        FROM bg_users 
        WHERE user_role IN ('staff', 'admin', 'manager') 
        AND status = 'active' 
        ORDER BY profile_first_name";
$staff_members = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// If this is an AJAX request from ticket-manager modal
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    ?>
    <div class="ticket-details">
        <!-- Ticket Header -->
        <div class="row mb-3">
            <div class="col-md-8">
                <h6 class="text-muted"><?= htmlspecialchars($ticket['ticket_number']) ?></h6>
                <h4><?= htmlspecialchars($ticket['subject']) ?></h4>
                <p class="text-muted">
                    Created by <strong><?= htmlspecialchars($ticket['creator_first'] . ' ' . $ticket['creator_last']) ?></strong>
                    on <?= date('M d, Y g:i A', strtotime($ticket['created_dt'])) ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge priority-<?= $ticket['priority'] ?> me-2">
                    <?= ucfirst($ticket['priority']) ?> Priority
                </span>
                <span class="badge status-<?= $ticket['status'] ?>">
                    <?= str_replace('_', ' ', ucfirst($ticket['status'])) ?>
                </span>
            </div>
        </div>

        <!-- Ticket Info Cards -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Type</h6>
                        <p class="card-text"><?= str_replace('_', ' ', ucfirst($ticket['ticket_type'])) ?></p>
                        <?php if ($ticket['ticket_category']): ?>
                            <small class="text-muted"><?= htmlspecialchars($ticket['ticket_category']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Assigned To</h6>
                        <p class="card-text">
                            <?php if ($ticket['assigned_to']): ?>
                                <?= htmlspecialchars($ticket['assigned_first'] . ' ' . $ticket['assigned_last']) ?>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </p>
                        <button class="btn btn-sm btn-outline-primary" onclick="showAssignModal(<?= $ticket_id ?>)">
                            <i class="fas fa-user-plus"></i> Assign
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Age</h6>
                        <p class="card-text">
                            <?php 
                            if ($ticket['hours_open'] < 24) {
                                echo $ticket['hours_open'] . ' hours';
                            } else {
                                echo round($ticket['hours_open'] / 24) . ' days';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Description</h6>
            </div>
            <div class="card-body">
                <p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
            </div>
        </div>

        <!-- Comments Timeline -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Activity & Comments</h6>
            </div>
            <div class="card-body">
                <div class="ticket-timeline">
                    <?php foreach ($comments as $comment): ?>
                        <div class="timeline-item <?= $comment['is_internal'] ? 'comment-internal' : '' ?>">
                            <div class="d-flex justify-content-between">
                                <strong><?= htmlspecialchars($comment['profile_first_name'] . ' ' . $comment['profile_last_name']) ?></strong>
                                <small class="text-muted"><?= date('M d, Y g:i A', strtotime($comment['created_dt'])) ?></small>
                            </div>
                            <?php if ($comment['comment_type'] !== 'comment'): ?>
                                <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $comment['comment_type'])) ?></small>
                            <?php endif; ?>
                            <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                            <?php if ($comment['is_internal']): ?>
                                <small class="text-warning"><i class="fas fa-lock"></i> Internal Note</small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Add Comment Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Add Comment</h6>
            </div>
            <div class="card-body">
                <form id="addCommentForm" onsubmit="addComment(event, <?= $ticket_id ?>)">
                    <div class="mb-3">
                        <textarea class="form-control" name="comment" rows="3" placeholder="Enter your comment..." required></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal">
                        <label class="form-check-label" for="is_internal">
                            Internal Note (visible to staff only)
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-comment"></i> Add Comment
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-3">
            <button class="btn btn-success" onclick="quickUpdateStatus(<?= $ticket_id ?>, 'in_progress')">
                <i class="fas fa-play"></i> Start Progress
            </button>
            <button class="btn btn-warning" onclick="quickUpdateStatus(<?= $ticket_id ?>, 'pending')">
                <i class="fas fa-pause"></i> Set Pending
            </button>
            <button class="btn btn-secondary" onclick="quickUpdateStatus(<?= $ticket_id ?>, 'resolved')">
                <i class="fas fa-check"></i> Resolve
            </button>
            <button class="btn btn-dark" onclick="quickUpdateStatus(<?= $ticket_id ?>, 'closed')">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>

    <script>
    function addComment(event, ticketId) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('ticket_id', ticketId);
        
        fetch('/staff/ticket-manager?ajax=add_comment', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the modal content
                viewTicket(ticketId);
            } else {
                alert('Error adding comment');
            }
        });
    }

    function showAssignModal(ticketId) {
        const staffList = <?= json_encode($staff_members) ?>;
        let options = '<select class="form-control" id="assignToSelect">';
        options += '<option value="">Select staff member...</option>';
        staffList.forEach(staff => {
            options += `<option value="${staff.user_id}">${staff.profile_first_name} ${staff.profile_last_name}</option>`;
        });
        options += '</select>';
        
        const assignHtml = `
            <div class="mb-3">
                <label>Assign ticket to:</label>
                ${options}
            </div>
            <button class="btn btn-primary" onclick="doAssign(${ticketId})">Assign</button>
        `;
        
        // Simple assignment - you could make this a proper modal
        const assignDiv = document.createElement('div');
        assignDiv.innerHTML = assignHtml;
        assignDiv.className = 'p-3 border rounded mb-3';
        document.querySelector('.ticket-details').prepend(assignDiv);
    }

    function doAssign(ticketId) {
        const assignTo = document.getElementById('assignToSelect').value;
        if (!assignTo) {
            alert('Please select a staff member');
            return;
        }
        
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
                viewTicket(ticketId); // Reload
            } else {
                alert('Error assigning ticket');
            }
        });
    }
    </script>
    <?php
    exit;
}

// Otherwise, show as a full page
$pagetitle = "Ticket #" . $ticket['ticket_number'];

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

/* Priority and status badges */
.priority-critical { background: #dc3545; color: white; font-weight: bold; }
.priority-high { background: #fd7e14; color: white; }
.priority-normal { background: #6c757d; color: white; }
.priority-low { background: #adb5bd; color: white; }

.status-open { background: #28a745; color: white; }
.status-in_progress { background: #ffc107; color: black; }
.status-pending { background: #17a2b8; color: white; }
.status-resolved { background: #6c757d; color: white; }
.status-closed { background: #343a40; color: white; }

/* Timeline styles */
.ticket-timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
    margin-bottom: 15px;
}
.comment-internal {
    background-color: #fff3cd;
    border-left: 3px solid #ffc107;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container my-4">
    <div class="row">
        <div class="col">
            <a href="/staff/ticket-manager" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-left"></i> Back to Tickets
            </a>
        </div>
    </div>

    <!-- Full page view would go here - similar to AJAX content but with full layout -->
    <div class="alert alert-info">
        Full page ticket view coming soon. For now, please view tickets from the ticket manager.
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>