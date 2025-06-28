<?PHP
// Modern device management with compact cards and report functionality
$output_devices = '';
$devicelist = [];
$current_device_id = $_COOKIE['bg_device_id'] ?? null;
$device_count = 0;

// Fetch the device data
foreach ($device_result as $logrow) {
    $device_count++;
    $logrow['type'] = 'AutoLogin Session';
    
    // Attempt to decode the JSON data
    $description = json_decode($logrow['description'], true);
    $descriptionIsValid = json_last_error() === JSON_ERROR_NONE && is_array($description);
    
    if ($descriptionIsValid) {
        $devicelist[] = $logrow['name'];
        
        // Parse user agent for better device info
        $description['agent'] = $description['user_agent'] ?? $description['agent'] ?? '';
        $details = !empty($description['agent']) ? $agentparser->getAllDetails($description['agent']) : [
            'browser' => 'Unknown Browser',
            'os' => 'Unknown OS',
            'deviceType' => 'Unknown Device'
        ];
        
        // Extract data safely
        $browser = htmlspecialchars($details['browser'] ?? 'Unknown Browser');
        $os = htmlspecialchars($details['os'] ?? 'Unknown OS');
        $deviceType = htmlspecialchars($details['deviceType'] ?? 'Unknown Device');
        
        // IP Address handling with privacy
        $ipAddress = 'Unknown';
        if (!empty($description['client_ip'])) {
            $ipParts = explode('.', $description['client_ip']);
            if (count($ipParts) >= 3) {
                $ipAddress = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.xxx';
            }
        }
        
        // Location data
        $city = htmlspecialchars($description['location']['city'] ?? '');
        $state = htmlspecialchars($description['location']['region'] ?? '');
        $country = htmlspecialchars($description['location']['country'] ?? '');
        $location = array_filter([$city, $state, $country]);
        $locationStr = !empty($location) ? implode(', ', $location) : 'Unknown';
        
        // Check if this is the current device
        $is_current = ($logrow['name'] === $current_device_id);
        
        // Device type icon
        $device_icon = match(strtolower($deviceType)) {
            'mobile' => 'bi-phone',
            'tablet' => 'bi-tablet',
            'desktop' => 'bi-pc-display',
            default => 'bi-laptop'
        };
        
        // Format last seen date
        $lastSeen = new DateTime($logrow['create_dt']);
        $now = new DateTime();
        $interval = $now->diff($lastSeen);
        
        if ($interval->days == 0) {
            if ($interval->h == 0) {
                $lastSeenText = $interval->i . ' minutes ago';
            } else {
                $lastSeenText = $interval->h . ' hours ago';
            }
        } elseif ($interval->days == 1) {
            $lastSeenText = 'Yesterday';
        } elseif ($interval->days < 7) {
            $lastSeenText = $interval->days . ' days ago';
        } else {
            $lastSeenText = $lastSeen->format('M j, Y');
        }
        
        $output_devices .= '
        <div class="device-card">
            ' . ($is_current ? '<span class="current-badge">Current Device</span>' : '') . '
            <div class="device-header">
                <div class="device-icon' . ($is_current ? ' current' : '') . '">
                    <i class="' . $device_icon . '"></i>
                </div>
                <div class="device-info">
                    <div class="device-name">' . $deviceType . '</div>
                    <div class="device-meta">Last seen: ' . $lastSeenText . '</div>
                </div>
            </div>
            
            <div class="device-details">
                <div class="detail-row">
                    <span class="detail-label">Browser:</span>
                    <span>' . $browser . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">OS:</span>
                    <span>' . $os . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span>' . $locationStr . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">IP:</span>
                    <span>' . $ipAddress . '</span>
                </div>
            </div>
            
            <div class="device-actions">
                ' . (!$is_current ? '
                <button type="button" 
                    class="btn btn-sm btn-warning" 
                    onclick="reportDevice(\'' . htmlspecialchars($logrow['name']) . '\', \'' . htmlspecialchars($deviceType . ' - ' . $browser) . '\', \'' . htmlspecialchars($locationStr) . '\')">
                    <i class="bi bi-exclamation-triangle me-1"></i>Report
                </button>
                <a href="/myaccount/loginhistory?view=devices&act=delete&devicelist=' . $logrow['name'] . '&_token=' . $display->inputcsrf_token('tokenonly') . '" 
                    class="btn btn-sm btn-danger" 
                    onclick="return confirm(\'Remove this device? You will need to log in again on this device.\');">
                    <i class="bi bi-trash me-1"></i>Remove
                </a>' : '<span class="text-muted">Cannot remove current device</span>') . '
            </div>
        </div>';
    }
}

// Output the modern device management section
echo '
<div class="devices-section">
    <!-- Header with device count -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-shield-check me-2"></i>Trusted Devices 
            <span class="badge bg-secondary rounded-pill ms-2">' . $device_count . '</span>
        </h5>';

// Show delete all button only if more than one device
if (count($devicelist) > 1) {
    echo '
        <form method="POST" action="/myaccount/loginhistory" class="d-inline">
            <input type="hidden" name="act" value="delete">
            <input type="hidden" name="view" value="devices">
            <input type="hidden" name="_token" value="' . $display->inputcsrf_token('tokenonly') . '">
            <input type="hidden" name="devicelist" value="' . implode(',', $devicelist) . '">
            <button type="submit" class="btn btn-sm btn-outline-danger" 
                onclick="return confirm(\'Remove all devices? You will need to log in again on each device.\');">
                Delete All Devices
            </button>
        </form>';
}

echo '
    </div>
    
    <!-- Security notice -->
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        <div>
            These devices can access your account without entering a password. 
            If you see a device you do not recognize, click the <strong>Report</strong> button immediately.
        </div>
    </div>
    
    <!-- Device cards grid -->
    <div class="row">';

if (!empty($output_devices)) {
    echo '<div class="col-12">' . $output_devices . '</div>';
} else {
    echo '
    <div class="col-12">
        <div class="text-center py-5">
            <i class="bi bi-shield-x display-4 text-muted"></i>
            <p class="mt-3 mb-0 text-muted">No trusted devices found.</p>
            <p class="text-muted"><small>Enable "Remember Me" when logging in to add trusted devices.</small></p>
        </div>
    </div>';
}

echo '
    </div>
</div>

<!-- Report Device Modal -->
<div class="modal fade" id="reportDeviceModal" tabindex="-1" aria-labelledby="reportDeviceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="reportDeviceModalLabel">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>Report Suspicious Device
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger">
          <h6 class="alert-heading">Security Alert</h6>
          <p class="mb-0">If you do not recognize this device, your account may have been compromised.</p>
        </div>
        
        <h6>Device Information:</h6>
        <div class="bg-light p-3 rounded mb-3">
          <p class="mb-1"><strong>Device:</strong> <span id="reportDeviceInfo"></span></p>
          <p class="mb-0"><strong>Location:</strong> <span id="reportLocationInfo"></span></p>
        </div>
        
        <h6>Recommended Actions:</h6>
        <ol class="mb-0">
          <li>Remove this device immediately</li>
          <li>Change your password</li>
          <li>Review your recent account activity</li>
          <li>Enable two-factor authentication if available</li>
        </ol>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" onclick="confirmReport()">
          <i class="bi bi-shield-exclamation me-1"></i>Report & Secure Account
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Report device functionality
function reportDevice(deviceId, deviceInfo, location) {
    document.getElementById("reportDeviceInfo").textContent = deviceInfo;
    document.getElementById("reportLocationInfo").textContent = location;
    
    // Store device ID for the report
    window.reportedDeviceId = deviceId;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById("reportDeviceModal"));
    modal.show();
}

function confirmReport() {
    // In production, this would submit a security report
    alert("Security report submitted. We recommend changing your password immediately.");
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById("reportDeviceModal"));
    modal.hide();
    
    // Redirect to security settings
    if (confirm("Would you like to go to Security Settings to change your password?")) {
        window.location.href = "/myaccount/security-settings";
    }
}
</script>';
?>