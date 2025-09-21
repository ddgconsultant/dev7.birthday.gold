<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = $_GET['id'] ?? 6231;

header('Content-Type: text/plain');
echo "AI Enhancements for Company ID: $company_id\n";
echo "==========================================\n\n";

// Get AI enhancement data
$sql = "SELECT * FROM bg_company_attributes 
        WHERE company_id = :company_id 
        AND type = 'ai_enhancement'
        ORDER BY create_dt DESC";
$stmt = $database->query($sql, ['company_id' => $company_id]);
$enhancements = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($enhancements as $enhancement) {
    echo "Enhancement: {$enhancement['name']}\n";
    echo "Created: {$enhancement['create_dt']}\n";
    
    if ($enhancement['name'] === 'full_analysis') {
        $data = json_decode($enhancement['description'], true);
        echo "Content:\n";
        echo "  - Validation Issues: " . count($data['validation_issues'] ?? []) . "\n";
        if (!empty($data['validation_issues'])) {
            foreach ($data['validation_issues'] as $issue) {
                echo "    * $issue\n";
            }
        }
        echo "  - Confidence Score: " . ($data['confidence_score'] ?? 0) . "%\n";
        echo "  - Has Enhanced Description: " . (!empty($data['reward_description']) ? 'Yes' : 'No') . "\n";
        echo "  - Has Signup Instructions: " . (!empty($data['signup_instructions']) ? 'Yes' : 'No') . "\n";
        if (!empty($data['recommendations'])) {
            echo "  - Recommendations:\n";
            foreach ($data['recommendations'] as $rec) {
                echo "    * $rec\n";
            }
        }
    } elseif ($enhancement['name'] === 'reward_description_enhanced') {
        echo "Enhanced Description:\n";
        echo "  \"" . $enhancement['description'] . "\"\n";
    } elseif ($enhancement['name'] === 'signup_instructions') {
        echo "Signup Instructions:\n";
        $instructions = json_decode($enhancement['description'], true);
        foreach ($instructions as $i => $step) {
            echo "  " . ($i + 1) . ". $step\n";
        }
    }
    echo "\n";
}