<?php
/**
 * Flexible script to set up upgradeable features based on actual products in database
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

// Start output
echo "<h2>Setting Up Upgradeable Features</h2>\n";
echo "<pre>\n";

try {
    // Get all active products grouped by account type
    $sql = "SELECT id, account_plan, account_name, account_type, price 
            FROM bg_products 
            WHERE status = 'active' 
            ORDER BY account_type, price ASC";
    
    $products = $database->getrows($sql);
    
    echo "Found " . count($products) . " active products\n\n";
    
    // Group products by account type
    $productsByType = [];
    $planToProductId = [];
    
    foreach ($products as $product) {
        $productsByType[$product['account_type']][] = $product;
        $planToProductId[$product['account_plan']] = $product['id'];
    }
    
    // Process each account type
    foreach ($productsByType as $accountType => $typeProducts) {
        echo "Processing $accountType accounts:\n";
        echo str_repeat('-', 50) . "\n";
        
        // For each product in this type (except the most expensive)
        for ($i = 0; $i < count($typeProducts) - 1; $i++) {
            $fromProduct = $typeProducts[$i];
            $upgradeOptions = [];
            
            // This product can upgrade to any more expensive product of the same type
            for ($j = $i + 1; $j < count($typeProducts); $j++) {
                $toProduct = $typeProducts[$j];
                $upgradeOptions[] = $toProduct['id'];
            }
            
            if (!empty($upgradeOptions)) {
                // Check if upgradeable feature already exists
                $checkSql = "SELECT * FROM bg_product_features 
                             WHERE product_id = :product_id 
                             AND name = 'upgradeable'";
                
                $existing = $database->getrow($checkSql, ['product_id' => $fromProduct['id']]);
                
                if ($existing) {
                    // Update existing feature
                    $updateSql = "UPDATE bg_product_features 
                                 SET value = :value,
                                     status = 'active'
                                 WHERE id = :id";
                    
                    $params = [
                        'id' => $existing['id'],
                        'value' => json_encode($upgradeOptions)
                    ];
                    
                    $database->query($updateSql, $params);
                    echo "✓ Updated: {$fromProduct['account_name']} can upgrade to " . count($upgradeOptions) . " plans\n";
                } else {
                    // Get upgrade names for display
                    $upgradeNames = [];
                    foreach ($upgradeOptions as $upgradeId) {
                        foreach ($typeProducts as $p) {
                            if ($p['id'] == $upgradeId) {
                                $upgradeNames[] = $p['account_name'];
                                break;
                            }
                        }
                    }
                    
                    // Insert new feature
                    $insertSql = "INSERT INTO bg_product_features 
                                 (product_id, name, value, status)
                                 VALUES 
                                 (:product_id, 'upgradeable', :value, 'active')";
                    
                    $params = [
                        'product_id' => $fromProduct['id'],
                        'value' => json_encode($upgradeOptions)
                    ];
                    
                    $database->query($insertSql, $params);
                    echo "✓ Added: {$fromProduct['account_name']} can upgrade to " . count($upgradeOptions) . " plans\n";
                }
                
                // Show details
                foreach ($upgradeOptions as $upgradeId) {
                    foreach ($typeProducts as $p) {
                        if ($p['id'] == $upgradeId) {
                            $priceDiff = ($p['price'] - $fromProduct['price']) / 100;
                            echo "  → {$p['account_name']} (+\${$priceDiff})\n";
                            break;
                        }
                    }
                }
            }
        }
        echo "\n";
    }
    
    // Special case: Allow cross-type upgrades (e.g., individual to family)
    echo "Setting up cross-type upgrades:\n";
    echo str_repeat('-', 50) . "\n";
    
    // Define cross-type upgrade paths
    $crossTypeUpgrades = [
        'free' => ['family_free', 'gold', 'family_gold'],  // Free users can upgrade to any plan
        'gold' => ['family_gold'],  // Gold individual can upgrade to family
    ];
    
    foreach ($crossTypeUpgrades as $fromPlan => $toPlanOptions) {
        if (!isset($planToProductId[$fromPlan])) {
            echo "⚠ Warning: Plan '$fromPlan' not found in database\n";
            continue;
        }
        
        $fromProductId = $planToProductId[$fromPlan];
        $fromProduct = null;
        foreach ($products as $p) {
            if ($p['id'] == $fromProductId) {
                $fromProduct = $p;
                break;
            }
        }
        
        // Get valid upgrade IDs
        $validUpgradeIds = [];
        $validUpgradeNames = [];
        foreach ($toPlanOptions as $toPlan) {
            if (isset($planToProductId[$toPlan])) {
                $validUpgradeIds[] = $planToProductId[$toPlan];
                // Find product name
                foreach ($products as $p) {
                    if ($p['account_plan'] == $toPlan) {
                        $validUpgradeNames[] = $p['account_name'];
                        break;
                    }
                }
            }
        }
        
        if (!empty($validUpgradeIds)) {
            // Check if this overrides existing upgradeable feature
            $checkSql = "SELECT * FROM bg_product_features 
                         WHERE product_id = :product_id 
                         AND name = 'upgradeable'";
            
            $existing = $database->getrow($checkSql, ['product_id' => $fromProductId]);
            
            if ($existing) {
                // Update to include cross-type upgrades
                $updateSql = "UPDATE bg_product_features 
                             SET value = :value
                             WHERE id = :id";
                
                $params = [
                    'id' => $existing['id'],
                    'value' => json_encode($validUpgradeIds)
                ];
                
                $database->query($updateSql, $params);
                echo "✓ Updated cross-type upgrades for {$fromProduct['account_name']}\n";
            }
            
            foreach ($validUpgradeIds as $idx => $upgradeId) {
                echo "  → {$validUpgradeNames[$idx]}\n";
            }
        }
    }
    
    echo "\n✅ Upgrade features setup complete!\n";
    echo "\nYou can now test the upgrade feature at: /myaccount/upgrade-plan\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo '<p><a href="/admin_actions/check_products_for_upgrade">Check current setup</a></p>';
?>