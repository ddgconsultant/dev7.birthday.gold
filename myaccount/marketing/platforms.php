<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Platforms";

// Get user's company context with staff override capability
$company_id = $current_user_data['company_id'] ?? 0;
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

// Get platforms for active company using new schema
$platforms_sql = "SELECT * FROM mk_platforms 
                  WHERE company_id = :company_id AND status != 'archived'
                  ORDER BY display_order ASC, platform_name ASC";
$platforms = $database->getrows($platforms_sql, ['company_id' => $active_company_id]);

// Get AccessManager credentials for platforms
foreach ($platforms as &$platform) {
    if ($platform['credential_id']) {
        $cred_sql = "SELECT id, name, host, type FROM am_datastore WHERE id = :id";
        $credential = $database->getrow($cred_sql, ['id' => $platform['credential_id']]);
        $platform['has_credentials'] = !empty($credential);
    } else {
        $platform['has_credentials'] = false;
    }
    
    // Get campaign count
    $campaign_count = $database->getrow(
        "SELECT COUNT(*) as count FROM mk_campaigns WHERE platform_id = :platform_id",
        ['platform_id' => $platform['platform_id']]
    );
    $platform['campaign_count'] = $campaign_count['count'] ?? 0;
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}
.table td, .table th {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}
.table tbody tr:hover {
    background-color: #e3f2fd !important;
}
.table tbody tr {
    transition: background-color 0.2s ease;
}
.table tbody tr:hover a {
    text-decoration: underline;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-link me-3"></i>Marketing Platforms</h1>
        <p class="lead">Manage your marketing platform connections and credentials</p>';

// Show company context
if ($active_company_id == 0) {
    echo '
        <div class="badge bg-primary fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Birthday Gold (Internal Marketing)
        </div>';
} else {
    echo '
        <div class="badge bg-info fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Company ID: ' . $active_company_id . '
        </div>';
}

echo '
    </div>
</div>';

// Include marketing tab navigation
include('nav.inc.php');

echo '
<div class="container mb-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Platform Links</h5>
                    <a href="/myaccount/marketing/platform-create.php" class="btn btn-primary">
                        <i class="bi bi-plus me-2"></i>Create Platform
                    </a>
                </div>
                <div class="card-body">';

if (empty($platforms)) {
    echo '
                    <div class="text-center py-4">
                        <i class="bi bi-link display-4 text-muted"></i>
                        <h5 class="mt-3 text-muted">No platforms configured</h5>
                        <p class="text-muted">Add your first marketing platform to get started</p>
                        <a href="/myaccount/marketing/platform-create.php" class="btn btn-primary">
                            <i class="bi bi-plus me-2"></i>Create Platform
                        </a>
                    </div>';
} else {
    echo '
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="40">Icon</th>
                                    <th>Platform <small class="text-muted">(click to manage)</small></th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th width="100">Credentials</th>
                                    <th width="100">Campaigns</th>
                                    <th width="80">Status</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    foreach ($platforms as $platform) {
        echo '
                                <tr>
                                    <td class="text-center">
                                        <i class="' . htmlspecialchars($platform['icon_class']) . ' fa-lg"></i>
                                    </td>
                                    <td>
                                        <a href="/myaccount/marketing/platform-manage.php?platform_id=' . $platform['platform_id'] . '" class="text-decoration-none">
                                            <strong>' . htmlspecialchars($platform['platform_name']) . '</strong>
                                        </a><br>
                                        <small class="text-muted">
                                            <a href="' . htmlspecialchars($platform['platform_url']) . '" target="_blank" class="text-muted">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                                ' . htmlspecialchars(substr($platform['platform_url'], 0, 40)) . 
                                                (strlen($platform['platform_url']) > 40 ? '...' : '') . '
                                            </a>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">' . htmlspecialchars($platform['platform_type']) . '</span>
                                    </td>
                                    <td>
                                        <small>' . htmlspecialchars($platform['description']) . '</small>
                                    </td>
                                    <td class="text-center">';
        
        if ($platform['has_credentials']) {
            echo '<span class="badge bg-success"><i class="bi bi-key"></i> Valid</span>';
        } else {
            echo '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Missing</span>';
        }
        
        echo '
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">' . $platform['campaign_count'] . ' campaigns</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-' . ($platform['status'] == 'active' ? 'success' : 'secondary') . '">' . 
                                        htmlspecialchars($platform['status']) . '</span>
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
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>