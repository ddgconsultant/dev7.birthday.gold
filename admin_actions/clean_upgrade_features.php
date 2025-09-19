<?php
/**
 * Script to clean up invalid upgrade features and reset them properly
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

// Start output
echo "<h2>Cleaning Upgrade Features</h2>\n";
echo "<pre>\n";

try {
    // Find all upgradeable features
    $sql = "SELECT pf.id, pf.product_id, pf.name, pf.value, pf.status,
                   p.account_plan, p.account_name 
            FROM bg_product_features pf
            JOIN bg_products p ON p.id = pf.product_id
            WHERE pf.name = 'upgradeable'";
    
    $features = $database->getrows($sql);
    
    echo "Found " . count($features) . " upgradeable features\n\n";
    
    $invalidCount = 0;
    $fixedCount = 0;
    $deletedCount = 0;
    
    foreach ($features as $feature) {
        $productName = $feature['account_name'] ?: '(No name)';
        echo "Checking: {$productName} (Plan: {$feature['account_plan']})\n";
        echo "  Current value: " . var_export($feature['value'], true) . "\n";
        
        $isValid = false;
        $upgradeIds = null;
        
        // Check different value types
        if ($feature['value'] === 'Y' || $feature['value'] === 'N') {
            echo "  ❌ Found Y/N value - will delete\n";
            $invalidCount++;
            
            // Delete this invalid entry
            $deleteSql = "DELETE FROM bg_product_features WHERE id = :id";
            $database->query($deleteSql, ['id' => $feature['id']]);
            $deletedCount++;
            echo "  🗑️ Deleted invalid entry\n";
            
        } elseif (is_numeric($feature['value'])) {
            // Already fixed by previous script
            echo "  ✓ Already fixed to JSON array\n";
            $isValid = true;
            
        } else {
            // Try to decode JSON
            $decoded = json_decode($feature['value'], true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                echo "  ❌ Invalid data - will delete\n";
                $invalidCount++;
                
                // Delete this invalid entry
                $deleteSql = "DELETE FROM bg_product_features WHERE id = :id";
                $database->query($deleteSql, ['id' => $feature['id']]);
                $deletedCount++;
                echo "  🗑️ Deleted invalid entry\n";
                
            } else {
                // Valid JSON array
                if (is_array($decoded) && !empty($decoded)) {
                    // Check if the product IDs are valid
                    $validIds = [];
                    foreach ($decoded as $productId) {
                        $checkProduct = $database->getrow(
                            "SELECT id FROM bg_products WHERE id = :id AND status = 'active'",
                            ['id' => $productId]
                        );
                        if ($checkProduct) {
                            $validIds[] = $productId;
                        }
                    }
                    
                    if (count($validIds) !== count($decoded)) {
                        echo "  ⚠️ Some product IDs are invalid\n";
                        echo "    Original: " . json_encode($decoded) . "\n";
                        echo "    Valid: " . json_encode($validIds) . "\n";
                        
                        if (empty($validIds)) {
                            // No valid IDs, delete the entry
                            $deleteSql = "DELETE FROM bg_product_features WHERE id = :id";
                            $database->query($deleteSql, ['id' => $feature['id']]);
                            $deletedCount++;
                            echo "  🗑️ Deleted entry with no valid product IDs\n";
                        } else {
                            // Update with only valid IDs
                            $updateSql = "UPDATE bg_product_features SET value = :value WHERE id = :id";
                            $database->query($updateSql, [
                                'value' => json_encode($validIds),
                                'id' => $feature['id']
                            ]);
                            $fixedCount++;
                            echo "  ✅ Updated with valid product IDs only\n";
                        }
                    } else {
                        echo "  ✓ Valid JSON array with valid product IDs\n";
                        $isValid = true;
                    }
                } else {
                    echo "  ⚠️ Empty array - will delete\n";
                    $deleteSql = "DELETE FROM bg_product_features WHERE id = :id";
                    $database->query($deleteSql, ['id' => $feature['id']]);
                    $deletedCount++;
                    echo "  🗑️ Deleted empty entry\n";
                }
            }
        }
        echo "\n";
    }
    
    echo "=== SUMMARY ===\n";
    echo "Invalid entries found: $invalidCount\n";
    echo "Entries fixed: $fixedCount\n";
    echo "Entries deleted: $deletedCount\n\n";
    
    echo "✅ Cleanup complete!\n\n";
    echo "Next step: Run the setup script to properly configure upgrade paths.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo '<p><a href="/admin_actions/setup_upgrade_features">Set up upgrade features properly</a></p>';
?>