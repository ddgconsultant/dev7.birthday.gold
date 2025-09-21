<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Note: admin_actions directory is protected by host configuration
// No authentication key needed for schedulers

#-------------------------------------------------------------------------------
# PAYDAY REMINDER SCHEDULER
# Purpose: Send reminders to @richard about upcoming payday (1st of month)
# Runs: Daily
# Alert: 3 days or less before end of month - alert once per day
#-------------------------------------------------------------------------------

// Configuration
$notification_recipient = "@richard";  // Who gets the notifications
$debug_mode = isset($_GET['debug']) ? true : false;

// Get current date information
$current_date = new DateTime();
$current_year = $current_date->format('Y');
$current_month = $current_date->format('n'); // 1-12 without leading zeros
$current_day = $current_date->format('j');   // 1-31 without leading zeros

// Calculate last day of current month
$last_day_of_month = date('t', strtotime($current_date->format('Y-m-01')));

// Calculate days remaining in month
$days_until_end_of_month = $last_day_of_month - $current_day;

// Log execution start
$qik->logmessage("Starting payday reminder script");
$qik->logmessage("Current date: " . $current_date->format('Y-m-d'));
$qik->logmessage("Last day of month: $last_day_of_month");
$qik->logmessage("Days until end of month: $days_until_end_of_month");

if ($debug_mode) {
    echo "=== PAYDAY REMINDER - DEBUG MODE ===\n";
    echo "Current Date: " . $current_date->format('Y-m-d') . "\n";
    echo "Current Day: $current_day\n";
    echo "Last Day of Month: $last_day_of_month\n";
    echo "Days Until End of Month: $days_until_end_of_month\n\n";
}

// Check if we should send a payday reminder
// Send alert if 3 or fewer days before end of month
if ($days_until_end_of_month <= 3) {
    
    // Calculate next payday date (1st of next month)
    $next_month = $current_month == 12 ? 1 : $current_month + 1;
    $next_year = $current_month == 12 ? $current_year + 1 : $current_year;
    $payday_date = new DateTime("$next_year-$next_month-01");
    
    $formatted_payday = $payday_date->format('F 1, Y'); // e.g., "January 1, 2025"
    
    // Different messages based on urgency
    $message = "";
    $emoji = "";
    
    if ($days_until_end_of_month == 0) {
        // Last day of month - payday is tomorrow!
        $emoji = "💰";
        $message = "$emoji **PAYDAY TOMORROW!** $emoji\n\n";
        $message .= "Hi Richard, just a friendly reminder that tomorrow ($formatted_payday) is payday! 💸\n\n";
        $message .= "Time to celebrate - the first of the month is here! 🎉";
        
    } elseif ($days_until_end_of_month == 1) {
        // 2 days until payday
        $emoji = "💳";
        $message = "$emoji **Payday in 2 Days** $emoji\n\n";
        $message .= "Hi Richard, payday ($formatted_payday) is just 2 days away! 💰\n\n";
        $message .= "The first of the month is almost here! 📅";
        
    } elseif ($days_until_end_of_month == 2) {
        // 3 days until payday  
        $emoji = "📅";
        $message = "$emoji **Payday in 3 Days** $emoji\n\n";
        $message .= "Hi Richard, payday ($formatted_payday) is coming up in 3 days! 💰\n\n";
        $message .= "The first of the month is just around the corner! 🗓️";
        
    } elseif ($days_until_end_of_month == 3) {
        // 4 days until payday (still within 3-day window)
        $emoji = "🗓️";
        $message = "$emoji **Payday This Week** $emoji\n\n";
        $message .= "Hi Richard, payday ($formatted_payday) is coming up this week! 💰\n\n";
        $message .= "The first of the month will be here soon! 📆";
    }
    
    // Send the message
    if (!empty($message)) {
        $qik->logmessage("Sending payday reminder to $notification_recipient");
        $qik->logmessage("Message: " . str_replace("\n", " | ", $message));
        
        if ($debug_mode) {
            echo "PAYDAY REMINDER Message:\n$message\n\n";
            echo "Would send to: $notification_recipient\n";
        } else {
            // Send to Rocket.Chat
            $system->postToRocketChat($message, $notification_recipient);
        }
    }
    
} else {
    // Not within the 3-day window
    $qik->logmessage("Not within 3-day payday reminder window (days remaining: $days_until_end_of_month)");
    
    if ($debug_mode) {
        echo "Not within 3-day reminder window.\n";
        echo "Days remaining in month: $days_until_end_of_month\n";
        echo "No message sent.\n";
    }
}

// Log execution completion
$qik->logmessage("Payday reminder script completed");

// Output summary for cron log
echo date('Y-m-d H:i:s') . " - Payday Reminder executed successfully\n";
echo "Notification recipient: $notification_recipient (Rocket.Chat)\n";
echo "Current date: " . $current_date->format('Y-m-d') . "\n";
echo "Days until end of month: $days_until_end_of_month\n";

if ($days_until_end_of_month <= 3) {
    $next_month = $current_month == 12 ? 1 : $current_month + 1;
    $next_year = $current_month == 12 ? $current_year + 1 : $current_year;
    $payday_date = new DateTime("$next_year-$next_month-01");
    echo "Payday reminder sent for: " . $payday_date->format('F 1, Y') . "\n";
} else {
    echo "No reminder sent (outside 3-day window)\n";
}

if ($debug_mode) {
    echo "\n=== END DEBUG MODE ===\n";
    echo "Available parameters:\n";
    echo "  ?debug=1 - Show debug output with message that would be sent\n";
    echo "\nThis scheduler will send daily reminders to @richard when:\n";
    echo "  - 3 days before end of month\n";
    echo "  - 2 days before end of month\n";
    echo "  - 1 day before end of month\n";
    echo "  - Last day of month (payday tomorrow!)\n";
}

?>