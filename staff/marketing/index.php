<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Hub";

$additionalstyles = '
<style>
:root {
    --admin-grid-gap: 1.5rem;
    --admin-grid-mb: 3rem;
    --admin-card-padding: 1.5rem;
    --admin-card-gap: 1rem;
    --admin-icon-size: 48px;
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: var(--admin-grid-gap);
    margin-bottom: var(--admin-grid-mb);
}

.admin-card {
    background: white;
    border-radius: 12px;
    padding: var(--admin-card-padding);
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: var(--admin-card-gap);
}

.admin-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
    text-decoration: none !important;
}

.admin-card:hover .admin-card-title,
.admin-card:hover .admin-card-text {
    text-decoration: none !important;
}

.admin-icon {
    flex-shrink: 0;
    width: var(--admin-icon-size);
    height: var(--admin-icon-size);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.admin-content {
    flex-grow: 1;
}

.admin-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.admin-card-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
    line-height: 1.5;
}

.section-header {
    text-align: center;
    margin-top: 3rem;
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #212529;
}

.section-subtitle {
    color: #6c757d;
    font-size: 1rem;
    margin: 0;
}

.icon-newsletter {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

.icon-marketing {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
}

.icon-analytics {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
}

.icon-tools {
    background: linear-gradient(135deg, #858796 0%, #60616f 100%);
}

@media (max-width: 768px) {
    :root {
        --admin-grid-gap: 1rem;
        --admin-card-padding: 1rem;
        --admin-icon-size: 40px;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff">
    <div class="container text-center">
        <h1><i class="bi bi-megaphone"></i> Marketing Hub</h1>
        <p class="lead">Newsletters, campaigns, analytics, and marketing tools</p>
    </div>
</div>

<div class="container mb-5">
    
    <div class="section-header">
        <h2 class="section-title">Newsletter Campaigns</h2>
        <p class="section-subtitle">Email marketing with Birthday Gold member rewards and CTA promotions</p>
    </div>
    
    <div class="admin-grid">
        <a href="newsletter-edit.php" class="admin-card">
            <div class="admin-icon icon-newsletter">
                <i class="bi bi-plus-lg"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Create Newsletter</h3>
                <p class="admin-card-text">Design personalized email campaigns with member rewards and business CTAs</p>
            </div>
        </a>
        
        <a href="newsletter-report.php" class="admin-card">
            <div class="admin-icon icon-newsletter">
                <i class="bi bi-list-ul"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Newsletter Reports</h3>
                <p class="admin-card-text">Newsletter management and delivery status tracking</p>
            </div>
        </a>
        
        <a href="newsletter-reports.php" class="admin-card">
            <div class="admin-icon icon-newsletter">
                <i class="bi bi-graph-up"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Newsletter Reports</h3>
                <p class="admin-card-text">View delivery rates, open rates, and engagement analytics</p>
            </div>
        </a>
        
        <a href="newsletter-unsubscribe.php" class="admin-card">
            <div class="admin-icon icon-newsletter">
                <i class="bi bi-person-x"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Unsubscribe Management</h3>
                <p class="admin-card-text">Handle email unsubscriptions and preference management</p>
            </div>
        </a>
    </div>

    <div class="section-header">
        <h2 class="section-title">Marketing Campaigns</h2>
        <p class="section-subtitle">Multi-platform campaigns with budget tracking and performance analytics</p>
    </div>
    
    <div class="admin-grid">
        <a href="marketing-edit.php" class="admin-card">
            <div class="admin-icon icon-marketing">
                <i class="bi bi-plus-lg"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Create Campaign</h3>
                <p class="admin-card-text">Launch cross-platform marketing campaigns with budget and goal tracking</p>
            </div>
        </a>
        
        <a href="marketing-campaigns.php" class="admin-card">
            <div class="admin-icon icon-marketing">
                <i class="bi bi-list-ul"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">All Campaigns</h3>
                <p class="admin-card-text">View and manage all marketing campaigns across platforms</p>
            </div>
        </a>
        
        <a href="marketing-calendar.php" class="admin-card">
            <div class="admin-icon icon-marketing">
                <i class="bi bi-calendar3"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Marketing Calendar</h3>
                <p class="admin-card-text">Schedule and coordinate marketing activities</p>
            </div>
        </a>
        
        <a href="marketing-platforms.php" class="admin-card">
            <div class="admin-icon icon-marketing">
                <i class="bi bi-link-45deg"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Platform Links</h3>
                <p class="admin-card-text">Quick access to external marketing platforms and tools</p>
            </div>
        </a>
    </div>

    <div class="section-header">
        <h2 class="section-title">Analytics & Reports</h2>
        <p class="section-subtitle">Performance tracking and insights across all marketing channels</p>
    </div>
    
    <div class="admin-grid">
        <a href="marketing-analytics.php" class="admin-card">
            <div class="admin-icon icon-analytics">
                <i class="bi bi-graph-up"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Marketing Analytics</h3>
                <p class="admin-card-text">Campaign performance, budget tracking, and ROI analysis</p>
            </div>
        </a>
        
        <a href="newsletter-track.php" class="admin-card">
            <div class="admin-icon icon-analytics">
                <i class="bi bi-cursor"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Email Tracking</h3>
                <p class="admin-card-text">Real-time email delivery and engagement tracking</p>
            </div>
        </a>
    </div>

    <div class="section-header">
        <h2 class="section-title">Advanced Tools</h2>
        <p class="section-subtitle">Detailed management and configuration tools</p>
    </div>
    
    <div class="admin-grid">
        <a href="marketing-hub.php" class="admin-card">
            <div class="admin-icon icon-tools">
                <i class="bi bi-house-door"></i>
            </div>
            <div class="admin-content">
                <h3 class="admin-card-title">Marketing Hub</h3>
                <p class="admin-card-text">Comprehensive dashboard with detailed statistics and management</p>
            </div>
        </a>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>