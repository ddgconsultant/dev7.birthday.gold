<?php
header('Content-Type: text/plain');

// Direct database test without full framework
try {
    $dbHost = 'localhost';
    $dbName = 'birthday_gold';
    $dbUser = 'root'; // adjust as needed
    $dbPass = ''; // adjust as needed
    
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== SQL QUERY TEST ===\n\n";
    
    // Test the problematic query
    $sql = "SELECT t.*, 
            cl.location_id as cl_location_id,
            cl.address as cl_address,
            cl.city as cl_city,
            cl.state as cl_state,
            cl.zip_code as cl_zip_code,
            c.company_name,
            c.company_logo,
            c.latitude as c_latitude,
            c.longitude as c_longitude,
            c.address as c_address,
            c.city as c_city,
            c.state as c_state,
            c.zip_code as c_zip_code
            FROM bg_user_tours t 
            LEFT JOIN bg_companies c ON t.company_id = c.company_id
            LEFT JOIN bg_company_locations cl ON t.location_id = cl.location_id 
            WHERE t.calendar_dt = :date 
            AND t.user_id = :user_id 
            ORDER BY t.rank ASC
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => '2025-07-03', ':user_id' => 1]);
    
    echo "Query executed successfully!\n";
    echo "Rows returned: " . $stmt->rowCount() . "\n\n";
    
    // Show column structure
    echo "=== COLUMN STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE bg_company_locations");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %s\n", $row['Field'], $row['Type']);
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}