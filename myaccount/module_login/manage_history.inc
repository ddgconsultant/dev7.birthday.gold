<?PHP
$additionalstyles .= '
<style>
.timeline-container {
    position: relative;
    padding-left: 2rem;
    margin-top: 1rem;
}

.timeline-card {
    position: relative;
    margin-bottom: 1rem;
    padding: 0.6rem 1rem 0.6rem 2rem; /* Adjust padding for left icon space */
    border: 1px solid rgba(0,0,0,.1);
    border-radius: 6px;
    background-color: #fff;
}

.auto-login-indicator {
    position: absolute;
  
    width: 24px;
    height: 24px;
    font-size: 1.25rem;
    color: #ffc107;
}
.auto-login-indicator {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
}

.auto-login-indicator-empty {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    font-size: 1.25rem;
    color: transparent; /* Invisible placeholder for spacing */
}

.timeline-card:not(:last-child)::after {
    content: "";
    position: absolute;
    left: 1rem;
    top: 100%;
    height: 1rem;
    width: 5px;
    background-color: #dee2e6;
}

.session-badge {
    font-size: 0.8125rem;
    padding: 0.25rem 0.5rem;
    font-weight: 500;
    display: inline-block;
    margin-bottom: 0.25rem;
}

.info-group {
    display: flex;
    align-items: center;
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.125rem;
}

.info-group i {
    width: 16px;
    margin-right: 0.375rem;
    text-align: center;
}

.info-separator {
    margin: 0 0.5rem;
    color: #dee2e6;
}

@media (max-width: 768px) {
    .timeline-card {
        padding: 0.5rem 0.75rem 0.5rem 3rem; /* Match left padding for smaller screens */
    }
    .info-group {
        margin-bottom: 0.25rem;
    }
}
</style>';

echo '<div class="timeline-container">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Login History</h5>
        <a href="/myaccount/loginhistory?view=&act=deletehistory&_token=' . $display->inputcsrf_token('tokenonly') . '" 
           class="btn btn-sm btn-danger">Delete History</a>
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
    echo '<div class="p-3 text-center"><i class="bi bi-slash-circle-fill text-danger me-2"></i><strong>You have no login history.</strong> This is typically because it was recently deleted.</div>';
    return;
}

$first = true;
$second = true;

foreach ($logins as $login) {
    $cardClasses = 'timeline-card';
    $sessionBadge = '';
    $autoLoginIndicator = '<div class="auto-login-indicator-empty"></div>'; // Default empty space

    // Determine session type
    if ($first) {
        $sessionBadge = '<div class="session-badge bg-primary text-white">Current Session</div>';
        $first = false;
    } elseif ($second) {
        $sessionBadge = '<div class="session-badge bg-success text-white">Last Session</div>';
        $second = false;
    }

    // Parse JSON data
    $decoded = !empty($login['description']) ? json_decode($login['description'], true) : [];
    $isValidJson = json_last_error() === JSON_ERROR_NONE && is_array($decoded);

    // Get location and IP
    $location = $isValidJson && !empty($decoded['location'])
        ? implode(', ', array_filter([
            $decoded['location']['city'] ?? '',
            $decoded['location']['region'] ?? '',
            $decoded['location']['country'] ?? ''
        ])) : 'Unknown Location';
    $ipAddress = $isValidJson && !empty($decoded['client_ip'])
        ? substr(htmlspecialchars($decoded['client_ip']), 0, strrpos($decoded['client_ip'], '.')) . '.xxx'
        : 'Unknown IP';

    // Get device info
    $details = $isValidJson && !empty($decoded['agent']) ? $agentparser->getAllDetails($decoded['agent']) : [
        'browser' => 'Unknown Browser',
        'os' => 'Unknown OS',
        'deviceType' => 'Unknown Device'
    ];

    // Check for auto-login
    $device_id = htmlspecialchars($decoded['device_id'] ?? 'N/A');
$autoLoginIndicator = '';
if ($device_id !== 'N/A') {
 #   $autoLoginIndicator = '<div class="auto-login-indicator"><i class="bi bi-shield-check" title="Auto-Logged In"></i></div>';
    $autoLoginIndicator = '<div class="auto-login-indicator">
    <i class="bi bi-shield-check" data-bs-toggle="tooltip" data-bs-placement="top" title="Auto-Login using one of your trusted devices"></i>
</div>';
#    $autoLoginIndicator = '<div class="auto-login-indicator" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);"><i class="bi bi-shield-check" title="Auto-Logged In"></i></div>';
}

echo '<div class="' . $cardClasses . '" style="position: relative;">
       ' . $autoLoginIndicator . '

        <div class="row g-2">
            <div class="col-md-3">
                ' . $sessionBadge . '
                <div class="info-group">
                    <i class="bi bi-clock"></i>
                    ' . date('M j, Y g:i A', strtotime($login['create_dt'])) . '
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-group">
                    <i class="bi bi-geo-alt"></i>
                    <span class="' . ($location === 'Unknown Location' ? 'text-danger' : '') . '">' . $location . '</span>
                </div>
                <div class="info-group">
                    <i class="bi bi-shield-lock"></i>
                    IP: ' . $ipAddress . '
                </div>
            </div>
            <div class="col-md-5">
                <div class="info-group">
                    <i class="bi bi-windows"></i>
                    ' . htmlspecialchars($details['os']) . '
                    <span class="info-separator">|</span>
                    <i class="bi bi-display"></i>
                    ' . htmlspecialchars($details['deviceType']) . '
                </div>
                <div class="info-group">
                    <i class="bi bi-globe"></i>
                    ' . htmlspecialchars($details['browser']) . '
                </div>
            </div>
        </div>
    </div>';

}

echo '</div>';
?>
