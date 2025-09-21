<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

header('Content-Type: text/plain');
echo "Checking policies for company ID: $company_id\n";
echo "==========================================\n\n";

// Check bg_company_attributes for policy URLs
echo "1. Policy URLs in bg_company_attributes:\n";
$sql = "SELECT * FROM bg_company_attributes 
        WHERE company_id = :company_id 
        AND type = 'url' 
        AND `grouping` = 'policies'
        ORDER BY name";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$urls = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($urls as $url) {
    echo "   - {$url['name']}: {$url['description']}\n";
    echo "     Status: {$url['status']}, Created: {$url['create_dt']}\n\n";
}

// Check bg_company_policies
echo "\n2. Policies in bg_company_policies:\n";
$sql = "SELECT * FROM bg_company_policies 
        WHERE company_id = :company_id 
        ORDER BY policy_type, version DESC";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$policies = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($policies as $policy) {
    echo "   - Type: {$policy['policy_type']}, Version: {$policy['version']}\n";
    echo "     Name: {$policy['policy_name']}\n";
    echo "     URL: {$policy['url']}\n";
    echo "     Status: {$policy['status']}\n";
    echo "     Hash: " . substr($policy['content_hash'], 0, 16) . "...\n";
    echo "     Last Verified: {$policy['last_verified']}\n\n";
}

// Check onboarding progress
echo "\n3. ABO Progress for terms and privacy:\n";
$sql = "SELECT * FROM bg_company_attributes 
        WHERE company_id = :company_id 
        AND type = 'onboarding_progress'
        AND name IN ('abo_grabterms', 'abo_grabprivacy')";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($progress as $prog) {
    echo "   - {$prog['name']}: {$prog['description']}\n";
    echo "     Modified: {$prog['modify_dt']}\n\n";
}