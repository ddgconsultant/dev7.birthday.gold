<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get column information for bg_config
$sql = "SHOW COLUMNS FROM bg_config";
$stmt = $database->query($sql);
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "bg_config table structure:\n";
echo "==========================\n";
foreach ($columns as $col) {
    echo sprintf("%-20s %-20s %s\n", 
        $col['Field'], 
        $col['Type'], 
        ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL')
    );
}

echo "\n\nSample data from bg_config:\n";
echo "==========================\n";

$data_sql = "SELECT * FROM bg_config LIMIT 5";
$data_stmt = $database->query($data_sql);
$data = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($data)) {
    // Print headers
    $headers = array_keys($data[0]);
    foreach ($headers as $header) {
        echo str_pad($header, 20) . " ";
    }
    echo "\n" . str_repeat("-", count($headers) * 21) . "\n";
    
    // Print data
    foreach ($data as $row) {
        foreach ($row as $value) {
            echo str_pad(substr($value ?? '', 0, 19), 20) . " ";
        }
        echo "\n";
    }
}

header('Content-Type: text/plain');