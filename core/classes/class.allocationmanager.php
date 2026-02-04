<?php
/**
 * AllocationManager Class for Enrollment Allocations
 * Uses centralized $account->getAllocationBalance() for consistency
 */

class AllocationManager {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get user's current allocation balance
     * Delegates to centralized $account->getAllocationBalance() for consistency
     *
     * @param int $user_id User ID
     * @param int|null $year Year (kept for API compatibility, not used)
     * @param array $options Options to pass to getAllocationBalance
     * @return array Allocation balance data
     */
    public function getUserBalance($user_id, $year = null, $options = []) {
        global $account;

        // Use centralized function from Account class
        $balance = $account->getAllocationBalance($user_id, $options);

        // Count expiring soon (within 30 days) - bonus-specific feature
        $currentYear = date('Y');
        $sql = "SELECT COUNT(*) as expiring_count
                FROM bg_user_allocations
                WHERE user_id = :user_id
                AND allocation_year = :year
                AND status = 'active'
                AND amount > amount_used
                AND expires_at IS NOT NULL
                AND expires_at > NOW()
                AND expires_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)";

        $expiring_result = $this->db->getrow($sql, [
            'user_id' => $user_id,
            'year' => $currentYear
        ]);

        // Return in expected format for backward compatibility
        return [
            'user_id' => $user_id,
            'year' => $currentYear,
            'available_allocations' => $balance['available'],
            'total_earned' => $balance['total_allocations'],
            'total_used' => $balance['used'],
            'earn_count' => 0,
            'use_count' => $balance['used'],
            'expiring_soon_count' => $expiring_result['expiring_count'] ?? 0,
            'pending_allocations' => $balance['pending'],
            'plan_allocations' => $balance['plan_allocations'],
            'bonus_allocations' => $balance['bonus_allocations'],
            'plan_limit' => $balance['plan_allocations'],
            'cart_count' => $balance['cart']
        ];
    }
    
    /**
     * Get allocation warning message
     */
    public function getAllocationWarning($user_id, $options = []) {
        $balance = $this->getUserBalance($user_id, null, $options);

        if ($balance['available_allocations'] == 0) {
            return [
                'type' => 'danger',
                'message' => 'You have no enrollment allocations left.'
            ];
        } elseif ($balance['available_allocations'] <= 3) {
            return [
                'type' => 'warning',
                'message' => "You have only {$balance['available_allocations']} enrollments left."
            ];
        }

        return null;
    }
    
    /**
     * Use an allocation for enrollment
     * This is a placeholder for when enrollment tracking is implemented
     */
    public function useAllocation($user_id, $company_id, $enrollment_id = null) {
        // Check balance
        $balance = $this->getUserBalance($user_id);
        if ($balance['available_allocations'] < 1) {
            return ['error' => 'No available allocations'];
        }
        
        // In the future, this would record the usage
        return ['success' => true];
    }
    
    /**
     * Grant bonus allocations
     */
    public function grantBonus($user_id, $amount, $reason, $reference_type = 'bonus') {
        $year = date('Y');
        
        try {
            // First ensure user has plan allocation for the year
            $this->ensurePlanAllocation($user_id, $year);
            
            // Insert bonus allocation
            $sql = "INSERT INTO bg_user_allocations (
                        user_id, 
                        allocation_type, 
                        allocation_year, 
                        amount, 
                        allocation_comment,
                        reference_type,
                        created_by,
                        starts_at,
                        status
                    ) VALUES (
                        :user_id1,
                        'bonus',
                        :year,
                        :amount,
                        :reason,
                        :reference_type,
                        :user_id2,
                        NOW(),
                        'active'
                    )";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'user_id1' => $user_id,
                'user_id2' => $user_id,
                'year' => $year,
                'amount' => $amount,
                'reason' => $reason,
                'reference_type' => $reference_type
            ]);
            
            if (!$result) {
                throw new Exception("Failed to insert allocation");
            }
            
            $insert_id = $this->db->lastInsertId();
            
            return ['success' => true, 'message' => "Added {$amount} bonus allocations (ID: {$insert_id})", 'insert_id' => $insert_id];
        } catch (Exception $e) {
            error_log("AllocationManager::grantBonus error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Ensure user has plan allocation for the year
     */
    public function ensurePlanAllocation($user_id, $year) {
        global $app, $account;
        
        // Check if plan allocation exists
        $check_sql = "SELECT COUNT(*) as count FROM bg_user_allocations 
                      WHERE user_id = :user_id 
                      AND allocation_year = :year 
                      AND allocation_type = 'plan'";
        
        $result = $this->db->getrow($check_sql, [
            'user_id' => $user_id,
            'year' => $year
        ]);
        
        if ($result['count'] == 0) {
            // Get user's plan details
            $user_data = $account->getuserdata($user_id, 'user_id');
            $plan_details = $app->plandetail('details_id', $user_data['account_product_id']);
            $plan_allocations = $plan_details['max_business_select'] ?? 10;
            
            if ($user_data['account_plan'] == 'free') {
                $plan_allocations = 3;
            }
            
            // Insert plan allocation
            $sql = "INSERT INTO bg_user_allocations (
                        user_id, 
                        allocation_type, 
                        allocation_year, 
                        amount, 
                        allocation_comment,
                        reference_type,
                        created_by,
                        starts_at,
                        status,
                        is_recurring
                    ) VALUES (
                        :user_id1,
                        'plan',
                        :year1,
                        :amount,
                        'Annual plan allocation',
                        'plan',
                        :user_id2,
                        CONCAT(:year2, '-01-01'),
                        'active',
                        1
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id1' => $user_id,
                'user_id2' => $user_id,
                'year1' => $year,
                'year2' => $year,
                'amount' => $plan_allocations
            ]);
        }
    }
    
    /**
     * Check if user has earned a specific bonus type (placeholder)
     */
    public function hasEarnedBonus($user_id, $bonus_type, $within_days = null) {
        // For now, return false
        return false;
    }
}
?>