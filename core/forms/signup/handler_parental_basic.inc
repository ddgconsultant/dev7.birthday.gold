<?php
/**
 * Parental Basic Signup Form Handler
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

// Name validation
if (empty($data['firstname'])) {
    $errors['firstname'] = 'Parent first name is required';
}

if (empty($data['lastname'])) {
    $errors['lastname'] = 'Parent last name is required';
}

// Email validation (required for parents)
if (empty($data['email'])) {
    $errors['email'] = 'Email is required';
} else {
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
}

// Phone validation (optional)
if (!empty($data['phone'])) {
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

// Birthday validation
if (empty($data['birthday'])) {
    $errors['birthday'] = 'Parent birthday is required';
} else {
    $birthdate = DateTime::createFromFormat('Y-m-d', $data['birthday']);
    if (!$birthdate) {
        $errors['birthday'] = 'Please enter a valid date';
    } else {
        $age = $birthdate->diff(new DateTime())->y;
        if ($age < 18) {
            $errors['birthday'] = 'Parent must be at least 18 years old';
        }
    }
}

// Number of children validation
if (empty($data['num_children'])) {
    $errors['num_children'] = 'Please specify number of children';
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
    
    // Process birthday
    $birthday_date = DateTime::createFromFormat('Y-m-d', $data['birthday']);
    
    // Build processed data
    $processed['first_name'] = ucfirst(trim($data['firstname']));
    $processed['last_name'] = ucfirst(trim($data['lastname']));
    $processed['email'] = trim(strtolower($data['email']));
    $processed['phone_number'] = !empty($data['phone']) ? preg_replace('/\D/', '', $data['phone']) : '';
    $processed['hashed_password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    $processed['birthday'] = $birthday_date->format('Y-m-d');
    $processed['birthday_month'] = $birthday_date->format('m');
    
    // Profile duplicates
    $processed['profile_first_name'] = $processed['first_name'];
    $processed['profile_last_name'] = $processed['last_name'];
    $processed['profile_email'] = $processed['email'];
    $processed['profile_phone_type'] = 'mobile';
    
    // Generate username (will be done by main processor if not provided)
    $processed['username'] = '';
    $processed['profile_username'] = '';
    
    // Location data
    $processed['city'] = trim($client_locationdata['city'] ?? '');
    $processed['state'] = trim($client_locationdata['regionName'] ?? '');
    $processed['zip_code'] = trim($client_locationdata['zip'] ?? '');
    $processed['city2'] = $processed['city'];
    $processed['state2'] = $processed['state'];
    $processed['zip_code2'] = $processed['zip_code'];
    
    // Store parental data in extra_data
    $parental_data = [
        'num_children' => $data['num_children'],
        'children' => [] // Will be populated after account creation
    ];
    
    $processed['extra_data'] = json_encode($parental_data);
    
    // Other fields
    $processed['type'] = 'real';
    $processed['avatar_file'] = '';
    
    // Newsletter preference
    $processed['newsletter'] = isset($data['newsletter']) ? 1 : 0;
}

// Return results
return [
    'errors' => $errors,
    'processed' => $processed
];
?>