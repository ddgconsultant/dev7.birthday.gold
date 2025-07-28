<?php
// abo_aivalidate.php - AI-powered validation of birthday program details
// Part of the Automation Business Onboarding (ABO) system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get company ID - support both encoded and raw for debugging
$specific_company_id = null;

if (isset($_GET['rawid'])) {
    // Debug mode - use raw ID directly
    $specific_company_id = intval($_GET['rawid']);
} elseif (isset($_GET['id'])) {
    // Production mode - decode the ID
    $encoded_id = $_GET['id'];
    $specific_company_id = $qik->decodeID($encoded_id);
}

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processor' => 'abo_aivalidate',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => []
];

try {
    // Get companies to process
    if ($specific_company_id) {
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.company_id = :company_id 
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_aivalidate'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending AI validation
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_aivalidate'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending AI validation';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    
    try {
        $database->beginTransaction();
        
        // Update progress to in_progress
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'in_progress', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_aivalidate'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Gather all data for validation
        $validation_data = [
            'company' => [
                'name' => $company_name,
                'url' => $company['company_url'],
                'signup_url' => $company['signup_url']
            ],
            'collected_data' => [],
            'enhancements' => []
        ];
        
        // Get birthday program data
        $birthday_sql = "SELECT * FROM bg_company_attributes 
                        WHERE company_id = :company_id 
                        AND type = 'birthday_program'
                        AND status = 'active'";
        $stmt = $database->query($birthday_sql, ['company_id' => $company_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $validation_data['collected_data']['birthday_' . $row['name']] = json_decode($row['description'], true);
        }
        
        // Get age requirements
        $age_sql = "SELECT * FROM bg_company_attributes 
                   WHERE company_id = :company_id 
                   AND type IN ('age_requirements', 'requirement')
                   AND name IN ('minimum_age', 'maximum_age', 'birthday_program')
                   AND status = 'active'";
        $stmt = $database->query($age_sql, ['company_id' => $company_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $validation_data['collected_data']['age_' . $row['name']] = $row['description'];
        }
        
        // Get AI enhancements
        $enhance_sql = "SELECT * FROM bg_company_attributes 
                       WHERE company_id = :company_id 
                       AND type = 'ai_enhancement'
                       AND status = 'active'";
        $stmt = $database->query($enhance_sql, ['company_id' => $company_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $validation_data['enhancements'][$row['name']] = 
                (substr($row['description'], 0, 1) === '{' || substr($row['description'], 0, 1) === '[') 
                ? json_decode($row['description'], true) 
                : $row['description'];
        }
        
        // Get locations data
        $locations_sql = "SELECT COUNT(*) as location_count, 
                         GROUP_CONCAT(DISTINCT source) as sources
                         FROM bg_company_locations 
                         WHERE company_id = :company_id 
                         AND status = 'active'";
        $stmt = $database->query($locations_sql, ['company_id' => $company_id]);
        $location_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $validation_data['collected_data']['locations'] = $location_data;
        
        // Get policies
        $policies_sql = "SELECT policy_type, url, status, last_verified 
                        FROM bg_company_policies 
                        WHERE company_id = :company_id 
                        AND status IN ('active', 'verified')";
        $stmt = $database->query($policies_sql, ['company_id' => $company_id]);
        $validation_data['collected_data']['policies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Perform validation checks
        $validation_results = [
            'is_valid' => true,
            'validation_score' => 100,
            'issues' => [],
            'warnings' => [],
            'successes' => [],
            'recommendations' => []
        ];
        
        // Check 1: Birthday program exists
        if (!empty($validation_data['collected_data']['birthday_program_data']['has_program'])) {
            $validation_results['successes'][] = 'Birthday program detected';
        } else {
            $validation_results['issues'][] = 'No birthday program detected';
            $validation_results['is_valid'] = false;
            $validation_results['validation_score'] -= 50;
        }
        
        // Check 2: Age requirements are reasonable
        $min_age = intval($validation_data['collected_data']['age_minimum_age'] ?? 13);
        $max_age = intval($validation_data['collected_data']['age_maximum_age'] ?? 250);
        
        if ($min_age < 0 || $min_age > 100) {
            $validation_results['issues'][] = "Invalid minimum age: $min_age";
            $validation_results['validation_score'] -= 10;
        } elseif ($min_age >= 21) {
            $validation_results['warnings'][] = "High minimum age ($min_age) may exclude younger customers";
        } else {
            $validation_results['successes'][] = "Age requirements validated: $min_age-$max_age";
        }
        
        // Check 3: Enhanced description exists
        if (!empty($validation_data['enhancements']['reward_description_enhanced'])) {
            $validation_results['successes'][] = 'Enhanced reward description available';
        } else {
            $validation_results['warnings'][] = 'No enhanced reward description';
            $validation_results['validation_score'] -= 5;
        }
        
        // Check 4: Signup instructions exist
        if (!empty($validation_data['enhancements']['signup_instructions'])) {
            $validation_results['successes'][] = 'Signup instructions available';
        } else {
            $validation_results['warnings'][] = 'No signup instructions generated';
            $validation_results['validation_score'] -= 5;
        }
        
        // Check 5: Confidence score
        $confidence = intval($validation_data['enhancements']['data_confidence_score'] ?? 0);
        if ($confidence >= 80) {
            $validation_results['successes'][] = "High confidence score: $confidence%";
        } elseif ($confidence >= 60) {
            $validation_results['warnings'][] = "Moderate confidence score: $confidence%";
            $validation_results['recommendations'][] = 'Consider manual verification to improve confidence';
        } else {
            $validation_results['issues'][] = "Low confidence score: $confidence%";
            $validation_results['validation_score'] -= 15;
            $validation_results['recommendations'][] = 'Manual review required due to low confidence';
        }
        
        // Check 6: Policy compliance
        $has_terms = false;
        $has_privacy = false;
        foreach ($validation_data['collected_data']['policies'] as $policy) {
            if ($policy['policy_type'] === 'terms') $has_terms = true;
            if ($policy['policy_type'] === 'privacy') $has_privacy = true;
        }
        
        if ($has_terms && $has_privacy) {
            $validation_results['successes'][] = 'Terms and privacy policies found';
        } else {
            if (!$has_terms) $validation_results['warnings'][] = 'Terms of service not found';
            if (!$has_privacy) $validation_results['warnings'][] = 'Privacy policy not found';
            $validation_results['validation_score'] -= 5;
        }
        
        // Check 7: Data completeness for specific program types
        if (!empty($validation_data['collected_data']['birthday_program_data'])) {
            $program = $validation_data['collected_data']['birthday_program_data'];
            
            if ($program['program_type'] === 'loyalty_platform' && 
                (empty($program['rewards']) || strpos($program['rewards'][0], 'check program') !== false)) {
                $validation_results['warnings'][] = 'Specific reward details not captured from loyalty platform';
                $validation_results['recommendations'][] = 'Manual verification of exact birthday reward recommended';
                $validation_results['validation_score'] -= 10;
            }
            
            if (empty($program['signup_method'])) {
                $validation_results['issues'][] = 'No signup method identified';
                $validation_results['validation_score'] -= 10;
            }
        }
        
        // Calculate final validation status
        if ($validation_results['validation_score'] < 0) {
            $validation_results['validation_score'] = 0;
        }
        
        if ($validation_results['validation_score'] >= 90) {
            $validation_results['validation_status'] = 'excellent';
        } elseif ($validation_results['validation_score'] >= 75) {
            $validation_results['validation_status'] = 'good';
        } elseif ($validation_results['validation_score'] >= 60) {
            $validation_results['validation_status'] = 'fair';
        } else {
            $validation_results['validation_status'] = 'needs_review';
            $validation_results['is_valid'] = false;
        }
        
        // Store validation results
        $validate_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'ai_validation', :name, :data, 'active', NOW())
                        ON DUPLICATE KEY UPDATE 
                        description = VALUES(description),
                        modify_dt = NOW()";
        
        // Store complete validation results
        $database->query($validate_sql, [
            'company_id' => $company_id,
            'name' => 'validation_results',
            'data' => json_encode($validation_results)
        ]);
        
        // Store validation score
        $database->query($validate_sql, [
            'company_id' => $company_id,
            'name' => 'validation_score',
            'data' => $validation_results['validation_score']
        ]);
        
        // Store validation status
        $database->query($validate_sql, [
            'company_id' => $company_id,
            'name' => 'validation_status',
            'data' => $validation_results['validation_status']
        ]);
        
        // Create validation issues if any
        foreach ($validation_results['issues'] as $issue) {
            $issue_sql = "INSERT INTO bg_company_attributes 
                         (company_id, type, name, description, status, create_dt)
                         VALUES 
                         (:company_id, 'validation_issue', 'critical', :issue, 'active', NOW())";
            $database->query($issue_sql, [
                'company_id' => $company_id,
                'issue' => $issue
            ]);
        }
        
        // Update company status based on validation
        if ($validation_results['is_valid'] && $validation_results['validation_score'] >= 75) {
            // Ready for final review
            $status_sql = "UPDATE bg_companies 
                          SET status = 'pending_final_review', modify_dt = NOW() 
                          WHERE company_id = :company_id";
            $database->query($status_sql, ['company_id' => $company_id]);
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = 'completed', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_aivalidate'";
        $database->query($complete_sql, ['company_id' => $company_id]);
        
        $database->commit();
        
        $result['successful'] = 1;
        $result['data_collected'] = "Validation completed. Score: {$validation_results['validation_score']}% ({$validation_results['validation_status']})";
        $result['validation_summary'] = [
            'score' => $validation_results['validation_score'],
            'status' => $validation_results['validation_status'],
            'is_valid' => $validation_results['is_valid'],
            'issues_count' => count($validation_results['issues']),
            'warnings_count' => count($validation_results['warnings']),
            'successes_count' => count($validation_results['successes'])
        ];
        
    } catch (Exception $e) {
        $database->rollBack();
        
        // Update progress to error
        $error_sql = "UPDATE bg_company_attributes 
                     SET description = 'error', modify_dt = NOW() 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress' 
                     AND name = 'abo_aivalidate'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        session_tracking('ABO AI validate error', "Company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    session_tracking('ABO AI validate fatal error', $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);