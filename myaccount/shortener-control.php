<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Simple control panel for shortener behavior
echo "<h1>URL Shortener Control Panel</h1>";

// Check if we're updating settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_shortener') {
        $enabled = $_POST['enabled'] === '1' ? 1 : 0;
        
        // Store in session since bg_settings table doesn't exist
        $_SESSION['shortener_enabled'] = (bool)$enabled;
        
        // Also try to store in bg_user_attributes for persistence
        $sql = "INSERT INTO bg_user_attributes (user_id, type, name, value, status, create_dt, modify_dt) 
                VALUES (:user_id, 'system_setting', 'shortener_enabled', :value, 'active', NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE value = :value2, modify_dt = NOW()";
        $stmt = $database->prepare($sql);
        $stmt->execute([
            ':user_id' => 0, // System-wide setting
            ':value' => $enabled,
            ':value2' => $enabled
        ]);
        
        echo "<p style='color: green;'>✓ Shortener " . ($enabled ? 'ENABLED' : 'DISABLED') . "</p>";
    }
    
    if ($_POST['action'] === 'clear_logs') {
        $sql = "DELETE FROM bg_errors WHERE type LIKE 'shortener_%'";
        $stmt = $database->prepare($sql);
        $stmt->execute();
        echo "<p style='color: green;'>✓ Shortener logs cleared</p>";
    }
}

// Get current status from config or session
// First check if we have it stored in bg_user_attributes
$sql = "SELECT value FROM bg_user_attributes 
        WHERE user_id = 0 AND type = 'system_setting' AND name = 'shortener_enabled' AND status = 'active'
        LIMIT 1";
try {
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $shortenerEnabled = (bool)$result['value'];
        $_SESSION['shortener_enabled'] = $shortenerEnabled;
    } else {
        // Fall back to session or default
        $shortenerEnabled = $_SESSION['shortener_enabled'] ?? true; // Default to enabled
    }
} catch (Exception $e) {
    // If table doesn't exist or other error, use session
    $shortenerEnabled = $_SESSION['shortener_enabled'] ?? true; // Default to enabled
}

// Get stats
$sql = "SELECT 
    type,
    COUNT(*) as count,
    MAX(create_dt) as last_occurrence
FROM bg_errors 
WHERE type LIKE 'shortener_%' 
GROUP BY type 
ORDER BY last_occurrence DESC";
$stmt = $database->prepare($sql);
$stmt->execute();
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Current Status</h2>
<p>Shortener is currently: <strong style="color: <?= $shortenerEnabled ? 'green' : 'red' ?>"><?= $shortenerEnabled ? 'ENABLED' : 'DISABLED' ?></strong></p>

<h2>Controls</h2>
<form method="POST" style="margin-bottom: 20px;">
    <input type="hidden" name="action" value="toggle_shortener">
    <input type="hidden" name="enabled" value="<?= $shortenerEnabled ? '0' : '1' ?>">
    <button type="submit" style="padding: 10px 20px; font-size: 16px;">
        <?= $shortenerEnabled ? 'Disable Shortener' : 'Enable Shortener' ?>
    </button>
</form>

<form method="POST" style="margin-bottom: 20px;">
    <input type="hidden" name="action" value="clear_logs">
    <button type="submit" style="padding: 10px 20px; font-size: 16px;" onclick="return confirm('Clear all shortener logs?')">
        Clear Shortener Logs
    </button>
</form>

<h2>Shortener Statistics</h2>
<?php if (empty($stats)): ?>
    <p>No shortener activity recorded.</p>
<?php else: ?>
    <table border="1" cellpadding="5" style="border-collapse: collapse;">
        <tr>
            <th>Event Type</th>
            <th>Count</th>
            <th>Last Occurrence</th>
        </tr>
        <?php foreach ($stats as $stat): ?>
            <tr>
                <td><?= htmlspecialchars($stat['type']) ?></td>
                <td><?= $stat['count'] ?></td>
                <td><?= htmlspecialchars($stat['last_occurrence']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Recent Activity (Last 10)</h2>
<?php
$sql = "SELECT * FROM bg_errors WHERE type LIKE 'shortener_%' ORDER BY create_dt DESC LIMIT 10";
$stmt = $database->prepare($sql);
$stmt->execute();
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($recent)) {
    echo "<p>No recent activity.</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Type</th><th>Message</th><th>Time</th></tr>";
    foreach ($recent as $log) {
        $rowColor = '';
        if ($log['type'] == 'shortener_success') {
            $rowColor = 'style="background-color: #e8f5e9;"';
        } elseif (strpos($log['type'], 'fail') !== false || strpos($log['type'], 'error') !== false) {
            $rowColor = 'style="background-color: #ffebee;"';
        }
        
        echo "<tr $rowColor>";
        echo "<td>" . htmlspecialchars($log['type']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($log['message'], 0, 100)) . "...</td>";
        echo "<td>" . htmlspecialchars($log['create_dt']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>

<h2>Test Links</h2>
<ul>
    <li><a href="/myaccount/test-shortener-simple">Simple Shortener Test</a></li>
    <li><a href="/myaccount/test-shortener-email">Email Shortener Test</a></li>
    <li><a href="/myaccount/test-url-shortener">Original URL Shortener Test</a></li>
</ul>