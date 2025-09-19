<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Setup Business Ownership and Business Plan Product
// This script handles:
// 1. Add user_id 20 as owner to company_id 99
// 2. Insert Business plan product (ID 995099) for Birthday.Gold  
// 3. Create unlimited product features for plan 995099

echo "=== Birthday.Gold Business Ownership Setup ===" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

try {
    // Task 1: Add user_id 20 as owner to company_id 99
    echo "Task 1: Adding user_id 20 as owner to company_id 99..." . PHP_EOL;
    
    // Check if ownership record already exists
    $check_sql = "SELECT * FROM bg_company_attributes 
                  WHERE company_id = :company_id 
                  AND type = 'business_owner' 
                  AND value = '20'";
    $stmt = $database->prepare($check_sql);
    $stmt->execute(['company_id' => 99]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        // Insert ownership record
        $owner_sql = "INSERT INTO bg_company_attributes 
                      (company_id, type, name, value, description, status, create_dt, modify_dt, grouping)
                      VALUES 
                      (:company_id, 'business_owner', 'owner_user_id', :user_id, :description, 'active', NOW(), NOW(), 'ownership')";
        
        $stmt = $database->prepare($owner_sql);
        $stmt->execute([
            'company_id' => 99,
            'user_id' => '20',
            'description' => 'Business owner: User ID 20'
        ]);
        echo "✓ Added business owner record for user_id 20" . PHP_EOL;
        
        // Add management record
        $mgmt_sql = "INSERT INTO bg_company_attributes 
                     (company_id, type, name, value, description, status, create_dt, modify_dt, grouping)
                     VALUES 
                     (:company_id, 'business_management', 'manager_user_id', :user_id, :description, 'active', NOW(), NOW(), 'management')";
        
        $stmt = $database->prepare($mgmt_sql);
        $stmt->execute([
            'company_id' => 99,
            'user_id' => '20',
            'description' => 'Business manager: User ID 20'
        ]);
        echo "✓ Added business management record for user_id 20" . PHP_EOL;
    } else {
        echo "⚠ Ownership record already exists for user_id 20" . PHP_EOL;
    }
    
    echo PHP_EOL;

    // Task 2: Insert Business plan product (ID 995099)
    echo "Task 2: Creating Business plan product (ID 995099)..." . PHP_EOL;
    
    // Check if product exists
    $product_check = "SELECT * FROM bg_products WHERE product_id = 995099";
    $stmt = $database->prepare($product_check);
    $stmt->execute();
    $existing_product = $stmt->fetch();
    
    if (!$existing_product) {
        $product_sql = "INSERT INTO bg_products 
                        (product_id, product_name, product_description, product_type, price, status, create_dt, modify_dt)
                        VALUES 
                        (:product_id, :name, :description, :type, :price, 'active', NOW(), NOW())";
        
        $stmt = $database->prepare($product_sql);
        $stmt->execute([
            'product_id' => 995099,
            'name' => 'Birthday.Gold Business Plan',
            'description' => 'Comprehensive business management and marketing platform for Birthday.Gold partners',
            'type' => 'subscription',
            'price' => 0.00
        ]);
        echo "✓ Created Business plan product (ID: 995099)" . PHP_EOL;
    } else {
        echo "⚠ Product 995099 already exists, updating..." . PHP_EOL;
        $update_sql = "UPDATE bg_products 
                       SET product_name = :name, 
                           product_description = :description,
                           modify_dt = NOW()
                       WHERE product_id = 995099";
        $stmt = $database->prepare($update_sql);
        $stmt->execute([
            'name' => 'Birthday.Gold Business Plan',
            'description' => 'Comprehensive business management and marketing platform for Birthday.Gold partners'
        ]);
        echo "✓ Updated existing Business plan product" . PHP_EOL;
    }
    
    echo PHP_EOL;

    // Task 3: Create unlimited product features
    echo "Task 3: Creating unlimited product features for plan 995099..." . PHP_EOL;
    
    // Clear existing features for clean slate
    $delete_sql = "DELETE FROM bg_product_features WHERE product_id = 995099";
    $stmt = $database->prepare($delete_sql);
    $stmt->execute();
    echo "✓ Cleared existing features" . PHP_EOL;
    
    // Define unlimited features
    $features = [
        ['marketing_campaigns', -1, 'unlimited', 'Unlimited marketing campaigns'],
        ['email_sends', -1, 'unlimited', 'Unlimited email sends per month'],
        ['newsletter_subscribers', -1, 'unlimited', 'Unlimited newsletter subscribers'],
        ['analytics_reports', -1, 'unlimited', 'Unlimited analytics and reporting'],
        ['platform_integrations', -1, 'unlimited', 'Unlimited platform integrations'],
        ['data_storage', -1, 'unlimited', 'Unlimited data storage'],
        ['api_calls', -1, 'unlimited', 'Unlimited API calls per month'],
        ['custom_branding', 1, 'boolean', 'Custom branding enabled'],
        ['priority_support', 1, 'boolean', 'Priority customer support'],
        ['advanced_analytics', 1, 'boolean', 'Advanced analytics and insights'],
        ['white_label', 1, 'boolean', 'White label solution'],
        ['multi_user_access', -1, 'unlimited', 'Unlimited user accounts'],
        ['campaign_automation', 1, 'boolean', 'Campaign automation features'],
        ['advanced_segmentation', 1, 'boolean', 'Advanced audience segmentation'],
        ['calendar_management', -1, 'unlimited', 'Unlimited calendar events and management'],
        ['reporting_exports', -1, 'unlimited', 'Unlimited report exports']
    ];
    
    $feature_sql = "INSERT INTO bg_product_features 
                    (product_id, feature_name, feature_value, feature_type, description, status, create_dt)
                    VALUES 
                    (995099, :name, :value, :type, :description, 'active', NOW())";
    
    $stmt = $database->prepare($feature_sql);
    $feature_count = 0;
    
    foreach ($features as $feature) {
        $stmt->execute([
            'name' => $feature[0],
            'value' => $feature[1],
            'type' => $feature[2],
            'description' => $feature[3]
        ]);
        $feature_count++;
    }
    
    echo "✓ Created {$feature_count} unlimited product features" . PHP_EOL;
    echo PHP_EOL;
    
    // Verification
    echo "=== VERIFICATION ===" . PHP_EOL;
    
    // Verify ownership
    $verify_owner = "SELECT * FROM bg_company_attributes 
                     WHERE company_id = 99 AND type IN ('business_owner', 'business_management')
                     ORDER BY type, create_dt";
    $stmt = $database->prepare($verify_owner);
    $stmt->execute();
    $ownership_records = $stmt->fetchAll();
    
    echo "Company 99 ownership records: " . count($ownership_records) . PHP_EOL;
    foreach ($ownership_records as $record) {
        echo "  - {$record['type']}: {$record['name']} = {$record['value']} ({$record['description']})" . PHP_EOL;
    }
    
    // Verify product
    $verify_product = "SELECT * FROM bg_products WHERE product_id = 995099";
    $stmt = $database->prepare($verify_product);
    $stmt->execute();
    $product = $stmt->fetch();
    
    echo "Product 995099: " . ($product ? $product['product_name'] : 'Not found') . PHP_EOL;
    
    // Verify features
    $verify_features = "SELECT COUNT(*) as feature_count FROM bg_product_features WHERE product_id = 995099";
    $stmt = $database->prepare($verify_features);
    $stmt->execute();
    $feature_result = $stmt->fetch();
    
    echo "Product features: {$feature_result['feature_count']}" . PHP_EOL;
    echo PHP_EOL;
    
    echo "🎉 ALL TASKS COMPLETED SUCCESSFULLY!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== END SETUP ===" . PHP_EOL;
?>