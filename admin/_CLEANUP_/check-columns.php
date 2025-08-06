<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

if (!$account->isadmin()) die("Admin only");

header('Content-Type: text/plain');

echo "=== CHECKING TABLE COLUMNS ===\n\n";

// Check bg_companies columns
echo "bg_companies columns:\n";
$stmt = $database->query("SHOW COLUMNS FROM bg_companies");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n\nbg_company_locations columns:\n";
$stmt = $database->query("SHOW COLUMNS FROM bg_company_locations");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Test the problematic query
echo "\n\n=== TESTING TOUR QUERY ===\n";
try {
    $sql = "SELECT t.*, 
            cl.location_id as cl_location_id,
            cl.address as cl_address,
            cl.city as cl_city,
            cl.state as cl_state,
            cl.zip_code as cl_zip_code,
            cl.latitude as cl_latitude,
            cl.longitude as cl_longitude,
            c.company_id,
            c.company_name
            FROM bg_user_tours t 
            LEFT JOIN bg_companies c ON t.company_id = c.company_id
            LEFT JOIN bg_company_locations cl ON t.location_id = cl.location_id 
            WHERE t.calendar_dt = :date 
            AND t.user_id = :user_id 
            ORDER BY t.rank ASC
            LIMIT 1";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([':date' => date('Y-m-d'), ':user_id' => 1]);
    echo "Query executed successfully!\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>