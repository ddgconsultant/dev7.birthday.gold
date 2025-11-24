<?php
/**
 * MK Newsletter Personalizer - Test Version
 * Minimal version to test database connection without site-controller
 */

// Set execution limits to prevent timeout
set_time_limit(30); // 30 seconds for testing
ini_set('max_execution_time', 30);
ini_set('memory_limit', '256M');

// Disable output buffering completely for immediate output
@ini_set('output_buffering', 'Off');
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Tell PHP to flush after every output
ob_implicit_flush(true);

// Send headers to prevent buffering
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Disable Nginx buffering

// Output HTML header
echo '<pre style="font-family: monospace; font-size: 12px; line-height: 1.4; background: #f5f5f5; padding: 20px;">';
echo "MK Newsletter Personalizer - Test Mode\n";
echo str_repeat('=', 80) . "\n";
flush();

// Track if we've output a status yet
$statusOutput = false;

// Error handler to catch fatal errors AND ensure status is always output
register_shutdown_function(function() use (&$statusOutput) {
    $error = error_get_last();

    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\n\n=== FATAL ERROR ===\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";

        if (!$statusOutput) {
            echo "\nStatus: Error\n";
            $statusOutput = true;
        }
    } else if (!$statusOutput) {
        echo "\n\nScript terminated unexpectedly without status\n";
        echo "Status: Error\n";
        $statusOutput = true;
    }

    echo '</pre>';
    @flush();
});

try {
    echo "Testing database connection directly...\n";
    flush();

    // Database connection parameters
    $db_host = '10.251.0.14';
    $db_name = 'bg_prod';
    $db_user = 'bguser';
    $db_pass = 'pr_cur3pYHYhcxgV';

    echo "Connecting to database at $db_host...\n";
    flush();

    // Create PDO connection
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Database connection successful\n";
    flush();

    // Test query to check for pending newsletters
    echo "\nChecking for pending newsletters...\n";
    flush();

    $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'notsent' THEN 1 ELSE 0 END) as notsent,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent
            FROM bg_user_notifications
            WHERE type = 'newsletter'";

    $stmt = $pdo->query($sql);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\nNewsletter Queue Status:\n";
    echo "  Total: " . ($stats['total'] ?? 0) . "\n";
    echo "  Pending (need personalization): " . ($stats['pending'] ?? 0) . "\n";
    echo "  Ready to send: " . ($stats['notsent'] ?? 0) . "\n";
    echo "  Already sent: " . ($stats['sent'] ?? 0) . "\n";
    flush();

    // Check for scheduled campaigns
    echo "\nChecking for scheduled campaigns...\n";
    flush();

    $sql2 = "SELECT campaign_id, campaign_name, newsletter_status, status
             FROM mk_campaigns
             WHERE campaign_type = 'newsletter'
             AND newsletter_status IN ('scheduled', 'processing', 'queued')
             ORDER BY create_dt DESC
             LIMIT 5";

    $stmt2 = $pdo->query($sql2);
    $campaigns = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    if (empty($campaigns)) {
        echo "  No active campaigns found\n";
    } else {
        echo "  Active campaigns:\n";
        foreach ($campaigns as $campaign) {
            echo "    - {$campaign['campaign_name']} (ID: {$campaign['campaign_id']}, Status: {$campaign['newsletter_status']})\n";
        }
    }
    flush();

    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Test completed successfully\n";
    echo "\nStatus: Ok\n";
    $statusOutput = true;

} catch (PDOException $e) {
    echo "\n✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nStatus: Error\n";
    $statusOutput = true;
} catch (Exception $e) {
    echo "\n✗ Unexpected error: " . $e->getMessage() . "\n";
    echo "\nStatus: Error\n";
    $statusOutput = true;
}

echo '</pre>';
flush();
exit(0);
?>