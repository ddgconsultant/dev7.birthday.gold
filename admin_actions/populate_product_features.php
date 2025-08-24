<?php
// Populate Product Features Script
// This script inserts all product features for the database-driven plan details page

include '../core/site-controller.php';

// Check if user is admin
if (!$account->checkrole('admin')) {
    die('Access denied. Admin only.');
}

echo "<h2>Populating Product Features</h2>";
echo "<pre>";

// Product features data structure
$products_features = [
    // Product ID 1: FREE
    1 => [
        'plan_description' => 'Get started with Birthday Gold at no cost. Perfect for trying out our service and discovering birthday rewards from your favorite brands.',
        'plan_highlight_1' => 'Basic enrollment in select brands',
        'plan_highlight_2' => 'Email birthday reminders',
        'plan_highlight_3' => 'Access to community forums',
        'plan_highlight_4' => 'Basic reward tracking',
        'feature_1_title' => 'Brands You Can Register',
        'feature_1_value' => '5 Brands',
        'feature_1_description' => 'Start with 5 brand enrollments to try out the service. Upgrade anytime for unlimited access.',
        'feature_1_icon' => 'bi-gift',
        'feature_1_icon_color' => 'primary',
        'feature_2_title' => 'Birthday Reminders',
        'feature_2_value' => 'Email Only',
        'feature_2_description' => 'Get email reminders before your birthday month so you never miss a reward.',
        'feature_2_icon' => 'bi-bell',
        'feature_2_icon_color' => 'info',
        'feature_3_title' => 'Celebration Planner',
        'feature_3_value' => 'Not Available',
        'feature_3_description' => 'Upgrade to access our birthday tour planner and maximize your celebrations.',
        'feature_3_icon' => 'bi-map',
        'feature_3_icon_color' => 'secondary',
        'feature_4_title' => 'Customer Support',
        'feature_4_value' => 'Community',
        'feature_4_description' => 'Get help from our community forums and knowledge base articles.',
        'feature_4_icon' => 'bi-people',
        'feature_4_icon_color' => 'warning'
    ],
    
    // Product ID 11: GOLD
    11 => [
        'plan_description' => 'Our most popular plan for individuals who want to maximize their birthday rewards. Full access to all features with one-time payment.',
        'plan_highlight_1' => 'Unlimited brand enrollments',
        'plan_highlight_2' => 'Advanced reminder system',
        'plan_highlight_3' => 'Birthday tour planner with maps',
        'plan_highlight_4' => 'Priority email support',
        'plan_highlight_5' => 'Exclusive partner offers',
        'feature_1_title' => 'Brands You Can Register',
        'feature_1_value' => 'Unlimited',
        'feature_1_description' => 'Enroll in as many birthday programs as you want. We handle all the registrations for you automatically.',
        'feature_1_icon' => 'bi-gift',
        'feature_1_icon_color' => 'warning',
        'feature_2_title' => 'Birthday Reminders',
        'feature_2_value' => 'Multi-Channel',
        'feature_2_description' => 'Get reminders via email, SMS, and push notifications. Never miss a birthday reward again!',
        'feature_2_icon' => 'bi-bell-fill',
        'feature_2_icon_color' => 'success',
        'feature_3_title' => 'Celebration Planner',
        'feature_3_value' => 'Tour Maps',
        'feature_3_description' => 'Plan your perfect birthday tour with custom maps and schedules to collect all your rewards efficiently.',
        'feature_3_icon' => 'bi-map-fill',
        'feature_3_icon_color' => 'primary',
        'feature_4_title' => 'Priority Support',
        'feature_4_value' => 'Email Priority',
        'feature_4_description' => 'Get faster responses from our support team with priority email support during business hours.',
        'feature_4_icon' => 'bi-headset',
        'feature_4_icon_color' => 'info'
    ],
    
    // Product ID 21: LIFE
    21 => [
        'plan_description' => 'One-time payment for lifetime access. Never pay again and get all current and future features automatically.',
        'plan_highlight_1' => 'One-time payment - never pay again',
        'plan_highlight_2' => 'All current features included',
        'plan_highlight_3' => 'All future features included',
        'plan_highlight_4' => 'Unlimited enrollments forever',
        'plan_highlight_5' => 'Priority support for life',
        'plan_highlight_6' => 'Early adopter benefits',
        'feature_1_title' => 'Brands You Can Register',
        'feature_1_value' => 'Lifetime Unlimited',
        'feature_1_description' => 'Enroll in unlimited brands forever. All current and future partner brands included with your lifetime membership.',
        'feature_1_icon' => 'bi-infinity',
        'feature_1_icon_color' => 'success',
        'feature_2_title' => 'Birthday Reminders',
        'feature_2_value' => 'Forever Automated',
        'feature_2_description' => 'Lifetime access to our automated reminder system with all current and future notification channels.',
        'feature_2_icon' => 'bi-bell-fill',
        'feature_2_icon_color' => 'primary',
        'feature_3_title' => 'Celebration Planner',
        'feature_3_value' => 'Lifetime Access',
        'feature_3_description' => 'Use our birthday tour planner every year for life. Includes all future upgrades and features.',
        'feature_3_icon' => 'bi-calendar-heart',
        'feature_3_icon_color' => 'danger',
        'feature_4_title' => 'Lifetime Support',
        'feature_4_value' => 'Priority Forever',
        'feature_4_description' => 'Priority email support for life. As an early adopter, you get special treatment forever.',
        'feature_4_icon' => 'bi-shield-check',
        'feature_4_icon_color' => 'warning'
    ]
];

// Insert features for each product
$total_inserted = 0;
$total_updated = 0;

foreach ($products_features as $product_id => $features) {
    echo "\nProcessing Product ID $product_id...\n";
    
    foreach ($features as $name => $value) {
        // Check if feature exists
        $check_sql = "SELECT id FROM bg_product_features 
                     WHERE product_id = :product_id AND name = :name";
        $exists = $database->get_row($check_sql, [
            'product_id' => $product_id,
            'name' => $name
        ]);
        
        if ($exists) {
            // Update existing
            $update_sql = "UPDATE bg_product_features 
                          SET value = :value, status = 'active', modify_dt = NOW()
                          WHERE product_id = :product_id AND name = :name";
            $database->query($update_sql, [
                'product_id' => $product_id,
                'name' => $name,
                'value' => $value
            ]);
            echo "  Updated: $name\n";
            $total_updated++;
        } else {
            // Insert new
            $insert_sql = "INSERT INTO bg_product_features 
                          (product_id, name, value, status, create_dt, modify_dt)
                          VALUES (:product_id, :name, :value, 'active', NOW(), NOW())";
            $database->query($insert_sql, [
                'product_id' => $product_id,
                'name' => $name,
                'value' => $value
            ]);
            echo "  Inserted: $name\n";
            $total_inserted++;
        }
    }
}

echo "\n========================================\n";
echo "Complete! Inserted: $total_inserted, Updated: $total_updated\n";
echo "</pre>";

// Add link to test
echo '<p><a href="/myaccount/plan-details.php?product_id=11" class="btn btn-primary">Test Gold Plan Details</a></p>';
echo '<p><a href="/myaccount/plan-details.php?product_id=1" class="btn btn-secondary">Test Free Plan Details</a></p>';
echo '<p><a href="/myaccount/plan-details.php?product_id=21" class="btn btn-success">Test Life Plan Details</a></p>';
?>