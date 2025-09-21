<?php
// CLI test script for mail failure simulation
$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "\n=== Mail Failure Test CLI ===\n\n";

// Get current status
$status = $mail->getTestModeStatus();
echo "Current Test Mode Status:\n";
echo "- Enabled: " . ($status['enabled'] ? 'YES' : 'NO') . "\n";
echo "- Failure Rate: " . $status['failure_rate'] . "%\n\n";

// Parse command line arguments
$action = $argv[1] ?? 'help';

switch ($action) {
    case 'enable':
        $rate = $argv[2] ?? 100;
        $mail->setTestFailureMode(true, $rate);
        echo "✓ Test mode ENABLED with {$rate}% failure rate\n";
        break;

    case 'disable':
        $mail->setTestFailureMode(false);
        echo "✓ Test mode DISABLED\n";
        break;

    case 'send':
        $to = $argv[2] ?? 'test@example.com';
        echo "Sending test email to: $to\n";

        $details = [
            'to' => [$to, 'Test User'],
            'subject' => 'CLI Test Email - ' . date('Y-m-d H:i:s'),
            'body' => '<p>This is a test email sent from CLI to test failure simulation.</p>'
        ];

        $result = $mail->sendmail($details);

        if ($result && (is_array($result) ? ($result['mail_sent'] ?? false) : $result)) {
            echo "✓ Email sent successfully!\n";
        } else {
            if (is_array($result) && isset($result['test_failure']) && $result['test_failure']) {
                echo "✗ Email failed (TEST MODE SIMULATED FAILURE)\n";
            } else {
                echo "✗ Email failed (REAL FAILURE)\n";
            }
            echo "  Email has been stored for retry.\n";
        }
        break;

    case 'check':
        echo "Checking failed emails in queue...\n\n";

        $query = "SELECT notification_id, type, title, sent_to, create_dt, options
                  FROM bg_user_notifications
                  WHERE user_id = 0
                  AND status = 'notsent'
                  ORDER BY create_dt DESC
                  LIMIT 5";

        $stmt = $database->prepare($query);
        $stmt->execute();
        $failed = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($failed)) {
            echo "No failed emails in queue.\n";
        } else {
            echo "Found " . count($failed) . " failed email(s):\n";
            echo str_repeat('-', 80) . "\n";

            foreach ($failed as $email) {
                echo "ID: " . $email['notification_id'] . "\n";
                echo "To: " . $email['sent_to'] . "\n";
                echo "Subject: " . $email['title'] . "\n";
                echo "Created: " . $email['create_dt'] . "\n";

                if ($email['options']) {
                    $options = json_decode($email['options'], true);
                    if (isset($options['failed_at'])) {
                        echo "Failed At: " . $options['failed_at'] . "\n";
                    }
                    if (isset($options['retry_count'])) {
                        echo "Retry Count: " . $options['retry_count'] . "\n";
                    }
                }

                echo str_repeat('-', 80) . "\n";
            }
        }
        break;

    case 'clear':
        echo "Clearing failed email queue...\n";

        $query = "DELETE FROM bg_user_notifications
                  WHERE user_id = 0
                  AND status = 'notsent'
                  AND type = 'failed_email'";

        $stmt = $database->prepare($query);
        $affected = $stmt->execute();

        echo "✓ Cleared " . $stmt->rowCount() . " failed email(s)\n";
        break;

    case 'status':
        $status = $mail->getTestModeStatus();
        echo "Test Mode Configuration:\n";
        echo "- Enabled: " . ($status['enabled'] ? 'YES' : 'NO') . "\n";
        echo "- Failure Rate: " . $status['failure_rate'] . "%\n\n";

        // Count failed emails
        $query = "SELECT COUNT(*) as count FROM bg_user_notifications
                  WHERE user_id = 0 AND status = 'notsent'";
        $stmt = $database->prepare($query);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "Failed Emails in Queue: " . $count['count'] . "\n";
        break;

    case 'help':
    default:
        echo "Usage: php test-mail-cli.php [command] [options]\n\n";
        echo "Commands:\n";
        echo "  enable [rate]  - Enable test mode with optional failure rate (0-100, default: 100)\n";
        echo "  disable        - Disable test mode\n";
        echo "  send [email]   - Send a test email (default: test@example.com)\n";
        echo "  check          - Check failed emails in queue\n";
        echo "  clear          - Clear all failed emails from queue\n";
        echo "  status         - Show current test mode status and queue count\n";
        echo "  help           - Show this help message\n\n";
        echo "Examples:\n";
        echo "  php test-mail-cli.php enable 50    # Enable with 50% failure rate\n";
        echo "  php test-mail-cli.php send user@example.com\n";
        echo "  php test-mail-cli.php check\n";
        echo "  php test-mail-cli.php disable\n";
        break;
}

echo "\n";