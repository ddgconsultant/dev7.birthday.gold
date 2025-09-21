<?php
/**
 * Script to check products and display available plans for upgrade setup
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

// Start output
echo "<h2>Product Analysis for Upgrade Feature</h2>\n";
echo "<pre>\n";

try {
    // First, let's find all products and their plans
    $sql = "SELECT id, account_plan, account_name, account_type, price, status 
            FROM bg_products 
            WHERE status = 'active' 
            ORDER BY account_type, price ASC";
    
    $products = $database->getrows($sql);
    
    echo "=== ALL ACTIVE PRODUCTS ===\n\n";
    
    $productsByType = [];
    foreach ($products as $product) {
        $productsByType[$product['account_type']][] = $product;
    }
    
    foreach ($productsByType as $type => $typeProducts) {
        echo "Account Type: $type\n";
        echo str_repeat('-', 50) . "\n";
        foreach ($typeProducts as $product) {
            $productName = $product['account_name'] ?: '(No name)';
            echo sprintf(
                "ID: %d | Plan: %-20s | Name: %-30s | Price: $%s\n",
                $product['id'],
                $product['account_plan'],
                $productName,
                number_format($product['price'] / 100, 2)
            );
        }
        echo "\n";
    }
    
    // Check existing upgradeable features
    echo "\n=== EXISTING UPGRADEABLE FEATURES ===\n\n";
    
    $sql = "SELECT pf.*, p.account_plan, p.account_name 
            FROM bg_product_features pf
            JOIN bg_products p ON p.id = pf.product_id
            WHERE pf.name = 'upgradeable'
            AND pf.status = 'active'";
    
    $existingFeatures = $database->getrows($sql);
    
    if ($existingFeatures) {
        foreach ($existingFeatures as $feature) {
            $productName = $feature['account_name'] ?: '(No name)';
            echo "Product: {$productName} (Plan: {$feature['account_plan']})\n";
            
            // Handle both JSON arrays and single integers
            if (is_numeric($feature['value'])) {
                // Single product ID stored as integer
                $upgradeIds = [$feature['value']];
            } else {
                // JSON array of product IDs
                $upgradeIds = json_decode($feature['value'], true);
            }
            
            if (is_array($upgradeIds)) {
                foreach ($upgradeIds as $upgradeId) {
                    $upgradeProd = $database->getrow("SELECT account_plan, account_name FROM bg_products WHERE id = ?", [$upgradeId]);
                    if ($upgradeProd) {
                        $upgradeName = $upgradeProd['account_name'] ?: '(No name)';
                        echo "  - Can upgrade to: {$upgradeName} (Plan: {$upgradeProd['account_plan']})\n";
                    }
                }
            } else {
                echo "  - Invalid upgrade data: " . var_export($feature['value'], true) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "No upgradeable features found yet.\n";
    }
    
    // Suggest upgrade paths based on price
    echo "\n=== SUGGESTED UPGRADE PATHS ===\n\n";
    
    foreach ($productsByType as $type => $typeProducts) {
        if (count($typeProducts) > 1) {
            echo "For $type accounts:\n";
            for ($i = 0; $i < count($typeProducts) - 1; $i++) {
                $fromProduct = $typeProducts[$i];
                $fromPrice = number_format($fromProduct['price'] / 100, 2);
                $fromName = $fromProduct['account_name'] ?: '(No name)';
                echo "  {$fromName} (\${$fromPrice}) can upgrade to:\n";
                for ($j = $i + 1; $j < count($typeProducts); $j++) {
                    $toProduct = $typeProducts[$j];
                    $toPrice = number_format($toProduct['price'] / 100, 2);
                    $toName = $toProduct['account_name'] ?: '(No name)';
                    echo "    - {$toName} (\${$toPrice})\n";
                }
            }
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo '<p><a href="/admin_actions/add_upgradeable_features">Run the upgrade feature setup</a></p>';
?>