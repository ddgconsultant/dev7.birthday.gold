<?PHP
$addClasses[] = 'agentparser';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------

$p_displaylength = 30;

$workingUser = $current_user_data['user_id'];

if (isset($_REQUEST['uid'])) {
  $workingUser = $_REQUEST['uid'];

  // The rest of your code that depends on having a $workingUser and $workingUserData
  #  breakpoint($workinguserdata);
}

$workinguserdata = $account->getuserdata($workingUser, 'user_id');
$displaysection = $_REQUEST['view'] ?? '';
$sessionid = $_REQUEST['sessionid'] ?? '';



#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
  if (isset($_POST['formtype']) && ($_POST['formtype'] == 'changedisplaylength')) {
    $p_displaylength = $_POST['displaylength'];
  }
}



#-------------------------------------------------------------------------------
# HANDLE THE DELETE DEVICE
#-------------------------------------------------------------------------------
if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'delete') && ($app->formposted('GET') || $app->formposted())) {      
  // Retrieve the list of devices to delete
  $deviceList = explode(',', $_REQUEST['devicelist']);
  #breakpoint($deviceList);
  
  // Get current device ID from cookies to check if we need to clear cookies
  $current_device_id = $_COOKIE['bg_device_id'] ?? $_COOKIE['bgdeviceid'] ?? null;
  $should_clear_cookies = false;
  $devices_deleted = 0;
  
  foreach ($deviceList as $device_id) {
      if (!empty($device_id)) {
          // Check if this is the current device
          if ($current_device_id === $device_id) {
              $should_clear_cookies = true;
          }
          
          // Mark the device as deleted in bg_user_attributes
          $sql = "UPDATE bg_user_attributes SET `status`='deleted', modify_dt=NOW() 
                  WHERE user_id=:user_id AND type='bg_rememberme_set' AND name=:device_id AND `status`='A'";
          $stmt = $database->prepare($sql);
          $stmt->execute([':user_id' => $current_user_data['user_id'], ':device_id' => $device_id]);

          // Mark the device as deleted in bg_validations
          $sql = "UPDATE bg_validations SET `status`='deleted', modify_dt=NOW() 
                  WHERE user_id=:user_id AND device_id=:device_id AND validation_type='bgrememberme_autologin'  and `status`='cookie'";
          $stmt = $database->prepare($sql);
          $stmt->execute([':user_id' => $current_user_data['user_id'], ':device_id' => $device_id]);
          
          $devices_deleted++;
      }
  }
  
  // If the current device was deleted, clear the rememberme cookies
  if ($should_clear_cookies) {
      $account->clearRememberMeCookies();
  }

  // Set success message in session
  if ($devices_deleted == 1) {
      $_SESSION['device_success_message'] = 'Device has been successfully removed.';
  } else if ($devices_deleted > 1) {
      $_SESSION['device_success_message'] = 'All devices have been successfully removed.';
  }

  $goto = '/myaccount/loginhistory';
  if (!empty($displaysection)) {
      $goto .= '?view=' . $displaysection;
  }
  header('location: ' . $goto);
  exit;
}



#-------------------------------------------------------------------------------
# HANDLE THE DELETE HISTORY
#-------------------------------------------------------------------------------
if ($app->formposted('GET')) {
  if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'deletehistory') {

    $sql = "update bg_logintracking set `status`='X', modify_dt=now() WHERE user_id = " . $workinguserdata['user_id'] . " and `status`='A'  and `site`='" . $site . "' ";

    $stmt = $database->prepare($sql);
    $stmt->execute();
  }
  $goto = '/myaccount/loginhistory';
  if (!empty($displaysection)) $goto .= '?view=' . $displaysection;
  header('location: ' . $goto);
  exit;
}



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';

// Additional styles
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">
<style>
/* Modern minimal design for login history */
.login-container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Tab navigation with active bottom border */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    overflow: hidden;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd !important;
    background: none;
}

/* Settings tab aligned to the right */
.nav-tab-item.settings-tab {
    margin-left: auto;
}

/* Compact device cards for mobile */
.device-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
    position: relative;
}

.device-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-1px);
}

.device-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.device-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 1.5rem;
    color: #495057;
    flex-shrink: 0;
}

.device-icon.current {
    background: #d1ecf1;
    color: #0c5460;
}

.device-info {
    margin-left: 1rem;
    flex: 1;
}

.device-name {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.25rem;
    color: #212529;
}

.device-meta {
    font-size: 0.875rem;
    color: #6c757d;
}

.device-details {
    margin-bottom: 1rem;
}

.detail-row {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    color: #495057;
    padding: 0.25rem 0;
}

.detail-label {
    font-weight: 500;
    margin-right: 0.5rem;
    color: #6c757d;
    min-width: 60px;
}

.device-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.current-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: #0dcaf0;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .nav-tab-item {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }
    
    .device-card {
        padding: 0.875rem;
    }
    
    .device-icon {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
    
    .detail-row {
        font-size: 0.813rem;
    }
    
    .detail-label {
        min-width: 50px;
    }
}

/* Remove Bootstrap default styles that might interfere */
.nav-tabs {
    border-bottom: none;
}

.nav-link {
    border: none;
}

/* Pill-shaped buttons */
.btn {
    border-radius: 25px !important;
    padding: 0.375rem 1.25rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-sm {
    border-radius: 20px !important;
    padding: 0.25rem 1rem;
}

/* Specific button styles */
.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000;
}

.btn-warning:hover {
    background-color: #ffb300;
    border-color: #ffb300;
    transform: translateY(-1px);
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
    transform: translateY(-1px);
}

.btn-outline-danger {
    border-width: 2px;
}

.btn-outline-danger:hover {
    transform: translateY(-1px);
}

/* Badge pill style */
.badge.rounded-pill {
    padding: 0.35em 0.85em;
}

/* Clickable session entries */
.timeline-entry[onclick] {
    cursor: pointer;
    position: relative;
}

.timeline-entry[onclick]:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd;
}

.timeline-entry[onclick]::after {
    content: "\\F5D0";
    font-family: "bootstrap-icons";
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #0d6efd;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.timeline-entry[onclick]:hover::after {
    opacity: 1;
}

/* Square session badges for timeline */
.session-badge {
    border-radius: 4px !important;
    padding: 0.25rem 0.75rem !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}

.session-badge.current {
    background: #0d6efd !important;
    color: white !important;
}

.session-badge.previous {
    background: #198754 !important;
    color: white !important;
}

</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-clock-history me-3"></i>Login Activity</h1>
            <p class="lead mb-0">Monitor access to your account and manage trusted devices</p>
        </div>
    </div>
</div>

<?php
echo '<div class="container my-5 pt-5">
<div class="login-container">
';

// Display success message if exists
if (isset($_SESSION['device_success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            ' . htmlspecialchars($_SESSION['device_success_message']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['device_success_message']);
}

// Check if we're viewing session details
if (!empty($sessionid)) {
    // Verify user has permission to view session details
    $canViewSession = false;
    
    // Check if user is admin
    if ($account->isadmin()) {
        $canViewSession = true;
    } else {
        // Check if this is their own session or an impersonated session
        $sql = "SELECT * FROM bg_logintracking 
                WHERE user_id = :user_id 
                AND description LIKE :session_pattern 
                AND `status` = 'A' 
                LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->execute([
            ':user_id' => $workinguserdata['user_id'],
            ':session_pattern' => '%"session_id":"' . $sessionid . '"%'
        ]);
        $loginRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($loginRecord && (strpos($loginRecord['name'] ?? '', 'IMPERSONATED') !== false || 
                            strpos($loginRecord['name'] ?? '', 'ADMIN') !== false)) {
            $canViewSession = true;
        }
    }
    
    if ($canViewSession) {
        // Add custom styles for session details
        $additionalstyles .= '
        <style>
        .session-details-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .session-summary-card {
            background: #f8f9fa;
            border: none;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .session-stat {
            text-align: center;
            padding: 1rem;
        }
        
        .session-stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #0d6efd;
            margin-bottom: 0.25rem;
        }
        
        .session-stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .activity-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        
        .activity-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .activity-header {
            padding: 1rem;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .activity-header:hover {
            background: #f8f9fa;
        }
        
        .activity-time {
            font-weight: 600;
            color: #212529;
            margin-right: 1rem;
        }
        
        .activity-page {
            color: #0d6efd;
            flex: 1;
        }
        
        .activity-user {
            color: #6c757d;
            font-size: 0.875rem;
            margin-left: 1rem;
        }
        
        .activity-toggle {
            color: #6c757d;
            transition: transform 0.2s ease;
        }
        
        .activity-card.expanded .activity-toggle {
            transform: rotate(180deg);
        }
        
        .activity-details {
            display: none;
            padding: 1rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .activity-card.expanded .activity-details {
            display: block;
        }
        
        .detail-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #495057;
            display: inline-block;
            min-width: 120px;
        }
        
        .detail-value {
            color: #6c757d;
        }
        
        .detail-json {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.75rem;
            margin-top: 0.5rem;
            font-family: monospace;
            font-size: 0.813rem;
            max-height: 200px;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .session-stat-value {
                font-size: 1.5rem;
            }
            
            .activity-header {
                flex-wrap: wrap;
            }
            
            .activity-time {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
        </style>
        <script>
        function toggleActivity(element) {
            const card = element.closest(".activity-card");
            card.classList.toggle("expanded");
        }
        </script>
        ';
        
        // Display session details
        echo '<div class="session-details-container">
                <div class="mb-4">
                    <a href="/myaccount/loginhistory" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Login History
                    </a>
                </div>';
        
        // Fetch session tracking data with limit to prevent memory issues
        $sql = "SELECT * FROM bg_sessiontracking 
                WHERE sessionid = :sessionid 
                ORDER BY create_dt ASC
                LIMIT 500";
        $stmt = $database->prepare($sql);
        $stmt->execute([':sessionid' => $sessionid]);
        $sessionData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if there are more records
        $countSql = "SELECT COUNT(*) as total FROM bg_sessiontracking WHERE sessionid = :sessionid";
        $countStmt = $database->prepare($countSql);
        $countStmt->execute([':sessionid' => $sessionid]);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if (!empty($sessionData)) {
            // Calculate session duration
            $sessionStart = new DateTime($sessionData[0]['create_dt']);
            $sessionEnd = new DateTime(end($sessionData)['create_dt']);
            $duration = $sessionStart->diff($sessionEnd);
            
            // Session summary card
            echo '<div class="session-summary-card">
                    <h5 class="mb-4">
                        <i class="bi bi-activity me-2"></i>Session Overview
                    </h5>
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="session-stat">
                                <div class="session-stat-value">' . count($sessionData) . '</div>
                                <div class="session-stat-label">Pages Viewed</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="session-stat">
                                <div class="session-stat-value">' . $duration->format('%H:%I:%S') . '</div>
                                <div class="session-stat-label">Duration</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="session-stat">
                                <div class="session-stat-value">' . $sessionStart->format('g:i A') . '</div>
                                <div class="session-stat-label">Start Time</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="session-stat">
                                <div class="session-stat-value">' . $sessionEnd->format('g:i A') . '</div>
                                <div class="session-stat-label">End Time</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <small class="text-muted">Session ID: ' . htmlspecialchars($sessionid) . '</small>
                    </div>
                  </div>';
            
            // Activity timeline
            echo '<h6 class="mb-3">
                    <i class="bi bi-clock-history me-2"></i>Activity Timeline
                    <small class="text-muted ms-2">Click on any activity to view details</small>
                  </h6>';
            
            // Show notice if data is limited
            if ($totalCount > 500) {
                echo '<div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This session has ' . number_format($totalCount) . ' total activities. Showing first 500 for performance reasons.
                      </div>';
            }
            
            $activityIndex = 0;
            foreach ($sessionData as $activity) {
                $activityTime = new DateTime($activity['create_dt']);
                $activityIndex++;
                
                echo '<div class="activity-card" id="activity-' . $activityIndex . '">
                        <div class="activity-header" onclick="toggleActivity(this)">
                            <span class="activity-time">
                                <i class="bi bi-clock me-1"></i>' . $activityTime->format('g:i:s A') . '
                            </span>
                            <span class="activity-page">
                                <i class="bi bi-file-earmark me-1"></i>' . htmlspecialchars($activity['page'] ?? 'Unknown Page') . '
                            </span>
                            <span class="activity-user">
                                <i class="bi bi-person me-1"></i>' . htmlspecialchars($activity['username'] ?? 'Guest') . '
                            </span>
                            <span class="activity-toggle">
                                <i class="bi bi-chevron-down"></i>
                            </span>
                        </div>
                        <div class="activity-details">';
                
                // Basic details
                echo '<div class="detail-item">
                        <span class="detail-label">IP Address:</span>
                        <span class="detail-value">' . htmlspecialchars($activity['ip'] ?? 'Unknown') . '</span>
                      </div>';
                
                if (!empty($activity['site'])) {
                    echo '<div class="detail-item">
                            <span class="detail-label">Site:</span>
                            <span class="detail-value">' . htmlspecialchars($activity['site']) . '</span>
                          </div>';
                }
                
                // Parse and display tracking data
                if (!empty($activity['tracking_data'])) {
                    $trackingData = json_decode($activity['tracking_data'], true);
                    if (is_array($trackingData)) {
                        echo '<div class="detail-item">
                                <span class="detail-label">Tracking Data:</span>
                                <div class="detail-json"><pre style="margin: 0; white-space: pre-wrap;">' . 
                                htmlspecialchars(json_encode($trackingData, JSON_PRETTY_PRINT)) . 
                                '</pre></div>
                              </div>';
                    }
                }
                
                // Parse and display session data if available
                if (!empty($activity['session_data'])) {
                    $sessionDataParsed = json_decode($activity['session_data'], true);
                    if (is_array($sessionDataParsed) && !empty($sessionDataParsed)) {
                        echo '<div class="detail-item">
                                <span class="detail-label">Session Data:</span>
                                <div class="detail-json"><pre style="margin: 0; white-space: pre-wrap;">' . 
                                htmlspecialchars(json_encode($sessionDataParsed, JSON_PRETTY_PRINT)) . 
                                '</pre></div>
                              </div>';
                    }
                }
                
                echo '</div>
                      </div>';
            }
            
            // Show raw data for debugging (admin only)
            if ($account->isadmin()) {
                echo '<div class="mt-4">
                        <div class="card">
                            <div class="card-header" onclick="toggleActivity(this)" style="cursor: pointer;">
                                <i class="bi bi-code-slash me-2"></i>Raw Session Data (Admin View) - Limited to first 10 entries
                                <span class="float-end">
                                    <i class="bi bi-chevron-down"></i>
                                </span>
                            </div>
                            <div class="card-body" style="display: none;">
                                <pre style="font-size: 0.8rem; max-height: 400px; overflow-y: auto;">';
                
                // Limit raw data to prevent memory issues
                $limitedData = array_slice($sessionData, 0, 10);
                foreach ($limitedData as $index => $data) {
                    // Remove large fields that might cause memory issues
                    unset($data['request_data']);
                    unset($data['server_data']);
                    if (isset($data['session_data'])) {
                        $data['session_data'] = '(truncated)';
                    }
                    if (isset($data['tracking_data'])) {
                        $data['tracking_data'] = '(truncated)';
                    }
                    echo "Entry " . ($index + 1) . ":\n";
                    echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "\n\n";
                }
                
                if (count($sessionData) > 10) {
                    echo "... and " . (count($sessionData) - 10) . " more entries";
                }
                
                echo '</pre>
                            </div>
                        </div>
                      </div>';
            }
        } else {
            echo '<div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No session tracking data found for this session ID.
                  </div>';
        }
        
        echo '</div>'; // Close session-details-container
        echo '</div></div>'; // Close original containers
        include($dir['core_components'] . '/bg_footer.inc');
        $app->outputpage();
        exit;
    } else {
        // User does not have permission
        echo '<div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                You do not have permission to view this session.
              </div>';
    }
}

// Tab navigation
$device_result = $account->user_activedevices($workinguserdata['user_id']);
echo '<nav class="nav-tabs-modern">';

$loginHistoryActive = ($displaysection === '') ? 'active' : '';
$devicesActive = ($displaysection === 'devices') ? 'active' : '';
$settingsActive = ($displaysection === 'settings') ? 'active' : '';

echo '<a href="/myaccount/loginhistory" class="nav-tab-item ' . $loginHistoryActive . '">
        <i class="bi bi-clock-history me-2"></i>Login History
      </a>';

if (!empty($device_result)) {
    echo '<a href="/myaccount/loginhistory?view=devices" class="nav-tab-item ' . $devicesActive . '">
            <i class="bi bi-shield-check me-2"></i>Trusted Devices
          </a>';
}

echo '<a href="/myaccount/loginhistory?view=settings" class="nav-tab-item settings-tab ' . $settingsActive . '">
        <i class="bi bi-gear"></i>
      </a>';

echo '</nav>';



switch ($displaysection) {
// --------------------------
  case 'devices':
    include('module_login/manage_devices_modern.inc');
    break;

// --------------------------
  case 'settings':
    echo '<div class="settings-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-gear me-2"></i>Login Settings
                </h5>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Security Preferences</h6>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="emailAlerts" checked>
                        <label class="form-check-label" for="emailAlerts">
                            Email me when a new device logs in
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="locationTracking" checked>
                        <label class="form-check-label" for="locationTracking">
                            Track login locations
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="deviceRemember">
                        <label class="form-check-label" for="deviceRemember">
                            Remember devices for 30 days
                        </label>
                    </div>
                </div>
            </div>
          </div>';
    break;

// --------------------------
  default:
    include('module_login/manage_history_timeline.inc');
    break;
}



echo '</div>'; // Close login-container
echo '</div>'; // Close container

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
