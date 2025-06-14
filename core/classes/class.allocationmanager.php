<?php
/**
 * AllocationManager Class for Enrollment Allocations
 * Simplified version that works without additional tables
 */

class AllocationManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get user's current allocation balance from database
     */
    public function getUserBalance($user_id, $year = null) {
        global $app, $account;
        
        if (!$year) {
            $year = date('Y');
        }
        
        // Get user's plan details
        $user_data = $account->getuserdata($user_id, 'user_id');
        $plan_details = $app->plandetail('details_id', $user_data['account_product_id']);
        
        // Get allocations from bg_user_allocations table
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) THEN (COALESCE(amount, 0) - COALESCE(amount_used, 0)) ELSE 0 END), 0) as total_available,
                    COALESCE(SUM(CASE WHEN allocation_type = 'plan' THEN amount ELSE 0 END), 0) as plan_allocations,
                    COALESCE(SUM(CASE WHEN allocation_type = 'bonus' THEN amount ELSE 0 END), 0) as bonus_allocations,
                    COALESCE(SUM(amount), 0) as total_allocated,
                    COALESCE(SUM(COALESCE(amount_used, 0)), 0) as total_used_from_allocations
                FROM bg_user_allocations
                WHERE user_id = :user_id
                AND allocation_year = :year
                AND status IN ('active', 'depleted')";
        
        $allocation_data = $this->db->getrow($sql, [
            'user_id' => $user_id,
            'year' => $year
        ]);
        
        // Debug logging
        error_log("AllocationManager::getUserBalance - Raw allocation data: " . json_encode($allocation_data));
        
        // Get number of enrollments used this year from bg_user_companies
        $sql = "SELECT COUNT(*) as used_count 
                FROM bg_user_companies 
                WHERE user_id = :user_id 
                AND YEAR(create_dt) = :year 
                AND status NOT IN ('failed', 'removed')";
        
        $result = $this->db->getrow($sql, [
            'user_id' => $user_id,
            'year' => $year
        ]);
        
        $used_count = $result['used_count'] ?? 0;
        
        // Get max allocations from plan (default to 10 if not set)
        $plan_max_allocations = $plan_details['max_business_select'] ?? 10;
        
        // For free plans, give a small number of allocations
        if ($user_data['account_plan'] == 'free') {
            $plan_max_allocations = 3;
        }
        
        // Check if we have any allocations at all (including just bonus)
        $has_any_allocations = !empty($allocation_data) && ($allocation_data['total_allocated'] > 0 || $allocation_data['bonus_allocations'] > 0);
        
        if ($has_any_allocations) {
            // Use data from database
            $total_available = $allocation_data['total_available'] ?? 0;
            $total_allocated = $allocation_data['total_allocated'] ?? 0;
            $plan_allocations = $allocation_data['plan_allocations'] ?? 0;
            $bonus_allocations = $allocation_data['bonus_allocations'] ?? 0;
            
            // If we have bonus but no plan allocations, add the plan default to available
            if ($plan_allocations == 0 && $bonus_allocations > 0) {
                // User has bonus allocations but no plan allocation record
                // Add the plan max to their available balance
                $total_available += max(0, $plan_max_allocations - $used_count);
                $total_allocated += $plan_max_allocations;
                // Don't create the plan allocation record here, just account for it
            }
        } else {
            // No allocations at all - use plan defaults
            $total_available = max(0, $plan_max_allocations - $used_count);
            $total_allocated = $plan_max_allocations;
            $plan_allocations = $plan_max_allocations;
            $bonus_allocations = 0;
        }
        
        // Count expiring soon (within 30 days)
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
            'year' => $year
        ]);
        
        return [
            'user_id' => $user_id,
            'year' => $year,
            'available_allocations' => $total_available,
            'total_earned' => $total_allocated,
            'total_used' => $used_count,
            'earn_count' => 0, // Can be calculated from allocation records
            'use_count' => $used_count,
            'expiring_soon_count' => $expiring_result['expiring_count'] ?? 0,
            'pending_allocations' => 0, // Can be implemented later
            'plan_allocations' => $plan_allocations,
            'bonus_allocations' => $bonus_allocations
        ];
    }
    
    /**
     * Get allocation warning message
     */
    public function getAllocationWarning($user_id) {
        $balance = $this->getUserBalance($user_id);
        
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