<?php
// test_abo.php - Test ABO system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// No authorization required - handled by site-controller.php

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test 1: Check if bg_config has processors
$test1 = [
    'name' => 'Check bg_config processors',
    'status' => 'pass'
];

$processors = $database->query(
    "SELECT config_key, config_value FROM bg_config 
     WHERE config_type = 'automation_processor' 
     ORDER BY display_order"
)->fetchAll(PDO::FETCH_ASSOC);

$test1['processor_count'] = count($processors);
$test1['processors'] = array_column($processors, 'config_value', 'config_key');

if (count($processors) === 0) {
    $test1['status'] = 'fail';
    $test1['message'] = 'No processors found in bg_config';
}

$result['tests'][] = $test1;

// Test 2: Check for companies with onboarding progress
$test2 = [
    'name' => 'Check companies with onboarding progress',
    'status' => 'pass'
];

$companies = $database->query(
    "SELECT c.company_id, c.company_name, c.status,
            COUNT(ca.attribute_id) as progress_count
     FROM bg_companies c
     LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id 
         AND ca.type = 'onboarding_progress'
     WHERE c.source = 'user_recommendation'
     GROUP BY c.company_id
     LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

$test2['company_count'] = count($companies);
$test2['companies'] = $companies;

$result['tests'][] = $test2;

// Test 3: Check if abo_processsubmission.php exists
$test3 = [
    'name' => 'Check abo_processsubmission.php exists',
    'status' => 'pass'
];

$file_path = __DIR__ . '/abo_processsubmission.php';
if (!file_exists($file_path)) {
    $test3['status'] = 'fail';
    $test3['message'] = 'abo_processsubmission.php not found';
} else {
    $test3['file_size'] = filesize($file_path);
}

$result['tests'][] = $test3;

// Output results
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);