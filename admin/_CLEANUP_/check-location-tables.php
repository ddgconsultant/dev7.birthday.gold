<?php
require_once dirname(__FILE__) . '/../core/site-controller.php';

// Tables we're checking for
$tables_to_check = [
    'bg_company_locations',
    'bg_company_rewards',
    'bg_reward_types',
    'bg_location_contacts',
    'bg_location_policies', 
    'bg_company_policies',
    'bg_location_enrollment_settings',
    'bg_location_attributes'
];

echo "<h1>Location Editor Table Check</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Table Name</th><th>Exists?</th><th>Row Count</th></tr>";

foreach ($tables_to_check as $table) {
    try {
        $sql = "SELECT COUNT(*) FROM $table";
        $stmt = $database->query($sql);
        $count = $stmt->fetchColumn();
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td style='color: green;'>✓ Yes</td>";
        echo "<td>$count rows</td>";
        echo "</tr>";
    } catch (Exception $e) {
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td style='color: red;'>✗ No</td>";
        echo "<td>N/A</td>";
        echo "</tr>";
    }
}

echo "</table>";

// Check what columns exist in bg_company_locations
echo "<h2>bg_company_locations columns:</h2>";
try {
    $sql = "SHOW COLUMNS FROM bg_company_locations";
    $stmt = $database->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>