<?php
/**
 * Product Manager Class
 * Handles dynamic product and feature management for signup flows
 */

class ProductManager {
    private $database;
    private $qik;
    
    public function __construct($database, $qik = null) {
        $this->database = $database;
        $this->qik = $qik;
    }
    
    /**
     * Get all active products with features for a specific account type
     * @param string $accountType Account type (user, parental, business, giftcertificate)
     * @param string $version Version (v2, v3, v7)
     * @return array Products with their features
     */
    public function getProductsWithFeatures($accountType = 'user', $version = 'v7') {
        // Get products
        $sql = "SELECT p.* 
                FROM bg_products p 
                WHERE p.account_type = :account_type 
                AND p.version = :version 
                AND p.status = 'active' 
                AND p.display_grouping_status = 'active'
                ORDER BY p.price ASC";
        
        $params = [
            'account_type' => $accountType,
            'version' => $version
        ];
        
        $products = $this->database->getrows($sql, $params);
        
        // Get features for each product
        foreach ($products as &$product) {
            $product['features'] = $this->getProductFeatures($product['id']);
            $product['encoded_id'] = $this->qik ? $this->qik->encodeId($product['id']) : $product['id'];
        }
        
        return $products;
    }
    
    
    /**
     * Get single product by ID or plan name
     * @param mixed $identifier Product ID or plan name
     * @param string $identifierType 'id' or 'plan_name'
     * @return array|false Product data or false if not found
     */
    public function getProduct($identifier, $identifierType = 'id') {
        if ($identifierType == 'id') {
            $sql = "SELECT * FROM bg_products WHERE id = :identifier AND status = 'active'";
        } else {
            $sql = "SELECT * FROM bg_products WHERE account_plan = :identifier AND status = 'active'";
        }
        
        $product = $this->database->getrow($sql, ['identifier' => $identifier]);
        
        if ($product) {
            $product['features'] = $this->getProductFeatures($product['id']);
            $product['encoded_id'] = $this->qik ? $this->qik->encodeId($product['id']) : $product['id'];
        }
        
        return $product;
    }
    
    /**
     * Get available account types with plan counts
     * @param string $version Version to check (v2, v3, v7)
     * @return array Account types with display info
     */
    public function getAvailableAccountTypes($version = 'v7') {
        // First get account types from products
        $sql = "SELECT p.account_type, COUNT(*) as plan_count
                FROM bg_products p
                WHERE p.status = 'active' 
                AND p.display_grouping_status = 'active'
                AND p.version = :version
                GROUP BY p.account_type";
        
        $accountTypes = $this->database->getrows($sql, ['version' => $version]);
        
        // Try to join with account types table for display info
        try {
            $sql = "SELECT 
                    p.account_type, 
                    COUNT(*) as plan_count,
                    COALESCE(at.display_name, CONCAT(UPPER(SUBSTRING(p.account_type,1,1)), SUBSTRING(p.account_type,2))) as display_name,
                    COALESCE(at.short_label, p.account_type) as short_label,
                    COALESCE(at.description, '') as description,
                    COALESCE(at.icon, 'bi-tag') as icon,
                    COALESCE(at.display_order, 999) as display_order
                FROM bg_products p
                LEFT JOIN bg_account_types at ON p.account_type = at.account_type AND at.version = :version AND at.status = 'active'
                WHERE p.status = 'active' 
                AND p.display_grouping_status = 'active'
                AND p.version = :version
                GROUP BY p.account_type
                ORDER BY display_order, p.account_type";
            
            $accountTypes = $this->database->getrows($sql, ['version' => $version]);
        } catch (Exception $e) {
            // If join fails (table doesn't exist), add display info manually
            foreach ($accountTypes as &$type) {
                $config = $this->getAccountTypeConfig($type['account_type']);
                $type['display_name'] = $config['label'];
                $type['short_label'] = $config['short_label'];
                $type['description'] = $config['description'];
                $type['icon'] = $config['icon'];
            }
        }
        
        return $accountTypes;
    }
    
    /**
     * Get recommended plan for an account type
     * @param string $accountType Account type
     * @return array|false Recommended product or false
     */
    public function getRecommendedPlan($accountType = 'user') {
        // Logic to determine recommended plan
        // For now, let's recommend 'gold' plans
        $recommendedPlans = [
            'user' => 'gold',
            'parental' => 'family_gold',
            'business' => 'business_pro',
            'giftcertificate' => 'gift_gold'
        ];
        
        $planName = $recommendedPlans[$accountType] ?? 'gold';
        return $this->getProduct($planName, 'plan_name');
    }
    
    /**
     * Validate promo code for a product
     * @param string $promoCode Promo code
     * @param int $productId Product ID
     * @return array Validation result
     */
    public function validatePromoCode($promoCode, $productId) {
        // First check if product allows promo codes
        $product = $this->getProduct($productId, 'id');
        if (!$product || $product['allow_promo'] != 'yes') {
            return ['valid' => false, 'message' => 'Promo codes not allowed for this plan'];
        }
        
        // Check promo code validity
        $sql = "SELECT * FROM bg_promocodes 
                WHERE code = :code 
                AND (start_dt IS NULL OR start_dt <= NOW())
                AND (end_dt IS NULL OR end_dt >= NOW())
                AND (limit_count IS NULL OR tracking_count < limit_count)
                LIMIT 1";
        
        $promo = $this->database->getrow($sql, ['code' => $promoCode]);
        
        if ($promo) {
            return [
                'valid' => true,
                'discount_method' => $promo['discountmethod'],
                'amount' => $promo['amount'],
                'message' => 'Promo code applied successfully'
            ];
        }
        
        return ['valid' => false, 'message' => 'Invalid or expired promo code'];
    }
    
    /**
     * Calculate final price with promo code
     * @param int $productId Product ID
     * @param string $promoCode Promo code
     * @return array Price details
     */
    public function calculatePrice($productId, $promoCode = null) {
        $product = $this->getProduct($productId, 'id');
        if (!$product) {
            return ['error' => 'Product not found'];
        }
        
        $originalPrice = $product['price'];
        $finalPrice = $originalPrice;
        $discount = 0;
        
        if ($promoCode) {
            $promoValidation = $this->validatePromoCode($promoCode, $productId);
            if ($promoValidation['valid']) {
                if ($promoValidation['discount_method'] == 'percentage') {
                    $discount = ($originalPrice * $promoValidation['amount']) / 100;
                } else {
                    $discount = $promoValidation['amount'];
                }
                $finalPrice = max(0, $originalPrice - $discount);
            }
        }
        
        return [
            'original_price' => $originalPrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'formatted_original' => '$' . number_format($originalPrice / 100, 2),
            'formatted_discount' => '$' . number_format($discount / 100, 2),
            'formatted_final' => '$' . number_format($finalPrice / 100, 2)
        ];
    }
    
    /**
     * Get account type details
     * @param string $accountType Account type
     * @return array Account type configuration
     */
    public function getAccountTypeConfig($accountType) {
        // First try to get from database
        try {
            $sql = "SELECT * FROM bg_account_types 
                    WHERE account_type = :account_type 
                    AND status = 'active' 
                    AND version = 'v7'
                    LIMIT 1";
            
            $result = $this->database->fetchOne($sql, ['account_type' => $accountType]);
            
            if ($result) {
                // Map database fields to expected array keys
                return [
                    'icon' => $result['icon'] ?? 'bi-tag',
                    'label' => $result['display_name'] ?? ucfirst($accountType),
                    'short_label' => $result['short_label'] ?? ucfirst($accountType),
                    'description' => $result['description'] ?? 'Birthday rewards account',
                    'context_text' => $result['description'] ?? 'Choose the plan that best fits your needs'
                ];
            }
        } catch (Exception $e) {
            // If table doesn't exist or query fails, fall back to hardcoded
        }
        
        // Fallback to hardcoded values if database lookup fails
        $configs = [
            'user' => [
                'icon' => 'bi-person',
                'label' => 'Individual',
                'short_label' => 'Just me',
                'description' => 'Perfect for individuals who want to celebrate their birthday with exclusive rewards',
                'context_text' => 'Choose the plan that works best for your personal birthday rewards'
            ],
            'parental' => [
                'icon' => 'bi-people',
                'label' => 'Family',
                'short_label' => 'My family',
                'description' => 'Manage birthday rewards for your entire family in one account',
                'context_text' => 'Select a family plan to manage birthday rewards for multiple family members'
            ],
            'business' => [
                'icon' => 'bi-building',
                'label' => 'Business',
                'short_label' => 'Business',
                'description' => 'Employee birthday management and rewards for your organization',
                'context_text' => 'Choose a business plan to manage employee birthdays and boost morale'
            ],
            'giftcertificate' => [
                'icon' => 'bi-gift',
                'label' => 'Gift Certificate',
                'short_label' => 'As a gift',
                'description' => 'Give the gift of birthday rewards to someone special',
                'context_text' => 'Select a gift certificate to surprise someone with a year of birthday rewards'
            ]
        ];
        
        return $configs[$accountType] ?? [
            'icon' => 'bi-tag',
            'label' => ucfirst($accountType),
            'short_label' => ucfirst($accountType),
            'description' => 'Birthday rewards account',
            'context_text' => 'Choose the plan that best fits your needs'
        ];
    }
    
    /**
     * Get product features including system features
     * @param int $productId Product ID
     * @param bool $includeSystem Include system features (_sys_*)
     * @return array Features array
     */
    public function getProductFeatures($productId, $includeSystem = false) {
        $sql = "SELECT * FROM bg_product_features 
                WHERE product_id = :product_id ";
        
        if (!$includeSystem) {
            $sql .= "AND name NOT LIKE '_sys_%' ";
        }
        
        // Add status check if not looking for system features
        if (!$includeSystem) {
            $sql .= "AND status = 'active' ";
        }
        
        $sql .= "ORDER BY id ASC";
        
        return $this->database->getrows($sql, ['product_id' => $productId]);
    }
}