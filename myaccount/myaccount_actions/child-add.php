<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

if (!$app->formposted()) {
    header('Location: /myaccount/parental-mode');
    exit;
}

try {
    $stmt = $database->prepare("SELECT COUNT(*) FROM bg_users WHERE feature_parent_id = :parent_id");
    $stmt->execute([':parent_id' => $current_user_data['user_id']]);
    if ($stmt->fetchColumn() >= 6) {
        throw new Exception('Maximum number of child accounts (6) reached');
    }

    $birthday = trim($_POST['dob'] ?? '');
    $birthday_date = new DateTime($birthday);
    
    // Get avatar and banner
    $avatar_file = $display->generateAvatarUrl($fileuploader);
    if (is_array($avatar_file)) $avatar_file = '/public/avatars/problemavatar.png';
    
    $input = [
        'first_name' => trim($_POST['first'] ?? ''),
        'last_name' => trim($_POST['last'] ?? ''),
        'gender' => trim($_POST['gender'] ?? ''),
        'birthday' => $birthday,
        'birthday_month' => $birthday_date->format('m'),
        'email' => trim($_POST['email'] ?? ''),
        'type' => 'test',
        'account_plan' => $current_user_data['account_plan'],
        'account_type' => 'minor',
        'account_product_id' => $current_user_data['account_product_id'],
        'account_verification' => 'notrequired',
        'status' => 'active',
        'feature_parent_id' => $current_user_data['user_id'],
        'city' => $current_user_data['city'],
        'state' => $current_user_data['state'],
        'zip_code' => $current_user_data['zip_code'],
        'country' => 'United States',
        'hashed_password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
        'enrollment_mode' => 'wizard',
        'avatar_file' => $avatar_file
    ];

    $child_id = $createaccount->create_user($input);
    $session->set('ALERT_MESSAGE', 'Child account created successfully');

} catch (Exception $e) {
    $session->set('ALERT_MESSAGE', 'Error creating child account: ' . $e->getMessage());
}

header('Location: /myaccount/parental-mode');
exit;