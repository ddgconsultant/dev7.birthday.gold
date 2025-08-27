<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Starting<br>";

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "Step 2: Site controller loaded<br>";

$pagetitle = "Legal Policy Editor";

echo "Step 3: Testing query<br>";

$policies_sql = "SELECT id, name, display_name, category, type, version, modify_dt, status, tags, `grouping`
                 FROM bg_content 
                 WHERE (category = 'legal' OR category = 'Policies' OR `grouping` = 'legal') AND status = 'active' 
                 ORDER BY category, display_name";

echo "Step 4: Running query<br>";

try {
    $all_policies = $database->getrows($policies_sql);
    echo "Step 5: Got " . count($all_policies) . " policies<br>";
    
    // Show first policy details
    if (count($all_policies) > 0) {
        echo "First policy: <pre>";
        print_r($all_policies[0]);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "Query failed: " . $e->getMessage() . "<br>";
}

echo "Step 6: Processing policies<br>";

// Test the foreach loop that might be causing issues
foreach ($all_policies as &$pol) {
    echo "Processing policy: " . $pol['name'] . "<br>";
    $tags = json_decode($pol['tags'] ?? '{}', true);
    echo "Tags decoded<br>";
    $review_period = $tags['review_period'] ?? 180;
    echo "Review period: $review_period<br>";
    
    try {
        $modify_date = new DateTime($pol['modify_dt']);
        $current_date = new DateTime();
        $days_since = $current_date->diff($modify_date)->days;
        echo "Days since modified: $days_since<br>";
    } catch (Exception $e) {
        echo "DateTime error: " . $e->getMessage() . "<br>";
    }
    
    break; // Just test first one
}

echo "Step 7: Done with processing<br>";

echo "Step 8: Would include header next...<br>";
?>