<?php
$nosessiontracking=true;
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Function to check MySQL replication status for all channels
function checkReplicationStatus($database) {
    $sql = 'SHOW REPLICA STATUS';
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $allRunning = true;
    $output = '';
    $count=0;
    foreach ($statuses as $status) {
        $count++;
        if ($status['Replica_IO_Running'] == 'Yes' && $status['Replica_SQL_Running'] == 'Yes') {
            $output .= 'Channel ' . $status['Channel_Name'] . ' is running: Yes-Running: ' . date('r') . "\n";
        } else {
            $allRunning = false;
            $output .= 'Channel ' . $status['Channel_Name'] . ' is not running.' . "\n";
        }
    }
    
    return [$allRunning, $count, $output];
}

// Check replication status
list($isAllRunning, $count, $output) = checkReplicationStatus($database);

// Return appropriate HTTP status code and message
if ($isAllRunning) {
    http_response_code(200);
    echo 'ALL OK ['.$count.'] - '.$output;
} else {
    http_response_code(500);
    echo 'UH OH ['.$count.'] - '.$output;
}
