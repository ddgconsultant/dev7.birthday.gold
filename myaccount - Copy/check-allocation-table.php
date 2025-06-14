<?php
/**
 * Check if allocation table exists and show structure
 * Development only
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login');
    exit;
}

// Only allow in development mode
if ($mode !== 'dev') {
    header('Location: /myaccount');
    exit;
}

// Check if table exists
$table_check = "SHOW TABLES LIKE 'bg_user_allocations'";
$stmt = $database->prepare($table_check);
$stmt->execute();
$table_exists = $stmt->fetch();

// If table exists, get structure
$columns = [];
if ($table_exists) {
    $column_check = "SHOW COLUMNS FROM bg_user_allocations";
    $stmt = $database->prepare($column_check);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get sample data
$sample_data = [];
if ($table_exists) {
    $sample_sql = "SELECT * FROM bg_user_allocations ORDER BY created_at DESC LIMIT 5";
    try {
        $sample_data = $database->getrows($sample_sql);
    } catch (Exception $e) {
        $sample_error = $e->getMessage();
    }
}

// Page setup
$pagetitle = 'Check Allocation Table';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content">
    <h1>Allocation Table Check</h1>
    
    <div class="card mb-3">
        <div class="card-body">
            <h5>Table Exists: <?php echo $table_exists ? 'YES' : 'NO'; ?></h5>
            
            <?php if (!$table_exists): ?>
                <div class="alert alert-danger">
                    The bg_user_allocations table does not exist! The allocation system requires this table to function.
                </div>
                
                <details>
                    <summary>SQL to create table</summary>
                    <pre><code>CREATE TABLE IF NOT EXISTS `bg_user_allocations` (
  `allocation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `allocation_type` enum('plan','bonus','compensation','referral','campaign','admin','special') NOT NULL,
  `allocation_year` year NOT NULL,
  `amount` int NOT NULL,
  `amount_used` int NOT NULL DEFAULT '0',
  `amount_available` int GENERATED ALWAYS AS (amount - amount_used) STORED,
  `allocation_comment` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `starts_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `first_used_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `fully_used_at` datetime DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `recurring_day` smallint DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `status` enum('active','expired','revoked','depleted') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`allocation_id`),
  KEY `idx_user_year` (`user_id`,`allocation_year`),
  KEY `idx_availability` (`user_id`,`status`,`amount_available`,`expires_at`),
  KEY `idx_type` (`allocation_type`),
  KEY `idx_dates` (`starts_at`,`expires_at`),
  KEY `idx_recurring` (`is_recurring`,`recurring_day`),
  KEY `idx_reference` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;</code></pre>
                </details>
            <?php else: ?>
                <h6 class="mt-3">Table Structure:</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($columns as $col): ?>
                        <tr>
                            <td><?php echo $col['Field']; ?></td>
                            <td><?php echo $col['Type']; ?></td>
                            <td><?php echo $col['Null']; ?></td>
                            <td><?php echo $col['Key']; ?></td>
                            <td><?php echo $col['Default']; ?></td>
                            <td><?php echo $col['Extra']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <h6 class="mt-3">Sample Data:</h6>
                <?php if (isset($sample_error)): ?>
                    <div class="alert alert-danger">Error: <?php echo htmlspecialchars($sample_error); ?></div>
                <?php elseif (empty($sample_data)): ?>
                    <p>No data in table</p>
                <?php else: ?>
                    <pre><?php print_r($sample_data); ?></pre>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="/myaccount/test-addition" class="btn btn-primary">Back to Test Addition</a>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>