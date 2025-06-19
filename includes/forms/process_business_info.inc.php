<?php
/**
 * Process Business Information Form Data
 * Validates and processes business-specific fields
 * 
 * Expected variables:
 * - $_POST: form submission data
 * - $errors: array to append validation errors
 * - $processed_data: array to store processed values
 */

// Initialize processed data array if not exists
if (!isset($processed_data)) {
    $processed_data = [];
}

// Validate business name (required for business accounts)
if (empty($_POST['business_name'])) {
    $errors[] = 'Business name is required';
} else {
    $processed_data['business_name'] = trim($_POST['business_name']);
}

// Process optional business fields
if (!empty($_POST['business_type'])) {
    $valid_types = ['restaurant', 'retail', 'service', 'entertainment', 'other'];
    if (in_array($_POST['business_type'], $valid_types)) {
        $processed_data['business_type'] = $_POST['business_type'];
    }
}

// Process business phone (optional)
if (!empty($_POST['business_phone'])) {
    $business_phone = preg_replace('/\D/', '', $_POST['business_phone']);
    if (strlen($business_phone) === 10) {
        $processed_data['business_phone'] = $business_phone;
    } elseif (strlen($business_phone) > 0) {
        $errors[] = 'Business phone must be a valid 10-digit number';
    }
}

// Process business website (optional)
if (!empty($_POST['business_website'])) {
    $website = trim($_POST['business_website']);
    // Basic URL validation
    if (!filter_var($website, FILTER_VALIDATE_URL)) {
        // Try adding https:// if missing
        if (!preg_match('/^https?:\/\//', $website)) {
            $website = 'https://' . $website;
        }
        
        if (!filter_var($website, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid website URL';
        } else {
            $processed_data['business_website'] = $website;
        }
    } else {
        $processed_data['business_website'] = $website;
    }
}

// Return processed data for use in main form handler
return $processed_data;