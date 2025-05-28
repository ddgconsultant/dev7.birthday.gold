<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$app->formposted()) {
        http_response_code(403);
        echo 'CSRF token validation failed.';
        exit;
    }

    $alertType = $_POST['alertType'];
    $isChecked = $_POST['isChecked'];
    $userId = $_SESSION['user_id']; // Assuming user ID is stored in the session


      // Create the $params array
      $params = [
        ':isChecked' => $isChecked,
        ':userId' => $userId,
        ':alertType' => $alertType,
    ];

    // Update the setting in the database
        $stmt = $database->prepare("UPDATE notification_settings SET is_enabled = :isChecked WHERE user_id = :userId AND alert_type = :alertType");
        $stmt->execute($params);

        echo 'Notification setting updated successfully.';
   
}