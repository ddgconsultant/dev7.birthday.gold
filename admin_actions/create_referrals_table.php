<?php
/**
 * Migration script to create bg_referrals table
 * Run this script to create the missing referrals table
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Ensure admin access
if (!$account->isadmin()) {
    die('Unauthorized - Admin access required');
}

echo "<h2>Creating bg_referrals Table</h2>";

try {
    // Check if table already exists
    $checkTable = $database->prepare("SHOW TABLES LIKE 'bg_referrals'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() > 0) {
        echo "<p style='color: orange;'>Table 'bg_referrals' already exists. No action needed.</p>";
    } else {
        // Create the table
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `bg_referrals` (
            `referral_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL COMMENT 'The user who made the referral',
            `referred_user_id` int(11) DEFAULT NULL COMMENT 'The new user who was referred',
            `referral_code` varchar(50) DEFAULT NULL COMMENT 'Unique referral code used',
            `referral_email` varchar(255) DEFAULT NULL COMMENT 'Email of the person referred',
            `referral_status` enum('pending','completed','expired','cancelled') DEFAULT 'pending',
            `referral_type` varchar(50) DEFAULT 'standard' COMMENT 'Type of referral campaign',
            `reward_given` tinyint(1) DEFAULT 0 COMMENT 'Whether reward was given to referrer',
            `reward_amount` decimal(10,2) DEFAULT NULL COMMENT 'Amount or value of reward',
            `reward_type` varchar(50) DEFAULT NULL COMMENT 'Type of reward (credit, discount, etc)',
            `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of referral',
            `source` varchar(100) DEFAULT NULL COMMENT 'Source of referral (email, social, etc)',
            `create_dt` datetime DEFAULT current_timestamp(),
            `modify_dt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `complete_dt` datetime DEFAULT NULL COMMENT 'When referral was completed',
            PRIMARY KEY (`referral_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_referred_user_id` (`referred_user_id`),
            KEY `idx_referral_code` (`referral_code`),
            KEY `idx_referral_email` (`referral_email`),
            KEY `idx_status` (`referral_status`),
            KEY `idx_create_dt` (`create_dt`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $database->query($createTableSQL);
        
        // Verify table was created
        $checkTable->execute();
        if ($checkTable->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table 'bg_referrals' created successfully!</p>";
            
            // Show table structure
            $describeTable = $database->query("DESCRIBE bg_referrals");
            $columns = $describeTable->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Table Structure:</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                foreach ($column as $value) {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create table.</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='/admin/'>Back to Admin Dashboard</a></p>";
?>