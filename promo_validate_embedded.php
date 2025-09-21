<?php
/**
 * Embedded promo validation - returns JavaScript function
 * This approach avoids AJAX entirely
 */
$addClasses[] = 'productmanager';
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Set JavaScript content type
header('Content-Type: application/javascript');

// Get session data
$signup_process = $session->get('signup_process_data', []);
$productId = $signup_process['account_plan_id'] ?? 0;


// ProductManager is auto-instantiated as $productmanager by site-controller

// Pre-validate common promo codes
$commonPromos = ['80off', 'TEST50', 'BETA', 'SAVE20', 'GOLD50'];
$promoData = [];

foreach ($commonPromos as $code) {
    if ($productId) {
        $validation = $productmanager->validatePromoCode($code, $productId);
        if ($validation['valid']) {
            $pricing = $productmanager->calculatePrice($productId, $code);
            $promoData[strtoupper($code)] = [
                'valid' => true,
                'message' => $validation['message'] ?? 'Promo code applied!',
                'new_price' => $pricing['formatted_final'] ?? '',
                'discount_amount' => $pricing['formatted_discount'] ?? ''
            ];
        }
    }
}

// Output JavaScript
?>
// Embedded promo validation data
window.promoValidation = {
    productId: <?php echo json_encode($productId); ?>,
    promos: <?php echo json_encode($promoData); ?>,
    
    validate: function(promoCode) {
        const code = promoCode.toUpperCase();
        const data = this.promos[code];
        
        if (data) {
            return data;
        } else {
            return {
                valid: false,
                message: 'Invalid promo code'
            };
        }
    },
    
    saveToSession: function(promoCode, validation) {
        // Store in local storage as backup
        localStorage.setItem('promo_code', promoCode);
        localStorage.setItem('promo_validation', JSON.stringify(validation));
    }
};
