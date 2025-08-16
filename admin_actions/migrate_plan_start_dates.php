<?php
/**
 * Migration script to set plan_start_date for existing users
 * This should be run once to fix the allocation reset date calculation
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Check if running from command line or with proper authentication
if (php_sapi_name() !== 'cli') {
    // Web access - require admin authentication
    if (!$account->checkrole('admin')) {
        die('Access denied. Admin privileges required.');
    }
}

echo "Starting plan_start_date migration...\n";

// Get all users who don't have a plan_start_date attribute
$sql = "SELECT u.user_id, u.create_dt, u.account_plan, u.account_product_id
        FROM users u
        LEFT JOIN bg_user_attributes ua ON u.user_id = ua.user_id 
            AND ua.name = 'plan_start_date' 
            AND ua.status = 'active'
        WHERE ua.id IS NULL
        AND u.status = 'active'
        LIMIT 1000";

$users = $database->getrows($sql);

$count = 0;
$errors = 0;

foreach ($users as $user) {
    try {
        // For existing users, use their account creation date as the plan start date
        // In the future, this should be updated when they change plans
        $plan_start_date = $user['create_dt'];
        
        // Insert the plan_start_date attribute
        $insert_sql = "INSERT INTO bg_user_attributes 
                      (user_id, type, name, description, status, create_dt, modify_dt)
                      VALUES 
                      (:user_id, 'account', 'plan_start_date', :plan_start_date, 'active', NOW(), NOW())";
        
        $database->query($insert_sql, [
            'user_id' => $user['user_id'],
            'plan_start_date' => $plan_start_date
        ]);
        
        $count++;
        
        if ($count % 100 == 0) {
            echo "Processed $count users...\n";
        }
        
    } catch (Exception $e) {
        $errors++;
        echo "Error processing user {$user['user_id']}: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration complete!\n";
echo "Successfully processed: $count users\n";
echo "Errors: $errors\n";

// Add a note about future implementation
echo "\nNOTE: Going forward, the plan_start_date should be updated whenever:\n";
echo "1. A user upgrades their plan\n";
echo "2. A user downgrades their plan\n";
echo "3. A user's subscription renews (for annual plans)\n";
echo "\nThis ensures allocation resets are calculated correctly.\n";