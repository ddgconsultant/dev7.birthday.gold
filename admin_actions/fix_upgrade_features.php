<?php
/**
 * Script to fix upgrade features that have single integer values instead of JSON arrays
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

// Start output
echo "<h2>Fixing Upgrade Features</h2>\n";
echo "<pre>\n";

try {
    // Find features with single integer values
    $sql = "SELECT pf.*, p.account_plan, p.account_name 
            FROM bg_product_features pf
            JOIN bg_products p ON p.id = pf.product_id
            WHERE pf.name = 'upgradeable'
            AND pf.status = 'active'";
    
    $features = $database->getrows($sql);
    
    echo "Found " . count($features) . " upgradeable features\n\n";
    
    foreach ($features as $feature) {
        $productName = $feature['account_name'] ?: '(No name)';
        echo "Checking: {$productName} (Plan: {$feature['account_plan']})\n";
        
        // Check if value is numeric (single integer)
        if (is_numeric($feature['value'])) {
            echo "  ⚠️ Found single integer value: {$feature['value']}\n";
            
            // Convert to JSON array
            $newValue = json_encode([(int)$feature['value']]);
            
            // Update the record
            $updateSql = "UPDATE bg_product_features 
                         SET value = :value
                         WHERE id = :id";
            
            $database->query($updateSql, [
                'value' => $newValue,
                'id' => $feature['id']
            ]);
            
            echo "  ✅ Fixed: converted to JSON array [{$feature['value']}]\n";
        } else {
            // Try to decode JSON
            $decoded = json_decode($feature['value'], true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                echo "  ❌ Invalid JSON: {$feature['value']}\n";
                echo "     Error: " . json_last_error_msg() . "\n";
            } else {
                echo "  ✓ Already a valid JSON array\n";
            }
        }
        echo "\n";
    }
    
    echo "✅ Upgrade features check complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo '<p><a href="/admin_actions/check_products_for_upgrade.php">Check products again</a></p>';
?>