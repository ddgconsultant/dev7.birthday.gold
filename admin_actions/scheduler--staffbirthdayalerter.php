<?php
// Include site controller
include(dirname(__FILE__) . '/../core/site-controller.php');

// Note: admin_actions directory is protected by host configuration
// No authentication key needed for schedulers

#-------------------------------------------------------------------------------
# STAFF BIRTHDAY ALERTER SCHEDULER
# Purpose: Send reminders about upcoming staff birthdays
# Runs: Daily
# Target: @Richard with birthday notifications
#-------------------------------------------------------------------------------

// Configuration
$notification_recipient = "@Richard";  // Who gets the notifications
$notification_days = [30, 15, 7, 3, 1, 0];  // Days before birthday to send alerts (0 = today)

// Get current date
$current_date = new DateTime();
$current_month = $current_date->format('m');
$current_day = $current_date->format('d');
$current_year = $current_date->format('Y');

// Debug mode
$debug_mode = isset($_GET['debug']) ? true : false;
$list_all = isset($_GET['listall']) ? true : false;
$json_output = isset($_GET['json']) ? true : false;

// Initialize JSON response structure
$json_response = [
    'meta' => [
        'current_date' => $current_date->format('Y-m-d'),
        'mode' => [],
        'timestamp' => date('c')
    ],
    'staff_members' => [],
    'birthdays' => [
        'today' => [],
        'upcoming' => [],
        'this_week' => [],
        'this_month' => [],
        'next_30_days' => []
    ],
    'statistics' => [
        'total_staff' => 0,
        'birthdays_today' => 0,
        'birthdays_this_week' => 0,
        'birthdays_this_month' => 0,
        'birthdays_next_30_days' => 0
    ],
    'by_month' => [],
    'alerts_sent' => []
];

if ($debug_mode) {
    $json_response['meta']['mode'][] = 'debug';
    if (!$json_output) {
        echo "=== STAFF BIRTHDAY ALERTER - DEBUG MODE ===\n";
        echo "Current Date: " . $current_date->format('Y-m-d') . "\n";
        echo "Checking for staff birthdays...\n\n";
    }
}

if ($list_all) {
    $json_response['meta']['mode'][] = 'listall';
}

if ($json_output) {
    $json_response['meta']['mode'][] = 'json';
    header('Content-Type: application/json');
}

#-------------------------------------------------------------------------------
# FETCH STAFF MEMBERS WITH BIRTHDAYS
#-------------------------------------------------------------------------------

// Query for active staff members with birthdays
// Join bg_users with bg_user_attributes to get staff members only
// Handle invalid dates properly - use STR_TO_DATE to avoid strict mode issues
$sql = "SELECT 
    u.user_id,
    u.profile_first_name,
    u.profile_last_name,
    u.profile_username,
    u.birthdate,
    u.email,
    ua.description as staff_role
FROM bg_users u
INNER JOIN bg_user_attributes ua ON u.user_id = ua.user_id
WHERE ua.type = 'staff' 
AND ua.status = 'active'
AND u.birthdate IS NOT NULL
AND STR_TO_DATE(u.birthdate, '%Y-%m-%d') IS NOT NULL
AND YEAR(u.birthdate) > 1900
AND YEAR(u.birthdate) < 2010
AND u.status = 'active'
ORDER BY 
    MONTH(STR_TO_DATE(u.birthdate, '%Y-%m-%d')),
    DAY(STR_TO_DATE(u.birthdate, '%Y-%m-%d'))";

try {
    $staff_members = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_msg = "Error fetching staff birthdays: " . $e->getMessage();
    if ($debug_mode) {
        echo "ERROR: $error_msg\n";
    }
    $system->postToRocketChat($error_msg, "#BG-Technical");
    exit;
}

$json_response['statistics']['total_staff'] = count($staff_members);

if ($debug_mode && !$json_output) {
    echo "Found " . count($staff_members) . " active staff members with birthdays.\n\n";
}

// If list all mode, show all staff and their birthdays
if ($list_all && !$json_output) {
    echo "=== ALL STAFF MEMBERS AND BIRTHDAYS ===\n\n";
    echo str_pad("ID", 8) . str_pad("Name", 30) . str_pad("Username", 20) . str_pad("Role", 25) . str_pad("Birthday", 15) . str_pad("Age", 5) . str_pad("Next Birthday", 15) . "Days Until\n";
    echo str_repeat("-", 138) . "\n";
}

#-------------------------------------------------------------------------------
# PROCESS BIRTHDAYS AND CHECK FOR UPCOMING DATES
#-------------------------------------------------------------------------------

$birthdays_today = [];
$birthdays_upcoming = [];
$birthday_alerts = [];
$all_staff_birthdays = [];

foreach ($staff_members as $staff) {
    // Skip invalid birthdates
    if (empty($staff['birthdate']) || $staff['birthdate'] == '0000-00-00' || $staff['birthdate'] == '00-00-0000') {
        if ($debug_mode || $list_all) {
            echo "Skipping user {$staff['user_id']} ({$staff['profile_first_name']} {$staff['profile_last_name']}) - Invalid birthdate\n";
        }
        continue;
    }
    
    try {
        // Parse the birthdate
        $birthdate = new DateTime($staff['birthdate']);
    } catch (Exception $e) {
        if ($debug_mode || $list_all) {
            echo "Skipping user {$staff['user_id']} ({$staff['profile_first_name']} {$staff['profile_last_name']}) - Cannot parse birthdate: {$staff['birthdate']}\n";
        }
        continue;
    }
    
    $birth_month = $birthdate->format('m');
    $birth_day = $birthdate->format('d');
    
    // Calculate this year's birthday (or next year if already passed)
    $this_year_birthday = new DateTime($current_year . '-' . $birth_month . '-' . $birth_day);
    
    // If birthday has already passed this year, calculate for next year
    if ($this_year_birthday < $current_date) {
        $next_birthday = new DateTime(($current_year + 1) . '-' . $birth_month . '-' . $birth_day);
    } else {
        $next_birthday = $this_year_birthday;
    }
    
    // Calculate days until birthday
    $days_until = $current_date->diff($next_birthday)->days;
    
    // Calculate age they will be
    $age = $next_birthday->format('Y') - $birthdate->format('Y');
    
    // Add staff info with calculated data
    $staff_info = [
        'user_id' => $staff['user_id'],
        'name' => $staff['profile_first_name'] . ' ' . $staff['profile_last_name'],
        'username' => $staff['profile_username'],
        'email' => $staff['email'],
        'role' => $staff['staff_role'],
        'birthdate' => $birthdate->format('F j'),  // e.g., "January 15"
        'birthdate_full' => $birthdate->format('Y-m-d'),  // Full date for listall
        'next_birthday' => $next_birthday->format('Y-m-d'),
        'days_until' => $days_until,
        'turning_age' => $age,
        'current_age' => $current_date->format('Y') - $birthdate->format('Y')
    ];
    
    // Store for listall mode
    $all_staff_birthdays[] = $staff_info;
    
    // Add to JSON response
    if ($json_output || $list_all) {
        $json_response['staff_members'][] = [
            'user_id' => $staff_info['user_id'],
            'name' => trim($staff_info['name']) ?: 'Unknown',
            'username' => $staff_info['username'] ?: 'N/A',
            'email' => $staff_info['email'],
            'role' => $staff_info['role'] ?: 'staff',
            'birthdate' => $staff_info['birthdate'],
            'birthdate_full' => $staff_info['birthdate_full'],
            'next_birthday' => $staff_info['next_birthday'],
            'days_until' => $staff_info['days_until'],
            'turning_age' => $staff_info['turning_age'],
            'current_age' => $staff_info['current_age']
        ];
        
        // Categorize birthdays
        if ($staff_info['days_until'] == 0) {
            $json_response['birthdays']['today'][] = $staff_info['user_id'];
        }
        if ($staff_info['days_until'] <= 7) {
            $json_response['birthdays']['this_week'][] = $staff_info['user_id'];
        }
        if ($staff_info['days_until'] <= 30) {
            $json_response['birthdays']['this_month'][] = $staff_info['user_id'];
            $json_response['birthdays']['next_30_days'][] = [
                'user_id' => $staff_info['user_id'],
                'name' => trim($staff_info['name']) ?: 'Unknown',
                'days_until' => $staff_info['days_until'],
                'birthday' => $staff_info['birthdate']
            ];
        }
        
        // Group by month
        $birth_month = date('F', strtotime($staff_info['birthdate_full']));
        if (!isset($json_response['by_month'][$birth_month])) {
            $json_response['by_month'][$birth_month] = [];
        }
        $json_response['by_month'][$birth_month][] = [
            'user_id' => $staff_info['user_id'],
            'name' => trim($staff_info['name']) ?: 'Unknown',
            'day' => date('j', strtotime($staff_info['birthdate_full']))
        ];
    }
    
    if ($list_all && !$json_output) {
        // Display in table format
        echo str_pad($staff_info['user_id'], 8);
        echo str_pad(substr($staff_info['name'], 0, 28), 30);
        echo str_pad(substr($staff_info['username'] ?: 'N/A', 0, 18), 20);
        echo str_pad(substr($staff_info['role'] ?: 'Staff', 0, 23), 25);
        echo str_pad($staff_info['birthdate'], 15);
        echo str_pad($staff_info['current_age'], 5);
        echo str_pad($next_birthday->format('M d, Y'), 15);
        
        // Color code days until birthday
        if ($days_until == 0) {
            echo "TODAY!";
        } elseif ($days_until <= 7) {
            echo $days_until . " days";
        } elseif ($days_until <= 30) {
            echo $days_until . " days";
        } else {
            echo $days_until . " days";
        }
        echo "\n";
    } elseif ($debug_mode && !$list_all) {
        echo "Staff: {$staff_info['name']} ({$staff_info['username']})\n";
        echo "  Birthday: {$staff_info['birthdate']} (turning {$age})\n";
        echo "  Days until: {$days_until}\n\n";
    }
    
    // Check if we need to send an alert for this birthday
    if (in_array($days_until, $notification_days)) {
        if ($days_until == 0) {
            $birthdays_today[] = $staff_info;
        } else {
            $birthdays_upcoming[$days_until][] = $staff_info;
        }
        $birthday_alerts[] = $staff_info;
    }
}

// Update statistics
$json_response['statistics']['birthdays_today'] = count($birthdays_today);
$json_response['statistics']['birthdays_this_week'] = count(array_filter($all_staff_birthdays, function($s) { return $s['days_until'] <= 7; }));
$json_response['statistics']['birthdays_this_month'] = count(array_filter($all_staff_birthdays, function($s) { return $s['days_until'] <= 30; }));
$json_response['statistics']['birthdays_next_30_days'] = count($json_response['birthdays']['next_30_days']);

// Show summary statistics in listall mode
if ($list_all && !$json_output) {
    echo str_repeat("-", 138) . "\n\n";
    
    // Sort by days until birthday for summary
    usort($all_staff_birthdays, function($a, $b) {
        return $a['days_until'] - $b['days_until'];
    });
    
    echo "=== BIRTHDAY SUMMARY ===\n\n";
    
    // Count birthdays by month
    $birthdays_by_month = [];
    foreach ($all_staff_birthdays as $staff) {
        $month = date('F', strtotime($staff['next_birthday']));
        if (!isset($birthdays_by_month[$month])) {
            $birthdays_by_month[$month] = [];
        }
        $birthdays_by_month[$month][] = $staff;
    }
    
    // Show upcoming birthdays (next 30 days)
    echo "📅 UPCOMING BIRTHDAYS (Next 30 Days):\n";
    echo str_repeat("-", 50) . "\n";
    $upcoming_count = 0;
    foreach ($all_staff_birthdays as $staff) {
        if ($staff['days_until'] <= 30) {
            $upcoming_count++;
            $icon = $staff['days_until'] == 0 ? "🎂" : ($staff['days_until'] <= 7 ? "🎁" : "📅");
            echo $icon . " ";
            echo str_pad($staff['name'], 25);
            echo str_pad($staff['birthdate'], 15);
            if ($staff['days_until'] == 0) {
                echo "TODAY! (turning " . $staff['turning_age'] . ")";
            } else {
                echo "in " . $staff['days_until'] . " days (turning " . $staff['turning_age'] . ")";
            }
            echo "\n";
        }
    }
    if ($upcoming_count == 0) {
        echo "No birthdays in the next 30 days\n";
    }
    
    echo "\n";
    
    // Show birthdays by month
    echo "📊 BIRTHDAYS BY MONTH:\n";
    echo str_repeat("-", 50) . "\n";
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 
               'July', 'August', 'September', 'October', 'November', 'December'];
    foreach ($months as $month) {
        $month_birthdays = [];
        foreach ($all_staff_birthdays as $staff) {
            if (strpos($staff['birthdate'], $month) === 0) {
                $month_birthdays[] = $staff;
            }
        }
        if (!empty($month_birthdays)) {
            echo str_pad($month . ":", 15) . count($month_birthdays) . " birthday(s)\n";
            foreach ($month_birthdays as $staff) {
                echo "  • " . $staff['name'] . " (" . $staff['birthdate'] . ")\n";
            }
        }
    }
    
    echo "\n";
    echo "Total Staff Members: " . count($staff_members) . "\n";
    echo "Birthdays Today: " . count($birthdays_today) . "\n";
    echo "Birthdays This Week: " . count(array_filter($all_staff_birthdays, function($s) { return $s['days_until'] <= 7; })) . "\n";
    echo "Birthdays This Month: " . count(array_filter($all_staff_birthdays, function($s) { return $s['days_until'] <= 30; })) . "\n";
    
    echo "\n=== END LISTALL MODE ===\n";
    if (!$json_output) {
        exit; // Exit after showing the list
    }
}

// If JSON output mode and listall, output JSON and exit
if ($json_output && $list_all) {
    // Sort next_30_days by days_until
    usort($json_response['birthdays']['next_30_days'], function($a, $b) {
        return $a['days_until'] - $b['days_until'];
    });
    
    // Sort by_month entries by day
    foreach ($json_response['by_month'] as $month => &$entries) {
        usort($entries, function($a, $b) {
            return $a['day'] - $b['day'];
        });
    }
    
    echo json_encode($json_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

#-------------------------------------------------------------------------------
# SEND NOTIFICATIONS
#-------------------------------------------------------------------------------

$messages_sent = false;

// Send TODAY's birthday message (highest priority)
if (!empty($birthdays_today) && !$json_output) {
    $message = "🎂 **STAFF BIRTHDAYS TODAY!** 🎂\n\n";
    
    foreach ($birthdays_today as $staff) {
        $message .= "🎉 **{$staff['name']}** ({$staff['username']})\n";
        $message .= "   • Role: {$staff['role']}\n";
        $message .= "   • Turning: {$staff['turning_age']} years old\n";
        $message .= "   • Email: {$staff['email']}\n\n";
    }
    
    $message .= "_Don't forget to wish them a happy birthday!_ 🎈\n";
    
    $system->postToRocketChat($message, $notification_recipient);
    $messages_sent = true;
    
    if ($debug_mode) {
        echo "TODAY'S BIRTHDAY Message:\n$message\n\n";
    }
}

// Send UPCOMING birthday messages
if (!empty($birthdays_upcoming)) {
    // Sort by days until birthday
    ksort($birthdays_upcoming);
    
    foreach ($birthdays_upcoming as $days => $staff_list) {
        $message = "";
        
        // Customize message based on urgency
        if ($days == 1) {
            $message .= "🎂 **Birthday Tomorrow!** 🎂\n\n";
        } elseif ($days == 3) {
            $message .= "📅 **Birthday in 3 Days** 📅\n\n";
        } elseif ($days == 7) {
            $message .= "📆 **Birthdays This Week** 📆\n\n";
        } elseif ($days == 15) {
            $message .= "📅 **Birthdays in 2 Weeks** 📅\n\n";
        } elseif ($days == 30) {
            $message .= "📆 **Birthdays This Month** 📆\n\n";
        }
        
        $message .= "Hi {$notification_recipient}, upcoming staff birthdays:\n\n";
        
        foreach ($staff_list as $staff) {
            $icon = $days <= 3 ? "🎁" : "🎂";
            $message .= "{$icon} **{$staff['name']}** - {$staff['birthdate']}\n";
            $message .= "   • In {$days} days (turning {$staff['turning_age']})\n";
            $message .= "   • Role: {$staff['role']}\n\n";
        }
        
        if ($days <= 7) {
            $message .= "_Time to plan the celebration!_ 🎉\n";
        } else {
            $message .= "_Mark your calendar!_ 📅\n";
        }
        
        $system->postToRocketChat($message, $notification_recipient);
        $messages_sent = true;
        
        if ($debug_mode) {
            echo "UPCOMING ({$days} days) Message:\n$message\n\n";
        }
    }
}

// Send monthly summary on the 1st of each month
if ($current_day == '01' || ($debug_mode && isset($_GET['monthly']))) {
    $message = "📊 **Monthly Birthday Report** 📊\n\n";
    $message .= "Staff birthdays this month:\n\n";
    
    $birthdays_this_month = [];
    foreach ($staff_members as $staff) {
        $birthdate = new DateTime($staff['birthdate']);
        if ($birthdate->format('m') == $current_month) {
            $birthdays_this_month[] = [
                'name' => $staff['profile_first_name'] . ' ' . $staff['profile_last_name'],
                'day' => $birthdate->format('j'),
                'date' => $birthdate->format('F j')
            ];
        }
    }
    
    if (!empty($birthdays_this_month)) {
        // Sort by day of month
        usort($birthdays_this_month, function($a, $b) {
            return $a['day'] - $b['day'];
        });
        
        foreach ($birthdays_this_month as $birthday) {
            $message .= "• **{$birthday['name']}** - {$birthday['date']}\n";
        }
    } else {
        $message .= "_No birthdays this month_\n";
    }
    
    $message .= "\nTotal staff members: " . count($staff_members);
    
    $system->postToRocketChat($message, $notification_recipient);
    
    if ($debug_mode) {
        echo "MONTHLY SUMMARY Message:\n$message\n\n";
    }
}

#-------------------------------------------------------------------------------
# LOG EXECUTION
#-------------------------------------------------------------------------------

// Output summary for cron log
echo date('Y-m-d H:i:s') . " - Staff Birthday Alerter executed successfully\n";
echo "Notification recipient: " . $notification_recipient . " (Rocket.Chat)\n";
echo "Staff members checked: " . count($staff_members) . "\n";
echo "Birthday alerts sent: " . count($birthday_alerts);
if (count($birthday_alerts) > 0) {
    echo " → sent to " . $notification_recipient . "\n";
} else {
    echo "\n";
}
echo "Today's birthdays: " . count($birthdays_today) . "\n";
echo "Upcoming birthdays tracked: " . (count($birthday_alerts) - count($birthdays_today)) . "\n";

// Output JSON if requested (and not already output in listall mode)
if ($json_output && !$list_all) {
    // Add alerts that were sent
    if (!empty($birthday_alerts)) {
        foreach ($birthday_alerts as $alert) {
            $json_response['alerts_sent'][] = [
                'user_id' => $alert['user_id'],
                'name' => $alert['name'],
                'days_until' => $alert['days_until'],
                'type' => $alert['days_until'] == 0 ? 'today' : 'upcoming'
            ];
        }
    }
    
    echo json_encode($json_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($debug_mode) {
    echo "\n=== END DEBUG MODE ===\n";
    echo "Available parameters:\n";
    echo "  ?debug=1         - Show debug output with alerts that would be sent\n";
    echo "  ?listall=1       - List all staff members and their birthdays\n";
    echo "  ?json=1          - Output in JSON format (can combine with other params)\n";
    echo "  ?debug=1&monthly=1 - Test monthly summary message\n";
    echo "\nExamples:\n";
    echo "  ?listall=1&json=1 - Get all staff birthdays in JSON\n";
    echo "  ?debug=1&json=1   - Get debug info in JSON\n";
}

?>