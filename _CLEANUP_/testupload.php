<?php
$addClasses[] = 'fileuploader';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['profile-image'])) {
        // Simulate current user data
        $current_user_data = ['user_id' => 1, 'username' => 'testuser']; // Example user data

        $b2Credentials = $sitesettings['storage'];

         $randomString = bin2hex(random_bytes(10));
        $fileType = pathinfo($_FILES['profile-image']['name'], PATHINFO_EXTENSION);
        $hashedFileName = hash('sha256', $current_user_data['user_id'] . $randomString) . '.' . $fileType;
     
$currentfile=$_FILES['profile-image'];


        $result = $fileuploader->uploadFile($currentfile, 'public/useravatars/' . $hashedFileName, $current_user_data['user_id']);

        if ($result['success']) {
            echo 'File uploaded successfully: ' . $result['file_path'];
        } else {
            echo 'Error: ' . $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Test</title>
</head>
<body>
    <h2>Upload Test</h2>
    <form action="testupload.php" method="post" enctype="multipart/form-data">
        <label for="profile-image">Select an image to upload:</label>
        <input type="file" name="profile-image" id="profile-image">
        <input type="hidden" name="token" value="some_token_value"> <!-- Add a token value if needed -->
        <input type="submit" value="Upload Image" name="submit">
    </form>
</body>
</html>
