<?php
/**
 * Auto Error Fixer - Setup/Installation Script
 * Run this once to create the database tables and configuration
 *
 * URL: /admin_actions/setup_auto_error_fixer.php
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin check
if (!$account->isadmin()) {
    die("Admin access required");
}

header('Content-Type: text/plain; charset=utf-8');

echo "═══════════════════════════════════════════════════════\n";
echo "  AUTO ERROR FIXER - SETUP SCRIPT\n";
echo "═══════════════════════════════════════════════════════\n\n";

try {
    // Read SQL file
    $sql_file = __DIR__ . '/setup_auto_error_fixer.sql';

    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: {$sql_file}");
    }

    $sql_content = file_get_contents($sql_file);

    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        fn($s) => !empty($s) && !preg_match('/^\s*--/', $s)
    );

    echo "Found " . count($statements) . " SQL statements to execute\n\n";

    $executed = 0;
    $failed = 0;

    foreach ($statements as $idx => $statement) {
        $num = $idx + 1;

        // Extract statement type
        if (preg_match('/^\s*(CREATE|INSERT|ALTER|DROP)/i', $statement, $matches)) {
            $type = strtoupper($matches[1]);
            echo "[{$num}] Executing {$type}... ";

            try {
                $database->exec($statement . ';');
                echo "✓ Success\n";
                $executed++;
            } catch (PDOException $e) {
                // Check if it's just "already exists" error
                if (strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "⚠ Already exists (skipped)\n";
                    $executed++;
                } else {
                    echo "✗ Failed: {$e->getMessage()}\n";
                    $failed++;
                }
            }
        }
    }

    echo "\n═══════════════════════════════════════════════════════\n";
    echo "  SETUP SUMMARY\n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "Executed: {$executed}\n";
    echo "Failed: {$failed}\n";
    echo "\n";

    if ($failed === 0) {
        echo "✓ Setup completed successfully!\n\n";

        echo "NEXT STEPS:\n";
        echo "1. Configure Uptime Kuma monitor:\n";
        echo "   URL: " . $sitesettings['site_url'] . "/admin_actions/scheduler--auto-error-fixer.php?key=" . $sitesettings['scheduler_key'] . "\n";
        echo "   Interval: Every 8 hours\n";
        echo "   Keyword: STATUS: SUCCESS\n\n";

        echo "2. View dashboard:\n";
        echo "   " . $sitesettings['site_url'] . "/admin/error-fix-dashboard.php\n\n";

        echo "3. Test the scheduler manually:\n";
        echo "   " . $sitesettings['site_url'] . "/admin_actions/scheduler--auto-error-fixer.php?key=" . $sitesettings['scheduler_key'] . "\n\n";

    } else {
        echo "⚠ Setup completed with errors. Please review the failed statements above.\n";
    }

} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
