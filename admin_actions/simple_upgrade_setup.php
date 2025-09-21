<?php
/**
 * Simple upgrade setup script that works with actual table structure
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

echo "<h2>Simple Upgrade Setup</h2>\n";
echo "<pre>\n";

try {
    // First, clean up any invalid entries
    echo "=== CLEANING UP INVALID ENTRIES ===\n";
    
    // Delete entries with Y/N values
    $sql = "DELETE FROM bg_product_features 
            WHERE name = 'upgradeable' 
            AND (value = 'Y' OR value = 'N')";
    $stmt = $database->query($sql);
    $deletedYN = $stmt->rowCount();
    echo "Deleted Y/N entries: " . $deletedYN . "\n";
    
    // Delete entries with invalid product IDs (0 or 1)
    $sql = "DELETE FROM bg_product_features 
            WHERE name = 'upgradeable' 
            AND (value = '0' OR value = '1' OR value = '[0]' OR value = '[1]')";
    $stmt = $database->query($sql);
    $deletedInvalid = $stmt->rowCount();
    echo "Deleted entries with invalid product IDs: " . $deletedInvalid . "\n\n";
    
    // Get all active products
    echo "=== SETTING UP UPGRADE PATHS ===\n";
    
    $sql = "SELECT id, account_plan, account_name, account_type, price 
            FROM bg_products 
            WHERE status = 'active' 
            AND id > 10  -- Skip low ID products which seem invalid
            ORDER BY account_type, price ASC";
    
    $products = $database->getrows($sql);
    
    echo "Found " . count($products) . " valid products\n\n";
    
    // Define simple upgrade paths
    $upgradePaths = [
        // User account upgrades
        ['from_plan' => 'free', 'from_type' => 'user', 'to_plans' => ['gold', 'life']],
        ['from_plan' => 'gold', 'from_type' => 'user', 'to_plans' => ['life']],
        
        // Parental/Family upgrades
        ['from_plan' => 'family_free', 'from_type' => 'parental', 'to_plans' => ['family_gold', 'family_lifetime']],
        ['from_plan' => 'family_gold', 'from_type' => 'parental', 'to_plans' => ['family_lifetime']],
        
        // Cross-type upgrades (individual to family)
        ['from_plan' => 'free', 'from_type' => 'user', 'to_plans' => ['family_free', 'family_gold']],
        ['from_plan' => 'gold', 'from_type' => 'user', 'to_plans' => ['family_gold']],
    ];
    
    // Create a map of plan names to product IDs
    $planToProduct = [];
    foreach ($products as $product) {
        $planToProduct[$product['account_plan']] = $product;
    }
    
    // Process upgrade paths
    foreach ($upgradePaths as $path) {
        $fromPlan = $path['from_plan'];
        
        if (!isset($planToProduct[$fromPlan])) {
            echo "⚠️ Plan not found: $fromPlan\n";
            continue;
        }
        
        $fromProduct = $planToProduct[$fromPlan];
        $upgradeIds = [];
        
        // Find valid upgrade targets
        foreach ($path['to_plans'] as $toPlan) {
            if (isset($planToProduct[$toPlan])) {
                $upgradeIds[] = $planToProduct[$toPlan]['id'];
            }
        }
        
        if (empty($upgradeIds)) {
            echo "⚠️ No valid upgrades found for: {$fromProduct['account_name']}\n";
            continue;
        }
        
        // Check if feature already exists
        $checkSql = "SELECT id FROM bg_product_features 
                     WHERE product_id = :product_id 
                     AND name = 'upgradeable'";
        
        $existing = $database->getrow($checkSql, ['product_id' => $fromProduct['id']]);
        
        if ($existing) {
            // Update existing
            $sql = "UPDATE bg_product_features 
                    SET value = :value,
                        status = 'active'
                    WHERE id = :id";
            
            $database->query($sql, [
                'id' => $existing['id'],
                'value' => json_encode($upgradeIds)
            ]);
            
            echo "✅ Updated: {$fromProduct['account_name']} can upgrade to " . count($upgradeIds) . " plans\n";
        } else {
            // Insert new
            $sql = "INSERT INTO bg_product_features 
                    (product_id, name, value, status)
                    VALUES 
                    (:product_id, 'upgradeable', :value, 'active')";
            
            $database->query($sql, [
                'product_id' => $fromProduct['id'],
                'value' => json_encode($upgradeIds)
            ]);
            
            echo "✅ Added: {$fromProduct['account_name']} can upgrade to " . count($upgradeIds) . " plans\n";
        }
        
        // Show details
        foreach ($upgradeIds as $upgradeId) {
            if (isset($planToProduct)) {
                foreach ($planToProduct as $plan => $prod) {
                    if ($prod['id'] == $upgradeId) {
                        echo "    → {$prod['account_name']}\n";
                        break;
                    }
                }
            }
        }
    }
    
    echo "\n✅ Setup complete!\n";
    echo "\nYou can now test the upgrade feature at: /myaccount/upgrade-plan\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "SQL: " . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo '<p><a href="/myaccount/upgrade-plan">Test the upgrade feature</a></p>';
?>