<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

header('Content-Type: text/plain');
echo "ABO Progress for Company ID: $company_id\n";
echo "==========================================\n\n";

// Get company info
$sql = "SELECT * FROM bg_companies WHERE company_id = :company_id";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Company: {$company['company_name']}\n";
echo "Status: {$company['status']}\n\n";

// Get ABO progress
$sql = "SELECT ca.*, c.config_value 
        FROM bg_company_attributes ca
        LEFT JOIN bg_config c ON c.config_key COLLATE utf8mb4_unicode_ci = ca.name COLLATE utf8mb4_unicode_ci 
            AND c.config_type = 'automation_processor'
        WHERE ca.company_id = :company_id 
        AND ca.type = 'onboarding_progress'
        ORDER BY c.display_order";
$stmt = $database->query($sql, ['company_id' => $company_id]);

echo "ABO Progress:\n";
echo "-------------\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $status_icon = $row['description'] === 'completed' ? '✓' : ($row['description'] === 'error' ? '✗' : '⋯');
    echo "$status_icon " . str_pad($row['config_value'] ?? $row['name'], 35) . " => {$row['description']}\n";
}

// Check if validation has been completed
$sql = "SELECT * FROM bg_company_attributes 
        WHERE company_id = :company_id 
        AND type = 'ai_validation'
        AND name IN ('validation_score', 'validation_status')";
$stmt = $database->query($sql, ['company_id' => $company_id]);

echo "\nValidation Results:\n";
echo "-------------------\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['name']}: {$row['description']}\n";
}

// Check for validation issues
$sql = "SELECT * FROM bg_company_attributes 
        WHERE company_id = :company_id 
        AND type = 'validation_issue'
        AND status = 'active'";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($issues) > 0) {
    echo "\nValidation Issues:\n";
    echo "------------------\n";
    foreach ($issues as $issue) {
        echo "- {$issue['description']}\n";
    }
}