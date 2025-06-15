<?php
/**
 * Check the structure of bg_product_features table
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

echo "<h2>Table Structure Check</h2>\n";
echo "<pre>\n";

try {
    // Get table structure
    echo "=== bg_product_features table columns ===\n";
    $sql = "SHOW COLUMNS FROM bg_product_features";
    $columns = $database->getrows($sql);
    
    foreach ($columns as $column) {
        echo sprintf("%-20s %-20s %-10s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'],
            $column['Key']
        );
    }
    
    echo "\n=== Sample data from bg_product_features ===\n";
    $sql = "SELECT * FROM bg_product_features WHERE name = 'upgradeable' LIMIT 5";
    $features = $database->getrows($sql);
    
    if ($features) {
        // Show column names
        $firstRow = $features[0];
        echo implode(' | ', array_keys($firstRow)) . "\n";
        echo str_repeat('-', 100) . "\n";
        
        // Show data
        foreach ($features as $feature) {
            echo implode(' | ', array_values($feature)) . "\n";
        }
    } else {
        echo "No upgradeable features found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>