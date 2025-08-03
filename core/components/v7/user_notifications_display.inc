<?php
// always use single PHP BLOCK, ECHO block statements. 
// Do not use Short Echo Tags, Short Tags, Multiple PHP Tags or Nowdoc/Heredoc syntax

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// Get current user ID
$user_id = $current_user_data['user_id'];

// Get filter from query parameter
$filter = isset($_GET['notification_filter']) ? $_GET['notification_filter'] : 'all';

// Initialize output variable
$notifications_output = '';

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// Fetch notifications based on filter
if ($filter === 'unread') {
    $query = "SELECT * FROM bg_user_notifications WHERE user_id = :user_id AND status = 'unread' ORDER BY create_dt DESC";
} elseif ($filter === 'read') {
    $query = "SELECT * FROM bg_user_notifications WHERE user_id = :user_id AND status = 'read' ORDER BY create_dt DESC LIMIT 50";
} else {
    $query = "SELECT * FROM bg_user_notifications WHERE user_id = :user_id ORDER BY create_dt DESC LIMIT 100";
}

$stmt = $database->prepare($query);
$stmt->execute(['user_id' => $user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
// Render notifications
if (count($notifications) > 0) {
    foreach ($notifications as $notification) {
        $isRead = $notification['status'] === 'read';
        $timeElapsed = $qik->timeago($notification['create_dt']);
        $iconClass = isset($typeIcons[$notification['type']]) ? $typeIcons[$notification['type']] : 'bell-fill';
        
        // Extract message content for email notifications and security alerts
        $messageContent = $notification['message'];
        $hasFullContent = false;
        $snippetContent = '';
        
        if ($notification['type'] == 'email_notification' || $notification['type'] == 'security_alert') {
            preg_match('/<!-- Message Content start -->(.*?)<!-- Message Content end -->/s', $messageContent, $matches);
            if (isset($matches[1])) {
                $hasFullContent = true;
                // Create a plain text snippet (first 150 chars)
                $plainText = strip_tags($matches[1]);
                $plainText = preg_replace('/\s+/', ' ', $plainText); // Replace multiple spaces with single space
                $snippetContent = trim($plainText);
                if (strlen($snippetContent) > 150) {
                    $snippetContent = substr($snippetContent, 0, 150) . '...';
                }
            }
        }
        
        // Use snippet if available, otherwise use original message
        $displayMessage = $hasFullContent ? $snippetContent : $messageContent;
        
        $notifications_output .= '
        <div class="notification-item" data-notification-id="' . $notification['notification_id'] . '">
            <input type="checkbox" class="notification-checkbox" value="' . $notification['notification_id'] . '" style="display: none;">
            <div class="notification-icon">
                <i class="bi bi-' . $iconClass . '"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">' . htmlspecialchars($notification['title']) . '</div>
                <p class="notification-text">' . htmlspecialchars($displayMessage) . '</p>
                ' . ($hasFullContent ? '<a href="#" class="view-full-message" data-notification-id="' . $notification['notification_id'] . '">View full message</a>' : '') . '
                <div class="notification-time">' . $timeElapsed['message'] . '</div>
            </div>
            <div class="notification-actions">
                <div class="btn">
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" type="button" onclick="markNotification(\'' . $notification['notification_id'] . '\', \'' . ($isRead ? 'unread' : 'read') . '\')">
                            <i class="bi bi-check-circle"></i> Mark ' . ($isRead ? 'Unread' : 'Read') . '
                        </button></li>
                        <li><button class="dropdown-item" type="button" onclick="deleteNotification(\'' . $notification['notification_id'] . '\')">
                            <i class="bi bi-trash"></i> Delete
                        </button></li>
                    </ul>
                </div>
            </div>
        </div>';
    }
} else {
    $no_notifications_message = '';
    if ($filter === 'unread') {
        $no_notifications_message = 'No unread notifications';
    } elseif ($filter === 'read') {
        $no_notifications_message = 'No read notifications';
    } else {
        $no_notifications_message = 'No notifications';
    }
    
    $notifications_output .= '
    <div class="p-5 text-center text-muted">
        <i class="bi bi-bell-slash" style="font-size: 3rem;"></i>
        <p class="mt-3">' . $no_notifications_message . '</p>
    </div>';
}

// Set notification count
$notifications_count = count($notifications);

// Output the notifications only if not suppressed
if (empty($nonotificationoutput)) {
    echo $notifications_output;
}