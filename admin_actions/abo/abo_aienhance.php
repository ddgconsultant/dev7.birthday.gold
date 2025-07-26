<?php
// abo_aienhance.php - Use AI to enhance and validate collected data
// Part of the Automation Business Onboarding (ABO) system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Load AI class if not already loaded
if (!class_exists('AI')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.ai.php');
}

// Load AI configuration
$config_ai_path = $dir['configs'] . '/config-ai.inc';
if (file_exists($config_ai_path)) {
    $config_ai = file_get_contents($config_ai_path);
    $sitesettings_ai = parse_ini_string($config_ai, true);
} else {
    // Fallback to empty config
    $sitesettings_ai = ['ai' => []];
}

$ai = new AI($system, $sitesettings_ai);

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
        
        // Gather all collected data for AI analysis
        $collected_data = [
            'company_name' => $company_name,
            'company_url' => $company['company_url'],
            'signup_url' => $company['signup_url'],
            'info_url' => $company['info_url']
        ];
        
        // Get birthday program data
        $birthday_sql = "SELECT * FROM bg_company_attributes 
                        WHERE company_id = :company_id 
                        AND type = 'birthday_program'
                        AND status = 'active'";
        $stmt = $database->query($birthday_sql, ['company_id' => $company_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $collected_data['birthday_program'][$row['name']] = json_decode($row['description'], true);
        }
        
        // Get rewards data
        $rewards_sql = "SELECT * FROM bg_company_rewards 
                       WHERE company_id = :company_id 
                       AND category = 'birthday'
                       AND status = 'active'";
        $stmt = $database->query($rewards_sql, ['company_id' => $company_id]);
        $collected_data['rewards'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get age requirements
        $age_sql = "SELECT * FROM bg_company_attributes 
                   WHERE company_id = :company_id 
                   AND type IN ('age_requirements', 'requirement')
                   AND status = 'active'";
        $stmt = $database->query($age_sql, ['company_id' => $company_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $collected_data['age_requirements'][$row['name']] = $row['description'];
        }
        
        // Get locations data
        $locations_sql = "SELECT * FROM bg_company_locations 
                         WHERE company_id = :company_id 
                         AND status = 'active'";
        $stmt = $database->query($locations_sql, ['company_id' => $company_id]);
        $collected_data['locations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get policies
        $policies_sql = "SELECT * FROM bg_company_policies 
                        WHERE company_id = :company_id 
                        AND status IN ('active', 'verified')
                        ORDER BY policy_type, version DESC";
        $stmt = $database->query($policies_sql, ['company_id' => $company_id]);
        $collected_data['policies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare AI prompt
        $ai_prompt = "You are analyzing birthday reward program data for {$company_name}. Please review the collected data and provide:

1. VALIDATION: Identify any inconsistencies or missing critical information
2. ENHANCEMENTS: Suggest improvements or clarifications
3. REWARD_DESCRIPTION: Write a clear, customer-friendly description of the birthday reward
4. SIGNUP_INSTRUCTIONS: Create step-by-step instructions for signing up
5. CONFIDENCE_SCORE: Rate data completeness from 0-100

Collected Data:
" . json_encode($collected_data, JSON_PRETTY_PRINT) . "

Respond in JSON format with these keys: validation_issues, enhancements, reward_description, signup_instructions, confidence_score, recommendations";

        // Call AI API
        $ai_messages = [
            ['role' => 'user', 'content' => $ai_prompt]
        ];
        
        $ai_options = [
            'temperature' => 0.3,
            'max_tokens' => 2000,
            'system' => 'You are a data quality analyst specializing in birthday reward programs. Provide responses in valid JSON format.'
        ];
        
        $ai_response_raw = $ai->process($ai_messages, $ai_options);
        $ai_response = $ai_response_raw['content'] ?? '';
        
        if (!empty($ai_response)) {
            // Parse AI response
            $ai_data = json_decode($ai_response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If not valid JSON, try to extract JSON from the response
                if (preg_match('/\{.*\}/s', $ai_response, $matches)) {
                    $ai_data = json_decode($matches[0], true);
                }
            }
            
            if (is_array($ai_data)) {
                // Store AI enhancements
                $enhance_sql = "INSERT INTO bg_company_attributes 
                               (company_id, type, name, description, status, create_dt)
                               VALUES 
                               (:company_id, 'ai_enhancement', :name, :data, 'active', NOW())
                               ON DUPLICATE KEY UPDATE 
                               description = VALUES(description),
                               modify_dt = NOW()";
                
                // Store complete AI response
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
                
                if (isset($ai_data['confidence_score'])) {
                    $database->query($enhance_sql, [
                        'company_id' => $company_id,
                        'name' => 'data_confidence_score',
                        'data' => $ai_data['confidence_score']
                    ]);
                }
                
                // Handle validation issues
                if (!empty($ai_data['validation_issues']) && is_array($ai_data['validation_issues'])) {
                    foreach ($ai_data['validation_issues'] as $issue) {
                        $issue_sql = "INSERT INTO bg_company_attributes 
                                     (company_id, type, name, description, status, create_dt)
                                     VALUES 
                                     (:company_id, 'validation_issue', 'ai_identified', :issue, 'active', NOW())";
                        $database->query($issue_sql, [
                            'company_id' => $company_id,
                            'issue' => $issue
                        ]);
                    }
                }
                
                // Update reward record with enhanced description if available
                if (!empty($ai_data['reward_description']) && !empty($collected_data['rewards'][0]['reward_id'])) {
                    $update_reward_sql = "UPDATE bg_company_rewards 
                                         SET reward_description_short = :desc,
                                             modify_dt = NOW()
                                         WHERE reward_id = :reward_id";
                    $database->query($update_reward_sql, [
                        'desc' => substr($ai_data['reward_description'], 0, 1000),
                        'reward_id' => $collected_data['rewards'][0]['reward_id']
                    ]);
                }
                
                $status = 'completed';
                $confidence = $ai_data['confidence_score'] ?? 'unknown';
                $result['data_collected'] = "AI enhancement completed. Confidence: {$confidence}%";
                $result['ai_enhancements'] = [
                    'validation_issues' => count($ai_data['validation_issues'] ?? []),
                    'confidence_score' => $confidence,
                    'has_enhanced_description' => !empty($ai_data['reward_description']),
                    'has_signup_instructions' => !empty($ai_data['signup_instructions'])
                ];
            } else {
                $status = 'error';
                $result['errors'][] = 'Failed to parse AI response';
            }
        } else {
            $status = 'error';
            $result['errors'][] = 'Empty AI response';
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_aienhance'";
        $database->query($complete_sql, [
            'status' => $status,
            'company_id' => $company_id
        ]);
        
        $database->commit();
        
        if ($status === 'completed') {
            $result['successful'] = 1;
        } else {
            $result['failed'] = 1;
        }
        
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