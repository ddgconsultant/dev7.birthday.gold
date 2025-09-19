<?php
/**
 * Migration script to create allocation records for existing users
 * who don't have them yet
 */

$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "Creating missing allocation records for existing users...\n\n";

$current_year = date('Y');

// Get all active users who don't have allocation records
$sql = "SELECT u.user_id, u.username, u.account_plan, u.account_product_id, u.create_dt
        FROM bg_users u
        LEFT JOIN bg_user_allocations a ON u.user_id = a.user_id
            AND a.allocation_year = :year
            AND a.allocation_type = 'plan'
        WHERE u.status = 'active'
        AND a.allocation_id IS NULL
        AND u.account_product_id IS NOT NULL
        AND u.account_product_id > 0
        ORDER BY u.user_id";

$users = $database->getrows($sql, ['year' => $current_year]);

echo "Found " . count($users) . " users without plan allocations for {$current_year}\n\n";

$created_count = 0;
$skipped_count = 0;

foreach ($users as $user) {
    // Get allocation amount from product features
    $sql = "SELECT value FROM bg_product_features
            WHERE product_id = :product_id
            AND name = 'max_business_select'
            AND status = 'active'";

    $result = $database->getrow($sql, ['product_id' => $user['account_product_id']]);

    if ($result && isset($result['value']) && $result['value'] > 0) {
        $allocation_amount = intval($result['value']);

        // Create allocation record
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
                    'migration',
                    'active',
                    1,
                    NOW(),
                    1
                )";

        $params = [
            'user_id' => $user['user_id'],
            'year' => $current_year,
            'amount' => $allocation_amount,
            'comment' => ucfirst($user['account_plan']) . ' plan allocation (migration)'
        ];

        try {
            $database->query($sql, $params);
            echo "✓ Created {$allocation_amount} allocations for {$user['username']} (ID: {$user['user_id']}, Plan: {$user['account_plan']})\n";
            $created_count++;
        } catch (Exception $e) {
            echo "✗ Failed for {$user['username']}: " . $e->getMessage() . "\n";
        }
    } else {
        // No allocation amount or free plan
        echo "- Skipped {$user['username']} (ID: {$user['user_id']}, Plan: {$user['account_plan']}) - No allocation amount defined\n";
        $skipped_count++;
    }
}

echo "\n";
echo "Summary:\n";
echo "- Created allocations for {$created_count} users\n";
echo "- Skipped {$skipped_count} users (free plans or no allocation amount defined)\n";
echo "\nDone!\n";
?>