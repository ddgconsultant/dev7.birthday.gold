<?php
// Include site controller
include '../../core/site-controller.php';

// Set headers for JSON output
header('Content-Type: application/json');

// Query for all onboarding_progress records for company_id 6231
$query = "SELECT * FROM bg_company_attributes 
          WHERE company_id = 6231 
          AND type = 'onboarding_progress' 
          ORDER BY name ASC";

$stmt = $database->query($query);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process results to handle NULL values properly
$processed_results = [];
foreach ($results as $row) {
    $processed_row = [];
    foreach ($row as $key => $value) {
        // Convert NULL to string "NULL" for clear visibility
        $processed_row[$key] = $value === null ? 'NULL' : $value;
    }
    $processed_results[] = $processed_row;
}

// Output as formatted JSON
echo json_encode([
    'company_id' => 6231,
    'type' => 'onboarding_progress',
    'total_records' => count($results),
    'records' => $processed_results
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>