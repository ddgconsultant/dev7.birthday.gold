<?php
/**
 * Dedicated AJAX handler for promo code validation
 * This avoids potential 403 errors from security rules on the main createaccount.php
 */

// Start output buffering to prevent any accidental output
ob_start();

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Clear any output that might have been generated
ob_clean();

// Set JSON headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Methods: GET, POST');
header('X-Content-Type-Options: nosniff');

// Get session data
$signup_process = $session->get('signup_process_data', []);

if (empty($signup_process) || empty($signup_process['account_plan_id'])) {
    echo json_encode(['valid' => false, 'message' => 'Session expired. Please start over.']);
    exit;
}

// Load ProductManager
if (!class_exists('ProductManager')) {
    include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
}
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager_promo.php');

$productManager = new ProductManagerPromo($database, $qik);

// Get parameters
$promoCode = $_REQUEST['promo_code'] ?? '';
$productId = $signup_process['account_plan_id'] ?? null;

// Log for debugging
error_log('[AJAX_PROMO] Validating promo: ' . $promoCode . ' for product: ' . $productId);

try {
    if ($productId && $promoCode) {
        $validation = $productManager->validatePromoCode($promoCode, $productId);
        
        if ($validation['valid']) {
            // Calculate new price
            $pricing = $productManager->calculatePrice($productId, $promoCode);
            $validation['new_price'] = $pricing['formatted_final'] ?? '';
            $validation['discount_amount'] = $pricing['formatted_discount'] ?? '';
            
            // Store in session
            $signup_process['promo_code'] = $promoCode;
            $signup_process['promo_validation'] = $validation;
            $signup_process['final_price'] = $pricing['final_price'] ?? 0;
            $session->set('signup_process_data', $signup_process);
            
            error_log('[AJAX_PROMO] Promo valid! New price: ' . $validation['new_price']);
        } else {
            error_log('[AJAX_PROMO] Promo invalid: ' . $validation['message']);
        }
        
        echo json_encode($validation);
    } else {
        echo json_encode(['valid' => false, 'message' => 'Please enter a promo code']);
    }
} catch (Exception $e) {
    error_log('[AJAX_PROMO] Exception: ' . $e->getMessage());
    echo json_encode(['valid' => false, 'message' => 'Error processing promo code']);
}
?>