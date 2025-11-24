<?php
/**
 * Migration Script: Create Contact Replies Table
 *
 * This script creates the bg_contact_replies table for storing admin replies
 * to contact form messages.
 *
 * Run this script once with: php create_contact_replies_table.php
 */

// Set DOCUMENT_ROOT if not already set (for CLI execution)
if (!isset($_SERVER['DOCUMENT_ROOT']) || empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "Creating bg_contact_replies table...\n";

$sql = "
CREATE TABLE IF NOT EXISTS bg_contact_replies (
    reply_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    contact_event_id BIGINT NOT NULL COMMENT 'ID from bg_sessiontracking table',
    admin_user_id BIGINT NOT NULL COMMENT 'Admin user who sent the reply',
    admin_username VARCHAR(255) NOT NULL,
    reply_message LONGTEXT NOT NULL,
    recipient_email VARCHAR(255) DEFAULT NULL COMMENT 'Email address to send reply to',
    recipient_user_id BIGINT DEFAULT NULL COMMENT 'User ID if contact was from logged-in user',
    status ENUM('draft', 'sent', 'failed') DEFAULT 'draft',
    sent_via ENUM('email', 'notification', 'both', 'internal') DEFAULT 'internal',
    notification_id BIGINT DEFAULT NULL COMMENT 'ID from bg_user_notifications if sent as notification',
    email_status VARCHAR(50) DEFAULT NULL COMMENT 'Email sending status',
    create_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_dt DATETIME DEFAULT NULL,
    modify_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    metadata TEXT DEFAULT NULL COMMENT 'JSON metadata for additional info',
    INDEX idx_contact_event (contact_event_id),
    INDEX idx_admin_user (admin_user_id),
    INDEX idx_recipient_user (recipient_user_id),
    INDEX idx_status (status),
    INDEX idx_create_dt (create_dt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores admin replies to contact form messages'
";

try {
    $database->exec($sql);
    echo "✓ Table bg_contact_replies created successfully!\n";

    // Verify table exists
    $check = $database->query("SHOW TABLES LIKE 'bg_contact_replies'")->fetch();
    if ($check) {
        echo "✓ Table verified in database\n";

        // Show table structure
        echo "\nTable structure:\n";
        $structure = $database->query("DESCRIBE bg_contact_replies")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($structure as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }

    echo "\n✓ Migration completed successfully!\n";

} catch (Exception $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}
