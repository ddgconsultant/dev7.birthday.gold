<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Find a company with locations
$sql = "SELECT c.company_id, c.company_name, c.company_url, COUNT(l.location_id) as location_count
        FROM bg_companies c
        INNER JOIN bg_company_locations l ON c.company_id = l.company_id
        WHERE c.status = 'active'
        GROUP BY c.company_id
        HAVING location_count > 2
        ORDER BY location_count DESC
        LIMIT 5";

$stmt = $database->query($sql);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/plain');
echo "Companies with multiple locations:\n";
echo "==================================\n\n";

foreach ($companies as $company) {
    echo "Company: {$company['company_name']} (ID: {$company['company_id']})\n";
    echo "URL: {$company['company_url']}\n";
    echo "Locations: {$company['location_count']}\n\n";
    
    // Show first 3 locations
    $loc_sql = "SELECT address, city, state, zip_code, source 
                FROM bg_company_locations 
                WHERE company_id = :cid 
                LIMIT 3";
    $loc_stmt = $database->query($loc_sql, ['cid' => $company['company_id']]);
    $locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($locations as $loc) {
        echo "  - {$loc['address']}, {$loc['city']}, {$loc['state']} {$loc['zip_code']} (source: {$loc['source']})\n";
    }
    echo "\n";