<?php
/**
 * Voice Assistant Integration Class
 * Handles Google Assistant, Amazon Alexa, and Siri integrations
 * 
 * Uses bg_validations table for device linking codes
 */

class Assistant
{
    private $db;
    private $app;
    private $account;
    private $session;
    
    public function __construct($database, $app = null, $account = null, $session = null)
    {
        $this->db = $database;
        $this->app = $app;
        $this->account = $account;
        $this->session = $session;
    }
    
    /**
     * Generate a device linking code for voice assistants
     * Uses bg_validations table with validation_type = 'voice_assistant_link'
     */
    public function generateLinkingCode($platform, $deviceId = null)
    {
        // Use the existing validation system
        $input = [
            'rawdata' => $platform . '|' . ($deviceId ?? uniqid()),
            'type' => 'voice_assistant_link',
            'expireminutes' => 10, // 10 minute expiration
            'numeric_only' => true, // Use 6-digit numeric code
            'device_id' => $deviceId,
            'status' => 'pending'
        ];
        
        $validation = $this->app->getvalidationcodes($input);
        
        if ($validation && isset($validation['minicode'])) {
            // Format as XXXX-XX for easier voice reading
            $code = $validation['minicode'];
            $formatted = substr($code, 0, 4) . '-' . substr($code, 4, 2);
            
            return [
                'code' => $formatted,
                'raw_code' => $code,
                'expires_at' => $validation['expire_dt'],
                'validation_id' => $validation['validation_id']
            ];
        }
        
        return false;
    }
    
    /**
     * Verify a linking code and associate with user
     */
    public function verifyLinkingCode($code, $userId, $platform)
    {
        // Remove formatting from code
        $code = str_replace('-', '', $code);
        
        $sql = "SELECT * FROM bg_validations 
                WHERE validation_minicode = :code 
                AND validation_type = 'voice_assistant_link'
                AND status = 'pending'
                AND expire_dt > NOW()
                LIMIT 1";
        
        $result = $this->db->getrow($sql, [':code' => $code]);
        
        if ($result) {
            // Update validation with user_id
            $updateSql = "UPDATE bg_validations 
                         SET user_id = :user_id,
                             status = 'linked',
                             validation_dt = NOW(),
                             modify_dt = NOW()
                         WHERE validation_id = :validation_id";
            
            $this->db->query($updateSql, [
                ':user_id' => $userId,
                ':validation_id' => $result['validation_id']
            ]);
            
            // Create OAuth tokens for the platform
            $tokens = $this->createAssistantTokens($userId, $platform, $result['device_id']);
            
            return [
                'success' => true,
                'tokens' => $tokens
            ];
        }
        
        return ['success' => false, 'error' => 'Invalid or expired code'];
    }
    
    /**
     * Create OAuth tokens for voice assistant
     * Stores tokens in bg_validations table
     */
    public function createAssistantTokens($userId, $platform, $deviceId = null)
    {
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));
        
        // Store access token
        $sql = "INSERT INTO bg_validations (
                    user_id,
                    device_id,
                    validation_type,
                    validation_rawdata,
                    validation_code,
                    status,
                    expire_dt,
                    create_dt,
                    modify_dt
                ) VALUES (
                    :user_id,
                    :device_id,
                    'voice_assistant_token',
                    :rawdata,
                    :access_token,
                    'active',
                    DATE_ADD(NOW(), INTERVAL 30 DAY),
                    NOW(),
                    NOW()
                )";
        
        $this->db->query($sql, [
            ':user_id' => $userId,
            ':device_id' => $deviceId,
            ':rawdata' => $platform . '|access|' . $refreshToken,
            ':access_token' => $accessToken
        ]);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 2592000 // 30 days in seconds
        ];
    }
    
    /**
     * Authenticate user from voice assistant token
     */
    public function authenticateToken($token, $platform)
    {
        $sql = "SELECT user_id, validation_id FROM bg_validations 
                WHERE validation_code = :token 
                AND validation_type = 'voice_assistant_token'
                AND validation_rawdata LIKE :platform_pattern
                AND status = 'active'
                AND expire_dt > NOW()
                LIMIT 1";
        
        $result = $this->db->getrow($sql, [
            ':token' => $token,
            ':platform_pattern' => $platform . '|%'
        ]);
        
        if ($result) {
            // Update last used
            $updateSql = "UPDATE bg_validations 
                         SET validation_dt = NOW(),
                             modify_dt = NOW()
                         WHERE validation_id = :validation_id";
            $this->db->query($updateSql, [':validation_id' => $result['validation_id']]);
            
            return $result['user_id'];
        }
        
        return false;
    }
    
    /**
     * Get enrollment count for user
     */
    public function getEnrollmentCount($userId)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM bg_user_companies 
                WHERE user_id = :user_id 
                AND status IN ('success', 'success-btn', 'pending')";
        
        $result = $this->db->getrow($sql, [':user_id' => $userId]);
        return $result['count'] ?? 0;
    }
    
    /**
     * Get active rewards for user
     */
    public function getActiveRewards($userId, $limit = 5)
    {
        $sql = "SELECT c.company_name, uc.status, uc.create_dt
                FROM bg_user_companies uc
                JOIN bg_companies c ON uc.company_id = c.company_id
                WHERE uc.user_id = :user_id 
                AND uc.status IN ('success', 'success-btn')
                ORDER BY uc.create_dt DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get allocation balance for user
     */
    public function getAllocationBalance($userId)
    {
        // Use AllocationManager if available
        if (class_exists('AllocationManager')) {
            $allocationManager = new AllocationManager($this->db);
            return $allocationManager->getUserBalance($userId);
        }
        
        // Fallback to direct query
        $sql = "SELECT 
                    SUM(CASE WHEN status = 'active' THEN allocations ELSE 0 END) as available_allocations,
                    SUM(CASE WHEN status = 'used' THEN allocations ELSE 0 END) as used_allocations
                FROM bg_user_allocations
                WHERE user_id = :user_id";
        
        $result = $this->db->getrow($sql, [':user_id' => $userId]);
        
        return [
            'available_allocations' => $result['available_allocations'] ?? 0,
            'used_allocations' => $result['used_allocations'] ?? 0
        ];
    }
    
    /**
     * Process intent from voice assistant
     */
    public function processIntent($intent, $parameters, $userId, $platform)
    {
        $response = '';
        
        switch ($intent) {
            case 'GetEnrollmentCount':
                $count = $this->getEnrollmentCount($userId);
                $response = $count == 0 ? 
                    "You don't have any enrollments yet. Would you like to start enrolling in birthday rewards?" :
                    "You have {$count} active " . ($count == 1 ? "enrollment" : "enrollments") . ".";
                break;
                
            case 'GetActiveRewards':
                $rewards = $this->getActiveRewards($userId, 3);
                if (empty($rewards)) {
                    $response = "You don't have any active rewards yet.";
                } else {
                    $names = array_column($rewards, 'company_name');
                    $response = "Your active rewards include " . $this->formatList($names) . ".";
                }
                break;
                
            case 'GetAllocationBalance':
                $balance = $this->getAllocationBalance($userId);
                $available = $balance['available_allocations'];
                $response = $available == 0 ?
                    "You don't have any allocations available right now." :
                    "You have {$available} " . ($available == 1 ? "allocation" : "allocations") . " available.";
                break;
                
            case 'GetAccountStatus':
                $userdata = $this->account->getuserdata($userId, 'user_id');
                $plandetails = $this->app->plandetail('details_id', $userdata['account_product_id']);
                $response = "You have a {$plandetails['name']} account with Birthday Gold.";
                break;
                
            default:
                $response = "I can help you check your enrollments, rewards, and account status. What would you like to know?";
        }
        
        // Log the query
        $this->logQuery($userId, $platform, $intent, $response);
        
        return $response;
    }
    
    /**
     * Format a list for voice output
     */
    private function formatList($items)
    {
        $count = count($items);
        if ($count == 0) return '';
        if ($count == 1) return $items[0];
        if ($count == 2) return $items[0] . ' and ' . $items[1];
        
        $last = array_pop($items);
        return implode(', ', $items) . ', and ' . $last;
    }
    
    /**
     * Log voice assistant query
     */
    private function logQuery($userId, $platform, $intent, $response)
    {
        $sql = "INSERT INTO bg_assistant_queries (
                    user_id,
                    platform,
                    intent,
                    response_text,
                    created_at
                ) VALUES (
                    :user_id,
                    :platform,
                    :intent,
                    :response,
                    NOW()
                )";
        
        $this->db->query($sql, [
            ':user_id' => $userId,
            ':platform' => $platform,
            ':intent' => $intent,
            ':response' => $response
        ]);
    }
    
    /**
     * Verify webhook request from platform
     */
    public function verifyWebhookRequest($platform, $headers, $body)
    {
        switch ($platform) {
            case 'google':
                // Google uses JWT verification
                // Implementation would verify Google's signature
                return true; // Placeholder
                
            case 'alexa':
                // Alexa uses request signature verification
                // Implementation would verify Amazon's signature
                return true; // Placeholder
                
            case 'siri':
                // Siri uses app-based authentication
                return true; // Placeholder
                
            default:
                return false;
        }
    }
}
?>