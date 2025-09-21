<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: text/plain');

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
try {
    // Function to get stats for a specific day window
    function getWindowStats($days, $database) {
        $matrix_query = "SELECT 
            (SELECT COUNT(*) 
             FROM bg_users 
             WHERE type = 'real'
             AND create_dt >= DATE_SUB(NOW(), INTERVAL :days DAY)) as total_signups,
            SUM(CASE WHEN status = 'validated' THEN 1 ELSE 0 END) as validated_count,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
        FROM bg_users 
        WHERE type = 'real'
        AND create_dt >= DATE_SUB(NOW(), INTERVAL :days DAY)";
                         
        $stmt = $database->prepare($matrix_query);
        $stmt->execute(['days' => $days]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Function to format matrix message for a time window
    function formatMatrixMessage($matrix, $days) {
        $total_signups = (int)$matrix['total_signups'];
        if ($total_signups === 0) {
            return sprintf("%dd: no signups", $days);
        }

        $pending_ratio = round(($matrix['pending_count'] / $total_signups) * 100, 1);
        $validated_ratio = round(($matrix['validated_count'] / $total_signups) * 100, 1);
        $active_ratio = round(($matrix['active_count'] / $total_signups) * 100, 1);

        return sprintf("%dd: %d signups (%d pending, %0.1f%%), %d validated (%0.1f%%), %d active (%0.1f%%)",
            $days,
            $total_signups,
            $matrix['pending_count'],
            $pending_ratio,
            $matrix['validated_count'],
            $validated_ratio,
            $matrix['active_count'],
            $active_ratio
        );
    }

    // Get stats for each window
    $windows = [1, 3, 7];
    $stats = [];
    $windows_above_threshold = 0;
    $active_messages = [];

    foreach ($windows as $days) {
        $stats[$days] = getWindowStats($days, $database);
        
        // Calculate active ratio if there are signups
        if ($stats[$days]['total_signups'] > 0) {
            $active_ratio = round(($stats[$days]['active_count'] / $stats[$days]['total_signups']) * 100, 1);
            if ($active_ratio >= 70) {
                $windows_above_threshold++;
            } else {
                $active_messages[] = sprintf("%dd: %0.1f%%", $days, $active_ratio);
            }
        }
    }

    // Build combined matrix message
    $matrix_msg = "";
    foreach ($windows as $days) {
        $matrix_msg .= ($matrix_msg ? " | " : "") . formatMatrixMessage($stats[$days], $days);
    }

    // Determine if we should error
    $is_error = false;
    $status = [];

    // Check days since last signup
    $days_since_last = (int)getWindowStats(1, $database)['total_signups'] === 0 ? 1 : 0;
    if ($days_since_last > 0) {
        $status[] = "no signups in last day";
        $is_error = true;
    }

    // Check if we have at least 2 windows above 70%
    if ($windows_above_threshold < 2) {
        $status[] = "low active conversion in multiple windows: " . implode(", ", $active_messages);
        $is_error = true;
    }

    // Output response
    if ($is_error) {
        http_response_code(500);
        echo "error - " . implode(", ", $status) . " | " . $matrix_msg;
    } else {
        http_response_code(200);
        echo "ok - " . $matrix_msg;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo "error - monitoring script exception: " . $e->getMessage();
}

// Exit early since this is an API endpoint
exit();