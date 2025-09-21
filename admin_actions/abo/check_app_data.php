<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

// Get company data
$company = $database->query("SELECT company_id, company_name, appgoogle, appapple FROM bg_companies WHERE company_id = :id", 
    ['id' => $company_id])->fetch(PDO::FETCH_ASSOC);

// Get app attributes
$attrs = $database->query("SELECT * FROM bg_company_attributes 
    WHERE company_id = :id 
    AND type = 'data_collection' 
    AND name LIKE '%google_app%'
    ORDER BY create_dt DESC", 
    ['id' => $company_id])->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'company' => $company,
    'google_app_attributes' => $attrs
], JSON_PRETTY_PRINT);