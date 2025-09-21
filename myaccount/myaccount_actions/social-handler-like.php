<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? 1; // Placeholder user

    if ($post_id) {
        try {
            // Check if the user has already liked the post
            $sql = "SELECT * FROM bgs_post_likes WHERE post_id = :post_id AND user_id = :user_id";
            $params = [':post_id' => $post_id, ':user_id' => $user_id];
            $existingLike = $database->run($sql, $params)->fetch();

            if (!$existingLike) {
                // Insert like into the database
                $sql = "INSERT INTO bgs_post_likes (post_id, user_id, create_dt) VALUES (:post_id, :user_id, NOW())";
                $database->run($sql, $params);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Already liked!']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error processing like: ' . $e->getMessage()]);
        }
    }
}
?>
