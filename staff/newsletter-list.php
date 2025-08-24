<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Newsletter Campaigns";

// Get campaigns from database
$campaigns_sql = "SELECT 
    nc.*,
    u.first_name, 
    u.last_name,
    (SELECT COUNT(*) FROM bg_newsletter_queue WHERE campaign_id = nc.campaign_id) as total_recipients,
    (SELECT COUNT(*) FROM bg_newsletter_queue WHERE campaign_id = nc.campaign_id AND status = 'sent') as sent_count,
    (SELECT COUNT(*) FROM bg_newsletter_events WHERE campaign_id = nc.campaign_id AND event_type = 'open') as open_count,
    (SELECT COUNT(*) FROM bg_newsletter_events WHERE campaign_id = nc.campaign_id AND event_type = 'click') as click_count
FROM bg_newsletter_campaigns nc
LEFT JOIN bg_users u ON nc.created_by = u.user_id
ORDER BY nc.created_dt DESC";

$campaigns = $database->getrows($campaigns_sql);

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="fas fa-envelope"></i> Newsletter System</h1>
        <p class="lead">Manage and track email campaigns</p>
    </div>
</div>';

// Include navigation
include('includes/newsletter-nav.php');

echo '
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col">
            <h3>Campaigns</h3>
        </div>
        <div class="col-auto">
            <a href="/staff/newsletter-edit.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Campaign
            </a>
        </div>
    </div>';

if (empty($campaigns)) {
    echo '
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No campaigns have been created yet.
        <a href="/staff/newsletter-edit.php">Create your first campaign</a>
    </div>';
} else {
    echo '
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Send Date</th>
                    <th>Recipients</th>
                    <th>Opens</th>
                    <th>Clicks</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($campaigns as $campaign) {
        $status_class = 'bg-secondary';
        if ($campaign['status'] == 'scheduled') $status_class = 'bg-warning';
        if ($campaign['status'] == 'sending') $status_class = 'bg-info';
        if ($campaign['status'] == 'sent') $status_class = 'bg-success';
        if ($campaign['status'] == 'cancelled') $status_class = 'bg-danger';
        
        $open_rate = 0;
        $click_rate = 0;
        if ($campaign['sent_count'] > 0) {
            $open_rate = round(($campaign['open_count'] / $campaign['sent_count']) * 100, 1);
            $click_rate = round(($campaign['click_count'] / $campaign['sent_count']) * 100, 1);
        }
        
        echo '
                <tr>
                    <td>' . htmlspecialchars($campaign['title']) . '</td>
                    <td>' . htmlspecialchars($campaign['subject']) . '</td>
                    <td>
                        <span class="badge bg-secondary">
                            ' . htmlspecialchars($campaign['cta_category']) . '
                        </span>
                    </td>
                    <td>
                        <span class="badge ' . $status_class . '">
                            ' . ucfirst($campaign['status']) . '
                        </span>
                    </td>
                    <td>
                        ' . date('M j, Y g:i A', strtotime($campaign['send_dt'])) . '
                    </td>
                    <td class="text-center">
                        ' . number_format($campaign['total_recipients']);
        
        if ($campaign['sent_count'] > 0) {
            echo '<br><small class="text-muted">
                        (' . number_format($campaign['sent_count']) . ' sent)
                    </small>';
        }
        
        echo '
                    </td>
                    <td class="text-center">';
        
        if ($campaign['sent_count'] > 0) {
            echo number_format($campaign['open_count']) . '
                        <br><small class="text-muted">
                            (' . $open_rate . '%)
                        </small>';
        } else {
            echo '-';
        }
        
        echo '
                    </td>
                    <td class="text-center">';
        
        if ($campaign['sent_count'] > 0) {
            echo number_format($campaign['click_count']) . '
                        <br><small class="text-muted">
                            (' . $click_rate . '%)
                        </small>';
        } else {
            echo '-';
        }
        
        echo '
                    </td>
                    <td>
                        ' . htmlspecialchars($campaign['first_name'] . ' ' . $campaign['last_name']) . '
                        <br><small class="text-muted">
                            ' . date('M j, Y', strtotime($campaign['created_dt'])) . '
                        </small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">';
        
        if ($campaign['status'] == 'draft') {
            echo '
                            <a href="/staff/newsletter-edit.php?id=' . $campaign['campaign_id'] . '" 
                               class="btn btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>';
        }
        
        echo '
                            <a href="/staff/newsletter-reports.php?id=' . $campaign['campaign_id'] . '" 
                               class="btn btn-outline-info" title="View Report">
                                <i class="fas fa-chart-bar"></i>
                            </a>';
        
        if ($campaign['status'] == 'draft') {
            echo '
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="deleteCampaign(' . $campaign['campaign_id'] . ')" 
                                    title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>';
        }
        
        if ($campaign['status'] == 'scheduled') {
            echo '
                            <button type="button" class="btn btn-outline-warning" 
                                    onclick="cancelCampaign(' . $campaign['campaign_id'] . ')" 
                                    title="Cancel">
                                <i class="fas fa-times"></i>
                            </button>';
        }
        
        echo '
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

<script>
function deleteCampaign(campaignId) {
    if (confirm("Are you sure you want to delete this campaign? This action cannot be undone.")) {
        $.post("/staff/ajax/newsletter-delete.php", {
            campaign_id: campaignId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert("Error: " + response.message);
            }
        }, "json");
    }
}

function cancelCampaign(campaignId) {
    if (confirm("Are you sure you want to cancel this scheduled campaign?")) {
        $.post("/staff/ajax/newsletter-cancel.php", {
            campaign_id: campaignId
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