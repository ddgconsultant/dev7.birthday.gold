<?php
/**
 * AJAX handler for user notifications
 * Returns paginated notifications for a specific user
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get parameters
$user_id = intval($_GET['user_id'] ?? 0);
$offset = intval($_GET['offset'] ?? 0);
$limit = intval($_GET['limit'] ?? 50);

// Validate
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// Ensure limit is reasonable
$limit = min($limit, 100);

try {
    // Get summary counts
    $summary_sql = "SELECT
                        status,
                        sent_to,
                        COUNT(*) as count
                    FROM bg_user_notifications
                    WHERE user_id = :user_id
                    GROUP BY status, sent_to";

    $stmt = $database->prepare($summary_sql);
    $stmt->execute([':user_id' => $user_id]);
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build query for notifications
    $sql = "SELECT
                notification_id,
                type,
                title,
                message,
                status,
                sent_to,
                sent_dt,
                create_dt,
                modify_dt,
                priority,
                category,
                options
            FROM bg_user_notifications
            WHERE user_id = :user_id
            ORDER BY create_dt DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $database->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine which notifications can be resent
    foreach ($notifications as &$notification) {
        // Determine if this notification can be resent
        // Resendable if: sent_to contains an email address OR is 'email'/'both'
        $can_resend = false;

        // Check if sent_to is an email address (contains @)
        if (!empty($notification['sent_to']) &&
            (strpos($notification['sent_to'], '@') !== false ||
             in_array($notification['sent_to'], ['email', 'both']))) {
            $can_resend = true;
        }

        $notification['can_resend'] = $can_resend;

        // Normalize sent_to for display
        if (!empty($notification['sent_to'])) {
            if (strpos($notification['sent_to'], '@') !== false) {
                // It is an email address
                $notification['sent_to_display'] = 'email';
                $notification['recipient_email'] = $notification['sent_to'];
            } else {
                $notification['sent_to_display'] = $notification['sent_to'];
                $notification['recipient_email'] = null;
            }
        } else {
            $notification['sent_to_display'] = 'display';
            $notification['recipient_email'] = null;
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'summary' => $summary,
        'count' => count($notifications),
        'offset' => $offset,
        'limit' => $limit
    ]);

} catch (PDOException $e) {
    error_log("Database error in user-notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
