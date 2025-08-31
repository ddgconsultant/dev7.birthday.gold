<?PHP
$addClasses[] = 'marketing';
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Newsletter Campaign Management";

// Get all campaigns for dashboard
$campaigns_sql = "SELECT c.*, 
    (SELECT COUNT(*) FROM bg_newsletter_queue q WHERE q.campaign_id = c.campaign_id) as total_queued,
    (SELECT COUNT(*) FROM bg_newsletter_queue q WHERE q.campaign_id = c.campaign_id AND q.status = 'sent') as sent_count
    FROM bg_newsletter_campaigns c 
    ORDER BY c.create_dt DESC 
    LIMIT 20";

$campaigns = $database->getrows($campaigns_sql);

// Handle status message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <?php if ($message): ?>
                <?= $message ?>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-envelope-paper"></i> Newsletter Campaign Management</h2>
                <div>
                    <a href="newsletter-edit.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Create New Campaign
                    </a>
                    <a href="newsletter-list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-list"></i> All Campaigns
                    </a>
                </div>
            </div>

            <!-- Quick Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-75">Draft Campaigns</div>
                                    <div class="h2 mb-0">
                                        <?php 
                                        $draft_count = count(array_filter($campaigns, function($c) { return $c['status'] == 'draft'; }));
                                        echo $draft_count; 
                                        ?>
                                    </div>
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
                                    <div class="text-white-75">Queued Campaigns</div>
                                    <div class="h2 mb-0">
                                        <?php 
                                        $queued_count = count(array_filter($campaigns, function($c) { return in_array($c['status'], ['scheduled', 'queued']); }));
                                        echo $queued_count; 
                                        ?>
                                    </div>
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
                                    <div class="text-white-75">Active Campaigns</div>
                                    <div class="h2 mb-0">
                                        <?php 
                                        $active_count = count(array_filter($campaigns, function($c) { return $c['status'] == 'sending'; }));
                                        echo $active_count; 
                                        ?>
                                    </div>
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
                                    <div class="text-white-75">Completed Campaigns</div>
                                    <div class="h2 mb-0">
                                        <?php 
                                        $completed_count = count(array_filter($campaigns, function($c) { return $c['status'] == 'completed'; }));
                                        echo $completed_count; 
                                        ?>
                                    </div>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Campaigns Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Campaigns</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($campaigns)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <h4 class="text-muted mt-3">No Campaigns Found</h4>
                            <p class="text-muted">Create your first newsletter campaign to get started.</p>
                            <a href="newsletter-edit.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Create Campaign
                            </a>
                        </div>
                    <?php else: ?>
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
                                <tbody>
                                    <?php foreach ($campaigns as $campaign): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($campaign['title']) ?></strong>
                                                <br><small class="text-muted">ID: <?= $campaign['campaign_id'] ?></small>
                                            </td>
                                            <td><?= htmlspecialchars(substr($campaign['subject'], 0, 50)) ?><?= strlen($campaign['subject']) > 50 ? '...' : '' ?></td>
                                            <td>
                                                <?php if ($campaign['cta_category']): ?>
                                                    <span class="badge bg-info"><?= htmlspecialchars($campaign['cta_category']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'draft' => 'bg-secondary',
                                                    'scheduled' => 'bg-warning',
                                                    'queued' => 'bg-warning',
                                                    'sending' => 'bg-primary',
                                                    'completed' => 'bg-success',
                                                    'paused' => 'bg-info',
                                                    'cancelled' => 'bg-danger'
                                                ][$campaign['status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?= $status_class ?>"><?= ucfirst($campaign['status']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($campaign['total_queued'] > 0): ?>
                                                    <div class="progress" style="width: 100px; height: 15px;">
                                                        <div class="progress-bar" 
                                                             style="width: <?= ($campaign['sent_count'] / $campaign['total_queued']) * 100 ?>%">
                                                        </div>
                                                    </div>
                                                    <small><?= $campaign['sent_count'] ?> / <?= $campaign['total_queued'] ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Not queued</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($campaign['send_dt']): ?>
                                                    <?= date('M j, Y g:i A', strtotime($campaign['send_dt'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not scheduled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="preview.php?id=<?= $campaign['campaign_id'] ?>">
                                                            <i class="bi bi-eye"></i> Preview
                                                        </a></li>
                                                        <?php if (in_array($campaign['status'], ['draft', 'scheduled'])): ?>
                                                            <li><a class="dropdown-item" href="newsletter-edit.php?id=<?= $campaign['campaign_id'] ?>">
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="send.php?id=<?= $campaign['campaign_id'] ?>">
                                                                <i class="bi bi-send"></i> Queue for Sending
                                                            </a></li>
                                                        <?php endif; ?>
                                                        <li><a class="dropdown-item" href="newsletter-reports.php?id=<?= $campaign['campaign_id'] ?>">
                                                            <i class="bi bi-graph-up"></i> Reports
                                                        </a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?= $campaign['campaign_id'] ?>)">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(campaignId) {
    if (confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
        window.location.href = '../ajax/newsletter-delete.php?id=' + campaignId;
    }
}
</script>

<?php include($dir['core_components'] . '/bg_footer.inc'); ?>