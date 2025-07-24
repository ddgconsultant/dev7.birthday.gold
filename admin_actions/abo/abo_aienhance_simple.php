<?php
// abo_aienhance_simple.php - Simple AI enhancement without actual AI call
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
    'processor' => 'abo_aienhance',
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
                AND ca.name = 'abo_aienhance'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending AI enhancement
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_aienhance'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending AI enhancement';
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
                        AND name = 'abo_aienhance'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Gather all collected data for analysis
        $collected_data = [
            'company_name' => $company_name,
            'company_url' => $company['company_url']
        ];
        
        // Get birthday program data
        $birthday_sql = "SELECT * FROM bg_company_attributes 
                        WHERE company_id = :company_id 
                        AND type = 'birthday_program'
                        AND name = 'program_data'
                        AND status = 'active'
                        LIMIT 1";
        $stmt = $database->query($birthday_sql, ['company_id' => $company_id]);
        $birthday_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($birthday_data) {
            $collected_data['birthday_program'] = json_decode($birthday_data['description'], true);
        }
        
        // Simulate AI enhancement based on collected data
        $ai_data = [
            'validation_issues' => [],
            'enhancements' => [],
            'reward_description' => '',
            'signup_instructions' => [],
            'confidence_score' => 0,
            'recommendations' => []
        ];
        
        // Analyze the data
        if (!empty($collected_data['birthday_program'])) {
            $program = $collected_data['birthday_program'];
            
            // Check for issues
            if ($program['program_type'] === 'loyalty_platform' && empty($program['rewards'][0])) {
                $ai_data['validation_issues'][] = 'Birthday reward details are vague - specific reward not identified';
            }
            
            // Generate enhanced description based on program type
            if ($program['program_type'] === 'loyalty_platform') {
                $platform_name = str_replace('_', ' ', $program['signup_method']);
                $ai_data['reward_description'] = "Join {$company_name}'s rewards program and receive a special birthday surprise! "
                    . "Members enjoy exclusive birthday benefits through our {$platform_name}. "
                    . "Sign up with your birth date to automatically receive your birthday reward.";
                
                $ai_data['signup_instructions'] = [
                    "Visit {$company['company_url']}",
                    "Look for the rewards program widget or link",
                    "Click 'Join' or 'Sign Up'",
                    "Enter your email and create an account",
                    "Make sure to provide your birth date",
                    "Your birthday reward will be sent automatically during your birthday period"
                ];
                
                $ai_data['confidence_score'] = 75;
            } elseif (!empty($program['rewards'])) {
                // Direct birthday program
                $reward_desc = implode(', ', $program['rewards']);
                $ai_data['reward_description'] = "Celebrate your birthday with {$company_name}! "
                    . "Birthday club members receive: {$reward_desc}. "
                    . "Join today to ensure you don't miss out on your special birthday treat.";
                
                $ai_data['confidence_score'] = 85;
            } else {
                $ai_data['validation_issues'][] = 'No birthday program detected';
                $ai_data['confidence_score'] = 0;
            }
            
            // Add recommendations
            if (count($ai_data['validation_issues']) > 0) {
                $ai_data['recommendations'][] = 'Manual verification recommended to confirm birthday reward details';
            }
            if (empty($program['rewards']) || strpos($program['rewards'][0], 'check program') !== false) {
                $ai_data['recommendations'][] = 'Specific reward details should be verified from the loyalty platform';
            }
        } else {
            $ai_data['validation_issues'][] = 'No birthday program data found';
            $ai_data['confidence_score'] = 0;
        }
        
        // Store AI enhancements
        $enhance_sql = "INSERT INTO bg_company_attributes 
                       (company_id, type, name, description, status, create_dt)
                       VALUES 
                       (:company_id, 'ai_enhancement', :name, :data, 'active', NOW())
                       ON DUPLICATE KEY UPDATE 
                       description = VALUES(description),
                       modify_dt = NOW()";
        
        // Store complete analysis
        $database->query($enhance_sql, [
            'company_id' => $company_id,
            'name' => 'full_analysis',
            'data' => json_encode($ai_data)
        ]);
        
        // Store individual enhancements
        if (!empty($ai_data['reward_description'])) {
            $database->query($enhance_sql, [
                'company_id' => $company_id,
                'name' => 'reward_description_enhanced',
                'data' => $ai_data['reward_description']
            ]);
        }
        
        if (!empty($ai_data['signup_instructions'])) {
            $database->query($enhance_sql, [
                'company_id' => $company_id,
                'name' => 'signup_instructions',
                'data' => json_encode($ai_data['signup_instructions'])
            ]);
        }
        
        $database->query($enhance_sql, [
            'company_id' => $company_id,
            'name' => 'data_confidence_score',
            'data' => $ai_data['confidence_score']
        ]);
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = 'completed', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_aienhance'";
        $database->query($complete_sql, ['company_id' => $company_id]);
        
        $database->commit();
        
        $result['successful'] = 1;
        $result['data_collected'] = "AI enhancement completed (simulated). Confidence: {$ai_data['confidence_score']}%";
        $result['ai_enhancements'] = [
            'validation_issues' => count($ai_data['validation_issues']),
            'confidence_score' => $ai_data['confidence_score'],
            'has_enhanced_description' => !empty($ai_data['reward_description']),
            'has_signup_instructions' => !empty($ai_data['signup_instructions'])
        ];
        
    } catch (Exception $e) {
        $database->rollBack();
        
        // Update progress to error
        $error_sql = "UPDATE bg_company_attributes 
                     SET description = 'error', modify_dt = NOW() 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress' 
                     AND name = 'abo_aienhance'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        error_log("ABO AI enhance error for company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO AI enhance fatal error: " . $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);