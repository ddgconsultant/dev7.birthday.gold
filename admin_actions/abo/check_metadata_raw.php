<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

// Get metadata attributes
$sql = "SELECT name, description FROM bg_company_attributes 
        WHERE company_id = :id AND type = 'metadata'
        ORDER BY name";
$stmt = $database->query($sql, ['id' => $company_id]);
$metadata = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = ['company_id' => $company_id, 'metadata' => []];

foreach ($metadata as $row) {
    // Try to decode JSON if it looks like JSON
    if (substr($row['description'], 0, 1) === '[' || substr($row['description'], 0, 1) === '{') {
        $result['metadata'][$row['name']] = json_decode($row['description'], true);
    } else {
        $result['metadata'][$row['name']] = $row['description'];
    }
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);