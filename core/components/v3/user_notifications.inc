<?php
// Assume $database is an instance of your PDO database connection
// Fetch recent notifications (1-3 days old)
$recentdays=7;
$recentQuery = "
    SELECT *
    FROM bg_user_notifications
    WHERE create_dt >= NOW() - INTERVAL ".$recentdays." DAY
    ORDER BY create_dt DESC
";
$recentNotifications = $database->query($recentQuery)->fetchAll(PDO::FETCH_ASSOC);

// Fetch earlier notifications (older than 3 days)
$earlierQuery = "
    SELECT *
    FROM bg_user_notifications
    WHERE create_dt < NOW() - INTERVAL ".$recentdays." DAY
    ORDER BY create_dt DESC
    limit 30
";
$earlierNotifications = $database->query($earlierQuery)->fetchAll(PDO::FETCH_ASSOC);


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
    $iconClass = ($isRead?'envelope-fill text-primary':'envelope-open text-success');

  // Use a regular expression to extract the content between the tags
preg_match('/<!-- Message Content start -->(.*?)<!-- Message Content end -->/s', $messageContent, $matches);

// If the message content was found, assign it to a variable
if (isset($matches[1])) {
    $extractedMessage = trim(str_replace('<br><br>', '<br>', $matches[1])); // Trim any excess whitespace
} else {
   # $extractedMessage = 'Message content not found';
}
}


// Construct the HTML output with the Bootstrap icon
$output.= '<div class="p-3 d-flex  align-items-start bg-light border-bottom osahan-post-header">
    <div class="dropdown-list-image avatar me-2">
        <i class="bi bi-' . $iconClass . ' rounded-circle avatar-img"></i>
    </div>

    <div class="' . $boldClass . ' me-2 pe-4 flex-grow-1">
        <div class="text-truncate ' . $boldClass . '">' . $notification['title'] . '</div>
        <div class="small">' .   $extractedMessage . '</div>
    </div>

    <div class="d-flex flex-column align-items-end">
            <div class="btn-group mb-2">
                <button type="button" class="btn btn-light btn-sm rounded" data-bs-toggle="dropdown" aria-expanded="false" id="' . $dropdownId . '">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="' . $dropdownId . '">
                    <li><button class="dropdown-item" type="button"><i class="bi bi-trash"></i> Delete</button></li>
                    <li><button class="dropdown-item" type="button"><i class="bi bi-x-circle"></i> Mark '.($isRead?'Unread':'Read').'</button></li>
                    <li><button class="dropdown-item" type="button"><i class="bi bi-x-circle"></i> Turn Off</button></li>                    
                </ul>
            </div>
         
        <div class="text-right d-none d-md-block text-muted pt-1">' . $timeElapsed['message'] . ' ' . $readStatusIcon . '</div>
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

if (count($recentNotifications)>0) {
echo '
<div class="box shadow-sm rounded bg-white mb-3">
    <div class="box-title border-bottom p-3">
        <h6 class="text-primary fw-bold m-0">Recent</h6>
    </div>
    <div class="box-body p-0">
          '. renderNotifications($recentNotifications, true).'
    </div>
</div>
';
}


if (count($earlierNotifications)>0) {
echo '
<div class="box shadow-sm rounded bg-white mb-3">
    <div class="box-title border-bottom p-3">
        <h6 class="text-primary fw-bold m-0">Earlier</h6>
    </div>
    <div class="box-body p-0">
        '. renderNotifications($earlierNotifications, false).'
    </div>
</div>
';
}
