<?php
/**
 * Product Manager Class
 * Handles dynamic product and feature management for signup flows
 */

class ProductManager {
    private $database;
    private $qik;
    private $debugMode = false;

    public function __construct($database, $qik = null) {
        $this->database = $database;
        $this->qik = $qik;
        // Enable debug mode in development environments
        $this->debugMode = (isset($GLOBALS['mode']) && $GLOBALS['mode'] == 'dev');
    }



    
# ##--------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Log debug messages if debug mode is enabled
     */
    private function debugLog($message) {
        if ($this->debugMode) {
            error_log('[ProductManager] ' . $message);
        }
    }
    


    
# ##--------------------------------------------------------------------------------------------------------------------------------------------------

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
                ORDER BY p.display_order ASC, p.price ASC";
        
        $params = [
            'account_type' => $accountType,
            'version' => $version
        ];
        
        $products = $this->database->getrows($sql, $params);
        
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
    public function getAllProductsWithFeatures($version = 'v7') {
        // Get all active products
        $sql = "SELECT p.* 
                FROM bg_products p 
                WHERE p.version = :version 
                AND p.status = 'active' 
                AND p.display_grouping_status = 'active'
                ORDER BY p.account_type, p.price ASC";
        
        $params = ['version' => $version];
        
        $products = $this->database->getrows($sql, $params);
        
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
        
        $product = $this->database->getrow($sql, ['identifier' => $identifier]);
        
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
    public function getAvailableAccountTypes($version = 'v7', $includeInactive = false) {
        try {
            // Build the complete SQL query with proper concatenation
            $sql = "
                SELECT
                    p.account_type,
                    COUNT(DISTINCT p.id) AS plan_count,
                    COALESCE(at.display_name, CONCAT(UPPER(SUBSTRING(p.account_type,1,1)), SUBSTRING(p.account_type,2))) AS display_name,
                    COALESCE(at.short_label, p.account_type) AS short_label,
                    COALESCE(at.description, '') AS description,
                    COALESCE(at.icon, 'bi-tag') AS icon,
                    COALESCE(at.display_order, 999) AS display_order,
                    at.`status` AS type_status
                FROM bg_products p
                LEFT JOIN bg_account_types at 
                    ON at.account_type = p.account_type
                    AND at.version = :version1";
            
            // Add status condition for account_types if needed
            if (!$includeInactive) {
                $sql .= " AND at.`status` = 'active'";
            }
            
            // Add WHERE clause
            $sql .= " WHERE p.version = :version2";
            
            // Add product status conditions if needed
            if (!$includeInactive) {
                $sql .= " AND p.`status` = 'active' AND p.display_grouping_status = 'active'";
            }
            
            // Add GROUP BY
            $sql .= "
                GROUP BY 
                    p.account_type,
                    at.display_name,
                    at.short_label,
                    at.description,
                    at.icon,
                    at.display_order,
                    at.`status`";
            
            // Add ORDER BY
            $sql .= "
                ORDER BY 
                    COALESCE(at.display_order, 999),
                    p.account_type";
            
            $params = ['version1' => $version, 'version2' => $version];
           #breakpoint($sql);
            $accountTypes = $this->database->getrows($sql, $params);
            
            // Add display_grouping_status after the query if not including inactive
            if (!$includeInactive) {
                foreach ($accountTypes as &$type) {
                    $type['display_grouping_status'] = 'active';
                }
            }
            
        } catch (Exception $e) {
            // Database error - track and forward to error page
            $error_data = [
                'error_type' => 'database_error',
                'error_message' => $e->getMessage(),
                'method' => 'getAvailableAccountTypes',
                'class' => 'ProductManager',
                'version' => $version,
                'include_inactive' => $includeInactive
            ];

            // Track the error
            if (function_exists('session_tracking')) {
                session_tracking('productmanager_database_error', $error_data);
            }

            // Log for debugging
            $this->debugLog('Database error in getAvailableAccountTypes: ' . $e->getMessage());
            error_log('[ProductManager] Database error: ' . json_encode($error_data));

            // Redirect to error page
            $encoded = base64_encode(json_encode($error_data));
            header('Location: /error?e=' . urlencode($encoded));
            exit();
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
        try {
            // Query database for recommended plan configuration
            $sql = "SELECT recommended_plan FROM bg_account_types
                    WHERE account_type = :account_type
                    AND status = 'active'
                    AND version = 'v7'
                    LIMIT 1";

            $result = $this->database->fetchOne($sql, ['account_type' => $accountType]);

            if ($result && !empty($result['recommended_plan'])) {
                // Get the recommended product by plan name
                return $this->getProduct($result['recommended_plan'], 'plan_name');
            }

            // If no recommended plan found, try to find any gold/premium plan for this account type
            $sql = "SELECT * FROM bg_products
                    WHERE account_type = :account_type
                    AND version = 'v7'
                    AND status = 'active'
                    AND (account_plan LIKE '%gold%' OR account_plan LIKE '%premium%')
                    ORDER BY price DESC
                    LIMIT 1";

            $product = $this->database->getrow($sql, ['account_type' => $accountType]);

            if ($product) {
                $product['features'] = $this->getProductFeatures($product['id']);
                $product['encoded_id'] = $this->qik ? $this->qik->encodeId($product['id']) : $product['id'];
                return $product;
            }

            // No recommended plan found
            return false;

        } catch (Exception $e) {
            // Log error but don't crash - return false to indicate no recommendation available
            $this->debugLog('Error getting recommended plan for ' . $accountType . ': ' . $e->getMessage());

            if (function_exists('session_tracking')) {
                session_tracking('productmanager_warning', [
                    'warning_type' => 'recommended_plan_error',
                    'account_type' => $accountType,
                    'error' => $e->getMessage()
                ]);
            }

            return false;
        }
    }






    
# ##--------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Validate promo code for a product with advanced features support
     * @param string $promoCode Promo code
     * @param int $productId Product ID
     * @return array Validation result
     */
    public function validatePromoCode($promoCode, $productId) {
        $this->debugLog('Validating promo code: ' . $promoCode . ' for product: ' . $productId);

        // Backward compatibility: check old-style allow_promo field
        $product = $this->getProduct($productId, 'id');
        if ($product && isset($product['allow_promo']) && $product['allow_promo'] != 'yes') {
            return ['valid' => false, 'message' => 'Promo codes not allowed for this plan'];
        }

        // Check if product has specific promo allowlist via bg_product_features
        $sql = "SELECT value FROM bg_product_features
                WHERE product_id = :product_id
                AND name = 'allowed_promos'
                AND status = 'active'
                LIMIT 1";

        $feature = $this->database->getrow($sql, ['product_id' => $productId]);

        if ($feature) {
            $allowedPromos = $feature['value'];
            $this->debugLog('Product has allowed_promos: ' . $allowedPromos);

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
        } else {
            // No promo feature found - allow all promos for backward compatibility
            $this->debugLog('No allowed_promos feature found for product ' . $productId . ', allowing all promos');
        }

        // Check promo code validity in bg_promocodes (case-insensitive)
        $sql = "SELECT * FROM bg_promocodes
                WHERE UPPER(code) = UPPER(:code)
                AND status = 'active'
                AND (start_dt IS NULL OR start_dt <= NOW())
                AND (end_dt IS NULL OR end_dt >= NOW())
                AND (limit_count IS NULL OR tracking_count < limit_count)
                LIMIT 1";

        $promo = $this->database->getrow($sql, ['code' => strtoupper($promoCode)]);

        if ($promo) {
            $this->debugLog('Promo code found: ' . $promo['code']);
            return [
                'valid' => true,
                'discount_method' => $promo['discountmethod'],
                'amount' => $promo['amount'],
                'message' => $promo['successmessage'] ?? 'Promo code applied successfully',
                'promo_data' => $promo
            ];
        }

        $this->debugLog('Promo code not found or invalid');
        return ['valid' => false, 'message' => 'Invalid or expired promo code'];
    }
    








    /**
     * Calculate final price with promo code - supports multiple discount methods
     * @param int $productId Product ID
     * @param string $promoCode Promo code
     * @return array Price details
     */
    public function calculatePrice($productId, $promoCode = null) {
        $product = $this->getProduct($productId, 'id');
        if (!$product) {
            return ['error' => 'Product not found'];
        }

        $originalPrice = $product['price'] ?? 2900; // Default to $29 if price missing
        $finalPrice = $originalPrice;
        $discount = 0;
        $promoValidation = null;

        if ($promoCode) {
            $promoValidation = $this->validatePromoCode($promoCode, $productId);
            if ($promoValidation['valid']) {
                // Handle different discount methods
                $discountMethod = strtolower($promoValidation['discount_method'] ?? 'percentage');

                if ($discountMethod == 'percentage') {
                    $discount = ($originalPrice * $promoValidation['amount']) / 100;
                } elseif ($discountMethod == 'amount') {
                    // For amount discount, check if it's in dollars or cents
                    $promoAmount = $promoValidation['amount'];
                    if ($promoAmount < 100) {
                        // Likely in dollars, convert to cents
                        $discount = $promoAmount * 100;
                    } else {
                        // Already in cents
                        $discount = $promoAmount;
                    }
                } elseif ($discountMethod == 'count') {
                    // Count-based discount - assume it's a percentage (e.g., 80 = 80% off)
                    $discount = ($originalPrice * $promoValidation['amount']) / 100;
                } else {
                    // Default to percentage if method unclear
                    $discount = ($originalPrice * $promoValidation['amount']) / 100;
                }

                // Ensure minimum price of 50 cents
                $finalPrice = max(50, $originalPrice - $discount);

                $this->debugLog('Price calculation - Original: ' . $originalPrice . ', Discount: ' . $discount . ', Final: ' . $finalPrice);
            }
        }

        return [
            'original_price' => $originalPrice,
            'final_price' => $finalPrice,
            'discount' => $discount,
            'formatted_original' => '$' . number_format($originalPrice / 100, 2),
            'formatted_final' => '$' . number_format($finalPrice / 100, 2),
            'formatted_discount' => '$' . number_format($discount / 100, 2),
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
            } else {
                // No data found for this account type
                $error_data = [
                    'error_type' => 'missing_account_type_config',
                    'account_type' => $accountType,
                    'version' => 'v7',
                    'method' => 'getAccountTypeConfig',
                    'class' => 'ProductManager'
                ];

                // Track the error
                if (function_exists('session_tracking')) {
                    session_tracking('productmanager_error', $error_data);
                }

                // Log for debugging
                $this->debugLog('Account type config not found for: ' . $accountType);
                error_log('[ProductManager] Missing account type config: ' . json_encode($error_data));

                // Redirect to error page
                $encoded = base64_encode(json_encode($error_data));
                header('Location: /error?e=' . urlencode($encoded));
                exit();
            }
        } catch (Exception $e) {
            // Database error - track and forward to error page
            $error_data = [
                'error_type' => 'database_error',
                'error_message' => $e->getMessage(),
                'account_type' => $accountType,
                'method' => 'getAccountTypeConfig',
                'class' => 'ProductManager',
                'table' => 'bg_account_types'
            ];

            // Track the error
            if (function_exists('session_tracking')) {
                session_tracking('productmanager_database_error', $error_data);
            }

            // Log for debugging
            $this->debugLog('Database error in getAccountTypeConfig: ' . $e->getMessage());
            error_log('[ProductManager] Database error: ' . json_encode($error_data));

            // Redirect to error page
            $encoded = base64_encode(json_encode($error_data));
            header('Location: /error?e=' . urlencode($encoded));
            exit();
        }
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
        
        return $this->database->getrows($sql, ['product_id' => $productId]);
    }
}