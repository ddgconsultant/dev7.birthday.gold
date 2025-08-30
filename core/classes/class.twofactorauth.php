<?php
class TwoFactorAuth {
    private $secretLength = 16;  // Default length for secrets
    private $telegramToken = 'your-telegram-bot-token';
    private $smtpConfig = [
        'host' => 'smtp.gmail.com',
        'username' => 'your-smtp-username',
        'password' => 'your-smtp-password',
        'port' => 587,
        'from_email' => 'no-reply@birthday.gold',
        'from_name' => 'Birthday.Gold'
    ];

# ##==================================================================================================================================================
# ##==================================================================================================================================================
# ##==================================================================================================================================================

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Create a TOTP secret
    public function createSecret($length = 16) {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        $secret = '';
        
        if ($length < 16 || $length > 128) {
            throw new Exception('Bad secret length');
        }
        
        $rnd = random_bytes($length);
        for ($i = 0; $i < $length; ++$i) {
            $secret .= $validChars[ord($rnd[$i]) & 31];  // 31 = 0x1F = 00011111
        }
        
        return $secret;
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Verify a TOTP code
    public function verifyCode($secret, $code, $discrepancy = 1) {
        if (strlen($code) != 6) {
            return false;
        }
        
        $currentTimeSlice = floor(time() / 30);
        
        // Check codes generated in last 30s and next 30s if discrepancy = 1
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if ($this->timingSafeEquals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function getCode($secret, $timeSlice) {
        $secretkey = $this->TOTPAuthbase32Decode($secret);
        
        // Pack time into binary string
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        
        // Hash it with users secret key
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        
        // Use last nipple of result as index/offset
        $offset = ord(substr($hm, -1)) & 0x0F;
        
        // grab 4 bytes of the result
        $hashpart = substr($hm, $offset, 4);
        
        // Unpack binary value
        $value = unpack('N', $hashpart)[1];
        
        // Only 32 bits
        $value = $value & 0x7FFFFFFF;
        
        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function TOTPAuthbase32Decode($secret) {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = array(6, 4, 3, 1, 0);
        
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        
        $secret = strtoupper($secret);
        $secret = str_replace('=', '', $secret);
        $secretLength = strlen($secret);
        $buffer = 0;
        $bufferBits = 0;
        $binary = '';
        
        for ($i = 0; $i < $secretLength; $i++) {
            $buffer = ($buffer << 5) | $base32charsFlipped[$secret[$i]];
            $bufferBits += 5;
            
            if ($bufferBits >= 8) {
                $bufferBits -= 8;
                $binary .= chr(($buffer >> $bufferBits) & 0xFF);
                $buffer &= ((1 << $bufferBits) - 1);
            }
        }
        
        return $binary;
    }

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function timingSafeEquals($safe, $user) {
        return hash_equals(
            str_pad($safe, strlen($user), '0'),
            str_pad($user, strlen($safe), '0')
        );
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Check if 2FA is enabled for the user
    public function isTwoFactorEnabled($userData) {
        // Assume $userData includes user attributes
        return !empty($userData['2fa_enabled']) && !empty($userData['2fa_method']);
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Generate a 6-digit code
    public function generateCode() {
        return rand(100000, 999999);
    }

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Send verification code for "Secure" method (email/SMS)
    public function sendVerificationCode($user_id, $email = null, $phone = null) {
        global $database;
        
        error_log("2FA sendVerificationCode: UserID=$user_id, Email='$email', Phone='$phone'");
        
        // Generate a new code
        $code = $this->generateCode();
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        error_log("2FA sendVerificationCode: Generated code='$code', ExpiresAt='$expires_at'");
        
        // First, deactivate any existing codes for this user
        $cleanup_sql = "UPDATE bg_user_attributes 
                        SET status = 'inactive', modify_dt = NOW() 
                        WHERE user_id = :user_id 
                        AND type = '2fa_temp_code' 
                        AND name = 'verification_code' 
                        AND status = 'active'";
        
        $cleanup_stmt = $database->prepare($cleanup_sql);
        $cleanup_stmt->execute(['user_id' => $user_id]);
        
        // Insert the new code
        $sql = "INSERT INTO bg_user_attributes (user_id, type, name, string_value, description, status, create_dt, modify_dt) 
                VALUES (:user_id, '2fa_temp_code', 'verification_code', :code, :expires_at, 'active', NOW(), NOW())";
        
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'code' => $code,
            'expires_at' => $expires_at
        ]);
        
        $sent = false;
        $error = '';
        
        // Prioritize SMS over email for security (phone is more secure)
        if (!empty($phone)) {
            if ($this->sendSMS($phone, $code)) {
                $sent = true;
            } else {
                $error = 'Failed to send SMS';
            }
        }
        
        // Send via email if SMS failed or phone not available
        if (!empty($email) && !$sent) {
            if ($this->sendEmail($email, $code)) {
                $sent = true;
                $error = ''; // Clear SMS error if email succeeds
            } else {
                $error = empty($error) ? 'Failed to send email' : $error . '; Failed to send email';
            }
        }
        
        return [
            'success' => $sent,
            'error' => $error,
            'expires_at' => $expires_at
        ];
    }

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Verify a temporary code for "Secure" method
    public function verifyTempCode($user_id, $code) {
        global $database;
        
        error_log("2FA verifyTempCode: UserID=$user_id, SubmittedCode='$code'");
        
        // Get the stored code (most recent active code)
        $sql = "SELECT string_value, description FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = '2fa_temp_code' 
                AND name = 'verification_code' 
                AND status = 'active'
                ORDER BY create_dt DESC
                LIMIT 1";
        
        $stmt = $database->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $stored = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("2FA verifyTempCode: StoredCode='" . ($stored['string_value'] ?? 'NULL') . "', ExpiresAt='" . ($stored['description'] ?? 'NULL') . "'");
        
        if (!$stored) {
            error_log("2FA verifyTempCode: No code found in database");
            return ['success' => false, 'error' => 'No verification code found'];
        }
        
        // Check if code has expired
        if (strtotime($stored['description']) < time()) {
            error_log("2FA verifyTempCode: Code expired. ExpiryTime=" . $stored['description'] . ", CurrentTime=" . date('Y-m-d H:i:s'));
            // Clean up expired code
            $this->cleanupTempCode($user_id);
            return ['success' => false, 'error' => 'Verification code has expired'];
        }
        
        // Verify the code
        if ($stored['string_value'] === $code) {
            error_log("2FA verifyTempCode: Code match successful");
            // Clean up the specific used code
            $this->cleanupTempCode($user_id, $code);
            return ['success' => true];
        } else {
            error_log("2FA verifyTempCode: Code mismatch. Stored='" . $stored['string_value'] . "', Submitted='$code'");
            return ['success' => false, 'error' => 'Invalid verification code'];
        }
    }

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Clean up temporary verification code
    private function cleanupTempCode($user_id, $specific_code = null) {
        global $database;
        
        // If a specific code is provided, only deactivate that code
        // Otherwise, deactivate all active codes for this user (for backwards compatibility)
        if ($specific_code !== null) {
            $sql = "UPDATE bg_user_attributes 
                    SET status = 'inactive', modify_dt = NOW() 
                    WHERE user_id = :user_id 
                    AND type = '2fa_temp_code' 
                    AND name = 'verification_code'
                    AND string_value = :code
                    AND status = 'active'";
            
            $stmt = $database->prepare($sql);
            $stmt->execute(['user_id' => $user_id, 'code' => $specific_code]);
        } else {
            $sql = "UPDATE bg_user_attributes 
                    SET status = 'inactive', modify_dt = NOW() 
                    WHERE user_id = :user_id 
                    AND type = '2fa_temp_code' 
                    AND name = 'verification_code'
                    AND status = 'active'";
            
            $stmt = $database->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
        }
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Send 2FA code via email
    public function sendEmail($email, $code) {
        global $mail_config, $mail, $dir;
        
        // Try to use the existing mail system
        if (isset($mail) && is_object($mail)) {
            $email_details = [
                'to' => [$email, $email],
                'from' => ['noreply@birthday.gold', 'Birthday.Gold Security'],
                'subject' => 'Your 2FA Verification Code',
                'body' => "
                    <h2>Your Two-Factor Authentication Code</h2>
                    <p>Your verification code is: <strong style='font-size: 1.5em; color: #0d6efd;'>$code</strong></p>
                    <p>This code will expire in 15 minutes for your security.</p>
                    <p>If you did not request this code, please ignore this email.</p>
                    <hr>
                    <small>This is an automated message from Birthday.Gold</small>
                ",
                'donottrack' => true
            ];
            
            try {
                return $mail->sendmail($email_details);
            } catch (Exception $e) {
                error_log('Mail object sendmail failed: ' . $e->getMessage());
                return false;
            }
        } else {
            // Initialize mail if not available
            if (!isset($mail) && isset($mail_config) && isset($dir)) {
                try {
                    include_once($dir['core_classes'] . '/class.mail.php');
                    $mail = new Mail($mail_config);
                } catch (Exception $e) {
                    error_log('Failed to initialize Mail class: ' . $e->getMessage());
                }
                
                // Try again with initialized mail
                if (isset($mail) && is_object($mail)) {
                    $email_details = [
                        'to' => [$email, $email],
                        'from' => ['noreply@birthday.gold', 'Birthday.Gold Security'],
                        'subject' => 'Your 2FA Verification Code',
                        'body' => "
                            <h2>Your Two-Factor Authentication Code</h2>
                            <p>Your verification code is: <strong style='font-size: 1.5em; color: #0d6efd;'>$code</strong></p>
                            <p>This code will expire in 15 minutes for your security.</p>
                            <p>If you did not request this code, please ignore this email.</p>
                            <hr>
                            <small>This is an automated message from Birthday.Gold</small>
                        ",
                        'donottrack' => true
                    ];
                    
                    try {
                        return $mail->sendmail($email_details);
                    } catch (Exception $e) {
                        error_log('Initialized mail sendmail failed: ' . $e->getMessage());
                        return false;
                    }
                }
            }
            
            // Final fallback to basic mail function
            $subject = "Your 2FA Code - Birthday.Gold";
            $message = "<h2>Your Two-Factor Authentication Code</h2>
                       <p>Your verification code is: <strong style='font-size: 1.5em; color: #0d6efd;'>$code</strong></p>
                       <p>This code will expire in 15 minutes for your security.</p>
                       <p>If you did not request this code, please ignore this email.</p>";
            $headers = "From: Birthday.Gold <noreply@birthday.gold>\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            
            return mail($email, $subject, $message, $headers);
        }
    }

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Send 2FA code via SMS  
    public function sendSMS($phone, $code) {
        global $sms;
        
        error_log("2FA sendSMS: Original phone='$phone', Code='$code'");
        
        // Clean phone number - remove all non-digits
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format for SMS gateway - Birthday Gold SMS expects 10-digit US numbers without country code
        if (strlen($clean_phone) === 11 && str_starts_with($clean_phone, '1')) {
            $clean_phone = substr($clean_phone, 1); // Remove leading 1
        }
        
        // Validate we have exactly 10 digits
        if (strlen($clean_phone) !== 10) {
            error_log("2FA sendSMS: Invalid phone number length: " . strlen($clean_phone) . " digits");
            return false;
        }
        
        error_log("2FA sendSMS: Cleaned phone='$clean_phone' (10 digits)");
        
        $message = "Your Birthday.Gold 2FA code is: {$code}. This code expires in 15 minutes.";
        
        try {
            if (isset($sms) && is_object($sms)) {
                error_log("2FA sendSMS: SMS object available, attempting to send");
                $result = $sms->sendSingleMessage($clean_phone, $message);
                error_log("2FA sendSMS: SMS result: " . json_encode($result));
                
                // Check various possible success indicators
                if (isset($result['status']) && $result['status'] === 'success') {
                    return true;
                } elseif (isset($result['success']) && $result['success'] === true) {
                    return true;
                } elseif (isset($result['id']) && !empty($result['id'])) {
                    return true; // SMS was queued with an ID
                } else {
                    error_log("2FA sendSMS: SMS failed - no success indicator found");
                    return false;
                }
            } else {
                error_log('2FA sendSMS: SMS object not available for 2FA');
                return false;
            }
        } catch (Exception $e) {
            error_log('2FA sendSMS failed: ' . $e->getMessage());
            return false;
        }
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Send 2FA code via Telegram
    public function sendTelegram($chatId, $code) {
        $url = "https://api.telegram.org/bot{$this->telegramToken}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => "Your 2FA code is: $code. It expires in 15 minutes."
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response !== false;
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Store the code and expiration
    public function storeCode($userId, $code, $expirationTime, $db) {
        $stmt = $db->prepare("UPDATE bg_user_attributes SET 2fa_code = :code, 2fa_expires = :expires WHERE user_id = :user_id");
        return $stmt->execute([
            ':code' => $code,
            ':expires' => $expirationTime,
            ':user_id' => $userId
        ]);
    }

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Validate the user-entered 2FA code
    public function validateCode($userId, $inputCode, $db) {
        $stmt = $db->prepare("SELECT 2fa_code, 2fa_expires FROM bg_user_attributes WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['2fa_code'] == $inputCode && time() < $result['2fa_expires']) {
            $this->clearCode($userId, $db);
            return true;
        }
        return false;
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Clear the 2FA code after use
    private function clearCode($userId, $db) {
        $stmt = $db->prepare("UPDATE bg_user_attributes SET 2fa_code = NULL, 2fa_expires = NULL WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
    }
}
