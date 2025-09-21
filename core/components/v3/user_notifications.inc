<?php
// Assume $database is an instance of your PDO database connection
// Get current user ID (assuming it's available in session or through account class)
$user_id = isset($_SESSION['user']['user_id']) ? $_SESSION['user']['user_id'] : 0;

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

// For backward compatibility, keep recent and earlier arrays
$recentdays=7;
$recentNotifications = array_filter(array_merge($unreadNotifications, $readNotifications), function($n) use ($recentdays) {
    return strtotime($n['create_dt']) >= strtotime("-{$recentdays} days");
});
$earlierNotifications = array_filter($readNotifications, function($n) use ($recentdays) {
    return strtotime($n['create_dt']) < strtotime("-{$recentdays} days");
});


###------------------------------------------------------------------------------------------------------------
function renderNotifications($notifications, $isRecent = true) {
    global $qik;
    $output = '';   
    // Define the icon mapping for each notification type
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
    'community_event' => 'calendar-event'
];

    foreach ($notifications as $notification) {
        $isRead = $notification['status'] === 'read';
        $boldClass = $isRead ?  'fw-bold':'fw-normal' ;
        $readStatusIcon = $isRead ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-circle-fill text-muted"></i>';
        $timeElapsed = $qik->timeago($notification['create_dt']);

        // Get the appropriate icon for the notification type
$iconClass = isset($typeIcons[$notification['type']]) ? $typeIcons[$notification['type']] : 'bell-fill';
// Unique ID for each dropdown button
$dropdownId = 'dropdown-' . $notification['notification_id'];
$messageContent = $notification['message'];

if ($notification['type']=='email_notification'){
    $iconClass = ($isRead?'envelope-open text-primary':'envelope-fill text-primary');

  // Use a regular expression to extract the content between the tags
preg_match('/<!-- Message Content start -->(.*?)<!-- Message Content end -->/s', $messageContent, $matches);

// If the message content was found, assign it to a variable
if (isset($matches[1])) {
    $extractedMessage = trim(str_replace('<br><br>', '<br>', $matches[1])); // Trim any excess whitespace
} else {
   # $extractedMessage = 'Message content not found';
}
}


// Construct the HTML output with modern styling
$output.= '<div class="notification-item">
    <div class="notification-icon">
        <i class="bi bi-' . $iconClass . '"></i>
    </div>
    <div class="notification-content">
        <div class="notification-title">' . $notification['title'] . '</div>
        <p class="notification-text">' . $extractedMessage . '</p>
        <div class="notification-time">' . $timeElapsed['message'] . '</div>
    </div>
    <div class="notification-actions">
        <div class="btn-group">
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><button class="dropdown-item" type="button"><i class="bi bi-check-circle"></i> Mark '.($isRead?'Unread':'Read').'</button></li>
                <li><button class="dropdown-item" type="button"><i class="bi bi-trash"></i> Delete</button></li>
                <li><button class="dropdown-item" type="button"><i class="bi bi-bell-slash"></i> Turn Off</button></li>                    
            </ul>
        </div>
    </div>
</div>';

    }
    return $output;
}
###------------------------------------------------------------------------------------------------------------


/*
<div class="box shadow-sm rounded bg-white mb-3">
    <div class="box-title border-bottom p-3">
        <h6 class="m-0">Recent</h6>
    </div>
    <div class="box-body p-0">
        <?php renderNotifications($recentNotifications, true); ?>
    </div>
</div>
<div class="box shadow-sm rounded bg-white mb-3">
    <div class="box-title border-bottom p-3">
        <h6 class="m-0">Earlier</h6>
    </div>
    <div class="box-body p-0">
        <?php renderNotifications($earlierNotifications, false); ?>
    </div>
</div>

*/

// Render both mobile and desktop views
// Mobile view with separate tabs
echo '<div class="mobile-notification-view">';
    // Mobile view with separate tabs
    echo '<div id="unread-notifications" class="notification-section">';
    if (count($unreadNotifications) > 0) {
        echo renderNotifications($unreadNotifications, true);
    } else {
        echo '<div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1"></i>
                <p class="mt-3">No unread notifications</p>
              </div>';
    }
    echo '</div>';
    
    echo '<div id="read-notifications" class="notification-section" style="display:none;">';
    if (count($readNotifications) > 0) {
        echo renderNotifications($readNotifications, true);
    } else {
        echo '<div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1"></i>
                <p class="mt-3">No read notifications</p>
              </div>';
    }
    echo '</div>';
    
    echo '<div id="all-notifications" class="notification-section" style="display:none;">';
    if (count($recentNotifications) > 0 || count($earlierNotifications) > 0) {
        if (count($recentNotifications) > 0) {
            echo '<div class="notification-group">
                    <h6 class="text-muted small text-uppercase mb-3">Recent</h6>
                    '. renderNotifications($recentNotifications, true).'
                  </div>';
        }
        if (count($earlierNotifications) > 0) {
            echo '<div class="notification-group mt-4">
                    <h6 class="text-muted small text-uppercase mb-3">Earlier</h6>
                    '. renderNotifications($earlierNotifications, false).'
                  </div>';
        }
    } else {
        echo '<div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1"></i>
                <p class="mt-3">No notifications</p>
              </div>';
    }
    echo '</div>';
echo '</div>'; // close mobile-notification-view

// Desktop view - show recent/earlier
echo '<div class="desktop-notification-view">';
    if (count($recentNotifications) > 0) {
        echo '<div class="notification-group">
                <h6 class="text-muted small text-uppercase mb-3">Recent</h6>
                '. renderNotifications($recentNotifications, true).'
              </div>';
    }

    if (count($earlierNotifications) > 0) {
        echo '<div class="notification-group mt-4">
                <h6 class="text-muted small text-uppercase mb-3">Earlier</h6>
                '. renderNotifications($earlierNotifications, false).'
              </div>';
    }
    
    if (count($recentNotifications) == 0 && count($earlierNotifications) == 0) {
        echo '<div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1"></i>
                <p class="mt-3">No notifications</p>
              </div>';
    }
echo '</div>'; // close desktop-notification-view
