<?php
header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

$locationiq_key = 'YOUR_LOCATIONIQ_API_KEY'; // Leave blank to skip
$results = null;

// ---- Try LocationIQ First ----
if (!empty($locationiq_key)) {
    $url = 'https://us1.locationiq.com/v1/search.php?key=' . urlencode($locationiq_key)
         . '&q=' . urlencode($query) . '&format=json&limit=5';

    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: BirthdayGoldLookup/1.0\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            $results = array_map(function($item) {
                return [
                    'display_name' => $item['display_name'],
                    'lat' => $item['lat'],
                    'lon' => $item['lon'],
                    'address' => $item['address'] ?? []
                ];
            }, $decoded);
        }
    }
}

// ---- Fallback to Nominatim if needed ----
if (empty($results)) {
    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' . urlencode($query);
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: BirthdayGoldLookup/1.0\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            $results = array_map(function($item) {
                return [
                    'display_name' => $item['display_name'],
                    'lat' => $item['lat'],
                    'lon' => $item['lon'],
                    'address' => $item['address'] ?? []
                ];
            }, $decoded);
        }
    }
}

echo json_encode($results ?: []);
