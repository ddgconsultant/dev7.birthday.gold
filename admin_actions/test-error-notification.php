<?php
/**
 * Test Error Notification
 * Manually sends a RocketChat notification for pending review fixes
 */

$addClasses[] = 'chat';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get pending review fixes
$sql = "SELECT * FROM bg_auto_error_fixes
        WHERE fix_status = 'pending_review'
        ORDER BY ai_analyzed_dt DESC
        LIMIT 5";

$stmt = $database->query($sql);
$fixes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($fixes)) {
    die("No pending review fixes found\n");
}

// Build notification message (condensed format)
$base_url = 'https://dev7.birthday.gold/admin/error-fix-review.php';

$message = "🤖 **Auto Error Fixer (TEST)**\n\n";
$message .= "🔍 **" . count($fixes) . " Fix(es) Pending Review:**\n\n";

foreach ($fixes as $idx => $fix) {
    $message .= "**#{$fix['fix_id']}** `{$fix['error_file']}:{$fix['error_line']}` ({$fix['ai_confidence']}%)\n";
    $review_url = $base_url . "?token=" . $fix['review_token'];
    $message .= "→ [Review & Approve]({$review_url})\n\n";
}

$message .= "📊 [View All Fixes](https://dev7.birthday.gold/admin/error-fix-dashboard.php)";

// Send notification
echo "Sending notification...\n\n";
echo "Message:\n" . $message . "\n\n";

// Check if $system exists
if (!isset($system)) {
    echo "ERROR: \$system object not found!\n";
    echo "Available variables: " . implode(', ', array_keys(get_defined_vars())) . "\n";
    exit(1);
}

try {
    // Send to BG_Technical channel as Goldie
    echo "Calling \$system->postToRocketChat()...\n";
    echo "  Channel: #BG_Technical\n";
    echo "  Sender: Goldie\n\n";

    $result = $system->postToRocketChat($message, '#BG_Technical', 'Goldie');

    echo "Result type: " . gettype($result) . "\n";
    echo "Result value: ";
    var_dump($result);
    echo "\n";

    // The method might not return anything (void), so check if message was likely sent
    // by checking if no exception was thrown
    echo "✓ Method executed without exception - message should have been sent!\n";
    echo "\nCheck #BG_Technical channel in RocketChat for the message from Goldie.\n";

} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
