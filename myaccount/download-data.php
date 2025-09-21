<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Download My Data - Birthday.Gold';
$pagedata['metakeywords'] = 'Birthday.Gold Data Export, Download Personal Data, GDPR, Privacy';
$pagedata['metadescriptions'] = 'Download your Birthday.Gold account data. Export your personal information, activity history, and preferences in various formats.';

// Additional styles
$additionalstyles = '
<style>
/* Content Header Dark - From v7 theme */
.content-header-dark {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 50%, #16213e 100%);
    color: white;
    padding: 60px 0;
    position: relative;
    overflow: hidden;
    margin-bottom: 30px;
}

.content-header-dark::before {
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

.content-header-dark h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.content-header-dark p {
    font-size: 1.1rem;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

.text-white-50 {
    color: rgba(255, 255, 255, 0.5) !important;
}

/* Download Data Styles */
/* Data Category Cards */
.data-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 0;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    overflow: hidden;
}

.data-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.data-card-header {
    padding: 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.data-card-icon {
    font-size: 2rem;
    margin-right: 1rem;
    color: #495057;
}

.data-card-title {
    display: flex;
    align-items: center;
    margin: 0;
    flex-grow: 1;
}

.data-card-title h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #212529;
}

.data-card-body {
    padding: 1.5rem;
}

.data-description {
    color: #6c757d;
    margin-bottom: 1rem;
    line-height: 1.6;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    width: 60px;
    height: 30px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #198754;
}

input:checked + .toggle-slider:before {
    transform: translateX(30px);
}

/* Data Items List */
.data-items {
    margin-top: 1rem;
    padding-left: 1.5rem;
}

.data-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.data-item i {
    margin-right: 0.5rem;
    color: #198754;
}

/* Format Selection */
.format-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.format-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.format-option {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.format-option:hover {
    border-color: #198754;
    background: #f8f9fa;
}

.format-option.selected {
    border-color: #198754;
    background: #d4edda;
}

.format-option i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}

.format-option h4 {
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}

.format-option p {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

/* Download Summary */
.summary-card {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.summary-stat {
    text-align: center;
}

.summary-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #198754;
    display: block;
}

.summary-stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

/* Action Buttons */
.btn-download-primary {
    background: #198754;
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 1.1rem;
}

.btn-download-primary:hover {
    background: #157347;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(25, 135, 84, 0.3);
}

.btn-download-primary:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.btn-download-secondary {
    background: transparent;
    color: #495057;
    border: 2px solid #dee2e6;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-download-secondary:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    color: #212529;
}

/* Icon Colors */
.icon-profile { color: #0d6efd; }
.icon-activity { color: #ffc107; }
.icon-content { color: #6f42c1; }
.icon-transactions { color: #198754; }
.icon-preferences { color: #0dcaf0; }
.icon-technical { color: #fd7e14; }
.icon-connections { color: #d63384; }
.icon-security { color: #dc3545; }

/* Select All Controls */
.select-controls {
    background: #e9ecef;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Progress Bar */
.download-progress {
    display: none;
    margin-top: 1.5rem;
}

.progress {
    height: 25px;
    background-color: #e9ecef;
}

.progress-bar {
    background-color: #198754;
    transition: width 0.3s ease;
}

/* Notification Options */
.notification-options .form-check {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.3s ease;
}

.notification-options .form-check:hover {
    background: #f8f9fa;
    border-color: #198754;
}

.notification-options .form-check-input:checked ~ .form-check-label {
    color: #198754;
}

/* Timeline Styles */
.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: "";
    position: absolute;
    left: 1.25rem;
    top: 3rem;
    width: 2px;
    height: calc(100% + 0.5rem);
    background: #e0e0e0;
}

.timeline-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Request Status */
.request-status {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .download-hero h1 {
        font-size: 1.5rem;
    }
    
    .download-hero p {
        font-size: 1rem;
    }
    
    .data-card-header {
        flex-direction: column;
        align-items: start;
        gap: 1rem;
    }
    
    .toggle-switch {
        align-self: flex-end;
    }
    
    .format-options {
        grid-template-columns: 1fr;
    }
    
    .summary-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .notification-options .form-check {
        padding: 0.75rem;
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

<!-- Dark Header Section -->
<div class="content-header-dark">
    <div class="container py-4">
        <h1 class="mb-0">Download My Data</h1>
        <p class="mb-0 text-white-50">Export your personal information and account data</p>
    </div>
</div>

<div class="container my-4">

    <!-- Select Controls -->
    <div class="select-controls">
        <div>
            <strong>Quick Actions:</strong>
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary me-2" id="selectAll">
                <i class="bi bi-check-all me-1"></i>Select All
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">
                <i class="bi bi-x-circle me-1"></i>Deselect All
            </button>
        </div>
    </div>

    <!-- Personal Account Data -->
    <div class="data-card">
        <div class="data-card-header">
            <div class="data-card-title">
                <i class="bi bi-person-circle data-card-icon icon-profile"></i>
                <h3>Personal Account Data</h3>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="personal_data" checked>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="data-card-body">
            <p class="data-description">
                Your basic profile information and account details including registration data and authentication methods.
            </p>
            <div class="data-items">
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Name, email, phone, and address</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Account creation date and user IDs</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Profile picture and avatar settings</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Authentication methods and security status</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Data -->
    <div class="data-card">
        <div class="data-card-header">
            <div class="data-card-title">
                <i class="bi bi-activity data-card-icon icon-activity"></i>
                <h3>Activity History</h3>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="activity_data" checked>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="data-card-body">
            <p class="data-description">
                Your account activity including login history, enrollment actions, and interaction logs.
            </p>
            <div class="data-items">
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Login history with timestamps and locations</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Birthday program enrollment history</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Reward redemption records</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Search and browsing history</span>
                </div>
            </div>
        </div>
    </div>

    <!-- User Content -->
    <div class="data-card">
        <div class="data-card-header">
            <div class="data-card-title">
                <i class="bi bi-file-earmark-text data-card-icon icon-content"></i>
                <h3>Your Content</h3>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="user_content">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="data-card-body">
            <p class="data-description">
                Content you have created or uploaded including posts, comments, and uploaded files.
            </p>
            <div class="data-items">
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Posts and social interactions</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Comments and reviews</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Uploaded documents and images</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Form submissions and feedback</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Data -->
    <div class="data-card">
        <div class="data-card-header">
            <div class="data-card-title">
                <i class="bi bi-credit-card data-card-icon icon-transactions"></i>
                <h3>Transactions & Payments</h3>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="transaction_data">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="data-card-body">
            <p class="data-description">
                Your payment history and subscription details (payment methods are redacted for security).
            </p>
            <div class="data-items">
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Transaction records and amounts</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Subscription status and billing history</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Invoices and receipts</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Refund and dispute history</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Preferences -->
    <div class="data-card">
        <div class="data-card-header">
            <div class="data-card-title">
                <i class="bi bi-gear data-card-icon icon-preferences"></i>
                <h3>Preferences & Settings</h3>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="preferences_data" checked>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="data-card-body">
            <p class="data-description">
                Your personalized settings, preferences, and customizations.
            </p>
            <div class="data-items">
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Notification preferences</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Language and accessibility settings</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Saved filters and search preferences</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Theme and display settings</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Technical Data -->
    <div class="data-card">
        <div class="data-card-header">
            <div class="data-card-title">
                <i class="bi bi-cpu data-card-icon icon-technical"></i>
                <h3>Device & Technical Data</h3>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="technical_data">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="data-card-body">
            <p class="data-description">
                Technical information about your devices and how you access Birthday.Gold.
            </p>
            <div class="data-items">
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Device information and browser details</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>IP address history</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Session logs and access patterns</span>
                </div>
                <div class="data-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Cookie data and tracking IDs</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Format Selection -->
    <div class="format-card">
        <h3 class="mb-3">Choose Export Format</h3>
        <p class="text-muted">Select how you would like to receive your data</p>
        <div class="format-options">
            <div class="format-option selected" data-format="json">
                <i class="bi bi-filetype-json text-primary"></i>
                <h4>JSON</h4>
                <p>Machine-readable format, ideal for developers</p>
            </div>
            <div class="format-option" data-format="csv">
                <i class="bi bi-filetype-csv text-success"></i>
                <h4>CSV</h4>
                <p>Spreadsheet format, easy to view in Excel</p>
            </div>
            <div class="format-option" data-format="zip">
                <i class="bi bi-file-earmark-zip text-warning"></i>
                <h4>ZIP Archive</h4>
                <p>Complete archive with all files organized</p>
            </div>
        </div>
        <input type="hidden" id="selectedFormat" value="json">
    </div>

    <!-- Notification Preferences -->
    <div class="data-card">
        <div class="data-card-header">
            <div class="data-card-title">
                <i class="bi bi-bell data-card-icon icon-preferences"></i>
                <h3>Notification Preferences</h3>
            </div>
        </div>
        <div class="data-card-body">
            <p class="data-description">
                Choose how you would like to be notified when your data export is ready for download.
            </p>
            <div class="notification-options">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="notificationMethod" id="notifyEmail" value="email" checked>
                    <label class="form-check-label" for="notifyEmail">
                        <strong><i class="bi bi-envelope-fill me-2"></i>Email Notification</strong>
                        <p class="text-muted small mb-0">Send download link to: <?php echo htmlspecialchars($current_user_data['email']); ?></p>
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="notificationMethod" id="notifySMS" value="sms">
                    <label class="form-check-label" for="notifySMS">
                        <strong><i class="bi bi-phone-fill me-2"></i>SMS Text Message</strong>
                        <p class="text-muted small mb-0">Send notification to: <?php echo htmlspecialchars($current_user_data['phone'] ?? 'No phone number on file'); ?></p>
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="notificationMethod" id="notifyBoth" value="both">
                    <label class="form-check-label" for="notifyBoth">
                        <strong><i class="bi bi-check2-all me-2"></i>Email & SMS</strong>
                        <p class="text-muted small mb-0">Receive notifications via both email and SMS</p>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="notificationMethod" id="notifyDashboard" value="dashboard">
                    <label class="form-check-label" for="notifyDashboard">
                        <strong><i class="bi bi-grid-3x3-gap-fill me-2"></i>Dashboard Only</strong>
                        <p class="text-muted small mb-0">Check your account dashboard for the download link</p>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Review Notice -->
    <div class="data-card" style="border: 2px solid #ffc107;">
        <div class="data-card-header" style="background: #fff3cd;">
            <div class="data-card-title">
                <i class="bi bi-person-check-fill data-card-icon" style="color: #856404;"></i>
                <h3>Manual Review Process</h3>
            </div>
        </div>
        <div class="data-card-body">
            <div class="alert alert-warning mb-3" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Important:</strong> All data export requests are manually reviewed by our privacy team to ensure data security and compliance.
            </div>
            <div class="review-timeline">
                <h6 class="mb-3">What to expect:</h6>
                <div class="timeline-item mb-3">
                    <div class="d-flex align-items-start">
                        <div class="timeline-icon bg-primary text-white rounded-circle p-2 me-3">
                            <i class="bi bi-1-circle"></i>
                        </div>
                        <div>
                            <strong>Request Submission</strong>
                            <p class="text-muted small mb-0">Your request is queued for review immediately</p>
                        </div>
                    </div>
                </div>
                <div class="timeline-item mb-3">
                    <div class="d-flex align-items-start">
                        <div class="timeline-icon bg-warning text-white rounded-circle p-2 me-3">
                            <i class="bi bi-2-circle"></i>
                        </div>
                        <div>
                            <strong>Manual Review (24-48 hours)</strong>
                            <p class="text-muted small mb-0">Our privacy team verifies your identity and request details</p>
                        </div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="d-flex align-items-start">
                        <div class="timeline-icon bg-success text-white rounded-circle p-2 me-3">
                            <i class="bi bi-3-circle"></i>
                        </div>
                        <div>
                            <strong>Data Preparation & Delivery</strong>
                            <p class="text-muted small mb-0">Your data is compiled and sent via your chosen notification method</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Limits -->
    <div class="data-card" style="border: 2px solid #dc3545;">
        <div class="data-card-header" style="background: #f8d7da;">
            <div class="data-card-title">
                <i class="bi bi-exclamation-triangle-fill data-card-icon" style="color: #721c24;"></i>
                <h3>Request Limits</h3>
            </div>
        </div>
        <div class="data-card-body">
            <?php
            // Check for previous data export requests using bg_user_attributes
            $sql = "SELECT COUNT(*) as request_count, MAX(create_dt) as last_request 
                    FROM bg_user_attributes 
                    WHERE user_id = :user_id 
                    AND type = 'data_export'
                    AND category = 'request'
                    AND create_dt >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    AND status = 'active'";
            
            $stmt = $database->prepare($sql);
            $stmt->execute(['user_id' => $current_user_data['user_id']]);
            $request_data = $stmt->fetch();
            
            $requests_used = $request_data['request_count'] ?? 0;
            $requests_remaining = max(0, 1 - $requests_used);
            $last_request_date = $request_data['last_request'] ? date('F j, Y', strtotime($request_data['last_request'])) : null;
            ?>
            
            <div class="request-status mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Annual Request Allowance</h6>
                    <span class="badge <?php echo $requests_remaining > 0 ? 'bg-success' : 'bg-danger'; ?> fs-6">
                        <?php echo $requests_remaining; ?> of 1 remaining
                    </span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar <?php echo $requests_remaining > 0 ? 'bg-success' : 'bg-danger'; ?>" 
                         role="progressbar" 
                         style="width: <?php echo $requests_remaining * 100; ?>%">
                    </div>
                </div>
            </div>
            
            <?php if ($requests_used > 0 && $last_request_date): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-clock-history me-2"></i>
                Your last data export request was on <strong><?php echo $last_request_date; ?></strong>
            </div>
            <?php endif; ?>
            
            <?php if ($requests_remaining == 0): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-x-octagon-fill me-2"></i>
                <strong>Request Limit Reached:</strong> You have used your annual data export allowance. 
                Your next request will be available 12 months after your last request.
            </div>
            <?php else: ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Request Available:</strong> You can submit a data export request.
            </div>
            <?php endif; ?>
            
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                To protect user privacy and prevent abuse, we limit data export requests to <strong>1 per 12-month period</strong>. 
                If you need access to your data more frequently due to special circumstances, please contact our support team.
            </p>
        </div>
    </div>

    <!-- Summary -->
    <div class="summary-card">
        <h3 class="mb-3">Export Summary</h3>
        <div class="summary-stats">
            <div class="summary-stat">
                <span class="summary-stat-value" id="categoryCount">3</span>
                <span class="summary-stat-label">Categories Selected</span>
            </div>
            <div class="summary-stat">
                <span class="summary-stat-value" id="estimatedSize">~1.2 MB</span>
                <span class="summary-stat-label">Estimated Size</span>
            </div>
            <div class="summary-stat">
                <span class="summary-stat-value">~2 min</span>
                <span class="summary-stat-label">Processing Time</span>
            </div>
        </div>
        
        <div class="alert alert-info" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Privacy Notice:</strong> Your data export will be prepared securely and made available for download. The download link will expire after 7 days for your security.
        </div>
        
        <div class="text-center">
            <?php if ($requests_remaining > 0): ?>
            <button type="button" class="btn btn-download-primary" id="downloadBtn">
                <i class="bi bi-download me-2"></i>Submit Export Request
            </button>
            <?php else: ?>
            <button type="button" class="btn btn-download-primary" id="downloadBtn" disabled>
                <i class="bi bi-x-circle me-2"></i>Request Limit Reached
            </button>
            <?php endif; ?>
            <button type="button" class="btn btn-download-secondary ms-2">
                <i class="bi bi-question-circle me-2"></i>Learn More
            </button>
        </div>
        
        <!-- Progress Bar -->
        <div class="download-progress" id="downloadProgress">
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
            </div>
            <p class="text-center mt-2 text-muted">Preparing your data export...</p>
        </div>
    </div>
    
    <!-- Additional Information -->
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="bi bi-shield-check me-2 text-success"></i>Data Security & Privacy
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <h6>What is included:</h6>
                    <ul class="small text-muted">
                        <li>All data associated with your account</li>
                        <li>Historical records and activity logs</li>
                        <li>Your preferences and settings</li>
                        <li>Content you have created or uploaded</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>What is NOT included:</h6>
                    <ul class="small text-muted">
                        <li>Password hashes or security tokens</li>
                        <li>Internal system identifiers</li>
                        <li>Other users' private information</li>
                        <li>Deleted content (per our retention policy)</li>
                    </ul>
                </div>
            </div>
            <hr>
            <p class="small text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>
                For questions about your data export, please contact our support team. We comply with GDPR, CCPA, and other data protection regulations.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Update download button state
    function updateDownloadButton() {
        const checkedCount = document.querySelectorAll(".toggle-switch input:checked").length;
        const downloadBtn = document.getElementById("downloadBtn");
        if (downloadBtn && !downloadBtn.hasAttribute("disabled")) {
            downloadBtn.disabled = checkedCount === 0;
        }
        updateSummary();
    }
    
    // Update summary statistics
    function updateSummary() {
        const checkedCategories = document.querySelectorAll(".toggle-switch input:checked");
        const categoryCountEl = document.getElementById("categoryCount");
        const estimatedSizeEl = document.getElementById("estimatedSize");
        
        if (categoryCountEl) {
            categoryCountEl.textContent = checkedCategories.length;
        }
        
        // Estimate file size (mock calculation)
        let estimatedSize = 0;
        checkedCategories.forEach(cat => {
            estimatedSize += Math.floor(Math.random() * 500) + 100; // Mock size
        });
        
        if (estimatedSizeEl) {
            if (estimatedSize < 1000) {
                estimatedSizeEl.textContent = estimatedSize + " KB";
            } else {
                estimatedSizeEl.textContent = (estimatedSize / 1000).toFixed(1) + " MB";
            }
        }
    }
    
    // Select/Deselect all functionality
    document.getElementById("selectAll").addEventListener("click", function(e) {
        e.preventDefault();
        const allToggles = document.querySelectorAll(".toggle-switch input[type='checkbox']");
        allToggles.forEach(toggle => {
            toggle.checked = true;
        });
        updateDownloadButton();
    });
    
    document.getElementById("deselectAll").addEventListener("click", function(e) {
        e.preventDefault();
        const allToggles = document.querySelectorAll(".toggle-switch input[type='checkbox']");
        allToggles.forEach(toggle => {
            toggle.checked = false;
        });
        updateDownloadButton();
    });
    
    // Add change listeners to toggles using event delegation
    document.addEventListener("change", function(e) {
        if (e.target.closest(".toggle-switch")) {
            updateDownloadButton();
        }
    });
    
    // Format selection
    const formatOptions = document.querySelectorAll(".format-option");
    formatOptions.forEach(option => {
        option.addEventListener("click", function() {
            formatOptions.forEach(opt => opt.classList.remove("selected"));
            this.classList.add("selected");
            document.getElementById("selectedFormat").value = this.dataset.format;
        });
    });
    
    // Download button handler
    const downloadBtn = document.getElementById("downloadBtn");
    if (downloadBtn) {
        downloadBtn.addEventListener("click", function() {
            const selectedFormat = document.getElementById("selectedFormat").value;
            const selectedCategories = Array.from(document.querySelectorAll(".toggle-switch input:checked"))
                .map(input => input.name);
            const notificationMethod = document.querySelector("input[name='notificationMethod']:checked").value;
            
            if (selectedCategories.length === 0) {
                alert("Please select at least one data category to download.");
                return;
            }
            
            // Show progress bar
            document.getElementById("downloadProgress").style.display = "block";
            const progressBar = document.querySelector(".progress-bar");
            
            // Simulate progress (in real implementation, this would be a form submission)
            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                progressBar.style.width = progress + "%";
                progressBar.textContent = progress + "%";
                
                if (progress >= 100) {
                    clearInterval(interval);
                    // In real implementation, this would submit a form to the server
                    setTimeout(() => {
                        alert("Your data export request has been submitted for review. You will be notified once your data is ready (24-48 hours).");
                        document.getElementById("downloadProgress").style.display = "none";
                        progressBar.style.width = "0%";
                        
                        // In real implementation, submit form to server
                        // document.getElementById("dataExportForm").submit();
                    }, 500);
                }
            }, 200);
        });
    }
    
    // Initialize
    updateDownloadButton();
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();