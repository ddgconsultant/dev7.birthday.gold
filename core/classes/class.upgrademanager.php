<?php
/**
 * Upgrade Manager Class
 * Handles plan upgrades, eligibility checking, and processing
 */

class UpgradeManager {
    private $database;
    private $account;
    private $productManager;
    private $session;
    private $qik;
    
    public function __construct($database, $account, $productManager, $session, $qik = null) {
        $this->database = $database;
        $this->account = $account;
        $this->productManager = $productManager;
        $this->session = $session;
        $this->qik = $qik;
    }
    
    /**
     * Check if current user can upgrade their plan
     * @return array Upgrade options available
     */
    public function getUpgradeOptions($userId = null) {
        if ($userId === null) {
            $userId = $this->account->getUserId();
        }
        
        // Get current user's plan
        $currentPlan = $this->getCurrentUserPlan($userId);
        if (!$currentPlan) {
            return [
                'can_upgrade' => false,
                'reason' => 'No active plan found',
                'current_plan' => null,
                'upgrade_options' => []
            ];
        }
        
        // Get upgradeable features for current product
        $upgradeableFeature = $this->getUpgradeableFeature($currentPlan['product_id']);
        if (!$upgradeableFeature) {
            return [
                'can_upgrade' => false, 
                'reason' => 'Current plan does not support upgrades',
                'current_plan' => $currentPlan,
                'upgrade_options' => []
            ];
        }
        
        // Parse the upgradeable product IDs
        $upgradeableProductIds = json_decode($upgradeableFeature['value'], true);
        if (empty($upgradeableProductIds)) {
            return [
                'can_upgrade' => false, 
                'reason' => 'No upgrade paths configured',
                'current_plan' => $currentPlan,
                'upgrade_options' => []
            ];
        }
        
        // Get details for each upgradeable product
        $upgradeOptions = [];
        foreach ($upgradeableProductIds as $productId) {
            $product = $this->productManager->getProduct($productId);
            if ($product && $product['status'] === 'active') {
                // Calculate upgrade pricing
                $upgradePricing = $this->calculateUpgradePricing($currentPlan, $product);
                
                $upgradeOptions[] = [
                    'product' => $product,
                    'pricing' => $upgradePricing,
                    'upgrade_token' => $this->generateUpgradeToken($userId, $currentPlan['product_id'], $productId)
                ];
            }
        }
        
        return [
            'can_upgrade' => !empty($upgradeOptions),
            'current_plan' => $currentPlan,
            'upgrade_options' => $upgradeOptions
        ];
    }
    
    /**
     * Get current user's active plan
     */
    private function getCurrentUserPlan($userId) {
        $sql = "SELECT u.*, p.* 
                FROM bg_users u
                JOIN bg_products p ON u.account_plan = p.account_plan
                WHERE u.user_id = :user_id
                AND u.status = 'active'
                AND p.status = 'active'
                LIMIT 1";
        
        return $this->database->getrow($sql, ['user_id' => $userId]);
    }
    
    /**
     * Get upgradeable feature for a product
     */
    private function getUpgradeableFeature($productId) {
        $sql = "SELECT * FROM bg_product_features 
                WHERE product_id = :product_id 
                AND name = 'upgradeable'
                AND status = 'active'
                LIMIT 1";
        
        return $this->database->getrow($sql, ['product_id' => $productId]);
    }
    
    /**
     * Calculate upgrade pricing (prorated, etc)
     */
    private function calculateUpgradePricing($currentPlan, $newPlan) {
        $currentPrice = intval($currentPlan['price']);
        $newPrice = intval($newPlan['price']);
        
        // Basic calculation - can be enhanced with proration logic
        $upgradeCost = $newPrice - $currentPrice;
        
        // If user has time remaining on current plan, calculate proration
        // This is a simplified version - you may want to enhance this
        $proration = 0;
        if ($currentPrice > 0) {
            // Check if user has paid subscription
            $sql = "SELECT * FROM bg_payments 
                    WHERE user_id = :user_id 
                    AND status = 'completed'
                    AND payment_type = 'subscription'
                    ORDER BY created_at DESC
                    LIMIT 1";
            
            $lastPayment = $this->database->getrow($sql, ['user_id' => $this->account->getUserId()]);
            
            if ($lastPayment) {
                // Calculate days remaining in current period
                // This would need to be enhanced based on your billing cycle logic
                $proration = $this->calculateProration($lastPayment, $currentPrice, $newPrice);
            }
        }
        
        return [
            'current_price' => $currentPrice,
            'new_price' => $newPrice,
            'upgrade_cost' => max(0, $upgradeCost - $proration),
            'proration' => $proration,
            'total_due' => max(0, $upgradeCost - $proration)
        ];
    }
    
    /**
     * Calculate proration amount
     */
    private function calculateProration($lastPayment, $currentPrice, $newPrice) {
        // This is a placeholder - implement based on your billing cycle
        // For now, return 0 (no proration)
        return 0;
    }
    
    /**
     * Generate secure upgrade token
     */
    private function generateUpgradeToken($userId, $fromProductId, $toProductId) {
        $data = [
            'user_id' => $userId,
            'from_product_id' => $fromProductId,
            'to_product_id' => $toProductId,
            'timestamp' => time(),
            'session_id' => session_id()
        ];
        
        return base64_encode(json_encode($data));
    }
    
    /**
     * Validate upgrade token
     */
    public function validateUpgradeToken($token) {
        try {
            $data = json_decode(base64_decode($token), true);
            
            // Check token age (valid for 1 hour)
            if ((time() - $data['timestamp']) > 3600) {
                return ['valid' => false, 'error' => 'Token expired'];
            }
            
            // Check session
            if ($data['session_id'] !== session_id()) {
                return ['valid' => false, 'error' => 'Invalid session'];
            }
            
            // Check user
            if ($data['user_id'] !== $this->account->getUserId()) {
                return ['valid' => false, 'error' => 'Invalid user'];
            }
            
            return ['valid' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['valid' => false, 'error' => 'Invalid token'];
        }
    }
    
    /**
     * Initialize upgrade session
     */
    public function initializeUpgradeSession($toProductId, $promoCode = null) {
        $upgradeSessionId = uniqid('upgrade_', true);
        
        $sessionData = [
            'upgrade_session_id' => $upgradeSessionId,
            'user_id' => $this->account->getUserId(),
            'from_product_id' => $this->getCurrentUserPlan($this->account->getUserId())['product_id'],
            'to_product_id' => $toProductId,
            'promo_code' => $promoCode,
            'status' => 'initialized',
            'created_at' => time()
        ];
        
        // Store in session
        $this->session->set('upgrade_session', $sessionData);
        
        // Log to database
        $this->logUpgradeActivity('session_initialized', $sessionData);
        
        return $upgradeSessionId;
    }
    
    /**
     * Apply promo code to upgrade
     */
    public function applyPromoCode($promoCode, $upgradeSessionId) {
        $upgradeSession = $this->session->get('upgrade_session');
        
        if (!$upgradeSession || $upgradeSession['upgrade_session_id'] !== $upgradeSessionId) {
            return ['success' => false, 'error' => 'Invalid upgrade session'];
        }
        
        // Validate promo code
        $sql = "SELECT * FROM bg_promo_codes 
                WHERE code = :code 
                AND status = 'active'
                AND (expires_at IS NULL OR expires_at > NOW())
                AND (max_uses IS NULL OR uses_count < max_uses)
                LIMIT 1";
        
        $promo = $this->database->getrow($sql, ['code' => $promoCode]);
        
        if (!$promo) {
            return ['success' => false, 'error' => 'Invalid or expired promo code'];
        }
        
        // Check if promo is valid for upgrades
        $validFor = json_decode($promo['valid_for_types'] ?? '[]', true);
        if (!empty($validFor) && !in_array('upgrade', $validFor)) {
            return ['success' => false, 'error' => 'Promo code not valid for upgrades'];
        }
        
        // Update session
        $upgradeSession['promo_code'] = $promoCode;
        $upgradeSession['promo_discount'] = $promo['discount_amount'];
        $upgradeSession['promo_type'] = $promo['discount_type'];
        $this->session->set('upgrade_session', $upgradeSession);
        
        // Log activity
        $this->logUpgradeActivity('promo_applied', ['promo_code' => $promoCode]);
        
        return [
            'success' => true,
            'discount_amount' => $promo['discount_amount'],
            'discount_type' => $promo['discount_type']
        ];
    }
    
    /**
     * Process the upgrade
     */
    public function processUpgrade($upgradeSessionId, $paymentIntentId = null) {
        $upgradeSession = $this->session->get('upgrade_session');
        
        if (!$upgradeSession || $upgradeSession['upgrade_session_id'] !== $upgradeSessionId) {
            return ['success' => false, 'error' => 'Invalid upgrade session'];
        }
        
        // Prevent double processing
        if ($upgradeSession['status'] === 'completed') {
            return ['success' => false, 'error' => 'Upgrade already processed'];
        }
        
        try {
            // Start transaction
            $this->database->beginTransaction();
            
            // Get new product details
            $newProduct = $this->productManager->getProduct($upgradeSession['to_product_id']);
            if (!$newProduct) {
                throw new Exception('Invalid product');
            }
            
            // Update user's plan
            $updateSql = "UPDATE bg_users 
                         SET account_plan = :new_plan,
                             account_type = :account_type,
                             updated_at = NOW()
                         WHERE user_id = :user_id";
            
            $this->database->query($updateSql, [
                'new_plan' => $newProduct['account_plan'],
                'account_type' => $newProduct['account_type'],
                'user_id' => $upgradeSession['user_id']
            ]);
            
            // Update enrollment allocations
            $this->updateEnrollmentAllocations($upgradeSession['user_id'], $newProduct);
            
            // Record the upgrade
            $this->recordUpgrade($upgradeSession, $newProduct, $paymentIntentId);
            
            // Update session status
            $upgradeSession['status'] = 'completed';
            $upgradeSession['completed_at'] = time();
            $this->session->set('upgrade_session', $upgradeSession);
            
            // Log success
            $this->logUpgradeActivity('upgrade_completed', [
                'new_plan' => $newProduct['account_plan']
            ]);
            
            // Commit transaction
            $this->database->commit();
            
            // Send confirmation email
            $this->sendUpgradeConfirmationEmail($upgradeSession['user_id'], $newProduct);
            
            return [
                'success' => true,
                'new_plan' => $newProduct['account_plan'],
                'confirmation_id' => $upgradeSession['upgrade_session_id']
            ];
            
        } catch (Exception $e) {
            $this->database->rollback();
            $this->logUpgradeActivity('upgrade_failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update enrollment allocations based on new plan
     */
    private function updateEnrollmentAllocations($userId, $newProduct) {
        // Get allocation rules for new product
        $features = $this->productManager->getProductFeatures($newProduct['id'], true);
        
        $maxEnrollments = null;
        $maxFamilyMembers = null;
        
        foreach ($features as $feature) {
            if ($feature['name'] === '_sys_max_enrollments') {
                $maxEnrollments = intval($feature['value']);
            } elseif ($feature['name'] === '_sys_max_family_members') {
                $maxFamilyMembers = intval($feature['value']);
            }
        }
        
        // Update user's allocation limits
        if ($maxEnrollments !== null) {
            $this->database->query(
                "UPDATE bg_users SET max_enrollments = :max WHERE user_id = :user_id",
                ['max' => $maxEnrollments, 'user_id' => $userId]
            );
        }
        
        if ($maxFamilyMembers !== null && $newProduct['account_type'] === 'parental') {
            $this->database->query(
                "UPDATE bg_users SET max_family_members = :max WHERE user_id = :user_id",
                ['max' => $maxFamilyMembers, 'user_id' => $userId]
            );
        }
    }
    
    /**
     * Record the upgrade in the database
     */
    private function recordUpgrade($upgradeSession, $newProduct, $paymentIntentId) {
        $sql = "INSERT INTO bg_plan_upgrades 
                (user_id, from_product_id, to_product_id, from_plan, to_plan, 
                 upgrade_session_id, payment_intent_id, promo_code, 
                 upgrade_cost, status, created_at)
                VALUES
                (:user_id, :from_product_id, :to_product_id, :from_plan, :to_plan,
                 :upgrade_session_id, :payment_intent_id, :promo_code,
                 :upgrade_cost, 'completed', NOW())";
        
        // Get from plan details
        $fromProduct = $this->productManager->getProduct($upgradeSession['from_product_id']);
        
        $params = [
            'user_id' => $upgradeSession['user_id'],
            'from_product_id' => $upgradeSession['from_product_id'],
            'to_product_id' => $upgradeSession['to_product_id'],
            'from_plan' => $fromProduct['account_plan'],
            'to_plan' => $newProduct['account_plan'],
            'upgrade_session_id' => $upgradeSession['upgrade_session_id'],
            'payment_intent_id' => $paymentIntentId,
            'promo_code' => $upgradeSession['promo_code'] ?? null,
            'upgrade_cost' => 0 // This should be calculated based on pricing
        ];
        
        $this->database->query($sql, $params);
    }
    
    /**
     * Log upgrade activity for tracking
     */
    private function logUpgradeActivity($action, $data = []) {
        $sql = "INSERT INTO bg_sessiontracking 
                (user_id, session_id, event_type, event_action, event_data, 
                 ip_address, user_agent, created_at)
                VALUES
                (:user_id, :session_id, 'upgrade', :action, :data,
                 :ip, :ua, NOW())";
        
        $params = [
            'user_id' => $this->account->getUserId() ?: 0,
            'session_id' => session_id(),
            'action' => $action,
            'data' => json_encode($data),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        try {
            $this->database->query($sql, $params);
        } catch (Exception $e) {
            // Don't fail the process if logging fails
            error_log("Failed to log upgrade activity: " . $e->getMessage());
        }
    }
    
    /**
     * Send upgrade confirmation email
     */
    private function sendUpgradeConfirmationEmail($userId, $newProduct) {
        // This would integrate with your email system
        // Placeholder for now
        return true;
    }
}