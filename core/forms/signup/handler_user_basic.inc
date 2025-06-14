<?php
/**
 * User Basic Signup Form Handler
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

// Contact method validation
$contact_method = $data['contact_method'] ?? 'phone';

if ($contact_method === 'phone') {
    if (empty($data['phone'])) {
        $errors['phone'] = 'Phone number is required';
    } else {
        $phone = preg_replace('/\D/', '', $data['phone']);
        if (strlen($phone) !== 10) {
            $errors['phone'] = 'Please enter a valid 10-digit phone number';
        } else {
            // Check if phone number is already in use
            $sql = "SELECT user_id, first_name, last_name, account_type, status 
                    FROM bg_users 
                    WHERE phone_number = :phone 
                    AND status IN ('active', 'pending', 'validated')
                    LIMIT 1";
            
            $existing_phone = $database->getrow($sql, ['phone' => $phone]);
            
            if ($existing_phone) {
                // Phone number is already registered
                if ($existing_phone['account_type'] === 'parental') {
                    $errors['phone'] = 'This phone number is already registered to a family account. Please contact the account holder to add you as a family member.';
                } else {
                    $errors['phone'] = 'This phone number is already registered. Please use a different phone number or login to your existing account.';
                }
                
                // Store info for potential future use (upgrade suggestions, etc.)
                $errors['existing_account_info'] = [
                    'user_id' => $existing_phone['user_id'],
                    'account_type' => $existing_phone['account_type'],
                    'suggestion' => $existing_phone['account_type'] === 'user' ? 
                        'Consider upgrading to a family account to manage multiple birthdays.' : 
                        'You may want to add additional features to your existing account.'
                ];
            }
        }
    }
} else {
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } else {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
    }
}

// Password validation
if (empty($data['password'])) {
    $errors['password'] = 'Password is required';
} elseif (strlen($data['password']) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
}

// Name validation
if (empty($data['firstname'])) {
    $errors['firstname'] = 'First name is required';
}

if (empty($data['lastname'])) {
    $errors['lastname'] = 'Last name is required';
}

// Birthday validation
if (empty($data['birthday'])) {
    $errors['birthday'] = 'Birthday is required';
} else {
    $birthdate = DateTime::createFromFormat('Y-m-d', $data['birthday']);
    if (!$birthdate) {
        $errors['birthday'] = 'Please enter a valid date';
    } else {
        $age = $birthdate->diff(new DateTime())->y;
        if ($age < 13) {
            $errors['birthday'] = 'You must be at least 13 years old';
        }
    }
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
    $processed['email'] = !empty($data['email']) ? trim(strtolower($data['email'])) : '';
    $processed['phone_number'] = !empty($data['phone']) ? preg_replace('/\D/', '', $data['phone']) : '';
    $processed['hashed_password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    $processed['birthday'] = $birthday_date->format('Y-m-d');
    $processed['birthday_month'] = $birthday_date->format('m');
    
    // Profile duplicates
    $processed['profile_first_name'] = $processed['first_name'];
    $processed['profile_last_name'] = $processed['last_name'];
    $processed['profile_email'] = $processed['email'];
    $processed['profile_phone_type'] = 'unknown';
    
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