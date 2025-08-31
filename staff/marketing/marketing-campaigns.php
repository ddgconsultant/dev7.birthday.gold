<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Campaigns";

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
}
</style>
';

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_platform = $_GET['platform'] ?? '';
$search = $_GET['search'] ?? '';

// Build query for campaigns
$campaigns_sql = "SELECT * FROM bg_content 
                 WHERE category = 'marketing' 
                 AND type = 'campaign'";

$params = [];

if ($filter_status != 'all') {
    if ($filter_status == 'active') {
        $campaigns_sql .= " AND status = 'active' AND (expire_dt IS NULL OR expire_dt > NOW())";
    } elseif ($filter_status == 'scheduled') {
        $campaigns_sql .= " AND status = 'active' AND publish_dt > NOW()";
    } elseif ($filter_status == 'expired') {
        $campaigns_sql .= " AND (status = 'inactive' OR expire_dt < NOW())";
    }
}

if ($search) {
    $campaigns_sql .= " AND (display_name LIKE :search OR description LIKE :search OR content LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$campaigns_sql .= " ORDER BY create_dt DESC";

$campaigns = $database->getrows($campaigns_sql, $params);

// Parse campaign data from JSON in tags field
foreach ($campaigns as &$campaign) {
    $campaign['data'] = json_decode($campaign['tags'], true) ?: [];
    
    // Calculate campaign status
    $now = time();
    $start = strtotime($campaign['publish_dt']);
    $end = strtotime($campaign['expire_dt']);
    
    if ($campaign['status'] == 'inactive') {
        $campaign['campaign_status'] = 'draft';
        $campaign['status_color'] = 'secondary';
    } elseif ($now < $start) {
        $campaign['campaign_status'] = 'scheduled';
        $campaign['status_color'] = 'info';
    } elseif ($end && $now > $end) {
        $campaign['campaign_status'] = 'expired';
        $campaign['status_color'] = 'danger';
    } else {
        $campaign['campaign_status'] = 'active';
        $campaign['status_color'] = 'success';
    }
}

// Get marketing platform links
$platforms_sql = "SELECT * FROM bg_content 
                 WHERE category = 'marketing' 
                 AND type = 'platform_link' 
                 AND status = 'active'
                 ORDER BY COALESCE(`rank`, 50) ASC, display_name ASC";
$platforms = $database->getrows($platforms_sql);

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="fas fa-bullhorn"></i> Marketing Campaign Manager</h1>
        <p class="lead">Create and manage marketing campaigns across all platforms</p>
    </div>
</div>

<?php include('../includes/marketing-nav.php'); ?>

<div class="container mt-4 mb-5 pb-5">
    <div class="row">
        <!-- Left Column - Campaign List -->
        <div class="col-lg-9">
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select" name="status" onchange="this.form.submit()">
                                <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All Campaigns</option>
                                <option value="active" <?= $filter_status == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="scheduled" <?= $filter_status == 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="expired" <?= $filter_status == 'expired' ? 'selected' : '' ?>>Expired</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" placeholder="Search campaigns..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="/staff/marketing-edit.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> New Campaign
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Campaign List -->
            <div class="row">
                <?php if (empty($campaigns)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No campaigns found. 
                            <a href="/staff/marketing-edit.php">Create your first campaign</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($campaigns as $campaign): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <?php if (!empty($campaign['data']['primary_image'])): ?>
                                    <img src="<?= htmlspecialchars($campaign['data']['primary_image']) ?>" 
                                         class="card-img-top" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-gradient" style="height: 200px; 
                                         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                         display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-bullhorn text-white" style="font-size: 4rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0">
                                            <?= htmlspecialchars($campaign['display_name']) ?>
                                        </h5>
                                        <span class="badge bg-<?= $campaign['status_color'] ?>">
                                            <?= ucfirst($campaign['campaign_status']) ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text small text-muted">
                                        <?= htmlspecialchars(substr($campaign['description'], 0, 100)) ?>
                                        <?= strlen($campaign['description']) > 100 ? '...' : '' ?>
                                    </p>
                                    
                                    <div class="small mb-3">
                                        <?php if (!empty($campaign['data']['platforms'])): ?>
                                            <div class="mb-2">
                                                <?php foreach ($campaign['data']['platforms'] as $platform): ?>
                                                    <span class="badge bg-light text-dark me-1">
                                                        <?= htmlspecialchars($platform) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-muted">
                                            <i class="fas fa-calendar"></i> 
                                            <?= date('M j, Y', strtotime($campaign['publish_dt'])) ?>
                                            <?php if ($campaign['expire_dt']): ?>
                                                - <?= date('M j, Y', strtotime($campaign['expire_dt'])) ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($campaign['data']['budget'])): ?>
                                            <div class="text-muted">
                                                <i class="fas fa-dollar-sign"></i> 
                                                Budget: $<?= number_format($campaign['data']['budget'], 0) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="btn-group w-100" role="group">
                                        <a href="/staff/marketing-view.php?id=<?= $campaign['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="/staff/marketing-edit.php?id=<?= $campaign['id'] ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteCampaign(<?= $campaign['id'] ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Platform Links -->
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0 text-white"><i class="fas fa-link"></i> Marketing Platforms</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($platforms)): ?>
                        <p class="text-muted small">No platform links configured.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($platforms as $platform): ?>
                                <?php $platform_data = json_decode($platform['tags'], true) ?: []; ?>
                                <a href="<?= htmlspecialchars($platform_data['url'] ?? '#') ?>" 
                                   target="_blank" 
                                   class="list-group-item list-group-item-action d-flex align-items-center">
                                    <?php if (!empty($platform_data['icon'])): ?>
                                        <i class="<?= htmlspecialchars($platform_data['icon']) ?> me-2"></i>
                                    <?php else: ?>
                                        <i class="fas fa-external-link-alt me-2"></i>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small"><?= htmlspecialchars($platform['display_name']) ?></div>
                                        <?php if ($platform['description']): ?>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <?= htmlspecialchars($platform['description']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="/staff/marketing-platforms.php" class="btn btn-sm btn-outline-primary w-100">
                            <i class="fas fa-cog"></i> Manage Platforms
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Campaign Stats</h6>
                </div>
                <div class="card-body">
                    <?php
                    $active_count = count(array_filter($campaigns, fn($c) => $c['campaign_status'] == 'active'));
                    $scheduled_count = count(array_filter($campaigns, fn($c) => $c['campaign_status'] == 'scheduled'));
                    $total_budget = array_sum(array_column(array_column($campaigns, 'data'), 'budget'));
                    ?>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Active Campaigns:</span>
                            <strong><?= $active_count ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Scheduled:</span>
                            <strong><?= $scheduled_count ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Budget:</span>
                            <strong>$<?= number_format($total_budget, 0) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCampaign(id) {
    if (confirm('Are you sure you want to delete this campaign?')) {
        $.post('/staff/ajax/marketing-delete.php', {
            campaign_id: id
        }, function(response) {
            if (response.success) {
                location.reload();
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