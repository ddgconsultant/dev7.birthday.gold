<?php
/**
 * Standalone ProductManager class with promo code support
 * This doesn't extend the original to avoid private property issues
 */

class ProductManagerPromo {
    protected $database;
    protected $qik;
    protected $originalProductManager;
    
    public function __construct($database, $qik = null) {
        $this->database = $database;
        $this->qik = $qik;
        
        // Create instance of original ProductManager for methods we don't override
        if (class_exists('ProductManager')) {
            $this->originalProductManager = new ProductManager($database, $qik);
        }
    }
    
    /**
     * Delegate non-overridden methods to original ProductManager
     */
    public function __call($method, $args) {
        if ($this->originalProductManager && method_exists($this->originalProductManager, $method)) {
            return call_user_func_array([$this->originalProductManager, $method], $args);
        }
        throw new Exception("Method $method not found");
    }
    
    /**
     * Get product - delegates to original
     */
    public function getProduct($identifier, $by = 'id') {
        if ($this->originalProductManager) {
            return $this->originalProductManager->getProduct($identifier, $by);
        }
        
        // Fallback implementation
        $sql = "SELECT * FROM bg_products WHERE $by = :identifier AND status = 'active' LIMIT 1";
        return $this->database->getrow($sql, ['identifier' => $identifier]);
    }
    
    /**
     * Validate promo code with bg_product_features support
     */
    public function validatePromoCode($promoCode, $productId) {
        error_log('[ProductManagerPromo] Validating promo code: ' . $promoCode . ' for product: ' . $productId);
        
        // First check if product allows this promo code via bg_product_features
        $sql = "SELECT value FROM bg_product_features 
                WHERE product_id = :product_id 
                AND name = 'allowed_promos' 
                AND status = 'active'
                LIMIT 1";
        
        $feature = $this->database->getrow($sql, ['product_id' => $productId]);
        
        if ($feature) {
            $allowedPromos = $feature['value'];
            error_log('[ProductManagerPromo] Product has allowed_promos: ' . $allowedPromos);
            
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
            // No promo feature found - for now, allow all promos
            error_log('[ProductManagerPromo] No allowed_promos feature found for product ' . $productId . ', allowing all promos');
        }
        
        // Check promo code validity in bg_promocodes
        $sql = "SELECT * FROM bg_promocodes 
                WHERE UPPER(code) = UPPER(:code) 
                AND status = 'active'
                AND (start_dt IS NULL OR start_dt <= NOW())
                AND (end_dt IS NULL OR end_dt >= NOW())
                AND (limit_count IS NULL OR tracking_count < limit_count)
                LIMIT 1";
        
        $promo = $this->database->getrow($sql, ['code' => strtoupper($promoCode)]);
        
        if ($promo) {
            error_log('[ProductManagerPromo] Promo code found: ' . $promo['code']);
            return [
                'valid' => true,
                'discount_method' => $promo['discountmethod'],
                'amount' => $promo['amount'],
                'message' => $promo['successmessage'] ?? 'Promo code applied successfully',
                'promo_data' => $promo
            ];
        }
        
        error_log('[ProductManagerPromo] Promo code not found or invalid');
        return ['valid' => false, 'message' => 'Invalid or expired promo code'];
    }
    
    /**
     * Calculate price with promo code
     */
    public function calculatePrice($productId, $promoCode = null) {
        $product = $this->getProduct($productId, 'id');
        if (!$product) {
            return ['error' => 'Product not found'];
        }
        
        $originalPrice = $product['price'] ?? 2900; // Default to $29
        $finalPrice = $originalPrice;
        $discount = 0;
        $promoValidation = null;
        
        if ($promoCode) {
            $promoValidation = $this->validatePromoCode($promoCode, $productId);
            if ($promoValidation['valid']) {
                // Handle different discount methods
                if ($promoValidation['discount_method'] == 'percentage') {
                    $discount = ($originalPrice * $promoValidation['amount']) / 100;
                } elseif ($promoValidation['discount_method'] == 'amount') {
                    // For amount discount, check if it's in dollars or cents
                    $promoAmount = $promoValidation['amount'];
                    if ($promoAmount < 100) {
                        // Likely in dollars, convert to cents
                        $discount = $promoAmount * 100;
                    } else {
                        // Already in cents
                        $discount = $promoAmount;
                    }
                } elseif (strtolower($promoValidation['discount_method'] ?? '') == 'count') {
                    // Count-based discount - assume it's a percentage (e.g., 80 = 80% off)
                    $discount = ($originalPrice * $promoValidation['amount']) / 100;
                } else {
                    // Default to percentage if method unclear
                    $discount = ($originalPrice * $promoValidation['amount']) / 100;
                }
                
                $finalPrice = max(50, $originalPrice - $discount); // Minimum 50 cents
                
                error_log('[ProductManagerPromo] Price calculation - Original: ' . $originalPrice . ', Discount: ' . $discount . ', Final: ' . $finalPrice);
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
}
?>