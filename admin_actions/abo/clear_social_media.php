<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

// Clear social media fields
$sql = "UPDATE bg_companies 
        SET facebook = NULL, twitter = NULL, instagram = NULL, tiktok = NULL 
        WHERE company_id = :company_id";
$database->query($sql, ['company_id' => $company_id]);

echo json_encode(['status' => 'success', 'message' => "Cleared social media for company $company_id"]);