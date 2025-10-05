<?php
/*
 * Scheduler Job: Process User Eligibility
 * 
 * Purpose: Batch process user eligibility for stale records
 * Schedule: Run every hour during off-peak times (1 AM - 5 AM)
 * 
 * Usage: /admin_actions/scheduler--process_eligibility.php
 */

// Initialize site
require_once(dirname(__FILE__).'/../core/site-controller.php');

// Set execution limits for batch processing
set_time_limit(300); // 5 minutes max
ini_set('memory_limit', '256M');

// Initialize response
$response = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processed' => 0,
    'errors' => [],
    'stats' => []
];

try {
    // Initialize enrollment class
    require_once($installpath . 'core/classes/class.enrollment.php');
    $enrollment = new Enrollment();
    
    // Check if we are in off-peak hours (1 AM - 5 AM)
    $current_hour = (int)date('G');
    $is_off_peak = ($current_hour >= 1 && $current_hour <= 5);
    
    // Adjust batch size based on time
    $batch_size = $is_off_peak ? 1000 : 100; // Process more during off-peak
    
    // Get stale eligibility records (older than 24 hours)
    $stale_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    // Find records that need processing
    $sql = "SELECT u.user_id, c.company_id, MIN(e.last_checked) as last_checked
            FROM bg_users u
            CROSS JOIN bg_companies c
            LEFT JOIN bg_user_eligibility e 
                ON u.user_id = e.member_id AND c.company_id = e.company_id
            WHERE u.status = 'active' 
            AND c.status = 'finalized'
            AND (e.last_checked IS NULL OR e.last_checked < :stale_date)
            GROUP BY u.user_id, c.company_id
            ORDER BY MIN(CASE WHEN e.last_checked IS NULL THEN 0 ELSE 1 END), MIN(e.last_checked) ASC
            LIMIT :limit";
    
    $params = [
        'stale_date' => $stale_date,
        'limit' => $batch_size
    ];
    
    $records = $database->getrows($sql, $params);
    $response['stats']['records_found'] = count($records);
    
    // Process each record
    foreach ($records as $record) {
        try {
            // Check and store eligibility
            $reason_id = $enrollment->checkAndStoreEligibility(
                $record['user_id'], 
                $record['company_id']
            );
            
            $response['processed']++;
            
            // Track statistics
            if ($reason_id) {
                if (!isset($response['stats']['issues_by_reason'][$reason_id])) {
                    $response['stats']['issues_by_reason'][$reason_id] = 0;
                }
                $response['stats']['issues_by_reason'][$reason_id]++;
            }
            
        } catch (Exception $e) {
            $response['errors'][] = [
                'user_id' => $record['user_id'],
                'company_id' => $record['company_id'],
                'error' => $e->getMessage()
            ];
        }
        
        // Prevent timeout
        if ((time() - $_SERVER['REQUEST_TIME']) > 240) {
            $response['status'] = 'partial';
            $response['message'] = 'Processing stopped to prevent timeout';
            break;
        }
    }
    
    // Get overall statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total_issues,
                    COUNT(DISTINCT member_id) as affected_users,
                    COUNT(DISTINCT company_id) as affected_companies
                 FROM bg_user_eligibility";
    
    $stats = $database->getrow($stats_sql);
    $response['stats']['total_issues'] = $stats['total_issues'];
    $response['stats']['affected_users'] = $stats['affected_users'];
    $response['stats']['affected_companies'] = $stats['affected_companies'];
    
    // Get stale record count
    $stale_sql = "SELECT COUNT(*) as count 
                  FROM bg_user_eligibility 
                  WHERE last_checked < :stale_date";
    
    $stale_result = $database->getrow($stale_sql, ['stale_date' => $stale_date]);
    $stale_count = $stale_result['count'] ?? 0;
    $response['stats']['remaining_stale'] = $stale_count;
    
    // Log to database
    $log_sql = "INSERT INTO bg_scheduler_logs 
                (scheduler_name, run_date, status, records_processed, error_count, runtime_seconds, details)
                VALUES 
                (:name, NOW(), :status, :processed, :errors, :runtime, :details)";
    
    $log_params = [
        'name' => 'process_eligibility',
        'status' => $response['status'],
        'processed' => $response['processed'],
        'errors' => count($response['errors']),
        'runtime' => time() - $_SERVER['REQUEST_TIME'],
        'details' => json_encode($response['stats'])
    ];
    
    $database->query($log_sql, $log_params);
    
    // Clean up old eligibility records for inactive users/companies
    if ($is_off_peak && date('j') == 1) { // First day of month during off-peak
        $cleanup_sql = "DELETE e FROM bg_user_eligibility e
                       LEFT JOIN bg_users u ON e.member_id = u.user_id
                       LEFT JOIN bg_companies c ON e.company_id = c.company_id
                       WHERE u.status != 'active' OR c.status != 'active'";
        
        $cleaned = $database->query($cleanup_sql);
        $response['stats']['cleaned_records'] = $database->affected_rows();
    }
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    error_log('Eligibility processor error: ' . $e->getMessage());
}

// Output response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);

// Send alert if too many stale records
if (isset($response['stats']['remaining_stale']) && $response['stats']['remaining_stale'] > 10000) {
    $alert_message = "Eligibility processor alert: {$response['stats']['remaining_stale']} stale records remaining";
    
    // Send to admin notification system
    if (class_exists('System')) {
        $system = new System($database);
        $system->sendAdminNotification('scheduler_alert', $alert_message, [
            'scheduler' => 'process_eligibility',
            'stale_count' => $response['stats']['remaining_stale']
        ]);
    }
}