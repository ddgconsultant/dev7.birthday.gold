<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
if (!$account->isadmin()) die("Admin only");

header('Content-Type: text/plain');

echo "=== bg_user_tours columns ===\n";
$stmt = $database->query("SHOW COLUMNS FROM bg_user_tours");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>