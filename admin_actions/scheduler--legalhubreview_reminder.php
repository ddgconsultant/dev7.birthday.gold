<?php
// Include site controller
include(dirname(__FILE__) . '/../core/site-controller.php');

// Note: admin_actions directory is protected by host configuration
// No authentication key needed for schedulers

#-------------------------------------------------------------------------------
# LEGAL HUB REVIEW REMINDER SCHEDULER
# Purpose: Send reminders to legal team about policies that need review
# Runs: Daily
# Target: #BG_Legal channel with @Liz mentions
# 
# Tags Format: PHP json_encode'd array stored in tags column
# Example: {"review_period": 180, "other_key": "value"}
# Default review period: 180 days (auto-set if missing)
#-------------------------------------------------------------------------------

// Configuration
$rocketchat_channel = "#testing";  // TODO: Change back to #BG_Legal after testing
$primary_contact = "@Richard";      // TODO: Change back to @Liz after testing
$policy_editor_url = "https://dev7.birthday.gold/staff/legal-policy-editor";

// Get current date for comparison
$current_date = new DateTime();

// Debug mode (set to true for testing)
$debug_mode = isset($_GET['debug']) ? true : false;

#-------------------------------------------------------------------------------
# FETCH POLICIES THAT NEED REVIEW
#-------------------------------------------------------------------------------

// Query for active legal policies (including those without tags)
$sql = "SELECT 
    id,
    name,
    display_name,
    description,
    tags,
    modify_dt,
    category,
    type
FROM bg_content 
WHERE category IN ('Policies', 'legal', 'Legal') 
AND status = 'active'
ORDER BY modify_dt ASC";

if ($debug_mode) {
    echo "=== DEBUG MODE ===\n";
    echo "Query being run:\n";
    echo $sql . "\n\n";
}

try {
    $policies = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_msg = "Error fetching policies: " . $e->getMessage();
    $system->postToRocketChat($error_msg, "#BG-Technical");
    exit;
}

// Count policies that need default tags
$policies_needing_defaults = 0;
foreach ($policies as $policy) {
    if (empty($policy['tags']) || $policy['tags'] === null) {
        $policies_needing_defaults++;
    } else {
        $tags = json_decode($policy['tags'], true);
        if (!$tags || !isset($tags['review_period'])) {
            $policies_needing_defaults++;
        }
    }
}

if ($debug_mode) {
    echo "Found " . count($policies) . " active policies total\n";
    echo "Policies with NULL/empty tags or missing review_period: " . $policies_needing_defaults . "\n\n";
    
    if (count($policies) > 0) {
        echo "Sample of policies found:\n";
        foreach (array_slice($policies, 0, 3) as $p) {
            echo "  - ID: {$p['id']}, Name: {$p['name']}, Category: {$p['category']}, Tags: " . 
                 (empty($p['tags']) ? 'NULL' : substr($p['tags'], 0, 50)) . "\n";
        }
        echo "\n";
    }
}

#-------------------------------------------------------------------------------
# PROCESS EACH POLICY AND CHECK REVIEW PERIODS
#-------------------------------------------------------------------------------

$policies_needing_review = [];
$policies_approaching_review = [];
$policies_overdue = [];
$policies_updated_count = 0;

foreach ($policies as $policy) {
    // Parse the JSON tags to look for review_period
    $tags = json_decode($policy['tags'], true);
    
    // Default to 180 days if no review period is set
    if (!$tags || !isset($tags['review_period'])) {
        $tags = $tags ?: [];
        $tags['review_period'] = 180;
        
        // Update the database with the default review period
        $update_tags_sql = "UPDATE bg_content SET tags = :tags WHERE id = :id";
        $database->query($update_tags_sql, [
            'tags' => json_encode($tags),
            'id' => $policy['id']
        ]);
        
        $policies_updated_count++;
        
        if ($debug_mode) {
            echo "Updated policy ID {$policy['id']} ({$policy['name']}) with default 180-day review period\n";
        }
        
        $review_period_days = 180;
    } else {
        $review_period_days = intval($tags['review_period']);
        if ($review_period_days <= 0) {
            // If invalid, set to default and update
            $tags['review_period'] = 180;
            $update_tags_sql = "UPDATE bg_content SET tags = :tags WHERE id = :id";
            $database->query($update_tags_sql, [
                'tags' => json_encode($tags),
                'id' => $policy['id']
            ]);
            
            $policies_updated_count++;
            
            if ($debug_mode) {
                echo "Updated policy ID {$policy['id']} ({$policy['name']}) - invalid review period replaced with 180 days\n";
            }
            
            $review_period_days = 180;
        }
    }
    
    // Calculate days since last modification
    $modify_date = new DateTime($policy['modify_dt']);
    $days_since_modified = $current_date->diff($modify_date)->days;
    
    // Calculate days until review is due
    $days_until_review = $review_period_days - $days_since_modified;
    
    // Determine urgency level and categorize
    $policy_info = [
        'id' => $policy['id'],
        'name' => $policy['name'],
        'display_name' => $policy['display_name'] ?: $policy['name'],
        'description' => $policy['description'],
        'category' => $policy['category'],
        'type' => $policy['type'],
        'last_modified' => $policy['modify_dt'],
        'review_period' => $review_period_days,
        'days_since_modified' => $days_since_modified,
        'days_until_review' => $days_until_review,
        'edit_url' => $policy_editor_url . "?id=" . $policy['id']
    ];
    
    // Categorize based on urgency
    if ($days_until_review < 0) {
        // Overdue
        $policy_info['days_overdue'] = abs($days_until_review);
        $policies_overdue[] = $policy_info;
    } elseif ($days_until_review <= 7) {
        // Due within a week - HIGH priority
        $policy_info['urgency'] = 'high';
        $policies_needing_review[] = $policy_info;
    } elseif ($days_until_review <= 14) {
        // Due within 2 weeks - MEDIUM priority
        $policy_info['urgency'] = 'medium';
        $policies_approaching_review[] = $policy_info;
    } elseif ($days_until_review <= 30) {
        // Due within a month - LOW priority (weekly reminder)
        $policy_info['urgency'] = 'low';
        // Only include in approaching list on Mondays
        if (date('N') == 1) {
            $policies_approaching_review[] = $policy_info;
        }
    }
    
    if ($debug_mode) {
        echo "Policy: {$policy_info['display_name']}\n";
        echo "  - Review Period: {$review_period_days} days\n";
        echo "  - Days Since Modified: {$days_since_modified}\n";
        echo "  - Days Until Review: {$days_until_review}\n\n";
    }
}

#-------------------------------------------------------------------------------
# DETERMINE MESSAGE FREQUENCY BASED ON URGENCY
#-------------------------------------------------------------------------------

// Overdue policies - send daily
$send_overdue_message = !empty($policies_overdue);

// High priority (due within 7 days) - send daily
$send_high_priority_message = !empty($policies_needing_review);

// Medium/Low priority - send based on day of week
$day_of_week = date('N'); // 1 = Monday, 7 = Sunday
$send_approaching_message = false;

if (!empty($policies_approaching_review)) {
    // For medium priority (7-14 days), send Mon/Wed/Fri
    // For low priority (14-30 days), send Monday only
    if ($day_of_week == 1 || $day_of_week == 3 || $day_of_week == 5) {
        $send_approaching_message = true;
    }
}

#-------------------------------------------------------------------------------
# COMPOSE AND SEND ROCKET.CHAT MESSAGES
#-------------------------------------------------------------------------------

$messages_sent = false;

// Send OVERDUE message (RED ALERT - Daily)
if ($send_overdue_message) {
    $message = ":rotating_light: **OVERDUE POLICY REVIEWS** :rotating_light:\n\n";
    $message .= "Hi {$primary_contact}, the following policies are **OVERDUE** for review:\n\n";
    
    foreach ($policies_overdue as $policy) {
        $message .= ":red_circle: **[{$policy['display_name']}]({$policy['edit_url']})**\n";
        $message .= "   • Category: {$policy['category']} / {$policy['type']}\n";
        $message .= "   • **{$policy['days_overdue']} days overdue** (Review period: {$policy['review_period']} days)\n";
        $message .= "   • Last reviewed: {$policy['last_modified']}\n\n";
    }
    
    $message .= "_Please review these policies immediately. Click the policy name to edit._\n";
    
    $system->postToRocketChat($message, $rocketchat_channel);
    $messages_sent = true;
    
    if ($debug_mode) {
        echo "OVERDUE Message:\n$message\n\n";
    }
}

// Send HIGH PRIORITY message (Due within 7 days - Daily)
if ($send_high_priority_message) {
    $message = ":warning: **URGENT: Policy Reviews Due Soon** :warning:\n\n";
    $message .= "Hi {$primary_contact}, the following policies need review within the next week:\n\n";
    
    foreach ($policies_needing_review as $policy) {
        $icon = $policy['days_until_review'] <= 3 ? ":orange_circle:" : ":yellow_circle:";
        $message .= "{$icon} **[{$policy['display_name']}]({$policy['edit_url']})**\n";
        $message .= "   • Category: {$policy['category']} / {$policy['type']}\n";
        $message .= "   • **Due in {$policy['days_until_review']} days** (Review period: {$policy['review_period']} days)\n";
        $message .= "   • Last reviewed: {$policy['last_modified']}\n\n";
    }
    
    $message .= "_Please prioritize these reviews. Click the policy name to edit._\n";
    
    $system->postToRocketChat($message, $rocketchat_channel);
    $messages_sent = true;
    
    if ($debug_mode) {
        echo "HIGH PRIORITY Message:\n$message\n\n";
    }
}

// Send APPROACHING message (Due within 14-30 days - Mon/Wed/Fri)
if ($send_approaching_message) {
    $message = ":information_source: **Upcoming Policy Reviews** :information_source:\n\n";
    $message .= "Hi {$primary_contact}, the following policies will need review soon:\n\n";
    
    foreach ($policies_approaching_review as $policy) {
        $message .= ":blue_circle: **[{$policy['display_name']}]({$policy['edit_url']})**\n";
        $message .= "   • Category: {$policy['category']} / {$policy['type']}\n";
        $message .= "   • Due in {$policy['days_until_review']} days (Review period: {$policy['review_period']} days)\n";
        $message .= "   • Last reviewed: {$policy['last_modified']}\n\n";
    }
    
    $message .= "_Please plan to review these policies. Click the policy name to edit._\n";
    
    $system->postToRocketChat($message, $rocketchat_channel);
    $messages_sent = true;
    
    if ($debug_mode) {
        echo "APPROACHING Message:\n$message\n\n";
    }
}

// Send all-clear message on Mondays if no policies need review
if ($day_of_week == 1 && !$messages_sent) {
    $message = ":white_check_mark: **All Policies Current** :white_check_mark:\n\n";
    $message .= "Hi {$primary_contact}, all legal policies are up to date. No reviews needed at this time.\n\n";
    $message .= "_Total active policies monitored: " . count($policies) . "_";
    
    $system->postToRocketChat($message, $rocketchat_channel);
    
    if ($debug_mode) {
        echo "ALL CLEAR Message:\n$message\n\n";
    }
}

#-------------------------------------------------------------------------------
# LOG EXECUTION
#-------------------------------------------------------------------------------

if (!$debug_mode) {
    // Log the execution to database
    $log_sql = "INSERT INTO bg_cron_log (cron_name, execution_time, status, message, create_dt) 
                VALUES ('legalhubreview_reminder', NOW(), 'success', :message, NOW())";
    
    $summary = sprintf(
        "Checked %d policies. Overdue: %d, High Priority: %d, Approaching: %d",
        count($policies),
        count($policies_overdue),
        count($policies_needing_review),
        count($policies_approaching_review)
    );
    
    try {
        $database->query($log_sql, ['message' => $summary]);
    } catch (Exception $e) {
        // Silent fail on logging
    }
}

// Output summary for cron log
echo date('Y-m-d H:i:s') . " - Legal Hub Review Reminder executed successfully\n";
echo "Policies checked: " . count($policies) . "\n";
echo "Policies updated with defaults: " . $policies_updated_count . "\n";
echo "Overdue: " . count($policies_overdue) . "\n";
echo "Due Soon: " . count($policies_needing_review) . "\n";
echo "Approaching: " . count($policies_approaching_review) . "\n";
echo "Messages sent: " . ($messages_sent ? "Yes" : "No") . "\n";

if ($debug_mode && $policies_updated_count > 0) {
    echo "\nNote: " . $policies_updated_count . " policies were updated with 180-day review periods.\n";
}

?>