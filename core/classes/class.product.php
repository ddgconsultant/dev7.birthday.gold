<?php
/**
 * Consolidated Product Class
 * Combines ProductManager and ProductManagerPromo functionality
 * Handles products, features, pricing, and promo code validation
 * 
 * GRANDFATHERED PLANS SUPPORT:
 * - Users can have different plan versions than the site default
 * - Plan versions are stored in bg_user_attributes table
 * - Methods accept optional userId parameter to use user-specific versions
 * 
 * Usage Examples:
 * - Get products for current site version: $product->getProductsWithFeatures('user')
 * - Get products for specific user: $product->getProductsWithFeatures('user', null, $userId)
 * - Get user plan version: $product->getUserPlanVersion($userId)
 * - Set grandfathered plan: $product->setUserPlanVersion($userId, 'v3', 'grandfathered_plan')
 */


 
# ##==================================================================================================================================================
# ##==================================================================================================================================================
# ##==================================================================================================================================================
class Product {
    private $database;
    private $qik;
    private $defaultVersion;
    private $userPlanVersionCache = [];
    

     
# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct($database, $qik = null, $defaultVersion = null) {
        $this->database = $database;
        $this->qik = $qik;
        // Use provided version or get from global website config
        $this->defaultVersion = $defaultVersion ?: ($GLOBALS['website']['plan_version'] ?? 'v7');
    }

    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get all active products with features for a specific account type
     * @param string $accountType Account type (user, parental, business, giftcertificate)
     * @param string $version Version (v2, v3, v7)
     * @return array Products with their features
     */

    public function getProductsWithFeatures($accountType = 'user', $version = null, $userId = null) {
        // Use provided version, user-specific version, or default
        if ($version === null) {
            if ($userId !== null) {
                $version = $this->getUserPlanVersion($userId);
            } else {
                $version = $this->defaultVersion;
            }
        }
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
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get features for each product
        foreach ($products as &$product) {
            $product['features'] = $this->getProductFeatures($product['id'], false, 'user');
            $product['encoded_id'] = $this->qik ? $this->qik->encodeId($product['id']) : $product['id'];
        }
        
        return $products;
    }
    


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get all active products with features regardless of account type
     * @param string $version Version (v2, v3, v7)
     * @return array All products with their features
     */

    public function getAllProductsWithFeatures($version = null) {
        // Use provided version or default
        if ($version === null) {
            $version = $this->defaultVersion;
        }
        // Get all active products
        $sql = "SELECT p.* 
                FROM bg_products p 
                WHERE p.version = :version 
                AND p.status = 'active' 
                AND p.display_grouping_status = 'active'
                ORDER BY p.account_type, p.price ASC";
        
        $params = ['version' => $version];
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get features for each product
        foreach ($products as &$product) {
            $product['features'] = $this->getProductFeatures($product['id'], false, 'user');
            $product['encoded_id'] = $this->qik ? $this->qik->encodeId($product['id']) : $product['id'];
        }
        
        return $products;
    }

    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
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
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['identifier' => $identifier]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $product['features'] = $this->getProductFeatures($product['id']);
            $product['encoded_id'] = $this->qik ? $this->qik->encodeId($product['id']) : $product['id'];
        }
        
        return $product;
    }
    


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get available account types with plan counts
     * @param string $version Version to check (v2, v3, v7)
     * @param bool $includeInactive Include inactive types (for admin)
     * @return array Account types with display info
     */
     
    public function getAvailableAccountTypes($version = null, $includeInactive = false) {
        // Use provided version or default
        if ($version === null) {
            $version = $this->defaultVersion;
        }
        // Build WHERE clause based on whether we include inactive
        $whereClause = "WHERE p.version = :version";
        if (!$includeInactive) {
            $whereClause .= " AND p.status = 'active' AND p.display_grouping_status = 'active'";
        }
        
        // First get account types from products
        $sql = "SELECT p.account_type, COUNT(*) as plan_count
                FROM bg_products p
                $whereClause
                GROUP BY p.account_type";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['version' => $version]);
        $accountTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Try to join with account types table for display info
        try {
            $atStatusClause = $includeInactive ? "" : " AND at.status = 'active'";
            
            $sql = "SELECT 
                    p.account_type, 
                    COUNT(*) as plan_count,
                    COALESCE(at.display_name, CONCAT(UPPER(SUBSTRING(p.account_type,1,1)), SUBSTRING(p.account_type,2))) as display_name,
                    COALESCE(at.short_label, p.account_type) as short_label,
                    COALESCE(at.description, '') as description,
                    COALESCE(at.icon, 'bi-tag') as icon,
                    COALESCE(at.display_order, 999) as display_order,
                    p.display_grouping_status,
                    at.status as type_status
                FROM bg_products p
                LEFT JOIN bg_account_types at ON p.account_type = at.account_type AND at.version = :version $atStatusClause
                $whereClause
                GROUP BY p.account_type
                ORDER BY display_order, p.account_type";
            
            $stmt = $this->database->prepare($sql);
            $stmt->execute(['version' => $version]);
            $accountTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get recommended plan for an account type
     * @param string $accountType Account type
     * @return array|false Recommended product or false
     */

     
    public function getRecommendedPlan($accountType = 'user') {
        // Logic to determine recommended plan
        // For now, let us recommend 'gold' plans
        $recommendedPlans = [
            'user' => 'gold',
            'parental' => 'family_gold',
            'business' => 'business_pro',
            'giftcertificate' => 'gift_gold'
        ];
        
        $planName = $recommendedPlans[$accountType] ?? 'gold';
        return $this->getProduct($planName, 'plan_name');
    }
    


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Validate promo code with enhanced bg_product_features support
     * @param string $promoCode Promo code
     * @param int $productId Product ID
     * @return array Validation result
     */

         public function validatePromoCode($promoCode, $productId, $userId = null) {
        // First check if product allows promo codes
        $product = $this->getProduct($productId, 'id');
        if (!$product || $product['allow_promo'] != 'yes') {
            return ['valid' => false, 'message' => 'Promo codes not allowed for this plan'];
        }
        
        // Check if product has specific allowed promos via bg_product_features
        $sql = "SELECT value FROM bg_product_features 
                WHERE product_id = :product_id 
                AND name = 'allowed_promos' 
                AND status = 'active'
                LIMIT 1";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        $feature = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($feature) {
            $allowedPromos = $feature['value'];
            
            // Check if promo is allowed
            if ($allowedPromos !== 'all') {
                // Check if it's JSON array
                if (strpos($allowedPromos, '[') === 0) {
                    $allowedList = json_decode($allowedPromos, true);
                } else {
                    // Assume comma-separated list
                    $allowedList = array_map('trim', explode(',', $allowedPromos));
                }
                
                // Case-insensitive check
                $allowedList = array_map('strtoupper', $allowedList);
                if (!in_array(strtoupper($promoCode), $allowedList)) {
                    return ['valid' => false, 'message' => 'Promo code not valid for this product'];
                }
            }
        }
        
        // Check promo code validity in bg_promocodes
        $sql = "SELECT * FROM bg_promocodes 
                WHERE UPPER(code) = UPPER(:code) 
                AND status = 'active'
                AND (start_dt IS NULL OR start_dt <= NOW())
                AND (end_dt IS NULL OR end_dt >= NOW())
                AND (limit_count = 0 OR limit_count IS NULL OR tracking_count < limit_count)
                LIMIT 1";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['code' => strtoupper($promoCode)]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($promo) {
            // Calculate discount details
            $originalPrice = $product['price'];
            $discount = 0;
            $finalPrice = $originalPrice;
            
            if ($promo['discountmethod'] == 'percentage' || $promo['discountmethod'] == 'count') {
                $discount = ($originalPrice * $promo['amount']) / 100;
            } elseif ($promo['discountmethod'] == 'amount') {
                // Handle amount discount - check if in dollars or cents
                $promoAmount = $promo['amount'];
                if ($promoAmount < 100) {
                    // Likely in dollars, convert to cents
                    $discount = $promoAmount * 100;
                } else {
                    // Already in cents
                    $discount = $promoAmount;
                }
            }
            
            $finalPrice = max(50, $originalPrice - $discount); // Minimum 50 cents
            
            return [
                'valid' => true,
                'discount_method' => $promo['discountmethod'],
                'amount' => $promo['amount'],
                'message' => $promo['successmessage'] ?? 'Promo code applied successfully',
                'original_price' => $originalPrice,
                'new_price' => $finalPrice,
                'discount_amount' => $discount,
                'promo_data' => $promo
            ];
        }
        
        return ['valid' => false, 'message' => 'Invalid or expired promo code'];
    }
    


# ##--------------------------------------------------------------------------------------------------------------------------------------------------
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
        $promoValidation = null;
        
        if ($promoCode) {
            $promoValidation = $this->validatePromoCode($promoCode, $productId);
            if ($promoValidation['valid']) {
                $discount = $promoValidation['discount_amount'] ?? 0;
                $finalPrice = $promoValidation['new_price'] ?? $originalPrice;
            }
        }
        
        return [
            'original_price' => $originalPrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'formatted_original' => '$' . number_format($originalPrice / 100, 2),
            'formatted_discount' => '$' . number_format($discount / 100, 2),
            'formatted_final' => '$' . number_format($finalPrice / 100, 2),
            'promo_validation' => $promoValidation
        ];
    }

    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
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
                    AND version = :version
                    LIMIT 1";
            
            $stmt = $this->database->prepare($sql);
            $stmt->execute(['account_type' => $accountType, 'version' => $this->defaultVersion]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
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

    
# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get product features including system features
     * @param int $productId Product ID
     * @param bool $includeSystem Include system features (_sys_*)
     * @param string $context Context for display ('user', 'admin', 'all')
     * @return array Features array
     */

     
    public function getProductFeatures($productId, $includeSystem = false, $context = 'user') {
        $sql = "SELECT * FROM bg_product_features 
                WHERE product_id = :product_id ";
        
        if (!$includeSystem) {
            $sql .= "AND name NOT LIKE '_sys_%' ";
        }
        
        // Add status check if not looking for system features
        if (!$includeSystem) {
            $sql .= "AND status = 'active' ";
        }
        
        // Filter by display_mode based on context
        if ($context === 'user') {
            // For user context, only show features marked as 'show'
            $sql .= "AND (display_mode = 'show' OR display_mode IS NULL) ";
        } elseif ($context === 'admin') {
            // For admin context, show 'show' and 'admin_only', but not 'hide'
            $sql .= "AND (display_mode IN ('show', 'admin_only') OR display_mode IS NULL) ";
        }
        // For 'all' context, no additional filtering
        
        $sql .= "ORDER BY id ASC";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Check if a user is on a grandfathered plan
     * @param int $userId User ID
     * @return bool True if user is grandfathered
     */
     
    public function isUserGrandfathered($userId) {
        // Check for explicit grandfathered status
        $sql = "SELECT id FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = 'grandfathered_plan' 
                AND status = 'active'
                LIMIT 1";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return true;
        }
        
        // Check if user version differs from current site version
        $userVersion = $this->getUserPlanVersion($userId);
        return $userVersion !== $this->defaultVersion;
    }
    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get the plan version for a specific user
     * Checks for grandfathered plans or user-specific versions
     * @param int $userId User ID
     * @return string Plan version for the user
     */
     
    public function getUserPlanVersion($userId) {
        // Check cache first
        if (isset($this->userPlanVersionCache[$userId])) {
            return $this->userPlanVersionCache[$userId];
        }
        
        // First check if user has a specific plan version stored in attributes
        $sql = "SELECT string_value FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = 'plan_version' 
                AND status = 'active'
                LIMIT 1";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['string_value'])) {
            $this->userPlanVersionCache[$userId] = $result['string_value'];
            return $result['string_value'];
        }
        
        // Check if user has a grandfathered status
        $sql = "SELECT string_value FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = 'grandfathered_plan' 
                AND status = 'active'
                LIMIT 1";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['string_value'])) {
            $this->userPlanVersionCache[$userId] = $result['string_value'];
            return $result['string_value'];
        }
        
        // If no specific version, try to determine based on signup date
        $version = $this->inferVersionFromSignupDate($userId);
        
        if ($version) {
            $this->userPlanVersionCache[$userId] = $version;
            return $version;
        }
        
        // Default to current site version
        $this->userPlanVersionCache[$userId] = $this->defaultVersion;
        return $this->defaultVersion;
    }
    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Infer plan version based on user signup date
     * @param int $userId User ID
     * @return string|false Plan version or false if cannot determine
     */
     
    private function inferVersionFromSignupDate($userId) {
        // Get user signup date
        $sql = "SELECT create_dt FROM bg_users WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || empty($user['create_dt'])) {
            return false;
        }
        
        $signupDate = strtotime($user['create_dt']);
        
        // Define version launch dates (these are example dates - adjust as needed)
        $versionDates = [
            'v2' => strtotime('2020-01-01'),
            'v3' => strtotime('2022-01-01'),
            'v7' => strtotime('2024-01-01')
        ];
        
        // Find which version was active when user signed up
        $userVersion = 'v2'; // Oldest version as default
        foreach ($versionDates as $version => $launchDate) {
            if ($signupDate >= $launchDate) {
                $userVersion = $version;
            }
        }
        
        return $userVersion;
    }
    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Set or update user plan version (for grandfathering)
     * @param int $userId User ID
     * @param string $version Plan version
     * @param string $type Type of version storage ('plan_version' or 'grandfathered_plan')
     * @return bool Success status
     */
     
    public function setUserPlanVersion($userId, $version, $type = 'plan_version') {
        // First check if attribute already exists
        $sql = "SELECT id FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = :type 
                LIMIT 1";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute(['user_id' => $userId, 'type' => $type]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing
            $sql = "UPDATE bg_user_attributes 
                    SET string_value = :version, 
                        status = 'active',
                        modify_dt = NOW() 
                    WHERE id = :id";
            
            $stmt = $this->database->prepare($sql);
            $success = $stmt->execute(['version' => $version, 'id' => $existing['id']]);
        } else {
            // Insert new
            $sql = "INSERT INTO bg_user_attributes 
                    (user_id, type, name, string_value, status, create_dt) 
                    VALUES 
                    (:user_id, :type, :name, :version, 'active', NOW())";
            
            $stmt = $this->database->prepare($sql);
            $success = $stmt->execute([
                'user_id' => $userId,
                'type' => $type,
                'name' => 'User plan version',
                'version' => $version
            ]);
        }
        
        // Clear cache
        if ($success) {
            unset($this->userPlanVersionCache[$userId]);
        }
        
        return $success;
    }
    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get product for a specific user considering their plan version
     * @param int $userId User ID
     * @param mixed $identifier Product ID or plan name
     * @param string $identifierType 'id' or 'plan_name'
     * @return array|false Product data or false if not found
     */
     
    public function getUserProduct($userId, $identifier, $identifierType = 'id') {
        // Get user-specific version
        $userVersion = $this->getUserPlanVersion($userId);
        
        if ($identifierType == 'id') {
            $sql = "SELECT * FROM bg_products WHERE id = :identifier AND status = 'active'";
        } else {
            // For plan name, we need to consider the user version
            $sql = "SELECT * FROM bg_products 
                    WHERE account_plan = :identifier 
                    AND version = :version 
                    AND status = 'active'
                    LIMIT 1";
        }
        
        $params = ['identifier' => $identifier];
        if ($identifierType == 'plan_name') {
            $params['version'] = $userVersion;
        }
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $product['features'] = $this->getProductFeatures($product['id']);
            $product['encoded_id'] = $this->qik ? $this->qik->encodeId($product['id']) : $product['id'];
            $product['user_version'] = $userVersion;
        }
        
        return $product;
    }
    

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get products available to a user based on their plan version
     * @param int $userId User ID
     * @param string $accountType Account type filter (optional)
     * @return array Products available to the user
     */
     
    public function getUserAvailableProducts($userId, $accountType = null) {
        // Get user-specific version
        $userVersion = $this->getUserPlanVersion($userId);
        
        $sql = "SELECT p.* 
                FROM bg_products p 
                WHERE p.version = :version 
                AND p.status = 'active' 
                AND p.display_grouping_status = 'active'";
        
        $params = ['version' => $userVersion];
        
        if ($accountType) {
            $sql .= " AND p.account_type = :account_type";
            $params['account_type'] = $accountType;
        }
        
        $sql .= " ORDER BY p.account_type, p.price ASC";
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get features for each product
        foreach ($products as &$product) {
            $product['features'] = $this->getProductFeatures($product['id'], false, 'user');
            $product['encoded_id'] = $this->qik ? $this->qik->encodeId($product['id']) : $product['id'];
            $product['user_version'] = $userVersion;
        }
        
        return $products;
    }
}
?>