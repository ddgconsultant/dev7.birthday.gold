<?php
// monitor_abo_scheduler.php - Monitor ABO scheduler status and pending tasks
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: text/plain');
echo "ABO Scheduler Monitor\n";
echo "=====================\n\n";

// Check last processed tracking
$tracking_sql = "SELECT config_value, config_data, updated_at 
                 FROM bg_config 
                 WHERE config_type = 'abo_tracking' 
                 AND config_key = 'last_processed'";
$tracking_stmt = $database->query($tracking_sql);
$tracking = $tracking_stmt->fetch(PDO::FETCH_ASSOC);

if ($tracking) {
    $tracking_data = json_decode($tracking['config_data'], true);
    echo "Last Processed Task:\n";
    echo "- Company ID: {$tracking_data['company_id']}\n";
    echo "- Task: {$tracking_data['task_name']}\n";
    echo "- Timestamp: {$tracking_data['timestamp']}\n";
    echo "- Minutes ago: " . round((time() - strtotime($tracking_data['timestamp'])) / 60, 1) . "\n";
} else {
    echo "No tracking data found - scheduler has not run yet\n";
}

echo "\n";

// Count pending tasks
$pending_sql = "
    SELECT 
        COUNT(DISTINCT c.company_id) as companies_with_pending_tasks,
        COUNT(*) as total_pending_tasks
    FROM bg_companies c
    CROSS JOIN bg_config p
    LEFT JOIN bg_company_attributes ca ON 
        c.company_id = ca.company_id 
        AND ca.type = 'onboarding_progress'
        AND ca.name = p.config_key
        AND ca.status = 'active'
    WHERE 
        p.config_type = 'automation_processor'
        AND p.is_active = 1
        AND c.status IN ('pending_review', 'processing', 'active')
        AND c.source = 'user_recommendation'
        AND (ca.description IS NULL OR ca.description = 'pending')";

$pending_stmt = $database->query($pending_sql);
$pending = $pending_stmt->fetch(PDO::FETCH_ASSOC);

echo "Pending Tasks Summary:\n";
echo "- Companies with pending tasks: {$pending['companies_with_pending_tasks']}\n";
echo "- Total pending tasks: {$pending['total_pending_tasks']}\n\n";

// Show next 5 tasks in queue
$queue_sql = "
    SELECT 
        c.company_id,
        c.company_name,
        p.config_key as task_name,
        p.config_value as task_display_name,
        p.display_order,
        c.create_dt as company_created
    FROM bg_companies c
    CROSS JOIN bg_config p
    LEFT JOIN bg_company_attributes ca ON 
        c.company_id = ca.company_id 
        AND ca.type = 'onboarding_progress'
        AND ca.name = p.config_key
        AND ca.status = 'active'
    WHERE 
        p.config_type = 'automation_processor'
        AND p.is_active = 1
        AND c.status IN ('pending_review', 'processing', 'active')
        AND c.source = 'user_recommendation'
        AND (ca.description IS NULL OR ca.description = 'pending')
    ORDER BY 
        c.create_dt ASC,
        p.display_order ASC
    LIMIT 5";

$queue_stmt = $database->query($queue_sql);
$queue = $queue_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Next Tasks in Queue:\n";
echo "--------------------\n";
foreach ($queue as $idx => $task) {
    echo ($idx + 1) . ". {$task['company_name']} (ID: {$task['company_id']})\n";
    echo "   Task: {$task['task_display_name']}\n";
    echo "   Created: {$task['company_created']}\n";
}

// Show recent errors
echo "\nRecent Errors (last 24 hours):\n";
echo "-------------------------------\n";

$errors_sql = "SELECT c.company_name, ca.name, ca.description, ca.create_dt
               FROM bg_company_attributes ca
               JOIN bg_companies c ON ca.company_id = c.company_id
               WHERE ca.type = 'onboarding_error'
               AND ca.create_dt > DATE_SUB(NOW(), INTERVAL 24 HOUR)
               ORDER BY ca.create_dt DESC
               LIMIT 5";

$errors_stmt = $database->query($errors_sql);
$errors = $errors_stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($errors) > 0) {
    foreach ($errors as $error) {
        echo "- {$error['company_name']}: {$error['name']}\n";
        echo "  Error: " . substr($error['description'], 0, 100) . "...\n";
        echo "  Time: {$error['create_dt']}\n";
    }
} else {
    echo "No errors in the last 24 hours\n";
}

echo "\n\nCron Configuration:\n";
echo "-------------------\n";
echo "Add this line to your crontab to run every 3 minutes:\n";
echo "*/3 * * * * curl -s 'https://dev7.birthday.gold/admin_actions/scheduler--process-abo-tasks.php' > /dev/null\n";
?>