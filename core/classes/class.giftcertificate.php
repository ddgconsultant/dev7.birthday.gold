<?php
/**
 * Gift Certificate Management Class
 * Handles gift certificate operations using bg_user_attributes table
 */

class GiftCertificate {
    private $database;
    
    public function __construct($database) {
        $this->database = $database;
    }
    
    /**
     * Get all gift certificates purchased by a user
     * @param int $user_id User ID
     * @return array Array of gift certificates
     */
    public function getUserGiftCertificates($user_id) {
        // Get all gift certificate attributes for this user
        $sql = "SELECT name, description, string_value 
                FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = 'gift_certificate' 
                AND status = 'active'
                ORDER BY create_dt DESC";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $gift_certificates = [];
        
        foreach ($results as $row) {
            // Parse the JSON description field
            $gift_data = json_decode($row['description'], true);
            if ($gift_data) {
                $gift_data['gift_id'] = str_replace('gift_cert_', '', $row['name']);
                $gift_data['code'] = $row['string_value'];
                $gift_certificates[] = $gift_data;
            }
        }
        
        return $gift_certificates;
    }
    
    /**
     * Get a specific gift certificate by ID
     * @param int $user_id User ID
     * @param string $gift_id Gift certificate ID
     * @return array|null Gift certificate data or null if not found
     */
    public function getGiftCertificate($user_id, $gift_id) {
        $sql = "SELECT description, string_value 
                FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = 'gift_certificate'
                AND name = :name
                AND status = 'active'";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'name' => 'gift_cert_' . $gift_id
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        // Parse the JSON description
        $gift_data = json_decode($result['description'], true);
        if (!$gift_data) {
            return null;
        }
        
        $gift_data['gift_id'] = $gift_id;
        $gift_data['code'] = $result['string_value'];
        
        return $gift_data;
    }
    
    /**
     * Get gift certificate by code
     * @param string $code Gift certificate code
     * @return array|null Gift certificate data or null if not found
     */
    public function getGiftCertificateByCode($code) {
        // Search for the code in user attributes
        $sql = "SELECT user_id, name, description 
                FROM bg_user_attributes 
                WHERE type = 'gift_certificate' 
                AND string_value = :code
                AND status = 'active'";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['code' => $code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        // Parse the gift data
        $gift_data = json_decode($result['description'], true);
        if (!$gift_data) {
            return null;
        }
        
        $gift_data['gift_id'] = str_replace('gift_cert_', '', $result['name']);
        $gift_data['code'] = $code;
        $gift_data['buyer_user_id'] = $result['user_id'];
        
        return $gift_data;
    }
    
    /**
     * Redeem a gift certificate
     * @param string $code Gift certificate code
     * @param int $redeemer_user_id User ID of person redeeming
     * @return array Result array with success status and message
     */
    public function redeemGiftCertificate($code, $redeemer_user_id) {
        $gift_cert = $this->getGiftCertificateByCode($code);
        
        if (!$gift_cert) {
            return ['success' => false, 'message' => 'Invalid gift certificate code'];
        }
        
        // Check if already redeemed
        if ($gift_cert['status'] === 'redeemed') {
            return ['success' => false, 'message' => 'This gift certificate has already been redeemed'];
        }
        
        // Check if expired
        if (strtotime($gift_cert['expires_at']) < time()) {
            return ['success' => false, 'message' => 'This gift certificate has expired'];
        }
        
        // Update the gift certificate status
        $gift_cert['status'] = 'redeemed';
        $gift_cert['redeemed_at'] = date('Y-m-d H:i:s');
        $gift_cert['redeemed_by'] = $redeemer_user_id;
        
        // Update the description field with new status
        $sql = "UPDATE bg_user_attributes 
                SET description = :description,
                    modify_dt = NOW() 
                WHERE user_id = :user_id 
                AND type = 'gift_certificate'
                AND name = :name";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            'user_id' => $gift_cert['buyer_user_id'],
            'name' => 'gift_cert_' . $gift_cert['gift_id'],
            'description' => json_encode($gift_cert)
        ]);
        
        // Create redemption record for the redeemer
        $sql = "INSERT INTO bg_user_attributes 
                (user_id, type, name, description, string_value, category, grouping, status, create_dt) 
                VALUES 
                (:user_id, 'gift_certificate_redeemed', :name, :description, :code, 'gift_redeemed', 'gift_certificates', 'active', NOW())";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            'user_id' => $redeemer_user_id,
            'name' => 'redeemed_gift_' . $gift_cert['gift_id'],
            'description' => json_encode([
                'gift_id' => $gift_cert['gift_id'],
                'code' => $code,
                'plan_id' => $gift_cert['plan_id'],
                'plan_name' => $gift_cert['plan_name'],
                'redeemed_at' => date('Y-m-d H:i:s'),
                'buyer_user_id' => $gift_cert['buyer_user_id']
            ]),
            'code' => $code
        ]);
        
        return [
            'success' => true, 
            'message' => 'Gift certificate redeemed successfully',
            'plan_id' => $gift_cert['plan_id'],
            'plan_name' => $gift_cert['plan_name']
        ];
    }
    
    /**
     * Get gift certificates that need to be delivered
     * @param string $date Date to check for scheduled deliveries (Y-m-d)
     * @return array Array of gift certificates to deliver
     */
    public function getScheduledDeliveries($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        // Find all scheduled deliveries for the specified date
        $sql = "SELECT user_id, name, description, string_value 
                FROM bg_user_attributes 
                WHERE type = 'gift_delivery_schedule'
                AND DATE(start_dt) = :date
                AND status = 'pending'";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['date' => $date]);
        $scheduled = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $deliveries = [];
        
        foreach ($scheduled as $row) {
            $delivery_info = json_decode($row['description'], true);
            if ($delivery_info) {
                $delivery_info['delivery_methods'] = explode(',', $row['string_value']);
                $delivery_info['buyer_user_id'] = $row['user_id'];
                $delivery_info['delivery_attribute_name'] = $row['name'];
                $deliveries[] = $delivery_info;
            }
        }
        
        return $deliveries;
    }
    
    /**
     * Mark a scheduled delivery as sent
     * @param int $user_id User ID
     * @param string $attribute_name Attribute name for the delivery record
     * @return bool Success
     */
    public function markDeliveryAsSent($user_id, $attribute_name) {
        $sql = "UPDATE bg_user_attributes 
                SET status = 'sent',
                    end_dt = NOW(),
                    modify_dt = NOW() 
                WHERE user_id = :user_id 
                AND name = :name
                AND type = 'gift_delivery_schedule'";
        
        $stmt = $this->database->prepare($sql);
        return $stmt->execute([
            'user_id' => $user_id,
            'name' => $attribute_name
        ]);
    }
    
    /**
     * Generate a printable gift certificate
     * @param array $gift_cert Gift certificate data
     * @return string HTML content for printing
     */
    public function generatePrintableGiftCertificate($gift_cert) {
        $recipient_name = $gift_cert['recipient']['firstname'] . ' ' . $gift_cert['recipient']['lastname'];
        $message = $gift_cert['delivery']['message'] ?? 'Happy Birthday! Enjoy your special rewards.';
        
        $html = '
        <div style="width: 600px; margin: 0 auto; padding: 20px; border: 2px solid #ddd; font-family: Arial, sans-serif;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #333;">Birthday Gold</h1>
                <h2 style="color: #666;">Gift Certificate</h2>
            </div>
            
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <p style="font-size: 18px; margin: 10px 0;">This certificate entitles</p>
                <p style="font-size: 24px; font-weight: bold; color: #333; margin: 10px 0;">' . htmlspecialchars($recipient_name) . '</p>
                <p style="font-size: 18px; margin: 10px 0;">to</p>
                <p style="font-size: 22px; font-weight: bold; color: #0066cc; margin: 10px 0;">' . htmlspecialchars($gift_cert['plan_name']) . '</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <p style="font-size: 16px; color: #666;">Gift Certificate Code:</p>
                <p style="font-size: 28px; font-weight: bold; color: #0066cc; letter-spacing: 2px;">' . htmlspecialchars($gift_cert['code']) . '</p>
            </div>
            
            ' . (!empty($message) ? '<div style="background: #fff; padding: 15px; border-left: 4px solid #0066cc; margin: 20px 0;">
                <p style="font-style: italic; color: #666; margin: 0;">' . htmlspecialchars($message) . '</p>
            </div>' : '') . '
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                <p style="color: #999; font-size: 12px;">Valid until ' . date('F j, Y', strtotime($gift_cert['expires_at'])) . '</p>
                <p style="color: #999; font-size: 12px;">Redeem at birthday.gold</p>
            </div>
        </div>';
        
        return $html;
    }
}