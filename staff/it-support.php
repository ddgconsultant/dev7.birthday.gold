<?php
// IT Support Portal for Staff
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Staff access is handled by site-controller.php
// This page is in /staff/ directory - accessible to staff and admin users

$pagetitle = "IT Support Portal";
$additionalstyles = '
<style>
/* Hide skip to main content link */
.sr-only, .sr-only-focusable:not(:focus) {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0,0,0,0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

/* Add bottom margin to body */
body { 
    margin-bottom: 100px !important; 
    padding-bottom: 50px !important; 
}

/* Card hover effects */
.support-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.support-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Icon styling */
.card-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

/* Status badges */
.status-badge {
    font-size: 0.875rem;
    padding: 0.25rem 0.75rem;
}

/* Quick links */
.quick-link {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 0.25rem;
    margin-bottom: 0.5rem;
    text-decoration: none;
    color: #333;
    transition: background 0.2s;
}
.quick-link:hover {
    background: #e9ecef;
    text-decoration: none;
    color: #333;
}
.quick-link i {
    width: 30px;
    text-align: center;
    margin-right: 10px;
}
</style>
';

// Get current user for ticket creation
$staff_user = $current_user_data;

// Handle ticket submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_ticket') {
        // Handle IT ticket creation
        $ticket_type = $_POST['ticket_type'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? 'normal';
        
        // Generate unique ticket number
        $ticket_number = 'IT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        try {
            // Save ticket to universal tickets table
            $sql = "INSERT INTO bg_tickets 
                    (ticket_number, user_id, ticket_type, ticket_category, priority, subject, description, status, created_dt) 
                    VALUES 
                    (:ticket_number, :user_id, 'it_support', :ticket_category, :priority, :subject, :description, 'open', NOW())";
            
            $database->query($sql, [
                'ticket_number' => $ticket_number,
                'user_id' => $staff_user['user_id'],
                'ticket_category' => $ticket_type,  // bug, feature, access, etc.
                'priority' => $priority,
                'subject' => $subject,
                'description' => $description
            ]);
            
            $ticket_id = $database->lastInsertId();
            
            $message = "Support ticket created successfully! Ticket #{$ticket_number}";
            $message_type = 'success';
            
            // Send notification to IT team via Rocket.Chat
            if (isset($system) && method_exists($system, 'postToRocketChat')) {
                $rocket_message = "🎫 **New IT Support Ticket #{$ticket_number}**\n";
                $rocket_message .= "**From:** {$staff_user['profile_first_name']} {$staff_user['profile_last_name']}\n";
                $rocket_message .= "**Type:** {$ticket_type}\n";
                $rocket_message .= "**Priority:** " . strtoupper($priority) . "\n";
                $rocket_message .= "**Subject:** {$subject}\n";
                $rocket_message .= "**Description:** {$description}\n\n";
                $rocket_message .= "View ticket: https://dev7.birthday.gold/staff/it-support?ticket={$ticket_number}";
                
                $system->postToRocketChat($rocket_message, "#BG-Technical");
            }
        } catch (Exception $e) {
            $message = "Error creating ticket. Please try again or contact IT directly.";
            $message_type = 'danger';
            error_log("IT Support Ticket Error: " . $e->getMessage());
        }
    } elseif ($_POST['action'] === 'hardware_request') {
        // Handle hardware request
        $hardware_type = $_POST['hardware_type'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $notes = $_POST['hardware_notes'] ?? '';
        
        try {
            // Create a ticket for the hardware request
            $ticket_number = 'HW-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Save to universal tickets table
            $sql = "INSERT INTO bg_tickets 
                    (ticket_number, user_id, ticket_type, ticket_category, priority, subject, description, status, created_dt, metadata) 
                    VALUES 
                    (:ticket_number, :user_id, 'hardware_request', :hardware_type, 'normal', :subject, :description, 'open', NOW(), :metadata)";
            
            // Store hardware-specific data in metadata JSON field
            $metadata = json_encode([
                'hardware_type' => $hardware_type,
                'reason' => $reason,
                'notes' => $notes
            ]);
            
            $database->query($sql, [
                'ticket_number' => $ticket_number,
                'user_id' => $staff_user['user_id'],
                'hardware_type' => $hardware_type,
                'subject' => "Hardware Request: {$hardware_type}",
                'description' => "Hardware Type: {$hardware_type}\nReason: {$reason}\nNotes: {$notes}",
                'metadata' => $metadata
            ]);
            
            $ticket_id = $database->lastInsertId();
            
            $message = "Hardware request submitted successfully! Request #{$ticket_number}";
            $message_type = 'success';
            
            // Send notification
            if (isset($system) && method_exists($system, 'postToRocketChat')) {
                $rocket_message = "🖥️ **New Hardware Request #{$ticket_number}**\n";
                $rocket_message .= "**From:** {$staff_user['profile_first_name']} {$staff_user['profile_last_name']}\n";
                $rocket_message .= "**Hardware:** {$hardware_type}\n";
                $rocket_message .= "**Reason:** {$reason}\n";
                if ($notes) {
                    $rocket_message .= "**Notes:** {$notes}\n";
                }
                
                $system->postToRocketChat($rocket_message, "#BG-Technical");
            }
        } catch (Exception $e) {
            $message = "Error submitting hardware request. Please contact IT directly.";
            $message_type = 'danger';
            error_log("Hardware Request Error: " . $e->getMessage());
        }
    }
}

// Include page components
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container my-5 pt-3">
    <div class="row">
        <div class="col-12">
            <h1><i class="fas fa-headset"></i> IT Support Portal</h1>
            <p class="lead">Get technical support, request hardware, access documentation, and learn about our systems.</p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'warning' ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Action Cards -->
    <div class="row mt-4">
        <div class="col-md-3 mb-4">
            <div class="card support-card h-100">
                <div class="card-body text-center">
                    <div class="card-icon text-primary">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h5 class="card-title">Submit Ticket</h5>
                    <p class="card-text">Report issues or request assistance</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ticketModal">
                        Create Ticket
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card support-card h-100">
                <div class="card-body text-center">
                    <div class="card-icon text-success">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h5 class="card-title">Hardware Request</h5>
                    <p class="card-text">Request new equipment or replacements</p>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hardwareModal">
                        Request Hardware
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card support-card h-100">
                <div class="card-body text-center">
                    <div class="card-icon text-info">
                        <i class="fas fa-book"></i>
                    </div>
                    <h5 class="card-title">Documentation</h5>
                    <p class="card-text">Access guides and system documentation</p>
                    <a href="https://docs.birthdaygold.cloud" target="_blank" class="btn btn-info">
                        View Docs
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card support-card h-100">
                <div class="card-body text-center">
                    <div class="card-icon text-warning">
                        <i class="fas fa-server"></i>
                    </div>
                    <h5 class="card-title">System Status</h5>
                    <p class="card-text">Check current system status</p>
                    <span class="badge bg-success status-badge">All Systems Operational</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="row mt-4">
        <!-- Left Column - Quick Links & Resources -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-link"></i> Quick Links & Resources</h5>
                </div>
                <div class="card-body">
                    <h6>Development Tools</h6>
                    <a href="https://github.com/birthdaygold" target="_blank" class="quick-link">
                        <i class="fab fa-github"></i> GitHub Repository
                    </a>
                    <a href="https://chat.birthdaygold.cloud" target="_blank" class="quick-link">
                        <i class="fas fa-comments"></i> Rocket.Chat
                    </a>
                    <a href="https://my.birthdaygold.cloud" target="_blank" class="quick-link">
                        <i class="fas fa-cloud"></i> Cloudron Dashboard
                    </a>
                    
                    <h6 class="mt-3">Monitoring & Analytics</h6>
                    <a href="https://status.birthdaygold.cloud" target="_blank" class="quick-link">
                        <i class="fas fa-heartbeat"></i> Uptime Monitoring
                    </a>
                    <a href="https://metabase.birthdaygold.cloud" target="_blank" class="quick-link">
                        <i class="fas fa-chart-bar"></i> Metabase Analytics
                    </a>
                    
                    <h6 class="mt-3">Documentation</h6>
                    <a href="https://docs.birthdaygold.cloud" target="_blank" class="quick-link">
                        <i class="fas fa-book-open"></i> Internal Documentation
                    </a>
                    <a href="/staff/redirect_legalpolicyeditor" class="quick-link">
                        <i class="fas fa-gavel"></i> Legal Policy Editor
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Column - How Things Work -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> How Birthday Gold Works</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="howItWorksAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                    User Flow
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#howItWorksAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>User creates account with birthday</li>
                                        <li>Selects companies to enroll in</li>
                                        <li>System automates enrollment</li>
                                        <li>User receives birthday rewards</li>
                                        <li>Notifications sent before birthdays</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                    System Architecture
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#howItWorksAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li><strong>Frontend:</strong> Bootstrap 5, jQuery</li>
                                        <li><strong>Backend:</strong> PHP 7.4+, Custom MVC</li>
                                        <li><strong>Database:</strong> MySQL</li>
                                        <li><strong>Infrastructure:</strong> Cloudron, Ubuntu</li>
                                        <li><strong>CDN:</strong> Backblaze B2 + CloudFlare</li>
                                        <li><strong>Payments:</strong> Stripe</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                    Key Directories
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#howItWorksAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li><code>/core/</code> - Framework & classes</li>
                                        <li><code>/admin/</code> - Admin interface</li>
                                        <li><code>/staff/</code> - Staff portal</li>
                                        <li><code>/myaccount/</code> - User dashboard</li>
                                        <li><code>/api/</code> - API endpoints</li>
                                        <li><code>/admin_actions/</code> - Automation scripts</li>
                                        <li><code>/public/</code> - Static assets</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                                    Common Tasks
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#howItWorksAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li><strong>Deploy to Production:</strong> <code>./admin_actions/deploy_www.sh -s dev7</code></li>
                                        <li><strong>View Logs:</strong> Check Rocket.Chat #BG-Technical</li>
                                        <li><strong>Database Access:</strong> Via phpMyAdmin or MySQL CLI</li>
                                        <li><strong>Clear Cache:</strong> CDN purge via CloudFlare</li>
                                        <li><strong>Monitor Performance:</strong> Metabase dashboards</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Updates Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Recent System Updates</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Legal Policy Review System</h6>
                                <small class="text-muted">Today</small>
                            </div>
                            <p class="mb-1">Added automated review reminders and policy editor for legal team.</p>
                            <small class="text-muted">Access via Staff Portal → Legal Policy Editor</small>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Voice Assistant Integration</h6>
                                <small class="text-muted">This Week</small>
                            </div>
                            <p class="mb-1">Google Assistant and Alexa support for checking enrollments.</p>
                            <small class="text-muted">Users can link accounts in My Account → Settings</small>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">ABO System Enhancement</h6>
                                <small class="text-muted">This Month</small>
                            </div>
                            <p class="mb-1">Improved automated business onboarding with AI validation.</p>
                            <small class="text-muted">Documentation available in internal docs</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Ticket Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Support Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_ticket">
                    
                    <div class="mb-3">
                        <label for="ticket_type" class="form-label">Ticket Type</label>
                        <select class="form-control" id="ticket_type" name="ticket_type" required>
                            <option value="">Select Type...</option>
                            <option value="bug">Bug Report</option>
                            <option value="feature">Feature Request</option>
                            <option value="access">Access Issue</option>
                            <option value="performance">Performance Problem</option>
                            <option value="data">Data Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-control" id="priority" name="priority">
                            <option value="low">Low - Can wait</option>
                            <option value="normal" selected>Normal - Soon please</option>
                            <option value="high">High - Urgent</option>
                            <option value="critical">Critical - System down</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                        <small class="text-muted">Please include steps to reproduce if reporting a bug</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hardware Request Modal -->
<div class="modal fade" id="hardwareModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hardware Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="hardware_request">
                    
                    <div class="mb-3">
                        <label for="hardware_type" class="form-label">Hardware Type</label>
                        <select class="form-control" id="hardware_type" name="hardware_type" required>
                            <option value="">Select Type...</option>
                            <option value="laptop">Laptop</option>
                            <option value="monitor">Monitor</option>
                            <option value="keyboard">Keyboard</option>
                            <option value="mouse">Mouse</option>
                            <option value="headset">Headset</option>
                            <option value="phone">Phone</option>
                            <option value="other">Other (specify)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Request</label>
                        <select class="form-control" id="reason" name="reason" required>
                            <option value="">Select Reason...</option>
                            <option value="new_employee">New Employee</option>
                            <option value="replacement">Replacement (broken/old)</option>
                            <option value="upgrade">Upgrade Needed</option>
                            <option value="additional">Additional Equipment</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hardware_notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="hardware_notes" name="hardware_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>