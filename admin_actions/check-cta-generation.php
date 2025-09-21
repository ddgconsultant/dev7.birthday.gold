<?php
/**
 * Check if CTA blocks are being generated properly
 */

include('../core/site-controller.php');

echo "<pre>";
echo "=== Checking CTA Block Generation ===\n\n";

// Get a sample notification that should have a CTA block
$notification = $database->getrow(
    "SELECT notification_id, user_id, title, message, status, modify_dt
     FROM bg_user_notifications 
     WHERE type = 'newsletter'
     AND category = 'campaign_191'
     AND status = 'notsent'
     LIMIT 1"
);

if ($notification) {
    echo "Notification ID: {$notification['notification_id']}\n";
    echo "User ID: {$notification['user_id']}\n";
    echo "Status: {$notification['status']}\n\n";
    
    // Check if CTA_BLOCK placeholder is still there or replaced
    if (strpos($notification['message'], '[[CTA_BLOCK]]') !== false) {
        echo "❌ CTA_BLOCK placeholder NOT replaced - still shows [[CTA_BLOCK]]\n";
    } elseif (strpos($notification['message'], '{{CTA_BLOCK}}') !== false) {
        echo "❌ CTA_BLOCK placeholder NOT replaced - still shows {{CTA_BLOCK}}\n";
    } else {
        // Check for actual CTA content
        if (strpos($notification['message'], 'Birthday Rewards') !== false && 
            strpos($notification['message'], 'View Details') !== false || 
            strpos($notification['message'], 'Enroll Now') !== false) {
            echo "✅ CTA block appears to be generated with company content\n";
            
            // Extract and show a portion of the CTA
            preg_match('/<div style="background: #f8f9fa.*?<\/div>\s*<\/div>/s', $notification['message'], $matches);
            if (!empty($matches[0])) {
                echo "\nCTA Block Preview (first 500 chars):\n";
                echo substr(strip_tags($matches[0]), 0, 500) . "...\n";
            }
        } elseif (strpos($notification['message'], 'More birthday rewards coming soon') !== false) {
            echo "⚠️ CTA block shows fallback message (no companies found)\n";
        } else {
            echo "❓ Cannot determine CTA block status\n";
            echo "Checking for CTA-related content in message...\n";
            
            // Show part of the message to debug
            $message_excerpt = substr($notification['message'], 0, 2000);
            echo "\nMessage excerpt:\n" . $message_excerpt . "\n";
        }
    }
    
    // Now let's check what the campaign settings were
    echo "\n=== Campaign Settings ===\n";
    $campaign = $database->getrow(
        "SELECT cta_category, cta_mode 
         FROM mk_campaigns 
         WHERE campaign_id = 191"
    );
    
    echo "CTA Category: " . ($campaign['cta_category'] ?? 'Not set') . "\n";
    echo "CTA Mode: " . ($campaign['cta_mode'] ?? 'Not set') . "\n";
    
    // Check if there are companies in that category
    if (!empty($campaign['cta_category'])) {
        $company_count = $database->getrow(
            "SELECT COUNT(*) as count 
             FROM bg_companies 
             WHERE status = 'finalized' 
             AND display_category = :category",
            ['category' => $campaign['cta_category']]
        );
        
        echo "Companies in category '{$campaign['cta_category']}': " . $company_count['count'] . "\n";
        
        if ($company_count['count'] == 0) {
            echo "⚠️ No companies found in this category - that's why CTA might be empty\n";
        }
    }
} else {
    echo "No personalized notifications found for campaign 191\n";
}

echo "</pre>";
?>