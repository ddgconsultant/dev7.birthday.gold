<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin only access
if (!$account->isadmin()) {
    header('Location: /myaccount/');
    exit;
}

$pagetitle = "Setup Contact Replies System";
$page_title = "Setup Contact Replies - Birthday.Gold Admin";
$page_description = "One-time setup for contact message reply system";

$message = '';
$error = '';

// Handle table creation
if ($app->formposted() && isset($_POST['action']) && $_POST['action'] === 'create_table') {
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
        $message = 'Table bg_contact_replies created successfully!';
    } catch (Exception $e) {
        $error = 'Error creating table: ' . $e->getMessage();
    }
}

// Check if table exists
$table_exists = false;
try {
    $check = $database->query("SHOW TABLES LIKE 'bg_contact_replies'")->fetch();
    $table_exists = ($check !== false);
} catch (Exception $e) {
    $error = 'Error checking table: ' . $e->getMessage();
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="content-header-admin">
    <div class="container">
        <h1><i class="bi bi-gear me-2"></i>Setup Contact Replies System</h1>
        <p class="lead">One-time database setup for the contact message reply feature</p>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-database me-2"></i>Database Setup Status</h5>
                </div>
                <div class="card-body">
                    <?php if ($table_exists): ?>
                    <div class="alert alert-success">
                        <h5 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Setup Complete!</h5>
                        <p>The <code>bg_contact_replies</code> table already exists and is ready to use.</p>
                        <hr>
                        <a href="/admin/contact-messages" class="btn btn-success">
                            <i class="bi bi-envelope me-2"></i>Go to Contact Messages
                        </a>
                    </div>

                    <?php
                    // Show table structure
                    try {
                        $structure = $database->query("DESCRIBE bg_contact_replies")->fetchAll(PDO::FETCH_ASSOC);
                        echo '<h6 class="mt-4">Table Structure:</h6>';
                        echo '<div class="table-responsive"><table class="table table-sm table-bordered">';
                        echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead><tbody>';
                        foreach ($structure as $col) {
                            echo '<tr>';
                            echo '<td><code>' . htmlspecialchars($col['Field']) . '</code></td>';
                            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                            echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                            echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                            echo '<td>' . htmlspecialchars($col['Default'] ?? '') . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table></div>';
                    } catch (Exception $e) {
                        echo '<div class="alert alert-warning">Could not fetch table structure: ' . $e->getMessage() . '</div>';
                    }
                    ?>

                    <?php else: ?>
                    <div class="alert alert-warning">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Setup Required</h5>
                        <p>The <code>bg_contact_replies</code> table does not exist yet.</p>
                        <p>Click the button below to create it now.</p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="create_table">
                        <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Create the bg_contact_replies table?')">
                            <i class="bi bi-plus-circle me-2"></i>Create Table Now
                        </button>
                    </form>

                    <div class="mt-4">
                        <h6>What this will create:</h6>
                        <ul>
                            <li>Table name: <code>bg_contact_replies</code></li>
                            <li>Stores admin replies to contact form messages</li>
                            <li>Tracks email and notification delivery</li>
                            <li>Links to bg_sessiontracking and bg_user_notifications</li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3">
                <a href="/admin" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Admin
                </a>
            </div>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
