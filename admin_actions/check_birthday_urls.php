<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

// Check what URLs were found and checked
$sql = "SELECT name, description, create_dt 
        FROM bg_company_attributes 
        WHERE company_id = :id 
        AND type = 'data_collection'
        AND name IN ('birthday_urls_checked', 'program_urls_found')
        ORDER BY create_dt DESC";

$stmt = $database->query($sql, ['id' => $company_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/plain');
echo "Birthday/Program URL Discovery for Company $company_id:\n";
echo "==============================================\n\n";

foreach ($results as $row) {
    echo "Type: {$row['name']}\n";
    echo "Time: {$row['create_dt']}\n";
    if (substr($row['description'], 0, 1) === '[') {
        $urls = json_decode($row['description'], true);
        foreach ($urls as $i => $url) {
            echo "  URL $i: $url\n";
        }
    } else {
        echo "  URL: {$row['description']}\n";
    }
    echo "\n";
}