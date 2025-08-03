<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Admin Dashboard - Birthday Gold";
$page_description = "Birthday Gold administrative dashboard and management tools";

// Modern Admin Dashboard CSS inspired by help page design
$additionalstyles .= '
<style>
/* Modern Admin Dashboard Styles */

/* Remove all padding/margins that might cause white space */
body {
    padding: 0 !important;
    margin: 0 !important;
}

.navbar {
    margin-bottom: 0 !important;
}

/* Ensure content header is flush with navbar */
.content-header-admin {
    margin-top: 0 !important;
}

/* Remove the row div spacing after navbar */
.navbar + .row {
    margin: 0 !important;
    padding: 0 !important;
    height: 0 !important;
}

/* Force admin header to be flush */
.navbar + .row + .content-header-admin {
    margin-top: 0 !important;
}

/* Search Box - matching help page */
.admin-search {
    max-width: 600px;
    margin: -2rem auto 3rem;
    position: relative;
    z-index: 1000; /* Much higher z-index */
}

.search-input {
    width: 100%;
    padding: 1rem 3rem 1rem 1.5rem;
    font-size: 1.125rem;
    border: 1px solid #dee2e6;
    border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
    z-index: 1001; /* Ensure input is above other elements */
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.search-icon {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
    z-index: 1002; /* Ensure icon is visible */
}

/* Section Headers */
.section-header {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.section-subtitle {
    font-size: 1rem;
    color: #6c757d;
}

/* CSS Variables for responsive spacing */
:root {
    --admin-grid-gap: 1.5rem;
    --admin-grid-mb: 3rem;
    --admin-card-padding: 1.5rem;
    --admin-card-gap: 1rem;
    --admin-icon-size: 48px;
    --admin-icon-img-size: 40px;
}

/* Admin Cards Grid */
.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: var(--admin-grid-gap);
    margin-bottom: var(--admin-grid-mb);
}

/* Admin Card */
.admin-card {
    background: white;
    border-radius: 12px;
    padding: var(--admin-card-padding);
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center; /* Changed to center for vertical alignment */
    gap: var(--admin-card-gap);
}

.admin-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
    text-decoration: none;
}

.admin-icon {
    flex-shrink: 0;
    width: var(--admin-icon-size);
    height: var(--admin-icon-size);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.admin-icon img {
    max-width: var(--admin-icon-img-size);
    max-height: var(--admin-icon-img-size);
    width: auto;
    height: auto;
}

/* Icon background colors by category */
.icon-productivity { background: #e7f3ff; color: #0066cc; }
.icon-user { background: #e8f5e9; color: #2e7d32; }
.icon-plans { background: #fff3e0; color: #ef6c00; }
.icon-system { background: #f3e5f5; color: #7b1fa2; }
.icon-sales { background: #e0f2f1; color: #00897b; }
.icon-admin { background: #fce4ec; color: #c2185b; }
.icon-enrollment { background: #ffebee; color: #c62828; }
.icon-brand { background: #e8eaf6; color: #3f51b5; }
.icon-help { background: #e1f5fe; color: #0288d1; }
.icon-tech { background: #f1f8e9; color: #558b2f; }

.admin-content {
    flex: 1;
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

/* Badge styling */
.enrollment-badge {
    background-color: #dc3545;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    margin-left: 0.5rem;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--bs-primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

/* Main content styling to match help page */
.main-content {
    background-color: #f8f9fa;
    padding-top: 2rem;
    padding-bottom: 2rem;
    min-height: calc(100vh - 200px);
}

/* Remove main-content background and padding since header handles it */
.main-content {
    background-color: transparent !important;
    padding-top: 0 !important;
}

/* Mobile adjustments */
@media (max-width: 767px) {
    /* Override CSS variables for mobile */
    :root {
        --admin-grid-gap: 0.25rem;
        --admin-grid-mb: 1rem;
        --admin-card-padding: 0.75rem;
        --admin-card-gap: 0.75rem;
        --admin-icon-size: 32px;
        --admin-icon-img-size: 24px;
    }
    
    /* Remove Bootstrap default container padding on mobile */
    body > .container,
    body > .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Compact hero section on mobile */
    .content-header-admin {
        padding: 1.5rem 0 !important;
    }
    
    .content-header-admin h1 {
        font-size: 1.75rem !important;
        margin-top: 0 !important;
        margin-bottom: 0.5rem !important;
    }
    
    .content-header-admin .lead {
        font-size: 0.9rem !important;
        margin-bottom: 1.5rem !important;
        line-height: 1.4 !important;
    }
    
    /* Admin search positioning */
    .admin-search {
        margin: -1.5rem auto 1.5rem; /* Less negative margin on mobile */
        padding: 0 15px;
        position: relative;
        z-index: 1100;
    }
    
    /* Smaller search input on mobile */
    .search-input {
        padding: 0.75rem 2.5rem 0.75rem 1rem !important;
        font-size: 1rem !important;
    }
    
    .search-icon {
        right: 1rem !important;
    }
    
    /* Ensure search has white background on mobile */
    .admin-search .search-input {
        background: white;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    
    /* Compact main content padding */
    .main-content {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }
    
    /* Hide stats grid on mobile - too much space */
    .stats-grid {
        display: none !important;
    }
    
    /* Compact section headers */
    .section-header {
        margin-bottom: 1rem !important;
    }
    
    .section-title {
        font-size: 1.25rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .section-subtitle {
        font-size: 0.875rem !important;
        display: none; /* Hide subtitles on mobile to save space */
    }
    
    /* Mobile-specific adjustments */
    .admin-icon {
        font-size: 0.875rem !important;
        border-radius: 8px !important;
    }
    
    .admin-card-title {
        font-size: 0.9375rem !important;
        line-height: 1.2 !important;
        margin-bottom: 0 !important;
    }
    
    .admin-card-text {
        display: none; /* Hide description text on mobile */
    }
    
    /* Enrollment badge smaller on mobile */
    .enrollment-badge {
        font-size: 0.65rem !important;
        padding: 0.15rem 0.4rem !important;
    }
    
    /* Collapsible sections on mobile */
    .mobile-collapse {
        display: block;
    }
    
    /* Add expand/collapse buttons for sections - mobile only */
    .section-header {
        cursor: pointer;
        position: relative;
        padding-right: 30px;
    }
    
    .section-header::after {
        content: "\\F282"; /* Bootstrap Icons chevron-down */
        font-family: "bootstrap-icons";
        position: absolute;
        right: 0;
        top: 0;
        font-size: 1rem;
        transition: transform 0.3s;
    }
    
    .section-header.collapsed::after {
        transform: rotate(-90deg);
    }
}

/* Remove collapse styling on desktop */
@media (min-width: 768px) {
    .section-header {
        cursor: default !important;
        padding-right: 0 !important;
    }
    
    .section-header::after {
        display: none !important;
    }
}

/* Responsive */
@media (max-width: 767px) {
    .admin-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

@media (min-width: 768px) {
    .main-content {
        padding: 3rem 2rem;
    }
    
    .admin-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 992px) {
    .admin-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1400px) {
    .admin-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>
';

$bodycontentclass = ''; // This removes the my-4 margin from the row after nav
$header_flush = true; // Ensure header content is flush with admin header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
// Removed bg_admin_leftpanel.inc as it wraps content in main-content div

// Get enrollment count for badge
$enrollmentCount = $app->admin_getenrollments();

// Get business hours for future use
$businessHours = $app->bg_businesshours();
?>

<!-- Hero Section -->
<div class="content-header-admin no-rounded-corners">
    <div class="container">
        <h1 class="mt-3">Admin Dashboard</h1>
        <p class="lead mb-4">Manage the Birthday Gold platform with powerful administrative tools</p>
    </div>
</div>

<div class="container">
    <!-- Search Bar -->
    <div class="admin-search">
        <div class="position-relative">
            <input 
                type="text" 
                class="search-input" 
                placeholder="Search admin functions..."
                id="adminSearch"
                autocomplete="off"
            >
            <i class="bi bi-search search-icon"></i>
        </div>
    </div>
</div>

<div class="main-content py-4 py-md-5 bg-light">
    <div class="container" style="max-width: 1400px;">
        
        <!-- Mobile Quick Access (only shown on mobile) -->
        <div class="d-block d-md-none mb-3">
            <div class="card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-around flex-wrap">
                        <a href="/admin/redirect-enrollments" class="btn btn-sm btn-outline-primary m-1">
                            <i class="bi bi-person-plus"></i> Enrollments
                            <?php if ($enrollmentCount > 0): ?>
                            <span class="badge bg-danger"><?php echo $enrollmentCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/admin/user-list-v2" class="btn btn-sm btn-outline-primary m-1">
                            <i class="bi bi-people"></i> Users
                        </a>
                        <a href="/admin/business-submissions" class="btn btn-sm btn-outline-primary m-1">
                            <i class="bi bi-inbox"></i> Submissions
                        </a>
                        <a href="/admin/eligibility-dashboard" class="btn btn-sm btn-outline-primary m-1">
                            <i class="bi bi-shield-check"></i> Eligibility
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <?php if ($account->isadmin()): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo isset($database) && method_exists($database, 'bg_activeusers') ? number_format($database->bg_activeusers()) : '0'; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $enrollmentCount; ?></div>
                <div class="stat-label">Pending Enrollments</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo isset($database) && method_exists($database, 'bg_sessioncount') ? number_format($database->bg_sessioncount()) : '0'; ?></div>
                <div class="stat-label">Active Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $businessHours['display']['displaystatus'] ?? 'Open'; ?></div>
                <div class="stat-label">Business Status</div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Productivity Section -->
        <div class="section-header">
            <h2 class="section-title">Productivity Tools</h2>
            <p class="section-subtitle">Core tools for daily operations and management</p>
        </div>
        
        <div class="admin-grid">
            <a href="/admin/accessmanager" class="admin-card">
                <div class="admin-icon icon-productivity">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Access Manager</h3>
                    <p class="admin-card-text">Manage user access levels and permissions</p>
                </div>
            </a>
            
            <a href="/admin/redirect-projectmanagement" target="_blank" class="admin-card">
                <div class="admin-icon icon-productivity">
                    <img src="/public/images/system_icons/io.leantime.cloudronapp.png" alt="" width="40">
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Leantime</h3>
                    <p class="admin-card-text">Project management and task tracking</p>
                </div>
            </a>
            
            <a href="/admin/manage-pageeditor" class="admin-card">
                <div class="admin-icon icon-productivity">
                    <img src="/public/images/system_icons/pageeditor-icon.png" alt="" width="40">
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Page Editor</h3>
                    <p class="admin-card-text">Toggle and manage the page editor feature</p>
                </div>
            </a>
            
            <a href="/admin/redirect-docs" target="_blank" class="admin-card">
                <div class="admin-icon icon-productivity">
                    <i class="bi bi-book"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Document Wiki</h3>
                    <p class="admin-card-text">Documentation and knowledge base</p>
                </div>
            </a>
            
            <a href="/admin/blog_editor" target="_blank" class="admin-card">
                <div class="admin-icon icon-productivity">
                    <i class="bi bi-newspaper"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Blog Editor</h3>
                    <p class="admin-card-text">Create and manage blog content</p>
                </div>
            </a>
        </div>
        
        <!-- User Management Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">User Management</h2>
            <p class="section-subtitle">Manage users, enrollments, and customer feedback</p>
        </div>
        
        <div class="admin-grid">
            <a href="/admin/user-list" class="admin-card">
                <div class="admin-icon icon-user">
                    <i class="bi bi-people"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">User List</h3>
                    <p class="admin-card-text">View and manage all platform users</p>
                </div>
            </a>
            
            <a href="/admin/user-list-v2" class="admin-card">
                <div class="admin-icon icon-user">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Enhanced User Management</h3>
                    <p class="admin-card-text">Advanced user search with real-time filtering</p>
                </div>
            </a>
            
            <a href="/admin/security-reports" class="admin-card">
                <div class="admin-icon icon-security">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Security Reports</h3>
                    <p class="admin-card-text">Review and manage device security reports</p>
                </div>
            </a>
            
            <a href="/admin/redirect-enrollments" target="_blank" class="admin-card">
                <div class="admin-icon icon-enrollment">
                    <i class="bi bi-person-plus"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Enrollments
                        <?php if ($enrollmentCount > 0): ?>
                        <span class="enrollment-badge"><?php echo $enrollmentCount; ?></span>
                        <?php endif; ?>
                    </h3>
                    <p class="admin-card-text">Process pending enrollment requests</p>
                </div>
            </a>
            
            <a href="/admin/customer-satisfaction" class="admin-card">
                <div class="admin-icon icon-user">
                    <i class="bi bi-star"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Customer Satisfaction</h3>
                    <p class="admin-card-text">Review customer feedback and surveys</p>
                </div>
            </a>
            
            <a href="/admin/ai-dashboard.php" class="admin-card">
                <div class="admin-icon icon-user">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">AI Dashboard</h3>
                    <p class="admin-card-text">Ask Goldie analytics and conversation insights</p>
                </div>
            </a>
        </div>
        
        <!-- Plans & Products Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">Plans & Products</h2>
            <p class="section-subtitle">Manage subscription plans and promotional offers</p>
        </div>
        
        <div class="admin-grid">
            <a href="/admin/plan_editor.php" class="admin-card">
                <div class="admin-icon icon-plans">
                    <i class="bi bi-tag"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Plan Editor</h3>
                    <p class="admin-card-text">Configure subscription plans and pricing</p>
                </div>
            </a>
            
            <a href="/admin/promo_editor.php" class="admin-card">
                <div class="admin-icon icon-plans">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Promo Code Editor</h3>
                    <p class="admin-card-text">Create and manage promotional codes</p>
                </div>
            </a>
        </div>
        
        <!-- Analytics & Monitoring Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">Analytics & Monitoring</h2>
            <p class="section-subtitle">Track performance and system health</p>
        </div>
        
        <div class="admin-grid">
            <a href="/admin/redirect-metabase" target="_blank" class="admin-card">
                <div class="admin-icon icon-system">
                    <img src="/public/images/system_icons/com.metabase.cloudronapp.png" alt="" width="40">
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Metabase</h3>
                    <p class="admin-card-text">Business intelligence and analytics</p>
                </div>
            </a>
            
            <a href="/admin/redirect-netdata" target="_blank" class="admin-card">
                <div class="admin-icon icon-system">
                    <img src="/public/assets/logos/netdata.png" alt="" width="40">
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">System Performance</h3>
                    <p class="admin-card-text">Real-time infrastructure monitoring</p>
                </div>
            </a>
            
            <a href="/admin/redirect-uptime" target="_blank" class="admin-card">
                <div class="admin-icon icon-system">
                    <i class="bi bi-activity"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Uptime Status</h3>
                    <p class="admin-card-text">Service availability monitoring</p>
                </div>
            </a>
            
            <a href="/admin/mouse-tracker" class="admin-card">
                <div class="admin-icon icon-system">
                    <i class="bi bi-mouse"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Mouse Tracker</h3>
                    <p class="admin-card-text">User interaction analytics</p>
                </div>
            </a>
            
            <a href="/admin/eligibility-dashboard" class="admin-card">
                <div class="admin-icon icon-system">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Eligibility Monitor
                        <?php
                        // Get count of stale eligibility records
                        $stale_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
                        $stale_count = $database->query("SELECT COUNT(*) FROM bg_user_eligibility WHERE last_checked < '$stale_date'")->fetchColumn();
                        if ($stale_count > 1000):
                        ?>
                        <span class="enrollment-badge"><?php echo number_format($stale_count); ?></span>
                        <?php endif; ?>
                    </h3>
                    <p class="admin-card-text">Monitor user eligibility issues and requirements</p>
                </div>
            </a>
        </div>
        
        <?php if ($account->isadmin()): ?>
        <!-- Brand Management Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">Brand Management</h2>
            <p class="section-subtitle">Configure brands and reward programs</p>
        </div>
        
        <div class="admin-grid">
            <a href="/admin/brands" class="admin-card">
                <div class="admin-icon icon-brand">
                    <i class="bi bi-palette"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Brand Editor</h3>
                    <p class="admin-card-text">Manage brand configurations</p>
                </div>
            </a>
            
            <a href="/admin/business-submissions" class="admin-card">
                <div class="admin-icon icon-brand">
                    <i class="bi bi-inbox"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Business Submissions
                        <?php
                        $submission_count = $database->query("SELECT COUNT(*) FROM bg_companies WHERE status IN ('submitted', 'pending_review')")->fetchColumn();
                        if ($submission_count > 0):
                        ?>
                        <span class="enrollment-badge"><?php echo $submission_count; ?></span>
                        <?php endif; ?>
                    </h3>
                    <p class="admin-card-text">Review user-submitted businesses</p>
                </div>
            </a>
            
            <a href="<?php echo $dir['bge_webA']; ?>/companysetup.php?filter=finalized" class="admin-card">
                <div class="admin-icon icon-brand">
                    <i class="bi bi-building"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Company Setup</h3>
                    <p class="admin-card-text">Configure company partnerships</p>
                </div>
            </a>
            
            <a href="/admin/abo-status" class="admin-card">
                <div class="admin-icon icon-brand">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">ABO Status
                        <?php
                        $abo_count = $database->query("SELECT COUNT(DISTINCT company_id) FROM bg_company_attributes WHERE type = 'onboarding_progress' AND status = 'active'")->fetchColumn();
                        if ($abo_count > 0):
                        ?>
                        <span class="enrollment-badge"><?php echo $abo_count; ?></span>
                        <?php endif; ?>
                    </h3>
                    <p class="admin-card-text">Monitor Automated Business Onboarding</p>
                </div>
            </a>
            
            <a href="/admin_actions/manual_rewards" class="admin-card">
                <div class="admin-icon icon-brand">
                    <i class="bi bi-gift"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Reward Details</h3>
                    <p class="admin-card-text">Configure reward program details</p>
                </div>
            </a>
            
            <a href="/admin_actions/manual_policies" class="admin-card">
                <div class="admin-icon icon-brand">
                    <i class="bi bi-file-text"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Reward Policies</h3>
                    <p class="admin-card-text">Manage reward program policies</p>
                </div>
            </a>
            
            <a href="/admin_actions/rewardprocessors" class="admin-card">
                <div class="admin-icon icon-brand">
                    <i class="bi bi-cpu"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Reward Processor</h3>
                    <p class="admin-card-text">Configure reward processing systems</p>
                </div>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($account->isstaff('sysops') || $account->isadmin()): ?>
        <!-- System Administration Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">System Administration</h2>
            <p class="section-subtitle">Advanced system configuration and management</p>
        </div>
        
        <div class="admin-grid">
            <a href="/admin/redirect-cloudron" target="_blank" class="admin-card">
                <div class="admin-icon icon-system">
                    <img src="/public/images/system_icons/io.cloudron.buildservice.png" alt="" width="40">
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Cloudron</h3>
                    <p class="admin-card-text">Manage cloud infrastructure</p>
                </div>
            </a>
            
            <a href="/admin/serverlayout" class="admin-card">
                <div class="admin-icon icon-system">
                    <i class="bi bi-server"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Server Layout</h3>
                    <p class="admin-card-text">View infrastructure architecture</p>
                </div>
            </a>
            
            <a href="/admin/create_vhost" target="vhostwindow" class="admin-card">
                <div class="admin-icon icon-system">
                    <i class="bi bi-hdd-network"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Create Vhost</h3>
                    <p class="admin-card-text">Configure virtual hosts</p>
                </div>
            </a>
            
            <a href="http://april21.bday.gold:8080/" target="_blank" class="admin-card">
                <div class="admin-icon icon-system">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">HAProxy Dashboard</h3>
                    <p class="admin-card-text">Load balancer monitoring</p>
                </div>
            </a>
            
            <a href="/admin/servicelist.txt" class="admin-card">
                <div class="admin-icon icon-system">
                    <i class="bi bi-list-check"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Service List</h3>
                    <p class="admin-card-text">View all system services</p>
                </div>
            </a>
            
            <a href="/staff/systemlinks" class="admin-card">
                <div class="admin-icon icon-system">
                    <i class="bi bi-link-45deg"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">System Links</h3>
                    <p class="admin-card-text">Quick access to system resources</p>
                </div>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($account->isstaff()): ?>
        <!-- Staff Resources Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">Staff Resources</h2>
            <p class="section-subtitle">Tools and resources for staff members</p>
        </div>
        
        <div class="admin-grid">
            <a href="/staff/" class="admin-card">
                <div class="admin-icon icon-help">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Your Staff Profile</h3>
                    <p class="admin-card-text">View and edit your staff profile</p>
                </div>
            </a>
            
            <a href="/hr/" class="admin-card">
                <div class="admin-icon icon-help">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Human Resources</h3>
                    <p class="admin-card-text">HR policies and resources</p>
                </div>
            </a>
            
            <a href="//chat.birthdaygold.cloud" target="_blank" class="admin-card">
                <div class="admin-icon icon-help">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">RocketChat</h3>
                    <p class="admin-card-text">Team communication platform</p>
                </div>
            </a>
            
            <a href="/admin/redirect_corporateholidays" target="_blank" class="admin-card">
                <div class="admin-icon icon-help">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Corporate Holidays</h3>
                    <p class="admin-card-text">View company holiday schedule</p>
                </div>
            </a>
            
            <a href="/roadmap" target="_blank" class="admin-card">
                <div class="admin-icon icon-help">
                    <i class="bi bi-signpost-split"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Roadmap</h3>
                    <p class="admin-card-text">Product development roadmap</p>
                </div>
            </a>
            
            <a href="//whimsical.com/birthday-gold-organization-chart-DLzWNLXvT4wTb8VHD2Q7TH@6HYTAunKLgTVs8vpi5WC98mUWW3PbpNxuKAVmjk196shQQP" target="_blank" class="admin-card">
                <div class="admin-icon icon-help">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Organization Chart</h3>
                    <p class="admin-card-text">Company organizational structure</p>
                </div>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($current_user_data['username'] == 'ddgconsultant'): ?>
        <!-- HR Management Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">HR Management</h2>
            <p class="section-subtitle">Human resources and payroll management</p>
        </div>
        
        <div class="admin-grid">
            <a href="/admin/hr_handler.php" class="admin-card">
                <div class="admin-icon icon-admin">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Payroll Management</h3>
                    <p class="admin-card-text">Process monthly payroll for contractors</p>
                </div>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($account->iscconsultant()): ?>
        <!-- Sales Team Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">Sales Team Tools</h2>
            <p class="section-subtitle">Resources for sales team members</p>
        </div>
        
        <div class="admin-grid">
            <a href="/staff/ccdashboard" class="admin-card">
                <div class="admin-icon icon-sales">
                    <i class="bi bi-speedometer"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Sales Dashboard</h3>
                    <p class="admin-card-text">View sales metrics and performance</p>
                </div>
            </a>
            
            <a href="/staff/companylogos" class="admin-card">
                <div class="admin-icon icon-sales">
                    <i class="bi bi-building"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Companies</h3>
                    <p class="admin-card-text">Manage company partnerships</p>
                </div>
            </a>
            
            <a href="/staff/timecards" class="admin-card">
                <div class="admin-icon icon-sales">
                    <i class="bi bi-stopwatch"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">TimeCards</h3>
                    <p class="admin-card-text">Track time and activities</p>
                </div>
            </a>
            
            <a href="/staff/unlock_timecards" class="admin-card">
                <div class="admin-icon icon-sales">
                    <i class="bi bi-unlock"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Unlock TimeCards</h3>
                    <p class="admin-card-text">Manage timecard permissions</p>
                </div>
            </a>
            
            <a href="/hr/form_onboarding" class="admin-card">
                <div class="admin-icon icon-sales">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Onboarding Forms</h3>
                    <p class="admin-card-text">Access new client onboarding</p>
                </div>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($account->isstaff('techops') || $account->isadmin()): ?>
        <!-- Tech Operations Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">Tech Operations</h2>
            <p class="section-subtitle">Developer tools and API management</p>
        </div>
        
        <div class="admin-grid">
            <a href="//swagger.birthday.gold/" target="_blank" class="admin-card">
                <div class="admin-icon icon-tech">
                    <i class="bi bi-code-square"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">API Swagger</h3>
                    <p class="admin-card-text">API documentation and testing</p>
                </div>
            </a>
            
            <a href="https://dev.birthday.gold/api/keygen" class="admin-card">
                <div class="admin-icon icon-tech">
                    <i class="bi bi-key"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">API Key Management</h3>
                    <p class="admin-card-text">Generate and manage API keys</p>
                </div>
            </a>
            
            <a href="/admin/formbuilder/" class="admin-card">
                <div class="admin-icon icon-tech">
                    <i class="bi bi-ui-checks"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">Form Builder</h3>
                    <p class="admin-card-text">Create custom forms</p>
                </div>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Development Servers (for consultants) -->
        <?php if ($account->iscconsultant()): ?>
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">Development Servers</h2>
            <p class="section-subtitle">Quick access to development environments</p>
        </div>
        
        <div class="admin-grid">
            <a href="//dev.birthday.gold/" class="admin-card">
                <div class="admin-icon icon-tech">
                    <i class="bi bi-hdd-network-fill"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">DEV4 Server</h3>
                    <p class="admin-card-text">Development environment 4</p>
                </div>
            </a>
            
            <a href="//dev5.birthday.gold/" class="admin-card">
                <div class="admin-icon icon-tech">
                    <i class="bi bi-hdd-network-fill"></i>
                </div>
                <div class="admin-content">
                    <h3 class="admin-card-title">DEV5 Server</h3>
                    <p class="admin-card-text">Development environment 5</p>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$footerattribute['postfooter'] = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("adminSearch");
    const adminCards = document.querySelectorAll(".admin-card");
    const sectionHeaders = document.querySelectorAll(".section-header");
    
    if (searchInput) {
        searchInput.addEventListener("input", function(e) {
            const searchTerm = e.target.value.toLowerCase();
            let sectionsWithVisibleCards = new Set();
            
            adminCards.forEach(card => {
                const title = card.querySelector(".admin-card-title").textContent.toLowerCase();
                const text = card.querySelector(".admin-card-text").textContent.toLowerCase();
                
                if (title.includes(searchTerm) || text.includes(searchTerm)) {
                    card.style.display = "";
                    // Find which section this card belongs to
                    let currentSection = card.closest(".admin-grid").previousElementSibling;
                    while (currentSection && !currentSection.classList.contains("section-header")) {
                        currentSection = currentSection.previousElementSibling;
                    }
                    if (currentSection) {
                        sectionsWithVisibleCards.add(currentSection);
                    }
                } else {
                    card.style.display = "none";
                }
            });
            
            // Show/hide section headers based on visible cards
            sectionHeaders.forEach(section => {
                if (searchTerm === "") {
                    section.style.display = "";
                } else {
                    section.style.display = sectionsWithVisibleCards.has(section) ? "" : "none";
                }
            });
            
            // Show/hide grids based on visible cards
            document.querySelectorAll(".admin-grid").forEach(grid => {
                const visibleCards = grid.querySelectorAll(".admin-card:not([style*=\"display: none\"])");
                grid.style.display = visibleCards.length > 0 || searchTerm === "" ? "" : "none";
            });
        });
        
        // Focus search input on page load (not on mobile)
        if (window.innerWidth > 767) {
            searchInput.focus();
        }
    }
    
    // Mobile collapsible sections
    if (window.innerWidth <= 767) {
        // Add collapsible functionality to section headers
        sectionHeaders.forEach((header, index) => {
            const grid = header.nextElementSibling;
            
            // Skip if not followed by admin-grid
            if (!grid || !grid.classList.contains(\'admin-grid\')) return;
            
            // Set initial state - collapse all except first section
            if (index > 0) {
                header.classList.add(\'collapsed\');
                grid.style.display = \'none\';
            }
            
            // Add click handler
            header.addEventListener(\'click\', function(e) {
                // Do not collapse if clicking on a link inside header
                if (e.target.tagName === \'A\') return;
                
                this.classList.toggle(\'collapsed\');
                const isCollapsed = this.classList.contains(\'collapsed\');
                grid.style.display = isCollapsed ? \'none\' : \'\';
                
                // Save state to localStorage
                const sectionTitle = this.querySelector(\'.section-title\').textContent;
                const storageKey = `admin-section-${sectionTitle.replace(/\s+/g, \'-\')}`;
                localStorage.setItem(storageKey, isCollapsed ? \'collapsed\' : \'expanded\');
            });
            
            // Restore saved state
            const sectionTitle = header.querySelector(\'.section-title\').textContent;
            const storageKey = `admin-section-${sectionTitle.replace(/\s+/g, \'-\')}`;
            const savedState = localStorage.getItem(storageKey);
            
            if (savedState === \'collapsed\') {
                header.classList.add(\'collapsed\');
                grid.style.display = \'none\';
            } else if (savedState === \'expanded\' && index > 0) {
                header.classList.remove(\'collapsed\');
                grid.style.display = \'\';
            }
        });
    } else {
        // On desktop, ensure all sections are visible and remove collapse styling
        sectionHeaders.forEach(header => {
            header.classList.remove(\'collapsed\');
            const grid = header.nextElementSibling;
            if (grid && grid.classList.contains(\'admin-grid\')) {
                grid.style.display = \'\';
            }
        });
    }
});
</script>
';

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>