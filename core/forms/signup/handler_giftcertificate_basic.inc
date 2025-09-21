<?php
/**
 * Gift Certificate Basic Handler
 * 
 * Processes gift certificate data after successful payment and stores in bg_user_attributes
 */

// This handler is called after payment is successful
// It creates the gift certificate records in bg_user_attributes

if (!isset($signup_process) || empty($signup_process)) {
    return;
}

// Get buyer's user_id (should be set after account creation)
$buyer_user_id = $session->get('user_id');
if (!$buyer_user_id) {
    error_log('Gift certificate handler: No user_id found in session');
    return;
}

// Get gift certificate data from session
$recipient_data = [
    'firstname' => $session->get('gift_recipient_firstname', ''),
    'lastname' => $session->get('gift_recipient_lastname', ''),
    'birthdate' => $session->get('gift_recipient_birthdate', ''),
    'email' => $session->get('gift_recipient_email', ''),
    'phone' => $session->get('gift_recipient_phone', '')
];

$delivery_data = [
    'method' => $session->get('gift_delivery_method', ''),
    'date' => $session->get('gift_delivery_date', ''),
    'message' => $session->get('gift_message', '')
];

// Create gift certificate record using user attributes
try {
    // Generate unique gift certificate code
    $gift_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
    $gift_id = time() . '_' . rand(1000, 9999);
    
    // Prepare attributes to insert
    // Using bg_user_attributes table structure:
    // - type: for categorization
    // - name: specific attribute name
    // - string_value: for text values
    // - value: for numeric values
    
    $attributes_to_insert = [
        // Main gift certificate entry
        [
            'type' => 'gift_certificate',
            'name' => 'gift_cert_' . $gift_id,
            'string_value' => $gift_code,
            'description' => json_encode([
                'code' => $gift_code,
                'status' => 'active',
                'recipient' => $recipient_data,
                'delivery' => $delivery_data,
                'plan_id' => $signup_process['account_plan_id'] ?? '',
                'plan_name' => $signup_process['plandata']['account_name'] ?? '',
                'amount' => $signup_process['account_cost'] ?? 0,
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
            ]),
            'category' => 'gift_purchased',
            'grouping' => 'gift_certificates'
        ],
        
        // Track this gift in user's purchase list
        [
            'type' => 'gift_certificate_list',
            'name' => 'purchased_gifts',
            'string_value' => $gift_id,
            'category' => 'gift_purchased',
            'grouping' => 'gift_certificates'
        ]
    ];
    
    // Insert attributes
    foreach ($attributes_to_insert as $attr) {
        // Check if we need to append to existing list
        if ($attr['name'] === 'purchased_gifts') {
            $sql = "SELECT string_value FROM bg_user_attributes 
                    WHERE user_id = :user_id 
                    AND type = :type 
                    AND name = :name 
                    AND status = 'active'";
            $stmt = $database->prepare($sql);
            $stmt->execute([
                'user_id' => $buyer_user_id,
                'type' => $attr['type'],
                'name' => $attr['name']
            ]);
            $existing = $stmt->fetchColumn();
            
            if ($existing) {
                // Update existing record
                $attr['string_value'] = $existing . ',' . $attr['string_value'];
                $sql = "UPDATE bg_user_attributes 
                        SET string_value = :string_value,
                            modify_dt = NOW() 
                        WHERE user_id = :user_id 
                        AND type = :type 
                        AND name = :name 
                        AND status = 'active'";
                $stmt = $database->prepare($sql);
                $stmt->execute([
                    'user_id' => $buyer_user_id,
                    'type' => $attr['type'],
                    'name' => $attr['name'],
                    'string_value' => $attr['string_value']
                ]);
                continue;
            }
        }
        
        // Insert new attribute
        $sql = "INSERT INTO bg_user_attributes 
                (user_id, type, name, description, string_value, value, category, grouping, status, create_dt) 
                VALUES 
                (:user_id, :type, :name, :description, :string_value, :value, :category, :grouping, 'active', NOW())";
        
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'user_id' => $buyer_user_id,
            'type' => $attr['type'],
            'name' => $attr['name'],
            'description' => $attr['description'] ?? null,
            'string_value' => $attr['string_value'] ?? null,
            'value' => $attr['value'] ?? null,
            'category' => $attr['category'] ?? null,
            'grouping' => $attr['grouping'] ?? null
        ]);
    }
    
    // Store gift certificate info for confirmation page
    $session->set('gift_certificate_code', $gift_code);
    $session->set('gift_certificate_id', $gift_id);
    
    // Handle scheduled delivery
    if (!empty($delivery_data['date']) && $delivery_data['method'] !== 'print') {
        // Store scheduled delivery info
        $sql = "INSERT INTO bg_user_attributes 
                (user_id, type, name, description, string_value, start_dt, category, grouping, status, create_dt) 
                VALUES 
                (:user_id, 'gift_delivery_schedule', :name, :description, :method, :start_dt, 'gift_delivery', 'gift_certificates', 'pending', NOW())";
        
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'user_id' => $buyer_user_id,
            'name' => 'gift_cert_' . $gift_id . '_delivery',
            'description' => json_encode([
                'gift_id' => $gift_id,
                'code' => $gift_code,
                'recipient' => $recipient_data,
                'message' => $delivery_data['message']
            ]),
            'method' => $delivery_data['method'],
            'start_dt' => $delivery_data['date']
        ]);
    } elseif ($delivery_data['method'] === 'email' || $delivery_data['method'] === 'phone') {
        // Send immediately (mark for immediate processing)
        // This would be picked up by a cron job or queue processor
        $sql = "INSERT INTO bg_user_attributes 
                (user_id, type, name, description, string_value, category, grouping, status, create_dt) 
                VALUES 
                (:user_id, 'gift_delivery_immediate', :name, :description, :method, 'gift_delivery', 'gift_certificates', 'pending', NOW())";
        
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'user_id' => $buyer_user_id,
            'name' => 'gift_cert_' . $gift_id . '_delivery',
            'description' => json_encode([
                'gift_id' => $gift_id,
                'code' => $gift_code,
                'recipient' => $recipient_data,
                'message' => $delivery_data['message']
            ]),
            'method' => $delivery_data['method']
        ]);
    }
    
    // Clear gift certificate session data
    $session->unset('gift_recipient_firstname');
    $session->unset('gift_recipient_lastname');
    $session->unset('gift_recipient_birthdate');
    $session->unset('gift_recipient_email');
    $session->unset('gift_recipient_phone');
    $session->unset('gift_delivery_method');
    $session->unset('gift_delivery_date');
    $session->unset('gift_message');
    
    // Set success message
    $session->set('signup_success', true);
    $session->set('account_type', 'giftcertificate');
    
} catch (Exception $e) {
    // Log error and set error message
    error_log('Gift certificate creation failed: ' . $e->getMessage());
    $session->set('signup_error', 'There was an error creating your gift certificate. Please contact support.');
}