<?php
/**
 * Process Family Members Form Data
 * Validates and processes children information for family accounts
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

// Initialize children array
$processed_data['children'] = [];

// Check if we have children data
if (!empty($_POST['child_firstname']) && is_array($_POST['child_firstname'])) {
    $child_count = count($_POST['child_firstname']);
    
    // Validate we have matching arrays for all child fields
    if (!isset($_POST['child_lastname']) || !is_array($_POST['child_lastname']) ||
        !isset($_POST['child_month']) || !is_array($_POST['child_month']) ||
        !isset($_POST['child_day']) || !is_array($_POST['child_day']) ||
        !isset($_POST['child_year']) || !is_array($_POST['child_year'])) {
        $errors[] = 'Invalid child data format';
        return $processed_data;
    }
    
    // Process each child
    for ($i = 0; $i < $child_count; $i++) {
        // Skip if all fields for this child are empty
        if (empty($_POST['child_firstname'][$i]) && 
            empty($_POST['child_lastname'][$i]) && 
            empty($_POST['child_month'][$i]) && 
            empty($_POST['child_day'][$i]) && 
            empty($_POST['child_year'][$i])) {
            continue;
        }
        
        $child_data = [];
        $child_num = $i + 1;
        
        // Validate child first name
        if (empty($_POST['child_firstname'][$i])) {
            $errors[] = "Child $child_num: First name is required";
        } else {
            $child_data['firstname'] = ucfirst(trim($_POST['child_firstname'][$i]));
        }
        
        // Validate child last name
        if (empty($_POST['child_lastname'][$i])) {
            $errors[] = "Child $child_num: Last name is required";
        } else {
            $child_data['lastname'] = ucfirst(trim($_POST['child_lastname'][$i]));
        }
        
        // Validate child birthday
        if (!empty($_POST['child_month'][$i]) && !empty($_POST['child_day'][$i]) && !empty($_POST['child_year'][$i])) {
            $child_birthday = $_POST['child_year'][$i] . '-' . $_POST['child_month'][$i] . '-' . $_POST['child_day'][$i];
            $child_birthdate = DateTime::createFromFormat('Y-m-d', $child_birthday);
            
            if (!$child_birthdate) {
                $errors[] = "Child $child_num: Please select a valid birth date";
            } else {
                // Check age (must be under 18 for child account)
                $child_age = $child_birthdate->diff(new DateTime())->y;
                if ($child_age >= 18) {
                    $errors[] = "Child $child_num: Children must be under 18 years old";
                } elseif ($child_age < 0) {
                    $errors[] = "Child $child_num: Invalid birth date (future date)";
                } else {
                    $child_data['birthday'] = $child_birthdate->format('Y-m-d');
                    $child_data['birthday_month'] = $child_birthdate->format('m');
                    $child_data['age'] = $child_age;
                }
            }
        } else {
            $errors[] = "Child $child_num: Complete birth date is required";
        }
        
        // If no errors for this child, add to processed data
        if (!empty($child_data['firstname']) && !empty($child_data['lastname']) && !empty($child_data['birthday'])) {
            $processed_data['children'][] = $child_data;
        }
    }
    
    // Check if we have at least one child for family accounts
    if (empty($processed_data['children'])) {
        $errors[] = 'At least one child must be added for a family account';
    }
    
    // Check maximum children
    if (count($processed_data['children']) > 4) {
        $errors[] = 'Maximum 4 children can be added during registration';
    }
}

// Return processed data for use in main form handler
return $processed_data;