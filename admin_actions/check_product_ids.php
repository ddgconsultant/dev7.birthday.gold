<?php
/**
 * Quick script to check what products have IDs 0 and 1
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

echo "<h2>Checking Product IDs</h2>\n";
echo "<pre>\n";

// Check products with IDs 0, 1
$sql = "SELECT id, account_plan, account_name, account_type, price, status 
        FROM bg_products 
        WHERE id IN (0, 1, 2, 3, 4, 5)
        ORDER BY id";

$products = $database->getrows($sql);

echo "Products with low IDs:\n";
echo str_repeat('-', 80) . "\n";

foreach ($products as $product) {
    $name = $product['account_name'] ?: '(No name)';
    $price = number_format($product['price'] / 100, 2);
    echo sprintf(
        "ID: %d | Plan: %-20s | Name: %-30s | Type: %-15s | Price: $%-8s | Status: %s\n",
        $product['id'],
        $product['account_plan'],
        $name,
        $product['account_type'],
        $price,
        $product['status']
    );
}

echo "\n\nThese product IDs (0, 1) being used as upgrade targets are likely invalid.\n";
echo "The cleanup script will handle this.\n";

echo "</pre>\n";
?>