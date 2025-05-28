<?php
class TelegramSMSService {
    private $telegramToken;
    private $telegramAPI;
    private $database;

    /**
     * Constructor
     * @param string $telegramToken Telegram bot token
     * @param string $telegramAPI Telegram API endpoint
     * @param object $database Database connection
     */
    public function __construct($telegramToken, $telegramAPI, $database) {
        $this->telegramToken = $telegramToken;
        $this->telegramAPI = $telegramAPI;
        $this->database = $database;

        if (empty($this->telegramToken)) {
            throw new Exception("Telegram token is missing");
        }
    }

    /**
     * Send SMS via Telegram
     * @param string $mobile Mobile number
     * @param string $message Message content
     * @return bool Sending status
     */

     public function sendSMS($mobile, $message) {
        // Validate inputs
        if (empty($mobile) || empty($message)) {
            error_log("Telegram SMS: Invalid mobile or message");
            return false;
        }
    
        // This is crucial - you need an actual Telegram chat ID, not a phone number
        // For testing, you might want to hardcode a known chat ID
        $chatId = 'YOUR_TELEGRAM_CHAT_ID'; // Replace with an actual Telegram chat ID
    
        // Prepare message data
        $postData = [
            'chat_id' => $chatId,
            'text' => $message
        ];
    
        // Prepare cURL request
        $url = "https://api.telegram.org/bot{$this->telegramToken}/sendMessage";
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL
        curl_close($ch);
    
        // Log the full response for debugging
        error_log("Telegram SMS Response: " . print_r([
            'response' => $response,
            'httpCode' => $httpCode
        ], true));
    
        // Check response
        if ($httpCode == 200) {
            $responseData = json_decode($response, true);
            if ($responseData['ok'] ?? false) {
                return true;
            }
        }
    
        return false;
    }

    /**
     * Store OTP in database
     * @param int $userId User ID
     * @param string $mobile Mobile number
     * @param string $otp Generated OTP
     * @return bool Storage status
     */
    public function storeOTP($userId, $mobile, $otp) {
        try {
            $stmt = $this->database->prepare("
                INSERT INTO bg_otp_verification 
                (user_id, contact_id, platform, otp, expiry_time, verified) 
                VALUES (:user_id, :mobile, 'telegram', :otp, 
                        DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)
                ON DUPLICATE KEY UPDATE 
                otp = :otp, 
                expiry_time = DATE_ADD(NOW(), INTERVAL 10 MINUTE),
                verified = 0
            ");

            return $stmt->execute([
                ':user_id' => $userId,
                ':mobile' => $mobile,
                ':otp' => $otp
            ]);
        } catch (PDOException $e) {
            error_log("OTP Storage Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a cryptographically secure OTP
     * @param int $length OTP length (default 6)
     * @return string Generated OTP
     */
    public function generateSecureOTP($length = 6) {
        $otp = '';
        $chars = '0123456789';
        
        for ($i = 0; $i < $length; $i++) {
            // Use random_int for cryptographically secure random number generation
            $otp .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $otp;
    }

    /**
     * Validate mobile number format
     * @param string $mobile Mobile number
     * @return bool
     */
    public function validateMobileNumber($mobile) {
        // Remove non-numeric characters
        $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);
        
        // Check for valid mobile number formats
        // This is a basic validation - adjust as per your country's mobile number rules
        return (
            preg_match('/^(\\+?\\d{10,14})$/', $cleanMobile) === 1
        );
    }

    /**
     * Verify OTP
     * @param int $userId User ID
     * @param string $otp Submitted OTP
     * @return bool Verification status
     */
    public function verifyOTP($userId, $otp) {
        try {
            $stmt = $this->database->prepare("
                SELECT * FROM bg_otp_verification 
                WHERE user_id = :user_id 
                AND otp = :otp 
                AND platform = 'telegram' 
                AND expiry_time > NOW() 
                AND verified = 0
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':otp' => $otp
            ]);

            if ($stmt->rowCount() > 0) {
                // Mark OTP as verified
                $updateStmt = $this->database->prepare("
                    UPDATE bg_otp_verification 
                    SET verified = 1 
                    WHERE user_id = :user_id 
                    AND otp = :otp
                ");
                $updateStmt->execute([
                    ':user_id' => $userId,
                    ':otp' => $otp
                ]);

                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("OTP Verification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Debugging method to get Telegram configuration
     * @return array Telegram configuration details
     */
    public function getConfig() {
        return [
            'token' => $this->telegramToken ? 'SET' : 'NOT SET',
            'api' => $this->telegramAPI
        ];
    }
}