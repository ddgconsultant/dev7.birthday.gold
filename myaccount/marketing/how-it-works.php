<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "How Marketing Platform Works";

// Set the marketing visit attribute so user won't be redirected again
$input = [
    'type' => 'marketing_preference',
    'name' => 'first_marketing_visit',
    'status' => 'A',
    'description' => 'completed'
];
$account->setUserAttribute($current_user_data['user_id'], $input);

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}
.step-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    height: 100%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    text-align: center;
}
.step-card:hover {
    transform: translateY(-5px);
}
.step-number {
    display: inline-block;
    width: 50px;
    height: 50px;
    background: #0d6efd;
    color: white;
    border-radius: 50%;
    line-height: 50px;
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
}
.step-icon {
    font-size: 3rem;
    color: #0d6efd;
    margin-bottom: 1rem;
}
.features-section {
    padding: 2rem 0;
    background: #f8f9fa;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-lightbulb me-3"></i>How Marketing Platform Works</h1>
        <p class="lead">Your complete guide to creating campaigns, managing platforms, and tracking performance</p>
        <div class="alert alert-info mt-4">
            <i class="bi bi-gem me-2"></i>
            <strong>Birthday.Gold Marketing Platform</strong> - Create campaigns that drive signups and engagement with our integrated tracking and analytics.
        </div>
    </div>
</div>';

// Include marketing tab navigation
include('nav.inc.php');

echo '
<div class="container mb-5">
    <div class="features-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="step-icon">
                            <i class="bi bi-broadcast"></i>
                        </div>
                        <h4>Choose Your Platform</h4>
                        <p class="text-muted">Start with the Birthday.Gold integrated platform for free marketing, or connect external platforms like Facebook and Google Ads.</p>
                        <ul class="list-unstyled text-start">
                            <li>✓ Birthday.Gold Platform (Free)</li>
                            <li>✓ Facebook Ads Integration</li>
                            <li>✓ Google Ads Integration</li>
                            <li>✓ Instagram Business Tools</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="step-icon">
                            <i class="bi bi-target"></i>
                        </div>
                        <h4>Create Targeted Campaigns</h4>
                        <p class="text-muted">Build campaigns with professional audience targeting, budget controls, and performance tracking.</p>
                        <ul class="list-unstyled text-start">
                            <li>✓ Demographic Targeting</li>
                            <li>✓ Geographic Filtering</li>
                            <li>✓ Interest-Based Audiences</li>
                            <li>✓ Budget Management</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="step-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h4>Track Performance</h4>
                        <p class="text-muted">Monitor campaign performance, analyze results, and optimize your marketing efforts with comprehensive analytics.</p>
                        <ul class="list-unstyled text-start">
                            <li>✓ Real-time Analytics</li>
                            <li>✓ Campaign Calendar</li>
                            <li>✓ Performance Reports</li>
                            <li>✓ ROI Tracking</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12 text-center">
            <h3 class="mb-4">Ready to Get Started?</h3>
            <div class="btn-group">
                <a href="/myaccount/marketing/" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-right me-2"></i>Go to Marketing Dashboard
                </a>
                <a href="/myaccount/marketing/campaign-create" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-plus me-2"></i>Create Your First Campaign
                </a>
            </div>
            <div class="mt-3">
                <small class="text-muted">Start with our free Birthday.Gold platform or connect your existing marketing accounts</small>
            </div>
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>