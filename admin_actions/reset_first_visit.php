<?php
// Reset first visit attributes for testing
$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
$addClasses[] = 'account';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$username = 'DavisJr209';
$user_id = 7181;

echo "Resetting first visit attributes for user: $username (ID: $user_id)\n\n";

// Get current attributes
$first_visit = $account->getUserAttribute($user_id, 'first_profile_visit');
$first_save = $account->getUserAttribute($user_id, 'first_profile_save');

echo "Current status:\n";
echo "- first_profile_visit: " . ($first_visit ? "Set on {$first_visit['description']}" : "Not set") . "\n";
echo "- first_profile_save: " . ($first_save ? "Set on {$first_save['description']}" : "Not set") . "\n\n";

// Delete the attributes by setting them to inactive/deleted
if ($first_visit) {
    $sql = "UPDATE bg_user_attributes SET status = 'deleted' WHERE user_id = :user_id AND name = 'first_profile_visit'";
    $database->query($sql, ['user_id' => $user_id]);
    echo "Deleted first_profile_visit attribute\n";
}

if ($first_save) {
    $sql = "UPDATE bg_user_attributes SET status = 'deleted' WHERE user_id = :user_id AND name = 'first_profile_save'";
    $database->query($sql, ['user_id' => $user_id]);
    echo "Deleted first_profile_save attribute\n";
}

// Verify deletion
$first_visit_check = $account->getUserAttribute($user_id, 'first_profile_visit');
$first_save_check = $account->getUserAttribute($user_id, 'first_profile_save');

echo "\nVerification:\n";
echo "- first_profile_visit: " . ($first_visit_check ? "STILL EXISTS" : "Successfully removed") . "\n";
echo "- first_profile_save: " . ($first_save_check ? "STILL EXISTS" : "Successfully removed") . "\n";

echo "\nDone! User $username can now experience the first visit flow again.\n";
?>