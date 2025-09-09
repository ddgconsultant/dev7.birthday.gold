<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Campaigns";

// Get user's company context
$company_id = $current_user_data['company_id'] ?? 99;
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

// Get campaigns for active company
$campaigns_sql = "SELECT c.*, 
                 COALESCE(p.platform_name, 'Birthday.Gold Platform') as platform_name,
                 COALESCE(p.icon_class, 'bi bi-cake2-fill') as icon_class
                 FROM mk_campaigns c
                 LEFT JOIN mk_platforms p ON c.platform_id = p.platform_id
                 WHERE c.company_id = :company_id 
                 ORDER BY c.create_dt DESC";
$campaigns = $database->getrows($campaigns_sql, ['company_id' => $active_company_id]);

// Calculate recipient counts for newsletter campaigns
foreach ($campaigns as &$campaign) {
    if ($campaign['campaign_type'] === 'newsletter') {
        // Parse recipient criteria to get count
        $recipient_count = 0;
        
        if (!empty($campaign['recipient_criteria'])) {
            $criteria = json_decode($campaign['recipient_criteria'], true);
            
            // If it's "all recipients"
            if (isset($criteria['type']) && $criteria['type'] === 'all') {
                // Count all active users
                $count_sql = "SELECT COUNT(*) as count FROM bg_users WHERE status = 'active'";
                $result = $database->getrow($count_sql);
                $recipient_count = $result['count'] ?? 0;
            } else if (!empty($criteria)) {
                // For now, show estimated count for complex criteria
                // This would need the full token parsing logic from marketing class
                $recipient_count = '~100'; // Placeholder
            }
        }
        
        $campaign['recipient_count'] = $recipient_count;
    } else {
        // For non-newsletter campaigns, we might track different metrics
        $campaign['recipient_count'] = null;
    }
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}

/* Card Styling - matching createaccount.php */
.card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    background: white;
}

.card-header {
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    padding: 1rem 1.5rem;
    border-radius: 12px 12px 0 0 !important;
}

.card-body {
    padding: 1.75rem;
}

@media (min-width: 768px) {
    .card-body {
        padding: 2rem;
    }
}

/* Campaign Card Specific */
.campaign-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #e9ecef;
    border-radius: 12px;
}

.campaign-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    border-color: #dee2e6;
}

.campaign-card .card-body {
    padding: 1.5rem;
}

.campaign-card .card-footer {
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    padding: 0.75rem;
    border-radius: 0 0 12px 12px;
}

/* Status Badge */
.status-badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Buttons - matching createaccount.php */
.btn {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #0d6efd;
    border: none;
}

.btn-primary:hover {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
}

.btn-outline-secondary {
    border: 2px solid #e9ecef;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background: #f8f9fa;
    border-color: #6c757d;
    color: #495057;
}

.btn-outline-primary,
.btn-outline-success,
.btn-outline-info {
    border-width: 2px;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

/* Button Group in Cards */
.btn-group .btn {
    border-radius: 0;
    padding: 0.5rem 0.75rem;
}

.btn-group .btn:first-child {
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}

.btn-group .btn:last-child {
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}

/* Empty State */
.text-center.py-5 {
    padding: 3rem 1.5rem !important;
}

.display-4 {
    font-size: 3rem;
    opacity: 0.2;
}


.badge {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
}

/* Icon styling */
.campaign-card i {
    font-size: 1.1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-header {
        padding: 1rem;
    }
    
    .btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
    }
    
    .campaign-card .card-body {
        padding: 1rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-megaphone me-3"></i>Marketing Campaigns</h1>
        <p class="lead">Manage and track your marketing campaigns across all platforms</p>';

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
                    <h5 class="mb-0">All Campaigns</h5>
                    <div>
                        <a href="/myaccount/marketing/campaign-create.php" class="btn btn-primary me-2">
                            <i class="bi bi-plus me-2"></i>Create Campaign
                        </a>
                        <a href="/myaccount/marketing/platforms.php" class="btn btn-outline-secondary">
                            <i class="bi bi-link me-2"></i>Manage Platforms
                        </a>
                    </div>
                </div>
                <div class="card-body">';

if (empty($campaigns)) {
    echo '
                    <div class="text-center py-5">
                        <i class="bi bi-megaphone display-4 text-muted"></i>
                        <h5 class="mt-3 text-muted">No campaigns yet</h5>
                        <p class="text-muted">Create your first marketing campaign to get started</p>
                        <a href="/myaccount/marketing/campaign-create.php" class="btn btn-primary">
                            <i class="bi bi-plus me-2"></i>Create Your First Campaign
                        </a>
                    </div>';
} else {
    echo '
                    <div class="row">';
    
    foreach ($campaigns as $campaign) {
        $status_colors = [
            'draft' => 'secondary',
            'active' => 'success', 
            'paused' => 'warning',
            'completed' => 'info',
            'cancelled' => 'danger',
            'archived' => 'dark'
        ];
        $status_color = $status_colors[$campaign['status']] ?? 'secondary';
        
        echo '
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card campaign-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="' . htmlspecialchars($campaign['icon_class']) . ' me-2"></i>
                                            <small class="text-muted">' . htmlspecialchars($campaign['platform_name']) . '</small>
                                        </div>
                                        <span class="badge bg-' . $status_color . ' status-badge">' . 
                                        htmlspecialchars(ucfirst($campaign['status'] ?? 'unknown')) . '</span>
                                    </div>
                                    
                                    <h6 class="card-title">' . htmlspecialchars($campaign['campaign_name'] ?? '') . '</h6>
                                    <p class="card-text text-muted small">' . htmlspecialchars($campaign['description'] ?? '') . '</p>
                                    
                                    <div class="row text-center small mb-3">
                                        <div class="col-4">
                                            <strong>' . htmlspecialchars($campaign['campaign_type'] ?? 'Unknown') . '</strong><br>
                                            <small class="text-muted">Type</small>
                                        </div>
                                        <div class="col-4">';
        
        // Show recipient count for newsletters, reach for other campaigns
        if ($campaign['campaign_type'] === 'newsletter' && isset($campaign['recipient_count'])) {
            echo '
                                            <strong>' . (is_numeric($campaign['recipient_count']) ? number_format($campaign['recipient_count']) : $campaign['recipient_count']) . '</strong><br>
                                            <small class="text-muted">Recipients</small>';
        } else {
            // For non-newsletter campaigns, show reach or engagement metrics
            echo '
                                            <strong>-</strong><br>
                                            <small class="text-muted">Reach</small>';
        }
        
        echo '
                                        </div>
                                        <div class="col-4">
                                            <strong>' . ($campaign['start_date'] ? date('M j', strtotime($campaign['start_date'])) : 'TBD') . '</strong><br>
                                            <small class="text-muted">' . ($campaign['campaign_type'] === 'newsletter' ? 'Send Date' : 'Start') . '</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100">';
        
        // Check if this is a newsletter campaign
        if ($campaign['campaign_type'] === 'newsletter') {
            // For newsletters, link directly to newsletter editor with mk_campaigns campaign_id
            echo '
                                        <a href="/myaccount/marketing/newsletter-edit.php?id=' . $qik->encodeId($campaign['campaign_id']) . '" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-envelope-paper"></i> Edit Newsletter
                                        </a>';
        } else {
            echo '
                                        <a href="/myaccount/marketing/campaign-edit.php?id=' . $qik->encodeId($campaign['campaign_id']) . '" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>';
        }
        
        echo '
                                        <a href="/myaccount/marketing/campaign-analytics.php?id=' . $qik->encodeId($campaign['campaign_id']) . '" class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-bar-chart"></i> Analytics
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>';
    }
    
    echo '
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