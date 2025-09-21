<?php
/**
 * AJAX Endpoint: Check User Eligibility for Specific Company/Reward
 * Returns eligibility status and detailed information
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.enrollment.php');

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!$account->isactive()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

// Get parameters
$company_id = intval($_POST['company_id'] ?? 0);
$reward_id = intval($_POST['reward_id'] ?? 0);
$check_details = $_POST['details'] ?? false;

// Validate input
if ($company_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid company ID'
    ]);
    exit;
}

// Get current user
$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

// Initialize enrollment class
$enrollment = new Enrollment();

try {
    // Check company eligibility
    $eligibilities = $enrollment->getCompanyEligibilities($user_id, [$company_id]);
    $eligibility = $eligibilities[$company_id] ?? ['eligible' => true];
    
    $response = [
        'success' => true,
        'eligible' => $eligibility['eligible'],
        'company_id' => $company_id
    ];
    
    if (!$eligibility['eligible']) {
        $response['reason'] = $eligibility['message'];
        $response['reason_code'] = $eligibility['code'];
        $response['action_url'] = $eligibility['action_url'] ?? null;
        
        // Get detailed reason if requested
        if ($check_details) {
            $detailed_reason = $enrollment->getDisplayReason($eligibility['reason_id'], true);
            $response['detailed_reason'] = $detailed_reason['message'] ?? $eligibility['message'];
            $response['category'] = $detailed_reason['category'] ?? 'unknown';
        }
    }
    
    // If checking specific reward, add reward-specific checks
    if ($reward_id > 0 && $eligibility['eligible']) {
        // Get reward details
        $sql = "SELECT * FROM bg_company_rewards WHERE reward_id = :reward_id AND company_id = :company_id";
        $reward = $database->getrow($sql, ['reward_id' => $reward_id, 'company_id' => $company_id]);
        
        if ($reward) {
            $response['reward'] = [
                'id' => $reward['reward_id'],
                'name' => $reward['reward_name'],
                'description' => $reward['description'],
                'value' => $reward['cash_value']
            ];
            
            // Check reward-specific requirements
            if (!empty($reward['min_age']) || !empty($reward['max_age'])) {
                $user_age = $app->calculateage($current_user_data['birthdate'])['years'];
                
                if (!empty($reward['min_age']) && $user_age < $reward['min_age']) {
                    $response['eligible'] = false;
                    $response['reason'] = "Must be at least {$reward['min_age']} years old";
                    $response['reason_code'] = 'reward_age_restriction';
                } elseif (!empty($reward['max_age']) && $user_age > $reward['max_age']) {
                    $response['eligible'] = false;
                    $response['reason'] = "Must be {$reward['max_age']} years old or younger";
                    $response['reason_code'] = 'reward_age_restriction';
                }
            }
            
            // Check location restrictions if any
            if (!empty($reward['restricted_states'])) {
                $restricted_states = json_decode($reward['restricted_states'], true);
                if (is_array($restricted_states) && in_array($current_user_data['state'], $restricted_states)) {
                    $response['eligible'] = false;
                    $response['reason'] = "Not available in your state";
                    $response['reason_code'] = 'reward_location_restricted';
                }
            }
        }
    }
    
    // Add timestamp for cache purposes
    $response['checked_at'] = date('Y-m-d H:i:s');
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Eligibility check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred checking eligibility'
    ]);
}