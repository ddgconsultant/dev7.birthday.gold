<?PHP
$addClasses[] = 'marketing';
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Hub";

// Get overview statistics
$stats = [];

// Newsletter stats - with error handling for missing tables
try {
    $newsletter_stats_sql = "SELECT 
        COUNT(*) as total_campaigns,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_campaigns,
        SUM(CASE WHEN status IN ('scheduled', 'queued') THEN 1 ELSE 0 END) as queued_campaigns,
        SUM(CASE WHEN status = 'sending' THEN 1 ELSE 0 END) as active_campaigns,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_campaigns
        FROM bg_newsletter_campaigns 
        WHERE create_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

    $newsletter_stats = $database->getrow($newsletter_stats_sql);
} catch (Exception $e) {
    // Tables don't exist yet - use default values
    $newsletter_stats = [
        'total_campaigns' => 0,
        'draft_campaigns' => 0,
        'queued_campaigns' => 0,
        'active_campaigns' => 0,
        'completed_campaigns' => 0
    ];
}

// Queue stats - with error handling for missing tables
try {
    $queue_stats_sql = "SELECT 
        COUNT(*) as total_queued,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
        FROM bg_newsletter_queue 
        WHERE create_dt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

    $queue_stats = $database->getrow($queue_stats_sql);
} catch (Exception $e) {
    // Tables don't exist yet - use default values
    $queue_stats = [
        'total_queued' => 0,
        'pending' => 0,
        'sent' => 0,
        'errors' => 0
    ];
}

// Recent activity - with error handling
try {
    $recent_campaigns_sql = "SELECT campaign_id, title, subject, status, create_dt, send_dt 
        FROM bg_newsletter_campaigns 
        ORDER BY create_dt DESC 
        LIMIT 5";

    $recent_campaigns = $database->getrows($recent_campaigns_sql);
} catch (Exception $e) {
    $recent_campaigns = [];
}

// Upcoming scheduled campaigns - with error handling
try {
    $upcoming_campaigns_sql = "SELECT campaign_id, title, subject, send_dt 
        FROM bg_newsletter_campaigns 
        WHERE status IN ('scheduled', 'queued') 
        AND send_dt > NOW() 
        ORDER BY send_dt ASC 
        LIMIT 5";

    $upcoming_campaigns = $database->getrows($upcoming_campaigns_sql);
} catch (Exception $e) {
    $upcoming_campaigns = [];
}

// Handle status message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <?php if ($message): ?>
                <?= $message ?>
            <?php endif; ?>
            
            <?php if (empty($newsletter_stats) || $newsletter_stats['total_campaigns'] === 0): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Newsletter System Setup Required</strong><br>
                    The newsletter database tables need to be created. Please run the SQL schema file:
                    <code>/core/dbschema/newsletter_tables.sql</code>
                    <br><small class="text-muted">This will create the required tables for campaign management, queue processing, and analytics.</small>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1><i class="bi bi-megaphone"></i> Marketing Hub</h1>
                    <p class="text-muted mb-0">Unified platform for newsletters, campaigns, and marketing analytics</p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-plus-lg"></i> Quick Create
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="newsletter-edit.php">
                            <i class="bi bi-envelope"></i> New Newsletter Campaign
                        </a></li>
                        <li><a class="dropdown-item" href="marketing-edit.php">
                            <i class="bi bi-bullhorn"></i> New Marketing Campaign
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="marketing-analytics.php">
                            <i class="bi bi-graph-up"></i> View Analytics
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Quick Stats Overview -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-white-75 small">Total Campaigns (30 days)</div>
                                <div class="h3 mb-0"><?= $newsletter_stats['total_campaigns'] ?? 0 ?></div>
                                <small class="text-white-75">
                                    <?= ($newsletter_stats['draft_campaigns'] ?? 0) ?> drafts, 
                                    <?= ($newsletter_stats['completed_campaigns'] ?? 0) ?> completed
                                </small>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-envelope-paper fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-white-75 small">Queue Status (7 days)</div>
                                <div class="h3 mb-0"><?= $queue_stats['pending'] ?? 0 ?></div>
                                <small class="text-white-75">
                                    <?= ($queue_stats['sent'] ?? 0) ?> sent, 
                                    <?= ($queue_stats['errors'] ?? 0) ?> errors
                                </small>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-white-75 small">Active Campaigns</div>
                                <div class="h3 mb-0"><?= ($newsletter_stats['active_campaigns'] ?? 0) + ($newsletter_stats['queued_campaigns'] ?? 0) ?></div>
                                <small class="text-white-75">
                                    <?= $newsletter_stats['active_campaigns'] ?? 0 ?> sending, 
                                    <?= $newsletter_stats['queued_campaigns'] ?? 0 ?> queued
                                </small>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-send fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-white-75 small">Delivery Rate</div>
                                <div class="h3 mb-0">
                                    <?php 
                                    $total_attempted = ($queue_stats['sent'] ?? 0) + ($queue_stats['errors'] ?? 0);
                                    $delivery_rate = $total_attempted > 0 ? round((($queue_stats['sent'] ?? 0) / $total_attempted) * 100) : 0;
                                    echo $delivery_rate;
                                    ?>%
                                </div>
                                <small class="text-white-75">Last 7 days</small>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Navigation Grid -->
            <div class="row mb-4">
                <!-- Newsletter Campaigns Section -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-envelope-paper text-primary"></i> Newsletter Campaigns</h5>
                            <a href="index.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h4 mb-0 text-primary"><?= $newsletter_stats['draft_campaigns'] ?? 0 ?></div>
                                        <small class="text-muted">Drafts</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h4 mb-0 text-warning"><?= $newsletter_stats['queued_campaigns'] ?? 0 ?></div>
                                        <small class="text-muted">Queued</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h4 mb-0 text-success"><?= $newsletter_stats['active_campaigns'] ?? 0 ?></div>
                                        <small class="text-muted">Sending</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="newsletter-edit.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg"></i> Create Newsletter Campaign
                                </a>
                                <div class="btn-group">
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-list"></i> Manage Newsletters
                                    </a>
                                    <a href="newsletter-reports.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-graph-up"></i> Reports
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Marketing Campaigns Section -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-bullhorn text-warning"></i> Marketing Campaigns</h5>
                            <a href="marketing-campaigns.php" class="btn btn-sm btn-outline-warning">View All</a>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Manage cross-platform marketing campaigns, social media integration, and promotional activities.</p>
                            
                            <div class="d-grid gap-2">
                                <a href="marketing-edit.php" class="btn btn-warning">
                                    <i class="bi bi-plus-lg"></i> Create Marketing Campaign
                                </a>
                                <div class="btn-group">
                                    <a href="marketing-campaigns.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-list"></i> All Campaigns
                                    </a>
                                    <a href="marketing-platforms.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-share"></i> Platforms
                                    </a>
                                    <a href="marketing-view.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-eye"></i> Resources
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <!-- Analytics Section -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-graph-up text-success"></i> Analytics & Insights</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Track performance, engagement metrics, and ROI across all marketing channels.</p>
                            
                            <div class="d-grid gap-2">
                                <a href="marketing-analytics.php" class="btn btn-success">
                                    <i class="bi bi-bar-chart"></i> View Analytics Dashboard
                                </a>
                                <div class="btn-group">
                                    <a href="newsletter-track.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-cursor"></i> Email Tracking
                                    </a>
                                    <a href="engagement-reports.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-people"></i> Engagement
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Section -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-calendar3 text-info"></i> Marketing Calendar</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Plan and coordinate campaigns, schedule content, and track marketing activities.</p>
                            
                            <div class="d-grid gap-2">
                                <a href="marketing-calendar.php" class="btn btn-info">
                                    <i class="bi bi-calendar-plus"></i> Open Marketing Calendar
                                </a>
                                <div class="btn-group">
                                    <a href="schedule-campaign.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-clock"></i> Schedule
                                    </a>
                                    <a href="content-planner.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-journal-text"></i> Content Plan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Upcoming -->
            <div class="row">
                <!-- Recent Campaigns -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_campaigns)): ?>
                                <div class="text-center py-3">
                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">No recent campaigns</p>
                                    <a href="newsletter-edit.php" class="btn btn-sm btn-primary">Create First Campaign</a>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recent_campaigns as $campaign): ?>
                                        <div class="list-group-item border-0 px-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars($campaign['title']) ?></h6>
                                                    <p class="mb-1 small text-muted"><?= htmlspecialchars(substr($campaign['subject'], 0, 60)) ?><?= strlen($campaign['subject']) > 60 ? '...' : '' ?></p>
                                                    <small class="text-muted"><?= date('M j, Y g:i A', strtotime($campaign['create_dt'])) ?></small>
                                                </div>
                                                <div class="ms-2">
                                                    <?php
                                                    $status_class = [
                                                        'draft' => 'bg-secondary',
                                                        'scheduled' => 'bg-warning',
                                                        'queued' => 'bg-warning', 
                                                        'sending' => 'bg-primary',
                                                        'completed' => 'bg-success',
                                                        'paused' => 'bg-info',
                                                        'cancelled' => 'bg-danger'
                                                    ][$campaign['status']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?= $status_class ?>"><?= ucfirst($campaign['status']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Scheduled -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-calendar-check"></i> Upcoming Campaigns</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcoming_campaigns)): ?>
                                <div class="text-center py-3">
                                    <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">No scheduled campaigns</p>
                                    <a href="newsletter-edit.php" class="btn btn-sm btn-primary">Schedule Campaign</a>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($upcoming_campaigns as $campaign): ?>
                                        <div class="list-group-item border-0 px-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars($campaign['title']) ?></h6>
                                                    <p class="mb-1 small text-muted"><?= htmlspecialchars(substr($campaign['subject'], 0, 60)) ?><?= strlen($campaign['subject']) > 60 ? '...' : '' ?></p>
                                                </div>
                                                <div class="ms-2 text-end">
                                                    <div class="small text-muted">
                                                        <?= date('M j', strtotime($campaign['send_dt'])) ?>
                                                    </div>
                                                    <div class="small text-primary">
                                                        <?= date('g:i A', strtotime($campaign['send_dt'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();