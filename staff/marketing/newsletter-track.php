<?PHP
// Newsletter Click Tracking
// Tracks link clicks and redirects to destination

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get parameters
$campaign_id = isset($_GET['c']) ? $qik->decodeId($_GET['c']) : 0;
$user_id = isset($_GET['u']) ? $qik->decodeId($_GET['u']) : 0;
$brand_id = isset($_GET['b']) ? $qik->decodeId($_GET['b']) : 0;
$destination_url = isset($_GET['url']) ? urldecode($_GET['url']) : '';

// Validate parameters
if ($campaign_id > 0 && $user_id > 0) {
    // Log the click event
    $event_data = ['url' => $destination_url];
    
    if ($brand_id > 0) {
        $event_data['brand_id'] = $brand_id;
        $event_type = 'cta_click';
    } else {
        $event_type = 'click';
    }
    
    $log_sql = "INSERT INTO bg_newsletter_events 
               (campaign_id, user_id, event_type, event_dt, extra) 
               VALUES 
               (:campaign_id, :user_id, :event_type, NOW(), :extra)";
    
    $database->query($log_sql, [
        'campaign_id' => $campaign_id,
        'user_id' => $user_id,
        'event_type' => $event_type,
        'extra' => json_encode($event_data)
    ]);
}

// Redirect to destination or show error if no URL
if (!empty($destination_url) && filter_var($destination_url, FILTER_VALIDATE_URL)) {
    header('Location: ' . $destination_url);
    exit;
} else {
    // Show newsletter tracking analytics dashboard
    $addClasses[] = 'marketing';
    $addClasses[] = 'mail';
    $pagetitle = "Email Tracking";
    
    $additionalstyles = '
    <style>
    .metric-card {
        transition: transform 0.2s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
    }
    </style>
    ';
    
    // Get newsletter analytics data
    try {
        // Get campaigns with event counts
        $analytics_sql = "SELECT 
            c.campaign_id,
            c.campaign_name,
            c.subject_line,
            c.status,
            c.create_dt,
            c.send_dt,
            COUNT(DISTINCT q.user_id) as total_sent,
            COUNT(DISTINCT CASE WHEN e.event_type = 'open' THEN e.user_id END) as opens,
            COUNT(DISTINCT CASE WHEN e.event_type IN ('click', 'cta_click') THEN e.user_id END) as clicks,
            COUNT(CASE WHEN e.event_type = 'open' THEN 1 END) as total_opens,
            COUNT(CASE WHEN e.event_type IN ('click', 'cta_click') THEN 1 END) as total_clicks
            FROM bg_newsletter_campaigns c
            LEFT JOIN bg_newsletter_queue q ON c.campaign_id = q.campaign_id AND q.status = 'sent'
            LEFT JOIN bg_newsletter_events e ON c.campaign_id = e.campaign_id
            WHERE c.status IN ('completed', 'sending')
            GROUP BY c.campaign_id, c.campaign_name, c.subject_line, c.status, c.create_dt, c.send_dt
            ORDER BY c.send_dt DESC, c.create_dt DESC
            LIMIT 20";
            
        $campaigns = $database->getrows($analytics_sql);
        
        // Get overall stats
        $total_stats_sql = "SELECT 
            COUNT(DISTINCT c.campaign_id) as total_campaigns,
            COUNT(DISTINCT q.user_id) as total_recipients,
            COUNT(DISTINCT CASE WHEN e.event_type = 'open' THEN e.user_id END) as unique_opens,
            COUNT(DISTINCT CASE WHEN e.event_type IN ('click', 'cta_click') THEN e.user_id END) as unique_clicks,
            COUNT(CASE WHEN e.event_type = 'open' THEN 1 END) as total_opens,
            COUNT(CASE WHEN e.event_type IN ('click', 'cta_click') THEN 1 END) as total_clicks
            FROM bg_newsletter_campaigns c
            LEFT JOIN bg_newsletter_queue q ON c.campaign_id = q.campaign_id AND q.status = 'sent'
            LEFT JOIN bg_newsletter_events e ON c.campaign_id = e.campaign_id
            WHERE c.status IN ('completed', 'sending')";
            
        $overall_stats = $database->getrow($total_stats_sql);
        
    } catch (Exception $e) {
        $campaigns = [];
        $overall_stats = ['total_campaigns' => 0, 'total_recipients' => 0, 'unique_opens' => 0, 'unique_clicks' => 0];
    }
    
    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');
    
    echo '
    <div class="content-header-staff compact">
        <div class="container text-center">
            <h1><i class="bi bi-graph-up-arrow"></i> Email Tracking</h1>
            <p class="lead">Performance analytics and engagement metrics for email newsletters</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-bar-chart"></i> Email Performance Analytics</h2>
                    <div>
                        <a href="/staff/marketing/newsletter-report.php" class="btn btn-outline-primary">
                            <i class="bi bi-envelope-paper"></i> Newsletter Management
                        </a>
                        <a href="/staff/marketing/" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Marketing Hub
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Overall Metrics -->
        <div class="row mb-4">';
        
    $open_rate = $overall_stats['total_recipients'] > 0 ? 
        round(($overall_stats['unique_opens'] / $overall_stats['total_recipients']) * 100, 1) : 0;
    $click_rate = $overall_stats['total_recipients'] > 0 ? 
        round(($overall_stats['unique_clicks'] / $overall_stats['total_recipients']) * 100, 1) : 0;
    $ctr = $overall_stats['unique_opens'] > 0 ? 
        round(($overall_stats['unique_clicks'] / $overall_stats['unique_opens']) * 100, 1) : 0;
        
    echo '
            <div class="col-md-3">
                <div class="card bg-primary text-white metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-white-75">Total Newsletters</div>
                                <div class="h2 mb-0">' . number_format($overall_stats['total_campaigns']) . '</div>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-envelope-paper fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-white-75">Open Rate</div>
                                <div class="h2 mb-0">' . $open_rate . '%</div>
                                <div class="small">' . number_format($overall_stats['unique_opens']) . ' / ' . number_format($overall_stats['total_recipients']) . '</div>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-envelope-open fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-white-75">Click Rate</div>
                                <div class="h2 mb-0">' . $click_rate . '%</div>
                                <div class="small">' . number_format($overall_stats['unique_clicks']) . ' / ' . number_format($overall_stats['total_recipients']) . '</div>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-cursor-fill fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-white-75">Click-to-Open</div>
                                <div class="h2 mb-0">' . $ctr . '%</div>
                                <div class="small">Engagement Quality</div>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-graph-up fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Newsletter Details -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0"><i class="bi bi-list-task"></i> Recent Newsletter Performance</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Newsletter</th>
                                        <th>Subject Line</th>
                                        <th>Send Date</th>
                                        <th>Sent</th>
                                        <th>Opens</th>
                                        <th>Clicks</th>
                                        <th>Open Rate</th>
                                        <th>Click Rate</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>';
                                
    if (empty($campaigns)) {
        echo '
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox"></i> No newsletters found. 
                                            <a href="/staff/marketing/newsletter-edit.php">Create your first newsletter</a>
                                        </td>
                                    </tr>';
    } else {
        foreach ($campaigns as $campaign) {
            $camp_open_rate = $campaign['total_sent'] > 0 ? 
                round(($campaign['opens'] / $campaign['total_sent']) * 100, 1) : 0;
            $camp_click_rate = $campaign['total_sent'] > 0 ? 
                round(($campaign['clicks'] / $campaign['total_sent']) * 100, 1) : 0;
                
            $status_badge = '';
            switch($campaign['status']) {
                case 'completed': $status_badge = '<span class="badge bg-success">Completed</span>'; break;
                case 'sending': $status_badge = '<span class="badge bg-primary">Sending</span>'; break;
                default: $status_badge = '<span class="badge bg-secondary">' . ucfirst($campaign['status']) . '</span>';
            }
            
            echo '
                                    <tr>
                                        <td>
                                            <strong>' . htmlspecialchars($campaign['campaign_name']) . '</strong><br>
                                            <small class="text-muted">ID: ' . $campaign['campaign_id'] . '</small>
                                        </td>
                                        <td>' . htmlspecialchars($campaign['subject_line']) . '</td>
                                        <td>' . ($campaign['send_dt'] ? date('M j, Y g:i A', strtotime($campaign['send_dt'])) : 'Not sent') . '</td>
                                        <td><span class="badge bg-light text-dark">' . number_format($campaign['total_sent']) . '</span></td>
                                        <td>
                                            <span class="badge bg-success">' . number_format($campaign['opens']) . '</span>
                                            <small class="text-muted">(' . number_format($campaign['total_opens']) . ' total)</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">' . number_format($campaign['clicks']) . '</span>
                                            <small class="text-muted">(' . number_format($campaign['total_clicks']) . ' total)</small>
                                        </td>
                                        <td><strong>' . $camp_open_rate . '%</strong></td>
                                        <td><strong>' . $camp_click_rate . '%</strong></td>
                                        <td>
                                            <a href="newsletter-track.php?c=' . $qik->encodeId($campaign['campaign_id']) . '" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-graph-up"></i> Details
                                            </a>
                                        </td>
                                    </tr>';
        }
    }
    
    echo '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
    
    $display_footertype = 'min';
    include($dir['core_components'] . '/bg_footer.inc');
    $app->outputpage();
    exit;
}
?>