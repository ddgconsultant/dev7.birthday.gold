<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Partner API Documentation - Birthday Gold Business';
$pagedata['metakeywords'] = 'Birthday Gold API, Partner API, Business API Documentation, Birthday Rewards API';
$pagedata['metadescriptions'] = 'Complete API documentation for Birthday Gold business partners. Manage users, campaigns, and reward programs programmatically.';

// Header flush for better spacing
$header_flush = true;

// Additional styles
$additionalstyles = '
<style>
/* API Documentation Styles */
.api-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 50%, #16213e 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.api-hero::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

.api-hero h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

.api-hero p {
    font-size: 1.5rem;
    position: relative;
    z-index: 1;
    opacity: 0.9;
}

/* API Navigation */
.api-nav {
    background: #f8f9fa;
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 100;
    border-bottom: 1px solid #dee2e6;
}

.api-nav-links {
    display: flex;
    gap: 2rem;
    justify-content: center;
    flex-wrap: wrap;
}

.api-nav-links a {
    color: #495057;
    text-decoration: none;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    transition: all 0.2s ease;
}

.api-nav-links a:hover {
    background: #e9ecef;
    color: #198754;
}

/* API Sections */
.api-section {
    padding: 4rem 0;
    border-bottom: 1px solid #dee2e6;
}

.api-section:last-child {
    border-bottom: none;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #212529;
}

.section-subtitle {
    font-size: 1.3rem;
    color: #6c757d;
    margin-bottom: 3rem;
}

/* Endpoint Cards */
.endpoint-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.endpoint-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #198754;
}

.endpoint-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.method-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-weight: 700;
    font-size: 0.875rem;
    text-transform: uppercase;
}

.method-get {
    background: #d4edda;
    color: #155724;
}

.method-post {
    background: #cce5ff;
    color: #004085;
}

.method-put {
    background: #fff3cd;
    color: #856404;
}

.method-delete {
    background: #f8d7da;
    color: #721c24;
}

.endpoint-path {
    font-family: monospace;
    font-size: 1.1rem;
    color: #495057;
    flex: 1;
}

.endpoint-description {
    color: #6c757d;
    margin-bottom: 1rem;
}

/* Code Blocks */
.code-block {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 1rem;
    border-radius: 5px;
    overflow-x: auto;
    margin: 1rem 0;
    font-family: "Consolas", "Monaco", "Courier New", monospace;
    font-size: 0.9rem;
    line-height: 1.5;
}

.code-block pre {
    margin: 0;
}

/* Response Example */
.response-example {
    background: #f8f9fa;
    border-left: 4px solid #198754;
    padding: 1rem;
    margin: 1rem 0;
    border-radius: 0 5px 5px 0;
}

/* Feature Cards */
.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.feature-card {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.feature-icon {
    font-size: 3rem;
    color: #198754;
    margin-bottom: 1rem;
}

.feature-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

/* Status Badges */
.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-available {
    background: #d4edda;
    color: #155724;
}

.status-coming-soon {
    background: #fff3cd;
    color: #856404;
}

.status-beta {
    background: #cce5ff;
    color: #004085;
}

/* Tables */
.params-table {
    width: 100%;
    margin: 1rem 0;
    border-collapse: collapse;
}

.params-table th,
.params-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.params-table th {
    background: #f8f9fa;
    font-weight: 700;
    color: #495057;
}

.params-table td {
    color: #6c757d;
}

.params-table code {
    background: #f8f9fa;
    padding: 0.125rem 0.25rem;
    border-radius: 3px;
    font-size: 0.875rem;
    color: #d73502;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, #198754 0%, #157347 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
    margin-top: 4rem;
}

.cta-section h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.cta-section p {
    font-size: 1.3rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.btn-api-access {
    background: white;
    color: #198754;
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-size: 1.2rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-api-access:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
    color: #198754;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .api-hero h1 {
        font-size: 2rem;
    }
    
    .api-hero p {
        font-size: 1.2rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .endpoint-header {
        flex-direction: column;
        align-items: start;
    }
    
    .api-nav-links {
        gap: 0.5rem;
    }
    
    .api-nav-links a {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="api-hero">
    <div class="container">
        <h1 class="text-white pt-4">Birthday.Gold Partner API</h1>
        <p>Integrate birthday rewards directly into your business systems</p>
    </div>
</div>

<!-- API Navigation -->
<div class="api-nav">
    <div class="container">
        <div class="api-nav-links">
            <a href="#overview">Overview</a>
            <a href="#authentication">Authentication</a>
            <a href="#users">User Management</a>
            <a href="#campaigns">Campaigns</a>
            <a href="#rewards">Rewards</a>
            <a href="#analytics">Analytics</a>
            <a href="#webhooks">Webhooks</a>
            <a href="#sdks">SDKs</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    
    <!-- Overview Section -->
    <section id="overview" class="api-section">
        <h2 class="section-title">API Overview</h2>
        <p class="section-subtitle">Build powerful integrations with Birthday Gold's Partner API</p>
        
        <div class="feature-grid">
            <div class="feature-card">
                <i class="bi bi-people-fill feature-icon"></i>
                <h3 class="feature-title">User Management</h3>
                <p>Enroll members, manage profiles, and track birthday celebrations seamlessly.</p>
                <span class="status-badge status-coming-soon">Coming Soon</span>
            </div>
            
            <div class="feature-card">
                <i class="bi bi-megaphone-fill feature-icon"></i>
                <h3 class="feature-title">Campaign Management</h3>
                <p>Create, schedule, and monitor marketing campaigns targeted at birthday celebrants.</p>
                <span class="status-badge status-coming-soon">Coming Soon</span>
            </div>
            
            <div class="feature-card">
                <i class="bi bi-gift-fill feature-icon"></i>
                <h3 class="feature-title">Reward Programs</h3>
                <p>Design and deploy custom birthday reward programs with flexible redemption options.</p>
                <span class="status-badge status-coming-soon">Coming Soon</span>
            </div>
            
            <div class="feature-card">
                <i class="bi bi-graph-up-arrow feature-icon"></i>
                <h3 class="feature-title">Analytics & Reporting</h3>
                <p>Access detailed insights on redemptions, engagement, and ROI metrics.</p>
                <span class="status-badge status-coming-soon">Coming Soon</span>
            </div>
        </div>
        
        <div class="alert alert-info mt-5">
            <h5><i class="bi bi-info-circle me-2"></i>API Access</h5>
            <p>The Birthday Gold Partner API is currently under development. Join our partner program to be notified when API access becomes available.</p>
        </div>
    </section>
    
    <!-- Authentication Section -->
    <section id="authentication" class="api-section">
        <h2 class="section-title">Authentication</h2>
        <p class="section-subtitle">Secure API access with OAuth 2.0 and API keys</p>
        
        <h3>Authentication Methods</h3>
        <div class="endpoint-card">
            <h4>API Key Authentication</h4>
            <p>For server-to-server integrations, use your API key in the request header:</p>
            <div class="code-block">
                <pre>Authorization: Bearer YOUR_API_KEY</pre>
            </div>
        </div>
        
        <div class="endpoint-card">
            <h4>OAuth 2.0</h4>
            <p>For user-authorized actions, implement OAuth 2.0 flow:</p>
            <div class="code-block">
                <pre>POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code=AUTHORIZATION_CODE
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
&redirect_uri=YOUR_REDIRECT_URI</pre>
            </div>
        </div>
    </section>
    
    <!-- User Management Section -->
    <section id="users" class="api-section">
        <h2 class="section-title">User Management API</h2>
        <p class="section-subtitle">Manage Birthday Gold members from your platform</p>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/users/enroll</span>
            </div>
            <p class="endpoint-description">Enroll a new user in Birthday Gold</p>
            
            <h5>Request Body</h5>
            <div class="code-block">
                <pre>{
  "email": "user@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "birthday": "1990-06-15",
  "phone": "+1234567890",
  "source": "partner_enrollment",
  "partner_user_id": "USR123456"
}</pre>
            </div>
            
            <h5>Response</h5>
            <div class="code-block">
                <pre>{
  "success": true,
  "user": {
    "id": "bguser_1234567890",
    "email": "user@example.com",
    "status": "active",
    "enrollment_date": "2025-07-06T10:30:00Z"
  }
}</pre>
            </div>
        </div>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/users/{user_id}</span>
            </div>
            <p class="endpoint-description">Retrieve user details and enrollment status</p>
        </div>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-put">PUT</span>
                <span class="endpoint-path">/api/v1/users/{user_id}</span>
            </div>
            <p class="endpoint-description">Update user profile information</p>
        </div>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-delete">DELETE</span>
                <span class="endpoint-path">/api/v1/users/{user_id}</span>
            </div>
            <p class="endpoint-description">Remove user from Birthday Gold (soft delete)</p>
        </div>
    </section>
    
    <!-- Campaign Management Section -->
    <section id="campaigns" class="api-section">
        <h2 class="section-title">Campaign Management API</h2>
        <p class="section-subtitle">Create and manage targeted birthday marketing campaigns</p>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/campaigns</span>
            </div>
            <p class="endpoint-description">Create a new birthday campaign</p>
            
            <h5>Request Body</h5>
            <div class="code-block">
                <pre>{
  "name": "Summer Birthday Special",
  "description": "20% off for July birthdays",
  "start_date": "2025-07-01",
  "end_date": "2025-07-31",
  "target_criteria": {
    "birth_month": 7,
    "age_range": {
      "min": 18,
      "max": 65
    }
  },
  "reward": {
    "type": "percentage_discount",
    "value": 20,
    "applicable_to": "entire_purchase"
  }
}</pre>
            </div>
        </div>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/campaigns</span>
            </div>
            <p class="endpoint-description">List all campaigns with filtering options</p>
            
            <h5>Query Parameters</h5>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>status</code></td>
                        <td>string</td>
                        <td>Filter by campaign status (active, scheduled, completed)</td>
                    </tr>
                    <tr>
                        <td><code>start_date</code></td>
                        <td>date</td>
                        <td>Filter campaigns starting after this date</td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td>integer</td>
                        <td>Number of results per page (default: 20)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/campaigns/{campaign_id}/analytics</span>
            </div>
            <p class="endpoint-description">Get detailed analytics for a specific campaign</p>
        </div>
    </section>
    
    <!-- Rewards Section -->
    <section id="rewards" class="api-section">
        <h2 class="section-title">Rewards Program API</h2>
        <p class="section-subtitle">Configure and manage birthday reward offerings</p>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/rewards</span>
            </div>
            <p class="endpoint-description">Create a new reward program</p>
            
            <h5>Request Body</h5>
            <div class="code-block">
                <pre>{
  "name": "Birthday Free Dessert",
  "type": "free_item",
  "description": "Complimentary dessert during birthday month",
  "terms": [
    "Valid during birthday month only",
    "One redemption per member",
    "Dine-in only"
  ],
  "redemption_method": "qr_code",
  "locations": ["all"]
}</pre>
            </div>
        </div>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/rewards/{reward_id}/validate</span>
            </div>
            <p class="endpoint-description">Validate a reward redemption attempt</p>
        </div>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/rewards/{reward_id}/redeem</span>
            </div>
            <p class="endpoint-description">Process a reward redemption</p>
        </div>
    </section>
    
    <!-- Analytics Section -->
    <section id="analytics" class="api-section">
        <h2 class="section-title">Analytics & Reporting API</h2>
        <p class="section-subtitle">Access comprehensive data on your birthday programs</p>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/analytics/overview</span>
            </div>
            <p class="endpoint-description">Get high-level metrics for your account</p>
            
            <h5>Response Example</h5>
            <div class="code-block">
                <pre>{
  "period": "last_30_days",
  "metrics": {
    "total_members": 1523,
    "new_enrollments": 87,
    "redemptions": 342,
    "redemption_rate": 0.225,
    "average_transaction_value": 45.67,
    "revenue_impact": 15632.14
  }
}</pre>
            </div>
        </div>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/analytics/redemptions</span>
            </div>
            <p class="endpoint-description">Detailed redemption analytics with filtering</p>
        </div>
        
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/analytics/demographics</span>
            </div>
            <p class="endpoint-description">Member demographic insights</p>
        </div>
    </section>
    
    <!-- Webhooks Section -->
    <section id="webhooks" class="api-section">
        <h2 class="section-title">Webhooks</h2>
        <p class="section-subtitle">Receive real-time notifications for important events</p>
        
        <h3>Available Webhook Events</h3>
        
        <div class="endpoint-card">
            <h4><i class="bi bi-bell me-2"></i>user.enrolled</h4>
            <p>Triggered when a new user successfully enrolls</p>
            <div class="code-block">
                <pre>{
  "event": "user.enrolled",
  "timestamp": "2025-07-06T10:30:00Z",
  "data": {
    "user_id": "bguser_1234567890",
    "email": "user@example.com",
    "enrollment_source": "partner_api"
  }
}</pre>
            </div>
        </div>
        
        <div class="endpoint-card">
            <h4><i class="bi bi-bell me-2"></i>reward.redeemed</h4>
            <p>Triggered when a member redeems a reward</p>
        </div>
        
        <div class="endpoint-card">
            <h4><i class="bi bi-bell me-2"></i>campaign.completed</h4>
            <p>Triggered when a campaign ends</p>
        </div>
        
        <h3>Webhook Configuration</h3>
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/webhooks</span>
            </div>
            <p class="endpoint-description">Register a new webhook endpoint</p>
        </div>
    </section>
    
    <!-- SDKs Section -->
    <section id="sdks" class="api-section">
        <h2 class="section-title">SDKs & Libraries</h2>
        <p class="section-subtitle">Get started quickly with our official SDKs</p>
        
        <div class="feature-grid">
            <div class="feature-card">
                <i class="bi bi-filetype-php feature-icon"></i>
                <h3 class="feature-title">PHP SDK</h3>
                <p>Native PHP library with full API coverage</p>
                <div class="code-block">
                    <pre>composer require birthdaygold/partner-sdk-php</pre>
                </div>
                <span class="status-badge status-coming-soon">Coming Soon</span>
            </div>
            
            <div class="feature-card">
                <i class="bi bi-filetype-js feature-icon"></i>
                <h3 class="feature-title">JavaScript SDK</h3>
                <p>Browser and Node.js compatible library</p>
                <div class="code-block">
                    <pre>npm install @birthdaygold/partner-sdk</pre>
                </div>
                <span class="status-badge status-coming-soon">Coming Soon</span>
            </div>
            
            <div class="feature-card">
                <i class="bi bi-filetype-py feature-icon"></i>
                <h3 class="feature-title">Python SDK</h3>
                <p>Python library with async support</p>
                <div class="code-block">
                    <pre>pip install birthdaygold-partner</pre>
                </div>
                <span class="status-badge status-coming-soon">Coming Soon</span>
            </div>
        </div>
    </section>
    
</div>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to Integrate?</h2>
        <p>Join our partner program to get early access to the API</p>
        <a href="/business/partner" class="btn-api-access">Become a Partner</a>
    </div>
</section>

<!-- Back to Partner Program -->
<div class="container my-5">
    <div class="text-center">
        <a href="/business/partner" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i>Back to Partner Program
        </a>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>