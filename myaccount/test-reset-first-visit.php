<?php
// Test page for resetting first visit - user must be logged in
$addClasses[] = 'account';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Only works for the currently logged-in user
$user_id = $current_user_data['user_id'];
$username = $current_user_data['username'];

echo "<h2>Reset First Visit Test Page</h2>";
echo "<p>Logged in as: $username (ID: $user_id)</p>";
echo "<p>Account Plan: " . $current_user_data['account_plan'] . "</p>";

// Get current attributes
$first_visit = $account->getUserAttribute($user_id, 'first_profile_visit');
$first_save = $account->getUserAttribute($user_id, 'first_profile_save');

echo "<h3>Before Reset:</h3>";
echo "<ul>";
echo "<li>first_profile_visit: " . ($first_visit ? "EXISTS" : "NOT SET") . "</li>";
echo "<li>first_profile_save: " . ($first_save ? "EXISTS" : "NOT SET") . "</li>";
echo "</ul>";

// Delete the attributes if requested
if (isset($_GET['reset'])) {
    $sql = "DELETE FROM bg_user_attributes WHERE user_id = :user_id AND name IN ('first_profile_visit', 'first_profile_save')";
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);

    // Don't destroy the entire session - just clear the attribute cache
    // session_destroy() loses all user data!
    if (isset($_SESSION['user_attributes_' . $user_id])) {
        unset($_SESSION['user_attributes_' . $user_id]);
    }
    if (isset($_SESSION['user_attributes'])) {
        unset($_SESSION['user_attributes']);
    }

    echo "<div style='background-color: yellow; padding: 10px;'>";
    echo "<strong>Attributes deleted and session cleared!</strong>";
    echo "</div>";

    // Re-check
    $first_visit = $account->getUserAttribute($user_id, 'first_profile_visit');
    $first_save = $account->getUserAttribute($user_id, 'first_profile_save');

    echo "<h3>After Reset:</h3>";
    echo "<ul>";
    echo "<li>first_profile_visit: " . ($first_visit ? "STILL EXISTS!" : "Successfully removed") . "</li>";
    echo "<li>first_profile_save: " . ($first_save ? "STILL EXISTS!" : "Successfully removed") . "</li>";
    echo "</ul>";
}

echo "<hr>";
if (!isset($_GET['reset'])) {
    echo "<p><a href='?reset=1' class='btn btn-danger'>RESET MY FIRST VISIT ATTRIBUTES</a></p>";
}
echo "<p><a href='/myaccount/welcome' class='btn btn-primary'>Go to Welcome Page</a></p>";
?>