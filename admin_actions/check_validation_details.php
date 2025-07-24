<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

header('Content-Type: text/plain');
echo "Validation Details for Company ID: $company_id\n";
echo "==========================================\n\n";

// Get validation results
$sql = "SELECT * FROM bg_company_attributes 
        WHERE company_id = :company_id 
        AND type = 'ai_validation'
        AND name = 'validation_results'";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $results = json_decode($row['description'], true);
    
    echo "Validation Score: {$results['validation_score']}%\n";
    echo "Validation Status: {$results['validation_status']}\n";
    echo "Is Valid: " . ($results['is_valid'] ? 'Yes' : 'No') . "\n\n";
    
    if (!empty($results['successes'])) {
        echo "✓ Successes (" . count($results['successes']) . "):\n";
        foreach ($results['successes'] as $success) {
            echo "  - $success\n";
        }
        echo "\n";
    }
    
    if (!empty($results['warnings'])) {
        echo "⚠ Warnings (" . count($results['warnings']) . "):\n";
        foreach ($results['warnings'] as $warning) {
            echo "  - $warning\n";
        }
        echo "\n";
    }
    
    if (!empty($results['issues'])) {
        echo "✗ Issues (" . count($results['issues']) . "):\n";
        foreach ($results['issues'] as $issue) {
            echo "  - $issue\n";
        }
        echo "\n";
    }
    
    if (!empty($results['recommendations'])) {
        echo "💡 Recommendations (" . count($results['recommendations']) . "):\n";
        foreach ($results['recommendations'] as $recommendation) {
            echo "  - $recommendation\n";
        }
        echo "\n";
    }
}

// Get birthday program data
echo "Birthday Program Data:\n";
echo "---------------------\n";
$sql = "SELECT * FROM bg_company_attributes 
        WHERE company_id = :company_id 
        AND type = 'birthday_program'
        AND name = 'program_data'";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $program_data = json_decode($row['description'], true);
    echo "Has Program: " . ($program_data['has_program'] ? 'Yes' : 'No') . "\n";
    echo "Program Type: {$program_data['program_type']}\n";
    echo "Signup Method: {$program_data['signup_method']}\n";
    if (!empty($program_data['rewards'])) {
        echo "Rewards: " . implode(', ', $program_data['rewards']) . "\n";
    }
}

// Get AI enhancements
echo "\nAI Enhancements:\n";
echo "----------------\n";
$sql = "SELECT * FROM bg_company_attributes 
        WHERE company_id = :company_id 
        AND type = 'ai_enhancement'
        ORDER BY create_dt DESC";
$stmt = $database->query($sql, ['company_id' => $company_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['name'] === 'reward_description_enhanced') {
        echo "Enhanced Description:\n  \"" . $row['description'] . "\"\n\n";
    } elseif ($row['name'] === 'data_confidence_score') {
        echo "Confidence Score: {$row['description']}%\n";
    }
}