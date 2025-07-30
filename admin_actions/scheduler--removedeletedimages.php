<?php
/**
 * Scheduler: Remove Deleted Images
 * 
 * This job handles the permanent deletion of soft-deleted company logo images.
 * - Warns at 60 days after soft deletion
 * - Sends reminders every 30 days
 * - Permanently deletes at 120 days
 * - Reports to RocketChat #bg_technical channel
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Configuration
$DAYS_UNTIL_DELETE = 120;
$FIRST_WARNING_DAYS = 60;
$REMINDER_INTERVAL = 30;
$ROCKETCHAT_CHANNEL = '#bg_technical';

// Initialize results array
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'warnings_sent' => 0,
    'files_deleted' => 0,
    'errors' => []
];

try {
    // Get current date
    $now = new DateTime();
    
    // Find all soft-deleted logos
    $sql = "SELECT ca.*, c.company_name 
            FROM bg_company_attributes ca
            LEFT JOIN bg_companies c ON ca.company_id = c.company_id
            WHERE ca.category = 'company_logos' 
            AND ca.status = 'inactive'
            AND ca.modify_dt IS NOT NULL
            ORDER BY ca.modify_dt ASC";
    
    $stmt = $database->query($sql);
    $deleted_logos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group logos by deletion timeline
    $warning_60_days = [];
    $warning_30_days = [];
    $delete_now = [];
    
    foreach ($deleted_logos as $logo) {
        $deleted_date = new DateTime($logo['modify_dt']);
        $days_deleted = $now->diff($deleted_date)->days;
        
        if ($days_deleted >= $DAYS_UNTIL_DELETE) {
            // 120+ days - delete now
            $delete_now[] = array_merge($logo, ['days_deleted' => $days_deleted]);
        } elseif ($days_deleted >= 90) {
            // 90-119 days - 30 day warning
            $warning_30_days[] = array_merge($logo, ['days_deleted' => $days_deleted]);
        } elseif ($days_deleted >= $FIRST_WARNING_DAYS) {
            // 60-89 days - 60 day warning
            $warning_60_days[] = array_merge($logo, ['days_deleted' => $days_deleted]);
        }
    }
    
    // Build RocketChat message
    $message = "🗑️ **Company Logo Deletion Report**\n";
    $message .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    // 60-day warnings
    if (!empty($warning_60_days)) {
        $message .= "⚠️ **Will be deleted in ~60 days:**\n";
        foreach ($warning_60_days as $logo) {
            $days_remaining = $DAYS_UNTIL_DELETE - $logo['days_deleted'];
            $message .= "- Company: {$logo['company_name']} (ID: {$logo['company_id']})\n";
            $message .= "  File: {$logo['description']}\n";
            $message .= "  Deleted: {$logo['days_deleted']} days ago | Remaining: {$days_remaining} days\n\n";
        }
        $results['warnings_sent'] += count($warning_60_days);
    }
    
    // 30-day warnings
    if (!empty($warning_30_days)) {
        $message .= "⚠️⚠️ **Will be deleted in ~30 days:**\n";
        foreach ($warning_30_days as $logo) {
            $days_remaining = $DAYS_UNTIL_DELETE - $logo['days_deleted'];
            $message .= "- Company: {$logo['company_name']} (ID: {$logo['company_id']})\n";
            $message .= "  File: {$logo['description']}\n";
            $message .= "  Deleted: {$logo['days_deleted']} days ago | Remaining: {$days_remaining} days\n\n";
        }
        $results['warnings_sent'] += count($warning_30_days);
    }
    
    // Process permanent deletions
    if (!empty($delete_now)) {
        $message .= "🗑️ **PERMANENTLY DELETED TODAY:**\n";
        
        foreach ($delete_now as $logo) {
            try {
                // Build file path
                $file_path = "/mnt/w/BIRTHDAY_SERVER/cdn.birthday.gold/public/images/company_images/{$logo['company_id']}/{$logo['description']}";
                
                // Delete file if it exists
                $file_deleted = false;
                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        $file_deleted = true;
                    }
                }
                
                // Delete database record
                $delete_sql = "DELETE FROM bg_company_attributes WHERE attribute_id = :attribute_id";
                $delete_stmt = $database->prepare($delete_sql);
                $delete_stmt->execute(['attribute_id' => $logo['attribute_id']]);
                
                // Add to message
                $message .= "- Company: {$logo['company_name']} (ID: {$logo['company_id']})\n";
                $message .= "  File: {$logo['description']}\n";
                $message .= "  Soft-deleted: {$logo['days_deleted']} days ago\n";
                $message .= "  File removed: " . ($file_deleted ? "Yes" : "No - file not found") . "\n\n";
                
                $results['files_deleted']++;
                
                // Log the permanent deletion
                $log_sql = "INSERT INTO bg_company_attributes (company_id, category, type, name, description, status, create_dt) 
                           VALUES (:company_id, 'audit_log', 'logo_permanent_delete', 'logo_permanently_deleted', :description, 'active', NOW())";
                $log_stmt = $database->prepare($log_sql);
                $log_stmt->execute([
                    'company_id' => $logo['company_id'],
                    'description' => "Logo permanently deleted: {$logo['description']} (attribute_id: {$logo['attribute_id']}) after {$logo['days_deleted']} days"
                ]);
                
            } catch (Exception $e) {
                $results['errors'][] = "Failed to delete logo {$logo['attribute_id']}: " . $e->getMessage();
            }
        }
    }
    
    // Only send message if there's something to report
    if (!empty($warning_60_days) || !empty($warning_30_days) || !empty($delete_now)) {
        // Send to RocketChat
        try {
            $chat = new Chat();
            $chat->sendRocketChatMessage($message);
        } catch (Exception $e) {
            // Fallback: log the message
            error_log("RocketChat message error: " . $e->getMessage());
            error_log("Message content:\n" . $message);
        }
    }
    
    // Summary
    $results['summary'] = [
        'total_inactive_logos' => count($deleted_logos),
        '60_day_warnings' => count($warning_60_days),
        '30_day_warnings' => count($warning_30_days),
        'deleted_today' => count($delete_now)
    ];
    
} catch (Exception $e) {
    $results['errors'][] = $e->getMessage();
}

// Output results
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);