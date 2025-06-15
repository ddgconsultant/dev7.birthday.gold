<?php
/**
 * Script to add upgradeable features to products
 * This adds the ability for certain plans to upgrade to higher tier plans
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

// Define upgrade paths
// Note: These should match the account_plan values in bg_products
$upgradePaths = [
    'free' => ['gold', 'family_gold'],  // Free can upgrade to gold individual or family
    'gold' => ['family_gold']  // Gold individual can upgrade to gold family
];

// Start output
echo "<h2>Adding Upgradeable Features to Products</h2>\n";
echo "<pre>\n";

try {
    // First, let's find the product IDs for each plan
    $planToProductId = [];
    
    $sql = "SELECT id, account_plan, account_name, account_type FROM bg_products WHERE status = 'active'";
    $products = $database->getrows($sql);
    
    echo "Found products:\n";
    foreach ($products as $product) {
        echo "- ID: {$product['id']}, Plan: {$product['account_plan']}, Name: {$product['account_name']}, Type: {$product['account_type']}\n";
        $planToProductId[$product['account_plan']] = $product['id'];
    }
    echo "\n";
    
    // Now add upgradeable features
    foreach ($upgradePaths as $fromPlan => $toPlanOptions) {
        if (!isset($planToProductId[$fromPlan])) {
            echo "Warning: Product not found for plan: $fromPlan\n";
            continue;
        }
        
        $fromProductId = $planToProductId[$fromPlan];
        
        // Convert target plan names to product IDs
        $upgradeableProductIds = [];
        foreach ($toPlanOptions as $toPlan) {
            if (isset($planToProductId[$toPlan])) {
                $upgradeableProductIds[] = $planToProductId[$toPlan];
            } else {
                echo "Warning: Target product not found for plan: $toPlan\n";
            }
        }
        
        if (empty($upgradeableProductIds)) {
            echo "No valid upgrade targets found for plan: $fromPlan\n";
            continue;
        }
        
        // Check if upgradeable feature already exists
        $checkSql = "SELECT * FROM bg_product_features 
                     WHERE product_id = :product_id 
                     AND name = 'upgradeable'";
        
        $existing = $database->getrow($checkSql, ['product_id' => $fromProductId]);
        
        if ($existing) {
            // Update existing feature
            $updateSql = "UPDATE bg_product_features 
                         SET value = :value,
                             status = 'active'
                         WHERE id = :id";
            
            $params = [
                'id' => $existing['id'],
                'value' => json_encode($upgradeableProductIds)
            ];
            
            $database->query($updateSql, $params);
            echo "Updated upgradeable feature for product ID $fromProductId (plan: $fromPlan)\n";
        } else {
            // Insert new feature
            $insertSql = "INSERT INTO bg_product_features 
                         (product_id, name, value, status)
                         VALUES 
                         (:product_id, 'upgradeable', :value, 'active')";
            
            $params = [
                'product_id' => $fromProductId,
                'value' => json_encode($upgradeableProductIds)
            ];
            
            $database->query($insertSql, $params);
            echo "Added upgradeable feature for product ID $fromProductId (plan: $fromPlan)\n";
        }
        
        echo "  - Can upgrade to product IDs: " . implode(', ', $upgradeableProductIds) . "\n";
    }
    
    // Also add downgrade capability tracking (for future use)
    echo "\nAdding downgrade capability (for future implementation):\n";
    
    $downgradePaths = [
        'family_gold' => ['gold', 'free'],  // Family can downgrade to individual or free
        'gold' => ['free']  // Individual can downgrade to free
    ];
    
    foreach ($downgradePaths as $fromPlan => $toPlanOptions) {
        if (!isset($planToProductId[$fromPlan])) {
            continue;
        }
        
        $fromProductId = $planToProductId[$fromPlan];
        
        // Convert target plan names to product IDs
        $downgradeableProductIds = [];
        foreach ($toPlanOptions as $toPlan) {
            if (isset($planToProductId[$toPlan])) {
                $downgradeableProductIds[] = $planToProductId[$toPlan];
            }
        }
        
        if (empty($downgradeableProductIds)) {
            continue;
        }
        
        // Check if downgradeable feature already exists
        $checkSql = "SELECT * FROM bg_product_features 
                     WHERE product_id = :product_id 
                     AND name = 'downgradeable'";
        
        $existing = $database->getrow($checkSql, ['product_id' => $fromProductId]);
        
        if ($existing) {
            // Update existing feature
            $updateSql = "UPDATE bg_product_features 
                         SET value = :value,
                             status = 'inactive' -- Inactive until implemented
                         WHERE id = :id";
            
            $params = [
                'id' => $existing['id'],
                'value' => json_encode($downgradeableProductIds)
            ];
            
            $database->query($updateSql, $params);
            echo "Updated downgradeable feature for product ID $fromProductId (plan: $fromPlan) - INACTIVE\n";
        } else {
            // Insert new feature
            $insertSql = "INSERT INTO bg_product_features 
                         (product_id, name, value, status)
                         VALUES 
                         (:product_id, 'downgradeable', :value, 'inactive')";
            
            $params = [
                'product_id' => $fromProductId,
                'value' => json_encode($downgradeableProductIds)
            ];
            
            $database->query($insertSql, $params);
            echo "Added downgradeable feature for product ID $fromProductId (plan: $fromPlan) - INACTIVE\n";
        }
    }
    
    echo "\nUpgradeable features added successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>