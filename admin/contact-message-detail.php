<?php
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Contact Message Detail - Admin Dashboard";
$page_description = "Detailed view of contact form submission";

// Get message ID from query
$message_id = $_GET['id'] ?? null;

if (!$message_id) {
    header('Location: /admin/contact-messages');
    exit;
}

// Get the main message
$message_sql = "
SELECT
    id,
    create_dt,
    ip,
    user_id,
    username,
    type,
    name,
    sessionid,
    page,
    site,
    server,
    version,
    request_data,
    tracking_data,
    session_data,
    server_data
FROM bg_sessiontracking
WHERE id = :id
";
$message = $database->getrow($message_sql, ['id' => $message_id]);

if (!$message) {
    header('Location: /admin/contact-messages');
    exit;
}

// Parse JSON data (handle null values)
$tracking_data = (is_string($message['tracking_data'] ?? null) && $message['tracking_data'] !== '') ? json_decode($message['tracking_data'], true) : [];
$session_data = (is_string($message['session_data'] ?? null) && $message['session_data'] !== '') ? json_decode($message['session_data'], true) : [];
$server_data = (is_string($message['server_data'] ?? null) && $message['server_data'] !== '') ? json_decode($message['server_data'], true) : [];
$request_data = (is_string($message['request_data'] ?? null) && $message['request_data'] !== '') ? json_decode($message['request_data'], true) : [];

// Ensure arrays even if JSON decode fails
$tracking_data = is_array($tracking_data) ? $tracking_data : [];
$session_data = is_array($session_data) ? $session_data : [];
$server_data = is_array($server_data) ? $server_data : [];
$request_data = is_array($request_data) ? $request_data : [];

// Get all events for this session
$session_events_sql = "
SELECT
    id,
    create_dt,
    name,
    tracking_data
FROM bg_sessiontracking
WHERE sessionid = :sessionid
ORDER BY create_dt ASC
";
$session_events = $database->query($session_events_sql, ['sessionid' => $message['sessionid']])->fetchAll();

// Get admin replies for this contact message (will return empty until table is created)
try {
    $replies_sql = "
    SELECT
        id,
        create_dt,
        reply_subject,
        reply_message,
        admin_username,
        status,
        email_sent_dt,
        email_error
    FROM bg_contact_replies
    WHERE contact_message_id = :message_id
    ORDER BY create_dt DESC
    ";
    $admin_replies = $database->query($replies_sql, ['message_id' => $message_id])->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet
    $admin_replies = [];
}

// Page styles
$additionalstyles .= '
<style>
.detail-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    margin-bottom: 1.5rem;
}

.detail-row {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}

.detail-value {
    color: #212529;
}

.json-viewer {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1rem;
    overflow-x: auto;
    max-height: 400px;
}

.json-viewer pre {
    margin: 0;
    font-size: 0.875rem;
}

.timeline-event {
    padding: 0.75rem;
    border-left: 3px solid #dee2e6;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.timeline-event.current {
    border-left-color: #0d6efd;
    background: #e7f1ff;
}

.timeline-event.spam {
    border-left-color: #dc3545;
}

.timeline-event.legitimate {
    border-left-color: #28a745;
}

.timeline-event.success {
    border-left-color: #0d6efd;
}

.badge-spam {
    background-color: #dc3545;
    color: white;
}

.badge-legitimate {
    background-color: #28a745;
    color: white;
}

.badge-sent {
    background-color: #0d6efd;
    color: white;
}

.alert-spam {
    background-color: #f8d7da;
    border-color: #f5c2c7;
    color: #842029;
}

.alert-legitimate {
    background-color: #d1e7dd;
    border-color: #badbcc;
    color: #0f5132;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin">
    <div class="container">
        <h1><i class="bi bi-envelope-open me-2"></i>Contact Message Detail</h1>
        <p class="lead">Complete information for tracking ID: <?php echo htmlspecialchars($message_id); ?></p>
    </div>
</div>

<div class="container py-4">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="/admin/contact-messages" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Messages
        </a>
    </div>

    <!-- Main Status Alert -->
    <?php
    $ai_decision = $tracking_data['ai_decision'] ?? '';
    $status = $tracking_data['status'] ?? '';

    if (strpos($ai_decision, 'SPAM') !== false):
    ?>
    <div class="alert alert-spam mb-4">
        <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Spam Detected</h4>
        <p class="mb-0">This message was flagged as spam by the AI spam detection system.</p>
        <?php if (($tracking_data['confirmed_after_spam'] ?? 'no') === 'yes'): ?>
        <hr>
        <p class="mb-0"><strong>Note:</strong> User confirmed and sent this message after spam detection.</p>
        <?php endif; ?>
    </div>
    <?php elseif ($status === 'legitimate'): ?>
    <div class="alert alert-legitimate mb-4">
        <h4 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Legitimate Message</h4>
        <p class="mb-0">This message was verified as legitimate by the AI spam detection system.</p>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column: Message Details -->
        <div class="col-lg-8">
            <!-- Message Content -->
            <div class="detail-card">
                <h5 class="mb-3"><i class="bi bi-envelope me-2"></i>Message Content</h5>

                <?php if (!empty($tracking_data['email'])): ?>
                <div class="detail-row">
                    <div class="detail-label">From Email</div>
                    <div class="detail-value">
                        <a href="mailto:<?php echo htmlspecialchars($tracking_data['email']); ?>">
                            <?php echo htmlspecialchars($tracking_data['email']); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($tracking_data['name'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($tracking_data['name']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($tracking_data['subject'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Subject</div>
                    <div class="detail-value"><?php echo htmlspecialchars($tracking_data['subject']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($tracking_data['message_preview'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Message Preview</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($tracking_data['message_preview'])); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($tracking_data['timestamp'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Timestamp</div>
                    <div class="detail-value"><?php echo htmlspecialchars($tracking_data['timestamp']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- AI Analysis -->
            <?php if (!empty($tracking_data['ai_decision']) || !empty($tracking_data['ai_response'])): ?>
            <div class="detail-card">
                <h5 class="mb-3"><i class="bi bi-robot me-2"></i>AI Spam Analysis</h5>

                <?php if (!empty($tracking_data['ai_decision'])): ?>
                <div class="detail-row">
                    <div class="detail-label">AI Decision</div>
                    <div class="detail-value">
                        <?php if (strpos($tracking_data['ai_decision'], 'SPAM') !== false): ?>
                        <span class="badge badge-spam"><?php echo htmlspecialchars($tracking_data['ai_decision']); ?></span>
                        <?php else: ?>
                        <span class="badge badge-legitimate"><?php echo htmlspecialchars($tracking_data['ai_decision']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($tracking_data['ai_response'])): ?>
                <div class="detail-row">
                    <div class="detail-label">AI Response Details</div>
                    <div class="detail-value">
                        <div class="json-viewer">
                            <pre><?php echo json_encode($tracking_data['ai_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (($tracking_data['confirmed_after_spam'] ?? 'no') === 'yes'): ?>
                <div class="detail-row">
                    <div class="detail-label">User Confirmation</div>
                    <div class="detail-value">
                        <span class="badge bg-info">User confirmed and sent message after spam flag</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Session Timeline -->
            <?php if (!empty($session_events)): ?>
            <div class="detail-card">
                <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i>Session Timeline (<?php echo count($session_events); ?> events)</h5>
                <?php foreach ($session_events as $event):
                    $is_current = ($event['id'] == $message_id);
                    $event_class = '';

                    if ($is_current) {
                        $event_class = 'current';
                    } elseif (strpos($event['name'], 'spam') !== false) {
                        $event_class = 'spam';
                    } elseif (strpos($event['name'], 'legitimate') !== false) {
                        $event_class = 'legitimate';
                    } elseif (strpos($event['name'], 'sent') !== false || strpos($event['name'], 'success') !== false) {
                        $event_class = 'success';
                    }
                ?>
                <div class="timeline-event <?php echo $event_class; ?>" id="event-<?php echo $event['id']; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo htmlspecialchars($event['name']); ?></strong>
                            <?php if ($is_current): ?>
                            <span class="badge bg-primary ms-2">Current Event</span>
                            <?php endif; ?>
                            <div class="small text-muted mt-1">
                                <?php echo date('M d, Y g:i:s A', strtotime($event['create_dt'])); ?>
                            </div>
                        </div>
                        <button onclick="loadEventData(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['name'], ENT_QUOTES); ?>')" class="btn btn-sm btn-outline-primary load-event-btn" data-event-id="<?php echo $event['id']; ?>">
                            <i class="bi bi-eye me-1"></i>View
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Technical Details -->
        <div class="col-lg-4">
            <!-- Event Information -->
            <div class="detail-card">
                <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Event Information</h5>

                <div class="detail-row">
                    <div class="detail-label">Event Name</div>
                    <div class="detail-value"><code><?php echo htmlspecialchars($message['name']); ?></code></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Event ID</div>
                    <div class="detail-value"><?php echo htmlspecialchars($message['id']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Created</div>
                    <div class="detail-value"><?php echo date('M d, Y g:i:s A', strtotime($message['create_dt'])); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Session ID</div>
                    <div class="detail-value">
                        <code class="small"><?php echo htmlspecialchars($message['sessionid']); ?></code>
                        <a href="/admin/sessiondetails?sid=<?php echo urlencode($message['sessionid']); ?>" class="btn btn-sm btn-outline-primary ms-2">
                            View Session
                        </a>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">IP Address</div>
                    <div class="detail-value"><?php echo htmlspecialchars($message['ip']); ?></div>
                </div>

                <?php if ($message['user_id']): ?>
                <div class="detail-row">
                    <div class="detail-label">User ID</div>
                    <div class="detail-value">
                        <a href="/admin/user-details?id=<?php echo $message['user_id']; ?>">
                            <?php echo htmlspecialchars($message['user_id']); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($message['username']): ?>
                <div class="detail-row">
                    <div class="detail-label">Username</div>
                    <div class="detail-value"><?php echo htmlspecialchars($message['username']); ?></div>
                </div>
                <?php endif; ?>

                <div class="detail-row">
                    <div class="detail-label">Site</div>
                    <div class="detail-value"><?php echo htmlspecialchars($message['site']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Server</div>
                    <div class="detail-value"><?php echo htmlspecialchars($message['server']); ?></div>
                </div>

                <?php if ($message['page']): ?>
                <div class="detail-row">
                    <div class="detail-label">Page</div>
                    <div class="detail-value"><?php echo htmlspecialchars($message['page']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="detail-card">
                <h5 class="mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>

                <?php if (!empty($tracking_data['email']) || !empty($message['user_id'])): ?>
                <button onclick="showReplyModal()" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-reply-fill me-2"></i>Reply to Message
                </button>
                <?php endif; ?>

                <?php if (!empty($tracking_data['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($tracking_data['email']); ?>" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-envelope me-2"></i>Reply via Email Client
                </a>
                <?php endif; ?>

                <a href="/admin/sessiondetails?sid=<?php echo urlencode($message['sessionid']); ?>" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-diagram-3 me-2"></i>View Full Session
                </a>

                <a href="/admin/contact-messages?status=all" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-list me-2"></i>Back to All Messages
                </a>
            </div>

            <!-- Previous Replies -->
            <div class="detail-card">
                <h5 class="mb-3"><i class="bi bi-chat-left-text me-2"></i>Reply History</h5>
                <div id="reply-history-container">
                    <?php if (empty($admin_replies)): ?>
                    <p class="text-muted text-center py-3">No replies sent yet</p>
                    <?php else: ?>
                    <?php foreach ($admin_replies as $reply): ?>
                    <div class="reply-item mb-3 p-3" style="background: #f8f9fa; border-left: 4px solid #0d6efd; border-radius: 4px;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong><?php echo htmlspecialchars($reply['reply_subject'] ?? 'No Subject'); ?></strong>
                                <span class="badge bg-<?php echo ($reply['status'] ?? 'draft') === 'sent' ? 'success' : (($reply['status'] ?? 'draft') === 'failed' ? 'danger' : 'secondary'); ?> ms-2">
                                    <?php echo strtoupper($reply['status'] ?? 'draft'); ?>
                                </span>
                            </div>
                            <small class="text-muted"><?php echo date('M d, Y g:i A', strtotime($reply['create_dt'])); ?></small>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">From: <?php echo htmlspecialchars($reply['admin_username'] ?? 'Admin'); ?></small>
                        </div>
                        <div><?php echo nl2br(htmlspecialchars($reply['reply_message'] ?? '')); ?></div>
                        <?php if (!empty($reply['email_sent_dt'])): ?>
                        <div class="mt-2"><small class="text-success"><i class="bi bi-check-circle"></i> Sent: <?php echo date('M d, Y g:i A', strtotime($reply['email_sent_dt'])); ?></small></div>
                        <?php endif; ?>
                        <?php if (!empty($reply['email_error'])): ?>
                        <div class="mt-2"><small class="text-danger"><i class="bi bi-exclamation-circle"></i> Error: <?php echo htmlspecialchars($reply['email_error']); ?></small></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Raw Data (Collapsible) -->
    <div class="detail-card" id="rawDataCard">
        <h5 class="mb-3">
            <a data-bs-toggle="collapse" href="#rawDataCollapse" role="button" aria-expanded="false" aria-controls="rawDataCollapse" id="rawDataToggle">
                <i class="bi bi-code-square me-2"></i>Raw Data <span id="rawDataEventName"></span><span id="rawDataHint">(Click to expand)</span>
            </a>
        </h5>
        <div class="collapse" id="rawDataCollapse">
            <div id="rawDataLoading" class="text-center py-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading event data...</p>
            </div>
            <div id="rawDataContent">
            <!-- Tracking Data -->
            <?php if (!empty($tracking_data)): ?>
            <h6 class="mt-3">Tracking Data</h6>
            <div class="json-viewer">
                <pre><?php echo json_encode($tracking_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
            </div>
            <?php endif; ?>

            <!-- Session Data -->
            <?php if (!empty($session_data)): ?>
            <h6 class="mt-3">Session Data</h6>
            <div class="json-viewer">
                <pre><?php echo json_encode($session_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
            </div>
            <?php endif; ?>

            <!-- Server Data -->
            <?php if (!empty($server_data)): ?>
            <h6 class="mt-3">Server Data</h6>
            <div class="json-viewer">
                <pre><?php echo json_encode($server_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
            </div>
            <?php endif; ?>

            <!-- Request Data -->
            <?php if (!empty($request_data)): ?>
            <h6 class="mt-3">Request Data</h6>
            <div class="json-viewer">
                <pre><?php echo json_encode($request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
            </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-reply-fill me-2"></i>Reply to Contact Message
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="replyForm">
                    <input type="hidden" name="contact_event_id" value="<?php echo $message_id; ?>">

                    <div class="mb-3">
                        <label class="form-label"><strong>Send Reply To:</strong></label>
                        <div class="bg-light p-3 rounded">
                            <?php if (!empty($tracking_data['name'])): ?>
                            <div><i class="bi bi-person me-2"></i><?php echo htmlspecialchars($tracking_data['name']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($tracking_data['email'])): ?>
                            <div><i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($tracking_data['email']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($message['user_id'])): ?>
                            <div><i class="bi bi-person-badge me-2"></i>User ID: <?php echo $message['user_id']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reply_message" class="form-label"><strong>Your Reply:</strong></label>
                        <textarea class="form-control" id="reply_message" name="reply_message" rows="8" required
                                  placeholder="Type your reply message here..."></textarea>
                        <small class="text-muted">Your reply will be stored internally. Choose below how to deliver it.</small>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="aiAssistBtn" onclick="generateAIReply()">
                            <i class="bi bi-robot me-2"></i>AI Assist - Generate Reply
                        </button>
                        <small class="text-muted d-block mt-1">Let AI draft a helpful reply based on the original message</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Delivery Method:</strong></label>
                        <div>
                            <?php if (!empty($tracking_data['email']) && !empty($message['user_id'])): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sent_via" id="sent_both" value="both" checked>
                                <label class="form-check-label" for="sent_both">
                                    <i class="bi bi-send me-1"></i>Send via Email AND Notification
                                    <small class="text-muted d-block">Recommended - ensures they receive it</small>
                                </label>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($tracking_data['email'])): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sent_via" id="sent_email" value="email"
                                       <?php echo (empty($message['user_id']) ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="sent_email">
                                    <i class="bi bi-envelope me-1"></i>Send via Email Only
                                </label>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($message['user_id'])): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sent_via" id="sent_notification" value="notification"
                                       <?php echo (empty($tracking_data['email']) && !empty($message['user_id']) ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="sent_notification">
                                    <i class="bi bi-bell me-1"></i>Send via In-App Notification Only
                                </label>
                            </div>
                            <?php endif; ?>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sent_via" id="sent_internal" value="internal">
                                <label class="form-check-label" for="sent_internal">
                                    <i class="bi bi-archive me-1"></i>Save Internally Only (Don't Send)
                                    <small class="text-muted d-block">Store the reply without sending it</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="reply-status" class="alert d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="sendReply()">
                    <i class="bi bi-send me-2"></i>Send Reply
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Load event data via AJAX
function loadEventData(eventId, eventName) {
    // Show loading state
    const loadingDiv = document.getElementById('rawDataLoading');
    const contentDiv = document.getElementById('rawDataContent');
    const eventNameSpan = document.getElementById('rawDataEventName');
    const collapseDiv = document.getElementById('rawDataCollapse');
    const rawDataCard = document.getElementById('rawDataCard');

    // Update event name in header
    eventNameSpan.textContent = 'for "' + eventName + '" ';

    // Show loading
    loadingDiv.style.display = 'block';
    contentDiv.style.display = 'none';

    // Always expand the collapse (don't toggle)
    if (!collapseDiv.classList.contains('show')) {
        const bsCollapse = new bootstrap.Collapse(collapseDiv, { show: true });
    }

    // Hide the "(Click to expand)" hint when open
    const hintSpan = document.getElementById('rawDataHint');
    if (hintSpan) {
        hintSpan.style.display = 'none';
    }

    // Scroll to the raw data card
    rawDataCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Highlight the selected event
    document.querySelectorAll('.timeline-event').forEach(el => {
        el.style.backgroundColor = '';
    });
    const eventElement = document.getElementById('event-' + eventId);
    if (eventElement) {
        eventElement.style.backgroundColor = '#fff3cd';
    }

    // Update button states
    document.querySelectorAll('.load-event-btn').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
        btn.innerHTML = '<i class="bi bi-eye me-1"></i>View';
    });
    const clickedBtn = document.querySelector('.load-event-btn[data-event-id="' + eventId + '"]');
    if (clickedBtn) {
        clickedBtn.classList.remove('btn-outline-primary');
        clickedBtn.classList.add('btn-primary');
        clickedBtn.innerHTML = '<i class="bi bi-eye-fill me-1"></i>Viewing';
    }

    // Fetch event data
    fetch('/admin/ajax/get-event-data.php?id=' + eventId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Build HTML
                let html = '';

                // Tracking Data
                if (data.tracking_data && Object.keys(data.tracking_data).length > 0) {
                    html += '<h6 class="mt-3">Tracking Data</h6>';
                    html += '<div class="json-viewer"><pre>' + JSON.stringify(data.tracking_data, null, 2) + '</pre></div>';
                }

                // Session Data
                if (data.session_data && Object.keys(data.session_data).length > 0) {
                    html += '<h6 class="mt-3">Session Data</h6>';
                    html += '<div class="json-viewer"><pre>' + JSON.stringify(data.session_data, null, 2) + '</pre></div>';
                }

                // Server Data
                if (data.server_data && Object.keys(data.server_data).length > 0) {
                    html += '<h6 class="mt-3">Server Data</h6>';
                    html += '<div class="json-viewer"><pre>' + JSON.stringify(data.server_data, null, 2) + '</pre></div>';
                }

                // Request Data
                if (data.request_data && Object.keys(data.request_data).length > 0) {
                    html += '<h6 class="mt-3">Request Data</h6>';
                    html += '<div class="json-viewer"><pre>' + JSON.stringify(data.request_data, null, 2) + '</pre></div>';
                }

                if (!html) {
                    html = '<p class="text-muted text-center py-4">No data available for this event.</p>';
                }

                // Update content
                contentDiv.innerHTML = html;
                loadingDiv.style.display = 'none';
                contentDiv.style.display = 'block';
            } else {
                contentDiv.innerHTML = '<div class="alert alert-danger">Error loading event data: ' + (data.error || 'Unknown error') + '</div>';
                loadingDiv.style.display = 'none';
                contentDiv.style.display = 'block';
            }
        })
        .catch(error => {
            contentDiv.innerHTML = '<div class="alert alert-danger">Error loading event data: ' + error.message + '</div>';
            loadingDiv.style.display = 'none';
            contentDiv.style.display = 'block';
        });
}

// Listen for collapse events to update the hint text
document.addEventListener('DOMContentLoaded', function() {
    const collapseDiv = document.getElementById('rawDataCollapse');
    const hintSpan = document.getElementById('rawDataHint');

    if (collapseDiv && hintSpan) {
        // Show hint when collapsed
        collapseDiv.addEventListener('hidden.bs.collapse', function () {
            hintSpan.style.display = 'inline';
        });

        // Hide hint when expanded
        collapseDiv.addEventListener('shown.bs.collapse', function () {
            hintSpan.style.display = 'none';
        });
    }

    // Load reply history on page load
    loadReplyHistory();
});

// Show reply modal
function showReplyModal() {
    const modal = new bootstrap.Modal(document.getElementById('replyModal'));
    modal.show();
    document.getElementById('reply_message').focus();
}

// Send reply
function sendReply() {
    const form = document.getElementById('replyForm');
    const formData = new FormData(form);
    const statusDiv = document.getElementById('reply-status');
    const sendButton = event.target;

    // Validate
    if (!formData.get('reply_message').trim()) {
        statusDiv.className = 'alert alert-danger';
        statusDiv.textContent = 'Please enter a reply message';
        statusDiv.classList.remove('d-none');
        return;
    }

    // Disable button and show loading
    sendButton.disabled = true;
    sendButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
    statusDiv.classList.add('d-none');

    // Send via AJAX
    fetch('/admin/ajax/send-contact-reply.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.className = 'alert alert-success';
            statusDiv.innerHTML = '<i class="bi bi-check-circle me-2"></i>Reply sent successfully!';

            if (data.email_sent) {
                statusDiv.innerHTML += '<br><small>✓ Email delivered</small>';
            }
            if (data.notification_sent) {
                statusDiv.innerHTML += '<br><small>✓ Notification created</small>';
            }

            statusDiv.classList.remove('d-none');

            // Reset form and reload history
            form.reset();
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('replyModal')).hide();
                loadReplyHistory();
            }, 2000);
        } else {
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Error: ' + (data.error || 'Unknown error');
            statusDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        statusDiv.className = 'alert alert-danger';
        statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Error sending reply: ' + error.message;
        statusDiv.classList.remove('d-none');
    })
    .finally(() => {
        sendButton.disabled = false;
        sendButton.innerHTML = '<i class="bi bi-send me-2"></i>Send Reply';
    });
}

// Load reply history
function loadReplyHistory() {
    const container = document.getElementById('reply-history-container');
    const contactEventId = <?php echo $message_id; ?>;

    fetch('/admin/ajax/get-contact-replies.php?contact_event_id=' + contactEventId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.replies.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center py-3"><i class="bi bi-chat-left-dots me-2"></i>No replies yet</p>';
                } else {
                    let html = '<div class="list-group">';
                    data.replies.forEach(reply => {
                        const statusBadge = reply.status === 'sent' ? 'success' :
                                          reply.status === 'failed' ? 'danger' : 'secondary';
                        const sentViaIcon = reply.sent_via === 'email' ? 'envelope' :
                                          reply.sent_via === 'notification' ? 'bell' :
                                          reply.sent_via === 'both' ? 'send' : 'archive';

                        html += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>${reply.admin_username}</strong>
                                        <span class="badge bg-${statusBadge} ms-2">${reply.status}</span>
                                    </div>
                                    <small class="text-muted">${new Date(reply.create_dt).toLocaleString()}</small>
                                </div>
                                <div class="mb-2">${reply.reply_message.replace(/\n/g, '<br>')}</div>
                                <div class="small text-muted">
                                    <i class="bi bi-${sentViaIcon} me-1"></i>Sent via: ${reply.sent_via}
                                    ${reply.email_status ? ' | Email: ' + reply.email_status : ''}
                                    ${reply.notification_id ? ' | Notification ID: ' + reply.notification_id : ''}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                }
            } else {
                container.innerHTML = '<div class="alert alert-danger">Error loading replies: ' + (data.error || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            container.innerHTML = '<div class="alert alert-danger">Error loading replies: ' + error.message + '</div>';
        });
}

// Generate AI reply
function generateAIReply() {
    const aiBtn = document.getElementById('aiAssistBtn');
    const replyTextarea = document.getElementById('reply_message');
    const statusDiv = document.getElementById('reply-status');
    const contactMessageId = <?php echo $message_id; ?>;
    const currentDraft = replyTextarea.value.trim();

    // Show loading state
    const originalBtnHtml = aiBtn.innerHTML;
    aiBtn.disabled = true;
    aiBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    statusDiv.classList.add('d-none');

    // Prepare form data
    const formData = new FormData();
    formData.append('contact_message_id', contactMessageId);
    formData.append('current_draft', currentDraft);

    // Call AI assist endpoint
    fetch('/admin/ajax/contact-reply-ai-assist.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Insert AI-generated reply
            replyTextarea.value = data.suggested_reply;

            // Show success message
            statusDiv.className = 'alert alert-success';
            statusDiv.innerHTML = '<i class="bi bi-robot me-2"></i>AI suggestion generated! (' + data.tokens_used + ' tokens used) Feel free to edit before sending.';
            statusDiv.classList.remove('d-none');

            // Focus on message field
            replyTextarea.focus();
            replyTextarea.setSelectionRange(0, 0);
            replyTextarea.scrollTop = 0;
        } else {
            // Show error message
            statusDiv.className = 'alert alert-warning';
            statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>' + (data.error || 'AI assist failed');
            statusDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        statusDiv.className = 'alert alert-danger';
        statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Error: ' + error.message;
        statusDiv.classList.remove('d-none');
    })
    .finally(() => {
        // Reset button
        aiBtn.disabled = false;
        aiBtn.innerHTML = originalBtnHtml;
    });
}

// ============================================================================
// Reply Form Handlers
// ============================================================================

// Handle reply form submission
document.addEventListener('DOMContentLoaded', function() {
    const replyForm = document.getElementById('replyForm');
    const sendReplyBtn = document.getElementById('sendReplyBtn');
    const aiAssistBtn = document.getElementById('aiAssistBtn');
    const replyStatus = document.getElementById('replyStatus');
    const replyMessage = document.getElementById('replyMessage');

    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            sendReplyBtn.disabled = true;
            sendReplyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
            replyStatus.style.display = 'none';

            // Get form data
            const formData = new FormData(replyForm);

            // Send AJAX request
            fetch('/admin/ajax/contact-reply-send.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    replyStatus.className = 'alert alert-success';
                    replyStatus.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + data.message;
                    replyStatus.style.display = 'block';

                    // Clear form
                    replyMessage.value = '';

                    // Reload page after 2 seconds to show updated reply history
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error message
                    replyStatus.className = 'alert alert-danger';
                    replyStatus.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + data.error;
                    replyStatus.style.display = 'block';

                    // Reset button
                    sendReplyBtn.disabled = false;
                    sendReplyBtn.innerHTML = '<i class="bi bi-send me-2"></i>Send Reply';
                }
            })
            .catch(error => {
                replyStatus.className = 'alert alert-danger';
                replyStatus.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Error: ' + error.message;
                replyStatus.style.display = 'block';

                // Reset button
                sendReplyBtn.disabled = false;
                sendReplyBtn.innerHTML = '<i class="bi bi-send me-2"></i>Send Reply';
            });
        });
    }

    // Handle AI Assist button
    if (aiAssistBtn) {
        aiAssistBtn.addEventListener('click', function() {
            // Show loading state
            aiAssistBtn.disabled = true;
            aiAssistBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
            replyStatus.style.display = 'none';

            // Get current draft
            const currentDraft = replyMessage.value.trim();

            // Prepare form data
            const formData = new FormData();
            formData.append('contact_message_id', document.querySelector('input[name="contact_message_id"]').value);
            formData.append('current_draft', currentDraft);

            // Send AJAX request
            fetch('/admin/ajax/contact-reply-ai-assist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Insert AI-generated reply
                    replyMessage.value = data.suggested_reply;

                    // Show success message
                    replyStatus.className = 'alert alert-info';
                    replyStatus.innerHTML = '<i class="bi bi-robot me-2"></i>AI suggestion generated (' + data.tokens_used + ' tokens used). Feel free to edit before sending.';
                    replyStatus.style.display = 'block';

                    // Focus on message field
                    replyMessage.focus();
                } else {
                    // Show error message
                    replyStatus.className = 'alert alert-warning';
                    replyStatus.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>' + data.error;
                    replyStatus.style.display = 'block';
                }

                // Reset button
                aiAssistBtn.disabled = false;
                aiAssistBtn.innerHTML = '<i class="bi bi-robot me-2"></i>AI Assist';
            })
            .catch(error => {
                replyStatus.className = 'alert alert-danger';
                replyStatus.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Error: ' + error.message;
                replyStatus.style.display = 'block';

                // Reset button
                aiAssistBtn.disabled = false;
                aiAssistBtn.innerHTML = '<i class="bi bi-robot me-2"></i>AI Assist';
            });
        });
    }
});

// ============================================================================
// Generate AI Reply for Modal Form
// ============================================================================
function generateAIReply() {
    const aiAssistBtn = document.getElementById('aiAssistBtn');
    const replyMessageField = document.getElementById('reply_message');
    const contactEventId = document.querySelector('input[name="contact_event_id"]').value;

    // Show loading state
    const originalBtnText = aiAssistBtn.innerHTML;
    aiAssistBtn.disabled = true;
    aiAssistBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

    // Get current draft
    const currentDraft = replyMessageField.value.trim();

    // Prepare form data
    const formData = new FormData();
    formData.append('contact_message_id', contactEventId);
    formData.append('current_draft', currentDraft);

    // Send AJAX request
    fetch('/admin/ajax/contact-reply-ai-assist.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Insert AI-generated reply
            replyMessageField.value = data.suggested_reply;

            // Show success notification
            alert('✓ AI suggestion generated successfully!\n\nTokens used: ' + data.tokens_used + '\n\nFeel free to edit the message before sending.');

            // Focus on message field
            replyMessageField.focus();
        } else {
            // Show error message
            alert('⚠ AI assist failed: ' + data.error);
        }

        // Reset button
        aiAssistBtn.disabled = false;
        aiAssistBtn.innerHTML = originalBtnText;
    })
    .catch(error => {
        alert('✗ Error generating AI reply: ' + error.message);

        // Reset button
        aiAssistBtn.disabled = false;
        aiAssistBtn.innerHTML = originalBtnText;
    });
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
