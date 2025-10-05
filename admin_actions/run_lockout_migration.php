<?php
/**
 * LOCKOUT CONSOLIDATION MIGRATION RUNNER
 *
 * This script executes the lockout table consolidation migration
 * Run from command line: php admin_actions/run_lockout_migration.php
 *
 * Migration steps:
 * 1. Create bg_lockout_history table and add tracking columns
 * 2. Copy data to history and consolidate parent records
 * 3. Verify data integrity
 */

// Set up environment
$dir['base'] = __DIR__ . '/..';
require_once($dir['base'] . '/core/site-controller.php');

echo "\n";
echo "========================================================================\n";
echo "LOCKOUT CONSOLIDATION MIGRATION\n";
echo "========================================================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Database: birthday_gold_www\n";
echo "\n";

// Function to execute SQL file
function executeSqlFile($database, $filepath, $step_name) {
    echo "------------------------------------------------------------------------\n";
    echo "STEP: $step_name\n";
    echo "------------------------------------------------------------------------\n";

    if (!file_exists($filepath)) {
        echo "❌ ERROR: File not found: $filepath\n";
        return false;
    }

    $sql = file_get_contents($filepath);

    // Split by semicolons but preserve them in queries
    $statements = array_filter(
        array_map('trim', preg_split('/;(?=(?:[^\'"]|[\'"][^\'"]*[\'"])*$)/', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );

    $success_count = 0;
    $error_count = 0;

    foreach ($statements as $statement) {
        // Skip comments
        if (preg_match('/^--/', trim($statement))) {
            continue;
        }

        try {
            $result = $database->query($statement);

            // If it's a SELECT query, show results
            if (stripos(trim($statement), 'SELECT') === 0) {
                if ($result) {
                    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($rows)) {
                        // Print table header
                        $headers = array_keys($rows[0]);
                        echo "\n";
                        foreach ($headers as $header) {
                            echo str_pad($header, 25) . " | ";
                        }
                        echo "\n" . str_repeat("-", count($headers) * 28) . "\n";

                        // Print rows
                        foreach ($rows as $row) {
                            foreach ($row as $value) {
                                echo str_pad(substr($value ?? 'NULL', 0, 24), 25) . " | ";
                            }
                            echo "\n";
                        }
                        echo "\n";
                    }
                }
            }

            $success_count++;

        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            echo "QUERY: " . substr($statement, 0, 200) . "...\n\n";
            $error_count++;
        }
    }

    echo "\nCompleted: $success_count successful, $error_count errors\n";
    echo "\n";

    return $error_count === 0;
}

// Confirm execution
echo "⚠️  WARNING: This migration will:\n";
echo "  1. Create a new bg_lockout_history table\n";
echo "  2. Add tracking columns to bg_lockout\n";
echo "  3. Create a backup table (bg_lockout_backup_20251004)\n";
echo "  4. Consolidate 742K records down to ~296 parent records\n";
echo "  5. Update application code to use new structure\n";
echo "\n";
echo "Current record count: " . $database->count('bg_lockout', '1=1') . "\n";
echo "\n";
echo "Type 'YES' to proceed or anything else to cancel: ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'YES') {
    echo "\n❌ Migration cancelled by user.\n\n";
    exit(1);
}

echo "\n";
echo "========================================================================\n";
echo "STARTING MIGRATION\n";
echo "========================================================================\n";
echo "\n";

// Step 1: Schema changes
$step1_success = executeSqlFile(
    $database,
    __DIR__ . '/migration_lockout_consolidation_step1_schema.sql',
    'STEP 1: Schema Changes'
);

if (!$step1_success) {
    echo "❌ Migration failed at Step 1. Aborting.\n\n";
    exit(1);
}

// Step 2: Data migration
$step2_success = executeSqlFile(
    $database,
    __DIR__ . '/migration_lockout_consolidation_step2_data.sql',
    'STEP 2: Data Migration'
);

if (!$step2_success) {
    echo "❌ Migration failed at Step 2. You can restore from bg_lockout_backup_20251004.\n\n";
    exit(1);
}

// Step 3: Verification
$step3_success = executeSqlFile(
    $database,
    __DIR__ . '/migration_lockout_consolidation_step3_verify.sql',
    'STEP 3: Verification'
);

echo "\n";
echo "========================================================================\n";
echo "MIGRATION COMPLETE\n";
echo "========================================================================\n";
echo "\n";
echo "✅ All steps completed successfully!\n";
echo "\n";
echo "Next steps:\n";
echo "1. Test the application to ensure lockout functionality works\n";
echo "2. Monitor for 30 days\n";
echo "3. After successful operation, you can drop the backup table:\n";
echo "   DROP TABLE bg_lockout_backup_20251004;\n";
echo "\n";
echo "Code changes have been applied to:\n";
echo "  - core/site-controller.php (lines 827-896)\n";
echo "\n";
echo "Rollback plan (if needed):\n";
echo "  TRUNCATE TABLE bg_lockout;\n";
echo "  INSERT INTO bg_lockout SELECT * FROM bg_lockout_backup_20251004;\n";
echo "  (Then revert code changes in site-controller.php)\n";
echo "\n";
