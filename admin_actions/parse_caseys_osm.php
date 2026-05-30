<?php
// Parse Casey's OSM data and generate SQL inserts
$json = file_get_contents("/tmp/caseys_osm.json");
$data = json_decode($json, true);

if (!$data || !isset($data['elements'])) {
    echo "Error parsing JSON\n";
    exit(1);
}

$locations = [];
$seen = [];

foreach ($data['elements'] as $el) {
    // Get coordinates (for ways/relations, use center)
    $lat = $el['lat'] ?? ($el['center']['lat'] ?? null);
    $lon = $el['lon'] ?? ($el['center']['lon'] ?? null);

    if (!$lat || !$lon) continue;

    $tags = $el['tags'] ?? [];

    // Build address from tags
    $address = "";
    if (isset($tags['addr:housenumber']) && isset($tags['addr:street'])) {
        $address = $tags['addr:housenumber'] . " " . $tags['addr:street'];
    } elseif (isset($tags['addr:full'])) {
        $address = $tags['addr:full'];
    }

    $city = $tags['addr:city'] ?? "";
    $state = $tags['addr:state'] ?? "";
    $zip = $tags['addr:postcode'] ?? "";

    // Skip if no useful address info
    if (empty($address) && empty($city)) continue;

    // Create unique key to avoid duplicates
    $key = strtoupper(trim("$address|$city|$state"));
    if (isset($seen[$key])) continue;
    $seen[$key] = true;

    $locations[] = [
        'address' => strtoupper(trim($address)),
        'city' => strtoupper(trim($city)),
        'state' => strtoupper(trim($state)),
        'zip' => trim($zip),
        'lat' => $lat,
        'lon' => $lon
    ];
}

echo "Total unique locations with addresses: " . count($locations) . "\n";

// Group by state
$byState = [];
foreach ($locations as $loc) {
    $st = $loc['state'] ?: 'UNKNOWN';
    if (!isset($byState[$st])) $byState[$st] = 0;
    $byState[$st]++;
}

arsort($byState);
echo "\nBy State:\n";
foreach ($byState as $st => $count) {
    echo "  $st: $count\n";
}

// Generate SQL file
$sqlFile = "/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/admin_actions/caseys_locations_import.sql";
$sql = "-- Casey's General Store Locations (from OpenStreetMap)\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Total locations: " . count($locations) . "\n\n";

$sql .= "-- Delete existing locations for company 5886 first (optional - uncomment if needed)\n";
$sql .= "-- DELETE FROM bg_company_locations WHERE company_id = 5886;\n\n";

$chunks = array_chunk($locations, 100);
foreach ($chunks as $chunk) {
    $sql .= "INSERT INTO bg_company_locations (company_id, source, address, city, state, zip_code, country, latitude, longitude, status, create_dt, modify_dt) VALUES\n";
    $values = [];
    foreach ($chunk as $loc) {
        $addr = addslashes($loc['address']);
        $city = addslashes($loc['city']);
        $state = addslashes($loc['state']);
        $zip = addslashes($loc['zip']);
        $lat = $loc['lat'];
        $lon = $loc['lon'];
        $values[] = "(5886, 'openstreetmap', '$addr', '$city', '$state', '$zip', 'US', $lat, $lon, 'active', NOW(), NOW())";
    }
    $sql .= implode(",\n", $values) . ";\n\n";
}

file_put_contents($sqlFile, $sql);
echo "\nSQL file written to: $sqlFile\n";
