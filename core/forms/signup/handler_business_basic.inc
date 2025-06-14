<?php
/**
 * Business Basic Signup Form Handler
 * 
 * Variables available:
 * - $data: POST data
 * - $signup_process: Session data from step 1
 * - $createaccount: CreateAccount class instance
 * - $session: Session instance
 * - $qik: QIK instance
 * 
 * Returns:
 * - ['errors'] => array of validation errors
 * - ['processed'] => array of processed data ready for create_user()
 */

$errors = [];
$processed = [];

#-------------------------------------------------------------------------------
# VALIDATION
#-------------------------------------------------------------------------------

// Business Information
if (empty($data['business_name'])) {
    $errors['business_name'] = 'Business name is required';
}

if (empty($data['business_type'])) {
    $errors['business_type'] = 'Business type is required';
}

// Tax ID validation (optional but must be valid format if provided)
if (!empty($data['tax_id'])) {
    if (!preg_match('/^\d{2}-\d{7}$/', $data['tax_id'])) {
        $errors['tax_id'] = 'Tax ID must be in format XX-XXXXXXX';
    }
}

// Website validation (optional but must be valid URL if provided)
if (!empty($data['website'])) {
    if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
        $errors['website'] = 'Please enter a valid website URL';
    }
}

// Contact Information
if (empty($data['contact_firstname'])) {
    $errors['contact_firstname'] = 'Contact first name is required';
}

if (empty($data['contact_lastname'])) {
    $errors['contact_lastname'] = 'Contact last name is required';
}

if (empty($data['email'])) {
    $errors['email'] = 'Business email is required';
} else {
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
}

if (empty($data['phone'])) {
    $errors['phone'] = 'Business phone is required';
} else {
    $phone = preg_replace('/\D/', '', $data['phone']);
    if (strlen($phone) !== 10) {
        $errors['phone'] = 'Please enter a valid 10-digit phone number';
    }
}

// Password validation
if (empty($data['password'])) {
    $errors['password'] = 'Password is required';
} elseif (strlen($data['password']) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
}

// Check email availability if provided and no errors yet
if (empty($errors['email']) && !empty($data['email'])) {
    $email = trim(strtolower($data['email']));
    $response = $createaccount->isemailaccountavailable($email);
    
    if ($response !== true) {
        // We found an existing record
        $tempinfo = $response;
        
        // Check if it's a pending or validated user we can continue with
        if (!empty($tempinfo['status']) && in_array($tempinfo['status'], ['pending', 'validated'])) {
            // This will be handled by the main form processor
            $processed['existing_user'] = $tempinfo;
        } else {
            // Email is truly unavailable
            $errors['email'] = 'This email is already registered';
        }
    }
}

#-------------------------------------------------------------------------------
# PROCESS DATA IF NO ERRORS
#-------------------------------------------------------------------------------
if (empty($errors)) {
    // Get location data from session
    $client_locationdata = $session->get('client_locationdata', []);
    
    // Business data - store in JSON in extra_data field
    $business_data = [
        'business_name' => trim($data['business_name']),
        'business_type' => $data['business_type'],
        'tax_id' => trim($data['tax_id'] ?? ''),
        'num_locations' => $data['num_locations'],
        'website' => trim($data['website'] ?? ''),
        'contact_title' => trim($data['contact_title'] ?? '')
    ];
    
    // Contact person data - map to standard user fields
    $processed['first_name'] = ucfirst(trim($data['contact_firstname']));
    $processed['last_name'] = ucfirst(trim($data['contact_lastname']));
    
    // Store business name as display name for easier identification
    $processed['display_name'] = $business_data['business_name'];
    
    // Contact info
    $processed['email'] = trim(strtolower($data['email']));
    $processed['phone_number'] = preg_replace('/\D/', '', $data['phone']);
    $processed['hashed_password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Profile duplicates
    $processed['profile_first_name'] = $processed['first_name'];
    $processed['profile_last_name'] = $processed['last_name'];
    $processed['profile_email'] = $processed['email'];
    $processed['profile_phone_type'] = 'business';
    
    // Birthday (required by system but not meaningful for businesses)
    $processed['birthday'] = '2000-01-01';
    $processed['birthday_month'] = '01';
    
    // Username will be generated based on business name
    $processed['username'] = '';
    $processed['profile_username'] = '';
    
    // Location data
    $processed['city'] = trim($client_locationdata['city'] ?? '');
    $processed['state'] = trim($client_locationdata['regionName'] ?? '');
    $processed['zip_code'] = trim($client_locationdata['zip'] ?? '');
    $processed['city2'] = $processed['city'];
    $processed['state2'] = $processed['state'];
    $processed['zip_code2'] = $processed['zip_code'];
    
    // Other fields
    $processed['type'] = 'real';
    $processed['avatar_file'] = '';
    
    // Store business data as JSON
    $processed['extra_data'] = json_encode($business_data);
    
    // Newsletter preference
    $processed['newsletter'] = isset($data['newsletter']) ? 1 : 0;
}

// Return results
return [
    'errors' => $errors,
    'processed' => $processed
];
?>