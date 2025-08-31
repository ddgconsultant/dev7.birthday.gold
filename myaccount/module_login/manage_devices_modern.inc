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
                    class="btn btn-sm btn-danger" 
                    onclick="reportDevice(\'' . htmlspecialchars($logrow['name']) . '\', \'' . htmlspecialchars($deviceType . ' - ' . $browser) . '\', \'' . htmlspecialchars($locationStr) . '\')">
                    <i class="bi bi-exclamation-triangle me-1"></i>Report
                </button>
                <button type="button" 
                    class="btn btn-sm btn-warning" 
                    onclick="confirmRemoveDevice(\'' . htmlspecialchars($logrow['name']) . '\', \'' . htmlspecialchars($deviceType . ' - ' . $browser) . '\')">
                    <i class="bi bi-trash me-1"></i>Remove
                </button>' : '<span class="text-muted">Cannot remove current device</span>') . '
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
        <button type="button" class="btn btn-sm btn-outline-danger" 
            onclick="confirmRemoveAllDevices()">
            Delete All Devices
        </button>';
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

<!-- Remove Device Modal -->
<div class="modal fade" id="removeDeviceModal" tabindex="-1" aria-labelledby="removeDeviceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="removeDeviceModalLabel">
          <i class="bi bi-laptop me-2"></i>Remove Device
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">Remove this device from your saved logins?</p>
        
        <div class="bg-light p-3 rounded mb-3">
          <div class="d-flex align-items-center">
            <i class="bi bi-laptop fs-4 me-3 text-muted"></i>
            <div>
              <div class="fw-bold" id="removeDeviceInfo"></div>
              <small class="text-muted">This device will no longer stay logged in</small>
            </div>
          </div>
        </div>
        
        <p class="text-muted small mb-0">
          <i class="bi bi-info-circle me-1"></i>
          You can always log in again and choose "Remember Me" to re-add this device.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="GET" action="/myaccount/loginhistory" style="display: inline;">
          <input type="hidden" name="view" value="devices">
          <input type="hidden" name="act" value="delete">
          <input type="hidden" name="devicelist" id="removeDeviceId" value="">
          <input type="hidden" name="_token" value="' . $display->inputcsrf_token('tokenonly') . '">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check me-1"></i>Remove Device
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Remove All Devices Modal -->
<div class="modal fade" id="removeAllDevicesModal" tabindex="-1" aria-labelledby="removeAllDevicesModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="removeAllDevicesModalLabel">
          <i class="bi bi-shield-x me-2"></i>Remove All Devices
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Warning!</strong> This action will remove ALL trusted devices from your account.
        </div>
        
        <p>You are about to remove <strong>' . count($devicelist) . ' devices</strong> from your trusted devices list.</p>
        
        <div class="alert alert-warning">
          <h6 class="alert-heading">Important:</h6>
          <ul class="mb-0">
            <li>You will need to log in again on each device</li>
            <li>All "Remember Me" sessions will be terminated</li>
            <li>This action cannot be undone</li>
          </ul>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST" action="/myaccount/loginhistory" style="display: inline;">
          <input type="hidden" name="act" value="delete">
          <input type="hidden" name="view" value="devices">
          <input type="hidden" name="_token" value="' . $display->inputcsrf_token('tokenonly') . '">
          <input type="hidden" name="devicelist" value="' . implode(',', $devicelist) . '">
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash me-1"></i>Yes, Remove All Devices
          </button>
        </form>
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
    // Submit the security report via AJAX
    const deviceId = window.reportedDeviceId;
    
    // Create form data
    const formData = new FormData();
    formData.append("action", "report_device");
    formData.append("device_id", deviceId);
    formData.append("_token", "' . $display->inputcsrf_token('tokenonly') . '");
    
    // Submit report
    fetch("/myaccount/ajax/security-report.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin"
    })
    .then(response => {
        if (!response.ok) {
            console.error("HTTP Error:", response.status, response.statusText);
        }
        return response.json();
    })
    .then(data => {
        console.log("Security report response:", data);
        if (data.success) {
            // Close the report modal
            const modal = bootstrap.Modal.getInstance(document.getElementById("reportDeviceModal"));
            modal.hide();
            
            // Show success alert at top of page
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Security report submitted successfully!</strong> Our security team will review your report. 
                    We recommend you <a href="/myaccount/security-settings" class="alert-link">change your password</a> immediately.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
            
            // Insert alert at the beginning of login-container
            const container = document.querySelector(".login-container");
            if (container) {
                container.insertAdjacentHTML("afterbegin", alertHtml);
                // Scroll to top to show the alert
                window.scrollTo(0, 0);
            }
        } else {
            // Close modal and show error alert
            const modal = bootstrap.Modal.getInstance(document.getElementById("reportDeviceModal"));
            modal.hide();
            
            const errorHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <strong>Error:</strong> ${data.error || "Failed to submit security report"}. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
            
            const container = document.querySelector(".login-container");
            if (container) {
                container.insertAdjacentHTML("afterbegin", errorHtml);
                window.scrollTo(0, 0);
            }
        }
    })
    .catch(error => {
        console.error("Error submitting report:", error);
        // Close modal and show error
        const modal = bootstrap.Modal.getInstance(document.getElementById("reportDeviceModal"));
        modal.hide();
        
        const errorHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                There was an error submitting your security report. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        
        const container = document.querySelector(".login-container");
        if (container) {
            container.insertAdjacentHTML("afterbegin", errorHtml);
            window.scrollTo(0, 0);
        }
    });
}

// Remove device functionality
function confirmRemoveDevice(deviceId, deviceInfo) {
    document.getElementById("removeDeviceInfo").textContent = deviceInfo;
    document.getElementById("removeDeviceId").value = deviceId;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById("removeDeviceModal"));
    modal.show();
}

// Remove all devices functionality
function confirmRemoveAllDevices() {
    const modal = new bootstrap.Modal(document.getElementById("removeAllDevicesModal"));
    modal.show();
}

// Auto-trigger delete all modal if coming from security settings
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("action") === "delete_all") {
        // Wait a moment for the page to fully load, then trigger the modal
        setTimeout(function() {
            confirmRemoveAllDevices();
            // Clean up the URL to remove the action parameter
            window.history.replaceState({}, document.title, window.location.pathname + "?view=devices");
        }, 500);
    }
});
</script>';
?>