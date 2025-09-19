<?php
/**
 * Check and add newsletter-specific fields to mk_campaigns
 */

include('../core/site-controller.php');

echo "<pre>";
echo "=== Checking mk_campaigns for Newsletter Fields ===\n\n";

if (!isset($database)) {
    die("ERROR: Database not available\n");
}

// Fields we need for newsletters in mk_campaigns
$required_fields = [
    'email_subject' => "VARCHAR(255) NULL COMMENT 'Email subject line for newsletters'",
    'cta_category' => "VARCHAR(100) NULL COMMENT 'CTA block category'",
    'cta_mode' => "VARCHAR(20) DEFAULT 'inclusive' COMMENT 'CTA selection mode'",
    'recipient_criteria' => "TEXT NULL COMMENT 'JSON recipient criteria'",
    'newsletter_status' => "VARCHAR(20) DEFAULT 'draft' COMMENT 'Newsletter status'"
];

echo "Checking mk_campaigns table structure...\n\n";

// Check existing columns
$existing_cols = [];
try {
    $cols = $database->query("SHOW COLUMNS FROM mk_campaigns");
    while ($col = $cols->fetch(PDO::FETCH_ASSOC)) {
        $existing_cols[$col['Field']] = $col['Type'];
        if (in_array($col['Field'], ['campaign_content', 'campaign_type', 'gen_specific_messaging', 'email_subject', 'cta_category', 'cta_mode', 'recipient_criteria', 'newsletter_status'])) {
            echo "✓ Found: " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
} catch (Exception $e) {
    die("ERROR reading mk_campaigns: " . $e->getMessage());
}

echo "\n";

// Add missing fields
foreach ($required_fields as $field => $definition) {
    if (!isset($existing_cols[$field])) {
        echo "Adding $field...\n";
        try {
            $sql = "ALTER TABLE mk_campaigns ADD COLUMN $field $definition";
            $database->query($sql);
            echo "  ✓ Added $field\n";
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== Field Mapping ===\n";
echo "bg_newsletter_campaigns → mk_campaigns\n";
echo "----------------------------------------\n";
echo "title                → campaign_name\n";
echo "subject              → email_subject\n";
echo "body_html            → campaign_content\n";
echo "cta_category         → cta_category\n";
echo "cta_mode             → cta_mode\n";
echo "gen_specific_messaging → gen_specific_messaging\n";
echo "recipient_criteria   → recipient_criteria\n";
echo "send_dt              → start_date\n";
echo "status               → newsletter_status\n";
echo "created_by           → create_by\n";
echo "created_dt           → create_dt\n";

echo "\n=== Testing Newsletter Storage ===\n";

// Test creating a newsletter in mk_campaigns
try {
    $test_data = [
        'company_id' => 1,
        'campaign_name' => 'Test Newsletter ' . date('Y-m-d H:i:s'),
        'campaign_type' => 'newsletter',
        'email_subject' => 'Test Subject',
        'campaign_content' => '<p>Test content</p>',
        'cta_category' => 'food',
        'cta_mode' => 'inclusive',
        'gen_specific_messaging' => 1,
        'recipient_criteria' => '{"type":"all"}',
        'newsletter_status' => 'draft',
        'status' => 'draft',
        'create_by' => 1,
        'create_dt' => date('Y-m-d H:i:s')
    ];
    
    // Build insert query
    $fields = array_keys($test_data);
    $placeholders = array_map(function($f) { return ":$f"; }, $fields);
    
    $sql = "INSERT INTO mk_campaigns (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $database->query($sql, $test_data);
    $test_id = $database->lastInsertId();
    
    echo "✓ Successfully created test newsletter with ID: $test_id\n";
    
    // Read it back
    $read_back = $database->getrow("SELECT * FROM mk_campaigns WHERE campaign_id = :id", ['id' => $test_id]);
    if ($read_back) {
        echo "✓ Can read back newsletter data\n";
        echo "  - gen_specific_messaging: " . $read_back['gen_specific_messaging'] . "\n";
        
        // Clean up test
        $database->query("DELETE FROM mk_campaigns WHERE campaign_id = :id", ['id' => $test_id]);
        echo "✓ Test record cleaned up\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error testing: " . $e->getMessage() . "\n";
}

echo "\n=== mk_campaigns is Ready for Newsletters! ===\n";
echo "</pre>";
?>