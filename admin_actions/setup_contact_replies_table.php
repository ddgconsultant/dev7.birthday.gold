<?php
/**
 * Setup Contact Replies Table
 * Creates a table to store admin replies to contact form submissions
 */

// Set DOCUMENT_ROOT if not set
if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$sql = "
CREATE TABLE IF NOT EXISTS `bg_contact_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_message_id` int(11) NOT NULL COMMENT 'References bg_sessiontracking.id',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'Session ID from original contact',
  `reply_to_email` varchar(255) NOT NULL COMMENT 'Email address to send reply to',
  `reply_subject` varchar(500) DEFAULT NULL,
  `reply_message` text NOT NULL,
  `admin_user_id` int(11) DEFAULT NULL COMMENT 'Admin who sent the reply',
  `admin_username` varchar(255) DEFAULT NULL,
  `status` enum('draft','sent','failed') NOT NULL DEFAULT 'draft',
  `email_sent_dt` datetime DEFAULT NULL,
  `email_error` text DEFAULT NULL,
  `create_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `contact_message_id` (`contact_message_id`),
  KEY `session_id` (`session_id`),
  KEY `reply_to_email` (`reply_to_email`),
  KEY `status` (`status`),
  KEY `create_dt` (`create_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin replies to contact form submissions';
";

try {
    $database->query($sql);
    echo "✓ Table bg_contact_replies created successfully\n";

    // Check if table exists and show structure
    $check = $database->query("SHOW TABLES LIKE 'bg_contact_replies'")->fetch();
    if ($check) {
        echo "✓ Table verified\n";

        // Show columns
        $columns = $database->query("DESCRIBE bg_contact_replies")->fetchAll();
        echo "\nTable structure:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }

} catch (Exception $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "\n";
}
