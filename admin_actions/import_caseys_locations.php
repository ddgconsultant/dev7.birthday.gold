<?php
/**
 * Import Casey's locations from OpenStreetMap data
 * Run this via browser: https://dev7.birthday.gold/admin_actions/import_caseys_locations.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php';

$company_id = 5886;

// Check current count
$stmt = $database->prepare("SELECT COUNT(*) FROM bg_company_locations WHERE company_id = ?");
$stmt->execute([$company_id]);
$before_count = $stmt->fetchColumn();

// Disable output buffering for real-time display
if (ob_get_level()) ob_end_flush();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
set_time_limit(300);

echo "<pre>\n";
echo "Casey's Locations Import\n";
echo "========================\n";
echo "Company ID: $company_id\n";
echo "Existing locations: $before_count\n\n";
flush();

// Read the SQL file
$sqlFile = __DIR__ . '/caseys_locations_import.sql';
if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(";\n\n", $sql)),
    function($s) { return stripos($s, 'INSERT INTO') !== false; }
);

echo "Found " . count($statements) . " INSERT statements\n";
echo "Importing...\n\n";

$total_inserted = 0;
$errors = [];

foreach ($statements as $i => $stmt_sql) {
    try {
        $stmt_sql = trim($stmt_sql);
        if (empty($stmt_sql) || strpos($stmt_sql, 'INSERT') === false) continue;

        // Remove trailing semicolon for query()
        $stmt_sql = rtrim($stmt_sql, ';');

        $stmt = $database->query($stmt_sql);
        $result = $stmt->rowCount();
        $total_inserted += $result;
        echo "Batch " . ($i + 1) . ": inserted $result rows\n";
    } catch (Exception $e) {
        $errors[] = "Batch " . ($i + 1) . ": " . $e->getMessage();
        echo "Batch " . ($i + 1) . ": ERROR - " . $e->getMessage() . "\n";
    }
}

// Get final count
$stmt = $database->prepare("SELECT COUNT(*) FROM bg_company_locations WHERE company_id = ?");
$stmt->execute([$company_id]);
$after_count = $stmt->fetchColumn();

echo "\n========================\n";
echo "Import Complete!\n";
echo "Rows inserted: $total_inserted\n";
echo "Locations before: $before_count\n";
echo "Locations after: $after_count\n";
echo "Net change: " . ($after_count - $before_count) . "\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
}

echo "</pre>\n";
