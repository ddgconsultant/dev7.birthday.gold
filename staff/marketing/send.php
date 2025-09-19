<?PHP
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pagetitle = "Queue Campaign for Sending";

if (!$campaign_id) {
    header('Location: newsletter-report.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'queue_campaign') {
        // Get send parameters
        $send_immediately = $_POST['send_immediately'] ?? false;
        $scheduled_dt = $_POST['scheduled_dt'] ?? '';
        $override_criteria = $_POST['override_criteria'] ?? false;
        
        // Parse criteria override if provided
        $criteria_override = null;
        if ($override_criteria && !empty($_POST['criteria_json'])) {
            $criteria_override = json_decode($_POST['criteria_json'], true);
        }
        
        // Update campaign send time if provided
        if (!$send_immediately && $scheduled_dt) {
            $update_sql = "UPDATE bg_newsletter_campaigns SET send_dt = :send_dt WHERE campaign_id = :campaign_id";
            $database->query($update_sql, [
                'send_dt' => $scheduled_dt,
                'campaign_id' => $campaign_id
            ]);
        }
        
        // Queue the campaign
        $result = $marketing->queueCampaignForSending($campaign_id, $criteria_override);
        
        if ($result['success']) {
            $_SESSION['message'] = '<div class="alert alert-success">
                <i class="bi bi-check-circle"></i> Campaign queued successfully! 
                ' . $result['queued_count'] . ' users queued for delivery.
            </div>';
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> Error queuing campaign: ' . $result['error'] . '
            </div>';
        }
        
        header('Location: index.php');
        exit;
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] == 'get_recipient_count') {
        // Get estimated recipient count
        $criteria = [];
        if (!empty($_GET['criteria'])) {
            $criteria = json_decode($_GET['criteria'], true);
        }
        
        // Get campaign to extract default criteria if no override
        $campaign_sql = "SELECT recipient_criteria FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
        $campaign = $database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);
        
        if (!$criteria && !empty($campaign['recipient_criteria'])) {
            $criteria = json_decode($campaign['recipient_criteria'], true);
        }
        
        // Build count query
        $where_conditions = ["u.status = 'active'"];
        $params = [];
        
        if (!empty($criteria['age_range'])) {
            if (!empty($criteria['age_range']['min'])) {
                $where_conditions[] = "TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) >= :min_age";
                $params['min_age'] = $criteria['age_range']['min'];
            }
            if (!empty($criteria['age_range']['max'])) {
                $where_conditions[] = "TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) <= :max_age";
                $params['max_age'] = $criteria['age_range']['max'];
            }
        }
        
        if (!empty($criteria['city'])) {
            $where_conditions[] = "u.city = :city";
            $params['city'] = $criteria['city'];
        }
        
        if (!empty($criteria['state'])) {
            $where_conditions[] = "u.state = :state";
            $params['state'] = $criteria['state'];
        }
        
        if (!empty($criteria['birth_month'])) {
            $where_conditions[] = "u.birth_month = :birth_month";
            $params['birth_month'] = $criteria['birth_month'];
        }
        
        // Exclude unsubscribed users
        $where_conditions[] = "u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')";
        
        $count_sql = "SELECT COUNT(*) as count FROM bg_users u WHERE " . implode(' AND ', $where_conditions);
        $count_result = $database->getrow($count_sql, $params);
        
        echo json_encode(['count' => $count_result['count'] ?? 0]);
        exit;
    }
    
    if ($_GET['action'] == 'get_stats') {
        $stats = $marketing->getCampaignStats($campaign_id);
        echo json_encode($stats);
        exit;
    }
}

// Get campaign details
$campaign_sql = "SELECT * FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
$campaign = $database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);

if (!$campaign) {
    header('Location: index.php');
    exit;
}

// Check if campaign is eligible for sending
$can_send = in_array($campaign['status'], ['draft', 'scheduled']);

// Get current queue stats
$stats = $marketing->getCampaignStats($campaign_id);

// Get available states and cities for criteria building
$states_sql = "SELECT DISTINCT state FROM bg_users WHERE state IS NOT NULL AND state != '' ORDER BY state";
$states = $database->getrows($states_sql);

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-send"></i> Queue Campaign for Sending</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index">Campaigns</a></li>
                            <li class="breadcrumb-item active">Send: <?= htmlspecialchars($campaign['title']) ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="preview.php?id=<?= $campaign_id ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i> Preview
                    </a>
                    <a href="index" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Back to Campaigns
                    </a>
                </div>
            </div>

            <?php if (!$can_send): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    This campaign cannot be sent in its current state. Status: <strong><?= ucfirst($campaign['status']) ?></strong>
                    <br>Only campaigns with 'draft' or 'scheduled' status can be queued for sending.
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Left Column - Send Configuration -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-gear"></i> Sending Configuration</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="sendForm">
                                <input type="hidden" name="action" value="queue_campaign">
                                
                                <!-- Campaign Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Campaign Details</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-borderless">
                                                        <tr>
                                                            <td><strong>Title:</strong></td>
                                                            <td><?= htmlspecialchars($campaign['title']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Subject:</strong></td>
                                                            <td><?= htmlspecialchars($campaign['subject']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>CTA Category:</strong></td>
                                                            <td>
                                                                <?= $campaign['cta_category'] ? htmlspecialchars($campaign['cta_category']) : '<span class="text-muted">None</span>' ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Current Status:</strong></td>
                                                            <td>
                                                                <span class="badge bg-secondary"><?= ucfirst($campaign['status']) ?></span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Recipient Estimate</h6>
                                                <div class="text-center">
                                                    <div class="h2 text-primary" id="recipient-count">
                                                        <div class="spinner-border spinner-border-sm" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                    </div>
                                                    <p class="text-muted">Estimated Recipients</p>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshRecipientCount()">
                                                        <i class="bi bi-arrow-clockwise"></i> Refresh
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Scheduling Options -->
                                <div class="mb-4">
                                    <h6>When to Send</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="send_timing" id="send_now" value="now" checked>
                                        <label class="form-check-label" for="send_now">
                                            <strong>Queue Immediately</strong>
                                            <br><small class="text-muted">Add to queue and begin sending based on worker schedule</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="send_timing" id="send_scheduled" value="scheduled">
                                        <label class="form-check-label" for="send_scheduled">
                                            <strong>Schedule for Later</strong>
                                            <br><small class="text-muted">Queue for sending at a specific time</small>
                                        </label>
                                    </div>
                                    
                                    <div id="schedule-options" class="mt-3" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Send Date & Time</label>
                                                <input type="datetime-local" 
                                                       class="form-control" 
                                                       name="scheduled_dt" 
                                                       id="scheduled_dt"
                                                       value="<?= $campaign['send_dt'] ? date('Y-m-d\TH:i', strtotime($campaign['send_dt'])) : '' ?>"
                                                       min="<?= date('Y-m-d\TH:i') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Options -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="override_criteria">
                                        <label class="form-check-label" for="override_criteria">
                                            <strong>Override recipient criteria</strong>
                                            <br><small class="text-muted">Customize who receives this campaign</small>
                                        </label>
                                    </div>
                                    
                                    <div id="criteria-override" class="mt-3" style="display: none;">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6>Custom Recipient Criteria</h6>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Birth Month</label>
                                                        <select class="form-select" name="criteria_birth_month">
                                                            <option value="">Any Month</option>
                                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                                                <option value="<?= $i ?>"><?= date('F', mktime(0, 0, 0, $i, 1)) ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">State</label>
                                                        <select class="form-select" name="criteria_state">
                                                            <option value="">Any State</option>
                                                            <?php foreach ($states as $state): ?>
                                                                <option value="<?= htmlspecialchars($state['state']) ?>"><?= htmlspecialchars($state['state']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Minimum Age</label>
                                                        <input type="number" class="form-control" name="criteria_min_age" placeholder="18" min="13" max="100">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Maximum Age</label>
                                                        <input type="number" class="form-control" name="criteria_max_age" placeholder="65" min="13" max="100">
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">City</label>
                                                    <input type="text" class="form-control" name="criteria_city" placeholder="Specific city (optional)">
                                                </div>
                                                
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshRecipientCount()">
                                                    <i class="bi bi-calculator"></i> Recalculate Recipients
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                                            <i class="bi bi-arrow-left"></i> Cancel
                                        </button>
                                    </div>
                                    <div>
                                        <?php if ($can_send): ?>
                                            <button type="submit" class="btn btn-success" <?= $stats['queue_stats']['total_queued'] > 0 ? 'onclick="return confirm(\'This campaign already has queued recipients. Continue to add more?\')"' : '' ?>>
                                                <i class="bi bi-send"></i> Queue Campaign for Sending
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-success" disabled>
                                                <i class="bi bi-send"></i> Cannot Send (<?= ucfirst($campaign['status']) ?>)
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Current Status -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Current Queue Status</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($stats['queue_stats']['total_queued'] > 0): ?>
                                <div class="mb-3">
                                    <h6>Queue Statistics</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Total Queued:</td>
                                                <td><strong><?= $stats['queue_stats']['total_queued'] ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td>Pending:</td>
                                                <td><span class="badge bg-warning"><?= $stats['queue_stats']['pending'] ?></span></td>
                                            </tr>
                                            <tr>
                                                <td>Processing:</td>
                                                <td><span class="badge bg-primary"><?= $stats['queue_stats']['processing'] ?></span></td>
                                            </tr>
                                            <tr>
                                                <td>Sent:</td>
                                                <td><span class="badge bg-success"><?= $stats['queue_stats']['sent'] ?></span></td>
                                            </tr>
                                            <tr>
                                                <td>Errors:</td>
                                                <td><span class="badge bg-danger"><?= $stats['queue_stats']['errors'] ?></span></td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <?php if ($stats['queue_stats']['sent'] > 0): ?>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-success" 
                                                 style="width: <?= ($stats['queue_stats']['sent'] / $stats['queue_stats']['total_queued']) * 100 ?>%">
                                                <?= round(($stats['queue_stats']['sent'] / $stats['queue_stats']['total_queued']) * 100) ?>%
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                    <p>No emails queued yet</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Real-time Updates -->
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshStats()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Status
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Worker Status -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-cpu"></i> Mail Worker Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <div class="mb-2">
                                    <strong>Batch Size:</strong> 100 emails per run
                                </div>
                                <div class="mb-2">
                                    <strong>Rate Limit:</strong> ~10 emails/second
                                </div>
                                <div class="text-muted">
                                    The mail worker processes queued emails automatically. Large campaigns are sent in batches to ensure reliable delivery.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle radio button changes
document.addEventListener('DOMContentLoaded', function() {
    const sendNow = document.getElementById('send_now');
    const sendScheduled = document.getElementById('send_scheduled');
    const scheduleOptions = document.getElementById('schedule-options');
    
    sendScheduled.addEventListener('change', function() {
        if (this.checked) {
            scheduleOptions.style.display = 'block';
        }
    });
    
    sendNow.addEventListener('change', function() {
        if (this.checked) {
            scheduleOptions.style.display = 'none';
        }
    });
    
    // Handle criteria override
    const overrideCriteria = document.getElementById('override_criteria');
    const criteriaOverride = document.getElementById('criteria-override');
    
    overrideCriteria.addEventListener('change', function() {
        criteriaOverride.style.display = this.checked ? 'block' : 'none';
        refreshRecipientCount();
    });
    
    // Load initial recipient count
    refreshRecipientCount();
});

function refreshRecipientCount() {
    const countElement = document.getElementById('recipient-count');
    countElement.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';
    
    let criteria = {};
    
    // Check if override is enabled
    if (document.getElementById('override_criteria').checked) {
        const birthMonth = document.querySelector('[name="criteria_birth_month"]').value;
        const state = document.querySelector('[name="criteria_state"]').value;
        const minAge = document.querySelector('[name="criteria_min_age"]').value;
        const maxAge = document.querySelector('[name="criteria_max_age"]').value;
        const city = document.querySelector('[name="criteria_city"]').value;
        
        if (birthMonth) criteria.birth_month = birthMonth;
        if (state) criteria.state = state;
        if (city) criteria.city = city;
        if (minAge || maxAge) {
            criteria.age_range = {};
            if (minAge) criteria.age_range.min = parseInt(minAge);
            if (maxAge) criteria.age_range.max = parseInt(maxAge);
        }
    }
    
    const url = new URL(window.location.href);
    url.searchParams.set('action', 'get_recipient_count');
    if (Object.keys(criteria).length > 0) {
        url.searchParams.set('criteria', JSON.stringify(criteria));
    }
    
    fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            countElement.textContent = data.count.toLocaleString();
        })
        .catch(err => {
            countElement.innerHTML = '<span class="text-danger">Error</span>';
            console.error('Error getting recipient count:', err);
        });
}

function refreshStats() {
    const url = new URL(window.location.href);
    url.searchParams.set('action', 'get_stats');
    
    fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            // Refresh the page to show updated stats
            // In a more advanced implementation, you could update the DOM directly
            window.location.reload();
        })
        .catch(err => {
            console.error('Error refreshing stats:', err);
        });
}

// Form submission handling
document.getElementById('sendForm').addEventListener('submit', function(e) {
    const recipientCount = parseInt(document.getElementById('recipient-count').textContent.replace(/,/g, ''));
    
    if (recipientCount === 0) {
        e.preventDefault();
        alert('No recipients match the current criteria. Please adjust your settings.');
        return;
    }
    
    if (!confirm(`Are you sure you want to queue this campaign for ${recipientCount.toLocaleString()} recipients?`)) {
        e.preventDefault();
    }
});
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();