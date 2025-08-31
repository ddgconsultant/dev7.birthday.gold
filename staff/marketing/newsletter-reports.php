<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($campaign_id == 0) {
    header('Location: /staff/newsletter-list.php');
    exit;
}

// Get campaign details
$campaign_sql = "SELECT c.*, u.first_name, u.last_name
                FROM bg_newsletter_campaigns c
                LEFT JOIN bg_users u ON c.created_by = u.user_id
                WHERE c.campaign_id = :campaign_id";

$campaign = $database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);

if (!$campaign) {
    header('Location: /staff/newsletter-list.php');
    exit;
}

$pagetitle = "Campaign Report: " . $campaign['title'];

// Get overall stats - use unique parameter names for each occurrence
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM bg_newsletter_queue WHERE campaign_id = :cid1) as total_recipients,
    (SELECT COUNT(*) FROM bg_newsletter_queue WHERE campaign_id = :cid2 AND status = 'sent') as sent_count,
    (SELECT COUNT(*) FROM bg_newsletter_queue WHERE campaign_id = :cid3 AND status = 'error') as error_count,
    (SELECT COUNT(*) FROM bg_newsletter_queue WHERE campaign_id = :cid4 AND status = 'pending') as pending_count,
    (SELECT COUNT(DISTINCT user_id) FROM bg_newsletter_events WHERE campaign_id = :cid5 AND event_type = 'open') as unique_opens,
    (SELECT COUNT(*) FROM bg_newsletter_events WHERE campaign_id = :cid6 AND event_type = 'open') as total_opens,
    (SELECT COUNT(DISTINCT user_id) FROM bg_newsletter_events WHERE campaign_id = :cid7 AND event_type = 'click') as unique_clicks,
    (SELECT COUNT(*) FROM bg_newsletter_events WHERE campaign_id = :cid8 AND event_type = 'click') as total_clicks,
    (SELECT COUNT(DISTINCT user_id) FROM bg_newsletter_events WHERE campaign_id = :cid9 AND event_type = 'cta_click') as cta_clicks,
    (SELECT COUNT(*) FROM bg_newsletter_events WHERE campaign_id = :cid10 AND event_type = 'unsubscribe') as unsubscribes";

$stats = $database->getrow($stats_sql, [
    'cid1' => $campaign_id,
    'cid2' => $campaign_id,
    'cid3' => $campaign_id,
    'cid4' => $campaign_id,
    'cid5' => $campaign_id,
    'cid6' => $campaign_id,
    'cid7' => $campaign_id,
    'cid8' => $campaign_id,
    'cid9' => $campaign_id,
    'cid10' => $campaign_id
]);

// Calculate rates
$open_rate = $stats['sent_count'] > 0 ? round(($stats['unique_opens'] / $stats['sent_count']) * 100, 1) : 0;
$click_rate = $stats['sent_count'] > 0 ? round(($stats['unique_clicks'] / $stats['sent_count']) * 100, 1) : 0;
$cta_rate = $stats['sent_count'] > 0 ? round(($stats['cta_clicks'] / $stats['sent_count']) * 100, 1) : 0;

// Get CTA performance
$cta_sql = "SELECT 
    c.company_name,
    c.company_id,
    COUNT(DISTINCT l.user_id) as impressions,
    COUNT(DISTINCT e.user_id) as clicks
FROM bg_newsletter_cta_log l
LEFT JOIN bg_companies c ON l.brand_id = c.company_id
LEFT JOIN bg_newsletter_events e ON e.campaign_id = l.campaign_id 
    AND e.user_id = l.user_id 
    AND e.event_type = 'cta_click' 
    AND JSON_EXTRACT(e.extra, '$.brand_id') = l.brand_id
WHERE l.campaign_id = :campaign_id
GROUP BY c.company_id
ORDER BY clicks DESC, impressions DESC";

$cta_performance = $database->getrows($cta_sql, ['campaign_id' => $campaign_id]);

// Get hourly performance
$hourly_sql = "SELECT 
    HOUR(event_dt) as hour,
    COUNT(CASE WHEN event_type = 'open' THEN 1 END) as opens,
    COUNT(CASE WHEN event_type IN ('click', 'cta_click') THEN 1 END) as clicks
FROM bg_newsletter_events
WHERE campaign_id = :campaign_id
GROUP BY HOUR(event_dt)
ORDER BY hour";

$hourly_data = $database->getrows($hourly_sql, ['campaign_id' => $campaign_id]);

$additionalscripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js'
];

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Calculate key metrics for header display
$delivery_rate = $stats['total_recipients'] > 0 ? round(($stats['sent_count'] / $stats['total_recipients']) * 100, 1) : 0;

echo '
<div class="content-header-staff">
    <div class="container text-center">
        <h1><i class="fas fa-envelope"></i> Newsletter System</h1>
        <p class="lead">' . htmlspecialchars($campaign['title']) . '</p>
        <div class="stats">
            <div class="stat-item">
                <span class="stat-number">' . number_format($stats['sent_count']) . '</span>
                <span class="stat-label">Emails Sent</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">' . $open_rate . '%</span>
                <span class="stat-label">Open Rate</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">' . $click_rate . '%</span>
                <span class="stat-label">Click Rate</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">' . $cta_rate . '%</span>
                <span class="stat-label">CTA Engagement</span>
            </div>
        </div>
    </div>
</div>';

// Include navigation
include('../includes/newsletter-nav.php');

echo '
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col">
            <h3>Report Details</h3>
        </div>
        <div class="col-auto">
            <button onclick="exportReport()" class="btn btn-primary">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
    </div>
    
    <!-- Campaign Info -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Subject:</strong><br>
                    ' . htmlspecialchars($campaign['subject']) . '
                </div>
                <div class="col-md-3">
                    <strong>Category:</strong><br>
                    <span class="badge bg-secondary">' . htmlspecialchars($campaign['cta_category']) . '</span>
                </div>
                <div class="col-md-3">
                    <strong>Sent:</strong><br>
                    ' . date('M j, Y g:i A', strtotime($campaign['send_dt'])) . '
                </div>
                <div class="col-md-3">
                    <strong>Created By:</strong><br>
                    ' . htmlspecialchars($campaign['first_name'] . ' ' . $campaign['last_name']) . '
                </div>
            </div>
        </div>
    </div>
    
    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Sent</h5>
                    <h2 class="text-primary">' . number_format($stats['sent_count']) . '</h2>
                    <small class="text-muted">of ' . number_format($stats['total_recipients']) . ' total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Opens</h5>
                    <h2 class="text-success">' . number_format($stats['unique_opens']) . '</h2>
                    <small class="text-muted">' . $open_rate . '% open rate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Clicks</h5>
                    <h2 class="text-info">' . number_format($stats['unique_clicks']) . '</h2>
                    <small class="text-muted">' . $click_rate . '% click rate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">CTA Clicks</h5>
                    <h2 class="text-warning">' . number_format($stats['cta_clicks']) . '</h2>
                    <small class="text-muted">' . $cta_rate . '% CTA rate</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Engagement Over Time -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Engagement Timeline</h5>
        </div>
        <div class="card-body">
            <canvas id="engagementChart" height="100"></canvas>
        </div>
    </div>
    
    <!-- CTA Performance -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">CTA Brand Performance</h5>
        </div>
        <div class="card-body">';

if (empty($cta_performance)) {
    echo '<p class="text-muted">No CTA data available yet.</p>';
} else {
    echo '
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <th>Impressions</th>
                            <th>Clicks</th>
                            <th>CTR</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($cta_performance as $cta) {
        $ctr = $cta['impressions'] > 0 ? round(($cta['clicks'] / $cta['impressions']) * 100, 1) : 0;
        echo '
                        <tr>
                            <td>' . htmlspecialchars($cta['company_name']) . '</td>
                            <td>' . number_format($cta['impressions']) . '</td>
                            <td>' . number_format($cta['clicks']) . '</td>
                            <td>' . $ctr . '%</td>
                        </tr>';
    }
    
    echo '
                    </tbody>
                </table>
            </div>';
}

echo '
        </div>
    </div>
    
    <!-- Additional Stats -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Delivery Stats</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Successful:</strong> 
                        <span class="float-end">' . number_format($stats['sent_count']) . '</span>
                    </div>
                    <div class="mb-2">
                        <strong>Errors:</strong> 
                        <span class="float-end text-danger">' . number_format($stats['error_count']) . '</span>
                    </div>
                    <div class="mb-2">
                        <strong>Pending:</strong> 
                        <span class="float-end text-warning">' . number_format($stats['pending_count']) . '</span>
                    </div>
                    <div>
                        <strong>Unsubscribes:</strong> 
                        <span class="float-end">' . number_format($stats['unsubscribes']) . '</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Engagement Stats</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Total Opens:</strong> 
                        <span class="float-end">' . number_format($stats['total_opens']) . '</span>
                    </div>
                    <div class="mb-2">
                        <strong>Unique Opens:</strong> 
                        <span class="float-end">' . number_format($stats['unique_opens']) . '</span>
                    </div>
                    <div class="mb-2">
                        <strong>Total Clicks:</strong> 
                        <span class="float-end">' . number_format($stats['total_clicks']) . '</span>
                    </div>
                    <div>
                        <strong>Unique Clicks:</strong> 
                        <span class="float-end">' . number_format($stats['unique_clicks']) . '</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Prepare chart data
var hourlyData = ' . json_encode($hourly_data) . ';
var hours = [];
var opens = [];
var clicks = [];

// Fill in all 24 hours
for (var i = 0; i < 24; i++) {
    hours.push(i + ":00");
    opens.push(0);
    clicks.push(0);
}

// Populate with actual data
hourlyData.forEach(function(item) {
    opens[item.hour] = item.opens;
    clicks[item.hour] = item.clicks;
});

// Create chart
var ctx = document.getElementById("engagementChart").getContext("2d");
var chart = new Chart(ctx, {
    type: "line",
    data: {
        labels: hours,
        datasets: [{
            label: "Opens",
            data: opens,
            borderColor: "rgb(75, 192, 192)",
            backgroundColor: "rgba(75, 192, 192, 0.2)",
            tension: 0.4
        }, {
            label: "Clicks",
            data: clicks,
            borderColor: "rgb(255, 99, 132)",
            backgroundColor: "rgba(255, 99, 132, 0.2)",
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function exportReport() {
    window.location.href = "/staff/ajax/newsletter-export.php?campaign_id=' . $campaign_id . '";
}
</script>';

include($dir['core_components'] . '/bg_footer.inc');

$app->outputpage();
?>