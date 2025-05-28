<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Debug flag
$debug = false; // Set to true for detailed debug output

// Query all members with pending enrollments and calculate days until birthday
$query = "SELECT 
    u.user_id, 
    u.first_name, 
    u.last_name,
    DATE_FORMAT(u.birthdate, '%M %e') AS formatted_birthdate,
    /* Calculate days between today and birthday, ignoring year */
    MOD(
        DAYOFYEAR(DATE_FORMAT(u.birthdate, '%Y-%m-%d')) - 
        DAYOFYEAR(NOW()) + 
        365, 365
    ) AS days_until_birthday,
    SUM(CASE 
        WHEN uc.status IN ('selected', 'pending') 
        AND c.signup_url != ? 
        THEN 1 
        ELSE 0 
    END) AS pending_count
FROM 
    bg_user_companies uc
INNER JOIN 
    bg_users u ON uc.user_id = u.user_id AND u.status = 'active'
INNER JOIN
    bg_companies c ON c.company_id = uc.company_id
WHERE 
    c.status = 'finalized'
    AND u.create_dt >= '2023-08-01'
    AND uc.create_dt >= '2023-08-01'
    AND NOT (uc.status LIKE '%failed%' AND LOWER(uc.reason) LIKE '%account%exists%')
    AND u.type = 'real'
GROUP BY 
    u.user_id
HAVING 
    pending_count > 0
ORDER BY 
    days_until_birthday";

if ($debug) {
    echo "\nDEBUG: Executing query:\n" . $query . "\n";
}

$stmt = $database->prepare($query);
$stmt->execute([$website['apponlytag']]);
$users = $stmt->fetchAll();

if ($debug) {
    echo "\n=== DEBUG: All users with pending enrollments ===\n";
    foreach ($users as $user) {
        echo "\nUser: {$user['first_name']} {$user['last_name']} (ID: {$user['user_id']})\n";
        echo "Birthday: {$user['formatted_birthdate']}\n";
        echo "Days until birthday: {$user['days_until_birthday']}\n";
        echo "Pending count: {$user['pending_count']}\n";
        echo "------------------------\n";
    }
}

// Current hour for notification timing
$currentHour = (int)date('G');

// Message variations for different urgency levels
$urgentMessages = [
    // With birthday plural handling
    "🚨 Hey! I see %s that needs immediate attention for their %s!",
    "🎂 Quick heads up - %s waiting for enrollment processing for their %s!",
    "⚡️ Urgent! %s needs processing for their %s RIGHT NOW!",
    "🎈 Can't keep %s waiting - their %s enrollment needs processing!",
    
    // Action focused alternatives
    "🚨 Time-sensitive: %s needs immediate enrollment attention!",
    "⚡️ High priority! %s needs processing right away!",
    "🎂 Got %s that needs urgent enrollment processing!",
    "🎈 Immediate action needed: %s requires processing today!"
];

$soonMessages = [
    // With birthday plural handling
    "👋 I've spotted %s with their %s coming up in the next few days!",
    "🎁 Heads up! %s has their %s approaching and needs processing!",
    "📅 Looking ahead - %s with their %s in the next few days needs attention!",
    "🎈 Just noticed %s with their %s coming up soon!",
    
    // Action focused alternatives
    "👋 Got %s in the upcoming processing queue!",
    "🎁 Time to plan: %s needs enrollment attention soon!",
    "📅 On the horizon: %s needs processing in the next few days!",
    "🎈 Added to short-term queue: %s needs enrollment processing!"
];

$futureMessages = [
    // With birthday plural handling
    "📝 I've noticed %s with their %s coming up!",
    "🗓 There's %s with their %s in the future queue!",
    "✨ For planning: %s has their %s coming up later!",
    "📊 Future processing needed: %s has their %s approaching!",
    
    // Action focused alternatives
    "📝 Added to future processing: %s needs attention when you can!",
    "🗓 For your planning: %s needs future enrollment processing!",
    "✨ No rush, but %s will need processing eventually!",
    "📊 When you have time: %s needs to be processed!"
];

function sendNotification($users, $timeframe, $system, $qik, $debug = false) {
    $listofusers = [];
    foreach ($users as $user) {
        $listofusers[] = $user['first_name'] . ' ' . $user['last_name'] . 
                        ' (' . $user['user_id'] . ')=' . $user['pending_count'];
    }
    
    $count = count($listofusers);
    if ($count > 0) {
        global $urgentMessages, $soonMessages, $futureMessages;
        
        // Create properly pluralized strings
        $memberCount = $qik->plural2($count, 'member');
        $birthdayCount = $qik->plural2($count, 'birthday');
        
        // Select message template based on timeframe
        switch ($timeframe) {
            case "TODAY'S BIRTHDAYS ⚠️":
                $messages = $urgentMessages;
                $closing = "\nPlease process " . ($count == 1 ? "this" : "these") . " as soon as possible! 🙏";
                break;
            case "UPCOMING (1-3 DAYS) 📅":
                $messages = $soonMessages;
                $closing = "\nLet's try to get " . ($count == 1 ? "this" : "these") . " processed soon! 🌟";
                break;
            default:
                $messages = $futureMessages;
                $closing = "\nPlease process " . ($count == 1 ? "this" : "these") . " when you can. 😊";
                break;
        }
        
        // Randomly select a message from the appropriate array
        $messageTemplate = $messages[array_rand($messages)];
        
        // If message contains two %s placeholders, use both member and birthday counts
        $message = (substr_count($messageTemplate, '%s') === 2) 
            ? sprintf($messageTemplate, $memberCount, $birthdayCount)
            : sprintf($messageTemplate, $memberCount);
            
        $message .= "\n";
        $message .= "You can process " . ($count == 1 ? "it" : "them") . " here: https://birthday.gold/admin/bgreb_v3/enrollment-listv2\n\n";
        $message .= "[" . implode(', ', $listofusers) . "]";
        $message .= $closing;
        
        if (!$debug) {
            $system->postToRocketChat($message, '@Richard');
            $system->postToRocketChat($message, '@Stephanie');
        }
        return "✅ Message sent for {$timeframe} - {$count} " . ($count == 1 ? "member" : "members");
    }
    return "⚪ No users to notify for {$timeframe}";
}

// Group users based on days until birthday
$todayUsers = [];
$nearFutureUsers = [];
$farFutureUsers = [];

foreach ($users as $user) {
    $days = $user['days_until_birthday'];
    
    if ($days == 0) {
        $todayUsers[] = $user;
    } elseif ($days <= 3) {
        $nearFutureUsers[] = $user;
    } else {
        $farFutureUsers[] = $user;
    }
}

// Send notifications based on timing rules
if (count($todayUsers) > 0) {
    // Today's birthdays - send every 2 hours
    echo sendNotification($todayUsers, "TODAY'S BIRTHDAYS ⚠️", $system, $qik, $debug) . "\n";
    exit;
}

if (($currentHour == 7 || $currentHour == 19) && count($nearFutureUsers) > 0) {
    // 1-3 days away - send at 7AM and 7PM
    echo sendNotification($nearFutureUsers, "UPCOMING (1-3 DAYS) 📅", $system, $qik, $debug) . "\n";
    exit;
}

if (($currentHour == 7 && count($farFutureUsers) > 0)) {
    // 4+ days away - send at 7AM only
    echo sendNotification($farFutureUsers, "FUTURE BIRTHDAYS (4+ DAYS) 🔮", $system, $qik, $debug) . "\n";
} else {
    echo "⚪ No notifications needed at this time\n";
}