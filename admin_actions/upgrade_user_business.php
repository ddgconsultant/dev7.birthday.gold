<?php
// Quick script to upgrade user to business account
include '../core/site-controller.php';

// Admin check
if (!$account->isadmin()) {
    die("Admin access required");
}

$user_id = $_GET['user_id'] ?? 20;

try {
    // Update user to business account
    $sql = "UPDATE bg_users 
            SET account_type = 'business',
                modify_dt = NOW()
            WHERE user_id = :user_id";
    
    $database->query($sql, ['user_id' => $user_id]);
    
    // Get user details to confirm
    $user = $database->get_row("SELECT user_id, email, firstname, lastname, account_type 
                                FROM bg_users 
                                WHERE user_id = :user_id", 
                                ['user_id' => $user_id]);
    
    echo "User upgraded successfully!\n";
    echo "User ID: {$user['user_id']}\n";
    echo "Name: {$user['firstname']} {$user['lastname']}\n";
    echo "Email: {$user['email']}\n";
    echo "Account Type: {$user['account_type']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>