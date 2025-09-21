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
        // First check if we have a stored count in campaign_config
        $recipient_count = 0;
        
        if (!empty($campaign['campaign_config'])) {
            $config = json_decode($campaign['campaign_config'], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($config['recipient_count'])) {
                $recipient_count = $config['recipient_count'];
            }
        }
        
        // If no stored count, try to calculate from criteria (fallback)
        if ($recipient_count == 0 && !empty($campaign['recipient_criteria'])) {
            $criteria = json_decode($campaign['recipient_criteria'], true);
            
            // If it's "all recipients"
            if (isset($criteria['type']) && $criteria['type'] === 'all') {
                // Count all active users
                $count_sql = "SELECT COUNT(*) as count FROM bg_users WHERE status = 'active'";
                $result = $database->getrow($count_sql);
                $recipient_count = $result['count'] ?? 0;
            } else if (!empty($criteria)) {
                // For complex criteria without stored count, show placeholder
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

/* Status Badge with distinct colors */
.status-badge {
    font-size: 0.85rem;
    padding: 0.5rem 0.875rem;
    border-radius: 8px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.75px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Custom status badge colors for better differentiation */
.status-badge.bg-draft {
    background-color: #6c757d !important;
    color: white !important;
}

.status-badge.bg-active {
    background-color: #28a745 !important;
    color: white !important;
    animation: pulse-green 2s infinite;
}

.status-badge.bg-scheduled {
    background-color: #17a2b8 !important;
    color: white !important;
}

.status-badge.bg-sending {
    background-color: #fd7e14 !important;
    color: white !important;
    animation: pulse-orange 1.5s infinite;
}

.status-badge.bg-paused {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.status-badge.bg-completed {
    background-color: #6610f2 !important;
    color: white !important;
}

.status-badge.bg-sent {
    background-color: #28a745 !important;
    color: white !important;
}

.status-badge.bg-cancelled {
    background-color: #dc3545 !important;
    color: white !important;
}

.status-badge.bg-archived {
    background-color: #343a40 !important;
    color: white !important;
}

/* Pulse animations for active statuses */
@keyframes pulse-green {
    0% { box-shadow: 0 2px 4px rgba(40, 167, 69, 0.4); }
    50% { box-shadow: 0 2px 8px rgba(40, 167, 69, 0.6); }
    100% { box-shadow: 0 2px 4px rgba(40, 167, 69, 0.4); }
}

@keyframes pulse-orange {
    0% { box-shadow: 0 2px 4px rgba(253, 126, 20, 0.4); }
    50% { box-shadow: 0 2px 8px rgba(253, 126, 20, 0.6); }
    100% { box-shadow: 0 2px 4px rgba(253, 126, 20, 0.4); }
}

/* Campaign title styling - make it stand out */
.campaign-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Card background colors based on status */
.campaign-card.status-draft {
    background-color: #f8f9fa !important;
    border-color: #6c757d !important;
}

.campaign-card.status-active {
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%) !important;
    border-color: #28a745 !important;
}

.campaign-card.status-scheduled {
    background: linear-gradient(135deg, #e7f6fc 0%, #ffffff 100%) !important;
    border-color: #17a2b8 !important;
}

.campaign-card.status-sending {
    background: linear-gradient(135deg, #fff4e6 0%, #ffffff 100%) !important;
    border-color: #fd7e14 !important;
}

.campaign-card.status-paused {
    background: linear-gradient(135deg, #fffbf0 0%, #ffffff 100%) !important;
    border-color: #ffc107 !important;
}

.campaign-card.status-completed {
    background: linear-gradient(135deg, #f3f0ff 0%, #ffffff 100%) !important;
    border-color: #6610f2 !important;
}

.campaign-card.status-sent {
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%) !important;
    border-color: #28a745 !important;
}

.campaign-card.status-cancelled {
    background: linear-gradient(135deg, #fef1f2 0%, #ffffff 100%) !important;
    border-color: #dc3545 !important;
}

.campaign-card.status-archived {
    background: linear-gradient(135deg, #f1f3f5 0%, #ffffff 100%) !important;
    border-color: #343a40 !important;
    opacity: 0.9;
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

/* Table view styles */
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom-width: 2px;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Status badge works in both card and table views */
.table .status-badge {
    display: inline-block;
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

    /* Hide less important columns on mobile */
    .table .d-none-mobile {
        display: none;
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
                        <a href="/myaccount/marketing/campaign-create" class="btn btn-primary me-2">
                            <i class="bi bi-plus me-2"></i>Create Campaign
                        </a>
                        <a href="/myaccount/marketing/platforms" class="btn btn-outline-secondary">
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
                        <a href="/myaccount/marketing/campaign-create" class="btn btn-primary">
                            <i class="bi bi-plus me-2"></i>Create Your First Campaign
                        </a>
                    </div>';
} else if (count($campaigns) > 6) {
    // Table view for more than 6 campaigns
    echo '
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Campaign</th>
                                    <th scope="col">Platform</th>
                                    <th scope="col">Type</th>
                                    <th scope="col" class="text-center">Status</th>
                                    <th scope="col" class="text-center">Recipients</th>
                                    <th scope="col">Date</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>';

    foreach ($campaigns as $campaign) {
        // Normalize status for CSS class
        $status_class = strtolower($campaign['status'] ?? 'draft');
        if ($status_class === 'queued') {
            $status_class = 'scheduled';
        } else if ($status_class === 'sent') {
            $status_class = 'sent';
        }

        echo '
                                <tr>
                                    <td>
                                        <div class="fw-bold">' . htmlspecialchars($campaign['campaign_name'] ?? '') . '</div>
                                        <small class="text-muted">' . htmlspecialchars(substr($campaign['description'] ?? '', 0, 50)) .
                                        (strlen($campaign['description'] ?? '') > 50 ? '...' : '') . '</small>
                                    </td>
                                    <td>
                                        <i class="' . htmlspecialchars($campaign['icon_class']) . ' me-1"></i>
                                        <small>' . htmlspecialchars($campaign['platform_name']) . '</small>
                                    </td>
                                    <td>' . htmlspecialchars(ucwords(str_replace('_', ' ', $campaign['campaign_type'] ?? 'Unknown'))) . '</td>
                                    <td class="text-center">
                                        <span class="status-badge bg-' . $status_class . '">' .
                                        htmlspecialchars(ucfirst($campaign['status'] ?? 'unknown')) . '</span>
                                    </td>
                                    <td class="text-center">';

        if ($campaign['campaign_type'] === 'newsletter' && isset($campaign['recipient_count'])) {
            echo is_numeric($campaign['recipient_count']) ? number_format($campaign['recipient_count']) : $campaign['recipient_count'];
        } else {
            echo '-';
        }

        echo '</td>
                                    <td>' . ($campaign['start_date'] ? date('M j, Y', strtotime($campaign['start_date'])) : 'TBD') . '</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">';

        // Action buttons based on campaign type and status
        if ($campaign['campaign_type'] === 'newsletter') {
            $editable_statuses = ['draft', 'cancelled'];

            if (in_array($campaign['status'], $editable_statuses)) {
                echo '
                                            <a href="/myaccount/marketing/newsletter-edit.php?id=' . $qik->encodeId($campaign['campaign_id']) . '"
                                               class="btn btn-outline-success" title="Edit Newsletter">
                                                <i class="bi bi-pencil"></i>
                                            </a>';
            } else if (in_array($campaign['status'], ['scheduled', 'queued', 'active', 'sending'])) {
                echo '
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cancelModal"
                                                    data-campaign-id="' . $qik->encodeId($campaign['campaign_id']) . '"
                                                    data-campaign-name="' . htmlspecialchars($campaign['campaign_name']) . '"
                                                    data-campaign-status="' . htmlspecialchars($campaign['status']) . '"
                                                    title="Cancel">
                                                <i class="bi bi-x-circle"></i>
                                            </button>';
            }
        } else {
            echo '
                                            <a href="/myaccount/marketing/campaign-edit.php?id=' . $qik->encodeId($campaign['campaign_id']) . '"
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>';
        }

        echo '
                                            <a href="/myaccount/marketing/campaign-analytics.php?id=' . $qik->encodeId($campaign['campaign_id']) . '"
                                               class="btn btn-outline-info" title="Analytics">
                                                <i class="bi bi-bar-chart"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>';
    }

    echo '
                            </tbody>
                        </table>
                    </div>';
} else {
    // Card view for 6 or fewer campaigns
    echo '
                    <div class="row">';
    
    foreach ($campaigns as $campaign) {
        // Normalize status for CSS class (handle all possible status values)
        $status_class = strtolower($campaign['status'] ?? 'draft');
        // Map any additional statuses to our defined CSS classes
        if ($status_class === 'queued') {
            $status_class = 'scheduled';
        }
        // Keep 'sent' as its own status for green coloring

        echo '
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card campaign-card status-' . $status_class . ' h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="' . htmlspecialchars($campaign['icon_class']) . ' me-2"></i>
                                            <small class="text-muted">' . htmlspecialchars($campaign['platform_name']) . '</small>
                                        </div>
                                        <span class="status-badge bg-' . $status_class . '">' .
                                        htmlspecialchars(ucfirst($campaign['status'] ?? 'unknown')) . '</span>
                                    </div>

                                    <h5 class="campaign-title">' . htmlspecialchars($campaign['campaign_name'] ?? '') . '</h5>
                                    <p class="card-text text-muted small">' . htmlspecialchars($campaign['description'] ?? '') . '</p>
                                    
                                    <div class="row text-center small mb-3">
                                        <div class="col-4">
                                            <strong>' . htmlspecialchars(ucwords(str_replace('_', ' ', $campaign['campaign_type'] ?? 'Unknown'))) . '</strong><br>
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
            // Check status to determine if it can be edited or needs to be cancelled
            $editable_statuses = ['draft', 'cancelled'];
            
            if (in_array($campaign['status'], $editable_statuses)) {
                // Show Edit button for draft/cancelled newsletters
                echo '
                                        <a href="/myaccount/marketing/newsletter-edit.php?id=' . $qik->encodeId($campaign['campaign_id']) . '" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-envelope-paper"></i> Edit Newsletter
                                        </a>';
            } else if (in_array($campaign['status'], ['scheduled', 'queued', 'active', 'sending'])) {
                // Show Cancel button for scheduled/queued/active newsletters
                echo '
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#cancelModal"
                                                data-campaign-id="' . $qik->encodeId($campaign['campaign_id']) . '"
                                                data-campaign-name="' . htmlspecialchars($campaign['campaign_name']) . '"
                                                data-campaign-status="' . htmlspecialchars($campaign['status']) . '">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>';
            } else {
                // For completed/sent newsletters, show View only
                echo '
                                        <a href="/myaccount/marketing/newsletter-view.php?id=' . $qik->encodeId($campaign['campaign_id']) . '" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-eye"></i> View
                                        </a>';
            }
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

// Cancel Newsletter Modal
echo '
<!-- Cancel Newsletter Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Cancel Newsletter
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
                </div>
                <h6 class="text-center mb-3">Are you sure you want to cancel this newsletter?</h6>
                <div class="alert alert-warning">
                    <strong>Newsletter:</strong> <span id="cancelNewsletterName"></span><br>
                    <strong>Current Status:</strong> <span id="cancelNewsletterStatus" class="text-capitalize"></span>
                </div>
                <p class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    This action will stop the newsletter from being sent. You can edit and reschedule it later if needed.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-1"></i>Keep Scheduled
                </button>
                <a href="#" id="confirmCancelBtn" class="btn btn-danger">
                    <i class="bi bi-x-circle me-1"></i>Yes, Cancel Newsletter
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Handle cancel modal data
document.addEventListener("DOMContentLoaded", function() {
    var cancelModal = document.getElementById("cancelModal");
    if (cancelModal) {
        cancelModal.addEventListener("show.bs.modal", function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;
            
            // Extract info from data-* attributes
            var campaignId = button.getAttribute("data-campaign-id");
            var campaignName = button.getAttribute("data-campaign-name");
            var campaignStatus = button.getAttribute("data-campaign-status");
            
            // Update modal content
            document.getElementById("cancelNewsletterName").textContent = campaignName;
            document.getElementById("cancelNewsletterStatus").textContent = campaignStatus;
            
            // Update confirmation link
            var confirmBtn = document.getElementById("confirmCancelBtn");
            confirmBtn.href = "/myaccount/marketing/newsletter-cancel.php?id=" + campaignId;
        });
    }
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>