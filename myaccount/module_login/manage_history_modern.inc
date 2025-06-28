<?PHP
// Modern login history with improved mobile experience
$additionalstyles .= '
<style>
/* Modern timeline styles */
.history-container {
    position: relative;
}

.history-entry {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
    position: relative;
}

.history-entry:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-1px);
}

.history-entry.current {
    border-color: #0d6efd;
    background: #f0f8ff;
}

.history-entry.recent {
    border-color: #198754;
}

.session-badge {
    display: inline-block;
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.history-time {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.5rem;
}

.history-details {
    display: grid;
    gap: 0.5rem;
}

.history-detail {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    color: #495057;
}

.history-detail i {
    width: 20px;
    margin-right: 0.5rem;
    color: #6c757d;
}

.auto-login-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #ffc107;
    color: #000;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

@media (max-width: 576px) {
    .history-entry {
        padding: 0.875rem;
    }
    
    .history-detail {
        font-size: 0.813rem;
    }
}
</style>';

echo '<div class="history-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>Login History
        </h5>
        <a href="/myaccount/loginhistory?view=&act=deletehistory&_token=' . $display->inputcsrf_token('tokenonly') . '" 
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm(\'Delete all login history?\');">
           Clear History
        </a>
    </div>';

// Fetch the login history
$sql = "SELECT * FROM bg_logintracking 
        WHERE user_id = :user_id 
        AND `status` = 'A' 
        AND `site` = :site 
        ORDER BY modify_dt DESC, id DESC 
        LIMIT 0, 100";
$stmt = $database->prepare($sql);
$stmt->execute([
    ':user_id' => $workinguserdata['user_id'],
    ':site' => $site
]);

$logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($logins)) {
    echo '
    <div class="text-center py-5">
        <i class="bi bi-clock-history display-4 text-muted"></i>
        <p class="mt-3 mb-0 text-muted">No login history available.</p>
        <p class="text-muted"><small>Your login history may have been recently cleared.</small></p>
    </div>';
    echo '</div>';
    return;
}

$entry_count = 0;
foreach ($logins as $login) {
    $entry_count++;
    $entryClasses = 'history-entry';
    $sessionBadge = '';
    
    // Determine session type
    if ($entry_count === 1) {
        $entryClasses .= ' current';
        $sessionBadge = '<span class="session-badge bg-primary text-white">Current Session</span>';
    } elseif ($entry_count === 2) {
        $entryClasses .= ' recent';
        $sessionBadge = '<span class="session-badge bg-success text-white">Previous Session</span>';
    }
    
    // Parse JSON data
    $decoded = !empty($login['description']) ? json_decode($login['description'], true) : [];
    $isValidJson = json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    
    // Get location and IP
    $location = 'Unknown Location';
    if ($isValidJson && !empty($decoded['location'])) {
        $locationParts = array_filter([
            $decoded['location']['city'] ?? '',
            $decoded['location']['region'] ?? '',
            $decoded['location']['country'] ?? ''
        ]);
        if (!empty($locationParts)) {
            $location = implode(', ', $locationParts);
        }
    }
    
    $ipAddress = 'Unknown IP';
    if ($isValidJson && !empty($decoded['client_ip'])) {
        $ipParts = explode('.', $decoded['client_ip']);
        if (count($ipParts) >= 3) {
            $ipAddress = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.xxx';
        }
    }
    
    // Get device info
    $details = $isValidJson && !empty($decoded['agent']) ? $agentparser->getAllDetails($decoded['agent']) : [
        'browser' => 'Unknown Browser',
        'os' => 'Unknown OS',
        'deviceType' => 'Unknown Device'
    ];
    
    // Check for auto-login
    $device_id = $decoded['device_id'] ?? null;
    $isAutoLogin = !empty($device_id) && $device_id !== 'N/A';
    
    // Format date/time
    $loginTime = new DateTime($login['create_dt']);
    $formattedDate = $loginTime->format('M j, Y');
    $formattedTime = $loginTime->format('g:i A');
    
    echo '<div class="' . $entryClasses . '">
            ' . ($isAutoLogin ? '<div class="auto-login-badge" title="Auto-login from trusted device">
                <i class="bi bi-shield-check"></i>
            </div>' : '') . '
            
            ' . $sessionBadge . '
            
            <div class="history-time">
                ' . $formattedDate . ' at ' . $formattedTime . '
            </div>
            
            <div class="history-details">
                <div class="history-detail">
                    <i class="bi bi-geo-alt' . ($location === 'Unknown Location' ? ' text-danger' : '') . '"></i>
                    ' . htmlspecialchars($location) . '
                </div>
                <div class="history-detail">
                    <i class="bi bi-wifi"></i>
                    IP: ' . htmlspecialchars($ipAddress) . '
                </div>
                <div class="history-detail">
                    <i class="bi bi-laptop"></i>
                    ' . htmlspecialchars($details['deviceType']) . ' • ' . htmlspecialchars($details['os']) . '
                </div>
                <div class="history-detail">
                    <i class="bi bi-globe"></i>
                    ' . htmlspecialchars($details['browser']) . '
                </div>
            </div>
          </div>';
}

echo '</div>';
?>