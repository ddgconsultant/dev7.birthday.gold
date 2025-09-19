<?PHP
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

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-megaphone-fill"></i> Marketing Campaign Manager</h1>
        <p class="lead">Create and manage marketing campaigns across all platforms</p>
    </div>
</div>';

include('../includes/marketing-nav.php');

echo '
<div class="container mt-4 mb-5 pb-5">
    <div class="row">
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select" name="status" onchange="this.form.submit()">
                                <option value="all"' . ($filter_status == 'all' ? ' selected' : '') . '>All Campaigns</option>
                                <option value="active"' . ($filter_status == 'active' ? ' selected' : '') . '>Active</option>
                                <option value="scheduled"' . ($filter_status == 'scheduled' ? ' selected' : '') . '>Scheduled</option>
                                <option value="expired"' . ($filter_status == 'expired' ? ' selected' : '') . '>Expired</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" placeholder="Search campaigns..." 
                                   value="' . htmlspecialchars($search) . '">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <a href="/staff/marketing-edit.php" class="btn btn-success">
                                <i class="bi bi-plus-lg"></i> New Campaign
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">';

if (empty($campaigns)) {
    echo '
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill"></i> No campaigns found. 
                        <a href="/staff/marketing-edit.php">Create your first campaign</a>
                    </div>
                </div>';
} else {
    foreach ($campaigns as $campaign) {
        echo '
                <div class="col-md-6 mb-4">
                    <div class="card h-100">';
        
        if (!empty($campaign['data']['primary_image'])) {
            echo '
                        <img src="' . htmlspecialchars($campaign['data']['primary_image']) . '" 
                             class="card-img-top" style="height: 200px; object-fit: cover;">';
        } else {
            echo '
                        <div class="card-img-top bg-gradient" style="height: 200px; 
                             background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                             display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-megaphone-fill text-white" style="font-size: 4rem;"></i>
                        </div>';
        }
        
        echo '
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">
                                    ' . htmlspecialchars($campaign['display_name']) . '
                                </h5>
                                <span class="badge bg-' . $campaign['status_color'] . '">
                                    ' . ucfirst($campaign['campaign_status']) . '
                                </span>
                            </div>
                            
                            <p class="card-text small text-muted">
                                ' . htmlspecialchars(substr($campaign['description'], 0, 100)) . 
                                (strlen($campaign['description']) > 100 ? '...' : '') . '
                            </p>
                            
                            <div class="small mb-3">';
        
        if (!empty($campaign['data']['platforms'])) {
            echo '
                                <div class="mb-2">';
            foreach ($campaign['data']['platforms'] as $platform) {
                echo '
                                    <span class="badge bg-light text-dark me-1">
                                        ' . htmlspecialchars($platform) . '
                                    </span>';
            }
            echo '
                                </div>';
        }
        
        echo '
                                <div class="text-muted">
                                    <i class="bi bi-calendar-fill"></i> 
                                    ' . date('M j, Y', strtotime($campaign['publish_dt']));
        
        if ($campaign['expire_dt']) {
            echo ' - ' . date('M j, Y', strtotime($campaign['expire_dt']));
        }
        
        echo '
                                </div>';
        
        if (!empty($campaign['data']['budget'])) {
            echo '
                                <div class="text-muted">
                                    <i class="bi bi-currency-dollar"></i> 
                                    Budget: $' . number_format($campaign['data']['budget'], 0) . '
                                </div>';
        }
        
        echo '
                            </div>
                            
                            <div class="btn-group w-100" role="group">
                                <a href="/staff/marketing-view.php?id=' . $campaign['id'] . '" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye-fill"></i> View
                                </a>
                                <a href="/staff/marketing-edit.php?id=' . $campaign['id'] . '" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil-fill"></i> Edit
                                </a>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteCampaign(' . $campaign['id'] . ')">
                                    <i class="bi bi-trash-fill"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>';
    }
}

echo '
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0 text-white"><i class="bi bi-link-45deg"></i> Marketing Platforms</h6>
                </div>
                <div class="card-body">';

if (empty($platforms)) {
    echo '
                    <p class="text-muted small">No platform links configured.</p>';
} else {
    echo '
                    <div class="list-group list-group-flush">';
    
    foreach ($platforms as $platform) {
        $platform_data = json_decode($platform['tags'], true) ?: [];
        echo '
                        <a href="' . htmlspecialchars($platform_data['url'] ?? '#') . '" 
                           target="_blank" 
                           class="list-group-item list-group-item-action d-flex align-items-center">';
        
        if (!empty($platform_data['icon'])) {
            echo '
                            <i class="' . htmlspecialchars($platform_data['icon']) . ' me-2"></i>';
        } else {
            echo '
                            <i class="bi bi-box-arrow-up-right me-2"></i>';
        }
        
        echo '
                            <div class="flex-grow-1">
                                <div class="fw-bold small">' . htmlspecialchars($platform['display_name']) . '</div>';
        
        if ($platform['description']) {
            echo '
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    ' . htmlspecialchars($platform['description']) . '
                                </div>';
        }
        
        echo '
                            </div>
                        </a>';
    }
    
    echo '
                    </div>';
}

echo '
                    <div class="mt-3">
                        <a href="/staff/marketing-platforms.php" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-gear-fill"></i> Manage Platforms
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-pie-chart-fill"></i> Campaign Stats</h6>
                </div>
                <div class="card-body">';

$active_count = count(array_filter($campaigns, fn($c) => $c['campaign_status'] == 'active'));
$scheduled_count = count(array_filter($campaigns, fn($c) => $c['campaign_status'] == 'scheduled'));
$total_budget = array_sum(array_column(array_column($campaigns, 'data'), 'budget'));

echo '
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Active Campaigns:</span>
                            <strong>' . $active_count . '</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Scheduled:</span>
                            <strong>' . $scheduled_count . '</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Budget:</span>
                            <strong>$' . number_format($total_budget, 0) . '</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCampaign(id) {
    if (confirm("Are you sure you want to delete this campaign?")) {
        $.post("/staff/ajax/marketing-delete.php", {
            campaign_id: id
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert("Error: " + response.message);
            }
        }, "json");
    }
}
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>