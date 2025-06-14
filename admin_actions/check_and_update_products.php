<?php
/**
 * Safe Product and Features Update Script
 * This script checks existing data before making updates
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Only allow admin access
if (!$admin->isadmin()) {
    die("Admin access required");
}

echo "<h1>Product and Features Database Check</h1>";
echo "<pre>";

// Check existing products
echo "=== EXISTING PRODUCTS ===\n";
$existingProducts = $database->getrows("SELECT * FROM bg_products WHERE version IN ('v2', 'v3', 'v7') ORDER BY version DESC, account_type, account_plan");
echo "Found " . count($existingProducts) . " existing products\n\n";

foreach ($existingProducts as $product) {
    echo "ID: {$product['id']} | Type: {$product['account_type']} | Plan: {$product['account_plan']} | Name: {$product['account_name']} | Price: \${$product['price']}\n";
    
    // Check features for this product
    $features = $database->getrows("SELECT * FROM bg_product_features WHERE product_id = ?", [$product['id']]);
    if ($features) {
        echo "  Features (" . count($features) . "):\n";
        foreach ($features as $feature) {
            echo "    - {$feature['name']}: {$feature['value']}\n";
        }
    } else {
        echo "  No features found\n";
    }
    echo "\n";
}

// Show what needs to be added
echo "\n=== RECOMMENDED UPDATES ===\n";

// Define what should exist
$requiredProducts = [
    'user' => ['free', 'gold', 'lifetime'],
    'parental' => ['family_free', 'family_gold', 'family_lifetime'],
    'business' => ['business_basic', 'business_pro'],
    'giftcertificate' => ['gift_gold']
];

$missingProducts = [];
$productsNeedingFeatures = [];

foreach ($requiredProducts as $accountType => $plans) {
    foreach ($plans as $plan) {
        // Check if this product exists
        $exists = false;
        $hasFeatures = false;
        
        foreach ($existingProducts as $product) {
            if ($product['account_type'] == $accountType && $product['account_plan'] == $plan) {
                $exists = true;
                
                // Check if it has features
                $featureCount = $database->getval("SELECT COUNT(*) FROM bg_product_features WHERE product_id = ?", [$product['id']]);
                if ($featureCount > 0) {
                    $hasFeatures = true;
                } else {
                    $productsNeedingFeatures[] = [
                        'id' => $product['id'],
                        'account_type' => $accountType,
                        'account_plan' => $plan
                    ];
                }
                break;
            }
        }
        
        if (!$exists) {
            $missingProducts[] = ['account_type' => $accountType, 'account_plan' => $plan];
        }
    }
}

if ($missingProducts) {
    echo "Missing Products:\n";
    foreach ($missingProducts as $missing) {
        echo "  - {$missing['account_type']}: {$missing['account_plan']}\n";
    }
} else {
    echo "All required products exist!\n";
}

if ($productsNeedingFeatures) {
    echo "\nProducts without features:\n";
    foreach ($productsNeedingFeatures as $product) {
        echo "  - {$product['account_type']}: {$product['account_plan']} (ID: {$product['id']})\n";
    }
}

// Show sample feature structure
echo "\n=== SAMPLE FEATURE STRUCTURE ===\n";
echo "For a typical Gold plan, you might want features like:\n";
echo "  - Advanced birthday tracking & calendar\n";
echo "  - Access to 500+ birthday offers\n";
echo "  - Automatic enrollment in programs\n";
echo "  - Email & SMS reminders\n";
echo "  - VIP experiences and upgrades\n";
echo "  - Priority 24/7 support\n";
echo "  - Year-round exclusive deals\n";

// Provide update option
if ($missingProducts || $productsNeedingFeatures) {
    echo "\n=== UPDATE OPTIONS ===\n";
    echo "To add missing data, you can:\n";
    echo "1. Run individual INSERT statements for missing products\n";
    echo "2. Add features to products that don't have them\n";
    echo "3. Use the ProductManager class to programmatically add data\n";
    
    // Example insert for missing products
    if ($missingProducts) {
        echo "\nExample INSERT statements for missing products:\n\n";
        foreach ($missingProducts as $missing) {
            $sampleName = ucfirst(str_replace('_', ' ', $missing['account_plan']));
            $samplePrice = 0;
            
            // Determine sample price based on plan type
            if (strpos($missing['account_plan'], 'gold') !== false) {
                $samplePrice = 2997; // $29.97
            } elseif (strpos($missing['account_plan'], 'lifetime') !== false) {
                $samplePrice = 9997; // $99.97
            } elseif (strpos($missing['account_plan'], 'business') !== false) {
                $samplePrice = 19997; // $199.97
            }
            
            echo "INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) VALUES\n";
            echo "('v3', '{$missing['account_type']}', '{$missing['account_plan']}', '{$sampleName}', 'Description here', {$samplePrice}, 'active', 'yes', 'required', '/register', '{$missing['account_type']}', 'active');\n\n";
        }
    }
}

echo "</pre>";

// Add a form to safely add missing data
?>

<h2>Safe Data Management</h2>
<p>Use the forms below to safely add missing products and features without affecting existing data.</p>

<details>
<summary><strong>Add New Product</strong></summary>
<form method="post" action="?action=add_product" style="margin: 20px;">
    <table>
        <tr>
            <td>Account Type:</td>
            <td>
                <select name="account_type" required>
                    <option value="user">Individual User</option>
                    <option value="parental">Family/Parental</option>
                    <option value="business">Business</option>
                    <option value="giftcertificate">Gift Certificate</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>Plan Code:</td>
            <td><input type="text" name="account_plan" required placeholder="e.g., gold, family_gold"></td>
        </tr>
        <tr>
            <td>Display Name:</td>
            <td><input type="text" name="account_name" required placeholder="e.g., Gold Membership"></td>
        </tr>
        <tr>
            <td>Description:</td>
            <td><textarea name="description" rows="3" cols="50"></textarea></td>
        </tr>
        <tr>
            <td>Price (cents):</td>
            <td><input type="number" name="price" required placeholder="2997 for $29.97"></td>
        </tr>
        <tr>
            <td>Version:</td>
            <td>
                <select name="version">
                    <option value="v7">v7 (New)</option>
                    <option value="v3">v3 (Current)</option>
                    <option value="v2">v2 (Legacy)</option>
                </select>
            </td>
        </tr>
    </table>
    <button type="submit">Add Product (Safe)</button>
</form>
</details>

<details>
<summary><strong>Add Features to Product</strong></summary>
<form method="post" action="?action=add_features" style="margin: 20px;">
    <table>
        <tr>
            <td>Product:</td>
            <td>
                <select name="product_id" required>
                    <option value="">Select a product...</option>
                    <?php
                    foreach ($existingProducts as $product) {
                        echo "<option value='{$product['id']}'>{$product['account_type']} - {$product['account_plan']} ({$product['account_name']})</option>";
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>Features (one per line):</strong><br>
                <textarea name="features" rows="10" cols="60" placeholder="Feature name|Feature description
Example:
tracking|Advanced birthday tracking & calendar
offers|Access to 500+ birthday offers
automation|Automatic enrollment in programs"></textarea>
            </td>
        </tr>
    </table>
    <button type="submit">Add Features (Safe)</button>
</form>
</details>

<?php
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'add_product') {
        // Safely add a new product
        // First check if it already exists
        $exists = $database->getval("SELECT id FROM bg_products WHERE account_type = ? AND account_plan = ? AND version = ?", 
            [$_POST['account_type'], $_POST['account_plan'], $_POST['version']]);
        
        if ($exists) {
            echo "<div style='color: red;'>Product already exists with ID: $exists</div>";
        } else {
            // Add the product
            $data = [
                'version' => $_POST['version'],
                'account_type' => $_POST['account_type'],
                'account_plan' => $_POST['account_plan'],
                'account_name' => $_POST['account_name'],
                'description' => $_POST['description'],
                'price' => intval($_POST['price']),
                'status' => 'active',
                'allow_promo' => 'yes',
                'account_verification' => 'required',
                'redirect_url' => '/register',
                'display_grouping' => $_POST['account_type'],
                'display_grouping_status' => 'active'
            ];
            
            if ($database->insert('bg_products', $data)) {
                echo "<div style='color: green;'>Product added successfully!</div>";
                echo "<script>setTimeout(() => location.reload(), 2000);</script>";
            } else {
                echo "<div style='color: red;'>Error adding product</div>";
            }
        }
    }
    
    if ($action === 'add_features' && !empty($_POST['product_id'])) {
        // Parse features
        $features = explode("\n", trim($_POST['features']));
        $added = 0;
        
        foreach ($features as $feature) {
            if (strpos($feature, '|') !== false) {
                list($name, $value) = explode('|', $feature, 2);
                
                // Check if feature already exists
                $exists = $database->getval("SELECT id FROM bg_product_features WHERE product_id = ? AND name = ?", 
                    [$_POST['product_id'], trim($name)]);
                
                if (!$exists) {
                    $data = [
                        'product_id' => $_POST['product_id'],
                        'version' => 'v3',
                        'name' => trim($name),
                        'value' => trim($value),
                        'status' => 'active'
                    ];
                    
                    if ($database->insert('bg_product_features', $data)) {
                        $added++;
                    }
                }
            }
        }
        
        echo "<div style='color: green;'>Added $added new features!</div>";
        echo "<script>setTimeout(() => location.reload(), 2000);</script>";
    }
}
?>