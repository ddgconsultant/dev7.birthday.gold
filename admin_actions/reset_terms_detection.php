<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

header('Content-Type: text/plain');
echo "Resetting terms detection for company ID: $company_id\n";
echo "============================================\n\n";

try {
    $database->beginTransaction();
    
    // Delete the incorrect terms URL from attributes
    $sql1 = "DELETE FROM bg_company_attributes 
            WHERE company_id = :company_id 
            AND type = 'url' 
            AND name = 'terms' 
            AND `grouping` = 'policies'";
    $stmt1 = $database->query($sql1, ['company_id' => $company_id]);
    echo "Deleted " . $stmt1->rowCount() . " policy URL records\n";
    
    // Delete the incorrect policy record
    $sql2 = "DELETE FROM bg_company_policies 
            WHERE company_id = :company_id 
            AND policy_type = 'terms'";
    $stmt2 = $database->query($sql2, ['company_id' => $company_id]);
    echo "Deleted " . $stmt2->rowCount() . " policy records\n";
    
    // Reset the onboarding progress
    $sql3 = "UPDATE bg_company_attributes 
            SET description = 'pending', modify_dt = NOW() 
            WHERE company_id = :company_id 
            AND type = 'onboarding_progress' 
            AND name = 'abo_grabterms'";
    $stmt3 = $database->query($sql3, ['company_id' => $company_id]);
    echo "Reset " . $stmt3->rowCount() . " progress records\n";
    
    $database->commit();
    echo "\nSuccessfully reset terms detection for company $company_id\n";
    
} catch (Exception $e) {
    $database->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}