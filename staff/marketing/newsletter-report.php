<?PHP

$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Newsletter Reports";

// Get user's company context
$company_id = $current_user_data['company_id'] ?? 99;
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

$additionalstyles = '
<style>
.container {
    margin-top: 3rem;
}
</style>
';

// Get all campaigns for dashboard - with error handling for missing tables
try {
    $campaigns_sql = "SELECT c.*, mk.campaign_name as mk_campaign_name, mk.company_id,
        (SELECT COUNT(*) FROM bg_newsletter_queue q WHERE q.campaign_id = c.campaign_id) as total_queued,
        (SELECT COUNT(*) FROM bg_newsletter_queue q WHERE q.campaign_id = c.campaign_id AND q.status = 'sent') as sent_count
        FROM bg_newsletter_campaigns c 
        LEFT JOIN mk_campaigns mk ON c.mk_campaign_id = mk.campaign_id
        WHERE (mk.company_id = :company_id OR (c.mk_campaign_id IS NULL AND c.created_by IN (SELECT user_id FROM bg_users WHERE company_id = :company_id2)))
        ORDER BY c.create_dt DESC 
        LIMIT 20";

    $campaigns = $database->getrows($campaigns_sql, [
        'company_id' => $active_company_id,
        'company_id2' => $active_company_id
    ]);
} catch (Exception $e) {
    // Tables do not exist yet
    $campaigns = [];
}

// Handle status message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-envelope-paper"></i> Newsletter Reports</h1>
        <p class="lead">Newsletter management and batch delivery system</p>';

// Show company context
if ($active_company_id == 0) {
    echo '
        <div class="badge bg-primary fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Birthday Gold (Internal Marketing)
        </div>';
} elseif ($active_company_id == 99) {
    echo '
        <div class="badge bg-info fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Demo Company
        </div>';
} else {
    echo '
        <div class="badge bg-info fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Company ID: ' . $active_company_id . '
        </div>';
}

echo '
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-12">';
            
if ($message) {
    echo $message;
}

echo '
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-envelope-paper"></i> Newsletter Management</h2>
                <div>
                    <a href="newsletter-edit" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Create New Newsletter
                    </a>
                    <a href="newsletter-report" class="btn btn-outline-secondary">
                        <i class="bi bi-list"></i> All Newsletters
                    </a>
                </div>
            </div>';

// Calculate stats
$draft_count = count(array_filter($campaigns, function($c) { return $c['status'] == 'draft'; }));
$queued_count = count(array_filter($campaigns, function($c) { return in_array($c['status'], ['scheduled', 'queued']); }));
$active_count = count(array_filter($campaigns, function($c) { return $c['status'] == 'sending'; }));
$completed_count = count(array_filter($campaigns, function($c) { return $c['status'] == 'completed'; }));

echo '
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-75">Draft Newsletters</div>
                                    <div class="h2 mb-0">' . $draft_count . '</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-file-earmark-text fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-75">Queued Newsletters</div>
                                    <div class="h2 mb-0">' . $queued_count . '</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-75">Active Newsletters</div>
                                    <div class="h2 mb-0">' . $active_count . '</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-send fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-75">Completed Newsletters</div>
                                    <div class="h2 mb-0">' . $completed_count . '</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Newsletters</h5>
                </div>
                <div class="card-body">';

if (empty($campaigns)) {
    echo '
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <h4 class="text-muted mt-3">No Newsletters Found</h4>
                        <p class="text-muted">Create your first newsletter to get started.</p>
                        <a href="newsletter-edit" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Create Newsletter
                        </a>
                    </div>';
} else {
    echo '
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Subject</th>
                                    <th>CTA Category</th>
                                    <th>Status</th>
                                    <th>Queued/Sent</th>
                                    <th>Scheduled</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    foreach ($campaigns as $campaign) {
        $status_class = [
            'draft' => 'bg-secondary',
            'scheduled' => 'bg-warning',
            'queued' => 'bg-warning',
            'sending' => 'bg-primary',
            'completed' => 'bg-success',
            'paused' => 'bg-info',
            'cancelled' => 'bg-danger'
        ][$campaign['status']] ?? 'bg-secondary';
        
        echo '
                                <tr>
                                    <td>
                                        <strong>' . htmlspecialchars($campaign['title']) . '</strong>
                                        <br><small class="text-muted">ID: ' . $campaign['campaign_id'] . '</small>
                                    </td>
                                    <td>' . htmlspecialchars(substr($campaign['subject'], 0, 50)) . (strlen($campaign['subject']) > 50 ? '...' : '') . '</td>
                                    <td>';
        
        if ($campaign['cta_category']) {
            echo '<span class="badge bg-info">' . htmlspecialchars($campaign['cta_category']) . '</span>';
        } else {
            echo '<span class="text-muted">None</span>';
        }
        
        echo '
                                    </td>
                                    <td>
                                        <span class="badge ' . $status_class . '">' . ucfirst($campaign['status']) . '</span>
                                    </td>
                                    <td>';
        
        if ($campaign['total_queued'] > 0) {
            $progress = ($campaign['sent_count'] / $campaign['total_queued']) * 100;
            echo '
                                        <div class="progress" style="width: 100px; height: 15px;">
                                            <div class="progress-bar" style="width: ' . $progress . '%"></div>
                                        </div>
                                        <small>' . $campaign['sent_count'] . ' / ' . $campaign['total_queued'] . '</small>';
        } else {
            echo '<span class="text-muted">Not queued</span>';
        }
        
        echo '
                                    </td>
                                    <td>';
        
        if ($campaign['send_dt']) {
            echo date('M j, Y g:i A', strtotime($campaign['send_dt']));
        } else {
            echo '<span class="text-muted">Not scheduled</span>';
        }
        
        echo '
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="bi bi-gear"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="preview.php?id=' . $campaign['campaign_id'] . '">
                                                    <i class="bi bi-eye"></i> Preview
                                                </a></li>';
        
        if (in_array($campaign['status'], ['draft', 'scheduled'])) {
            echo '
                                                <li><a class="dropdown-item" href="newsletter-edit.php?id=' . $campaign['campaign_id'] . '">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a></li>
                                                <li><a class="dropdown-item" href="send.php?id=' . $campaign['campaign_id'] . '">
                                                    <i class="bi bi-send"></i> Queue for Sending
                                                </a></li>';
        }
        
        echo '
                                                <li><a class="dropdown-item" href="newsletter-reports.php?id=' . $campaign['campaign_id'] . '">
                                                    <i class="bi bi-graph-up"></i> Reports
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(' . $campaign['campaign_id'] . ')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>';
    }
    
    echo '
                            </tbody>
                        </table>
                    </div>';
}

echo '
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(campaignId) {
    if (confirm("Are you sure you want to delete this campaign? This action cannot be undone.")) {
        window.location.href = "ajax/newsletter-delete.php?id=" + campaignId;
    }
}
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>