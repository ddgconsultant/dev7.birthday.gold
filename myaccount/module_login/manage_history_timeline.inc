<?PHP
// Timeline-style login history with dynamic scale
$additionalstyles .= '
<style>
/* Timeline styles */
.timeline-container {
    position: relative;
    padding-left: 50px;
    margin-top: 2rem;
}

/* Vertical line */
.timeline-line {
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, #e9ecef 0%, #dee2e6 100%);
}

/* Timeline period (year/month) */
.timeline-period {
    position: relative;
    margin-bottom: 3rem;
}

.timeline-period-marker {
    background: #dc3545;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    z-index: 2;
    display: inline-block;
    margin-bottom: 1.5rem;
    margin-left: -31px; /* Touch the timeline */
    position: relative;
}

/* Timeline dot for each day/period */
.timeline-dot {
    position: absolute;
    left: -34px;
    width: 16px;
    height: 16px;
    background: #fff;
    border: 3px solid #6c757d;
    border-radius: 50%;
    z-index: 1;
    cursor: pointer;
    transition: all 0.3s ease;
}

.timeline-dot:hover,
.timeline-dot.active {
    transform: scale(1.3);
    border-color: #0d6efd;
    background: #0d6efd;
}

.timeline-dot.has-multiple {
    border-color: #ffc107;
    background: #ffc107;
}

/* Timeline entry cards */
.timeline-entries {
    margin-bottom: 2rem;
    padding-left: 22px;
}

.timeline-date-group {
    position: relative;
    margin-bottom: 2rem;
}

.timeline-date-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.timeline-entry {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    margin-left: -8px;
    position: relative;
    transition: all 0.2s ease;
}

.timeline-entry::before {
    content: "";
    position: absolute;
    left: -58px;
    top: 50%;
    transform: translateY(-50%);
    width: 58px;
    height: 2px;
    background: #dee2e6;
}

.timeline-entry:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateX(4px);
}

.timeline-entry.current {
    border-color: #0d6efd;
    background: #f0f8ff;
}

.entry-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.entry-time {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
    white-space: nowrap;
}

.entry-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    font-size: 0.813rem;
    color: #495057;
    flex: 1;
    min-width: 0;
}

.entry-detail {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}

.entry-detail i {
    color: #6c757d;
    font-size: 0.875rem;
    flex-shrink: 0;
}

/* Session badges */
.session-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    font-size: 0.7rem;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.session-badge.current {
    background: #0d6efd;
    color: white;
}

.session-badge.previous {
    background: #198754;
    color: white;
}

/* Compact layout for many entries (>25) */
.timeline-entry.compact {
    padding: 0.5rem 0.75rem;
}

.timeline-entry.compact .entry-details {
    display: flex;
    gap: 1rem;
    flex: 1;
}

.timeline-entry.compact .entry-detail {
    font-size: 0.813rem;
    flex: 0 0 auto;
}

.timeline-entry.compact .entry-detail:last-child {
    flex: 1;
    min-width: 0;
}

/* Expanded layout for fewer entries (<10) */
.timeline-entry.expanded {
    padding: 1rem;
}

.timeline-entry.expanded .entry-content {
    flex-direction: column;
    align-items: flex-start;
}

.timeline-entry.expanded .entry-time {
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.timeline-entry.expanded .entry-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    width: 100%;
}

.timeline-entry.expanded .entry-detail {
    font-size: 0.875rem;
}

/* Scale indicator */
.timeline-scale {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #6c757d;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 100;
}

@media (max-width: 768px) {
    .timeline-container {
        padding-left: 40px;
    }
    
    .timeline-line {
        left: 15px;
    }
    
    .timeline-period-marker {
        margin-left: -26px;
        font-size: 1rem;
        padding: 0.4rem 0.8rem;
    }
    
    .timeline-dot {
        left: -24px;
        width: 14px;
        height: 14px;
    }
    
    .timeline-entry {
        margin-left: 0;
        padding: 0.875rem;
    }
    
    .timeline-entry::before {
        left: -48px;
        width: 48px;
    }
    
    .timeline-entries {
        padding-left: 17px;
    }
    
    .entry-details {
        grid-template-columns: 1fr;
    }
    
    .timeline-scale {
        bottom: 10px;
        right: 10px;
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
    }
}
</style>';

echo '<div class="history-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>Login Timeline
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
        ORDER BY create_dt DESC 
        LIMIT 0, 200";
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

// Group logins by time period
$loginsByPeriod = [];
$timeScale = 'day'; // Default scale
$totalLogins = count($logins);
// Layout breakpoints: >25 = compact, 10-25 = medium, <10 = large
$layoutType = 'compact';
if ($totalLogins <= 25) {
    $layoutType = 'medium';
}
if ($totalLogins < 10) {
    $layoutType = 'large';
}

foreach ($logins as $login) {
    $date = new DateTime($login['create_dt']);
    $yearMonth = $date->format('Y-m');
    $day = $date->format('Y-m-d');
    
    if (!isset($loginsByPeriod[$yearMonth])) {
        $loginsByPeriod[$yearMonth] = [];
    }
    
    if (!isset($loginsByPeriod[$yearMonth][$day])) {
        $loginsByPeriod[$yearMonth][$day] = [];
    }
    
    $loginsByPeriod[$yearMonth][$day][] = $login;
}

// Determine appropriate scale based on activity
$totalDays = count(array_reduce($loginsByPeriod, function($carry, $month) {
    return array_merge($carry, array_keys($month));
}, []));

$firstLogin = new DateTime(end($logins)['create_dt']);
$lastLogin = new DateTime(reset($logins)['create_dt']);
$daySpan = $firstLogin->diff($lastLogin)->days;

// Adjust scale based on activity density
if ($daySpan > 365 || $totalDays < 10) {
    $timeScale = 'month';
} elseif ($daySpan > 90 && $totalDays < 30) {
    $timeScale = 'week';
}

echo '<div class="timeline-container">';
echo '<div class="timeline-line"></div>';

$globalIndex = 0;
foreach ($loginsByPeriod as $yearMonth => $days) {
    $periodDate = new DateTime($yearMonth . '-01');
    $periodLabel = $periodDate->format('F Y');
    
    echo '<div class="timeline-period">';
    echo '<div class="timeline-period-marker">' . $periodDate->format('M Y') . '</div>';
    
    foreach ($days as $day => $dayLogins) {
        $dayDate = new DateTime($day);
        $dotClass = count($dayLogins) > 1 ? 'has-multiple' : '';
        $dotTitle = count($dayLogins) . ' login' . (count($dayLogins) > 1 ? 's' : '') . ' on ' . $dayDate->format('M j');
        
        echo '<div class="timeline-date-group">';
        echo '<div class="timeline-dot ' . $dotClass . '" title="' . $dotTitle . '" style="top: 0;"></div>';
        echo '<div class="timeline-date-label">' . $dayDate->format('l, F j') . '</div>';
        echo '<div class="timeline-entries">';
        
        foreach ($dayLogins as $index => $login) {
            $entryClasses = 'timeline-entry';
            if ($layoutType === 'large') {
                $entryClasses .= ' expanded';
            }
            
            $sessionBadge = '';
            if ($globalIndex === 0) {
                $entryClasses .= ' current';
                $sessionBadge = '<span class="session-badge current">Current</span>';
            } elseif ($globalIndex === 1) {
                $sessionBadge = '<span class="session-badge previous">Previous</span>';
            }
            $globalIndex++;
            
            // Parse JSON data
            $decoded = !empty($login['description']) ? json_decode($login['description'], true) : [];
            $isValidJson = json_last_error() === JSON_ERROR_NONE && is_array($decoded);
            
            // Get location
            $location = 'Unknown Location';
            if ($isValidJson && !empty($decoded['location'])) {
                $locationParts = array_filter([
                    $decoded['location']['city'] ?? '',
                    $decoded['location']['region'] ?? ''
                ]);
                if (!empty($locationParts)) {
                    $location = implode(', ', $locationParts);
                }
            }
            
            // Get IP address
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
            
            $loginTime = new DateTime($login['create_dt']);
            
            // Different layouts based on entry count
            if ($layoutType === 'large') {
                echo '<div class="' . $entryClasses . '">
                        ' . $sessionBadge . '
                        <div class="entry-content">
                            <div class="entry-time">' . $loginTime->format('g:i A') . '</div>
                            <div class="entry-details">
                                <div class="entry-detail">
                                    <i class="bi bi-geo-alt"></i>
                                    <span>' . htmlspecialchars($location) . '</span>
                                </div>
                                <div class="entry-detail">
                                    <i class="bi bi-wifi"></i>
                                    <span>' . htmlspecialchars($ipAddress) . '</span>
                                </div>
                                <div class="entry-detail">
                                    <i class="bi bi-' . ($details['deviceType'] === 'Mobile' ? 'phone' : 'laptop') . '"></i>
                                    <span>' . htmlspecialchars($details['deviceType']) . '</span>
                                </div>
                                <div class="entry-detail">
                                    <i class="bi bi-globe"></i>
                                    <span>' . htmlspecialchars($details['browser']) . ' • ' . htmlspecialchars($details['os']) . '</span>
                                </div>
                            </div>
                        </div>
                      </div>';
            } elseif ($layoutType === 'medium') {
                // Medium layout - 2 lines
                echo '<div class="' . $entryClasses . '">
                        ' . $sessionBadge . '
                        <div class="entry-content">
                            <div class="entry-time">' . $loginTime->format('g:i A') . '</div>
                            <div class="entry-details">
                                <div class="entry-detail">
                                    <i class="bi bi-geo-alt"></i>
                                    <span>' . htmlspecialchars($location) . '</span>
                                </div>
                                <div class="entry-detail">
                                    <i class="bi bi-wifi"></i>
                                    <span>' . htmlspecialchars($ipAddress) . '</span>
                                </div>
                                <div class="entry-detail">
                                    <i class="bi bi-' . ($details['deviceType'] === 'Mobile' ? 'phone' : 'laptop') . '"></i>
                                    <span>' . htmlspecialchars($details['deviceType']) . '</span>
                                </div>
                                <div class="entry-detail">
                                    <i class="bi bi-globe"></i>
                                    <span>' . htmlspecialchars($details['browser']) . '</span>
                                </div>
                            </div>
                        </div>
                      </div>';
            } else {
                // Compact layout - 1 line
                echo '<div class="' . $entryClasses . ' compact">
                        ' . $sessionBadge . '
                        <div class="entry-content">
                            <div class="entry-time">' . $loginTime->format('g:i A') . '</div>
                            <div class="entry-details compact">
                                <div class="entry-detail">
                                    <i class="bi bi-geo-alt"></i>
                                    <span>' . htmlspecialchars($location) . '</span>
                                </div>
                                <div class="entry-detail">
                                    <i class="bi bi-wifi"></i>
                                    <span>' . htmlspecialchars($ipAddress) . '</span>
                                </div>
                                <div class="entry-detail">
                                    <i class="bi bi-' . ($details['deviceType'] === 'Mobile' ? 'phone' : 'laptop') . '"></i>
                                    <span>' . htmlspecialchars($details['browser']) . ' • ' . htmlspecialchars($details['os']) . '</span>
                                </div>
                            </div>
                        </div>
                      </div>';
            }
        }
        
        echo '</div>'; // Close timeline-entries
        echo '</div>'; // Close timeline-date-group
    }
    
    echo '</div>'; // Close timeline-period
}

echo '</div>'; // Close timeline-container

// Scale indicator
echo '<div class="timeline-scale">
        <i class="bi bi-info-circle me-1"></i>
        Scale: One dot per ' . $timeScale . '
      </div>';

echo '</div>'; // Close history-container
?>