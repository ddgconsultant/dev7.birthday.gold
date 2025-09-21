<?PHP
// Export newsletter campaign report to CSV
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;

if ($campaign_id == 0) {
    die('Invalid campaign ID');
}

// Get campaign details
$campaign_sql = "SELECT * FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
$campaign = $database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);

if (!$campaign) {
    die('Campaign not found');
}

// Get detailed recipient data
$data_sql = "SELECT 
    u.email,
    u.first_name,
    u.last_name,
    u.city,
    q.status as delivery_status,
    q.scheduled_dt,
    q.processed_dt,
    (SELECT COUNT(*) FROM bg_newsletter_events WHERE campaign_id = q.campaign_id AND user_id = q.user_id AND event_type = 'open') as opens,
    (SELECT COUNT(*) FROM bg_newsletter_events WHERE campaign_id = q.campaign_id AND user_id = q.user_id AND event_type = 'click') as clicks,
    (SELECT COUNT(*) FROM bg_newsletter_events WHERE campaign_id = q.campaign_id AND user_id = q.user_id AND event_type = 'cta_click') as cta_clicks,
    (SELECT MIN(event_dt) FROM bg_newsletter_events WHERE campaign_id = q.campaign_id AND user_id = q.user_id AND event_type = 'open') as first_open,
    (SELECT MIN(event_dt) FROM bg_newsletter_events WHERE campaign_id = q.campaign_id AND user_id = q.user_id AND event_type = 'click') as first_click
FROM bg_newsletter_queue q
JOIN bg_users u ON q.user_id = u.user_id
WHERE q.campaign_id = :campaign_id
ORDER BY u.email";

$data = $database->getrows($data_sql, ['campaign_id' => $campaign_id]);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="campaign_' . $campaign_id . '_report_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write campaign summary
fputcsv($output, ['Campaign Report: ' . $campaign['title']]);
fputcsv($output, ['Subject: ' . $campaign['subject']]);
fputcsv($output, ['Sent: ' . $campaign['send_dt']]);
fputcsv($output, ['']);

// Write column headers
fputcsv($output, [
    'Email',
    'First Name',
    'Last Name',
    'City',
    'Delivery Status',
    'Scheduled',
    'Processed',
    'Opens',
    'Clicks',
    'CTA Clicks',
    'First Open',
    'First Click'
]);

// Write data rows
foreach ($data as $row) {
    fputcsv($output, [
        $row['email'],
        $row['first_name'],
        $row['last_name'],
        $row['city'],
        $row['delivery_status'],
        $row['scheduled_dt'],
        $row['processed_dt'],
        $row['opens'],
        $row['clicks'],
        $row['cta_clicks'],
        $row['first_open'],
        $row['first_click']
    ]);
}

// Write summary stats
fputcsv($output, ['']);
fputcsv($output, ['Summary Statistics']);
fputcsv($output, ['Total Recipients', count($data)]);

$sent_count = count(array_filter($data, function($r) { return $r['delivery_status'] == 'sent'; }));
$open_count = count(array_filter($data, function($r) { return $r['opens'] > 0; }));
$click_count = count(array_filter($data, function($r) { return $r['clicks'] > 0; }));
$cta_click_count = count(array_filter($data, function($r) { return $r['cta_clicks'] > 0; }));

fputcsv($output, ['Sent', $sent_count]);
fputcsv($output, ['Unique Opens', $open_count]);
fputcsv($output, ['Open Rate', $sent_count > 0 ? round(($open_count / $sent_count) * 100, 1) . '%' : '0%']);
fputcsv($output, ['Unique Clicks', $click_count]);
fputcsv($output, ['Click Rate', $sent_count > 0 ? round(($click_count / $sent_count) * 100, 1) . '%' : '0%']);
fputcsv($output, ['CTA Clicks', $cta_click_count]);
fputcsv($output, ['CTA Rate', $sent_count > 0 ? round(($cta_click_count / $sent_count) * 100, 1) . '%' : '0%']);

fclose($output);
exit;
?>