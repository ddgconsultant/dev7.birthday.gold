<?php
$addClasses[] = 'agentparser';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Security Settings - Birthday Gold';
$pagedata['metakeywords'] = 'Birthday Gold Security, Account Security, Two-Factor Authentication, Password Settings';
$pagedata['metadescriptions'] = 'Manage your Birthday Gold account security settings. Enable two-factor authentication, update passwords, and monitor account activity.';

// Check if security questions are configured and get setup date
$sql = "SELECT COUNT(*) as question_count, MAX(create_dt) as last_configured_date
        FROM bg_user_attributes 
        WHERE user_id = :user_id 
        AND type = 'security' 
        AND category = 'security' 
        AND `grouping` = 'security_questions' 
        AND status = 'active'";

$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $current_user_data['user_id']]);
$result = $stmt->fetch();
$has_security_questions = ($result['question_count'] == 3);
$security_questions_date = $result['last_configured_date'];

// Additional styles
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">
<style>
/* Security Settings Styles */
.security-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 50%, #16213e 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    position: relative;
    overflow: hidden;
}

.security-hero::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.05); opacity: 0.8; }
}

.security-hero h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.security-hero p {
    font-size: 1.1rem;
    opacity: 0.9;
    position: relative;
    z-index: 1;
    margin-bottom: 0;
}

/* Security Cards */
.security-card {
    background: white;
    border: 1px solid #cbd5e1; /* Darker border for better definition */
    border-radius: 12px;
    padding: 0;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    overflow: hidden;
}

.security-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.security-card-header {
    padding: 1.5rem;
    background: #e9ecef; /* Darker gray for better contrast */
    /* Alternative: Use Bootstrap classes like bg-light (#f8f9fa) or bg-secondary-subtle */
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: nowrap; /* Prevent wrapping */
    gap: 1rem; /* Add space between elements */
}

.security-card-icon {
    font-size: 2rem;
    margin-right: 1rem;
    color: #495057;
}

.security-card-title {
    display: flex;
    align-items: center;
    margin: 0;
    flex-shrink: 1; /* Allow title to shrink if needed */
    min-width: 0; /* Allow text truncation */
}

.security-card-title h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #212529;
    white-space: nowrap; /* Prevent title wrapping */
}

.security-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0; /* Prevent badge from shrinking */
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    white-space: nowrap; /* Prevent badge text wrapping */
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
}

.status-unknown {
    background: #e9ecef;
    color: #495057;
}

.security-card-body {
    padding: 1.5rem;
}

.security-description {
    color: #6c757d;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.security-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Button Styles */
.btn-security-primary {
    background: #198754;
    color: white;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-security-primary:hover {
    background: #157347;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(25, 135, 84, 0.3);
}

.btn-security-secondary {
    background: transparent;
    color: #495057;
    border: 2px solid #dee2e6;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-security-secondary:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    color: #212529;
}

.btn-security-danger {
    background: #dc3545;
    color: white;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-security-danger:hover {
    background: #c82333;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(220, 53, 69, 0.3);
}

/* Icon Colors */
.icon-password { color: #0d6efd; }
.icon-2fa { color: #198754; }
.icon-activity { color: #ffc107; }
.icon-devices { color: #0dcaf0; }
.icon-questions { color: #6f42c1; }
.icon-delete { color: #dc3545; }

/* Activity List */
.activity-list {
    margin-top: 1rem;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.activity-icon {
    color: #6c757d;
}

.activity-text {
    color: #495057;
    font-size: 0.9rem;
}

.activity-time {
    color: #6c757d;
    font-size: 0.875rem;
}

/* Password Strength Indicator */
.password-strength {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.strength-bar {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    margin-top: 0.5rem;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    transition: all 0.3s ease;
}

.strength-weak { background: #dc3545; width: 33%; }
.strength-medium { background: #ffc107; width: 66%; }
.strength-strong { background: #198754; width: 100%; }

/* Quick Stats */
.security-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid #cbd5e1; /* Matching darker border */
    border-radius: 8px;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #198754;
    display: block;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Collapsible Danger Zone */
.security-card-header[data-bs-toggle="collapse"] {
    transition: background-color 0.3s ease;
}

.security-card-header[data-bs-toggle="collapse"]:hover {
    filter: brightness(0.95);
}

.security-card-header[data-bs-toggle="collapse"] .bi-chevron-down {
    transition: transform 0.3s ease;
}

.security-card-header[data-bs-toggle="collapse"][aria-expanded="true"] .bi-chevron-down {
    transform: rotate(180deg);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .security-hero h1 {
        font-size: 2rem;
    }
    
    .security-hero p {
        font-size: 1rem;
    }
    
    .security-card-header {
        /* Keep flex-direction: row on mobile to prevent wrapping */
        flex-direction: row;
        align-items: center;
        gap: 0.5rem;
    }
    
    .security-card-title h3 {
        font-size: 1.1rem; /* Slightly smaller on mobile */
    }
    
    .status-badge {
        font-size: 0.75rem; /* Smaller badge text on mobile */
        padding: 0.2rem 0.5rem;
    }
    
    .security-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .security-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .activity-item {
        flex-direction: column;
        align-items: start;
        gap: 0.5rem;
    }
}
</style>
';

#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-shield-lock me-3"></i>Security Settings</h1>
            <p class="lead mb-0">Protect your account with our comprehensive security features</p>
        </div>
    </div>
</div>

<div class="container my-4 pt-5">

    <!-- Security Stats -->
    <div class="security-stats">
        <div class="stat-card">
            <span class="stat-number">Strong</span>
            <span class="stat-label">Password Strength</span>
        </div>
        <div class="stat-card">
            <span class="stat-number">Disabled</span>
            <span class="stat-label">2FA Status</span>
        </div>
        <div class="stat-card">
            <span class="stat-number">3</span>
            <span class="stat-label">Active Sessions</span>
        </div>
        <?php
        // Get device count for stats
        $quick_device_result = $account->user_activedevices($current_user_data['user_id']);
        $quick_device_count = count($quick_device_result);
        ?>
        <div class="stat-card">
            <span class="stat-number"><?php echo $quick_device_count; ?></span>
            <span class="stat-label">Trusted Devices</span>
        </div>
    </div>

    <?php
    // Check password change history
    $sql = 'SELECT modify_dt, description FROM bg_user_attributes 
            WHERE user_id = :user_id 
            AND type = "security" 
            AND name = "password_changed" 
            AND status = "active"
            ORDER BY modify_dt DESC LIMIT 1';
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $current_user_data['user_id']]);
    $password_change = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate days since password change and extract strength info
    $days_since_password_change = null;
    $password_status = 'No Recent Changes';
    $password_status_class = 'status-warning';
    $password_strength_info = null;
    
    if ($password_change) {
        // Use actual password change date
        $days_since_password_change = floor((time() - strtotime($password_change['modify_dt'])) / (24 * 60 * 60));
        $reference_date = 'password change';
        
        // Try to extract password strength from stored description
        if (!empty($password_change['description'])) {
            $decoded_data = json_decode($password_change['description'], true);
            if (is_array($decoded_data) && isset($decoded_data['strength_category'])) {
                $password_strength_info = $decoded_data;
            }
        }
        
        // Determine status based on both age and strength
        if ($password_strength_info) {
            $strength_category = $password_strength_info['strength_category'];
            $strength_score = $password_strength_info['strength_score'] ?? 0;
            
            // Base status on strength, modified by age
            if ($strength_score >= 80 && $days_since_password_change <= 180) {
                $password_status = 'Strong Password';
                $password_status_class = 'status-active';
            } elseif ($strength_score >= 60 && $days_since_password_change <= 120) {
                $password_status = 'Good Password';
                $password_status_class = 'status-active';
            } elseif ($strength_score >= 40) {
                $password_status = $days_since_password_change > 90 ? 'Fair & Aging' : 'Fair Password';
                $password_status_class = 'status-warning';
            } else {
                $password_status = 'Weak Password';
                $password_status_class = 'status-inactive';
            }
            
            // Override for very old passwords regardless of strength
            if ($days_since_password_change > 180) {
                $password_status = 'Update Recommended';
                $password_status_class = 'status-inactive';
            }
        } else {
            // Legacy password change without strength info
            if ($days_since_password_change <= 30) {
                $password_status = 'Recently Updated';
                $password_status_class = 'status-active';
            } elseif ($days_since_password_change <= 90) {
                $password_status = 'Good';
                $password_status_class = 'status-active';
            } elseif ($days_since_password_change <= 180) {
                $password_status = 'Consider Updating';
                $password_status_class = 'status-warning';
            } else {
                $password_status = 'Update Recommended';
                $password_status_class = 'status-inactive';
            }
        }
    } else {
        // Fallback to account creation date
        $days_since_password_change = floor((time() - strtotime($current_user_data['create_dt'])) / (24 * 60 * 60));
        $reference_date = 'account creation';
        
        if ($days_since_password_change <= 30) {
            $password_status = 'New Account';
            $password_status_class = 'status-active';
        } else {
            $password_status = 'No Recent Changes';
            $password_status_class = 'status-warning';
        }
    }
    ?>

    <!-- Password Settings Card -->
    <div class="security-card">
        <div class="security-card-header">
            <div class="security-card-title">
                <i class="bi bi-key-fill security-card-icon icon-password"></i>
                <h3>Password Settings</h3>
            </div>
            <div class="security-status">
                <span class="status-badge <?= $password_status_class ?>">
                    <span class="d-none d-sm-inline"><?= htmlspecialchars($password_status) ?></span>
                    <span class="d-inline d-sm-none"><?= $password_status_class === 'status-active' ? 'Good' : 'Update' ?></span>
                </span>
            </div>
        </div>
        <div class="security-card-body">
            <p class="security-description">
                Keep your account secure with a strong, unique password. We recommend updating your password every 90 days and using a combination of letters, numbers, and special characters.
            </p>
            
            <div class="password-info mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">
                        <?php if ($password_change): ?>
                            Last Changed:
                        <?php else: ?>
                            Account Created:
                        <?php endif; ?>
                    </span>
                    <span class="fw-bold small <?= $days_since_password_change > 90 ? 'text-warning' : 'text-success' ?>">
                        <?php if ($days_since_password_change == 0): ?>
                            Today
                        <?php elseif ($days_since_password_change == 1): ?>
                            1 day ago
                        <?php else: ?>
                            <?= $days_since_password_change ?> days ago
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if ($password_strength_info): ?>
                    <!-- Password Strength Display -->
                    <div class="password-strength mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Password Strength:</span>
                            <span class="small fw-bold text-<?= $password_strength_info['strength_color'] ?>">
                                <?= htmlspecialchars($password_strength_info['strength_category']) ?> 
                                (<?= $password_strength_info['strength_score'] ?>%)
                            </span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-<?= $password_strength_info['strength_color'] ?>" 
                                 role="progressbar" 
                                 style="width: <?= $password_strength_info['strength_score'] ?>%">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!$password_change): ?>
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Password strength tracking will start after your next password change.
                    </small>
                <?php elseif ($password_strength_info && $password_strength_info['strength_score'] < 60): ?>
                    <small class="text-warning">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Consider using a stronger password with more character variety
                    </small>
                <?php elseif ($days_since_password_change > 90): ?>
                    <small class="text-warning">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Consider updating your password for better security
                    </small>
                <?php endif; ?>
            </div>
            
            <div class="security-actions mt-3">
                <a href="/myaccount/changepassword" class="btn btn-security-primary">
                    <i class="bi bi-shield-lock-fill me-2"></i>Change Password
                </a>
            </div>
        </div>
    </div>

    <?php
    // Check if 2FA is enabled
    $sql = 'SELECT string_value as auth_method FROM bg_user_attributes 
            WHERE user_id = :user_id 
            AND type = "2fa_method" 
            AND status = "active"';
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $current_user_data['user_id']]);
    $user_2fa = $stmt->fetch(PDO::FETCH_ASSOC);
    $has_2fa = $user_2fa && !empty($user_2fa['auth_method']);
    
    // Get real account activity data
    $account_activities = $account->getAccountActivity(null, 5, 7); // Last 5 activities in 7 days
    $security_summary = $account->getSecuritySummary(); // Security assessment
    ?>

    <!-- Two-Factor Authentication Card -->
    <div class="security-card">
        <div class="security-card-header">
            <div class="security-card-title">
                <i class="bi bi-phone-fill security-card-icon icon-2fa"></i>
                <h3>Two-Factor Authentication (2FA)</h3>
            </div>
            <div class="security-status">
                <?php if ($has_2fa): ?>
                    <span class="status-badge status-active">
                        <span class="d-none d-sm-inline">Enabled (<?= htmlspecialchars($user_2fa['auth_method']) ?>)</span>
                        <span class="d-inline d-sm-none">On</span>
                    </span>
                <?php else: ?>
                    <span class="status-badge status-inactive">
                        <span class="d-none d-sm-inline">Not Enabled</span>
                        <span class="d-inline d-sm-none">Off</span>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="security-card-body">
            <?php if ($has_2fa): ?>
                <p class="security-description">
                    Your account is protected with <strong><?= htmlspecialchars($user_2fa['auth_method']) ?></strong> two-factor authentication. You need to enter a code from your phone in addition to your password when signing in.
                </p>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Great!</strong> Your account has enhanced security with 2FA enabled.
                </div>
                <div class="security-actions">
                    <a href="/myaccount/security-2fa" class="btn btn-security-primary">
                        <i class="bi bi-gear-fill me-2"></i>Manage 2FA
                    </a>
            <?php else: ?>
                <p class="security-description">
                    Add an extra layer of security to your account by enabling two-factor authentication. You will need to enter a code from your phone in addition to your password when signing in.
                </p>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Recommended:</strong> Enable 2FA to significantly improve your account security.
                </div>
                <div class="security-actions">
                    <a href="/myaccount/security-2fa" class="btn btn-security-primary">
                        <i class="bi bi-shield-check me-2"></i>Enable 2FA
                    </a>
            <?php endif; ?>
                <a href="#" class="btn btn-security-secondary">
                    <i class="bi bi-info-circle me-2"></i>Learn More
                </a>
            </div>
        </div>
    </div>

    <!-- Account Activity Card -->
    <div class="security-card">
        <div class="security-card-header">
            <div class="security-card-title">
                <i class="bi bi-activity security-card-icon icon-activity"></i>
                <h3>Account Activity</h3>
            </div>
            <div class="security-status">
                <span class="status-badge <?= $security_summary['status_class'] ?>">
                    <span class="d-none d-sm-inline"><?= htmlspecialchars($security_summary['message']) ?></span>
                    <span class="d-inline d-sm-none"><?= $security_summary['status'] === 'secure' ? 'Secure' : 'Alert' ?></span>
                </span>
            </div>
        </div>
        <div class="security-card-body">
            <p class="security-description">
                Monitor your account for any suspicious activity. Review your recent sign-ins and account changes.
            </p>
            
            <?php if (!empty($security_summary['stats']['failed_logins'])): ?>
                <div class="alert alert-warning mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong><?= $security_summary['stats']['failed_logins'] ?> failed login attempts</strong> in the last <?= $security_summary['days_analyzed'] ?> days.
                </div>
            <?php endif; ?>
            
            <div class="activity-list">
                <?php if (!empty($account_activities)): ?>
                    <?php foreach ($account_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <i class="bi bi-<?= $activity['icon'] ?> activity-icon text-<?= $activity['color'] ?>"></i>
                                <span class="activity-text">
                                    <?= htmlspecialchars($activity['title']) ?>
                                    <?php if (!empty($activity['details'])): ?>
                                        <?= htmlspecialchars($activity['details']) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="activity-time">
                                <?= $account->formatTimeAgo($activity['timestamp']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <i class="bi bi-info-circle activity-icon text-muted"></i>
                            <span class="activity-text">No recent activity in the last 7 days</span>
                        </div>
                        <span class="activity-time">-</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="security-actions mt-3">
                <a href="/myaccount/loginhistory" class="btn btn-security-primary">
                    <i class="bi bi-list-ul me-2"></i>View Full Activity Log
                </a>
            </div>
        </div>
    </div>

    <?php
    // Get real trusted devices data
    $device_result = $account->user_activedevices($current_user_data['user_id']);
    $device_count = count($device_result);
    $current_device_id = $_COOKIE['bg_device_id'] ?? $_COOKIE['bgdeviceid'] ?? null;
    ?>
    
    <!-- Trusted Devices Card -->
    <div class="security-card">
        <div class="security-card-header">
            <div class="security-card-title">
                <i class="bi bi-laptop security-card-icon icon-devices"></i>
                <h3>Trusted Devices</h3>
            </div>
            <div class="security-status">
                <span class="status-badge <?php echo $device_count > 0 ? 'status-active' : 'status-warning'; ?>">
                    <?php echo $device_count; ?> Device<?php echo $device_count != 1 ? 's' : ''; ?>
                </span>
            </div>
        </div>
        <div class="security-card-body">
            <p class="security-description">
                Manage devices that you have marked as trusted. These devices can sign in without additional verification steps.
            </p>
            
            <?php if ($device_count > 0): ?>
                <div class="activity-list">
                    <?php 
                    $displayed_devices = 0;
                    foreach ($device_result as $device): 
                        if ($displayed_devices >= 3) break; // Show max 3 devices in summary
                        
                        // Decode device information
                        $description = json_decode($device['description'], true);
                        $descriptionIsValid = json_last_error() === JSON_ERROR_NONE && is_array($description);
                        
                        if ($descriptionIsValid):
                            // Parse user agent for better device info
                            $description['agent'] = $description['user_agent'] ?? $description['agent'] ?? '';
                            $details = !empty($description['agent']) ? $agentparser->getAllDetails($description['agent']) : [
                                'browser' => 'Unknown Browser',
                                'os' => 'Unknown OS',
                                'deviceType' => 'Unknown Device'
                            ];
                            
                            $browser = htmlspecialchars($details['browser'] ?? 'Unknown Browser');
                            $os = htmlspecialchars($details['os'] ?? 'Unknown OS');
                            $deviceType = htmlspecialchars($details['deviceType'] ?? 'Unknown Device');
                            
                            // Location data
                            $city = htmlspecialchars($description['location']['city'] ?? '');
                            $state = htmlspecialchars($description['location']['region'] ?? '');
                            $country = htmlspecialchars($description['location']['country'] ?? '');
                            $location = array_filter([$city, $state, $country]);
                            $locationStr = !empty($location) ? implode(', ', $location) : 'Unknown Location';
                            
                            // Check if this is the current device
                            $is_current = ($device['name'] === $current_device_id);
                            
                            // Device type icon
                            $device_icon = match(strtolower($deviceType)) {
                                'mobile' => 'bi-phone',
                                'tablet' => 'bi-tablet',
                                'desktop' => 'bi-pc-display',
                                default => 'bi-laptop'
                            };
                            
                            // Format last seen date
                            $lastSeen = new DateTime($device['create_dt']);
                            $now = new DateTime();
                            $interval = $now->diff($lastSeen);
                            
                            if ($interval->days == 0) {
                                if ($interval->h == 0) {
                                    $lastSeenText = $interval->i <= 1 ? 'Just now' : $interval->i . ' minutes ago';
                                } else {
                                    $lastSeenText = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                                }
                            } elseif ($interval->days == 1) {
                                $lastSeenText = 'Yesterday';
                            } elseif ($interval->days < 7) {
                                $lastSeenText = $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' ago';
                            } elseif ($interval->days < 30) {
                                $lastSeenText = 'Trusted ' . ceil($interval->days / 7) . ' week' . (ceil($interval->days / 7) > 1 ? 's' : '') . ' ago';
                            } else {
                                $lastSeenText = 'Trusted ' . $lastSeen->format('M j, Y');
                            }
                            
                            $displayed_devices++;
                    ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <i class="bi <?php echo $device_icon; ?> activity-icon <?php echo $is_current ? 'text-primary' : 'text-muted'; ?>"></i>
                            <span class="activity-text">
                                <?php echo $browser; ?> on <?php echo $os; ?><?php echo $is_current ? ' <small class="text-primary">(Current)</small>' : ''; ?>
                                <?php if ($locationStr !== 'Unknown Location'): ?>
                                    <br><small class="text-muted"><?php echo $locationStr; ?></small>
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="activity-time"><?php echo $lastSeenText; ?></span>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    
                    // Show "and X more" if there are additional devices
                    if ($device_count > 3):
                    ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <i class="bi bi-three-dots activity-icon text-muted"></i>
                            <span class="activity-text text-muted">
                                and <?php echo $device_count - 3; ?> more device<?php echo ($device_count - 3) != 1 ? 's' : ''; ?>
                            </span>
                        </div>
                        <span class="activity-time">-</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($device_count > 3): ?>
                <div class="alert alert-info mt-3 py-2">
                    <small><i class="bi bi-info-circle me-1"></i>
                    Showing 3 of <?php echo $device_count; ?> trusted devices. View all devices to see complete list.</small>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-shield-exclamation me-2"></i>
                    <strong>No trusted devices found.</strong> Enable "Remember Me" when logging in to add trusted devices.
                </div>
            <?php endif; ?>
            
            <div class="security-actions mt-3">
                <a href="/myaccount/loginhistory?view=devices" class="btn btn-security-primary">
                    <i class="bi bi-gear me-2"></i><?php echo $device_count > 0 ? 'Manage' : 'View'; ?> Devices
                </a>
                <?php if ($device_count > 1): ?>
                <a href="/myaccount/loginhistory?view=devices&action=delete_all" class="btn btn-security-secondary">
                    <i class="bi bi-trash me-2 text-danger"></i>Delete All Devices
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Security Questions Card -->
    <div class="security-card">
        <div class="security-card-header">
            <div class="security-card-title">
                <i class="bi bi-question-circle-fill security-card-icon icon-questions"></i>
                <h3>Security Questions</h3>
            </div>
            <div class="security-status">
                <?php if ($has_security_questions): ?>
                    <span class="status-badge status-active">
                        <span class="d-none d-sm-inline">Configured</span>
                        <span class="d-inline d-sm-none">Set</span>
                    </span>
                <?php else: ?>
                    <span class="status-badge status-warning">
                        <span class="d-none d-sm-inline">Not Set</span>
                        <span class="d-inline d-sm-none">None</span>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="security-card-body">
            <p class="security-description">
                <?php if ($has_security_questions): ?>
                    Your security questions are configured and ready to help you recover your account if needed.
                <?php else: ?>
                    Set up security questions to help recover your account if you forget your password or need to verify your identity.
                <?php endif; ?>
            </p>
            
            <?php if ($has_security_questions && $security_questions_date): ?>
                <?php
                // Format the configuration date
                $config_date = new DateTime($security_questions_date);
                $now = new DateTime();
                $interval = $now->diff($config_date);
                
                if ($interval->days == 0) {
                    $date_text = 'Today';
                } elseif ($interval->days == 1) {
                    $date_text = 'Yesterday';
                } elseif ($interval->days < 7) {
                    $date_text = $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' ago';
                } elseif ($interval->days < 30) {
                    $weeks = ceil($interval->days / 7);
                    $date_text = $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
                } elseif ($interval->days < 365) {
                    $months = ceil($interval->days / 30);
                    $date_text = $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
                } else {
                    $date_text = $config_date->format('M j, Y');
                }
                ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Last configured:</span>
                    <span class="fw-bold small text-success"><?php echo $date_text; ?></span>
                </div>
                
                <div class="alert alert-success d-flex align-items-center py-2" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div class="small">
                        <strong>3 security questions</strong> are configured and ready to help with account recovery.
                    </div>
                </div>
            <?php elseif (!$has_security_questions): ?>
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Security questions provide an additional way to verify your identity when recovering your account.
                </div>
            <?php endif; ?>
            <div class="security-actions">
                <a href="/myaccount/security-questions" class="btn btn-security-primary">
                    <?php if ($has_security_questions): ?>
                        <i class="bi bi-gear me-2"></i>Manage Security Questions
                    <?php else: ?>
                        <i class="bi bi-pencil-square me-2"></i>Set Up Security Questions
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Account Deletion Card - Danger Zone -->
    <div class="security-card">
        <div class="security-card-header bg-danger-subtle" data-bs-toggle="collapse" data-bs-target="#deleteAccountCollapse" aria-expanded="false" aria-controls="deleteAccountCollapse" style="cursor: pointer;">
            <div class="security-card-title">
                <i class="bi bi-trash-fill security-card-icon icon-delete"></i>
                <h3>Delete Account - Danger Zone</h3>
            </div>
            <div class="security-status">
                <i class="bi bi-chevron-down"></i>
            </div>
        </div>
        <div class="collapse" id="deleteAccountCollapse">
            <div class="security-card-body">
                <p class="security-description">
                    Permanently delete your Birthday Gold account and all associated data. This action cannot be undone.
                </p>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    <strong>Warning:</strong> Deleting your account will permanently remove all your data, including:
                    <ul class="mb-0 mt-2">
                        <li>Your profile information</li>
                        <li>Birthday reward enrollments</li>
                        <li>Redemption history</li>
                        <li>Saved preferences</li>
                    </ul>
                </div>
                <div class="security-actions">
                    <button type="button" class="btn btn-security-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="bi bi-trash me-2"></i>Delete My Account
                    </button>
                    <a href="/myaccount/download-data" class="btn btn-security-secondary">
                        <i class="bi bi-download me-2"></i>Download My Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</div></div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Account Confirmation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="bi bi-trash-fill text-danger" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-center mb-3">Are you absolutely sure?</h5>
                <p class="mb-3">
                    This action <strong>cannot be undone</strong>. This will permanently delete your Birthday Gold account and remove all your data from our servers.
                </p>
                <div class="alert alert-warning" role="alert">
                    <strong>What will be deleted:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Your profile and personal information</li>
                        <li>All birthday reward enrollments</li>
                        <li>Complete redemption history</li>
                        <li>Saved preferences and settings</li>
                        <li>Any pending rewards or benefits</li>
                    </ul>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                    <label class="form-check-label" for="confirmDelete">
                        I understand that this action is permanent and cannot be reversed
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                    <i class="bi bi-trash-fill me-2"></i>Yes, Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('confirmDelete');
    const confirmButton = document.getElementById('confirmDeleteBtn');
    
    // Enable/disable delete button based on checkbox
    confirmCheckbox.addEventListener('change', function() {
        confirmButton.disabled = !this.checked;
    });
    
    // Handle delete confirmation
    confirmButton.addEventListener('click', function() {
        // Here you would typically submit a form or make an AJAX request
        // For now, we'll just redirect to a delete confirmation page
        window.location.href = '/myaccount/delete-account-confirm';
    });
    
    // Reset checkbox when modal is closed
    const deleteModal = document.getElementById('deleteAccountModal');
    deleteModal.addEventListener('hidden.bs.modal', function () {
        confirmCheckbox.checked = false;
        confirmButton.disabled = true;
    });
});
</script>

<?php

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();