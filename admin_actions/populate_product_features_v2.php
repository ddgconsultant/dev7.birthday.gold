<?php
/**
 * Populate Product Features with Feature Metadata
 *
 * This script adds feature definitions to bg_product_features table
 * for Gold and Life plans. Features are stored as JSON in the value field.
 *
 * Run this once to populate the feature data.
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Only allow admin users to run this
if (!$account->isadmin()) {
    die('Unauthorized');
}

echo "<pre>";
echo "===========================================\n";
echo "Product Features Population Script\n";
echo "===========================================\n\n";

// Define feature metadata structure
$features_metadata = [
    'feature_email' => [
        'display_name' => 'Birthday Gold Inbox',
        'display_description' => 'Managed email address for all your birthday enrollments',
        'icon' => 'bi-envelope-paper-heart',
        'setup_url' => '/myaccount/components/feature_email.php',
        'settings_url' => '/myaccount/mail-box#settings',
        'user_column' => 'feature_email', // Column in bg_users to check if configured
        'display_order' => 1
    ],
    'feature_inbox' => [
        'display_name' => 'Email Management Dashboard',
        'display_description' => 'View and manage all birthday-related emails in one place',
        'icon' => 'bi-inbox-fill',
        'setup_url' => '/myaccount/components/feature_inbox.php',
        'settings_url' => '/myaccount/mail-box',
        'user_column' => 'feature_inbox',
        'display_order' => 2
    ],
    'feature_premium_support' => [
        'display_name' => 'Premium Support Access',
        'display_description' => 'Priority support via live chat and email',
        'icon' => 'bi-headset',
        'setup_url' => null, // No setup needed, just enabled
        'settings_url' => '/myaccount/support',
        'user_column' => 'feature_premium_support',
        'display_order' => 3
    ],
    'feature_advanced_analytics' => [
        'display_name' => 'Advanced Birthday Analytics',
        'display_description' => 'Detailed insights and statistics about your birthday rewards',
        'icon' => 'bi-graph-up-arrow',
        'setup_url' => null,
        'settings_url' => '/myaccount/analytics',
        'user_column' => 'feature_advanced_analytics',
        'display_order' => 4
    ]
];

// Product IDs that get features
// Gold plans: 11, 321, 441
// Life plans: 21
// Parental Gold: 41, 351, 471
$gold_products = [11, 321, 441, 41, 351, 471];
$life_products = [21, 51]; // Life and Parental Life

// Features for each plan tier
$gold_features = ['feature_email']; // Gold gets email only for now
$life_features = ['feature_email', 'feature_inbox', 'feature_premium_support', 'feature_advanced_analytics']; // Life gets all

echo "Step 1: Checking existing features...\n";
$check_sql = "SELECT COUNT(*) as count FROM bg_product_features WHERE name LIKE 'feature_%' AND product_id IS NOT NULL AND display_mode = 'show'";
$result = $database->getrow($check_sql);
echo "Found {$result['count']} existing feature entries\n\n";

echo "Step 2: Clearing old feature metadata entries...\n";
$delete_sql = "DELETE FROM bg_product_features WHERE name LIKE 'feature_%' AND product_id IS NOT NULL AND display_mode = 'show'";
$database->execute($delete_sql);
echo "Old entries cleared\n\n";

echo "Step 3: Inserting new feature definitions...\n\n";

$insert_count = 0;
$errors = [];

// Insert Gold plan features
foreach ($gold_products as $product_id) {
    foreach ($gold_features as $feature_name) {
        if (!isset($features_metadata[$feature_name])) {
            $errors[] = "Metadata missing for: $feature_name";
            continue;
        }

        $metadata = $features_metadata[$feature_name];
        $value_json = json_encode($metadata);

        $sql = "INSERT INTO bg_product_features
                (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
                VALUES
                (:product_id, 'v3', NULL, :name, :value, 'active', 'show', NOW(), NOW())";

        try {
            $database->execute($sql, [
                'product_id' => $product_id,
                'name' => $feature_name,
                'value' => $value_json
            ]);
            $insert_count++;
            echo "✓ Added $feature_name to product $product_id (Gold)\n";
        } catch (Exception $e) {
            $errors[] = "Failed to insert $feature_name for product $product_id: " . $e->getMessage();
        }
    }
}

// Insert Life plan features
foreach ($life_products as $product_id) {
    foreach ($life_features as $feature_name) {
        if (!isset($features_metadata[$feature_name])) {
            $errors[] = "Metadata missing for: $feature_name";
            continue;
        }

        $metadata = $features_metadata[$feature_name];
        $value_json = json_encode($metadata);

        $sql = "INSERT INTO bg_product_features
                (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
                VALUES
                (:product_id, 'v3', NULL, :name, :value, 'active', 'show', NOW(), NOW())";

        try {
            $database->execute($sql, [
                'product_id' => $product_id,
                'name' => $feature_name,
                'value' => $value_json
            ]);
            $insert_count++;
            echo "✓ Added $feature_name to product $product_id (Life)\n";
        } catch (Exception $e) {
            $errors[] = "Failed to insert $feature_name for product $product_id: " . $e->getMessage();
        }
    }
}

echo "\n===========================================\n";
echo "Summary:\n";
echo "===========================================\n";
echo "Features inserted: $insert_count\n";
echo "Errors: " . count($errors) . "\n\n";

if (!empty($errors)) {
    echo "Error details:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n===========================================\n";
echo "Step 4: Verifying inserted features...\n";
echo "===========================================\n\n";

$verify_sql = "SELECT product_id, name, status, display_mode FROM bg_product_features
               WHERE name LIKE 'feature_%' AND product_id IS NOT NULL AND display_mode = 'show'
               ORDER BY product_id, name";
$stmt = $database->prepare($verify_sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo "Product {$row['product_id']}: {$row['name']} [{$row['status']}/{$row['display_mode']}]\n";
}

echo "\n===========================================\n";
echo "DONE!\n";
echo "===========================================\n";
echo "</pre>";
