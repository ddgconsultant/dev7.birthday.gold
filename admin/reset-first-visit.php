<?php
// Web-accessible reset script for first visit attributes
$addClasses[] = 'account';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is admin
if (!isset($current_user_data['acl']) || $current_user_data['acl'] < 100) {
    die('Access denied - admin only');
}

// Clear session if requested
if (isset($_GET['clear_session'])) {
    session_destroy();
    session_start();
    echo "<div style='background-color: yellow; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Session cleared!</strong> Now re-running reset...";
    echo "</div>";
}

$username = isset($_GET['user']) ? $_GET['user'] : 'DavisJr209';

// Get user data
$user_data = $account->getuserdata($username, 'username');
if (!$user_data) {
    die("User not found: $username");
}

$user_id = $user_data['user_id'];

echo "<h2>Resetting first visit attributes for user: $username (ID: $user_id)</h2>";

// Get current attributes
$first_visit = $account->getUserAttribute($user_id, 'first_profile_visit');
$first_save = $account->getUserAttribute($user_id, 'first_profile_save');

echo "<h3>Current status:</h3>";
echo "<ul>";
echo "<li>first_profile_visit: " . ($first_visit ? "Set on {$first_visit['description']}" : "Not set") . "</li>";
echo "<li>first_profile_save: " . ($first_save ? "Set on {$first_save['description']}" : "Not set") . "</li>";
echo "</ul>";

// Also check raw database
$sql = "SELECT name, status, description FROM bg_user_attributes WHERE user_id = :user_id AND name IN ('first_profile_visit', 'first_profile_save')";
$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$raw_attrs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($raw_attrs) {
    echo "<h3>Raw database records:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Status</th><th>Description</th></tr>";
    foreach ($raw_attrs as $attr) {
        echo "<tr><td>{$attr['name']}</td><td>{$attr['status']}</td><td>{$attr['description']}</td></tr>";
    }
    echo "</table>";
}

// Delete the attributes - use DELETE instead of UPDATE
if ($first_visit || $first_save) {
    echo "<h3>Deleting attributes...</h3>";

    // Delete first_profile_visit
    if ($first_visit) {
        $sql = "DELETE FROM bg_user_attributes WHERE user_id = :user_id AND name = 'first_profile_visit'";
        $stmt = $database->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        echo "<p>Deleted first_profile_visit attribute</p>";
    }

    // Delete first_profile_save
    if ($first_save) {
        $sql = "DELETE FROM bg_user_attributes WHERE user_id = :user_id AND name = 'first_profile_save'";
        $stmt = $database->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        echo "<p>Deleted first_profile_save attribute</p>";
    }

    // Clear ALL possible cached data
    $session->unset('user_attributes_' . $user_id);
    $session->unset('user_attributes');

    // Clear current user data from session if it's the same user
    if ($current_user_data['user_id'] == $user_id) {
        $session->unset('current_user_data');
        $session->unset('user_data');
    }

    // Force reload user data
    $current_user_data = $account->getuserdata($user_id, 'user_id', ['force_refresh' => true]);

    // Verify deletion
    $first_visit_check = $account->getUserAttribute($user_id, 'first_profile_visit');
    $first_save_check = $account->getUserAttribute($user_id, 'first_profile_save');

    echo "<h3>Verification:</h3>";
    echo "<ul>";
    echo "<li>first_profile_visit: " . ($first_visit_check ? "<span style='color:red'>STILL EXISTS</span>" : "<span style='color:green'>Successfully removed</span>") . "</li>";
    echo "<li>first_profile_save: " . ($first_save_check ? "<span style='color:red'>STILL EXISTS</span>" : "<span style='color:green'>Successfully removed</span>") . "</li>";
    echo "</ul>";

    echo "<h3>Done!</h3>";
    echo "<p>User $username can now experience the first visit flow again.</p>";
} else {
    echo "<p>No attributes to reset - user already in clean state.</p>";
}

echo "<hr>";
echo "<p><strong>Important:</strong> The session might be caching old data. Try these steps:</p>";
echo "<ol>";
echo "<li><a href='?user=$username&clear_session=1'>Click here to clear session and reset</a></li>";
echo "<li><a href='/logout.php' target='_blank'>Logout</a> and login again as $username</li>";
echo "<li><a href='/myaccount/welcome'>Go to Welcome Page</a></li>";
echo "</ol>";
echo "<p><a href='?user=$username'>Refresh this page</a></p>";
?>