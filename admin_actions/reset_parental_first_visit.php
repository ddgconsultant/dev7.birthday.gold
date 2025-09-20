<?php
// Reset parental first visit attribute for testing
$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
$addClasses[] = 'account';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user_id is provided in GET request
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
} else {
    // Default to current user
    $user_id = $current_user_data['user_id'];
}

// Get user info
$user = $database->get_row("SELECT username, account_type FROM bg_users WHERE user_id = :user_id", ['user_id' => $user_id]);

if (!$user) {
    echo "User ID $user_id not found.\n";
    exit;
}

echo "<pre>";
echo "Resetting parental first visit attribute for user: {$user['username']} (ID: $user_id)\n";
echo "Account type: {$user['account_type']}\n\n";

// Get current attribute
$first_visit = $account->getUserAttribute($user_id, 'first_parental_visit');

echo "Current status:\n";
echo "- first_parental_visit: " . ($first_visit ? "Set on {$first_visit['description']}" : "Not set") . "\n\n";

// Delete the attribute by setting it to inactive/deleted
if ($first_visit) {
    $sql = "UPDATE bg_user_attributes SET status = 'deleted' WHERE user_id = :user_id AND name = 'first_parental_visit'";
    $database->query($sql, ['user_id' => $user_id]);
    echo "Deleted first_parental_visit attribute\n";
} else {
    echo "No first_parental_visit attribute to delete\n";
}

// Verify deletion
$first_visit_check = $account->getUserAttribute($user_id, 'first_parental_visit');

echo "\nVerification:\n";
echo "- first_parental_visit: " . ($first_visit_check ? "STILL EXISTS" : "Successfully removed") . "\n";

echo "\nDone! User {$user['username']} can now experience the parental mode first visit flow again.\n";
echo "</pre>";

echo "<br><a href='/myaccount/parental-mode' class='btn btn-primary'>Go to Parental Mode</a>";
?>