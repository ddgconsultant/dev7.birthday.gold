<?php
/**
 * Process Organization Information Form Data
 * Validates and processes organization-specific fields
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

// Validate organization name (required)
if (empty($_POST['org_name'])) {
    $errors[] = 'Organization name is required';
} else {
    $processed_data['org_name'] = trim($_POST['org_name']);
}

// Validate organization type (required)
if (empty($_POST['org_type'])) {
    $errors[] = 'Organization type is required';
} else {
    $valid_types = ['nonprofit', 'school', 'church', 'club', 'government', 'other'];
    if (in_array($_POST['org_type'], $valid_types)) {
        $processed_data['org_type'] = $_POST['org_type'];
    } else {
        $errors[] = 'Please select a valid organization type';
    }
}

// Process tax ID (optional)
if (!empty($_POST['tax_id'])) {
    // Basic EIN format validation (XX-XXXXXXX)
    $tax_id = preg_replace('/[^0-9-]/', '', $_POST['tax_id']);
    if (preg_match('/^\d{2}-\d{7}$/', $tax_id)) {
        $processed_data['tax_id'] = $tax_id;
    } else {
        // Try without dash
        $tax_id_digits = preg_replace('/\D/', '', $_POST['tax_id']);
        if (strlen($tax_id_digits) === 9) {
            // Format it properly
            $processed_data['tax_id'] = substr($tax_id_digits, 0, 2) . '-' . substr($tax_id_digits, 2);
        } else {
            $errors[] = 'Tax ID must be in format XX-XXXXXXX';
        }
    }
}

// Process member count (optional)
if (!empty($_POST['member_count'])) {
    $valid_counts = ['1-10', '11-50', '51-100', '101-500', '500+'];
    if (in_array($_POST['member_count'], $valid_counts)) {
        $processed_data['member_count'] = $_POST['member_count'];
    }
}

// Return processed data for use in main form handler
return $processed_data;