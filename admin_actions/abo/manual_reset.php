<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['cid'] ?? 6231;
$processor = $_GET['processor'] ?? 'abo_grabiosapp';

// Reset the processor to pending
$sql = "UPDATE bg_company_attributes 
        SET description = 'pending', modify_dt = NOW() 
        WHERE company_id = :company_id 
        AND type = 'onboarding_progress' 
        AND name = :processor";

$database->query($sql, [
    'company_id' => $company_id,
    'processor' => $processor
]);

// Also clear the appapple field if resetting iOS processor
if ($processor === 'abo_grabiosapp') {
    $clear_sql = "UPDATE bg_companies SET appapple = NULL WHERE company_id = :company_id";
    $database->query($clear_sql, ['company_id' => $company_id]);
}

echo json_encode([
    'status' => 'success',
    'message' => "Reset $processor for company $company_id to pending"
]);