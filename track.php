<?php
// Campaign Click Tracking Handler
// Tracks campaign link clicks and redirects based on campaign configuration

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get tracking parameters
$campaign_id = 0;
$path_info = $_SERVER['PATH_INFO'] ?? '';

// Parse campaign ID from URL path (/track/campaign/{encoded_id} or /track/{encoded_id})
if (preg_match('/\/(?:campaign\/)?([^\/]+)$/', $path_info, $matches)) {
    $encoded_id = $matches[1];
    $decoded_id = base64_decode($encoded_id);
    
    // Handle both new campaign IDs and old temp IDs
    if (is_numeric($decoded_id)) {
        $campaign_id = intval($decoded_id);
    } elseif (preg_match('/new_\d+_\d+/', $decoded_id)) {
        // Handle temp IDs during campaign creation
        header('Location: /myaccount/marketing/campaigns.php');
        exit;
    }
}

// Get additional tracking parameters
$source = trim($_GET['source'] ?? ''); // utm_source equivalent
$medium = trim($_GET['medium'] ?? ''); // utm_medium equivalent  
$content = trim($_GET['content'] ?? ''); // utm_content equivalent
$term = trim($_GET['term'] ?? ''); // utm_term equivalent

if (!$campaign_id) {
    // Invalid campaign ID - redirect to homepage
    header('Location: /');
    exit;
}

// Get campaign details
$campaign_sql = "SELECT c.*, p.platform_name, p.platform_type 
                 FROM mk_campaigns c
                 LEFT JOIN mk_platforms p ON c.platform_id = p.platform_id
                 WHERE c.campaign_id = :campaign_id AND c.status != 'archived'";
$campaign = $database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);

if (!$campaign) {
    // Campaign not found - redirect to homepage
    header('Location: /');
    exit;
}

// Collect comprehensive tracking data
$tracking_data = [
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'referer' => $_SERVER['HTTP_REFERER'] ?? '',
    'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
    'utm_source' => $source,
    'utm_medium' => $medium,
    'utm_content' => $content,
    'utm_term' => $term,
    'platform_name' => $campaign['platform_name'],
    'platform_type' => $campaign['platform_type'],
    'click_timestamp' => date('Y-m-d H:i:s'),
    'session_id' => session_id()
];

// Log the click event to mk_metrics table
$metrics_sql = "INSERT INTO mk_metrics 
               (company_id, campaign_id, platform_id, metric_date, metric_type, 
                metric_value, external_source, metadata, create_dt) 
               VALUES 
               (:company_id, :campaign_id, :platform_id, CURDATE(), 'clicks', 
                1, :external_source, :metadata, NOW())
               ON DUPLICATE KEY UPDATE 
               metric_value = metric_value + 1, 
               modify_dt = NOW()";

$database->query($metrics_sql, [
    'company_id' => $campaign['company_id'],
    'campaign_id' => $campaign_id,
    'platform_id' => $campaign['platform_id'],
    'external_source' => $campaign['platform_type'],
    'metadata' => json_encode($tracking_data)
]);

// Also log detailed click event to activities table for audit trail
$activity_sql = "INSERT INTO mk_activities 
                 (company_id, create_by, activity_type, activity_title, activity_description,
                  related_campaign_id, related_platform_id, activity_date, metadata) 
                 VALUES 
                 (:company_id, 0, 'campaign_click', 'Campaign Click Tracked', 
                  'Click tracked for campaign', :campaign_id, :platform_id, NOW(), :metadata)";

$database->query($activity_sql, [
    'company_id' => $campaign['company_id'],
    'campaign_id' => $campaign_id,
    'platform_id' => $campaign['platform_id'],
    'metadata' => json_encode($tracking_data)
]);

// Determine redirect destination based on campaign configuration
$tracking_action = $campaign['tracking_link_action'] ?? 'forward_signup';

switch ($tracking_action) {
    case 'forward_url':
        $destination = $campaign['destination_url'];
        if (!empty($destination) && filter_var($destination, FILTER_VALIDATE_URL)) {
            header('Location: ' . $destination);
            exit;
        }
        // Fall through to signup if URL is invalid
        
    case 'forward_campaign':
        if (!empty($campaign['destination_campaign_id'])) {
            // Redirect to another campaign's tracking URL
            $dest_campaign = $database->getrow(
                "SELECT generated_tracking_url FROM mk_campaigns WHERE campaign_id = :id",
                ['id' => $campaign['destination_campaign_id']]
            );
            if (!empty($dest_campaign['generated_tracking_url'])) {
                header('Location: ' . $dest_campaign['generated_tracking_url']);
                exit;
            }
        }
        // Fall through to signup if campaign chaining fails
        
    case 'forward_signup':
    default:
        // Add campaign tracking to signup URL
        $signup_params = [
            'ref_campaign' => $campaign_id,
            'utm_source' => $source ?: $campaign['platform_name'],
            'utm_medium' => $medium ?: $campaign['platform_type'],
            'utm_campaign' => $campaign['campaign_name']
        ];
        
        $signup_url = '/signup?' . http_build_query(array_filter($signup_params));
        header('Location: ' . $signup_url);
        exit;
        
    case 'track_only':
        // Show confirmation page with campaign details
        $pagetitle = "Click Tracked";
        $additionalstyles = '
        <style>
        .tracking-confirmation {
            max-width: 600px;
            margin: 3rem auto;
            text-align: center;
        }
        </style>
        ';
        
        include($dir['core_components'] . '/bg_pagestart.inc');
        include($dir['core_components'] . '/bg_header.inc');
        
        echo '
        <div class="container">
            <div class="tracking-confirmation">
                <div class="card">
                    <div class="card-body">
                        <i class="bi bi-check-circle text-success display-4 mb-3"></i>
                        <h3>Click Tracked Successfully</h3>
                        <p class="text-muted">Your interaction with this campaign has been recorded.</p>
                        <div class="mt-4">
                            <h6 class="text-muted">Campaign Details</h6>
                            <p><strong>' . htmlspecialchars($campaign['campaign_name']) . '</strong><br>
                            <small class="text-muted">' . htmlspecialchars($campaign['platform_name']) . '</small></p>
                        </div>
                        <div class="mt-4">
                            <a href="/" class="btn btn-primary">Continue to Birthday.Gold</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        include($dir['core_components'] . '/bg_footer.inc');
        $app->outputpage();
        exit;
}

// Fallback to homepage if something goes wrong
header('Location: /');
exit;
?>