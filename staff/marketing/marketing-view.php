<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$campaign_id) {
    header('Location: /staff/marketing-campaigns.php');
    exit;
}

// Load campaign
$campaign_sql = "SELECT * FROM bg_content WHERE id = :id AND category = 'marketing' AND type = 'campaign'";
$campaign = $database->getrow($campaign_sql, ['id' => $campaign_id]);

if (!$campaign) {
    header('Location: /staff/marketing-campaigns.php');
    exit;
}

$campaign_data = json_decode($campaign['tags'], true) ?: [];
$pagetitle = "View Campaign: " . $campaign['display_name'];

// Calculate campaign status
$now = time();
$start = strtotime($campaign['publish_dt']);
$end = $campaign['expire_dt'] ? strtotime($campaign['expire_dt']) : null;

if ($campaign['status'] == 'inactive') {
    $campaign_status = 'draft';
    $status_color = 'secondary';
} elseif ($now < $start) {
    $campaign_status = 'scheduled';
    $status_color = 'info';
} elseif ($end && $now > $end) {
    $campaign_status = 'expired';
    $status_color = 'danger';
} else {
    $campaign_status = 'active';
    $status_color = 'success';
}

// Track view
$database->query("UPDATE bg_content SET views = views + 1 WHERE id = :id", ['id' => $campaign_id]);

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="fas fa-bullhorn"></i> Marketing Campaign</h1>
        <p class="lead"><?= htmlspecialchars($campaign['display_name']) ?></p>
    </div>
</div>

<?php include('../includes/marketing-nav.php'); ?>

<div class="container mt-4 mb-5 pb-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- Campaign Overview -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Campaign Overview</h5>
                    <span class="badge bg-<?= $status_color ?> fs-6">
                        <?= ucfirst($campaign_status) ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if ($campaign['description']): ?>
                        <div class="alert alert-light">
                            <i class="fas fa-info-circle"></i> <?= htmlspecialchars($campaign['description']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Start Date:</strong><br>
                            <?= date('F j, Y g:i A', strtotime($campaign['publish_dt'])) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>End Date:</strong><br>
                            <?= $campaign['expire_dt'] ? date('F j, Y g:i A', strtotime($campaign['expire_dt'])) : 'No end date' ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($campaign_data['platforms'])): ?>
                        <div class="mb-3">
                            <strong>Platforms:</strong><br>
                            <?php foreach ($campaign_data['platforms'] as $platform): ?>
                                <span class="badge bg-primary me-1"><?= htmlspecialchars($platform) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($campaign_data['target_audience'])): ?>
                        <div class="mb-3">
                            <strong>Target Audience:</strong><br>
                            <?= nl2br(htmlspecialchars($campaign_data['target_audience'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($campaign_data['goals'])): ?>
                        <div class="mb-3">
                            <strong>Campaign Goals:</strong><br>
                            <?= nl2br(htmlspecialchars($campaign_data['goals'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Campaign Content -->
            <?php if ($campaign['content']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Campaign Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="campaign-content">
                            <?= $campaign['content'] ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Media Assets -->
            <?php if (!empty($campaign_data['assets'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Media Assets (<?= count($campaign_data['assets']) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($campaign_data['assets'] as $asset): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="border rounded p-2">
                                        <?php if (strpos($asset['type'], 'image') !== false): ?>
                                            <img src="<?= htmlspecialchars($asset['url']) ?>" class="img-fluid" alt="Asset">
                                        <?php elseif (strpos($asset['type'], 'video') !== false): ?>
                                            <video controls class="w-100">
                                                <source src="<?= htmlspecialchars($asset['url']) ?>">
                                            </video>
                                        <?php else: ?>
                                            <div class="text-center p-3">
                                                <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                                <p class="mt-2 mb-0 small"><?= htmlspecialchars($asset['name']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <a href="<?= htmlspecialchars($asset['url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Internal Notes -->
            <?php if (!empty($campaign_data['notes'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Internal Notes</h5>
                    </div>
                    <div class="card-body">
                        <?= nl2br(htmlspecialchars($campaign_data['notes'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    <a href="/staff/marketing-edit.php?id=<?= $campaign_id ?>" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-edit"></i> Edit Campaign
                    </a>
                    <button class="btn btn-outline-secondary w-100 mb-2" onclick="duplicateCampaign()">
                        <i class="fas fa-copy"></i> Duplicate Campaign
                    </button>
                    <button class="btn btn-outline-danger w-100" onclick="deleteCampaign()">
                        <i class="fas fa-trash"></i> Delete Campaign
                    </button>
                </div>
            </div>

            <!-- Budget & Metrics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Budget & Metrics</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($campaign_data['budget'])): ?>
                        <div class="mb-3">
                            <strong>Budget:</strong><br>
                            <span class="fs-4 text-primary">$<?= number_format($campaign_data['budget'], 2) ?></span>
                            <span class="text-muted">(<?= ucfirst($campaign_data['budget_type'] ?? 'total') ?>)</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($campaign_data['metrics'])): ?>
                        <hr>
                        <strong>Goal Metrics:</strong>
                        <ul class="list-unstyled mt-2">
                            <?php if ($campaign_data['metrics']['impressions_goal']): ?>
                                <li><i class="fas fa-eye text-info"></i> <?= number_format($campaign_data['metrics']['impressions_goal']) ?> impressions</li>
                            <?php endif; ?>
                            <?php if ($campaign_data['metrics']['clicks_goal']): ?>
                                <li><i class="fas fa-mouse-pointer text-primary"></i> <?= number_format($campaign_data['metrics']['clicks_goal']) ?> clicks</li>
                            <?php endif; ?>
                            <?php if ($campaign_data['metrics']['conversions_goal']): ?>
                                <li><i class="fas fa-chart-line text-success"></i> <?= number_format($campaign_data['metrics']['conversions_goal']) ?> conversions</li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Campaign Info -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Campaign Information</h6>
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <strong>Created:</strong><br>
                        <?= date('M j, Y g:i A', strtotime($campaign['create_dt'])) ?>
                    </div>
                    <div class="mb-2">
                        <strong>Last Modified:</strong><br>
                        <?= date('M j, Y g:i A', strtotime($campaign['modify_dt'])) ?>
                    </div>
                    <div class="mb-2">
                        <strong>Views:</strong><br>
                        <?= number_format($campaign['views']) ?>
                    </div>
                    <?php if (!empty($campaign_data['created_by'])): ?>
                        <div>
                            <strong>Created By:</strong><br>
                            User #<?= $campaign_data['created_by'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCampaign() {
    if (confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
        $.post('/staff/ajax/marketing-delete.php', {
            campaign_id: <?= $campaign_id ?>
        }, function(response) {
            if (response.success) {
                window.location.href = '/staff/marketing-campaigns.php';
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    }
}

function duplicateCampaign() {
    if (confirm('Create a duplicate of this campaign?')) {
        $.post('/staff/ajax/marketing-duplicate.php', {
            campaign_id: <?= $campaign_id ?>
        }, function(response) {
            if (response.success) {
                window.location.href = '/staff/marketing-edit.php?id=' + response.new_id;
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    }
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>