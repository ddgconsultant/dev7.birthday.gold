<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "Database class exists: " . (class_exists('Database') ? 'Yes' : 'No') . "<br>";
echo "Database object exists: " . (isset($database) ? 'Yes' : 'No') . "<br>";

if (isset($database)) {
    try {
        $test_sql = "SELECT COUNT(*) as count FROM bg_content WHERE status = 'active'";
        $result = $database->getrow($test_sql);
        echo "Database query successful. Count: " . ($result['count'] ?? 'null') . "<br>";
    } catch (Exception $e) {
        echo "Database error: " . $e->getMessage() . "<br>";
    }
}

echo "User logged in: " . ($account->isloggedin() ? 'Yes' : 'No') . "<br>";
echo "User is staff: " . ($account->isstaff() ? 'Yes' : 'No') . "<br>";
echo "User is admin: " . ($account->isadmin() ? 'Yes' : 'No') . "<br>";
?>