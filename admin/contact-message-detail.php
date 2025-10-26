<?php
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
$tracking_data = !empty($message['tracking_data']) ? json_decode($message['tracking_data'], true) : [];
$session_data = !empty($message['session_data']) ? json_decode($message['session_data'], true) : [];
$server_data = !empty($message['server_data']) ? json_decode($message['server_data'], true) : [];
$request_data = !empty($message['request_data']) ? json_decode($message['request_data'], true) : [];

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

                <?php if (!empty($tracking_data['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($tracking_data['email']); ?>" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-envelope me-2"></i>Reply via Email
                </a>
                <?php endif; ?>

                <a href="/admin/sessiondetails?sid=<?php echo urlencode($message['sessionid']); ?>" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-diagram-3 me-2"></i>View Full Session
                </a>

                <a href="/admin/contact-messages?status=all" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-list me-2"></i>Back to All Messages
                </a>
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
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
