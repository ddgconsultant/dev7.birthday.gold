<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Contact Messages - Admin Dashboard";
$page_description = "Contact form submissions and AI spam detection analytics";

// Get filters from query params
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'all';
$ai_filter = $_GET['ai_decision'] ?? 'all';

// Build WHERE clause based on filters
$where_conditions = ["create_dt BETWEEN :date_from AND DATE_ADD(:date_to, INTERVAL 1 DAY)"];
$query_params = ['date_from' => $date_from, 'date_to' => $date_to];

// Status filter
if ($status_filter === 'spam') {
    $where_conditions[] = "name = 'contact-ai-spam-check' AND JSON_EXTRACT(tracking_data, '$.ai_decision') LIKE '%SPAM%'";
} elseif ($status_filter === 'legitimate') {
    $where_conditions[] = "name = 'contact-ai-legitimate'";
} elseif ($status_filter === 'sent') {
    $where_conditions[] = "name IN ('contact-form-sent', 'contact-mail-sent-successfully')";
} elseif ($status_filter === 'failed') {
    $where_conditions[] = "name IN ('sendOnlineContactForm_error!!', 'contact_form_email_failed_retry')";
} elseif ($status_filter === 'captcha_fail') {
    $where_conditions[] = "name = 'contact-captcha-fail'";
} else {
    // All contact-related events
    $where_conditions[] = "name LIKE '%contact%'";
}

$where_clause = implode(' AND ', $where_conditions);

#-------------------------------------------------------------------------------
# ANALYTICS QUERIES
#-------------------------------------------------------------------------------

// 1. Get overall stats
$stats_sql = "
SELECT
    SUM(CASE WHEN name = 'contact-form-sent' THEN 1 ELSE 0 END) as total_sent,
    SUM(CASE WHEN name = 'contact-ai-spam-check' AND JSON_EXTRACT(tracking_data, '\$.ai_decision') LIKE '%SPAM%' THEN 1 ELSE 0 END) as total_spam,
    SUM(CASE WHEN name = 'contact-ai-legitimate' THEN 1 ELSE 0 END) as total_legitimate,
    SUM(CASE WHEN name = 'contact-captcha-fail' THEN 1 ELSE 0 END) as total_captcha_fails,
    SUM(CASE WHEN name IN ('sendOnlineContactForm_error!!', 'contact_form_email_failed_retry') THEN 1 ELSE 0 END) as total_errors
FROM bg_sessiontracking
WHERE create_dt BETWEEN :date_from AND DATE_ADD(:date_to, INTERVAL 1 DAY)
";
$stats = $database->getrow($stats_sql, $query_params);

// 2. Get submissions over time
$timeline_sql = "
SELECT
    DATE(create_dt) as date,
    SUM(CASE WHEN name = 'contact-form-sent' THEN 1 ELSE 0 END) as sent,
    SUM(CASE WHEN name = 'contact-ai-spam-check' AND JSON_EXTRACT(tracking_data, '\$.ai_decision') LIKE '%SPAM%' THEN 1 ELSE 0 END) as spam,
    SUM(CASE WHEN name = 'contact-captcha-fail' THEN 1 ELSE 0 END) as captcha_fails
FROM bg_sessiontracking
WHERE create_dt BETWEEN :date_from AND DATE_ADD(:date_to, INTERVAL 1 DAY)
    AND name LIKE '%contact%'
GROUP BY DATE(create_dt)
ORDER BY date DESC
";
$timeline_data = $database->query($timeline_sql, $query_params)->fetchAll();

// 3. Get detailed message list
$messages_sql = "
SELECT
    id,
    create_dt,
    name,
    ip,
    sessionid,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '\$.email')) as email,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '\$.subject')) as subject,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '\$.message_preview')) as message_preview,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '\$.ai_decision')) as ai_decision,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '\$.status')) as status,
    JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '\$.confirmed_after_spam')) as confirmed_after_spam,
    tracking_data
FROM bg_sessiontracking
WHERE $where_clause
ORDER BY create_dt DESC
LIMIT 100
";
$messages = $database->query($messages_sql, $query_params)->fetchAll();

// Auto-redirect to detail page if only 1 result
if (count($messages) === 1 && $status_filter !== 'all') {
    header('Location: /admin/contact-message-detail?id=' . $messages[0]['id']);
    exit;
}

// Page styles
$additionalstyles .= '
<style>
.stat-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    text-decoration: none;
    color: inherit;
    display: block;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: var(--bs-primary);
    text-decoration: none;
    color: inherit;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.filter-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    margin-bottom: 1.5rem;
}

.message-row {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
    cursor: pointer;
}

.message-row:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.message-row.spam {
    border-left: 4px solid #dc3545;
}

.message-row.legitimate {
    border-left: 4px solid #28a745;
}

.message-row.sent {
    border-left: 4px solid #0d6efd;
}

.message-row.failed {
    border-left: 4px solid #fd7e14;
}

.timeline-row {
    background: white;
    padding: 0.75rem;
    border-bottom: 1px solid #e9ecef;
}

.timeline-row:hover {
    background: #f8f9fa;
}

.timeline-date-cell {
    padding-left: 1.5rem !important;
    cursor: pointer;
    transition: all 0.2s ease;
}

.timeline-date-cell:hover {
    color: var(--bs-primary);
    font-weight: 600;
}

.timeline-date-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.timeline-date-link:hover {
    color: var(--bs-primary);
    text-decoration: underline;
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

.badge-warning {
    background-color: #ffc107;
    color: #000;
}

/* Make badges interactive when wrapped in links */
a .badge {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

a:hover .badge {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin">
    <div class="container">
        <h1><i class="bi bi-envelope me-2"></i>Contact Messages</h1>
        <p class="lead">Contact form submissions and AI spam detection analytics</p>
    </div>
</div>

<div class="container py-4">
    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" action="/admin/contact-messages">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Events</option>
                        <option value="spam" <?php echo $status_filter === 'spam' ? 'selected' : ''; ?>>Spam Detected</option>
                        <option value="legitimate" <?php echo $status_filter === 'legitimate' ? 'selected' : ''; ?>>Legitimate</option>
                        <option value="sent" <?php echo $status_filter === 'sent' ? 'selected' : ''; ?>>Successfully Sent</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed/Errors</option>
                        <option value="captcha_fail" <?php echo $status_filter === 'captcha_fail' ? 'selected' : ''; ?>>Captcha Failures</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <a href="/admin/contact-messages?date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status=sent" class="stat-card text-center" title="Click to view sent messages">
                <div class="stat-value text-primary"><?php echo number_format($stats['total_sent'] ?? 0); ?></div>
                <div class="stat-label">Sent</div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="/admin/contact-messages?date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status=spam" class="stat-card text-center" title="Click to view spam messages">
                <div class="stat-value text-danger"><?php echo number_format($stats['total_spam'] ?? 0); ?></div>
                <div class="stat-label">Spam Detected</div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="/admin/contact-messages?date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status=legitimate" class="stat-card text-center" title="Click to view legitimate messages">
                <div class="stat-value text-success"><?php echo number_format($stats['total_legitimate'] ?? 0); ?></div>
                <div class="stat-label">Legitimate</div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="/admin/contact-messages?date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status=captcha_fail" class="stat-card text-center" title="Click to view captcha failures">
                <div class="stat-value text-warning"><?php echo number_format($stats['total_captcha_fails'] ?? 0); ?></div>
                <div class="stat-label">Captcha Fails</div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="/admin/contact-messages?date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status=failed" class="stat-card text-center" title="Click to view failed/error messages">
                <div class="stat-value text-danger"><?php echo number_format($stats['total_errors'] ?? 0); ?></div>
                <div class="stat-label">Errors</div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="/admin/contact-messages?date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status=all" class="stat-card text-center" title="Click to view all activity">
                <div class="stat-value"><?php
                $total_attempts = ($stats['total_sent'] ?? 0) + ($stats['total_spam'] ?? 0) + ($stats['total_captcha_fails'] ?? 0);
                echo number_format($total_attempts);
                ?></div>
                <div class="stat-label">Total Activity</div>
            </a>
        </div>
    </div>

    <!-- Timeline -->
    <?php if (!empty($timeline_data)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Activity Timeline</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="padding-left: 1.5rem;">Date</th>
                            <th class="text-center">Sent</th>
                            <th class="text-center">Spam</th>
                            <th class="text-center">Captcha Fails</th>
                            <th class="text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeline_data as $row): ?>
                        <tr class="timeline-row">
                            <td class="timeline-date-cell">
                                <a href="/admin/contact-messages?date_from=<?php echo urlencode($row['date']); ?>&date_to=<?php echo urlencode($row['date']); ?>&status=<?php echo urlencode($status_filter); ?>" class="timeline-date-link" title="View messages from this date">
                                    <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <?php if ($row['sent'] > 0): ?>
                                <a href="/admin/contact-messages?date_from=<?php echo urlencode($row['date']); ?>&date_to=<?php echo urlencode($row['date']); ?>&status=sent" class="text-decoration-none" title="View sent messages from this date">
                                    <span class="badge badge-sent"><?php echo $row['sent']; ?></span>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['spam'] > 0): ?>
                                <a href="/admin/contact-messages?date_from=<?php echo urlencode($row['date']); ?>&date_to=<?php echo urlencode($row['date']); ?>&status=spam" class="text-decoration-none" title="View spam messages from this date">
                                    <span class="badge badge-spam"><?php echo $row['spam']; ?></span>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['captcha_fails'] > 0): ?>
                                <a href="/admin/contact-messages?date_from=<?php echo urlencode($row['date']); ?>&date_to=<?php echo urlencode($row['date']); ?>&status=captcha_fail" class="text-decoration-none" title="View captcha failures from this date">
                                    <span class="badge badge-warning"><?php echo $row['captcha_fails']; ?></span>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php echo $row['sent'] + $row['spam'] + $row['captcha_fails']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Message List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Recent Messages (Last 100)</h5>
            <span class="text-muted"><?php echo count($messages); ?> results</span>
        </div>
        <div class="card-body">
            <?php if (empty($messages)): ?>
                <p class="text-muted text-center py-4">No messages found for the selected filters.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg):
                    // Determine row class based on event type
                    $rowClass = '';
                    $statusBadge = '';

                    if ($msg['name'] === 'contact-ai-spam-check' && strpos($msg['ai_decision'], 'SPAM') !== false) {
                        $rowClass = 'spam';
                        $statusBadge = '<span class="badge badge-spam">SPAM</span>';
                    } elseif ($msg['name'] === 'contact-ai-legitimate') {
                        $rowClass = 'legitimate';
                        $statusBadge = '<span class="badge badge-legitimate">LEGITIMATE</span>';
                    } elseif (in_array($msg['name'], ['contact-form-sent', 'contact-mail-sent-successfully'])) {
                        $rowClass = 'sent';
                        $statusBadge = '<span class="badge badge-sent">SENT</span>';
                    } elseif (in_array($msg['name'], ['sendOnlineContactForm_error!!', 'contact_form_email_failed_retry'])) {
                        $rowClass = 'failed';
                        $statusBadge = '<span class="badge bg-danger">FAILED</span>';
                    } elseif ($msg['name'] === 'contact-captcha-fail') {
                        $rowClass = 'failed';
                        $statusBadge = '<span class="badge badge-warning">CAPTCHA FAIL</span>';
                    }
                ?>
                <div class="message-row <?php echo $rowClass; ?>" onclick="window.location.href='/admin/contact-message-detail?id=<?php echo $msg['id']; ?>'">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <?php echo $statusBadge; ?>
                                <span class="text-muted small"><?php echo date('M d, Y g:i A', strtotime($msg['create_dt'])); ?></span>
                                <?php if ($msg['confirmed_after_spam'] === 'yes'): ?>
                                <span class="badge bg-info">User Confirmed</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($msg['email']): ?>
                            <div class="fw-bold"><?php echo htmlspecialchars($msg['email']); ?></div>
                            <?php endif; ?>
                            <?php if ($msg['subject']): ?>
                            <div class="text-primary"><?php echo htmlspecialchars($msg['subject']); ?></div>
                            <?php endif; ?>
                            <?php if ($msg['message_preview']): ?>
                            <div class="text-muted small mt-1"><?php echo htmlspecialchars($msg['message_preview']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">ID: <?php echo $msg['id']; ?></small>
                            <small class="text-muted d-block">IP: <?php echo htmlspecialchars($msg['ip']); ?></small>
                        </div>
                    </div>
                    <div class="small text-muted">
                        Event: <code><?php echo htmlspecialchars($msg['name']); ?></code>
                        <?php if ($msg['ai_decision']): ?>
                        | AI: <strong><?php echo htmlspecialchars($msg['ai_decision']); ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
