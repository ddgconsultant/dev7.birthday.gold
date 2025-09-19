<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Reports";

// Get user's company context
$company_id = $current_user_data['company_id'] ?? 99;
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

// Get analytics data for active company
$platforms_count = $database->getrow(
    "SELECT COUNT(*) as count FROM mk_platforms WHERE company_id = :company_id AND status = 'active'",
    ['company_id' => $active_company_id]
);

$campaigns_stats = $database->getrow(
    "SELECT 
        COUNT(*) as total_campaigns,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_campaigns,
        SUM(budget_amount) as total_budget,
        AVG(budget_amount) as avg_budget
     FROM mk_campaigns 
     WHERE company_id = :company_id",
    ['company_id' => $active_company_id]
);

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}
.stats-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    text-align: center;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: transform 0.2s ease;
}
.stats-card:hover {
    transform: translateY(-2px);
}
.stats-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #0d6efd;
    margin: 0;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-bar-chart me-3"></i>Marketing Reports</h1>
        <p class="lead">Track performance and ROI across your marketing efforts</p>';

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
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number" data-target="' . ($platforms_count['count'] ?? 0) . '">0</div>
                <p class="mb-0 text-muted">Active Platforms</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number" data-target="' . ($campaigns_stats['total_campaigns'] ?? 0) . '">0</div>
                <p class="mb-0 text-muted">Total Campaigns</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number" data-target="' . ($campaigns_stats['active_campaigns'] ?? 0) . '">0</div>
                <p class="mb-0 text-muted">Active Campaigns</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number" data-target="' . ($campaigns_stats['total_budget'] ?? 0) . '" data-prefix="$">0</div>
                <p class="mb-0 text-muted">Total Budget</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Performance Reports</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="bi bi-graph-up display-4 text-muted"></i>
                        <h5 class="mt-3 text-muted">Advanced Reports Coming Soon</h5>
                        <p class="text-muted">Detailed analytics, ROI tracking, and performance insights will be available here</p>
                        <div class="btn-group">
                            <a href="/myaccount/marketing/campaigns.php" class="btn btn-outline-primary">
                                <i class="bi bi-megaphone me-2"></i>View Campaigns
                            </a>
                            <a href="/myaccount/marketing/calendar.php" class="btn btn-outline-info">
                                <i class="bi bi-calendar me-2"></i>Marketing Calendar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>

<script>
// Animate numbers counting up
function animateNumbers() {
    const numbers = document.querySelectorAll('.stats-number');
    
    numbers.forEach(number => {
        const target = parseInt(number.getAttribute('data-target'));
        const prefix = number.getAttribute('data-prefix') || '';
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (prefix === '$') {
                number.textContent = '$' + Math.floor(current).toLocaleString();
            } else {
                number.textContent = Math.floor(current).toLocaleString();
            }
        }, 16);
    });
}

// Start animation when page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(animateNumbers, 500); // Small delay for better effect
});
</script>