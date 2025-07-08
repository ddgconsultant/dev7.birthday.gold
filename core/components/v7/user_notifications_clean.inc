<?php
// Get current user ID
$user_id = $current_user_data['user_id'];

// Fetch unread notifications
$unreadQuery = "
    SELECT *
    FROM bg_user_notifications
    WHERE user_id = :user_id AND status = 'unread'
    ORDER BY create_dt DESC
";
$stmt = $database->prepare($unreadQuery);
$stmt->execute(['user_id' => $user_id]);
$unreadNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count($unreadNotifications);

// Fetch read notifications
$readQuery = "
    SELECT *
    FROM bg_user_notifications
    WHERE user_id = :user_id AND status = 'read'
    ORDER BY create_dt DESC
    LIMIT 50
";
$stmt = $database->prepare($readQuery);
$stmt->execute(['user_id' => $user_id]);
$readNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to render clean notifications
function renderCleanNotification($notification) {
    global $qik;
    
    $isRead = $notification['status'] === 'read';
    $timeElapsed = $qik->timeago($notification['create_dt']);
    
    // Icon mapping
    $typeIcons = [
        'employee_onboarding' => 'person-badge',
        'password_expiry' => 'key-fill',
        'promotion' => 'arrow-up-circle-fill',
        'maintenance' => 'tools',
        'account_verification' => 'check-circle-fill',
        'security_alert' => 'shield-exclamation',
        'feature_update' => 'info-circle-fill',
        'billing' => 'credit-card-fill',
        'survey' => 'clipboard-data',
        'community_event' => 'calendar-event',
        'email_notification' => 'envelope-fill'
    ];
    
    $iconClass = isset($typeIcons[$notification['type']]) ? $typeIcons[$notification['type']] : 'bell-fill';
    
    // Extract message content if email notification
    $messageContent = $notification['message'];
    if ($notification['type'] == 'email_notification') {
        preg_match('/<!-- Message Content start -->(.*?)<!-- Message Content end -->/s', $messageContent, $matches);
        if (isset($matches[1])) {
            $messageContent = trim(str_replace('<br><br>', '<br>', $matches[1]));
        }
    }
    
    $html = '<div class="notification-item" data-notification-id="' . $notification['notification_id'] . '">
        <div class="notification-icon">
            <i class="bi bi-' . $iconClass . '"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">' . htmlspecialchars($notification['title']) . '</div>
            <p class="notification-text">' . $messageContent . '</p>
            <div class="notification-time">' . $timeElapsed['message'] . '</div>
        </div>
        <div class="notification-actions">
            <div class="btn-group">
                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item" type="button" onclick="markNotification(' . $notification['notification_id'] . ', \'' . ($isRead ? 'unread' : 'read') . '\')">
                        <i class="bi bi-check-circle"></i> Mark ' . ($isRead ? 'Unread' : 'Read') . '
                    </button></li>
                    <li><button class="dropdown-item" type="button" onclick="deleteNotification(' . $notification['notification_id'] . ')">
                        <i class="bi bi-trash"></i> Delete
                    </button></li>
                    <li><button class="dropdown-item" type="button" onclick="turnOffNotification(' . $notification['notification_id'] . ')">
                        <i class="bi bi-bell-slash"></i> Turn Off
                    </button></li>
                </ul>
            </div>
        </div>
    </div>';
    
    return $html;
}

// Render notifications
// Determine which notifications to show based on filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if ($filter === 'unread') {
    $notifications = $unreadNotifications;
    $emptyMessage = 'No unread notifications';
} elseif ($filter === 'read') {
    $notifications = $readNotifications;
    $emptyMessage = 'No read notifications';
} else {
    $notifications = array_merge($unreadNotifications, $readNotifications);
    $emptyMessage = 'No notifications';
}

// Render notifications
if (count($notifications) > 0) {
    foreach ($notifications as $notification) {
        echo renderCleanNotification($notification);
    }
} else {
    echo '<div class="text-center py-5 text-muted">
            <i class="bi bi-bell-slash fs-1"></i>
            <p class="mt-3">' . $emptyMessage . '</p>
          </div>';
}

// Output unread count for JavaScript
echo '<script>var notificationUnreadCount = ' . $unreadCount . ';</script>';
?>