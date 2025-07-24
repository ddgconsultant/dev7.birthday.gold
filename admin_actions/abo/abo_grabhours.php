<?php
// abo_grabhours.php - DEPRECATED/DISABLED
// Business hours collection is handled by abo_grablocations.php
// This file exists only for backwards compatibility

header('Content-Type: application/json');
echo json_encode([
    'status' => 'disabled',
    'message' => 'Business hours collection is handled by abo_grablocations processor',
    'recommendation' => 'Use abo_grablocations which captures business hours from structured data and Google Places API',
    'data_location' => 'bg_company_locations.business_hours column',
    'disabled_date' => '2025-07-23'
]);