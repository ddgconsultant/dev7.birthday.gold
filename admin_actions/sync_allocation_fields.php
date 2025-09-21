<?php
/**
 * Sync max_business_select field with enrollments field
 * The system ONLY uses max_business_select for allocations
 * But many products only have enrollments defined
 */

$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h2>Syncing Allocation Fields</h2>\n";
echo "<pre>\n";

// Find products with enrollments but no max_business_select
$sql = "SELECT e.product_id, e.value as enrollments_value, e.version,
               m.value as max_business_select_value
        FROM bg_product_features e
        LEFT JOIN bg_product_features m ON e.product_id = m.product_id
            AND m.name = 'max_business_select'
            AND m.status = 'active'
        WHERE e.name = 'enrollments'
        AND e.status = 'active'
        AND (m.value IS NULL OR m.value = '')";

echo "Checking for products with enrollments but no/empty max_business_select...\n\n";

$products = $database->getrows($sql);

echo "Found " . count($products) . " products needing max_business_select\n\n";

foreach ($products as $product) {
    echo "Product {$product['product_id']}: enrollments = {$product['enrollments_value']}\n";

    // Insert max_business_select
    $insert_sql = "INSERT INTO bg_product_features
                   (product_id, version, name, value, status, display_mode, create_dt)
                   VALUES (:product_id, :version, 'max_business_select', :value, 'active', 'hide', NOW())";

    try {
        $database->query($insert_sql, [
            'product_id' => $product['product_id'],
            'version' => $product['version'] ?: 'v7',
            'value' => $product['enrollments_value']
        ]);
        echo "  ✓ Added max_business_select = {$product['enrollments_value']}\n";
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n---\n";

// Now create allocation records for users who don't have them
echo "\nCreating allocation records for users...\n\n";

$current_year = date('Y');

$sql = "SELECT u.user_id, u.username, u.account_plan, u.account_product_id,
               pf.value as max_allocations
        FROM bg_users u
        INNER JOIN bg_product_features pf ON u.account_product_id = pf.product_id
            AND pf.name = 'max_business_select'
            AND pf.status = 'active'
        LEFT JOIN bg_user_allocations a ON u.user_id = a.user_id
            AND a.allocation_year = :year
            AND a.allocation_type = 'plan'
        WHERE u.status = 'active'
        AND a.allocation_id IS NULL
        AND pf.value > 0
        ORDER BY u.user_id";

$users = $database->getrows($sql, ['year' => $current_year]);

echo "Found " . count($users) . " users without allocation records\n\n";

$created = 0;
foreach ($users as $user) {
    $sql = "INSERT INTO bg_user_allocations
            (user_id, allocation_type, allocation_year, amount, amount_used,
             allocation_comment, reference_type, status, is_recurring, created_at, created_by)
            VALUES
            (:user_id, 'plan', :year, :amount, 0,
             :comment, 'sync_script', 'active', 1, NOW(), 1)";

    try {
        $database->query($sql, [
            'user_id' => $user['user_id'],
            'year' => $current_year,
            'amount' => $user['max_allocations'],
            'comment' => 'Plan allocation (' . $user['account_plan'] . ')'
        ]);
        echo "✓ Created {$user['max_allocations']} allocations for {$user['username']} (Product: {$user['account_product_id']})\n";
        $created++;
    } catch (Exception $e) {
        echo "✗ Failed for {$user['username']}: " . $e->getMessage() . "\n";
    }
}

echo "\nCreated allocations for {$created} users\n";
echo "\nDone!\n";
echo "</pre>";
?>