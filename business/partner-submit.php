<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check CSRF token
if (!$app->checkCSRF()) {
    $system->addmessage('error', 'Invalid form submission. Please try again.');
    header('Location: /business/partner');
    exit();
}

// Validate form data
$required_fields = ['business_name', 'business_type', 'contact_name', 'contact_email', 'contact_phone', 'birthday_offer', 'agree_terms'];
$form_data = [];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = "Missing required field: " . str_replace('_', ' ', ucfirst($field));
    } else {
        $form_data[$field] = $system->cleanfordb($_POST[$field]);
    }
}

// Additional fields
$optional_fields = ['locations', 'website', 'additional_info'];
foreach ($optional_fields as $field) {
    $form_data[$field] = isset($_POST[$field]) ? $system->cleanfordb($_POST[$field]) : '';
}

// Validate email
if (!filter_var($form_data['contact_email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address";
}

// Validate phone
$phone = preg_replace('/[^0-9]/', '', $form_data['contact_phone']);
if (strlen($phone) < 10) {
    $errors[] = "Invalid phone number";
}

// Check for errors
if (!empty($errors)) {
    $system->addmessage('error', implode('<br>', $errors));
    header('Location: /business/partner');
    exit();
}

// Store in database
try {
    $sql = "INSERT INTO bg_partner_applications (
        business_name, business_type, contact_name, contact_email, 
        contact_phone, locations, website, birthday_offer, 
        additional_info, ip_address, created_at, status
    ) VALUES (
        :business_name, :business_type, :contact_name, :contact_email,
        :contact_phone, :locations, :website, :birthday_offer,
        :additional_info, :ip_address, NOW(), 'pending'
    )";
    
    $params = [
        'business_name' => $form_data['business_name'],
        'business_type' => $form_data['business_type'],
        'contact_name' => $form_data['contact_name'],
        'contact_email' => $form_data['contact_email'],
        'contact_phone' => $form_data['contact_phone'],
        'locations' => $form_data['locations'],
        'website' => $form_data['website'],
        'birthday_offer' => $form_data['birthday_offer'],
        'additional_info' => $form_data['additional_info'],
        'ip_address' => $client_ip
    ];
    
    $database->query($sql, $params);
    $application_id = $database->lastInsertId();
    
    // Send notification email to admin
    $admin_subject = "New Partner Application: " . $form_data['business_name'];
    $admin_message = "A new partner application has been submitted:\n\n";
    $admin_message .= "Business Name: " . $form_data['business_name'] . "\n";
    $admin_message .= "Business Type: " . $form_data['business_type'] . "\n";
    $admin_message .= "Contact: " . $form_data['contact_name'] . "\n";
    $admin_message .= "Email: " . $form_data['contact_email'] . "\n";
    $admin_message .= "Phone: " . $form_data['contact_phone'] . "\n";
    $admin_message .= "Locations: " . $form_data['locations'] . "\n";
    $admin_message .= "Website: " . $form_data['website'] . "\n";
    $admin_message .= "Birthday Offer: " . $form_data['birthday_offer'] . "\n";
    $admin_message .= "Additional Info: " . $form_data['additional_info'] . "\n\n";
    $admin_message .= "Application ID: " . $application_id . "\n";
    $admin_message .= "View in admin: https://dev7.birthday.gold/admin/partner-applications.php?id=" . $application_id;
    
    // Send to admin email(s)
    $system->sendemail('partners@birthday.gold', $admin_subject, $admin_message);
    
    // Send confirmation email to applicant
    $applicant_subject = "Thank you for applying to Birthday Gold Partner Program";
    $applicant_message = "Dear " . $form_data['contact_name'] . ",\n\n";
    $applicant_message .= "Thank you for your interest in becoming a Birthday Gold partner!\n\n";
    $applicant_message .= "We have received your application for " . $form_data['business_name'] . " and our team will review it within 1-2 business days.\n\n";
    $applicant_message .= "Here's what happens next:\n";
    $applicant_message .= "1. Our team will review your application and proposed birthday offer\n";
    $applicant_message .= "2. We may contact you if we need any additional information\n";
    $applicant_message .= "3. Once approved, we'll send you onboarding materials and get your offer live\n\n";
    $applicant_message .= "If you have any questions in the meantime, please feel free to reach out to partners@birthday.gold\n\n";
    $applicant_message .= "Best regards,\n";
    $applicant_message .= "The Birthday Gold Team";
    
    $system->sendemail($form_data['contact_email'], $applicant_subject, $applicant_message);
    
    // Log the application
    session_tracking('partner_application_submitted', [
        'application_id' => $application_id,
        'business_name' => $form_data['business_name'],
        'business_type' => $form_data['business_type']
    ]);
    
    // Redirect to success page
    header('Location: /business/partner-success');
    exit();
    
} catch (Exception $e) {
    // Log error
    error_log("Partner application error: " . $e->getMessage());
    
    $system->addmessage('error', 'There was an error submitting your application. Please try again or contact support.');
    header('Location: /business/partner');
    exit();
}
?>