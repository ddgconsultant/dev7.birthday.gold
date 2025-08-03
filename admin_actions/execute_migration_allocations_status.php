<?php
/**
 * Execute migration to change bg_user_allocations.status from ENUM to VARCHAR(32)
 * Run this script once to update the database schema
 */

include(dirname(__FILE__) . '/../core/site-controller.php');

// Verify admin access
if (!$account->checkrole('admin')) {
    die("Access denied. Admin privileges required.\n");
}

echo "Starting migration: bg_user_allocations.status ENUM to VARCHAR(32)\n";
echo "============================================================\n\n";

try {
    // Start transaction
    $database->beginTransaction();
    
    // Read the migration SQL
    $migration_file = $installpath . 'core/dbschema/migration_user_allocations_status_varchar.sql';
    if (!file_exists($migration_file)) {
        throw new Exception("Migration file not found: $migration_file");
    }
    
    $sql_content = file_get_contents($migration_file);
    echo "Migration SQL:\n" . $sql_content . "\n\n";
    
    // Split SQL statements and execute each one
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 60) . "...\n";
            $database->exec($statement);
            echo "✓ Success\n\n";
        }
    }
    
    // Verify the change
    $check_sql = "SHOW COLUMNS FROM bg_user_allocations WHERE Field = 'status'";
    $result = $database->query($check_sql);
    $column_info = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "Column verification:\n";
    echo "Field: " . $column_info['Field'] . "\n";
    echo "Type: " . $column_info['Type'] . "\n";
    echo "Default: " . $column_info['Default'] . "\n\n";
    
    if (strpos($column_info['Type'], 'varchar(32)') !== false) {
        echo "✓ Migration successful! Column is now VARCHAR(32)\n";
        $database->commit();
    } else {
        throw new Exception("Migration verification failed. Column type is: " . $column_info['Type']);
    }
    
} catch (Exception $e) {
    $database->rollBack();
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMigration completed successfully!\n";
echo "You can now use the recommend-business.php page without SQL errors.\n";
?>