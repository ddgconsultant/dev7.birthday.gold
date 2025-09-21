<?php
/**
 * Fix allocation records based on bg_product_features
 * This will:
 * 1. Create missing allocations where max_business_select is defined
 * 2. Update incorrect amounts
 * 3. Handle products that use 'enrollments' instead of 'max_business_select'
 */

$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "Fixing allocation records based on product features...\n\n";

// Test database connection
try {
    $test = $database->getrow("SELECT COUNT(*) as count FROM bg_users");
    echo "Database connection OK - Found {$test['count']} users in system\n\n";
} catch (Exception $e) {
    echo "Database connection FAILED: " . $e->getMessage() . "\n";
    exit;
}

$current_year = date('Y');

// First, let's check for products using 'enrollments' instead of 'max_business_select'
echo "Checking for products using 'enrollments' field...\n";
$sql = "SELECT DISTINCT pf.product_id, pf.value as enrollments, pf2.value as max_business_select
        FROM bg_product_features pf
        LEFT JOIN bg_product_features pf2 ON pf.product_id = pf2.product_id
            AND pf2.name = 'max_business_select' AND pf2.status = 'active'
        WHERE pf.name = 'enrollments' AND pf.status = 'active'";

echo "Running SQL: " . $sql . "\n";

$products_with_enrollments = $database->getrows($sql);

echo "Found " . count($products_with_enrollments) . " products with 'enrollments' field\n";

if (empty($products_with_enrollments)) {
    echo "No products found with 'enrollments' field - checking what fields exist...\n";

    $sql = "SELECT DISTINCT name FROM bg_product_features WHERE name LIKE '%enroll%' OR name LIKE '%business%' OR name LIKE '%select%' ORDER BY name";
    $fields = $database->getrows($sql);
    echo "Found these related fields: ";
    foreach ($fields as $field) {
        echo $field['name'] . ", ";
    }
    echo "\n\n";
} else {
    foreach ($products_with_enrollments as $product) {
        echo "  Product {$product['product_id']}: enrollments={$product['enrollments']}, max_business_select=" .
             ($product['max_business_select'] === null ? "NULL" : $product['max_business_select']) . "\n";

        if ($product['max_business_select'] === null) {
            echo "    → Will add max_business_select={$product['enrollments']}\n";

        // Add max_business_select based on enrollments value
        $sql = "INSERT INTO bg_product_features (product_id, name, value, status, version)
                SELECT :product_id, 'max_business_select', :value, 'active', version
                FROM bg_product_features
                WHERE product_id = :product_id2
                LIMIT 1";

        try {
            $database->query($sql, [
                'product_id' => $product['product_id'],
                'product_id2' => $product['product_id'],
                'value' => $product['enrollments']
            ]);
            echo "  ✓ Added max_business_select={$product['enrollments']} to product {$product['product_id']}\n";
        } catch (Exception $e) {
            echo "  ✗ Failed to add max_business_select: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nNow checking all users for correct allocations...\n\n";

// Get all active users with their product allocations
$sql = "SELECT u.user_id, u.username, u.account_plan, u.account_product_id,
               a.allocation_id, a.amount as current_amount,
               COALESCE(pf.value, pf2.value) as correct_amount
        FROM bg_users u
        LEFT JOIN bg_user_allocations a ON u.user_id = a.user_id
            AND a.allocation_year = :year
            AND a.allocation_type = 'plan'
        LEFT JOIN bg_product_features pf ON u.account_product_id = pf.product_id
            AND pf.name = 'max_business_select' AND pf.status = 'active'
        LEFT JOIN bg_product_features pf2 ON u.account_product_id = pf2.product_id
            AND pf2.name = 'enrollments' AND pf2.status = 'active'
        WHERE u.status = 'active'
        AND u.account_product_id IS NOT NULL
        AND (
            a.allocation_id IS NULL
            OR a.amount != COALESCE(pf.value, pf2.value)
            OR (a.amount IS NULL AND COALESCE(pf.value, pf2.value) > 0)
        )
        ORDER BY u.user_id";

$users = $database->getrows($sql, ['year' => $current_year]);

echo "Found " . count($users) . " users with missing or incorrect allocations\n\n";

$created_count = 0;
$updated_count = 0;
$skipped_count = 0;

foreach ($users as $user) {
    $correct_amount = intval($user['correct_amount']);

    if ($correct_amount > 0) {
        if ($user['allocation_id']) {
            // Update existing allocation
            $sql = "UPDATE bg_user_allocations
                    SET amount = :amount,
                        allocation_comment = CONCAT(allocation_comment, ' (fixed)'),
                        modify_dt = NOW()
                    WHERE allocation_id = :allocation_id";

            try {
                $database->query($sql, [
                    'amount' => $correct_amount,
                    'allocation_id' => $user['allocation_id']
                ]);
                echo "✓ Updated {$user['username']} from {$user['current_amount']} to {$correct_amount} allocations\n";
                $updated_count++;
            } catch (Exception $e) {
                echo "✗ Failed to update {$user['username']}: " . $e->getMessage() . "\n";
            }
        } else {
            // Create new allocation
            $sql = "INSERT INTO bg_user_allocations (
                        user_id,
                        allocation_type,
                        allocation_year,
                        amount,
                        amount_used,
                        allocation_comment,
                        reference_type,
                        status,
                        is_recurring,
                        created_at,
                        created_by
                    ) VALUES (
                        :user_id,
                        'plan',
                        :year,
                        :amount,
                        0,
                        :comment,
                        'fix_script',
                        'active',
                        1,
                        NOW(),
                        1
                    )";

            $params = [
                'user_id' => $user['user_id'],
                'year' => $current_year,
                'amount' => $correct_amount,
                'comment' => ucfirst($user['account_plan']) . ' plan allocation (fixed)'
            ];

            try {
                $database->query($sql, $params);
                echo "✓ Created {$correct_amount} allocations for {$user['username']} (Plan: {$user['account_plan']})\n";
                $created_count++;
            } catch (Exception $e) {
                echo "✗ Failed to create for {$user['username']}: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "- Skipped {$user['username']} - No allocation amount defined in product features\n";
        $skipped_count++;
    }
}

echo "\n";
echo "Summary:\n";
echo "- Created allocations for {$created_count} users\n";
echo "- Updated allocations for {$updated_count} users\n";
echo "- Skipped {$skipped_count} users (no allocation amount defined)\n";
echo "\nDone!\n";
?>