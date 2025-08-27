<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting...<br>";

// Include site controller
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "Site controller loaded<br>";

$pagetitle = "Legal Policy Editor Debug";

// Simple test query
echo "Testing database...<br>";
try {
    $sql = "SELECT COUNT(*) as count FROM bg_content WHERE status = 'active'";
    $result = $database->getrow($sql);
    echo "Database works. Active content count: " . ($result['count'] ?? 'null') . "<br>";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Now test the actual query
echo "Testing policy query...<br>";
try {
    $policies_sql = "SELECT id, name, display_name, category, type, version, modify_dt, status
                     FROM bg_content 
                     WHERE `grouping` = 'legal' AND status = 'active' 
                     ORDER BY category, display_name";
    $all_policies = $database->getrows($policies_sql);
    echo "Found " . count($all_policies) . " policies<br>";
} catch (Exception $e) {
    echo "Policy query error: " . $e->getMessage() . "<br>";
}

echo "Including page components...<br>";
include($dir['core_components'] . '/bg_pagestart.inc');
echo "Pagestart included<br>";
include($dir['core_components'] . '/bg_header.inc');
echo "Header included<br>";

echo '<div class="container my-5">
<h1>Legal Policy Editor - Debug Mode</h1>
<p>If you see this, the page is working.</p>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>