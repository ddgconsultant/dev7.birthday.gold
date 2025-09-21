<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

// Get company social media data
$company = $database->query("SELECT company_id, company_name, facebook, twitter, instagram, tiktok 
    FROM bg_companies WHERE company_id = :id", 
    ['id' => $company_id])->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($company, JSON_PRETTY_PRINT);