<?php
/**
 * Seed enrollment summary attributes for all users
 *
 * This initializes the 'last-sent-datetime' attribute for all active users
 * to prevent mass notification spam when the enrollment summary system is first activated.
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h1>Seeding Enrollment Summary Attributes</h1>\n";
echo "<p>Started: " . date('Y-m-d H:i:s') . "</p>\n";

// Get all active real users who don't already have the attribute
$query = "SELECT u.user_id, u.profile_first_name, u.profile_last_name, u.profile_email
FROM bg_users u
LEFT JOIN bg_user_attributes ua ON u.user_id = ua.user_id
    AND ua.type = 'enrollment-summary'
    AND ua.name = 'last-sent-datetime'
    AND ua.status = 'active'
WHERE u.status = 'active'
    AND u.type = 'real'
    AND ua.attribute_id IS NULL";

$stmt = $database->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($users) . " users without enrollment summary attribute</p>\n";

$success_count = 0;
$error_count = 0;

// Insert the attribute for each user
foreach ($users as $user) {
    try {
        $insert_query = "INSERT INTO bg_user_attributes
            (user_id, type, name, description, string_value, status, create_dt, modify_dt)
            VALUES
            (:user_id, 'enrollment-summary', 'last-sent-datetime',
             'Last enrollment summary notification sent', NOW(), 'active', NOW(), NOW())";

        $insert_stmt = $database->prepare($insert_query);
        $insert_stmt->execute([':user_id' => $user['user_id']]);

        $success_count++;

        if ($success_count % 100 == 0) {
            echo "<p>Processed {$success_count} users...</p>\n";
            flush();
        }

    } catch (Exception $e) {
        $error_count++;
        echo "<p style='color: red;'>Error for user {$user['user_id']}: " .
             htmlspecialchars($e->getMessage()) . "</p>\n";
    }
}

echo "\n<h2>Summary</h2>\n";
echo "<ul>\n";
echo "<li>Users seeded: {$success_count}</li>\n";
echo "<li>Errors: {$error_count}</li>\n";
echo "<li>Completed: " . date('Y-m-d H:i:s') . "</li>\n";
echo "</ul>\n";

session_tracking('enrollment_summary_seeding_complete', [
    'success_count' => $success_count,
    'error_count' => $error_count
]);

echo "<p><strong>✅ Seeding complete! Enrollment summary notifications are now safe to activate.</strong></p>\n";
