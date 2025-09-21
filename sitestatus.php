<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$pagetitle = 'Site Status - Birthday.Gold';
$statusUrl = 'https://uptime.birthdaygold.cloud/status/production';

// Function to fetch and parse status from Uptime Kuma API
function fetchUptimeStatus($baseUrl, $cacheFile = null, $cacheTime = 60) {
    $defaultStatus = [
        'overall_status' => 'Unknown',
        'status_color' => 'secondary',
        'status_icon' => 'question-circle-fill',
        'last_checked' => date('Y-m-d H:i:s'),
        'services' => [],
        'individual_services' => [],
        'uptime' => null,
        'error' => null
    ];
    
    // Check cache if provided
    if ($cacheFile && file_exists($cacheFile)) {
        $cacheAge = time() - filemtime($cacheFile);
        if ($cacheAge < $cacheTime) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) {
                return $cached;
            }
        }
    }
    
    try {
        global $system;
        
        // First fetch the service status page for detailed monitor list
        $serviceStatusUrl = 'https://uptime.birthdaygold.cloud/api/status-page/servicestatus';
        
        $options = [
            'timeout' => 10,
            'followlocation' => true,
            'useragent' => 'Birthday.Gold Status Checker/1.0'
        ];
        
        $serviceStatusResponse = $system->curlRequest($serviceStatusUrl, [], [], 'GET', $options);
        $serviceStatusData = isset($serviceStatusResponse['decoded']) ? $serviceStatusResponse['decoded'] : (isset($serviceStatusResponse['raw']) ? json_decode($serviceStatusResponse['raw'], true) : null);
        
        // Also fetch service heartbeat data
        $serviceHeartbeatUrl = 'https://uptime.birthdaygold.cloud/api/status-page/heartbeat/servicestatus';
        
        $serviceHeartbeatResponse = $system->curlRequest($serviceHeartbeatUrl, [], [], 'GET', $options);
        $serviceHeartbeatData = isset($serviceHeartbeatResponse['decoded']) ? $serviceHeartbeatResponse['decoded'] : (isset($serviceHeartbeatResponse['raw']) ? json_decode($serviceHeartbeatResponse['raw'], true) : null);
        
        // Now fetch the main status page config
        $configUrl = 'https://uptime.birthdaygold.cloud/api/status-page/config/production';
        
        $configResponse = $system->curlRequest($configUrl, [], [], 'GET', $options);
        $configData = isset($configResponse['decoded']) ? $configResponse['decoded'] : (isset($configResponse['raw']) ? json_decode($configResponse['raw'], true) : null);
        
        // Now fetch the heartbeat data
        $apiUrl = 'https://uptime.birthdaygold.cloud/api/status-page/heartbeat/production';
        
        $response = $system->curlRequest($apiUrl, [], [], 'GET', $options);
        $httpCode = isset($response['http_code']) ? $response['http_code'] : 0;
        
        if ($httpCode !== 200 || (!isset($response['raw']) && !isset($response['decoded']))) {
            $defaultStatus['error'] = 'Unable to fetch status data';
            return $defaultStatus;
        }
        
        // Parse the JSON response
        $data = isset($response['decoded']) ? $response['decoded'] : (isset($response['raw']) ? json_decode($response['raw'], true) : null);
        if (!$data || !isset($data['heartbeatList'])) {
            $defaultStatus['error'] = 'Invalid status data received';
            return $defaultStatus;
        }
        
        $status = $defaultStatus;
        
        // Check the status of all monitors
        $allOperational = true;
        $anyDown = false;
        $serviceCount = 0;
        $operationalCount = 0;
        $responseTimes = [];
        
        foreach ($data['heartbeatList'] as $monitorId => $heartbeats) {
            $serviceCount++;
            if (!empty($heartbeats)) {
                // Get the most recent heartbeat
                $latestHeartbeat = $heartbeats[count($heartbeats) - 1];
                if ($latestHeartbeat['status'] == 1) {
                    $operationalCount++;
                    // Collect response time if available
                    if (isset($latestHeartbeat['ping'])) {
                        $responseTimes[] = $latestHeartbeat['ping'];
                    }
                } elseif ($latestHeartbeat['status'] == 0) {
                    $anyDown = true;
                    $allOperational = false;
                } else {
                    $allOperational = false;
                }
            }
        }
        
        // Calculate average response time
        if (!empty($responseTimes)) {
            $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
            $status['avg_response_time'] = round($avgResponseTime, 0);
        } else {
            $status['avg_response_time'] = null;
        }
        
        // Calculate overall uptime from uptimeList
        if (isset($data['uptimeList'])) {
            $uptimeValues = [];
            foreach ($data['uptimeList'] as $key => $value) {
                if (strpos($key, '_24') !== false) {
                    $uptimeValues[] = $value * 100;
                }
            }
            if (!empty($uptimeValues)) {
                $averageUptime = array_sum($uptimeValues) / count($uptimeValues);
                $status['uptime'] = number_format($averageUptime, 2) . '%';
            }
        }
        
        // Determine overall status
        if ($allOperational && $serviceCount > 0) {
            $status['overall_status'] = 'All Systems Operational';
            $status['status_color'] = 'success';
            $status['status_icon'] = 'check-circle-fill';
        } elseif ($anyDown) {
            $status['overall_status'] = 'Major Outage';
            $status['status_color'] = 'danger';
            $status['status_icon'] = 'x-circle-fill';
        } elseif ($operationalCount > 0 && $operationalCount < $serviceCount) {
            $status['overall_status'] = 'Partial Outage';
            $status['status_color'] = 'warning';
            $status['status_icon'] = 'exclamation-triangle-fill';
        } else {
            // Default to success if no clear status (e.g., no monitors found)
            $status['overall_status'] = 'All Systems Operational';
            $status['status_color'] = 'success';
            $status['status_icon'] = 'check-circle-fill';
        }
        
        $status['services'] = [
            'total' => $serviceCount,
            'operational' => $operationalCount,
            'down' => $serviceCount - $operationalCount
        ];
        
        // Try to get individual monitor details from service status page
        if ($serviceStatusData && isset($serviceStatusData['publicGroupList']) && $serviceHeartbeatData && isset($serviceHeartbeatData['heartbeatList'])) {
            $monitors = [];
            
            // Label mapping to hide sensitive service names
            $labelMapping = [
                // Map actual monitor names to display names
                'Automations n8n' => 'Automation Service',
                'BG URL Shortener - bd.gold' => 'URL Shortener',
                'DiceBear Avatar API' => 'Avatar Service',
                'Cloudron' => 'Cloud Platform',
                'Rocket Chat' => 'Team Chat',
                'Birthday.Gold HTTP-STATUS Availability' => 'Main Website',
                'december20.bday.gold' => 'December Rewards',
                'april21.bday.gold' => 'April Rewards',
                'BackBlaze BG CDN' => 'CDN Service',
                'Metabase' => 'Analytics',
                'BG OCR Extract Messages' => 'OCR Service',
                'BG User Eligibility Batch Processor' => 'Eligibility Processor',
                'birthday_today_unsent_notification' => 'Notification Service',
                // Additional mappings for other potential services
                'MySQL' => 'Database',
                'Redis' => 'Cache Server',
                'Stripe' => 'Payment Processing',
                'SendGrid' => 'Email Service',
                'dev7.birthday.gold' => 'Development Site',
                'www.birthday.gold' => 'Production Site'
            ];
            
            // Process each group of monitors
            foreach ($serviceStatusData['publicGroupList'] as $group) {
                if (isset($group['monitorList'])) {
                    foreach ($group['monitorList'] as $monitor) {
                        $monitorId = $monitor['id'];
                        $latestStatus = 'unknown';
                        $uptime24h = null;
                        
                        // Check heartbeat data for this monitor
                        if (isset($serviceHeartbeatData['heartbeatList'][$monitorId])) {
                            $heartbeats = $serviceHeartbeatData['heartbeatList'][$monitorId];
                            if (!empty($heartbeats)) {
                                $latest = $heartbeats[count($heartbeats) - 1];
                                $latestStatus = $latest['status'] == 1 ? 'operational' : 'down';
                            }
                        }
                        
                        // Calculate 24h uptime if we have uptimeList data
                        if (isset($serviceHeartbeatData['uptimeList'][$monitorId . '_24'])) {
                            $uptime24h = round($serviceHeartbeatData['uptimeList'][$monitorId . '_24'] * 100, 1);
                        }
                        
                        // Apply label mapping
                        $serviceName = $monitor['name'] ?? 'Unknown Service';
                        $displayName = isset($labelMapping[$serviceName]) ? $labelMapping[$serviceName] : $serviceName;
                        
                        $monitors[] = [
                            'name' => $displayName,
                            'original_name' => $serviceName,
                            'status' => $latestStatus,
                            'uptime' => $uptime24h,
                            'type' => $monitor['type'] ?? 'unknown'
                        ];
                    }
                }
            }
            $status['individual_services'] = $monitors;
        }
        
        $status['last_checked'] = date('Y-m-d H:i:s');
        
        // Cache the result if cache file provided
        if ($cacheFile) {
            file_put_contents($cacheFile, json_encode($status));
        }
        
        return $status;
        
    } catch (Exception $e) {
        $defaultStatus['error'] = 'Error fetching status: ' . $e->getMessage();
        return $defaultStatus;
    }
}

// Fetch actual status with 1-minute cache
$cacheFile = sys_get_temp_dir() . '/birthday_gold_status_cache.json';
$statusData = fetchUptimeStatus($statusUrl, $cacheFile, 60);

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// Handle AJAX request for status refresh
if (isset($_GET['ajax']) && $_GET['ajax'] === 'refresh') {
    header('Content-Type: application/json');
    echo json_encode($statusData);
    exit;
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
$header_flush = true;  // Flush with header for hero design

// Add bg_theme.css for content-header-dark
$additionalstyles = '<link href="' . cssUrl('/public/css/v7/bg_theme.css') . '" rel="stylesheet">';

// Add minimal custom styles for animations and special design elements only
$additionalstyles .= '
<style>
/* Animations - cannot be replaced by Bootstrap */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes status-pulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Main container positioning */
.status-container {
    margin-top: -3rem;
}

/* Status card specific design - unique elements only */
.status-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--bs-success);
}

.status-main-icon {
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.25);
    animation: float 3s ease-in-out infinite;
}

.status-card-header.status-success .status-main-icon {
    animation: float 3s ease-in-out infinite, status-pulse 2s infinite;
}

/* Service status card left border indicator */
.service-status-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--bs-success);
}

.service-status-card:hover {
    background: #f8fffc;
    border-color: var(--bs-success);
}

/* Status indicator dot - removed absolute positioning to stay in column */
.status-indicator {
    display: inline-block;
    border: 1.5px solid white;
}

.status-indicator.active {
    background: #10b981;
    animation: status-pulse 2s infinite;
}

/* Monitor list checkmarks */
.monitor-item::before {
    content: "✓";
    color: var(--bs-success);
    margin-right: 0.75rem;
    width: 20px;
    height: 20px;
    background: rgba(25, 135, 84, 0.1);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    flex-shrink: 0;
}

/* Two column grid for monitor list */
@media (min-width: 768px) {
    .monitor-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem 2rem;
    }
}
</style>';
// Additional scripts
$additionalscripts = '';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>System Status</h1>
        <p class="lead">Real-time monitoring and performance metrics for Birthday.Gold</p>
    </div>
</div>

<div class="container status-container">
    <?php if ($statusData['error'] && strpos($statusData['error'], 'Unable to fetch status data') !== false): ?>
    <!-- Dashboard Link State - Intentional Design -->
    <div class="row justify-content-center">
        <div class="col-lg-10 mb-5">
            <div class="card shadow-lg border-0 position-relative status-card">
                <div class="status-card-header status-success">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow status-main-icon">
                        <i class="bi bi-graph-up-arrow" style="font-size: 3rem;"></i>
                    </div>
                    <div class="fs-2 fw-bold mb-2">
                        System Status Dashboard
                    </div>
                    <div class="small opacity-75">
                        Real-time monitoring powered by Uptime Kuma
                    </div>
                </div>
                
                <div class="p-4 text-center">
                    <p class="lead mb-4">View comprehensive status information, historical uptime data, and detailed service metrics on our dedicated monitoring dashboard.</p>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="d-flex flex-column align-items-center">
                                <div class="mb-2">
                                    <i class="bi bi-hdd-stack-fill text-primary" style="font-size: 2.5rem;"></i>
                                </div>
                                <h5>Service Monitoring</h5>
                                <p class="text-muted small">Track all services in real-time</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex flex-column align-items-center">
                                <div class="mb-2">
                                    <i class="bi bi-graph-up text-success" style="font-size: 2.5rem;"></i>
                                </div>
                                <h5>Historical Data</h5>
                                <p class="text-muted small">View uptime trends and statistics</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex flex-column align-items-center">
                                <div class="mb-2">
                                    <i class="bi bi-bell-fill text-info" style="font-size: 2.5rem;"></i>
                                </div>
                                <h5>Incident Reports</h5>
                                <p class="text-muted small">Stay informed about service events</p>
                            </div>
                        </div>
                    </div>
                    
                    <a href="https://uptime.birthdaygold.cloud/status/production" target="_blank" class="btn btn-primary btn-lg">
                        <i class="fas fa-external-link-alt me-2"></i>
                        Open Status Dashboard
                    </a>
                    
                    <div class="mt-3">
                        <small class="text-muted">Opens in a new window</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Normal State - Two Panels -->
    <div class="row">
        <!-- Left Panel - Main Status (Larger) -->
        <div class="col-lg-7 mb-5">
            <div class="card shadow-lg border-0 position-relative status-card">
                <div class="p-4 text-center text-white bg-<?php echo htmlspecialchars($statusData['status_color']); ?> status-card-header status-<?php echo htmlspecialchars($statusData['status_color']); ?>">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow status-main-icon">
                        <i class="bi bi-<?php echo htmlspecialchars($statusData['status_icon']); ?>" style="font-size: 3rem;"></i>
                    </div>
                    <div class="fs-2 fw-bold mb-2">
                        <?php echo htmlspecialchars($statusData['overall_status']); ?>
                    </div>
                    <div class="small opacity-75">
                        Last checked <?php 
                        $checkTime = strtotime($statusData['last_checked']);
                        $timezone = date_default_timezone_get();
                        // If today, show time only with timezone
                        if (date('Y-m-d', $checkTime) == date('Y-m-d')) {
                            echo date('g:i A', $checkTime) . ' ' . date('T');
                        } else {
                            // If not today, show day, date and time with timezone
                            echo date('D, M j g:i A T', $checkTime);
                        }
                        ?>
                    </div>
                </div>
                
                <div class="p-3" style="background: #fafafa;">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small text-muted fw-medium">Services Monitored</span>
                        <span class="fw-semibold"><?php echo $statusData['services']['operational'] ?? 0; ?> of <?php echo $statusData['services']['total'] ?? 0; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small text-muted fw-medium">24-Hour Uptime</span>
                        <span class="fw-semibold"><?php echo htmlspecialchars($statusData['uptime'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small text-muted fw-medium">Avg Response Time</span>
                        <span class="fw-semibold">
                            <?php 
                            if (isset($statusData['avg_response_time']) && $statusData['avg_response_time'] !== null) {
                                echo htmlspecialchars($statusData['avg_response_time']) . 'ms';
                            } else {
                                echo '< 50ms';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small text-muted fw-medium">Auto-Refresh</span>
                        <span class="fw-semibold">
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-secondary" id="refreshCountdown" style="background-color: #5a6268; border-color: #545b62; min-width: 75px;">
                                    <i class="fas fa-sync-alt me-1"></i>
                                    <span id="countdownText">60s</span>
                                </button>
                                <button class="btn btn-secondary" onclick="refreshStatus()" style="border-left: 1px solid rgba(255,255,255,0.3);">
                                    Now
                                </button>
                            </div>
                        </span>
                    </div>
                </div>
                
                <div class="text-center mt-3 mb-4">
                    <a href="<?php echo htmlspecialchars($statusUrl); ?>" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>
                        View Detailed Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Service Status -->
        <div class="col-lg-5 mb-5">
            <div class="card shadow h-100 pb-0">
                <h2 class="mb-3 d-flex align-items-center p-3"><i class="bi bi-hdd-stack-fill text-success me-3"></i>Service Status</h2>
                
                <div class="p-2 px-5">
                <?php if (!empty($statusData['individual_services'])): ?>
                    <!-- Real monitor data from Uptime Kuma -->
                    <?php foreach ($statusData['individual_services'] as $service):
                        // Skip BG JOB entries
                        if (strpos($service['original_name'], 'BG JOB') === 0) {
                            continue;
                        }

                        $statusClass = $service['status'] === 'operational' ? 'active' : 'error';
                        $badgeClass = $service['status'] === 'operational' ? 'bg-success' : 'bg-danger';
                        $badgeText = $service['status'] === 'operational' ? 'OPERATIONAL' : 'DOWN';
                        $uptimeText = $service['uptime'] !== null ? $service['uptime'] . '%' : 'N/A';

                    ?>
                    <div class="bg-white rounded border mb-1 position-relative overflow-hidden service-status-card py-1 ps-3 pe-2">
                        <div class="d-flex align-items-center">
                            <div style="width: 20px;">
                                <span class="rounded-circle d-inline-block <?php echo $service['status'] === 'operational' ? 'bg-success' : 'bg-danger'; ?> status-indicator <?php echo $statusClass; ?>" style="width: 8px; height: 8px;"></span>
                            </div>
                            <div class="flex-grow-1 me-2">
                                <h6 class="text-truncate mb-0" style="font-size: 0.875rem;"><?php echo htmlspecialchars($service['name']); ?></h6>
                            </div>
                            <div style="width: 50px;" class="text-end">
                                <small class="text-muted" style="font-size: 0.7rem;"><?php echo $uptimeText; ?></small>
                            </div>
                            <div style="width: 85px;" class="text-end ms-2">
                                <span class="badge rounded-pill <?php echo $badgeClass; ?>" style="font-size: 0.65rem;"><?php echo $badgeText; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div><!-- End service-status-container -->
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bottom Section - What We Monitor (Always shown) -->
    <div class="card shadow mt-4 mb-5 p-4">
        <h2 class="d-flex align-items-center mb-4"><i class="bi bi-shield-check-fill text-success me-3"></i>What We Monitor</h2>
        
        <p class="mb-4">Our comprehensive monitoring system tracks all critical Birthday.Gold services to ensure optimal performance and availability:</p>
        
        <ul class="list-unstyled monitor-list">
            <li class="d-flex align-items-center py-2 monitor-item">Website availability & response times</li>
            <li class="d-flex align-items-center py-2 monitor-item">API endpoints & integrations</li>
            <li class="d-flex align-items-center py-2 monitor-item">Database performance & connectivity</li>
            <li class="d-flex align-items-center py-2 monitor-item">Payment processing systems</li>
</div>

<script>
// Auto-refresh functionality with visual countdown
let refreshInterval;
let countdownInterval;
let secondsUntilRefresh = 60;

const updateCountdown = () => {
    const countdownTextEl = document.getElementById('countdownText');
    if (countdownTextEl) {
        countdownTextEl.textContent = secondsUntilRefresh + 's';
    }
};

const startAutoRefresh = () => {
    // Countdown timer
    countdownInterval = setInterval(() => {
        secondsUntilRefresh--;
        if (secondsUntilRefresh <= 0) {
            clearInterval(countdownInterval);
            location.reload();
        } else {
            updateCountdown();
        }
    }, 1000);
    
    // Initial countdown display
    updateCountdown();
};

// Manual refresh function
function refreshStatus() {
    // Add spinning animation to refresh icon in the countdown button
    const refreshBtn = document.getElementById('refreshCountdown');
    const refreshIcon = refreshBtn ? refreshBtn.querySelector('i') : null;
    if (refreshIcon) {
        refreshIcon.classList.add('fa-spin');
    }
    
    fetch("<?php echo $_SERVER['REQUEST_URI']; ?>?ajax=refresh")
        .then(response => response.json())
        .then(data => {
            // Update status card header color
            const statusHeader = document.querySelector('.status-card-header');
            if (statusHeader) {
                statusHeader.className = 'status-card-header status-' + data.status_color;
            }
            
            // Update main icon
            const mainIcon = document.querySelector('.status-main-icon i');
            if (mainIcon) {
                mainIcon.className = 'bi bi-' + data.status_icon;
                mainIcon.style.fontSize = '3rem';
            }
            
            // Update status text
            const statusText = document.querySelector('.status-main-text');
            if (statusText) {
                statusText.textContent = data.overall_status;
            }
            
            // Update last checked time
            const subText = document.querySelector('.status-sub-text');
            if (subText) {
                const date = new Date(data.last_checked);
                subText.textContent = 'Last checked ' + date.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
            }
            
            // Update metrics
            const metrics = document.querySelectorAll('.metric-value');
            if (metrics[0] && data.services) {
                metrics[0].textContent = data.services.operational + ' of ' + data.services.total;
            }
            if (metrics[1] && data.uptime) {
                metrics[1].textContent = data.uptime;
            }
            
            // Reset countdown
            secondsUntilRefresh = 60;
            
            // Remove spinning animation
            setTimeout(() => {
                if (refreshIcon) {
                    refreshIcon.classList.remove('fa-spin');
                }
            }, 500);
        })
        .catch(error => {
            console.error('Error refreshing status:', error);
            if (refreshIcon) {
                refreshIcon.classList.remove('fa-spin');
            }
        });
}

// Add pulse animation style
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { 
            transform: scale(1); 
            opacity: 0.95;
        }
        50% { 
            transform: scale(1.05); 
            opacity: 1;
        }
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .fa-spin {
        animation: spin 1s linear infinite;
    }
`;
document.head.appendChild(style);

// Animate numbers on load
function animateValue(el, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const current = Math.floor(progress * (end - start) + start);
        el.textContent = current;
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            el.textContent = end;
        }
    };
    window.requestAnimationFrame(step);
}

// Add hover effects to service cards
document.addEventListener('DOMContentLoaded', () => {
    // Animate uptime percentages on load
    document.querySelectorAll('.uptime-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
    
    // Add subtle hover effects to service cards (no bounce)
    document.querySelectorAll('.service-status-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            const indicator = this.querySelector('.status-indicator');
            if (indicator && indicator.classList.contains('active')) {
                indicator.style.animation = 'status-pulse 0.5s infinite';
            }
        });
        card.addEventListener('mouseleave', function() {
            const indicator = this.querySelector('.status-indicator');
            if (indicator) {
                indicator.style.animation = 'status-pulse 2s infinite';
            }
        });
    });
    
    // Start auto-refresh countdown
    startAutoRefresh();
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();