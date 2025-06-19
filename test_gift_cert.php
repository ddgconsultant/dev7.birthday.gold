<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if gift certificate products exist
$sql = "SELECT id, account_type, account_name, status, display_grouping_status 
        FROM bg_products 
        WHERE account_type = 'giftcertificate' 
        AND version = :version";

// Get the site's plan version
global $website;
$version = $website['plan_version'] ?? 'v7';

$results = $database->getrows($sql, ['version' => $version]);

echo "<h2>Gift Certificate Products (Version $version)</h2>";
if (empty($results)) {
    echo "<p>No gift certificate products found!</p>";
} else {
    echo "<pre>";
    print_r($results);
    echo "</pre>";
}

// Check all available account types
$sql2 = "SELECT DISTINCT account_type, COUNT(*) as count 
         FROM bg_products 
         WHERE version = :version 
         AND status = 'active'
         GROUP BY account_type";

$types = $database->getrows($sql2, ['version' => $version]);

echo "<h2>All Account Types (Version $version)</h2>";
echo "<pre>";
print_r($types);
echo "</pre>";

// Check if we need to create a gift certificate product
echo "<h2>Need to create gift certificate product?</h2>";
if (empty($results)) {
    echo "<p>YES - No gift certificate products exist. You need to:</p>";
    echo "<ol>";
    echo "<li>Go to /admin/plan_editor.php</li>";
    echo "<li>Create a new product with account_type = 'giftcertificate'</li>";
    echo "<li>Set appropriate pricing and features</li>";
    echo "<li>Make sure display_grouping_status is 'active'</li>";
    echo "</ol>";
}
?>