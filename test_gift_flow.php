<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Test the gift certificate flow
echo "<h1>Gift Certificate Flow Test</h1>";

// 1. Check if gift certificate shows in account types
echo "<h2>1. Testing Account Types</h2>";
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
$productManager = new ProductManager($database, $qik);
$accountTypes = $productManager->getAvailableAccountTypes($website['plan_version']);

echo "<p>Available account types:</p>";
echo "<pre>";
foreach ($accountTypes as $type) {
    echo "- " . $type['account_type'] . " (" . $type['display_name'] . ") - " . $type['plan_count'] . " plans\n";
}
echo "</pre>";

// 2. Check gift certificate products
echo "<h2>2. Gift Certificate Products</h2>";
$giftProducts = $productManager->getProductsWithFeatures('giftcertificate', $website['plan_version']);
echo "<p>Found " . count($giftProducts) . " gift certificate products:</p>";
echo "<pre>";
foreach ($giftProducts as $product) {
    echo "ID: " . $product['id'] . "\n";
    echo "Name: " . $product['account_name'] . "\n";
    echo "Price: $" . number_format($product['price'] / 100, 2) . "\n";
    echo "Billing: " . $product['billing_cycle'] . "\n";
    echo "Status: " . $product['status'] . "\n";
    echo "Grouping Status: " . $product['display_grouping_status'] . "\n";
    echo "Redirect URL: " . $product['redirect_url'] . "\n";
    echo "---\n";
}
echo "</pre>";

// 3. Test signup flow
echo "<h2>3. Test Signup Flow</h2>";
echo "<p>To test the full flow:</p>";
echo "<ol>";
echo "<li><a href='/signup.php' target='_blank'>Go to Signup Page</a></li>";
echo "<li>Look for 'Gift Certificate' or 'As a gift' option</li>";
echo "<li>Select the gift certificate plan</li>";
echo "<li>Click Continue</li>";
echo "</ol>";

// 4. Test direct access
echo "<h2>4. Test Direct Access</h2>";
$giftProduct = reset($giftProducts); // Get first gift product
if ($giftProduct) {
    $encodedId = $qik->encodeId($giftProduct['id']);
    echo "<p>Direct link to test gift certificate signup:</p>";
    echo "<p><a href='/signup.php?account_type=giftcertificate&account_plan=" . $encodedId . "' target='_blank' class='btn btn-primary'>Test Gift Certificate Signup</a></p>";
    
    // Set up session data for testing
    echo "<h3>Setting up test session data...</h3>";
    $test_data = [
        'account_type' => 'giftcertificate',
        'account_plan' => $giftProduct['account_plan'],
        'account_plan_id' => $giftProduct['id'],
        'account_cost' => $giftProduct['price'],
        'plandata' => $giftProduct
    ];
    $session->set('signup_process_data', $test_data);
    echo "<p>Session data set. <a href='/createaccount.php' target='_blank' class='btn btn-success'>Go directly to Create Account page</a></p>";
}

// 5. Check configuration
echo "<h2>5. Configuration Check</h2>";
echo "<p>Site plan version: " . $website['plan_version'] . "</p>";
echo "<p>Gift certificate redirect URL issue: The product has redirect_url set to '/register-giftcertificate' which might bypass the new flow.</p>";
echo "<p><strong>Recommendation:</strong> Update the gift certificate product in <a href='/admin/plan_editor.php' target='_blank'>Plan Editor</a> and clear the redirect_url field to use the standard signup flow.</p>";
?>