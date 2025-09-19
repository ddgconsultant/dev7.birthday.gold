<?php
/**
 * Fix campaign mode for testing
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "Updating campaign 191 to exclusive mode for testing\n\n";

// Update the campaign
$update_sql = "UPDATE mk_campaigns SET cta_mode = 'exclusive' WHERE campaign_id = 191";
$database->query($update_sql);

echo "✓ Campaign updated to exclusive mode\n";

// Verify
$campaign = $database->getrow("SELECT cta_mode, cta_category FROM mk_campaigns WHERE campaign_id = 191");
echo "Current settings:\n";
echo "  - CTA Mode: " . $campaign['cta_mode'] . "\n";
echo "  - CTA Category: " . $campaign['cta_category'] . "\n";

echo "\nNote: Exclusive mode shows companies the user is NOT enrolled in.\n";
echo "This is better for test users who have no enrollments.\n";
?>