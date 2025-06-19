<?php
/**
 * Process Name and Birthday Form Data
 * Validates and processes common name/birthday fields
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

// Validate first name
if (empty($_POST['firstname'])) {
    $errors[] = 'First name is required';
} else {
    $processed_data['firstname'] = ucfirst(trim($_POST['firstname']));
}

// Validate last name
if (empty($_POST['lastname'])) {
    $errors[] = 'Last name is required';
} else {
    $processed_data['lastname'] = ucfirst(trim($_POST['lastname']));
}

// Validate birthday from dropdowns
if (!empty($_POST['birth_month']) && !empty($_POST['birth_day']) && !empty($_POST['birth_year'])) {
    // Combine the fields
    $birthday_string = $_POST['birth_year'] . '-' . $_POST['birth_month'] . '-' . $_POST['birth_day'];
    $_POST['birthday'] = $birthday_string; // Store combined value
    
    $birthdate = DateTime::createFromFormat('Y-m-d', $birthday_string);
    if (!$birthdate) {
        $errors[] = 'Please select a valid date';
    } else {
        // Check age requirement
        $age = $birthdate->diff(new DateTime())->y;
        if ($age < 13) {
            $errors[] = 'You must be at least 13 years old to create an account';
        } else {
            // Store processed birthday data
            $processed_data['birthday'] = $birthdate->format('Y-m-d');
            $processed_data['birthday_month'] = $birthdate->format('m');
            $processed_data['birth_month'] = $_POST['birth_month'];
            $processed_data['birth_day'] = $_POST['birth_day'];
            $processed_data['birth_year'] = $_POST['birth_year'];
        }
    }
} else {
    $errors[] = 'Please select your complete birth date';
}

// Return processed data for use in main form handler
return $processed_data;