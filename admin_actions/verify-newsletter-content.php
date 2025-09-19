<?php
/**
 * Verify newsletter content generation
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<pre>";
echo "=== Newsletter Content Verification ===\n\n";

// Get the campaign details
$campaign = $database->getrow("SELECT * FROM mk_campaigns WHERE campaign_id = 191");

echo "Campaign Details:\n";
echo "  Name: " . $campaign['campaign_name'] . "\n";
echo "  CTA Category: " . $campaign['cta_category'] . "\n";
echo "  Original Subject: " . substr($campaign['email_subject'], 0, 100) . "\n\n";

// Get some processed notifications to compare
$notifications_sql = "SELECT n.*, u.first_name, u.birthdate 
                     FROM bg_user_notifications n
                     JOIN bg_users u ON n.user_id = u.user_id
                     WHERE n.type = 'newsletter' 
                     AND n.category = 'campaign_191'
                     AND n.status = 'notsent'
                     ORDER BY n.modify_dt DESC
                     LIMIT 3";

$notifications = $database->getrows($notifications_sql);

if (empty($notifications)) {
    echo "No processed notifications found yet.\n";
} else {
    echo "Processed Notifications (showing title variations):\n\n";
    
    foreach ($notifications as $idx => $notif) {
        $birthYear = date('Y', strtotime($notif['birthdate']));
        $age = date('Y') - $birthYear;
        
        // Determine generation
        if ($age < 27) {
            $generation = 'Gen Z';
        } elseif ($age < 43) {
            $generation = 'Millennial';
        } elseif ($age < 59) {
            $generation = 'Gen X';
        } else {
            $generation = 'Boomer';
        }
        
        echo ($idx + 1) . ". User: " . $notif['first_name'] . " (ID: " . $notif['user_id'] . ", $generation)\n";
        echo "   Subject: " . $notif['title'] . "\n";
        
        // Extract first line of content
        if (!empty($notif['message'])) {
            $doc = new DOMDocument();
            @$doc->loadHTML($notif['message']);
            $body = $doc->getElementsByTagName('body')->item(0);
            if ($body) {
                $text = strip_tags($body->nodeValue);
                $firstLine = strtok($text, "\n");
                echo "   Content Preview: " . substr($firstLine, 0, 100) . "...\n";
            }
        }
        echo "\n";
    }
    
    echo "Analysis:\n";
    echo "- Each generation should have a different tone/style\n";
    echo "- All should mention '" . $campaign['cta_category'] . "' rewards\n";
    echo "- All should relate to campaign: '" . $campaign['campaign_name'] . "'\n";
}

echo "</pre>";
?>