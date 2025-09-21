<?php
// Test page for mail failure simulation
$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check for admin access or dev mode
if ($mode !== 'dev' && !$user->is_admin()) {
    die('Access denied. This page is for testing purposes only.');
}

// Handle form submission
$message = '';
$testResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'enable_test_mode':
            $failureRate = (int)($_POST['failure_rate'] ?? 100);
            $mail->setTestFailureMode(true, $failureRate);
            $message = "Test failure mode ENABLED with {$failureRate}% failure rate";
            session_tracking('mail_test_mode_enabled_via_ui', ['failure_rate' => $failureRate]);
            break;

        case 'disable_test_mode':
            $mail->setTestFailureMode(false);
            $message = "Test failure mode DISABLED";
            session_tracking('mail_test_mode_disabled_via_ui', []);
            break;

        case 'send_test_email':
            $to = $_POST['to_email'] ?? 'test@example.com';
            $testSubject = $_POST['subject'] ?? 'Test Email - Failure Simulation';
            $testBody = $_POST['body'] ?? 'This is a test email to verify the failure simulation is working.';

            // Send test email
            $details = [
                'to' => [$to, 'Test Recipient'],
                'subject' => $testSubject,
                'body' => '<h2>' . htmlspecialchars($testSubject) . '</h2><p>' . htmlspecialchars($testBody) . '</p>'
            ];

            $result = $mail->sendmail($details);

            if ($result && (is_array($result) ? ($result['mail_sent'] ?? false) : $result)) {
                $testResults[] = [
                    'status' => 'success',
                    'message' => "Email sent successfully to: $to",
                    'details' => $result
                ];
            } else {
                $testResults[] = [
                    'status' => 'failure',
                    'message' => "Email failed (stored for retry) to: $to",
                    'details' => $result,
                    'test_failure' => isset($result['test_failure']) ? $result['test_failure'] : false
                ];
            }
            break;

        case 'check_failed_emails':
            // Query for failed emails in bg_user_notifications
            $query = "SELECT notification_id, user_id, type, title, status, sent_to, options, create_dt, modify_dt
                      FROM bg_user_notifications
                      WHERE user_id = 0
                      AND status = 'notsent'
                      ORDER BY create_dt DESC
                      LIMIT 10";

            $stmt = $database->prepare($query);
            $stmt->execute();
            $failedEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $testResults[] = [
                'status' => 'info',
                'message' => 'Failed emails in queue:',
                'failed_emails' => $failedEmails
            ];
            break;
    }
}

// Get current test mode status
$currentStatus = $mail->getTestModeStatus();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Failure Test Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .status-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status-enabled {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .status-disabled {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="number"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background: #0056b3;
        }
        button.danger {
            background: #dc3545;
        }
        button.danger:hover {
            background: #c82333;
        }
        button.success {
            background: #28a745;
        }
        button.success:hover {
            background: #218838;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .test-results {
            margin-top: 20px;
        }
        .result-item {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .result-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .result-failure {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .result-info {
            background: #e9ecef;
            border: 1px solid #dee2e6;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .failed-email-item {
            background: #fff;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mail Failure Test Page</h1>

        <!-- Current Status -->
        <div class="status-box <?php echo $currentStatus['enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
            <h3>Current Status</h3>
            <p><strong>Test Mode:</strong> <?php echo $currentStatus['enabled'] ? 'ENABLED' : 'DISABLED'; ?></p>
            <?php if ($currentStatus['enabled']): ?>
                <p><strong>Failure Rate:</strong> <?php echo $currentStatus['failure_rate']; ?>%</p>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="message info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Test Mode Control -->
        <div class="form-section">
            <h2>Test Mode Control</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="failure_rate">Failure Rate (%):</label>
                    <input type="number" id="failure_rate" name="failure_rate" min="0" max="100" value="<?php echo $currentStatus['failure_rate']; ?>">
                </div>
                <button type="submit" name="action" value="enable_test_mode" class="danger">Enable Test Mode</button>
                <button type="submit" name="action" value="disable_test_mode" class="success">Disable Test Mode</button>
            </form>
        </div>

        <!-- Send Test Email -->
        <div class="form-section">
            <h2>Send Test Email</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="to_email">To Email:</label>
                    <input type="email" id="to_email" name="to_email" value="test@example.com" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" value="Test Email - Failure Simulation">
                </div>
                <div class="form-group">
                    <label for="body">Body:</label>
                    <textarea id="body" name="body" rows="4">This is a test email to verify the failure simulation is working.</textarea>
                </div>
                <button type="submit" name="action" value="send_test_email">Send Test Email</button>
            </form>
        </div>

        <!-- Check Failed Emails -->
        <div class="form-section">
            <h2>Failed Email Queue</h2>
            <form method="POST">
                <button type="submit" name="action" value="check_failed_emails">Check Failed Emails</button>
            </form>
        </div>

        <!-- Test Results -->
        <?php if (!empty($testResults)): ?>
            <div class="test-results">
                <h2>Test Results</h2>
                <?php foreach ($testResults as $result): ?>
                    <div class="result-item result-<?php echo $result['status']; ?>">
                        <strong><?php echo ucfirst($result['status']); ?>:</strong>
                        <?php echo htmlspecialchars($result['message']); ?>

                        <?php if (isset($result['test_failure']) && $result['test_failure']): ?>
                            <span style="color: #dc3545;">(Test failure simulated)</span>
                        <?php endif; ?>

                        <?php if (!empty($result['details']) && !isset($result['failed_emails'])): ?>
                            <pre><?php echo htmlspecialchars(print_r($result['details'], true)); ?></pre>
                        <?php endif; ?>

                        <?php if (!empty($result['failed_emails'])): ?>
                            <div style="margin-top: 10px;">
                                <?php foreach ($result['failed_emails'] as $email): ?>
                                    <div class="failed-email-item">
                                        <strong>ID:</strong> <?php echo $email['notification_id']; ?><br>
                                        <strong>To:</strong> <?php echo htmlspecialchars($email['sent_to']); ?><br>
                                        <strong>Subject:</strong> <?php echo htmlspecialchars($email['title']); ?><br>
                                        <strong>Type:</strong> <?php echo htmlspecialchars($email['type']); ?><br>
                                        <strong>Created:</strong> <?php echo $email['create_dt']; ?><br>
                                        <?php if ($email['options']): ?>
                                            <?php $options = json_decode($email['options'], true); ?>
                                            <strong>Failed At:</strong> <?php echo $options['failed_at'] ?? 'Unknown'; ?><br>
                                            <strong>Retry Count:</strong> <?php echo $options['retry_count'] ?? 0; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="form-section" style="background: #f8f9fa;">
            <h2>How to Test</h2>
            <ol>
                <li><strong>Enable Test Mode:</strong> Set a failure rate (e.g., 100% for all emails to fail) and click "Enable Test Mode"</li>
                <li><strong>Send Test Email:</strong> Use the form to send a test email. It will either succeed or fail based on the failure rate.</li>
                <li><strong>Check Failed Queue:</strong> Click "Check Failed Emails" to see emails that failed and are waiting for retry.</li>
                <li><strong>Verify Retry:</strong> The notify-processor will automatically retry these failed emails on its next run.</li>
                <li><strong>Disable When Done:</strong> Always disable test mode when finished testing!</li>
            </ol>

            <h3>Environment Variables</h3>
            <p>You can also control test mode via environment variables:</p>
            <ul>
                <li><code>MAIL_TEST_FAILURE=1</code> - Enable test mode</li>
                <li><code>MAIL_TEST_FAILURE_RATE=50</code> - Set failure rate to 50%</li>
            </ul>

            <h3>PHP Code Usage</h3>
            <pre>// Enable test mode with 75% failure rate
$mail->setTestFailureMode(true, 75);

// Check current status
$status = $mail->getTestModeStatus();

// Disable test mode
$mail->setTestFailureMode(false);</pre>
        </div>
    </div>
</body>
</html>