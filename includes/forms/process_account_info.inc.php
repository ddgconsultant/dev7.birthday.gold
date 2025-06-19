<?php
/**
 * Process Account Information Form Data
 * Validates and processes common account fields
 * 
 * Expected variables:
 * - $_POST: form submission data
 * - $errors: array to append validation errors
 * - $processed_data: array to store processed values
 * - $createaccount: createaccount class instance for availability checks
 * - $account_type: current account type
 */

// Initialize processed data array if not exists
if (!isset($processed_data)) {
    $processed_data = [];
}

// Process contact method
$contact_method = $_POST['contact_method'] ?? 'phone';
$processed_data['contact_method'] = $contact_method;

// Validate based on contact method
if ($contact_method == 'phone') {
    if (empty($_POST['phone'])) {
        $errors[] = 'Phone number is required';
    } else {
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        if (strlen($phone) !== 10) {
            $errors[] = 'Please enter a valid 10-digit phone number';
        } else {
            $processed_data['phone_number'] = $phone;
        }
    }
    // Email is optional when using phone
    if (!empty($_POST['email'])) {
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        } else {
            $processed_data['email'] = trim(strtolower($_POST['email']));
        }
    }
} else {
    // Email is required when that's the contact method
    if (empty($_POST['email'])) {
        $errors[] = 'Email address is required';
    } else {
        $email = trim(strtolower($_POST['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        } else {
            $processed_data['email'] = $email;
            
            // Check email availability if createaccount instance is available
            if (isset($createaccount)) {
                $response = $createaccount->isemailaccountavailable($email);
                
                if ($response !== true) {
                    // We found an existing record
                    $tempinfo = $response;
                    
                    // Check if it's a pending or validated user we can continue with
                    if (!empty($tempinfo['status']) && in_array($tempinfo['status'], ['pending', 'validated'])) {
                        // Store for potential redirect handling
                        $processed_data['existing_user'] = $tempinfo;
                    } else {
                        // Email is truly unavailable
                        $errors[] = 'This email is already registered';
                    }
                }
            }
        }
    }
    
    // Phone is optional when using email
    if (!empty($_POST['phone'])) {
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        if (strlen($phone) === 10) {
            $processed_data['phone_number'] = $phone;
        } elseif (strlen($phone) > 0) {
            $errors[] = 'Phone number must be 10 digits';
        }
    }
}

// Process username (optional for user accounts)
if ($account_type == 'user' && !empty($_POST['username'])) {
    $username = trim($_POST['username']);
    
    // Basic username validation
    if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters and contain only letters, numbers, underscores, and hyphens';
    } elseif (isset($createaccount) && !$createaccount->isavailable($username, 'username')) {
        $errors[] = 'This username is already taken';
    } else {
        $processed_data['username'] = $username;
    }
}

// Validate password
if (empty($_POST['password'])) {
    $errors[] = 'Password is required';
} elseif (strlen($_POST['password']) < 8) {
    $errors[] = 'Password must be at least 8 characters';
} else {
    $processed_data['password'] = $_POST['password'];
    $processed_data['hashed_password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
}

// Validate terms acceptance
if (empty($_POST['terms'])) {
    $errors[] = 'You must agree to the Terms and Privacy Policy';
} else {
    $processed_data['terms_accepted'] = true;
}

// Process promo code if provided
if (!empty($_POST['promo_code'])) {
    $processed_data['promo_code'] = trim($_POST['promo_code']);
}

// Process referral code if provided
if (!empty($_POST['referral_code'])) {
    $processed_data['referral_code'] = trim($_POST['referral_code']);
}

// Return processed data for use in main form handler
return $processed_data;