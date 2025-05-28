<?php
class TwoFactorAuth {
    private $secretLength = 16;  // Default length for secrets
    private $telegramToken = 'your-telegram-bot-token';
    private $smtpConfig = [
        'host' => 'smtp.example.com',
        'username' => 'your-smtp-username',
        'password' => 'your-smtp-password',
        'port' => 587,
        'from_email' => 'no-reply@example.com',
        'from_name' => 'YourAppName'
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
    // Send 2FA code via email
    public function sendEmail($email, $code) {
        $subject = "Your 2FA Code";
        $message = "Your 2FA code is: $code. It expires in 15 minutes.";
        $headers = [
            'From' => "{$this->smtpConfig['from_name']} <{$this->smtpConfig['from_email']}>",
            'Reply-To' => $this->smtpConfig['from_email'],
            'X-Mailer' => 'PHP/' . phpversion()
        ];

        return mail($email, $subject, $message, $headers);
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
