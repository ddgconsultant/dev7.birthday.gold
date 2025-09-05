<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "How to use Birthday.Gold Marketing Platform";

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}

.how-it-works {
    max-width: 1000px;
    margin: 0 auto;
}

.step-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid #e9ecef;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.step-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.step-number {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.25rem;
    margin-bottom: 1rem;
}

.system-comparison {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
    margin: 2rem 0;
}

.system-box {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    border: 2px solid #e9ecef;
    transition: border-color 0.2s ease;
}

.system-box:hover {
    border-color: #667eea;
}

.system-newsletter {
    border-color: #28a745 !important;
}

.system-external {
    border-color: #007bff !important;
}

.flow-arrow {
    font-size: 2rem;
    color: #667eea;
    text-align: center;
    margin: 1rem 0;
}

.example-box {
    background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1rem 0;
    border-left: 4px solid #2196f3;
}

.highlight-box {
    background: linear-gradient(135deg, #fff3e0 0%, #ffffff 100%);
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1rem 0;
    border-left: 4px solid #ff9800;
}

.icon-large {
    font-size: 3rem;
    color: #667eea;
    margin-bottom: 1rem;
}

</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-lightbulb me-3"></i>How to use Birthday.Gold Marketing Platform</h1>
        <p class="lead">Your complete guide to creating platforms, launching campaigns, and tracking performance</p>
    </div>
</div>';

echo '
<div class="container how-it-works mb-5">

    <!-- Back Button -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="/myaccount/marketing/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Marketing Dashboard
            </a>
        </div>
    </div>

    <!-- Main Concept -->
    <div class="step-card text-center mb-4">
        <div class="icon-large">
            <i class="bi bi-diagram-3"></i>
        </div>
        <h2>Simple 3-Step Process</h2>
        <p class="lead">Create platforms → Launch campaigns → Track performance</p>
    </div>

    <!-- Step 1: Platforms -->
    <div class="step-card">
        <div class="step-number">1</div>
        <h3><i class="bi bi-link-45deg me-2"></i>Create Your Platforms</h3>
        <p><strong>Platforms are the marketing tools and websites you use.</strong></p>
        
        <div class="example-box">
            <h6><i class="bi bi-lightbulb-fill me-2"></i>Platform Examples:</h6>
            <ul class="mb-0">
                <li><strong>Facebook Ads</strong> - Where you create Facebook advertising campaigns</li>
                <li><strong>Google Ads</strong> - Your Google advertising account</li>
                <li><strong>Instagram Business</strong> - Your Instagram business profile</li>
                <li><strong>Email Newsletter</strong> - Your email marketing service</li>
                <li><strong>Website Blog</strong> - Your company website or blog</li>
            </ul>
        </div>
        
        <p>Each platform is a "place" where you can launch marketing campaigns. You only need to set up each platform once.</p>
        
        <div class="mt-3">
            <a href="/myaccount/marketing/platforms.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add Your Platforms
            </a>
        </div>
    </div>

    <!-- Arrow -->
    <div class="flow-arrow">
        <i class="bi bi-arrow-down"></i>
    </div>

    <!-- Step 2: Campaigns -->
    <div class="step-card">
        <div class="step-number">2</div>
        <h3><i class="bi bi-megaphone me-2"></i>Launch Your Campaigns</h3>
        <p><strong>Campaigns are specific marketing activities that link back to Birthday.Gold.</strong></p>
        
        <div class="example-box">
            <h6><i class="bi bi-lightbulb-fill me-2"></i>Campaign Examples by Platform:</h6>
            
            <div class="mb-3">
                <strong><i class="bi bi-award me-1"></i>Birthday.Gold Marketing Platform:</strong>
                <ul class="small mb-1 ms-3">
                    <li><strong>Newsletter Campaign</strong> - "Join Our Birthday Club" email to members</li>
                    <li><strong>Direct Email Campaign</strong> - "Special Birthday Offer" targeted email</li>
                    <li><strong>Member Update</strong> - "New Rewards Available" communication</li>
                </ul>
            </div>
            
            <div class="mb-0">
                <strong><i class="bi bi-globe me-1"></i>External Platforms:</strong>
                <ul class="small mb-0 ms-3">
                    <li><strong>Facebook Campaign</strong> - "Summer Birthday Special" ad with tracking link</li>
                    <li><strong>Google Ads Campaign</strong> - "Birthday Rewards" search ads</li>
                    <li><strong>Instagram Campaign</strong> - Social media post with signup link</li>
                    <li><strong>Website Campaign</strong> - Blog article "How to Get Free Birthday Gifts"</li>
                </ul>
            </div>
        </div>
        
        <div class="highlight-box">
            <h6><i class="bi bi-calendar-check me-2"></i>Required Information:</h6>
            <ul class="mb-0">
                <li><strong>Start Date:</strong> When the campaign goes live (required)</li>
                <li><strong>End Date:</strong> When it ends (optional - can run indefinitely)</li>
                <li><strong>Campaign Type:</strong> Brand awareness, lead generation, conversions, etc.</li>
                <li><strong>Budget:</strong> How much you are spending (optional)</li>
            </ul>
        </div>
        
        <p>Each campaign gets tracked separately so you can see which marketing efforts work best.</p>
        
        <div class="mt-3">
            <a href="/myaccount/marketing/campaigns.php" class="btn btn-primary">
                <i class="bi bi-eye me-2"></i>View Your Campaigns
            </a>
        </div>
    </div>

    <!-- Arrow -->
    <div class="flow-arrow">
        <i class="bi bi-arrow-down"></i>
    </div>

    <!-- Step 3: Tracking -->
    <div class="step-card">
        <div class="step-number">3</div>
        <h3><i class="bi bi-graph-up me-2"></i>Track Performance</h3>
        <p><strong>Birthday.Gold automatically tracks how well each campaign performs.</strong></p>
        
        <div class="example-box">
            <h6><i class="bi bi-bar-chart-fill me-2"></i>What Gets Tracked:</h6>
            <ul class="mb-0">
                <li><strong>Clicks:</strong> How many people clicked your links</li>
                <li><strong>Views:</strong> How many people saw your content</li>
                <li><strong>Signups:</strong> How many people joined Birthday.Gold from your campaign</li>
                <li><strong>Click-Through Rate:</strong> Percentage of people who clicked (minimum tracking)</li>
                <li><strong>Conversions:</strong> People who completed the desired action</li>
            </ul>
        </div>
        
        <p>All tracking happens automatically when people click links that go to Birthday.Gold. You can see which campaigns bring you the most new members.</p>
        
        <div class="mt-3">
            <a href="/myaccount/marketing/reports.php" class="btn btn-primary">
                <i class="bi bi-graph-up me-2"></i>View Your Reports
            </a>
        </div>
    </div>

    <!-- Platform Types & Metrics -->
    <div class="system-comparison">
        <h3 class="text-center mb-4"><i class="bi bi-speedometer me-2"></i>Platform Types & Available Metrics</h3>
        <p class="text-center text-muted mb-4">Different platforms provide different levels of tracking detail:</p>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="system-box system-newsletter">
                    <h5><i class="bi bi-award me-2"></i>Birthday.Gold Marketing</h5>
                    <p><strong>Default Platform (Rich Analytics)</strong></p>
                    <ul class="small">
                        <li><strong>Newsletter campaigns</strong> - Email to your members</li>
                        <li><strong>Direct email campaigns</strong> - Targeted messaging</li>
                        <li><strong>Internal promotions</strong> - Birthday.Gold hosted content</li>
                        <li><strong>Member communications</strong> - Updates and announcements</li>
                    </ul>
                    <div class="badge bg-success mb-2">Rich KPIs Available</div>
                    <div class="small text-muted">
                        <strong>Metrics:</strong> Opens, clicks, conversions, audience data, detailed engagement, signup rates
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="system-box system-external">
                    <h5><i class="bi bi-globe me-2"></i>External Platforms</h5>
                    <p><strong>Your Other Marketing Tools</strong></p>
                    <ul class="small">
                        <li><strong>Facebook/Instagram ads</strong> - Social media advertising</li>
                        <li><strong>Google Ads</strong> - Search and display advertising</li>
                        <li><strong>Social media posts</strong> - Organic social content</li>
                        <li><strong>Video campaigns</strong> - YouTube, TikTok, etc.</li>
                        <li><strong>Website/blog content</strong> - Your own website</li>
                    </ul>
                    <div class="badge bg-primary mb-2">Basic KPIs Available</div>
                    <div class="small text-muted">
                        <strong>Metrics:</strong> Click-through rates, traffic referrals, basic conversion tracking
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">
                <strong>Why the difference?</strong> We control the Birthday.Gold platform and have access to your member data, 
                so we can provide much more detailed analytics. External platforms use tracking links for basic metrics.
            </small>
        </div>
    </div>

    <!-- Getting Started -->
    <div class="step-card text-center">
        <h3><i class="bi bi-rocket me-2"></i>Ready to Get Started?</h3>
        <p class="lead">The easiest way to begin is to add your first platform.</p>
        
        <div class="row mt-4">
            <div class="col-md-4 offset-md-2 mb-3">
                <a href="/myaccount/marketing/platform-create.php" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-plus-circle me-2"></i>Add Platform
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="/myaccount/marketing/campaigns.php" class="btn btn-outline-primary btn-lg w-100">
                    <i class="bi bi-eye me-2"></i>View Campaigns
                </a>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="step-card">
        <h3><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h3>
        
        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        What is the difference between a platform and a campaign?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Platform:</strong> The tool or website you use for marketing (Facebook, Google, your website)<br>
                        <strong>Campaign:</strong> A specific marketing activity on that platform (an ad, email, or post)
                        <br><br>
                        Think of it like: <em>Platform = Your toolbox, Campaign = Using a specific tool</em>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        How does Birthday.Gold track my campaigns?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Birthday.Gold Marketing Platform:</strong> We have complete control and can track opens, clicks, conversions, audience engagement, and detailed member behavior.
                        <br><br>
                        <strong>External Platforms:</strong> When someone clicks a tracking link that goes to Birthday.Gold, we record:
                        <ul class="mt-2">
                            <li>Which campaign they came from</li>
                            <li>When they clicked</li>
                            <li>Basic conversion actions (signups)</li>
                        </ul>
                        <strong>The key difference:</strong> We provide richer analytics when we control the platform and audience data.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        Do I need to set up tracking codes?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        No! The tracking happens automatically when people visit Birthday.Gold from your campaigns. 
                        Just make sure your campaign links point to your Birthday.Gold signup or referral pages.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                        Can I track campaigns that are not on social media?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes! You can track any campaign that drives people to Birthday.Gold:
                        <ul class="mt-2">
                            <li>Print advertisements with QR codes</li>
                            <li>Email signatures with links</li>
                            <li>Business card QR codes</li>
                            <li>Website banner ads</li>
                            <li>Radio or podcast mentions</li>
                        </ul>
                        As long as people end up on Birthday.Gold, we can track it.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                        Why does Birthday.Gold Marketing provide better metrics?
                    </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Birthday.Gold Marketing</strong> is our internal platform where we control everything:
                        <ul class="mt-2">
                            <li>We send the emails directly to your member list</li>
                            <li>We have access to detailed member data and behavior</li>
                            <li>We can track opens, clicks, time spent, and full conversion funnels</li>
                            <li>We know exactly who engaged and how they responded</li>
                        </ul>
                        <strong>External platforms</strong> (Facebook, Google, etc.) only allow us to track what happens when someone visits Birthday.Gold, so we get basic click and conversion data but cannot see the full engagement picture.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>