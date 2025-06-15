<?php
/**
 * Debug script to understand the upgrade system state
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.upgrademanager.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

echo "<h2>Upgrade System Debug</h2>\n";
echo "<pre>\n";

try {
    // Initialize managers
    $productManager = new ProductManager($database, $qik);
    $upgradeManager = new UpgradeManager($database, $account, $productManager, $session, $qik);
    
    // Get current user's product
    echo "=== CURRENT USER INFO ===\n";
    $currentUserData = $session->get('current_user_data');
    echo "User ID: " . $account->getUserId() . "\n";
    echo "Account Plan: " . ($currentUserData['account_plan'] ?? 'Not set') . "\n";
    echo "Account Type: " . ($currentUserData['account_type'] ?? 'Not set') . "\n\n";
    
    // Get current product
    echo "=== CURRENT PRODUCT LOOKUP ===\n";
    $sql = "SELECT * FROM bg_products 
            WHERE account_plan = :plan 
            AND status = 'active' 
            LIMIT 1";
    
    $currentProduct = $database->getrow($sql, ['plan' => $currentUserData['account_plan'] ?? '']);
    
    if ($currentProduct) {
        echo "Product found:\n";
        echo "  ID: {$currentProduct['id']}\n";
        echo "  Name: " . ($currentProduct['account_name'] ?: '(No name)') . "\n";
        echo "  Plan: {$currentProduct['account_plan']}\n";
        echo "  Type: {$currentProduct['account_type']}\n";
        echo "  Price: $" . number_format($currentProduct['price'] / 100, 2) . "\n\n";
        
        // Check for upgradeable feature
        echo "=== CHECKING UPGRADEABLE FEATURE ===\n";
        $sql = "SELECT * FROM bg_product_features 
                WHERE product_id = :product_id 
                AND name = 'upgradeable'
                AND status = 'active'";
        
        $upgradeFeature = $database->getrow($sql, ['product_id' => $currentProduct['id']]);
        
        if ($upgradeFeature) {
            echo "Upgradeable feature found:\n";
            echo "  Value: {$upgradeFeature['value']}\n";
            
            // Decode the upgrade IDs
            $upgradeIds = json_decode($upgradeFeature['value'], true);
            if (is_array($upgradeIds) && !empty($upgradeIds)) {
                echo "  Can upgrade to product IDs: " . implode(', ', $upgradeIds) . "\n\n";
                
                // Look up those products
                echo "  Upgrade options:\n";
                foreach ($upgradeIds as $upgradeId) {
                    $sql = "SELECT * FROM bg_products WHERE id = :id AND status = 'active'";
                    $upgradeProduct = $database->getrow($sql, ['id' => $upgradeId]);
                    
                    if ($upgradeProduct) {
                        $name = $upgradeProduct['account_name'] ?: '(No name)';
                        $price = number_format($upgradeProduct['price'] / 100, 2);
                        echo "    - {$name} (ID: {$upgradeId}, Plan: {$upgradeProduct['account_plan']}, Price: \${$price})\n";
                    } else {
                        echo "    - Product ID {$upgradeId} not found or inactive\n";
                    }
                }
            } else {
                echo "  ⚠️ Invalid or empty upgrade IDs\n";
            }
        } else {
            echo "❌ No upgradeable feature found for this product\n";
        }
    } else {
        echo "❌ No active product found for plan: " . ($currentUserData['account_plan'] ?? 'none') . "\n";
    }
    
    // Call getUpgradeOptions to see what it returns
    echo "\n=== CALLING getUpgradeOptions() ===\n";
    $upgradeData = $upgradeManager->getUpgradeOptions();
    
    echo "Result:\n";
    echo "  can_upgrade: " . ($upgradeData['can_upgrade'] ? 'true' : 'false') . "\n";
    echo "  reason: " . ($upgradeData['reason'] ?? 'No reason given') . "\n";
    echo "  current_plan: " . print_r($upgradeData['current_plan'] ?? [], true);
    echo "  upgrade_options count: " . count($upgradeData['upgrade_options'] ?? []) . "\n";
    
    if (!empty($upgradeData['upgrade_options'])) {
        echo "\nUpgrade options:\n";
        foreach ($upgradeData['upgrade_options'] as $option) {
            $name = $option['product']['name'] ?? '(No name)';
            echo "  - {$name}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo '<p><a href="/myaccount/upgrade-plan">Go to upgrade page</a></p>';
?>