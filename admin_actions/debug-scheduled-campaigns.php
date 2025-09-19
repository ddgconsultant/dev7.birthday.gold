<?php
/**
 * Debug scheduled campaigns
 */

include('../core/site-controller.php');

echo "<pre>";
echo "=== Debugging Scheduled Campaigns ===\n\n";

// Check all newsletter campaigns
echo "1. All newsletter campaigns:\n";
$campaigns = $database->getrows(
    "SELECT campaign_id, campaign_name, campaign_type, newsletter_status, 
            start_date, end_date, status, create_dt
     FROM mk_campaigns 
     WHERE campaign_type = 'newsletter'
     ORDER BY create_dt DESC"
);

if (empty($campaigns)) {
    echo "   No newsletter campaigns found\n";
} else {
    foreach ($campaigns as $c) {
        echo "   ID: {$c['campaign_id']}\n";
        echo "   Name: {$c['campaign_name']}\n";
        echo "   Type: {$c['campaign_type']}\n";
        echo "   Newsletter Status: {$c['newsletter_status']}\n";
        echo "   Campaign Status: {$c['status']}\n";
        echo "   Start Date: {$c['start_date']}\n";
        echo "   End Date: {$c['end_date']}\n";
        echo "   Created: {$c['create_dt']}\n";
        echo "   ---\n";
    }
}

echo "\n2. Campaigns that SHOULD be processed:\n";
$now = date('Y-m-d H:i:s');
echo "   Current time: $now\n\n";

$scheduled = $database->getrows(
    "SELECT campaign_id, campaign_name, newsletter_status, start_date
     FROM mk_campaigns 
     WHERE campaign_type = 'newsletter' 
     AND newsletter_status = 'scheduled'"
);

foreach ($scheduled as $s) {
    echo "   Campaign {$s['campaign_id']}: {$s['campaign_name']}\n";
    echo "   - newsletter_status: {$s['newsletter_status']}\n";
    echo "   - start_date: {$s['start_date']}\n";
    echo "   - start_date <= NOW()? " . ($s['start_date'] <= $now ? 'YES' : 'NO') . "\n";
    echo "   ---\n";
}

echo "\n3. Exact query the scheduler uses:\n";
$sql = "SELECT * FROM mk_campaigns 
        WHERE campaign_type = 'newsletter' 
        AND newsletter_status = 'scheduled' 
        AND start_date <= NOW()
        AND (end_date IS NULL OR end_date > NOW())";

echo "SQL: $sql\n\n";

$results = $database->getrows($sql);
echo "Results: " . count($results) . " campaigns found\n";

if (!empty($results)) {
    foreach ($results as $r) {
        echo "   - {$r['campaign_id']}: {$r['campaign_name']}\n";
    }
}

echo "\n4. Check for data type issues:\n";
$test = $database->getrow(
    "SELECT 
        campaign_id,
        campaign_type,
        newsletter_status,
        start_date,
        NOW() as now_time,
        CASE WHEN start_date <= NOW() THEN 'YES' ELSE 'NO' END as should_run
     FROM mk_campaigns 
     WHERE campaign_id = (
         SELECT MAX(campaign_id) 
         FROM mk_campaigns 
         WHERE campaign_type = 'newsletter'
     )"
);

if ($test) {
    echo "   Campaign ID: {$test['campaign_id']}\n";
    echo "   Type: '{$test['campaign_type']}' (length: " . strlen($test['campaign_type']) . ")\n";
    echo "   Newsletter Status: '{$test['newsletter_status']}' (length: " . strlen($test['newsletter_status']) . ")\n";
    echo "   Start Date: {$test['start_date']}\n";
    echo "   NOW(): {$test['now_time']}\n";
    echo "   Should Run: {$test['should_run']}\n";
}

echo "\n</pre>";
?>