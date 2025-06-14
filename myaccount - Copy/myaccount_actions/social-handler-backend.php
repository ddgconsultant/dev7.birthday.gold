<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the post data
        $content = $_POST['content'] ?? '';
        $tags = $_POST['tags'] ?? '';
        $location = $_POST['location'] ?? null;

        // Handle file upload (if any)
        $media_url = null;
        if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
            $uploadDir = '/var/www/uploads/';
            $fileName = time() . '_' . basename($_FILES['media']['name']);
            $uploadFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['media']['tmp_name'], $uploadFile)) {
                $media_url = '/uploads/' . $fileName;
            }
        }

        // Insert post into the database using your $database class
        $user_id = $_SESSION['user_id'] ?? 1; // Placeholder user

        $media_type = $media_url ? pathinfo($media_url, PATHINFO_EXTENSION) : 'text';

        $sql = "
            INSERT INTO bgs_posts (user_id, content_text, media_url, media_type, tags, location_id, create_dt)
            VALUES (:user_id, :content, :media_url, :media_type, :tags, :location_id, NOW())
        ";

        $params = [
            ':user_id' => $user_id,
            ':content' => $content,
            ':media_url' => $media_url,
            ':media_type' => $media_type,
            ':tags' => $tags,
            ':location_id' => getLocationId($location),
        ];

        $database->run($sql, $params); // Use your existing $database class for execution

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error processing post: ' . $e->getMessage()]);
    }
}

function getLocationId($location) {
    global $database;
    
    if (!$location) return null;

    $sql = "SELECT location_id FROM bg_company_locations WHERE address = :location LIMIT 1";
    $existingLocation = $database->run($sql, [':location' => $location])->fetch();

    if ($existingLocation) return $existingLocation['location_id'];

    // Insert new location
    $sql = "INSERT INTO bg_company_locations (address, create_dt) VALUES (:location, NOW())";
    $database->run($sql, [':location' => $location]);

    return $database->lastInsertId();
}
?>
