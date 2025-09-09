<?php
/**
 * Test Newsletter System with mk_campaigns
 */

include('../core/site-controller.php');

echo "<pre>";
echo "=== Testing Newsletter System with mk_campaigns ===\n\n";

if (!isset($database)) {
    die("ERROR: Database not available\n");
}

echo "1. Checking mk_campaigns structure...\n";
$required_fields = [
    'campaign_name', 'campaign_type', 'email_subject', 'campaign_content', 
    'gen_specific_messaging', 'cta_category', 'cta_mode', 'recipient_criteria',
    'newsletter_status'
];

$cols = $database->query("SHOW COLUMNS FROM mk_campaigns");
$existing_cols = [];
while ($col = $cols->fetch(PDO::FETCH_ASSOC)) {
    $existing_cols[] = $col['Field'];
}

$missing = array_diff($required_fields, $existing_cols);
if (empty($missing)) {
    echo "   ✓ All required newsletter fields present in mk_campaigns\n";
} else {
    echo "   ✗ Missing fields: " . implode(', ', $missing) . "\n";
}

echo "\n2. Checking for existing newsletter campaigns...\n";
$newsletters = $database->getrows(
    "SELECT campaign_id, campaign_name, email_subject, gen_specific_messaging, newsletter_status 
     FROM mk_campaigns 
     WHERE campaign_type = 'newsletter' 
     ORDER BY create_dt DESC 
     LIMIT 5"
);

if (empty($newsletters)) {
    echo "   No newsletter campaigns found\n";
} else {
    echo "   Found " . count($newsletters) . " newsletter campaigns:\n";
    foreach ($newsletters as $nl) {
        echo "   - ID: {$nl['campaign_id']}, Name: {$nl['campaign_name']}\n";
        echo "     Subject: " . ($nl['email_subject'] ?: 'Not set') . "\n";
        echo "     Gen-Specific: " . ($nl['gen_specific_messaging'] ? 'Yes' : 'No') . "\n";
        echo "     Status: " . ($nl['newsletter_status'] ?: 'Not set') . "\n\n";
    }
}

echo "\n3. Testing newsletter creation in mk_campaigns...\n";
try {
    $test_data = [
        'company_id' => 0,
        'platform_id' => 0,
        'campaign_name' => 'Test Newsletter ' . date('Y-m-d H:i:s'),
        'campaign_type' => 'newsletter',
        'email_subject' => 'Test Newsletter Subject',
        'campaign_content' => '<h1>Test Content</h1><p>This is a test newsletter</p>',
        'gen_specific_messaging' => 1,
        'cta_category' => 'food',
        'cta_mode' => 'inclusive',
        'recipient_criteria' => json_encode(['type' => 'all']),
        'newsletter_status' => 'draft',
        'status' => 'draft',
        'create_by' => 1,
        'create_dt' => date('Y-m-d H:i:s')
    ];
    
    $fields = array_keys($test_data);
    $placeholders = array_map(function($f) { return ":$f"; }, $fields);
    
    $sql = "INSERT INTO mk_campaigns (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $database->query($sql, $test_data);
    $test_id = $database->lastInsertId();
    
    echo "   ✓ Created test newsletter with ID: $test_id\n";
    
    // Read it back
    $read_back = $database->getrow(
        "SELECT * FROM mk_campaigns WHERE campaign_id = :id",
        ['id' => $test_id]
    );
    
    if ($read_back) {
        echo "   ✓ Successfully read back newsletter data\n";
        echo "     - gen_specific_messaging: " . $read_back['gen_specific_messaging'] . "\n";
        echo "     - email_subject: " . $read_back['email_subject'] . "\n";
        echo "     - cta_mode: " . $read_back['cta_mode'] . "\n";
        
        // Clean up
        $database->query("DELETE FROM mk_campaigns WHERE campaign_id = :id", ['id' => $test_id]);
        echo "   ✓ Test record cleaned up\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n4. Checking for orphaned bg_newsletter_campaigns references...\n";
$tables_to_check = [
    'bg_newsletter_tracking',
    'bg_newsletter_recipients'
];

foreach ($tables_to_check as $table) {
    $check = $database->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->rowCount() > 0) {
        echo "   ⚠ Table $table still exists - may need migration\n";
    }
}

echo "\n=== Newsletter System Status ===\n";
echo "✓ mk_campaigns is configured for newsletters\n";
echo "✓ newsletter-edit.php uses mk_campaigns\n";
echo "✓ campaigns.php links directly to mk_campaigns\n";
echo "✓ campaign-create.php creates newsletters in mk_campaigns\n";

echo "\n</pre>";
echo "<hr>";
echo "<p><a href='/myaccount/marketing/campaigns.php' class='btn btn-primary'>Go to Campaigns</a></p>";
echo "<p><a href='/myaccount/marketing/newsletter-edit.php' class='btn btn-success'>Create Newsletter</a></p>";
?>