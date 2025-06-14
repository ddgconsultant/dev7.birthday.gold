<?php
/**
 * Enhanced Validation Code System v7 - Numeric Only with Recycling
 * Integrates with existing Birthday.Gold bg_validations table
 * Location: /includes/validation-code-manager.php
 * 
 * Supports both email and SMS with automatic code cleanup and collision detection
 * Works seamlessly with existing validation infrastructure
 */

class ValidationCodeManager {
    private $database;
    private $code_expiry_minutes = 15; // 15 minute expiry
    private $max_attempts = 3;
    private $collision_retry_limit = 10;
    private $rate_limit_window = 60; // minutes
    private $max_codes_per_hour = 5;
    
    public function __construct($database) {
        $this->database = $database;
        $this->cleanupExpiredCodes(); // Clean old codes on initialization
    }
    
    /**
     * Generate a unique numeric validation code
     * Integrates with existing bg_validations table structure
     */
    public function generateValidationCode($user_id, $method = 'email', $contact_info = '') {
        $attempts = 0;
        
        // Check rate limit first
        $rate_check = $this->checkRateLimit($user_id, $method);
        if (!$rate_check['within_limit']) {
            throw new Exception('Rate limit exceeded. Please wait before requesting another code.');
        }
        
        while ($attempts < $this->collision_retry_limit) {
            // Generate 6-digit numeric code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Check if code is currently active (not expired)
            if (!$this->isCodeActive($code)) {
                // Code is available, create record
                $validation_data = [
                    'user_id' => $user_id,
                    'validation_code' => $code, // Match existing column name
                    'validation_method' => $method, // 'email' or 'sms'
                    'contact_info' => $contact_info,
                    'created_datetime' => date('Y-m-d H:i:s'),
                    'expiry_datetime' => date('Y-m-d H:i:s', strtotime("+{$this->code_expiry_minutes} minutes")),
                    'attempt_count' => 0,
                    'validation_status' => 'pending'
                ];
                
                $this->storeValidationCode($validation_data);
                
                return [
                    'code' => $code,
                    'expires_at' => $validation_data['expiry_datetime'],
                    'method' => $method,
                    'contact_info' => $contact_info
                ];
            }
            
            $attempts++;
        }
        
        // If we get here, we couldn't find a unique code - extremely rare
        throw new Exception('Unable to generate unique validation code. Please try again.');
    }
    
    /**
     * Check if a code is currently active (not expired)
     */
    private function isCodeActive($code) {
        $sql = "SELECT validation_id FROM bg_validations 
                WHERE validation_code = :code 
                AND validation_status = 'pending' 
                AND expiry_datetime > NOW()";
        
        $result = $this->database->query($sql, [':code' => $code]);
        return !empty($result);
    }
    
    /**
     * Store validation code in existing bg_validations table
     */
    private function storeValidationCode($data) {
        // First, deactivate any existing pending codes for this user
        $this->deactivateUserCodes($data['user_id']);
        
        $sql = "INSERT INTO bg_validations 
                (user_id, validation_code, validation_method, contact_info, 
                 created_datetime, expiry_datetime, attempt_count, validation_status)
                VALUES (:user_id, :validation_code, :validation_method, :contact_info, 
                        :created_datetime, :expiry_datetime, :attempt_count, :validation_status)";
        
        $this->database->query($sql, [
            ':user_id' => $data['user_id'],
            ':validation_code' => $data['validation_code'],
            ':validation_method' => $data['validation_method'],
            ':contact_info' => $data['contact_info'],
            ':created_datetime' => $data['created_datetime'],
            ':expiry_datetime' => $data['expiry_datetime'],
            ':attempt_count' => $data['attempt_count'],
            ':validation_status' => $data['validation_status']
        ]);
    }
    
    /**
     * Validate a submitted code against existing bg_validations table
     */
    public function validateCode($user_id, $submitted_code, $method = null) {
        // Clean expired codes first
        $this->cleanupExpiredCodes();
        
        $sql = "SELECT * FROM bg_validations 
                WHERE user_id = :user_id 
                AND validation_code = :code 
                AND validation_status = 'pending' 
                AND expiry_datetime > NOW()
                ORDER BY created_datetime DESC
                LIMIT 1";
        
        $params = [
            ':user_id' => $user_id,
            ':code' => $submitted_code
        ];
        
        $result = $this->database->query($sql, $params);
        
        if (empty($result)) {
            // Code not found or expired
            $this->incrementAttempts($user_id, $submitted_code);
            return [
                'valid' => false,
                'message' => 'Invalid or expired verification code',
                'attempts_remaining' => $this->getRemainingAttempts($user_id)
            ];
        }
        
        $code_record = $result[0];
        
        // Check method if specified
        if ($method && $code_record['validation_method'] !== $method) {
            return [
                'valid' => false,
                'message' => 'Invalid verification method',
                'attempts_remaining' => $this->getRemainingAttempts($user_id)
            ];
        }
        
        // Check if too many attempts
        if ($code_record['attempt_count'] >= $this->max_attempts) {
            $this->markCodeAsExpired($code_record['validation_id']);
            return [
                'valid' => false,
                'message' => 'Too many attempts. Please request a new verification code.',
                'attempts_remaining' => 0
            ];
        }
        
        // Code is valid - mark as validated
        $this->markCodeAsValidated($code_record['validation_id']);
        
        return [
            'valid' => true,
            'message' => 'Verification code validated successfully',
            'method' => $code_record['validation_method'],
            'contact_info' => $code_record['contact_info'],
            'validation_id' => $code_record['validation_id']
        ];
    }
    
    /**
     * Deactivate all pending codes for a user (when generating new code)
     */
    private function deactivateUserCodes($user_id) {
        $sql = "UPDATE bg_validations 
                SET validation_status = 'superseded' 
                WHERE user_id = :user_id 
                AND validation_status = 'pending'";
        
        $this->database->query($sql, [':user_id' => $user_id]);
    }
    
    /**
     * Mark a specific code as validated
     */
    private function markCodeAsValidated($validation_id) {
        $sql = "UPDATE bg_validations 
                SET validation_status = 'validated', 
                    validated_datetime = NOW() 
                WHERE validation_id = :id";
        
        $this->database->query($sql, [':id' => $validation_id]);
    }
    
    /**
     * Mark a specific code as expired due to too many attempts
     */
    private function markCodeAsExpired($validation_id) {
        $sql = "UPDATE bg_validations 
                SET validation_status = 'expired' 
                WHERE validation_id = :id";
        
        $this->database->query($sql, [':id' => $validation_id]);
    }
    
    /**
     * Increment attempt counter for failed validation
     */
    private function incrementAttempts($user_id, $code) {
        $sql = "UPDATE bg_validations 
                SET attempt_count = attempt_count + 1 
                WHERE user_id = :user_id 
                AND validation_code = :code 
                AND validation_status = 'pending'";
        
        $this->database->query($sql, [
            ':user_id' => $user_id,
            ':code' => $code
        ]);
    }
    
    /**
     * Get remaining attempts for user's active code
     */
    private function getRemainingAttempts($user_id) {
        $sql = "SELECT attempt_count FROM bg_validations 
                WHERE user_id = :user_id 
                AND validation_status = 'pending' 
                AND expiry_datetime > NOW() 
                ORDER BY created_datetime DESC 
                LIMIT 1";
        
        $result = $this->database->query($sql, [':user_id' => $user_id]);
        
        if (!empty($result)) {
            return max(0, $this->max_attempts - $result[0]['attempt_count']);
        }
        
        return $this->max_attempts;
    }
    
    /**
     * Clean up expired codes (integrates with existing cleanup patterns)
     */
    public function cleanupExpiredCodes() {
        $sql = "UPDATE bg_validations 
                SET validation_status = 'expired' 
                WHERE validation_status = 'pending' 
                AND expiry_datetime < NOW()";
        
        $this->database->query($sql);
        
        // Optional: Archive very old records (older than 30 days)
        $sql = "UPDATE bg_validations 
                SET validation_status = 'archived' 
                WHERE validation_status IN ('expired', 'validated', 'superseded')
                AND created_datetime < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $this->database->query($sql);
    }
    
    /**
     * Check if user has reached rate limit for code generation
     */
    public function checkRateLimit($user_id, $method = null) {
        $sql = "SELECT COUNT(*) as count 
                FROM bg_validations 
                WHERE user_id = :user_id 
                AND created_datetime > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
        
        $params = [
            ':user_id' => $user_id,
            ':minutes' => $this->rate_limit_window
        ];
        
        if ($method) {
            $sql .= " AND validation_method = :method";
            $params[':method'] = $method;
        }
        
        $result = $this->database->query($sql, $params);
        $count = $result[0]['count'] ?? 0;
        
        return [
            'within_limit' => $count < $this->max_codes_per_hour,
            'current_count' => $count,
            'max_allowed' => $this->max_codes_per_hour,
            'time_window_minutes' => $this->rate_limit_window
        ];
    }
    
    /**
     * Get validation statistics for monitoring
     */
    public function getValidationStats($hours = 24) {
        $sql = "SELECT 
                    validation_method,
                    validation_status,
                    COUNT(*) as count
                FROM bg_validations 
                WHERE created_datetime > DATE_SUB(NOW(), INTERVAL :hours HOUR)
                GROUP BY validation_method, validation_status
                ORDER BY validation_method, validation_status";
        
        return $this->database->query($sql, [':hours' => $hours]);
    }
    
    /**
     * Get user's validation history
     */
    public function getUserValidationHistory($user_id, $limit = 10) {
        $sql = "SELECT validation_code, validation_method, validation_status, 
                       created_datetime, expiry_datetime, attempt_count
                FROM bg_validations 
                WHERE user_id = :user_id
                ORDER BY created_datetime DESC
                LIMIT :limit";
        
        return $this->database->query($sql, [
            ':user_id' => $user_id,
            ':limit' => $limit
        ]);
    }
}

/**
 * Integration helper class for existing Birthday.Gold infrastructure
 */
class ValidationIntegration {
    private $validationManager;
    private $mailManager;
    private $smsManager;
    
    public function __construct($database, $mail = null, $sms = null) {
        $this->validationManager = new ValidationCodeManager($database);
        $this->mailManager = $mail;
        $this->smsManager = $sms;
    }
    
    /**
     * Send validation code via email (integrates with existing mail system)
     */
    public function sendEmailValidation($user_id, $email, $user_name = '') {
        try {
            // Generate code
            $code_data = $this->validationManager->generateValidationCode($user_id, 'email', $email);
            
            // Prepare email data for existing mail system
            $email_data = [
                'toemail' => $email,
                'fullname' => $user_name ?: 'Valued Customer',
                'validationcode' => $code_data['code'],
                'expires_minutes' => 15,
                'validation_method' => 'email'
            ];
            
            // Send using existing mail system
            if ($this->mailManager) {
                $result = $this->mailManager->sendValidationEmail($email_data);
            } else {
                // Fallback if mail manager not available
                $result = true;
            }
            
            return [
                'success' => $result,
                'code' => $code_data['code'], // Remove in production
                'expires_at' => $code_data['expires_at'],
                'method' => 'email'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send validation code via SMS (if SMS system available)
     */
    public function sendSmsValidation($user_id, $phone_number, $user_name = '') {
        try {
            // Generate code
            $code_data = $this->validationManager->generateValidationCode($user_id, 'sms', $phone_number);
            
            // Prepare SMS message
            $message = "Your Birthday.Gold verification code is: {$code_data['code']}. Valid for 15 minutes.";
            
            // Send using existing SMS system
            if ($this->smsManager) {
                $result = $this->smsManager->sendSMS($phone_number, $message);
            } else {
                // Fallback if SMS not available
                $result = false;
            }
            
            return [
                'success' => $result,
                'code' => $code_data['code'], // Remove in production
                'expires_at' => $code_data['expires_at'],
                'method' => 'sms'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate submitted code
     */
    public function validateSubmittedCode($user_id, $submitted_code, $method = null) {
        return $this->validationManager->validateCode($user_id, $submitted_code, $method);
    }
    
    /**
     * Get validation manager for advanced operations
     */
    public function getValidationManager() {
        return $this->validationManager;
    }
}
?>