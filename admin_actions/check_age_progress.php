<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

header('Content-Type: text/plain');

// Check company status
$company_sql = "SELECT company_id, company_name, status FROM bg_companies WHERE company_id = :id";
$stmt = $database->query($company_sql, ['id' => $company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Company: " . print_r($company, true) . "\n";

// Check onboarding progress
$progress_sql = "SELECT * FROM bg_company_attributes 
                WHERE company_id = :id 
                AND type = 'onboarding_progress' 
                AND name = 'abo_grabage'";
$stmt = $database->query($progress_sql, ['id' => $company_id]);
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nAge Progress Records:\n";
foreach ($progress as $p) {
    echo "- Status: {$p['description']}, Created: {$p['create_dt']}, Modified: {$p['modify_dt']}\n";
}

// Check existing age data
$age_sql = "SELECT * FROM bg_company_attributes 
           WHERE company_id = :id 
           AND type IN ('age_requirements', 'requirement') 
           AND name IN ('birthday_program', 'minimum_age', 'maximum_age')";
$stmt = $database->query($age_sql, ['id' => $company_id]);
$age_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nExisting Age Data:\n";
foreach ($age_data as $a) {
    echo "- Type: {$a['type']}, Name: {$a['name']}, Value: {$a['description']}\n";
}